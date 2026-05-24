<?php

namespace Botble\JobBoard\Http\Requests;

use Botble\Support\Http\Requests\Request;

class UploadResumeRequest extends Request
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'mimes:pdf,doc,docx,ppt,pptx'],
            'cv_upload_consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'cv_upload_consent.accepted' => __('Please accept the CV visibility terms before uploading your CV.'),
        ];
    }
}
