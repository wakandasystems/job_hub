<?php

namespace Botble\JobBoard\Http\Requests;

use Botble\Base\Rules\OnOffRule;
use Botble\Support\Http\Requests\Request;

class AccountEditRequest extends Request
{
    public function rules(): array
    {
        $rules = [
            'first_name' => 'required|max:120|min:2',
            'last_name' => 'required|max:120|min:2',
            'confirmed_at' => new OnOffRule(),
            'email' => 'required|max:60|min:6|email|unique:jb_accounts,email,' . $this->route('account.id'),
            'unique_id' => 'nullable|string|unique:jb_accounts,unique_id,' . $this->route('account.id'),
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

        if ($this->input('is_change_password') == 1) {
            $rules['password'] = 'required|min:6|confirmed';
        }

        return $rules;
    }
}
