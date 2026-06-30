<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_sales_agent_campaign_leads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_agent_id')->constrained('jb_sales_agents')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('jb_sales_agent_campaigns')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('jb_accounts')->nullOnDelete();
            $table->string('sales_agent_code', 30);
            $table->string('candidate_name', 150);
            $table->string('candidate_phone', 40);
            $table->string('candidate_email', 150)->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('product_type', 30);
            $table->string('product_label', 120)->nullable();
            $table->string('promo_price', 30)->nullable();
            $table->string('promo_original_price', 30)->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('public_token', 40)->unique();
            $table->timestamp('notified_admin_at')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('onboarded_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'jb_sales_agent_campaign_leads_status_created_idx');
            $table->index('candidate_phone', 'jb_sales_agent_campaign_leads_phone_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_sales_agent_campaign_leads');
    }
};
