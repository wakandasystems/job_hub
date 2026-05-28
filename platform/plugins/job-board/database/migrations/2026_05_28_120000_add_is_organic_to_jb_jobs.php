<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_jobs', function (Blueprint $table): void {
            $table->boolean('is_organic')->default(false)->after('is_featured')
                ->comment('True for jobs posted directly on the site (not via crawlers)');
        });

        DB::statement('UPDATE jb_jobs SET is_organic = 1 WHERE crawler_id IS NULL');
    }

    public function down(): void
    {
        Schema::table('jb_jobs', function (Blueprint $table): void {
            $table->dropColumn('is_organic');
        });
    }
};
