<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\Products\Product;

class ProductDealService 
{
    public function __construct(protected DiscountService $discount_service) {}

    /**
     * Get ALL products that have any discount
     * 
     * Example: If you have 50 products with discounts, this returns all 50.
     * Use this for the main Deals page where you want to show everything.
     * 
     * @param Collection $active_discounts Discounts grouped by shop_id
     * @param int|null $limit Maximum number to return (null = all)
     * @return Collection Returns every product that has a discount
     * 
     * Example output:
     * [
     *     ['name' => 'iPhone 13', 'discount_pct' => 10, 'price' => 108000],
     *     ['name' => 'Samsung TV', 'discount_pct' => 25, 'price' => 60000],
     *     ['name' => 'Sony Headphones', 'discount_pct' => 50, 'price' => 7500],
     *     ['name' => 'Nike Shoes', 'discount_pct' => 60, 'price' => 3200],
     *     ['name' => 'Adidas Shirt', 'discount_pct' => 70, 'price' => 1200],
     * ]
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
     * Get products with SMALL discounts (less than threshold or flash sales)
     * 
     * Example: If threshold is 40%, this returns products with 10%, 25%, 35% off.
     * For sections where discounts are small but tempting.
     * 
     * @param Collection $active_discounts Discounts grouped by shop_id
     * @param int $threshold Percentage cutoff (default: 40%)
     * @param int|null $limit Maximum number to return
     * @return Collection Only products with discount percentage BELOW the threshold
     * 
     * Example output (threshold = 40, limit = 3):
     * [
     *     ['name' => 'iPhone 13', 'discount_pct' => 10],  // ✅ Less than 40
     *     ['name' => 'Samsung TV', 'discount_pct' => 25], // ✅ Less than 40
     *     // Sony (50%), Nike (60%), Adidas (70%) are EXCLUDED because they're 40 or above
     * ]
     */
    public function getSmallDiscounts(Collection $active_discounts, int $threshold = 40, ?int $limit = null): Collection
    {
        return $this->getDealsByComparison($active_discounts, $threshold, 'less', $limit);
    }

    /**
     * Get products with BIG discounts (equal or greater than threshold or clearance sales)
     * 
     * Example: If threshold is 40%, this returns products with 40%, 50%, 60%, 70% off.
     * For sections where discounts are huge.
     * 
     * @param Collection $active_discounts Discounts grouped by shop_id
     * @param int $threshold Percentage cutoff (default: 40%)
     * @param int|null $limit Maximum number to return
     * @return Collection Only products with discount percentage AT OR ABOVE the threshold
     * 
     * Example output (threshold = 40, limit = 3):
     * [
     *     ['name' => 'Sony Headphones', 'discount_pct' => 50], // ✅ 50 or above
     *     ['name' => 'Nike Shoes', 'discount_pct' => 60],      // ✅ 50 or above
     *     ['name' => 'Adidas Shirt', 'discount_pct' => 70],    // ✅ 50 or above
     *     // iPhone (10%), Samsung (25%) are EXCLUDED because they're below 40
     * ]
     */
    public function getBigDiscounts(Collection $active_discounts, int $threshold = 40, ?int $limit = null): Collection
    {
        return $this->getDealsByComparison($active_discounts, $threshold, 'greater_or_equal', $limit);
    }

    /**
     * Get RANDOM products with any discount
     * 
     * Example: Randomly picks 3 products from all discounted products.
     * 
     * 
     * @param Collection $active_discounts Discounts grouped by shop_id
     * @param int $limit How many random products to return (default: 3)
     * @return Collection Random selection of products with discounts
     * 
     * Example output (limit = 3):
     * // First page load
     * [
     *     ['name' => 'Adidas Shirt', 'discount_pct' => 70],
     *     ['name' => 'iPhone 13', 'discount_pct' => 10],
     *     ['name' => 'Sony Headphones', 'discount_pct' => 50],
     * ]
     * 
     * // Refresh page - different random products
     * [
     *     ['name' => 'Nike Shoes', 'discount_pct' => 60],
     *     ['name' => 'Samsung TV', 'discount_pct' => 25],
     *     ['name' => 'Adidas Shirt', 'discount_pct' => 70],
     * ]
     */
    public function getRandomDiscountedProducts(Collection $active_discounts, int $limit = 3): Collection
    {
        if ($active_discounts->isEmpty()) {
            return collect();
        }

        // Get extra products (limit * 3) to account for products without discounts
        // Then randomly pick the exact number we need
        $products = $this->getProductsFromShopsWithDiscounts($active_discounts, $limit * 3);

        return $products->map(fn($product) => $this->transformProductWithDiscount($product, $active_discounts))
            ->filter()
            ->values()
            ->shuffle() // Randomize order
            ->take($limit);
    }

    // ==================== PRIVATE HELPER METHODS ====================

    /**
     * Get raw products from shops that have active discounts
     * 
     * 
     * @param Collection $active_discounts Discounts grouped by shop_id
     * @param int|null $limit Maximum number of products to fetch
     * @return Collection Raw product models with their relations loaded
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

    /**
     * Add discount information to a single product
     * 
     * 
     * @param Product $product The product to transform
     * @param Collection $active_discounts All active discounts grouped by shop
     * @return array|null Product data with discount info, or null if no discount
     * 
     * Example output:
     * [
     *     'id' => 1,
     *     'name' => 'iPhone 13',
     *     'slug' => 'iphone-13',
     *     'category' => 'Electronics',
     *     'price' => 108000,        // After discount
     *     'old_price' => 120000,    // Original price
     *     'discount_pct' => 10,     // How much off
     *     'image_url' => 'https://...',
     *     'shop_name' => 'Apple Store',
     *     'shop_slug' => 'apple-store',
     *     'expires_at' => '2024-12-31 23:59:59'
     * ]
     */
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
            'price' => (float) $discounted_price,
            'old_price' => (float) $product->price,
            'discount_pct' => (int) $percentage_off,
            'image_url' => $product->primary_image_url,
            'shop_name' => $product->shop->name,
            'shop_slug' => $product->shop->slug,
            'expires_at' => $discount->expires_at,
        ];
    }

    /**
     * Filter products by discount percentage comparison
     * 
     * 
     * @param Collection $active_discounts Discounts grouped by shop_id
     * @param int $threshold Percentage to compare against
     * @param string $comparison 'less', 'greater', or 'greater_or_equal'
     * @param int|null $limit Maximum number to return
     * @return Collection Filtered products
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

                // Apply the filter
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