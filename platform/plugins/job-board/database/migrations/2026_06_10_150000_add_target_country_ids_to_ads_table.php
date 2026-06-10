<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table): void {
            $table->json('target_country_ids')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table): void {
            $table->dropColumn('target_country_ids');
        });
    }
};
