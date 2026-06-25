<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_sales_agent_marketing_images', function (Blueprint $table) {
            $table->string('subject_mode', 20)->default('nakia')->after('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::table('jb_sales_agent_marketing_images', function (Blueprint $table) {
            $table->dropColumn('subject_mode');
        });
    }
};
