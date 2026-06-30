<?php

namespace Botble\JobBoard\Services;

use Botble\Base\Supports\Language;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AccountEducation;
use Botble\JobBoard\Models\AccountExperience;
use Botble\JobBoard\Models\AccountLanguage;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\JobExperience;
use Botble\JobBoard\Models\JobType;
use Botble\JobBoard\Models\LanguageLevel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AccountCvProfileSyncService
{
    public function analyzeStoredCv(Account $account): array
    {
        if (! $account->resume) {
            throw new RuntimeException('This account does not have a CV on file.');
        }

        $resumePath = Storage::disk('public')->path($account->resume);
        $extension = strtolower(pathinfo($resumePath, PATHINFO_EXTENSION));

        if (! is_file($resumePath)) {
            throw new RuntimeException('The stored CV file could not be found.');
        }

        $cvText = app(CvScoringService::class)->extractTextFromFile($resumePath, $extension);

        if (mb_strlen(trim($cvText)) < 50) {
            throw new RuntimeException('Could not extract enough text from the linked CV.');
        }

        return $this->analyzeFromText($cvText);
    }

    public function analyzeFromText(string $cvText): array
    {
        $jobTypes = JobType::query()->orderBy('name')->pluck('name', 'id')->all();
        $categories = Category::query()->orderBy('name')->pluck('name', 'id')->all();
        $experiences = JobExperience::query()->orderBy('name')->pluck('name', 'id')->all();
        $countries = DB::table('countries')->where('status', 'published')->orderBy('name')->pluck('name', 'id')->all();

        $analysis = app(CvFilterAnalyzerService::class)->analyzeFromText($cvText, $jobTypes, $categories, $experiences, $countries) ?: [];
        $profile = app(CvProfileAnalyzerService::class)->analyzeFromText($cvText);

        if ($profile) {
            $analysis['profile'] = $profile;
        }

        return $analysis;
    }

    public function syncFromAnalysis(Account $account, array $analysis): void
    {
        $profile = is_array($analysis['profile'] ?? null) ? $analysis['profile'] : [];
        $name = $this->splitName((string) ($analysis['candidate_name'] ?? ''));
        $phone = trim((string) ($analysis['candidate_phone'] ?? ''));
        $email = trim((string) ($analysis['candidate_email'] ?? ''));
        $summary = trim((string) ($profile['summary'] ?? ($analysis['summary'] ?? '')));
        $headline = trim((string) ($profile['headline'] ?? ''));
        $location = trim((string) ($profile['location'] ?? ($analysis['location_keyword'] ?? '')));
        $address = trim((string) ($profile['address'] ?? ''));
        $linkedin = trim((string) ($profile['linkedin'] ?? ''));

        if ($name['first_name'] !== '' && trim((string) $account->first_name) === '') {
            $account->first_name = $name['first_name'];
        }

        if ($name['last_name'] !== '' && trim((string) $account->last_name) === '') {
            $account->last_name = $name['last_name'];
        }

        if ($email !== '' && trim((string) $account->email) === '') {
            $account->email = $email;
        }

        if ($phone !== '') {
            $account->call_numbers = $this->mergeContactValues($account->call_numbers ?? [], [$phone]);
            $account->whatsapp_numbers = $this->mergeContactValues($account->whatsapp_numbers ?? [], [$phone]);
        }

        if ($headline !== '' && trim((string) $account->description) === '') {
            $account->description = Str::limit($headline, 400, '');
        } elseif ($summary !== '' && trim((string) $account->description) === '') {
            $account->description = Str::limit($summary, 400, '');
        }

        if ($summary !== '' && trim((string) $account->bio) === '') {
            $account->bio = $summary;
        }

        if ($linkedin !== '' && trim((string) ($account->linkedin ?? '')) === '') {
            $account->linkedin = $linkedin;
        }

        if ($address !== '' && trim((string) $account->address) === '') {
            $account->address = $address;
        }

        if (($profile['education_level'] ?? '') !== '' && ! $account->education_level) {
            $account->education_level = $profile['education_level'];
        }

        if (($profile['experience_years'] ?? '') !== '' && ! $account->experience_years) {
            $account->experience_years = $profile['experience_years'];
        }

        if (($profile['availability'] ?? '') !== '' && ! $account->availability) {
            $account->availability = $profile['availability'];
        }

        $this->applyLocation($account, $analysis, $location);

        $account->profile_updated_at = now();
        $account->save();

        $this->syncEducations($account, $profile['educations'] ?? []);
        $this->syncExperiences($account, $profile['experiences'] ?? []);
        $this->syncLanguages($account, $profile['languages'] ?? []);
    }

    private function splitName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName));

        if ($fullName === '') {
            return ['first_name' => '', 'last_name' => ''];
        }

        $parts = preg_split('/\s+/', $fullName, 2) ?: [];

        return [
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
        ];
    }

    private function mergeContactValues(array $existing, array $incoming): array
    {
        $items = [];

        foreach (array_merge($existing, $incoming) as $value) {
            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            $key = preg_replace('/\D+/', '', $value) ?: Str::lower($value);

            if (isset($items[$key])) {
                continue;
            }

            $items[$key] = $value;
        }

        return array_values($items);
    }

    private function applyLocation(Account $account, array $analysis, string $location): void
    {
        if (! $account->country_id && ! empty($analysis['country_ids'][0])) {
            $account->country_id = (int) $analysis['country_ids'][0];
        }

        if ($location === '') {
            return;
        }

        if (! $account->city_id) {
            $city = DB::table('cities')
                ->select(['id', 'state_id'])
                ->where('name', 'like', $location)
                ->orWhere('name', 'like', '%' . $location . '%')
                ->orderBy('name')
                ->first();

            if ($city) {
                $account->city_id = (int) $city->id;
                $account->state_id = $account->state_id ?: (int) $city->state_id;
            }
        }

        if (! $account->state_id) {
            $state = DB::table('states')
                ->select(['id', 'country_id'])
                ->where('name', 'like', $location)
                ->orWhere('name', 'like', '%' . $location . '%')
                ->orderBy('name')
                ->first();

            if ($state) {
                $account->state_id = (int) $state->id;
                $account->country_id = $account->country_id ?: (int) $state->country_id;
            }
        }

        if (! $account->country_id) {
            $country = DB::table('countries')
                ->select(['id'])
                ->where('status', 'published')
                ->where('name', 'like', $location)
                ->orWhere(function ($query) use ($location): void {
                    $query->where('status', 'published')
                        ->where('name', 'like', '%' . $location . '%');
                })
                ->orderBy('name')
                ->first();

            if ($country) {
                $account->country_id = (int) $country->id;
            }
        }
    }

    private function syncEducations(Account $account, array $rows): void
    {
        foreach ($rows as $row) {
            $school = trim((string) ($row['school'] ?? ''));
            $specialized = trim((string) ($row['specialized'] ?? ''));

            if ($school === '' && $specialized === '') {
                continue;
            }

            $exists = $account->educations()
                ->where('school', $school)
                ->where('specialized', $specialized)
                ->exists();

            if ($exists) {
                continue;
            }

            $account->educations()->create([
                'school' => $school,
                'specialized' => $specialized,
                'description' => trim((string) ($row['description'] ?? '')),
                'started_at' => $this->normalizeDate($row['started_at'] ?? null),
                'ended_at' => $this->normalizeDate($row['ended_at'] ?? null),
            ]);
        }
    }

    private function syncExperiences(Account $account, array $rows): void
    {
        foreach ($rows as $row) {
            $company = trim((string) ($row['company'] ?? ''));
            $position = trim((string) ($row['position'] ?? ''));
            $startedAt = $this->normalizeDate($row['started_at'] ?? null);

            if ($company === '' && $position === '') {
                continue;
            }

            $exists = $account->experiences()
                ->where('company', $company)
                ->where('position', $position)
                ->when($startedAt !== null,
                    fn ($q) => $q->where('started_at', $startedAt),
                    fn ($q) => $q->whereNull('started_at')
                )
                ->exists();

            if ($exists) {
                continue;
            }

            $account->experiences()->create([
                'company' => $company,
                'position' => $position,
                'description' => trim((string) ($row['description'] ?? '')),
                'started_at' => $startedAt,
                'ended_at' => $this->normalizeDate($row['ended_at'] ?? null),
            ]);
        }
    }

    private function syncLanguages(Account $account, array $rows): void
    {
        $languageMap = collect(Language::getLocales() ?: [])
            ->mapWithKeys(function ($name, $code) {
                return [Str::lower(trim((string) $name)) => (string) $code];
            })
            ->all();

        $levels = LanguageLevel::query()->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [Str::lower((string) $name) => (int) $id])
            ->all();

        foreach ($rows as $row) {
            $name = Str::lower(trim((string) ($row['language'] ?? '')));

            if ($name === '' || ! isset($languageMap[$name])) {
                continue;
            }

            $code = $languageMap[$name];
            $levelName = Str::lower(trim((string) ($row['level'] ?? 'intermediate')));
            $levelId = $levels[$levelName] ?? null;

            if (! $levelId) {
                $levelId = LanguageLevel::query()->orderBy('id')->value('id');
            }

            $exists = $account->languages()
                ->where('language', $code)
                ->exists();

            if ($exists) {
                continue;
            }

            $account->languages()->create([
                'language' => $code,
                'language_level_id' => $levelId,
                'is_native' => (bool) ($row['is_native'] ?? false),
            ]);
        }
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}$/', $value)) {
            return $value . '-01-01';
        }

        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            return $value . '-01';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
