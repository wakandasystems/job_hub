<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_sales_agent_campaigns', function (Blueprint $table): void {
            if (! Schema::hasColumn('jb_sales_agent_campaigns', 'product_type')) {
                $table->string('product_type', 30)->default('auto_apply')->after('name');
            }

            if (! Schema::hasColumn('jb_sales_agent_campaigns', 'product_label')) {
                $table->string('product_label', 120)->nullable()->after('product_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jb_sales_agent_campaigns', function (Blueprint $table): void {
            if (Schema::hasColumn('jb_sales_agent_campaigns', 'product_label')) {
                $table->dropColumn('product_label');
            }

            if (Schema::hasColumn('jb_sales_agent_campaigns', 'product_type')) {
                $table->dropColumn('product_type');
            }
        });
    }
};
