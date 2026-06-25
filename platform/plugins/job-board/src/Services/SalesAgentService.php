<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Models\SalesAgent;
use Botble\JobBoard\Models\SalesAgentCommission;
use Botble\JobBoard\Models\SalesAgentReferral;

class SalesAgentService
{
    public function findActiveByCode(?string $code): ?SalesAgent
    {
        $code = trim((string) $code);

        if ($code === '') {
            return null;
        }

        return SalesAgent::query()
            ->where('code', strtoupper($code))
            ->where('status', 'active')
            ->first();
    }

    public function resolveAgentForPhone(?string $phone): ?SalesAgent
    {
        $phone = $this->normalizePhone($phone);

        if ($phone === '') {
            return null;
        }

        $referral = SalesAgentReferral::query()->where('phone', $phone)->first();

        if (! $referral) {
            return null;
        }

        $agent = $referral->salesAgent;

        return $agent && $agent->status === 'active' ? $agent : null;
    }

    /** Explicit code wins; otherwise fall back to the sticky phone -> agent mapping. */
    public function resolveAgent(?string $code, ?string $phone): ?SalesAgent
    {
        return $this->findActiveByCode($code) ?: $this->resolveAgentForPhone($phone);
    }

    /** @return array{0: float, 1: float} [discountedAmount, discountAmount] */
    public function applyDiscount(float $amount): array
    {
        $rate = (float) setting('sales_agent_global_discount_rate', 0);

        if ($rate <= 0) {
            return [$amount, 0.0];
        }

        $discount = round($amount * ($rate / 100), 2);

        return [round($amount - $discount, 2), $discount];
    }

    /** First use of a phone number sticks to that agent — later orders from the same phone attribute automatically, code or not. */
    public function recordReferral(
        SalesAgent $agent,
        ?string $phone,
        ?string $code,
        string $source,
        ?int $accountId = null
    ): void {
        $phone = $this->normalizePhone($phone);

        if ($phone === '') {
            return;
        }

        SalesAgentReferral::query()->firstOrCreate(
            ['phone' => $phone],
            [
                'sales_agent_id' => $agent->getKey(),
                'account_id' => $accountId,
                'code_used' => $code,
                'source' => $source,
                'first_used_at' => now(),
            ]
        );
    }

    /** One commission row per order — safe to call more than once for the same order. */
    public function creditCommission(SalesAgent $agent, string $orderType, int $orderId, float $amount, string $currency): void
    {
        $exists = SalesAgentCommission::query()
            ->where('order_type', $orderType)
            ->where('order_id', $orderId)
            ->exists();

        if ($exists) {
            return;
        }

        $rate = (float) $agent->commission_rate;

        SalesAgentCommission::query()->create([
            'sales_agent_id' => $agent->getKey(),
            'order_type' => $orderType,
            'order_id' => $orderId,
            'amount' => $amount,
            'commission_rate' => $rate,
            'commission_amount' => round($amount * ($rate / 100), 2),
            'currency' => $currency,
            'status' => 'unpaid',
        ]);
    }

    public function normalizePhone(?string $phone): string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return '';
        }

        return (string) preg_replace('/[^0-9+]/', '', $phone);
    }
}
