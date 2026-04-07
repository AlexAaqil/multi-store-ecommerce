# DB Design
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('name', 120);
    $table->string('email')->unique();
    $table->unsignedTinyInteger('role')->default(3);
    $table->string('image')->nullable();
    $table->timestamp('email_verified_at')->nullable();
    $table->boolean('is_anonymized')->default(false);
    $table->timestamp('deleted_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});

Schema::create('addresses', function (Blueprint $table) {
    $table->id();
    $table->string('address_line1');
    $table->string('address_line2')->nullable();
    $table->string('city', 100);
    $table->string('state', 100);
    $table->string('country', 100);
    $table->string('postal_code', 20);
    $table->string('phone', 20)->nullable();
    $table->string('recipient_name', 120)->nullable();
    $table->boolean('is_default')->default(false);
    $table->unsignedTinyInteger('type')->default(2);
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->timestamps();
});

Schema::create('shop_categories', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('name')->unique();
    $table->string('slug')->unique();
    $table->timestamps();
});

Schema::create('shops', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('name', 150);
    $table->string('slug')->unique();
    $table->string('custom_slug')->nullable()->unique();
    $table->text('description')->nullable();
    $table->string('logo_url')->nullable();
    $table->string('cover_url')->nullable();
    $table->string('contact_email')->nullable();
    $table->string('contact_phone')->nullable();
    $table->boolean('is_active')->default(true);
    $table->boolean('is_verified')->default(false);
    $table->json('settings')->nullable(); // Store shop preferences
    $table->foreignId('shop_category_id')->nullable()->constrained('shop_categories')->setNullOnDelete();
    $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
    $table->softDeletes();
    $table->timestamps();

    $table->unique(['owner_id', 'name']);

    $table->index(['is_active', 'is_verified']);
});

Schema::create('product_categories', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('name', 100)->unique();
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('icon')->nullable();
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->foreignId('parent_id')->nullable()->constrained('product_categories')->cascadeOnDelete();
    $table->softDeletes();
    $table->timestamps();

    $table->index('slug');
    $table->index('parent_id');
    $table->index(['is_active', 'sort_order']);
});

Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('name', 200);
    $table->string('slug')->unique();
    $table->string('sku')->unique();
    $table->text('description')->nullable();
    $table->decimal('price', 12, 2);
    $table->integer('stock_qty')->default(0);
    $table->integer('low_stock_threshold')->default(5);
    $table->integer('reserved_stock')->default(0); // For pending orders
    $table->json('attributes')->nullable(); // {"brand": "Nike", "material": "Cotton"}
    $table->boolean('is_active')->default(true);
    $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
    $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->setNullOnDelete();
    $table->softDeletes();
    $table->timestamps();

    $table->unique(['shop_id', 'name']);

    $table->index(['price', 'is_active']);
    $table->index(['shop_id', 'is_active']);
    $table->index('slug');
    $table->index('product_category_id');
    $table->index('sku');
});

Schema::create('product_images', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('alt_text')->nullable();
    $table->integer('sort_order')->default(0);
    $table->unsignedTinyInteger('type')->default(1);
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    $table->timestamps();

    $table->index(['product_id', 'sort_order']);
});

Schema::create('product_variants', function (Blueprint $table) {
    $table->id();
    $table->string('sku')->unique();
    $table->json('attributes'); // {"color": "red", "size": "M"}
    $table->decimal('price_adjustment', 12, 2)->default(0);
    $table->integer('stock_qty')->default(0);
    $table->string('image_url')->nullable();
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    $table->softDeletes();
    $table->timestamps();

    $table->index('sku');
    $table->index('attributes');
});

Schema::create('product_views', function (Blueprint $table) {
    $table->id();
    $table->string('ip_address', 45)->nullable();
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained('users')->setNullOnDelete();
    $table->timestamps();
    
    $table->index(['product_id', 'created_at']);
});

Schema::create('carts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete(); // One cart per user
    $table->timestamps();
});

Schema::create('cart_items', function (Blueprint $table) {
    $table->id();
    $table->integer('quantity')->check('quantity > 0');
    $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
    $table->foreignId('product_id')->constrained('products')->setNullOnDelete();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->setNullOnDelete();
    $table->timestamps();

    $table->unique(['cart_id', 'product_id', 'variant_id']);
});

Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('order_number')->unique();
    $table->decimal('subtotal', 12, 2);
    $table->decimal('discount_amount', 12, 2)->default(0);
    $table->decimal('shipping_cost', 12, 2)->default(0);
    $table->decimal('tax_amount', 12, 2)->default(0);
    $table->decimal('total_amount', 12, 2);
    $table->unsignedTinyInteger('status')->default(0);
    $table->unsignedTinyInteger('payment_method')->nullable();
    $table->unsignedTinyInteger('payment_status')->default(0);
    $table->text('notes')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->timestamp('cancelled_at')->nullable();
    // Snapshot of customer info at order time (for when user gets anonymized)
    $table->string('customer_name_snapshot', 150);
    $table->string('customer_email_snapshot', 200);
    // Types of deletion
    $table->boolean('is_test_order')->default(false);
    $table->boolean('is_void')->default(false);
    $table->timestamp('voided_at')->nullable();
    $table->foreignId('voided_by')->nullable()->constrained('users');
    $table->text('void_reason')->nullable();
    $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete(); // Don't delete shop with orders
    $table->foreignId('shipping_address_id')->constrained('addresses')->restrictOnDelete();
    $table->foreignId('billing_address_id')->constrained('addresses')->restrictOnDelete();
    $table->foreignId('coupon_id')->nullable()->constrained('coupons')->setNullOnDelete(); // Keep order if coupon deleted;
    $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
    $table->softDeletes();
    $table->timestamps();

    $table->index(['customer_id', 'status']);
    $table->index('order_number');
    $table->index('shop_id');
    $table->index(['status', 'payment_status']);
    $table->index('created_at');
    $table->index(['is_test_order', 'is_void']); // For cleanup queries
    $table->index('deleted_at'); // For soft delete queries
});

Schema::create('order_items', function (Blueprint $table) {
    $table->id();
    $table->integer('quantity');
    $table->decimal('unit_price', 12, 2);
    $table->decimal('discount', 12, 2)->default(0);
    $table->decimal('total_price', 12, 2);
    // Snapshot of product info (in case product is deleted later)
    $table->string('product_name_snapshot', 200);
    $table->string('product_sku_snapshot', 100);
    $table->json('product_variant_snapshot')->nullable();
    $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
    $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();
    $table->timestamps();

    $table->index(['order_id']);
    $table->index(['product_id']);
    $table->index(['order_id', 'product_id']);
});

Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('transaction_id')->unique(); // From payment gateway
    $table->decimal('amount', 12, 2);
    $table->unsignedTinyInteger('type')->default(0);
    $table->unsignedTinyInteger('status')->default(0);
    $table->unsignedTinyInteger('payment_method')->nullable();
    $table->json('gateway_response')->nullable(); // Store webhook data
    $table->string('failure_reason')->nullable();
    $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
    $table->timestamps();

    $table->index('transaction_id');
});

Schema::create('refunds', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->decimal('amount', 12, 2);
    $table->text('reason');
    $table->unsignedTinyInteger('status')->default(0);
    $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
    $table->foreignId('payment_id')->constrained('payments')->restrictOnDelete();
    $table->timestamps();
});

Schema::create('shipments', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('tracking_number')->nullable();
    $table->string('carrier', 50);
    $table->string('service_level')->nullable(); // Express, Standard, etc.
    $table->timestamp('shipped_at')->nullable();
    $table->timestamp('estimated_delivery')->nullable();
    $table->timestamp('delivered_at')->nullable();
    $table->unsignedTinyInteger('status')->default(0);
    $table->json('tracking_history')->nullable(); // Carrier tracking events
    $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
    $table->timestamps();

    $table->index('tracking_number');
});

Schema::create('vendor_payouts', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->decimal('amount', 12, 2);
    $table->decimal('platform_fee', 12, 2)->default(0);
    $table->decimal('net_amount', 12, 2);
    $table->unsignedTinyInteger('status')->default(0);
    $table->string('stripe_account_id')->nullable();
    $table->string('transfer_id')->nullable(); // Stripe/PayPal transfer ID
    $table->timestamp('paid_at')->nullable();
    $table->json('metadata')->nullable();
    $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
    $table->foreignId('order_id')->constrained('orders')->restrictOnDelete(); // Payout per order
    $table->timestamps();

    $table->index(['shop_id', 'status']);
});

Schema::create('coupons', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('code')->unique();
    $table->string('name');
    $table->unsignedTinyInteger('type')->default(0);
    $table->decimal('value', 12, 2);
    $table->decimal('min_order_amount', 12, 2)->nullable();
    $table->integer('usage_limit')->nullable();
    $table->integer('used_count')->default(0);
    $table->dateTime('starts_at');
    $table->dateTime('expires_at');
    $table->boolean('is_active')->default(true);
    $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
    $table->timestamps();

    $table->index('code');
    $table->index(['is_active', 'expires_at']);
});

Schema::create('coupon_redemptions', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
    $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->decimal('discount_amount', 12, 2);
    $table->timestamps();
});

Schema::create('discounts', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('name');
    $table->unsignedTinyInteger('type')->default(0);
    $table->decimal('value', 12, 2);
    
    // What it applies to
    $table->unsignedTinyInteger('target_type')->default(0);
    $table->json('target_ids')->nullable(); // [1,2,3] for category or product IDs
    
    // Conditions
    $table->decimal('min_order_amount', 12, 2)->nullable();
    $table->integer('min_quantity')->nullable();
    
    // Schedule
    $table->timestamp('starts_at');
    $table->timestamp('expires_at');
    
    // Priority (if multiple discounts apply)
    $table->integer('priority')->default(0); // Higher = applies first
    
    $table->boolean('is_active')->default(true);
    $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
    $table->timestamps();
    
    $table->index(['shop_id', 'is_active', 'expires_at']);
});

Schema::create('reviews', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->integer('rating')->check('rating BETWEEN 1 AND 5');
    $table->text('comment')->nullable();
    $table->json('images')->nullable();
    $table->boolean('is_approved')->default(false);
    $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete(); // Product-specific or shop review
    $table->timestamps();

    $table->unique(['user_id', 'product_id']); // One review per user per product

    $table->index(['shop_id', 'is_approved']);
    $table->index(['product_id', 'is_approved']);
    $table->index(['product_id', 'rating']);
});

Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->text('content');
    $table->json('media_urls')->nullable();
    $table->integer('likes_count')->default(0);
    $table->integer('comments_count')->default(0);
    $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
    $table->softDeletes();
    $table->timestamps();

    $table->index(['shop_id', 'created_at']);
});

Schema::create('post_likes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->timestamps();

    $table->unique(['post_id', 'user_id']);
});

Schema::create('comments', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->text('content');
    $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
    $table->softDeletes();
    $table->timestamps();

    $table->index(['post_id', 'created_at']);
});

Schema::create('wishlists', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    $table->timestamps();

    $table->unique(['user_id', 'product_id']);

    $table->index('user_id');
});

Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('action', 100);
    $table->string('entity_type', 50);
    $table->unsignedBigInteger('entity_id')->nullable();
    $table->json('old_data')->nullable();
    $table->json('new_data')->nullable();
    $table->ipAddress('ip_address')->nullable();
    $table->text('user_agent')->nullable();
    $table->foreignId('user_id')->nullable()->constrained('users')->setNullOnDelete();
    $table->timestamps();

    $table->index(['entity_type', 'entity_id']);
    $table->index('created_at');
});

Schema::create('notifications', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('type'); // order_created, payment_received, etc.
    $table->string('title');
    $table->text('content');
    $table->json('data')->nullable();
    $table->boolean('is_read')->default(false);
    $table->timestamp('read_at')->nullable();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->timestamps();

    $table->index(['user_id', 'is_read']);
    $table->index('created_at');
});
```

# Models
```php
// app/Models/User.php
public function shops() { return $this->hasMany(Shop::class, 'owner_id'); }
public function orders() { return $this->hasMany(Order::class, 'customer_id'); }
public function cart() { return $this->hasOne(Cart::class); }
public function wishlists() { return $this->hasMany(Wishlist::class); }

// app/Models/Shop.php
public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
public function products() { return $this->hasMany(Product::class); }
public function orders() { return $this->hasMany(Order::class); }
public function reviews() { return $this->hasMany(Review::class); }



// app/Models/User.php
use App\Enums\UserRole;

class User extends Authenticatable
{
    protected $casts = [
        'role' => UserRole::class,
        'email_verified_at' => 'datetime',
    ];
    
    public function shops() { return $this->hasMany(Shop::class, 'owner_id'); }
    public function orders() { return $this->hasMany(Order::class, 'customer_id'); }
    public function cart() { return $this->hasOne(Cart::class); }
    public function wishlists() { return $this->hasMany(Wishlist::class); }
    public function reviews() { return $this->hasMany(Review::class); }
    public function addresses() { return $this->hasMany(Address::class); }
    
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
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


// app/Models/Product.php
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



// app/Models/Discount.php
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

# Services
```php
// In your Cart calculation logic:
class CartService
{
    public function calculateTotal(Cart $cart): array
    {
        $subtotal = 0;
        $discountTotal = 0;
        $appliedDiscounts = [];
        
        foreach ($cart->items as $item) {
            $subtotal += $item->product->price * $item->quantity;
            
            // Apply automatic discounts
            $bestDiscount = Discount::where('shop_id', $item->product->shop_id)
                ->where('is_active', true)
                ->where('starts_at', '<=', now())
                ->where('expires_at', '>=', now())
                ->get()
                ->filter(fn($d) => $d->appliesToProduct($item->product))
                ->sortByDesc('priority')
                ->first();
            
            if ($bestDiscount) {
                $discountAmount = $bestDiscount->calculateDiscount($item->product, $item->quantity);
                $discountTotal += $discountAmount;
                $appliedDiscounts[] = $bestDiscount;
            }
        }
        
        // Then apply coupon if user entered one
        $couponDiscount = $this->applyCoupon($cart->user, $subtotal - $discountTotal);
        
        return [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'coupon_discount' => $couponDiscount,
            'total' => $subtotal - $discountTotal - $couponDiscount,
            'applied_discounts' => $appliedDiscounts,
        ];
    }
}





// ============================================
// TYPE 1: CANCEL (No deletion, just change status)
// ============================================
class OrderCancellationService
{
    public function cancel(Order $order, string $reason): void
    {
        // Order still exists, just marked as cancelled
        $order->update([
            'status' => OrderStatus::CANCELLED,
            'cancelled_at' => now(),
            'notes' => $reason
        ]);
        
        // Restore stock if payment wasn't made
        if ($order->payment_status !== PaymentStatus::PAID) {
            foreach ($order->items as $item) {
                $item->product->increment('stock_qty', $item->quantity);
            }
        }
        
        // Order still appears in reports, but as 'cancelled'
        // Financial reports show: "Revenue: $1000, Cancelled: $50, Net: $950"
    }
}

// ============================================
// TYPE 2: VOID (Soft delete - hides from reports)
// ============================================
class OrderVoidService
{
    public function void(Order $order, User $voidedBy, string $reason): void
    {
        DB::transaction(function () use ($order, $voidedBy, $reason) {
            // Mark as void BEFORE soft deleting
            $order->update([
                'is_void' => true,
                'voided_at' => now(),
                'voided_by' => $voidedBy->id,
                'void_reason' => $reason,
                'status' => OrderStatus::CANCELLED
            ]);
            
            // Restore stock if needed
            if ($order->payment_status !== PaymentStatus::PAID) {
                foreach ($order->items as $item) {
                    $item->product->increment('stock_qty', $item->quantity);
                }
            }
            
            // NOW soft delete (hides from normal queries)
            $order->delete(); // Sets deleted_at timestamp
            
            // Log for audit
            AuditLog::create([
                'action' => 'order_voided',
                'entity_type' => 'order',
                'entity_id' => $order->id,
                'new_data' => ['reason' => $reason, 'voided_by' => $voidedBy->id]
            ]);
        });
    }
}

// ============================================
// TYPE 3: HARD DELETE (Permanent - use with caution!)
// ============================================
class OrderHardDeleteService
{
    public function hardDelete(Order $order, User $deletedBy): void
    {
        // ONLY allowed for:
        // 1. Test orders that never touched real payments
        // 2. Development/staging data
        // 3. Orders created in error within last 5 minutes
        
        if (!$this->canHardDelete($order)) {
            throw new Exception("This order cannot be permanently deleted");
        }
        
        DB::transaction(function () use ($order, $deletedBy) {
            // Log before deletion
            AuditLog::create([
                'action' => 'order_permanently_deleted',
                'entity_type' => 'order',
                'entity_id' => $order->id,
                'old_data' => $order->toArray(),
                'user_id' => $deletedBy->id
            ]);
            
            // Delete related records
            $order->items()->forceDelete(); // Permanently delete order items
            $order->payments()->forceDelete(); // Permanently delete payments
            
            // Finally, permanently delete the order
            $order->forceDelete(); // Completely removes from database
        });
    }
    
    private function canHardDelete(Order $order): bool
    {
        // Only test orders can be hard deleted
        if (!$order->is_test_order) {
            return false;
        }
        
        // Can't hard delete if payment was processed
        if ($order->payment_status === PaymentStatus::PAID) {
            return false;
        }
        
        // Can't hard delete if older than 1 hour
        if ($order->created_at->diffInHours(now()) > 1) {
            return false;
        }
        
        return true;
    }
}
```