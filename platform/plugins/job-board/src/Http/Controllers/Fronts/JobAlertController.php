<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobAlert;
use Botble\JobBoard\Models\JobAlertQuota;
use Botble\SeoHelper\Facades\SeoHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JobAlertController extends BaseController
{
    public function index()
    {
        SeoHelper::setTitle(__('Job Alerts'));

        /** @var Account $account */
        $account = auth('account')->user();

        $alerts = $account->jobAlerts()->with(['category', 'country', 'state', 'city'])->latest()->get();

        $categories = Category::query()
            ->wherePublished()
            ->select('name', DB::raw('MIN(id) as id'))
            ->groupBy('name')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->collect();

        $countries = collect();
        if (is_plugin_active('location')) {
            $countries = \Botble\Location\Models\Country::query()->orderBy('name')->pluck('name', 'id');
        }

        $alertStats = $alerts
            ->mapWithKeys(function (JobAlert $alert): array {
                $matches = $this->matchedJobsForAlert($alert, 2);

                return [
                    $alert->getKey() => [
                        'matches_last_two_days' => $matches->count(),
                        'latest_match' => $matches->first(),
                    ],
                ];
            });

        $period = JobAlertQuota::currentPeriod();
        $sentThisMonth = (int) JobAlertQuota::query()
            ->where('account_id', $account->getKey())
            ->where('period', $period)
            ->sum('alerts_sent');

        return JobBoardHelper::scope(
            'account.job-alerts',
            compact('account', 'alerts', 'categories', 'countries', 'alertStats', 'sentThisMonth', 'period')
        );
    }

    public function store(Request $request)
    {
        $request->validate($this->validationRules());

        $categoryIds = array_values(array_filter((array) $request->input('category_ids', [])));

        if (! $request->input('keyword') && empty($categoryIds) && ! $request->input('country_id')) {
            return redirect()->back()
                ->withErrors(['keyword' => __('Please specify at least a keyword, category, or country.')])
                ->withInput();
        }

        /** @var Account $account */
        $account = auth('account')->user();

        $account->jobAlerts()->create([
            'keyword'          => $request->input('keyword'),
            'category_ids'     => $categoryIds ?: null,
            'country_id'       => $request->input('country_id') ?: null,
            'state_id'         => $request->input('state_id') ?: null,
            'city_id'          => $request->input('city_id') ?: null,
            'notify_email'     => (bool) $request->input('notify_email', false),
            'notify_whatsapp'  => (bool) $request->input('notify_whatsapp', false),
            'notify_telegram'  => (bool) $request->input('notify_telegram', false),
            'is_active'        => true,
        ]);

        return redirect()->back()->with('success', __('Job alert created successfully.'));
    }

    public function update(JobAlert $jobAlert, Request $request)
    {
        /** @var Account $account */
        $account = auth('account')->user();

        if ($jobAlert->account_id !== $account->id) {
            abort(403);
        }

        // Allow toggling is_active via a simple request, or a full update
        if ($request->has('is_active') && count($request->all()) <= 2) {
            $jobAlert->update(['is_active' => (bool) $request->input('is_active')]);
        } else {
            $request->validate($this->validationRules());

            $jobAlert->update([
                'keyword'         => $request->input('keyword'),
                'category_id'     => $request->input('category_id') ?: null,
                'country_id'      => $request->input('country_id') ?: null,
                'state_id'        => $request->input('state_id') ?: null,
                'city_id'         => $request->input('city_id') ?: null,
                'notify_email'    => (bool) $request->input('notify_email', false),
                'notify_whatsapp' => (bool) $request->input('notify_whatsapp', false),
                'notify_telegram' => (bool) $request->input('notify_telegram', false),
                'is_active'       => (bool) $request->input('is_active', $jobAlert->is_active),
            ]);
        }

        return redirect()->back()->with('success', __('Job alert updated.'));
    }

    public function destroy(JobAlert $jobAlert)
    {
        /** @var Account $account */
        $account = auth('account')->user();

        if ($jobAlert->account_id !== $account->id) {
            abort(403);
        }

        $jobAlert->delete();

        return redirect()->back()->with('success', __('Job alert deleted.'));
    }

    protected function validationRules(): array
    {
        return [
            'keyword'          => 'nullable|string|max:255',
            'category_id'      => 'nullable|numeric|exists:jb_categories,id',
            'country_id'       => 'nullable|numeric',
            'state_id'         => 'nullable|numeric',
            'city_id'          => 'nullable|numeric',
            'notify_email'     => 'nullable|boolean',
            'notify_whatsapp'  => 'nullable|boolean',
            'notify_telegram'  => 'nullable|boolean',
            'is_active'        => 'nullable|boolean',
        ];
    }

    protected function matchedJobsForAlert(JobAlert $alert, int $days = 2)
    {
        return Job::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->with(['categories', 'company', 'slugable'])
            ->latest('created_at')
            ->get()
            ->filter(fn (Job $job): bool => $this->jobMatchesAlert($job, $alert))
            ->values();
    }

    protected function jobMatchesAlert(Job $job, JobAlert $alert): bool
    {
        $jobText = Str::lower($job->name . ' ' . strip_tags((string) ($job->content ?? '')));

        if ($alert->keyword !== null && $alert->keyword !== '' && ! Str::contains($jobText, Str::lower($alert->keyword))) {
            return false;
        }

        $jobCategoryIds = $job->categories->pluck('id');
        $categoryIds = array_filter((array) ($alert->category_ids ?: ($alert->category_id ? [$alert->category_id] : [])));

        if ($categoryIds && ! collect($categoryIds)->contains(fn ($id): bool => $jobCategoryIds->contains((int) $id))) {
            return false;
        }

        if ($alert->country_id !== null && $job->country_id != $alert->country_id) {
            return false;
        }

        if ($alert->state_id !== null && $job->state_id != $alert->state_id) {
            return false;
        }

        if ($alert->city_id !== null && $job->city_id != $alert->city_id) {
            return false;
        }

        return true;
    }

}
