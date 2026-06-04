<?php

use Botble\Base\Facades\AdminHelper;
use Botble\JobBoard\Http\Controllers\CandidateAlertController;
use Botble\JobBoard\Http\Controllers\AccountEducationController;
use Botble\JobBoard\Http\Controllers\CreditOrderController;
use Botble\JobBoard\Http\Controllers\AccountExperienceController;
use Botble\JobBoard\Http\Controllers\CareerServiceOrderController;
use Botble\JobBoard\Http\Controllers\EmployerSubscriptionController;
use Botble\JobBoard\Http\Controllers\FeaturedOrderController;
use Botble\JobBoard\Http\Controllers\FeaturedPackageController;
use Botble\JobBoard\Http\Controllers\JobAlertOrderController;
use Botble\JobBoard\Http\Controllers\JobAlertPackageController;
use Botble\JobBoard\Http\Controllers\Settings\CareerServiceSettingController;
use Botble\JobBoard\Http\Controllers\CouponController;
use Botble\JobBoard\Http\Controllers\CustomFieldController;
use Botble\JobBoard\Http\Controllers\ExportAccountController;
use Botble\JobBoard\Http\Controllers\ExportCompanyController;
use Botble\JobBoard\Http\Controllers\ExportJobController;
use Botble\JobBoard\Http\Controllers\ImportAccountController;
use Botble\JobBoard\Http\Controllers\ImportCompanyController;
use Botble\JobBoard\Http\Controllers\ImportJobController;
use Botble\JobBoard\Http\Controllers\ReportController;
use Botble\JobBoard\Http\Controllers\SalaryAnalyticsController;
use Botble\JobBoard\Http\Controllers\SalaryApiKeyController;
use Botble\JobBoard\Http\Controllers\SalaryReportController;
use Illuminate\Support\Facades\Route;

AdminHelper::registerRoutes(function (): void {
    Route::group(['prefix' => 'salary-analytics', 'as' => 'salary-analytics.', 'middleware' => 'auth'], function (): void {
        Route::get('', [SalaryAnalyticsController::class, 'index'])->name('index');
    });

    Route::group(['prefix' => 'salary-reports', 'as' => 'salary-reports.', 'middleware' => 'auth'], function (): void {
        Route::resource('', SalaryReportController::class)
            ->parameters(['' => 'salaryReport'])
            ->except('show');
        Route::post('{salaryReport}/generate-pdf', [SalaryReportController::class, 'generatePdf'])->name('generate-pdf');
        Route::post('{salaryReport}/toggle-published', [SalaryReportController::class, 'togglePublished'])->name('toggle-published');
        Route::get('{salaryReport}/download-pdf', [SalaryReportController::class, 'downloadPdf'])->name('download-pdf');
    });

    Route::group(['prefix' => 'salary-api-keys', 'as' => 'salary-api-keys.', 'middleware' => 'auth'], function (): void {
        Route::resource('', SalaryApiKeyController::class)
            ->parameters(['' => 'salaryApiKey'])
            ->except('show');
    });

    Route::group(['prefix' => 'career-alert-packages', 'as' => 'career-alert-packages.', 'middleware' => 'auth'], function (): void {
        Route::resource('', JobAlertPackageController::class)
            ->parameters(['' => 'careerAlertPackage'])
            ->except('show');
    });

    Route::group(['prefix' => 'featured-packages', 'as' => 'featured-packages.', 'middleware' => 'auth'], function (): void {
        Route::resource('', FeaturedPackageController::class)
            ->parameters(['' => 'featuredPackage'])
            ->except('show');
    });

    Route::group(['prefix' => 'featured-orders', 'as' => 'featured-orders.', 'middleware' => 'auth'], function (): void {
        Route::get('', [FeaturedOrderController::class, 'index'])->name('index');
        Route::post('{featuredOrder}/approve', [FeaturedOrderController::class, 'approve'])->name('approve');
        Route::post('{featuredOrder}/reject', [FeaturedOrderController::class, 'reject'])->name('reject');
    });

    Route::group(['prefix' => 'employer-subscriptions', 'as' => 'employer-subscriptions.', 'middleware' => 'auth'], function (): void {
        Route::get('', [EmployerSubscriptionController::class, 'index'])->name('index');
        Route::post('{employerSubscription}/activate', [EmployerSubscriptionController::class, 'activate'])->name('activate');
        Route::post('{employerSubscription}/cancel', [EmployerSubscriptionController::class, 'cancel'])->name('cancel');
    });

    Route::group(['prefix' => 'job-board/settings', 'as' => 'job-board.settings.', 'middleware' => 'auth'], function (): void {
        Route::get('career-services', [CareerServiceSettingController::class, 'edit'])->name('career-services');
        Route::put('career-services', [CareerServiceSettingController::class, 'update'])->name('career-services.update');
    });

    Route::group(['prefix' => 'job-board/candidate-alerts', 'as' => 'job-board.candidate-alerts.', 'middleware' => 'auth'], function (): void {
        Route::get('', [CandidateAlertController::class, 'index'])->name('index');
        Route::post('', [CandidateAlertController::class, 'store'])->name('store');
        Route::put('{candidateAlert}', [CandidateAlertController::class, 'update'])->name('update')->wherePrimaryKey('candidateAlert');
        Route::delete('{candidateAlert}', [CandidateAlertController::class, 'destroy'])->name('destroy')->wherePrimaryKey('candidateAlert');
        Route::post('{candidateAlert}/toggle', [CandidateAlertController::class, 'toggle'])->name('toggle')->wherePrimaryKey('candidateAlert');
        Route::get('{candidateAlert}/logs', [CandidateAlertController::class, 'logs'])->name('logs')->wherePrimaryKey('candidateAlert');
        Route::get('{candidateAlert}/preview', [CandidateAlertController::class, 'preview'])->name('preview')->wherePrimaryKey('candidateAlert');
        Route::post('{candidateAlert}/send-now', [CandidateAlertController::class, 'sendNow'])->name('send-now')->wherePrimaryKey('candidateAlert');
        Route::post('analyze-cv', [CandidateAlertController::class, 'analyzeCv'])->name('analyze-cv');
        Route::get('check-phone', [CandidateAlertController::class, 'checkPhone'])->name('check-phone');
        Route::get('location/states', [CandidateAlertController::class, 'locationStates'])->name('location.states');
        Route::get('location/cities', [CandidateAlertController::class, 'locationCities'])->name('location.cities');
    });

    Route::group(['prefix' => 'job-alert-orders', 'as' => 'job-alert-orders.', 'middleware' => 'auth'], function (): void {
        Route::get('', [JobAlertOrderController::class, 'index'])->name('index');
        Route::post('{jobAlertOrder}/approve', [JobAlertOrderController::class, 'approve'])->name('approve');
        Route::post('{jobAlertOrder}/reject', [JobAlertOrderController::class, 'reject'])->name('reject');
    });

    Route::prefix('credit-orders')->name('credit-orders.')->middleware('auth')->group(function (): void {
        Route::get('', [CreditOrderController::class, 'index'])->name('index');
        Route::post('{order}/approve', [CreditOrderController::class, 'approve'])->name('approve');
        Route::post('{order}/reject', [CreditOrderController::class, 'reject'])->name('reject');
    });

    Route::group(['prefix' => 'documentation', 'as' => 'documentation.', 'middleware' => 'auth'], function (): void {
        Route::get('', [\Botble\JobBoard\Http\Controllers\DocumentationController::class, 'index'])->name('index');
        Route::get('create', [\Botble\JobBoard\Http\Controllers\DocumentationController::class, 'create'])->name('create');
        Route::post('', [\Botble\JobBoard\Http\Controllers\DocumentationController::class, 'store'])->name('store');
        Route::get('{documentation}/edit', [\Botble\JobBoard\Http\Controllers\DocumentationController::class, 'edit'])->name('edit');
        Route::put('{documentation}', [\Botble\JobBoard\Http\Controllers\DocumentationController::class, 'update'])->name('update');
        Route::delete('{documentation}', [\Botble\JobBoard\Http\Controllers\DocumentationController::class, 'destroy'])->name('destroy');
    });

    Route::group(['prefix' => 'wakanda-verification', 'as' => 'wakanda-verification.', 'middleware' => 'auth'], function (): void {
        Route::get('', [\Botble\JobBoard\Http\Controllers\WakandaVerificationAdminController::class, 'index'])->name('index');
        Route::post('{verificationRequest}/approve', [\Botble\JobBoard\Http\Controllers\WakandaVerificationAdminController::class, 'approve'])->name('approve');
        Route::post('{verificationRequest}/reject', [\Botble\JobBoard\Http\Controllers\WakandaVerificationAdminController::class, 'reject'])->name('reject');
    });

    Route::group(['prefix' => 'career-service-orders', 'as' => 'career-service-orders.', 'middleware' => 'auth'], function (): void {
        Route::resource('', CareerServiceOrderController::class)
            ->parameters(['' => 'career-service-order'])
            ->only(['index', 'edit', 'update', 'destroy']);
        Route::post('{career_service_order}/upload-reviewed-cv', [CareerServiceOrderController::class, 'uploadReviewedCv'])->name('upload-reviewed-cv');
        Route::get('{career_service_order}/download-candidate-cv', [CareerServiceOrderController::class, 'downloadCandidateCv'])->name('download-candidate-cv');
        Route::get('{career_service_order}/download-reviewed-cv', [CareerServiceOrderController::class, 'downloadReviewedCv'])->name('download-reviewed-cv');
        Route::post('{career_service_order}/send-email', [CareerServiceOrderController::class, 'sendEmail'])->name('send-email');
        Route::delete('bulk-delete', [CareerServiceOrderController::class, 'bulkDestroy'])->name('bulk-destroy');
    });

    Route::group(['namespace' => 'Botble\JobBoard\Http\Controllers', 'prefix' => 'job-board', 'middleware' => 'auth'], function (): void {
        Route::prefix('settings')->name('job-board.settings.')->group(function (): void {
            Route::get('general', [
                'as' => 'general',
                'uses' => 'Settings\GeneralSettingController@edit',
            ]);

            Route::put('general', [
                'as' => 'general.update',
                'uses' => 'Settings\GeneralSettingController@update',
                'permission' => 'job-board.settings',
            ]);

            Route::get('currencies', [
                'as' => 'currencies',
                'uses' => 'Settings\CurrencySettingController@edit',
                'permission' => 'job-board.settings',
            ]);

            Route::put('currencies', [
                'as' => 'currencies.update',
                'uses' => 'Settings\CurrencySettingController@update',
                'permission' => 'job-board.settings',
            ]);

            Route::get('invoices', [
                'as' => 'invoices',
                'uses' => 'Settings\InvoiceSettingController@edit',
                'permission' => 'job-board.settings',
            ]);

            Route::put('invoices', [
                'as' => 'invoices.update',
                'uses' => 'Settings\InvoiceSettingController@update',
                'permission' => 'job-board.settings',
            ]);

            Route::get('invoice-template', [
                'as' => 'invoice-template',
                'uses' => 'Settings\InvoiceTemplateSettingController@edit',
                'permission' => 'invoice-template.index',
            ]);

            Route::put('invoice-template', [
                'as' => 'invoice-template.update',
                'uses' => 'Settings\InvoiceTemplateSettingController@update',
                'permission' => 'invoice-template.index',
                'middleware' => 'preventDemo',
            ]);

            Route::post('invoice-template/reset', [
                'as' => 'invoice-template.reset',
                'uses' => 'Settings\InvoiceTemplateSettingController@reset',
                'permission' => 'invoice-template.index',
                'middleware' => 'preventDemo',
            ]);

            Route::get('invoice-template/preview', [
                'as' => 'invoice-template.preview',
                'uses' => 'Settings\InvoiceTemplateSettingController@preview',
                'permission' => 'invoice-template.index',
            ]);
        });

        Route::group(['prefix' => 'jobs', 'as' => 'jobs.'], function (): void {
            Route::resource('', 'JobController')->parameters(['' => 'job']);

            Route::get('{id}/analytics', [
                'as' => 'analytics',
                'uses' => 'JobController@analytics',
                'permission' => 'jobs.index',
            ])->wherePrimaryKey();

            Route::get('{id}/generate-cover-image', [
                'as'         => 'generate-cover-image',
                'uses'       => 'JobCoverImageController@generate',
                'permission' => 'jobs.edit',
            ])->wherePrimaryKey();

            Route::get('{job}/post-kit', [
                'as'         => 'post-kit',
                'uses'       => 'TelegramSocialMessageController@showAdmin',
                'permission' => 'jobs.edit',
            ])->wherePrimaryKey();
        });

        Route::group(['prefix' => 'automations', 'as' => 'job-board.automations.'], function (): void {
            Route::get('', [
                'as'         => 'index',
                'uses'       => 'SocialAutomationController@index',
                'permission' => 'job-board.automations.index',
            ]);

            Route::post('', [
                'as'         => 'store',
                'uses'       => 'SocialAutomationController@store',
                'permission' => 'job-board.automations.index',
            ]);

            Route::put('{automation}', [
                'as'         => 'update',
                'uses'       => 'SocialAutomationController@update',
                'permission' => 'job-board.automations.index',
            ])->wherePrimaryKey();

            Route::delete('{automation}', [
                'as'         => 'destroy',
                'uses'       => 'SocialAutomationController@destroy',
                'permission' => 'job-board.automations.index',
            ])->wherePrimaryKey();

            Route::post('{automation}/toggle', [
                'as'         => 'toggle',
                'uses'       => 'SocialAutomationController@toggle',
                'permission' => 'job-board.automations.index',
            ])->wherePrimaryKey();

            Route::post('actions/clear-all-chats', [
                'as'         => 'clear-all-chats',
                'uses'       => 'SocialAutomationController@clearAllChats',
                'permission' => 'job-board.automations.index',
            ]);

            Route::post('actions/regenerate-today', [
                'as'         => 'regenerate-today',
                'uses'       => 'SocialAutomationController@regenerateTodayJobs',
                'permission' => 'job-board.automations.index',
            ]);

            Route::post('actions/whapi-send-yesterday', [
                'as'         => 'whapi-send-yesterday',
                'uses'       => 'SocialAutomationController@whapiSendYesterdayJobs',
                'permission' => 'job-board.automations.index',
            ]);

            Route::post('actions/whapi-send-job/{job}', [
                'as'         => 'whapi-send-job',
                'uses'       => 'SocialAutomationController@whapiSendJob',
                'permission' => 'job-board.automations.index',
            ])->wherePrimaryKey();
        });

        Route::group(['prefix' => 'agents', 'as' => 'job-board.crawlers.'], function (): void {
            Route::get('active-runs', [
                'as' => 'active-runs',
                'uses' => 'JobCrawlerController@activeRuns',
                'permission' => 'job-board.crawlers.run',
            ]);

            Route::post('{crawler}/run', [
                'as' => 'run',
                'uses' => 'JobCrawlerController@run',
                'permission' => 'job-board.crawlers.run',
            ])->wherePrimaryKey();

            Route::get('{crawler}/run-status/{run}', [
                'as' => 'run-status',
                'uses' => 'JobCrawlerController@runStatus',
                'permission' => 'job-board.crawlers.run',
            ])->where(['crawler' => '[0-9]+', 'run' => '[0-9]+']);

            Route::delete('{crawler}/clear-jobs', [
                'as' => 'clear-jobs',
                'uses' => 'JobCrawlerController@clearJobs',
                'permission' => 'job-board.crawlers.edit',
            ])->wherePrimaryKey();

            Route::post('{crawler}/toggle-active', [
                'as' => 'toggle-active',
                'uses' => 'JobCrawlerController@toggleActive',
                'permission' => 'job-board.crawlers.edit',
            ])->wherePrimaryKey();

            Route::resource('', 'JobCrawlerController')
                ->parameters(['' => 'crawler'])
                ->except('show');
        });

        Route::group(['prefix' => 'agent-runs', 'as' => 'job-board.crawler-runs.'], function (): void {
            Route::match(['GET', 'POST'], '', [
                'as' => 'index',
                'uses' => 'JobCrawlerRunController@index',
                'permission' => 'job-board.crawler-runs.index',
            ]);

            Route::get('{run}', [
                'as' => 'show',
                'uses' => 'JobCrawlerRunController@show',
                'permission' => 'job-board.crawler-runs.index',
            ])->wherePrimaryKey();
        });

        Route::group(['prefix' => 'job-types', 'as' => 'job-types.'], function (): void {
            Route::resource('', 'JobTypeController')
                ->parameters(['' => 'job-type']);
        });

        Route::group(['prefix' => 'job-skills', 'as' => 'job-skills.'], function (): void {
            Route::resource('', 'JobSkillController')->parameters(['' => 'job-skill']);
        });

        Route::group(['prefix' => 'job-shifts', 'as' => 'job-shifts.'], function (): void {
            Route::resource('', 'JobShiftController')->parameters(['' => 'job-shift']);
        });

        Route::group(['prefix' => 'job-experiences', 'as' => 'job-experiences.'], function (): void {
            Route::resource('', 'JobExperienceController')->parameters(['' => 'job-experience']);
        });

        Route::group(['prefix' => 'language-levels', 'as' => 'language-levels.'], function (): void {
            Route::resource('', 'LanguageLevelController')->parameters(['' => 'language-level']);
        });

        Route::group(['prefix' => 'career-levels', 'as' => 'career-levels.'], function (): void {
            Route::resource('', 'CareerLevelController')
                ->parameters(['' => 'career-level']);
        });

        Route::group(['prefix' => 'functional-areas', 'as' => 'functional-areas.'], function (): void {
            Route::resource('', 'FunctionalAreaController')
                ->parameters(['' => 'functional-area']);
        });

        Route::group(['prefix' => 'job-categories', 'as' => 'job-categories.'], function (): void {
            Route::resource('', 'CategoryController')
                ->parameters(['' => 'job-category']);

            Route::put('update-tree', [
                'as' => 'update-tree',
                'uses' => 'CategoryController@updateTree',
                'permission' => 'job-categories.edit',
            ]);

            Route::get('search', [
                'as' => 'search',
                'uses' => 'CategoryController@getSearch',
                'permission' => 'job-categories.index',
            ]);
        });

        Route::group(['prefix' => 'degree-types', 'as' => 'degree-types.'], function (): void {
            Route::resource('', 'DegreeTypeController')
                ->parameters(['' => 'degree-type']);
        });

        Route::group(['prefix' => 'degree-levels', 'as' => 'degree-levels.'], function (): void {
            Route::resource('', 'DegreeLevelController')
                ->parameters(['' => 'degree-level']);
        });

        Route::group(['prefix' => 'tags', 'as' => 'job-board.tag.'], function (): void {
            Route::resource('', 'TagController')
                ->parameters(['' => 'tag']);

            Route::get('all', [
                'as' => 'all',
                'uses' => 'TagController@getAllTags',
                'permission' => 'job-board.tag.index',
            ]);
        });

        Route::group(['prefix' => 'accounts', 'as' => 'accounts.'], function (): void {
            Route::resource('', 'AccountController')->parameters(['' => 'account']);

            Route::group(['prefix' => 'educations', 'as' => 'educations.'], function (): void {
                Route::resource('', AccountEducationController::class)->parameters(['' => 'education']);
                Route::get('{id}/{accountId}/edit-modal', [AccountEducationController::class, 'editModal'])->name(
                    'edit-modal'
                )->wherePrimaryKey(['id', 'accountId']);
            });

            Route::group(['prefix' => 'experiences', 'as' => 'experiences.'], function (): void {
                Route::resource('', AccountExperienceController::class)->parameters(['' => 'experience']);
                Route::get('{id}/{accountId}/edit-modal', [AccountExperienceController::class, 'editModal'])->name(
                    'edit-modal'
                )->wherePrimaryKey(['id', 'accountId']);
            });

            Route::prefix('languages')->name('languages.')->group(function (): void {
                Route::resource('', 'AccountLanguageController')->parameters(['' => 'language']);
                Route::get('{id}/{accountId}/edit-modal', 'AccountLanguageController@editModal')->name('edit-modal')->wherePrimaryKey(['id', 'accountId']);
            });

            Route::get('list', [
                'as' => 'list',
                'uses' => 'AccountController@getList',
                'permission' => 'accounts.index',
            ]);

            Route::post('credits/{id}', [
                'as' => 'credits.add',
                'uses' => 'TransactionController@postCreate',
                'permission' => 'accounts.edit',
            ])->wherePrimaryKey();

            Route::get('all-employers', [
                'as' => 'all-employers',
                'uses' => 'AccountController@getAllEmployers',
                'permission' => 'accounts.index',
            ]);
        });

        Route::group(['prefix' => 'packages', 'as' => 'packages.'], function (): void {
            Route::resource('', 'PackageController')->parameters(['' => 'package']);
        });

        Route::group(['prefix' => 'companies', 'as' => 'companies.'], function (): void {
            Route::resource('', 'CompanyController')->parameters(['' => 'company']);

            Route::get('list', [
                'as' => 'list',
                'uses' => 'CompanyController@getList',
                'permission' => 'companies.index',
            ]);

            Route::get('all', [
                'as' => 'all',
                'uses' => 'CompanyController@getAllCompanies',
                'permission' => 'companies.index',
            ]);

            Route::get('{company}/analytics', [
                'as' => 'analytics',
                'uses' => 'CompanyController@analytics',
                'permission' => 'companies.index',
            ])->wherePrimaryKey();

            Route::get('{company}/view', [
                'as' => 'view',
                'uses' => 'CompanyController@view',
                'permission' => 'companies.index',
            ])->wherePrimaryKey();

            Route::post('{company}/verify', [
                'as' => 'verify',
                'uses' => 'CompanyController@verify',
                'permission' => 'companies.edit',
            ])->wherePrimaryKey();

            Route::post('{company}/unverify', [
                'as' => 'unverify',
                'uses' => 'CompanyController@unverify',
                'permission' => 'companies.edit',
            ])->wherePrimaryKey();
        });

        Route::get('ajax/companies/{company}', [
            'as' => 'ajax.company.show',
            'uses' => 'CompanyController@ajaxGetCompany',
            'permission' => 'companies.index',
        ])->wherePrimaryKey();

        Route::post('ajax/companies', [
            'as' => 'ajax.company.create',
            'uses' => 'CompanyController@ajaxCreateCompany',
            'permission' => 'companies.create',
        ])->wherePrimaryKey();

        Route::group(['prefix' => 'job-applications', 'as' => 'job-applications.'], function (): void {
            Route::resource('', 'JobApplicationController')
                ->except(['create', 'store'])
                ->parameters(['' => 'job-application']);

            Route::get('download-cv/{application}', [
                'as' => 'download-cv',
                'uses' => 'JobApplicationController@downloadCv',
                'permission' => false,
            ])->wherePrimaryKey('application');
        });

        Route::group(['prefix' => 'invoices', 'as' => 'invoice.'], function (): void {
            Route::resource('', 'InvoiceController')
                ->parameters(['' => 'invoice'])
                ->except(['create', 'store', 'update']);

            Route::get('generate-invoice/{id}', [
                'as' => 'generate-invoice',
                'uses' => 'InvoiceController@getGenerateInvoice',
                'permission' => 'invoice.edit',
            ])->wherePrimaryKey();
        });

        Route::prefix('custom-fields')->name('job-board.custom-fields.')->group(function (): void {
            Route::resource('', CustomFieldController::class)->parameters(['' => 'custom-field']);

            Route::get('info', [
                'as' => 'get-info',
                'uses' => 'CustomFieldController@getInfo',
                'permission' => false,
            ]);
        });



        Route::group(['prefix' => 'coupons', 'as' => 'coupons.'], function (): void {
            Route::resource('', CouponController::class)
                ->parameters(['' => 'coupon']);

            Route::post('generate-coupon', [
                'as' => 'generate-coupon',
                'uses' => 'CouponController@generateCouponCode',
                'permission' => 'coupons.index',
            ]);
        });

        Route::get('reports', [
            'as' => 'job-board.reports',
            'uses' => 'ReportController@index',
            'permission' => 'job-board.reports',
        ]);

        Route::prefix('tools/data-synchronize')->name('tools.data-synchronize.')->group(function (): void {
            Route::prefix('export')->name('export.')->group(function (): void {
                Route::group(['prefix' => 'companies', 'as' => 'companies.', 'permission' => 'companies.export'], function (): void {
                    Route::get('/', [ExportCompanyController::class, 'index'])->name('index');
                    Route::post('/', [ExportCompanyController::class, 'store'])->name('store');
                });

                Route::group(['prefix' => 'accounts', 'as' => 'accounts.', 'permission' => 'accounts.export'], function (): void {
                    Route::get('/', [ExportAccountController::class, 'index'])->name('index');
                    Route::post('/', [ExportAccountController::class, 'store'])->name('store');
                });

                Route::group(['prefix' => 'jobs', 'as' => 'jobs.', 'permission' => 'jobs.export'], function (): void {
                    Route::get('/', [ExportJobController::class, 'index'])->name('index');
                    Route::post('/', [ExportJobController::class, 'store'])->name('store');
                });
            });

            Route::prefix('import')->name('import.')->group(function (): void {
                Route::group(['prefix' => 'companies', 'as' => 'companies.', 'permission' => 'companies.import'], function (): void {
                    Route::get('/', [ImportCompanyController::class, 'index'])->name('index');
                    Route::post('/', [ImportCompanyController::class, 'import'])->name('store');
                    Route::post('validate', [ImportCompanyController::class, 'validateData'])->name('validate');
                    Route::post('download-example', [ImportCompanyController::class, 'downloadExample'])->name('download-example');
                });

                Route::group(['prefix' => 'accounts', 'as' => 'accounts.', 'permission' => 'accounts.import'], function (): void {
                    Route::get('/', [ImportAccountController::class, 'index'])->name('index');
                    Route::post('/', [ImportAccountController::class, 'import'])->name('store');
                    Route::post('validate', [ImportAccountController::class, 'validateData'])->name('validate');
                    Route::post('download-example', [ImportAccountController::class, 'downloadExample'])->name('download-example');
                });

                Route::group(['prefix' => 'jobs', 'as' => 'jobs.', 'permission' => 'jobs.import'], function (): void {
                    Route::get('/', [ImportJobController::class, 'index'])->name('index');
                    Route::post('/', [ImportJobController::class, 'import'])->name('store');
                    Route::post('validate', [ImportJobController::class, 'validateData'])->name('validate');
                    Route::post('download-example', [ImportJobController::class, 'downloadExample'])->name('download-example');
                });
            });
        });
    });
});
