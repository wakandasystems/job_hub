<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('jb_job_crawlers')->updateOrInsert(
            ['name' => 'Gamjobs Gambia'],
            [
                'source_url' => 'https://gamjobs.com',
                'parser_type' => 'noojobmonster',
                'schedule' => 'hourly',
                'is_active' => true,
                'field_mappings' => json_encode([
                    'country_id' => 29,
                    'currency_id' => 18,
                    'default_location' => 'The Gambia',
                ]),
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
            ->where('name', 'Gamjobs Gambia')
            ->delete();
    }
};
