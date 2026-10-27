<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watchdog_security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('route_name')->nullable();
            $table->string('method', 10);
            $table->string('url', 2048);
            $table->json('params')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referer', 2048)->nullable();
            $table->unsignedSmallInteger('response_code');
            $table->timestamps();

            $table->index('created_at');
            $table->index('ip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watchdog_security_logs');
    }
};
