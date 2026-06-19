<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AutoApplyOrder;
use Botble\JobBoard\Models\Currency;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AutoApplyCheckoutController extends BaseController
{
    public function plans()
    {
        SeoHelper::setTitle('Auto Apply Plans');
        $plans = AutoApplyOrder::plans();

        $appCurrency = get_application_currency();
        foreach ($plans as &$plan) {
            [$plan['displayPrice'], $plan['displayCurrency']] = $this->convertPrice(
                $plan['price'], $plan['currency'], $appCurrency
            );
        }
        unset($plan);

        return Theme::scope('job-board.auto-apply.plans', compact('plans'))->render();
    }

    public function checkout(string $plan)
    {
        $planData = AutoApplyOrder::plan($plan);
        abort_unless($planData, 404);

        /** @var Account|null $account */
        $account = auth('account')->user();

        SeoHelper::setTitle('Auto Apply — Checkout');

        [$planData['displayPrice'], $planData['displayCurrency']] = $this->convertPrice(
            $planData['price'], $planData['currency']
        );

        return Theme::scope('job-board.auto-apply.checkout', compact('plan', 'planData', 'account'))->render();
    }

    public function prepareCheckout(string $plan, Request $request): RedirectResponse
    {
        $planData = AutoApplyOrder::plan($plan);
        abort_unless($planData, 404);

        /** @var Account $account */
        $account = auth('account')->user();
        abort_unless($account, 403);

        $order = AutoApplyOrder::create([
            'account_id'           => $account->id,
            'plan'                 => $plan,
            'duration_days'        => $planData['duration_days'],
            'applications_allowed' => $planData['applications_per_month'],
            'amount'               => $planData['price'],
            'currency'             => $planData['currency'],
            'status'               => 'pending',
            'admin_status'         => 'pending',
        ]);

        $callbackUrl = route('public.auto-apply.callback', $order);
        $returnUrl   = route('public.auto-apply.plans');

        session([
            'auto_apply_order_id'     => $order->id,
            'auto_apply_callback_url' => $callbackUrl,
            'auto_apply_return_url'   => $returnUrl,
        ]);

        return redirect()->route('public.auto-apply.pay', $order);
    }

    public function pay(AutoApplyOrder $order)
    {
        /** @var Account $account */
        $account = auth('account')->user();
        abort_unless($account && $order->account_id === $account->id, 403);
        abort_unless($order->status === 'pending', 404);

        SeoHelper::setTitle('Auto Apply — Complete Payment');

        $planData    = AutoApplyOrder::plan($order->plan, includeDisabled: true);
        $amount      = (float) $order->amount;
        $currency    = $order->currency;
        $name        = 'Wakanda Jobs Auto Apply — ' . ($planData['label'] ?? $order->plan);
        $callbackUrl = route('public.auto-apply.callback', $order);
        $returnUrl   = route('public.auto-apply.plans');

        session([
            'auto_apply_order_id'     => $order->id,
            'auto_apply_callback_url' => $callbackUrl,
            'auto_apply_return_url'   => $returnUrl,
        ]);

        return Theme::scope('job-board.auto-apply.pay', compact(
            'order', 'planData', 'amount', 'currency', 'name', 'callbackUrl', 'returnUrl'
        ))->render();
    }

    public function callback(AutoApplyOrder $order, Request $request)
    {
        $chargeId = $request->input('charge_id') ?: $order->charge_id;

        if ($chargeId && ! $order->charge_id) {
            $order->update(['charge_id' => $chargeId]);
        }

        $order->refresh();

        return redirect()->route('public.auto-apply.thanks', $order);
    }

    public function thanks(AutoApplyOrder $order)
    {
        /** @var Account $account */
        $account = auth('account')->user();
        abort_unless($account && $order->account_id === $account->id, 403);

        SeoHelper::setTitle('Auto Apply — Order Received');
        $planData = AutoApplyOrder::plan($order->plan, includeDisabled: true);

        return Theme::scope('job-board.auto-apply.thanks', compact('order', 'planData'))->render();
    }

    private function convertPrice(float $price, string $currencyCode, ?Currency $appCurrency = null): array
    {
        $appCurrency  ??= get_application_currency();
        $planCurrency   = Currency::query()->where('title', $currencyCode)->first();

        if ($planCurrency && $appCurrency) {
            $inDefault  = (!$planCurrency->is_default && $planCurrency->exchange_rate > 0)
                ? $price / $planCurrency->exchange_rate
                : $price;
            $converted  = (!$appCurrency->is_default && $appCurrency->exchange_rate > 0)
                ? $inDefault * $appCurrency->exchange_rate
                : $inDefault;

            return [$converted, $appCurrency->title];
        }

        return [$price, $currencyCode];
    }
}
