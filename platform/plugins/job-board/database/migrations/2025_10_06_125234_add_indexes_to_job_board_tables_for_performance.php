<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $connection = Schema::getConnection();
        $dbName = $connection->getDatabaseName();

        $indexExists = $connection->selectOne(
            'SELECT COUNT(*) as count FROM information_schema.statistics
            WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$dbName, 'jb_jobs', 'jb_jobs_experience_active_idx']
        );

        if (! $indexExists->count) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index(
                    ['job_experience_id', 'moderation_status', 'status', 'never_expired', 'expire_date'],
                    'jb_jobs_experience_active_idx'
                );
            });
        }

        $expIndexExists = $connection->selectOne(
            'SELECT COUNT(*) as count FROM information_schema.statistics
            WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$dbName, 'jb_job_experiences', 'jb_job_experiences_status_order_created_at_index']
        );

        if (! $expIndexExists->count) {
            Schema::table('jb_job_experiences', function (Blueprint $table): void {
                $table->index(['status', 'order', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        $connection = Schema::getConnection();
        $dbName = $connection->getDatabaseName();

        $indexExists = $connection->selectOne(
            'SELECT COUNT(*) as count FROM information_schema.statistics
            WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$dbName, 'jb_jobs', 'jb_jobs_experience_active_idx']
        );

        if ($indexExists->count) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->dropIndex('jb_jobs_experience_active_idx');
            });
        }

        $expIndexExists = $connection->selectOne(
            'SELECT COUNT(*) as count FROM information_schema.statistics
            WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$dbName, 'jb_job_experiences', 'jb_job_experiences_status_order_created_at_index']
        );

        if ($expIndexExists->count) {
            Schema::table('jb_job_experiences', function (Blueprint $table): void {
                $table->dropIndex(['status', 'order', 'created_at']);
            });
        }
    }
};
