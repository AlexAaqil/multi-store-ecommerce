<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $guarded = [];

    protected $appends = [
        'full_url', 
        'thumbnail_url'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getFullUrlAttribute(): string
    {
        if (!$this->name) {
            return asset('images/default-product.png');
        }
        
        return asset('storage/products/' . $this->name);
    }

    public function getThumbnailUrlAttribute(): string
    {
        if (!$this->name) {
            return asset('images/default-product.png');
        }
        
        // You can create a thumbnail version if needed
        return asset('storage/products/' . $this->name);
    }
}
