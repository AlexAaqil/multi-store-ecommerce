<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\Products\Product;
use App\Models\Products\Discount;

class ProductDealService 
{
    public function __construct(protected DiscountService $discount_service) {}

    /**
     * Transform a single product (with or without discount)
     * Use this for shopDetails and productDetails pages
     * 
     * @param Product $product
     * @param Collection|null $preloaded_discounts Optional pre-loaded discounts for the shop
     * @return array Always returns the same structure
     */
    public function transformProduct(Product $product, ?Collection $preloaded_discounts = null): array
    {
        // Get discounts if not preloaded
        if ($preloaded_discounts === null) {
            $preloaded_discounts = Discount::active()
                ->forShop($product->shop_id)
                ->get();
        }
        
        $discount = $this->discount_service->resolveForProduct($product, $preloaded_discounts);
        
        // Always return the same structure, with or without discount
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'slug' => $product->slug,
            'price' => $product->price,
            'discounted_price' => $discount ? (float) $this->discount_service->calculateDiscountedPrice($product->price, $discount) : null,
            'discount_pct' => $discount ? (int) $this->discount_service->calculatePercentageOff($product->price, $discount) : null,
            'stock_qty' => $product->stock_qty,
            'is_active' => $product->is_active,
            'image_url' => $product->primary_image_url,
            'category' => $product->category?->name,
            'shop_id' => $product->shop_id,
            'shop_name' => $product->shop->name,
            'shop_slug' => $product->shop->slug,
        ];
    }

    /**
     * Get ALL products that have any discount
     * Example: If you have 50 products with discounts, this returns all 50.
     */
    public function getAllDiscountedProducts(Collection $active_discounts, ?int $limit = null): Collection
    {
        if ($active_discounts->isEmpty()) {
            return collect();
        }

        $products = $this->getProductsFromShopsWithDiscounts($active_discounts, $limit);

        return $products->map(fn($product) => $this->transformProductWithDiscount($product, $active_discounts))
            ->filter()
            ->values();
    }

    /**
     * Get products with SMALL discounts (less than threshold)
     * Example: If threshold is 40%, this returns products with 10%, 25%, 35% off.
     */
    public function getSmallDiscounts(Collection $active_discounts, int $threshold = 40, ?int $limit = null): Collection
    {
        return $this->getDealsByComparison($active_discounts, $threshold, 'less', $limit);
    }

    /**
     * Get products with BIG discounts (equal or greater than threshold)
     * Example: If threshold is 40%, this returns products with 40%, 50%, 60%, 70% off.
     */
    public function getBigDiscounts(Collection $active_discounts, int $threshold = 40, ?int $limit = null): Collection
    {
        return $this->getDealsByComparison($active_discounts, $threshold, 'greater_or_equal', $limit);
    }

    /**
     * Get RANDOM products with any discount
     * Example: Randomly picks 3 products from all discounted products.
     */
    public function getRandomDiscountedProducts(Collection $active_discounts, int $limit = 3): Collection
    {
        if ($active_discounts->isEmpty()) {
            return collect();
        }

        $products = $this->getProductsFromShopsWithDiscounts($active_discounts, $limit * 3);

        return $products->map(fn($product) => $this->transformProductWithDiscount($product, $active_discounts))
            ->filter()
            ->values()
            ->shuffle()
            ->take($limit);
    }

    // ==================== PRIVATE HELPER METHODS ====================

    /**
     * Get raw products from shops that have active discounts
     * 
     */
    protected function getProductsFromShopsWithDiscounts(Collection $active_discounts, ?int $limit = null): Collection
    {
        if ($active_discounts->isEmpty()) {
            return collect();
        }

        $query = Product::with(['images', 'category', 'shop'])
            ->whereIn('shop_id', $active_discounts->keys())
            ->where('is_active', true)
            ->where('stock_qty', '>', 0);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    protected function transformProductWithDiscount($product, Collection $active_discounts): ?array
    {
        $shop_discounts = $active_discounts->get($product->shop_id, collect());
        $discount = $this->discount_service->resolveForProduct($product, $shop_discounts);

        if (!$discount) {
            return null;
        }

        $percentage_off = $this->discount_service->calculatePercentageOff($product->price, $discount);
        $discounted_price = $this->discount_service->calculateDiscountedPrice($product->price, $discount);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'category' => $product->category?->name,
            'price' => $product->price,
            'discounted_price' => (float) $discounted_price,
            'discount_pct' => (int) $percentage_off,
            'stock_qty' => $product->stock_qty,
            'image_url' => $product->primary_image_url,
            'shop_id' => $product->shop_id,
            'shop_name' => $product->shop->name,
            'shop_slug' => $product->shop->slug,
            'expires_at' => $discount->expires_at,
        ];
    }

    /**
     * Filter products by discount percentage comparison
     * 
     */
    protected function getDealsByComparison(Collection $active_discounts, int $threshold, string $comparison, ?int $limit = null): Collection
    {
        if ($active_discounts->isEmpty()) {
            return collect();
        }

        $products = $this->getProductsFromShopsWithDiscounts($active_discounts);
        $deals = collect();

        foreach ($active_discounts as $shop_id => $shop_discounts) {
            $shop_products = $products->where('shop_id', $shop_id);

            foreach ($shop_products as $product) {
                $discount = $this->discount_service->resolveForProduct($product, $shop_discounts);
                if (!$discount) continue;

                $percentage_off = $this->discount_service->calculatePercentageOff($product->price, $discount);

                if ($comparison === 'less' && $percentage_off >= $threshold) continue;
                if ($comparison === 'greater' && $percentage_off < $threshold) continue;
                if ($comparison === 'greater_or_equal' && $percentage_off < $threshold) continue;

                $deals->push($this->transformProductWithDiscount($product, $active_discounts));

                if ($limit && $deals->count() >= $limit) {
                    break 2;
                }
            }
        }

        return $deals;
    }
}