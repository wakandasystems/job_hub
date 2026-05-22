<?php

namespace Botble\JobBoard\Supports;

use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\EmployerSubscription;

class SubscriptionService
{
    public function getActiveSubscription(Account $account): ?EmployerSubscription
    {
        return EmployerSubscription::query()
            ->with('package')
            ->where('account_id', $account->getKey())
            ->active()
            ->latest('started_at')
            ->first();
    }

    public function canSearchCandidates(Account $account): bool
    {
        $sub = $this->getActiveSubscription($account);
        return $sub !== null && (bool) $sub->package?->can_search_candidates;
    }

    public function isRecruiterPlan(Account $account): bool
    {
        $sub = $this->getActiveSubscription($account);
        return $sub !== null && (bool) $sub->package?->is_recruiter_plan;
    }

    public function getRemainingPosts(Account $account): int|string
    {
        $sub = $this->getActiveSubscription($account);

        if (! $sub) {
            return 0;
        }

        $limit = (int) ($sub->package?->posts_per_cycle ?? 0);

        if ($limit === 0) {
            return 'unlimited';
        }

        return max(0, $limit - $sub->posts_used_this_cycle);
    }

    public function canPostJob(Account $account): bool
    {
        $remaining = $this->getRemainingPosts($account);
        return $remaining === 'unlimited' || $remaining > 0;
    }

    public function incrementPostsUsed(Account $account): void
    {
        $sub = $this->getActiveSubscription($account);

        if ($sub) {
            $sub->increment('posts_used_this_cycle');
        }
    }

    public function hasActiveSubscription(Account $account): bool
    {
        return $this->getActiveSubscription($account) !== null;
    }
}
