<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Percentage discount an admin can put on a manually created order. */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->default(0)->after('tax');
            $table->decimal('discount', 10, 2)->default(0)->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'discount']);
        });
    }
};
