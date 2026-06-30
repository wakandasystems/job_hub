<?php

namespace Tests\Feature;

use Botble\Base\Facades\BaseHelper;
use Botble\ACL\Models\User;
use Botble\ACL\Services\ActivateUserService;
use Botble\JobBoard\Jobs\SendSalesAgentCampaignLinkJob;
use Botble\JobBoard\Models\SalesAgent;
use Botble\JobBoard\Models\SalesAgentCampaign;
use Botble\JobBoard\Models\SalesAgentCampaignClick;
use Botble\JobBoard\Models\SalesAgentCampaignLead;
use Botble\JobBoard\Models\SalesAgentReferral;
use Botble\JobBoard\Services\CandidateAlertAccountSyncService;
use Botble\JobBoard\Services\WhapiSenderService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalesAgentCampaignFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_public_campaign_lead_flow_and_admin_update_work_end_to_end(): void
    {
        $campaign = SalesAgentCampaign::query()->create([
            'name' => 'Auto Apply June Offer ' . Str::random(6),
            'product_type' => 'auto_apply',
            'product_label' => 'Auto Apply',
            'landing_headline' => 'Activate Auto Apply at K100',
            'landing_body' => 'Limited offer for this campaign.',
            'landing_cta_text' => 'Activate Offer',
            'share_message_template' => 'Share this: {link}',
            'prompt_template' => 'Promote {product_label} for {promo_price}.',
            'aspect_ratio' => '4:5',
            'promo_price' => 'K100',
            'promo_original_price' => 'K350',
            'is_active' => true,
        ]);

        $agent = SalesAgent::query()->create([
            'name' => 'Grace Agent ' . Str::random(4),
            'phone' => '+260977000111',
            'email' => 'grace-agent-' . Str::lower(Str::random(5)) . '@example.com',
            'code' => 'TST' . strtoupper(Str::random(6)),
            'commission_rate' => 10,
            'status' => 'active',
        ]);

        $whapiFake = new class() extends WhapiSenderService
        {
            public array $messages = [];

            public function sendText(string $whatsappNumber, string $body, ?string &$errorMessage = null): bool
            {
                $this->messages[] = compact('whatsappNumber', 'body');

                return true;
            }
        };

        $accountSyncFake = new class() extends CandidateAlertAccountSyncService
        {
            public function resolveAccount(?string $email, ?string $phone): ?\Botble\JobBoard\Models\Account
            {
                return null;
            }
        };

        $this->app->instance(WhapiSenderService::class, $whapiFake);
        $this->app->instance(CandidateAlertAccountSyncService::class, $accountSyncFake);

        $showResponse = $this->get(route('public.sales-agent-campaigns.show', [$agent->code, $campaign->getKey()]));

        $showResponse->assertOk();
        $this->assertSame('public.sales-agent-campaigns.show', app('router')->currentRouteName());
        $this->assertDatabaseHas('jb_sales_agent_campaign_clicks', [
            'sales_agent_id' => $agent->getKey(),
            'campaign_id' => $campaign->getKey(),
        ]);

        $submitResponse = $this->post(route('public.sales-agent-campaigns.store', [$agent->code, $campaign->getKey()]), [
            'candidate_name' => 'Test Lead',
            'candidate_phone' => '+260966123456',
            'candidate_email' => 'lead-' . Str::lower(Str::random(6)) . '@example.com',
            'customer_notes' => 'Needs onboarding help',
            'confirm_campaign' => '1',
        ]);

        $lead = SalesAgentCampaignLead::query()->latest('id')->firstOrFail();

        $submitResponse->assertRedirect(route('public.sales-agent-campaigns.thanks', $lead->public_token));

        $this->assertSame('pending', $lead->status);
        $this->assertSame($campaign->getKey(), $lead->campaign_id);
        $this->assertSame($agent->getKey(), $lead->sales_agent_id);
        $this->assertNotNull($lead->notified_admin_at);
        $this->assertNotEmpty($whapiFake->messages);
        $this->assertStringContainsString('New sales campaign lead', $whapiFake->messages[0]['body']);

        $thanksResponse = $this->get(route('public.sales-agent-campaigns.thanks', $lead->public_token));

        $thanksResponse->assertOk();
        $thanksResponse->assertSee('Request Received');
        $thanksResponse->assertSee('+260966123456');

        $this->assertDatabaseHas('jb_sales_agent_referrals', [
            'sales_agent_id' => $agent->getKey(),
            'phone' => '+260966123456',
            'source' => 'camp_auto_apply',
        ]);

        $admin = $this->createAdminUser();

        $updateResponse = $this
            ->actingAs($admin)
            ->put(route('sales-agent-leads.update', $lead->getKey()), [
                'status' => 'onboarded',
                'admin_notes' => 'Payment confirmed and onboarding completed.',
            ]);

        $updateResponse->assertRedirect(route('sales-agent-leads.show', $lead->getKey()));

        $lead->refresh();

        $this->assertSame('onboarded', $lead->status);
        $this->assertSame('Payment confirmed and onboarding completed.', $lead->admin_notes);
        $this->assertNotNull($lead->onboarded_at);
        $this->assertSame(1, SalesAgentReferral::query()->where('phone', '+260966123456')->count());
    }

    public function test_admin_campaign_links_screen_exports_and_queues_whatsapp_jobs(): void
    {
        Queue::fake();

        $campaign = SalesAgentCampaign::query()->create([
            'name' => 'VIP Alert Offer ' . Str::random(6),
            'product_type' => 'vip_alert',
            'product_label' => 'VIP Alert',
            'share_message_template' => 'Send this exact offer: {link}',
            'prompt_template' => 'Promote {product_label} for {promo_price}.',
            'aspect_ratio' => '4:5',
            'promo_price' => 'K50',
            'promo_original_price' => 'K120',
            'is_active' => true,
        ]);

        $activeAgent = SalesAgent::query()->create([
            'name' => 'Alpha Agent',
            'phone' => '+260977000211',
            'email' => 'alpha-' . Str::lower(Str::random(5)) . '@example.com',
            'code' => 'ALPHA' . strtoupper(Str::random(4)),
            'commission_rate' => 10,
            'status' => 'active',
        ]);

        $secondActiveAgent = SalesAgent::query()->create([
            'name' => 'Bravo Agent',
            'phone' => '+260977000212',
            'email' => 'bravo-' . Str::lower(Str::random(5)) . '@example.com',
            'code' => 'BRAVO' . strtoupper(Str::random(4)),
            'commission_rate' => 10,
            'status' => 'active',
        ]);

        $inactiveAgent = SalesAgent::query()->create([
            'name' => 'Charlie Agent',
            'phone' => '+260977000213',
            'email' => 'charlie-' . Str::lower(Str::random(5)) . '@example.com',
            'code' => 'CHARLIE' . strtoupper(Str::random(4)),
            'commission_rate' => 10,
            'status' => 'inactive',
        ]);

        SalesAgentCampaignClick::query()->create([
            'sales_agent_id' => $activeAgent->getKey(),
            'campaign_id' => $campaign->getKey(),
            'ip_address' => '127.0.0.1',
        ]);

        SalesAgentCampaignClick::query()->create([
            'sales_agent_id' => $activeAgent->getKey(),
            'campaign_id' => $campaign->getKey(),
            'ip_address' => '127.0.0.2',
        ]);

        SalesAgentCampaignClick::query()->create([
            'sales_agent_id' => $secondActiveAgent->getKey(),
            'campaign_id' => $campaign->getKey(),
            'ip_address' => '127.0.0.3',
        ]);

        $admin = $this->createAdminUser();

        $linksResponse = $this
            ->actingAs($admin)
            ->get(route('sales-agent-campaigns.links', $campaign->getKey()));

        $linksResponse->assertOk();
        $linksResponse->assertSee('Alpha Agent');
        $linksResponse->assertSee('Bravo Agent');
        $linksResponse->assertDontSee('Charlie Agent');
        $linksResponse->assertSee('2');
        $linksResponse->assertSee('1');

        $exportResponse = $this
            ->actingAs($admin)
            ->get(route('sales-agent-campaigns.links.export', [$campaign->getKey(), 'active_only' => '1']));

        $exportResponse->assertOk();
        $exportResponse->assertHeader('content-type', 'text/csv; charset=utf-8');

        $csv = $exportResponse->streamedContent();

        $this->assertStringContainsString('Alpha Agent', $csv);
        $this->assertStringContainsString('Bravo Agent', $csv);
        $this->assertStringNotContainsString('Charlie Agent', $csv);
        $this->assertStringContainsString(',2,', $csv);
        $this->assertStringContainsString(',1,', $csv);
        $this->assertStringContainsString(route('public.sales-agent-campaigns.show', [$activeAgent->code, $campaign->getKey()]), $csv);

        $singleSendResponse = $this
            ->actingAs($admin)
            ->post(route('sales-agent-campaigns.links.send', [$campaign->getKey(), $activeAgent->getKey()]));

        $singleSendResponse->assertStatus(302);

        Queue::assertPushed(SendSalesAgentCampaignLinkJob::class, function (SendSalesAgentCampaignLinkJob $job) use ($activeAgent, $campaign): bool {
            return $this->queuedJobMatches($job, $activeAgent->getKey(), $campaign->getKey());
        });

        $inactiveSendResponse = $this
            ->actingAs($admin)
            ->post(route('sales-agent-campaigns.links.send', [$campaign->getKey(), $inactiveAgent->getKey()]));

        $inactiveSendResponse->assertStatus(302);

        Queue::assertPushed(SendSalesAgentCampaignLinkJob::class, 1);

        $bulkSendResponse = $this
            ->actingAs($admin)
            ->post(route('sales-agent-campaigns.links.send-bulk', $campaign->getKey()), [
                'agent_ids' => [$activeAgent->getKey(), $secondActiveAgent->getKey()],
            ]);

        $bulkSendResponse->assertStatus(302);

        Queue::assertPushed(SendSalesAgentCampaignLinkJob::class, 3);

        Queue::assertPushed(SendSalesAgentCampaignLinkJob::class, function (SendSalesAgentCampaignLinkJob $job) use ($secondActiveAgent, $campaign): bool {
            return $this->queuedJobMatches($job, $secondActiveAgent->getKey(), $campaign->getKey());
        });
    }

    public function test_campaign_update_normalizes_non_promo_state_and_restore_history(): void
    {
        if (! Schema::hasColumn('jb_sales_agent_campaigns', 'inspiration_images') || ! Schema::hasTable('jb_sales_agent_campaign_versions')) {
            $this->markTestSkipped('Sales agent campaign history migrations have not been applied to this database yet.');
        }

        $campaign = SalesAgentCampaign::query()->create([
            'name' => 'Poster Restore ' . Str::random(6),
            'product_type' => 'career_service',
            'product_label' => 'CV Rewrite',
            'landing_headline' => 'Launch price',
            'landing_body' => 'Initial body',
            'landing_cta_text' => 'Start now',
            'share_message_template' => 'See {link}',
            'prompt_template' => 'Use {campaign_name} with {price_line}.',
            'inspiration_images' => ['sales-agents/examples/a.png'],
            'aspect_ratio' => 'portrait_4_5',
            'promo_price' => 'K100',
            'promo_original_price' => 'K250',
            'promo_end_date' => '2026-07-31',
            'is_active' => true,
        ]);

        $admin = $this->createAdminUser();
        $dateFormat = BaseHelper::getDateFormat();

        $updateResponse = $this
            ->actingAs($admin)
            ->put(route('sales-agent-campaigns.update', $campaign->getKey()), [
                'name' => 'Poster Restore Updated',
                'product_type' => 'career_service',
                'product_label' => 'CV Rewrite',
                'landing_headline' => 'Standard price',
                'landing_body' => 'Updated body',
                'landing_cta_text' => 'Start now',
                'share_message_template' => 'See {link}',
                'prompt_template' => 'Use {campaign_name} with {price_line}.',
                'inspiration_images' => ['sales-agents/examples/b.png'],
                'aspect_ratio' => 'portrait_4_5',
                'promo_price' => 'K100',
                'promo_original_price' => '',
                'promo_end_date' => now()->addDays(7)->format($dateFormat),
                'is_active' => '1',
            ]);

        $updateResponse->assertRedirect(route('sales-agent-campaigns.index'));

        $campaign->refresh();

        $this->assertSame('Poster Restore Updated', $campaign->name);
        $this->assertSame('K100', $campaign->promo_price);
        $this->assertNull($campaign->promo_original_price);
        $this->assertNull($campaign->promo_end_date);
        $this->assertFalse($campaign->isPromoCampaign());

        $initialVersion = $campaign->versions()->oldest('id')->firstOrFail();

        $restoreResponse = $this
            ->actingAs($admin)
            ->post(route('sales-agent-campaigns.versions.restore', [$campaign->getKey(), $initialVersion->getKey()]));

        $restoreResponse->assertRedirect(route('sales-agent-campaigns.edit', [$campaign->getKey(), 'tab' => 'history']));

        $campaign->refresh();

        $this->assertSame($initialVersion->snapshot['name'], $campaign->name);
        $this->assertSame($initialVersion->snapshot['promo_original_price'], $campaign->promo_original_price);
        $this->assertSame($initialVersion->snapshot['inspiration_images'], $campaign->inspiration_images);
        $this->assertGreaterThanOrEqual(3, $campaign->versions()->count());
    }

    private function createAdminUser(): User
    {
        $user = new User();
        $user->forceFill([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin-' . Str::lower(Str::random(8)) . '@example.com',
            'username' => 'admin_' . Str::lower(Str::random(8)),
            'password' => 'secret123',
            'super_user' => 1,
            'manage_supers' => 1,
        ]);
        $user->save();

        app(ActivateUserService::class)->activate($user);

        return $user;
    }

    private function queuedJobMatches(SendSalesAgentCampaignLinkJob $job, int $salesAgentId, int $campaignId): bool
    {
        $reflection = new \ReflectionClass($job);

        return $reflection->getProperty('salesAgentId')->getValue($job) === $salesAgentId
            && $reflection->getProperty('campaignId')->getValue($job) === $campaignId;
    }
}
