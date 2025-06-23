<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->json('country');
            $table->decimal('default_rate', 8, 2);
            $table->decimal('free_shipping_threshold', 10, 2)->nullable();
            $table->json('estimated_days');
            $table->json('cities')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('currency', 3)->default('EUR');
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
