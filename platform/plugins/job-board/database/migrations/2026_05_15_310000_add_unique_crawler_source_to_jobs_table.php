<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $duplicates = DB::table('jb_jobs')
            ->select('crawler_id', 'external_source_id')
            ->whereNotNull('crawler_id')
            ->whereNotNull('external_source_id')
            ->groupBy('crawler_id', 'external_source_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $ids = DB::table('jb_jobs')
                ->where('crawler_id', $duplicate->crawler_id)
                ->where('external_source_id', $duplicate->external_source_id)
                ->orderByRaw("CASE WHEN status = 'published' THEN 0 ELSE 1 END")
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->pluck('id')
                ->all();

            array_shift($ids);

            if ($ids === []) {
                continue;
            }

            DB::table('jb_analytics')->whereIn('job_id', $ids)->delete();
            DB::table('jb_applications')->whereIn('job_id', $ids)->delete();
            DB::table('jb_saved_jobs')->whereIn('job_id', $ids)->delete();
            DB::table('jb_jobs_skills')->whereIn('job_id', $ids)->delete();
            DB::table('jb_jobs_types')->whereIn('job_id', $ids)->delete();
            DB::table('jb_jobs_categories')->whereIn('job_id', $ids)->delete();
            DB::table('jb_jobs_tags')->whereIn('job_id', $ids)->delete();
            DB::table('jb_custom_field_values')
                ->where('reference_type', 'Botble\JobBoard\Models\Job')
                ->whereIn('reference_id', $ids)
                ->delete();
            DB::table('jb_jobs')->whereIn('id', $ids)->delete();
        }

        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_crawler_source_unique')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->unique(['crawler_id', 'external_source_id'], 'jb_jobs_crawler_source_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('jb_jobs', 'jb_jobs_crawler_source_unique')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->dropUnique('jb_jobs_crawler_source_unique');
            });
        }
    }
};
