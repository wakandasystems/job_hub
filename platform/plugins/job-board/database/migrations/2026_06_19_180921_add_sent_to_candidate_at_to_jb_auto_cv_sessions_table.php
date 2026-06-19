<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table) {
            $table->timestamp('sent_to_candidate_at')->nullable()->after('admin_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table) {
            $table->dropColumn('sent_to_candidate_at');
        });
    }
};
