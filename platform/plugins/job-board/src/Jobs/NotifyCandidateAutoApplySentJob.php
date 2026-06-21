<?php

namespace Botble\JobBoard\Jobs;

use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotifyCandidateAutoApplySentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        private readonly int $accountId,
        private readonly int $jobId,
    ) {
    }

    public function handle(): void
    {
        $account = Account::find($this->accountId);
        $job = Job::find($this->jobId);

        if (! $account || ! $job) {
            return;
        }

        $this->sendEmail($account, $job);
        $this->sendWhatsApp($account, $job);
    }

    private function sendEmail(Account $account, Job $job): void
    {
        $email = trim((string) $account->email);
        if ($email === '') {
            return;
        }

        $jobUrl = url('/jobs/' . ($job->slugable?->key ?? $job->id));
        $company = $job->company?->name;

        $body = "Hi {$account->first_name},\n\n"
            . "Good news — Wakanda Jobs Auto Apply has automatically submitted your application for:\n\n"
            . $job->name . ($company ? " at {$company}" : '') . "\n{$jobUrl}\n\n"
            . "No action is needed from you. We'll keep applying to new matching jobs on your behalf for the rest of your plan.\n\n"
            . 'Wakanda Jobs Auto Apply';

        try {
            Mail::raw($body, function ($message) use ($email, $job) {
                $message->to($email)->subject("Auto Apply: We applied for \"{$job->name}\" on your behalf");
            });
        } catch (Throwable $e) {
            Log::error('AutoApply: Candidate notification email failed', [
                'account_id' => $account->id,
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendWhatsApp(Account $account, Job $job): void
    {
        $phone = trim((string) ($account->whatsapp_number ?: $account->phone));
        if ($phone === '') {
            return;
        }

        [$token, $gatewayUrl] = $this->getWhapiCredentials();
        if (! $token) {
            return;
        }

        $jobUrl = url('/jobs/' . ($job->slugable?->key ?? $job->id));
        $company = $job->company?->name;

        $message = "Hi {$account->first_name},\n\n"
            . "Wakanda Jobs Auto Apply has just applied for *{$job->name}*" . ($company ? " at {$company}" : '') . " on your behalf.\n"
            . "{$jobUrl}\n\n"
            . '_Wakanda Jobs Auto Apply_';

        $jid = preg_replace('/\D/', '', $phone) . '@s.whatsapp.net';

        try {
            Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                'to' => $jid,
                'body' => $message,
            ]);
        } catch (Throwable $e) {
            Log::error('AutoApply: Candidate WhatsApp notification failed', [
                'account_id' => $account->id,
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getWhapiCredentials(): array
    {
        $automation = SocialAutomation::where('platform', 'whapi')->where('is_active', true)->first();
        if (! $automation) {
            return [null, null];
        }

        $settings = $automation->settings ?? [];
        $token = SocialAutomation::whapiToken($automation);
        $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';

        return $token ? [$token, $gatewayUrl] : [null, null];
    }
}
