<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Concerns\HasSlug;
use App\Concerns\HasUuid;

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
}
