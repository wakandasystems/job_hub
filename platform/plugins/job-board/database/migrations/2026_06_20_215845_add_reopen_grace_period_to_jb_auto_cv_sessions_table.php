<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table): void {
            // Set when "Continue Interview" reopens an already-completed session because
            // something was missing — these get a longer 30-minute grace period (with a
            // 2-minute heads-up warning) instead of the normal 2-minute confirmation timeout,
            // since the candidate needs time to type out real new content, not just say "Done".
            $table->boolean('reopened_for_missing_detail')->default(false)->after('awaiting_final_confirmation');
            $table->timestamp('reopen_warning_sent_at')->nullable()->after('reopened_for_missing_detail');
        });
    }

    public function down(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table): void {
            $table->dropColumn(['reopened_for_missing_detail', 'reopen_warning_sent_at']);
        });
    }
};
