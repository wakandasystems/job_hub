<?php

namespace Botble\JobBoard\Http\Requests;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Supports\Language;
use Botble\JobBoard\Enums\AccountGenderEnum;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class SettingRequest extends Request
{
    public function rules(): array
    {
        $rules = [
            'first_name' => 'required|max:120|min:2',
            'last_name' => 'required|max:120|min:2',
            'phone' => 'nullable|' . BaseHelper::getPhoneValidationRule(),
            'dob' => 'nullable|date',
            'address' => 'nullable|max:250',
            'gender' => 'nullable|' . Rule::in(AccountGenderEnum::values()),
            'description' => 'nullable|max:4000',
            'bio' => 'nullable',
            'country_id' => 'nullable|numeric',
            'state_id' => 'nullable|numeric',
            'city_id' => 'nullable|numeric',
            'locale' => ['sometimes', 'required', Rule::in(array_keys(Language::getAvailableLocales()))],
        ];

        $account = auth('account')->user();
        if ($account && ! $account->type->getKey()) {
            $rules['type'] = Rule::in(AccountTypeEnum::values());
        }

        if ($account && $account->isJobSeeker()) {
            $rules = array_merge($rules, [
                'is_public_profile' => Rule::in([0, 1]),
                'hide_cv' => Rule::in([0, 1]),
                'available_for_hiring' => Rule::in([0, 1]),
                'resume' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx',
                'cover_letter' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx',
            ]);
        }

        return $rules;
    }

    /**
     * Get the body parameters for API documentation.
     */
    public function bodyParameters(): array
    {
        return [
            'first_name' => [
                'description' => 'User\'s first name',
                'example' => 'John',
            ],
            'last_name' => [
                'description' => 'User\'s last name',
                'example' => 'Doe',
            ],
            'phone' => [
                'description' => 'User\'s phone number',
                'example' => '+1234567890',
            ],
            'dob' => [
                'description' => 'Date of birth',
                'example' => '1990-01-01',
            ],
            'address' => [
                'description' => 'User\'s address',
                'example' => '123 Main St, City, State',
            ],
            'gender' => [
                'description' => 'User\'s gender',
                'example' => 'male',
            ],
            'description' => [
                'description' => 'User\'s description',
                'example' => 'Experienced software developer...',
            ],
            'bio' => [
                'description' => 'User\'s bio',
                'example' => 'Passionate about technology...',
            ],
            'country_id' => [
                'description' => 'Country ID',
                'example' => 1,
            ],
            'state_id' => [
                'description' => 'State ID',
                'example' => 1,
            ],
            'city_id' => [
                'description' => 'City ID',
                'example' => 1,
            ],
            'avatar' => [
                'description' => 'Avatar image file',
                'example' => 'No-example',
            ],
            'resume' => [
                'description' => 'Resume file',
                'example' => 'No-example',
            ],
            'cover_letter' => [
                'description' => 'Cover letter file',
                'example' => 'No-example',
            ],
            'is_public_profile' => [
                'description' => 'Whether profile is public (job seekers only)',
                'example' => true,
            ],
            'hide_cv' => [
                'description' => 'Whether to hide CV (job seekers only)',
                'example' => false,
            ],
            'available_for_hiring' => [
                'description' => 'Whether available for hiring (job seekers only)',
                'example' => true,
            ],
        ];
    }
}
