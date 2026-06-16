<?php

namespace Botble\JobBoard\Forms;

use Botble\Base\Forms\FieldOptions\NameFieldOption;
use Botble\Base\Forms\Fields\OnOffField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;
use Botble\JobBoard\Http\Requests\JobCrawlerRequest;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\JobCrawler;

class JobCrawlerForm extends FormAbstract
{
    public function setup(): void
    {
        /** @var JobCrawler|null $model */
        $model = $this->getModel();

        $companies = Company::query()
            ->oldest('name')
            ->pluck('name', 'id')
            ->prepend('Use first company as fallback', '')
            ->all();

        $this
            ->setupModel(new JobCrawler())
            ->setValidatorClass(JobCrawlerRequest::class)
            ->columns(12)
            ->add('name', TextField::class, NameFieldOption::make()->required()->colspan(6))
            ->add('is_active', OnOffField::class, [
                'label' => 'Active',
                'default_value' => true,
                'colspan' => 6,
            ])
            ->add('source_url', TextField::class, [
                'label' => 'Source URL',
                'required' => true,
                'attr' => ['placeholder' => 'https://example.com/jobs'],
            ])
            ->add('parser_type', SelectField::class, [
                'label' => 'Parser type',
                'choices' => [
                    'html' => 'HTML page using CSS selectors',
                    'json' => 'JSON feed using dot-path mappings',
                    'gozambiajobs' => 'Go Zambia Jobs listing + detail pages',
                    'wpjobmanager' => 'WP Job Manager live AJAX listings',
                    'empregomz' => 'Emprego Mozambique live listings',
                    'vacancybox' => 'VacancyBox Zimbabwe (XML sitemaps + JSON-LD)',
                    'jobzambia' => 'JobZambia (XML sitemap + HTML parsing)',
                    'ethiojobs' => 'EthioJobs Ethiopia (Next.js __NEXT_DATA__)',
                    'novojob' => 'Novojob (RSS feed, West/North Africa)',
                    'vacancymail' => 'VacancyMail Zimbabwe (HTML listing + JSON-LD)',
                    'noojobmonster' => 'Noo JobMonster theme (paginated HTML + JSON-LD)',
                    'emploitic' => 'Emploitic Algeria (XML sitemap + JSON-LD)',
                    'jobpoint' => 'JobPoint Botswana (paginated Inertia listings)',
                    'ijob' => 'iJob South Africa (paginated WordPress articles)',
                ],
                'colspan' => 6,
            ])
            ->add('schedule', SelectField::class, [
                'label' => 'Schedule',
                'choices' => JobCrawler::scheduleOptions(),
                'default_value' => JobCrawler::SCHEDULE_HOURLY,
                'colspan' => 6,
            ])
            ->add('default_company_id', SelectField::class, [
                'label' => 'Default company',
                'choices' => $companies,
                'colspan' => 6,
            ])
            ->add('item_selector', TextareaField::class, [
                'label' => 'Item selector / JSON list path',
                'attr' => ['rows' => 2, 'placeholder' => '.job-card or data.jobs'],
            ])
            ->add('title_selector', TextField::class, [
                'label' => 'Title selector / path',
                'attr' => ['placeholder' => '.job-title or title'],
                'colspan' => 6,
            ])
            ->add('company_selector', TextField::class, [
                'label' => 'Company selector / path',
                'attr' => ['placeholder' => '.company or company.name'],
                'colspan' => 6,
            ])
            ->add('location_selector', TextField::class, [
                'label' => 'Location selector / path',
                'attr' => ['placeholder' => '.location or location'],
                'colspan' => 6,
            ])
            ->add('description_selector', TextField::class, [
                'label' => 'Description selector / path',
                'attr' => ['placeholder' => '.summary or description'],
                'colspan' => 6,
            ])
            ->add('content_selector', TextField::class, [
                'label' => 'Content selector / path',
                'attr' => ['placeholder' => '.job-content or content'],
                'colspan' => 6,
            ])
            ->add('apply_url_selector', TextField::class, [
                'label' => 'Apply URL selector / path',
                'attr' => ['placeholder' => 'a.apply@href or apply_url'],
                'colspan' => 6,
            ])
            ->add('field_mappings', TextareaField::class, [
                'label' => 'Extra field mappings',
                'value' => is_array($model?->field_mappings) ? json_encode($model->field_mappings, JSON_PRETTY_PRINT) : null,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => '{"salary_from":"salary.min","salary_to":"salary.max","external_source_id":"id"}',
                ],
                'help_block' => [
                    'text' => 'Optional JSON object. Keys are job fields; values are JSON dot paths or HTML selectors.',
                ],
            ])
            ->setBreakFieldPoint('item_selector');
    }
}
