<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('jb_job_crawlers')->updateOrInsert(
            ['name' => 'JobLink Zambia'],
            [
                'source_url' => 'https://joblinkzambia.com',
                'parser_type' => 'wpjobmanager',
                'schedule' => 'hourly',
                'is_active' => true,
                'field_mappings' => json_encode(['country_id' => 7, 'currency_id' => 4, 'default_location' => 'Zambia']),
                'next_run_at' => null,
                'last_error' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('jb_job_crawlers')
            ->where('name', 'JobLink Zambia')
            ->update([
                'source_url' => '#',
                'parser_type' => 'pending',
                'schedule' => null,
                'is_active' => false,
                'next_run_at' => null,
                'updated_at' => now(),
            ]);
    }
};
