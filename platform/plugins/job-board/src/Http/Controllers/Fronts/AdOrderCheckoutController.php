<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AdOrder;
use Botble\JobBoard\Models\AdPlacement;
use Botble\JobBoard\Models\Currency;
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

        $placements = AdPlacement::query()->where('is_active', true)->orderBy('sort_order')->orderBy('price')->get();

        $placementPrices = $placements
            ->mapWithKeys(fn (AdPlacement $placement): array => [$placement->getKey() => $this->placementPricing($placement)]);

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

        return JobBoardHelper::scope('account.ads', compact('account', 'placements', 'placementPrices', 'groups', 'myOrders'));
    }

    public function store(AdPlacement $placement, Request $request, BaseHttpResponse $response)
    {
        abort_unless($placement->is_active, 404);

        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
            'url' => ['nullable', 'url', 'max:255'],
            'open_in_new_tab' => ['nullable', 'boolean'],
        ]);

        /** @var Account $account */
        $account = auth('account')->user();

        $result = RvMedia::handleUpload($request->file('image'), 0, $account->upload_folder ?? 'ads');

        if ($result['error']) {
            return $response->setError()->setMessage($result['message']);
        }

        $pricing = $this->placementPricing($placement);

        $order = AdOrder::create([
            'account_id' => $account->getKey(),
            'placement_id' => $placement->getKey(),
            'amount' => $pricing['amount'],
            'currency' => $pricing['currency_code'],
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

    protected function placementPricing(AdPlacement $placement): array
    {
        $originCode = strtoupper((string) $placement->currency);
        $originCurrency = Currency::query()->where('title', $originCode)->first();
        $targetCurrency = get_application_currency();
        $amount = round((float) format_price($placement->price, $originCurrency, true, true, true), (int) ($targetCurrency->decimals ?? 2));
        $originMeta = function_exists('wakanda_currency_meta') ? wakanda_currency_meta($originCode) : null;
        $targetMeta = $targetCurrency && function_exists('wakanda_currency_meta') ? wakanda_currency_meta($targetCurrency->title) : null;

        return [
            'amount' => $amount,
            'display' => $originCurrency ? format_price($placement->price, $originCurrency, fullNumber: true) : number_format($placement->price, 2) . ' ' . $originCode,
            'currency_code' => strtoupper((string) ($targetCurrency->title ?? $originCode)),
            'target_country' => $targetMeta['country'] ?? null,
            'origin_country' => $originMeta['country'] ?? null,
            'origin_currency_code' => $originCode,
            'origin_display' => number_format($placement->price, 2) . ' ' . $originCode,
        ];
    }
}
