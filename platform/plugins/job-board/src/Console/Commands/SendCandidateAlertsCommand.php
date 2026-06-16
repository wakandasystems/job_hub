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
            $sent = $this->processAlert($alert, $token, $gatewayUrl, $hours);
            $totalSent += $sent;

            if ($sent > 0) {
                sleep(random_int(15, 25));
            }
        }

        $this->info("Done. Total jobs sent: {$totalSent}");

        return self::SUCCESS;
    }

    private function processAlert(CandidateAlert $alert, string $token, string $gatewayUrl, int $hours): int
    {
        $alreadySentToday = $alert->logs()
            ->where('status', 'sent')
            ->whereDate('sent_at', today())
            ->exists();

        if ($alreadySentToday) {
            return 0;
        }

        $filters    = $alert->filters ?? [];
        $sentJobIds = $alert->logs()->pluck('job_id')->toArray();

        $query = Job::query()
            ->select('jb_jobs.*')
            ->where('jb_jobs.status', JobStatusEnum::PUBLISHED)
            ->where('jb_jobs.created_at', '>=', now()->subHours($hours))
            ->whereNotIn('jb_jobs.id', $sentJobIds)
            ->with(['company', 'slugable', 'jobTypes', 'currency'])
            ->latest('jb_jobs.created_at');

        // Keywords — searches title, description, address (not company name)
        $keywords = array_values(array_filter(array_map('trim', (array) ($filters['keywords'] ?? (($filters['keyword'] ?? null) ? [$filters['keyword']] : [])))));
        if ($keywords) {
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    $pat = '\\b' . preg_quote(strtolower($kw), '/') . '\\b';
                    $q->orWhereRaw('LOWER(jb_jobs.name) REGEXP ?', [$pat])
                      ->orWhereRaw('LOWER(jb_jobs.description) REGEXP ?', [$pat])
                      ->orWhereRaw('LOWER(jb_jobs.address) REGEXP ?', [$pat]);
                }
            });
        }

        // Company keywords — LIKE search against company name
        if (! empty($filters['company_keywords'])) {
            $companyKws = array_filter(array_map('trim', (array) $filters['company_keywords']));
            if ($companyKws) {
                $query->whereHas('company', function ($q) use ($companyKws) {
                    $q->where(function ($q2) use ($companyKws) {
                        foreach ($companyKws as $ck) {
                            $q2->orWhere('name', 'like', "%{$ck}%");
                        }
                    });
                });
            }
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

        $jobs = $query->limit(10)->get();

        if ($jobs->isEmpty()) {
            return 0;
        }

        $this->line("  → {$alert->candidate_name} ({$alert->candidate_phone}): {$jobs->count()} job(s) in one digest");

        $ok = $this->sendDigestToCandidate($token, $gatewayUrl, $alert, $jobs);

        foreach ($jobs as $job) {
            CandidateAlertLog::create([
                'candidate_alert_id' => $alert->id,
                'job_id'             => $job->id,
                'status'             => $ok ? 'sent' : 'failed',
                'error_message'      => $ok ? null : 'Daily digest request failed',
                'sent_at'            => now(),
            ]);
        }

        return $ok ? $jobs->count() : 0;
    }

    private function sendDigestToCandidate(
        string $token,
        string $gatewayUrl,
        CandidateAlert $alert,
        $jobs
    ): bool
    {
        $message = "Hi {$alert->candidate_name},\n\n";
        $message .= "Here is your daily Wakanda Jobs summary based on the VIP job-alert preferences you requested.\n\n";

        foreach ($jobs as $index => $job) {
            $jobUrl = $job->slugable?->key ? url("/{$job->slugable->key}") : url('/jobs/' . $job->id);
            $company = $job->company?->name;
            $location = $job->full_location ?? $job->address;

            $message .= ($index + 1) . ". *{$job->name}*\n";
            if ($company) {
                $message .= "{$company}\n";
            }
            if ($location) {
                $message .= "{$location}\n";
            }
            $message .= "{$jobUrl}\n\n";
        }

        $message .= "You receive at most one job digest per day. To stop these alerts, contact Wakanda Jobs support.\n";
        $message .= "_Wakanda Jobs VIP Alerts_";

        $sentToAny = false;

        foreach ($alert->recipientJids() as $jid) {
            try {
                $response = Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                    'to'   => $jid,
                    'body' => $message,
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
