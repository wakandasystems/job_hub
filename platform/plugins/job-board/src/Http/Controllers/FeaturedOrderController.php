<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\FeaturedOrder;
use Illuminate\Http\Request;

class FeaturedOrderController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Featured Job Orders', route('featured-orders.index'));
    }

    public function index(Request $request)
    {
        $this->pageTitle('Featured Job Orders');

        $query = FeaturedOrder::query()
            ->with(['account', 'job', 'package'])
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
            'total'    => FeaturedOrder::query()->count(),
            'pending'  => FeaturedOrder::query()->where('status', 'pending')->count(),
            'approved' => FeaturedOrder::query()->where('status', 'approved')->count(),
        ];

        return view('plugins/job-board::featured-orders.index', compact('orders', 'stats'));
    }

    public function approve(FeaturedOrder $featuredOrder, BaseHttpResponse $response)
    {
        if ($featuredOrder->status !== 'pending') {
            return $response->setError()->setMessage('This order has already been processed.');
        }

        $featuredOrder->approve();

        return $response
            ->setNextUrl(route('featured-orders.index'))
            ->setMessage('Order approved — job is now featured.');
    }

    public function reject(FeaturedOrder $featuredOrder, Request $request, BaseHttpResponse $response)
    {
        if ($featuredOrder->status !== 'pending') {
            return $response->setError()->setMessage('This order has already been processed.');
        }

        $featuredOrder->update([
            'status' => 'rejected',
            'notes'  => $request->input('notes'),
        ]);

        return $response
            ->setNextUrl(route('featured-orders.index'))
            ->setMessage('Order rejected.');
    }
}
