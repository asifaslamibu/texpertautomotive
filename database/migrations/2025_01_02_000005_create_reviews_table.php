<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->cascadeOnDelete();
            $table->string('author_name');
            $table->string('author_role')->nullable();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('body');
            $table->boolean('is_published')->default(true);
            $table->boolean('featured')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
