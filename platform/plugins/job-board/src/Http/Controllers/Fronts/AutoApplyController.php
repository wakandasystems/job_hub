<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AutoApplyLog;
use Botble\JobBoard\Models\AutoApplyOrder;
use Botble\JobBoard\Models\AutoApplyPreference;
use Botble\JobBoard\Models\AutoApplyQuota;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Services\AutoApplyService;
use Botble\SeoHelper\Facades\SeoHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AutoApplyController extends BaseController
{
    public function index()
    {
        SeoHelper::setTitle(__('Auto Apply'));

        /** @var Account $account */
        $account = auth('account')->user();

        $preference = AutoApplyPreference::where('account_id', $account->id)->first();

        $logs = AutoApplyLog::where('account_id', $account->id)
            ->with('job')
            ->latest()
            ->take(50)
            ->get();

        $quota = AutoApplyQuota::currentForAccount($account->id);
        $activeOrder = AutoApplyOrder::activeForAccount($account->id);
        $period = $quota && $quota->cycle_started_at && $quota->cycle_ends_at
            ? $quota->cycle_started_at->format('d M Y') . ' - ' . $quota->cycle_ends_at->copy()->subDay()->format('d M Y')
            : AutoApplyQuota::currentPeriod();

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

        $hasCv = trim((string) $account->resume) !== '';
        $plans = AutoApplyOrder::plans();

        return JobBoardHelper::scope(
            'account.auto-apply',
            compact('account', 'preference', 'logs', 'quota', 'activeOrder', 'categories', 'countries', 'hasCv', 'plans', 'period')
        );
    }

    public function updatePreference(Request $request)
    {
        $data = $request->validate([
            'keywords'                => ['nullable', 'string', 'max:1000'],
            'category_ids'            => ['nullable', 'array'],
            'country_ids'             => ['nullable', 'array'],
            'location_keyword'        => ['nullable', 'string', 'max:200'],
            'job_experience_id'       => ['nullable', 'integer'],
            'blacklisted_company_ids' => ['nullable', 'array'],
            'match_score_threshold'   => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_active'               => ['nullable'],
        ]);

        /** @var Account $account */
        $account = auth('account')->user();

        // CV required
        if (trim((string) $account->resume) === '') {
            return redirect()->back()
                ->withErrors(['resume' => 'You must upload a CV before enabling Auto Apply. Go to your profile settings to upload one.'])
                ->withInput();
        }

        // Parse comma-separated keywords into array
        $keywords = array_values(array_filter(array_map('trim', explode(',', $data['keywords'] ?? ''))));

        AutoApplyPreference::updateOrCreate(
            ['account_id' => $account->id],
            [
                'keywords'                => $keywords,
                'category_ids'            => $data['category_ids'] ?? [],
                'country_ids'             => $data['country_ids'] ?? [],
                'location_keyword'        => $data['location_keyword'] ?? null,
                'job_experience_id'       => $data['job_experience_id'] ?? null,
                'blacklisted_company_ids' => $data['blacklisted_company_ids'] ?? [],
                'match_score_threshold'   => $data['match_score_threshold'] ?? AutoApplyOrder::globalMatchThreshold(),
                'is_active'               => ! empty($data['is_active']),
            ]
        );

        return redirect()->back()->with('success', 'Auto Apply preferences updated.');
    }

    /**
     * Show backfill preview — recent jobs matching the candidate's filters.
     */
    public function backfillPreview()
    {
        SeoHelper::setTitle(__('Auto Apply — Review Matching Jobs'));

        /** @var Account $account */
        $account = auth('account')->user();

        $preference = AutoApplyPreference::where('account_id', $account->id)->first();

        if (! $preference) {
            return redirect()->route('public.account.auto-apply.index')
                ->with('error', 'Please set up your Auto Apply preferences first.');
        }

        // Find recent published jobs with apply_email that match filters
        $jobs = Job::query()
            ->where('status', JobStatusEnum::PUBLISHED)
            ->whereNotNull('apply_email')
            ->where('apply_email', '!=', '')
            ->where('created_at', '>=', now()->subDays(7))
            ->with(['company', 'categories', 'slugable'])
            ->latest()
            ->limit(100)
            ->get();

        // Filter by preference criteria
        $matchingJobs = $jobs->filter(function (Job $job) use ($preference, $account) {
            // Already applied?
            if (AutoApplyLog::where('account_id', $account->id)->where('job_id', $job->id)->exists()) {
                return false;
            }
            if (\Botble\JobBoard\Models\JobApplication::where('account_id', $account->id)->where('job_id', $job->id)->exists()) {
                return false;
            }

            // Blacklisted company
            $blacklisted = $preference->blacklisted_company_ids ?? [];
            if ($job->company_id && in_array($job->company_id, $blacklisted)) {
                return false;
            }

            return $this->jobMatchesPreference($job, $preference);
        });

        return JobBoardHelper::scope(
            'account.auto-apply-backfill',
            compact('account', 'preference', 'matchingJobs')
        );
    }

    /**
     * Send auto-apply for a single job from the backfill preview.
     */
    public function sendSingle(Request $request, $jobId)
    {
        /** @var Account $account */
        $account = auth('account')->user();

        $job = Job::findOrFail($jobId);

        $service = app(AutoApplyService::class);

        // Checks
        if ($service->hasAlreadyApplied($account->id, $job->id)) {
            return redirect()->back()->with('error', 'You have already applied to this job.');
        }

        if (! $service->hasQuota($account->id)) {
            return redirect()->back()->with('error', 'You have no remaining auto-apply quota this month. Please upgrade your plan.');
        }

        $cvText = $service->extractCvText($account);
        $profile = $service->buildCandidateProfile($account, $cvText);
        $log = $service->processAutoApply($account, $job, $profile);

        if ($log && $log->status === 'sent') {
            return redirect()->back()->with('success', "Application sent to {$job->name}!");
        }

        $errorMsg = $log?->error_message ?? 'Failed to send application.';

        return redirect()->back()->with('error', $errorMsg);
    }

    private function jobMatchesPreference(Job $job, AutoApplyPreference $preference): bool
    {
        $keywords = array_filter(array_map('trim', (array) ($preference->keywords ?? [])));
        if ($keywords) {
            $matched = false;
            foreach ($keywords as $kw) {
                $kwPat = '/\b' . preg_quote($kw, '/') . '\b/iu';
                if (preg_match($kwPat, $job->name)
                    || preg_match($kwPat, (string) ($job->description ?? ''))
                    || preg_match($kwPat, (string) ($job->address ?? ''))) {
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                return false;
            }
        }

        if (! empty($preference->category_ids)) {
            $ids = array_filter(array_map('intval', (array) $preference->category_ids));
            $catIds = $job->categories->pluck('id')->toArray();
            if ($ids && ! empty($catIds) && empty(array_intersect($ids, $catIds))) {
                return false;
            }
        }

        if (! empty($preference->country_ids)) {
            $ids = array_filter(array_map('intval', (array) $preference->country_ids));
            if ($ids && ! in_array((int) $job->country_id, $ids)) {
                return false;
            }
        }

        if ($preference->location_keyword) {
            $loc = trim($preference->location_keyword);
            if ($loc !== '' && stripos((string) ($job->address ?? ''), $loc) === false) {
                return false;
            }
        }

        return true;
    }
}
