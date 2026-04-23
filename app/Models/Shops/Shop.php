<?php

namespace App\Models\Shops;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Products\Product;
use App\Models\Products\Discount;
use App\Concerns\HasUuid;

class Shop extends Model
{
    use SoftDeletes;
    use HasUuid;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'settings' => 'array',
    ];

    protected $appends = [
        'logo_url_full',
        'cover_url_full',
    ];

    protected array $searchable = [
        'name',
        'contact_email',
        'contact_phone',
    ];

    protected static function booted()
    {
        // Auto generate slug when creating
        static::creating(function ($shop) {
            $shop->slug = static::generateSystemSlug($shop->name);
        });

        static::updating(function ($shop) {
            if ($shop->isDirty('name')) {
                $shop->slug = static::generateSystemSlug($shop->name);

                if ($shop->getOriginal('logo_image')) {
                    $old_logo = $shop->getOriginal('logo_image');
                    $new_logo = static::renameImageFile($old_logo, $shop->getOriginal('name'), $shop->name, 'logo', $shop->id);

                    if ($new_logo) {
                        $shop->logo_image = $new_logo;
                    }
                }

                if ($shop->getOriginal('cover_image')) {
                    $old_cover = $shop->getOriginal('cover_image');
                    $new_cover = static::renameImageFile($old_cover, $shop->getOriginal('name'), $shop->name, 'cover', $shop->id);

                    if ($new_cover) {
                        $shop->cover_image = $new_cover;
                    }
                }
            }
        });

        static::deleting(function ($shop) {
            if ($shop->isForceDeleting()) {
                $shop->deleteImages();
            }
        });
    }

    /**
     * Rename image file when shop name changes
     */
    public static function renameImageFile(string $old_filename, string $old_name, string $new_name, string $type, int $shop_id): ?string
    {
        $old_path = $type === 'logo' ? "shops/logos/{$old_filename}" : "shops/covers/{$old_filename}";
        
        if (!Storage::disk('public')->exists($old_path)) {
            return null;
        }
        
        $extension = pathinfo($old_filename, PATHINFO_EXTENSION);
        $new_slug = Str::slug($new_name);
        $old_slug = Str::slug($old_name);
        
        // Replace old slug with new slug in filename
        $new_filename = str_replace($old_slug, $new_slug, $old_filename);
        
        // If no change, add timestamp to avoid cache
        if ($new_filename === $old_filename) {
            $timestamp = now()->format('dmY_His');
            $new_filename = "{$new_slug}_{$type}_{$shop_id}_{$timestamp}.{$extension}";
        }
        
        $new_path = $type === 'logo' ? "shops/logos/{$new_filename}" : "shops/covers/{$new_filename}";
        
        // Rename the file
        if (Storage::disk('public')->move($old_path, $new_path)) {
            return $new_filename;
        }
        
        return null;
    }
    
    /**
     * Delete all images associated with this shop
     */
    public function deleteImages(): void
    {
        if ($this->logo_image) {
            $path = "shops/logos/{$this->logo_image}";
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
        
        if ($this->cover_image) {
            $path = "shops/covers/{$this->cover_image}";
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    public static function generateSystemSlug(string $name): string
    {
        $base_slug = Str::slug($name);
        $slug = $base_slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base_slug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Public slug for URL (prefer custom slug, fallback to system slug)
     */
    public function getPublicSlugAttribute(): string
    {
        return $this->custom_slug ?? $this->slug;
    }

    public static function isValidCustomSlug(string $slug): bool
    {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) && strlen($slug) <= 50;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'shop_category_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class);
    }

    public function getLogoUrlFullAttribute(): string
    {
        if (!$this->logo_image) {
            return asset('assets/images/default.png');
        }

        return asset("storage/shops/logos/{$this->logo_image}");
    }

    public function getCoverUrlFullAttribute(): string
    {
        if (!$this->cover_image) {
            return asset('assets/images/default.png');
        }

        return asset("storage/shops/covers/{$this->cover_image}");
    }

    public function scopeSearch($query, ?string $searchTerm)
    {
        if (!$searchTerm) {
            return $query;
        }

        $fields = ['name', 'contact_email', 'contact_phone'];

        $terms = preg_split('/\s+/', trim(strtolower($searchTerm)));

        // Expand terms: sneakers → sneaker
        $expandedTerms = [];

        foreach ($terms as $term) {
            $expandedTerms[] = $term;

            // Simple plural handling
            if (str_ends_with($term, 's')) {
                $expandedTerms[] = rtrim($term, 's');
            } else {
                $expandedTerms[] = $term . 's';
            }
        }

        return $query->where(function ($q) use ($expandedTerms, $fields) {
            foreach ($expandedTerms as $term) {
                $q->orWhere(function ($sub) use ($term, $fields) {
                    foreach ($fields as $field) {
                        $sub->orWhereRaw("LOWER($field) LIKE ?", ["%{$term}%"]);
                    }
                });
            }
        });
    }
}
