<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->upsertCrawler(
            'Eswatini Jobs',
            'https://jobs.eswazi.org',
            'wpjobmanager',
            ['country_id' => 26, 'currency_id' => 40, 'default_location' => 'Eswatini']
        );

        $this->upsertCrawler(
            'Mozambique Jobs',
            'https://www.emprego.co.mz',
            'empregomz',
            ['country_id' => 43, 'currency_id' => 30, 'default_location' => 'Mozambique']
        );
    }

    public function down(): void
    {
        DB::table('jb_job_crawlers')
            ->whereIn('name', ['Eswatini Jobs', 'Mozambique Jobs'])
            ->update([
                'source_url' => '#',
                'parser_type' => 'pending',
                'schedule' => null,
                'is_active' => false,
                'next_run_at' => null,
                'updated_at' => now(),
            ]);
    }

    private function upsertCrawler(string $name, string $sourceUrl, string $parserType, array $mappings): void
    {
        DB::table('jb_job_crawlers')->updateOrInsert(
            ['name' => $name],
            [
                'source_url' => $sourceUrl,
                'parser_type' => $parserType,
                'schedule' => 'hourly',
                'is_active' => true,
                'field_mappings' => json_encode($mappings),
                'next_run_at' => null,
                'last_error' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
};
