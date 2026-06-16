<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\AdOrder;
use Illuminate\Http\Request;

class AdOrderController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Ad Requests', route('ad-orders.index'));
    }

    public function index(Request $request)
    {
        $this->pageTitle('Ad Requests');

        $query = AdOrder::query()
            ->with(['account', 'placement', 'tier'])
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
            'total'    => AdOrder::query()->count(),
            'pending'  => AdOrder::query()->where('status', 'pending')->count(),
            'approved' => AdOrder::query()->where('status', 'approved')->count(),
        ];

        return view('plugins/job-board::ad-orders.index', compact('orders', 'stats'));
    }

    public function approve(AdOrder $adOrder, BaseHttpResponse $response)
    {
        if ($adOrder->status !== 'pending') {
            return $response->setError()->setMessage('This request has already been processed.');
        }

        $adOrder->approve();

        return $response
            ->setNextUrl(route('ad-orders.index'))
            ->setMessage('Ad request approved — the ad is now live.');
    }

    public function reject(AdOrder $adOrder, Request $request, BaseHttpResponse $response)
    {
        if ($adOrder->status !== 'pending') {
            return $response->setError()->setMessage('This request has already been processed.');
        }

        $adOrder->update([
            'status' => 'rejected',
            'notes'  => $request->input('notes'),
        ]);

        return $response
            ->setNextUrl(route('ad-orders.index'))
            ->setMessage('Ad request rejected.');
    }
}
