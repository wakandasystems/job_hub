<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\AdPricingTier;
use Botble\Location\Models\Country;
use Illuminate\Http\Request;

class AdPricingTierController extends BaseController
{
    /**
     * Countries in our location list that are not part of Africa. Everything
     * else is treated as African for the "Select all African countries"
     * quick-pick on the pricing tier form.
     */
    protected const NON_AFRICAN_COUNTRIES = ['France', 'England', 'USA', 'Holland', 'Germany', 'Denmark'];

    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Ad Pricing Tiers', route('ad-pricing-tiers.index'));
    }

    public function index()
    {
        $this->pageTitle('Ad Pricing Tiers');

        $tiers = AdPricingTier::query()->orderBy('sort_order')->orderBy('name')->get();
        $countries = Country::query()->orderBy('name')->get(['id', 'name']);

        return view('plugins/job-board::ad-pricing-tiers.index', compact('tiers', 'countries'));
    }

    public function create()
    {
        $this->pageTitle('Create Pricing Tier');

        $countries = Country::query()->orderBy('name')->get(['id', 'name']);
        $africanCountryIds = $this->africanCountryIds($countries);

        return view('plugins/job-board::ad-pricing-tiers.create', compact('countries', 'africanCountryIds'));
    }

    public function store(Request $request, BaseHttpResponse $response)
    {
        $validated = $this->validateTier($request);

        AdPricingTier::query()->create($validated);

        return $response
            ->setPreviousUrl(route('ad-pricing-tiers.index'))
            ->setNextUrl(route('ad-pricing-tiers.index'))
            ->setMessage('Pricing tier created successfully.');
    }

    public function edit(AdPricingTier $adPricingTier)
    {
        $this->pageTitle('Edit Pricing Tier');

        $countries = Country::query()->orderBy('name')->get(['id', 'name']);
        $africanCountryIds = $this->africanCountryIds($countries);

        return view('plugins/job-board::ad-pricing-tiers.edit', [
            'tier' => $adPricingTier,
            'countries' => $countries,
            'africanCountryIds' => $africanCountryIds,
        ]);
    }

    public function update(AdPricingTier $adPricingTier, Request $request, BaseHttpResponse $response)
    {
        $validated = $this->validateTier($request);

        $adPricingTier->update($validated);

        return $response
            ->setPreviousUrl(route('ad-pricing-tiers.index'))
            ->setNextUrl(route('ad-pricing-tiers.edit', $adPricingTier))
            ->setMessage('Pricing tier updated successfully.');
    }

    public function destroy(AdPricingTier $adPricingTier, BaseHttpResponse $response)
    {
        $adPricingTier->delete();

        return $response
            ->setPreviousUrl(route('ad-pricing-tiers.index'))
            ->setMessage('Pricing tier deleted successfully.');
    }

    protected function validateTier(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'country_ids' => ['nullable', 'array'],
            'country_ids.*' => ['integer'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['country_ids'] = array_map('intval', $data['country_ids'] ?? []);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    /**
     * @param \Illuminate\Support\Collection<int, \Botble\Location\Models\Country> $countries
     * @return array<int, int>
     */
    protected function africanCountryIds($countries): array
    {
        return $countries
            ->reject(fn (Country $country) => in_array($country->name, self::NON_AFRICAN_COUNTRIES, true))
            ->pluck('id')
            ->values()
            ->all();
    }
}
