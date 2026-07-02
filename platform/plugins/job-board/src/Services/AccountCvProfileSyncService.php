<?php

namespace Botble\JobBoard\Services;

use Botble\Base\Supports\Language;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AccountEducation;
use Botble\JobBoard\Models\AccountExperience;
use Botble\JobBoard\Models\AccountLanguage;
use Botble\JobBoard\Models\AutoCvSession;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\JobExperience;
use Botble\JobBoard\Models\JobSkill;
use Botble\JobBoard\Models\JobType;
use Botble\JobBoard\Models\LanguageLevel;
use Botble\JobBoard\Models\Tag;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AccountCvProfileSyncService
{
    public function analyzeStoredCv(Account $account): array
    {
        $structuredCvSession = $this->findLatestStructuredCvSession($account);

        if ($structuredCvSession) {
            return $this->analysisFromStructuredCv($structuredCvSession);
        }

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

    private function findLatestStructuredCvSession(Account $account): ?AutoCvSession
    {
        return AutoCvSession::query()
            ->where('linked_account_id', $account->getKey())
            ->whereNotNull('structured_cv')
            ->latest('completed_at')
            ->latest('id')
            ->get()
            ->first(fn (AutoCvSession $session) => ! empty($session->structured_cv));
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

    private function analysisFromStructuredCv(AutoCvSession $session): array
    {
        $cv = is_array($session->structured_cv) ? $session->structured_cv : [];
        $summary = trim((string) ($cv['summary'] ?? ''));
        $headline = trim((string) ($cv['headline'] ?? ''));
        $location = trim((string) ($cv['location'] ?? ''));
        $address = trim((string) ($cv['address'] ?? ''));
        $phone = trim((string) (($cv['phone'] ?? '') ?: ($cv['whatsapp'] ?? '')));
        $email = trim((string) ($cv['email'] ?? ''));
        $linkedin = trim((string) ($cv['linkedin'] ?? ''));
        $skills = collect($cv['skills'] ?? [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();

        $profile = [
            'headline' => Str::limit($headline, 160, ''),
            'summary' => Str::limit($summary, 2000, ''),
            'linkedin' => Str::limit($linkedin, 250, ''),
            'address' => Str::limit($address, 250, ''),
            'location' => Str::limit($location, 160, ''),
            'education_level' => $this->guessEducationLevelFromStructuredCv($cv),
            'experience_years' => $this->guessExperienceYearsFromStructuredCv($cv),
            'availability' => '',
            'educations' => $this->mapStructuredCvEducations($cv),
            'experiences' => $this->mapStructuredCvExperiences($cv),
            'languages' => $this->mapStructuredCvLanguages($cv),
            'skills' => $skills,
        ];

        return [
            'candidate_name' => trim((string) ($cv['full_name'] ?? $session->candidate_name ?? '')),
            'candidate_phone' => $phone,
            'candidate_email' => $email,
            'summary' => Str::limit($summary, 500, ''),
            'location_keyword' => Str::limit($location, 160, ''),
            'keywords' => $this->structuredCvKeywords($cv),
            'profile' => $profile,
        ];
    }

    public function syncFromAnalysis(Account $account, array $analysis): array
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
        $report = [
            'updated_fields' => [],
            'created_educations' => 0,
            'created_experiences' => 0,
            'created_languages' => 0,
            'attached_skills' => 0,
            'attached_tags' => 0,
        ];

        if ($name['first_name'] !== '' && trim((string) $account->first_name) === '') {
            $account->first_name = $name['first_name'];
            $report['updated_fields'][] = 'first_name';
        }

        if ($name['last_name'] !== '' && trim((string) $account->last_name) === '') {
            $account->last_name = $name['last_name'];
            $report['updated_fields'][] = 'last_name';
        }

        if ($email !== '' && trim((string) $account->email) === '') {
            $account->email = $email;
            $report['updated_fields'][] = 'email';
        }

        if ($phone !== '') {
            $callNumbers = $this->mergeContactValues($account->call_numbers ?? [], [$phone]);
            $whatsAppNumbers = $this->mergeContactValues($account->whatsapp_numbers ?? [], [$phone]);

            if ($callNumbers !== ($account->call_numbers ?? [])) {
                $account->call_numbers = $callNumbers;
                $report['updated_fields'][] = 'phone';
            }

            if ($whatsAppNumbers !== ($account->whatsapp_numbers ?? [])) {
                $account->whatsapp_numbers = $whatsAppNumbers;
                $report['updated_fields'][] = 'whatsapp_number';
            }
        }

        if ($headline !== '' && trim((string) $account->description) === '') {
            $account->description = Str::limit($headline, 400, '');
            $report['updated_fields'][] = 'description';
        } elseif ($summary !== '' && trim((string) $account->description) === '') {
            $account->description = Str::limit($summary, 400, '');
            $report['updated_fields'][] = 'description';
        }

        if ($summary !== '' && trim((string) $account->bio) === '') {
            $account->bio = $summary;
            $report['updated_fields'][] = 'bio';
        }

        if ($linkedin !== '' && trim((string) ($account->linkedin ?? '')) === '') {
            $account->linkedin = $linkedin;
            $report['updated_fields'][] = 'linkedin';
        }

        if ($address !== '' && trim((string) $account->address) === '') {
            $account->address = $address;
            $report['updated_fields'][] = 'address';
        }

        if (($profile['education_level'] ?? '') !== '' && ! $account->education_level) {
            $account->education_level = $profile['education_level'];
            $report['updated_fields'][] = 'education_level';
        }

        if (($profile['experience_years'] ?? '') !== '' && ! $account->experience_years) {
            $account->experience_years = $profile['experience_years'];
            $report['updated_fields'][] = 'experience_years';
        }

        if (($profile['availability'] ?? '') !== '' && ! $account->availability) {
            $account->availability = $profile['availability'];
            $report['updated_fields'][] = 'availability';
        }

        $beforeCountryId = (int) $account->country_id;
        $beforeStateId = (int) $account->state_id;
        $beforeCityId = (int) $account->city_id;
        $this->applyLocation($account, $analysis, $location);

        if ((int) $account->country_id !== $beforeCountryId) {
            $report['updated_fields'][] = 'country_id';
        }

        if ((int) $account->state_id !== $beforeStateId) {
            $report['updated_fields'][] = 'state_id';
        }

        if ((int) $account->city_id !== $beforeCityId) {
            $report['updated_fields'][] = 'city_id';
        }

        $account->profile_updated_at = now();
        $account->save();

        $report['created_educations'] = $this->syncEducations($account, $profile['educations'] ?? []);
        $report['created_experiences'] = $this->syncExperiences($account, $profile['experiences'] ?? []);
        $report['created_languages'] = $this->syncLanguages($account, $profile['languages'] ?? []);
        $report['attached_skills'] = $this->syncFavoriteSkills($account, $profile['skills'] ?? []);
        $report['attached_tags'] = $this->syncFavoriteTags($account, $analysis['keywords'] ?? []);
        $report['updated_fields'] = array_values(array_unique($report['updated_fields']));

        return $report;
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

    private function syncEducations(Account $account, array $rows): int
    {
        $created = 0;

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

            $created++;
        }

        return $created;
    }

    private function syncExperiences(Account $account, array $rows): int
    {
        $created = 0;

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

            $created++;
        }

        return $created;
    }

    private function syncLanguages(Account $account, array $rows): int
    {
        $created = 0;
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

            $created++;
        }

        return $created;
    }

    private function syncFavoriteSkills(Account $account, array $skills): int
    {
        $terms = $this->normalizeMatchingTerms($skills);

        if ($terms === []) {
            return 0;
        }

        $existingIds = $account->favoriteSkills()->pluck('jb_job_skills.id')->map(fn ($id) => (int) $id)->all();
        $matches = JobSkill::query()
            ->select(['id', 'name'])
            ->get()
            ->filter(fn (JobSkill $skill) => in_array($this->normalizeMatchValue($skill->name), $terms, true))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->diff($existingIds)
            ->take(12)
            ->values()
            ->all();

        if ($matches !== []) {
            $account->favoriteSkills()->attach($matches);
        }

        return count($matches);
    }

    private function syncFavoriteTags(Account $account, array $tags): int
    {
        $terms = $this->normalizeMatchingTerms($tags);

        if ($terms === []) {
            return 0;
        }

        $existingIds = $account->favoriteTags()->pluck('jb_tags.id')->map(fn ($id) => (int) $id)->all();
        $matches = Tag::query()
            ->select(['id', 'name'])
            ->wherePublished()
            ->get()
            ->filter(fn (Tag $tag) => in_array($this->normalizeMatchValue($tag->name), $terms, true))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->diff($existingIds)
            ->take(12)
            ->values()
            ->all();

        if ($matches !== []) {
            $account->favoriteTags()->attach($matches);
        }

        return count($matches);
    }

    private function normalizeMatchingTerms(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => $this->normalizeMatchValue((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeMatchValue(string $value): string
    {
        $value = Str::lower(trim($value));

        if ($value === '') {
            return '';
        }

        return preg_replace('/[^a-z0-9]+/i', '', $value) ?: '';
    }

    private function mapStructuredCvEducations(array $cv): array
    {
        return collect($cv['education'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row): array {
                $qualification = trim((string) ($row['qualification'] ?? ''));
                $field = trim((string) ($row['field'] ?? ''));

                return [
                    'school' => Str::limit(trim((string) ($row['institution'] ?? '')), 120, ''),
                    'specialized' => Str::limit(trim(implode(' - ', array_filter([$qualification, $field]))), 120, ''),
                    'description' => '',
                    'started_at' => $this->normalizeDate($row['start_year'] ?? $row['start_date'] ?? null),
                    'ended_at' => $this->normalizeDate($row['end_year'] ?? $row['end_date'] ?? null),
                ];
            })
            ->filter(fn (array $row) => $row['school'] !== '' || $row['specialized'] !== '')
            ->values()
            ->all();
    }

    private function mapStructuredCvExperiences(array $cv): array
    {
        return collect($cv['experience'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row): array {
                $responsibilities = collect($row['responsibilities'] ?? [])
                    ->map(fn ($item) => trim((string) $item))
                    ->filter()
                    ->implode("\n");

                return [
                    'company' => Str::limit(trim((string) ($row['company'] ?? '')), 120, ''),
                    'position' => Str::limit(trim((string) ($row['job_title'] ?? '')), 120, ''),
                    'description' => Str::limit($responsibilities, 400, ''),
                    'started_at' => $this->normalizeDate($row['start_date'] ?? $row['start_year'] ?? null),
                    'ended_at' => $this->normalizeDate($row['end_date'] ?? $row['end_year'] ?? null),
                ];
            })
            ->filter(fn (array $row) => $row['company'] !== '' || $row['position'] !== '')
            ->values()
            ->all();
    }

    private function mapStructuredCvLanguages(array $cv): array
    {
        return collect($cv['languages'] ?? [])
            ->map(function ($row): ?array {
                if (is_string($row)) {
                    return [
                        'language' => trim($row),
                        'level' => 'Intermediate',
                        'is_native' => false,
                    ];
                }

                if (! is_array($row)) {
                    return null;
                }

                $level = trim((string) (($row['proficiency'] ?? '') ?: ($row['level'] ?? '')));
                $normalizedLevel = match (Str::lower($level)) {
                    'native', 'fluent', 'expert', 'advanced' => 'Expert',
                    'basic', 'beginner', 'elementary' => 'Beginner',
                    default => 'Intermediate',
                };

                return [
                    'language' => trim((string) ($row['language'] ?? '')),
                    'level' => $normalizedLevel,
                    'is_native' => Str::lower($level) === 'native' || (bool) ($row['is_native'] ?? false),
                ];
            })
            ->filter(fn ($row) => is_array($row) && $row['language'] !== '')
            ->values()
            ->all();
    }

    private function guessEducationLevelFromStructuredCv(array $cv): string
    {
        $text = collect($cv['education'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row) => implode(' ', array_filter([
                (string) ($row['qualification'] ?? ''),
                (string) ($row['field'] ?? ''),
                (string) ($row['institution'] ?? ''),
            ])))
            ->implode(' ');

        $text = Str::lower($text);

        if (preg_match('/\b(phd|doctorate)\b/', $text)) {
            return 'phd';
        }

        if (preg_match('/\b(master|mba|msc|ma)\b/', $text)) {
            return 'masters';
        }

        if (preg_match('/\b(bachelor|degree|bsc|ba|bed|beng|bcom)\b/', $text)) {
            return 'bachelor';
        }

        if (preg_match('/\b(diploma|certificate)\b/', $text)) {
            return 'diploma';
        }

        if ($text !== '') {
            return 'high_school';
        }

        return '';
    }

    private function guessExperienceYearsFromStructuredCv(array $cv): string
    {
        $periods = collect($cv['experience'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row): int {
                $start = $this->normalizeDate($row['start_date'] ?? $row['start_year'] ?? null);

                if (! $start) {
                    return 0;
                }

                $end = $this->normalizeDate($row['end_date'] ?? $row['end_year'] ?? null) ?: now()->format('Y-m-d');

                try {
                    return max(0, Carbon::parse($start)->diffInMonths(Carbon::parse($end)));
                } catch (\Throwable) {
                    return 0;
                }
            });

        $months = (int) $periods->sum();

        return match (true) {
            $months >= 120 => '10',
            $months >= 60 => '5',
            $months >= 36 => '3',
            $months >= 12 => '2',
            $months > 0 => '1',
            default => '',
        };
    }

    private function structuredCvKeywords(array $cv): array
    {
        return collect(array_merge(
            (array) ($cv['skills'] ?? []),
            [trim((string) ($cv['headline'] ?? ''))]
        ))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique(fn ($value) => Str::lower($value))
            ->take(15)
            ->values()
            ->all();
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
