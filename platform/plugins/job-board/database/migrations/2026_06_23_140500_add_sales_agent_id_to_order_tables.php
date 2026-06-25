<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_job_alert_orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('sales_agent_id')->nullable()->after('account_id');
        });

        Schema::table('jb_vip_alert_orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('sales_agent_id')->nullable()->after('candidate_phone');
        });

        Schema::table('jb_auto_apply_orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('sales_agent_id')->nullable()->after('account_id');
        });

        Schema::table('jb_career_service_orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('sales_agent_id')->nullable()->after('candidate_id');
        });

        Schema::table('jb_auto_cv_sessions', function (Blueprint $table): void {
            $table->unsignedBigInteger('sales_agent_id')->nullable()->after('admin_id');
        });
    }

    public function down(): void
    {
        Schema::table('jb_job_alert_orders', function (Blueprint $table): void {
            $table->dropColumn('sales_agent_id');
        });

        Schema::table('jb_vip_alert_orders', function (Blueprint $table): void {
            $table->dropColumn('sales_agent_id');
        });

        Schema::table('jb_auto_apply_orders', function (Blueprint $table): void {
            $table->dropColumn('sales_agent_id');
        });

        Schema::table('jb_career_service_orders', function (Blueprint $table): void {
            $table->dropColumn('sales_agent_id');
        });

        Schema::table('jb_auto_cv_sessions', function (Blueprint $table): void {
            $table->dropColumn('sales_agent_id');
        });
    }
};
