# Controllers
## Shop controller
```php
<?php

namespace App\Http\Controllers\Shops;

use App\Http\Controllers\Controller;
use App\Models\Shops\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ShopController extends Controller
{
    public function index()
    {
        $shops = Auth::user()->shops()->latest()->get();
        
        return inertia('Shops/Index', [
            'shops' => $shops,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:80',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string',
            'logo_url' => 'nullable|url',
            'cover_url' => 'nullable|url',
        ]);

        $shop = Auth::user()->shops()->create($validated);

        return redirect()->back()->with('success', 'Shop created successfully');
    }

    public function update(Request $request, Shop $shop)
    {
        Gate::authorize('update', $shop);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:80',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string',
            'logo_url' => 'nullable|url',
            'cover_url' => 'nullable|url',
        ]);

        $shop->update($validated);

        return redirect()->back()->with('success', 'Shop updated successfully');
    }

    public function destroy(Shop $shop)
    {
        Gate::authorize('delete', $shop);

        $shop->delete();

        return redirect()->back()->with('success', 'Shop deleted successfully');
    }

    public function toggleActive(Shop $shop)
    {
        Gate::authorize('update', $shop);

        $shop->update(['is_active' => !$shop->is_active]);

        return redirect()->back()->with('success', 'Shop status updated');
    }
}



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

# Models
## User Model
```php
<?php

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

    // Address relationships
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }

    // Shop relationships
    public function shops(): HasMany
    {
        return $this->hasMany(Shop::class, 'owner_id');
    }

    public function activeShop(): HasOne
    {
        return $this->hasOne(Shop::class, 'owner_id')->where('is_active', true);
    }

    // Order relationships
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function pendingOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id')->where('status', 0); // 0 = pending
    }

    // Cart relationship
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    // Wishlist relationship
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function wishlistedProducts()
    {
        return $this->belongsToMany(Product::class, 'wishlists');
    }

    // Reviews relationship
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // Notifications relationship
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)->where('is_read', false);
    }

    // Helper methods
    public function isAdmin(): bool
    {
        return $this->role === UserRoles::ADMIN || $this->role === UserRoles::SUPER_ADMIN;
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

    // Account deletion (anonymization)
    public function deleteAccount(): void
    {
        \DB::transaction(function () {
            $this->update([
                'full_name' => 'Deleted User #' . $this->id,
                'email' => 'deleted_' . $this->id . '@anonymized.local',
                'image' => null,
                'deleted_at' => now(),
                'is_anonymized' => true,
            ]);
            
            $this->addresses()->delete();
            $this->cart()?->delete();
            \DB::table('sessions')->where('user_id', $this->id)->delete();
        });
    }
}
```

## Shop Model
```php
<?php

namespace App\Models\Shops;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Review;
use App\Models\Post;
use App\Models\Discount;
use App\Models\Coupon;
use App\Models\VendorPayout;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'settings' => 'array',
    ];

    // Owner relationship
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // Products relationship
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

    // Orders relationship
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

    // Reviews relationship
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function averageRating()
    {
        return $this->reviews()->where('is_approved', true)->avg('rating');
    }

    // Posts relationship (social)
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // Discounts relationship
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

    // Coupons relationship
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

    // Payouts relationship
    public function payouts(): HasMany
    {
        return $this->hasMany(VendorPayout::class);
    }

    // Helper methods
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

    // Scopes
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


# Upgradable Shops Logic
```php
<?php
// database/migrations/2024_01_01_000001_add_shop_limits_to_users.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('max_shops')->default(1); // Maximum shops allowed
            $table->timestamp('shop_limit_expires_at')->nullable(); // For subscription-based limits
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['max_shops', 'shop_limit_expires_at']);
        });
    }
};



<?php

namespace App\Models;

// ... existing imports

class User extends Authenticatable
{
    // ... existing code

    /**
     * Check if user can create more shops
     */
    public function canCreateMoreShops(): bool
    {
        // Check if subscription expired
        if ($this->shop_limit_expires_at && now()->gt($this->shop_limit_expires_at)) {
            return false;
        }
        
        return $this->shops()->count() < $this->max_shops;
    }

    /**
     * Get remaining shop slots
     */
    public function remainingShopSlots(): int
    {
        $usedSlots = $this->shops()->count();
        return max(0, $this->max_shops - $usedSlots);
    }

    /**
     * Get current shop limit
     */
    public function getShopLimit(): int
    {
        return $this->max_shops;
    }

    /**
     * Upgrade shop limit (after payment)
     */
    public function upgradeShopLimit(int $newLimit, ?int $daysValid = null): void
    {
        $this->max_shops = $newLimit;
        
        if ($daysValid) {
            $this->shop_limit_expires_at = now()->addDays($daysValid);
        }
        
        $this->save();
    }
}


<?php

namespace App\Http\Controllers\Shops;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Shops\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Requests\Shops\ShopRequest;

class ShopController extends Controller
{
    protected function user(): User
    {
        return Auth::user();
    }

    public function index()
    {
        $shops = $this->user()->shops()
            ->latest()
            ->paginate(10);

        return inertia('shops/Index', [
            'shops' => $shops,
            'canCreateMore' => $this->user()->canCreateMoreShops(),
            'remainingSlots' => $this->user()->remainingShopSlots(),
            'maxShops' => $this->user()->getShopLimit(),
        ]);
    }

    public function create()
    {
        // Check if user can create more shops
        if (!$this->user()->canCreateMoreShops()) {
            return redirect()->route('shops.index')->with([
                'message' => 'You have reached your shop limit. Upgrade to create more shops.',
                'type' => 'error'
            ]);
        }

        return inertia('shops/Create', [
            'remainingSlots' => $this->user()->remainingShopSlots(),
        ]);
    }

    public function store(ShopRequest $request)
    {
        // Double-check limit before creating
        if (!$this->user()->canCreateMoreShops()) {
            return redirect()->route('shops.index')->with([
                'message' => 'You cannot create more shops. Please upgrade your plan.',
                'type' => 'error'
            ]);
        }

        $validated = $request->validated();
        
        // Create shop first without images
        $shop = $this->user()->shops()->create($validated);
        
        // Handle logo upload with shop ID
        if ($request->hasFile('logo')) {
            $logoPath = $this->uploadImageWithShopId($request->file('logo'), 'logo', $shop);
            $shop->update(['logo_url' => $logoPath]);
        }
        
        // Handle cover upload with shop ID
        if ($request->hasFile('cover')) {
            $coverPath = $this->uploadImageWithShopId($request->file('cover'), 'cover', $shop);
            $shop->update(['cover_url' => $coverPath]);
        }

        return redirect()->route('shops.index')->with([
            'message' => "Shop {$shop->name} created successfully",
            'type' => 'success'
        ]);
    }

    // ... rest of your existing methods

    private function uploadImageWithShopId($file, string $type, Shop $shop): string
    {
        $extension = $file->getClientOriginalExtension();
        $user = $this->user();
        $timestamp = now()->format('Ymd_His');
        $slug = Str::slug($shop->name, '_');
        
        $filename = "{$type}_{$user->id}_{$shop->id}_{$slug}_{$timestamp}.{$extension}";
        $directory = $type === 'logo' ? 'shops/logos' : 'shops/covers';
        
        return $file->storeAs($directory, $filename, 'public');
    }
}

<?php
// app/Http/Controllers/Subscription/ShopLimitController.php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShopLimitController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $plans = [
            [
                'name' => 'Free',
                'price' => 0,
                'shops' => 1,
                'duration' => null,
                'features' => ['1 Shop', 'Basic Support', 'Up to 50 Products'],
            ],
            [
                'name' => 'Basic',
                'price' => 9.99,
                'shops' => 3,
                'duration' => 30, // days
                'features' => ['3 Shops', 'Priority Support', 'Up to 200 Products', 'Analytics'],
            ],
            [
                'name' => 'Pro',
                'price' => 29.99,
                'shops' => 10,
                'duration' => 30,
                'features' => ['10 Shops', '24/7 Support', 'Unlimited Products', 'Advanced Analytics', 'Bulk Import'],
            ],
            [
                'name' => 'Enterprise',
                'price' => 99.99,
                'shops' => -1, // unlimited
                'duration' => 30,
                'features' => ['Unlimited Shops', 'Dedicated Support', 'Custom Development', 'API Access'],
            ],
        ];
        
        return inertia('Upgrade/Index', [
            'plans' => $plans,
            'currentLimit' => $user->max_shops,
            'currentShops' => $user->shops()->count(),
        ]);
    }
    
    public function processUpgrade(Request $request)
    {
        $validated = $request->validate([
            'plan' => 'required|in:basic,pro,enterprise',
            'payment_method' => 'required|string',
        ]);
        
        // Process payment here (Stripe, PayPal, etc.)
        
        $user = Auth::user();
        
        switch ($validated['plan']) {
            case 'basic':
                $user->upgradeShopLimit(3, 30);
                break;
            case 'pro':
                $user->upgradeShopLimit(10, 30);
                break;
            case 'enterprise':
                $user->upgradeShopLimit(999, 30); // Unlimited effectively
                break;
        }
        
        return redirect()->route('shops.index')->with([
            'message' => 'Your plan has been upgraded successfully!',
            'type' => 'success'
        ]);
    }
}

// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::get('/upgrade', [ShopLimitController::class, 'index'])->name('upgrade');
    Route::post('/upgrade', [ShopLimitController::class, 'processUpgrade'])->name('upgrade.process');
});
```

```ts
<!-- resources/js/pages/Shops/Create.vue -->
<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ImagePlus, X, AlertCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import shops from '@/routes/shops';

interface Props {
    remainingSlots: number;
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    description: '',
    category: '',
    contact_email: '',
    contact_phone: '',
    logo: null as File | null,
    cover: null as File | null,
});

const logoPreview = ref<string | null>(null);
const coverPreview = ref<string | null>(null);

// Handle logo file selection
const handleLogoChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

// Handle cover file selection
const handleCoverChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.cover = file;
        coverPreview.value = URL.createObjectURL(file);
    }
};

// Remove logo
const removeLogo = () => {
    form.logo = null;
    if (logoPreview.value) {
        URL.revokeObjectURL(logoPreview.value);
        logoPreview.value = null;
    }
};

// Remove cover
const removeCover = () => {
    form.cover = null;
    if (coverPreview.value) {
        URL.revokeObjectURL(coverPreview.value);
        coverPreview.value = null;
    }
};

const submitForm = () => {
    form.post(shops.store().url, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit('/shops');
        },
    });
};
</script>

<template>
    <Head title="Create Shop" />

    <div class="max-w-2xl mx-auto py-8 px-4">
        <div class="mb-6">
            <h1 class="text-2xl font-serif font-semibold">Create New Shop</h1>
            <p class="text-sm text-gray-500 mt-1">
                You have {{ remainingSlots }} shop slot{{ remainingSlots !== 1 ? 's' : '' }} remaining
            </p>
        </div>

        <!-- Limit Warning -->
        <div v-if="remainingSlots === 0" class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg flex items-start gap-3">
            <AlertCircle class="w-5 h-5 text-yellow-600 mt-0.5" />
            <div>
                <h3 class="text-sm font-medium text-yellow-800">Shop limit reached</h3>
                <p class="text-sm text-yellow-700 mt-1">
                    You've reached your maximum number of shops. 
                    <a href="/upgrade" class="font-medium underline">Upgrade your plan</a> to create more shops.
                </p>
            </div>
        </div>

        <form v-else @submit.prevent="submitForm" class="space-y-6 bg-white p-6 rounded-xl border border-gray-200">
            <!-- Rest of your form remains the same -->
            <!-- ... -->
        </form>
    </div>
</template>



<!-- Add to your shops index page -->
<div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
    <div class="flex justify-between items-center">
        <div>
            <p class="text-sm text-blue-800">
                You have used {{ shops.data.length }} of {{ maxShops }} shop slots
                ({{ remainingSlots }} remaining)
            </p>
        </div>
        <div v-if="remainingSlots === 0">
            <Button as-child variant="outline">
                <a href="/upgrade">Upgrade Plan</a>
            </Button>
        </div>
    </div>
    <div class="mt-2 w-full bg-blue-200 rounded-full h-2">
        <div 
            class="bg-blue-600 h-2 rounded-full transition-all"
            :style="{ width: `${(shops.data.length / maxShops) * 100}%` }"
        ></div>
    </div>
</div>
```

# General 
## Alternative for image
```ts
<!-- Logo -->
<div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0">
    <img 
        v-if="shop.logo_image" 
        :src="shop.logo_image" 
        :alt="shop.name"
        class="w-full h-full object-cover"
    />
    <Store v-else class="w-5 h-5 text-gray-400" />
</div>
```

## EOF













