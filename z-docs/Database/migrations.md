# Migrations
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
    $table->string('logo_image')->nullable();
    $table->string('cover_image')->nullable();
    $table->string('contact_email')->nullable();
    $table->string('contact_phone')->nullable();
    $table->boolean('is_active')->default(true);
    $table->boolean('is_verified')->default(false);
    $table->json('settings')->nullable(); // Store shop preferences
    $table->foreignId('shop_category_id')->nullable()->constrained('shop_categories')->nullOnDelete();
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
    $table->decimal('cost_price', 12, 2)->nullable();
    $table->decimal('price', 12, 2);
    $table->integer('stock_qty')->default(0);
    $table->integer('low_stock_threshold')->default(5);
    $table->integer('reserved_stock')->default(0); // For pending orders
    $table->json('attributes')->nullable(); // {"brand": "Nike", "material": "Cotton"}
    $table->boolean('is_featured')->default(false);
    $table->boolean('is_active')->default(true);
    $table->string('barcode', 50)->nullable()->unique();
    $table->decimal('weight', 10, 2)->nullable();
    $table->string('weight_units')->nullable();
    // For SEO
    $table->string('meta_title', 60)->nullable();
    $table->string('meta_description', 160)->nullable();
    $table->string('canonical_url')->nullable();
    $table->json('structured_data')->nullable(); // For rich snippets
    // Rating
    $table->unsignedTinyInteger('average_rating')->default(0);
    $table->integer('total_reviews')->default(0);
    $table->integer('total_sold')->default(0);
    // Relationships
    $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
    $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
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
    $table->integer('sort_order')->default(0);
    $table->unsignedTinyInteger('type')->default(1);
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    $table->timestamps();

    $table->index(['product_id', 'sort_order']);
});

Schema::create('product_variants', function (Blueprint $table) {
    $table->id();
    $table->string('sku')->unique();
    $table->json('attributes')->nullable(); // {"color": "red", "size": "M"}
    $table->decimal('cost_price', 12, 2)->nullable();
    $table->decimal('price_adjustment', 12, 2)->default(0);
    $table->string('barcode', 50)->nullable();
    $table->integer('low_stock_threshold')->default(5);
    $table->integer('stock_qty')->default(0);
    $table->string('image')->nullable();
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    $table->softDeletes();
    $table->timestamps();

    $table->index('sku');
});

Schema::create('product_views', function (Blueprint $table) {
    $table->id();
    $table->string('ip_address', 45)->nullable();
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    
    $table->index(['product_id', 'created_at']);
});

Schema::create('product_specifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    $table->string('name'); // e.g., "Brand", "Material", "Warranty"
    $table->string('value'); // e.g., "Nike", "Cotton", "2 Years"
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    
    $table->index(['product_id', 'sort_order']);
});

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

Schema::create('discounts', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('name');

    // Discount value
    $table->decimal('value', 12, 2);
    $table->unsignedTinyInteger('type')->default(0); // 0=percentage, 1=fixed_amount

    // Scope: what this discount applies to
    // 0=shop_wide, 1=product_category, 2=specific_products
    $table->unsignedTinyInteger('scope')->default(0);
    $table->json('target_ids')->nullable(); // product or category ids when scope = 1 or 2
    
    // Conditions
    $table->decimal('min_order_amount', 12, 2)->nullable();
    $table->integer('min_quantity')->nullable();
    
    // Schedule
    $table->timestamp('starts_at');
    $table->timestamp('expires_at');
    
    $table->boolean('is_active')->default(true);
    $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
    $table->timestamps();
    
    $table->index(['shop_id', 'is_active', 'starts_at', 'expires_at']);
    $table->index(['scope', 'is_active']);
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
    $table->foreignId('product_id')->constrained('products')->nullOnDelete();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
    $table->timestamps();

    $table->unique(['cart_id', 'product_id', 'variant_id']);
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
    $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete(); // Keep order if coupon deleted;
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
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
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