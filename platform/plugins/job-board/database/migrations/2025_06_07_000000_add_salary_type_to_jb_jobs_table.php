<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('jb_jobs', 'salary_type')) {
            return;
        }

        Schema::table('jb_jobs', function (Blueprint $table): void {
            $table->string('salary_type')->default('fixed')->after('salary_range');
        });
    }

    public function down(): void
    {
        Schema::table('jb_jobs', function (Blueprint $table): void {
            $table->dropColumn('salary_type');
        });
    }
};
