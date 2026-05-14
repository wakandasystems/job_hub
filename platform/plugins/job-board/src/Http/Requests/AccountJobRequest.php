<?php

namespace Botble\JobBoard\Http\Requests;

use Botble\JobBoard\Models\Account;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class AccountJobRequest extends JobRequest
{
    public function rules(): array
    {
        /**
         * @var Account $account
         */
        $account = auth('account')->user();
        $companyIds = $account && $account->companies ? $account->companies->pluck('id')->all() : [];

        $rules = parent::rules();
        Arr::forget($rules, 'moderation_status');

        return array_merge($rules, [
            'company_id' => [
                'required',
                Rule::in(array_values($companyIds)),
            ],
        ]);
    }

    public function messages(): array
    {
        return [
            'company_id.required' => trans('plugins/job-board::messages.must_add_company_first'),
        ];
    }

    /**
     * Get the body parameters for API documentation.
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Job title',
                'example' => 'Senior Software Engineer',
            ],
            'description' => [
                'description' => 'Job description',
                'example' => 'We are looking for a senior software engineer...',
            ],
            'content' => [
                'description' => 'Detailed job content',
                'example' => 'Full job description with requirements...',
            ],
            'company_id' => [
                'description' => 'ID of the company posting the job',
                'example' => 1,
            ],
            'salary_from' => [
                'description' => 'Minimum salary',
                'example' => 50000,
            ],
            'salary_to' => [
                'description' => 'Maximum salary',
                'example' => 80000,
            ],
            'salary_type' => [
                'description' => 'Type of salary (fixed, negotiable, competitive, hidden)',
                'example' => 'fixed',
            ],
            'number_of_positions' => [
                'description' => 'Number of open positions',
                'example' => 2,
            ],
            'expire_date' => [
                'description' => 'Job expiration date',
                'example' => '2024-12-31',
            ],
        ];
    }
}
