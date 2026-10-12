<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'shipping_service')) {
                $table->string('shipping_service', 80)->nullable()->after('shipping');   // e.g. "UPS Ground"
            }
            if (! Schema::hasColumn('orders', 'tracking_number')) {
                $table->string('tracking_number', 60)->nullable()->after('shipping_service');
            }
            if (! Schema::hasColumn('orders', 'tracking_carrier')) {
                $table->string('tracking_carrier', 40)->nullable()->default('UPS')->after('tracking_number');
            }
            if (! Schema::hasColumn('orders', 'label_path')) {
                $table->string('label_path', 255)->nullable()->after('tracking_carrier');   // stored UPS label (GIF/PDF)
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['shipping_service', 'tracking_number', 'tracking_carrier', 'label_path'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
