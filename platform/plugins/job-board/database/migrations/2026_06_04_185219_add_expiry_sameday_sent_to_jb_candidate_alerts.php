<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jb_candidate_alerts', function (Blueprint $table) {
            $table->boolean('expiry_sameday_sent')->default(false)->after('expiry_warning_sent');
        });
    }

    public function down(): void
    {
        Schema::table('jb_candidate_alerts', function (Blueprint $table) {
            $table->dropColumn('expiry_sameday_sent');
        });
    }
};
