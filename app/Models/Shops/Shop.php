<?php

namespace App\Models\Shops;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    protected static function booted()
    {
        // Auto generate slug when creating
        static::creating(function ($shop) {
            $shop->slug = static::generateSystemSlug($shop->name);
        });
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

    /**
     * Route binding - find by custom_slug first, then system slug
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('custom_slug', $value)
            ->orWhere('slug', $value)
            ->firstOrFail();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'shop_category_id');
    }

    public function getLogoUrlFullAttribute(): string
    {
        if (!$this->logo_image) {
            return asset('images/default-shop-logo.png');
        }

        return $this->getImageUrl('logo');
    }

    public function getCoverUrlFullAttribute(): string
    {
        if (!$this->logo_image) {
            return asset('images/default-shop-logo.png');
        }

        return $this->getImageUrl('cover');
    }

    private function getImageUrl(string $type): string
    {
        $path = $type === 'logo' ? 'shops/logos' : 'shops/covers';
        $image = $type === 'logo' ? $this->logo_image : $this->cover_image;
        
        return asset("storage/{$path}/{$image}");
    }
}
