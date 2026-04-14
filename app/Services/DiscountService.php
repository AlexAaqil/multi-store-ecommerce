<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\Products\Discount;
use App\Models\Products\Product;

class DiscountService
{
    /**
     * Resolve the single winning discount for a product.
     *
     * Priority (most specific wins):
     *   1. Specific product discount (scope = 2)
     *   2. Category discount        (scope = 1)
     *   3. Shop-wide discount       (scope = 0)
     *
     * If two discounts at the same scope level exist, the one saving
     * the customer more wins.
     */
    public function resolveForProduct(Product $product, Collection $shopDiscounts): ?Discount
    {
        // 1. Product-specific discounts
        $productDiscounts = $shopDiscounts->filter(function ($discount) use ($product) {
            if ($discount->scope !== Discount::SCOPE_SPECIFIC_PRODUCTS) {
                return false;
            }
            
            // Check if this product is in the discount's products pivot table
            return $discount->products->contains($product->id);
        });

        if ($productDiscounts->isNotEmpty()) {
            return $this->pickBest($productDiscounts, $product->price);
        }

        // 2. Category discounts
        if ($product->product_category_id) {
            $categoryDiscounts = $shopDiscounts->filter(function ($discount) use ($product) {
                if ($discount->scope !== Discount::SCOPE_PRODUCT_CATEGORY) {
                    return false;
                }
                
                // Check if product's category (or any parent category) is in discount's categories
                return $this->isCategoryEligible($discount, $product->product_category_id);
            });

            if ($categoryDiscounts->isNotEmpty()) {
                return $this->pickBest($categoryDiscounts, $product->price);
            }
        }

        // 3. Shop-wide discounts
        $shopWideDiscounts = $shopDiscounts->filter(
            fn($d) => $d->scope === Discount::SCOPE_SHOP_WIDE
        );

        if ($shopWideDiscounts->isNotEmpty()) {
            return $this->pickBest($shopWideDiscounts, $product->price);
        }

        return null;
    }

    /**
     * Check if a category (or its parent) is eligible for the discount
     */
    protected function isCategoryEligible(Discount $discount, int $categoryId): bool
    {
        // Make sure categories relationship is loaded
        if (!$discount->relationLoaded('categories')) {
            $discount->load('categories');
        }

        // Get all category IDs from the discount
        $discountCategoryIds = $discount->categories->pluck('id')->toArray();
        
        if (in_array($categoryId, $discountCategoryIds)) {
            return true;
        }
        
        // Optional: Check parent categories if you want category hierarchy support
        // You would need to load the category hierarchy for this check
        // For now, we'll just check direct category match
        
        return false;
    }

    /**
     * From a collection of same-scope discounts, return the one
     * that saves the customer the most money.
     */
    private function pickBest(Collection $discounts, float $price): Discount
    {
        return $discounts->sortByDesc(function ($discount) use ($price) {
            return $this->calculateSaving($discount, $price);
        })->first();
    }

    /**
     * Calculate the discounted price for a product.
     */
    public function calculateDiscountedPrice(float $originalPrice, Discount $discount): float
    {
        $saving = $this->calculateSaving($discount, $originalPrice);
        return max(0, round($originalPrice - $saving, 2));
    }

    /**
     * Calculate money saved in KES.
     */
    public function calculateSaving(Discount $discount, float $price): float
    {
        if ($discount->type === Discount::TYPE_PERCENTAGE) {
            return round(($discount->value / 100) * $price, 2);
        }

        // Fixed amount — can't save more than the price itself
        return min($discount->value, $price);
    }

    /**
     * Calculate the percentage off (for display), regardless of discount type.
     * e.g. a KES 300 fixed discount on a KES 1500 product = 20% off
     */
    public function calculatePercentageOff(float $originalPrice, Discount $discount): float
    {
        if ($originalPrice <= 0) return 0;

        $saving = $this->calculateSaving($discount, $originalPrice);
        return round(($saving / $originalPrice) * 100, 1);
    }

    /**
     * Apply discounts to an entire collection of products.
     * Loads all shop discounts once (N+1 safe) then resolves per product.
     *
     * Returns the product collection with discount data appended.
     */
    public function applyToProducts(Collection $products, int $shopId): Collection
    {
        // Load all active discounts for this shop with their relationships
        $shopDiscounts = Discount::active()
            ->forShop($shopId)
            ->with(['products', 'categories']) // Eager load pivot relationships
            ->get();

        if ($shopDiscounts->isEmpty()) {
            return $products->map(function ($product) {
                $product->active_discount = null;
                $product->discounted_price = null;
                $product->percentage_off = null;
                return $product;
            });
        }

        return $products->map(function ($product) use ($shopDiscounts) {
            $discount = $this->resolveForProduct($product, $shopDiscounts);

            $product->active_discount = $discount;
            $product->discounted_price = $discount
                ? $this->calculateDiscountedPrice($product->price, $discount)
                : null;
            $product->percentage_off = $discount
                ? $this->calculatePercentageOff($product->price, $discount)
                : null;

            return $product;
        });
    }

    /**
     * Resolve discount for a single variant.
     * Variant price = product base price + price_adjustment.
     */
    public function resolveForVariant($variant, Product $product, Collection $shopDiscounts): ?array
    {
        $variantPrice = $product->price + $variant->price_adjustment;
        $discount = $this->resolveForProduct($product, $shopDiscounts);

        if (!$discount) return null;

        return [
            'discount' => $discount,
            'original_price' => $variantPrice,
            'discounted_price' => $this->calculateDiscountedPrice($variantPrice, $discount),
            'percentage_off' => $this->calculatePercentageOff($variantPrice, $discount),
        ];
    }

    /**
     * Alternative: Resolve discount for a product using pre-loaded eligible IDs
     * This is more efficient if you have many discounts and products
     */
    public function resolveForProductOptimized(Product $product, Collection $shopDiscounts): ?Discount
    {
        // Pre-filter by scope first for better performance
        $productDiscounts = $shopDiscounts
            ->filter(fn($d) => $d->scope === Discount::SCOPE_SPECIFIC_PRODUCTS)
            ->filter(function ($discount) use ($product) {
                // Use the pre-loaded eligible_product_ids accessor
                return in_array($product->id, $discount->eligible_product_ids);
            });

        if ($productDiscounts->isNotEmpty()) {
            return $this->pickBest($productDiscounts, $product->price);
        }

        // Category discounts
        if ($product->product_category_id) {
            $categoryDiscounts = $shopDiscounts
                ->filter(fn($d) => $d->scope === Discount::SCOPE_PRODUCT_CATEGORY)
                ->filter(function ($discount) use ($product) {
                    return in_array($product->product_category_id, $discount->categories->pluck('id')->toArray());
                });

            if ($categoryDiscounts->isNotEmpty()) {
                return $this->pickBest($categoryDiscounts, $product->price);
            }
        }

        // Shop-wide
        $shopWideDiscounts = $shopDiscounts->filter(
            fn($d) => $d->scope === Discount::SCOPE_SHOP_WIDE
        );

        return $shopWideDiscounts->isNotEmpty() 
            ? $this->pickBest($shopWideDiscounts, $product->price) 
            : null;
    }
}