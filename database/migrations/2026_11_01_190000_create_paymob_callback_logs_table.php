<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paymob_callback_logs', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 128)->nullable()->index();
            $table->string('transaction_id', 128)->nullable()->index();
            $table->string('integration_id', 128)->nullable();
            $table->string('request_method', 8);
            $table->string('ip_address', 45)->nullable();
            $table->char('payload_hash', 64);
            $table->boolean('signature_valid')->index();
            $table->string('outcome', 40)->default('received')->index();
            $table->string('failure_reason', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paymob_callback_logs');
    }
};
