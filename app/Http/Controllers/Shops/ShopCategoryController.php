<?php

namespace App\Http\Controllers\Shops;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\Shops\ShopCategory;
use App\Http\Requests\Shops\ShopCategoryRequest;

class ShopCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ShopCategory::withCount('shops');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%");
        }

        $categories = $query->orderBy('name')->get();

        return inertia('shops/categories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        return inertia('shops/categories/Create');
    }

    public function store(ShopCategoryRequest $request)
    {
        try {
            DB::beginTransaction();

            ShopCategory::create([
                'name' => $request->name,
            ]);

            DB::commit();
            return redirect()
                ->route('shop-categories.index')
                ->with([
                    'message' => 'Shop Category created successfully',
                    'type' => 'success'
                ]);
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with([
                    'message' => 'Failed to create shop category ' . $e->getMessage(),
                    'type' => 'error'
                ]);
        }
    }

    public function edit(ShopCategory $shop_category)
    {
        return inertia('shops/categories/Edit', [
            'category' => $shop_category
        ]);
    }

    public function update(ShopCategoryRequest $request, ShopCategory $shop_category)
    {
        try {
            DB::beginTransaction();

            $shop_category->update([
                'name' => $request->name,
            ]);

            DB::commit();
            return redirect()
                ->route('shop-categories.index')
                ->with([
                    'message' => 'Shop Category updated successfully',
                    'type' => 'success'
                ]);
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with([
                    'message' => 'Failed to update shop category ' . $e->getMessage(),
                    'type' => 'error'
                ]);
        }
    }

    public function destroy(ShopCategory $shop_category)
    {
        $shop_category->delete();

        return redirect()
            ->route('shop-categories.index')
            ->with([
                'message' => 'Shop Category deleted successfully',
                'type' => 'success'
            ]);
    }
}
