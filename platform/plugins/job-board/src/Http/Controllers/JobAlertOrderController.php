<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\JobAlertOrder;
use Illuminate\Http\Request;

class JobAlertOrderController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Job Alert Orders', route('job-alert-orders.index'));
    }

    public function index(Request $request)
    {
        $this->pageTitle('Job Alert Orders');

        $query = JobAlertOrder::query()
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
            'total'    => JobAlertOrder::query()->count(),
            'pending'  => JobAlertOrder::query()->where('status', 'pending')->count(),
            'approved' => JobAlertOrder::query()->where('status', 'approved')->count(),
        ];

        return view('plugins/job-board::job-alert-orders.index', compact('orders', 'stats'));
    }

    public function approve(JobAlertOrder $jobAlertOrder, BaseHttpResponse $response)
    {
        if ($jobAlertOrder->status !== 'pending') {
            return $response
                ->setError()
                ->setMessage('This order has already been processed.');
        }

        $jobAlertOrder->approve();

        return $response
            ->setNextUrl(route('job-alert-orders.index'))
            ->setMessage('Order approved and quota credited.');
    }

    public function reject(JobAlertOrder $jobAlertOrder, Request $request, BaseHttpResponse $response)
    {
        if ($jobAlertOrder->status !== 'pending') {
            return $response
                ->setError()
                ->setMessage('This order has already been processed.');
        }

        $jobAlertOrder->update([
            'status' => 'rejected',
            'notes'  => $request->input('notes'),
        ]);

        return $response
            ->setNextUrl(route('job-alert-orders.index'))
            ->setMessage('Order rejected.');
    }
}
