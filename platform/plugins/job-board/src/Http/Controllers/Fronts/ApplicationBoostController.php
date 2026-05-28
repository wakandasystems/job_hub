<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\JobApplication;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicationBoostController extends BaseController
{
    public function store(Request $request, int $id): JsonResponse
    {
        /** @var Account $account */
        $account = auth('account')->user();

        try {
            $application = JobApplication::where('id', $id)
                ->where('account_id', $account->getKey())
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            return response()->json(['error' => true, 'message' => __('Application not found.')]);
        }

        $credits = (int) $request->input('credits', 0);

        if ($credits < 1) {
            return response()->json(['error' => true, 'message' => __('Enter at least 1 credit to boost.')]);
        }

        $deducted = DB::transaction(function () use ($account, $application, $credits): bool {
            $rows = Account::query()
                ->whereKey($account->getKey())
                ->where('credits', '>=', $credits)
                ->decrement('credits', $credits);

            if (! $rows) {
                return false;
            }

            $application->increment('boost_bid', $credits);

            return true;
        });

        if (! $deducted) {
            return response()->json(['error' => true, 'message' => __('Insufficient credits.')]);
        }

        return response()->json([
            'error'   => false,
            'message' => __('Your application has been boosted! Employers will see it at the top.'),
        ]);
    }
}
