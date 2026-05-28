<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\TalentUnlock;
use Botble\SeoHelper\Facades\SeoHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TalentPoolController extends BaseController
{
    public function index(Request $request)
    {
        SeoHelper::setTitle(__('Talent Pool'));
        PageTitle::setTitle(__('Talent Pool'));

        /** @var Account $employer */
        $employer = auth('account')->user();

        $unlockedIds = TalentUnlock::where('employer_account_id', $employer->getKey())
            ->pluck('candidate_account_id')
            ->all();

        $candidates = Account::query()
            ->where('type', AccountTypeEnum::JOB_SEEKER)
            ->where('wakanda_verified', true)
            ->orderByDesc('wakanda_score')
            ->paginate(20);

        $unlockCost = (int) setting('wakanda_unlock_cost', 20);

        return JobBoardHelper::view(
            'dashboard.talent-pool',
            compact('candidates', 'unlockedIds', 'unlockCost', 'employer')
        );
    }

    public function unlock(Request $request, int $candidateId): JsonResponse
    {
        /** @var Account $employer */
        $employer = auth('account')->user();

        if (TalentUnlock::where('employer_account_id', $employer->getKey())
            ->where('candidate_account_id', $candidateId)
            ->exists()) {
            return response()->json(['error' => true, 'message' => __('Profile already unlocked.')]);
        }

        $candidate = Account::where('id', $candidateId)
            ->where('wakanda_verified', true)
            ->first();

        if (! $candidate) {
            return response()->json(['error' => true, 'message' => __('Candidate not found.')]);
        }

        $cost = (int) setting('wakanda_unlock_cost', 20);

        $deducted = DB::transaction(function () use ($employer, $candidateId, $cost): bool {
            $rows = Account::query()
                ->whereKey($employer->getKey())
                ->where('credits', '>=', $cost)
                ->decrement('credits', $cost);

            if (! $rows) {
                return false;
            }

            TalentUnlock::create([
                'employer_account_id'  => $employer->getKey(),
                'candidate_account_id' => $candidateId,
                'credits_spent'        => $cost,
            ]);

            return true;
        });

        if (! $deducted) {
            return response()->json([
                'error'   => true,
                'message' => __('You need :cost credits to unlock this profile.', ['cost' => $cost]),
            ]);
        }

        $candidate->load('country');

        return response()->json([
            'error'   => false,
            'message' => __('Profile unlocked!'),
            'data'    => [
                'name'        => $candidate->name,
                'email'       => $candidate->email,
                'phone'       => $candidate->phone,
                'country'     => $candidate->country->name ?? '',
                'experience'  => $candidate->experience_years,
                'bio'         => $candidate->bio,
                'resume_url'  => $candidate->resume_url,
                'score'       => $candidate->wakanda_score,
            ],
        ]);
    }
}
