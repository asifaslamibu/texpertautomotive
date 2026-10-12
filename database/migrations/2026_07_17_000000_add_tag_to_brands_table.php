<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (! Schema::hasColumn('brands', 'tag')) {
                // Badge shown on every product of this brand, e.g. "Ships from manufacturer".
                $table->string('tag', 60)->nullable()->after('link');
            }
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (Schema::hasColumn('brands', 'tag')) {
                $table->dropColumn('tag');
            }
        });
    }
};
