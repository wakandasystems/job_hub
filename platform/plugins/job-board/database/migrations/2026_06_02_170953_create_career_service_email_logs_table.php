<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_career_service_email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('jb_career_service_orders')->cascadeOnDelete();
            $table->string('to_email');
            $table->string('subject');
            $table->text('body');
            $table->string('sent_by')->default('Admin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_career_service_email_logs');
    }
};
