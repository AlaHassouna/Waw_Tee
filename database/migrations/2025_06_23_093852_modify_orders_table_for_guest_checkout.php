<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Rendre user_id nullable pour permettre les commandes d'invités
            $table->foreignId('user_id')->nullable()->change();
            
            // Ajouter les champs d'adresse de livraison directement dans la table orders
            $table->string('first_name')->after('user_id');
            $table->string('last_name')->after('first_name');
            $table->string('email')->after('last_name');
            $table->string('phone')->after('email');
            $table->string('street')->after('phone');
            $table->string('city')->after('street');
            $table->string('state')->after('city');
            $table->string('zip_code')->after('state');
            $table->string('country', 2)->after('zip_code');
            
            // Ajouter des champs pour les montants
            $table->decimal('shipping_cost', 10, 2)->default(0)->after('total');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('shipping_cost');
            $table->decimal('total_amount', 10, 2)->after('tax_amount');
            
            // Ajouter un index pour la recherche par email (pour les invités)
            $table->index('email');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Remettre user_id comme non nullable
            $table->foreignId('user_id')->nullable(false)->change();
            
            // Supprimer les champs ajoutés
            $table->dropColumn([
                'first_name',
                'last_name', 
                'email',
                'phone',
                'street',
                'city',
                'state',
                'zip_code',
                'country',
                'shipping_cost',
                'tax_amount',
                'total_amount'
            ]);
            
            // Supprimer l'index email
            $table->dropIndex(['email']);
        });
    }
};
