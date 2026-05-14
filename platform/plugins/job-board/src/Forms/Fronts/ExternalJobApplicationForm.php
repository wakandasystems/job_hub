<?php

namespace Botble\JobBoard\Forms\Fronts;

use Botble\Base\Forms\FieldOptions\ButtonFieldOption;
use Botble\Base\Forms\FieldOptions\EmailFieldOption;
use Botble\Base\Forms\FieldOptions\HiddenFieldOption;
use Botble\Base\Forms\FieldOptions\HtmlFieldOption;
use Botble\Base\Forms\Fields\EmailField;
use Botble\Base\Forms\Fields\HiddenField;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Captcha\Facades\Captcha;
use Botble\JobBoard\Http\Requests\ApplyJobRequest;
use Botble\JobBoard\Models\JobApplication;
use Botble\Theme\FormFront;

class ExternalJobApplicationForm extends FormFront
{
    protected string $errorBag = 'job_application';

    public static function formTitle(): string
    {
        return trans('plugins/job-board::messages.apply_for_this_job');
    }

    public function setup(): void
    {
        $account = auth('account')->user();

        $this
            ->contentOnly()
            ->setUrl(route('public.job.apply'))
            ->setMethod('POST')
            ->setFormOption('class', 'job-apply-form')
            ->setFormOption('files', true)
            ->setValidatorClass(ApplyJobRequest::class)
            ->model(JobApplication::class)

            // Modal header
            ->add('modal_header', HtmlField::class, HtmlFieldOption::make()->content('
                <div class="text-center mb-4">
                    <h5 class="modal-title">' . trans('plugins/job-board::messages.apply_for_this_job') . '</h5>
                </div>
                <div class="position-absolute end-0 top-0 p-3">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            '))

            // Job info section
            ->add('job_info', HtmlField::class, HtmlFieldOption::make()->content('
                <div class="text-center mb-4">
                    <h5 class="modal-job-name"></h5>
                </div>
            '))

            // Hidden fields
            ->add(
                'job_id',
                HiddenField::class,
                HiddenFieldOption::make()
                ->addAttribute('class', 'modal-job-id')
                ->required()
            )
            ->add(
                'job_type',
                HiddenField::class,
                HiddenFieldOption::make()
                ->value('external')
            )

            // Email field
            ->add(
                'email',
                EmailField::class,
                EmailFieldOption::make()
                ->label(trans('plugins/job-board::messages.email_address'))
                ->value(old('email', $account->email ?? ''))
                ->placeholder(trans('plugins/job-board::messages.enter_email'))
                ->required()
                ->addAttribute('id', 'email_apply_external')
            );

        // Captcha if enabled
        if (is_plugin_active('captcha') && setting('enable_captcha') && setting('job_board_enable_recaptcha_in_apply_job', 0)) {
            $this->add('captcha', HtmlField::class, HtmlFieldOption::make()->content('
                <div class="mb-4">
                    ' . Captcha::display() . '
                </div>
            '));
        }

        $this
            // Submit button
            ->add(
                'submit',
                'submit',
                ButtonFieldOption::make()
                ->label(trans('plugins/job-board::messages.go_to_job_site') . ' <i class="mdi mdi-arrow-right"></i>')
                ->cssClass('btn btn-primary w-100')
            );
    }
}
