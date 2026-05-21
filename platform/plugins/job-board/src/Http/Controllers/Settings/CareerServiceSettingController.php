<?php

namespace Botble\JobBoard\Http\Controllers\Settings;

use Botble\Setting\Http\Controllers\SettingController;
use Illuminate\Http\Request;

class CareerServiceSettingController extends SettingController
{
    public function edit()
    {
        $this->pageTitle('Career Service Settings');

        $services = [
            'cv_review'          => ['label' => 'Basic CV Review',            'default_price' => 12,  'default_delivery' => '24 hrs'],
            'cv_rewrite'         => ['label' => 'Professional CV Rewrite',    'default_price' => 35,  'default_delivery' => '48 hrs'],
            'linkedin'           => ['label' => 'LinkedIn Optimisation',      'default_price' => 25,  'default_delivery' => '48 hrs'],
            'cover_letter'       => ['label' => 'Cover Letter Writing',       'default_price' => 10,  'default_delivery' => '24 hrs'],
            'interview_coaching' => ['label' => 'Interview Coaching (1 hr)',  'default_price' => 45,  'default_delivery' => '72 hrs'],
            'bundle'             => ['label' => 'Complete Bundle',            'default_price' => 75,  'default_delivery' => '72 hrs'],
        ];

        $freeAlertLimit = setting('job_alert_free_monthly_limit', 3);
        $telegramToken  = setting('telegram_bot_token', '');

        return view('plugins/job-board::settings.career-services', compact('services', 'freeAlertLimit', 'telegramToken'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'services'                   => ['sometimes', 'array'],
            'services.*.price'           => ['numeric', 'min:0'],
            'services.*.delivery'        => ['string', 'max:20'],
            'job_alert_free_monthly_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'telegram_bot_token'         => ['nullable', 'string', 'max:200'],
        ]);

        foreach ($data['services'] ?? [] as $key => $fields) {
            setting()->set("career_service_price_{$key}", $fields['price'] ?? null);
            setting()->set("career_service_delivery_{$key}", $fields['delivery'] ?? null);
        }

        setting()->set('job_alert_free_monthly_limit', $data['job_alert_free_monthly_limit']);
        setting()->set('telegram_bot_token', $data['telegram_bot_token'] ?? '');
        setting()->save();

        return $this->httpResponse()
            ->setNextUrl(route('job-board.settings.career-services'))
            ->setMessage('Settings saved successfully.');
    }
}
