<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    protected array $placements = [
        [
            'name' => 'WhatsApp Channel Pinned Post',
            'location' => 'social_whatsapp_pin',
            'description' => 'Pinned to the top of the WakandaJobs WhatsApp Channel for the duration of the placement.',
            'sort_order' => 33,
        ],
        [
            'name' => 'Facebook Pinned Post',
            'location' => 'social_facebook_pin',
            'description' => 'Pinned to the top of the WakandaJobs Facebook Page for the duration of the placement.',
            'sort_order' => 34,
        ],
        [
            'name' => 'LinkedIn Pinned Post',
            'location' => 'social_linkedin_pin',
            'description' => 'Pinned to the top of the WakandaJobs LinkedIn Page for the duration of the placement.',
            'sort_order' => 35,
        ],
        [
            'name' => 'TikTok Pinned Post',
            'location' => 'social_tiktok_pin',
            'description' => 'Pinned to the top of the WakandaJobs TikTok account for the duration of the placement.',
            'sort_order' => 36,
        ],
        [
            'name' => 'Instagram Pinned Post',
            'location' => 'social_instagram_pin',
            'description' => 'Pinned to the top of the WakandaJobs Instagram account for the duration of the placement.',
            'sort_order' => 37,
        ],
        [
            'name' => 'Newsletter Top Feature',
            'location' => 'social_newsletter_pin',
            'description' => 'Featured at the very top of the WakandaJobs email newsletter for the duration of the placement.',
            'sort_order' => 38,
        ],
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->placements as $placement) {
            DB::table('jb_ad_placements')->insert(array_merge($placement, [
                'price' => 60,
                'currency' => 'USD',
                'duration_days' => 30,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('jb_ad_placements')
            ->whereIn('location', array_column($this->placements, 'location'))
            ->delete();
    }
};
