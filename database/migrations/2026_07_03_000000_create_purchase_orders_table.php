<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();       // e.g. PO-0001
            $table->date('po_date');

            // Distributor / supplier the PO is sent to
            $table->string('distributor_name');
            $table->string('distributor_phone')->nullable();
            $table->string('distributor_email')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();

            // Line items: [{description, qty, price, discount}]
            $table->json('items')->nullable();

            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('shipping', 12, 2)->default(0);
            $table->text('notes')->nullable();

            $table->string('status')->default('draft')->index();  // draft | sent
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
