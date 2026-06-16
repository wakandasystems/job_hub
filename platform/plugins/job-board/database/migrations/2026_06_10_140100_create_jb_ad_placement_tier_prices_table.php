<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_ad_placement_tier_prices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ad_placement_id');
            $table->unsignedBigInteger('tier_id');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamps();

            $table->unique(['ad_placement_id', 'tier_id']);
            $table->foreign('ad_placement_id')->references('id')->on('jb_ad_placements')->cascadeOnDelete();
            $table->foreign('tier_id')->references('id')->on('jb_ad_pricing_tiers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_ad_placement_tier_prices');
    }
};
