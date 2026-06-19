<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table) {
            $table->longText('error_trace')->nullable()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table) {
            $table->dropColumn('error_trace');
        });
    }
};
