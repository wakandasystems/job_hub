<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\JobBoard\Enums\JobStatusEnum;
use Botble\JobBoard\Models\CandidateAlert;
use Botble\JobBoard\Models\CandidateAlertLog;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobExperience;
use Botble\JobBoard\Models\JobType;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\CvFilterAnalyzerService;
use Botble\JobBoard\Services\CvScoringService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CandidateAlertController
{
    public function index(): View
    {
        $alerts      = CandidateAlert::with(['logs'])->latest()->paginate(20)->withQueryString();
        $jobTypes    = JobType::query()->orderBy('name')->pluck('name', 'id');
        $categories  = Category::query()->orderBy('name')->pluck('name', 'id');
        $experiences = JobExperience::query()->orderBy('name')->pluck('name', 'id');
        $countries   = DB::table('countries')->where('status', 'published')->orderBy('name')->pluck('name', 'id');

        // Only load city names that are actually referenced by visible alerts (avoids 48k query)
        $usedCityIds = [];
        foreach ($alerts as $alert) {
            $f = $alert->filters ?? [];
            foreach ((array) ($f['city_ids'] ?? (($f['city_id'] ?? null) ? [$f['city_id']] : [])) as $id) {
                if ($id) $usedCityIds[] = (int) $id;
            }
        }
        $cities = $usedCityIds
            ? DB::table('cities')->whereIn('id', array_unique($usedCityIds))->pluck('name', 'id')
            : collect();

        $stats = [
            'total'      => CandidateAlert::count(),
            'active'     => CandidateAlert::where('status', 'active')->where('is_active', true)->count(),
            'expired'    => CandidateAlert::where('status', 'expired')->count(),
            'sent_today' => CandidateAlertLog::whereDate('sent_at', today())->count(),
        ];

        return view('plugins/job-board::candidate-alerts.index', compact(
            'alerts', 'jobTypes', 'categories', 'experiences', 'countries', 'cities', 'stats'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label'           => ['required', 'string', 'max:150'],
            'candidate_name'  => ['required', 'string', 'max:100'],
            'candidate_phone' => ['required', 'string', 'max:30'],
            'candidate_email' => ['nullable', 'email', 'max:150'],
            'duration_days'   => ['required', 'in:7,14,30'],
            'filters'         => ['nullable', 'array'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'cv_file'         => ['nullable', 'file', 'mimes:pdf,doc,docx,txt', 'max:10240'],
        ]);

        // Server-side duplicate guard (catches both manual double-saves and race conditions)
        $duplicate = CandidateAlert::where('candidate_phone', $data['candidate_phone'])
            ->where('label', $data['label'])
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'label' => 'An alert with this name already exists for this phone number.',
            ]);
        }

        $duration  = (int) $data['duration_days'];
        $price     = CandidateAlert::$durations[$duration]['price'];
        $now       = now();

        $cvPath = null;
        if ($request->hasFile('cv_file')) {
            $cvPath = $request->file('cv_file')->store('candidate-cvs', 'local');
        }

        try {
            $alert = CandidateAlert::create([
                'label'           => $data['label'],
                'candidate_name'  => $data['candidate_name'],
                'candidate_phone' => $data['candidate_phone'],
                'candidate_email' => $data['candidate_email'] ?? null,
                'filters'         => $this->cleanFilters($data['filters'] ?? []),
                'duration_days'   => $duration,
                'price'           => $price,
                'is_active'       => true,
                'status'          => 'active',
                'activated_at'    => $now,
                'expires_at'      => $now->copy()->addDays($duration),
                'notes'           => $data['notes'] ?? null,
                'cv_path'         => $cvPath,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'label' => 'This alert was already saved. Please check the alerts list.',
            ]);
        }

        $this->sendWelcomeMessage($alert);

        return redirect()->route('job-board.candidate-alerts.index')
            ->with('success_message', "Alert for {$alert->candidate_name} created. Welcome message sent via WhatsApp.");
    }

    public function update(CandidateAlert $candidateAlert, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label'           => ['required', 'string', 'max:150'],
            'candidate_name'  => ['required', 'string', 'max:100'],
            'candidate_phone' => ['required', 'string', 'max:30'],
            'candidate_email' => ['nullable', 'email', 'max:150'],
            'filters'         => ['nullable', 'array'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'cv_file'         => ['nullable', 'file', 'mimes:pdf,doc,docx,txt', 'max:10240'],
            'duration_days'   => ['nullable', 'in:7,14,30'],
            'extend_from'     => ['nullable', 'in:today,original'],
        ]);

        $oldFilters = $candidateAlert->filters ?? [];
        $newFilters = $this->cleanFilters($data['filters'] ?? []);

        $duplicate = CandidateAlert::where('candidate_phone', $data['candidate_phone'])
            ->where('label', $data['label'])
            ->where('id', '!=', $candidateAlert->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'label' => 'Another alert with this name already exists for this phone number.',
            ]);
        }

        $updatePayload = [
            'label'           => $data['label'],
            'candidate_name'  => $data['candidate_name'],
            'candidate_phone' => $data['candidate_phone'],
            'candidate_email' => $data['candidate_email'] ?? null,
            'filters'         => $newFilters,
            'notes'           => $data['notes'] ?? null,
        ];

        if ($request->hasFile('cv_file')) {
            $updatePayload['cv_path'] = $request->file('cv_file')->store('candidate-cvs', 'local');
        }

        // Package upgrade / renewal
        if (! empty($data['duration_days'])) {
            $duration   = (int) $data['duration_days'];
            $extendFrom = $data['extend_from'] ?? 'today';
            $startDate  = ($extendFrom === 'original' && $candidateAlert->activated_at)
                ? $candidateAlert->activated_at->copy()
                : now();

            $updatePayload['duration_days']        = $duration;
            $updatePayload['price']                = CandidateAlert::$durations[$duration]['price'];
            $updatePayload['expires_at']           = $startDate->addDays($duration);
            $updatePayload['status']               = 'active';
            $updatePayload['is_active']            = true;
            $updatePayload['expiry_warning_sent']  = false;
            $updatePayload['expiry_notice_sent']   = false;
        }

        try {
            $candidateAlert->update($updatePayload);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'label' => 'Another alert with this name already exists for this phone number.',
            ]);
        }

        $upgraded = ! empty($data['duration_days']);
        $msg = $upgraded
            ? "Alert updated and package renewed to {$data['duration_days']} days successfully."
            : 'Alert updated successfully.';

        return redirect()->route('job-board.candidate-alerts.index')
            ->with('success_message', $msg);
    }

    public function destroy(CandidateAlert $candidateAlert): JsonResponse
    {
        $candidateAlert->delete();

        return response()->json(['message' => 'Alert deleted.']);
    }

    public function checkPhone(Request $request): JsonResponse
    {
        $phone     = trim((string) $request->input('phone', ''));
        $excludeId = (int) $request->input('exclude_id', 0);

        if ($phone === '') {
            return response()->json(['exists' => false, 'alerts' => []]);
        }

        $query = CandidateAlert::where('candidate_phone', $phone);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existing = $query->get(['id', 'label', 'status', 'is_active']);

        return response()->json([
            'exists' => $existing->isNotEmpty(),
            'alerts' => $existing->map(fn ($a) => [
                'id'     => $a->id,
                'label'  => $a->label,
                'status' => $a->status,
                'active' => $a->is_active,
            ]),
        ]);
    }

    public function locationStates(Request $request): JsonResponse
    {
        $countryId = (int) $request->input('country_id');
        if (! $countryId) {
            return response()->json([]);
        }

        $states = DB::table('states')
            ->where('country_id', $countryId)
            ->where('status', 'published')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($states);
    }

    public function locationCities(Request $request): JsonResponse
    {
        $stateId   = (int) $request->input('state_id');
        $countryId = (int) $request->input('country_id');
        $all       = $request->boolean('all');

        if (! $stateId && ! $countryId) {
            return response()->json(['data' => [], 'total' => 0]);
        }

        $search = trim((string) $request->input('search', ''));

        $query = DB::table('cities')->where('status', 'published')->orderBy('name');

        if ($stateId) {
            $query->where('state_id', $stateId);
        } elseif ($countryId) {
            $query->where('country_id', $countryId);
        }

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        // ?all=1 returns every city (used for "Add all in state/country" shortcuts)
        if ($all) {
            $cities = $query->limit(500)->get(['id', 'name']);
            return response()->json(['data' => $cities, 'total' => $cities->count(), 'all' => true]);
        }

        $total  = $query->count();
        $page   = (int) $request->input('page', 1);
        $cities = $query->offset(($page - 1) * 3)->limit(3)->get(['id', 'name']);

        return response()->json([
            'data'     => $cities,
            'total'    => $total,
            'has_more' => $total > ($page * 3),
            'page'     => $page,
        ]);
    }

    public function analyzeCv(Request $request): JsonResponse
    {
        $request->validate([
            'cv_file' => ['required', 'file', 'mimes:pdf,doc,docx,txt', 'max:10240'],
        ]);

        $file      = $request->file('cv_file');
        $realPath  = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        $scorer  = app(CvScoringService::class);
        $cvText  = $scorer->extractTextFromFile($realPath, $extension);

        if (strlen(trim($cvText)) < 50) {
            return response()->json(['error' => 'Could not extract text from the CV. Please try a different file format.'], 422);
        }

        $jobTypes   = JobType::query()->orderBy('name')->pluck('name', 'id')->toArray();
        $categories = Category::query()->orderBy('name')->pluck('name', 'id')->toArray();
        $experiences = JobExperience::query()->orderBy('name')->pluck('name', 'id')->toArray();
        $cities     = DB::table('cities')->where('status', 'published')->orderBy('name')->pluck('name', 'id')->toArray();

        $analyzer = app(CvFilterAnalyzerService::class);
        $result   = $analyzer->analyzeFromText($cvText, $jobTypes, $categories, $experiences, $cities);

        if (! $result) {
            return response()->json(['error' => 'CV analysis failed. Check that ANTHROPIC_API_KEY is configured.'], 422);
        }

        // Label job type names and category names for the UI
        $result['job_type_names']  = array_values(array_intersect_key($jobTypes, array_flip($result['job_type_ids'])));
        $result['category_names']  = array_values(array_intersect_key($categories, array_flip($result['category_ids'])));
        $result['experience_name'] = $result['job_experience_id'] ? ($experiences[$result['job_experience_id']] ?? null) : null;
        $result['country_name']    = $result['city_id'] ? ($cities[$result['city_id']] ?? null) : null;

        return response()->json(['data' => $result]);
    }

    public function toggle(CandidateAlert $candidateAlert): JsonResponse
    {
        $wasActive = $candidateAlert->is_active;
        $candidateAlert->update(['is_active' => ! $wasActive]);

        if (! $wasActive && $candidateAlert->status !== 'expired') {
            $this->sendWelcomeMessage($candidateAlert->fresh());
        }

        return response()->json(['data' => ['is_active' => ! $wasActive]]);
    }

    public function logs(CandidateAlert $candidateAlert): JsonResponse
    {
        $logs = $candidateAlert->logs()
            ->with(['job:id,name,company_id', 'job.company:id,name'])
            ->latest('sent_at')
            ->limit(200)
            ->get()
            ->map(fn ($log) => [
                'id'       => $log->id,
                'job_id'   => $log->job_id,
                'job_name' => $log->job?->name ?? '(deleted)',
                'company'  => $log->job?->company?->name ?? '',
                'status'   => $log->status,
                'error'    => $log->error_message,
                'sent_at'  => $log->sent_at?->format('d M Y H:i'),
            ]);

        return response()->json(['data' => $logs]);
    }

    public function preview(CandidateAlert $candidateAlert): JsonResponse
    {
        $jobs       = $this->getMatchingJobs($candidateAlert);
        $sentJobIds = $candidateAlert->logs()->pluck('job_id')->toArray();

        $data = $jobs->take(500)->map(fn (Job $job) => [
            'id'           => $job->id,
            'name'         => $job->name,
            'company'      => $job->company?->name ?? '',
            'city'         => $job->city?->name ?? '',
            'state'        => $job->state?->name ?? '',
            'country'      => $job->country?->name ?? '',
            'location'     => $this->jobLocation($job),
            'created'      => $job->created_at?->format('d M Y'),
            'created_date' => $job->created_at?->format('Y-m-d'),
            'already_sent' => in_array($job->id, $sentJobIds),
        ]);

        return response()->json(['data' => $data, 'total' => $jobs->count()]);
    }

    public function sendNow(CandidateAlert $candidateAlert, Request $request): JsonResponse
    {
        $forceResend = $request->boolean('force_resend');
        $jobIds      = $request->input('job_ids');

        if ($jobIds) {
            $jobs = Job::whereIn('id', (array) $jobIds)
                ->where('status', JobStatusEnum::PUBLISHED)
                ->with(['company', 'slugable', 'jobTypes'])
                ->get();
        } else {
            $jobs = $this->getMatchingJobs($candidateAlert, skipAlreadySent: ! $forceResend);
        }

        [$token, $gatewayUrl] = $this->getWhapiCredentials();

        if (! $token) {
            return response()->json(['error' => 'No active Whapi automation configured. Add a Whapi automation on the Automations page first.'], 422);
        }

        $sentJobIds = ! $forceResend ? $candidateAlert->logs()->pluck('job_id')->toArray() : [];
        $sent = $failed = 0;

        foreach ($jobs as $job) {
            if (! $forceResend && in_array($job->id, $sentJobIds)) {
                continue;
            }

            $ok = $this->sendJobMessage($token, $gatewayUrl, $candidateAlert, $job);

            CandidateAlertLog::create([
                'candidate_alert_id' => $candidateAlert->id,
                'job_id'             => $job->id,
                'status'             => $ok ? 'sent' : 'failed',
                'error_message'      => $ok ? null : 'HTTP send failed',
                'sent_at'            => now(),
            ]);

            $ok ? $sent++ : $failed++;
        }

        $msg = "Sent {$sent} job(s)" . ($failed ? ", {$failed} failed." : '.');

        return response()->json(['message' => $msg, 'sent' => $sent, 'failed' => $failed]);
    }

    // -------------------------------------------------------------------------

    private function jobLocation(Job $job): string
    {
        $parts = array_filter([
            $job->city?->name  ?? null,
            $job->state?->name ?? null,
            $job->country?->name ?? null,
        ]);

        if ($parts) {
            return implode(', ', $parts);
        }

        // Crawled jobs store free-text location in address
        return trim((string) $job->address);
    }

    private function getMatchingJobs(CandidateAlert $alert, bool $skipAlreadySent = false, ?int $sinceHours = null)
    {
        $filters = $alert->filters ?? [];

        $query = Job::query()
            ->select('jb_jobs.*')
            ->where('jb_jobs.status', JobStatusEnum::PUBLISHED)
            ->with(['company', 'slugable', 'jobTypes', 'city', 'state', 'country'])
            ->latest('jb_jobs.created_at');

        if ($sinceHours) {
            $query->where('jb_jobs.created_at', '>=', now()->subHours($sinceHours));
        }

        if ($skipAlreadySent) {
            $sent = $alert->logs()->pluck('job_id')->toArray();
            if ($sent) {
                $query->whereNotIn('jb_jobs.id', $sent);
            }
        }

        // Keywords — searches title, description, address and company name
        // select('jb_jobs.*') prevents the joined company `name` column overwriting the job `name`
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
                $query->whereHas('jobTypes', fn ($q) => $q->whereIn('jb_job_types.id', $ids));
            }
        }

        if (! empty($filters['category_ids'])) {
            $ids = array_filter(array_map('intval', (array) $filters['category_ids']));
            if ($ids) {
                $query->whereHas('categories', fn ($q) => $q->whereIn('jb_categories.id', $ids));
            }
        }

        // Countries
        if (! empty($filters['country_ids'])) {
            $ids = array_filter(array_map('intval', (array) $filters['country_ids']));
            if ($ids) $query->whereIn('jb_jobs.country_id', $ids);
        }

        if (! empty($filters['job_experience_id'])) {
            $query->where('jb_jobs.job_experience_id', (int) $filters['job_experience_id']);
        }

        return $query->get();
    }

    private function sendJobMessage(string $token, string $gatewayUrl, CandidateAlert $alert, Job $job): bool
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

    private function sendWelcomeMessage(CandidateAlert $alert): void
    {
        [$token, $gatewayUrl] = $this->getWhapiCredentials();
        if (! $token) return;

        $duration = CandidateAlert::$durations[$alert->duration_days] ?? ['label' => $alert->duration_days . ' Days'];

        $msg  = "🎉 *Welcome to Wakanda Jobs VIP Alert Service!*\n\n";
        $msg .= "Hi {$alert->candidate_name}! 👋\n\n";
        $msg .= "You've been subscribed to our *automated VIP job alert service*. ";
        $msg .= "We'll send you new job openings matching your profile directly on WhatsApp.\n\n";
        $msg .= "📋 *Subscription Details:*\n";
        $msg .= "• Subscription: *{$duration['label']}* — K{$alert->price}\n";
        $msg .= "• Activated: *" . ($alert->activated_at?->format('d M Y') ?? now()->format('d M Y')) . "*\n";
        $msg .= "• Expires: *" . ($alert->expires_at?->format('d M Y') ?? 'N/A') . "*\n\n";
        $msg .= "🔍 *Your Job Filters:*\n";
        $msg .= $this->buildFilterDescription($alert) . "\n\n";
        $msg .= "Sit back and let us find your next opportunity! 🚀\n\n";
        $msg .= "_Wakanda Jobs — wakandajobs.com_";

        try {
            Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                'to'   => $alert->recipientJid(),
                'body' => $msg,
            ]);
        } catch (Throwable) {}
    }

    private function sendFilterUpdateMessage(CandidateAlert $alert): void
    {
        [$token, $gatewayUrl] = $this->getWhapiCredentials();
        if (! $token) return;

        $msg  = "🔔 *Your Job Alert Filters Have Been Updated*\n\n";
        $msg .= "Hi {$alert->candidate_name}! 👋\n\n";
        $msg .= "Your VIP job alert *\"{$alert->label}\"* has been updated with new filters.\n\n";
        $msg .= "🔍 *New Filters:*\n";
        $msg .= $this->buildFilterDescription($alert) . "\n\n";
        $msg .= "You will now receive jobs matching these updated criteria.\n\n";
        $msg .= "_Wakanda Jobs — wakandajobs.com_";

        try {
            Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                'to'   => $alert->recipientJid(),
                'body' => $msg,
            ]);
        } catch (Throwable) {}
    }

    private function buildFilterDescription(CandidateAlert $alert): string
    {
        $filters = $alert->filters ?? [];
        $lines   = [];

        // Keywords (new multi-value, backward-compat with old single)
        $keywords = array_values(array_filter(array_map('trim', (array) ($filters['keywords'] ?? ($filters['keyword'] ? [$filters['keyword']] : [])))));
        if ($keywords) {
            $lines[] = '• Keywords: *' . implode('*, *', $keywords) . '*';
        }

        // Countries
        if (! empty($filters['country_ids'])) {
            $ids   = array_filter(array_map('intval', (array) $filters['country_ids']));
            $names = $ids ? DB::table('countries')->whereIn('id', $ids)->pluck('name')->implode(', ') : '';
            if ($names) $lines[] = "• Countries: *{$names}*";
        }

        if (! empty($filters['job_type_ids'])) {
            $ids   = array_filter(array_map('intval', (array) $filters['job_type_ids']));
            $names = $ids ? JobType::whereIn('id', $ids)->pluck('name')->implode(', ') : '';
            if ($names) $lines[] = "• Job Types: *{$names}*";
        }

        if (! empty($filters['job_experience_id'])) {
            $expName = JobExperience::find((int) $filters['job_experience_id'])?->name ?? 'N/A';
            $lines[] = "• Experience: *{$expName}*";
        }

        return $lines ? implode("\n", $lines) : '• All jobs (no specific filters set)';
    }

    private function cleanFilters(array $filters): array
    {
        $clean = [];

        // Keywords — store as array, discard empty strings
        $keywords = array_values(array_filter(array_map('trim', (array) ($filters['keywords'] ?? []))));
        if ($keywords) {
            $clean['keywords'] = $keywords;
        }

        // Countries
        if (! empty($filters['country_ids'])) {
            $ids = array_values(array_filter(array_map('intval', (array) $filters['country_ids'])));
            if ($ids) $clean['country_ids'] = $ids;
        }

        if (! empty($filters['job_type_ids'])) {
            $ids = array_values(array_filter(array_map('intval', (array) $filters['job_type_ids'])));
            if ($ids) $clean['job_type_ids'] = $ids;
        }

        if (! empty($filters['category_ids'])) {
            $ids = array_values(array_filter(array_map('intval', (array) $filters['category_ids'])));
            if ($ids) $clean['category_ids'] = $ids;
        }

        if (! empty($filters['job_experience_id'])) {
            $clean['job_experience_id'] = (int) $filters['job_experience_id'];
        }

        return $clean;
    }

    private function filtersChanged(array $old, array $new): bool
    {
        return json_encode($old) !== json_encode($new);
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
