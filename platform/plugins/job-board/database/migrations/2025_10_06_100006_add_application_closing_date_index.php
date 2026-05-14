<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_application_closing_date_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index('application_closing_date', 'jb_jobs_application_closing_date_index');
            });
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_listing_optimized_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index(
                    ['moderation_status', 'status', 'created_at', 'never_expired', 'expire_date', 'application_closing_date'],
                    'jb_jobs_listing_optimized_index'
                );
            });
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_never_expired_status_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index(['never_expired', 'moderation_status', 'status', 'created_at'], 'jb_jobs_never_expired_status_index');
            });
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_expire_date_listing_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index(['moderation_status', 'status', 'expire_date', 'created_at'], 'jb_jobs_expire_date_listing_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('jb_jobs', function (Blueprint $table): void {
            $table->dropIndex('jb_jobs_application_closing_date_index');
            $table->dropIndex('jb_jobs_listing_optimized_index');
            $table->dropIndex('jb_jobs_never_expired_status_index');
            $table->dropIndex('jb_jobs_expire_date_listing_index');
        });
    }
};
