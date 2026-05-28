<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_jobs', function (Blueprint $table): void {
            $table->unsignedSmallInteger('featured_bid')->default(0)->after('featured_until');
        });
    }

    public function down(): void
    {
        Schema::table('jb_jobs', function (Blueprint $table): void {
            $table->dropColumn('featured_bid');
        });
    }
};
