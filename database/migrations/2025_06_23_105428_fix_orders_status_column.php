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
        // First, let's see what values we currently have
        $currentStatuses = DB::table('orders')->select('status')->distinct()->get();
        $currentPaymentStatuses = DB::table('orders')->select('payment_status')->distinct()->get();
        
        \Log::info('Current order statuses before migration:', [
            'statuses' => $currentStatuses->pluck('status')->toArray(),
            'payment_statuses' => $currentPaymentStatuses->pluck('payment_status')->toArray()
        ]);

        // Update existing data to match new enum values
        // Map old values to new values
        $statusMapping = [
            'pending' => 'pending',
            'processing' => 'processing',
            'completed' => 'completed',
            'paid' => 'completed',  // Map 'paid' status to 'completed'
            'shipped' => 'shipped',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled',  // Handle typo variations
            'refunded' => 'refunded',
            'failed' => 'failed'
        ];

        $paymentStatusMapping = [
            'pending' => 'pending',
            'processing' => 'processing',
            'paid' => 'paid',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled',  // Handle typo variations
            'refunded' => 'refunded'
        ];

        // Update status column
        foreach ($statusMapping as $oldValue => $newValue) {
            DB::table('orders')
                ->where('status', $oldValue)
                ->update(['status' => $newValue]);
        }

        // Update payment_status column
        foreach ($paymentStatusMapping as $oldValue => $newValue) {
            DB::table('orders')
                ->where('payment_status', $oldValue)
                ->update(['payment_status' => $newValue]);
        }

        // Handle any remaining invalid values by setting them to default
        DB::table('orders')
            ->whereNotIn('status', ['pending', 'processing', 'completed', 'shipped', 'delivered', 'cancelled', 'refunded', 'failed'])
            ->update(['status' => 'pending']);

        DB::table('orders')
            ->whereNotIn('payment_status', ['pending', 'processing', 'paid', 'failed', 'cancelled', 'refunded'])
            ->update(['payment_status' => 'pending']);

        // Now modify the column structure
        Schema::table('orders', function (Blueprint $table) {
            // Modify the status column to allow the new values
            $table->enum('status', [
                'pending',
                'processing', 
                'completed',
                'shipped',
                'delivered',
                'cancelled',
                'refunded',
                'failed'
            ])->default('pending')->change();
            
            // Also ensure payment_status has the right values
            $table->enum('payment_status', [
                'pending',
                'processing',
                'paid',
                'failed',
                'cancelled',
                'refunded'
            ])->default('pending')->change();
        });

        \Log::info('Orders status migration completed successfully');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert to a more basic enum structure
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending')->change();
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending')->change();
        });
    }
};
