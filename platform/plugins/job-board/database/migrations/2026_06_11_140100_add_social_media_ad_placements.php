<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    protected array $placements = [
        [
            'name' => 'WhatsApp Channel Post',
            'location' => 'social_whatsapp',
            'description' => 'Sponsored post on the WakandaJobs WhatsApp Channel.',
            'sort_order' => 27,
        ],
        [
            'name' => 'Facebook Post',
            'location' => 'social_facebook',
            'description' => 'Sponsored post on the WakandaJobs Facebook Page.',
            'sort_order' => 28,
        ],
        [
            'name' => 'LinkedIn Post',
            'location' => 'social_linkedin',
            'description' => 'Sponsored post on the WakandaJobs LinkedIn Page.',
            'sort_order' => 29,
        ],
        [
            'name' => 'TikTok Post',
            'location' => 'social_tiktok',
            'description' => 'Sponsored post on the WakandaJobs TikTok account.',
            'sort_order' => 30,
        ],
        [
            'name' => 'Instagram Post',
            'location' => 'social_instagram',
            'description' => 'Sponsored post on the WakandaJobs Instagram account.',
            'sort_order' => 31,
        ],
        [
            'name' => 'Newsletter Feature',
            'location' => 'social_newsletter',
            'description' => 'Featured placement in the WakandaJobs email newsletter.',
            'sort_order' => 32,
        ],
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->placements as $placement) {
            DB::table('jb_ad_placements')->insert(array_merge($placement, [
                'price' => 40,
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
