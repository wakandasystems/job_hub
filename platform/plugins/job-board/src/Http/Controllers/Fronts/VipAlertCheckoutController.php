<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\VipAlertOrder;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VipAlertCheckoutController extends BaseController
{
    public function plans()
    {
        SeoHelper::setTitle('VIP WhatsApp Job Alerts');
        $plans = VipAlertOrder::plans();

        return Theme::scope('job-board.vip-alerts.plans', compact('plans'))->render();
    }

    public function checkout(string $plan)
    {
        $planData = VipAlertOrder::plan($plan);
        abort_unless($planData, 404);

        SeoHelper::setTitle('VIP Alerts — Enter Your Details');

        return Theme::scope('job-board.vip-alerts.checkout', compact('plan', 'planData'))->render();
    }

    public function prepareCheckout(string $plan, Request $request): RedirectResponse
    {
        $planData = VipAlertOrder::plan($plan);
        abort_unless($planData, 404);

        $data = $request->validate([
            'candidate_name'  => ['required', 'string', 'max:100'],
            'candidate_phone' => ['required', 'string', 'max:30'],
            'candidate_email' => ['required', 'email', 'max:150'],
            'filters'         => ['nullable', 'array'],
            'whatsapp_consent' => ['accepted'],
        ]);

        $order = VipAlertOrder::create([
            'candidate_name'  => $data['candidate_name'],
            'candidate_phone' => $data['candidate_phone'],
            'candidate_email' => $data['candidate_email'],
            'plan'            => $plan,
            'duration_days'   => $planData['duration_days'],
            'amount'          => $planData['price'],
            'currency'        => $planData['currency'],
            'payment_status'  => 'pending',
            'admin_status'    => 'pending',
            'filters'         => $this->cleanFilters($data['filters'] ?? []),
        ]);

        return redirect()->route('public.vip-alerts.pay', ['token' => $order->public_token]);
    }

    public function pay(string $token)
    {
        $order = $this->findOrder($token);
        abort_unless($order->payment_status === 'pending', 404);

        SeoHelper::setTitle('VIP Alerts — Complete Payment');

        $planData    = VipAlertOrder::plan($order->plan, includeDisabled: true);
        $amount      = (float) $order->amount;
        $currency    = $order->currency;
        $name        = 'Wakanda Jobs VIP Alerts — ' . ($planData['label'] ?? $order->plan);
        $callbackUrl = route('public.vip-alerts.callback', ['token' => $order->public_token]);
        $returnUrl   = route('public.vip-alerts.plans');

        session([
            'vip_alert_order_id'     => $order->id,
            'vip_alert_callback_url' => $callbackUrl,
            'vip_alert_return_url'   => $returnUrl,
        ]);

        return Theme::scope('job-board.vip-alerts.pay', compact(
            'order', 'planData', 'amount', 'currency', 'name', 'callbackUrl', 'returnUrl'
        ))->render();
    }

    public function callback(string $token, Request $request)
    {
        $order = $this->findOrder($token);
        $chargeId = $request->input('charge_id') ?: $order->charge_id;

        if ($chargeId && ! $order->charge_id) {
            $order->update(['charge_id' => $chargeId]);
        }

        $order->refresh();

        return redirect()->route('public.vip-alerts.pending', ['token' => $order->public_token]);
    }

    public function pending(string $token)
    {
        $order = $this->findOrder($token);
        SeoHelper::setTitle('VIP Alerts — Order Received');
        $planData = VipAlertOrder::plan($order->plan, includeDisabled: true);

        return Theme::scope('job-board.vip-alerts.pending', compact('order', 'planData'))->render();
    }

    private function findOrder(string $token): VipAlertOrder
    {
        abort_unless(strlen($token) === 64, 404);

        return VipAlertOrder::query()->where('public_token', $token)->firstOrFail();
    }

    private function cleanFilters(array $filters): array
    {
        $clean = [];

        $keywords = array_values(array_filter(array_map('trim', (array) ($filters['keywords'] ?? []))));
        if ($keywords) {
            $clean['keywords'] = $keywords;
        }

        if (! empty($filters['country_ids'])) {
            $ids = array_values(array_filter(array_map('intval', (array) $filters['country_ids'])));
            if ($ids) {
                $clean['country_ids'] = $ids;
            }
        }

        return $clean;
    }
}
