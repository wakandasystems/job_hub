<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('candidate_reminder_count')->default(0)->after('admin_notified_at');
            $table->timestamp('last_candidate_reminder_sent_at')->nullable()->after('candidate_reminder_count');
        });
    }

    public function down(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table) {
            $table->dropColumn(['candidate_reminder_count', 'last_candidate_reminder_sent_at']);
        });
    }
};
