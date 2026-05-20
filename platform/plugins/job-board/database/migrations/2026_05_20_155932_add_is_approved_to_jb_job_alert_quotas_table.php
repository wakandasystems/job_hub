<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_job_alert_quotas', function (Blueprint $table): void {
            // null = free tier (always active), true = paid & approved, false = paid but pending
            $table->boolean('is_approved')->nullable()->default(null)->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('jb_job_alert_quotas', function (Blueprint $table): void {
            $table->dropColumn('is_approved');
        });
    }
};
