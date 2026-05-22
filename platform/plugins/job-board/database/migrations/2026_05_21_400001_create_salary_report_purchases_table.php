<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_salary_report_purchases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->constrained('jb_salary_reports')->cascadeOnDelete();
            $table->string('buyer_name');
            $table->string('buyer_email');
            $table->string('buyer_company')->nullable();
            $table->decimal('amount_paid', 10, 2)->unsigned()->default(0);
            $table->string('currency_code', 10)->default('USD');
            $table->string('payment_channel')->nullable();
            $table->string('charge_id')->nullable();
            $table->uuid('access_token')->unique();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_salary_report_purchases');
    }
};
