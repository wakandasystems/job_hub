<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\VipAlertOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class VipAlertOrderController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('VIP Alert Orders', route('vip-alert-orders.index'));
    }

    public function index(Request $request)
    {
        $this->pageTitle('VIP Alert Orders');

        $query = VipAlertOrder::query()->latest();

        if ($status = $request->query('status')) {
            $query->where('admin_status', $status);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search): void {
                $q->where('candidate_name', 'like', "%{$search}%")
                  ->orWhere('candidate_email', 'like', "%{$search}%")
                  ->orWhere('candidate_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(30)->withQueryString();

        $stats = [
            'total'    => VipAlertOrder::count(),
            'pending'  => VipAlertOrder::where('admin_status', 'pending')->count(),
            'approved' => VipAlertOrder::where('admin_status', 'approved')->count(),
        ];

        return view('plugins/job-board::vip-alert-orders.index', compact('orders', 'stats'));
    }

    public function approve(VipAlertOrder $vipAlertOrder, BaseHttpResponse $response)
    {
        if ($vipAlertOrder->admin_status !== 'pending') {
            return $response->setError()->setMessage('This order has already been processed.');
        }

        $vipAlertOrder->approve();
        $this->sendConfirmationEmail($vipAlertOrder->fresh());

        return $response
            ->setNextUrl(route('vip-alert-orders.index'))
            ->setMessage('VIP Alert activated. Welcome message sent via WhatsApp.');
    }

    public function reject(VipAlertOrder $vipAlertOrder, Request $request, BaseHttpResponse $response)
    {
        if ($vipAlertOrder->admin_status !== 'pending') {
            return $response->setError()->setMessage('This order has already been processed.');
        }

        $vipAlertOrder->update([
            'admin_status' => 'rejected',
            'notes'        => $request->input('notes'),
        ]);

        return $response
            ->setNextUrl(route('vip-alert-orders.index'))
            ->setMessage('Order rejected.');
    }

    private function sendConfirmationEmail(VipAlertOrder $order): void
    {
        if (! $order->candidate_email) {
            return;
        }

        $plan = VipAlertOrder::plan($order->plan, includeDisabled: true)
            ?? ['label' => $order->duration_days . ' days'];

        try {
            Mail::raw(
                "Hi {$order->candidate_name},\n\n" .
                "Your Wakanda Jobs VIP Alert subscription is now active!\n\n" .
                "Plan: {$plan['label']}\n" .
                "Expires: " . now()->addDays($order->duration_days)->format('d M Y') . "\n\n" .
                "You will start receiving matching job alerts on WhatsApp at {$order->candidate_phone}.\n\n" .
                "Wakanda Jobs — wakandajobs.com",
                function ($msg) use ($order, $plan): void {
                    $msg->to($order->candidate_email, $order->candidate_name)
                        ->subject("Your VIP Job Alert is Active — {$plan['label']}");
                }
            );
        } catch (\Throwable) {
        }
    }
}
