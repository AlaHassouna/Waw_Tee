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
        Schema::table('payment_errors', function (Blueprint $table) {
            // Make error_code nullable or add default value
            $table->string('error_code')->nullable()->default('unknown')->change();
            
            // Add missing fields that might be referenced
            if (!Schema::hasColumn('payment_errors', 'stripe_error_id')) {
                $table->string('stripe_error_id')->nullable()->after('error_code');
            }
            
            if (!Schema::hasColumn('payment_errors', 'payment_intent_id')) {
                $table->string('payment_intent_id')->nullable()->after('stripe_error_id');
            }
            
            // Make other required fields nullable with defaults
            $table->string('error_message')->nullable()->default('Unknown error')->change();
            $table->string('error_type')->nullable()->default('general_error')->change();
            $table->decimal('amount', 10, 2)->nullable()->default(0)->change();
            $table->string('currency', 3)->nullable()->default('EUR')->change();
            $table->string('customer_email')->nullable()->default('')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_errors', function (Blueprint $table) {
            // Revert changes
            $table->string('error_code')->nullable(false)->change();
            $table->string('error_message')->nullable(false)->change();
            $table->string('error_type')->nullable(false)->change();
            $table->decimal('amount', 10, 2)->nullable(false)->change();
            $table->string('currency', 3)->nullable(false)->change();
            $table->string('customer_email')->nullable(false)->change();
            
            // Drop added columns if they exist
            if (Schema::hasColumn('payment_errors', 'stripe_error_id')) {
                $table->dropColumn('stripe_error_id');
            }
            
            if (Schema::hasColumn('payment_errors', 'payment_intent_id')) {
                $table->dropColumn('payment_intent_id');
            }
        });
    }
};
