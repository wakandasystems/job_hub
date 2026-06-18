<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class VipAlertOrder extends BaseModel
{
    protected $table = 'jb_vip_alert_orders';

    protected $fillable = [
        'public_token',
        'candidate_name',
        'candidate_phone',
        'candidate_email',
        'plan',
        'duration_days',
        'amount',
        'currency',
        'charge_id',
        'payment_method',
        'payment_status',
        'admin_status',
        'filters',
        'notes',
        'candidate_alert_id',
        'approved_at',
    ];

    protected $casts = [
        'filters'     => 'array',
        'amount'      => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    private const DEFAULT_PLANS = [
        'weekly'   => ['label' => '1 Week',   'duration_days' => 7,  'price' => 5.00,   'currency' => 'USD', 'badge' => null, 'enabled' => true],
        'monthly'  => ['label' => '1 Month',  'duration_days' => 30, 'price' => 15.00,  'currency' => 'USD', 'badge' => 'Most Popular', 'enabled' => true],
        'one_time' => ['label' => '3 Months', 'duration_days' => 90, 'price' => 150.00, 'currency' => 'USD', 'badge' => 'Best Value', 'enabled' => true],
    ];

    protected static function booted(): void
    {
        static::creating(function (VipAlertOrder $order): void {
            $order->public_token ??= Str::random(64);
        });
    }

    public static function defaultPlans(): array
    {
        return self::DEFAULT_PLANS;
    }

    public static function plans(bool $includeDisabled = false): array
    {
        $plans = [];

        foreach (self::DEFAULT_PLANS as $key => $defaults) {
            $plan = [
                'label' => trim((string) setting("vip_alert_plan_{$key}_label", $defaults['label'])),
                'duration_days' => max(1, (int) setting("vip_alert_plan_{$key}_duration_days", $defaults['duration_days'])),
                'price' => max(0, (float) setting("vip_alert_plan_{$key}_price", $defaults['price'])),
                'currency' => strtoupper(trim((string) setting("vip_alert_plan_{$key}_currency", $defaults['currency']))),
                'badge' => trim((string) setting("vip_alert_plan_{$key}_badge", $defaults['badge'] ?? '')) ?: null,
                'enabled' => (bool) setting("vip_alert_plan_{$key}_enabled", $defaults['enabled']),
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

    public static function renewalPricingLines(): string
    {
        $plans = collect(self::plans())->sortBy('duration_days');

        return $plans
            ->map(function (array $plan): string {
                $price = $plan['price'] == floor($plan['price'])
                    ? number_format($plan['price'], 0)
                    : number_format($plan['price'], 2);

                return "• {$plan['duration_days']} Days — {$plan['currency']} {$price}";
            })
            ->implode("\n");
    }

    public function candidateAlert(): BelongsTo
    {
        return $this->belongsTo(CandidateAlert::class, 'candidate_alert_id');
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

        $now = now();

        $alert = CandidateAlert::create([
            'label'           => $this->candidate_name,
            'candidate_name'  => $this->candidate_name,
            'candidate_phone' => $this->candidate_phone,
            'candidate_email' => $this->candidate_email,
            'filters'         => $this->filters ?? [],
            'duration_days'   => $this->duration_days,
            'price'           => $this->amount,
            'is_active'       => true,
            'status'          => 'active',
            'activated_at'    => $now,
            'expires_at'      => $now->copy()->addDays($this->duration_days),
            'notes'           => 'VIP Alert Order #' . $this->id . ' (' . $this->currency . ' ' . number_format((float) $this->amount, 2) . ')',
        ]);

        $this->update([
            'admin_status'       => 'approved',
            'candidate_alert_id' => $alert->id,
            'approved_at'        => $now,
        ]);

        $this->sendWelcomeMessage($alert);
    }

    private function sendWelcomeMessage(CandidateAlert $alert): void
    {
        $automation = SocialAutomation::where('platform', 'whapi')->where('is_active', true)->first();
        if (! $automation) {
            return;
        }

        $settings   = $automation->settings ?? [];
        $token      = SocialAutomation::whapiToken($automation);
        $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';

        if (! $token) {
            return;
        }

        $plan  = self::plan($this->plan, includeDisabled: true);
        $label = $plan ? $plan['label'] : ($this->duration_days . ' days');
        $price = $this->currency . ' ' . number_format((float) $this->amount, 2);

        $msg  = "🎉 *Welcome to Wakanda Jobs VIP Alert Service!*\n\n";
        $msg .= "Hi {$alert->candidate_name}! 👋\n\n";
        $msg .= "Your payment has been confirmed and your VIP job alert is now *active*. ";
        $msg .= "We will send you matching job openings directly on WhatsApp.\n\n";
        $msg .= "📋 *Subscription Details:*\n";
        $msg .= "• Plan: *{$label}* — {$price}\n";
        $msg .= "• Activated: *" . ($alert->activated_at?->format('d M Y') ?? now()->format('d M Y')) . "*\n";
        $msg .= "• Expires: *" . ($alert->expires_at?->format('d M Y') ?? 'N/A') . "*\n\n";
        $msg .= "Sit back and let us find your next opportunity! 🚀\n\n";
        $msg .= "_Wakanda Jobs — wakandajobs.com_";

        foreach ($alert->recipientJids() as $jid) {
            try {
                Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                    'to'   => $jid,
                    'body' => $msg,
                ]);
            } catch (Throwable) {
            }
        }
    }
}
