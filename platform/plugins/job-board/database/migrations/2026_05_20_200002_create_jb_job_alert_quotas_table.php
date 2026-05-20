<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_job_alert_quotas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('package_id')->nullable()->comment('null = free tier');
            $table->string('period', 7)->comment('YYYY-MM');
            $table->smallInteger('alerts_allowed')->default(3)->comment('-1 = unlimited');
            $table->unsignedSmallInteger('alerts_sent')->default(0);
            $table->string('charge_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'period', 'package_id'], 'jb_job_alert_quotas_unique');
            $table->foreign('account_id')->references('id')->on('jb_accounts')->cascadeOnDelete();
            $table->foreign('package_id')->references('id')->on('jb_job_alert_packages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_job_alert_quotas');
    }
};
