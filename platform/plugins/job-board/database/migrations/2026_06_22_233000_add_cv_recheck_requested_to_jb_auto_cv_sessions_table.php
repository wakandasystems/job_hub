<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table): void {
            // Set when an admin manually asks the candidate again for their CV file mid-conversation
            // (e.g. they missed the first ask, or the chat moved on before they sent it). Only changes
            // how the *next attachment* is interpreted — unlike awaiting_cv_upload, it must not hijack
            // ordinary text replies, since the candidate may still be mid-interview on something else.
            $table->boolean('cv_recheck_requested')->default(false)->after('awaiting_cv_photo');
        });
    }

    public function down(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table): void {
            $table->dropColumn('cv_recheck_requested');
        });
    }
};
