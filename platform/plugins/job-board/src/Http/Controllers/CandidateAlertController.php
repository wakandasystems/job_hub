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
use Botble\JobBoard\Tables\CandidateAlertTable;
use Botble\Media\Facades\RvMedia;
use Botble\Newsletter\Jobs\DispatchNewsletterBatchJob;
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
    public function index(CandidateAlertTable $table): mixed
    {
        // For DataTable AJAX data requests, no modal data needed
        if (request()->ajax()) {
            return $table->renderTable();
        }

        $jobTypes    = JobType::query()->orderBy('name')->pluck('name', 'id');
        $categories  = Category::query()->orderBy('name')->pluck('name', 'id');
        $experiences = JobExperience::query()->orderBy('name')->pluck('name', 'id');
        $countries   = DB::table('countries')->where('status', 'published')->orderBy('name')->pluck('name', 'id');

        $stats = [
            'total'      => CandidateAlert::count(),
            'active'     => CandidateAlert::where('status', 'active')->where('is_active', true)->count(),
            'expired'    => CandidateAlert::where('status', 'expired')->count(),
            'sent_today' => CandidateAlertLog::whereDate('sent_at', today())->count(),
        ];

        return $table->renderTable(compact('jobTypes', 'categories', 'experiences', 'countries', 'stats'));
    }

    public function edit(CandidateAlert $candidateAlert): View
    {
        $jobTypes    = JobType::query()->orderBy('name')->pluck('name', 'id');
        $categories  = Category::query()->orderBy('name')->pluck('name', 'id');
        $experiences = JobExperience::query()->orderBy('name')->pluck('name', 'id');
        $countries   = DB::table('countries')->where('status', 'published')->orderBy('name')->pluck('name', 'id');

        return view('plugins/job-board::candidate-alerts.edit', [
            'alert'       => $candidateAlert,
            'jobTypes'    => $jobTypes,
            'categories'  => $categories,
            'experiences' => $experiences,
            'countries'   => $countries,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'candidate_name'    => ['required', 'string', 'max:100'],
            'candidate_phone'   => ['required', 'string', 'max:30'],
            'candidate_phone_2' => ['nullable', 'string', 'max:30'],
            'candidate_email'   => ['nullable', 'email', 'max:150'],
            'duration_days'   => ['required', 'in:7,30,60'],
            'filters'         => ['nullable', 'array'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'cv_file'         => ['nullable', 'file', 'mimes:pdf,doc,docx,txt', 'max:10240'],
        ]);

        // Auto-generate a unique label from the candidate name
        $base = $data['candidate_name'];
        $label = $base;
        $counter = 1;
        while (CandidateAlert::where('candidate_phone', $data['candidate_phone'])->where('label', $label)->exists()) {
            $counter++;
            $label = $base . ' (' . $counter . ')';
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
                'label'           => $label,
                'candidate_name'    => $data['candidate_name'],
                'candidate_phone'   => $data['candidate_phone'],
                'candidate_phone_2' => $data['candidate_phone_2'] ?? null,
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
            'candidate_name'    => ['required', 'string', 'max:100'],
            'candidate_phone'   => ['required', 'string', 'max:30'],
            'candidate_phone_2' => ['nullable', 'string', 'max:30'],
            'candidate_email'   => ['nullable', 'email', 'max:150'],
            'filters'         => ['nullable', 'array'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'cv_file'         => ['nullable', 'file', 'mimes:pdf,doc,docx,txt', 'max:10240'],
            'duration_days'   => ['nullable', 'in:7,30,60'],
            'extend_from'     => ['nullable', 'in:today,original'],
        ]);

        $oldFilters = $candidateAlert->filters ?? [];
        $newFilters = $this->cleanFilters($data['filters'] ?? []);

        // Regenerate label if name changed, keeping it unique for this phone
        $base = $data['candidate_name'];
        $label = $base;
        $counter = 1;
        while (CandidateAlert::where('candidate_phone', $data['candidate_phone'])
            ->where('label', $label)
            ->where('id', '!=', $candidateAlert->id)
            ->exists()) {
            $counter++;
            $label = $base . ' (' . $counter . ')';
        }

        $updatePayload = [
            'label'           => $label,
            'candidate_name'  => $data['candidate_name'],
            'candidate_phone'   => $data['candidate_phone'],
            'candidate_phone_2' => $data['candidate_phone_2'] ?? null,
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

    public function sendWelcome(CandidateAlert $candidateAlert): JsonResponse
    {
        [$token] = $this->getWhapiCredentials();

        if (! $token) {
            return response()->json(['error' => 'No active Whapi automation configured.'], 422);
        }

        $this->sendWelcomeMessage($candidateAlert);

        return response()->json(['message' => "Welcome message sent to {$candidateAlert->candidate_name}."]);
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
            'id'            => $job->id,
            'name'          => $job->name,
            'company'       => $job->company?->name ?? '',
            'company_logo'  => $job->company?->logo ? \Botble\Media\Facades\RvMedia::getImageUrl($job->company->logo) : null,
            'city'          => $job->city?->name ?? '',
            'state'         => $job->state?->name ?? '',
            'country'       => $job->country?->name ?? '',
            'location'      => $this->jobLocation($job),
            'created'       => $job->created_at?->format('d M Y'),
            'created_date'  => $job->created_at?->format('Y-m-d'),
            'deadline'      => $job->never_expired ? null : ($job->expire_date ? \Carbon\Carbon::parse($job->expire_date)->format('d M Y') : null),
            'deadline_days' => $job->never_expired ? null : ($job->expire_date ? max(-999, (int) now()->diffInDays(\Carbon\Carbon::parse($job->expire_date), false)) : null),
            'already_sent'  => in_array($job->id, $sentJobIds),
        ]);

        return response()->json(['data' => $data, 'total' => $jobs->count()]);
    }

    public function sendNow(CandidateAlert $candidateAlert, Request $request): JsonResponse
    {
        [$token, $gatewayUrl] = $this->getWhapiCredentials();

        if (! $token) {
            return response()->json(['error' => 'No active Whapi automation configured.'], 422);
        }

        $jobIds = array_filter(array_map('intval', (array) $request->input('job_ids', [])));

        if (! $jobIds) {
            return response()->json(['error' => 'No jobs specified.'], 422);
        }

        $forceResend = (bool) $request->input('force_resend', false);

        $jobs = Job::query()
            ->whereIn('id', $jobIds)
            ->with(['company', 'slugable', 'jobTypes', 'currency'])
            ->get();

        $sentCount = 0;

        foreach ($jobs as $job) {
            if (! $forceResend && $candidateAlert->logs()->where('job_id', $job->id)->where('status', 'sent')->exists()) {
                continue;
            }

            $ok = $this->sendJobMessage($token, $gatewayUrl, $candidateAlert, $job);

            CandidateAlertLog::create([
                'candidate_alert_id' => $candidateAlert->id,
                'job_id'             => $job->id,
                'status'             => $ok ? 'sent' : 'failed',
                'error_message'      => $ok ? null : 'Manual send failed',
                'sent_at'            => now(),
            ]);

            if ($ok) {
                $sentCount++;
            }
        }

        if ($sentCount === 0) {
            return response()->json(['error' => 'Failed to send job(s) via WhatsApp.'], 422);
        }

        return response()->json(['message' => "Sent {$sentCount} job(s)."]);
    }

    public function previewFilters(Request $request): JsonResponse
    {
        $filters = $this->cleanFilters($request->input('filters', []));

        $mock          = new CandidateAlert();
        $mock->filters = $filters;

        $jobs = $this->getMatchingJobs($mock);

        // Load categories for match-reason analysis (not in default eager-loads)
        if (! empty($filters['category_ids'])) {
            $jobs->loadMissing('categories');
        }

        $data = $jobs->take(200)->map(fn (Job $job) => [
            'id'            => $job->id,
            'name'          => $job->name,
            'company'       => $job->company?->name ?? '',
            'location'      => $this->jobLocation($job),
            'country'       => $job->country?->name ?? '',
            'created'       => $job->created_at?->format('d M Y'),
            'match_reasons' => $this->getMatchReasons($job, $filters),
        ]);

        return response()->json(['data' => $data, 'total' => $jobs->count()]);
    }

    private function getMatchReasons(Job $job, array $filters): array
    {
        $reasons = [];

        // Keywords — check name, stripped description, and address
        $keywords = array_values(array_filter(array_map('trim', (array) ($filters['keywords'] ?? []))));
        foreach ($keywords as $kw) {
            $kwPat = '/\b' . preg_quote($kw, '/') . '\b/iu';
            if (preg_match($kwPat, $job->name)) {
                $reasons[] = ['type' => 'keyword', 'keyword' => $kw, 'field' => 'Job Title',    'snippet' => $this->kwSnippet($job->name, $kw)];
            }
            $desc = trim(preg_replace('/\s+/', ' ', strip_tags($job->description ?? '')));
            if ($desc !== '' && preg_match($kwPat, $desc)) {
                $reasons[] = ['type' => 'keyword', 'keyword' => $kw, 'field' => 'Description', 'snippet' => $this->kwSnippet($desc, $kw)];
            }
            if ($job->address && preg_match($kwPat, $job->address)) {
                $reasons[] = ['type' => 'keyword', 'keyword' => $kw, 'field' => 'Address',     'snippet' => $this->kwSnippet($job->address, $kw)];
            }
        }

        // Company keywords
        foreach ((array) ($filters['company_keywords'] ?? []) as $ck) {
            if ($ck !== '' && $job->company && stripos($job->company->name, $ck) !== false) {
                $reasons[] = ['type' => 'company', 'keyword' => $ck, 'field' => 'Company', 'snippet' => $job->company->name];
            }
        }

        // Job types
        if (! empty($filters['job_type_ids'])) {
            $ids     = array_map('intval', (array) $filters['job_type_ids']);
            $matched = $job->jobTypes->whereIn('id', $ids);
            foreach ($matched as $jt) {
                $reasons[] = ['type' => 'job_type', 'keyword' => null, 'field' => 'Job Type', 'snippet' => $jt->name];
            }
            if ($matched->isEmpty() && $job->jobTypes->isEmpty()) {
                $reasons[] = ['type' => 'job_type', 'keyword' => null, 'field' => 'Job Type', 'snippet' => 'No job type set — crawled job included by default'];
            }
        }

        // Categories
        if (! empty($filters['category_ids'])) {
            $ids     = array_map('intval', (array) $filters['category_ids']);
            $cats    = $job->relationLoaded('categories') ? $job->categories : collect();
            $matched = $cats->whereIn('id', $ids);
            foreach ($matched as $cat) {
                $reasons[] = ['type' => 'category', 'keyword' => null, 'field' => 'Category', 'snippet' => $cat->name];
            }
            if ($matched->isEmpty() && $cats->isEmpty()) {
                $reasons[] = ['type' => 'category', 'keyword' => null, 'field' => 'Category', 'snippet' => 'No category set — crawled job included by default'];
            }
        }

        // Country
        if (! empty($filters['country_ids']) && $job->country_id) {
            if (in_array($job->country_id, array_map('intval', (array) $filters['country_ids']))) {
                $reasons[] = ['type' => 'country', 'keyword' => null, 'field' => 'Country', 'snippet' => $job->country?->name ?? 'Matched'];
            }
        }

        // Location keyword (address free-text)
        if (! empty($filters['location_keyword']) && $job->address) {
            $loc = $filters['location_keyword'];
            if (stripos($job->address, $loc) !== false) {
                $reasons[] = ['type' => 'location', 'keyword' => $loc, 'field' => 'Location / Address', 'snippet' => $this->kwSnippet($job->address, $loc)];
            }
        }

        return $reasons;
    }

    private function kwSnippet(string $text, string $keyword, int $context = 55): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        $pos  = stripos($text, $keyword);
        if ($pos === false) {
            return mb_substr($text, 0, 100) . (mb_strlen($text) > 100 ? '…' : '');
        }
        $start   = max(0, $pos - $context);
        $end     = min(mb_strlen($text), $pos + mb_strlen($keyword) + $context);
        $snippet = mb_substr($text, $start, $end - $start);
        if ($start > 0)                 $snippet = '…' . $snippet;
        if ($end < mb_strlen($text))    $snippet .= '…';
        return $snippet;
    }

    public function sendDiscountNewsletter(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string'],
        ]);

        $subscriberCount = DB::table('newsletters')->where('status', 'subscribed')->count();

        if ($subscriberCount === 0) {
            return response()->json(['error' => 'No subscribed newsletter contacts found.'], 422);
        }

        $sendId = (int) DB::table('newsletter_sends')->insertGetId([
            'subject'          => $data['subject'],
            'body'             => $data['body'],
            'image_url'        => null,
            'pdf_path'         => null,
            'status'           => 'scheduled',
            'recipient_count'  => 0,
            'sent_count'       => 0,
            'failed_count'     => 0,
            'dedup_minutes'    => 0,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        DispatchNewsletterBatchJob::dispatch($sendId)->onQueue('emails');

        return response()->json([
            'message' => "Discount campaign queued for {$subscriberCount} subscriber(s). It will send in the background.",
            'send_id' => $sendId,
        ]);
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
            ->where(fn ($q) => $q->whereNull('jb_jobs.expire_date')->orWhere('jb_jobs.expire_date', '>=', now()->toDateString()))
            ->with(['company', 'slugable', 'jobTypes', 'currency', 'city', 'state', 'country'])
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

        // Job types: include jobs that match OR have no job types assigned (crawled jobs)
        if (! empty($filters['job_type_ids'])) {
            $ids = array_filter(array_map('intval', (array) $filters['job_type_ids']));
            if ($ids) {
                $query->where(fn ($q) => $q
                    ->whereHas('jobTypes', fn ($q2) => $q2->whereIn('jb_job_types.id', $ids))
                    ->orDoesntHave('jobTypes')
                );
            }
        }

        // Categories: include jobs that match OR have no categories assigned (crawled jobs)
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

        // City / Province — LIKE search on the free-text address field
        if (! empty($filters['location_keyword'])) {
            $loc = trim((string) $filters['location_keyword']);
            if ($loc !== '') {
                $query->where('jb_jobs.address', 'like', "%{$loc}%");
            }
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

        foreach ($alert->recipientJids() as $jid) {
            try {
                Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                    'to'   => $jid,
                    'body' => $msg,
                ]);
            } catch (Throwable) {}
        }
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

        foreach ($alert->recipientJids() as $jid) {
            try {
                Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                    'to'   => $jid,
                    'body' => $msg,
                ]);
            } catch (Throwable) {}
        }
    }

    private function buildFilterDescription(CandidateAlert $alert): string
    {
        $filters = $alert->filters ?? [];
        $lines   = [];

        // Keywords (new multi-value, backward-compat with old single)
        $keywords = array_values(array_filter(array_map('trim', (array) ($filters['keywords'] ?? (($filters['keyword'] ?? null) ? [$filters['keyword']] : [])))));
        if ($keywords) {
            $lines[] = '• Keywords: *' . implode('*, *', $keywords) . '*';
        }

        // Countries
        if (! empty($filters['country_ids'])) {
            $ids   = array_filter(array_map('intval', (array) $filters['country_ids']));
            $names = $ids ? DB::table('countries')->whereIn('id', $ids)->pluck('name')->implode(', ') : '';
            if ($names) $lines[] = "• Countries: *{$names}*";
        }

        // City / Province
        if (! empty($filters['location_keyword'])) {
            $lines[] = "• City/Province: *{$filters['location_keyword']}*";
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

        // City / Province — free-text search against the address field
        if (! empty($filters['location_keyword'])) {
            $loc = trim((string) $filters['location_keyword']);
            if ($loc !== '') $clean['location_keyword'] = $loc;
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

        // Company keywords
        if (! empty($filters['company_keywords'])) {
            $companyKws = array_values(array_filter(array_map('trim', (array) $filters['company_keywords'])));
            if ($companyKws) $clean['company_keywords'] = $companyKws;
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
        $token      = SocialAutomation::whapiToken($automation);
        $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';

        return $token ? [$token, $gatewayUrl] : [null, null];
    }
}
