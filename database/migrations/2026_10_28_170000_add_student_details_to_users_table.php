<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('student_phone', 20)->nullable()->after('role');
            $table->string('parent_phone', 20)->nullable()->after('student_phone');
            $table->string('education_stage', 20)->nullable()->after('parent_phone');
            $table->string('education_type', 20)->nullable()->after('education_stage');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'student_phone',
                'parent_phone',
                'education_stage',
                'education_type',
            ]);
        });
    }
};
