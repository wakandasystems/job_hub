<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\AdPlacement;
use Botble\JobBoard\Models\AdPlacementTierPrice;
use Botble\JobBoard\Models\AdPricingTier;
use Illuminate\Http\Request;

class AdPlacementController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Ad Pricing', route('ad-placements.index'));
    }

    public function index()
    {
        $this->pageTitle('Ad Pricing');

        $placements = AdPlacement::query()->orderBy('sort_order')->orderBy('name')->get();

        $groups = $placements->groupBy(function (AdPlacement $placement): string {
            return match (true) {
                str_starts_with($placement->location, 'job_') => 'Jobs',
                str_starts_with($placement->location, 'company_') => 'Companies',
                str_starts_with($placement->location, 'candidate_') => 'Candidates',
                str_starts_with($placement->location, 'post_'), str_starts_with($placement->location, 'blog_') => 'Blog',
                str_starts_with($placement->location, 'social_') => 'Social Media',
                default => 'General',
            };
        });

        $groupOrder = ['Jobs', 'Companies', 'Candidates', 'Blog', 'Social Media', 'General'];

        $groups = collect($groupOrder)
            ->filter(fn (string $group) => $groups->has($group))
            ->mapWithKeys(fn (string $group) => [$group => $groups->get($group)]);

        return view('plugins/job-board::ad-placements.index', compact('placements', 'groups'));
    }

    public function create()
    {
        $this->pageTitle('Create Ad Placement');

        return view('plugins/job-board::ad-placements.create');
    }

    public function store(Request $request, BaseHttpResponse $response)
    {
        $validated = $this->validatePlacement($request);

        AdPlacement::query()->create($validated);

        return $response
            ->setPreviousUrl(route('ad-placements.index'))
            ->setNextUrl(route('ad-placements.index'))
            ->setMessage('Ad placement created successfully.');
    }

    public function edit(AdPlacement $adPlacement)
    {
        $this->pageTitle('Edit Ad Placement');

        $adPlacement->load('tierPrices');

        $tiers = AdPricingTier::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('plugins/job-board::ad-placements.edit', ['placement' => $adPlacement, 'tiers' => $tiers]);
    }

    public function update(AdPlacement $adPlacement, Request $request, BaseHttpResponse $response)
    {
        $validated = $this->validatePlacement($request);

        $adPlacement->update($validated);

        $this->saveTierPrices($adPlacement, $request);

        return $response
            ->setPreviousUrl(route('ad-placements.index'))
            ->setNextUrl(route('ad-placements.edit', $adPlacement))
            ->setMessage('Ad placement updated successfully.');
    }

    protected function saveTierPrices(AdPlacement $adPlacement, Request $request): void
    {
        $tierIds = AdPricingTier::query()->pluck('id');

        foreach ($tierIds as $tierId) {
            $price = $request->input("tier_prices.$tierId.price");
            $currency = $request->input("tier_prices.$tierId.currency");

            if ($price === null || $price === '') {
                AdPlacementTierPrice::query()
                    ->where('ad_placement_id', $adPlacement->getKey())
                    ->where('tier_id', $tierId)
                    ->delete();

                continue;
            }

            AdPlacementTierPrice::query()->updateOrCreate(
                ['ad_placement_id' => $adPlacement->getKey(), 'tier_id' => $tierId],
                ['price' => (float) $price, 'currency' => strtoupper((string) ($currency ?: $adPlacement->currency))]
            );
        }
    }

    public function destroy(AdPlacement $adPlacement, BaseHttpResponse $response)
    {
        $adPlacement->delete();

        return $response
            ->setPreviousUrl(route('ad-placements.index'))
            ->setMessage('Ad placement deleted successfully.');
    }

    protected function validatePlacement(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'location' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:3'],
            'duration_days' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['currency'] = strtoupper($data['currency']);

        return $data;
    }
}
