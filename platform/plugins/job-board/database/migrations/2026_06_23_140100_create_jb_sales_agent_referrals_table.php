<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_sales_agent_referrals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sales_agent_id');
            // Sticky phone -> agent mapping: first code use wins, so this must be unique.
            $table->string('phone', 30)->unique();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('code_used', 30)->nullable();
            $table->string('source', 20); // job_alert, vip_alert, auto_apply, career_service, cv_bot
            $table->timestamp('first_used_at')->nullable();
            $table->timestamps();

            $table->index('sales_agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_sales_agent_referrals');
    }
};
