<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shops\Shop;
use App\Models\Products\Product;

class GuestPagesController extends Controller
{
    public function homePage(Request $request)
    {
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
        
        // Random ordering for variety (or use latest() for newest first)
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
                'rating' => 4.9, // TODO Replace with actual rating when available
                'reviews_count' => 312, // TODO Replace with actual count when available
                'logo_image' => $shop->logo_url_full,
                'cover_image' => $shop->cover_url_full,
                'status' => $shop->is_active ? 'Open' : 'Closed',
                'status_class' => $shop->is_active ? 'badge-green' : 'badge-gray',
                'is_active' => $shop->is_active,
                'is_verified' => $shop->is_verified,
            ];
        });
        
        return inertia('guest/homepage/Home', [
            'featured_shops' => $featured_shops,
            'total_shops' => Shop::where('is_active', true)->count(),
            'total_products' => Product::where('is_active', true)->count(),
            'total_shoppers' => 000
        ]);
    }

    public function shopDetails($slug)
    {
        $shop = Shop::with(['category', 'owner'])
            ->where('custom_slug', $slug)
            ->orWhere('slug', $slug)
            ->firstOrFail();

                // Get products for this shop
        $products = $shop->products()
            ->with('category')
            ->where('is_active', true)
            ->latest()
            ->paginate(12)
            ->through(function ($product) {
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
                ];
            });
        
        // Get shop statistics
        $shop_stats = [
            'total_products' => $shop->products()->count(),
            // 'total_sales' => $shop->orders()->where('payment_status', 1)->count(),
            // 'total_reviews' => $shop->reviews()->count(),
            // 'average_rating' => $shop->reviews()->avg('rating') ?? 0,
            'total_sales' => 000,
            'total_reviews' => 000,
            'average_rating' => 000,
            'response_rate' => 98, // You can calculate this later
            'response_time' => 'within 24 hours', // You can calculate this later
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
            ->orWhere('slug', $slug)
            ->firstOrFail();
        
        return inertia('guest/products/ProductDetails', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'price' => $product->price,
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

    public function dealsAndOffersPage()
    {
        return inertia('guest/dealspage/Deals');
    }

    public function about()
    {
        return 'about-page';
    }
}
