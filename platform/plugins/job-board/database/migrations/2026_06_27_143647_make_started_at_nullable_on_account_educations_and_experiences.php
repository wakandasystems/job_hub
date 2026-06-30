<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_account_educations', function (Blueprint $table) {
            $table->date('started_at')->nullable()->change();
        });

        Schema::table('jb_account_experiences', function (Blueprint $table) {
            $table->date('started_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('jb_account_educations', function (Blueprint $table) {
            $table->date('started_at')->nullable(false)->change();
        });

        Schema::table('jb_account_experiences', function (Blueprint $table) {
            $table->date('started_at')->nullable(false)->change();
        });
    }
};
