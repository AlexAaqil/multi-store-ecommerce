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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
