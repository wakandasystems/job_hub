<?php

namespace Botble\JobBoard\Models;

use Botble\Ads\Models\Ads;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Models\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AdOrder extends BaseModel
{
    protected $table = 'jb_ad_orders';

    protected $fillable = [
        'account_id',
        'placement_id',
        'tier_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'charge_id',
        'image',
        'url',
        'open_in_new_tab',
        'ads_id',
        'approved_at',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'open_in_new_tab' => 'boolean',
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(AdPlacement::class, 'placement_id');
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(AdPricingTier::class, 'tier_id');
    }

    public function approve(): void
    {
        $placement = $this->placement;

        $expiresAt = ($placement && ! $placement->isUnlimitedDuration())
            ? Carbon::now()->addDays($placement->duration_days)
            : null;

        $ad = Ads::query()->find($this->ads_id) ?? new Ads();

        if (! $ad->exists) {
            do {
                $key = strtoupper(Str::random(12));
            } while (Ads::query()->where('key', $key)->exists());

            $ad->key = $key;
        }

        $ad->fill([
            'name' => 'Ad Order #' . $this->id . ' — ' . ($placement->name ?? $placement->location ?? ''),
            'ads_type' => 'custom_ad',
            'image' => $this->image,
            'url' => $this->url,
            'open_in_new_tab' => $this->open_in_new_tab,
            'location' => $placement?->location,
            'target_country_ids' => $this->tier?->country_ids ?: null,
            'status' => BaseStatusEnum::PUBLISHED,
            'expired_at' => $expiresAt ?? Carbon::now()->addYears(10),
        ]);

        $ad->save();

        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'expires_at' => $expiresAt,
            'ads_id' => $ad->getKey(),
        ]);
    }

    public function isManualPayment(): bool
    {
        return in_array($this->payment_method, ['bank_transfer', 'cod']);
    }

    public static function statuses(): array
    {
        return [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
        ];
    }
}
