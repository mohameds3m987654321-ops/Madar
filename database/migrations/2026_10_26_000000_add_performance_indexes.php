<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->index(['teacher_id', 'status'], 'courses_teacher_status_index');
        });

        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->index(['course_id', 'status'], 'enrollments_course_status_index');
        });

        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->index(['lesson_id', 'user_id'], 'progress_lesson_user_index');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->dropIndex('progress_lesson_user_index');
        });

        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropIndex('enrollments_course_status_index');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('courses_teacher_status_index');
        });
    }
};
