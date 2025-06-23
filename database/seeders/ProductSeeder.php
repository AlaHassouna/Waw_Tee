<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tshirtCategory = Category::where('slug', 't-shirts')->first();
        $hoodieCategory = Category::where('slug', 'hoodies')->first();
        $accessoryCategory = Category::where('slug', 'accessories')->first();

        $products = [
            [
                'title' => 'Classic White T-Shirt',
                'slug' => 'classic-white-t-shirt',
                'description' => 'A timeless classic white t-shirt made from 100% cotton.',
                'price' => 19.99,
                'compare_price' => 24.99,
                'sku' => 'TSH-WHT-001',
                'category_id' => $tshirtCategory->id,
                'images' => ['/placeholder.svg?height=400&width=400'],
                'variants' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                    ['name' => 'Color', 'values' => ['White', 'Black', 'Gray']],
                ],
                'is_active' => true,
                'is_featured' => true,
                'quantity' => 100,
            ],
            [
                'title' => 'Premium Hoodie',
                'slug' => 'premium-hoodie',
                'description' => 'Ultra-soft premium hoodie with kangaroo pocket.',
                'price' => 49.99,
                'compare_price' => 59.99,
                'sku' => 'HOD-PRM-001',
                'category_id' => $hoodieCategory->id,
                'images' => ['/placeholder.svg?height=400&width=400'],
                'variants' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL', 'XXL']],
                    ['name' => 'Color', 'values' => ['Black', 'Navy', 'Gray', 'Burgundy']],
                ],
                'is_active' => true,
                'is_featured' => true,
                'quantity' => 50,
            ],
            [
                'title' => 'Baseball Cap',
                'slug' => 'baseball-cap',
                'description' => 'Adjustable baseball cap with embroidered logo.',
                'price' => 24.99,
                'sku' => 'CAP-BSB-001',
                'category_id' => $accessoryCategory->id,
                'images' => ['/placeholder.svg?height=400&width=400'],
                'variants' => [
                    ['name' => 'Color', 'values' => ['Black', 'Navy', 'Red', 'White']],
                ],
                'is_active' => true,
                'quantity' => 75,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
