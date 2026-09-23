<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('route_name')->nullable();
            $table->string('method', 10);
            $table->string('url');
            $table->json('params')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('session_id')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->smallInteger('response_code')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('exception')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
