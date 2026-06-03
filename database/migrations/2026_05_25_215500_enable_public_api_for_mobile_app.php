<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'api_enabled'],
            ['value' => '1']
        );
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'api_enabled')->update(['value' => '0']);
    }
};
