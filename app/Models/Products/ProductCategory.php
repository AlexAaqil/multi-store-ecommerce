<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;
use App\Concerns\HasSlug;
use App\Concerns\HasUuid;

class ProductCategory extends Model
{
    use HasSlug, HasUuid;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
