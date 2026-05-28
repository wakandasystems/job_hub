<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\WakandaVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WakandaVerificationController extends BaseController
{
    public function store(Request $request): JsonResponse
    {
        /** @var Account $account */
        $account = auth('account')->user();

        if ($account->wakanda_verified) {
            return response()->json(['error' => true, 'message' => __('You are already Wakanda Verified.')]);
        }

        $existing = WakandaVerificationRequest::where('account_id', $account->getKey())
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return response()->json(['error' => true, 'message' => __('You already have a pending verification request.')]);
        }

        $cost = (int) setting('wakanda_verification_cost', 5);

        $deducted = DB::transaction(function () use ($account, $cost): bool {
            $rows = Account::query()
                ->whereKey($account->getKey())
                ->where('credits', '>=', $cost)
                ->decrement('credits', $cost);

            if (! $rows) {
                return false;
            }

            WakandaVerificationRequest::create([
                'account_id' => $account->getKey(),
                'status'     => 'pending',
            ]);

            return true;
        });

        if (! $deducted) {
            return response()->json([
                'error'   => true,
                'message' => __('You need :cost credits to request verification. Buy more credits first.', ['cost' => $cost]),
            ]);
        }

        return response()->json([
            'error'   => false,
            'message' => __('Verification request submitted! Our team will review your profile and get back to you.'),
        ]);
    }
}
