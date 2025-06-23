<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('email', 100)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone', 20)->nullable();
            $table->json('address')->nullable();
            $table->enum('role', ['customer', 'admin'])->default('customer');
            $table->boolean('is_active')->default(true);
            $table->string('remember_token')->nullable();
            $table->string('reset_token')->nullable();
            $table->timestamp('reset_token_expires')->nullable();
            $table->timestamps();

            $table->index(['email', 'is_active']);
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
