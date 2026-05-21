<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_career_service_orders', function (Blueprint $table): void {
            $table->string('candidate_cv_path')->nullable()->after('notes');
            $table->string('reviewed_cv_path')->nullable()->after('candidate_cv_path');
        });
    }

    public function down(): void
    {
        Schema::table('jb_career_service_orders', function (Blueprint $table): void {
            $table->dropColumn(['candidate_cv_path', 'reviewed_cv_path']);
        });
    }
};
