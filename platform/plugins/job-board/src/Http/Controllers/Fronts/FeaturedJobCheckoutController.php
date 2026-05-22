<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Facades\PageTitle;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\FeaturedOrder;
use Botble\JobBoard\Models\FeaturedPackage;
use Botble\JobBoard\Models\Job;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeaturedJobCheckoutController extends BaseController
{
    public function index(Request $request)
    {
        SeoHelper::setTitle(__('Feature a Job'));
        PageTitle::setTitle(__('Feature a Job'));

        /** @var Account $account */
        $account  = auth('account')->user();
        $packages = FeaturedPackage::query()->where('is_active', true)->orderBy('sort_order')->orderBy('price')->get();

        $myOrders = FeaturedOrder::query()
            ->with(['package', 'job'])
            ->where('account_id', $account->getKey())
            ->latest()
            ->get();

        $myJobs = Job::query()
            ->where('author_id', $account->getKey())
            ->where('author_type', Account::class)
            ->whereIn('status', ['published', 'draft'])
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'created_at', 'is_featured', 'featured_until']);

        return JobBoardHelper::view('dashboard.featured-jobs', compact('account', 'packages', 'myOrders', 'myJobs'));
    }

    public function checkout(FeaturedPackage $package, Request $request)
    {
        abort_unless($package->is_active, 404);

        $jobId = $request->input('job_id');

        /** @var Account $account */
        $account = auth('account')->user();

        $job = null;
        if ($jobId) {
            $job = Job::query()
                ->where('id', $jobId)
                ->where('author_id', $account->getKey())
                ->where('author_type', Account::class)
                ->first();
        }

        abort_if(! $job, 404, 'Select a valid job from your listings.');

        $creditCost = $this->getCreditCost($package);

        $order = DB::transaction(function () use ($account, $job, $package, $creditCost): ?FeaturedOrder {
            if ($creditCost > 0) {
                $deducted = Account::query()
                    ->whereKey($account->getKey())
                    ->where('credits', '>=', $creditCost)
                    ->decrement('credits', $creditCost);

                if (! $deducted) {
                    return null;
                }
            }

            $order = FeaturedOrder::create([
                'account_id'     => $account->getKey(),
                'job_id'         => $job->getKey(),
                'package_id'     => $package->getKey(),
                'amount'         => $creditCost,
                'currency'       => 'CRD',
                'status'         => 'pending',
                'payment_method' => 'credits',
                'charge_id'      => 'credits-' . Str::uuid(),
            ]);

            $order->approve();

            return $order;
        });

        if (! $order) {
            return redirect()
                ->route('public.account.featured-jobs.index')
                ->with('error', __('You need :credits credit(s) to feature this job. Buy more credits and try again.', ['credits' => $creditCost]));
        }

        return redirect()->route('public.account.featured-jobs.thanks', ['order' => $order->id])
            ->with('success', __(':credits credit(s) deducted. Your job is now featured.', ['credits' => $creditCost]));
    }

    public function callback(FeaturedOrder $order, Request $request)
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
            return redirect()->route('public.account.featured-jobs.thanks', ['order' => $order->id]);
        }

        return redirect()->route('public.account.featured-jobs.pending', ['order' => $order->id]);
    }

    public function thanks(FeaturedOrder $order)
    {
        /** @var Account $account */
        $account = auth('account')->user();
        abort_unless($order->account_id === $account->getKey() && $order->status === 'approved', 403);

        SeoHelper::setTitle(__('Thank You!'));

        $package = $order->package;
        $job     = $order->job;

        return Theme::scope('job-board.featured-jobs.thanks', compact('package', 'order', 'account', 'job'))->render();
    }

    public function pending(FeaturedOrder $order)
    {
        /** @var Account $account */
        $account = auth('account')->user();
        abort_unless($order->account_id === $account->getKey(), 403);

        if ($order->status === 'approved') {
            return redirect()->route('public.account.featured-jobs.thanks', ['order' => $order->id]);
        }

        SeoHelper::setTitle(__('Activation Pending'));

        $package = $order->package;
        $job     = $order->job;

        return Theme::scope('job-board.featured-jobs.pending', compact('package', 'order', 'account', 'job'))->render();
    }

    protected function getCreditCost(FeaturedPackage $package): int
    {
        return max(0, (int) ceil((float) $package->price));
    }
}
