<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_message_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('automation_id')->nullable()->index();
            $table->string('chat_id', 64)->index();
            $table->string('message_id', 64);
            $table->unsignedBigInteger('job_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_message_log');
    }
};
