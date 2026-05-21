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
use Botble\Location\Models\Country;
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

        $this->fillDefaultCountryFromSelectedCountry($account);

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
                    )
                    ->add(
                        'whatsapp_number',
                        TextField::class,
                        TextFieldOption::make()
                            ->label(__('WhatsApp Number (for job alerts)'))
                            ->placeholder('+260 97x xxx xxx')
                    )
                    ->add(
                        'telegram_chat_id',
                        TextField::class,
                        TextFieldOption::make()
                            ->label(__('Telegram Chat ID (for job alerts)'))
                            ->placeholder('e.g. 123456789')
                            ->helperText(__('Message @userinfobot on Telegram to get your chat ID.'))
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
                    ->when($account->resume && $account->cv_score, function ($form) use ($account) {
                        $score    = (int) $account->cv_score;
                        $feedback = (array) (($account->cv_score_data['feedback'] ?? null) ?: []);
                        $scoredAt = $account->cv_score_data['scored_at'] ?? null;

                        [$color, $label] = match (true) {
                            $score >= 88 => ['#22c55e', 'Excellent'],
                            $score >= 75 => ['#3b82f6', 'Good'],
                            $score >= 60 => ['#f59e0b', 'Fair'],
                            default      => ['#ef4444', 'Needs improvement'],
                        };

                        $dash      = round($score * 100 / 100);
                        $timeAgo   = $scoredAt ? \Carbon\Carbon::parse($scoredAt)->diffForHumans() : '';
                        $feedbackHtml = '';
                        foreach ($feedback as $item) {
                            $escaped = e($item);
                            $feedbackHtml .= "<div class=\"color-text-paragraph-2 font-xs mb-1\"><i class=\"fi-rr-angle-right me-1\"></i>{$escaped}</div>";
                        }

                        $upsell = $score < 75
                            ? '<div class="alert alert-warning d-flex align-items-center gap-3 py-2 px-3 mb-0 mt-3"><i class="fi-rr-star fs-5 text-warning flex-shrink-0"></i><div class="font-sm"><strong>Boost your chances</strong> — have a career coach professionally review and rewrite your CV. <a href="' . route('public.account.career-services') . '" class="fw-semibold ms-1">View Career Services →</a></div></div>'
                            : '';

                        $html = <<<HTML
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body p-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h5 class="fw-semibold mb-0">Your CV Score</h5>
      <span class="color-text-paragraph-2 font-xs">{$timeAgo}</span>
    </div>
    <div class="d-flex align-items-center gap-4 mb-0">
      <div style="position:relative;width:80px;height:80px;flex-shrink:0;">
        <svg viewBox="0 0 36 36" width="80" height="80">
          <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e5e7eb" stroke-width="3"></circle>
          <circle cx="18" cy="18" r="15.9" fill="none" stroke="{$color}" stroke-width="3"
            stroke-dasharray="{$dash}, 100" stroke-linecap="round" transform="rotate(-90 18 18)"></circle>
        </svg>
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;">
          <span class="fw-bold" style="font-size:16px;color:{$color}">{$score}</span>
          <span style="font-size:9px;color:#6b7280">/ 100</span>
        </div>
      </div>
      <div class="flex-grow-1">
        <div class="fw-semibold mb-1" style="color:{$color}">{$label}</div>
        {$feedbackHtml}
      </div>
    </div>
    {$upsell}
  </div>
</div>
HTML;

                        return $form->add('cv_score_display', HtmlField::class, HtmlFieldOption::make()->content($html));
                    })
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

    protected function fillDefaultCountryFromSelectedCountry(Account $account): void
    {
        if (old('country_id')) {
            return;
        }

        $selectedCountry = $this->resolveSelectedCountryForDefault();

        if (! $selectedCountry || ! $selectedCountry->id) {
            return;
        }

        $countryId = (int) $account->country_id;

        if (! $countryId) {
            $account->country_id = $selectedCountry->id;

            return;
        }

        if ((int) $selectedCountry->id === $countryId || $account->state_id || $account->city_id) {
            return;
        }

        $country = Country::query()
            ->whereKey($countryId)
            ->first(['id', 'name', 'is_default']);

        if ($country && ($country->is_default || $country->name === 'France')) {
            $account->country_id = $selectedCountry->id;
        }
    }

    protected function resolveSelectedCountryForDefault(): ?Country
    {
        $countries = Country::query()
            ->select(['id', 'name', 'code', 'is_default'])
            ->get();

        if ($countries->isEmpty()) {
            return null;
        }

        $countryId = function_exists('wakanda_decode_country_token') ? wakanda_decode_country_token(request()->query('c')) : null;
        $countryId = $countryId ?: (int) session('wakanda_country_id') ?: (int) request()->cookie('wakanda_country_id');

        if ($countryId && $country = $countries->firstWhere('id', $countryId)) {
            return $country;
        }

        if (function_exists('wakanda_country_from_host') && $country = wakanda_country_from_host()) {
            return $country;
        }

        if (function_exists('wakanda_detect_country_code') && $countryCode = wakanda_detect_country_code()) {
            if ($country = $countries->firstWhere('code', strtoupper($countryCode))) {
                return $country;
            }
        }

        return $countries->firstWhere('is_default', true) ?: $countries->first();
    }
}
