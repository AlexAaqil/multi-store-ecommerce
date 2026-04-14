<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shops\ShopCategory;
use App\Models\Shops\Shop;
use App\Models\Products\ProductCategory;
use App\Models\Products\Product;
use App\Models\Products\Discount;
use App\Services\DiscountService;
use App\Services\ProductDealService;

class GuestPagesController extends Controller
{
    public function __construct(
        protected DiscountService $discount_service,
        protected ProductDealService $product_deals_service
    ) {}
    
    public function homePage(Request $request)
    {
        // Get active shops for featured section
        $query = Shop::with('category')->where('is_active', true);
        
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

        $shop_categories = ShopCategory::orderBy('name')->get();
        
        // Get all active discounts grouped by shop
        $active_discounts = Discount::active()->with('shop')->get()->groupBy('shop_id');
        
        // Get flash sales (discount < 40%)
        $flash_sales = $this->product_deals_service->getSmallDiscounts($active_discounts, 40, 4);
        
        // Get clearance sales (discount >= 40%)
        $clearance_sales = $this->product_deals_service->getBigDiscounts($active_discounts, 40, 3);

        $hot_deals = $this->product_deals_service->getRandomDiscountedProducts($active_discounts, 3);
        
        return inertia('guest/homepage/Home', [
            'featured_shops' => $featured_shops,
            'flash_sales' => $flash_sales,
            'clearance_sales' => $clearance_sales,
            'hot_deals' => $hot_deals,
            'total_shops' => Shop::where('is_active', true)->count(),
            'total_products' => Product::where('is_active', true)->count(),
            'total_shoppers' => 000,
            'shop_categories' => $shop_categories,
        ]);
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
                $discount = $this->discount_service->resolveForProduct($product, $shop_discounts);
                $discounted_price = $discount ? $this->discount_service->calculateDiscountedPrice($product->price, $discount) : null;
                $percentage_off   = $discount ? $this->discount_service->calculatePercentageOff($product->price, $discount) : null;
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
        
        $discount = $this->discount_service->resolveForProduct($product, $shop_discounts);
        $discounted_price = $discount ? $this->discount_service->calculateDiscountedPrice($product->price, $discount) : null;
        $percentage_off = $discount ? $this->discount_service->calculatePercentageOff($product->price, $discount) : null;
        
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
        $product_categories = ProductCategory::orderBy('name')->get();

        // Get all active discounts grouped by shop
        $active_discounts = Discount::active()->with('shop')->get()->groupBy('shop_id');

        if ($active_discounts->isEmpty()) {
            return inertia('guest/dealspage/Deals', [
                'deals' => [],
                'total' => 0,
                'product_categories' => $product_categories,
                'flash_sales' => [],
                'clearance_sales' => []
            ]);
        }

        $all_deals = $this->product_deals_service->getAllDiscountedProducts($active_discounts);

        $flash_sales = $this->product_deals_service->getSmallDiscounts($active_discounts, 40, 4);
        
        $clearance_sales = $this->product_deals_service->getBigDiscounts($active_discounts, 40, 4);

        return inertia('guest/dealspage/Deals', [
            'deals' => $all_deals,
            'total' => $all_deals->count(),
            'product_categories' => $product_categories,
            'flash_sales' => $flash_sales,
            'clearance_sales' => $clearance_sales
        ]);
    }

    public function about()
    {
        return 'about-page';
    }
}