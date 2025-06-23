<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentError extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'stripe_payment_intent_id',
        'payment_intent_id',
        'stripe_error_id',
        'error_code',
        'error_message',
        'error_type',
        'decline_code',
        'amount',
        'currency',
        'customer_email',
        'metadata',
        'resolved',
        'resolved_at',
        'resolved_by',
        'notes',
    ];

    protected $casts = [
        'metadata' => 'array',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    protected $attributes = [
        'error_code' => 'unknown',
        'error_message' => 'Unknown error',
        'error_type' => 'general_error',
        'amount' => 0,
        'currency' => 'EUR',
        'customer_email' => '',
        'resolved' => false,
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
