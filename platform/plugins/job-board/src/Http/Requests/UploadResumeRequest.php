<?php

namespace Botble\JobBoard\Http\Requests;

use Botble\Support\Http\Requests\Request;

class UploadResumeRequest extends Request
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'mimes:pdf', 'max:10240'],
            'cv_upload_consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'cv_upload_consent.accepted' => __('Please accept the CV visibility terms before uploading your CV.'),
            'file.mimes'                 => __('Your CV must be a PDF file.'),
            'file.max'                   => __('Your CV must not exceed 10 MB.'),
        ];
    }
}
