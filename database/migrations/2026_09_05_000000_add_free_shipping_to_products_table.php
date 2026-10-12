<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'free_shipping')) {
                // When true, having this product in the cart makes the whole order ship free.
                $table->boolean('free_shipping')->default(false)->after('in_stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'free_shipping')) {
                $table->dropColumn('free_shipping');
            }
        });
    }
};
