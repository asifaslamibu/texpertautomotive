<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            // Stripe references — NO raw card number / CVV is ever stored.
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_payment_method_id')->unique();
            // Safe, displayable card metadata only
            $table->string('brand')->nullable();        // visa, mastercard…
            $table->string('last4', 4)->nullable();
            $table->unsignedSmallInteger('exp_month')->nullable();
            $table->unsignedSmallInteger('exp_year')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_cards');
    }
};
