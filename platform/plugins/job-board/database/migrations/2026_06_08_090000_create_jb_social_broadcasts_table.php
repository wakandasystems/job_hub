<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_social_broadcasts', function (Blueprint $table): void {
            $table->id();
            $table->text('message');
            $table->string('image_path')->nullable();
            $table->string('status', 20)->default('pending'); // pending, scheduled, sent, failed, cancelled
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->json('results')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_social_broadcasts');
    }
};
