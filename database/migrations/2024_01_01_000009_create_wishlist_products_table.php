<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wishlist_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('variant')->nullable();
            $table->timestamp('added_at');
            $table->timestamps();

            $table->unique(['wishlist_id', 'product_id', 'variant']);
            $table->index(['wishlist_id', 'added_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_products');
    }
};
