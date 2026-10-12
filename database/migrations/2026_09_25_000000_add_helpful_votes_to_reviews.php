<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedInteger('helpful_yes')->default(0)->after('featured');
            $table->unsignedInteger('helpful_no')->default(0)->after('helpful_yes');
        });

        // One vote per visitor per review; the voter is remembered as a hash, so
        // no address is stored in readable form.
        Schema::create('review_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->string('voter_key', 64);
            $table->boolean('helpful');
            $table->timestamps();
            $table->unique(['review_id', 'voter_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_votes');
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['helpful_yes', 'helpful_no']);
        });
    }
};
