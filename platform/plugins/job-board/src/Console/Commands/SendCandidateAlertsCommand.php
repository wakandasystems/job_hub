<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Models\CandidateAlert;
use Botble\JobBoard\Models\CandidateAlertLog;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class SendCandidateAlertsCommand extends Command
{
    protected $signature = 'job-board:send-candidate-alerts {--hours=25 : Hours lookback window for new jobs}';
    protected $description = 'Send new matching job alerts to VIP candidate subscribers via WhatsApp';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $this->info("Sending candidate job alerts (jobs posted in last {$hours}h)...");

        [$token, $gatewayUrl] = $this->getWhapiCredentials();

        if (! $token) {
            $this->warn('No active Whapi automation found. Skipping candidate alerts.');
            return self::SUCCESS;
        }

        $alerts = CandidateAlert::where('is_active', true)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->get();

        $this->info("Processing {$alerts->count()} active alert(s).");
        $totalSent = 0;

        foreach ($alerts as $alert) {
            $totalSent += $this->processAlert($alert, $token, $gatewayUrl, $hours);
        }

        $this->info("Done. Total jobs sent: {$totalSent}");

        return self::SUCCESS;
    }

    private function processAlert(CandidateAlert $alert, string $token, string $gatewayUrl, int $hours): int
    {
        $filters    = $alert->filters ?? [];
        $sentJobIds = $alert->logs()->pluck('job_id')->toArray();

        $query = Job::query()
            ->select('jb_jobs.*')
            ->where('jb_jobs.status', JobStatusEnum::PUBLISHED)
            ->where('jb_jobs.created_at', '>=', now()->subHours($hours))
            ->whereNotIn('jb_jobs.id', $sentJobIds)
            ->with(['company', 'slugable'])
            ->latest('jb_jobs.created_at');

        // Keywords — searches title, description, address and company name
        $keywords = array_values(array_filter(array_map('trim', (array) ($filters['keywords'] ?? (($filters['keyword'] ?? null) ? [$filters['keyword']] : [])))));
        if ($keywords) {
            $query->leftJoin('jb_companies as kw_companies', 'kw_companies.id', '=', 'jb_jobs.company_id');
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    $q->orWhere('jb_jobs.name', 'like', "%{$kw}%")
                      ->orWhere('jb_jobs.description', 'like', "%{$kw}%")
                      ->orWhere('jb_jobs.address', 'like', "%{$kw}%")
                      ->orWhere('kw_companies.name', 'like', "%{$kw}%");
                }
            });
        }

        if (! empty($filters['job_type_ids'])) {
            $ids = array_filter(array_map('intval', (array) $filters['job_type_ids']));
            if ($ids) {
                $query->where(fn ($q) => $q
                    ->whereHas('jobTypes', fn ($q2) => $q2->whereIn('jb_job_types.id', $ids))
                    ->orDoesntHave('jobTypes')
                );
            }
        }

        if (! empty($filters['category_ids'])) {
            $ids = array_filter(array_map('intval', (array) $filters['category_ids']));
            if ($ids) {
                $query->where(fn ($q) => $q
                    ->whereHas('categories', fn ($q2) => $q2->whereIn('jb_categories.id', $ids))
                    ->orDoesntHave('categories')
                );
            }
        }

        // Countries
        if (! empty($filters['country_ids'])) {
            $ids = array_filter(array_map('intval', (array) $filters['country_ids']));
            if ($ids) $query->whereIn('jb_jobs.country_id', $ids);
        }

        // City / Province — LIKE on address field
        if (! empty($filters['location_keyword'])) {
            $loc = trim((string) $filters['location_keyword']);
            if ($loc !== '') {
                $query->where('jb_jobs.address', 'like', "%{$loc}%");
            }
        }

        if (! empty($filters['job_experience_id'])) {
            $query->where('jb_jobs.job_experience_id', (int) $filters['job_experience_id']);
        }

        $jobs = $query->get();

        if ($jobs->isEmpty()) {
            return 0;
        }

        $this->line("  → {$alert->candidate_name} ({$alert->candidate_phone}): {$jobs->count()} new job(s)");

        $sent = 0;
        foreach ($jobs as $job) {
            $ok = $this->sendJobToCandidate($token, $gatewayUrl, $alert, $job);

            CandidateAlertLog::create([
                'candidate_alert_id' => $alert->id,
                'job_id'             => $job->id,
                'status'             => $ok ? 'sent' : 'failed',
                'error_message'      => $ok ? null : 'HTTP request failed',
                'sent_at'            => now(),
            ]);

            if ($ok) {
                $sent++;
            }

            usleep(900_000); // 0.9s delay between messages to avoid rate limiting
        }

        return $sent;
    }

    private function sendJobToCandidate(string $token, string $gatewayUrl, CandidateAlert $alert, Job $job): bool
    {
        $jobUrl  = $job->slugable?->key ? url("/{$job->slugable->key}") : url('/jobs/' . $job->id);
        $company = $job->company?->name ?? '';
        $loc     = $job->full_location ?? $job->address ?? '';

        $msg  = "🔔 *JOB ALERT*\n\n";
        $msg .= "Hi {$alert->candidate_name}! 👋\n\n";
        $msg .= "*{$job->name}*\n";
        if ($company) $msg .= "🏢 {$company}\n";
        if ($loc)     $msg .= "📍 {$loc}\n";
        $msg .= "\n👉 *Apply:* {$jobUrl}\n\n";
        $msg .= "_Wakanda Jobs VIP Alert — wakandajobs.com_";

        try {
            $response = Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                'to'   => $alert->recipientJid(),
                'body' => $msg,
            ]);

            return $response->successful();
        } catch (Throwable) {
            return false;
        }
    }

    private function getWhapiCredentials(): array
    {
        $automation = SocialAutomation::where('platform', 'whapi')->where('is_active', true)->first();
        if (! $automation) return [null, null];

        $settings   = $automation->settings ?? [];
        $token      = trim((string) ($settings['token'] ?? ''));
        $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';

        return $token ? [$token, $gatewayUrl] : [null, null];
    }
}
