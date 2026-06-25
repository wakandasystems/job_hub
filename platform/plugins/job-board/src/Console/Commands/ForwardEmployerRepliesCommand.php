<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AutoApplyLog;
use Botble\JobBoard\Services\WhapiSenderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;

class ForwardEmployerRepliesCommand extends Command
{
    protected $signature = 'job-board:forward-employer-replies';
    protected $description = 'Poll the company mailbox for employer replies to Auto Apply emails and forward each one to the right candidate';

    private const LAST_UID_SETTING_KEY = 'auto_apply_reply_imap_last_uid';

    public function handle(WhapiSenderService $whapi): int
    {
        $host = setting('email_host');
        $username = setting('email_username');
        $password = setting('email_password');

        if (! $host || ! $username || ! $password) {
            $this->info('Mail settings are not configured, skipping.');

            return self::SUCCESS;
        }

        try {
            $client = (new ClientManager())->make([
                'host' => $host,
                'port' => 993,
                'protocol' => 'imap',
                'encryption' => 'ssl',
                'validate_cert' => true,
                'username' => $username,
                'password' => $password,
            ]);

            $client->connect();
        } catch (Throwable $e) {
            Log::error('ForwardEmployerReplies: IMAP connect failed', ['error' => $e->getMessage()]);
            $this->error('IMAP connect failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $lastUid = (int) setting(self::LAST_UID_SETTING_KEY, 0);
        $maxUidSeen = $lastUid;
        $forwarded = 0;

        try {
            $messages = $client->getFolder('INBOX')
                ->messages()
                ->since(now()->subDays(2))
                ->leaveUnread()
                ->get();

            foreach ($messages as $message) {
                $uid = (int) $message->getUid();

                if ($uid <= $lastUid) {
                    continue;
                }

                $maxUidSeen = max($maxUidSeen, $uid);

                $log = $this->matchToAutoApplyLog($message);

                if (! $log || $log->employer_reply_forwarded_at) {
                    continue;
                }

                if ($this->forwardToCandidate($log, $message)) {
                    $log->update(['employer_reply_forwarded_at' => now()]);
                    $forwarded++;

                    $this->notifyByWhatsApp($whapi, $log, $message);
                }
            }
        } finally {
            $client->disconnect();
        }

        setting()->set(self::LAST_UID_SETTING_KEY, $maxUidSeen)->save();

        $this->info("Checked employer replies, forwarded {$forwarded} to candidate(s).");

        return self::SUCCESS;
    }

    /**
     * Match an inbound reply back to the AutoApplyLog it answers, by reading the
     * Message-ID we tagged on the original outgoing email out of In-Reply-To/References.
     */
    private function matchToAutoApplyLog(Message $message): ?AutoApplyLog
    {
        $raw = (string) $message->getHeader()?->raw;

        preg_match_all('/<([^>]+)>/', $raw, $matches);
        $ids = array_unique($matches[1] ?? []);

        if ($ids === []) {
            return null;
        }

        return AutoApplyLog::whereIn('message_id', $ids)->first();
    }

    private function forwardToCandidate(AutoApplyLog $log, Message $message): bool
    {
        $account = $log->account;

        if (! $account || ! $account->email) {
            return false;
        }

        $from = $message->getFrom()->first();
        $fromAddress = $from?->mail ?? '';
        $fromName = $from?->personal ?: $fromAddress;
        $subject = (string) $message->getSubject();

        $body = trim((string) $message->getTextBody());
        if ($body === '') {
            $body = trim(strip_tags((string) $message->getHTMLBody()));
        }

        $forwardBody = "Good news — an employer replied to your job application:\n\n"
            . "From: {$fromName} <{$fromAddress}>\n"
            . "Subject: {$subject}\n\n"
            . "----------\n"
            . $body;

        try {
            Mail::raw($forwardBody, function ($mail) use ($account, $subject) {
                $mail->to($account->email, "{$account->first_name} {$account->last_name}")
                    ->subject("Employer reply: {$subject}");
            });

            return true;
        } catch (Throwable $e) {
            Log::error('ForwardEmployerReplies: forward failed', [
                'log_id' => $log->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function notifyByWhatsApp(WhapiSenderService $whapi, AutoApplyLog $log, Message $message): void
    {
        $account = $log->account;
        $phone = $this->resolveWhatsAppNumber($account);

        if ($phone === '') {
            Log::warning('ForwardEmployerReplies: WhatsApp notification skipped, no number on file', [
                'log_id' => $log->id,
                'account_id' => $account?->id,
            ]);

            return;
        }

        $from = $message->getFrom()->first();
        $companyName = $from?->personal ?: ($from?->mail ?? 'The employer');
        $job = $log->job;

        $text = "Hi {$account->first_name},\n\n"
            . "*{$companyName}* just replied to your application"
            . ($job?->name ? " for *{$job->name}*" : '') . ".\n\n"
            . "Check your email ({$account->email}) for the full message.\n\n"
            . '_Nakia_';

        $errorMessage = null;

        if (! $whapi->sendText($phone, $text, $errorMessage)) {
            Log::error('ForwardEmployerReplies: WhatsApp notification failed', [
                'log_id' => $log->id,
                'phone' => $phone,
                'error' => $errorMessage ?: 'Unknown WhatsApp send failure',
            ]);
        }
    }

    private function resolveWhatsAppNumber(?Account $account): string
    {
        if (! $account) {
            return '';
        }

        return trim((string) ($account->whatsapp_number ?: $account->phone));
    }
}
