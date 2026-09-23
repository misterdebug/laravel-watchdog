<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('success')->default(false);
            $table->timestamps();

            $table->index('created_at');
            $table->index('email');
            $table->index('ip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
