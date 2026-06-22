<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table): void {
            // Set right after the candidate confirms "DONE", while we're waiting to find out
            // whether they want to add a photo to their CV before it's generated.
            $table->boolean('awaiting_cv_photo')->default(false)->after('awaiting_cv_upload');
            $table->string('candidate_photo_path')->nullable()->after('cv_document_paths');
        });
    }

    public function down(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table): void {
            $table->dropColumn(['awaiting_cv_photo', 'candidate_photo_path']);
        });
    }
};
