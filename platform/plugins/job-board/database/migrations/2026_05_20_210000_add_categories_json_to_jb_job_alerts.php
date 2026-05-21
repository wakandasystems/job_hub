<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_job_alerts', function (Blueprint $table): void {
            $table->json('category_ids')->nullable()->after('category_id');
        });

        // Migrate existing single category_id → category_ids array
        DB::statement("UPDATE jb_job_alerts SET category_ids = JSON_ARRAY(category_id) WHERE category_id IS NOT NULL");
    }

    public function down(): void
    {
        Schema::table('jb_job_alerts', function (Blueprint $table): void {
            $table->dropColumn('category_ids');
        });
    }
};
