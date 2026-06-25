<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Botble\JobBoard\Services\SalesAgentService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AutoApplyOrder extends BaseModel
{
    protected $table = 'jb_auto_apply_orders';

    protected $fillable = [
        'account_id',
        'sales_agent_id',
        'sales_agent_original_amount',
        'sales_agent_discount_amount',
        'sales_agent_code',
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
        'sales_agent_original_amount' => 'decimal:2',
        'sales_agent_discount_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    private const DEFAULT_PLANS = [
        'weekly'   => ['label' => '1 Week',   'duration_days' => 7,  'price' => 3.99,  'currency' => 'USD', 'applications_per_month' => 8,  'badge' => null,           'enabled' => true],
        'monthly'  => ['label' => '1 Month',  'duration_days' => 30, 'price' => 12.99, 'currency' => 'USD', 'applications_per_month' => 30, 'badge' => 'Most Popular', 'enabled' => true],
        'one_time' => ['label' => '3 Months', 'duration_days' => 90, 'price' => 29.99, 'currency' => 'USD', 'applications_per_month' => 40, 'badge' => 'Best Value',   'enabled' => true],
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

    public function salesAgent(): BelongsTo
    {
        return $this->belongsTo(SalesAgent::class, 'sales_agent_id');
    }

    public function planLabel(): string
    {
        return self::plan($this->plan, includeDisabled: true)['label']
            ?? ucfirst(str_replace('_', ' ', $this->plan));
    }

    public function currentApplicationsAllowed(): int
    {
        $plan = self::plan($this->plan, includeDisabled: true);

        if ($plan) {
            return (int) ($plan['applications_per_month'] ?? 0);
        }

        return (int) $this->applications_allowed;
    }

    public function approve(): void
    {
        if ($this->admin_status === 'approved') {
            return;
        }

        $this->update([
            'status'       => 'approved',
            'admin_status' => 'approved',
            'approved_at'  => now(),
        ]);

        if ($this->sales_agent_id) {
            $agent = SalesAgent::query()->find($this->sales_agent_id);

            if ($agent) {
                $service = app(SalesAgentService::class);
                $service->recordReferral($agent, $this->account?->phone, $this->sales_agent_code, 'auto_apply', $this->account_id);
                $service->creditCommission($agent, 'auto_apply_order', $this->getKey(), (float) $this->amount, $this->currency);
            }
        }

        // Activate the candidate's auto-apply preference
        AutoApplyPreference::updateOrCreate(
            ['account_id' => $this->account_id],
            ['is_active' => true]
        );

        AutoApplyQuota::syncForAccount($this->account_id);
    }

    public function scopeApproved($query)
    {
        return $query
            ->where('status', 'approved')
            ->where('admin_status', 'approved')
            ->whereNotNull('approved_at');
    }

    public static function activeForAccount(int $accountId, ?Carbon $at = null): ?self
    {
        $at ??= now();

        return self::query()
            ->approved()
            ->where('account_id', $accountId)
            ->orderByDesc('approved_at')
            ->get()
            ->first(fn (self $order) => $order->isActiveAt($at));
    }

    public function expiresAt(): ?Carbon
    {
        return $this->approved_at?->copy()->addDays($this->duration_days);
    }

    public function isActiveAt(?Carbon $at = null): bool
    {
        $at ??= now();

        if ($this->status !== 'approved' || $this->admin_status !== 'approved' || ! $this->approved_at) {
            return false;
        }

        $expiresAt = $this->expiresAt();

        return $expiresAt && $at->gte($this->approved_at) && $at->lt($expiresAt);
    }

    public function cycleLengthDays(): int
    {
        return $this->duration_days < 30 ? max(1, $this->duration_days) : 30;
    }

    public function cycleCount(): int
    {
        return (int) ceil($this->duration_days / $this->cycleLengthDays());
    }

    public function cycleDetails(?Carbon $at = null): ?array
    {
        $at ??= now();

        if (! $this->isActiveAt($at)) {
            return null;
        }

        $cycleLength = $this->cycleLengthDays();
        $elapsedDays = (int) $this->approved_at->diffInDays($at);
        $cycleIndex = min($this->cycleCount() - 1, intdiv($elapsedDays, $cycleLength));
        $cycleStart = $this->approved_at->copy()->addDays($cycleIndex * $cycleLength);
        $cycleEnd = $cycleStart->copy()->addDays($cycleLength);
        $expiresAt = $this->expiresAt();

        if ($expiresAt && $cycleEnd->gt($expiresAt)) {
            $cycleEnd = $expiresAt->copy();
        }

        return [
            'key' => sprintf('order-%d-cycle-%d', $this->id, $cycleIndex + 1),
            'index' => $cycleIndex,
            'start' => $cycleStart,
            'end' => $cycleEnd,
            'period' => $cycleStart->format('Y-m'),
            'applications_allowed' => $this->applicationsAllowedForCycle($cycleIndex),
        ];
    }

    public function applicationsAllowedForCycle(int $cycleIndex): int
    {
        $rawAllowance = $this->currentApplicationsAllowed();

        if ($rawAllowance <= 0) {
            return -1;
        }

        if ($this->duration_days <= 30) {
            return $rawAllowance;
        }

        $remainderDays = $this->duration_days % 30;
        $lastCycleIndex = $this->cycleCount() - 1;

        if ($remainderDays === 0 || $cycleIndex < $lastCycleIndex) {
            return $rawAllowance;
        }

        return max(1, (int) ceil($rawAllowance * ($remainderDays / 30)));
    }

    public function applicationsLabel(): string
    {
        $applicationsAllowed = $this->currentApplicationsAllowed();

        if ($applicationsAllowed <= 0) {
            return 'Unlimited';
        }

        return $this->duration_days < 30
            ? $applicationsAllowed . ' per plan'
            : $applicationsAllowed . ' per 30 days';
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
