<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Facades\AdminHelper;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Helper;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Events\JobAppliedEvent;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Http\Requests\ApplyJobRequest;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AccountEducation;
use Botble\JobBoard\Models\AccountExperience;
use Botble\JobBoard\Models\Analytics;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\Currency;
use Botble\JobBoard\Models\Job as JobModel;
use Botble\JobBoard\Models\JobApplication;
use Botble\JobBoard\Models\JobExperience;
use Botble\JobBoard\Models\JobSkill;
use Botble\JobBoard\Models\JobType;
use Botble\JobBoard\Models\Tag;
use Botble\JobBoard\Repositories\Interfaces\JobInterface;
use Botble\Language\Facades\Language;
use Botble\Location\Facades\Location;
use Botble\Media\Facades\RvMedia;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\SeoHelper\SeoOpenGraph;
use Botble\Slug\Facades\SlugHelper;
use Botble\Theme\Facades\Theme;
use Exception;
use GeoIp2\Database\Reader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PublicController extends BaseController
{
    public function __construct(
        protected JobInterface $jobRepository,
    ) {
    }

    public function getJob(string $slug)
    {
        $slug = SlugHelper::getSlug($slug, SlugHelper::getPrefix(JobModel::class));

        abort_unless($slug, 404);

        $condition = ['jb_jobs.id' => $slug->reference_id];

        if (AdminHelper::isPreviewing()) {
            Arr::forget($condition, 'status');
            Arr::forget($condition, 'moderation_status');
        }

        $job = $this->jobRepository->getJobs([], [
            'condition' => $condition,
            'take' => 1,
            'with' => [],
        ]);

        if (! $job) {
            $expiredJob = JobModel::query()
                ->where('id', $slug->reference_id)
                ->first();

            if ($expiredJob && $expiredJob->is_expired) {
                return $this->showExpiredJob($expiredJob, $slug);
            }

            abort(404);
        }

        $job->setRelation('slugable', $slug);

        SeoHelper::setTitle($job->name)->setDescription($job->description);

        $meta = new SeoOpenGraph();
        $meta->setDescription($job->description);
        $meta->setUrl($job->url);
        $meta->setTitle($job->name);
        $meta->setType('article');

        $companyJobs = collect();

        $company = $job->company;

        if ($company && $company->id) {
            $company->loadCount('jobs');

            if (! $job->hide_company) {
                if ($company->logo) {
                    $meta->setImage(RvMedia::getImageUrl($company->logo));
                }

                $condition = [
                    ['jb_jobs.company_id', '=', $company->id],
                    ['jb_jobs.id', '!=', $job->id],
                    ['jb_jobs.hide_company', '=', false],
                ];

                $companyJobs = $this->jobRepository->getJobs(
                    [],
                    [
                        'condition' => $condition,
                        'take' => 5,
                        'order_by' => [
                            'jb_jobs.created_at' => 'desc',
                        ],
                    ],
                );
            }
        }

        SeoHelper::setSeoOpenGraph($meta);

        Theme::breadcrumb()
            ->add(trans('plugins/job-board::messages.jobs'), JobBoardHelper::getJobsPageURL())
            ->add($job->name, $job->url);

        if (function_exists('admin_bar')) {
            admin_bar()->registerLink(trans('plugins/job-board::messages.edit_this_job'), route('jobs.edit', $job->id), 'jobs.edit');
        }

        do_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, JOB_MODULE_SCREEN_NAME, $job);

        if (
            JobBoardHelper::shouldNoIndexInactiveJobs()
            && ($job->is_expired || $job->status == JobStatusEnum::CLOSED)
        ) {
            SeoHelper::meta()->addMeta('robots', 'noindex, follow');
        }

        $viewed = Helper::handleViewCount($job, 'viewed_job');

        if ($viewed) {
            $ip = Helper::getIpFromThirdParty();

            $countries = $this->getCountries($ip);

            Analytics::query()->create([
                'job_id' => $job->id,
                'country' => Arr::get($countries, 'countryCode'),
                'country_full' => Arr::get($countries, 'countryName'),
                'referer' => Str::limit(request()->server('HTTP_REFERER') ?? null, 250),
                'ip_address' => Str::limit($ip, 39),
                'ip_hashed' => 0,
            ]);
        }

        $job->loadMissing('customFields');

        return Theme::scope(
            'job-board.job',
            compact('job', 'companyJobs', 'company'),
            'plugins/job-board::themes.job'
        )->render();
    }

    public function getJobs(Request $request)
    {
        $requestQuery = JobBoardHelper::getJobFilters($request->input());

        if (! empty($requestQuery['keyword'])) {
            SeoHelper::setTitle(trans('plugins/job-board::messages.search_results_for', ['keyword' => $requestQuery['keyword']]));
        }

        if (! empty($requestQuery['job_categories'])) {
            $categories = Category::query()
                ->whereIn('id', $requestQuery['job_categories'])
                ->select('id', 'name')
                ->get()
                ->map(fn ($category) => $category->name)
                ->implode(', ');

            if ($categories) {
                if (! empty($requestQuery['keyword'])) {
                    SeoHelper::setTitle(trans('plugins/job-board::messages.search_results_in_categories', [
                        'keyword' => $requestQuery['keyword'],
                        'categories' => $categories,
                    ]));
                } else {
                    SeoHelper::setTitle(trans('plugins/job-board::messages.jobs_in_categories', [
                        'keyword' => $requestQuery['keyword'],
                        'categories' => $categories,
                    ]));
                }
            }
        }

        $with = [
            'tags.slugable',
            'jobTypes',
            'slugable',
            'jobExperience',
            'company',
            'company.metadata',
            'company.slugable',
        ];

        $sortBy = match ($request->input('sort_by') ?: 'newest') {
            'oldest' => [
                'jb_jobs.created_at' => 'ASC',
            ],
            default => [
                'jb_jobs.created_at' => 'DESC',
            ],
        };

        if (JobBoardHelper::isPinFeaturedJobsInTheTop()) {
            $sortBy = ['jb_jobs.is_featured' => 'DESC', ...$sortBy];
        }

        if (is_plugin_active('location')) {
            $with = array_merge($with, array_keys(Location::getSupported(JobModel::class)));
        }

        $jobs = app(JobInterface::class)->getJobs(
            $requestQuery,
            [
                'with' => $with,
                'order_by' => $sortBy,
                'paginate' => [
                    'per_page' => $requestQuery['per_page'] ?? Arr::first(JobBoardHelper::getPerPageParams()),
                    'current_paged' => $requestQuery['page'] ?? 1,
                ],
            ],
        );

        $additional['total'] = $jobs->total();

        if ($additional['total']) {
            $message = trans('plugins/job-board::messages.showing_results', [
                'from' => number_format($jobs->firstItem()),
                'to' => number_format($jobs->lastItem()),
                'total' => number_format($jobs->total()),
            ]);
        } else {
            $message = trans('plugins/job-board::messages.no_results_found');
        }

        $additional['message'] = $message;

        $jobsView = Theme::getThemeNamespace('views.job-board.partials.job-items');

        if (! view()->exists($jobsView)) {
            $jobsView = 'plugins/job-board::themes.partials.job-items';
        }

        $filtersData['jobs'] = $jobs;
        if ($requestQuery['city_id']) {
            $filtersData['stateId'] = $requestQuery['city_id'];
        }
        if ($requestQuery['state_id']) {
            $filtersData['stateId'] = $requestQuery['state_id'];
        }

        $filtersView = Theme::getThemeNamespace('views.job-board.partials.filters');

        if (view()->exists($filtersView)) {
            $additional['filters_html'] = view(
                $filtersView,
                $filtersData
            )->render();
        }

        return $this
            ->httpResponse()
            ->setData(view($jobsView, compact('jobs'))->render())
            ->setAdditional($additional)
            ->setMessage($message);
    }

    public function getCompanies(Request $request)
    {
        $requestQuery = JobBoardHelper::getCompanyFilterParams($request->input());

        $companies = Company::query()
            ->withCount([
                'activeJobs as jobs_count',
                'reviews',
            ])
            ->withAvg('reviews', 'star')
            ->with(['slugable'])
            ->pinFeatured();

        if ($requestQuery['keyword']) {
            if (
                is_plugin_active('language') &&
                is_plugin_active('language-advanced') &&
                Language::getCurrentLocale() != Language::getDefaultLocale()
            ) {
                $companies = $companies->where(function (Builder $query) use ($requestQuery): void {
                    $query->where('name', 'LIKE', $requestQuery['keyword'] . '%')
                        ->orWhereHas('translations', function (Builder $query) use ($requestQuery): void {
                            $query->where('name', 'LIKE', $requestQuery['keyword'] . '%');
                        });
                });
            } else {
                $companies = $companies->where('name', 'LIKE', $requestQuery['keyword'] . '%');
            }
        }

        match ($requestQuery['sort_by'] ?? 'oldest') {
            'newest' => $companies = $companies->latest(),
            default => $companies = $companies->oldest(),
        };

        $companies = $companies->paginate($requestQuery['per_page'] ?: 12);

        $total = $companies->total();

        if ($total) {
            $message = trans('plugins/job-board::messages.showing_results', [
                'from' => number_format($companies->firstItem()),
                'to' => number_format($companies->lastItem()),
                'total' => number_format($companies->total()),
            ]);
        } else {
            $message = trans('plugins/job-board::messages.no_results_found');
        }

        $view = Theme::getThemeNamespace('views.job-board.partials.companies');

        if (! view()->exists($view)) {
            $view = 'plugins/job-board::themes.partials.companies';
        }

        return $this
            ->httpResponse()
            ->setData(view($view, compact('companies'))->render())
            ->setAdditional([
                'total' => $total,
                'message' => $message,
            ])
            ->setMessage($message);
    }

    public function postApplyJob(ApplyJobRequest $request, ?int $id = null)
    {
        if (! auth('account')->check() && ! JobBoardHelper::isGuestApplyEnabled()) {
            throw new HttpException(422, trans('plugins/job-board::messages.please_login_to_apply'));
        }

        try {
            if (! $id) {
                $id = $request->input('job_id');
            }

            if (! $id) {
                return $this
                    ->httpResponse()
                    ->setError()
                    ->setCode(404);
            }

            $request->merge(['account_id' => null]);

            $job = $this->jobRepository->getJobs([], [
                'condition' => ['jb_jobs.id' => $id],
                'take' => 1,
                'with' => ['author'],
            ]);

            if (! $job) {
                return $this
                    ->httpResponse()
                    ->setError()
                    ->setCode(404);
            }

            if (! $job->isJobOpen()) {
                return $this
                    ->httpResponse()
                    ->setError()
                    ->setMessage(trans('plugins/job-board::messages.job_closed'))
                    ->setCode(404);
            }

            $jobType = $request->input('job_type');

            if (($job->apply_url && $jobType !== 'external') ||
                (! $job->apply_url && $jobType !== 'internal')
            ) {
                return $this
                    ->httpResponse()->setError()->setMessage(trans('plugins/job-board::messages.job_not_available'));
            }

            $account = null;

            if (auth('account')->check()) {
                /**
                 * @var Account $account
                 */
                $account = auth('account')->user();

                if ($account->isEmployer()) {
                    return $this
                        ->httpResponse()
                        ->setError()
                        ->setMessage(trans('plugins/job-board::messages.employers_cannot_apply'));
                }

                $request->merge(['account_id' => $account->getKey()]);

                if ($job->is_applied) {
                    return $this
                        ->httpResponse()
                        ->setError()
                        ->setMessage(
                            trans('plugins/job-board::messages.already_applied')
                        );
                }
            }

            $jobApplication = new JobApplication();

            $request->merge(['job_id' => $job->id]);

            if (! $job->apply_url) {
                if ($request->hasFile('resume')) {
                    $result = RvMedia::handleUpload($request->file('resume'), 0, 'job-applications');

                    if (! $result['error']) {
                        $file = $result['data'];
                        $request->merge(['resume' => $file->url]);
                    } else {
                        $request->merge(['resume' => null]);
                    }
                } elseif ($account && $resume = $account->resume) {
                    $request->merge(['resume' => $resume]);
                }

                if ($request->hasFile('cover_letter')) {
                    $result = RvMedia::handleUpload($request->file('cover_letter'), 0, 'job-applications');

                    if (! $result['error']) {
                        $file = $result['data'];
                        $request->merge(['cover_letter' => $file->url]);
                    } else {
                        $request->merge(['cover_letter' => null]);
                    }
                } elseif ($account && $coverLetter = $account->cover_letter) {
                    $request->merge(['cover_letter' => $coverLetter]);
                }
            } else {
                $request->merge(['resume' => null, 'cover_letter' => null]);
                $jobApplication->is_external_apply = true;
            }

            $jobApplication->fill($request->input());
            $jobApplication->save();

            $job::withoutEvents(fn () => $job::withoutTimestamps(fn () => $job->increment('number_of_applied')));

            if (! $job->apply_url) {
                $jobApplication->setRelation('job', $job);

                if ($account) {
                    $jobApplication->setRelation('account', $account);
                }

                JobAppliedEvent::dispatch($jobApplication, $job);
            }

            if (! $request->ajax()) {
                return redirect()->to($job->apply_url);
            }

            $message = $job->apply_url
                ? trans('plugins/job-board::job-application.email.external_redirect')
                : trans('plugins/job-board::job-application.email.success');

            return $this
                ->httpResponse()
                ->setData(['url' => $job->apply_url])
                ->setMessage($message);
        } catch (Exception) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage(trans('plugins/job-board::job-application.email.failed'));
        }
    }

    public function getJobCategory(
        string $slug,
        Request $request
    ) {
        $slug = SlugHelper::getSlug($slug, SlugHelper::getPrefix(Category::class));

        abort_unless($slug, 404);

        $condition = [
            'id' => $slug->reference_id,
            'status' => BaseStatusEnum::PUBLISHED,
        ];

        if (AdminHelper::isPreviewing()) {
            Arr::forget($condition, 'status');
            Arr::forget($condition, 'moderation_status');
        }

        $category = Category::query()
            ->where($condition)
            ->with('activeChildren')
            ->firstOrFail();

        SeoHelper::setTitle($category->name)->setDescription($category->description);

        $meta = new SeoOpenGraph();
        $meta->setDescription($category->description);
        $meta->setUrl($category->url);
        $meta->setTitle($category->name);
        $meta->setType('article');

        SeoHelper::setSeoOpenGraph($meta);

        Theme::breadcrumb()
            ->add(trans('plugins/job-board::messages.categories'), JobBoardHelper::getJobCategoriesPageURL())
            ->add($category->name, $category->url);

        if (function_exists('admin_bar')) {
            admin_bar()->registerLink(
                trans('plugins/job-board::messages.edit_job_category'),
                route('job-categories.edit', $category->id),
                'job-categories.edit'
            );
        }

        $requestQuery = JobBoardHelper::getJobFilters($request->input());

        // Get all category IDs including child categories
        $categoryIds = $category->getAllCategoryIds();

        $with = [
            'tags.slugable',
            'jobTypes',
            'slugable',
            'jobExperience',
            'company',
            'company.metadata',
            'company.slugable',
        ];

        $sortBy = match ($request->input('sort_by') ?: 'newest') {
            'oldest' => [
                'jb_jobs.created_at' => 'ASC',
            ],
            default => [
                'jb_jobs.created_at' => 'DESC',
            ],
        };

        if (JobBoardHelper::isPinFeaturedJobsInTheTop()) {
            $sortBy = ['jb_jobs.is_featured' => 'DESC', ...$sortBy];
        }

        if (is_plugin_active('location')) {
            $with = array_merge($with, array_keys(Location::getSupported(JobModel::class)));
        }

        $jobs = $this->jobRepository->getJobs(
            array_merge($requestQuery, [
                'job_categories' => $categoryIds,
            ]),
            [
                'with' => $with,
                'order_by' => $sortBy,
                'paginate' => [
                    'per_page' => $requestQuery['per_page'] ?? Arr::first(JobBoardHelper::getPerPageParams()),
                    'current_paged' => $requestQuery['page'] ?? 1,
                ],
            ]
        );

        // Handle AJAX request
        if ($request->ajax()) {
            $additional['total'] = $jobs->total();

            if ($additional['total']) {
                $message = trans('plugins/job-board::messages.showing_results', [
                    'from' => number_format($jobs->firstItem()),
                    'to' => number_format($jobs->lastItem()),
                    'total' => number_format($jobs->total()),
                ]);
            } else {
                $message = trans('plugins/job-board::messages.no_results_found');
            }

            $additional['message'] = $message;

            $jobsView = Theme::getThemeNamespace('views.job-board.partials.job-items');

            if (! view()->exists($jobsView)) {
                $jobsView = 'plugins/job-board::themes.partials.job-items';
            }

            $filtersData['jobs'] = $jobs;
            if ($requestQuery['city_id']) {
                $filtersData['stateId'] = $requestQuery['city_id'];
            }
            if ($requestQuery['state_id']) {
                $filtersData['stateId'] = $requestQuery['state_id'];
            }

            $filtersView = Theme::getThemeNamespace('views.job-board.partials.filters');

            if (view()->exists($filtersView)) {
                $additional['filters_html'] = view(
                    $filtersView,
                    $filtersData
                )->render();
            }

            return $this
                ->httpResponse()
                ->setData(view($jobsView, compact('jobs'))->render())
                ->setAdditional($additional)
                ->setMessage($message);
        }

        $data = $this->getJobFilterData();

        $data['category'] = $category;
        $data['jobs'] = $jobs;

        do_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, JOB_CATEGORY_MODULE_SCREEN_NAME, $category);

        return Theme::scope('job-board.job-category', $data, 'plugins/job-board::themes.job-category')->render();
    }

    protected function getJobFilterData(): array
    {
        return Cache::remember('job_filter_data', 3600, function () {
            $jobCategories = Category::query()
                ->where('status', BaseStatusEnum::PUBLISHED)
                ->with(['activeChildren.activeChildren.activeChildren'])
                ->get();

            Category::addJobsCountWithChildren($jobCategories);

            $jobTypes = JobType::query()
                ->where('status', BaseStatusEnum::PUBLISHED)
                ->withCount([
                    'jobs' => function ($query): void {
                        $query
                            ->where('jb_jobs.status', JobStatusEnum::PUBLISHED)
                            ->notExpired();
                    },
                ])
                ->get();

            $jobExperiences = JobExperience::query()
                ->where('status', BaseStatusEnum::PUBLISHED)
                ->withCount([
                    'jobs' => function ($query): void {
                        $query
                            ->where('jb_jobs.status', JobStatusEnum::PUBLISHED)
                            ->notExpired();
                    },
                ])
                ->get();

            $jobSkills = JobSkill::query()
                ->where('status', BaseStatusEnum::PUBLISHED)
                ->withCount([
                    'jobs' => function ($query): void {
                        $query
                            ->where('jb_jobs.status', JobStatusEnum::PUBLISHED)
                            ->notExpired();
                    },
                ])
                ->get();

            $jobFeaturedCategories = $jobCategories->where('is_featured');

            return compact(
                'jobCategories',
                'jobTypes',
                'jobExperiences',
                'jobFeaturedCategories',
                'jobSkills'
            );
        });
    }

    public function getJobTag(string $slug, Request $request)
    {
        $slug = SlugHelper::getSlug($slug, SlugHelper::getPrefix(Tag::class));

        abort_unless($slug, 404);

        $condition = [
            'id' => $slug->reference_id,
            'status' => BaseStatusEnum::PUBLISHED,
        ];

        if (AdminHelper::isPreviewing()) {
            Arr::forget($condition, 'status');
            Arr::forget($condition, 'moderation_status');
        }

        $tag = Tag::query()
            ->where($condition)
            ->firstOrFail();

        SeoHelper::setTitle($tag->name)->setDescription($tag->description);

        $meta = new SeoOpenGraph();
        $meta->setDescription($tag->description);
        $meta->setUrl($tag->url);
        $meta->setTitle($tag->name);
        $meta->setType('article');

        SeoHelper::setSeoOpenGraph($meta);

        Theme::breadcrumb()
            ->add($tag->name, $tag->url);

        if (function_exists('admin_bar')) {
            admin_bar()->registerLink(
                trans('plugins/job-board::messages.edit_job_tag'),
                route('job-board.tag.edit', $tag->id),
                'job-board.tag.edit'
            );
        }

        $requestQuery = JobBoardHelper::getJobFilters($request->input());

        $jobs = $this->jobRepository->getJobs(
            array_merge($requestQuery, [
                'tags' => [$tag->getKey()],
                'job_tags' => [$tag->getKey()],
            ]),
            [
                'paginate' => [
                    'per_page' => isset($requestQuery['per_page']) ? (int) $requestQuery['per_page'] : 20,
                    'current_paged' => isset($requestQuery['page']) ? (int) $requestQuery['page'] : 1,
                ],
            ]
        );

        $data = $this->getJobFilterData();

        $data['tag'] = $tag;
        $data['jobs'] = $jobs;

        do_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, JOB_BOARD_TAG_MODULE_SCREEN_NAME, $tag);

        return Theme::scope('job-board.job-tag', $data, 'plugins/job-board::themes.job-tag')->render();
    }

    protected function getCountries(string $ip): array
    {
        // We try to get the IP country using (or not) the anonymized IP
        // If it fails, because GeoLite2 doesn't know the IP country, we
        // will set it to Unknown
        try {
            $reader = new Reader(__DIR__ . '/../../../database/GeoLite2-Country.mmdb');
            $record = $reader->country($ip);
            $countryCode = $record->country->isoCode;
            $countryName = $record->country->name;
        } catch (Exception) {
            $countryCode = 'N/A';
            $countryName = 'Unknown';
        }

        return compact('countryCode', 'countryName');
    }

    public function getCompany(string $slug)
    {
        $slug = SlugHelper::getSlug($slug, SlugHelper::getPrefix(Company::class));

        abort_unless($slug, 404);

        $condition = [
            'id' => $slug->reference_id,
            'status' => BaseStatusEnum::PUBLISHED,
        ];

        if (AdminHelper::isPreviewing()) {
            Arr::forget($condition, 'status');
        }

        /**
         * @var Company $company
         */
        $company = Company::query()
            ->where($condition)
            ->withCount([
                'jobs' => function (Builder $query): void {
                    // @phpstan-ignore-next-line
                    $query
                        ->active()
                        ->where(['jb_jobs.hide_company' => false]);
                },
                'reviews',
            ])
            ->withAvg('reviews', 'star')
            ->firstOrFail();

        $company->setRelation('slugable', $slug);

        $params = [
            'condition' => [
                'jb_jobs.company_id' => $company->getKey(),
                'jb_jobs.hide_company' => false,
            ],
            'order_by' => ['created_at' => 'DESC'],
            'paginate' => [
                'per_page' => 3,
                'current_paged' => request()->integer('page') ?: 1,
            ],
        ];

        $jobs = $this->jobRepository->getJobs([], $params);

        if (request()->ajax()) {
            $view = Theme::getThemeNamespace('views.job-board.partials.company-job-items');

            if (! view()->exists($view)) {
                $view = 'plugins/job-board::themes.partials.job-items';
            }

            return $this
                ->httpResponse()->setData(view($view, compact('jobs', 'company'))->render());
        }

        if (function_exists('admin_bar')) {
            admin_bar()->registerLink(trans('plugins/job-board::messages.edit_this_company'), route('companies.edit', $company->getKey()), 'companies.edit');
        }

        SeoHelper::setTitle($company->name)->setDescription($company->description);

        $meta = new SeoOpenGraph();
        if ($company->logo) {
            $meta->setImage(RvMedia::getImageUrl($company->logo));
        }
        $meta->setDescription($company->description);
        $meta->setUrl($company->url);
        $meta->setTitle($company->name);
        $meta->setType('article');

        SeoHelper::setSeoOpenGraph($meta);

        Helper::handleViewCount($company, 'viewed_company');

        Theme::breadcrumb()
            ->add(trans('plugins/job-board::messages.companies'), JobBoardHelper::getJobCompaniesPageURL())
            ->add($company->name, $company->url);

        do_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, COMPANY_MODULE_SCREEN_NAME, $company);

        if (JobBoardHelper::isEnabledReview()) {
            $company->setRelation('reviews', $company->reviews()->with('createdBy')->paginate(10));

            /** @var Account $account */
            $account = Auth::guard('account')->user();

            $canReview = $account
                && ! $account->isEmployer()
                && $account->canReview($company);
        } else {
            $canReview = false;
        }

        $canReviewCompany = $canReview;

        return Theme::scope(
            'job-board.company',
            compact('company', 'jobs', 'canReview', 'canReviewCompany'),
            'plugins/job-board::themes.company'
        )->render();
    }

    public function getCandidate(string $slug)
    {
        abort_if(JobBoardHelper::isDisabledPublicProfile(), 404);

        $slug = SlugHelper::getSlug($slug, SlugHelper::getPrefix(Account::class));

        abort_unless($slug, 404);

        $condition = [
            ['id', '=', $slug->reference_id],
            ['is_public_profile', '=', 1],
            ['type', '=', AccountTypeEnum::JOB_SEEKER],
        ];

        if (setting('verify_account_email', 0)) {
            $condition[] = ['confirmed_at', '!=', null];
        }

        /**
         * @var Account $candidate
         */
        $candidate = Account::query()
            ->where($condition)
            ->firstOrFail();

        $candidate->setRelation('slugable', $slug);

        SeoHelper::setTitle($candidate->name)->setDescription($candidate->description);

        $meta = new SeoOpenGraph();
        if ($candidate->avatar_url) {
            $meta->setImage(RvMedia::getImageUrl($candidate->avatar_url));
        }
        $meta->setDescription($candidate->description);
        $meta->setUrl($candidate->url);
        $meta->setTitle($candidate->name);
        $meta->setType('article');

        SeoHelper::setSeoOpenGraph($meta);

        Helper::handleViewCount($candidate, 'viewed_account');

        Theme::breadcrumb()
            ->add(trans('plugins/job-board::messages.candidates'), JobBoardHelper::getJobCandidatesPageURL())
            ->add($candidate->name, $candidate->url);

        do_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, ACCOUNT_MODULE_SCREEN_NAME, $candidate);

        $experiences = AccountExperience::where('account_id', $candidate->id)->get();
        $educations = AccountEducation::where('account_id', $candidate->id)->get();

        /**
         * @var Account $account
         */
        $account = Auth::guard('account')->user();

        if (JobBoardHelper::isEnabledReview()) {
            $candidate
                ->loadCount('reviews')
                ->loadAvg('reviews', 'star')
                ->setRelation('reviews', $candidate->reviews()->latest()->paginate(10));

            $canReview = $account
                && $account->isEmployer()
                && $account->canReview($candidate);
        } else {
            $canReview = false;
        }

        return Theme::scope(
            'job-board.candidate',
            compact('candidate', 'experiences', 'educations', 'account', 'canReview'),
            'plugins/job-board::themes.candidate'
        )->render();
    }

    public function getCandidates(Request $request)
    {
        abort_if(! $request->ajax() || JobBoardHelper::isDisabledPublicProfile(), 404);

        $candidates = JobBoardHelper::filterCandidates(request()->input());

        return $this
            ->httpResponse()
            ->setData([
                'list' => view(
                    Theme::getThemeNamespace('views.job-board.partials.candidate-list'),
                    compact('candidates')
                )->render(),
                'total_text' => trans('plugins/job-board::messages.showing_candidates', [
                    'from' => number_format($candidates->firstItem()),
                    'to' => number_format($candidates->lastItem()),
                    'total' => number_format($candidates->total()),
                ]),
            ]);
    }

    public function getJobFilters(Request $request)
    {
        $requestQuery = JobBoardHelper::getJobFilters($request->input());

        [$jobCategories, $jobTypes, $jobExperiences, $jobSkills, $maxSalaryRange, $jobTags] = JobBoardHelper::dataForFilter($requestQuery);

        return $this
            ->httpResponse()
            ->setData([
                'jobTypes' => $jobTypes->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'jobs_count' => $item->jobs_count ?? 0,
                    ];
                }),
                'jobExperiences' => $jobExperiences->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'jobs_count' => $item->jobs_count ?? 0,
                    ];
                }),
                'jobSkills' => $jobSkills->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'jobs_count' => $item->jobs_count ?? 0,
                    ];
                }),
                'maxSalaryRange' => $maxSalaryRange,
            ]);
    }

    public function changeCurrency(Request $request, ?string $title = null)
    {
        if (empty($title)) {
            $title = $request->input('currency');
        }

        if (! $title) {
            return $this->httpResponse();
        }

        /**
         * @var Currency $currency
         */
        $currency = Currency::query()
            ->where('title', $title)
            ->first();

        if ($currency) {
            cms_currency()->setApplicationCurrency($currency);
        }

        return $this->httpResponse();
    }

    protected function showExpiredJob(JobModel $job, $slug)
    {
        $job->setRelation('slugable', $slug);

        $job->load(['company', 'company.slugable']);

        SeoHelper::setTitle(trans('plugins/job-board::messages.position_closed', ['name' => $job->name]))
            ->setDescription(trans('plugins/job-board::messages.position_no_longer_available'))
            ->meta()
            ->addMeta('robots', 'noindex, follow');

        $meta = new SeoOpenGraph();
        $meta->setDescription(trans('plugins/job-board::messages.position_no_longer_available'));
        $meta->setUrl($job->url);
        $meta->setTitle(trans('plugins/job-board::messages.position_closed', ['name' => $job->name]));
        $meta->setType('article');

        SeoHelper::setSeoOpenGraph($meta);

        $jobsUrl = JobBoardHelper::getJobsPageURL();

        return Theme::scope(
            'job-board.job-expired',
            compact('job', 'jobsUrl'),
            'plugins/job-board::themes.job-expired'
        )->render();
    }
}
