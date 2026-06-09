<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\Job;
use Illuminate\Support\Facades\DB;

class CompanyContactService
{
    private ?array $publicSuffixes = null;

    private const AFRICA_CALLING_CODES = [
        'DZ' => '213', 'AO' => '244', 'BJ' => '229', 'BW' => '267', 'BF' => '226',
        'BI' => '257', 'CV' => '238', 'CM' => '237', 'CF' => '236', 'TD' => '235',
        'KM' => '269', 'CG' => '242', 'CD' => '243', 'CI' => '225', 'DJ' => '253',
        'EG' => '20', 'GQ' => '240', 'ER' => '291', 'SZ' => '268', 'ET' => '251',
        'GA' => '241', 'GM' => '220', 'GH' => '233', 'GN' => '224', 'GW' => '245',
        'KE' => '254', 'LS' => '266', 'LR' => '231', 'LY' => '218', 'MG' => '261',
        'MW' => '265', 'ML' => '223', 'MR' => '222', 'MU' => '230', 'MA' => '212',
        'MZ' => '258', 'NA' => '264', 'NE' => '227', 'NG' => '234', 'RW' => '250',
        'ST' => '239', 'SN' => '221', 'SC' => '248', 'SL' => '232', 'SO' => '252',
        'ZA' => '27', 'SS' => '211', 'SD' => '249', 'TZ' => '255', 'TG' => '228',
        'TN' => '216', 'UG' => '256', 'ZM' => '260', 'ZW' => '263',
    ];

    public function syncFromJob(Job $job): void
    {
        if (! $job->company_id) {
            return;
        }

        $company = Company::query()->find($job->company_id);
        if (! $company) {
            return;
        }

        $emails = array_merge(
            $company->contact_emails ?? [],
            [$company->email, $job->apply_email],
            $job->employer_colleagues ?? [],
            $this->extractEmails($job->description . "\n" . $job->content),
        );
        $numbers = array_merge(
            $company->contact_numbers ?? [],
            [$company->phone],
            $this->extractPhones(
                $job->description . "\n" . $job->content,
                $this->countryCode($job->country_id ?: $company->country_id),
            ),
        );

        $company->forceFill([
            'contact_emails' => $this->normalizeEmails($emails),
            'contact_numbers' => $this->normalizePhones($numbers, $this->countryCode($company->country_id)),
        ])->saveQuietly();
    }

    public function backfill(): int
    {
        $count = 0;

        Job::query()
            ->whereNotNull('company_id')
            ->orderBy('id')
            ->chunkById(200, function ($jobs) use (&$count): void {
                foreach ($jobs as $job) {
                    $this->syncFromJob($job);
                    $count++;
                }
            });

        return $count;
    }

    public function normalizeEmails(array $emails): array
    {
        $normalized = collect($emails)
            ->map(fn ($email) => $this->repairEmail(trim((string) $email)))
            ->map(fn ($email) => strtolower($email))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        return $normalized
            ->reject(fn ($email) => $normalized->contains(
                fn ($other) => $other !== $email && str_starts_with($other, $email . '.')
            ))
            ->values()
            ->all();
    }

    public function normalizePhones(array $phones, ?string $countryCode = null): array
    {
        return collect($phones)
            ->map(fn ($phone) => $this->normalizePhone($phone, $countryCode))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function extractEmails(string $text): array
    {
        $text = html_entity_decode(
            preg_replace('/<[^>]+>/', ' ', $text),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );
        preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $matches);

        return $matches[0] ?? [];
    }

    private function extractPhones(string $text, ?string $countryCode): array
    {
        preg_match_all(
            '/\b(?:phone|tel(?:ephone)?|mobile|whatsapp|call|contact)\b[^\d+]{0,30}((?:\+|00)?\d[\d\s().-]{7,18}\d)/i',
            strip_tags($text),
            $matches
        );

        return $this->normalizePhones($matches[1] ?? [], $countryCode);
    }

    private function normalizePhone(mixed $phone, ?string $countryCode): ?string
    {
        $digits = preg_replace('/\D+/', '', trim((string) $phone));
        $digits = str_starts_with($digits, '00') ? substr($digits, 2) : $digits;

        if (str_starts_with($digits, '0')) {
            $dialingCode = self::AFRICA_CALLING_CODES[strtoupper(trim((string) $countryCode))] ?? null;
            if ($dialingCode) {
                $digits = $dialingCode . ltrim($digits, '0');
            }
        }

        return strlen($digits) >= 10 && strlen($digits) <= 15 ? $digits : null;
    }

    public function countryCode(mixed $countryId): ?string
    {
        if (! $countryId) {
            return null;
        }

        return DB::table('countries')->where('id', $countryId)->value('code');
    }

    private function repairEmail(string $email): string
    {
        $email = strtolower(trim($email));
        $at = strrpos($email, '@');
        if ($at === false) {
            return $email;
        }

        $local = substr($email, 0, $at);
        $domain = substr($email, $at + 1);
        if ($local === '' || $domain === '') {
            return $email;
        }

        if (preg_match('/^[a-f0-9]{32}(.+)$/i', $local, $matches)) {
            $candidate = $matches[1] . '@' . $domain;
            if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                $local = $matches[1];
                $email = $candidate;
            }
        }

        foreach (['com', 'org', 'net', 'edu', 'gov', 'mil', 'int', 'biz', 'info', 'jobs', 'io', 'ai', 'app', 'africa'] as $suffix) {
            if (preg_match('/^(.+\.' . preg_quote($suffix, '/') . ')\.([a-z]{2,})/i', $domain, $matches)) {
                $possibleCountrySuffix = $suffix . '.' . substr($matches[2], 0, 2);
                if ($suffix !== 'com' && isset($this->publicSuffixes()[$possibleCountrySuffix])) {
                    continue;
                }

                $candidate = $local . '@' . $matches[1];
                if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                    return $candidate;
                }
            }
        }

        // Prefer recognized country-sector endings even when scraped text after
        // them accidentally forms another syntactically valid public suffix.
        for ($length = 4; $length < strlen($domain); $length++) {
            $candidateDomain = rtrim(substr($domain, 0, $length), '.-_');
            if (! $this->hasCompoundCountrySuffix($candidateDomain)) {
                continue;
            }

            $candidate = $local . '@' . $candidateDomain;
            if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        if ($this->hasKnownPublicSuffix($domain)) {
            return $email;
        }

        $dotPositions = [];
        for ($position = 0; $position < strlen($domain); $position++) {
            if ($domain[$position] === '.') {
                $dotPositions[] = $position;
            }
        }

        foreach (array_reverse($dotPositions) as $position) {
            $candidateDomain = substr($domain, 0, $position);
            if (! $this->hasKnownPublicSuffix($candidateDomain)) {
                continue;
            }

            $candidate = $local . '@' . $candidateDomain;
            if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        $lastDot = strrpos($domain, '.');
        if ($lastDot !== false) {
            $lastLabel = substr($domain, $lastDot + 1);
            for ($length = strlen($lastLabel) - 1; $length >= 3; $length--) {
                $candidateDomain = substr($domain, 0, $lastDot + 1) . substr($lastLabel, 0, $length);
                if (! $this->hasKnownPublicSuffix($candidateDomain)) {
                    continue;
                }

                $candidate = $local . '@' . $candidateDomain;
                if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                    return $candidate;
                }
            }
        }

        return '';
    }

    private function hasCompoundCountrySuffix(string $domain): bool
    {
        if (! preg_match(
            '/\.((?:ac|co|com|edu|gov|mil|net|ngo|org|sch)\.[a-z]{2})$/i',
            $domain,
            $matches
        )) {
            return false;
        }

        return isset($this->publicSuffixes()[strtolower($matches[1])]);
    }

    private function hasKnownPublicSuffix(string $domain): bool
    {
        $labels = explode('.', strtolower($domain));
        if (count($labels) < 2) {
            return false;
        }

        $suffixes = $this->publicSuffixes();
        for ($index = 1; $index < count($labels); $index++) {
            $suffix = implode('.', array_slice($labels, $index));
            if (isset($suffixes[$suffix])) {
                return true;
            }

            $wildcard = '*.' . implode('.', array_slice($labels, $index + 1));
            if ($index + 1 < count($labels) && isset($suffixes[$wildcard])) {
                return true;
            }
        }

        return false;
    }

    private function publicSuffixes(): array
    {
        if ($this->publicSuffixes !== null) {
            return $this->publicSuffixes;
        }

        $suffixes = [];
        $path = '/usr/share/publicsuffix/public_suffix_list.dat';
        if (is_readable($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = strtolower(trim($line));
                if ($line === '' || str_starts_with($line, '//') || str_starts_with($line, '!')) {
                    continue;
                }

                $suffixes[$line] = true;
            }
        }

        foreach ([
            'com', 'org', 'net', 'edu', 'gov', 'mil', 'int', 'io', 'ai', 'app',
            'biz', 'info', 'jobs', 'me', 'africa', 'co.zm', 'org.zm', 'gov.zm',
            'ac.zm', 'edu.zm', 'net.zm', 'co.za', 'org.za', 'gov.za', 'ac.za',
        ] as $suffix) {
            $suffixes[$suffix] = true;
        }

        return $this->publicSuffixes = $suffixes;
    }
}
