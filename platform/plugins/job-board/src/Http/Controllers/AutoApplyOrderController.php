<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AutoApplyLog;
use Botble\JobBoard\Models\AutoApplyOrder;
use Botble\JobBoard\Models\AutoApplyPreference;
use Botble\JobBoard\Services\AutoApplyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AutoApplyOrderController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Auto Apply Orders', route('auto-apply-orders.index'));
    }

    public function index(Request $request)
    {
        $this->pageTitle('Auto Apply Orders');

        $query = AutoApplyOrder::query()->with('account')->latest();

        if ($status = $request->query('status')) {
            $query->where('admin_status', $status);
        }

        if ($search = $request->query('q')) {
            $query->whereHas('account', function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(30)->withQueryString();

        $stats = [
            'total'    => AutoApplyOrder::count(),
            'pending'  => AutoApplyOrder::where('admin_status', 'pending')->count(),
            'approved' => AutoApplyOrder::where('admin_status', 'approved')->count(),
        ];

        return view('plugins/job-board::auto-apply-orders.index', compact('orders', 'stats'));
    }

    public function approve(AutoApplyOrder $autoApplyOrder, BaseHttpResponse $response)
    {
        if ($autoApplyOrder->admin_status !== 'pending') {
            return $response->setError()->setMessage('This order has already been processed.');
        }

        $autoApplyOrder->approve();
        $this->sendConfirmationEmail($autoApplyOrder->fresh(['account']));

        return $response
            ->setNextUrl(route('auto-apply-orders.index'))
            ->setMessage('Auto Apply activated for ' . ($autoApplyOrder->account?->name ?? 'candidate') . '.');
    }

    public function reject(AutoApplyOrder $autoApplyOrder, Request $request, BaseHttpResponse $response)
    {
        if ($autoApplyOrder->admin_status !== 'pending') {
            return $response->setError()->setMessage('This order has already been processed.');
        }

        $autoApplyOrder->update([
            'admin_status' => 'rejected',
            'status'       => 'rejected',
            'notes'        => $request->input('notes'),
        ]);

        return $response
            ->setNextUrl(route('auto-apply-orders.index'))
            ->setMessage('Order rejected.');
    }

    /**
     * Admin: preview a sample auto-apply email for a candidate using a specific AI model.
     */
    public function preview(Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'account_id' => ['required', 'exists:jb_accounts,id'],
            'job_id'     => ['required', 'exists:jb_jobs,id'],
            'ai_model'   => ['required', 'in:gpt-4o-mini,gpt-4o'],
        ]);

        $account = Account::findOrFail($request->input('account_id'));
        $job = \Botble\JobBoard\Models\Job::findOrFail($request->input('job_id'));

        $service = app(AutoApplyService::class);
        $cvText = $service->extractCvText($account);
        $profile = $service->buildCandidateProfile($account, $cvText);

        $result = $service->generateApplicationEmail($account, $job, $profile, $request->input('ai_model'));

        if (! $result) {
            return $response->setError()->setMessage('OpenAI failed to generate email. Check API key configuration.');
        }

        return $response->setData($result)->setMessage('Preview generated successfully.');
    }

    /**
     * Admin: set up auto-apply preference on behalf of a candidate.
     */
    public function setupForCandidate(Request $request, BaseHttpResponse $response)
    {
        $data = $request->validate([
            'account_id'              => ['required', 'exists:jb_accounts,id'],
            'keywords'                => ['nullable', 'array'],
            'category_ids'            => ['nullable', 'array'],
            'country_ids'             => ['nullable', 'array'],
            'location_keyword'        => ['nullable', 'string', 'max:200'],
            'job_experience_id'       => ['nullable', 'integer'],
            'blacklisted_company_ids' => ['nullable', 'array'],
            'match_score_threshold'   => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_active'               => ['nullable', 'boolean'],
            'grant_free_quota'        => ['nullable', 'boolean'],
            'free_applications'       => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $preference = AutoApplyPreference::updateOrCreate(
            ['account_id' => $data['account_id']],
            [
                'keywords'                => $data['keywords'] ?? [],
                'category_ids'            => $data['category_ids'] ?? [],
                'country_ids'             => $data['country_ids'] ?? [],
                'location_keyword'        => $data['location_keyword'] ?? null,
                'job_experience_id'       => $data['job_experience_id'] ?? null,
                'blacklisted_company_ids' => $data['blacklisted_company_ids'] ?? [],
                'match_score_threshold'   => $data['match_score_threshold'] ?? AutoApplyOrder::globalMatchThreshold(),
                'is_active'               => $data['is_active'] ?? true,
            ]
        );

        // Optionally grant free quota
        if (! empty($data['grant_free_quota']) && ! empty($data['free_applications'])) {
            \Illuminate\Support\Facades\DB::table('jb_auto_apply_quotas')->updateOrInsert(
                ['account_id' => $data['account_id'], 'period' => \Botble\JobBoard\Models\AutoApplyQuota::currentPeriod(), 'plan' => 'admin_granted'],
                [
                    'applications_allowed' => $data['free_applications'],
                    'applications_sent'    => 0,
                    'is_approved'          => true,
                    'charge_id'            => null,
                    'payment_method'       => 'admin',
                    'updated_at'           => now(),
                    'created_at'           => now(),
                ]
            );
        }

        return $response
            ->setNextUrl(route('auto-apply-orders.index'))
            ->setMessage('Auto Apply preference configured for candidate.');
    }

    private function sendConfirmationEmail(AutoApplyOrder $order): void
    {
        $account = $order->account;
        if (! $account?->email) {
            return;
        }

        $plan = AutoApplyOrder::plan($order->plan, includeDisabled: true)
            ?? ['label' => $order->duration_days . ' days'];

        try {
            Mail::raw(
                "Hi {$account->first_name},\n\n" .
                "Your Wakanda Jobs Auto Apply subscription is now active!\n\n" .
                "Plan: {$plan['label']}\n" .
                "Applications per month: " . ($plan['applications_per_month'] ?? $order->applications_allowed) . "\n\n" .
                "The system will now automatically apply to matching jobs on your behalf using your CV and AI-crafted cover emails.\n\n" .
                "You can manage your preferences and view sent applications in your account dashboard.\n\n" .
                "Wakanda Jobs — wakandajobs.com",
                function ($msg) use ($account, $plan): void {
                    $msg->to($account->email, "{$account->first_name} {$account->last_name}")
                        ->subject("Your Auto Apply is Active — {$plan['label']}");
                }
            );
        } catch (\Throwable) {
        }
    }
}
