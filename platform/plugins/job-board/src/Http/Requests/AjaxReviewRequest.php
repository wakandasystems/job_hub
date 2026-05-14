<?php

namespace Botble\JobBoard\Http\Requests;

use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\Company;
use Botble\Support\Http\Requests\Request;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class AjaxReviewRequest extends Request
{
    public function rules(): array
    {
        return [
            'reviewable_type' => ['required', Rule::in([Company::class, Account::class])],
            'reviewable_id' => [
                'required',
                Rule::exists($this->input('reviewable_type'), 'id')->where(function (Builder $query): void {
                    if ($this->input('reviewable_type') === Account::class) {
                        $query->where('type', AccountTypeEnum::JOB_SEEKER);
                    }
                }),
            ],
        ];
    }

    /**
     * Get the body parameters for API documentation.
     */
    public function bodyParameters(): array
    {
        return [
            'reviewable_type' => [
                'description' => 'Type of entity being reviewed (Company or Account)',
                'example' => 'Botble\\JobBoard\\Models\\Company',
            ],
            'reviewable_id' => [
                'description' => 'ID of the entity being reviewed',
                'example' => 1,
            ],
            'rating' => [
                'description' => 'Rating from 1 to 5 stars',
                'example' => 5,
            ],
            'comment' => [
                'description' => 'Review comment',
                'example' => 'Great company to work with!',
            ],
        ];
    }
}
