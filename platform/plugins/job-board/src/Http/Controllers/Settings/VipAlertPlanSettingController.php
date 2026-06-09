<?php

namespace Botble\JobBoard\Http\Controllers\Settings;

use Botble\JobBoard\Models\VipAlertOrder;
use Botble\Setting\Http\Controllers\SettingController;
use Illuminate\Http\Request;

class VipAlertPlanSettingController extends SettingController
{
    public function edit()
    {
        $this->pageTitle('VIP Alert Plans');

        $plans = VipAlertOrder::plans(includeDisabled: true);

        return view('plugins/job-board::settings.vip-alert-plans', compact('plans'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'plans'                 => ['required', 'array'],
            'plans.*.label'         => ['required', 'string', 'max:60'],
            'plans.*.duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'plans.*.price'         => ['required', 'numeric', 'min:0'],
            'plans.*.currency'      => ['required', 'string', 'size:3'],
            'plans.*.badge'         => ['nullable', 'string', 'max:40'],
            'plans.*.enabled'       => ['nullable', 'boolean'],
        ]);

        foreach (VipAlertOrder::defaultPlans() as $key => $defaults) {
            $plan = $data['plans'][$key] ?? $defaults;

            setting()->set("vip_alert_plan_{$key}_label", trim((string) $plan['label']));
            setting()->set("vip_alert_plan_{$key}_duration_days", (int) $plan['duration_days']);
            setting()->set("vip_alert_plan_{$key}_price", number_format((float) $plan['price'], 2, '.', ''));
            setting()->set("vip_alert_plan_{$key}_currency", strtoupper(trim((string) $plan['currency'])));
            setting()->set("vip_alert_plan_{$key}_badge", trim((string) ($plan['badge'] ?? '')));
            setting()->set("vip_alert_plan_{$key}_enabled", ! empty($plan['enabled']) ? '1' : '0');
        }

        setting()->save();

        return $this->httpResponse()
            ->setNextUrl(route('job-board.settings.vip-alert-plans'))
            ->setMessage('VIP Alert plans saved successfully.');
    }
}
