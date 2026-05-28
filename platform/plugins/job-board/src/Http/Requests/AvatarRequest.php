<?php

namespace Botble\JobBoard\Http\Requests;

use Botble\Support\Http\Requests\Request;

class AvatarRequest extends Request
{
    public function rules(): array
    {
        return [
            'avatar_file' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'avatar_data' => ['required', 'string'],
        ];
    }
}
