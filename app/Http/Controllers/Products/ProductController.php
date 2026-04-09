<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Shops\Shop;
use App\Models\Products\ProductCategory;
use App\Models\Products\Product;
use App\Http\Requests\Products\ProductRequest;

class ProductController extends Controller
{
    protected function user(): User
    {
        return Auth::user();
    }

    protected function getCurrentShop(): Shop
    {
        $shop = $this->user()->shops()->first();

        if (!$shop) {
            abort(403, 'You need to create a shop first!');
        }

        return $shop;
    }

    private function uploadImages($images, $product): void
    {
        $current_image_count = $product->images()->count();

        foreach ($images as $index => $image) {
            $extension = $image->getClientOriginalExtension();
            $slug = Str::slug($product->name);
            $timestamp = now()->format('Ymd_His');
            $random = Str::random(6);
            $new_index = $current_image_count + $index;
            
            // Format: {slug}_{product_id}_{index}_{timestamp}_{random}.extension
            $filename = "{$slug}_{$product->id}_{$new_index}_{$timestamp}_{$random}.{$extension}";

            // Store the file - this returns the path including 'products/'
            $path = $image->storeAs('products', $filename, 'public');
            
            $product->images()->create([
                'name' => $filename,
                'sort_order' => $index,
            ]);
        }
    }

    public function index(Request $request)
    {
        $shop = $this->getCurrentShop();

        $query = $shop->products()->with('category');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')
            ->paginate(10)
            ->through(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                    'cost_price' => $product->cost_price,
                    'price' => $product->price,
                    'stock_qty' => $product->stock_qty,
                    'category' => $product->category?->name,
                    'is_active' => $product->is_active,
                    'is_featured' => $product->is_featured,
                    'image_url' => $product->primary_image_url,
                    'created_at' => $product->created_at,
                ];
            });
        
        return inertia('products/Index', [
            'products' => $products,
            'shop' => $shop,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        $shop = $this->getCurrentShop();
        $categories = ProductCategory::select('id', 'name')->get();

        return inertia('products/Create', [
            'shop' => $shop,
            'categories' => $categories
        ]);
    }

    public function store(ProductRequest $request)
    {
        $shop = $this->getCurrentShop();

        DB::beginTransaction();

        try {
            $attributes = null;
            if ($request->attributes && is_array($request->attributes) && !empty($request->attributes)) {
                $attributes = json_encode($request->attributes);
            }

            $product = $shop->products()->create([
                'name' => $request->name,
                'sku' => $request->sku,
                'description' => $request->description,
                'cost_price' => $request->cost_price === '' ? null : $request->cost_price,
                'price' => $request->price,
                'stock_qty' => $request->stock_qty ?? 0,
                'low_stock_threshold' => $request->low_stock_threshold ?? 5,
                'barcode' => $request->barcode,
                'weight' => $request->weight,
                'weight_units' => $request->weight_units,
                'is_featured' => $request->is_featured ?? false,
                'is_active' => $request->is_active ?? true,
                'product_category_id' => $request->product_category_id,
                'attributes' => $attributes,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
            ]);

            // Handle image upload
            if ($request->hasFile('images')) {
                $this->uploadImages($request->file('images'), $product);
            }

            DB::commit();

            return redirect()
                ->route('products.index')
                ->with([
                    'message' => "Product {$product->name} created successfully",
                    'type' => 'success'
                ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with([
                'message' => 'Failed to create product: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    public function edit(Product $product)
    {
        $shop = $this->getCurrentShop();
        
        if ($product->shop_id !== $shop->id) {
            abort(403);
        }

        $categories = ProductCategory::select('id', 'name')->get();

        return inertia('products/Edit', [
            'product' => $product->load('category', 'images'),
            'categories' => $categories,
            'shop' => $shop,
        ]);
    }

    public function update(ProductRequest $request, Product $product)
    {
        $shop = $this->getCurrentShop();
        
        if ($product->shop_id !== $shop->id) {
            abort(403);
        }

        DB::beginTransaction();
        
        try {
            $attributes = null;
            if ($request->attributes && is_array($request->attributes) && !empty($request->attributes)) {
                $attributes = json_encode($request->attributes);
            }

            $product->update([
                'name' => $request->name,
                'sku' => $request->sku,
                'description' => $request->description,
                'cost_price' => $request->cost_price,
                'price' => $request->price,
                'stock_qty' => $request->stock_qty ?? 0,
                'low_stock_threshold' => $request->low_stock_threshold ?? 5,
                'barcode' => $request->barcode,
                'weight' => $request->weight,
                'weight_units' => $request->weight_units,
                'is_featured' => $request->is_featured ?? false,
                'is_active' => $request->is_active ?? true,
                'product_category_id' => $request->product_category_id,
                'attributes' => $attributes,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
            ]);

            // Handle new images
            if ($request->hasFile('images')) {
                $this->uploadImages($request->file('images'), $product);
            }

            DB::commit();

            return redirect()->route('products.index')->with([
                'message' => "Product {$product->name} updated successfully",
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with([
                'message' => 'Failed to update product: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    public function destroy(Product $product)
    {
        $shop = $this->getCurrentShop();
        
        if ($product->shop_id !== $shop->id) {
            abort(403);
        }

        $product->delete();

        return redirect()->back()->with([
            'message' => 'Product deleted successfully',
            'type' => 'success'
        ]);
    }
}
