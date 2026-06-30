<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\SalesAgentCampaignLead;
use Botble\JobBoard\Services\CandidateAlertAccountSyncService;
use Botble\JobBoard\Services\SalesAgentService;
use Botble\JobBoard\Services\WhapiSenderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SalesAgentCampaignLeadController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Sales Agents', route('sales-agents.index'))
            ->add('Lead Requests', route('sales-agent-leads.index'));
    }

    public function index(Request $request)
    {
        $this->pageTitle('Sales Agent Lead Requests');

        $query = SalesAgentCampaignLead::query()
            ->with(['salesAgent', 'campaign', 'account'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('product_type')) {
            $query->where('product_type', $request->input('product_type'));
        }

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));
            $query->where(function ($builder) use ($keyword): void {
                $builder->where('candidate_name', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('candidate_phone', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('candidate_email', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('sales_agent_code', 'LIKE', '%' . $keyword . '%');
            });
        }

        $leads = $query->paginate(25)->withQueryString();
        $statuses = SalesAgentCampaignLead::statuses();

        return view('plugins/job-board::sales-agent-leads.index', compact('leads', 'statuses'));
    }

    public function show(SalesAgentCampaignLead $salesAgentLead)
    {
        $this->pageTitle('Lead Request #' . $salesAgentLead->getKey());

        $lead = $salesAgentLead->load(['salesAgent', 'campaign', 'account']);
        $statuses = SalesAgentCampaignLead::statuses();

        return view('plugins/job-board::sales-agent-leads.show', compact('lead', 'statuses'));
    }

    private function notifyAgentOfStatusChange(SalesAgentCampaignLead $lead, string $from, string $to): void
    {
        $agent = $lead->salesAgent;

        if (! $agent || ! $agent->phone) {
            return;
        }

        $name = $lead->candidate_name;
        $product = $lead->resolvedProductLabel();
        $promo = $lead->promo_price ? " (K{$lead->promo_price})" : '';

        $message = match ($to) {
            'contacted' => "Hi {$agent->name},\n\nUpdate on your lead *{$name}*:\nWe have contacted them about the *{$product}*{$promo} offer you shared. We will keep you posted.",
            'paid'      => "Hi {$agent->name},\n\nGreat news! *{$name}* has paid for *{$product}*{$promo} — a lead you referred.\n\nYour commission will be processed shortly. Thank you!",
            'onboarded' => "Hi {$agent->name},\n\n*{$name}* has been fully onboarded for *{$product}*{$promo}. Another successful referral from you!\n\nKeep sharing your link and earning. 💪",
            'rejected'  => "Hi {$agent->name},\n\nUnfortunately the lead *{$name}* for *{$product}* did not proceed. No commission will be issued for this one.\n\nKeep going — your next referral could be the one!",
            default     => null,
        };

        if ($message === null) {
            return;
        }

        $errorMessage = null;

        if (! app(WhapiSenderService::class)->sendText($agent->phone, $message, $errorMessage)) {
            Log::warning('SalesAgentLead: failed to notify agent of status change', [
                'lead_id' => $lead->getKey(),
                'agent_id' => $agent->getKey(),
                'status' => $to,
                'error' => $errorMessage,
            ]);
        }
    }

    public function update(SalesAgentCampaignLead $salesAgentLead, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(SalesAgentCampaignLead::statuses()))],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $lead = $salesAgentLead->loadMissing(['salesAgent', 'account']);
        $lead->status = $data['status'];
        $lead->admin_notes = trim((string) ($data['admin_notes'] ?? '')) ?: null;

        if ($lead->account_id === null) {
            $lead->account_id = app(CandidateAlertAccountSyncService::class)
                ->resolveAccount($lead->candidate_email, $lead->candidate_phone)?->getKey();
        }

        if ($data['status'] === 'contacted' && ! $lead->contacted_at) {
            $lead->contacted_at = now();
        }

        if ($data['status'] === 'paid' && ! $lead->paid_at) {
            $lead->paid_at = now();
        }

        if ($data['status'] === 'onboarded' && ! $lead->onboarded_at) {
            $lead->onboarded_at = now();
        }

        if ($data['status'] === 'rejected' && ! $lead->rejected_at) {
            $lead->rejected_at = now();
        }

        $previousStatus = $salesAgentLead->getOriginal('status');
        $lead->save();

        if ($data['status'] !== $previousStatus) {
            $this->notifyAgentOfStatusChange($lead, $previousStatus, $data['status']);
        }

        app(SalesAgentService::class)->recordReferral(
            $lead->salesAgent,
            $lead->candidate_phone,
            $lead->sales_agent_code,
            'camp_' . $lead->product_type,
            $lead->account_id
        );

        return redirect()
            ->route('sales-agent-leads.show', $lead->getKey())
            ->with('success_msg', 'Lead request updated.');
    }
}
