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
            // Make the 'total' field nullable or add a default value
            $table->decimal('total', 10, 2)->nullable()->change();
            
            // Also make other legacy fields nullable for compatibility
            $table->decimal('tax', 10, 2)->nullable()->change();
            $table->decimal('shipping', 10, 2)->nullable()->change();
            $table->decimal('discount', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert changes
            $table->decimal('total', 10, 2)->nullable(false)->change();
            $table->decimal('tax', 10, 2)->nullable(false)->change();
            $table->decimal('shipping', 10, 2)->nullable(false)->change();
            $table->decimal('discount', 10, 2)->nullable(false)->change();
        });
    }
};
