<?php

namespace Tests\Feature;

use Botble\ACL\Models\User;
use Botble\ACL\Services\ActivateUserService;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Models\CandidateAlert;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\VipAlertOrder;
use Botble\JobBoard\Services\AutoApplyService;
use Botble\Slug\Facades\SlugHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class VipAlertOrderOnDemandApplicationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_send_on_demand_application_for_approved_vip_customer_using_stored_vip_cv(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $storedCvPath = 'vip-cvs/' . Str::uuid() . '.txt';
        Storage::disk('local')->put($storedCvPath, 'Experienced administrator with procurement and finance support background.');

        $alert = CandidateAlert::query()->create([
            'label' => 'Grace Customer',
            'candidate_name' => 'Grace Customer',
            'candidate_phone' => '+260966123456',
            'candidate_email' => 'grace.customer@example.com',
            'filters' => [],
            'duration_days' => 30,
            'price' => 100,
            'is_active' => true,
            'status' => 'active',
            'activated_at' => now(),
            'expires_at' => now()->addDays(30),
            'cv_path' => $storedCvPath,
        ]);

        $order = VipAlertOrder::query()->create([
            'candidate_name' => 'Grace Customer',
            'candidate_phone' => '+260966123456',
            'candidate_email' => 'grace.customer@example.com',
            'plan' => 'monthly',
            'duration_days' => 30,
            'amount' => 100,
            'currency' => 'ZMW',
            'payment_status' => 'paid',
            'admin_status' => 'approved',
            'candidate_alert_id' => $alert->getKey(),
            'approved_at' => now(),
        ]);

        $job = Job::query()->create([
            'name' => 'Administrative Assistant',
            'description' => 'Strong communication and office administration skills required.',
            'status' => JobStatusEnum::PUBLISHED,
        ]);

        SlugHelper::createSlug($job);
        $job->refresh();

        $service = Mockery::mock(AutoApplyService::class);
        $service
            ->shouldReceive('sendOnDemandApplication')
            ->once()
            ->withArgs(function ($account, $resolvedJob) use ($job): bool {
                return $resolvedJob->getKey() === $job->getKey()
                    && $account->email === 'grace.customer@example.com'
                    && trim((string) $account->resume) !== '';
            })
            ->andReturn([
                'status' => 'sent',
                'job_id' => $job->getKey(),
                'message' => 'Application queued for sending.',
            ]);

        $this->app->instance(AutoApplyService::class, $service);

        $response = $this
            ->actingAs($this->createAdminUser())
            ->post(route('vip-alert-orders.send-application', $order), [
                'job_url' => 'https://www.wakandajobs.com/jobs/' . $job->slug,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success_msg', 'Application queued for sending.');

        $alert->refresh();

        $this->assertNotNull($alert->account_id);
        $this->assertDatabaseHas('jb_accounts', [
            'id' => $alert->account_id,
            'email' => 'grace.customer@example.com',
            'type' => AccountTypeEnum::JOB_SEEKER,
        ]);

        $account = $alert->account()->firstOrFail();

        $this->assertNotSame('', trim((string) $account->resume));
        Storage::disk('public')->assertExists($account->resume);
    }

    public function test_non_approved_vip_order_cannot_send_on_demand_application(): void
    {
        $alert = CandidateAlert::query()->create([
            'label' => 'Pending Customer',
            'candidate_name' => 'Pending Customer',
            'candidate_phone' => '+260977111222',
            'candidate_email' => 'pending.customer@example.com',
            'filters' => [],
            'duration_days' => 30,
            'price' => 100,
            'is_active' => true,
            'status' => 'active',
            'activated_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $order = VipAlertOrder::query()->create([
            'candidate_name' => 'Pending Customer',
            'candidate_phone' => '+260977111222',
            'candidate_email' => 'pending.customer@example.com',
            'plan' => 'monthly',
            'duration_days' => 30,
            'amount' => 100,
            'currency' => 'ZMW',
            'payment_status' => 'paid',
            'admin_status' => 'pending',
            'candidate_alert_id' => $alert->getKey(),
        ]);

        $service = Mockery::mock(AutoApplyService::class);
        $service->shouldNotReceive('sendOnDemandApplication');
        $this->app->instance(AutoApplyService::class, $service);

        $response = $this
            ->actingAs($this->createAdminUser())
            ->post(route('vip-alert-orders.send-application', $order), [
                'job_url' => 'https://www.wakandajobs.com/jobs/test-job',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error_msg', 'Only approved VIP customers with an active alert can use on-demand applications.');
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
}
