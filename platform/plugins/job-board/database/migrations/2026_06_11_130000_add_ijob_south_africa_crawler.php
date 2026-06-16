<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('jb_job_crawlers')->updateOrInsert(
            ['name' => 'iJob South Africa'],
            [
                'source_url' => 'https://www.ijob.co.za',
                'parser_type' => 'ijob',
                'schedule' => 'hourly',
                'is_active' => true,
                'field_mappings' => json_encode([
                    'country_id' => 53,
                    'currency_id' => 46,
                    'default_location' => 'South Africa',
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
            ->where('name', 'iJob South Africa')
            ->delete();
    }
};
