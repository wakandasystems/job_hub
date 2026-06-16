<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('jb_job_crawlers')->updateOrInsert(
            ['name' => 'JobPoint Botswana'],
            [
                'source_url' => 'https://jobpoint.ai/jobs',
                'parser_type' => 'jobpoint',
                'schedule' => 'hourly',
                'is_active' => true,
                'field_mappings' => json_encode([
                    'country_id' => 11,
                    'currency_id' => 7,
                    'default_location' => 'Botswana',
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
            ->where('name', 'JobPoint Botswana')
            ->delete();
    }
};
