<?php

namespace Botble\JobBoard\Http\Requests;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Supports\Language;
use Botble\JobBoard\Enums\AccountGenderEnum;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Supports\ProfileContactGuard;
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
                'is_public_profile'    => Rule::in([0, 1]),
                'hide_cv'              => Rule::in([0, 1]),
                'available_for_hiring' => Rule::in([0, 1]),
                'talent_hub_consent'   => Rule::in([0, 1]),
                'resume'               => 'nullable|file|mimes:pdf|max:10240',
                'cover_letter'         => 'nullable|file|mimes:pdf|max:10240',
                'cover_image'          => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
                'whatsapp_number'      => 'nullable|string|max:30',
                'telegram_chat_id'     => 'nullable|string|max:100',
                'experience_years'     => 'nullable|integer|in:0,1,2,3,5,10',
                'education_level'      => ['nullable', Rule::in(['high_school','diploma','bachelor','masters','phd'])],
                'availability'         => ['nullable', Rule::in(['immediate','one_week','two_weeks','one_month','not_looking'])],
                'desired_salary_from'  => 'nullable|integer|min:0',
                'desired_salary_to'    => 'nullable|integer|min:0',
            ]);

            if ($this->hasFile('resume')) {
                $rules['cv_upload_consent'] = 'required|accepted';
            }
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $account = auth('account')->user();

            if (! $account || ! $account->isJobSeeker()) {
                return;
            }

            $fields = [
                'first_name' => __('First name'),
                'last_name' => __('Last name'),
                'description' => __('Profile introduction'),
                'bio' => __('Bio'),
            ];

            foreach ($fields as $field => $label) {
                if (ProfileContactGuard::containsContactInfo($this->input($field))) {
                    $validator->errors()->add($field, ProfileContactGuard::violationMessage($label));
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'cv_upload_consent.accepted'       => __('Please accept the CV visibility terms before uploading your CV.'),
            'cv_upload_consent.required_with'  => __('Please accept the CV visibility terms before uploading your CV.'),
            'resume.mimes'                     => __('Your CV must be a PDF file.'),
            'resume.max'                       => __('Your CV must not exceed 10 MB.'),
            'cover_letter.mimes'               => __('Your cover letter must be a PDF file.'),
            'cover_letter.max'                 => __('Your cover letter must not exceed 10 MB.'),
            'cover_image.mimes'                => __('Your cover image must be a JPG or PNG file.'),
            'cover_image.max'                  => __('Your cover image must not exceed 5 MB.'),
        ];
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
