# Models
## Shop
```php
<?php

namespace App\Models\Shops;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Review;
use App\Models\Post;
use App\Models\Discount;
use App\Models\Coupon;
use App\Models\VendorPayout;

class Shop extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'settings' => 'array',
    ];

    public function owner(): BelongsTo
    { 
        return $this->belongsTo(User::class, 'owner_id'); 
    }

    public function products(): HasMany
    { 
        return $this->hasMany(Product::class); 
    }

    public function activeProducts(): HasMany
    {
        return $this->hasMany(Product::class)->where('is_active', true);
    }

    public function lowStockProducts(): HasMany
    {
        return $this->hasMany(Product::class)
            ->whereRaw('stock_qty <= low_stock_threshold')
            ->where('is_active', true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function pendingOrders(): HasMany
    {
        return $this->hasMany(Order::class)->where('status', 0); // 0 = pending
    }

    public function completedOrders(): HasMany
    {
        return $this->hasMany(Order::class)->where('status', 4); // 4 = delivered
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function averageRating()
    {
        return $this->reviews()->where('is_approved', true)->avg('rating');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class);
    }

    public function activeDiscounts(): HasMany
    {
        return $this->hasMany(Discount::class)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>=', now());
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function activeCoupons(): HasMany
    {
        return $this->hasMany(Coupon::class)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>=', now());
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(VendorPayout::class);
    }

    public function isOpen(): bool
    {
        return $this->is_active && !$this->trashed();
    }

    public function totalRevenue(): float
    {
        return $this->orders()
            ->where('payment_status', 1) // 1 = paid
            ->where('is_void', false)
            ->sum('total_amount');
    }

    public function totalSales(): int
    {
        return $this->orders()
            ->where('payment_status', 1)
            ->where('is_void', false)
            ->count();
    }

    public function totalProducts(): int
    {
        return $this->products()->where('is_active', true)->count();
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

## User
```php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use App\Models\Users\Address;
use App\Models\Shops\Shop;
use App\Models\Orders\Order;
use App\Models\Cart;
use App\Models\Wishlist;
use App\Models\Review;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Enums\UserRoles;
use App\Enums\UserStatuses;

class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRoles::class,
            'status' => UserStatuses::class,
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }

    public function shops(): HasMany
    {
        return $this->hasMany(Shop::class, 'owner_id');
    }

    public function activeShop(): HasOne
    {
        return $this->hasOne(Shop::class, 'owner_id')->where('is_active', true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function pendingOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id')->where('status', 0); // 0 = pending
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function wishlistedProducts()
    {
        return $this->belongsToMany(Product::class, 'wishlists');
    }
    
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)->where('is_read', false);
    }
    
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRoles::SUPER_ADMIN;
    }

    public function isSeller(): bool
    {
        return $this->role === UserRoles::SELLER;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRoles::CUSTOMER;
    }

    public function canSell(): bool
    {
        return $this->isSeller() || $this->isAdmin();
    }

    // Deletion Logic
    public function deleteAccount(): void
    {
        DB::transaction(function () {
            // 1. Anonymize personal data
            $this->update([
                'full_name' => 'Deleted User #' . $this->id,
                'email' => 'deleted_' . $this->id . '@anonymized.local',
                'image' => null,
                'phone' => null,
                'deleted_at' => now(),
                'is_anonymized' => true,
            ]);
            
            // 2. Remove personal addresses (keep order shipping addresses intact)
            $this->addresses()->delete();
            
            // 3. Clear active sessions
            DB::table('sessions')->where('user_id', $this->id)->delete();
            
            // 4. Orders remain for accounting (with anonymized snapshot)
            // Note: We already stored customer_name_snapshot at order time

            $this->cart()?->delete();
        });
    }

    // In Order model - store snapshot before saving
    protected static function booted()
    {
        static::creating(function ($order) {
            $order->customer_name_snapshot = $order->customer->full_name;
            $order->customer_email_snapshot = $order->customer->email;
        });
    }
}
```

## Product
```php
class Product extends Model
{
    // Get current best discount for this product
    public function getCurrentDiscountAttribute(): ?Discount
    {
        return Discount::where('shop_id', $this->shop_id)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>=', now())
            ->where(function($query) {
                $query->where('target_type', DiscountTargetType::ALL_PRODUCTS)
                    ->orWhere(function($q) {
                        $q->where('target_type', DiscountTargetType::CATEGORIES)
                          ->whereJsonContains('target_ids', $this->product_category_id);
                    })
                    ->orWhere(function($q) {
                        $q->where('target_type', DiscountTargetType::SPECIFIC_PRODUCTS)
                          ->whereJsonContains('target_ids', $this->id);
                    });
            })
            ->orderBy('priority', 'desc')
            ->first();
    }
    
    // Get final price after discount
    public function getFinalPriceAttribute(): float
    {
        $discount = $this->current_discount;
        
        if (!$discount) {
            return $this->price;
        }
        
        return match($discount->type) {
            DiscountType::PERCENTAGE => $this->price * (1 - $discount->value / 100),
            DiscountType::FIXED_AMOUNT => max(0, $this->price - $discount->value),
            default => $this->price,
        };
    }
    
    // Check if product is on offer
    public function getIsOnOfferAttribute(): bool
    {
        return !is_null($this->current_discount);
    }
}
```

## Order
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // ADD THIS

class Order extends Model
{
    use SoftDeletes; // ADD THIS

    protected $casts = [
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'voided_at' => 'datetime',
        'is_test_order' => 'boolean',
        'is_void' => 'boolean',
    ];
    
    // Auto-generate order number and snapshots
    protected static function booted()
    {
        static::creating(function ($order) {
            $order->order_number = 'ORD-' . strtoupper(uniqid());
            $order->customer_name_snapshot = $order->customer->full_name;
            $order->customer_email_snapshot = $order->customer->email;
        });
    }
    
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [OrderStatus::PENDING, OrderStatus::PROCESSING]);
    }

    /**
     * CANCEL: Change status, keep in database
     */
    public function cancel(string $reason): void
    {
        $this->update([
            'status' => OrderStatus::CANCELLED,
            'cancelled_at' => now(),
            'notes' => $reason
        ]);
        
        if ($this->payment_status !== PaymentStatus::PAID) {
            foreach ($this->items as $item) {
                $item->product->increment('stock_qty', $item->quantity);
            }
        }
    }

    /**
     * VOID: Soft delete, hide from reports
     */
    public function void(User $voidedBy, string $reason): void
    {
        DB::transaction(function () use ($voidedBy, $reason) {
            $this->update([
                'is_void' => true,
                'voided_at' => now(),
                'voided_by' => $voidedBy->id,
                'void_reason' => $reason,
                'status' => OrderStatus::CANCELLED
            ]);
            
            if ($this->payment_status !== PaymentStatus::PAID) {
                foreach ($this->items as $item) {
                    $item->product->increment('stock_qty', $item->quantity);
                }
            }
            
            $this->delete();
        });
    }
    
    /**
     * PERMANENT DELETE: Only for test orders
     */
    public function permanentDelete(User $deletedBy): bool
    {
        if (!$this->canBePermanentlyDeleted()) {
            return false;
        }
        
        DB::transaction(function () use ($deletedBy) {
            AuditLog::create([
                'user_id' => $deletedBy->id,
                'action' => 'order_permanently_deleted',
                'entity_type' => 'order',
                'entity_id' => $this->id,
                'old_data' => $this->toArray()
            ]);
            
            $this->items()->forceDelete();
            $this->payments()->forceDelete();
            $this->forceDelete();
        });
        
        return true;
    }
    
    public function canBePermanentlyDeleted(): bool
    {
        if (!$this->is_test_order) return false;
        if ($this->payment_status === PaymentStatus::PAID) return false;
        if ($this->created_at->diffInHours(now()) > 1) return false;
        return true;
    }
    
    public function isRevenueRelevant(): bool
    {
        return !$this->is_void 
            && $this->status !== OrderStatus::CANCELLED
            && $this->payment_status === PaymentStatus::PAID
            && !$this->trashed();
    }
    
    // Scopes (defined once)
    public function scopeRevenueRelevant($query)
    {
        return $query->where('is_void', false)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->where('payment_status', PaymentStatus::PAID)
            ->whereNull('deleted_at');
    }
    
    public function scopeTestOrders($query)
    {
        return $query->where('is_test_order', true);
    }
    
    public function scopeAdminView($query)
    {
        return $query->withTrashed();
    }
}
```

## Discount
```php
class Discount extends Model
{
    protected $casts = [
        'target_ids' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];
    
    // Check if discount applies to a product
    public function appliesToProduct(Product $product): bool
    {
        if (!$this->is_active || now()->lt($this->starts_at) || now()->gt($this->expires_at)) {
            return false;
        }
        
        return match($this->target_type) {
            DiscountTargetType::ALL_PRODUCTS => true,
            DiscountTargetType::CATEGORIES => in_array($product->product_category_id, $this->target_ids),
            DiscountTargetType::SPECIFIC_PRODUCTS => in_array($product->id, $this->target_ids),
            default => false,
        };
    }
    
    // Calculate discount amount
    public function calculateDiscount(Product $product, int $quantity = 1): float
    {
        return match($this->type) {
            DiscountType::PERCENTAGE => ($product->price * $this->value / 100) * $quantity,
            DiscountType::FIXED_AMOUNT => $this->value * $quantity,
            DiscountType::BOGO => floor($quantity / 2) * $product->price, // Buy 1 get 1 free
            DiscountType::VOLUME => $quantity >= 5 ? ($product->price * $this->value / 100) * $quantity : 0,
            default => 0,
        };
    }
}
```

# Policies
## Shop
```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Shops\Shop;

class ShopPolicy
{
    public function update(User $user, Shop $shop): bool
    {
        return $user->id === $shop->owner_id;
    }

    public function delete(User $user, Shop $shop): bool
    {
        return $user->id === $shop->owner_id;
    }
}
```

# EOF