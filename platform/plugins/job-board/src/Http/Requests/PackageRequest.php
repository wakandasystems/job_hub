<?php

namespace Botble\JobBoard\Http\Requests;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class PackageRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:400'],
            'price' => ['numeric', 'required', 'min:0'],
            'percent_save' => ['numeric', 'required', 'min:0'],
            'currency_id' => ['required', 'numeric', 'min:1'],
            'number_of_listings' => ['numeric', 'required', 'min:1'],
            'order' => ['required', 'integer', 'min:0', 'max:127'],
            'features' => ['nullable', 'array'],
            'features.*.*.value' => ['required', 'string'],
            'status' => Rule::in(BaseStatusEnum::values()),
        ];
    }

    protected function prepareForValidation(): void
    {
        $features = $this->input('features');

        if (in_array($features, ['', '[]', 'null', null], true) || empty($features)) {
            $this->merge(['features' => null]);
        }
    }

    public function attributes(): array
    {
        return [
            'features.*.*.value' => trans('plugins/job-board::package.feature_title'),
        ];
    }
}
