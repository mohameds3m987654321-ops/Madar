<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('education_stage', 20)->nullable()->after('price');
            $table->string('education_grade', 10)->nullable()->after('education_stage');
            $table->string('education_system', 20)->nullable()->after('education_grade');
            $table->string('education_type', 20)->nullable()->after('education_system');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'education_stage',
                'education_grade',
                'education_system',
                'education_type',
            ]);
        });
    }
};
