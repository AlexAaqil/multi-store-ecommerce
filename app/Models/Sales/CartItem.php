<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use App\Models\Shops\Shop;

class CartItem extends Model
{
    protected $guarded = [];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function getSubtotalAttribute(): float
    {
        $price = $this->variant ? $this->product->price + $this->variant->price_adjustment : $this->product->price;

        return $this->quantity * $price;
    }
}
