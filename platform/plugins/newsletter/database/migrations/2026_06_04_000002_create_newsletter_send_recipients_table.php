<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_send_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('newsletter_send_id');
            $table->string('email', 180);
            $table->string('name', 180)->nullable();
            $table->string('status', 10)->default('sent');  // sent | failed (only successes stored long-term)
            $table->timestamp('created_at')->useCurrent();

            $table->index(['email', 'created_at']);              // dedup window lookups
            $table->index(['newsletter_send_id', 'email']);      // resend targeting
            $table->foreign('newsletter_send_id')
                ->references('id')->on('newsletter_sends')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_send_recipients');
    }
};
