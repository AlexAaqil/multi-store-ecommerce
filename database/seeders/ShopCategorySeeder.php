<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Shops\ShopCategory;

class ShopCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shop_categories = [
            'Art & Crafts',
            'Beauty',
            'Clothing',
            'Computers',
            'Drinks',
            'Food',
            'Electronics',
            'Home & Living',
            'Health',
            'Phones',
            'Shoes'
        ];

        foreach ($shop_categories as $category) {
            ShopCategory::create([
                'name' => $category,
            ]);
        }
    }
}
