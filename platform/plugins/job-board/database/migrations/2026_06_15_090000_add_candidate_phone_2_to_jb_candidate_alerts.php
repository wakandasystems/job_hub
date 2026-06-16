<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_candidate_alerts', function (Blueprint $table) {
            $table->string('candidate_phone_2', 30)->nullable()->after('candidate_phone');
        });
    }

    public function down(): void
    {
        Schema::table('jb_candidate_alerts', function (Blueprint $table) {
            $table->dropColumn('candidate_phone_2');
        });
    }
};
