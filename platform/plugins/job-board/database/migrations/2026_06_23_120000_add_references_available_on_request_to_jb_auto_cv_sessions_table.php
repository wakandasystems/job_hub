<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table): void {
            // Admin override: force the generated CV documents to print "Available on request" for
            // references regardless of what was actually collected. Kept as its own column (not inside
            // structured_cv) because structured_cv gets wholesale replaced by the AI's JSON reply on
            // every candidate turn, which would silently drop a flag stored inside it.
            $table->boolean('references_available_on_request')->default(false)->after('cv_recheck_requested');
        });
    }

    public function down(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table): void {
            $table->dropColumn('references_available_on_request');
        });
    }
};
