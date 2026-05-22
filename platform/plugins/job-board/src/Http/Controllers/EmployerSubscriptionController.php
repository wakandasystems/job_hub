<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\EmployerSubscription;
use Illuminate\Http\Request;

class EmployerSubscriptionController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Employer Subscriptions', route('employer-subscriptions.index'));
    }

    public function index(Request $request)
    {
        $this->pageTitle('Employer Subscriptions');

        $query = EmployerSubscription::query()
            ->with(['account', 'package'])
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('q')) {
            $query->whereHas('account', function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(30)->withQueryString();

        $stats = [
            'total'   => EmployerSubscription::query()->count(),
            'active'  => EmployerSubscription::query()->active()->count(),
            'pending' => EmployerSubscription::query()->where('status', 'pending')->count(),
        ];

        return view('plugins/job-board::employer-subscriptions.index', compact('orders', 'stats'));
    }

    public function activate(EmployerSubscription $employerSubscription, BaseHttpResponse $response)
    {
        if ($employerSubscription->status !== 'pending') {
            return $response->setError()->setMessage('This subscription is not pending.');
        }

        $employerSubscription->activate();

        return $response
            ->setNextUrl(route('employer-subscriptions.index'))
            ->setMessage('Subscription activated.');
    }

    public function cancel(EmployerSubscription $employerSubscription, Request $request, BaseHttpResponse $response)
    {
        $employerSubscription->update([
            'status' => 'cancelled',
            'notes'  => $request->input('notes'),
        ]);

        return $response
            ->setNextUrl(route('employer-subscriptions.index'))
            ->setMessage('Subscription cancelled.');
    }
}
