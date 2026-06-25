<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\SalesAgent;
use Botble\JobBoard\Models\SalesAgentCommission;
use Illuminate\Http\Request;

class SalesAgentCommissionController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Sales Agents', route('sales-agents.index'))
            ->add('Commissions', route('sales-agent-commissions.index'));
    }

    public function index(Request $request)
    {
        $this->pageTitle('Sales Agent Commissions');

        $query = SalesAgentCommission::query()->with('salesAgent')->latest();

        if ($request->filled('sales_agent_id')) {
            $query->where('sales_agent_id', $request->integer('sales_agent_id'));
        }

        if ($request->filled('status') && in_array($request->input('status'), ['paid', 'unpaid'], true)) {
            $query->where('status', $request->input('status'));
        }

        $commissions = $query->paginate(20)->withQueryString();

        $totalUnpaid = (clone $query)->where('status', 'unpaid')->sum('commission_amount');
        $totalPaid = (clone $query)->where('status', 'paid')->sum('commission_amount');

        $agents = SalesAgent::query()->orderBy('name')->get(['id', 'name']);

        return view(
            'plugins/job-board::sales-agent-commissions.index',
            compact('commissions', 'totalUnpaid', 'totalPaid', 'agents')
        );
    }

    public function markPaid(SalesAgentCommission $salesAgentCommission): BaseHttpResponse
    {
        $salesAgentCommission->update(['status' => 'paid', 'paid_at' => now()]);

        return $this
            ->httpResponse()
            ->setMessage('Marked as paid.');
    }

    public function markUnpaid(SalesAgentCommission $salesAgentCommission): BaseHttpResponse
    {
        $salesAgentCommission->update(['status' => 'unpaid', 'paid_at' => null]);

        return $this
            ->httpResponse()
            ->setMessage('Marked as unpaid.');
    }

    public function bulkMarkPaid(Request $request): BaseHttpResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:jb_sales_agent_commissions,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $updates = [
            'status' => 'paid',
            'paid_at' => now(),
        ];

        if (trim((string) ($data['notes'] ?? '')) !== '') {
            $updates['notes'] = $data['notes'];
        }

        SalesAgentCommission::query()
            ->whereIn('id', $data['ids'])
            ->where('status', 'unpaid')
            ->update($updates);

        return $this
            ->httpResponse()
            ->setMessage('Selected commissions marked as paid.');
    }
}
