<?php

namespace Botble\JobBoard\Forms;

use Botble\Base\Forms\FieldOptions\DescriptionFieldOption;
use Botble\Base\Forms\FieldOptions\IsFeaturedFieldOption;
use Botble\Base\Forms\FieldOptions\StatusFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\OnOffField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TagField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Http\Requests\CompanyRequest;
use Botble\JobBoard\Models\Company;
use Botble\Location\Fields\Options\SelectLocationFieldOption;
use Botble\Location\Fields\SelectLocationField;
use Illuminate\Support\Facades\DB;

class CompanyForm extends FormAbstract
{
    public function setup(): void
    {
        $accounts = null;

        if ($this->getModel()) {
            $accounts = DB::table('jb_companies_accounts')
                ->where('jb_companies_accounts.company_id', $this->getModel()->id)
                ->join('jb_accounts', 'jb_accounts.id', '=', 'jb_companies_accounts.account_id')
                ->select(DB::raw('CONCAT(jb_accounts.first_name, " ", jb_accounts.last_name) as name'))
                ->pluck('name')
                ->all();

            $accounts = implode(';', $accounts);
        }

        $this
            ->setupModel(new Company())
            ->setValidatorClass(CompanyRequest::class)
            ->columns(12)
            ->addCustomField('tags', TagField::class)
            ->add('name', 'text', [
                'label' => trans('plugins/job-board::forms.company_name'),
                'required' => true,
                'attr' => [
                    'placeholder' => trans('core/base::forms.name_placeholder'),
                    'data-counter' => 120,
                ],
                'colspan' => 12,
            ])
            ->add('description', TextareaField::class, DescriptionFieldOption::make()->colspan(12))
            ->add(
                'is_featured',
                OnOffField::class,
                IsFeaturedFieldOption::make()
            )
            ->add('content', 'editor', [
                'label' => trans('core/base::forms.content'),
                'attr' => [
                    'rows' => 4,
                    'placeholder' => trans('core/base::forms.description_placeholder'),
                ],
            ])
            ->add('tax_id', 'text', [
                'label' => trans('plugins/job-board::forms.tax_id'),
                'attr' => [
                    'placeholder' => trans('plugins/job-board::forms.tax_id'),
                    'data-counter' => 60,
                ],
                'colspan' => 4,
            ])
            ->add('ceo', 'text', [
                'label' => trans('plugins/job-board::forms.company_ceo'),
                'attr' => [
                    'placeholder' => trans('plugins/job-board::forms.company_ceo'),
                    'data-counter' => 120,
                ],
                'colspan' => 4,
            ])
            ->add('email', 'email', [
                'label' => trans('plugins/job-board::forms.email'),
                'attr' => [
                    'placeholder' => trans('plugins/job-board::forms.email_placeholder'),
                    'data-counter' => 120,
                ],
                'colspan' => 4,
            ])
            ->add('phone', 'text', [
                'label' => trans('plugins/job-board::forms.phone'),
                'attr' => [
                    'placeholder' => trans('plugins/job-board::forms.phone_placeholder'),
                    'data-counter' => 30,
                ],
                'colspan' => 4,
            ])
            ->add('website', 'text', [
                'label' => trans('plugins/job-board::forms.website'),
                'attr' => [
                    'placeholder' => trans('plugins/job-board::forms.website_placeholder'),
                    'data-counter' => 120,
                ],
                'colspan' => 4,
            ])
            ->add('year_founded', 'number', [
                'label' => trans('plugins/job-board::forms.year_founded'),
                'attr' => [
                    'placeholder' => trans('plugins/job-board::forms.year_founded_placeholder'),
                    'data-counter' => 10,
                ],
                'colspan' => 4,
            ])
            ->add('number_of_offices', 'number', [
                'label' => trans('plugins/job-board::forms.number_of_offices'),
                'attr' => [
                    'placeholder' => trans('plugins/job-board::forms.number_of_offices_placeholder'),
                    'data-counter' => 10,
                ],
                'colspan' => 4,
            ])
            ->add('number_of_employees', 'text', [
                'label' => trans('plugins/job-board::forms.number_of_employees'),
                'attr' => [
                    'placeholder' => trans('plugins/job-board::forms.number_of_employees_placeholder'),
                    'data-counter' => 10,
                ],
                'colspan' => 4,
            ])
            ->add('annual_revenue', 'text', [
                'label' => trans('plugins/job-board::forms.annual_revenue'),
                'attr' => [
                    'placeholder' => trans('plugins/job-board::forms.annual_revenue_placeholder'),
                    'data-counter' => 10,
                ],
                'colspan' => 4,
            ])
            ->when(is_plugin_active('location'), function (FormAbstract $form): void {
                $form->add(
                    'location_data',
                    SelectLocationField::class,
                    SelectLocationFieldOption::make()
                );
            })
            ->add('address', 'text', [
                'label' => trans('plugins/job-board::forms.address'),
                'attr' => [
                    'placeholder' => trans('plugins/job-board::forms.address'),
                    'data-counter' => 120,
                ],
                'colspan' => 6,
            ])
            ->add('postal_code', 'text', [
                'label' => trans('plugins/job-board::forms.postal_code'),
                'attr' => [
                    'placeholder' => trans('plugins/job-board::forms.postal_code'),
                    'data-counter' => 20,
                ],
                'colspan' => 6,
            ])
            ->when(JobBoardHelper::isEnabledLatLongFields(), function (FormAbstract $form): void {
                $form->add('latitude', 'text', [
                    'label' => trans('plugins/job-board::forms.latitude'),
                    'attr' => [
                        'placeholder' => 'Ex: 1.462260',
                        'data-counter' => 25,
                    ],
                    'help_block' => [
                        'tag' => 'a',
                        'text' => trans('plugins/job-board::forms.latitude_helper'),
                        'attr' => [
                            'href' => 'https://www.latlong.net/convert-address-to-lat-long.html',
                            'target' => '_blank',
                            'rel' => 'nofollow',
                            'class' => 'small d-block mt-1',
                        ],
                    ],
                    'colspan' => 6,
                ])
                ->add('longitude', 'text', [
                    'label' => trans('plugins/job-board::forms.longitude'),
                    'attr' => [
                        'placeholder' => 'Ex: 103.812530',
                        'data-counter' => 25,
                    ],
                    'help_block' => [
                        'tag' => 'a',
                        'text' => trans('plugins/job-board::forms.longitude_helper'),
                        'attr' => [
                            'href' => 'https://www.latlong.net/convert-address-to-lat-long.html',
                            'target' => '_blank',
                            'rel' => 'nofollow',
                            'class' => 'small d-block mt-1',
                        ],
                    ],
                    'colspan' => 6,
                ]);
            })
            ->add('status', SelectField::class, StatusFieldOption::make())
            ->add('accounts', 'tags', [
                'label' => trans('plugins/job-board::forms.account_manager'),
                'value' => $accounts,
                'attr' => [
                    'placeholder' => trans('plugins/job-board::forms.account_manager_placeholder'),
                    'data-url' => route('accounts.all-employers'),
                    'data-delimiters' => ';',
                    'data-keep-invalid-tags' => 'false',
                    'data-enforce-whitelist' => 'true',
                    'data-whitelist' => $accounts,
                ],
            ])
            ->add(
                'unique_id',
                TextField::class,
                TextFieldOption::make()
                    ->value($this->getModel()->getKey() ? $this->getModel()->unique_id : $this->getModel()->generateUniqueId())
                    ->label(trans('plugins/job-board::job-board.form.unique_id'))
            )
            ->add('logo', 'mediaImage', [
                'label' => trans('plugins/job-board::forms.logo'),
            ])
            ->add('cover_image', 'mediaImage', [
                'label' => trans('plugins/job-board::forms.cover_image'),
            ])
            ->setBreakFieldPoint('status')
            ->addMetaBoxes([
                'social_links' => [
                    'title' => trans('plugins/job-board::forms.social_links'),
                    'content' => view(
                        JobBoardHelper::viewPath('dashboard.forms.social-links'),
                        ['company' => $this->getModel()]
                    )->render(),
                ],
            ]);
    }
}
