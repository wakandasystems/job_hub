<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_candidate_alerts', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('candidate_email')->constrained('jb_accounts')->nullOnDelete();
            $table->index(['account_id', 'status'], 'jb_candidate_alerts_account_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('jb_candidate_alerts', function (Blueprint $table) {
            $table->dropIndex('jb_candidate_alerts_account_status_idx');
            $table->dropConstrainedForeignId('account_id');
        });
    }
};
