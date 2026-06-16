<?php

namespace Botble\JobBoard\Http\Requests;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class CompanyRequest extends Request
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'contact_emails' => $this->tagValues('contact_emails'),
            'contact_numbers' => $this->tagValues('contact_numbers'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'contact_emails' => ['nullable', 'array'],
            'contact_emails.*' => ['email:rfc', 'max:255'],
            'description' => ['nullable', 'max:400'],
            'content' => ['nullable', 'string', 'max:100000'],
            'website' => ['nullable', 'max:120'],
            'address' => ['nullable', 'max:250'],
            'postal_code' => ['nullable', 'max:20'],
            'phone' => ['nullable', 'max:25'],
            'contact_numbers' => ['nullable', 'array'],
            'contact_numbers.*' => ['string', 'max:30'],
            'year_founded' => ['nullable', 'max:4'],
            'ceo' => ['nullable', 'max:120'],
            'number_of_offices' => ['nullable', 'numeric'],
            'number_of_employees' => ['nullable', 'numeric'],
            'annual_revenue' => ['nullable', 'string', 'max:60'],
            'facebook' => ['nullable', 'max:200'],
            'twitter' => ['nullable', 'max:200'],
            'linkedin' => ['nullable', 'max:200'],
            'instagram' => ['nullable', 'max:200'],
            'latitude' => ['max:20', 'nullable', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
            'longitude' => [
                'max:20',
                'nullable',
                'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/',
            ],
            'status' => Rule::in(BaseStatusEnum::values()),
            'tax_id' => ['nullable', 'string', 'max:60'],
            'unique_id' => 'nullable|string|unique:jb_companies,unique_id,' . $this->route('company.id'),
        ];
    }

    private function tagValues(string $key): array
    {
        $value = $this->input($key);
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value)));
        }

        $decoded = json_decode((string) $value, true);
        if (! is_array($decoded)) {
            return collect(preg_split('/[\r\n,;]+/', (string) $value) ?: [])
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->values()
                ->all();
        }

        return collect($decoded)
            ->pluck('value')
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }
}
