<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('jb_companies')
            ->whereIn('id', [
                402,
                2005,
                2011,
                2023,
                2025,
                2027,
                2029,
                2243,
                2838,
                4258,
                5326,
                5566,
            ])
            ->where('logo', 'gemini-generated-image-s1e9dgs1e9dgs1e9.png')
            ->update(['logo' => 'chatgpt-image-may-14-2026-03-00-04-pm.png']);
    }

    public function down(): void
    {
        // Crawler fallback logos should remain square.
    }
};
