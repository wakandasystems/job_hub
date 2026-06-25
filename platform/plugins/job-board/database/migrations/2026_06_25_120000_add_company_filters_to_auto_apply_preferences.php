<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_auto_apply_preferences', function (Blueprint $table): void {
            $table->json('whitelisted_company_ids')->nullable()->after('job_experience_id');
            $table->json('whitelisted_company_keywords')->nullable()->after('whitelisted_company_ids');
            $table->json('blacklisted_company_keywords')->nullable()->after('blacklisted_company_ids');
        });
    }

    public function down(): void
    {
        Schema::table('jb_auto_apply_preferences', function (Blueprint $table): void {
            $table->dropColumn([
                'whitelisted_company_ids',
                'whitelisted_company_keywords',
                'blacklisted_company_keywords',
            ]);
        });
    }
};
