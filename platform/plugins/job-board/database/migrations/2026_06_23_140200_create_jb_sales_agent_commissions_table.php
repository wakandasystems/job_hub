<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_sales_agent_commissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sales_agent_id');
            $table->string('order_type', 30); // job_alert_order, vip_alert_order, auto_apply_order, career_service_order
            $table->unsignedBigInteger('order_id');
            $table->decimal('amount', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 10, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('status', 20)->default('unpaid'); // unpaid, paid
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('sales_agent_id');
            $table->index(['order_type', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_sales_agent_commissions');
    }
};
