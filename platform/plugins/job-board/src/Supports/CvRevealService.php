<?php

namespace Botble\JobBoard\Supports;

use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\CvReveal;
use Botble\JobBoard\Supports\SubscriptionService;

class CvRevealService
{
    /**
     * True when the employer's active subscription grants unlimited candidate access.
     */
    public function hasSubscriptionAccess(Account $employer): bool
    {
        $sub = app(SubscriptionService::class)->getActiveSubscription($employer);
        return $sub !== null && (bool) $sub->package?->can_search_candidates;
    }

    public function hasRevealed(Account $employer, Account $candidate): bool
    {
        return CvReveal::query()
            ->where('employer_id', $employer->getKey())
            ->where('candidate_id', $candidate->getKey())
            ->exists();
    }

    /**
     * Check if employer can reveal a candidate contact.
     * Returns ['can' => bool, 'reason' => string, 'cost' => int]
     */
    public function canReveal(Account $employer): array
    {
        // Active subscription with candidate search access → unlimited free reveals
        if ($this->hasSubscriptionAccess($employer)) {
            return ['can' => true, 'reason' => 'subscription', 'cost' => 0];
        }

        // Credits-based reveal
        $cost = (int) setting('cv_reveal_credit_cost', 1);
        if ($cost > 0 && $employer->credits >= $cost) {
            return ['can' => true, 'reason' => 'credits', 'cost' => $cost];
        }

        if ($cost === 0) {
            return ['can' => true, 'reason' => 'free', 'cost' => 0];
        }

        return ['can' => false, 'reason' => 'no_access', 'cost' => $cost];
    }

    /**
     * Record a reveal and deduct credits if applicable.
     * Returns the CvReveal record or null on failure.
     */
    public function reveal(Account $employer, Account $candidate): ?CvReveal
    {
        if ($this->hasRevealed($employer, $candidate)) {
            return CvReveal::query()
                ->where('employer_id', $employer->getKey())
                ->where('candidate_id', $candidate->getKey())
                ->first();
        }

        $check = $this->canReveal($employer);
        if (! $check['can']) {
            return null;
        }

        $revealType = $check['reason'];
        $cost       = $check['cost'];

        if ($revealType === 'credits' && $cost > 0) {
            $employer->decrement('credits', $cost);
        }

        return CvReveal::query()->create([
            'employer_id'     => $employer->getKey(),
            'candidate_id'    => $candidate->getKey(),
            'reveal_type'     => $revealType,
            'amount_charged'  => 0,
        ]);
    }
}
