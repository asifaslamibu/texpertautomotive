<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whatever a buyer typed on the checkout page, saved as they type — so an
     * abandoned checkout can still be followed up. One row per browser session.
     */
    public function up(): void
    {
        Schema::create('checkout_leads', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 40)->nullable();
            $table->string('company')->nullable();
            $table->string('address')->nullable();
            $table->string('city', 80)->nullable();
            $table->string('state', 80)->nullable();
            $table->string('zip', 20)->nullable();
            $table->text('notes')->nullable();
            $table->json('items')->nullable();          // cart snapshot [{name, sku, qty, price}]
            $table->decimal('cart_total', 10, 2)->default(0);
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('ordered_at')->nullable(); // set when the checkout converted
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_leads');
    }
};
