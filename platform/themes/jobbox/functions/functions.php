<?php

use Botble\Base\Facades\MetaBox;
use Botble\Base\Forms\FieldOptions\CheckboxFieldOption;
use Botble\Base\Forms\FieldOptions\MediaImageFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\MediaImageField;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\OnOffField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;
use Botble\Base\Rules\OnOffRule;
use Botble\Blog\Models\Post;
use Botble\JobBoard\Forms\AccountForm;
use Botble\JobBoard\Forms\Fronts\AccountSettingForm;
use Botble\JobBoard\Forms\JobForm;
use Botble\JobBoard\Forms\Settings\GeneralSettingForm;
use Botble\JobBoard\Http\Requests\SettingRequest;
use Botble\JobBoard\Http\Requests\Settings\GeneralSettingRequest;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\Job;
use Botble\Media\Facades\RvMedia;
use Botble\Menu\Facades\Menu;
use Botble\Page\Models\Page;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Supports\ThemeSupport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

register_page_template([
    'default' => __('Default'),
    'page-detail' => __('Page detail full width'),
    'page-detail-boxed' => __('Page detail boxed'),
    'homepage' => __('Homepage'),
]);

register_sidebar([
    'id' => 'footer_sidebar',
    'name' => __('Footer sidebar'),
    'description' => __('Widgets in footer of page'),
]);

register_sidebar([
    'id' => 'pre_footer_sidebar',
    'name' => __('Pre footer sidebar'),
    'description' => __('Widgets at the bottom of the page.'),
]);

register_sidebar([
    'id' => 'blog_sidebar',
    'name' => __('Blog sidebar'),
    'description' => __('Widgets at the right of the page.'),
]);

register_sidebar([
    'id' => 'candidate_sidebar',
    'name' => __('Candidate sidebar'),
    'description' => __('Widgets at the right of the page candidate detail.'),
]);

register_sidebar([
    'id' => 'company_sidebar',
    'name' => __('Company sidebar'),
    'description' => __('Widgets at the right of the page company detail.'),
]);

Menu::addMenuLocation('footer-menu', 'Footer navigation');

if (! function_exists('wakanda_country_flag')) {
    function wakanda_country_flag(?string $countryCode): string
    {
        $countryCode = strtoupper((string) $countryCode);

        if (strlen($countryCode) !== 2 || ! ctype_alpha($countryCode)) {
            return '&#127987;';
        }

        return '&#' . (127397 + ord($countryCode[0])) . ';&#' . (127397 + ord($countryCode[1])) . ';';
    }
}

if (! function_exists('wakanda_currency_meta')) {
    function wakanda_currency_meta(?string $currencyCode): array
    {
        $currencyCode = strtoupper((string) $currencyCode);

        $currencies = [
            'AOA' => ['country' => 'Angola', 'country_code' => 'AO', 'name' => 'Angolan kwanza'],
            'BIF' => ['country' => 'Burundi', 'country_code' => 'BI', 'name' => 'Burundian franc'],
            'BWP' => ['country' => 'Botswana', 'country_code' => 'BW', 'name' => 'Botswana pula'],
            'CDF' => ['country' => 'Democratic Republic of the Congo', 'country_code' => 'CD', 'name' => 'Congolese franc'],
            'CVE' => ['country' => 'Cabo Verde', 'country_code' => 'CV', 'name' => 'Cape Verdean escudo'],
            'DJF' => ['country' => 'Djibouti', 'country_code' => 'DJ', 'name' => 'Djiboutian franc'],
            'DKK' => ['country' => 'Denmark', 'country_code' => 'DK', 'name' => 'Danish krone'],
            'DZD' => ['country' => 'Algeria', 'country_code' => 'DZ', 'name' => 'Algerian dinar'],
            'EGP' => ['country' => 'Egypt', 'country_code' => 'EG', 'name' => 'Egyptian pound'],
            'ERN' => ['country' => 'Eritrea', 'country_code' => 'ER', 'name' => 'Eritrean nakfa'],
            'ETB' => ['country' => 'Ethiopia', 'country_code' => 'ET', 'name' => 'Ethiopian birr'],
            'EUR' => ['country' => 'European Union', 'country_code' => null, 'flag' => '&#127466;&#127482;', 'name' => 'Euro'],
            'GBP' => ['country' => 'United Kingdom', 'country_code' => 'GB', 'name' => 'British pound'],
            'GHS' => ['country' => 'Ghana', 'country_code' => 'GH', 'name' => 'Ghanaian cedi'],
            'GMD' => ['country' => 'Gambia', 'country_code' => 'GM', 'name' => 'Gambian dalasi'],
            'GNF' => ['country' => 'Guinea', 'country_code' => 'GN', 'name' => 'Guinean franc'],
            'KES' => ['country' => 'Kenya', 'country_code' => 'KE', 'name' => 'Kenyan shilling'],
            'KMF' => ['country' => 'Comoros', 'country_code' => 'KM', 'name' => 'Comorian franc'],
            'LRD' => ['country' => 'Liberia', 'country_code' => 'LR', 'name' => 'Liberian dollar'],
            'LSL' => ['country' => 'Lesotho', 'country_code' => 'LS', 'name' => 'Lesotho loti'],
            'LYD' => ['country' => 'Libya', 'country_code' => 'LY', 'name' => 'Libyan dinar'],
            'MAD' => ['country' => 'Morocco', 'country_code' => 'MA', 'name' => 'Moroccan dirham'],
            'MGA' => ['country' => 'Madagascar', 'country_code' => 'MG', 'name' => 'Malagasy ariary'],
            'MRU' => ['country' => 'Mauritania', 'country_code' => 'MR', 'name' => 'Mauritanian ouguiya'],
            'MUR' => ['country' => 'Mauritius', 'country_code' => 'MU', 'name' => 'Mauritian rupee'],
            'MWK' => ['country' => 'Malawi', 'country_code' => 'MW', 'name' => 'Malawian kwacha'],
            'MZN' => ['country' => 'Mozambique', 'country_code' => 'MZ', 'name' => 'Mozambican metical'],
            'NAD' => ['country' => 'Namibia', 'country_code' => 'NA', 'name' => 'Namibian dollar'],
            'NGN' => ['country' => 'Nigeria', 'country_code' => 'NG', 'name' => 'Nigerian naira'],
            'RWF' => ['country' => 'Rwanda', 'country_code' => 'RW', 'name' => 'Rwandan franc'],
            'SCR' => ['country' => 'Seychelles', 'country_code' => 'SC', 'name' => 'Seychellois rupee'],
            'SDG' => ['country' => 'Sudan', 'country_code' => 'SD', 'name' => 'Sudanese pound'],
            'SLE' => ['country' => 'Sierra Leone', 'country_code' => 'SL', 'name' => 'Sierra Leonean leone'],
            'SOS' => ['country' => 'Somalia', 'country_code' => 'SO', 'name' => 'Somali shilling'],
            'SSP' => ['country' => 'South Sudan', 'country_code' => 'SS', 'name' => 'South Sudanese pound'],
            'STN' => ['country' => 'Sao Tome and Principe', 'country_code' => 'ST', 'name' => 'Sao Tome and Principe dobra'],
            'SZL' => ['country' => 'Eswatini', 'country_code' => 'SZ', 'name' => 'Swazi lilangeni'],
            'TND' => ['country' => 'Tunisia', 'country_code' => 'TN', 'name' => 'Tunisian dinar'],
            'TZS' => ['country' => 'Tanzania', 'country_code' => 'TZ', 'name' => 'Tanzanian shilling'],
            'UGX' => ['country' => 'Uganda', 'country_code' => 'UG', 'name' => 'Ugandan shilling'],
            'USD' => ['country' => 'United States', 'country_code' => 'US', 'name' => 'United States dollar'],
            'VND' => ['country' => 'Vietnam', 'country_code' => 'VN', 'name' => 'Vietnamese dong'],
            'XAF' => ['country' => 'Central Africa', 'country_code' => null, 'flag' => '&#127757;', 'name' => 'Central African CFA franc'],
            'XOF' => ['country' => 'West Africa', 'country_code' => null, 'flag' => '&#127757;', 'name' => 'West African CFA franc'],
            'ZAR' => ['country' => 'South Africa', 'country_code' => 'ZA', 'name' => 'South African rand'],
            'ZMW' => ['country' => 'Zambia', 'country_code' => 'ZM', 'name' => 'Zambian kwacha'],
            'ZWL' => ['country' => 'Zimbabwe', 'country_code' => 'ZW', 'name' => 'Zimbabwean dollar'],
        ];

        $meta = $currencies[$currencyCode] ?? [
            'country' => $currencyCode,
            'country_code' => null,
            'name' => $currencyCode,
        ];

        $meta['code'] = $currencyCode;
        $meta['flag'] = $meta['flag'] ?? wakanda_country_flag($meta['country_code']);
        $meta['label'] = trim(sprintf('%s - %s (%s)', $meta['country'], $meta['name'], $currencyCode));

        return $meta;
    }
}

if (! function_exists('wakanda_localization_countries')) {
    function wakanda_localization_countries()
    {
        if (! is_plugin_active('location')) {
            return collect();
        }

        return cache()->remember('wakanda_localization_countries', 3600, function () {
            return \Botble\Location\Models\Country::query()
                ->where('status', \Botble\Base\Enums\BaseStatusEnum::PUBLISHED)
                ->orderByDesc('is_default')
                ->oldest('order')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'is_default']);
        });
    }
}

if (! function_exists('wakanda_detect_country_code')) {
    function wakanda_detect_country_code(): ?string
    {
        $headerCountry = strtoupper((string) request()->header('CF-IPCountry'));

        if (strlen($headerCountry) === 2 && $headerCountry !== 'XX') {
            return $headerCountry;
        }

        try {
            $reader = new \GeoIp2\Database\Reader(base_path('platform/plugins/job-board/database/GeoLite2-Country.mmdb'));
            $record = $reader->country(request()->ip());

            return $record->country->isoCode ?: null;
        } catch (Throwable) {
            return null;
        }
    }
}

if (! function_exists('wakanda_encode_country_id')) {
    function wakanda_encode_country_id(int $countryId): string
    {
        $encrypted = \Illuminate\Support\Facades\Crypt::encryptString((string) $countryId);

        return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
    }
}

if (! function_exists('wakanda_decode_country_token')) {
    function wakanda_decode_country_token(?string $token): ?int
    {
        if (! $token) {
            return null;
        }

        try {
            $encrypted = base64_decode(strtr($token, '-_', '+/'), true);

            if (! $encrypted) {
                return null;
            }

            $countryId = (int) \Illuminate\Support\Facades\Crypt::decryptString($encrypted);

            return $countryId > 0 ? $countryId : null;
        } catch (Throwable) {
            return null;
        }
    }
}

if (! function_exists('wakanda_country_from_host')) {
    function wakanda_country_from_host()
    {
        $host = strtolower((string) request()->getHost());

        if (! str_ends_with($host, 'wakandajobs.com')) {
            return null;
        }

        $subdomain = str_replace('.wakandajobs.com', '', $host);

        if (! $subdomain || in_array($subdomain, ['www', 'wakandajobs.com'], true)) {
            return null;
        }

        $countryNames = [
            'zambia' => 'Zambia',
            'nigeria' => 'Nigeria',
            'ghana' => 'Ghana',
            'kenya' => 'Kenya',
            'zimbabwe' => 'Zimbabwe',
            'southafrica' => 'South Africa',
        ];

        if (! isset($countryNames[$subdomain])) {
            return null;
        }

        return wakanda_localization_countries()
            ->first(fn ($country) => strcasecmp($country->name, $countryNames[$subdomain]) === 0);
    }
}

if (! function_exists('wakanda_telegram_channel_url')) {
    function wakanda_telegram_channel_url(?int $countryId): ?string
    {
        static $map = [
            7  => 'https://t.me/wakanda_jobs_zambia',
            53 => 'https://t.me/wakanda_jobs_south_africa',
            46 => 'https://t.me/wakanda_jobs_nigeria',
            41 => 'https://t.me/wakanda_jobs_mauritius',
            58 => 'https://t.me/wakanda_jobs_tunisia',
            30 => 'https://t.me/wakanda_jobs_ghana',
            33 => 'https://t.me/wakanda_jobs_kenya',
            42 => 'https://t.me/wakanda_jobs_morocco',
            15 => 'https://t.me/wakanda_jobs_cameroon',
            59 => 'https://t.me/wakanda_jobs_uganda',
        ];
        return ($countryId && isset($map[$countryId])) ? $map[$countryId] : null;
    }
}

if (! function_exists('wakanda_all_telegram_channels')) {
    function wakanda_all_telegram_channels(): array
    {
        return [
            ['country_id' => 53, 'name' => 'South Africa', 'url' => 'https://t.me/wakanda_jobs_south_africa'],
            ['country_id' => 46, 'name' => 'Nigeria',       'url' => 'https://t.me/wakanda_jobs_nigeria'],
            ['country_id' => 7,  'name' => 'Zambia',        'url' => 'https://t.me/wakanda_jobs_zambia'],
            ['country_id' => 41, 'name' => 'Mauritius',     'url' => 'https://t.me/wakanda_jobs_mauritius'],
            ['country_id' => 58, 'name' => 'Tunisia',       'url' => 'https://t.me/wakanda_jobs_tunisia'],
            ['country_id' => 30, 'name' => 'Ghana',         'url' => 'https://t.me/wakanda_jobs_ghana'],
            ['country_id' => 42, 'name' => 'Morocco',       'url' => 'https://t.me/wakanda_jobs_morocco'],
            ['country_id' => 33, 'name' => 'Kenya',         'url' => 'https://t.me/wakanda_jobs_kenya'],
            ['country_id' => 15, 'name' => 'Cameroon',      'url' => 'https://t.me/wakanda_jobs_cameroon'],
            ['country_id' => 59, 'name' => 'Uganda',        'url' => 'https://t.me/wakanda_jobs_uganda'],
        ];
    }
}

if (! function_exists('wakanda_selected_country')) {
    function wakanda_selected_country()
    {
        static $selectedCountry = null;

        if ($selectedCountry !== null) {
            return $selectedCountry;
        }

        $countries = wakanda_localization_countries();

        if ($countries->isEmpty()) {
            return $selectedCountry = false;
        }

        $countryId = wakanda_decode_country_token(request()->query('c'))
            ?: (int) session('wakanda_country_id')
            ?: (int) request()->cookie('wakanda_country_id')
            ?: (int) optional(auth('account')->user())->country_id;

        if ($countryId && $country = $countries->firstWhere('id', $countryId)) {
            return $selectedCountry = $country;
        }

        if ($country = wakanda_country_from_host()) {
            return $selectedCountry = $country;
        }

        if ($countryCode = wakanda_detect_country_code()) {
            if ($country = $countries->firstWhere('code', strtoupper($countryCode))) {
                return $selectedCountry = $country;
            }
        }

        return $selectedCountry = $countries->firstWhere('is_default', true) ?: $countries->first();
    }
}

if (! function_exists('wakanda_apply_localized_job_filter')) {
    function wakanda_apply_localized_job_filter(array $filters): array
    {
        if (! is_plugin_active('location')) {
            return $filters;
        }

        if (
            ! empty($filters['country_id'])
            || ! empty($filters['state_id'])
            || ! empty($filters['city_id'])
            || ! empty($filters['location'])
        ) {
            return $filters;
        }

        $country = wakanda_selected_country();

        if ($country && $country->id) {
            $filters['country_id'] = $country->id;
        }

        return $filters;
    }
}

app()->booted(function (): void {
    ThemeSupport::registerDateFormatOption();
    ThemeSupport::registerSocialSharing();

    RvMedia::addSize('featured', 403, 257);

    if (is_plugin_active('job-board')) {
        // Move the permalink field to appear right after last_name
        add_filter(BASE_FILTER_BEFORE_RENDER_FORM, function (\Botble\Base\Forms\FormAbstract $form) {
            if (! $form instanceof AccountSettingForm) {
                return $form;
            }

            if (! array_key_exists('slug', $form->getFields())) {
                return $form;
            }

            $model = $form->getModel();
            $form->remove(['slug']);
            $form->addAfter('last_name', 'slug', \Botble\Slug\Forms\Fields\PermalinkField::class, [
                'model' => $model,
                'colspan' => 'full',
            ]);

            return $form;
        }, 1713);

        AccountSettingForm::beforeSaving(function (AccountSettingForm $form): void {
            $request = $form->getRequest();
            $model = $form->getModel();

            if ($request->has('cover_image')) {
                $coverImageUrl = $request->input('cover_image');
                if ($request->hasFile('cover_image')) {
                    $result = RvMedia::handleUpload($request->file('cover_image'), 0, $model->upload_folder);

                    $coverImageUrl = $result['data']->url;
                }

                MetaBox::saveMetaBoxData($model, 'cover_image', $coverImageUrl);
            }
        });

        FormAbstract::extend(function (FormAbstract $form) {
            if ($form instanceof AccountSettingForm || $form instanceof AccountForm) {
                $form
                    ->addAfter(
                        'description',
                        'linkedin',
                        TextField::class,
                        TextFieldOption::make()
                            ->label(__('LinkedIn URL'))
                            ->metadata()
                            ->toArray()
                    );
            }

            if ($form instanceof AccountForm) {
                $form->add(
                    'cover_image',
                    MediaImageField::class,
                    MediaImageFieldOption::make()
                        ->label(__('Cover Image'))
                        ->metadata()
                        ->toArray()
                );
            }

            return $form;
        });

        add_filter('core_request_rules', function (array $rules, Request $request) {
            if ($request instanceof SettingRequest) {
                return array_merge($rules, [
                    'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif,bmp|max:' . ceil(RvMedia::getServerConfigMaxUploadFileSize() / 1024),
                ]);
            }

            return $rules;
        }, 120, 2);

        FormAbstract::extend(function (FormAbstract $form) {
            if ($form instanceof JobForm) {
                $form->addAfter(
                    'apply_url',
                    'is_direct_redirect',
                    OnOffField::class,
                    CheckboxFieldOption::make()
                        ->defaultValue(setting('job_board_enabled_default_direct_redirect_apply_job_from_url', true))
                        ->metadata()
                        ->helperText(__('If you enable this option, the apply button will redirect to the apply URL directly.'))
                        ->label(__('Is direct redirect?'))
                );
            }

            return $form;
        });

        GeneralSettingForm::extend(function (GeneralSettingForm $form): void {
            $form->add(
                'job_board_enabled_default_direct_redirect_apply_job_from_url',
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->value(setting('job_board_enabled_default_direct_redirect_apply_job_from_url', true))
                    ->label(__('Enable default direct redirection when applying from URL?'))
            );
        });

        add_filter('core_request_rules', function (array $rules, Request $request) {
            if ($request instanceof GeneralSettingRequest) {
                return [...$rules,
                    'job_board_enabled_default_direct_redirect_apply_job_from_url' => new OnOffRule(),
                ];
            }

            return $rules;
        }, 120, 2);
    }

    add_filter(BASE_FILTER_BEFORE_RENDER_FORM, function (FormAbstract $form, ?Model $data) {
        switch (get_class($data)) {
            case Category::class:
                $form
                    ->addAfter('status', 'job_category_image', 'mediaImage', [
                        'label' => __('Image'),
                        'value' => MetaBox::getMetaData($data, 'job_category_image', true),
                    ])
                    ->addAfter('job_category_image', 'icon_image', 'mediaImage', [
                        'label' => __('Icon Image'),
                        'value' => MetaBox::getMetaData($data, 'icon_image', true),
                    ]);

                break;
            case Post::class:
                $form
                    ->add('cover_image', 'mediaImage', [
                        'label' => __('Cover Image'),
                        'label_attr' => ['class' => 'control-label'],
                        'value' => MetaBox::getMetaData($data, 'cover_image', true),
                    ])
                    ->addAfter('status', 'time_to_read', 'number', [
                        'label' => __('Time to read'),
                        'value' => MetaBox::getMetaData($data, 'time_to_read', true),
                        'attr' => [
                            'placeholder' => __('Time to read (minute)'),
                            'class' => ['image-data form-control'],
                        ],
                    ]);

                break;
            case Page::class:
                $form
                    ->add('background_breadcrumb', 'mediaImage', [
                        'label' => __('Background Breadcrumb'),
                        'label_attr' => ['class' => 'control-label'],
                        'value' => MetaBox::getMetaData($data, 'background_breadcrumb', true),
                    ]);

                break;
            case Job::class:
                if (auth()->check()) {
                    $form
                        ->addBefore('categories[]', 'featured_image', 'mediaImage', [
                            'label' => __('Featured Image'),
                            'label_attr' => ['class' => 'control-label'],
                            'value' => MetaBox::getMetaData($data, 'featured_image', true),
                        ]);
                } else {
                    $form
                        ->addAfter('status', 'featured_image', 'mediaImage', [
                            'label' => __('Featured Image'),
                            'label_attr' => ['class' => 'control-label'],
                            'value' => MetaBox::getMetaData($data, 'featured_image', true),
                        ]);
                }

                break;
        }

        return $form;
    }, 120, 3);

    add_action([BASE_ACTION_AFTER_CREATE_CONTENT, BASE_ACTION_AFTER_UPDATE_CONTENT], function (string $screen, Request $request, $data): void {
        if ($data instanceof Post && $request->has('time_to_read')) {
            MetaBox::saveMetaBoxData($data, 'time_to_read', $request->input('time_to_read'));
        }

        if ($data instanceof Category) {
            if ($request->has('job_category_image')) {
                MetaBox::saveMetaBoxData($data, 'job_category_image', $request->input('job_category_image'));
            }

            if ($request->has('icon_image')) {
                MetaBox::saveMetaBoxData($data, 'icon_image', $request->input('icon_image'));
            }
        }

        if ($data instanceof Post) {
            MetaBox::saveMetaBoxData($data, 'cover_image', $request->input('cover_image'));
        }

        if ($data instanceof Page) {
            MetaBox::saveMetaBoxData($data, 'background_breadcrumb', $request->input('background_breadcrumb'));
        }

        if ($data instanceof Job) {
            if ($request->has('featured_image')) {
                MetaBox::saveMetaBoxData($data, 'featured_image', $request->input('featured_image'));
            }

            if ($request->has('is_direct_redirect')) {
                MetaBox::saveMetaBoxData($data, 'is_direct_redirect', $request->input('is_direct_redirect'));
            }
        }
    }, 120, 3);

    add_filter('account_settings_page', function (?string $html, Account $account) {
        return $html . Theme::partial('account-custom-fields', compact('account'));
    }, 127, 2);

    if (is_plugin_active('ads')) {
        add_filter('ads_locations', function (array $locations) {
            return [
                ...$locations,
                'main_content_before' => __('Main Content (before)'),
                'main_content_after' => __('Main Content (after)'),
                'blog_sidebar_before' => __('Blog Sidebar (before)'),
                'blog_sidebar_after' => __('Blog Sidebar (after)'),
                'company_sidebar_before' => __('Company Sidebar (before)'),
                'company_sidebar_after' => __('Company Sidebar (after)'),
                'candidate_sidebar_before' => __('Candidate Sidebar (before)'),
                'candidate_sidebar_after' => __('Candidate Sidebar (after)'),
                'footer_before' => __('Footer (before)'),
                'footer_after' => __('Footer (after)'),
                'post_list_before' => __('Post List (before)'),
                'post_list_after' => __('Post List (after)'),
                'post_before' => __('Post Detail (before)'),
                'post_after' => __('Post Detail (after)'),
                'job_list_before' => __('Job List (before)'),
                'job_list_after' => __('Job List (after)'),
                'job_before' => __('Job Detail (before)'),
                'job_after' => __('Job Detail (after)'),
                'company_list_before' => __('Company List (before)'),
                'company_list_after' => __('Company List (after)'),
                'company_before' => __('Company Detail (before)'),
                'company_after' => __('Company Detail (after)'),
                'candidate_list_before' => __('Candidate List (before)'),
                'candidate_list_after' => __('Candidate List (after)'),
                'candidate_before' => __('Candidate Detail (before)'),
                'candidate_after' => __('Candidate Detail (after)'),
            ];
        }, 128);
    }
});

// Sync currency with the selected country on every page load.
// On every request, sync the application currency to the selected country.
// This runs before any rendering so salary displays are always correct.
// The filter approach only fires when there is no session — this hook
// also corrects a stale USD session when the user has Zambia selected.
app()->booted(function (): void {
    add_action('init', function (): void {
        $country = wakanda_selected_country();
        if (! $country || ! $country->code) {
            return;
        }
        $code = cms_currency()->countryCurrencies()[strtoupper((string) $country->code)] ?? null;
        if (! $code) {
            return;
        }
        if (session('currency') !== $code) {
            $currency = \Botble\JobBoard\Models\Currency::query()->where('title', $code)->first();
            if ($currency) {
                cms_currency()->setApplicationCurrency($currency);
            }
        }
    });
});

// Fallback filter for requests where init hook hasn't fired yet.
add_filter('cms_currency_detected_currency', function (?string $detected): ?string {
    $country = wakanda_selected_country();
    if ($country && $country->code) {
        $code = cms_currency()->countryCurrencies()[strtoupper((string) $country->code)] ?? null;
        if ($code) {
            return $code;
        }
    }
    return $detected;
}, 20);

if (! function_exists('get_currencies_json')) {
    function get_currencies_json(): array
    {
        $currency = get_application_currency();
        $numberAfterDot = $currency->decimals ?: 0;

        $thousandsSeparator = setting('job_board_thousands_separator', ',');

        return [
            'display_big_money' => config('plugins.real-estate.real-estate.display_big_money_in_million_billion'),
            'billion' => __('billion'),
            'million' => __('million'),
            'is_prefix_symbol' => $currency->is_prefix_symbol,
            'symbol' => $currency->symbol,
            'title' => $currency->title,
            'decimal_separator' => setting('job_board_decimal_separator', '.'),
            'thousands_separator' => $thousandsSeparator === 'space' ? ' ' : $thousandsSeparator,
            'number_after_dot' => $numberAfterDot,
            'show_symbol_or_title' => true,
        ];
    }
}
