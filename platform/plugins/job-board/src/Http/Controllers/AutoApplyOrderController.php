<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AutoApplyLog;
use Botble\JobBoard\Models\AutoApplyOrder;
use Botble\JobBoard\Models\AutoApplyPreference;
use Botble\JobBoard\Models\AutoApplyPreview;
use Botble\JobBoard\Models\AutoApplyQuota;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\CandidateAlert;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobExperience;
use Botble\JobBoard\Models\AutoCvSession;
use Botble\JobBoard\Services\AutoApplyService;
use Botble\JobBoard\Services\CvScoringService;
use Botble\JobBoard\Services\WhapiSenderService;
use Botble\Media\Facades\RvMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AutoApplyOrderController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Auto Apply Orders', route('auto-apply-orders.index'));
    }

    public function index(Request $request)
    {
        $this->pageTitle('Auto Apply Orders');

        $query = AutoApplyOrder::query()->with(['account.avatar'])->latest();

        if ($status = $request->query('status')) {
            $query->where('admin_status', $status);
        }

        if ($search = $request->query('q')) {
            $query->whereHas('account', function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(30)->withQueryString();
        $jobCountsByOrderId = $orders->getCollection()
            ->mapWithKeys(fn (AutoApplyOrder $order) => [$order->id => $this->summarizeOrderJobCounts($order)]);

        $preferences = AutoApplyPreference::query()
            ->whereIn('account_id', $orders->getCollection()->pluck('account_id')->filter()->all())
            ->get()
            ->keyBy('account_id');

        $countryIds = $preferences->pluck('country_ids')->flatten()->filter()->unique()->values();
        $categoryIds = $preferences->pluck('category_ids')->flatten()->filter()->unique()->values();
        $companyIds = $preferences->pluck('blacklisted_company_ids')->flatten()->filter()->unique()->values();

        $countriesById = $countryIds->isNotEmpty()
            ? DB::table('countries')->whereIn('id', $countryIds)->pluck('name', 'id')
            : collect();
        $categoriesById = $categoryIds->isNotEmpty()
            ? Category::query()->whereIn('id', $categoryIds)->pluck('name', 'id')
            : collect();
        $companiesById = $companyIds->isNotEmpty()
            ? Company::query()->whereIn('id', $companyIds)->pluck('name', 'id')
            : collect();

        $editOrdersData = $orders->getCollection()->mapWithKeys(function (AutoApplyOrder $order) use ($preferences, $countriesById, $categoriesById, $companiesById) {
            $preference = $preferences->get($order->account_id);
            $countryIds = collect($preference?->country_ids ?? [])->filter()->values();
            $categoryIds = collect($preference?->category_ids ?? [])->filter()->values();
            $companyIds = collect($preference?->blacklisted_company_ids ?? [])->filter()->values();

            return [$order->id => [
                'account_id' => $order->account_id,
                'candidate_label' => trim(($order->account?->name ?? 'Deleted candidate') . ($order->account?->email ? ' (' . $order->account->email . ')' : '')),
                'keywords' => $preference?->keywords ?? [],
                'category_ids' => $categoryIds->all(),
                'category_labels' => $categoryIds->mapWithKeys(fn ($id) => [$id => $categoriesById->get($id, '#' . $id)])->all(),
                'country_ids' => $countryIds->all(),
                'country_labels' => $countryIds->mapWithKeys(fn ($id) => [$id => $countriesById->get($id, '#' . $id)])->all(),
                'location_keyword' => $preference?->location_keyword,
                'job_experience_id' => $preference?->job_experience_id,
                'blacklisted_company_ids' => $companyIds->all(),
                'blacklisted_company_labels' => $companyIds->mapWithKeys(fn ($id) => [$id => $companiesById->get($id, '#' . $id)])->all(),
                'match_score_threshold' => $preference?->match_score_threshold ?? AutoApplyOrder::globalMatchThreshold(),
                'is_active' => (bool) ($preference?->is_active ?? false),
                'has_cv' => (bool) $order->account?->resume,
                'resume_name' => $order->account?->resume_name ?: ($order->account?->resume ? basename($order->account->resume) : null),
                'resume_url' => $order->account?->resume ? RvMedia::getImageUrl($order->account->resume) : null,
            ]];
        });

        $stats = [
            'total'    => AutoApplyOrder::count(),
            'pending'  => AutoApplyOrder::where('admin_status', 'pending')->count(),
            'approved' => AutoApplyOrder::where('admin_status', 'approved')->count(),
        ];

        $experiences = JobExperience::query()->orderBy('name')->pluck('name', 'id');
        $plans = AutoApplyOrder::plans(includeDisabled: true);

        return view(
            'plugins/job-board::auto-apply-orders.index',
            compact('orders', 'stats', 'experiences', 'plans', 'editOrdersData', 'jobCountsByOrderId')
        );
    }

    /**
     * Admin: search candidate accounts for the "Setup for Candidate" modal, paginated 3 at a time.
     */
    public function searchCandidates(Request $request, BaseHttpResponse $response)
    {
        $keyword = trim((string) $request->query('q'));

        $query = Account::query()->where('type', AccountTypeEnum::JOB_SEEKER);

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('first_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('last_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('email', 'LIKE', "%{$keyword}%");
            });
        }

        $candidates = $query
            // resume_name isn't a real column — it's a computed accessor derived from
            // resume below, so it can't be passed to select().
            ->select(['id', 'first_name', 'last_name', 'email', 'resume'])
            ->orderBy('first_name')
            ->paginate(3, ['*'], 'page', max((int) $request->query('page', 1), 1));

        return $response->setData([
            'items' => $candidates->getCollection()->map(fn (Account $account) => [
                'id'         => $account->id,
                'name'       => trim($account->first_name . ' ' . $account->last_name) ?: $account->email,
                'email'      => $account->email,
                'has_cv'     => (bool) $account->resume,
                'resume_name' => $account->resume_name ?: ($account->resume ? basename($account->resume) : null),
                'resume_url' => $account->resume ? \Botble\Media\Facades\RvMedia::getImageUrl($account->resume) : null,
            ])->values(),
            'current_page' => $candidates->currentPage(),
            'last_page'    => $candidates->lastPage(),
            'total'        => $candidates->total(),
        ]);
    }

    /**
     * Admin: search published countries for the "Setup for Candidate" modal, AJAX-driven (no full list sent to the page).
     */
    public function searchCountries(Request $request, BaseHttpResponse $response)
    {
        $keyword = trim((string) $request->query('q'));

        $query = DB::table('countries')->where('status', 'published');

        if ($keyword !== '') {
            $query->where('name', 'LIKE', "%{$keyword}%");
        }

        $query->orderBy('name');

        if (! $request->boolean('all')) {
            $query->limit(20);
        }

        $countries = $query->get(['id', 'name']);

        return $response->setData([
            'items' => $countries->map(fn ($country) => ['id' => $country->id, 'name' => $country->name])->values(),
        ]);
    }

    /**
     * Admin: search job categories for the "Setup for Candidate" modal, AJAX-driven (no full list sent to the page).
     */
    public function searchCategories(Request $request, BaseHttpResponse $response)
    {
        $keyword = trim((string) $request->query('q'));

        $query = Category::query();

        if ($keyword !== '') {
            $query->where('name', 'LIKE', "%{$keyword}%");
        }

        $categories = $query->orderBy('name')->limit(20)->get(['id', 'name']);

        return $response->setData([
            'items' => $categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])->values(),
        ]);
    }

    public function approve(AutoApplyOrder $autoApplyOrder, BaseHttpResponse $response)
    {
        if ($autoApplyOrder->admin_status !== 'pending') {
            return $response->setError()->setMessage('This order has already been processed.');
        }

        $autoApplyOrder->approve();
        $this->sendConfirmationEmail($autoApplyOrder->fresh(['account']));

        return $response
            ->setNextUrl(route('auto-apply-orders.index'))
            ->setMessage('Auto Apply activated for ' . ($autoApplyOrder->account?->name ?? 'candidate') . '.');
    }

    public function reject(AutoApplyOrder $autoApplyOrder, Request $request, BaseHttpResponse $response)
    {
        if ($autoApplyOrder->admin_status !== 'pending') {
            return $response->setError()->setMessage('This order has already been processed.');
        }

        $autoApplyOrder->update([
            'admin_status' => 'rejected',
            'status'       => 'rejected',
            'notes'        => $request->input('notes'),
        ]);

        return $response
            ->setNextUrl(route('auto-apply-orders.index'))
            ->setMessage('Order rejected.');
    }

    public function update(AutoApplyOrder $autoApplyOrder, Request $request, BaseHttpResponse $response)
    {
        $planKeys = array_keys(AutoApplyOrder::plans(includeDisabled: true));

        $data = $request->validate([
            'account_id'              => ['required', 'exists:jb_accounts,id'],
            'plan'                 => ['required', Rule::in($planKeys)],
            'keywords'                => ['nullable', 'array'],
            'category_ids'            => ['nullable', 'array'],
            'country_ids'             => ['nullable', 'array'],
            'location_keyword'        => ['nullable', 'string', 'max:200'],
            'job_experience_id'       => ['nullable', 'integer'],
            'blacklisted_company_ids' => ['nullable', 'array'],
            'match_score_threshold'   => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_active'               => ['nullable', 'boolean'],
            'payment_method'       => ['nullable', 'string', 'max:100'],
            'status'               => ['required', 'in:pending,approved,rejected,cancelled'],
            'admin_status'         => ['required', 'in:pending,approved,rejected,cancelled'],
            'notes'                => ['nullable', 'string'],
        ]);

        $planValues = $this->orderValuesFromPlan($data['plan']);
        $orderData = array_merge([
            'plan'           => $data['plan'],
            'payment_method' => $data['payment_method'] ?: null,
            'status'         => $data['status'],
            'admin_status'   => $data['admin_status'],
            'notes'          => $data['notes'] ?? null,
        ], $planValues);

        if ($orderData['admin_status'] === 'approved' && ! $autoApplyOrder->approved_at) {
            $orderData['approved_at'] = now();
        }

        if ($orderData['admin_status'] !== 'approved') {
            $orderData['approved_at'] = null;
        }

        $autoApplyOrder->update($orderData);

        if ($request->hasFile('cv_file')) {
            $request->validate(['cv_file' => ['file', 'mimes:pdf,doc,docx,txt', 'max:10240']]);

            if ($account = Account::query()->find($data['account_id'])) {
                $this->persistCandidateCv($account, $request->file('cv_file'));
            }
        }

        AutoApplyPreference::updateOrCreate(
            ['account_id' => $data['account_id']],
            [
                'keywords'                => $data['keywords'] ?? [],
                'category_ids'            => $data['category_ids'] ?? [],
                'country_ids'             => $data['country_ids'] ?? [],
                'location_keyword'        => $data['location_keyword'] ?? null,
                'job_experience_id'       => $data['job_experience_id'] ?? null,
                'blacklisted_company_ids' => $data['blacklisted_company_ids'] ?? [],
                'match_score_threshold'   => $data['match_score_threshold'] ?? AutoApplyOrder::globalMatchThreshold(),
                'is_active'               => (bool) ($data['is_active'] ?? false),
            ]
        );

        if ($orderData['admin_status'] === 'approved') {
            AutoApplyQuota::syncForAccount($autoApplyOrder->account_id);
        }

        return $response
            ->setNextUrl(route('auto-apply-orders.index'))
            ->setMessage('Auto Apply order updated.');
    }

    public function disable(AutoApplyOrder $autoApplyOrder, Request $request, BaseHttpResponse $response)
    {
        $notes = trim((string) $request->input('notes'));

        $autoApplyOrder->update([
            'status'       => 'cancelled',
            'admin_status' => 'cancelled',
            'notes'        => $notes !== '' ? $notes : $autoApplyOrder->notes,
        ]);

        AutoApplyPreference::query()
            ->where('account_id', $autoApplyOrder->account_id)
            ->update(['is_active' => false]);

        AutoApplyQuota::query()
            ->where('account_id', $autoApplyOrder->account_id)
            ->update([
                'is_approved' => false,
                'updated_at'  => now(),
            ]);

        return $response
            ->setNextUrl(route('auto-apply-orders.index'))
            ->setMessage('Auto Apply disabled for candidate.');
    }

    public function destroy(AutoApplyOrder $autoApplyOrder, BaseHttpResponse $response)
    {
        $autoApplyOrder->delete();

        return $response
            ->setNextUrl(route('auto-apply-orders.index'))
            ->setMessage('Auto Apply order deleted.');
    }

    /**
     * Admin: preview a sample auto-apply email for a candidate using a specific AI model.
     */
    public function preview(Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'account_id' => ['required', 'exists:jb_accounts,id'],
            'job_id'     => ['required', 'exists:jb_jobs,id'],
            'ai_model'   => ['required', 'in:gpt-4o-mini,gpt-4o'],
        ]);

        $account = Account::findOrFail($request->input('account_id'));
        $job = \Botble\JobBoard\Models\Job::findOrFail($request->input('job_id'));
        $aiModel = $request->input('ai_model');

        $extra = [
            'job' => $this->formatJobDetailForPreview($job),
            'resume_url' => $account->resume ? \Botble\Media\Facades\RvMedia::getImageUrl($account->resume) : null,
        ];

        // Reuse a cached preview unless the candidate's CV/profile has changed since it was generated.
        $profileSyncedAt = $account->profile_updated_at ?? $account->updated_at;

        $cached = AutoApplyPreview::query()
            ->where('account_id', $account->id)
            ->where('job_id', $job->id)
            ->where('ai_model', $aiModel)
            ->first();

        if (
            $cached
            && $cached->account_profile_synced_at
            && $profileSyncedAt
            && $cached->account_profile_synced_at->gte($profileSyncedAt)
        ) {
            return $response->setData([
                'score'             => $cached->score,
                'reasons'           => $cached->reasons ?? [],
                'subject'           => $cached->subject,
                'body'              => $cached->body,
                'ai_model'          => $cached->ai_model,
                'prompt_tokens'     => $cached->prompt_tokens,
                'completion_tokens' => $cached->completion_tokens,
                'total_tokens'      => $cached->total_tokens,
                'cost'              => $cached->cost_usd,
                'cached'            => true,
            ] + $extra)->setMessage('Preview loaded from cache — no new AI call made.');
        }

        $service = app(AutoApplyService::class);
        $cvText = $service->extractCvText($account);
        $profile = $service->buildCandidateProfile($account, $cvText);

        $result = $service->generateApplicationEmail($account, $job, $profile, $aiModel);

        if (! $result) {
            return $response->setError()->setMessage('OpenAI failed to generate email. Check API key configuration.');
        }

        AutoApplyPreview::updateOrCreate(
            ['account_id' => $account->id, 'job_id' => $job->id, 'ai_model' => $aiModel],
            [
                'score'                     => $result['score'],
                'reasons'                   => $result['reasons'],
                'subject'                   => $result['subject'],
                'body'                      => $result['body'],
                'prompt_tokens'             => $result['prompt_tokens'] ?? null,
                'completion_tokens'         => $result['completion_tokens'] ?? null,
                'total_tokens'              => $result['total_tokens'] ?? null,
                'cost_usd'                  => $result['cost'] ?? null,
                'account_profile_synced_at' => $profileSyncedAt,
            ]
        );

        $result['cached'] = false;

        return $response->setData($result + $extra)->setMessage('Preview generated successfully.');
    }

    private function formatJobDetailForPreview(Job $job): array
    {
        $job->loadMissing(['company', 'categories', 'skills', 'jobTypes', 'country']);

        return [
            'name' => $job->name,
            'company' => $job->company?->name ?? '',
            'company_logo' => $job->company?->logo ? \Botble\Media\Facades\RvMedia::getImageUrl($job->company->logo) : null,
            'address' => $job->address ?? '',
            'country' => $job->country?->name ?? '',
            'country_flag' => $this->countryFlagEmoji((string) ($job->country?->code ?? '')),
            'job_types' => $job->jobTypes->pluck('name')->values()->all(),
            'categories' => $job->categories->pluck('name')->values()->all(),
            'skills' => $job->skills->pluck('name')->values()->all(),
            'salary_text' => $job->salary_text,
            'apply_email' => $job->apply_email,
            'created_at' => $job->created_at?->toDateString(),
            'closing_date' => $job->application_closing_date?->toDateString(),
            // `content` is the rich HTML version of the job ad; `description` is a flattened
            // plain-text summary. Prefer content so the ad renders with its original formatting.
            'description' => $job->content
                ? \Botble\Base\Facades\BaseHelper::clean($job->content)
                : ($job->description ? \Botble\Base\Facades\BaseHelper::clean($job->description) : ''),
        ];
    }

    public function activeJobs(AutoApplyOrder $autoApplyOrder, Request $request, BaseHttpResponse $response)
    {
        if (! $autoApplyOrder->account) {
            return $response->setError()->setMessage('This order has no candidate attached.');
        }

        if (trim((string) $autoApplyOrder->account->resume) === '') {
            return $response->setError()->setMessage('Candidate has no CV uploaded.');
        }

        $keyword = trim((string) $request->query('q'));
        $perPage = 10;
        $page = max(1, (int) $request->query('page', 1));
        $preference = AutoApplyPreference::query()->where('account_id', $autoApplyOrder->account_id)->first();
        $query = $this->buildActiveJobsQuery($autoApplyOrder, $keyword, true);

        $selectColumns = ['id', 'name', 'description', 'address', 'apply_email', 'created_at', 'application_closing_date', 'company_id', 'country_id'];

        $paginator = $query
            ->with([
                'company' => fn ($q) => $q->select(['id', 'name', 'logo'])->with('slugable'),
                'country:id,name,code',
                'slugable',
            ])
            ->paginate($perPage, $selectColumns, 'page', $page);

        $items = $paginator->getCollection()
            ->map(fn (Job $job) => $this->formatActiveJobItem($job))
            ->values();

        $pagination = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'has_more_pages' => $paginator->hasMorePages(),
        ];

        return $response->setData([
            'account_id' => $autoApplyOrder->account_id,
            'match_score_threshold' => (int) (($preference?->match_score_threshold) ?? AutoApplyOrder::globalMatchThreshold()),
            'items' => $items,
            'pagination' => $pagination,
        ]);
    }

    public function sendAllActiveJobs(AutoApplyOrder $autoApplyOrder, Request $request, BaseHttpResponse $response)
    {
        if (! $autoApplyOrder->account) {
            return $response->setError()->setMessage('This order has no candidate attached.');
        }

        $account = $autoApplyOrder->account;

        if (trim((string) $account->resume) === '') {
            return $response->setError()->setMessage('Candidate has no CV uploaded.');
        }

        $keyword = trim((string) $request->input('q', ''));
        $threshold = (int) (AutoApplyPreference::query()
            ->where('account_id', $account->id)
            ->value('match_score_threshold') ?? AutoApplyOrder::globalMatchThreshold());

        $jobs = $this->buildActiveJobsQuery($autoApplyOrder, $keyword)
            ->get(['id', 'name', 'description', 'address', 'apply_email', 'created_at', 'application_closing_date', 'company_id', 'country_id']);

        if ($jobs->isEmpty()) {
            return $response->setError()->setMessage('No matching active jobs were found.');
        }

        $processedJobIds = AutoApplyLog::query()
            ->where('account_id', $account->id)
            ->whereIn('job_id', $jobs->pluck('id'))
            ->pluck('job_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $processedLookup = array_fill_keys($processedJobIds, true);
        $summary = [
            'matched_total' => $jobs->count(),
            'queued' => 0,
            'already_processed' => 0,
            'below_threshold' => 0,
            'scoring_failed' => 0,
            'missing_apply_email' => 0,
            'queued_job_ids' => [],
            'below_threshold_job_ids' => [],
            'scoring_failed_job_ids' => [],
            'already_processed_job_ids' => [],
        ];

        foreach ($jobs as $job) {
            $result = $this->queueAutoApplyJob($account, $job, $threshold, isset($processedLookup[$job->id]));

            if (! isset($summary[$result['status']])) {
                continue;
            }

            $summary[$result['status']]++;

            if (! empty($result['job_id'])) {
                $statusKey = $result['status'] . '_job_ids';

                if (isset($summary[$statusKey])) {
                    $summary[$statusKey][] = (int) $result['job_id'];
                }
            }
        }

        return $response
            ->setData($summary)
            ->setMessage(
                "Queued {$summary['queued']} job(s) across all matching pages. "
                . "{$summary['already_processed']} already processed, "
                . "{$summary['below_threshold']} below threshold, "
                . "{$summary['scoring_failed']} could not be scored."
            );
    }

    public function previewSetupJobs(Request $request, BaseHttpResponse $response)
    {
        $data = $request->validate([
            'keywords'                => ['nullable', 'array'],
            'keywords.*'              => ['nullable', 'string', 'max:120'],
            'category_ids'            => ['nullable', 'array'],
            'category_ids.*'          => ['nullable', 'integer'],
            'country_ids'             => ['nullable', 'array'],
            'country_ids.*'           => ['nullable', 'integer'],
            'location_keyword'        => ['nullable', 'string', 'max:200'],
            'job_experience_id'       => ['nullable', 'integer'],
            'blacklisted_company_ids' => ['nullable', 'array'],
            'blacklisted_company_ids.*' => ['nullable', 'integer'],
        ]);

        $keywords = array_values(array_filter(array_map('trim', (array) ($data['keywords'] ?? []))));
        $countryIds = array_values(array_filter(array_map('intval', (array) ($data['country_ids'] ?? []))));
        $categoryIds = array_values(array_filter(array_map('intval', (array) ($data['category_ids'] ?? []))));
        $blacklistedCompanyIds = array_values(array_filter(array_map('intval', (array) ($data['blacklisted_company_ids'] ?? []))));
        $locationKeyword = trim((string) ($data['location_keyword'] ?? ''));
        $jobExperienceId = (int) ($data['job_experience_id'] ?? 0);

        $query = Job::query()
            ->where('status', JobStatusEnum::PUBLISHED)
            ->whereNotNull('apply_email')
            ->where('apply_email', '!=', '')
            ->with([
                'company' => fn ($q) => $q->select(['id', 'name', 'logo'])->with('slugable'),
                'country:id,name,code',
                'slugable',
            ])
            ->latest();

        if ($keywords) {
            $query->where(function ($q) use ($keywords): void {
                foreach ($keywords as $keyword) {
                    $pattern = $this->keywordRegexPattern($keyword);
                    $q->orWhereRaw('LOWER(name) REGEXP ?', [$pattern])
                        ->orWhereRaw('LOWER(description) REGEXP ?', [$pattern])
                        ->orWhereRaw('LOWER(address) REGEXP ?', [$pattern]);
                }
            });
        }

        if ($countryIds) {
            $query->whereIn('country_id', $countryIds);
        }

        if ($categoryIds) {
            $query->whereHas('categories', fn ($q) => $q->whereIn('jb_categories.id', $categoryIds));
        }

        if ($locationKeyword !== '') {
            $query->where('address', 'LIKE', "%{$locationKeyword}%");
        }

        if ($jobExperienceId > 0) {
            $query->where('job_experience_id', $jobExperienceId);
        }

        if ($blacklistedCompanyIds) {
            $query->whereNotIn('company_id', $blacklistedCompanyIds);
        }

        $jobs = $query->limit(100)->get(['id', 'name', 'description', 'address', 'apply_email', 'created_at', 'application_closing_date', 'company_id', 'country_id']);

        return $response->setData([
            'items' => $jobs->map(fn (Job $job) => $this->formatActiveJobItem($job))->values(),
            'total' => $jobs->count(),
        ]);
    }

    /**
     * Score shown here is the real AI-generated CV-vs-job-description match score (not a
     * keyword heuristic). Already-processed jobs already have one recorded on their
     * AutoApplyLog from the actual send; unprocessed jobs are scored on-demand client-side
     * via the AI preview endpoint (which caches its result in AutoApplyPreview).
     */
    private function formatActiveJobItem(Job $job): array
    {
        $log = $job->autoApplyLogs->first();

        return [
            'id' => $job->id,
            'name' => $job->name,
            'url' => $job->url,
            'company' => $job->company?->name ?? '',
            'company_logo' => $job->company?->logo ? \Botble\Media\Facades\RvMedia::getImageUrl($job->company->logo) : null,
            'company_url' => $job->company?->url,
            'country' => $job->country?->name ?? '',
            'country_flag' => $this->countryFlagEmoji((string) ($job->country?->code ?? '')),
            'address' => trim((string) $job->address),
            'apply_email' => $job->apply_email,
            'created_at' => $job->created_at?->toDateString(),
            'closing_date' => $job->application_closing_date?->toDateString(),
            'score' => $log?->match_score,
            'match_reasons' => $log?->match_reasons ?? [],
            'needs_ai_score' => ! $log,
            'log_status' => $log?->status,
            'log_sent_at' => $log?->sent_at?->toDateString(),
            'log_error' => $log?->error_message,
        ];
    }

    private function resolveAutoApplyScore(Account $account, Job $job): ?int
    {
        $log = AutoApplyLog::query()
            ->where('account_id', $account->id)
            ->where('job_id', $job->id)
            ->latest('id')
            ->first();

        if ($log && $log->match_score !== null) {
            return (int) $log->match_score;
        }

        $profileSyncedAt = $account->profile_updated_at ?? $account->updated_at;
        $aiModel = AutoApplyOrder::globalAiModel();

        $cached = AutoApplyPreview::query()
            ->where('account_id', $account->id)
            ->where('job_id', $job->id)
            ->where('ai_model', $aiModel)
            ->first();

        if (
            $cached
            && $cached->score !== null
            && $cached->account_profile_synced_at
            && $profileSyncedAt
            && $cached->account_profile_synced_at->gte($profileSyncedAt)
        ) {
            return (int) $cached->score;
        }

        $service = app(AutoApplyService::class);
        $cvText = $service->extractCvText($account);
        $profile = $service->buildCandidateProfile($account, $cvText);
        $result = $service->generateApplicationEmail($account, $job, $profile, $aiModel);

        if (! $result || ! isset($result['score'])) {
            return null;
        }

        AutoApplyPreview::updateOrCreate(
            ['account_id' => $account->id, 'job_id' => $job->id, 'ai_model' => $aiModel],
            [
                'score' => (int) $result['score'],
                'reasons' => $result['reasons'] ?? [],
                'subject' => $result['subject'] ?? '',
                'body' => $result['body'] ?? '',
                'prompt_tokens' => $result['prompt_tokens'] ?? null,
                'completion_tokens' => $result['completion_tokens'] ?? null,
                'total_tokens' => $result['total_tokens'] ?? null,
                'cost_usd' => $result['cost'] ?? null,
                'account_profile_synced_at' => $profileSyncedAt,
            ]
        );

        return (int) $result['score'];
    }

    private function queueAutoApplyJob(Account $account, Job $job, int $threshold, bool $alreadyProcessed = false): array
    {
        if ($alreadyProcessed) {
            return [
                'status' => 'already_processed',
                'job_id' => $job->id,
            ];
        }

        if (trim((string) $job->apply_email) === '') {
            return [
                'status' => 'missing_apply_email',
                'job_id' => $job->id,
            ];
        }

        $score = $this->resolveAutoApplyScore($account, $job);

        if ($score === null) {
            return [
                'status' => 'scoring_failed',
                'job_id' => $job->id,
            ];
        }

        if ($score < $threshold) {
            return [
                'status' => 'below_threshold',
                'job_id' => $job->id,
            ];
        }

        \Botble\JobBoard\Jobs\ProcessAutoApplySendJob::dispatch($account->id, $job->id)->onQueue('emails');

        return [
            'status' => 'queued',
            'job_id' => $job->id,
        ];
    }

    private function buildActiveJobsQuery(AutoApplyOrder $autoApplyOrder, string $keyword = '', bool $includeLogs = false): Builder
    {
        $query = Job::query()
            ->where('status', JobStatusEnum::PUBLISHED)
            ->whereNotNull('apply_email')
            ->where('apply_email', '!=', '')
            ->orderByRaw(
                '(select count(*) from jb_auto_apply_logs where jb_auto_apply_logs.job_id = jb_jobs.id and jb_auto_apply_logs.account_id = ?) asc',
                [$autoApplyOrder->account_id]
            )
            ->latest();

        if ($includeLogs) {
            $query->with(['autoApplyLogs' => fn ($q) => $q->where('account_id', $autoApplyOrder->account_id)->latest('id')]);
        }

        $preference = AutoApplyPreference::query()->where('account_id', $autoApplyOrder->account_id)->first();
        $countryIds = $preference?->country_ids ?? [];

        if (! empty($countryIds)) {
            $query->whereIn('country_id', $countryIds);
        }

        $preferenceKeywords = array_values(array_filter(array_map('trim', (array) ($preference?->keywords ?? []))));

        if ($preferenceKeywords) {
            $query->where(function ($q) use ($preferenceKeywords): void {
                foreach ($preferenceKeywords as $kw) {
                    $pattern = $this->keywordRegexPattern($kw);
                    $q->orWhereRaw('LOWER(name) REGEXP ?', [$pattern])
                        ->orWhereRaw('LOWER(description) REGEXP ?', [$pattern])
                        ->orWhereRaw('LOWER(address) REGEXP ?', [$pattern]);
                }
            });
        }

        if ($keyword !== '') {
            $query->where('name', 'LIKE', "%{$keyword}%");
        }

        return $query;
    }

    private function summarizeOrderJobCounts(AutoApplyOrder $order): array
    {
        if (! $order->account_id || trim((string) $order->account?->resume) === '') {
            return [
                'ready' => null,
                'applied' => null,
                'total_matching' => null,
                'unsent_total' => null,
            ];
        }

        $matchingJobsQuery = $this->buildActiveJobsQuery($order)->reorder();
        $totalMatching = (clone $matchingJobsQuery)->count('jb_jobs.id');

        if ($totalMatching < 1) {
            return [
                'ready' => 0,
                'applied' => 0,
                'total_matching' => 0,
                'unsent_total' => 0,
            ];
        }

        $matchingJobIds = (clone $matchingJobsQuery)->select('jb_jobs.id');
        $appliedJobIds = AutoApplyLog::query()
            ->where('account_id', $order->account_id)
            ->whereIn('job_id', $matchingJobIds)
            ->distinct()
            ->pluck('job_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $applied = count($appliedJobIds);
        $unsentTotal = max($totalMatching - $applied, 0);

        if ($unsentTotal < 1) {
            return [
                'ready' => 0,
                'applied' => $applied,
                'total_matching' => $totalMatching,
                'unsent_total' => 0,
            ];
        }

        $unsentJobsQuery = $this->buildActiveJobsQuery($order)->reorder();

        if ($appliedJobIds !== []) {
            $unsentJobsQuery->whereNotIn('jb_jobs.id', $appliedJobIds);
        }

        $preference = AutoApplyPreference::query()->where('account_id', $order->account_id)->first();
        $threshold = (int) (($preference?->match_score_threshold) ?? AutoApplyOrder::globalMatchThreshold());
        $profileSyncedAt = $order->account?->profile_updated_at ?? $order->account?->updated_at;
        $aiModel = AutoApplyOrder::globalAiModel();

        $ready = AutoApplyPreview::query()
            ->where('account_id', $order->account_id)
            ->where('ai_model', $aiModel)
            ->where('score', '>=', $threshold)
            ->when(
                $profileSyncedAt,
                fn ($query) => $query->whereNotNull('account_profile_synced_at')
                    ->where('account_profile_synced_at', '>=', $profileSyncedAt)
            )
            ->whereIn('job_id', (clone $unsentJobsQuery)->select('jb_jobs.id'))
            ->distinct()
            ->count('job_id');

        return [
            'ready' => $ready,
            'applied' => $applied,
            'total_matching' => $totalMatching,
            'unsent_total' => $unsentTotal,
        ];
    }

    public function sendJob(Request $request, BaseHttpResponse $response)
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:jb_accounts,id'],
            'job_id' => ['required', 'exists:jb_jobs,id'],
        ]);

        $account = Account::query()->findOrFail($data['account_id']);
        $job = Job::query()
            ->where('status', JobStatusEnum::PUBLISHED)
            ->whereKey($data['job_id'])
            ->firstOrFail();

        if (trim((string) $account->resume) === '') {
            return $response->setError()->setNextUrl(route('auto-apply-orders.index'))->setMessage('Candidate has no CV uploaded.');
        }

        $threshold = (int) (AutoApplyPreference::query()
            ->where('account_id', $account->id)
            ->value('match_score_threshold') ?? AutoApplyOrder::globalMatchThreshold());
        $result = $this->queueAutoApplyJob(
            $account,
            $job,
            $threshold,
            AutoApplyLog::query()->where('account_id', $account->id)->where('job_id', $job->id)->exists()
        );

        if ($result['status'] === 'already_processed') {
            return $response->setError()->setNextUrl(route('auto-apply-orders.index'))->setMessage('This job has already been processed for this candidate.');
        }

        if ($result['status'] === 'missing_apply_email') {
            return $response->setError()->setNextUrl(route('auto-apply-orders.index'))->setMessage('This job cannot be auto-applied because it has no application email.');
        }

        if ($result['status'] === 'scoring_failed') {
            return $response->setError()->setMessage('Could not confirm the AI match score for this job. Preview it first, then try again.');
        }

        if ($result['status'] === 'below_threshold') {
            $score = $this->resolveAutoApplyScore($account, $job);

            return $response->setError()->setMessage("This job scores {$score}% for this candidate, below the {$threshold}% threshold, so it cannot be sent.");
        }

        return $response
            ->setNextUrl(route('auto-apply-logs.index', ['account_id' => $account->id]))
            ->setMessage('Auto Apply queued for sending — check the logs in a few seconds for the result.');
    }

    /**
     * Admin: set up auto-apply preference on behalf of a candidate.
     */
    public function setupForCandidate(Request $request, BaseHttpResponse $response)
    {
        $planKeys = array_keys(AutoApplyOrder::plans());

        $data = $request->validate([
            'account_id'              => ['nullable', 'exists:jb_accounts,id'],
            'create_candidate_account'=> ['nullable', 'boolean'],
            'candidate_first_name'    => ['nullable', 'string', 'max:120'],
            'candidate_last_name'     => ['nullable', 'string', 'max:120'],
            'candidate_email'         => ['nullable', 'email', 'max:150'],
            'candidate_phone'         => ['nullable', 'string', 'max:30'],
            'candidate_whatsapp_number' => ['nullable', 'string', 'max:30'],
            'plan'                    => ['required', Rule::in($planKeys)],
            'keywords'                => ['nullable', 'array'],
            'category_ids'            => ['nullable', 'array'],
            'country_ids'             => ['nullable', 'array'],
            'location_keyword'        => ['nullable', 'string', 'max:200'],
            'job_experience_id'       => ['nullable', 'integer'],
            'blacklisted_company_ids' => ['nullable', 'array'],
            'match_score_threshold'   => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_active'               => ['nullable', 'boolean'],
        ]);

        $account = null;
        $generatedPassword = null;

        if (! empty($data['account_id'])) {
            $account = Account::query()->find($data['account_id']);
        } elseif ($request->boolean('create_candidate_account')) {
            if (! $request->hasFile('cv_file')) {
                return $response->setError()->setMessage('Upload a CV first so we can create the candidate account from it.');
            }

            [$account, $generatedPassword, $createError] = $this->createCandidateAccountFromSetup($data);

            if (! $account) {
                return $response->setError()->setMessage($createError ?: 'Could not create the candidate account.');
            }
        }

        if (! $account) {
            return $response->setError()->setMessage('Select an existing candidate or create a new one from the uploaded CV.');
        }

        if ($request->hasFile('cv_file')) {
            $request->validate(['cv_file' => ['file', 'mimes:pdf,doc,docx,txt', 'max:10240']]);
            $this->persistCandidateCv($account, $request->file('cv_file'));
        }

        $preference = AutoApplyPreference::updateOrCreate(
            ['account_id' => $account->id],
            [
                'keywords'                => $data['keywords'] ?? [],
                'category_ids'            => $data['category_ids'] ?? [],
                'country_ids'             => $data['country_ids'] ?? [],
                'location_keyword'        => $data['location_keyword'] ?? null,
                'job_experience_id'       => $data['job_experience_id'] ?? null,
                'blacklisted_company_ids' => $data['blacklisted_company_ids'] ?? [],
                'match_score_threshold'   => $data['match_score_threshold'] ?? AutoApplyOrder::globalMatchThreshold(),
                'is_active'               => $data['is_active'] ?? true,
            ]
        );

        $planValues = $this->orderValuesFromPlan($data['plan']);

        // Record an order so this admin-configured candidate shows up in the Auto Apply Orders list
        AutoApplyOrder::updateOrCreate(
            ['account_id' => $account->id, 'plan' => $data['plan']],
            [
                ...$planValues,
                'payment_method'       => 'admin',
                'status'               => 'approved',
                'admin_status'         => 'approved',
                'notes'                => 'Configured directly by admin via Setup for Candidate.',
                'approved_at'          => now(),
            ]
        );

        AutoApplyQuota::syncForAccount($account->id);

        $order = AutoApplyOrder::query()
            ->where('account_id', $account->id)
            ->where('plan', $data['plan'])
            ->with('account')
            ->latest('id')
            ->first();

        if ($order) {
            $inviteError = $this->sendCandidateInvite($order, $generatedPassword);

            if ($inviteError !== null) {
                return $response
                    ->setError()
                    ->setNextUrl(route('auto-apply-orders.index'))
                    ->setMessage('Auto Apply was configured, but the invite failed: ' . $inviteError);
            }
        }

        return $response
            ->setNextUrl(route('auto-apply-orders.index'))
            ->setMessage('Auto Apply preference configured for candidate.');
    }

    public function resendInvite(AutoApplyOrder $autoApplyOrder, BaseHttpResponse $response)
    {
        $order = $autoApplyOrder->fresh(['account']);

        if (! $order?->account) {
            return $response
                ->setError()
                ->setMessage('Candidate account not found for this order.');
        }

        $errorMessage = $this->sendCandidateInvite($order);

        if ($errorMessage !== null) {
            return $response
                ->setError()
                ->setNextUrl(route('auto-apply-orders.index'))
                ->setMessage($errorMessage);
        }

        return $response
            ->setNextUrl(route('auto-apply-orders.index'))
            ->setMessage('Auto Apply invite sent to the candidate on WhatsApp.');
    }

    /**
     * Save an admin-uploaded CV file as the candidate's own account CV, so it shows up
     * for them when they log in and the "Missing CV" badge clears.
     */
    private function persistCandidateCv(Account $account, UploadedFile $file): void
    {
        $result = RvMedia::handleUpload($file, 0, $account->upload_folder);

        if ($result['error']) {
            return;
        }

        if ($oldPath = $account->resume) {
            Storage::disk('public')->delete($oldPath);
        }

        $account->resume = $result['data']->url;

        try {
            $cvScoreResult = app(CvScoringService::class)->scoreFile(
                $file->getRealPath(),
                $file->getClientOriginalExtension()
            );

            if ($cvScoreResult) {
                $account->cv_score = $cvScoreResult['score'];
                $account->cv_score_data = $cvScoreResult;
            }
        } catch (\Throwable) {
            // Non-fatal
        }

        $account->profile_updated_at = now();
        $account->save();
    }

    private function orderValuesFromPlan(string $planKey): array
    {
        $plan = AutoApplyOrder::plan($planKey, includeDisabled: true);

        abort_unless($plan, 422);

        return [
            'duration_days'        => $plan['duration_days'],
            'applications_allowed' => $plan['applications_per_month'],
            'amount'               => $plan['price'],
            'currency'             => $plan['currency'],
        ];
    }

    private function keywordRegexPattern(string $keyword): string
    {
        $keyword = mb_strtolower(trim($keyword));
        $pattern = preg_quote($keyword, '/');

        if (preg_match('/[a-z]$/i', $keyword)) {
            $pattern .= 's?';
        }

        return '\\b' . $pattern . '\\b';
    }

    private function countryFlagEmoji(string $code): string
    {
        $code = strtoupper(trim($code));

        if (! preg_match('/^[A-Z]{2}$/', $code)) {
            return '';
        }

        $flag = '';

        foreach (str_split($code) as $char) {
            $flag .= mb_chr(127397 + ord($char), 'UTF-8');
        }

        return $flag;
    }

    private function sendConfirmationEmail(AutoApplyOrder $order): void
    {
        $account = $order->account;
        if (! $account?->email) {
            return;
        }

        $plan = AutoApplyOrder::plan($order->plan, includeDisabled: true)
            ?? ['label' => $order->duration_days . ' days'];

        try {
            Mail::raw(
                "Hi {$account->first_name},\n\n" .
                "Your Wakanda Jobs Auto Apply subscription is now active!\n\n" .
                "Plan: {$plan['label']}\n" .
                "Usage limit: {$order->applicationsLabel()}\n" .
                "Expires: " . ($order->expiresAt()?->toFormattedDateString() ?? 'N/A') . "\n\n" .
                "The system will now automatically apply to matching jobs on your behalf using your CV and AI-crafted cover emails.\n\n" .
                "You can manage your preferences and view sent applications in your account dashboard.\n\n" .
                "Wakanda Jobs — wakandajobs.com",
                function ($msg) use ($account, $plan): void {
                    $msg->to($account->email, "{$account->first_name} {$account->last_name}")
                        ->subject("Your Auto Apply is Active — {$plan['label']}");
                }
            );
        } catch (\Throwable) {
        }
    }

    private function sendCandidateInvite(AutoApplyOrder $order, ?string $plainPassword = null): ?string
    {
        $account = $order->account;

        if (! $account) {
            return 'Candidate account not found for this order.';
        }

        $phone = $this->resolveWhatsAppNumber($account);

        if ($phone === '') {
            return 'Candidate has no WhatsApp number on file.';
        }

        $plan = AutoApplyOrder::plan($order->plan, includeDisabled: true)
            ?? ['label' => $order->planLabel()];

        $preference = AutoApplyPreference::query()->where('account_id', $account->id)->first();
        $categories = collect($preference?->category_ids ?? [])
            ->filter()
            ->whenNotEmpty(fn ($query) => Category::query()->whereIn('id', $query->all())->pluck('name'))
            ->implode(', ');
        $countries = collect($preference?->country_ids ?? [])
            ->filter()
            ->whenNotEmpty(fn ($query) => DB::table('countries')->whereIn('id', $query->all())->pluck('name'))
            ->implode(', ');
        $keywords = collect($preference?->keywords ?? [])->filter()->implode(', ');

        $lines = [
            'Hi ' . ($account->first_name ?: $account->name ?: 'there') . ',',
            "You've been successfully added to the Wakanda Jobs Auto Apply programme.",
            'Your subscription is now active.',
            '',
            '*Subscription details*',
            'Plan: ' . ($plan['label'] ?? $order->planLabel()),
            'Usage limit: ' . $order->applicationsLabel(),
            'Expires: ' . ($order->expiresAt()?->toFormattedDateString() ?? 'N/A'),
            '',
            '*What we have captured so far*',
            'CV on file: ' . ($account->resume ? 'Yes' : 'No'),
            'Match threshold: ' . (($preference?->match_score_threshold ?? AutoApplyOrder::globalMatchThreshold())) . '%',
        ];

        if ($keywords !== '') {
            $lines[] = 'Keywords: ' . $keywords;
        }

        if ($categories !== '') {
            $lines[] = 'Categories: ' . $categories;
        }

        if ($countries !== '') {
            $lines[] = 'Countries: ' . $countries;
        }

        if ($preference?->location_keyword) {
            $lines[] = 'Preferred location: ' . $preference->location_keyword;
        }

        $lines[] = '';
        $lines[] = 'Auto Apply will now submit matching jobs on your behalf using your CV and AI-written application emails.';
        $lines[] = 'If you want anything changed, just reply to this message.';

        $message = implode("\n", $lines);
        $imagePath = $this->settingImageLocalPath('auto_cv_bot_persona_image');
        $errorMessage = null;
        $sender = app(WhapiSenderService::class);

        $sent = $imagePath
            ? $sender->sendImage($phone, $imagePath, $message, $errorMessage)
            : $sender->sendText($phone, $message, $errorMessage);

        if (! $sent) {
            return $errorMessage ?: 'Could not send the Auto Apply invite.';
        }

        $loginUrl = route('public.account.login');
        $dashboardUrl = route('public.account.dashboard');
        $credentialsMessage = "Your Wakanda Jobs account is ready.\n\n"
            . '*Login email:* ' . $account->email
            . ($plainPassword ? "\n*Temporary password:* {$plainPassword}" : '')
            . "\n\nUse your email address to sign in.";
        $linksMessage = "Login: {$loginUrl}\n\nDashboard: {$dashboardUrl}";

        if (! $sender->sendText($phone, $credentialsMessage, $errorMessage)) {
            return $errorMessage ?: 'The invite image was sent, but the account details message failed.';
        }

        if (! $sender->sendText($phone, $linksMessage, $errorMessage)) {
            return $errorMessage ?: 'The invite image was sent, but the login links message failed.';
        }

        $this->sendCandidateInviteEmail($order, $plainPassword);

        return null;
    }

    private function sendCandidateInviteEmail(AutoApplyOrder $order, ?string $plainPassword = null): void
    {
        $account = $order->account;

        if (! $account?->email) {
            return;
        }

        $plan = AutoApplyOrder::plan($order->plan, includeDisabled: true)
            ?? ['label' => $order->planLabel()];
        $loginUrl = route('public.account.login');
        $dashboardUrl = route('public.account.dashboard');

        $body = "Hi {$account->first_name},\n\n"
            . "Welcome to Wakanda Jobs Auto Apply.\n\n"
            . "Your subscription is now active.\n\n"
            . "Plan: {$plan['label']}\n"
            . "Usage limit: {$order->applicationsLabel()}\n"
            . "Expires: " . ($order->expiresAt()?->toFormattedDateString() ?? 'N/A') . "\n\n"
            . "Your Wakanda Jobs account details:\n"
            . "Email: {$account->email}\n"
            . ($plainPassword ? "Temporary password: {$plainPassword}\n" : '')
            . "\nLogin here:\n{$loginUrl}\n\n"
            . "Your dashboard:\n{$dashboardUrl}\n\n"
            . "Wakanda Jobs — wakandajobs.com";

        try {
            Mail::raw($body, function ($msg) use ($account, $plan): void {
                $msg->to($account->email, "{$account->first_name} {$account->last_name}")
                    ->subject("Your Wakanda Jobs Account & Auto Apply Are Ready — {$plan['label']}");
            });
        } catch (\Throwable) {
        }
    }

    private function createCandidateAccountFromSetup(array $data): array
    {
        $firstName = trim((string) ($data['candidate_first_name'] ?? ''));
        $lastName = trim((string) ($data['candidate_last_name'] ?? ''));
        $email = strtolower(trim((string) ($data['candidate_email'] ?? '')));
        $phone = trim((string) ($data['candidate_phone'] ?? ''));
        $whatsapp = trim((string) ($data['candidate_whatsapp_number'] ?? ''));

        if ($firstName === '' || $lastName === '') {
            return [null, null, 'Please confirm both first name and last name before creating the candidate account.'];
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [null, null, 'Please confirm a valid candidate email before creating the account.'];
        }

        if ($phone === '' && $whatsapp === '') {
            return [null, null, 'Please confirm at least one phone or WhatsApp number before creating the account.'];
        }

        if (Account::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return [null, null, 'An account with this email already exists. Search and select that candidate instead.'];
        }

        $plainPassword = Str::random(10);

        $account = Account::query()->forceCreate([
            'type' => AccountTypeEnum::JOB_SEEKER,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone ?: $whatsapp,
            'whatsapp_number' => $whatsapp ?: $phone,
            'password' => Hash::make($plainPassword),
            'is_public_profile' => false,
            'confirmed_at' => now(),
            'profile_updated_at' => now(),
        ]);

        return [$account, $plainPassword, null];
    }

    private function resolveWhatsAppNumber(Account $account): string
    {
        $direct = trim((string) ($account->whatsapp_number ?: $account->phone));

        if ($direct !== '') {
            return $direct;
        }

        $fullName = trim((string) $account->name);

        if ($fullName !== '') {
            $sessionNumber = trim((string) AutoCvSession::query()
                ->where('candidate_name', $fullName)
                ->whereNotNull('whatsapp_number')
                ->latest('id')
                ->value('whatsapp_number'));

            if ($sessionNumber !== '') {
                return $sessionNumber;
            }

            $alertNumber = trim((string) CandidateAlert::query()
                ->where(function ($query) use ($account, $fullName): void {
                    $query->where('account_id', $account->id)
                        ->orWhere('candidate_name', $fullName);
                })
                ->whereNotNull('candidate_phone')
                ->latest('id')
                ->value('candidate_phone'));

            if ($alertNumber !== '') {
                return $alertNumber;
            }
        }

        return '';
    }

    private function settingImageLocalPath(string $settingKey): ?string
    {
        $url = trim((string) setting($settingKey, ''));

        if ($url === '') {
            return null;
        }

        if (! RvMedia::isUsingCloud()) {
            $path = RvMedia::getRealPath($url);

            return is_file($path) ? $path : null;
        }

        $contents = @file_get_contents(RvMedia::getImageUrl($url));

        if ($contents === false) {
            return null;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'auto_apply_img_') . '.' . (pathinfo($url, PATHINFO_EXTENSION) ?: 'jpg');
        file_put_contents($tempPath, $contents);

        return $tempPath;
    }
}
