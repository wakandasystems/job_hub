<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table): void {
            // Set right after a session starts, while we're waiting to find out whether the
            // candidate has an existing CV to upload (and pre-fill from) or wants to start fresh.
            $table->boolean('awaiting_cv_upload')->default(false)->after('awaiting_final_confirmation');
        });
    }

    public function down(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table): void {
            $table->dropColumn('awaiting_cv_upload');
        });
    }
};
