<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AdOrder;
use Botble\JobBoard\Models\AdPlacement;
use Botble\JobBoard\Models\AdPricingTier;
use Botble\JobBoard\Models\Currency;
use Botble\Location\Models\Country;
use Botble\Media\Facades\RvMedia;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;

class AdOrderCheckoutController extends BaseController
{
    public function index()
    {
        SeoHelper::setTitle(__('Advertise'));

        /** @var Account $account */
        $account = auth('account')->user();

        $placements = AdPlacement::query()->with('tierPrices')->where('is_active', true)->orderBy('sort_order')->orderBy('price')->get();

        $tiers = AdPricingTier::query()->orderBy('sort_order')->orderBy('name')->get();
        $countryNames = Country::query()->pluck('name', 'id');

        $placementOptions = $placements
            ->mapWithKeys(fn (AdPlacement $placement): array => [$placement->getKey() => $this->placementOptions($placement, $tiers, $countryNames)]);

        $groups = $placements->groupBy(function (AdPlacement $placement): string {
            return match (true) {
                str_starts_with($placement->location, 'job_') => 'Jobs',
                str_starts_with($placement->location, 'company_') => 'Companies',
                str_starts_with($placement->location, 'candidate_') => 'Candidates',
                str_starts_with($placement->location, 'post_'), str_starts_with($placement->location, 'blog_') => 'Blog',
                default => 'General',
            };
        });

        $groupOrder = ['Jobs', 'Companies', 'Candidates', 'Blog', 'General'];

        $groups = collect($groupOrder)
            ->filter(fn (string $group) => $groups->has($group))
            ->mapWithKeys(fn (string $group) => [$group => $groups->get($group)]);

        $myOrders = AdOrder::query()
            ->with('placement')
            ->where('account_id', $account->getKey())
            ->latest()
            ->get();

        return JobBoardHelper::scope('account.ads', compact('account', 'placements', 'placementOptions', 'groups', 'myOrders'));
    }

    public function store(AdPlacement $placement, Request $request, BaseHttpResponse $response)
    {
        abort_unless($placement->is_active, 404);

        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
            'url' => ['nullable', 'url', 'max:255'],
            'open_in_new_tab' => ['nullable', 'boolean'],
            'tier_id' => ['nullable', 'integer'],
        ]);

        $tiers = AdPricingTier::query()->orderBy('sort_order')->orderBy('name')->get();
        $countryNames = Country::query()->pluck('name', 'id');

        $option = collect($this->placementOptions($placement, $tiers, $countryNames))
            ->firstWhere('tier_id', $request->input('tier_id') ? (int) $request->input('tier_id') : null);

        if (! $option) {
            return $response->setError()->setMessage(__('Please choose a valid reach for this placement.'));
        }

        /** @var Account $account */
        $account = auth('account')->user();

        $result = RvMedia::handleUpload($request->file('image'), 0, $account->upload_folder ?? 'ads');

        if ($result['error']) {
            return $response->setError()->setMessage($result['message']);
        }

        $amount = $this->convertToAccountCurrency($option['price'], $option['currency']);

        $order = AdOrder::create([
            'account_id' => $account->getKey(),
            'placement_id' => $placement->getKey(),
            'tier_id' => $option['tier_id'],
            'amount' => $amount['amount'],
            'currency' => $amount['currency_code'],
            'status' => 'pending',
            'image' => $result['data']->url,
            'url' => $request->input('url'),
            'open_in_new_tab' => (bool) $request->input('open_in_new_tab', true),
        ]);

        return $response->setNextUrl(route('public.account.ads.checkout', ['order' => $order->id]));
    }

    public function checkout(AdOrder $order)
    {
        /** @var Account $account */
        $account = auth('account')->user();

        abort_unless($order->account_id === $account->getKey(), 403);

        if ($order->status !== 'pending') {
            return redirect()->route('public.account.ads.index');
        }

        SeoHelper::setTitle(__('Checkout: :name', ['name' => $order->placement?->name]));

        $placement = $order->placement;
        $currency = $order->currency;
        $amount = $order->amount;

        $callbackUrl = route('public.account.ads.callback', ['order' => $order->id]);
        $returnUrl = route('public.account.ads.index');
        $name = ($placement->name ?? 'Ad Placement') . ' — Ad Placement';

        return Theme::scope('job-board.ads.checkout', compact(
            'order', 'placement', 'account', 'callbackUrl', 'returnUrl', 'currency', 'name', 'amount'
        ))->render();
    }

    public function callback(AdOrder $order, Request $request)
    {
        /** @var Account $account */
        $account = auth('account')->user();

        abort_unless($order->account_id === $account->getKey(), 403);

        $chargeId = $request->input('charge_id') ?: $order->charge_id;

        if ($chargeId && ! $order->charge_id) {
            $order->update(['charge_id' => $chargeId]);
        }

        $order->refresh();

        if ($order->status === 'approved') {
            return redirect()->route('public.account.ads.thanks', ['order' => $order->id]);
        }

        return redirect()->route('public.account.ads.pending', ['order' => $order->id]);
    }

    public function thanks(AdOrder $order)
    {
        /** @var Account $account */
        $account = auth('account')->user();
        abort_unless($order->account_id === $account->getKey() && $order->status === 'approved', 403);

        SeoHelper::setTitle(__('Thank You!'));

        $placement = $order->placement;

        return Theme::scope('job-board.ads.thanks', compact('order', 'placement', 'account'))->render();
    }

    public function pending(AdOrder $order)
    {
        /** @var Account $account */
        $account = auth('account')->user();
        abort_unless($order->account_id === $account->getKey(), 403);

        if ($order->status === 'approved') {
            return redirect()->route('public.account.ads.thanks', ['order' => $order->id]);
        }

        SeoHelper::setTitle(__('Activation Pending'));

        $placement = $order->placement;

        return Theme::scope('job-board.ads.pending', compact('order', 'placement', 'account'))->render();
    }

    /**
     * Build the list of reach options an employer can choose from for a
     * placement, one per pricing tier with a price configured for this
     * placement (shown only to visitors from that tier's countries). If no
     * reach prices are configured for this placement, fall back to a single
     * "All locations" option using the placement's default price, shown to
     * everyone.
     *
     * @param \Illuminate\Support\Collection<int, AdPricingTier> $tiers
     * @param \Illuminate\Support\Collection<int, string> $countryNames
     * @return array<int, array{tier_id: ?int, label: string, display: string, price: float, currency: string}>
     */
    protected function placementOptions(AdPlacement $placement, $tiers, $countryNames): array
    {
        $options = [];

        foreach ($tiers as $tier) {
            $override = $placement->tierPrices->firstWhere('tier_id', $tier->getKey());

            if (! $override) {
                continue;
            }

            $names = collect($tier->country_ids ?? [])
                ->map(fn (int $id) => $countryNames->get($id))
                ->filter()
                ->values();

            $countryList = $names->count() > 5
                ? $names->take(5)->implode(', ') . ' + ' . ($names->count() - 5) . ' more'
                : $names->implode(', ');

            $options[] = [
                'tier_id' => $tier->getKey(),
                'label' => $countryList ? "{$tier->name} ({$countryList})" : $tier->name,
                'display' => $this->formatAmount($override->price, $override->currency),
                'price' => $override->price,
                'currency' => strtoupper($override->currency),
            ];
        }

        if (empty($options)) {
            $options[] = [
                'tier_id' => null,
                'label' => __('All locations (no targeting)'),
                'display' => $this->formatAmount($placement->price, $placement->currency),
                'price' => $placement->price,
                'currency' => strtoupper($placement->currency),
            ];
        }

        return $options;
    }

    protected function formatAmount(float $price, string $currencyCode): string
    {
        $currency = Currency::query()->where('title', strtoupper($currencyCode))->first();

        return $currency
            ? format_price($price, $currency, fullNumber: true)
            : number_format($price, 2) . ' ' . strtoupper($currencyCode);
    }

    /**
     * Convert a price in the given currency to the application's active
     * currency, for storing as the order's payable amount.
     *
     * @return array{amount: float, currency_code: string}
     */
    protected function convertToAccountCurrency(float $price, string $currencyCode): array
    {
        $originCurrency = Currency::query()->where('title', strtoupper($currencyCode))->first();
        $targetCurrency = get_application_currency();

        $amount = round((float) format_price($price, $originCurrency, true, true, true), (int) ($targetCurrency->decimals ?? 2));

        return [
            'amount' => $amount,
            'currency_code' => strtoupper((string) ($targetCurrency->title ?? $currencyCode)),
        ];
    }
}
