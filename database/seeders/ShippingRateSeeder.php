<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use Illuminate\Database\Seeder;

class ShippingRateSeeder extends Seeder
{
    public function run(): void
    {
        $shippingRates = [
            [
                'country' => [
                    'name' => 'United States',
                    'code' => 'US',
                ],
                'default_rate' => 9.99,
                'free_shipping_threshold' => 75.00,
                'estimated_days' => [
                    'min' => 3,
                    'max' => 7,
                ],
                'cities' => [
                    ['name' => 'New York', 'rate' => 12.99],
                    ['name' => 'Los Angeles', 'rate' => 14.99],
                ],
                'is_active' => true,
                'currency' => 'USD',
            ],
            [
                'country' => [
                    'name' => 'France',
                    'code' => 'FR',
                ],
                'default_rate' => 7.99,
                'free_shipping_threshold' => 50.00,
                'estimated_days' => [
                    'min' => 2,
                    'max' => 5,
                ],
                'cities' => [
                    ['name' => 'Paris', 'rate' => 9.99],
                    ['name' => 'Lyon', 'rate' => 8.99],
                ],
                'is_active' => true,
                'currency' => 'EUR',
            ],
        ];

        foreach ($shippingRates as $rate) {
            ShippingRate::create($rate);
        }
    }
}
