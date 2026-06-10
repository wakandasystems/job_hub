<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('jb_ad_orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('tier_id')->nullable()->after('placement_id');
            $table->foreign('tier_id')->references('id')->on('jb_ad_pricing_tiers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jb_ad_orders', function (Blueprint $table): void {
            $table->dropForeign(['tier_id']);
            $table->dropColumn('tier_id');
        });
    }
};
