<?php

namespace Botble\JobBoard\Listeners;

use Botble\JobBoard\Events\JobPublishedEvent;
use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Models\CandidateAlert;
use Botble\JobBoard\Models\CandidateAlertLog;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\OpenAiImageService;
use Botble\Media\Facades\RvMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Throwable;

class SendVipCandidateAlertsListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue   = 'default';

    /**
     * Generous headroom above IMAGE_WAIT_MAX_ATTEMPTS so the queue's own max-attempts
     * guard never cuts the image-wait loop short before it reaches its own cutoff below.
     */
    public int $tries = 10;

    /** Seconds between checks while waiting for GenerateSocialImagesListener to finish. */
    private const IMAGE_WAIT_INTERVAL = 20;

    /** Give up waiting after this many checks (~100s) and send text-only instead. */
    private const IMAGE_WAIT_MAX_ATTEMPTS = 6;

    public function handle(JobPublishedEvent $event): void
    {
        $job = $event->job;

        if ($job->status != JobStatusEnum::PUBLISHED) {
            return;
        }

        // Skip jobs already past their deadline at publish time
        $deadline = $job->expire_date ?? null;
        if ($deadline && now()->gt($deadline)) {
            return;
        }

        // GenerateSocialImagesListener generates job->whatsapp_image in a background
        // process. Wait for it (re-checking on a delay) so the VIP WhatsApp message goes
        // out WITH the image instead of racing ahead text-only — same fix already applied
        // to the public social-channel post. We only ever READ whatsapp_image here; we
        // never trigger generation ourselves. After IMAGE_WAIT_MAX_ATTEMPTS we give up and
        // send text-only rather than delay VIP candidates indefinitely.
        if ($this->isImagePending($job) && $this->attempts() < self::IMAGE_WAIT_MAX_ATTEMPTS) {
            $this->release(self::IMAGE_WAIT_INTERVAL);

            return;
        }

        $alerts = CandidateAlert::active()
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->get();

        if ($alerts->isEmpty()) {
            return;
        }

        [$token, $gatewayUrl] = $this->getWhapiCredentials();
        if (! $token) {
            return;
        }

        // Load only what's needed for filter matching
        $job->loadMissing(['company', 'slugable', 'jobTypes', 'categories']);

        foreach ($alerts as $alert) {
            // Skip if already sent this exact job to this alert
            if ($alert->logs()->where('job_id', $job->id)->exists()) {
                continue;
            }

            $matchedKeyword = null;
            if (! $this->jobMatchesFilters($job, $alert->filters ?? [], $matchedKeyword)) {
                continue;
            }

            $ok = $this->sendJobToCandidate($token, $gatewayUrl, $alert, $job, $matchedKeyword);

            CandidateAlertLog::create([
                'candidate_alert_id' => $alert->id,
                'job_id'             => $job->id,
                'status'             => $ok ? 'sent' : 'failed',
                'error_message'      => $ok ? null : 'Real-time send failed',
                'sent_at'            => now(),
            ]);
        }
    }

    /**
     * True while the job qualifies for AI image generation but the image hasn't
     * landed on the model yet. Never initiates generation — purely a read check.
     */
    private function isImagePending(Job $job): bool
    {
        if (trim((string) ($job->whatsapp_image ?? '')) !== '') {
            return false;
        }

        return (bool) setting('ai_social_image_enabled') && app(OpenAiImageService::class)->isConfigured();
    }

    /**
     * Plural-tolerant keyword pattern — kept in sync with
     * SendCandidateAlertsCommand::keywordRegexPattern() so the instant
     * listener and the daily digest match the same jobs.
     */
    private function keywordPattern(string $keyword): string
    {
        $keyword = trim($keyword);
        $pattern = preg_quote($keyword, '/');

        if (preg_match('/[a-z]$/i', $keyword)) {
            $pattern .= 's?';
        }

        return '/\b' . $pattern . '\b/iu';
    }

    private function jobMatchesFilters(Job $job, array $filters, ?string &$matchedKeyword = null): bool
    {
        $matchedKeyword = null;

        // Keywords — OR logic across title, description, address (not company name)
        $keywords = array_values(array_filter(array_map('trim', (array) ($filters['keywords'] ?? (($filters['keyword'] ?? null) ? [$filters['keyword']] : [])))));
        if ($keywords) {
            $matched = false;
            foreach ($keywords as $kw) {
                $kwPat = $this->keywordPattern($kw);
                if (preg_match($kwPat, $job->name)
                    || preg_match($kwPat, (string) ($job->description ?? ''))
                    || preg_match($kwPat, (string) ($job->address ?? ''))) {
                    $matched = true;
                    $matchedKeyword = $kw;
                    break;
                }
            }
            if (! $matched) return false;
        }

        // Company keywords — OR logic against company name
        if (! empty($filters['company_keywords'])) {
            $companyName = $job->company?->name ?? '';
            $companyKws  = array_filter(array_map('trim', (array) $filters['company_keywords']));
            $matched = false;
            foreach ($companyKws as $ck) {
                if ($ck !== '' && stripos($companyName, $ck) !== false) {
                    $matched = true;
                    break;
                }
            }
            if (! $matched) return false;
        }

        // Job types — pass if job has no types OR has a matching type
        if (! empty($filters['job_type_ids'])) {
            $ids       = array_filter(array_map('intval', (array) $filters['job_type_ids']));
            $jobTypes  = $job->jobTypes->pluck('id')->toArray();
            if ($ids && ! empty($jobTypes) && empty(array_intersect($ids, $jobTypes))) {
                return false;
            }
        }

        // Categories — pass if job has no categories OR has a matching category
        if (! empty($filters['category_ids'])) {
            $ids      = array_filter(array_map('intval', (array) $filters['category_ids']));
            $catIds   = $job->categories->pluck('id')->toArray();
            if ($ids && ! empty($catIds) && empty(array_intersect($ids, $catIds))) {
                return false;
            }
        }

        // Countries
        if (! empty($filters['country_ids'])) {
            $ids = array_filter(array_map('intval', (array) $filters['country_ids']));
            if ($ids && ! in_array((int) $job->country_id, $ids)) {
                return false;
            }
        }

        // City / Province — LIKE on address field
        if (! empty($filters['location_keyword'])) {
            $loc = trim((string) $filters['location_keyword']);
            if ($loc !== '' && stripos((string) ($job->address ?? ''), $loc) === false) {
                return false;
            }
        }

        // Experience
        if (! empty($filters['job_experience_id'])) {
            if ((int) $job->job_experience_id !== (int) $filters['job_experience_id']) {
                return false;
            }
        }

        return true;
    }

    private function sendJobToCandidate(string $token, string $gatewayUrl, CandidateAlert $alert, Job $job, ?string $matchedKeyword = null): bool
    {
        $jobUrl  = $job->slugable?->key ? url("/{$job->slugable->key}") : url('/jobs/' . $job->id);
        $company = $job->company?->name ?? '';
        $loc     = $job->full_location ?? $job->address ?? '';

        $msg  = "🔔 *JOB ALERT*\n\n";
        $msg .= "Hi {$alert->candidate_name}! 👋\n\n";
        $msg .= "*{$job->name}*\n";
        if ($company) $msg .= "🏢 {$company}\n";
        if ($loc)     $msg .= "📍 {$loc}\n";

        if ($job->created_at) {
            $msg .= "📅 *Posted:* " . $job->created_at->format('d M Y') . "\n";
        }

        $deadline = $job->application_closing_date ?? $job->expire_date ?? null;
        if ($deadline) {
            $msg .= "⏰ *Deadline:* " . $deadline->format('d M Y') . "\n";
        }

        $types = $job->jobTypes->pluck('name')->filter()->implode(', ');
        if ($types) $msg .= "💼 *Type:* {$types}\n";

        $salary = $job->salary_text ?? null;
        if ($salary) $msg .= "💰 *Salary:* {$salary}\n";

        if (($job->number_of_positions ?? 0) > 1) {
            $msg .= "👥 *Positions:* {$job->number_of_positions}\n";
        }

        $msg .= "\n👉 *Apply:* {$jobUrl}\n\n";
        if ($matchedKeyword) {
            $msg .= "_🔎 Matched: \"{$matchedKeyword}\"_\n";
        }
        $msg .= "_Wakanda Jobs VIP Alert — wakandajobs.com_";

        $imgField = trim((string) ($job->whatsapp_image ?? ''));
        $imageUrl = $imgField !== '' ? RvMedia::getImageUrl($imgField) : null;

        $sentToAny = false;

        foreach ($alert->recipientJids() as $jid) {
            try {
                if ($imageUrl) {
                    $response = Http::timeout(30)->withToken($token)->post("{$gatewayUrl}/messages/image", [
                        'to'      => $jid,
                        'media'   => $imageUrl,
                        'caption' => $msg,
                    ]);

                    if ($response->successful()) {
                        $sentToAny = true;
                        continue;
                    }
                }

                $response = Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                    'to'   => $jid,
                    'body' => $msg,
                ]);

                if ($response->successful()) {
                    $sentToAny = true;
                }
            } catch (Throwable) {
                // try next number
            }
        }

        return $sentToAny;
    }

    private function getWhapiCredentials(): array
    {
        $automation = SocialAutomation::where('platform', 'whapi')->where('is_active', true)->first();
        if (! $automation) return [null, null];

        $settings   = $automation->settings ?? [];
        $token      = SocialAutomation::whapiToken($automation);
        $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';

        return $token ? [$token, $gatewayUrl] : [null, null];
    }
}
