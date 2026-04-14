<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Concerns\HasSlug;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCategory extends Model
{
    use HasSlug, HasUuid;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    // Get all products in this category and subcategories
    public function getAllProductsAttribute()
    {
        $categoryIds = $this->getAllDescendantIds();
        $categoryIds[] = $this->id;
        
        return Product::whereIn('product_category_id', $categoryIds)->get();
    }

    protected function getAllDescendantIds()
    {
        $ids = [];
        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getAllDescendantIds());
        }
        return $ids;
    }
}
