<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Botble\JobBoard\Services\SalesAgentService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class JobAlertOrder extends BaseModel
{
    protected $table = 'jb_job_alert_orders';

    protected $fillable = [
        'account_id',
        'sales_agent_id',
        'sales_agent_original_amount',
        'sales_agent_discount_amount',
        'sales_agent_code',
        'package_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'charge_id',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'sales_agent_original_amount' => 'float',
        'sales_agent_discount_amount' => 'float',
        'approved_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function salesAgent(): BelongsTo
    {
        return $this->belongsTo(SalesAgent::class, 'sales_agent_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(JobAlertPackage::class, 'package_id');
    }

    public function approve(): void
    {
        $this->update(['status' => 'approved', 'approved_at' => now()]);

        if ($this->sales_agent_id) {
            $agent = SalesAgent::query()->find($this->sales_agent_id);

            if ($agent) {
                $service = app(SalesAgentService::class);
                $service->recordReferral($agent, $this->account?->phone, $this->sales_agent_code, 'job_alert', $this->account_id);
                $service->creditCommission($agent, 'job_alert_order', $this->getKey(), (float) $this->amount, $this->currency);
            }
        }

        $package = $this->package;
        if (! $package) {
            return;
        }

        $period  = JobAlertQuota::currentPeriod();
        $allowed = $package->isUnlimited() ? -1 : $package->alerts_per_month;

        DB::table('jb_job_alert_quotas')->updateOrInsert(
            ['account_id' => $this->account_id, 'period' => $period, 'package_id' => $this->package_id],
            [
                'alerts_allowed' => $allowed,
                'alerts_sent'    => 0,
                'charge_id'      => $this->charge_id,
                'payment_method' => $this->payment_method,
                'is_approved'    => true,
                'updated_at'     => now(),
                'created_at'     => now(),
            ]
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
