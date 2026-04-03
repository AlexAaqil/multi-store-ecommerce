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