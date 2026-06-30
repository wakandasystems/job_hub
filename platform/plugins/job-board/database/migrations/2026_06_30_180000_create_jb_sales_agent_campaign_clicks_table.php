<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('jb_sales_agent_campaign_clicks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_agent_id')->constrained('jb_sales_agents')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('jb_sales_agent_campaigns')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('referer', 2048)->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'sales_agent_id'], 'jb_sales_agent_campaign_clicks_campaign_agent_idx');
            $table->index(['sales_agent_id', 'created_at'], 'jb_sales_agent_campaign_clicks_agent_created_idx');
            $table->index(['campaign_id', 'created_at'], 'jb_sales_agent_campaign_clicks_campaign_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_sales_agent_campaign_clicks');
    }
};
