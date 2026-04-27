<?php

namespace App\Http\Controllers\Shops;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Shops\Shop;
use App\Models\Shops\ShopCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Requests\Shops\ShopRequest;
use App\Models\Products\Discount;
use App\Services\DiscountService;
use App\Services\ProductDealService;

class ShopController extends Controller
{
    public function __construct(
        protected DiscountService $discount_service,
        protected ProductDealService $product_deals_service
    ) {}

    protected function user(): User
    {
        return Auth::user();
    }

    public function index()
    {
        $shops = $this->user()->shops()
            ->with('category')
            ->latest()
            ->paginate(10)
            ->through(function ($shop) {
                return [
                    'id' => $shop->id,
                    'name' => $shop->name,
                    'slug' => $shop->public_slug,
                    'category' => $shop->category?->name,
                    'description' => $shop->description,
                    'contact_email' => $shop->contact_email,
                    'contact_phone' => $shop->contact_phone,
                    'rating' => 4.9, // TODO Replace with actual rating when available
                    'reviews_count' => 312, // TODO Replace with actual count when available
                    'logo_image' => $shop->logo_url_full,
                    'cover_image' => $shop->cover_url_full,
                    'status' => $shop->is_active ? 'Open' : 'Closed',
                    'status_class' => $shop->is_active ? 'badge-green' : 'badge-gray',
                    'is_active' => $shop->is_active,
                    'is_verified' => $shop->is_verified,
                    'created_at' => $shop->created_at
                ];
            });

        return inertia('shops/Index', [
            'shops' => $shops,
            'hasShop' => $this->user()->shops()->exists()
        ]);
    }

    public function create()
    {
        // Redirect if user already has a shop
        if ($this->user()->shops()->exists()) {
            return redirect()->route('shops.index')->with([
                'message' => 'You already have a shop. Only one shop is allowed per account.',
                'type' => 'error'
            ]);
        }

        $categories = ShopCategory::select('id', 'name')->get();

        return inertia('shops/Create', [
            'categories' => $categories
        ]);
    }

    public function store(ShopRequest $request)
    {
        if ($this->user()->shops()->exists()) {
            return redirect()->route('shops.index')->with([
                'message' => 'You already have a shop. Only one shop is allowed per account.',
                'type' => 'error'
            ]);
        }

        $validated = $request->validated();

        // Handle custom slug
        $customSlug = $request->custom_slug ? Str::slug($request->custom_slug) : null;

        $shop = $this->user()->shops()->create([
            'name' => $validated['name'],
            'custom_slug' => $customSlug,
            'description' => $validated['description'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'shop_category_id' => $validated['shop_category_id'] ?? null
        ]);
        
        // Handle logo upload with custom name
        if ($request->hasFile('logo')) {
            $logo_path = $this->uploadImage($request->file('logo'), 'logo', $shop);
            $shop->update(['logo_image' => $logo_path]);
        }
        
        // Handle cover upload with custom name
        if ($request->hasFile('cover')) {
            $cover_path = $this->uploadImage($request->file('cover'), 'cover', $shop);
            $shop->update(['cover_image' => $cover_path]);
        }

        return redirect()->route('shops.index')->with([
            'message' => "Shop {$shop->name} created successfully",
            'type' => 'success'
        ]);
    }

    public function show (Shop $shop) {
        if ($shop->owner_id !== $this->user()->id) {
            abort(403);
        }

        $shop_discounts = Discount::active()
            ->forShop($shop->id)
            ->get();

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

        return inertia('shops/Show', [
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

    public function edit(Shop $shop)
    {
        // Ensure user owns this shop
        if ($shop->owner_id !== $this->user()->id) {
            abort(403);
        }

        $shop->load('category');

        $categories = ShopCategory::select('id', 'name')->get();

        $shop_data = [
            'id' => $shop->id,
            'name' => $shop->name,
            'description' => $shop->description,
            'logo_image' => $shop->logo_url_full,  // Use the accessor for full URL
            'cover_image' => $shop->cover_url_full,  // Use the accessor for full URL
            'contact_email' => $shop->contact_email,
            'contact_phone' => $shop->contact_phone,
            'is_active' => $shop->is_active,
            'category' => $shop->category ? [
                'id' => $shop->category->id,
                'name' => $shop->category->name,
            ] : null,
        ];
        
        return inertia('shops/Edit', [
            'shop' => $shop_data,
            'categories' => $categories
        ]);
    }

    public function update(ShopRequest $request, Shop $shop)
    {
        // Ensure user owns this shop
        if ($shop->owner_id !== $this->user()->id) {
            abort(403);
        }
        
        $validated = $request->validated();

        // Handle custom slug update
        if ($request->has('custom_slug')) {
            $customSlug = $request->custom_slug ? Str::slug($request->custom_slug) : null;
            $validated['custom_slug'] = $customSlug;
        }
        
        // Handle logo upload with custom name
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($shop->logo_image) {
                $old_path = "shops/logos/{$shop->logo_image}";
                if (Storage::disk('public')->exists($old_path)) {
                    Storage::disk('public')->delete($old_path);
                }
            }
            $logo_path = $this->uploadImage($request->file('logo'), 'logo', $shop);
            $validated['logo_image'] = $logo_path;
        }
        
        // Handle cover upload with custom name
        if ($request->hasFile('cover')) {
            // Delete old cover if exists
            if ($shop->cover_image) {
                $old_path = "shops/covers/{$shop->cover_image}";
                if (Storage::disk('public')->exists($old_path)) {
                    Storage::disk('public')->delete($old_path);
                }
            }
            $cover_path = $this->uploadImage($request->file('cover'), 'cover', $shop);
            $validated['cover_image'] = $cover_path;
        }
        
        $shop->update($validated);

        return redirect()->route('shops.index')->with([
            'message' => "Shop {$shop->name} updated successfully",
            'type' => 'success'
        ]);
    }

    public function destroy(Shop $shop)
    {
        // Ensure user owns this shop
        if ($shop->owner_id !== $this->user()->id) {
            abort(403);
        }
        
        // Delete associated images using the model method
        $shop->deleteImages();
        
        $shop->delete();

        return redirect()->back()->with([
            'message' => 'Shop deleted successfully',
            'type' => 'success'
        ]);
    }

    private function uploadImage($file, string $type, Shop $shop): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('dmY_His');
        $slug = Str::slug($shop->name, '_');

        $filename = "{$slug}_{$type}_{$shop->id}_{$timestamp}.{$extension}";

        // Determine directory
        $directory = $type === 'logo' ? 'shops/logos' : 'shops/covers';
        
        // Store the file and get the path
        $path = $file->storeAs($directory, $filename, 'public');
        
        // Return the file name
        return $filename;
    }

    public function getAllShops(Request $request)
    {
        $query = Shop::with('category');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('contact_email', 'like', "%{$search}%")
                ->orWhere('contact_phone', 'like', "%{$search}%");
            });
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

        return inertia('shops/AllShops', [
            'shops' => $shops,
            'filters' => $request->only(['search'])
        ]);
    }
}