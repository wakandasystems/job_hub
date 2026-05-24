<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\Location\Models\City;
use Botble\Location\Models\Country;
use Botble\Location\Models\State;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\SeoHelper\SeoOpenGraph;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class JobByLocationController extends PublicController
{
    /**
     * Industry keywords per country code — used to enrich location page meta descriptions.
     * Falls back to a generic African-context sentence for unlisted countries.
     */
    protected function countryIndustries(string $code): string
    {
        return match (strtoupper($code)) {
            'NG' => 'banking, oil & gas, fintech, FMCG, telecom and public sector',
            'ZA' => 'finance, mining, IT, healthcare, retail and manufacturing',
            'KE' => 'tech, NGO, banking, telecom, agriculture and tourism',
            'GH' => 'mining, banking, oil & gas, tech, cocoa and public sector',
            'ET' => 'manufacturing, agriculture, NGO, construction and government',
            'TZ' => 'mining, tourism, agriculture, NGO and banking',
            'RW' => 'tech, NGO, banking, hospitality and government',
            'UG' => 'NGO, banking, agriculture, telecom and public sector',
            'ZM' => 'mining, banking, agriculture, NGO and construction',
            'ZW' => 'mining, agriculture, banking, tech and manufacturing',
            'MZ' => 'mining, oil & gas, agriculture and construction',
            'AO' => 'oil & gas, construction, mining and banking',
            'CM' => 'oil & gas, agriculture, banking and telecom',
            'SN' => 'fishing, tech, banking, tourism and public sector',
            'CI' => 'cocoa, banking, oil, tech and construction',
            'MA' => 'tourism, manufacturing, banking, tech and agriculture',
            'EG' => 'tourism, banking, manufacturing, oil & gas and tech',
            'SD' => 'oil, agriculture, telecom and banking',
            'LY' => 'oil & gas, banking and construction',
            'DZ' => 'oil & gas, banking, manufacturing and public sector',
            'TN' => 'tourism, manufacturing, banking and tech',
            'ML' => 'gold mining, agriculture, NGO and banking',
            'BF' => 'gold mining, agriculture and NGO',
            'NE' => 'uranium mining, agriculture and NGO',
            'CD' => 'mining, NGO, banking and construction',
            'MW' => 'tobacco, agriculture, NGO and banking',
            'LS' => 'textiles, mining, NGO and banking',
            'BW' => 'diamond mining, banking, tourism and government',
            'NA' => 'mining, tourism, fishing and banking',
            'MU' => 'tourism, finance, tech and manufacturing',
            'SC' => 'tourism, fishing and finance',
            'SS' => 'oil & gas, NGO and construction',
            'BI' => 'agriculture, NGO and banking',
            'DJ' => 'logistics, finance and government',
            'SO' => 'NGO, telecom and banking',
            'ER' => 'mining, agriculture and NGO',
            'GA' => 'oil, manganese, banking and forestry',
            'CG' => 'oil, banking and construction',
            'CF' => 'mining, NGO and agriculture',
            'TD' => 'oil, agriculture and NGO',
            'GN' => 'bauxite mining, agriculture and banking',
            'GW' => 'cashew agriculture, fishing and NGO',
            'SL' => 'mining, agriculture and NGO',
            'LR' => 'rubber, iron ore, NGO and banking',
            'GM' => 'tourism, agriculture and banking',
            'BJ' => 'cotton, agriculture and banking',
            'TG' => 'phosphate, agriculture and banking',
            'ST' => 'cocoa, tourism and fishing',
            'CV' => 'tourism, fishing and banking',
            'KM' => 'agriculture, tourism and NGO',
            'MG' => 'mining, agriculture, tourism and NGO',
            'MR' => 'iron ore, fishing, banking and NGO',
            'SZ' => 'manufacturing, agriculture and banking',
            default => 'banking, NGO, tech, mining, agriculture and public sector',
        };
    }

    public function city(string $slug, Request $request): Response|BaseHttpResponse
    {
        $city = City::query()
            ->with('country')
            ->wherePublished()
            ->where('slug', $slug)
            ->firstOrFail();

        $countryName  = $city->country->name ?: 'Africa';
        $countryCode  = strtoupper((string) $city->country->code);
        $industries   = $this->countryIndustries($countryCode);
        $locationName = trim($city->name . ($countryName !== 'Africa' ? ', ' . $countryName : ''));
        $title        = "Jobs in {$locationName} | Wakanda Jobs";
        $description  = "Find jobs in {$locationName} on Wakanda Jobs — Africa's leading job board. Browse current {$countryName} vacancies in {$industries}. Apply online today.";

        $this->setLandingPageSeo($title, $description, route('public.jobs-by-city', $city->slug));

        Theme::breadcrumb()
            ->add(SeoHelper::getTitle(), route('public.jobs-by-city', $city->slug));

        do_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, CITY_MODULE_SCREEN_NAME, $city);

        $request->merge(['city_id' => $city->getKey()]);
        $requestQuery = JobBoardHelper::getJobFilters($request->input());

        $jobs = $this->jobRepository->getJobs(
            $requestQuery,
            [
                'paginate' => [
                    'per_page' => $request->integer('per_page', 12),
                ],
            ]
        );

        $data = $this->getJobFilterData();

        $data['jobs'] = $jobs;
        $data['ajaxUrl'] = route('public.jobs-by-city', $city->slug);
        $data['actionUrl'] = route('public.jobs-by-city', $city->slug);
        $data['cityId'] = $city->getKey();
        $data['seoDescription'] = $description;

        return Theme::scope('job-board.jobs', $data)->render();
    }

    public function state(string $slug, Request $request): Response|BaseHttpResponse
    {
        $state = State::query()
            ->with('country')
            ->wherePublished()
            ->where('slug', $slug)
            ->firstOrFail();

        $countryName  = $state->country->name ?: 'Africa';
        $countryCode  = strtoupper((string) $state->country->code);
        $industries   = $this->countryIndustries($countryCode);
        $locationName = trim($state->name . ($countryName !== 'Africa' ? ', ' . $countryName : ''));
        $title        = "Jobs in {$locationName} | Wakanda Jobs";
        $description  = "Find jobs in {$locationName} on Wakanda Jobs — Africa's leading job board. Browse current {$countryName} vacancies in {$industries}. Apply online today.";

        $this->setLandingPageSeo($title, $description, route('public.jobs-by-state', $state->slug));

        Theme::breadcrumb()
            ->add(SeoHelper::getTitle(), route('public.jobs-by-state', $state->slug));

        do_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, STATE_MODULE_SCREEN_NAME, $state);

        $request->merge(['state_id' => $state->getKey()]);
        $requestQuery = JobBoardHelper::getJobFilters($request->input());

        $jobs = $this->jobRepository->getJobs(
            $requestQuery,
            [
                'paginate' => [
                    'per_page' => $request->integer('per_page', 12),
                ],
            ]
        );

        $data = $this->getJobFilterData();

        $data['jobs'] = $jobs;
        $data['ajaxUrl'] = route('public.jobs-by-state', $state->slug);
        $data['actionUrl'] = route('public.jobs-by-state', $state->slug);
        $data['stateId'] = $state->getKey();
        $data['seoDescription'] = $description;

        return Theme::scope('job-board.jobs', $data)->render();
    }

    public function country(string $slug, Request $request): Response|BaseHttpResponse
    {
        $country = Country::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->get()
            ->first(function (Country $country) use ($slug): bool {
                return strtolower((string) $country->code) === strtolower($slug)
                    || Str::slug($country->name) === Str::slug($slug);
            });

        abort_unless($country, 404);

        $countryCode = strtoupper((string) $country->code);
        $industries  = $this->countryIndustries($countryCode);
        $title       = "Jobs in {$country->name} | Wakanda Jobs — Find Your Next Job";
        $description = "Find jobs in {$country->name} on Wakanda Jobs — Africa's leading job board. Browse thousands of vacancies in {$industries}. Updated daily. Apply online today.";
        $url = route('public.jobs-by-country', $this->countryRouteKey($country));

        $this->setLandingPageSeo($title, $description, $url);

        Theme::breadcrumb()
            ->add(SeoHelper::getTitle(), $url);

        $request->merge(['country_id' => $country->getKey()]);
        $requestQuery = JobBoardHelper::getJobFilters($request->input());

        $jobs = $this->jobRepository->getJobs(
            $requestQuery,
            [
                'paginate' => [
                    'per_page' => $request->integer('per_page', 12),
                ],
            ]
        );

        $data = $this->getJobFilterData();

        $data['jobs'] = $jobs;
        $data['ajaxUrl'] = $url;
        $data['actionUrl'] = $url;
        $data['countryId'] = $country->getKey();
        $data['seoDescription'] = $description;

        return Theme::scope('job-board.jobs', $data)->render();
    }

    public function title(string $slug, Request $request): Response|BaseHttpResponse
    {
        $keyword = trim(Str::of($slug)->replace(['-', '_'], ' ')->squish()->lower()->toString());

        abort_unless($keyword, 404);

        $selectedCountry = function_exists('wakanda_selected_country') ? wakanda_selected_country() : null;
        $locationSuffix = $selectedCountry && $selectedCountry->name ? ' in ' . $selectedCountry->name : '';

        return $this->renderTitleLanding($keyword, $request, null, $locationSuffix, route('public.jobs-by-title', Str::slug($keyword)));
    }

    public function titleInCountry(string $country, string $slug, Request $request): Response|BaseHttpResponse
    {
        $country = $this->findCountry($country);
        $keyword = trim(Str::of($slug)->replace(['-', '_'], ' ')->squish()->lower()->toString());

        abort_unless($country && $keyword, 404);

        return $this->renderTitleLanding(
            $keyword,
            $request,
            $country,
            ' in ' . $country->name,
            route('public.jobs-by-country-title', [
                'country' => $this->countryRouteKey($country),
                'slug' => Str::slug($keyword),
            ])
        );
    }

    protected function renderTitleLanding(
        string $keyword,
        Request $request,
        ?Country $country,
        string $locationSuffix,
        string $url
    ): Response|BaseHttpResponse {
        $displayKeyword = Str::title($keyword);
        $title       = "{$displayKeyword} Jobs{$locationSuffix} | Wakanda Jobs";
        $description = "Browse {$displayKeyword} job vacancies{$locationSuffix} on Wakanda Jobs - Africa's leading job board. View employer listings, salaries and requirements. Apply online today.";

        $this->setLandingPageSeo($title, $description, $url);

        Theme::breadcrumb()
            ->add(SeoHelper::getTitle(), $url);

        $request->merge(array_filter([
            'keyword' => $keyword,
            'country_id' => $country?->getKey(),
        ]));
        $requestQuery = JobBoardHelper::getJobFilters($request->input());

        $jobs = $this->jobRepository->getJobs(
            $requestQuery,
            [
                'paginate' => [
                    'per_page' => $request->integer('per_page', 12),
                ],
            ]
        );

        $data = $this->getJobFilterData();

        $data['jobs'] = $jobs;
        $data['ajaxUrl'] = $url;
        $data['actionUrl'] = $url;
        $data['keyword'] = $keyword;
        $data['countryId'] = $country?->getKey();
        $data['seoDescription'] = $description;

        return Theme::scope('job-board.jobs', $data)->render();
    }

    protected function setLandingPageSeo(string $title, string $description, string $url): void
    {
        SeoHelper::setTitle($title)->setDescription($description);

        $meta = new SeoOpenGraph();
        $meta->setTitle($title);
        $meta->setDescription($description);
        $meta->setUrl($url);
        $meta->setType('website');

        SeoHelper::setSeoOpenGraph($meta);
    }

    protected function countryRouteKey(Country $country): string
    {
        return strtolower((string) $country->code) ?: Str::slug($country->name);
    }

    protected function findCountry(string $slug): ?Country
    {
        return Country::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->get()
            ->first(function (Country $country) use ($slug): bool {
                return strtolower((string) $country->code) === strtolower($slug)
                    || Str::slug($country->name) === Str::slug($slug);
            });
    }
}
