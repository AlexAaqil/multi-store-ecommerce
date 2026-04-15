<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class Cart extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function getTotalAttribute(): float
    {
        return $this->items->sum(function ($item) {
            $price = $item->variant 
                ? $item->product->price + $item->variant->price_adjustment
                : $item->product->price;
            return $item->quantity * $price;
        });
    }

    public function getItemCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }
}
