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
        Schema::table('orders', function (Blueprint $table) {
            // Update the status enum to include 'confirmed'
            $table->enum('status', [
                'pending',
                'confirmed',
                'processing', 
                'completed',
                'shipped',
                'delivered',
                'cancelled',
                'refunded',
                'failed'
            ])->default('pending')->change();
        });

        \Log::info('Orders status enum updated to include confirmed status');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert to previous enum values
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
        });
    }
};
