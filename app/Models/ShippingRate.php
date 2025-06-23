<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'country',
        'default_rate',
        'free_shipping_threshold',
        'estimated_days',
        'cities',
        'is_active',
        'currency',
    ];

    protected $casts = [
        'country' => 'array',
        'default_rate' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'estimated_days' => 'array',
        'cities' => 'array',
        'is_active' => 'boolean',
    ];

    public function calculateShipping($city = null, $orderTotal = 0)
    {
        // Parse country if it's a string
        $country = is_string($this->country) ? json_decode($this->country, true) : $this->country;
        
        // Parse estimated_days if it's a string
        $estimatedDays = is_string($this->estimated_days) ? json_decode($this->estimated_days, true) : $this->estimated_days;
        
        // Check for free shipping
        if ($this->free_shipping_threshold && $orderTotal >= $this->free_shipping_threshold) {
            return [
                'cost' => 0,
                'method' => 'Free Shipping',
                'estimated_days' => $estimatedDays,
                'free_shipping' => true,
                'currency' => $this->currency,
            ];
        }

        $rate = $this->default_rate;

        // Check for city-specific rates
        if ($city && $this->cities) {
            $cities = is_string($this->cities) ? json_decode($this->cities, true) : $this->cities;
            
            if (is_array($cities)) {
                foreach ($cities as $cityRate) {
                    if (isset($cityRate['name']) && strtolower($cityRate['name']) === strtolower($city)) {
                        $rate = $cityRate['rate'];
                        break;
                    }
                }
            }
        }

        return [
            'cost' => $rate,
            'method' => 'Standard Shipping',
            'estimated_days' => $estimatedDays,
            'free_shipping' => false,
            'currency' => $this->currency,
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}