# General notes
```php
// How product variants work:

// Example: T-shirt with size and color variants

// Products table (parent product)
Product::create([
    'name' => 'Classic T-Shirt',
    'price' => 29.99, // Base price
    'shop_id' => 1
]);

// Product variants (actual sellable items)
$variants = [
    [
        'product_id' => 1,
        'sku' => 'TS-RED-S',
        'attributes' => json_encode(['color' => 'Red', 'size' => 'S']),
        'price_adjustment' => 0, // Same as base price
        'stock_qty' => 50
    ],
    [
        'product_id' => 1,
        'sku' => 'TS-RED-M',
        'attributes' => json_encode(['color' => 'Red', 'size' => 'M']),
        'price_adjustment' => 0,
        'stock_qty' => 75
    ],
    [
        'product_id' => 1,
        'sku' => 'TS-BLUE-XL',
        'attributes' => json_encode(['color' => 'Blue', 'size' => 'XL']),
        'price_adjustment' => 5.00, // $34.99 total
        'stock_qty' => 30
    ]
];

// In cart/order, you reference variant_id, not product_id
// Product price calculation:
$finalPrice = $product->price + $variant->price_adjustment;

// Real-world inventory tracking:
// - Product has total stock (sum of variants)
// - Each variant tracks its own stock
// - When variant sells out, only that combination is unavailable
```


// COUPONS (User must enter code):
// - "WELCOME10" for 10% off first order
// - "FREESHIPPING" for free shipping
// - "FRIEND20" referral code
// These are user-initiated, trackable per user

// DISCOUNTS (Automatic, no code needed):
// - Black Friday: 20% off everything automatically
// - Buy 2 Get 1 Free (BOGO)
// - Summer sale: 15% off electronics category
// - Volume discount: 10% off when buying 5+ items


// LEVEL 1: CANCEL (Not deletion)
// Use when: Customer changes mind, item out of stock
// Effect: Order exists but status = 'cancelled'
// Financials: Reversed, but still in reports

// LEVEL 2: VOID (Soft deletion)
// Use when: Test order, duplicate, never should have existed
// Effect: Order hidden from normal views, but exists for audit
// Financials: Never counted in reports

// LEVEL 3: HARD DELETE (Rare)
// Use when: Integration test data, development seeding
// Effect: Completely removed from database
// Financials: Never existed



# Products
## Tables and Reasoning
### Product Specifications Table
Why: Amazon and Alibaba use this for product comparison. Customers want to compare "Screen Size", "Processor", "RAM" between products. Without this, you can't offer structured comparison.

Example: A phone product would have specs: "Display: 6.7 inches", "Battery: 5000mAh", "Camera: 108MP"
```php
Schema::create('product_specifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    $table->string('name'); // e.g., "Brand", "Material", "Warranty"
    $table->string('value'); // e.g., "Nike", "Cotton", "2 Years"
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    
    $table->index(['product_id', 'sort_order']);
});
```

### Inventory Locations Table
Why: Shopify Plus and Amazon Multi-Channel Fulfillment use this. A seller might have inventory in Nairobi, Mombasa, and Kisumu. Without this, you can't track where products are located or fulfill orders from the nearest location.

Example: Customer in Mombasa orders a product - system checks Mombasa warehouse first, then Nairobi, then Kisumu.
```php
Schema::create('inventory_locations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
    $table->string('name'); // "Main Warehouse", "Nairobi Store"
    $table->string('address')->nullable();
    $table->string('city')->nullable();
    $table->string('country')->nullable();
    $table->boolean('is_default')->default(false);
    $table->timestamps();
});

Schema::create('inventory_stocks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
    $table->foreignId('location_id')->constrained('inventory_locations');
    $table->integer('quantity')->default(0);
    $table->integer('reserved')->default(0);
    $table->timestamps();
    
    $table->unique(['product_id', 'variant_id', 'location_id']);
});
```

### Product Questions and Answers Table
Why: Amazon's #1 trust feature. Customers read Q&A before buying. Without this, potential buyers can't ask questions about products before purchasing.

Example: "Does this laptop come with a carry bag?" - Answered by seller: "Yes, free carry bag included"
```php
Schema::create('product_questions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->text('question');
    $table->integer('helpful_count')->default(0);
    $table->integer('unhelpful_count')->default(0);
    $table->boolean('is_answered')->default(false);
    $table->timestamps();
    
    $table->index(['product_id', 'created_at']);
});

Schema::create('product_answers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('question_id')->constrained('product_questions')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->text('answer');
    $table->integer('helpful_count')->default(0);
    $table->integer('unhelpful_count')->default(0);
    $table->boolean('is_verified_purchase')->default(false); // Amazon shows "Verified Purchase" badge
    $table->timestamps();
});
```

### Product Bundels Table
Why: Amazon and Shopify use bundles to increase average order value. "Buy the laptop + mouse + bag together and save 15%". Without this, you lose upsell opportunities.

Example: "Camera Bundle" includes Camera + Lens + Memory Card + Bag at 20% discount.
```php
Schema::create('product_bundles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('parent_product_id')->constrained('products')->cascadeOnDelete();
    $table->foreignId('child_product_id')->constrained('products')->cascadeOnDelete();
    $table->integer('quantity')->default(1);
    $table->decimal('discount_percentage', 5, 2)->default(0); // Bundle discount
    $table->timestamps();
    
    $table->unique(['parent_product_id', 'child_product_id']);
});
```

### Product Backorders & Pre-orders Table
Why: Alibaba and Amazon allow pre-orders for upcoming products. "iPhone 15 - Pre-order now, ships Sept 22". Without this, sellers can't generate buzz and capture early sales.

Example: "PS5 - Pre-order now for delivery in December. Pay only 50% deposit."
```php
Schema::create('product_backorders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
    $table->integer('max_quantity')->nullable(); // Maximum backorder quantity
    $table->timestamp('expected_available_at'); // When stock will arrive
    $table->timestamps();
});

Schema::create('product_preorders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
    $table->decimal('preorder_price', 12, 2)->nullable(); // Often discounted
    $table->timestamp('release_date');
    $table->timestamp('preorder_ends_at');
    $table->timestamps();
});
```

### Product Import/Export Logs Table
Why: Shopify and WooCommerce have bulk import/export. Sellers with 1000+ products can't add them one by one. Without this, large sellers won't use your platform.

Example: Seller uploads CSV with 500 products, system processes in background, emails report when done.
```php
Schema::create('product_imports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
    $table->string('filename');
    $table->string('file_path');
    $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
    $table->integer('total_rows')->default(0);
    $table->integer('processed_rows')->default(0);
    $table->integer('success_rows')->default(0);
    $table->integer('failed_rows')->default(0);
    $table->json('errors')->nullable();
    $table->foreignId('imported_by')->constrained('users');
    $table->timestamps();
    
    $table->index(['shop_id', 'status']);
});
```

### Product Reviews with Media Table
Why: Amazon reviews with images sell more products. "Verified Purchase" badges build trust. Without this, your reviews are less credible.

Example: Customer posts photo of product in use - 500 people find it helpful.
```php
Schema::create('review_images', function (Blueprint $table) {
    $table->id();
    $table->foreignId('review_id')->constrained('reviews')->cascadeOnDelete();
    $table->string('image_url');
    $table->string('thumbnail_url')->nullable();
    $table->integer('sort_order')->default(0);
    $table->timestamps();
});

Schema::create('review_helpful', function (Blueprint $table) {
    $table->id();
    $table->foreignId('review_id')->constrained('reviews')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->boolean('is_helpful');
    $table->timestamps();
    
    $table->unique(['review_id', 'user_id']);
});
```

### Product Wishlist Notifications Table
Why: Amazon notifies customers when wishlist items drop in price. This drives sales. Without this, customers forget about products they liked.

Example: Customer adds $1000 laptop to wishlist. Price drops to $900 → automatic email notification.
```php
Schema::create('wishlist_notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('wishlist_id')->constrained('wishlists')->cascadeOnDelete();
    $table->enum('type', ['price_drop', 'back_in_stock', 'on_sale']);
    $table->decimal('target_price', 12, 2)->nullable(); // Notify when price drops below this
    $table->boolean('is_sent')->default(false);
    $table->timestamp('sent_at')->nullable();
    $table->timestamps();
});
```

# Orders
```php
// ============================================
// Usage Examples:
// ============================================

// Monthly revenue report (excludes cancelled, void, test)
$revenue = Order::revenueRelevant()
    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
    ->sum('total_amount');

// Admin sees everything (including voided)
$allOrders = Order::adminView()->get();

// Clean up old test orders (hard delete)
Order::testOrders()
    ->where('created_at', '<', now()->subDays(7))
    ->where('payment_status', '!=', PaymentStatus::PAID)
    ->each(function ($order) {
        $order->forceDelete();
    });
```

Artisan command to cleanup test orders:
```php
<?php
namespace App\Console\Commands;

class CleanupOrders extends Command
{
    protected $signature = 'orders:cleanup {--test-only : Only clean test orders}';
    
    public function handle()
    {
        if ($this->option('test-only')) {
            // Hard delete old test orders
            $count = Order::testOrders()
                ->where('created_at', '<', now()->subDays(7))
                ->where('payment_status', '!=', PaymentStatus::PAID)
                ->get()
                ->filter->canBePermanentlyDeleted()
                ->each->permanentDelete();
                
            $this->info("Permanently deleted {$count} test orders");
        } else {
            // Soft delete old pending orders
            $count = Order::where('status', OrderStatus::PENDING)
                ->where('created_at', '<', now()->subDays(30))
                ->whereNull('deleted_at')
                ->each(function ($order) {
                    $order->void(User::find(1), 'Automated cleanup after 30 days');
                });
                
            $this->info("Voided {$count} abandoned orders");
        }
    }
}
```

# EOF