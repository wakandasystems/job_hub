<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_accounts', function (Blueprint $table): void {
            $table->unsignedTinyInteger('cv_score')->nullable()->after('cover_letter');
            $table->json('cv_score_data')->nullable()->after('cv_score');
        });
    }

    public function down(): void
    {
        Schema::table('jb_accounts', function (Blueprint $table): void {
            $table->dropColumn(['cv_score', 'cv_score_data']);
        });
    }
};
