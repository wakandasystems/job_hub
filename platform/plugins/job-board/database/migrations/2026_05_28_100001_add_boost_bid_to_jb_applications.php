<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_applications', function (Blueprint $table): void {
            $table->unsignedSmallInteger('boost_bid')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('jb_applications', function (Blueprint $table): void {
            $table->dropColumn('boost_bid');
        });
    }
};
