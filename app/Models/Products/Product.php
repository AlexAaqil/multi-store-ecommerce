<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Shops\Shop;
use App\Concerns\HasUuid;
use App\Concerns\HasSlug;

class Product extends Model
{
    use HasUuid, HasSlug;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'attributes' => 'array'
    ];

    protected static function booted()
    {
        static::updating(function ($product) {
            if ($product->isDirty('name')) {
                $old_name = $product->getOriginal('name');
                $new_name = $product->name;
                
                // Update slug
                $product->slug = Str::slug($new_name);
                
                // Get images directly from database
                $images = $product->images()->get();
                
                $new_slug = Str::slug($new_name);
                
                // Rename all images
                foreach ($images as $image) {
                    $old_filename = $image->name;
                    
                    // Extract the current slug from the filename (first part before first underscore)
                    $parts = explode('_', $old_filename);
                    $old_slug = $parts[0];
                    
                    // Replace old slug with new slug
                    $new_filename = str_replace($old_slug, $new_slug, $old_filename);
                    
                    if ($old_filename !== $new_filename) {
                        $old_path = 'products/' . $old_filename;
                        $new_path = 'products/' . $new_filename;
                        
                        // Rename the actual file
                        if (Storage::disk('public')->exists($old_path)) {
                            Storage::disk('public')->move($old_path, $new_path);
                        }
                        
                        // Update database record
                        $image->name = $new_filename;
                        $image->save();
                    }
                }
            }
        });

        static::deleting(function ($product) {
            // Get images directly from database
            $images = $product->images()->get();
            
            // Delete all images when product is deleted
            foreach ($images as $image) {
                $path = "products/{$image->name}";
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
                $image->delete();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function images(): HasMany
{
    return $this->hasMany(ProductImage::class)->orderBy('sort_order');
}

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function getProfitMarginAttribute(): float
    {
        if (!$this->cost_price || $this->cost_price <=0) {
            return 0;
        }

        return round((($this->price - $this->cost_price) / $this->price) * 100, 2);
    }

    public function getProfitAttribute(): float
    {
        return $this->price - ($this->cost_price ?? 0);
    }

    public function getPrimaryImageAttribute(): ?ProductImage
    {
        return $this->images->first();
    }

    public function getPrimaryImageUrlAttribute(): string
    {
        return $this->primaryImage?->full_url ?? asset('assets/images/default.png');
    }
}
