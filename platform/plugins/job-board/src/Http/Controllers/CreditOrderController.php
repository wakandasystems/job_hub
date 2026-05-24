<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\CreditOrder;
use Illuminate\Http\Request;

class CreditOrderController extends BaseController
{
    public function index(Request $request)
    {
        $query = CreditOrder::with(['account', 'package'])->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('q')) {
            $query->whereHas('account', function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(30)->withQueryString();

        $stats = [
            'total'    => CreditOrder::count(),
            'pending'  => CreditOrder::where('status', 'pending')->count(),
            'approved' => CreditOrder::where('status', 'approved')->count(),
        ];

        return view('plugins/job-board::credit-orders.index', compact('orders', 'stats'));
    }

    public function approve(CreditOrder $order)
    {
        abort_unless($order->status === 'pending', 422, 'Order is not pending.');

        $order->approve();

        return redirect()->back()->with('success', 'Credit order approved and credits awarded.');
    }

    public function reject(Request $request, CreditOrder $order)
    {
        abort_unless($order->status === 'pending', 422, 'Order is not pending.');

        $order->reject($request->input('notes', ''));

        return redirect()->back()->with('success', 'Credit order rejected.');
    }
}
