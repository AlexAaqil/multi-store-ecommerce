# Services
## Cart
```php
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
```

## Order
```php
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