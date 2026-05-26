<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\SocialAutomation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SocialAutomationController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/job-board::job-board.name'))
            ->add('Automations', route('job-board.automations.index'));
    }

    public function index()
    {
        $this->pageTitle('Social Automations');

        $automations = SocialAutomation::query()
            ->orderBy('platform')
            ->orderBy('name')
            ->get()
            ->groupBy('platform');

        return view('plugins/job-board::automations.index', compact('automations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'platform' => ['required', Rule::in(['facebook', 'linkedin', 'whatsapp', 'telegram'])],
            'name'     => ['required', 'string', 'max:150'],
            'settings' => ['nullable', 'array'],
        ]);

        SocialAutomation::query()->create([
            'platform'  => $validated['platform'],
            'name'      => $validated['name'],
            'is_active' => false,
            'settings'  => $validated['settings'] ?? [],
        ]);

        return $this->httpResponse()
            ->setMessage('Automation added successfully.')
            ->setNextUrl(route('job-board.automations.index'));
    }

    public function update(SocialAutomation $automation, Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:150'],
            'settings' => ['nullable', 'array'],
        ]);

        // Merge new settings over existing — blank password fields keep the saved value.
        // Checkbox keys are absent when unchecked, so explicitly set them to 0 if missing.
        $checkboxKeys = ['generate_image'];
        $existing = $automation->settings ?? [];
        $incoming = $validated['settings'] ?? [];
        foreach ($checkboxKeys as $key) {
            $incoming[$key] = isset($incoming[$key]) ? 1 : 0;
        }
        $merged = array_merge($existing, array_filter($incoming, fn ($v) => $v !== null && $v !== ''));
        // Allow checkbox=0 to override existing=1
        foreach ($checkboxKeys as $key) {
            $merged[$key] = $incoming[$key];
        }

        $automation->fill([
            'name'     => $validated['name'],
            'settings' => $merged,
        ])->save();

        return $this->httpResponse()
            ->setMessage('Automation updated.')
            ->setNextUrl(route('job-board.automations.index'));
    }

    public function destroy(SocialAutomation $automation)
    {
        $automation->delete();

        return $this->httpResponse()->setMessage('Automation deleted.');
    }

    public function toggle(SocialAutomation $automation)
    {
        $automation->is_active = ! $automation->is_active;
        $automation->save();

        return $this->httpResponse()->setData(['is_active' => $automation->is_active]);
    }
}
