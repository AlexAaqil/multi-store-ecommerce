<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shops\ShopCategory;
use App\Models\Shops\Shop;
use App\Models\Products\ProductCategory;
use App\Models\Products\Product;
use App\Models\Products\Discount;
use App\Services\DiscountService;

class GuestPagesController extends Controller
{
    public function __construct(protected DiscountService $discountService) {}
    
    public function homePage(Request $request)
    {
        // Get active shops for featured section
        $query = Shop::with('category')
            ->where('is_active', true);
        
        // Prioritize verified shops
        $query->orderBy('is_verified', 'desc');
        
        // Optional: Filter by category if requested
        if ($request->has('category') && $request->category !== 'All') {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', $request->category);
            });
        }
        
        // Random ordering for variety
        $query->inRandomOrder();
        
        $shops = $query->limit(4)->get();
        
        // If not enough shops, get more from active but not verified
        if ($shops->count() < 4) {
            $additional = Shop::where('is_active', true)
                ->whereNotIn('id', $shops->pluck('id'))
                ->inRandomOrder()
                ->limit(4 - $shops->count())
                ->get();
            
            $shops = $shops->concat($additional);
        }

        // Transform shops to include full image URLs
        $featured_shops = $shops->map(function ($shop) {
            return [
                'id' => $shop->id,
                'name' => $shop->name,
                'slug' => $shop->public_slug,
                'category' => $shop->category?->name,
                'rating' => 4.9,
                'reviews_count' => 312,
                'logo_image' => $shop->logo_url_full,
                'cover_image' => $shop->cover_url_full,
                'status' => $shop->is_active ? 'Open' : 'Closed',
                'status_class' => $shop->is_active ? 'badge-green' : 'badge-gray',
                'is_active' => $shop->is_active,
                'is_verified' => $shop->is_verified,
            ];
        });
        
        // Get all active discounts grouped by shop
        $activeDiscounts = Discount::active()
            ->with('shop')
            ->get()
            ->groupBy('shop_id');
        
        // Get flash sales (discount < 40%)
        $flashSales = $this->getDealsByDiscountThreshold($activeDiscounts, 40, 'less', 4);
        
        // Get clearance items (discount >= 40%)
        $clearanceItems = $this->getDealsByDiscountThreshold($activeDiscounts, 40, 'greater', 4);

        $shop_categories = ShopCategory::orderBy('name')->get();

        // After getting $activeDiscounts, just get products from those shops and limit to 3
        $hot_deals = collect();

        if ($activeDiscounts->isNotEmpty()) {
            $hot_deals = Product::with(['images', 'category', 'shop'])
                ->whereIn('shop_id', $activeDiscounts->keys())
                ->where('is_active', true)
                ->where('stock_qty', '>', 0)
                ->inRandomOrder()
                ->limit(10) // Get more to account for products without discounts
                ->get()
                ->map(function ($product) use ($activeDiscounts) {
                    $shop_discounts = $activeDiscounts->get($product->shop_id, collect());
                    $discount = $this->discountService->resolveForProduct($product, $shop_discounts);

                    if (!$discount) return null;

                    $percentage_off = $this->discountService->calculatePercentageOff($product->price, $discount);

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'old_price' => (float) $product->price,
                        'percentage_off' => (int) $percentage_off,
                        'image_url' => $product->primary_image_url,
                        'shop_name' => $product->shop->name,
                    ];
                })
                ->filter() // Remove nulls
                ->values()
                ->take(3); // Take only 3
        }
        
        return inertia('guest/homepage/Home', [
            'featured_shops' => $featured_shops,
            'flash_sales' => $flashSales,
            'clearance_sales' => $clearanceItems,
            'total_shops' => Shop::where('is_active', true)->count(),
            'total_products' => Product::where('is_active', true)->count(),
            'total_shoppers' => 000,
            'shop_categories' => $shop_categories,
            'hot_deals' => $hot_deals
        ]);
    }
    
    /**
     * Get deals based on discount percentage threshold
     * 
     * @param \Illuminate\Support\Collection $activeDiscounts Discounts grouped by shop_id
     * @param int $threshold Percentage threshold
     * @param string $comparison 'less' or 'greater'
     * @param int $limit Maximum number of deals to return
     */
    protected function getDealsByDiscountThreshold($activeDiscounts, int $threshold, string $comparison, int $limit = 4): array
    {
        if ($activeDiscounts->isEmpty()) {
            return [];
        }
        
        $shopIds = $activeDiscounts->keys();
        
        // Get products from shops with discounts
        $products = Product::with(['images', 'category', 'shop'])
            ->whereIn('shop_id', $shopIds)
            ->where('is_active', true)
            ->where('stock_qty', '>', 0)
            ->get();
        
        $deals = [];
        
        foreach ($activeDiscounts as $shopId => $shopDiscounts) {
            $shopProducts = $products->where('shop_id', $shopId);
            
            foreach ($shopProducts as $product) {
                $discount = $this->discountService->resolveForProduct($product, $shopDiscounts);
                
                if (!$discount) continue;
                
                $percentageOff = $this->discountService->calculatePercentageOff($product->price, $discount);
                
                // Filter by discount threshold
                if ($comparison === 'less' && $percentageOff >= $threshold) {
                    continue;
                }
                
                if ($comparison === 'greater' && $percentageOff < $threshold) {
                    continue;
                }
                
                $discountedPrice = $this->discountService->calculateDiscountedPrice($product->price, $discount);
                
                $deals[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $discountedPrice,
                    'old_price' => $product->price,
                    'discount_pct' => $percentageOff,
                    'discount_text' => $discount->type === Discount::TYPE_PERCENTAGE 
                        ? "{$discount->value}% OFF" 
                        : "KES {$discount->value} OFF",
                    'image_url' => $product->primary_image_url,
                    'shop_name' => $product->shop->name,
                    'shop_slug' => $product->shop->slug,
                    'expires_at' => $discount->expires_at,
                ];
                
                if (count($deals) >= $limit) {
                    break 2;
                }
            }
        }
        
        return $deals;
    }

    public function shopDetails($slug)
    {
        $shop = Shop::with(['category', 'owner'])
            ->where('custom_slug', $slug)
            ->orWhere('slug', $slug)
            ->firstOrFail();

        // Load active discounts for this shop ONCE
        $shop_discounts = Discount::active()
            ->forShop($shop->id)
            ->get();

        // Get products for this shop
        $products = $shop->products()
            ->with('category')
            ->where('is_active', true)
            ->latest()
            ->paginate(12)
            ->through(function ($product) use ($shop_discounts) {
                $discount = $this->discountService->resolveForProduct($product, $shop_discounts);
                $discounted_price = $discount ? $this->discountService->calculateDiscountedPrice($product->price, $discount) : null;
                $percentage_off   = $discount ? $this->discountService->calculatePercentageOff($product->price, $discount) : null;
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'cost_price' => $product->cost_price,
                    'stock_qty' => $product->stock_qty,
                    'category' => $product->category?->name,
                    'is_active' => $product->is_active,
                    'image_url' => $product->primary_image_url,
                    'created_at' => $product->created_at,
                    'discounted_price' => $discounted_price,
                    'percentage_off'   => $percentage_off,
                ];
            });
        
        // Get shop statistics
        $shop_stats = [
            'total_products' => $shop->products()->count(),
            'total_sales' => 000,
            'total_reviews' => 000,
            'average_rating' => 000,
            'response_rate' => 98,
            'response_time' => 'within 24 hours',
        ];
        
        return inertia('guest/shops/ShopDetails', [
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'slug' => $shop->public_slug,
                'description' => $shop->description,
                'logo_url' => $shop->logo_url_full,
                'cover_url' => $shop->cover_url_full,
                'contact_email' => $shop->contact_email,
                'contact_phone' => $shop->contact_phone,
                'is_active' => $shop->is_active,
                'is_verified' => $shop->is_verified,
                'category' => $shop->category?->name,
                'owner' => [
                    'name' => $shop->owner?->name,
                    'joined' => $shop->created_at->diffForHumans(),
                ],
                'stats' => $shop_stats,
                'created_at' => $shop->created_at,
            ],
            'products' => $products,
        ]);
    }

    public function productDetails($slug)
    {
        $product = Product::with(['category', 'shop', 'images'])
            ->where('slug', $slug)
            ->firstOrFail();
        
        // Get active discounts for this product
        $shop_discounts = Discount::active()
            ->forShop($product->shop_id)
            ->get();
        
        $discount = $this->discountService->resolveForProduct($product, $shop_discounts);
        $discounted_price = $discount ? $this->discountService->calculateDiscountedPrice($product->price, $discount) : null;
        $percentage_off = $discount ? $this->discountService->calculatePercentageOff($product->price, $discount) : null;
        
        return inertia('guest/products/ProductDetails', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'price' => $product->price,
                'discounted_price' => $discounted_price,
                'percentage_off' => $percentage_off,
                'description' => $product->description,
                'image_url' => $product->primary_image_url,
                'is_active' => $product->is_active,
                'stock_qty' => $product->stock_qty,
                'category' => $product->category?->name,
                'images' => $product->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'name' => $image->name,
                        'full_url' => $image->full_url,
                    ];
                }),
                'shop' => [
                    'id' => $product->shop->id,
                    'name' => $product->shop->name,
                    'slug' => $product->shop->slug,
                    'logo_url' => $product->shop->logo_url_full,
                    'is_verified' => $product->shop->is_verified,
                ]
            ],
            'reviews' => [],
            'related_products' => [],
        ]);
    }

    public function dealsAndOffersPage(Request $request)
    {
        // Get all active discounts grouped by shop
        $activeDiscounts = Discount::active()
            ->with('shop')
            ->get()
            ->groupBy('shop_id');

        if ($activeDiscounts->isEmpty()) {
            $product_categories = ProductCategory::orderBy('name')->get();
            
            return inertia('guest/dealspage/Deals', [
                'deals' => [],
                'total' => 0,
                'product_categories' => $product_categories,
                'flash_sales' => [],
                'clearance_sales' => []
            ]);
        }

        $shopIds = $activeDiscounts->keys();

        // Load products from those shops
        $query = Product::with(['images', 'category', 'shop'])
            ->whereIn('shop_id', $shopIds)
            ->where('is_active', true)
            ->where('stock_qty', '>', 0);

        if ($request->filled('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('shop')) {
            $query->whereHas('shop', fn($q) => $q->where('slug', $request->shop));
        }

        $products = $query->get();

        // Separate deals into flash and clearance
        $flashSales = [];
        $clearanceItems = [];
        $allDeals = [];

        foreach ($activeDiscounts as $shopId => $shopDiscounts) {
            $shop_products = $products->where('shop_id', $shopId);

            foreach ($shop_products as $product) {
                $discount = $this->discountService->resolveForProduct($product, $shopDiscounts);

                if (!$discount) continue;

                $percentageOff = $this->discountService->calculatePercentageOff($product->price, $discount);
                $discountedPrice = $this->discountService->calculateDiscountedPrice($product->price, $discount);
                
                $dealData = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'category' => $product->category?->name,
                    'price' => $discountedPrice,
                    'old_price' => $product->price,
                    'discount_pct' => $percentageOff,
                    'discount_text' => $discount->type === Discount::TYPE_PERCENTAGE 
                        ? "{$discount->value}% OFF" 
                        : "KES {$discount->value} OFF",
                    'image_url' => $product->primary_image_url,
                    'shop_name' => $product->shop->name,
                    'shop_slug' => $product->shop->slug,
                    'expires_at' => $discount->expires_at,
                ];
                
                // Separate based on discount percentage
                if ($percentageOff < 40) {
                    // Flash sales (discount less than 40%)
                    if (count($flashSales) < 4) {
                        $flashSales[] = $dealData;
                    }
                } else {
                    // Clearance items (discount 40% or more)
                    if (count($clearanceItems) < 4) {
                        $clearanceItems[] = $dealData;
                    }
                }
                
                $allDeals[] = $dealData;
            }
        }

        // Sort by highest percentage off first
        $sorted = collect($allDeals)->sortByDesc('discount_pct')->values();
        
        $product_categories = ProductCategory::orderBy('name')->get();

        return inertia('guest/dealspage/Deals', [
            'deals' => $sorted,
            'total' => $sorted->count(),
            'product_categories' => $product_categories,
            'flash_sales' => $flashSales,
            'clearance_sales' => $clearanceItems
        ]);
    }

    public function about()
    {
        return 'about-page';
    }
}