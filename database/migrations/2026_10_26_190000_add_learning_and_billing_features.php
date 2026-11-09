<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('code', 48);
            $table->string('type', 16);
            $table->decimal('value', 10, 2);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['course_id', 'code']);
        });

        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('discount_cents')->default(0);
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_enrollment_id')->unique()
                ->constrained('course_enrollments')->cascadeOnDelete();
            $table->string('status', 16)->default('pending');
            $table->timestamps();
            $table->index(['coupon_id', 'status']);
        });

        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_enrollment_id')->unique()
                ->constrained('course_enrollments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->string('status', 16)->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::table('course_quizzes', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->unsignedSmallInteger('time_limit_minutes')->default(30);
        });

        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('question_order');
            $table->timestamp('expires_at');
            $table->timestamp('submitted_at')->nullable();
            $table->string('status', 16)->default('active');
            $table->timestamps();
            $table->index(['user_id', 'course_quiz_id', 'status']);
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->foreignId('quiz_session_id')->nullable()
                ->unique()
                ->constrained('quiz_sessions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quiz_session_id');
        });
        Schema::dropIfExists('quiz_sessions');
        Schema::table('course_quizzes', function (Blueprint $table) {
            $table->dropColumn(['max_attempts', 'time_limit_minutes']);
        });
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('coupon_redemptions');
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn('discount_cents');
        });
        Schema::dropIfExists('coupons');
    }
};
