<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Supports\CvRevealService;
use Illuminate\Http\JsonResponse;

class CvRevealController extends BaseController
{
    public function __construct(protected CvRevealService $revealService)
    {
    }

    public function reveal(int|string $candidateId): JsonResponse
    {
        /** @var Account $employer */
        $employer  = auth('account')->user();
        $candidate = Account::query()
            ->where('id', $candidateId)
            ->where('type', 'job_seeker')
            ->where('is_public_profile', true)
            ->firstOrFail();

        if ($this->revealService->hasRevealed($employer, $candidate)) {
            return response()->json([
                'success' => true,
                'already_revealed' => true,
                'phone'   => $candidate->phone,
                'email'   => $candidate->email,
            ]);
        }

        $check = $this->revealService->canReveal($employer);

        if (! $check['can']) {
            $cost = $check['cost'];
            return response()->json([
                'success' => false,
                'message' => $cost > 0
                    ? __('You need :cost credit(s) to reveal this contact. <a href=":url">Buy credits</a> or <a href=":sub">subscribe</a>.', [
                        'cost' => $cost,
                        'url'  => route('public.account.credits'),
                        'sub'  => route('public.account.subscription.index'),
                    ])
                    : __('A subscription is required to reveal candidate contacts. <a href=":url">View plans</a>.', [
                        'url' => route('public.account.subscription.index'),
                    ]),
            ], 403);
        }

        $reveal = $this->revealService->reveal($employer, $candidate);

        if (! $reveal) {
            return response()->json(['success' => false, 'message' => __('Could not process reveal.')], 500);
        }

        return response()->json([
            'success'  => true,
            'phone'    => $candidate->phone,
            'email'    => $candidate->email,
            'cv_url'   => (! $candidate->hide_cv && $candidate->resume) ? $candidate->resumeDownloadUrl : null,
            'cost'     => $check['cost'],
            'type'     => $check['reason'],
        ]);
    }
}
