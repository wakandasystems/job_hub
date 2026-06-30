<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_sales_agent_campaigns', function (Blueprint $table): void {
            if (! Schema::hasColumn('jb_sales_agent_campaigns', 'landing_headline')) {
                $table->string('landing_headline', 190)->nullable()->after('product_label');
            }

            if (! Schema::hasColumn('jb_sales_agent_campaigns', 'landing_body')) {
                $table->text('landing_body')->nullable()->after('landing_headline');
            }

            if (! Schema::hasColumn('jb_sales_agent_campaigns', 'landing_cta_text')) {
                $table->string('landing_cta_text', 120)->nullable()->after('landing_body');
            }

            if (! Schema::hasColumn('jb_sales_agent_campaigns', 'share_message_template')) {
                $table->text('share_message_template')->nullable()->after('landing_cta_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jb_sales_agent_campaigns', function (Blueprint $table): void {
            foreach (['share_message_template', 'landing_cta_text', 'landing_body', 'landing_headline'] as $column) {
                if (Schema::hasColumn('jb_sales_agent_campaigns', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
