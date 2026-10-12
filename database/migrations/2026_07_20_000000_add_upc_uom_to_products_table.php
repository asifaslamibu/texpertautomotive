<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'upc')) {
                $table->string('upc', 40)->nullable()->after('sku');       // barcode / UPCCode
            }
            if (! Schema::hasColumn('products', 'uom')) {
                $table->string('uom', 30)->nullable()->after('upc');        // unit of measure, e.g. "Pack"
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['upc', 'uom'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
