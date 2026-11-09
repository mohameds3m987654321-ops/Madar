<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_post_id')->constrained('course_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['course_post_id', 'created_at']);
        });

        Schema::create('course_post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_post_id')->constrained('course_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['course_post_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_post_likes');
        Schema::dropIfExists('course_post_comments');
    }
};
