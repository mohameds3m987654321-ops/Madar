<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_video_uploads', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->unsignedSmallInteger('lesson_order');
            $table->unsignedInteger('video_duration_seconds');
            $table->unsignedInteger('total_chunks')->default(0);
            $table->string('status', 20)->default('uploading');
            $table->timestamps();

            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_video_uploads');
    }
};
