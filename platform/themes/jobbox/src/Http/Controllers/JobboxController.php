<?php

namespace Theme\Jobbox\Http\Controllers;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\Currency;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Repositories\Interfaces\CategoryInterface;
use Botble\JobBoard\Repositories\Interfaces\JobInterface;
use Botble\Location\Facades\Location;
use Botble\Location\Models\City;
use Botble\Location\Models\Country;
use Botble\Location\Models\State;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Http\Controllers\PublicController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Validator;
use Theme\Jobbox\Http\Resources\CategoryResource;

class JobboxController extends PublicController
{
    public function setLocalizationCountry(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'c' => ['required', 'string'],
            'redirect' => ['nullable', 'string'],
        ]);

        $countryId = wakanda_decode_country_token($validated['c']);

        abort_unless($countryId, 404);

        $country = Country::query()->findOrFail($countryId);

        session()->put('wakanda_country_id', $country->id);
        Cookie::queue('wakanda_country_id', $country->id, 60 * 24 * 365);

        $currencyCode = cms_currency()->countryCurrencies()[strtoupper((string) $country->code)] ?? null;
        if ($currencyCode) {
            $currency = Currency::query()->where('title', $currencyCode)->first();
            if ($currency) {
                cms_currency()->setApplicationCurrency($currency);
            }
        }

        $redirect = $validated['redirect'] ?? url()->previous() ?: route('public.index');

        if (! str_starts_with($redirect, url('/'))) {
            $redirect = route('public.index');
        }

        $parts = parse_url($redirect);
        parse_str($parts['query'] ?? '', $query);
        unset($query['country_id']);
        $query['c'] = wakanda_encode_country_id($country->id);

        $redirect = ($parts['scheme'] ?? request()->getScheme()) . '://'
            . ($parts['host'] ?? request()->getHost())
            . (isset($parts['port']) ? ':' . $parts['port'] : '')
            . ($parts['path'] ?? '/')
            . ($query ? '?' . http_build_query($query) : '');

        return redirect()->to($redirect);
    }

    public function ajaxGetLocation(
        Request $request,
        BaseHttpResponse $response
    ) {
        $request->validate([
            'k' => ['nullable', 'string'],
        ]);

        $keyword = BaseHelper::stringify($request->query('k'));
        $selectedCountry = wakanda_selected_country();

        $addresses = Job::query()
            ->select('address')
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->wherePublished()
            ->when($selectedCountry, fn ($q) => $q->where('country_id', $selectedCountry->id))
            ->when($keyword, fn ($q) => $q->where('address', 'LIKE', '%' . $keyword . '%'))
            ->distinct()
            ->orderBy('address')
            ->limit(50)
            ->pluck('address')
            ->map(fn ($addr) => ['id' => $addr, 'name' => $addr]);

        return $response->setData([$addresses, 'total' => $addresses->count()]);
    }

    public function ajaxGetTalentStates(Request $request)
    {
        abort_unless(is_plugin_active('location'), 404);

        $request->validate([
            'country_id' => ['required', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $states = State::query()
            ->select(['id', 'name'])
            ->wherePublished()
            ->where('country_id', $request->integer('country_id'))
            ->when($request->query('q'), function ($query, string $keyword): void {
                $query->where('name', 'LIKE', '%' . BaseHelper::stringify($keyword) . '%');
            })
            ->orderBy('order')
            ->orderBy('name')
            ->paginate(10);

        return response()->json([
            'results' => $states->map(fn (State $state) => [
                'id' => $state->id,
                'text' => $state->name,
            ])->values(),
            'pagination' => ['more' => $states->hasMorePages()],
        ]);
    }

    public function ajaxGetTalentCities(Request $request)
    {
        abort_unless(is_plugin_active('location'), 404);

        $request->validate([
            'country_id' => ['required', 'integer'],
            'state_id' => ['required', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $cities = City::query()
            ->select(['id', 'name'])
            ->wherePublished()
            ->where('country_id', $request->integer('country_id'))
            ->where('state_id', $request->integer('state_id'))
            ->when($request->query('q'), function ($query, string $keyword): void {
                $query->where('name', 'LIKE', '%' . BaseHelper::stringify($keyword) . '%');
            })
            ->orderBy('order')
            ->orderBy('name')
            ->paginate(min($request->integer('per_page', 10), 200));

        return response()->json([
            'results' => $cities->map(fn (City $city) => [
                'id' => $city->id,
                'text' => $city->name,
            ])->values(),
            'pagination' => ['more' => $cities->hasMorePages()],
        ]);
    }

    public function ajaxGetJobCategories(
        Request $request,
        BaseHttpResponse $response,
        CategoryInterface $categoryRepository
    ) {
        $keyword = BaseHelper::stringify($request->query('k'));

        $condition = [
            'with' => ['slugable'],
            'paginate' => [
                'per_page' => 10,
                'current_paged' => $request->integer('page', 1),
            ],
        ];

        if ($keyword) {
            $condition['condition'] = ['keyword' => ['name', 'like', '%' . $keyword . '%']];
        }

        $categories = $categoryRepository->advancedGet($condition);

        $total = $categories->total();

        return $response->setData([CategoryResource::collection($categories), 'total' => $total]);
    }

    public function ajaxGetJobByCategories(
        $categoryId,
        Request $request,
        BaseHttpResponse $response,
        JobInterface $jobRepository
    ) {
        $validator = Validator::make($request->input(), [
            'style' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $response->setNextUrl(route('public.index'));
        }

        $with = [
            'company',
            'slugable',
            'metadata',
        ];

        if (is_plugin_active('location')) {
            $with = array_merge($with, array_keys(Location::getSupported(Job::class)));
        }

        $view = Theme::getThemeNamespace('views.job-board.partials.job-of-the-day-items');

        $style = BaseHelper::stringify($request->input('style')) ?: 'style-1';

        $requestQuery = JobBoardHelper::getJobFilters($request->input());

        $jobs = $jobRepository
            ->getJobs(
                array_merge($requestQuery, [
                    'job_categories' => [$categoryId],
                ]),
                [
                    'take' => $request->integer('limit') ?: 8,
                    'with' => $with,
                ]
            );

        return $response
            ->setData(view($view, compact('jobs', 'style'))->render());
    }

    public function ajaxQuickSearchJobs(Request $request, BaseHttpResponse $response): BaseHttpResponse
    {
        $keyword = trim((string) $request->input('keyword', ''));

        if (strlen($keyword) < 2) {
            return $response->setError();
        }

        $conditions = JobBoardHelper::getJobDisplayQueryConditions();

        // FULLTEXT search on job name — fast on 30k+ rows.
        $booleanTerm = '+' . implode('* +', array_filter(array_map('trim', explode(' ', $keyword)))) . '*';

        $jobs = Job::select('jb_jobs.*')
            ->where($conditions)
            ->notExpired()
            ->notClosed()
            ->whereRaw('MATCH(jb_jobs.name) AGAINST(? IN BOOLEAN MODE)', [$booleanTerm])
            ->with(['company', 'company.slugable', 'slugable'])
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        // Fallback to LIKE if FULLTEXT returned nothing (e.g. stop-words, short tokens).
        if ($jobs->isEmpty()) {
            $jobs = Job::select('jb_jobs.*')
                ->where($conditions)
                ->notExpired()
                ->notClosed()
                ->where('jb_jobs.name', 'LIKE', '%' . $keyword . '%')
                ->with(['company', 'company.slugable', 'slugable'])
                ->orderByDesc('is_featured')
                ->orderByDesc('created_at')
                ->take(10)
                ->get();
        }

        if ($jobs->isEmpty()) {
            return $response->setError();
        }

        return $response->setData([
            'html' => Theme::partial('job-quick-search', compact('jobs')),
        ]);
    }
}
