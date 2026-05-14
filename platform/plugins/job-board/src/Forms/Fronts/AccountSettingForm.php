<?php

namespace Botble\JobBoard\Forms\Fronts;

use Botble\Base\Facades\Html;
use Botble\Base\Forms\FieldOptions\EditorFieldOption;
use Botble\Base\Forms\FieldOptions\HtmlFieldOption;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TagFieldOption;
use Botble\Base\Forms\FieldOptions\TextareaFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\EditorField;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\OnOffField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TagField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;
use Botble\Base\Forms\FormFieldOptions;
use Botble\Base\Supports\Language;
use Botble\JobBoard\Enums\AccountGenderEnum;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Http\Requests\SettingRequest;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\JobSkill;
use Botble\JobBoard\Models\Tag;
use Botble\Location\Fields\Options\SelectLocationFieldOption;
use Botble\Location\Fields\SelectLocationField;
use Botble\Media\Facades\RvMedia;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;

class AccountSettingForm extends FormAbstract
{
    public function setup(): void
    {
        $jobSkills = [];
        $jobTags = [];
        $selectedJobSkills = [];
        $selectedJobTags = [];

        /**
         * @var Account $account
         */
        $account = $this->getModel();

        $isJobSeeker = $account->isJobSeeker();

        if ($isJobSeeker) {
            $selectedJobSkills = $account->favoriteSkills()->pluck('jb_job_skills.id')->all();

            $jobSkills = JobSkill::query()
                ->wherePublished()
                ->pluck('name', 'id')
                ->all();

            $selectedJobTags = $account->favoriteTags()->pluck('jb_tags.id')->all();

            $jobTags = Tag::query()
                ->wherePublished()
                ->pluck('name', 'id')
                ->all();
        }

        $this
            ->setupModel(new Account())
            ->setValidatorClass(SettingRequest::class)
            ->when($account->isEmployer(), function (AccountSettingForm $form): void {
                $form->template(JobBoardHelper::viewPath('dashboard.forms.base'));
            }, function (AccountSettingForm $form): void {
                $form->contentOnly();
            })
            ->add(
                'first_name',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/job-board::messages.first_name_label'))
                    ->placeholder(trans('plugins/job-board::messages.enter_first_name'))
                    ->required()
            )
            ->add(
                'last_name',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/job-board::messages.last_name'))
                    ->placeholder(trans('plugins/job-board::messages.enter_last_name'))
                    ->required()
            )
            ->add(
                'phone',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/job-board::forms.phone'))
                    ->placeholder(trans('plugins/job-board::messages.enter_phone_number'))
            )
            ->add(
                'dob',
                'date',
                TextFieldOption::make()
                    ->label(trans('plugins/job-board::messages.date_of_birth'))
                    ->value($account->dob ? $account->dob->toDateString() : null)
                    ->attributes([
                        'max' => Carbon::now()->toDateString(),
                    ])
            )
            ->add(
                'gender',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(trans('plugins/job-board::messages.gender'))
                    ->choices(AccountGenderEnum::labels())
            )
            ->when(is_plugin_active('location'), function (FormAbstract $form): void {
                $form->add(
                    'location_data',
                    SelectLocationField::class,
                    SelectLocationFieldOption::make()
                );
            })
            ->add(
                'address',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/job-board::forms.address'))
                    ->placeholder(trans('plugins/job-board::messages.enter_address'))
            )
            ->when(! $account->type->getKey() && setting('job_board_enabled_register_as_employer'), function (AccountSettingForm $form): void {
                $form
                    ->add(
                        'type',
                        SelectField::class,
                        SelectFieldOption::make()
                            ->label(trans('plugins/job-board::messages.type'))
                            ->choices(AccountTypeEnum::labels())
                    );
            })
            ->when($isJobSeeker, function (AccountSettingForm $form) use ($selectedJobTags, $jobTags, $selectedJobSkills, $jobSkills): void {
                $form->when(! empty($jobSkills) || ! empty($selectedJobSkills), function (AccountSettingForm $form) use ($selectedJobSkills, $jobSkills): void {
                    $form->add(
                        'favorite_skills',
                        TagField::class,
                        TagFieldOption::make()
                            ->label(trans('plugins/job-board::messages.favorite_job_skills'))
                            ->choices($jobSkills)
                            ->selected(implode(',', $selectedJobSkills))
                    );
                })
                    ->when(! empty($jobTags) || ! empty($selectedJobTags), function (AccountSettingForm $form) use ($selectedJobTags, $jobTags): void {
                        $form->add(
                            'favorite_tags',
                            TagField::class,
                            TagFieldOption::make()
                                ->label(trans('plugins/job-board::messages.favorite_job_tags'))
                                ->choices($jobTags)
                                ->selected(implode(',', $selectedJobTags))
                        );
                    })
                    ->add(
                        'title_profile',
                        HtmlField::class,
                        HtmlFieldOption::make()
                            ->content(sprintf('<h5 class="fs-17 fw-semibold mb-3">%s</h5>', trans('plugins/job-board::messages.profile')))
                    )
                    ->when(! JobBoardHelper::isDisabledPublicProfile(), function (FormAbstract $form): void {
                        $form
                            ->add(
                                'is_public_profile',
                                OnOffField::class,
                                OnOffFieldOption::make()
                                    ->label(trans('plugins/job-board::messages.public_profile'))
                            )
                            ->add(
                                'hide_cv',
                                OnOffField::class,
                                OnOffFieldOption::make()
                                    ->label(trans('plugins/job-board::messages.hide_cv'))
                            );
                    })
                    ->add(
                        'available_for_hiring',
                        OnOffField::class,
                        OnOffFieldOption::make()
                            ->label(trans('plugins/job-board::messages.available_for_hiring'))
                    );
            })
            ->when($isJobSeeker, function (AccountSettingForm $form) use ($account): void {
                $form
                    ->add(
                        'description',
                        TextareaField::class,
                        TextareaFieldOption::make()
                            ->label(trans('plugins/job-board::messages.introduce_yourself'))
                    )
                    ->add(
                        'bio',
                        EditorField::class,
                        EditorFieldOption::make()
                            ->addAttribute('without-buttons', true)
                            ->allowedShortcodes(false)
                            ->label(trans('plugins/job-board::messages.bio'))
                    )
                    ->add(
                        'resume',
                        'file',
                        FormFieldOptions::make()
                            ->label(trans('plugins/job-board::messages.attachments_cv'))
                            ->when($account->resume, function (FormFieldOptions $fieldOptions) use ($account) {
                                return $fieldOptions->helperText(
                                    trans('plugins/job-board::messages.current_resume_message', [
                                        'resume' => Html::link(RvMedia::url($account->resume), $account->resume, ['target' => '_blank'])->toHtml(),
                                    ])
                                );
                            })
                    )
                    ->add(
                        'cover_letter',
                        'file',
                        FormFieldOptions::make()
                            ->label(trans('plugins/job-board::messages.cover_letter'))
                            ->when($account->cover_letter, function (FormFieldOptions $fieldOptions) use ($account) {
                                return $fieldOptions->helperText(
                                    trans('plugins/job-board::messages.current_cover_letter_change', [
                                        'cover_letter' => Html::link(RvMedia::url($account->cover_letter), $account->cover_letter, ['target' => '_blank'])->toHtml(),
                                    ])
                                );
                            })
                    )
                    ->add(
                        'cover_image',
                        'file',
                        FormFieldOptions::make()
                            ->label(trans('plugins/job-board::messages.cover_image'))
                    );
            }, function (AccountSettingForm $form): void {
                $languages = Language::getAvailableLocales();

                $form
                    ->when(count($languages) > 1, function (FormAbstract $form) use ($languages): void {
                        $languages = collect($languages)
                            ->pluck('name', 'locale')
                            ->map(fn ($item, $key) => $item . ' - ' . $key)
                            ->all();

                        $form
                            ->add(
                                'locale',
                                SelectField::class,
                                SelectFieldOption::make()
                                    ->label(trans('plugins/job-board::messages.language'))
                                    ->choices($languages)
                                    ->selected($this->getModel()->getMetaData('locale', 'true') ?: App::getLocale())
                                    ->metadata()
                            );
                    });
            })
            ->when(! $isJobSeeker, fn (AccountSettingForm $form) => $form->disablePermalinkField());
    }
}
