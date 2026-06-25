<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'jb_job_alert_orders',
            'jb_vip_alert_orders',
            'jb_auto_apply_orders',
            'jb_career_service_orders',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->decimal('sales_agent_original_amount', 10, 2)->nullable()->after('sales_agent_id');
                $table->decimal('sales_agent_discount_amount', 10, 2)->default(0)->after('sales_agent_original_amount');
                $table->string('sales_agent_code', 30)->nullable()->after('sales_agent_discount_amount');
            });
        }

        Schema::table('jb_auto_cv_sessions', function (Blueprint $table): void {
            $table->string('sales_agent_code', 30)->nullable()->after('sales_agent_id');
        });
    }

    public function down(): void
    {
        foreach ([
            'jb_job_alert_orders',
            'jb_vip_alert_orders',
            'jb_auto_apply_orders',
            'jb_career_service_orders',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn([
                    'sales_agent_original_amount',
                    'sales_agent_discount_amount',
                    'sales_agent_code',
                ]);
            });
        }

        Schema::table('jb_auto_cv_sessions', function (Blueprint $table): void {
            $table->dropColumn('sales_agent_code');
        });
    }
};
