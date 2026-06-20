<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_candidate_alerts', function (Blueprint $table): void {
            // One-time "you can edit your filters" engagement message, sent ~2 days
            // after signup at a randomised time so it doesn't look automated.
            $table->timestamp('filter_tip_scheduled_at')->nullable()->after('expiry_notice_sent');
            $table->timestamp('filter_tip_sent_at')->nullable()->after('filter_tip_scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('jb_candidate_alerts', function (Blueprint $table): void {
            $table->dropColumn(['filter_tip_scheduled_at', 'filter_tip_sent_at']);
        });
    }
};
