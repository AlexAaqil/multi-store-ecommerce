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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('name', 200);
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
