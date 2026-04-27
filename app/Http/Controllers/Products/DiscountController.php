<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Products\Discount;
use App\Models\Products\Product;
use App\Models\Products\ProductCategory;
use App\Models\Shops\Shop;
use App\Models\User;
use App\Http\Requests\Products\DiscountRequest;

class DiscountController extends Controller
{
    protected function getUser(): User
    {
        return Auth::user();
    }

    protected function getCurrentShop(): Shop
    {
        $shop = $this->getUser()->shops()->first();
        if (!$shop) abort(403, 'You need to create a shop first!');
        return $shop;
    }

    public function index(Request $request)
    {
        $shop = $this->getCurrentShop();

        $query = Discount::forShop($shop->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $discounts = $query->orderByDesc('created_at')
            ->withCount(['products', 'categories'])
            ->paginate(15)
            ->through(fn($d) => [
                'id'              => $d->id,
                'name'            => $d->name,
                'type_label'      => $d->type_label,
                'formatted_value' => $d->formatted_value,
                'scope_label'     => $d->scope_label,
                'is_active'       => $d->is_active,
                'is_expired'      => $d->is_expired,
                'starts_at'       => $d->starts_at->format('d/m/Y h:i A'),
                'expires_at'      => $d->expires_at->format('d/m/Y h:i A'),
                'is_scheduled'    => $d->starts_at->isFuture(), // True if starts in future
                'starts_in_days'  => $d->starts_at->isFuture() ? $d->starts_at->diffInDays(now()) : null,
                'status'          => $d->is_active_now ? 'active' : ($d->starts_at->isFuture() ? 'scheduled' : ($d->is_expired ? 'expired' : 'inactive')),
                'targets_count'   => $d->scope === Discount::SCOPE_PRODUCT_CATEGORY 
                    ? $d->categories_count 
                    : ($d->scope === Discount::SCOPE_SPECIFIC_PRODUCTS 
                        ? $d->products_count 
                        : 0),
            ]);

        return inertia('products/discounts/Index', [
            'discounts' => $discounts,
            'shop'      => ['id' => $shop->id, 'name' => $shop->name],
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        $shop       = $this->getCurrentShop();
        $categories = ProductCategory::select('id', 'name')->get();
        $products   = Product::where('shop_id', $shop->id)
                        ->select('id', 'name', 'price')
                        ->get();

        return inertia('products/discounts/Create', compact('shop', 'categories', 'products'));
    }

    public function store(DiscountRequest $request)
    {
        $shop = $this->getCurrentShop();

        DB::transaction(function () use ($request, $shop) {
            $discount = $shop->discounts()->create([
                'name' => $request->name,
                'type' => $request->type,
                'value' => $request->value,
                'scope' => $request->scope,
                'min_order_amount' => $request->min_order_amount,
                'min_quantity' => $request->min_quantity,
                'starts_at' => $request->starts_at,
                'expires_at' => $request->expires_at,
                'is_active' => $request->is_active ?? true,
            ]);

            $this->syncDiscountTargets($discount, $request);
        });

        return redirect()
            ->route('shops.show', $shop->id)
            ->with([
            'message' => 'Discount created successfully',
            'type'    => 'success',
        ]);
    }

    public function edit(Discount $discount)
    {
        $shop = $this->getCurrentShop();
        if ($discount->shop_id !== $shop->id) abort(403);

        $discount->load(['products', 'categories']);

        $categories = ProductCategory::select('id', 'name')->get();
        $products   = Product::where('shop_id', $shop->id)
                        ->select('id', 'name', 'price')
                        ->get();

        // Get selected IDs for the frontend
        $selected_products = $discount->products->pluck('id')->toArray();
        $selected_categories = $discount->categories->pluck('id')->toArray();

        return inertia('products/discounts/Edit', compact('discount', 'shop', 'categories', 'products', 'selected_products', 'selected_categories'));
    }

    public function update(DiscountRequest $request, Discount $discount)
    {
        $shop = $this->getCurrentShop();
        if ($discount->shop_id !== $shop->id) abort(403);

        DB::transaction(function () use ($request, $discount) {
            $discount->update([
                'name'             => $request->name,
                'type'             => $request->type,
                'value'            => $request->value,
                'scope'            => $request->scope,
                'min_order_amount' => $request->min_order_amount,
                'min_quantity'     => $request->min_quantity,
                'starts_at'        => $request->starts_at,
                'expires_at'       => $request->expires_at,
                'is_active'        => $request->is_active ?? true,
            ]);

            $this->syncDiscountTargets($discount, $request);
        });

        return redirect()
            ->route('shops.show', $shop->id)
            ->with([
            'message' => 'Discount updated successfully',
            'type'    => 'success',
        ]);
    }

    public function destroy(Discount $discount)
    {
        $shop = $this->getCurrentShop();
        if ($discount->shop_id !== $shop->id) abort(403);

        $discount->delete();

        return redirect()->back()->with([
            'message' => 'Discount deleted',
            'type'    => 'success',
        ]);
    }

    /**
     * Sync discount targets based on scope
     */
    protected function syncDiscountTargets(Discount $discount, Request $request): void
    {
        switch ($discount->scope) {
            case Discount::SCOPE_PRODUCT_CATEGORY:
                // Sync categories
                $categoryIds = $request->input('category_ids', []);
                $discount->categories()->sync($categoryIds);
                // Clear products if any
                $discount->products()->detach();
                break;

            case Discount::SCOPE_SPECIFIC_PRODUCTS:
                // Sync products
                $productIds = $request->input('product_ids', []);
                $discount->products()->sync($productIds);
                // Clear categories if any
                $discount->categories()->detach();
                break;

            case Discount::SCOPE_SHOP_WIDE:
                // Clear both relationships
                $discount->categories()->detach();
                $discount->products()->detach();
                break;
        }
    }
}
