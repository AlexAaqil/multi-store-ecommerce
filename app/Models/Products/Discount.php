<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Shops\Shop;
use App\Concerns\HasUuid;

class Discount extends Model
{
    use HasUuid;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'min_order_amount' => 'decimal:2',
        'value' => 'float',
    ];

    // Scope types
    const SCOPE_SHOP_WIDE = 0;
    const SCOPE_PRODUCT_CATEGORY = 1;
    const SCOPE_SPECIFIC_PRODUCTS = 2;

    // Discount value types
    const TYPE_PERCENTAGE = 0;
    const TYPE_FIXED_AMOUNT = 1;

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    // Categories this discount applies to (for scope = 1)
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductCategory::class, 
            'discount_categories', 
            'discount_id', 
            'product_category_id'
        )->withTimestamps();
    }

    // Products this discount applies to (for scope = 2)
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class, 
            'discount_products', 
            'discount_id', 
            'product_id'
        )->withTimestamps();
    }

    // Get all product IDs that qualify for this discount
    public function getEligibleProductIdsAttribute(): array
    {
        if ($this->scope === self::SCOPE_SHOP_WIDE) {
            return Product::where('shop_id', $this->shop_id)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();
        }

        if ($this->scope === self::SCOPE_PRODUCT_CATEGORY) {
            $categoryIds = $this->categories->pluck('id')->toArray();
            
            // Include products from subcategories
            $allCategoryIds = $categoryIds;
            foreach ($this->categories as $category) {
                $allCategoryIds = array_merge($allCategoryIds, $category->getAllDescendantIds());
            }
            
            return Product::whereIn('product_category_id', array_unique($allCategoryIds))
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();
        }

        if ($this->scope === self::SCOPE_SPECIFIC_PRODUCTS) {
            return $this->products->pluck('id')->toArray();
        }

        return [];
    }

    // Check if a specific product qualifies for this discount
    public function isProductEligible(int $productId): bool
    {
        $eligibleProductIds = $this->eligible_product_ids;
        return in_array($productId, $eligibleProductIds);
    }

    // Calculate discount amount for a given subtotal
    public function calculateDiscount(float $subtotal, ?int $productId = null, ?int $quantity = null): float
    {
        // If product-specific, use product price instead of subtotal
        if ($productId && $quantity) {
            $product = Product::find($productId);
            if ($product && $this->isProductEligible($productId)) {
                $subtotal = $product->price * $quantity;
            } else {
                return 0;
            }
        }

        if ($this->type === self::TYPE_PERCENTAGE) {
            $discount = $subtotal * ($this->value / 100);
            return round($discount, 2);
        }

        // Fixed amount
        return min($this->value, $subtotal);
    }

    // Check if discount is currently active
    public function getIsActiveNowAttribute(): bool
    {
        $now = now();
        return $this->is_active && 
               $this->starts_at <= $now && 
               $this->expires_at >= $now;
    }

    // Only return currently active & scheduled discounts
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>=', now());
    }

    public function scopeForShop(Builder $query, int $shopId): Builder
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where(function($q) use ($productId) {
            // Shop-wide discounts
            $q->where('scope', self::SCOPE_SHOP_WIDE)
              // Category discounts
              ->orWhere(function($sub) use ($productId) {
                  $sub->where('scope', self::SCOPE_PRODUCT_CATEGORY)
                      ->whereHas('categories', function($cat) use ($productId) {
                          $cat->whereIn('product_categories.id', function($prod) use ($productId) {
                              $prod->select('product_category_id')
                                  ->from('products')
                                  ->where('id', $productId);
                          });
                      });
              })
              // Specific product discounts
              ->orWhere(function($sub) use ($productId) {
                  $sub->where('scope', self::SCOPE_SPECIFIC_PRODUCTS)
                      ->whereHas('products', function($prod) use ($productId) {
                          $prod->where('products.id', $productId);
                      });
              });
        });
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at->isPast();
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->starts_at->isFuture();
    }

    public function getScopeLabelAttribute(): string
    {
        return match($this->scope) {
            self::SCOPE_SHOP_WIDE         => 'Shop-wide',
            self::SCOPE_PRODUCT_CATEGORY  => 'Category',
            self::SCOPE_SPECIFIC_PRODUCTS => 'Specific Products',
            default => 'Unknown',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === self::TYPE_PERCENTAGE ? 'Percentage' : 'Fixed Amount';
    }

    public function getFormattedValueAttribute(): string
    {
        $value = (float) $this->value;

        return $this->type === self::TYPE_PERCENTAGE
            ? "{$value}%"
            : "KES " . number_format($value, 2);
    }

    // Get status label for admin panel
    public function getStatusLabelAttribute(): string
    {
        if (!$this->is_active) {
            return 'Inactive';
        }
        
        if ($this->is_expired) {
            return 'Expired';
        }
        
        if ($this->is_upcoming) {
            return 'Scheduled';
        }
        
        return 'Active';
    }
}