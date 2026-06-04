<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove duplicates (keep newest) before adding the unique index
        DB::statement('
            DELETE a FROM jb_candidate_alerts a
            INNER JOIN jb_candidate_alerts b
                ON  a.candidate_phone = b.candidate_phone
                AND a.label           = b.label
                AND a.id              < b.id
        ');

        Schema::table('jb_candidate_alerts', function (Blueprint $table) {
            $table->unique(['candidate_phone', 'label'], 'uq_candidate_alert_phone_label');
        });
    }

    public function down(): void
    {
        Schema::table('jb_candidate_alerts', function (Blueprint $table) {
            $table->dropUnique('uq_candidate_alert_phone_label');
        });
    }
};
