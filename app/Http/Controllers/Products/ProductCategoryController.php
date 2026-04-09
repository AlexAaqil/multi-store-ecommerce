<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\Products\ProductCategory;
use App\Http\Requests\Products\ProductCategoryRequest;

class ProductCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductCategory::withCount('products');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%");
        }

        $categories = $query->orderBy('name')->get();

        return inertia('products/categories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        return inertia('products/categories/Create');
    }

    public function store(ProductCategoryRequest $request)
    {
        try {
            DB::beginTransaction();

            ProductCategory::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            DB::commit();

            return redirect()
                ->route('product-categories.index')
                ->with([
                    'message' => 'Product category created successfully',
                    'type' => 'success'
                ]);
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with([
                    'message' => 'Failed to create product category ' . $e->getMessage(),
                    'type' => 'error'
                ]);
        }
    }

    public function edit(ProductCategory $product_category)
    {
        return inertia('products/categories/Edit', [
            'product_category' => $product_category
        ]);
    }

    public function update(ProductCategoryRequest $request, ProductCategory $product_category)
    {
        try {
            DB::beginTransaction();

            $product_category->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            DB::commit();

            return redirect()
                ->route('product-categories.index')
                ->with([
                    'message' => 'Product category updated successfully',
                    'type' => 'success'
                ]);
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with([
                    'message' => 'Failed to update product category ' . $e->getMessage(),
                    'type' => 'error'
                ]);
        }
    }

    public function destroy(ProductCategory $product_category)
    {
        $product_category->delete();

        return redirect()
            ->route('product-categories.index')
            ->with([
                'message' => 'Product Category deleted successfully',
                'type' => 'success'
            ]);
    }
}
