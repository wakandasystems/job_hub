<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE jb_jobs ADD FULLTEXT INDEX jb_jobs_name_fulltext (name)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE jb_jobs DROP INDEX jb_jobs_name_fulltext');
    }
};
