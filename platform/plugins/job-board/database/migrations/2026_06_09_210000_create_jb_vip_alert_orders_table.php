<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_vip_alert_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('candidate_name', 100);
            $table->string('candidate_phone', 30);
            $table->string('candidate_email', 150);
            $table->string('plan', 20); // weekly, monthly, one_time
            $table->unsignedTinyInteger('duration_days');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('charge_id', 200)->nullable();
            $table->string('payment_method', 100)->nullable();
            $table->string('payment_status', 20)->default('pending'); // pending, paid, failed
            $table->string('admin_status', 20)->default('pending');   // pending, approved, rejected
            $table->json('filters')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('candidate_alert_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_vip_alert_orders');
    }
};
