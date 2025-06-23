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
        Schema::table('products', function (Blueprint $table) {
            // Vérifier si les colonnes n'existent pas déjà avant de les ajouter
            if (!Schema::hasColumn('products', 'slug')) {
                $table->string('slug')->unique()->after('title');
            }
            if (!Schema::hasColumn('products', 'sku')) {
                $table->string('sku')->unique()->nullable()->after('slug');
            }
            if (!Schema::hasColumn('products', 'variants')) {
                $table->json('variants')->nullable()->after('description');
            }
            if (!Schema::hasColumn('products', 'colors')) {
                $table->json('colors')->nullable()->after('variants');
            }
            if (!Schema::hasColumn('products', 'sizes')) {
                $table->json('sizes')->nullable()->after('colors');
            }
            if (!Schema::hasColumn('products', 'tags')) {
                $table->json('tags')->nullable()->after('sizes');
            }
            if (!Schema::hasColumn('products', 'images')) {
                $table->json('images')->nullable()->after('tags');
            }
            if (!Schema::hasColumn('products', 'track_quantity')) {
                $table->boolean('track_quantity')->default(false)->after('price');
            }
            if (!Schema::hasColumn('products', 'quantity')) {
                $table->integer('quantity')->default(0)->after('track_quantity');
            }
            if (!Schema::hasColumn('products', 'allow_backorder')) {
                $table->boolean('allow_backorder')->default(false)->after('quantity');
            }
            if (!Schema::hasColumn('products', 'requires_shipping')) {
                $table->boolean('requires_shipping')->default(true)->after('allow_backorder');
            }
            if (!Schema::hasColumn('products', 'taxable')) {
                $table->boolean('taxable')->default(true)->after('requires_shipping');
            }
            if (!Schema::hasColumn('products', 'compare_price')) {
                $table->decimal('compare_price', 10, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('products', 'sales_count')) {
                $table->integer('sales_count')->default(0)->after('taxable');
            }
            if (!Schema::hasColumn('products', 'view_count')) {
                $table->integer('view_count')->default(0)->after('sales_count');
            }
            if (!Schema::hasColumn('products', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columnsToCheck = [
                'slug', 'sku', 'variants', 'colors', 'sizes', 'tags', 'images',
                'track_quantity', 'quantity', 'allow_backorder', 'requires_shipping',
                'taxable', 'compare_price', 'sales_count', 'view_count', 'is_featured'
            ];

            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};