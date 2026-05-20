<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_job_alert_orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('package_id');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('pending')->comment('pending, approved, rejected, cancelled');
            $table->string('payment_method', 50)->nullable();
            $table->string('charge_id', 100)->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('jb_accounts')->cascadeOnDelete();
            $table->foreign('package_id')->references('id')->on('jb_job_alert_packages')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_job_alert_orders');
    }
};
