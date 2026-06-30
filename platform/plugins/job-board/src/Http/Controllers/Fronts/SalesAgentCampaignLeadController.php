<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\SalesAgent;
use Botble\JobBoard\Models\SalesAgentCampaign;
use Botble\JobBoard\Models\SalesAgentCampaignClick;
use Botble\JobBoard\Models\SalesAgentCampaignLead;
use Botble\JobBoard\Services\CandidateAlertAccountSyncService;
use Botble\JobBoard\Services\SalesAgentService;
use Botble\JobBoard\Services\WhapiSenderService;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\SeoHelper\SeoOpenGraph;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SalesAgentCampaignLeadController extends BaseController
{
    private const ADMIN_PHONE = '+260965480394';

    public function show(Request $request, string $agentCode, SalesAgentCampaign $salesAgentCampaign)
    {
        $campaign = $salesAgentCampaign;

        $agent = SalesAgent::query()
            ->where('code', strtoupper(trim($agentCode)))
            ->where('status', 'active')
            ->firstOrFail();

        abort_unless($campaign->is_active, 404);

        SalesAgentCampaignClick::query()->create([
            'sales_agent_id' => $agent->getKey(),
            'campaign_id' => $campaign->getKey(),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 512) ?: null,
            'referer' => mb_substr((string) $request->headers->get('referer'), 0, 2048) ?: null,
        ]);

        SeoHelper::setTitle($campaign->name . ' - ' . $agent->name);

        $marketingImage = $campaign->marketingImages()
            ->where('sales_agent_id', $agent->getKey())
            ->where('status', 'completed')
            ->whereNotNull('image_path')
            ->latest()
            ->first()
            ?? $campaign->latestCompletedMarketingImage()->first();

        if ($marketingImage && $marketingImage->imageUrl()) {
            $meta = new SeoOpenGraph();
            $meta->setTitle($campaign->name . ' - ' . $agent->name);
            $meta->setImage($marketingImage->imageUrl());
            $meta->setType('website');
            SeoHelper::setSeoOpenGraph($meta);
        }

        return Theme::scope('job-board.sales-agent-campaigns.landing', compact('agent', 'campaign', 'marketingImage'))->render();
    }

    public function store(string $agentCode, SalesAgentCampaign $salesAgentCampaign, Request $request): RedirectResponse
    {
        $campaign = $salesAgentCampaign;

        $agent = SalesAgent::query()
            ->where('code', strtoupper(trim($agentCode)))
            ->where('status', 'active')
            ->firstOrFail();

        abort_unless($campaign->is_active, 404);

        $data = $request->validate([
            'candidate_name' => ['required', 'string', 'max:150'],
            'candidate_phone' => ['required', 'string', 'max:40'],
            'candidate_email' => ['nullable', 'email', 'max:150'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'confirm_campaign' => ['accepted'],
        ]);

        $account = app(CandidateAlertAccountSyncService::class)->resolveAccount(
            $data['candidate_email'] ?? null,
            $data['candidate_phone']
        );

        $lead = SalesAgentCampaignLead::query()->create([
            'sales_agent_id' => $agent->getKey(),
            'campaign_id' => $campaign->getKey(),
            'account_id' => $account?->getKey(),
            'sales_agent_code' => $agent->code,
            'candidate_name' => trim((string) $data['candidate_name']),
            'candidate_phone' => trim((string) $data['candidate_phone']),
            'candidate_email' => trim((string) ($data['candidate_email'] ?? '')) ?: null,
            'status' => 'pending',
            'product_type' => $campaign->product_type ?: 'auto_apply',
            'product_label' => $campaign->resolvedProductLabel(),
            'promo_price' => $campaign->promo_price,
            'promo_original_price' => $campaign->promo_original_price,
            'customer_notes' => trim((string) ($data['customer_notes'] ?? '')) ?: null,
            'public_token' => (string) Str::uuid(),
        ]);

        app(SalesAgentService::class)->recordReferral(
            $agent,
            $lead->candidate_phone,
            $agent->code,
            'camp_' . $lead->product_type,
            $lead->account_id
        );

        $this->notifyAdmin($lead);
        $this->notifyAgent($lead, $agent);

        return redirect()->route('public.sales-agent-campaigns.thanks', $lead->public_token);
    }

    public function thanks(string $token)
    {
        $lead = SalesAgentCampaignLead::query()
            ->with(['salesAgent', 'campaign'])
            ->where('public_token', $token)
            ->firstOrFail();

        SeoHelper::setTitle('Request Received');

        return Theme::scope('job-board.sales-agent-campaigns.thanks', compact('lead'))->render();
    }

    private function notifyAdmin(SalesAgentCampaignLead $lead): void
    {
        $lead->loadMissing(['salesAgent', 'campaign']);

        $lines = [
            'New sales campaign lead',
            '',
            'Lead ID: #' . $lead->getKey(),
            'Name: ' . $lead->candidate_name,
            'Phone: ' . $lead->candidate_phone,
            'Email: ' . ($lead->candidate_email ?: 'None'),
            'Product: ' . $lead->resolvedProductLabel(),
            'Campaign: ' . ($lead->campaign?->name ?: 'Unknown'),
            'Agent: ' . ($lead->salesAgent?->name ?: 'Unknown') . ' (' . $lead->sales_agent_code . ')',
            'Promo: ' . ($lead->promo_price ?: 'N/A') . ($lead->promo_original_price ? ' from ' . $lead->promo_original_price : ''),
        ];

        if ($lead->customer_notes) {
            $lines[] = 'Notes: ' . $lead->customer_notes;
        }

        $lines[] = '';
        $lines[] = 'Open admin: ' . route('sales-agent-leads.show', $lead->getKey());

        $errorMessage = null;

        if (app(WhapiSenderService::class)->sendText(self::ADMIN_PHONE, implode("\n", $lines), $errorMessage)) {
            $lead->forceFill(['notified_admin_at' => now()])->save();
            return;
        }

        Log::warning('SalesAgentLead: failed to notify admin by WhatsApp', [
            'lead_id' => $lead->getKey(),
            'error' => $errorMessage ?: 'Unknown WhatsApp failure',
        ]);
    }

    private function notifyAgent(SalesAgentCampaignLead $lead, SalesAgent $agent): void
    {
        $agentPhone = trim((string) ($agent->phone ?? ''));

        if ($agentPhone === '') {
            return;
        }

        $lines = [
            'Hi ' . $agent->name . '! 👋',
            '',
            '*New lead from your campaign link:*',
            '',
            '*Name:* ' . $lead->candidate_name,
            '*Phone:* ' . $lead->candidate_phone,
            '*Product:* ' . $lead->resolvedProductLabel(),
            '*Campaign:* ' . ($lead->campaign?->name ?: 'Unknown'),
        ];

        if ($lead->promo_price) {
            $lines[] = '*Promo price:* ' . $lead->promo_price;
        }

        if ($lead->customer_notes) {
            $lines[] = '*Their notes:* ' . $lead->customer_notes;
        }

        $lines[] = '';
        $lines[] = 'Please follow up with them to confirm payment and get them onboarded. 🙏';

        $errorMessage = null;

        if (! app(WhapiSenderService::class)->sendText($agentPhone, implode("\n", $lines), $errorMessage)) {
            Log::warning('SalesAgentLead: failed to notify agent by WhatsApp', [
                'lead_id' => $lead->getKey(),
                'agent_id' => $agent->getKey(),
                'error' => $errorMessage ?: 'Unknown WhatsApp failure',
            ]);
        }
    }
}
