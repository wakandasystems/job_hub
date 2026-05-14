<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasIndex('jb_jobs', 'jb_jobs_views_index')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->index('views', 'jb_jobs_views_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('jb_jobs', function (Blueprint $table): void {
            $table->dropIndex('jb_jobs_views_index');
        });
    }
};
