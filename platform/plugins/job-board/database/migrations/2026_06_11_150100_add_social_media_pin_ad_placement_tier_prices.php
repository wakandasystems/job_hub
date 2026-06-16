<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    protected array $locations = [
        'social_whatsapp_pin',
        'social_facebook_pin',
        'social_linkedin_pin',
        'social_tiktok_pin',
        'social_instagram_pin',
        'social_newsletter_pin',
    ];

    /**
     * Tier name => price for a 30-day pinned/featured social media post (1.5x the regular post price).
     */
    protected array $tierPrices = [
        'Zambia Only' => 60,
        'Zambia + Southern Africa' => 120,
        'All Africa' => 300,
        'All Countries' => 480,
    ];

    public function up(): void
    {
        $now = now();

        $placementIds = DB::table('jb_ad_placements')
            ->whereIn('location', $this->locations)
            ->pluck('id', 'location');

        $tierIds = DB::table('jb_ad_pricing_tiers')
            ->whereIn('name', array_keys($this->tierPrices))
            ->pluck('id', 'name');

        foreach ($placementIds as $placementId) {
            foreach ($this->tierPrices as $tierName => $price) {
                DB::table('jb_ad_placement_tier_prices')->insert([
                    'ad_placement_id' => $placementId,
                    'tier_id' => $tierIds[$tierName],
                    'price' => $price,
                    'currency' => 'USD',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $placementIds = DB::table('jb_ad_placements')
            ->whereIn('location', $this->locations)
            ->pluck('id');

        DB::table('jb_ad_placement_tier_prices')
            ->whereIn('ad_placement_id', $placementIds)
            ->delete();
    }
};
