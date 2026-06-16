<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        DB::table('jb_ad_pricing_tiers')->insert([
            'name' => 'All Countries',
            'country_ids' => json_encode([]),
            'sort_order' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('jb_ad_pricing_tiers')->where('name', 'All Countries')->delete();
    }
};
