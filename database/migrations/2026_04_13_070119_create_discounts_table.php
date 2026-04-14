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
            $table->decimal('value', 12, 2); // Discount value
            $table->unsignedTinyInteger('type')->default(0); // 0=percentage, 1=fixed_amount
            $table->unsignedTinyInteger('scope')->default(0); // 0=shop_wide, 1=product_category, 2=specific_products
            $table->decimal('min_order_amount', 12, 2)->nullable();
            $table->integer('min_quantity')->nullable();
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
