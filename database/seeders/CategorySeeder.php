<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'title' => 'T-Shirts',
                'slug' => 't-shirts',
                'description' => 'Comfortable and stylish t-shirts for all occasions',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Hoodies',
                'slug' => 'hoodies',
                'description' => 'Warm and cozy hoodies perfect for casual wear',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Accessories',
                'slug' => 'accessories',
                'description' => 'Complete your look with our accessories collection',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'title' => 'Pants',
                'slug' => 'pants',
                'description' => 'Comfortable pants for everyday wear',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
