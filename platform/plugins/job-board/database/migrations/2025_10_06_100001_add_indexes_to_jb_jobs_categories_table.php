<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        DB::statement('CREATE TEMPORARY TABLE jb_jobs_categories_temp AS SELECT DISTINCT job_id, category_id FROM jb_jobs_categories');
        DB::statement('TRUNCATE TABLE jb_jobs_categories');
        DB::statement('INSERT INTO jb_jobs_categories SELECT * FROM jb_jobs_categories_temp');
        DB::statement('DROP TEMPORARY TABLE jb_jobs_categories_temp');

        try {
            DB::statement('ALTER TABLE jb_jobs_categories DROP INDEX jb_jobs_categories_job_id_index');
        } catch (Exception) {
        }

        try {
            DB::statement('ALTER TABLE jb_jobs_categories DROP INDEX jb_jobs_categories_category_id_index');
        } catch (Exception) {
        }

        try {
            DB::statement('ALTER TABLE jb_jobs_categories DROP INDEX jb_jobs_categories_unique');
        } catch (Exception) {
        }

        Schema::table('jb_jobs_categories', function (Blueprint $table): void {
            $table->index('job_id', 'jb_jobs_categories_job_id_index');
            $table->index('category_id', 'jb_jobs_categories_category_id_index');
            $table->unique(['job_id', 'category_id'], 'jb_jobs_categories_unique');
        });
    }

    public function down(): void
    {
        Schema::table('jb_jobs_categories', function (Blueprint $table): void {
            $table->dropIndex('jb_jobs_categories_job_id_index');
            $table->dropIndex('jb_jobs_categories_category_id_index');
            $table->dropUnique('jb_jobs_categories_unique');
        });
    }
};
