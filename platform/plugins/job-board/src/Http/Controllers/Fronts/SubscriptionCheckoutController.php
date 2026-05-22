<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\EmployerSubscription;
use Botble\JobBoard\Models\Package;
use Botble\JobBoard\Supports\SubscriptionService;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;

class SubscriptionCheckoutController extends BaseController
{
    public function __construct(protected SubscriptionService $subscriptionService)
    {
    }

    public function index()
    {
        SeoHelper::setTitle(__('Subscription Plans'));
        PageTitle::setTitle(__('Subscription Plans'));

        /** @var Account $account */
        $account = auth('account')->user();

        $plans = Package::query()
            ->wherePublished()
            ->whereIn('billing_cycle', ['monthly', 'annual'])
            ->orderByRaw('(price - (price * percent_save / 100)) asc')
            ->oldest('order')
            ->get();

        $activeSub = $this->subscriptionService->getActiveSubscription($account);

        $myOrders = EmployerSubscription::query()
            ->with('package')
            ->where('account_id', $account->getKey())
            ->latest()
            ->limit(20)
            ->get();

        return JobBoardHelper::view('dashboard.subscription', compact('account', 'plans', 'activeSub', 'myOrders'));
    }

    public function checkout(Package $package, Request $request)
    {
        abort_unless($package->wherePublished()->where('id', $package->id)->exists(), 404);
        abort_unless($package->isSubscription(), 404);

        SeoHelper::setTitle(__('Subscribe: :name', ['name' => $package->name]));

        /** @var Account $account */
        $account = auth('account')->user();

        $currency = strtoupper(cms_currency()->getDefaultCurrency()->title ?? 'USD');

        $price = (float) $package->price;
        if ($request->input('cycle', $package->billing_cycle) === 'annual' && $package->billing_cycle === 'monthly') {
            $price = round($price * 12 * 0.8, 2);
        }

        $billingCycle = $request->input('cycle', $package->billing_cycle);

        $sub = EmployerSubscription::create([
            'account_id'   => $account->getKey(),
            'package_id'   => $package->getKey(),
            'billing_cycle'=> $billingCycle,
            'amount'       => $price,
            'currency'     => $currency,
            'status'       => 'pending',
        ]);

        $callbackUrl = route('public.account.subscription.callback', ['subscription' => $sub->id]);
        $returnUrl   = route('public.account.subscription.index');
        $name        = $package->name . ' — ' . ucfirst($billingCycle) . ' Subscription';

        return Theme::scope('job-board.subscription.checkout', compact(
            'package', 'sub', 'account', 'callbackUrl', 'returnUrl', 'currency', 'name', 'price', 'billingCycle'
        ))->render();
    }

    public function callback(EmployerSubscription $subscription, Request $request)
    {
        /** @var Account $account */
        $account = auth('account')->user();
        abort_unless($subscription->account_id === $account->getKey(), 403);

        $chargeId = $request->input('charge_id') ?: $subscription->charge_id;
        if ($chargeId && ! $subscription->charge_id) {
            $subscription->update(['charge_id' => $chargeId]);
        }

        $subscription->refresh();

        if ($subscription->status === 'active') {
            return redirect()->route('public.account.subscription.thanks', ['subscription' => $subscription->id]);
        }

        return redirect()->route('public.account.subscription.pending', ['subscription' => $subscription->id]);
    }

    public function thanks(EmployerSubscription $subscription)
    {
        /** @var Account $account */
        $account = auth('account')->user();
        abort_unless($subscription->account_id === $account->getKey() && $subscription->status === 'active', 403);

        SeoHelper::setTitle(__('Subscription Activated!'));

        return Theme::scope('job-board.subscription.thanks', compact('subscription', 'account'))->render();
    }

    public function pending(EmployerSubscription $subscription)
    {
        /** @var Account $account */
        $account = auth('account')->user();
        abort_unless($subscription->account_id === $account->getKey(), 403);

        if ($subscription->status === 'active') {
            return redirect()->route('public.account.subscription.thanks', ['subscription' => $subscription->id]);
        }

        SeoHelper::setTitle(__('Payment Pending'));

        return Theme::scope('job-board.subscription.pending', compact('subscription', 'account'))->render();
    }

    public function cancel(Request $request)
    {
        /** @var Account $account */
        $account = auth('account')->user();

        $sub = $this->subscriptionService->getActiveSubscription($account);

        if ($sub) {
            $sub->cancel(immediately: false);
        }

        return redirect()->route('public.account.subscription.index')
            ->with('success', __('Your subscription will be cancelled at the end of the current period.'));
    }
}
