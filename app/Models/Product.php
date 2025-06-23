<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// Supprimez cette ligne : use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory;
    // Supprimez cette ligne : use Searchable;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'price',
        'compare_price',
        'sku',
        'track_quantity',
        'quantity',
        'allow_backorder',
        'requires_shipping',
        'taxable',
        'is_active',
        'is_featured',
        'category_id',
        'variants',
        'colors',
        'sizes',
        'tags',
        'images',
        'sales_count',
        'view_count',
    ];

    protected $casts = [
        'variants' => 'array',
        'colors' => 'array',
        'sizes' => 'array',
        'tags' => 'array',
        'images' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'track_quantity' => 'boolean',
        'allow_backorder' => 'boolean',
        'requires_shipping' => 'boolean',
        'taxable' => 'boolean',
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Si vous supprimez Scout, vous devez implémenter une recherche simple
    public function scopeSearch($query, $term)
    {
        return $query->where('title', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%")
                    ->orWhere('tags', 'LIKE', "%{$term}%");
    }
}