<?php

namespace Botble\JobBoard\Jobs;

use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AutoCvSession;
use Botble\JobBoard\Models\CandidateAlert;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Services\WhapiSenderService;
use Botble\Media\Facades\RvMedia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
        private readonly ?string $coverLetterSubject = null,
        private readonly ?string $coverLetterBody = null,
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
            . "I have just applied for:\n\n"
            . $job->name . ($company ? " at {$company}" : '') . "\n{$jobUrl}\n\n"
            . "No action is needed from you. I will keep applying to new matching jobs for you for the rest of your plan.\n\n"
            . 'Nakia';

        if ($this->coverLetterBody) {
            $body .= "\n\n---\nHere's exactly what we sent on your behalf:\n\n"
                . ($this->coverLetterSubject ? "Subject: {$this->coverLetterSubject}\n\n" : '')
                . $this->coverLetterBody;
        }

        try {
            Mail::raw($body, function ($message) use ($email, $job) {
                $message->to($email)->subject("I applied for \"{$job->name}\"");
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
        $phone = $this->resolveWhatsAppNumber($account);
        if ($phone === '') {
            Log::warning('AutoApply: Candidate WhatsApp notification skipped because no number was found', [
                'account_id' => $account->id,
                'job_id' => $job->id,
            ]);

            return;
        }

        $jobUrl = url('/jobs/' . ($job->slugable?->key ?? $job->id));
        $company = $job->company?->name;

        $message = "Hi {$account->first_name},\n\n"
            . "I have just applied for *{$job->name}*" . ($company ? " at {$company}" : '') . ".\n"
            . "{$jobUrl}\n\n"
            . '_Nakia_';

        if ($this->coverLetterBody) {
            $message .= "\n\n---\n*Here's exactly what we sent on your behalf:*\n\n"
                . ($this->coverLetterSubject ? "*Subject:* {$this->coverLetterSubject}\n\n" : '')
                . $this->coverLetterBody;
        }

        $sender = app(WhapiSenderService::class);
        $errorMessage = null;
        $confirmationImagePath = $this->settingImageLocalPath('auto_cv_bot_confirmation_image');

        $sent = $confirmationImagePath
            ? $sender->sendImage($phone, $confirmationImagePath, $message, $errorMessage)
            : $sender->sendText($phone, $message, $errorMessage);

        if (! $sent) {
            Log::error('AutoApply: Candidate WhatsApp notification failed', [
                'account_id' => $account->id,
                'job_id' => $job->id,
                'phone' => $phone,
                'error' => $errorMessage ?: 'Unknown WhatsApp send failure',
            ]);
        }
    }

    private function settingImageLocalPath(string $settingKey): ?string
    {
        $url = trim((string) setting($settingKey, ''));

        if ($url === '') {
            return null;
        }

        if (! RvMedia::isUsingCloud()) {
            $path = RvMedia::getRealPath($url);

            return is_file($path) ? $path : null;
        }

        $contents = @file_get_contents(RvMedia::getImageUrl($url));

        if ($contents === false) {
            return null;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'auto_apply_done_') . '.' . (pathinfo($url, PATHINFO_EXTENSION) ?: 'jpg');
        file_put_contents($tempPath, $contents);

        return $tempPath;
    }

    private function resolveWhatsAppNumber(Account $account): string
    {
        $direct = trim((string) ($account->whatsapp_number ?: $account->phone));

        if ($direct !== '') {
            return $direct;
        }

        $fullName = trim((string) $account->name);

        if ($fullName !== '') {
            $sessionNumber = trim((string) AutoCvSession::query()
                ->where('candidate_name', $fullName)
                ->whereNotNull('whatsapp_number')
                ->latest('id')
                ->value('whatsapp_number'));

            if ($sessionNumber !== '') {
                return $sessionNumber;
            }

            $alertNumber = trim((string) CandidateAlert::query()
                ->where(function ($query) use ($account, $fullName): void {
                    $query->where('account_id', $account->id)
                        ->orWhere('candidate_name', $fullName);
                })
                ->whereNotNull('candidate_phone')
                ->latest('id')
                ->value('candidate_phone'));

            if ($alertNumber !== '') {
                return $alertNumber;
            }
        }

        return '';
    }
}
