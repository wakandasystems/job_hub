<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class AutoApplyOrder extends BaseModel
{
    protected $table = 'jb_auto_apply_orders';

    protected $fillable = [
        'account_id',
        'plan',
        'duration_days',
        'applications_allowed',
        'amount',
        'currency',
        'charge_id',
        'payment_method',
        'status',
        'admin_status',
        'notes',
        'approved_at',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    private const DEFAULT_PLANS = [
        'weekly'   => ['label' => '1 Week',   'duration_days' => 7,  'price' => 5.00,   'currency' => 'USD', 'applications_per_month' => 10,  'badge' => null,           'enabled' => true],
        'monthly'  => ['label' => '1 Month',  'duration_days' => 30, 'price' => 15.00,  'currency' => 'USD', 'applications_per_month' => 50,  'badge' => 'Most Popular', 'enabled' => true],
        'one_time' => ['label' => '3 Months', 'duration_days' => 90, 'price' => 150.00, 'currency' => 'USD', 'applications_per_month' => 0,   'badge' => 'Best Value',   'enabled' => true],
    ];

    public static function defaultPlans(): array
    {
        return self::DEFAULT_PLANS;
    }

    public static function plans(bool $includeDisabled = false): array
    {
        $plans = [];

        foreach (self::DEFAULT_PLANS as $key => $defaults) {
            $plan = [
                'label'                  => trim((string) setting("auto_apply_plan_{$key}_label", $defaults['label'])),
                'duration_days'          => max(1, (int) setting("auto_apply_plan_{$key}_duration_days", $defaults['duration_days'])),
                'price'                  => max(0, (float) setting("auto_apply_plan_{$key}_price", $defaults['price'])),
                'currency'               => strtoupper(trim((string) setting("auto_apply_plan_{$key}_currency", $defaults['currency']))),
                'applications_per_month' => max(0, (int) setting("auto_apply_plan_{$key}_applications_per_month", $defaults['applications_per_month'])),
                'badge'                  => trim((string) setting("auto_apply_plan_{$key}_badge", $defaults['badge'] ?? '')) ?: null,
                'enabled'                => (bool) setting("auto_apply_plan_{$key}_enabled", $defaults['enabled']),
            ];

            if ($includeDisabled || $plan['enabled']) {
                $plans[$key] = $plan;
            }
        }

        return $plans;
    }

    public static function plan(string $key, bool $includeDisabled = false): ?array
    {
        return self::plans($includeDisabled)[$key] ?? null;
    }

    public static function globalAiModel(): string
    {
        return setting('auto_apply_ai_model', 'gpt-4o-mini');
    }

    public static function globalMatchThreshold(): int
    {
        return max(0, min(100, (int) setting('auto_apply_match_threshold', 60)));
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function planLabel(): string
    {
        return self::plan($this->plan, includeDisabled: true)['label']
            ?? ucfirst(str_replace('_', ' ', $this->plan));
    }

    public function approve(): void
    {
        if ($this->admin_status === 'approved') {
            return;
        }

        $plan = self::plan($this->plan, includeDisabled: true);
        $allowed = $plan ? ($plan['applications_per_month'] === 0 ? -1 : $plan['applications_per_month']) : $this->applications_allowed;

        $this->update([
            'status'       => 'approved',
            'admin_status' => 'approved',
            'approved_at'  => now(),
        ]);

        $period = AutoApplyQuota::currentPeriod();

        DB::table('jb_auto_apply_quotas')->updateOrInsert(
            ['account_id' => $this->account_id, 'period' => $period, 'plan' => $this->plan],
            [
                'applications_allowed' => $allowed,
                'applications_sent'    => 0,
                'is_approved'          => true,
                'charge_id'            => $this->charge_id,
                'payment_method'       => $this->payment_method,
                'updated_at'           => now(),
                'created_at'           => now(),
            ]
        );

        // Activate the candidate's auto-apply preference
        AutoApplyPreference::updateOrCreate(
            ['account_id' => $this->account_id],
            ['is_active' => true]
        );
    }

    public static function statuses(): array
    {
        return [
            'pending'   => 'Pending',
            'approved'  => 'Approved',
            'rejected'  => 'Rejected',
            'cancelled' => 'Cancelled',
        ];
    }

    public function isManualPayment(): bool
    {
        return in_array($this->payment_method, ['bank_transfer', 'cod']);
    }
}
