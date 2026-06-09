<?php

namespace Botble\JobBoard\Supports;

use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Services\CompanyContactService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmployerContactAudience
{
    public function __construct(private readonly CompanyContactService $contactService)
    {
    }

    public function emails(): Collection
    {
        $contacts = collect();
        $accountOrigins = $this->latestJobOrigins('author_id', 'author_type', Account::class);
        $companyOrigins = $this->latestJobOrigins('company_id');

        DB::table('jb_accounts')
            ->leftJoin('countries', 'countries.id', '=', 'jb_accounts.country_id')
            ->where('jb_accounts.type', AccountTypeEnum::EMPLOYER)
            ->whereNotNull('jb_accounts.email')
            ->selectRaw("jb_accounts.id, jb_accounts.email, TRIM(CONCAT(jb_accounts.first_name, ' ', jb_accounts.last_name)) as name, countries.code as country_code, countries.name as country_name")
            ->orderBy('jb_accounts.id')
            ->get()
            ->each(function ($row) use ($contacts, $accountOrigins): void {
                $origin = $row->country_code ? $row : $accountOrigins->get($row->id);
                $this->addEmail(
                    $contacts,
                    $row->email,
                    $row->name,
                    $origin,
                    route('accounts.edit', $row->id)
                );
            });

        DB::table('jb_companies')
            ->leftJoin('countries', 'countries.id', '=', 'jb_companies.country_id')
            ->select('jb_companies.id', 'jb_companies.email', 'jb_companies.contact_emails', 'jb_companies.name', 'countries.code as country_code', 'countries.name as country_name')
            ->orderBy('jb_companies.id')
            ->get()
            ->each(function ($row) use ($contacts, $companyOrigins): void {
                $origin = $row->country_code ? $row : $companyOrigins->get($row->id);
                foreach (array_merge([$row->email], $this->decodeList($row->contact_emails)) as $email) {
                    $this->addEmail(
                        $contacts,
                        $email,
                        $row->name,
                        $origin,
                        route('companies.edit', $row->id)
                    );
                }
            });

        return $contacts->values();
    }

    public function phones(): Collection
    {
        $contacts = collect();
        $accountOrigins = $this->latestJobOrigins('author_id', 'author_type', Account::class);
        $companyOrigins = $this->latestJobOrigins('company_id');

        DB::table('jb_accounts')
            ->leftJoin('countries', 'countries.id', '=', 'jb_accounts.country_id')
            ->where('jb_accounts.type', AccountTypeEnum::EMPLOYER)
            ->selectRaw("jb_accounts.id, jb_accounts.phone, jb_accounts.whatsapp_number, TRIM(CONCAT(jb_accounts.first_name, ' ', jb_accounts.last_name)) as name, countries.code as country_code, countries.name as country_name")
            ->orderBy('jb_accounts.id')
            ->get()
            ->each(function ($row) use ($contacts, $accountOrigins): void {
                $origin = $row->country_code ? $row : $accountOrigins->get($row->id);
                $editUrl = route('accounts.edit', $row->id);
                $this->addPhone($contacts, $row->phone, $row->name, $origin, $editUrl);
                $this->addPhone($contacts, $row->whatsapp_number, $row->name, $origin, $editUrl);
            });

        DB::table('jb_companies')
            ->leftJoin('countries', 'countries.id', '=', 'jb_companies.country_id')
            ->select('jb_companies.id', 'jb_companies.phone', 'jb_companies.contact_numbers', 'jb_companies.name', 'countries.code as country_code', 'countries.name as country_name')
            ->orderBy('jb_companies.id')
            ->get()
            ->each(function ($row) use ($contacts, $companyOrigins): void {
                $origin = $row->country_code ? $row : $companyOrigins->get($row->id);
                foreach (array_merge([$row->phone], $this->decodeList($row->contact_numbers)) as $phone) {
                    $this->addPhone(
                        $contacts,
                        $phone,
                        $row->name,
                        $origin,
                        route('companies.edit', $row->id)
                    );
                }
            });

        return $contacts->values();
    }

    private function addEmail(
        Collection $contacts,
        mixed $email,
        mixed $name,
        mixed $origin = null,
        ?string $editUrl = null
    ): void
    {
        $email = $this->contactService->normalizeEmails([$email])[0] ?? null;

        if (! $email || $contacts->has($email)) {
            return;
        }

        $contacts->put($email, (object) [
            'id' => 0,
            'email' => $email,
            'name' => trim((string) $name) ?: 'Employer',
            'country_code' => $this->flagCountryCode($origin->country_code ?? null),
            'country_name' => trim((string) ($origin->country_name ?? '')),
            'edit_url' => $editUrl,
        ]);
    }

    private function addPhone(
        Collection $contacts,
        mixed $phone,
        mixed $name,
        mixed $origin = null,
        ?string $editUrl = null
    ): void
    {
        $digits = $this->contactService->normalizePhones([$phone], (string) ($origin->country_code ?? ''))[0] ?? null;

        if (! $digits || $contacts->has($digits)) {
            return;
        }

        $contacts->put($digits, (object) [
            'phone' => $digits,
            'name' => trim((string) $name) ?: 'Employer',
            'country_code' => $this->flagCountryCode($origin->country_code ?? null),
            'country_name' => trim((string) ($origin->country_name ?? '')),
            'edit_url' => $editUrl,
        ]);
    }

    private function decodeList(mixed $value): array
    {
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function latestJobOrigins(string $foreignKey, ?string $typeKey = null, ?string $type = null): Collection
    {
        $latestJobs = DB::table('jb_jobs')
            ->selectRaw("MAX(id) as id, {$foreignKey}")
            ->whereNotNull($foreignKey)
            ->when($typeKey && $type, fn ($query) => $query->where($typeKey, $type))
            ->groupBy($foreignKey);

        return DB::table('jb_jobs')
            ->joinSub($latestJobs, 'latest_jobs', fn ($join) => $join->on('latest_jobs.id', '=', 'jb_jobs.id'))
            ->join('countries', 'countries.id', '=', 'jb_jobs.country_id')
            ->selectRaw("latest_jobs.{$foreignKey} as origin_id, countries.code as country_code, countries.name as country_name")
            ->get()
            ->keyBy('origin_id');
    }

    private function flagCountryCode(mixed $code): string
    {
        $code = strtoupper(trim((string) $code));

        return match ($code) {
            'FRA' => 'FR',
            default => strlen($code) === 2 ? $code : '',
        };
    }
}
