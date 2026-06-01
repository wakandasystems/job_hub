<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\WakandaVerificationRequest;
use Botble\SeoHelper\Facades\SeoHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WakandaVerificationController extends BaseController
{
    public function checkout()
    {
        /** @var Account $account */
        $account = auth('account')->user();

        if ($account->wakanda_verified) {
            return redirect()->route('public.account.settings')
                ->with('success_msg', __('You are already Wakanda Verified.'));
        }

        $hasPending = WakandaVerificationRequest::where('account_id', $account->getKey())
            ->whereIn('status', ['pending', 'pending_payment'])
            ->exists();

        if ($hasPending) {
            return redirect()->route('public.account.settings')
                ->with('success_msg', __('Your verification request is already under review.'));
        }

        $cost = (int) setting('wakanda_verification_cost', 5);

        SeoHelper::setTitle(__('Wakanda Verification'));

        return view(
            'plugins/job-board::themes.dashboard.wakanda-verification.checkout',
            compact('account', 'cost')
        );
    }

    public function store(Request $request)
    {
        /** @var Account $account */
        $account = auth('account')->user();

        if ($account->wakanda_verified) {
            return redirect()->route('public.account.settings')
                ->with('success_msg', __('You are already Wakanda Verified.'));
        }

        $hasPending = WakandaVerificationRequest::where('account_id', $account->getKey())
            ->whereIn('status', ['pending', 'pending_payment'])
            ->exists();

        if ($hasPending) {
            return redirect()->route('public.account.settings')
                ->with('success_msg', __('Your verification request is already under review.'));
        }

        $cost = (int) setting('wakanda_verification_cost', 5);

        $verificationRequest = null;

        $deducted = DB::transaction(function () use ($account, $cost, &$verificationRequest): bool {
            $rows = Account::query()
                ->whereKey($account->getKey())
                ->where('credits', '>=', $cost)
                ->decrement('credits', $cost);

            if (! $rows) {
                return false;
            }

            $verificationRequest = WakandaVerificationRequest::create([
                'account_id' => $account->getKey(),
                'status'     => 'pending',
            ]);

            return true;
        });

        if (! $deducted) {
            return redirect()->route('public.account.wakanda-verification.checkout')
                ->with('error_msg', __('You need :cost credits to request verification. Please buy more credits first.', ['cost' => $cost]));
        }

        $verificationRequest?->notifyAdminPendingReview($cost);

        return redirect()->route('public.account.settings')
            ->with('success_msg', __('Verification request submitted! Our team will review your profile and contact you.'));
    }
}
