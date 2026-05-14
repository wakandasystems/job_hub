<?php

namespace Botble\JobBoard\Forms\Settings;

use Botble\Base\Forms\FieldOptions\MediaImageFieldOption;
use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\FieldOptions\RadioFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\MediaImageField;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\RadioField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Http\Requests\Settings\GeneralSettingRequest;
use Botble\Setting\Forms\SettingForm;

class GeneralSettingForm extends SettingForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setSectionTitle(trans('plugins/job-board::settings.general.title'))
            ->setSectionDescription(trans('plugins/job-board::settings.general.description'))
            ->setValidatorClass(GeneralSettingRequest::class)
            ->add(
                'job_board_enable_guest_apply',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.enable_guest_apply'))
                    ->value(JobBoardHelper::isGuestApplyEnabled())
                    ->helperText(trans('plugins/job-board::settings.general.enable_guest_apply_helper'))
            )
            ->add(
                'job_board_enabled_register_account',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.enabled_register_account'))
                    ->value($enabledRegisterAccount = setting('job_board_enabled_register_account', true))
                    ->helperText(trans('plugins/job-board::settings.general.enabled_register_account_helper'))
            )
            ->addOpenCollapsible('job_board_enabled_register_account', '1', $enabledRegisterAccount)
            ->add(
                'job_board_enabled_register_as_employer',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.enabled_register_as_employer'))
                    ->value($enabledRegisterAsEmployer = setting('job_board_enabled_register_as_employer', true))
                    ->helperText(trans('plugins/job-board::settings.general.enabled_register_as_employer_helper'))
            )
            ->addOpenCollapsible('job_board_enabled_register_as_employer', '1', $enabledRegisterAsEmployer)
            ->add(
                'job_board_enable_credits_system',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.enable_credits_system'))
                    ->value(JobBoardHelper::isEnabledCreditsSystem())
                    ->helperText(trans('plugins/job-board::settings.general.enable_credits_system_helper'))
            )
            ->add(
                'job_board_enable_post_approval',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.enable_post_approval'))
                    ->value(setting('job_board_enable_post_approval', true))
                    ->helperText(trans('plugins/job-board::settings.general.enable_post_approval_helper'))
            )
            ->add(
                'verify_account_created_company',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.verify_account_created_company'))
                    ->value(setting('verify_account_created_company', true))
                    ->helperText(trans('plugins/job-board::settings.general.verify_account_created_company_helper'))
            )
            ->addCloseCollapsible('job_board_enabled_register_as_employer', '1')
            ->add(
                'verify_account_email',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.verify_account_email'))
                    ->value($verifyAccountEmail = setting('verify_account_email'))
                    ->helperText(trans('plugins/job-board::settings.general.verify_account_email_helper'))
            )
            ->addOpenCollapsible('verify_account_email', '1', $verifyAccountEmail)
            ->add(
                'job_board_email_verification_expire_minutes',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.verification_expire_minutes'))
                    ->value(setting('job_board_email_verification_expire_minutes', 60))
                    ->helperText(trans('plugins/job-board::settings.general.verification_expire_minutes_helper'))
                    ->min(1)
                    ->max(10080)
                    ->step(1)
            )
            ->addCloseCollapsible('verify_account_email', '1')
            ->addCloseCollapsible('job_board_enabled_register_account', '1')
            ->add(
                'job_board_enable_pin_featured_jobs_to_the_top',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.always_pin_featured_jobs_to_the_top'))
                    ->value(setting('job_board_enable_pin_featured_jobs_to_the_top', true))
                    ->helperText(trans('plugins/job-board::settings.general.always_pin_featured_jobs_to_the_top_helper'))
            )
            ->add(
                'job_board_enable_pin_featured_companies_to_the_top',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.always_pin_featured_companies_to_the_top'))
                    ->value(setting('job_board_enable_pin_featured_companies_to_the_top', true))
                    ->helperText(trans('plugins/job-board::settings.general.always_pin_featured_companies_to_the_top_helper'))
            )
            ->add(
                'job_expired_after_days',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.job_expired_after_days'))
                    ->value(JobBoardHelper::jobExpiredDays())
                    ->helperText(trans('plugins/job-board::settings.general.job_expired_after_days_helper'))
            )
            ->add(
                'job_board_job_expiration_warning_days',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.job_expiration_warning_days'))
                    ->value((int) setting('job_board_job_expiration_warning_days', 30))
                    ->helperText(trans('plugins/job-board::settings.general.job_expiration_warning_days_helper'))
            )
            ->add(
                'job_board_job_location_display',
                RadioField::class,
                RadioFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.job_location_display'))
                    ->selected(setting('job_board_job_location_display', 'state_and_country'))
                    ->helperText(trans('plugins/job-board::settings.general.job_location_display_helper'))
                    ->choices([
                        'state_and_country' => trans('plugins/job-board::settings.general.state_and_country'),
                        'city_and_state' => trans('plugins/job-board::settings.general.city_and_state'),
                        'city_state_and_country' => trans('plugins/job-board::settings.general.city_state_and_country'),
                    ])
            )
            ->add(
                'job_board_search_location_by',
                RadioField::class,
                RadioFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.search_location_by'))
                    ->helperText(trans('plugins/job-board::settings.general.search_location_by_helper'))
                    ->selected(setting('job_board_search_location_by', 'city_and_state'))
                    ->choices([
                        'city' => trans('plugins/job-board::settings.general.city'),
                        'city_and_state' => trans('plugins/job-board::settings.general.city_and_state'),
                        'state' => trans('plugins/job-board::settings.general.state'),
                    ])
            )
            ->add(
                'job_board_zip_code_enabled',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.enable_zip_code'))
                    ->value(JobBoardHelper::isZipCodeEnabled())
                    ->helperText(trans('plugins/job-board::settings.general.enable_zip_code_helper'))
            )
            ->when(is_plugin_active('captcha'), function (FormAbstract $form): void {
                $form
                    ->addHtml('<fieldset class="form-fieldset">')
                    ->addHtml(sprintf('<h4>%s</h4>', trans('plugins/captcha::captcha.settings.title')))
                    ->when(setting('enable_captcha'), function (FormAbstract $form): void {
                        $form
                            ->add(
                                'job_board_enable_recaptcha_in_apply_job',
                                OnOffCheckboxField::class,
                                OnOffFieldOption::make()
                                    ->label(trans('plugins/job-board::settings.general.enable_recaptcha_in_apply_job'))
                                    ->value(setting('job_board_enable_recaptcha_in_apply_job', false))
                            );
                    })
                    ->when(! setting('enable_captcha'), function (FormAbstract $form): void {
                        $form->addHtml(sprintf(
                            '<p class="mb-0 text-muted">%s</p>',
                            trans('plugins/job-board::settings.general.enable_recaptcha_in_pages_description')
                        ));
                    })
                    ->addHtml('</fieldset>');
            })
            ->addHtml('<fieldset class="form-fieldset">')
            ->addHtml(sprintf('<h4>%s</h4>', trans('plugins/job-board::settings.general.job_application_form_fields')))
            ->add(
                'job_board_require_message_in_apply_job',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.require_message_in_apply_job'))
                    ->value(setting('job_board_require_message_in_apply_job', false))
                    ->helperText(trans('plugins/job-board::settings.general.require_message_in_apply_job_helper'))
            )
            ->add(
                'job_board_require_resume_in_apply_job',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.require_resume_in_apply_job'))
                    ->value(setting('job_board_require_resume_in_apply_job', false))
                    ->helperText(trans('plugins/job-board::settings.general.require_resume_in_apply_job_helper'))
            )
            ->add(
                'job_board_require_cover_letter_in_apply_job',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.require_cover_letter_in_apply_job'))
                    ->value(setting('job_board_require_cover_letter_in_apply_job', false))
                    ->helperText(trans('plugins/job-board::settings.general.require_cover_letter_in_apply_job_helper'))
            )
            ->add(
                'job_board_external_apply_url_behavior',
                RadioField::class,
                RadioFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.external_apply_url_behavior'))
                    ->selected(setting('job_board_external_apply_url_behavior', 'disabled'))
                    ->helperText(trans('plugins/job-board::settings.general.external_apply_url_behavior_helper'))
                    ->choices([
                        'disabled' => trans('plugins/job-board::settings.general.external_apply_disabled'),
                        'new_tab' => trans('plugins/job-board::settings.general.external_apply_new_tab'),
                        'current_tab' => trans('plugins/job-board::settings.general.external_apply_current_tab'),
                    ])
            )
            ->addHtml('</fieldset>')
            ->add(
                'job_board_is_enabled_review_feature',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.enable_review_feature'))
                    ->value(JobBoardHelper::isEnabledReview())
                    ->helperText(trans('plugins/job-board::settings.general.enable_review_feature_helper'))
            )
            ->add(
                'job_board_disabled_public_profile',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.disabled_public_profile'))
                    ->value(JobBoardHelper::isDisabledPublicProfile())
                    ->helperText(trans('plugins/job-board::settings.general.disabled_public_profile_helper'))
            )
            ->add(
                'job_board_hide_company_email_enabled',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.hide_company_email'))
                    ->value(JobBoardHelper::hideCompanyEmailEnabled())
                    ->helperText(trans('plugins/job-board::settings.general.hide_company_email_helper'))
            )
            ->add(
                'job_board_enable_lat_long_fields',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.enable_lat_long_fields'))
                    ->value(JobBoardHelper::isEnabledLatLongFields())
                    ->helperText(trans('plugins/job-board::settings.general.enable_lat_long_fields_description'))
            )
            ->add(
                'job_board_default_account_avatar',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.default_account_avatar'))
                    ->value(setting('job_board_default_account_avatar'))
                    ->helperText(trans('plugins/job-board::settings.general.default_account_avatar_helper'))
            )
            ->add(
                'job_board_enabled_custom_fields_feature',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.enable_custom_fields'))
                    ->value(JobBoardHelper::isEnabledCustomFields())
                    ->helperText(trans('plugins/job-board::settings.general.enable_custom_fields_helper'))
            )
            ->add(
                'job_board_allow_employer_create_multiple_companies',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.allow_employer_multiple_companies'))
                    ->value(JobBoardHelper::employerCreateMultipleCompanies())
                    ->helperText(trans('plugins/job-board::settings.general.allow_employer_multiple_companies_helper'))
            )
            ->add(
                'job_board_allow_employer_manage_company_info',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.allow_employer_manage_company_info'))
                    ->value(JobBoardHelper::employerManageCompanyInfo())
                    ->helperText(trans('plugins/job-board::settings.general.allow_employer_manage_company_info_helper'))
            )
            ->add(
                'job_board_accessible_expired_job',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->value($canBeAccessExpiredJobs = JobBoardHelper::isExpiredJobAccessible())
                    ->label(trans('plugins/job-board::settings.general.accessible_expired_job'))
                    ->helperText(trans('plugins/job-board::settings.general.accessible_expired_job_helper'))
            )
            ->addOpenCollapsible('job_board_accessible_expired_job', '1', $canBeAccessExpiredJobs == '1')
            ->add(
                'job_board_listing_expired_job',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->value(JobBoardHelper::isExpiredJobListing())
                    ->collapsible('job_board_accessible_expired_job', 1, JobBoardHelper::isExpiredJobAccessible())
                    ->label(trans('plugins/job-board::settings.general.listing_expired_job'))
                    ->helperText(trans('plugins/job-board::settings.general.listing_expired_job_helper'))
            )
            ->addCloseCollapsible('job_board_accessible_expired_job', '1')
            ->add(
                'job_board_accessible_closed_job',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->value($canBeAccessClosedJobs = JobBoardHelper::isClosedJobAccessible())
                    ->label(trans('plugins/job-board::settings.general.accessible_closed_job'))
                    ->helperText(trans('plugins/job-board::settings.general.accessible_closed_job_helper'))
            )
            ->addOpenCollapsible('job_board_accessible_closed_job', '1', $canBeAccessClosedJobs == '1')
            ->add(
                'job_board_listing_closed_job',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->value(JobBoardHelper::isClosedJobListing())
                    ->collapsible('job_board_accessible_closed_job', 1, JobBoardHelper::isClosedJobAccessible())
                    ->label(trans('plugins/job-board::settings.general.listing_closed_job'))
                    ->helperText(trans('plugins/job-board::settings.general.listing_closed_job_helper'))
            )
            ->addCloseCollapsible('job_board_accessible_closed_job', '1')
            ->add(
                'job_board_noindex_inactive_jobs',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->value(JobBoardHelper::shouldNoIndexInactiveJobs())
                    ->label(trans('plugins/job-board::settings.general.noindex_expired_closed_jobs'))
                    ->helperText(trans('plugins/job-board::settings.general.noindex_expired_closed_jobs_helper'))
            )
            ->add(
                'job_board_hide_salary_for_guests',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->value(setting('job_board_hide_salary_for_guests', false))
                    ->label(trans('plugins/job-board::settings.general.hide_salary_for_guests'))
                    ->helperText(trans('plugins/job-board::settings.general.hide_salary_for_guests_helper'))
            )
            ->add(
                'job_board_hide_company_information_for_guests',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->value(setting('job_board_hide_company_information_for_guests', false))
                    ->label(trans('plugins/job-board::settings.general.hide_company_information_for_guests'))
                    ->helperText(trans('plugins/job-board::settings.general.hide_company_information_for_guests_helper'))
            )
            ->add(
                'job_board_hide_candidate_information_for_guests',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->value(
                        $hideCandidateInformationForGuest = setting(
                            'job_board_hide_candidate_information_for_guests',
                            false
                        )
                    )
                    ->label(trans('plugins/job-board::settings.general.hide_candidate_information_for_guests'))
                    ->helperText(trans('plugins/job-board::settings.general.hide_candidate_information_for_guests_helper'))
            )
            ->addOpenCollapsible(
                'job_board_hide_candidate_information_for_guests',
                '1',
                $hideCandidateInformationForGuest == '1'
            )
            ->add(
                'job_board_only_employer_can_view_candidate_information',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->value(setting('job_board_only_employer_can_view_candidate_information', true))
                    ->label(trans('plugins/job-board::settings.general.only_employer_can_view_candidate_information'))
                    ->helperText(trans('plugins/job-board::settings.general.only_employer_can_view_candidate_information_helper'))
            )
            ->addCloseCollapsible('job_board_hide_candidate_information_for_guests', '1')
            ->add(
                'job_board_auto_generate_unique_id',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.auto_generate_unique_id'))
                    ->value($targetValue = setting('job_board_auto_generate_unique_id', false))
                    ->helperText(trans('plugins/job-board::settings.general.auto_generate_unique_id_helper'))
            )
            ->addOpenCollapsible('job_board_auto_generate_unique_id', '1', $targetValue == '1')
            ->add(
                'job_board_unique_id_format',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.unique_id_format'))
                    ->value(setting('job_board_unique_id_format'))
                    ->helperText(trans('plugins/job-board::settings.general.unique_id_format_helper'))
            )
            ->add(
                'job_board_hide_unique_id_field_in_admin_form',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.hide_unique_id_field_in_admin_form'))
                    ->value(setting('job_board_hide_unique_id_field_in_admin_form', false))
                    ->helperText(trans('plugins/job-board::settings.general.hide_unique_id_field_in_admin_form_helper'))
            )
            ->add(
                'job_board_hide_unique_id_field_in_front_form',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/job-board::settings.general.hide_unique_id_field_in_front_form'))
                    ->value(setting('job_board_hide_unique_id_field_in_front_form', false))
                    ->helperText(trans('plugins/job-board::settings.general.hide_unique_id_field_in_front_form_helper'))
            )
            ->addCloseCollapsible('job_board_auto_generate_unique_id', '1');
        ;
    }
}
