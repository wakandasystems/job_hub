<?php

namespace Botble\JobBoard\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Botble\JobBoard\Models\AutoApplyLog;
use Botble\JobBoard\Models\AutoApplyOrder;
use Botble\JobBoard\Services\WhapiSenderService;
use Botble\Media\Facades\RvMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAutoApplyDigestCommand extends Command
{
    protected $signature = 'auto-apply:weekly-digest
                            {--order= : Send digest only for this order ID}
                            {--email= : Override recipient email (test mode)}
                            {--phone= : Override recipient WhatsApp number (test mode)}';

    protected $description = 'Send weekly Auto Apply digest (email + WhatsApp PDF) to all active subscribers.';

    public function handle(): int
    {
        $this->info('Sending Auto Apply weekly digests...');

        $weekAgo = now()->subWeek();

        $query = AutoApplyOrder::where('status', 'approved')
            ->where('admin_status', 'approved')
            ->whereNotNull('approved_at')
            ->with('account');

        if ($onlyId = $this->option('order')) {
            $query->where('id', $onlyId);
        }

        $orders = $query->get()
            ->filter(fn ($o) => $o->account && $o->isActiveAt(now()));

        $sent = 0;

        foreach ($orders as $order) {
            $account = $order->account;

            if (! $account->email) {
                continue;
            }

            $logs = AutoApplyLog::where('account_id', $account->id)
                ->where('created_at', '>=', $weekAgo)
                ->with(['job' => fn ($q) => $q->with('slugable')])
                ->latest('created_at')
                ->get();

            $sentLogs    = $logs->where('status', 'sent')->values();
            $skippedLogs = $logs->where('status', 'skipped_low_score')->values();
            $failedLogs  = $logs->where('status', 'failed')->values();

            if ($sentLogs->isEmpty() && $skippedLogs->isEmpty()) {
                $this->line("  Skipping {$account->email} — no activity this week.");
                continue;
            }

            $periodLabel = $weekAgo->format('d M') . ' – ' . now()->format('d M Y');
            $manualCount = $sentLogs->where('email_sent_to', 'manual-apply-notice')->count();

            // Build job URL map from slugable (url accessor doesn't work in CLI/PDF context)
            $jobUrls = [];
            foreach ($logs as $log) {
                if ($log->job && $log->job->slugable) {
                    $s = $log->job->slugable;
                    $jobUrls[$log->job_id] = url(trim($s->prefix, '/') . '/' . $s->key);
                }
            }

            $logoFile  = setting('email_template_logo') ?: setting('theme-jobbox-logo');
            $logoUrl   = null;
            if ($logoFile) {
                $localPath = public_path('storage/' . ltrim($logoFile, '/'));
                if (is_file($localPath)) {
                    $mime    = mime_content_type($localPath) ?: 'image/png';
                    $logoUrl = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($localPath));
                } else {
                    $logoUrl = RvMedia::getImageUrl($logoFile);
                }
            }

            $pdfData = [
                'account'      => $account,
                'periodLabel'  => $periodLabel,
                'sentLogs'     => $sentLogs,
                'skippedLogs'  => $skippedLogs,
                'failedLogs'   => $failedLogs,
                'sentCount'    => $sentLogs->count(),
                'skippedCount' => $skippedLogs->count(),
                'failedCount'  => $failedLogs->count(),
                'manualCount'  => $manualCount,
                'logoUrl'      => $logoUrl,
                'jobUrls'      => $jobUrls,
            ];

            // Generate PDF
            $pdfPath = null;
            try {
                $pdf = Pdf::loadView('plugins/job-board::auto-apply-orders.digest-pdf', $pdfData)
                    ->setPaper('a4', 'portrait');

                $tempDir = storage_path('app/temp');
                if (! is_dir($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }

                $pdfPath = $tempDir . '/auto-apply-digest-' . $account->id . '-' . now()->format('Ymd') . '.pdf';
                $pdf->save($pdfPath);
            } catch (Throwable $e) {
                $this->warn("  PDF generation failed for {$account->email}: {$e->getMessage()}");
            }

            // Build plain-text body for email fallback
            $body  = $this->buildTextBody($account, $sentLogs, $skippedLogs, $failedLogs, $periodLabel);
            $toEmail = $this->option('email') ?: $account->email;
            $toName  = "{$account->first_name} {$account->last_name}";
            $subject = "Auto Apply Weekly Digest — {$sentLogs->count()} application" . ($sentLogs->count() == 1 ? '' : 's') . " sent";

            // Send email (HTML body + PDF attachment)
            try {
                Mail::send(
                    [],
                    [],
                    function ($msg) use ($toEmail, $toName, $subject, $body, $pdfPath, $periodLabel): void {
                        $msg->to($toEmail, $toName)->subject($subject);
                        $msg->html($this->buildHtmlBody($body));
                        $msg->text($body);
                        if ($pdfPath && file_exists($pdfPath)) {
                            $msg->attach($pdfPath, [
                                'as'   => 'auto-apply-digest-' . now()->format('d-M-Y') . '.pdf',
                                'mime' => 'application/pdf',
                            ]);
                        }
                    }
                );
                $this->info("  Email sent → {$toEmail}");
                $sent++;
            } catch (Throwable $e) {
                $this->warn("  Email failed for {$toEmail}: {$e->getMessage()}");
            }

            // Send WhatsApp PDF
            $phone = $this->option('phone') ?: $account->whatsapp_number;
            if ($phone && $pdfPath && file_exists($pdfPath)) {
                try {
                    $whapi = app(WhapiSenderService::class);
                    $caption = "Hi {$account->first_name}! Here's your Wakanda Jobs Auto Apply weekly digest — {$sentLogs->count()} application" . ($sentLogs->count() == 1 ? '' : 's') . " sent this week. 📊";
                    $errMsg  = null;
                    $ok = $whapi->sendDocument(
                        $phone,
                        $pdfPath,
                        'auto-apply-digest-' . now()->format('d-M-Y') . '.pdf',
                        $caption,
                        $errMsg
                    );
                    if ($ok) {
                        $this->info("  WhatsApp PDF sent → {$phone}");
                    } else {
                        $this->warn("  WhatsApp PDF failed for {$phone}: {$errMsg}");
                    }
                } catch (Throwable $e) {
                    $this->warn("  WhatsApp exception for {$phone}: {$e->getMessage()}");
                }
            }

            // Clean up temp PDF
            if ($pdfPath && file_exists($pdfPath)) {
                @unlink($pdfPath);
            }
        }

        $this->info("Done. Sent {$sent} digests.");

        return self::SUCCESS;
    }

    private function buildTextBody($account, $sentLogs, $skippedLogs, $failedLogs, string $periodLabel): string
    {
        $body  = "Hi {$account->first_name},\n\n";
        $body .= "Here's your weekly Auto Apply digest from Wakanda Jobs.\n\n";
        $body .= "--- THIS WEEK'S SUMMARY ({$periodLabel}) ---\n";
        $body .= "Applications sent: {$sentLogs->count()}\n";
        if ($skippedLogs->isNotEmpty()) {
            $body .= "Skipped (low match score): {$skippedLogs->count()}\n";
        }
        if ($failedLogs->isNotEmpty()) {
            $body .= "Failed: {$failedLogs->count()}\n";
        }

        if ($sentLogs->isNotEmpty()) {
            $body .= "\n--- APPLICATIONS SENT ---\n";
            foreach ($sentLogs as $log) {
                $jobName  = $log->job?->name ?? 'Unknown Job';
                $score    = $log->match_score;
                $ts       = $log->sent_at ?? $log->created_at;
                $datetime = $ts?->format('d M Y H:i') . ' UTC';
                $type     = $log->email_sent_to === 'manual-apply-notice' ? 'Manual' : 'Auto';
                $url      = $log->job?->url ?? '';
                $body    .= "- {$jobName} (Score: {$score}% | {$type}) — {$datetime}\n";
                if ($url) {
                    $body .= "  {$url}\n";
                }
            }
        }

        if ($skippedLogs->isNotEmpty()) {
            $body .= "\n--- SKIPPED (Below Threshold) ---\n";
            foreach ($skippedLogs as $log) {
                $jobName  = $log->job?->name ?? 'Unknown Job';
                $score    = $log->match_score;
                $ts       = $log->sent_at ?? $log->created_at;
                $datetime = $ts?->format('d M Y H:i') . ' UTC';
                $url      = $log->job?->url ?? '';
                $body    .= "- {$jobName} (Score: {$score}%) — {$datetime}\n";
                if ($url) {
                    $body .= "  {$url}\n";
                }
            }
        }

        $body .= "\nWakanda Jobs — wakandajobs.com";

        return $body;
    }

    private function buildHtmlBody(string $textBody): string
    {
        $html  = '<html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;font-size:14px;color:#333;max-width:600px;margin:0 auto;padding:24px;">';
        $html .= '<div style="background:#1a1a2e;color:#fff;padding:20px 24px;border-radius:8px 8px 0 0;">';
        $html .= '<strong style="font-size:18px;">Wakanda<span style="color:#e94560;">Jobs</span></strong>';
        $html .= '<p style="margin:8px 0 0;font-size:13px;color:#a0aec0;">Auto Apply Weekly Digest</p></div>';
        $html .= '<div style="border:1px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px;padding:24px;">';
        $html .= '<pre style="font-family:Arial,sans-serif;font-size:13px;white-space:pre-wrap;line-height:1.7;">' . e($textBody) . '</pre>';
        $html .= '<hr style="border:none;border-top:1px solid #e2e8f0;margin:20px 0;">';
        $html .= '<p style="font-size:12px;color:#a0aec0;">The full PDF digest is attached to this email.</p>';
        $html .= '</div></body></html>';

        return $html;
    }
}
