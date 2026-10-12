<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('type', 10)->default('fixed');       // fixed = $ off, percent = % off
            $table->decimal('value', 10, 2)->default(0);
            $table->decimal('min_subtotal', 10, 2)->default(0); // order has to reach this before the code applies
            $table->unsignedInteger('max_uses')->nullable();    // null = no limit
            $table->unsignedInteger('used_count')->default(0);
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('active')->default(true);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // The welcome email already promises a code, so that code has to exist.
        $code = strtoupper(trim((string) Setting::get('newsletter_code', 'WELCOME5'))) ?: 'WELCOME5';
        DB::table('coupons')->insert([
            'code' => $code,
            'type' => 'fixed',
            'value' => 5,
            'min_subtotal' => 0,
            'active' => true,
            'note' => 'Newsletter sign-up — $5 off',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'coupon_code')) {
                $table->string('coupon_code', 40)->nullable()->after('discount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'coupon_code')) {
                $table->dropColumn('coupon_code');
            }
        });
        Schema::dropIfExists('coupons');
    }
};
