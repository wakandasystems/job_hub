<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\VipAlertOrder;
use Botble\JobBoard\Services\AutoApplyService;
use Botble\JobBoard\Services\CandidateAlertAccountSyncService;
use Botble\Slug\Facades\SlugHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class VipAlertOrderController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('VIP Alert Orders', route('vip-alert-orders.index'));
    }

    public function index(Request $request)
    {
        $this->pageTitle('VIP Alert Orders');

        $query = VipAlertOrder::query()->latest();

        if ($status = $request->query('status')) {
            $query->where('admin_status', $status);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search): void {
                $q->where('candidate_name', 'like', "%{$search}%")
                  ->orWhere('candidate_email', 'like', "%{$search}%")
                  ->orWhere('candidate_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(30)->withQueryString();

        $stats = [
            'total'    => VipAlertOrder::count(),
            'pending'  => VipAlertOrder::where('admin_status', 'pending')->count(),
            'approved' => VipAlertOrder::where('admin_status', 'approved')->count(),
        ];

        $applicationCustomers = VipAlertOrder::query()
            ->with(['candidateAlert.account'])
            ->where('admin_status', 'approved')
            ->whereNotNull('candidate_alert_id')
            ->when($request->filled('customer_q'), function ($query) use ($request): void {
                $keyword = trim((string) $request->input('customer_q'));

                $query->where(function ($builder) use ($keyword): void {
                    $builder->where('candidate_name', 'like', '%' . $keyword . '%')
                        ->orWhere('candidate_email', 'like', '%' . $keyword . '%')
                        ->orWhere('candidate_phone', 'like', '%' . $keyword . '%');
                });
            })
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->paginate(3, ['*'], 'apply_page')
            ->withQueryString();

        return view('plugins/job-board::vip-alert-orders.index', compact('orders', 'stats', 'applicationCustomers'));
    }

    public function approve(VipAlertOrder $vipAlertOrder, BaseHttpResponse $response)
    {
        if ($vipAlertOrder->admin_status !== 'pending') {
            return $response->setError()->setMessage('This order has already been processed.');
        }

        $vipAlertOrder->approve();
        $this->sendConfirmationEmail($vipAlertOrder->fresh());

        return $response
            ->setNextUrl(route('vip-alert-orders.index'))
            ->setMessage('VIP Alert activated. Welcome message sent via WhatsApp.');
    }

    public function reject(VipAlertOrder $vipAlertOrder, Request $request, BaseHttpResponse $response)
    {
        if ($vipAlertOrder->admin_status !== 'pending') {
            return $response->setError()->setMessage('This order has already been processed.');
        }

        $vipAlertOrder->update([
            'admin_status' => 'rejected',
            'notes'        => $request->input('notes'),
        ]);

        return $response
            ->setNextUrl(route('vip-alert-orders.index'))
            ->setMessage('Order rejected.');
    }

    public function sendApplication(VipAlertOrder $vipAlertOrder, Request $request): RedirectResponse
    {
        if ($vipAlertOrder->admin_status !== 'approved' || ! $vipAlertOrder->candidateAlert) {
            return redirect()
                ->to($this->indexReturnUrl($request))
                ->with('error_msg', 'Only approved VIP customers with an active alert can use on-demand applications.');
        }

        $data = $request->validate([
            'job_url' => ['required', 'string', 'max:500'],
        ]);

        $job = $this->resolveJobFromInput($data['job_url']);

        if (! $job) {
            return redirect()
                ->to($this->indexReturnUrl($request))
                ->with('error_msg', 'Could not find an active Wakanda Jobs posting from that URL or slug.');
        }

        $account = $this->resolveApplicationAccount($vipAlertOrder);

        if (! $account) {
            return redirect()
                ->to($this->indexReturnUrl($request))
                ->with('error_msg', 'This VIP customer has no linked candidate account yet, and Wakanda Jobs could not create one automatically. Add a valid candidate email or link the account first.');
        }

        if (trim((string) $account->resume) === '') {
            return redirect()
                ->to($this->indexReturnUrl($request))
                ->with('error_msg', 'This VIP customer has no CV on the linked account or VIP alert, so the application could not be prepared.');
        }

        $result = app(AutoApplyService::class)->sendOnDemandApplication($account, $job);
        $status = $result['status'] ?? 'failed';
        $message = $result['message'] ?? 'Application send failed.';

        $flashKey = in_array($status, ['sent', 'queued', 'manual_notified'], true) ? 'success_msg' : 'error_msg';

        return redirect()
            ->to($this->indexReturnUrl($request))
            ->with($flashKey, $message);
    }

    private function sendConfirmationEmail(VipAlertOrder $order): void
    {
        if (! $order->candidate_email) {
            return;
        }

        $plan = VipAlertOrder::plan($order->plan, includeDisabled: true)
            ?? ['label' => $order->duration_days . ' days'];

        try {
            $registerPrompt = $order->candidateAlert?->account_id
                ? ''
                : "\n\nWe also noticed you do not yet have a Wakanda Jobs account.\n"
                    . "Create your free account for faster applications, one saved CV/profile, and more candidate benefits:\n"
                    . route('public.account.register');

            Mail::raw(
                "Hi {$order->candidate_name},\n\n" .
                "Your Wakanda Jobs VIP Alert subscription is now active!\n\n" .
                "Plan: {$plan['label']}\n" .
                "Expires: " . now()->addDays($order->duration_days)->format('d M Y') . "\n\n" .
                "You will start receiving matching job alerts on WhatsApp at {$order->candidate_phone}." .
                $registerPrompt . "\n\n" .
                "Wakanda Jobs — wakandajobs.com",
                function ($msg) use ($order, $plan): void {
                    $msg->to($order->candidate_email, $order->candidate_name)
                        ->subject("Your VIP Job Alert is Active — {$plan['label']}");
                }
            );
        } catch (\Throwable) {
        }
    }

    private function resolveApplicationAccount(VipAlertOrder $vipAlertOrder): ?Account
    {
        $alert = $vipAlertOrder->candidateAlert?->loadMissing('account');

        if (! $alert) {
            return null;
        }

        $syncService = app(CandidateAlertAccountSyncService::class);
        $account = $alert->account ?: $syncService->resolveAccount($alert->candidate_email, $alert->candidate_phone);

        if (! $account && $alert->cv_path) {
            [$account] = $syncService->createAccountForAlert(
                $alert->candidate_name,
                $alert->candidate_email,
                $alert->candidate_phone
            );

            if ($account) {
                $syncService->syncStoredAlertCvToAccount($account, $alert->cv_path);
                $syncService->syncAlertWithAccount($alert, $account, true);
            }
        }

        if ($account && trim((string) $account->resume) === '' && $alert->cv_path) {
            $syncService->syncStoredAlertCvToAccount($account, $alert->cv_path);
        }

        if ($account && (int) $alert->account_id !== (int) $account->getKey()) {
            $syncService->syncAlertWithAccount($alert, $account, true);
        }

        return $account;
    }

    private function resolveJobFromInput(string $value): ?Job
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $candidate = trim(Str::afterLast($value, '/'));
        $path = null;

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $path = parse_url($value, PHP_URL_PATH) ?: null;
            $candidate = trim((string) Str::afterLast((string) $path, '/'));
        } elseif (str_starts_with($value, '/')) {
            $path = $value;
            $candidate = trim((string) Str::afterLast($value, '/'));
        }

        $candidate = trim($candidate);
        $candidate = trim(Str::before($candidate, '?'));
        $candidate = trim(Str::before($candidate, '#'));

        if ($candidate === '' && $path) {
            $candidate = trim((string) Str::afterLast((string) $path, '/'));
        }

        if ($candidate === '') {
            return null;
        }

        $jobQuery = Job::query()
            ->with(['slugable', 'company'])
            ->where('status', JobStatusEnum::PUBLISHED)
            ->notExpired()
            ->notClosed();

        $numericId = ltrim($candidate, '#');

        if (ctype_digit($numericId)) {
            return (clone $jobQuery)->whereKey((int) $numericId)->first();
        }

        $jobPrefix = SlugHelper::getPrefix(Job::class, 'jobs');
        $slug = SlugHelper::getSlug($candidate, $jobPrefix);

        if ($slug && (int) $slug->reference_id > 0) {
            return (clone $jobQuery)->whereKey((int) $slug->reference_id)->first();
        }

        return $jobQuery
            ->whereHas('slugable', function ($query) use ($candidate): void {
                $query->where('key', $candidate);
            })
            ->first();
    }

    private function indexReturnUrl(Request $request): string
    {
        $query = array_filter([
            'q' => $request->input('q'),
            'status' => $request->input('status'),
            'page' => $request->input('page'),
            'customer_q' => $request->input('customer_q'),
            'apply_page' => $request->input('apply_page'),
        ], fn ($value) => filled($value));

        $url = route('vip-alert-orders.index');

        return $query ? $url . '?' . http_build_query($query) : $url;
    }
}
