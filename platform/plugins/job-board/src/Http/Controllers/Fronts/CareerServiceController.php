<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\CareerServiceOrder;
use Botble\Payment\Services\Gateways\BankTransferPaymentService;
use Botble\Payment\Services\Gateways\CodPaymentService;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class CareerServiceController extends BaseController
{
    public function getCheckout(string $serviceType, Request $request)
    {
        $services = CareerServiceOrder::services();

        abort_unless(array_key_exists($serviceType, $services), 404);

        $service = $services[$serviceType];

        $candidate = null;
        if ($slug = $request->query('candidate')) {
            $candidate = Account::where('slug', $slug)->first();
        }

        SeoHelper::setTitle('Book: ' . $service['name']);

        $currency = strtoupper(cms_currency()->getDefaultCurrency()->title ?? 'USD');
        $orderId = null;

        // Create a pending order so we have an ID for the callback URL
        $order = CareerServiceOrder::create([
            'service_type'  => $serviceType,
            'service_name'  => $service['name'],
            'amount'        => $service['price'],
            'currency'      => $currency,
            'customer_name' => auth('account')->user()?->name ?? '',
            'customer_email'=> auth('account')->user()?->email ?? '',
            'candidate_id'  => $candidate?->id,
            'status'        => 'pending',
        ]);

        $callbackUrl = route('public.career-service.callback', ['order' => $order->id]);
        $returnUrl   = route('public.career-service.checkout', ['service' => $serviceType]);

        return view(Theme::getThemeNamespace('views.job-board.career-services.checkout'), compact(
            'service', 'serviceType', 'order', 'candidate', 'callbackUrl', 'returnUrl', 'currency'
        ));
    }

    public function getCallback(int $orderId, Request $request)
    {
        $order = CareerServiceOrder::findOrFail($orderId);

        if ($order->status === 'paid') {
            return redirect()->route('public.career-service.thanks', ['order' => $order->id]);
        }

        $chargeId = $request->input('charge_id');

        if (! $chargeId) {
            return redirect()->back()->with('error_msg', __('Payment could not be verified. Please try again.'));
        }

        $order->update([
            'charge_id'      => $chargeId,
            'payment_method' => $request->input('type'),
            'customer_name'  => $request->input('customer_name', $order->customer_name),
            'customer_email' => $request->input('customer_email', $order->customer_email),
            'customer_phone' => $request->input('customer_phone', $order->customer_phone),
            'status'         => 'paid',
        ]);

        $this->sendConfirmationEmail($order);

        return redirect()->route('public.career-service.thanks', ['order' => $order->id]);
    }

    public function getThanks(int $orderId)
    {
        $order = CareerServiceOrder::findOrFail($orderId);

        SeoHelper::setTitle(__('Booking Confirmed'));

        return view(Theme::getThemeNamespace('views.job-board.career-services.thanks'), compact('order'));
    }

    protected function sendConfirmationEmail(CareerServiceOrder $order): void
    {
        $adminEmail = setting('admin_email') ?: config('mail.from.address');
        if (! $adminEmail) return;

        try {
            Mail::raw(
                "New Career Service Order\n\n" .
                "Service: {$order->service_name}\n" .
                "Amount: {$order->currency} {$order->amount}\n" .
                "Customer: {$order->customer_name} ({$order->customer_email})\n" .
                "Phone: {$order->customer_phone}\n" .
                "Payment: {$order->payment_method} — {$order->charge_id}\n",
                function ($msg) use ($adminEmail, $order) {
                    $msg->to($adminEmail)
                        ->subject("Career Service Booked: {$order->service_name}");
                }
            );
        } catch (\Throwable) {
            // Non-fatal — log silently
        }
    }
}
