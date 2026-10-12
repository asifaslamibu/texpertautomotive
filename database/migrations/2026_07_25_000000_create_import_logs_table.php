<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();          // kept even if the staff account is later removed
            $table->string('original_name');                  // the file the admin picked
            $table->string('stored_path')->nullable();        // where we saved a copy, for re-download
            $table->unsignedInteger('size')->default(0);      // bytes
            $table->unsignedInteger('rows')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('categories_count')->default(0);
            $table->unsignedInteger('brands_count')->default(0);
            $table->unsignedInteger('images_count')->default(0);
            $table->boolean('download_images')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
