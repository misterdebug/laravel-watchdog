<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watchdog_sent_emails', function (Blueprint $table) {
            $table->id();
            $table->string('from')->nullable();
            $table->json('recipients')->nullable();
            $table->text('subject')->nullable();
            $table->longText('html')->nullable();
            $table->longText('text')->nullable();
            $table->json('attachments')->nullable();
            $table->json('headers')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watchdog_sent_emails');
    }
};
