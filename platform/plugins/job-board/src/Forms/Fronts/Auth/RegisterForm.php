<?php

namespace Botble\JobBoard\Forms\Fronts\Auth;

use Botble\Base\Facades\Html;
use Botble\Base\Forms\FieldOptions\CheckboxFieldOption;
use Botble\Base\Forms\FieldOptions\PhoneNumberFieldOption;
use Botble\Base\Forms\Fields\EmailField;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\PasswordField;
use Botble\Base\Forms\Fields\PhoneNumberField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;
use Botble\JobBoard\Forms\Fronts\Auth\FieldOptions\EmailFieldOption;
use Botble\JobBoard\Forms\Fronts\Auth\FieldOptions\TextFieldOption;
use Botble\JobBoard\Http\Requests\Fronts\Auth\RegisterRequest;
use Botble\JobBoard\Models\Account;

class RegisterForm extends AuthForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setUrl(route('public.account.register.post'))
            ->setValidatorClass(RegisterRequest::class)
            ->add(
                'first_name',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/job-board::messages.first_name'))
                    ->placeholder(trans('plugins/job-board::messages.first_name'))
                    ->icon('ti ti-user')
                    ->required()
            )
            ->add(
                'last_name',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/job-board::messages.last_name'))
                    ->placeholder(trans('plugins/job-board::messages.last_name'))
                    ->icon('ti ti-user')
                    ->required()
            )
            ->add(
                'email',
                EmailField::class,
                EmailFieldOption::make()
                    ->label(trans('plugins/job-board::messages.email_label'))
                    ->placeholder(trans('plugins/job-board::messages.email_address_placeholder'))
                    ->icon('ti ti-mail')
                    ->required()
            )
            ->add(
                'phone',
                PhoneNumberField::class,
                PhoneNumberFieldOption::make()
                    ->label(trans('plugins/job-board::messages.phone_optional'))
                    ->placeholder(trans('plugins/job-board::messages.phone_number_placeholder'))
                    ->withCountryCodeSelection()
            )
            ->add(
                'password',
                PasswordField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/job-board::messages.password'))
                    ->placeholder(trans('plugins/job-board::messages.password'))
                    ->icon('ti ti-lock')
                    ->required()
            )
            ->add(
                'password_confirmation',
                PasswordField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/job-board::messages.password_confirmation'))
                    ->placeholder(trans('plugins/job-board::messages.password_confirmation'))
                    ->icon('ti ti-lock')
                    ->required()
            )
            ->when(setting('job_board_enabled_register_as_employer', 1), function (FormAbstract $form): void {
                $form->add(
                    'account_type_selection',
                    HtmlField::class,
                    [
                        'html' => view('plugins/job-board::auth.partials.account-type-selection')->render(),
                    ]
                );
            })
            ->add(
                'agree_terms_and_policy',
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->when(
                        $privacyPolicyUrl = theme_option('term_and_privacy_policy_url'),
                        function (CheckboxFieldOption $fieldOption, string $url): void {
                            $fieldOption->label(trans('plugins/job-board::messages.terms_agreement', ['link' => Html::link($url, trans('plugins/job-board::messages.terms_privacy_policy'), attributes: ['class' => 'text-decoration-underline', 'target' => '_blank'])]));
                        }
                    )
                    ->when(! $privacyPolicyUrl, function (CheckboxFieldOption $fieldOption): void {
                        $fieldOption->label(trans('plugins/job-board::messages.terms_agreement_simple'));
                    })
            )
            ->submitButton(trans('plugins/job-board::messages.register'), 'ti ti-arrow-narrow-right')
            ->add('login', HtmlField::class, [
                'html' => sprintf(
                    '<div class="mt-3 text-center">%s <a href="%s" class="text-decoration-underline">%s</a></div>',
                    trans('plugins/job-board::messages.already_have_account'),
                    route('public.account.login'),
                    trans('plugins/job-board::messages.sign_in')
                ),
            ])
            ->add('filters', HtmlField::class, [
                'html' => apply_filters(BASE_FILTER_AFTER_LOGIN_OR_REGISTER_FORM, null, Account::class),
            ])
            ->when(setting('job_board_enabled_register_as_employer', 1), function (FormAbstract $form): void {
                $form->add('account_type_modal', HtmlField::class, [
                    'html' => view('plugins/job-board::auth.partials.account-type-modal')->render(),
                ]);
            });
    }
}
