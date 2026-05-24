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
        Schema::table('jb_accounts', function (Blueprint $table) {
            $table->json('cv_score_history')->nullable()->after('cv_score_data');
        });
    }

    public function down(): void
    {
        Schema::table('jb_accounts', function (Blueprint $table) {
            $table->dropColumn('cv_score_history');
        });
    }
};
