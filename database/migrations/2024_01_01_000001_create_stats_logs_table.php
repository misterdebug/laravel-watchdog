<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stats_logs', function (Blueprint $table) {
            $table->id();
            $table->string('route_name')->nullable();
            $table->string('method', 10);
            $table->string('url', 2048);
            $table->json('params')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('session_id')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->unsignedSmallInteger('response_code');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('response_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stats_logs');
    }
};
