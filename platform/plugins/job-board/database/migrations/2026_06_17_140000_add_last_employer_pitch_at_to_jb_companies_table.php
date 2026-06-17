<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_companies', function (Blueprint $table): void {
            // Timestamp of the last "your job ad is live" pitch email sent to this
            // employer. Used to throttle the auto-pitch to at most one email per
            // employer per calendar day, even when they post many jobs in a day.
            $table->timestamp('last_employer_pitch_at')->nullable()->after('contact_numbers');
        });
    }

    public function down(): void
    {
        Schema::table('jb_companies', function (Blueprint $table): void {
            $table->dropColumn('last_employer_pitch_at');
        });
    }
};
