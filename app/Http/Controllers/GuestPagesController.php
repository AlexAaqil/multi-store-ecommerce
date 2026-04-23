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
        $query = Shop::with('category')->where('is_active', true);
        $query->orderBy('is_verified', 'desc');
        
        if ($request->has('category') && $request->category !== 'All') {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', $request->category);
            });
        }
        
        $query->inRandomOrder();
        $shops = $query->limit(4)->get();
        
        if ($shops->count() < 4) {
            $additional = Shop::where('is_active', true)
                ->whereNotIn('id', $shops->pluck('id'))
                ->inRandomOrder()
                ->limit(4 - $shops->count())
                ->get();
            $shops = $shops->concat($additional);
        }

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
        
        $active_discounts = Discount::active()->with('shop')->get()->groupBy('shop_id');
        
        $flash_sales = $this->product_deals_service->getSmallDiscounts($active_discounts, 40, 4);
        $clearance_sales = $this->product_deals_service->getBigDiscounts($active_discounts, 40, 3);
        $hot_deals = $this->product_deals_service->getRandomDiscountedProducts($active_discounts, 3);
        
        return inertia('guest/homepage/Home', [
            'featured_shops' => $featured_shops,
            'flash_sales' => $flash_sales,
            'clearance_sales' => $clearance_sales,
            'hot_deals' => $hot_deals,
            'total_shops' => Shop::where('is_active', true)->count(),
            'total_products' => Product::where('is_active', true)->count(),
            'total_shoppers' => 0,
            'shop_categories' => $shop_categories,
        ]);
    }

    public function shopDetails($slug)
    {
        $shop = Shop::with(['category', 'owner'])
            ->where('custom_slug', $slug)
            ->orWhere('slug', $slug)
            ->firstOrFail();

        // Load discounts once for this shop
        $shop_discounts = Discount::active()
            ->forShop($shop->id)
            ->get();

        // Get products and transform using the service
        $products = $shop->products()
            ->with('category')
            ->where('is_active', true)
            ->latest()
            ->paginate(12)
            ->through(function ($product) use ($shop_discounts) {
                // Service handles everything - with or without discount
                return $this->product_deals_service->transformProduct($product, $shop_discounts);
            });
        
        $shop_stats = [
            'total_products' => $shop->products()->count(),
            'total_sales' => 0,
            'total_reviews' => 0,
            'average_rating' => 0,
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
        
        // Load discounts once for this shop
        $shop_discounts = Discount::active()
            ->forShop($product->shop_id)
            ->get();
        
        // Use the service to transform the product
        $transformed = $this->product_deals_service->transformProduct($product, $shop_discounts);
        
        return inertia('guest/products/ProductDetails', [
            'product' => array_merge($transformed, [
                'sku' => $product->sku,
                'description' => $product->description,
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
            ]),
            'reviews' => [],
            'related_products' => [],
        ]);
    }

    public function dealsAndOffersPage(Request $request)
    {
        $product_categories = ProductCategory::orderBy('name')->get();

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

    public function discoverShops(Request $request)
    {
        $query = Shop::with('category')->where('is_active', true);

        if ($request->filled('search')) {
            $query->search($request->search);
        }
        
        $shops = $query->orderBy('name')
            ->paginate(20)
            ->through(function ($shop) {
                return [
                    'id' => $shop->id,
                    'name' => $shop->name,
                    'slug' => $shop->public_slug,
                    'category' => $shop->category?->name,
                    'description' => $shop->description,
                    'contact_email' => $shop->contact_email,'contact_phone' => $shop->contact_phone,
                    'rating' => 4.9,
                    'reviews_count' => 312,
                    'logo_image' => $shop->logo_url_full,
                    'cover_image' => $shop->cover_url_full,
                    'is_active' => $shop->is_active,
                    'is_verified' => $shop->is_verified,
                    'created_at' => $shop->created_at
                ];
            });

        return inertia('guest/shops/DiscoverShops', [
            'shops' => $shops,
            'filters' => $request->only(['search'])
        ]);
    }
}