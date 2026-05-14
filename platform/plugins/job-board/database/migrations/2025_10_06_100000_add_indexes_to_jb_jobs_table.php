<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_active_jobs_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index(['moderation_status', 'status', 'expire_date'], 'jb_jobs_active_jobs_index');
            });
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_company_id_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index('company_id', 'jb_jobs_company_id_index');
            });
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_is_featured_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index('is_featured', 'jb_jobs_is_featured_index');
            });
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_created_at_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index('created_at', 'jb_jobs_created_at_index');
            });
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_expire_date_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index('expire_date', 'jb_jobs_expire_date_index');
            });
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_never_expired_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index('never_expired', 'jb_jobs_never_expired_index');
            });
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_country_id_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index('country_id', 'jb_jobs_country_id_index');
            });
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_state_id_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index('state_id', 'jb_jobs_state_id_index');
            });
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_city_id_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index('city_id', 'jb_jobs_city_id_index');
            });
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_job_experience_id_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index('job_experience_id', 'jb_jobs_job_experience_id_index');
            });
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_career_level_id_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index('career_level_id', 'jb_jobs_career_level_id_index');
            });
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_functional_area_id_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index('functional_area_id', 'jb_jobs_functional_area_id_index');
            });
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_job_shift_id_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index('job_shift_id', 'jb_jobs_job_shift_id_index');
            });
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_degree_level_id_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index('degree_level_id', 'jb_jobs_degree_level_id_index');
            });
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_author_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index(['author_id', 'author_type'], 'jb_jobs_author_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('jb_jobs', function (Blueprint $table): void {
            $table->dropIndex('jb_jobs_active_jobs_index');
            $table->dropIndex('jb_jobs_company_id_index');
            $table->dropIndex('jb_jobs_is_featured_index');
            $table->dropIndex('jb_jobs_created_at_index');
            $table->dropIndex('jb_jobs_expire_date_index');
            $table->dropIndex('jb_jobs_never_expired_index');
            $table->dropIndex('jb_jobs_country_id_index');
            $table->dropIndex('jb_jobs_state_id_index');
            $table->dropIndex('jb_jobs_city_id_index');
            $table->dropIndex('jb_jobs_job_experience_id_index');
            $table->dropIndex('jb_jobs_career_level_id_index');
            $table->dropIndex('jb_jobs_functional_area_id_index');
            $table->dropIndex('jb_jobs_job_shift_id_index');
            $table->dropIndex('jb_jobs_degree_level_id_index');
            $table->dropIndex('jb_jobs_author_index');
        });
    }
};
