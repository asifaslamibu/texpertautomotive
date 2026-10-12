<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (! Schema::hasColumn('brands', 'tag_enabled')) {
                // Lets a brand's badge be switched off without losing the wording.
                // Defaults on, so brands already carrying a tag keep showing it.
                $table->boolean('tag_enabled')->default(true)->after('tag');
            }
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (Schema::hasColumn('brands', 'tag_enabled')) {
                $table->dropColumn('tag_enabled');
            }
        });
    }
};
