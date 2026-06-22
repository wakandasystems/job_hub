<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_jobs', function (Blueprint $table): void {
            // Some crawled sources (e.g. emploitic.com) list a single job across many districts/cities
            // and concatenate them all into one address string, which can comfortably exceed 191 chars.
            $table->string('address', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('jb_jobs', function (Blueprint $table): void {
            $table->string('address', 191)->nullable()->change();
        });
    }
};
