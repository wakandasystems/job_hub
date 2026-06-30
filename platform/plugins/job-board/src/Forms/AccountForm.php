<?php

namespace Botble\JobBoard\Forms;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Forms\FieldOptions\DescriptionFieldOption;
use Botble\Base\Forms\FieldOptions\IsFeaturedFieldOption;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\OnOffField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;
use Botble\JobBoard\Enums\AccountGenderEnum;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Http\Requests\AccountCreateRequest;
use Botble\JobBoard\Models\Account;
use Botble\Location\Fields\Options\SelectLocationFieldOption;
use Botble\Location\Fields\SelectLocationField;
use Botble\Slug\Facades\SlugHelper;

class AccountForm extends FormAbstract
{
    public function setup(): void
    {
        Assets::addScriptsDirectly('vendor/core/plugins/job-board/js/account-admin.js');

        $this
            ->setupModel(new Account())
            ->setValidatorClass(AccountCreateRequest::class)
            ->template('plugins/job-board::accounts.form')
            ->add('first_name', 'text', [
                'label' => trans('plugins/job-board::account.form.first_name'),
                'required' => true,
                'attr' => [
                    'placeholder' => trans('core/base::forms.name_placeholder'),
                    'data-counter' => 120,
                ],
            ])
            ->add('last_name', 'text', [
                'label' => trans('plugins/job-board::account.form.last_name'),
                'required' => true,
                'attr' => [
                    'placeholder' => trans('core/base::forms.name_placeholder'),
                    'data-counter' => 120,
                ],
            ])
            ->add('email', 'text', [
                'label' => trans('plugins/job-board::account.form.email'),
                'required' => true,
                'attr' => [
                    'placeholder' => trans('plugins/job-board::forms.email_account_placeholder'),
                    'data-counter' => 60,
                ],
            ])
            ->when($this->getModel()->getKey(), function (FormAbstract $form): void {
                $form->add(
                    'confirmed_at',
                    OnOffField::class,
                    OnOffFieldOption::make()
                        ->label(trans('plugins/job-board::account.form.verified_email'))
                );
            })
            ->add('description', TextareaField::class, DescriptionFieldOption::make())
            ->add('linkedin', 'text', [
                'label' => 'LinkedIn URL',
                'attr' => [
                    'placeholder' => 'https://linkedin.com/in/username',
                    'data-counter' => 250,
                ],
            ])
            ->add('bio', 'editor', [
                'label' => trans('plugins/job-board::forms.bio'),
            ])
            ->when(is_plugin_active('location'), function (FormAbstract $form): void {
                $form->add(
                    'location_data',
                    SelectLocationField::class,
                    SelectLocationFieldOption::make()
                );
            })
            ->add('address', 'text', [
                'label' => trans('plugins/job-board::account.form.address'),
                'attr' => [
                    'data-counter' => 120,
                ],
            ])
            ->add('call_numbers', 'html', [
                'html' => $this->repeatableContactField(
                    'call_numbers',
                    'Call Numbers',
                    $this->getModel()->call_numbers ?? [],
                    'text',
                    '+260 97 000 0000',
                    'Add number',
                    'Add one call number per row. The first number becomes the primary call number used by older integrations.'
                ),
            ])
            ->add('whatsapp_numbers', 'html', [
                'html' => $this->repeatableContactField(
                    'whatsapp_numbers',
                    'WhatsApp Numbers',
                    $this->getModel()->whatsapp_numbers ?? [],
                    'text',
                    '+260 97 000 0000',
                    'Add number',
                    'Add one WhatsApp number per row. The first number becomes the primary WhatsApp number used by existing automations.'
                ),
            ])
            ->add('dob', 'datePicker', [
                'label' => trans('plugins/job-board::account.form.date_of_birth'),
                'value' => $this->getModel()->id ? BaseHelper::formatDate($this->getModel()->dob) : '',
            ])
            ->add('is_change_password', 'onOff', [
                'label' => trans('plugins/job-board::account.form.change_password'),
                'value' => false,
                'attr' => [
                    'data-bb-toggle' => 'collapse',
                    'data-bb-target' => '#change-password',
                ],
            ])
            ->add('openRow', 'html', [
                'html' => '<div id="change-password" class="row"' . ($this->getModel(
                )->id ? ' style="display: none"' : null) . '>',
            ])
            ->add('password', 'password', [
                'label' => trans('plugins/job-board::account.form.password'),
                'required' => true,
                'attr' => [
                    'data-counter' => 60,
                ],
                'wrapper' => [
                    'class' => $this->formHelper->getConfig('defaults.wrapper_class') . ' col-md-6',
                ],
            ])
            ->add('password_confirmation', 'password', [
                'label' => trans('plugins/job-board::account.form.password_confirmation'),
                'required' => true,
                'attr' => [
                    'data-counter' => 60,
                ],
                'wrapper' => [
                    'class' => $this->formHelper->getConfig('defaults.wrapper_class') . ' col-md-6',
                ],
            ])
            ->add('closeRow', 'html', [
                'html' => '</div>',
            ])
            ->add('type', 'customSelect', [
                'label' => trans('plugins/job-board::account.type'),
                'required' => true,
                'choices' => AccountTypeEnum::labels(),
            ])
            ->add(
                'unique_id',
                TextField::class,
                TextFieldOption::make()
                    ->value($this->getModel()->getKey() ? $this->getModel()->unique_id : $this->getModel()->generateUniqueId())
                    ->label(trans('plugins/job-board::job-board.form.unique_id'))
            )
            ->add('available_for_hiring', 'onOff', [
                'label' => trans('plugins/job-board::account.form.available_for_hiring'),
            ])
            ->when(! JobBoardHelper::isDisabledPublicProfile(), function (FormAbstract $form): void {
                $form
                    ->add(
                        'is_public_profile',
                        OnOffField::class,
                        OnOffFieldOption::make()
                            ->label(trans('plugins/job-board::account.form.is_public_profile'))
                            ->defaultValue(true)
                    )
                    ->add('hide_cv', 'onOff', [
                        'label' => trans('plugins/job-board::account.form.hide_cv'),
                    ]);
            })
            ->setBreakFieldPoint('type')
            ->add(
                'is_featured',
                OnOffField::class,
                IsFeaturedFieldOption::make()
            )
            ->add('gender', 'customSelect', [
                'label' => trans('plugins/job-board::account.form.gender'),
                'choices' => AccountGenderEnum::labels(),
                'empty_value' => trans('plugins/job-board::forms.select_placeholder'),
            ])
            ->add('avatar_image', 'mediaImage', [
                'label' => trans('plugins/job-board::account.form.avatar_image'),
                'value' => $this->getModel()->avatar->url,
            ])
            ->add('resume', 'mediaFile', [
                'label' => trans('plugins/job-board::account.form.resume'),
                'value' => $this->getModel()->resume,
            ])
            ->add('resume_actions', 'html', [
                'html' => view('plugins/job-board::accounts.resume-manager', [
                    'account' => $this->getModel(),
                ])->render(),
            ]);

        /**
         * @var Account $account
         */
        $account = $this->getModel();

        if ($account->getKey() && $account->isJobSeeker()) {
            $this
                ->add('career_preferences_heading', 'html', [
                    'html' => '<h5 class="mb-3">Career Preferences</h5>',
                ])
                ->add('career_preferences_row_open', 'html', [
                    'html' => '<div class="row">',
                ])
                ->add('experience_years', 'customSelect', [
                    'label' => 'Years of Experience',
                    'choices' => Account::experienceYearsOptions(),
                    'wrapper' => [
                        'class' => $this->formHelper->getConfig('defaults.wrapper_class') . ' col-md-4',
                    ],
                ])
                ->add('education_level', 'customSelect', [
                    'label' => 'Highest Education Level',
                    'choices' => Account::educationLevelOptions(),
                    'wrapper' => [
                        'class' => $this->formHelper->getConfig('defaults.wrapper_class') . ' col-md-4',
                    ],
                ])
                ->add('availability', 'customSelect', [
                    'label' => 'Availability',
                    'choices' => Account::availabilityOptions(),
                    'wrapper' => [
                        'class' => $this->formHelper->getConfig('defaults.wrapper_class') . ' col-md-4',
                    ],
                ])
                ->add('career_preferences_row_close', 'html', [
                    'html' => '</div>',
                ])
                ->add('salary_row_open', 'html', [
                    'html' => '<div class="row">',
                ])
                ->add('desired_salary_from', TextField::class, TextFieldOption::make()
                    ->label('Desired Salary (From)')
                    ->placeholder('e.g. 1500')
                    ->wrapperAttributes(['class' => $this->formHelper->getConfig('defaults.wrapper_class') . ' col-md-6']))
                ->add('desired_salary_to', TextField::class, TextFieldOption::make()
                    ->label('Desired Salary (To)')
                    ->placeholder('e.g. 3000')
                    ->wrapperAttributes(['class' => $this->formHelper->getConfig('defaults.wrapper_class') . ' col-md-6']))
                ->add('salary_row_close', 'html', [
                    'html' => '</div>',
                ])
                ->add('telegram_chat_id', 'text', [
                    'label' => 'Telegram Chat ID',
                    'attr' => [
                        'placeholder' => 'e.g. 123456789',
                        'data-counter' => 100,
                    ],
                ])
                ->addMetaBoxes([
                    'educations' => [
                        'title' => trans('plugins/job-board::forms.educations'),
                        'content' => view('plugins/job-board::accounts.educations', [
                            'educations' => $account->educations()->get(),
                        ])->render(),
                        'attributes' => [
                            'id' => 'educations-table',
                        ],
                        'header_actions' => view('plugins/job-board::accounts.header-actions-button', [
                            'modalTarget' => '#add-education-modal',
                            'label' => trans('plugins/job-board::account.add_education'),
                        ])->render(),
                        'has_table' => true,
                    ],
                ])
                ->addMetaBoxes([
                    'experiences' => [
                        'title' => trans('plugins/job-board::forms.experiences'),
                        'content' => view('plugins/job-board::accounts.experiences', [
                            'experiences' => $account->experiences()->get(),
                        ])->render(),
                        'attributes' => [
                            'id' => 'experiences-table',
                        ],
                        'header_actions' => view('plugins/job-board::accounts.header-actions-button', [
                            'modalTarget' => '#add-experience-modal',
                            'label' => trans('plugins/job-board::account.add_experience'),
                        ])->render(),
                        'has_table' => true,
                    ],
                ])
                ->addMetaBoxes([
                    'languages' => [
                        'title' => trans('plugins/job-board::forms.languages'),
                        'content' => view('plugins/job-board::accounts.languages', [
                            'languages' => $account->languages()->with('languageLevel')->get(),
                        ])->render(),
                        'attributes' => [
                            'id' => 'languages-table',
                        ],
                        'header_actions' => view('plugins/job-board::accounts.header-actions-button', [
                            'modalTarget' => '#add-language-modal',
                            'label' => trans('plugins/job-board::account.add_language'),
                        ])->render(),
                        'has_table' => true,
                    ],
                ]);
        }

        if ($account->getKey() && $account->isEmployer()) {
            SlugHelper::removeModule(Account::class);

            $this->addMetaBoxes([
                'credits' => [
                    'attributes' => [
                        'id' => 'credit-histories',
                    ],
                    'title' => trans('plugins/job-board::account.transactions'),
                    'subtitle' => trans('plugins/job-board::forms.credits_subtitle', ['count' => number_format($this->getModel()->credits)]),
                    'header_actions' => view('plugins/job-board::accounts.header-actions-button', [
                        'modalTarget' => '#add-credit-modal',
                        'label' => trans('plugins/job-board::account.add_credit'),
                    ])->render(),
                    'content' => view('plugins/job-board::accounts.credits', [
                        'account' => $account,
                        'transactions' => $account->transactions()->latest()->get(),
                    ])->render(),
                ],
            ]);
        }
    }

    protected function repeatableContactField(
        string $name,
        string $label,
        array $values,
        string $inputType,
        string $placeholder,
        string $addLabel,
        string $help
    ): string {
        $values = array_values(array_filter(array_map('trim', $values)));
        if (empty($values)) {
            $values = [''];
        }

        $ph = e($placeholder);
        $rows = '';
        foreach ($values as $value) {
            $rows .= '<div class="input-group input-group-sm mb-2 contact-row">'
                . '<input type="' . e($inputType) . '" name="' . e($name) . '[]" class="form-control" '
                . 'value="' . e($value) . '" placeholder="' . $ph . '">'
                . '<button type="button" class="btn btn-outline-danger js-contact-remove" title="Remove">'
                . '<i class="fa fa-times"></i></button>'
                . '</div>';
        }

        $script = <<<'JS'
<script>
(function () {
    if (window.__contactListInit) { return; }
    window.__contactListInit = true;

    function rowHtml(name, type, placeholder) {
        var span = document.createElement('span');
        span.textContent = placeholder || '';
        return '<div class="input-group input-group-sm mb-2 contact-row">'
            + '<input type="' + type + '" name="' + name + '[]" class="form-control" placeholder="' + span.innerHTML + '">'
            + '<button type="button" class="btn btn-outline-danger js-contact-remove" title="Remove"><i class="fa fa-times"></i></button>'
            + '</div>';
    }

    document.addEventListener('click', function (e) {
        var add = e.target.closest('.js-contact-add');
        if (add) {
            e.preventDefault();
            var name = add.getAttribute('data-name');
            var list = document.querySelector('.contact-list[data-name="' + name + '"]');
            if (!list) { return; }
            list.insertAdjacentHTML('beforeend', rowHtml(name, add.getAttribute('data-type') || 'text', add.getAttribute('data-placeholder') || ''));
            var inputs = list.querySelectorAll('input');
            if (inputs.length) { inputs[inputs.length - 1].focus(); }
            return;
        }

        var rm = e.target.closest('.js-contact-remove');
        if (rm) {
            e.preventDefault();
            var row = rm.closest('.contact-row');
            var list = row ? row.parentNode : null;
            if (row) { row.remove(); }
            if (list && list.querySelectorAll('.contact-row').length === 0) {
                var addBtn = document.querySelector('.js-contact-add[data-name="' + list.getAttribute('data-name') + '"]');
                list.insertAdjacentHTML('beforeend', rowHtml(
                    list.getAttribute('data-name'),
                    addBtn ? (addBtn.getAttribute('data-type') || 'text') : 'text',
                    addBtn ? (addBtn.getAttribute('data-placeholder') || '') : ''
                ));
            }
        }
    });
})();
</script>
JS;

        return '<div class="mb-3 position-relative">'
            . '<label class="form-label d-block">' . e($label) . '</label>'
            . '<div class="contact-list" data-name="' . e($name) . '">' . $rows . '</div>'
            . '<button type="button" class="btn btn-sm btn-outline-secondary js-contact-add" '
            . 'data-name="' . e($name) . '" data-type="' . e($inputType) . '" data-placeholder="' . $ph . '">'
            . '<i class="fa fa-plus me-1"></i> ' . e($addLabel) . '</button>'
            . '<small class="form-hint d-block mt-2 text-muted">' . e($help) . '</small>'
            . $script
            . '</div>';
    }
}
