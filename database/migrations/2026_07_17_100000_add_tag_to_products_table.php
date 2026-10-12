<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'tag')) {
                // Per-product badge text, used only when tag_mode = custom.
                $table->string('tag', 60)->nullable()->after('brand');
            }
            if (! Schema::hasColumn('products', 'tag_mode')) {
                // inherit = use the brand's tag · custom = use this row's tag · none = show nothing
                $table->string('tag_mode', 10)->default('inherit')->after('tag');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['tag', 'tag_mode'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
