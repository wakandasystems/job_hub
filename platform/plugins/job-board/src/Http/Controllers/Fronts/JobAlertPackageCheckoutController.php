<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\JobAlertOrder;
use Botble\JobBoard\Models\JobAlertPackage;
use Botble\JobBoard\Models\JobAlertQuota;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;

class JobAlertPackageCheckoutController extends BaseController
{
    public function index()
    {
        SeoHelper::setTitle(__('Job Alert Packages'));

        /** @var Account $account */
        $account  = auth('account')->user();
        $packages = JobAlertPackage::query()->where('is_active', true)->orderBy('sort_order')->orderBy('price')->get();

        $period   = JobAlertQuota::currentPeriod();
        $sentFree = (int) JobAlertQuota::query()
            ->where('account_id', $account->id)->where('period', $period)->whereNull('package_id')
            ->value('alerts_sent');
        $paidQuota = JobAlertQuota::query()
            ->activePaid()
            ->where('account_id', $account->id)->where('period', $period)
            ->with('package')->get();
        $freeLimit = (int) setting('job_alert_free_monthly_limit', 3);

        $myOrders = JobAlertOrder::query()
            ->with('package')
            ->where('account_id', $account->getKey())
            ->latest()
            ->get();

        return JobBoardHelper::scope('account.job-alert-packages', compact('account', 'packages', 'sentFree', 'paidQuota', 'freeLimit', 'period', 'myOrders'));
    }

    public function checkout(JobAlertPackage $package)
    {
        abort_unless($package->is_active, 404);

        SeoHelper::setTitle(__('Checkout: :name', ['name' => $package->name]));

        /** @var Account $account */
        $account  = auth('account')->user();
        $currency = strtoupper(cms_currency()->getDefaultCurrency()->title ?? 'USD');

        // Create a pending order to track this purchase
        $order = JobAlertOrder::create([
            'account_id' => $account->getKey(),
            'package_id' => $package->getKey(),
            'amount'     => $package->price,
            'currency'   => $currency,
            'status'     => 'pending',
        ]);

        $callbackUrl = route('public.account.job-alert.packages.callback', ['order' => $order->id]);
        $returnUrl   = route('public.account.job-alert.packages.index');
        $name        = $package->name . ' — Job Alerts';
        $amount      = $package->price;

        return Theme::scope('job-board.job-alert-packages.checkout', compact(
            'package', 'order', 'account', 'callbackUrl', 'returnUrl', 'currency', 'name', 'amount'
        ))->render();
    }

    public function callback(JobAlertOrder $order, Request $request)
    {
        /** @var Account $account */
        $account = auth('account')->user();

        abort_unless($order->account_id === $account->getKey(), 403);

        $chargeId = $request->input('charge_id') ?: $order->charge_id;

        if ($chargeId && ! $order->charge_id) {
            $order->update(['charge_id' => $chargeId]);
        }

        // Reload in case PAYMENT_ACTION_PAYMENT_PROCESSED already approved it
        $order->refresh();

        if ($order->status === 'approved') {
            return redirect()->route('public.account.job-alert.packages.thanks', ['order' => $order->id]);
        }

        return redirect()->route('public.account.job-alert.packages.pending', ['order' => $order->id]);
    }

    public function thanks(JobAlertOrder $order)
    {
        /** @var Account $account */
        $account = auth('account')->user();
        abort_unless($order->account_id === $account->getKey() && $order->status === 'approved', 403);

        SeoHelper::setTitle(__('Thank You!'));

        $package = $order->package;
        $period  = JobAlertQuota::currentPeriod();
        $quota   = JobAlertQuota::query()
            ->where('account_id', $account->id)->where('period', $period)->where('package_id', $package->id)
            ->first();

        return Theme::scope('job-board.job-alert-packages.thanks', compact('package', 'order', 'account', 'quota'))->render();
    }

    public function pending(JobAlertOrder $order)
    {
        /** @var Account $account */
        $account = auth('account')->user();
        abort_unless($order->account_id === $account->getKey(), 403);

        // If it got approved in the meantime, redirect to thanks
        if ($order->status === 'approved') {
            return redirect()->route('public.account.job-alert.packages.thanks', ['order' => $order->id]);
        }

        SeoHelper::setTitle(__('Payment Pending'));

        $package = $order->package;

        return Theme::scope('job-board.job-alert-packages.pending', compact('package', 'order', 'account'))->render();
    }
}
