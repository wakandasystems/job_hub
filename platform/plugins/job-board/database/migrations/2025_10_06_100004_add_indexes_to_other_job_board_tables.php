<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('jb_jobs_skills', function (Blueprint $table): void {
            $table->index('job_id', 'jb_jobs_skills_job_id_index');
            $table->index('job_skill_id', 'jb_jobs_skills_job_skill_id_index');
            $table->unique(['job_id', 'job_skill_id'], 'jb_jobs_skills_unique');
        });

        Schema::table('jb_applications', function (Blueprint $table): void {
            $table->index('job_id', 'jb_applications_job_id_index');
            $table->index('account_id', 'jb_applications_account_id_index');
            $table->index('status', 'jb_applications_status_index');
            $table->index('created_at', 'jb_applications_created_at_index');
            $table->index(['job_id', 'status'], 'jb_applications_job_status_index');
        });

        Schema::table('jb_saved_jobs', function (Blueprint $table): void {
            $table->index('job_id', 'jb_saved_jobs_job_id_index');
        });

        Schema::table('jb_companies_accounts', function (Blueprint $table): void {
            $table->index('company_id', 'jb_companies_accounts_company_id_index');
            $table->index('account_id', 'jb_companies_accounts_account_id_index');
            $table->unique(['company_id', 'account_id'], 'jb_companies_accounts_unique');
        });

        Schema::table('jb_accounts', function (Blueprint $table): void {
            $table->index('type', 'jb_accounts_type_index');
            $table->index('is_featured', 'jb_accounts_is_featured_index');
            $table->index('created_at', 'jb_accounts_created_at_index');
        });

        Schema::table('jb_analytics', function (Blueprint $table): void {
            $table->index('job_id', 'jb_analytics_job_id_index');
            $table->index('created_at', 'jb_analytics_created_at_index');
            $table->index(['job_id', 'created_at'], 'jb_analytics_job_date_index');
        });

        Schema::table('jb_transactions', function (Blueprint $table): void {
            $table->index('account_id', 'jb_transactions_account_id_index');
            $table->index('user_id', 'jb_transactions_user_id_index');
            $table->index('payment_id', 'jb_transactions_payment_id_index');
            $table->index('created_at', 'jb_transactions_created_at_index');
        });

        Schema::table('jb_account_packages', function (Blueprint $table): void {
            $table->index('account_id', 'jb_account_packages_account_id_index');
            $table->index('package_id', 'jb_account_packages_package_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('jb_jobs_skills', function (Blueprint $table): void {
            $table->dropIndex('jb_jobs_skills_job_id_index');
            $table->dropIndex('jb_jobs_skills_job_skill_id_index');
            $table->dropUnique('jb_jobs_skills_unique');
        });

        Schema::table('jb_applications', function (Blueprint $table): void {
            $table->dropIndex('jb_applications_job_id_index');
            $table->dropIndex('jb_applications_account_id_index');
            $table->dropIndex('jb_applications_status_index');
            $table->dropIndex('jb_applications_created_at_index');
            $table->dropIndex('jb_applications_job_status_index');
        });

        Schema::table('jb_saved_jobs', function (Blueprint $table): void {
            $table->dropIndex('jb_saved_jobs_job_id_index');
        });

        Schema::table('jb_companies_accounts', function (Blueprint $table): void {
            $table->dropIndex('jb_companies_accounts_company_id_index');
            $table->dropIndex('jb_companies_accounts_account_id_index');
            $table->dropUnique('jb_companies_accounts_unique');
        });

        Schema::table('jb_accounts', function (Blueprint $table): void {
            $table->dropIndex('jb_accounts_type_index');
            $table->dropIndex('jb_accounts_is_featured_index');
            $table->dropIndex('jb_accounts_created_at_index');
        });

        Schema::table('jb_analytics', function (Blueprint $table): void {
            $table->dropIndex('jb_analytics_job_id_index');
            $table->dropIndex('jb_analytics_created_at_index');
            $table->dropIndex('jb_analytics_job_date_index');
        });

        Schema::table('jb_transactions', function (Blueprint $table): void {
            $table->dropIndex('jb_transactions_account_id_index');
            $table->dropIndex('jb_transactions_user_id_index');
            $table->dropIndex('jb_transactions_payment_id_index');
            $table->dropIndex('jb_transactions_created_at_index');
        });

        Schema::table('jb_account_packages', function (Blueprint $table): void {
            $table->dropIndex('jb_account_packages_account_id_index');
            $table->dropIndex('jb_account_packages_package_id_index');
        });
    }
};
