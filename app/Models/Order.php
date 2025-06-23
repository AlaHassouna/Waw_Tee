<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'payment_method',
        'payment_status',
        'payment_intent_id',
        'payment_details',
        'currency',
        'subtotal',
        'shipping_cost',
        'tax_amount',
        'total_amount',
        'total',
        'first_name',
        'last_name',
        'email',
        'phone',
        'street',
        'city',
        'state',
        'zip_code',
        'country',
        'shipping_address',
        'billing_address',
        'tracking_number',
        'notes',
    ];

    protected $casts = [
        'payment_details' => 'array',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'pending',
        'payment_status' => 'pending',
        'currency' => 'EUR',
        'shipping_cost' => 0,
        'tax_amount' => 0,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentErrors()
    {
        return $this->hasMany(PaymentError::class);
    }

    // Accessor pour s'assurer que total est défini
    public function getTotalAttribute($value)
    {
        return $value ?? $this->total_amount;
    }

    // Mutator pour synchroniser total et total_amount
    public function setTotalAttribute($value)
    {
        $this->attributes['total'] = $value;
        $this->attributes['total_amount'] = $value;
    }
}
