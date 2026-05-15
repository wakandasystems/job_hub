<?php

namespace Botble\JobBoard\Http\Requests;

use Botble\JobBoard\Models\JobCrawler;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class JobCrawlerRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'source_url' => ['required', 'url', 'max:1000'],
            'parser_type' => ['required', Rule::in(['html', 'json', 'gozambiajobs'])],
            'schedule' => ['required', 'string', Rule::in(array_keys(JobCrawler::scheduleOptions()))],
            'is_active' => ['nullable', 'bool'],
            'default_company_id' => ['nullable', 'exists:jb_companies,id'],
            'item_selector' => ['nullable', 'string'],
            'title_selector' => ['nullable', 'string', 'max:255'],
            'company_selector' => ['nullable', 'string', 'max:255'],
            'location_selector' => ['nullable', 'string', 'max:255'],
            'description_selector' => ['nullable', 'string', 'max:255'],
            'content_selector' => ['nullable', 'string', 'max:255'],
            'apply_url_selector' => ['nullable', 'string', 'max:255'],
            'published_at_selector' => ['nullable', 'string', 'max:255'],
            'field_mappings' => ['nullable', 'string'],
        ];
    }
}
