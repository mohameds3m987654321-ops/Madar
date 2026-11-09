<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_inquiries', function (Blueprint $table) {
            $table->json('conversation')->nullable();
            $table->string('access_token_hash', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('support_inquiries', function (Blueprint $table) {
            $table->dropUnique(['access_token_hash']);
            $table->dropColumn(['conversation', 'access_token_hash']);
        });
    }
};
