<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table) {
            $table->boolean('awaiting_final_confirmation')->default(false)->after('last_candidate_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table) {
            $table->dropColumn('awaiting_final_confirmation');
        });
    }
};
