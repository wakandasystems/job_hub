<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table) {
            $table->json('section_scores')->nullable()->after('topics_covered');
            $table->json('suggested_job_positions')->nullable()->after('structured_cv');
        });
    }

    public function down(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table) {
            $table->dropColumn(['section_scores', 'suggested_job_positions']);
        });
    }
};
