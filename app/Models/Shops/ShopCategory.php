<?php

namespace App\Models\Shops;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Concerns\HasUuid;

class ShopCategory extends Model
{
    use HasUuid;

    protected $guarded = [];

    public function shops(): HasMany
    {
        return $this->hasMany(Shop::class);
    }
}
