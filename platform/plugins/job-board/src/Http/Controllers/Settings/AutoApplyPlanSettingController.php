<?php

namespace Botble\JobBoard\Http\Controllers\Settings;

use Botble\JobBoard\Models\AutoApplyOrder;
use Botble\Setting\Http\Controllers\SettingController;
use Illuminate\Http\Request;

class AutoApplyPlanSettingController extends SettingController
{
    public function edit()
    {
        $this->pageTitle('Auto Apply Plans');

        $plans = AutoApplyOrder::plans(includeDisabled: true);
        $aiModel = AutoApplyOrder::globalAiModel();
        $matchThreshold = AutoApplyOrder::globalMatchThreshold();

        return view('plugins/job-board::settings.auto-apply-plans', compact('plans', 'aiModel', 'matchThreshold'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'plans'                          => ['required', 'array'],
            'plans.*.label'                  => ['required', 'string', 'max:60'],
            'plans.*.duration_days'          => ['required', 'integer', 'min:1', 'max:3650'],
            'plans.*.price'                  => ['required', 'numeric', 'min:0'],
            'plans.*.currency'               => ['required', 'string', 'size:3'],
            'plans.*.applications_per_month' => ['required', 'integer', 'min:0'],
            'plans.*.badge'                  => ['nullable', 'string', 'max:40'],
            'plans.*.enabled'                => ['nullable', 'boolean'],
            'ai_model'                       => ['required', 'string', 'in:gpt-4o-mini,gpt-4o'],
            'match_threshold'                => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        foreach (AutoApplyOrder::defaultPlans() as $key => $defaults) {
            $plan = $data['plans'][$key] ?? $defaults;

            setting()->set("auto_apply_plan_{$key}_label", trim((string) $plan['label']));
            setting()->set("auto_apply_plan_{$key}_duration_days", (int) $plan['duration_days']);
            setting()->set("auto_apply_plan_{$key}_price", number_format((float) $plan['price'], 2, '.', ''));
            setting()->set("auto_apply_plan_{$key}_currency", strtoupper(trim((string) $plan['currency'])));
            setting()->set("auto_apply_plan_{$key}_applications_per_month", (int) $plan['applications_per_month']);
            setting()->set("auto_apply_plan_{$key}_badge", trim((string) ($plan['badge'] ?? '')));
            setting()->set("auto_apply_plan_{$key}_enabled", ! empty($plan['enabled']) ? '1' : '0');
        }

        setting()->set('auto_apply_ai_model', $data['ai_model']);
        setting()->set('auto_apply_match_threshold', $data['match_threshold']);

        setting()->save();

        return $this->httpResponse()
            ->setNextUrl(route('job-board.settings.auto-apply-plans'))
            ->setMessage('Auto Apply plans saved successfully.');
    }
}
