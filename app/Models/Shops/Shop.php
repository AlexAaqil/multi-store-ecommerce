<?php

namespace App\Models\Shops;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Shop extends Model
{
    use SoftDeletes;

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

    public function getLogoUrlFullAttribute(): string
    {
        if ($this->logo_image && Storage::disk('public')->exists(str_replace('/storage', '', $this->logo_image))) {
            return asset($this->logo_image);
        }
        
        return asset('images/default-shop-logo.png');
    }

    public function getCoverUrlFullAttribute(): string
    {
        if ($this->cover_image && Storage::disk('public')->exists(str_replace('/storage', '', $this->cover_image))) {
            return asset($this->cover_image);
        }
        
        return asset('images/default-shop-cover.jpg');
    }

    public function getLogoUrlAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }
        
        return $value;
    }

    public function getCoverUrlAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }
        
        return $value;
    }

    public function hasLogo(): bool
    {
        return !empty($this->logo_image) && Storage::disk('public')->exists(str_replace('/storage', '', $this->logo_image));
    }

    public function hasCover(): bool
    {
        return !empty($this->cover_image) && Storage::disk('public')->exists(str_replace('/storage', '', $this->cover_image));
    }
}
