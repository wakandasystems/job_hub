<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dateTime('publish_at')->nullable()->after('updated_at')->index();
        });

        // Migrate existing scheduled draft posts (seeded with created_at as schedule date).
        DB::table('posts')
            ->where('status', 'draft')
            ->whereIn('id', [16, 17, 18, 19, 20, 21, 22, 23])
            ->update(['publish_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn('publish_at');
        });
    }
};
