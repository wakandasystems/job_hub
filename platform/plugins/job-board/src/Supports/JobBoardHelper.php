<?php

namespace Botble\JobBoard\Supports;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\MetaBox;
use Botble\Base\Forms\FieldOptions\CoreIconFieldOption;
use Botble\Base\Forms\Fields\CoreIconField;
use Botble\Base\Forms\FormAbstract;
use Botble\Base\Models\BaseQueryBuilder;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Enums\ModerationStatusEnum;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobExperience;
use Botble\JobBoard\Models\JobSkill;
use Botble\JobBoard\Models\JobType;
use Botble\JobBoard\Models\Tag;
use Botble\Page\Models\Page;
use Botble\Theme\Facades\Theme;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JobBoardHelper
{
    protected ?string $jobsPageURL = null;

    protected ?string $jobCategoriesPageURL = null;

    protected ?string $jobCandidatesPageURL = null;

    protected ?string $jobCompaniesPageURL = null;

    public function isGuestApplyEnabled(): bool
    {
        return setting('job_board_enable_guest_apply', 1) == 1;
    }

    public function isRegisterEnabled(): bool
    {
        return (bool) setting('job_board_enabled_register_account', true);
    }

    public function jobExpiredDays(): int
    {
        $days = (int) setting('job_expired_after_days');

        if ($days > 0) {
            return $days;
        }

        return 45;
    }

    public function isEnabledCreditsSystem(): bool
    {
        return setting('job_board_enable_credits_system', 1) == 1;
    }

    public function isEnabledJobApproval(): bool
    {
        return setting('job_board_enable_post_approval', 1) == 1;
    }

    public function getThousandSeparatorForInputMask(): string
    {
        return ',';
    }

    public function getDecimalSeparatorForInputMask(): string
    {
        return '.';
    }

    public function getJobDisplayQueryConditions(): array
    {
        return [
            'jb_jobs.moderation_status' => ModerationStatusEnum::APPROVED,
            'jb_jobs.status' => JobStatusEnum::PUBLISHED,
        ];
    }

    public function postedDateRanges(): array
    {
        return [
            'last_hour' => [
                'name' => trans('plugins/job-board::messages.last_hour'),
                'end' => Carbon::now()->subHour(),
            ],
            'last_24_hours' => [
                'name' => trans('plugins/job-board::messages.last_24_hours'),
                'end' => Carbon::now()->subDay(),
            ],
            'last_7_days' => [
                'name' => trans('plugins/job-board::messages.last_7_days'),
                'end' => Carbon::now()->subWeek(),
            ],
            'last_14_days' => [
                'name' => trans('plugins/job-board::messages.last_14_days'),
                'end' => Carbon::now()->subWeeks(2),
            ],
            'last_1_month' => [
                'name' => trans('plugins/job-board::messages.last_1_month'),
                'end' => Carbon::now()->subMonth(),
            ],
        ];
    }

    public function getAssetVersion(): string
    {
        return '1.2.0';
    }

    public function viewPath(string $view): string
    {
        $themeView = Theme::getThemeNamespace(Theme::getConfig('containerDir.view') . '.job-board.' . $view);

        if (view()->exists($themeView)) {
            return $themeView;
        }

        return 'plugins/job-board::themes.' . $view;
    }

    public function view(string $view, array $data = []): Factory|View|Application
    {
        return view($this->viewPath($view), $data);
    }

    public function scope(string $view, array $data = []): Response|string
    {
        $path = Theme::getThemeNamespace(Theme::getConfig('containerDir.view') . '.job-board.' . $view);

        if (view()->exists($path)) {
            return Theme::scope('job-board.' . $view, $data)->render();
        }

        return view('plugins/job-board::themes.' . $view, $data)->render();
    }

    protected function getPage(int|string|null $pageId): Model|Page|null
    {
        if (! $pageId) {
            return null;
        }

        return Page::query()
            ->wherePublished()
            ->where('id', $pageId)
            ->with('slugable')
            ->select(['id', 'name'])
            ->first();
    }

    public function getJobsPageURL(): ?string
    {
        if ($this->jobsPageURL) {
            return $this->jobsPageURL;
        }

        $page = $this->getPage(theme_option('job_list_page_id'));

        $this->jobsPageURL = $page?->url;

        return $this->jobsPageURL;
    }

    public function getJobCategoriesPageURL(): ?string
    {
        if ($this->jobCategoriesPageURL) {
            return $this->jobCategoriesPageURL;
        }

        $page = $this->getPage(theme_option('job_categories_page_id'));

        $this->jobCategoriesPageURL = $page?->url;

        return $this->jobCategoriesPageURL;
    }

    public function getJobCompaniesPageURL(): ?string
    {
        if ($this->jobCompaniesPageURL) {
            return $this->jobCompaniesPageURL;
        }

        $page = $this->getPage(theme_option('job_companies_page_id'));

        $this->jobCompaniesPageURL = $page?->url;

        return $this->jobCompaniesPageURL;
    }

    public function getJobCandidatesPageURL(): ?string
    {
        if ($this->isDisabledPublicProfile()) {
            return route('public.index');
        }

        if ($this->jobCandidatesPageURL) {
            return $this->jobCandidatesPageURL;
        }

        $page = $this->getPage(theme_option('job_candidates_page_id'));

        $this->jobCandidatesPageURL = $page?->url;

        return $this->jobCandidatesPageURL;
    }

    public function getJobFilters(array|Request $inputs): array
    {
        if ($inputs instanceof Request) {
            $inputs = $inputs->input();
        }

        $params = [
            'keyword' => BaseHelper::stringify(Arr::get($inputs, 'keyword')),
            'country_id' => (int) Arr::get($inputs, 'country_id'),
            'city_id' => (int) Arr::get($inputs, 'city_id'),
            'state_id' => (int) Arr::get($inputs, 'state_id'),
            'location' => BaseHelper::stringify(Arr::get($inputs, 'location')),
            'job_categories' => (array) Arr::get($inputs, 'job_categories', []),
            'job_tags' => (array) Arr::get($inputs, 'job_tags', []),
            'job_types' => (array) Arr::get($inputs, 'job_types', []),
            'job_experiences' => (array) Arr::get($inputs, 'job_experiences', []),
            'job_skills' => (array) Arr::get($inputs, 'job_skills', []),
            'offered_salary_from' => BaseHelper::stringify(Arr::get($inputs, 'offered_salary_from')),
            'offered_salary_to' => BaseHelper::stringify(Arr::get($inputs, 'offered_salary_to')),
            'date_posted' => BaseHelper::stringify(Arr::get($inputs, 'date_posted')),
            'page' => (int) Arr::get($inputs, 'page', 1),
            'per_page' => (int) Arr::get($inputs, 'per_page', 12),
        ];

        $validator = Validator::make($params, [
            'keyword' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string'],
            'country_id' => ['nullable', 'numeric'],
            'city_id' => ['nullable', 'numeric'],
            'state_id' => ['nullable', 'numeric'],
            'job_categories' => ['nullable', 'array'],
            'job_tags' => ['nullable', 'array'],
            'job_types' => ['nullable', 'array'],
            'job_experiences' => ['nullable', 'array'],
            'job_skills' => ['nullable', 'array'],
            'offered_salary_from' => ['nullable', 'numeric'],
            'offered_salary_to' => ['nullable', 'numeric'],
            'date_posted' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'gt:0'],
            'per_page' => ['nullable', 'integer', 'gt:0'],
        ]);

        return $validator->valid();
    }

    /**
     * @deprecated
     */
    public function jobFilterParamsValidated(array $params): bool
    {
        return true;
    }

    /**
     * @deprecated
     */
    public function companyFilterParamsValidated(array $params): bool
    {
        return true;
    }

    public function getCompanyFilterParams(array|Request $inputs): array
    {
        if ($inputs instanceof Request) {
            $inputs = $inputs->input();
        }

        $params = [
            'keyword' => BaseHelper::stringify(Arr::get($inputs, 'keyword')),
            'sort_by' => (int) Arr::get($inputs, 'sort_by', 'newest') ?: 'newest',
            'order_by' => (int) Arr::get($inputs, 'order_by', 'newest') ?: 'newest',
            'page' => (int) Arr::get($inputs, 'page', 1) ?: 1,
            'per_page' => (int) Arr::get($inputs, 'per_page', 12) ?: 12,
        ];

        $validator = Validator::make($params, [
            'per_page' => ['nullable', 'numeric'],
            'keyword' => ['nullable', 'string'],
            'sort_by' => ['nullable', 'string', 'in:newest,oldest'],
            'order_by' => ['nullable', 'string', 'in:newest,oldest'],
            'page' => ['nullable', 'integer', 'gt:0'],
            'page_page' => ['nullable', 'integer', 'gt:0'],
        ]);

        return $validator->valid();
    }

    public function filterCandidates(array $params): LengthAwarePaginator
    {
        $data = Validator::validate($params, [
            'keyword' => ['nullable', 'string', 'max:200'],
            'sort_by' => ['nullable', Rule::in(array_keys($this->getSortByParams()))],
            'order_by' => ['nullable', Rule::in(array_keys($this->getSortByParams()))],
            'page' => ['nullable', 'numeric', 'min:1'],
            'per_page' => ['nullable', 'numeric', 'min:1'],
        ]);

        $with = [
            'avatar',
        ];

        if (! $this->isDisabledPublicProfile()) {
            $with[] = 'slugable';
        }

        if (is_plugin_active('location')) {
            $with = array_merge($with, [
                'country',
                'state',
            ]);
        }

        $candidates = Account::query()
            ->where([
                'is_public_profile' => 1,
                'type' => AccountTypeEnum::JOB_SEEKER,
            ])
            ->with($with);

        $sortBy = match ($data['sort_by'] ?? $data['order_by'] ?? 'newest') {
            'oldest' => [
                'is_featured' => 'DESC',
                'created_at' => 'ASC',
            ],
            default => [
                'is_featured' => 'DESC',
                'created_at' => 'DESC',
            ],
        };

        foreach ($sortBy as $column => $direction) {
            $candidates = $candidates->orderBy($column, $direction);
        }

        if (setting('verify_account_email', 0)) {
            $candidates = $candidates->whereNotNull('confirmed_at');
        }

        if (isset($data['keyword']) && $keyword = $data['keyword']) {
            if (strlen($keyword) === 1) {
                $candidates = $candidates->where('first_name', 'LIKE', $keyword . '%');
            } else {
                $candidates = $candidates->where(function (BaseQueryBuilder $query) use ($keyword): void {
                    $query
                        ->addSearch('first_name', $keyword, false, false)
                        ->addSearch('last_name', $keyword, false);
                });
            }
        }

        if (self::isEnabledReview()) {
            $candidates = $candidates
                ->withAvg('reviews', 'star')
                ->withCount('reviews');
        }

        return $candidates->paginate($data['per_page'] ?? 12);
    }

    public function getSortByParams(): array
    {
        return apply_filters('job_board_sort_by_params', [
            'newest' => trans('plugins/job-board::messages.newest'),
            'oldest' => trans('plugins/job-board::messages.oldest'),
        ]);
    }

    public function getPerPageParams(): array
    {
        return [12, 24, 36];
    }

    public function isEnabledReview(): bool
    {
        return (bool) setting('job_board_is_enabled_review_feature', true);
    }

    public function isDisabledPublicProfile(): bool
    {
        return (bool) setting('job_board_disabled_public_profile', false);
    }

    public function getMapCenterLatLng(): array
    {
        $center = theme_option('latitude_longitude_center_on_jobs_page');
        $latLng = [];
        if ($center) {
            $center = explode(',', $center);
            if (count($center) == 2) {
                $latLng = [trim($center[0]), trim($center[1])];
            }
        }

        if (! $latLng) {
            $latLng = [43.615134, -76.393186];
        }

        return $latLng;
    }

    public function isZipCodeEnabled(): bool
    {
        return (bool) setting('job_board_zip_code_enabled', false);
    }

    public function isEnabledLatLongFields(): bool
    {
        return (bool) setting('job_board_enable_lat_long_fields', true);
    }

    public function hideCompanyEmailEnabled(): bool
    {
        return (bool) setting('job_board_hide_company_email_enabled', false);
    }

    public function getJobMaxPrice(): int
    {
        return Cache::remember('job_board_job_max_price', Carbon::now()->addHour(), function (): int {
            $price = Job::query()
                ->where('status', JobStatusEnum::PUBLISHED)
                ->max('salary_to');

            return $price ? (int) ceil($price) : 0;
        });
    }

    public function clearJobMaxPriceCache(): void
    {
        Cache::forget('job_board_job_max_price');
    }

    public function jobCategoriesForFilter(array $data = []): Collection
    {
        return Cache::remember('job_categories_for_filter', 1800, function () {
            $categories = Category::query()
                ->wherePublished()
                ->with(['slugable', 'activeChildren.activeChildren.activeChildren'])
                ->latest('created_at')
                ->get();

            Category::addJobsCountWithChildren($categories);

            return $categories->where('jobs_count', '>', 0)->sortByDesc('jobs_count');
        });
    }

    public function jobTypesForFilter(array $data = []): Collection
    {
        $query = JobType::query()
            ->select([
                'jb_job_types.*',
                DB::raw('(SELECT COUNT(DISTINCT jb_jobs.id)
                    FROM jb_jobs
                    INNER JOIN jb_jobs_types ON jb_jobs.id = jb_jobs_types.job_id
                    WHERE jb_jobs_types.job_type_id = jb_job_types.id
                    AND jb_jobs.moderation_status = "' . ModerationStatusEnum::APPROVED . '"
                    AND jb_jobs.status = "' . JobStatusEnum::PUBLISHED . '"
                    AND (jb_jobs.never_expired = 1 OR jb_jobs.expire_date IS NULL OR jb_jobs.expire_date >= NOW())
                    ' . $this->buildFilterConditionsForSubquery($data) . '
                ) as jobs_count'),
            ])
            ->wherePublished()
            ->having('jobs_count', '>', 0)
            ->orderBy('jobs_count', 'desc')
            ->orderBy('created_at', 'desc');

        return $query->get();
    }

    public function jobExperiencesForFilter(array $data = []): Collection
    {
        $query = JobExperience::query()
            ->select([
                'jb_job_experiences.id',
                'jb_job_experiences.name',
                'jb_job_experiences.order',
                DB::raw('COALESCE(COUNT(DISTINCT jb_jobs.id), 0) as jobs_count'),
            ])
            ->leftJoin('jb_jobs', function ($join) use ($data): void {
                $join->on('jb_job_experiences.id', '=', 'jb_jobs.job_experience_id')
                    ->where('jb_jobs.moderation_status', '=', ModerationStatusEnum::APPROVED)
                    ->where('jb_jobs.status', '=', JobStatusEnum::PUBLISHED)
                    ->where(function ($query): void {
                        $query->where('jb_jobs.never_expired', '=', 1)
                            ->orWhereNull('jb_jobs.expire_date')
                            ->orWhere('jb_jobs.expire_date', '>=', now());
                    });

                if (! empty($data['job_categories'])) {
                    $join->whereExists(function ($subQuery) use ($data): void {
                        $subQuery->select(DB::raw(1))
                            ->from('jb_jobs_categories')
                            ->whereRaw('jb_jobs_categories.job_id = jb_jobs.id')
                            ->whereIn('jb_jobs_categories.category_id', (array) $data['job_categories']);
                    });
                }

                if (isset($data['country_id']) && $data['country_id']) {
                    $join->where('jb_jobs.country_id', '=', $data['country_id']);
                }

                if (isset($data['city_id']) && $data['city_id']) {
                    $join->where('jb_jobs.city_id', '=', $data['city_id']);
                }

                if (isset($data['state_id']) && $data['state_id']) {
                    $join->where('jb_jobs.state_id', '=', $data['state_id']);
                }
            })
            ->where('jb_job_experiences.status', '=', BaseStatusEnum::PUBLISHED)
            ->groupBy('jb_job_experiences.id', 'jb_job_experiences.name', 'jb_job_experiences.order')
            ->having('jobs_count', '>', 0)->oldest('jb_job_experiences.order')->latest('jobs_count');

        return $query->get();
    }

    public function jobSkillsForFilter(array $data = []): Collection
    {
        $query = JobSkill::query()
            ->select([
                'jb_job_skills.*',
                \DB::raw('(SELECT COUNT(DISTINCT jb_jobs.id)
                    FROM jb_jobs
                    INNER JOIN jb_jobs_skills ON jb_jobs.id = jb_jobs_skills.job_id
                    WHERE jb_jobs_skills.job_skill_id = jb_job_skills.id
                    AND jb_jobs.moderation_status = "' . ModerationStatusEnum::APPROVED . '"
                    AND jb_jobs.status = "' . JobStatusEnum::PUBLISHED . '"
                    AND (jb_jobs.never_expired = 1 OR jb_jobs.expire_date IS NULL OR jb_jobs.expire_date >= NOW())
                    ' . $this->buildFilterConditionsForSubquery($data) . '
                ) as jobs_count'),
            ])
            ->wherePublished()
            ->having('jobs_count', '>', 0)
            ->orderBy('jobs_count', 'desc')
            ->orderBy('created_at', 'desc');

        return $query->get();
    }

    private function buildFilterConditionsForSubquery(array $data = []): string
    {
        $conditions = '';

        if (! empty($data['job_categories'])) {
            $categoryIds = implode(',', array_map('intval', (array) $data['job_categories']));
            $conditions .= ' AND EXISTS (
                SELECT 1 FROM jb_jobs_categories
                WHERE jb_jobs_categories.job_id = jb_jobs.id
                AND jb_jobs_categories.category_id IN (' . $categoryIds . ')
            )';
        }

        if (isset($data['country_id']) && $data['country_id']) {
            $conditions .= ' AND jb_jobs.country_id = ' . (int) $data['country_id'];
        }

        if (isset($data['city_id']) && $data['city_id']) {
            $conditions .= ' AND jb_jobs.city_id = ' . (int) $data['city_id'];
        }

        if (isset($data['state_id']) && $data['state_id']) {
            $conditions .= ' AND jb_jobs.state_id = ' . (int) $data['state_id'];
        }

        return $conditions;
    }

    public function jobTagsForFilter(array $data = []): Collection
    {
        if (empty($data['job_tags'])) {
            return new Collection();
        }

        return Tag::query()
            ->wherePublished()
            ->whereKey($data['job_tags'])
            ->get();
    }

    public function dataForFilter(array $data = []): array
    {
        $data = $this->getJobFilters($data);

        return [
            $this->jobCategoriesForFilter($data),
            $this->jobTypesForFilter($data),
            $this->jobExperiencesForFilter($data),
            $this->jobSkillsForFilter($data),
            $this->getJobMaxPrice(),
            $this->jobTagsForFilter($data),
        ];
    }

    public function getMapTileLayer(): string
    {
        return 'https://mt0.google.com/vt/lyrs=m&x={x}&y={y}&z={z}&hl=' . app()->getLocale();
    }

    public function isEnabledCustomFields(): bool
    {
        return (bool) setting('job_board_enabled_custom_fields_feature', true);
    }

    public function employerCreateMultipleCompanies(): bool
    {
        return (bool) setting('job_board_allow_employer_create_multiple_companies', true);
    }

    public function employerManageCompanyInfo(): bool
    {
        return (bool) setting('job_board_allow_employer_manage_company_info', true);
    }

    public function useCategoryIconImage(): void
    {
        add_filter(BASE_FILTER_BEFORE_RENDER_FORM, function (FormAbstract $form, Model $data): FormAbstract {
            if (! $data instanceof Category) {
                return $form;
            }

            $data->loadMissing(['metadata']);

            $icon = $data->getMetaData('icon', true);

            $iconImage = $data->getMetaData('icon_image', true);

            $form
                ->addAfter(
                    'image',
                    'icon',
                    $form->getFormHelper()->hasCustomField('themeIcon') ? 'themeIcon' : CoreIconField::class,
                    CoreIconFieldOption::make()
                        ->value($icon)
                )
                ->addAfter('icon', 'icon_image', 'mediaImage', [
                    'label' => trans('plugins/job-board::messages.icon_image'),
                    'value' => $iconImage,
                    'help_block' => [
                        'text' => trans('plugins/job-board::messages.icon_image_helper'),
                    ],
                    'wrapper' => [
                        'style' => 'display: block;',
                    ],
                ]);

            return $form;
        }, 230, 3);

        add_action(
            [BASE_ACTION_AFTER_CREATE_CONTENT, BASE_ACTION_AFTER_UPDATE_CONTENT],
            function ($type, $request, $object): void {
                if ($object instanceof Category) {
                    if ($request->has('icon')) {
                        MetaBox::saveMetaBoxData($object, 'icon', $request->input('icon'));
                    }

                    if ($request->has('icon_image')) {
                        MetaBox::saveMetaBoxData($object, 'icon_image', $request->input('icon_image'));
                    }
                }
            },
            230,
            3
        );
    }

    public function isEnabledEmailVerification(): bool
    {
        return setting('verify_account_email', 0);
    }

    public function isExpiredJobAccessible(): bool
    {
        return (bool) setting('job_board_accessible_expired_job', false);
    }

    public function isExpiredJobListing(): bool
    {
        if (! $this->isExpiredJobAccessible()) {
            return false;
        }

        return setting('job_board_listing_expired_job', false);
    }

    public function isClosedJobAccessible(): bool
    {
        return (bool) setting('job_board_accessible_closed_job', true);
    }

    public function isClosedJobListing(): bool
    {
        if (! $this->isClosedJobAccessible()) {
            return false;
        }

        return setting('job_board_listing_closed_job', true);
    }

    public function shouldNoIndexInactiveJobs(): bool
    {
        return (bool) setting('job_board_noindex_inactive_jobs', false);
    }

    public function isSalaryHiddenForGuests(): bool
    {
        if (auth('account')->check()) {
            return false;
        }

        return (bool) setting('job_board_hide_salary_for_guests', false);
    }

    public function isUniqueIdFieldHiddenInAdminForm(): bool
    {
        return (bool) setting('job_board_auto_generate_unique_id', false) &&
               (bool) setting('job_board_hide_unique_id_field_in_admin_form', false);
    }

    public function isUniqueIdFieldHiddenInFrontForm(): bool
    {
        return (bool) setting('job_board_auto_generate_unique_id', false) &&
               (bool) setting('job_board_hide_unique_id_field_in_front_form', false);
    }

    public function isCompanyInformationHiddenForGuests(): bool
    {
        if (auth('account')->check()) {
            return false;
        }

        return (bool) setting('job_board_hide_company_information_for_guests', false);
    }

    public function isCandidateInformationHiddenForGuests(): bool
    {
        if (auth('account')->check()) {
            return false;
        }

        return (bool) setting('job_board_hide_candidate_information_for_guests', false);
    }

    public function isOnlyEmployerCanViewCandidateInformation(): bool
    {
        if (! $this->isCandidateInformationHiddenForGuests()) {
            return false;
        }

        return (bool) setting('job_board_only_employer_can_view_candidate_information', true);
    }

    public function canViewCandidateInformation(): bool
    {
        $disableForGuests = (bool) setting('job_board_hide_candidate_information_for_guests', false);

        $disableForCandidates = (bool) setting('job_board_only_employer_can_view_candidate_information', true);

        $isLoggedIn = auth('account')->check();

        if ($disableForGuests && ! $isLoggedIn) {
            return false;
        }

        if ($disableForCandidates && (! $isLoggedIn || auth('account')->user()?->type == AccountTypeEnum::JOB_SEEKER)) {
            return false;
        }

        return true;
    }

    public function isPinFeaturedJobsInTheTop(): bool
    {
        return setting('job_board_enable_pin_featured_jobs_to_the_top', true);
    }

    public function isPinFeaturedCompaniesInTheTop(): bool
    {
        return setting('job_board_enable_pin_featured_companies_to_the_top', true);
    }

    public function isOpenExternalApplyUrlDirectly(): bool
    {
        return setting('job_board_external_apply_url_behavior', 'disabled') !== 'disabled';
    }

    public function getExternalApplyUrlTarget(): string
    {
        $setting = setting('job_board_external_apply_url_behavior', 'disabled');

        return match ($setting) {
            'new_tab' => '_blank',
            'current_tab' => '_self',
            default => '',
        };
    }
}
