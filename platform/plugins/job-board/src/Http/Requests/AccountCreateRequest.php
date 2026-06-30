<?php

namespace Botble\JobBoard\Http\Requests;

use Botble\Support\Http\Requests\Request;

class AccountCreateRequest extends Request
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'max:120', 'min:2'],
            'last_name' => ['required', 'max:120', 'min:2'],
            'email' => ['required', 'max:60', 'min:6', 'email', 'unique:jb_accounts,email'],
            'password' => ['required', 'min:6', 'confirmed'],
            'unique_id' => ['nullable', 'string', 'unique:jb_accounts,unique_id'],
            'call_numbers' => ['nullable', 'array'],
            'call_numbers.*' => ['nullable', 'string', 'max:30'],
            'whatsapp_numbers' => ['nullable', 'array'],
            'whatsapp_numbers.*' => ['nullable', 'string', 'max:30'],
            'linkedin' => ['nullable', 'string', 'max:250'],
            'telegram_chat_id' => ['nullable', 'string', 'max:100'],
            'experience_years' => ['nullable', 'in:0,1,2,3,5,10'],
            'education_level' => ['nullable', 'in:high_school,diploma,bachelor,masters,phd'],
            'availability' => ['nullable', 'in:immediate,one_week,two_weeks,one_month,not_looking'],
            'desired_salary_from' => ['nullable', 'integer', 'min:0'],
            'desired_salary_to' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
