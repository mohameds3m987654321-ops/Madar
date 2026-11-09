<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();

            // الطالب صاحب التقدم
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // المحاضرة المرتبطة بالتقدم
            $table->foreignId('lesson_id')
                ->constrained('lessons')
                ->cascadeOnDelete();

            // آخر موضع وصل إليه الطالب بالثواني
            $table->unsignedInteger('last_position')->default(0);

            // مدة الفيديو بالثواني
            $table->unsignedInteger('duration')->default(0);

            // نسبة التقدم من 0 إلى 100
            $table->unsignedTinyInteger('progress')->default(0);

            // أول مرة بدأ فيها الطالب المحاضرة
            $table->timestamp('started_at')->nullable();

            // هل أكمل المحاضرة؟
            $table->boolean('completed')->default(false);

            // وقت إكمال المحاضرة
            $table->timestamp('completed_at')->nullable();


            $table->timestamps();

            // سجل واحد لكل طالب في كل محاضرة
            $table->unique(['user_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
    }
};
