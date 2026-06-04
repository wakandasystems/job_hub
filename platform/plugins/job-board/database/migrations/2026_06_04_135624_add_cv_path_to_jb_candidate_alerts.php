<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jb_candidate_alerts', function (Blueprint $table) {
            $table->string('cv_path', 500)->nullable()->after('notes');
            $table->json('cv_analysis')->nullable()->after('cv_path');
        });
    }

    public function down(): void
    {
        Schema::table('jb_candidate_alerts', function (Blueprint $table) {
            $table->dropColumn(['cv_path', 'cv_analysis']);
        });
    }
};
