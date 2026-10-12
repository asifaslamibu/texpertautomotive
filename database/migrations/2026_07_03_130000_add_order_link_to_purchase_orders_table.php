<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
            $table->dropColumn('approved_at');
        });
    }
};
