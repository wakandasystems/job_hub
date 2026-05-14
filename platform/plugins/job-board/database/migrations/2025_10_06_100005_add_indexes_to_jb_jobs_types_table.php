<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('jb_jobs_types', function (Blueprint $table): void {
            $table->index('job_id', 'jb_jobs_types_job_id_index');
            $table->index('job_type_id', 'jb_jobs_types_job_type_id_index');
            $table->unique(['job_id', 'job_type_id'], 'jb_jobs_types_unique');
        });
    }

    public function down(): void
    {
        Schema::table('jb_jobs_types', function (Blueprint $table): void {
            $table->dropIndex('jb_jobs_types_job_id_index');
            $table->dropIndex('jb_jobs_types_job_type_id_index');
            $table->dropUnique('jb_jobs_types_unique');
        });
    }
};
