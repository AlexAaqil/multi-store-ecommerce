<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
