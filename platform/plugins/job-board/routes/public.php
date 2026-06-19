<?php

use Botble\JobBoard\Http\Middleware\BlockScrapers;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\Tag;
use Botble\Location\Models\City;
use Botble\Location\Models\State;
use Botble\Slug\Facades\SlugHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Botble\JobBoard\Http\Controllers\Fronts', 'middleware' => ['web', 'core']], function (): void {
    Route::get('salary-checker', 'PublicSalaryController@index')->name('salary-checker');
    Route::get('ajax/salary-checker', 'PublicSalaryController@results')->name('salary-checker.results');

    Route::group(['prefix' => 'salary-reports', 'as' => 'salary-reports.public.'], function (): void {
        Route::get('', 'PublicSalaryReportController@index')->name('index');
        Route::get('access/{token}', 'PublicSalaryReportController@download')->name('download');
        Route::get('{slug}', 'PublicSalaryReportController@show')->name('show');
    });

    Route::post('jobs/apply/{id?}', [
        'as' => 'public.job.apply',
        'uses' => 'PublicController@postApplyJob',
    ]);

    Route::get('currency/switch/{code?}', [
        'as' => 'public.change-currency',
        'uses' => 'PublicController@changeCurrency',
    ]);

    Theme::registerRoutes(function (): void {
        Route::get('ajax/jobs', [
            'as' => 'public.ajax.jobs',
            'uses' => 'PublicController@getJobs',
        ]);

        Route::get('ajax/job-filters', [
            'as' => 'public.ajax.job-filters',
            'uses' => 'PublicController@getJobFilters',
        ]);

        Route::get('ajax/candidates', [
            'as' => 'public.ajax.candidates',
            'uses' => 'PublicController@getCandidates',
        ]);

        Route::get('ajax/companies', [
            'as' => 'public.ajax.companies',
            'uses' => 'PublicController@getCompanies',
        ]);

        Route::get(SlugHelper::getPrefix(Job::class, 'jobs') . '/{slug}', [
            'as' => 'public.job',
            'uses' => 'PublicController@getJob',
        ])->middleware([BlockScrapers::class, 'throttle:job-pages']);

        Route::get(SlugHelper::getPrefix(Category::class, 'job-categories') . '/{slug}', [
            'as' => 'public.job-category',
            'uses' => 'PublicController@getJobCategory',
        ]);

        Route::get(SlugHelper::getPrefix(Tag::class, 'job-tags') . '/{slug}', [
            'as' => 'public.job-tag',
            'uses' => 'PublicController@getJobTag',
        ]);

        Route::get(SlugHelper::getPrefix(Company::class, 'companies') . '/{slug}', [
            'as' => 'public.company',
            'uses' => 'PublicController@getCompany',
        ]);

        Route::get(SlugHelper::getPrefix(Account::class, 'candidates') . '/{slug}', [
            'as' => 'public.candidate',
            'uses' => 'PublicController@getCandidate',
        ]);

        Route::get(
            sprintf('%s/%s/{slug?}', SlugHelper::getPrefix(Job::class, 'jobs'), SlugHelper::getPrefix(City::class, 'city')),
            'JobByLocationController@city'
        )->name('public.jobs-by-city');

        Route::get(
            sprintf('%s/country/{slug}', SlugHelper::getPrefix(Job::class, 'jobs')),
            'JobByLocationController@country'
        )->name('public.jobs-by-country');

        Route::get(
            sprintf('%s/country/{country}/title/{slug}', SlugHelper::getPrefix(Job::class, 'jobs')),
            'JobByLocationController@titleInCountry'
        )->name('public.jobs-by-country-title');

        Route::get(
            sprintf('%s/%s/{slug?}', SlugHelper::getPrefix(Job::class, 'jobs'), SlugHelper::getPrefix(State::class, 'state')),
            'JobByLocationController@state'
        )->name('public.jobs-by-state');

        Route::get(
            sprintf('%s/title/{slug}', SlugHelper::getPrefix(Job::class, 'jobs')),
            'JobByLocationController@title'
        )->name('public.jobs-by-title');

        // Stores account type in session then redirects to social OAuth provider
        Route::get('social-register/{provider}', function (string $provider, \Illuminate\Http\Request $request) {
            $type = in_array($request->input('type'), ['employer', 'job-seeker']) ? $request->input('type') : 'job-seeker';

            // Verify reCAPTCHA if enabled
            if (setting('captcha_site_key') && setting('captcha_secret_key')) {
                $token = $request->input('g-recaptcha-response');
                if (! $token) {
                    return redirect()->route('public.account.register')
                        ->with('auth_error_message', __('Please complete the CAPTCHA.'));
                }
                $response = \Illuminate\Support\Facades\Http::asForm()->post(
                    'https://www.google.com/recaptcha/api/siteverify',
                    ['secret' => setting('captcha_secret_key'), 'response' => $token, 'remoteip' => $request->ip()]
                );
                if (! ($response->json('success') ?? false)) {
                    return redirect()->route('public.account.register')
                        ->with('auth_error_message', __('CAPTCHA verification failed. Please try again.'));
                }
            }

            session(['social_login_account_type' => $type]);
            return redirect()->route('auth.social', ['provider' => $provider]);
        })->name('public.social-register');

        Route::group(['prefix' => 'career-services', 'as' => 'public.career-service.'], function (): void {
            Route::get('/', 'CareerServiceController@getListing')->name('listing');
            Route::get('book/{service}', 'CareerServiceController@bookRedirect')->name('book');
            Route::get('cv-score', 'CareerServiceController@getCvScore')->name('cv-score');
            Route::post('cv-score', 'CareerServiceController@postCvScore')->name('cv-score.submit');
            Route::post('cv-score/profile', 'CareerServiceController@scoreProfileCv')->middleware('account')->name('cv-score.profile');
            Route::get('{service}/checkout', 'CareerServiceController@getCheckout')->name('checkout');
            Route::get('{order}/callback', 'CareerServiceController@getCallback')->name('callback');
            Route::get('{order}/thanks', 'CareerServiceController@getThanks')->name('thanks');
            Route::post('{order}/upload-cv', 'CareerServiceController@postUploadCandidateCv')->name('upload-cv');
            Route::get('{order}/download-reviewed-cv', 'CareerServiceController@downloadReviewedCv')->middleware('account')->name('download-reviewed-cv');
        });
    });

    Route::group(['prefix' => 'payments'], function (): void {
        Route::post('checkout', 'CheckoutController@postCheckout')->name('payments.checkout');
    });

    Route::group(['prefix' => 'vip-alerts', 'as' => 'public.vip-alerts.'], function (): void {
        Route::get('', 'VipAlertCheckoutController@plans')->name('plans');
        Route::get('checkout/{plan}', 'VipAlertCheckoutController@checkout')->name('checkout');
        Route::post('checkout/{plan}', 'VipAlertCheckoutController@prepareCheckout')->name('prepare-checkout');
        Route::get('pay/{token}', 'VipAlertCheckoutController@pay')->name('pay');
        Route::get('callback/{token}', 'VipAlertCheckoutController@callback')->name('callback');
        Route::get('pending/{token}', 'VipAlertCheckoutController@pending')->name('pending');
    });

    Route::group(['prefix' => 'auto-apply', 'as' => 'public.auto-apply.'], function (): void {
        Route::get('', 'AutoApplyCheckoutController@plans')->name('plans');
        Route::get('checkout/{plan}', 'AutoApplyCheckoutController@checkout')->name('checkout');
        Route::post('checkout/{plan}', 'AutoApplyCheckoutController@prepareCheckout')->name('prepare-checkout')->middleware('account');
        Route::get('pay/{order}', 'AutoApplyCheckoutController@pay')->name('pay')->middleware('account');
        Route::get('callback/{order}', 'AutoApplyCheckoutController@callback')->name('callback')->middleware('account');
        Route::get('thanks/{order}', 'AutoApplyCheckoutController@thanks')->name('thanks')->middleware('account');
    });
});

Route::group(['namespace' => 'Botble\JobBoard\Http\Controllers\Fronts', 'middleware' => ['web', 'core'], 'prefix' => 'push'], function (): void {
    Route::get('vapid-key', 'PushSubscriptionController@vapidKey')->name('push.vapid-key');
    Route::post('subscribe', 'PushSubscriptionController@store')->name('push.subscribe');
    Route::post('unsubscribe', 'PushSubscriptionController@destroy')->name('push.unsubscribe');
});

// Honeypot — bots that ignore robots.txt and follow hidden links get their IP blocked for 24 h.
Route::get('jobs-archive', function (\Illuminate\Http\Request $request) {
    Cache::put('blocked_scraper_' . $request->ip(), true, now()->addHours(24));
    return response('', 410);
})->name('public.honeypot');

// Telegram webhook — no web/CSRF middleware; authenticated via X-Telegram-Bot-Api-Secret-Token header.
Route::post('telegram/webhook', [\Botble\JobBoard\Http\Controllers\TelegramWebhookController::class, 'handle'])
    ->name('public.telegram-webhook');

// Whapi CV-bot webhook — no web/CSRF middleware; authenticated via secret embedded in the URL path
// (Whapi's webhook config has no custom-header option, unlike Telegram's secret header above).
Route::post('whapi/cv-bot/webhook/{secret}', [\Botble\JobBoard\Http\Controllers\AutoCvBotWebhookController::class, 'handle'])
    ->name('public.auto-cv-bot-webhook');

Route::group(['namespace' => 'Botble\JobBoard\Http\Controllers', 'middleware' => ['web', 'core']], function (): void {
    Route::get('download-cv/{account}', [
        'as' => 'public.candidate.download-cv',
        'uses' => 'AccountDownloadCvController@__invoke',
    ]);

    Route::get('telegram/social-message/prompt', [
        'as' => 'public.telegram-social-prompt',
        'uses' => 'TelegramSocialMessageController@show',
    ])->middleware('signed');

    Route::get('telegram/social-message/remove', [
        'as' => 'public.telegram-social-delete',
        'uses' => 'TelegramSocialMessageController@destroy',
    ])->middleware('signed');

    Route::post('telegram/social-message/upload', [
        'as' => 'public.telegram-social-upload',
        'uses' => 'TelegramSocialMessageController@upload',
    ])->middleware('signed');

    Route::post('telegram/social-message/generate', [
        'as' => 'public.telegram-social-generate',
        'uses' => 'TelegramSocialMessageController@generate',
    ])->middleware('signed');

    Route::post('telegram/social-message/send-to-employer', [
        'as' => 'public.telegram-social-send-to-employer',
        'uses' => 'TelegramSocialMessageController@sendToEmployer',
    ])->middleware('signed');

    Route::get('telegram/crawler-error/copy', [
        'as' => 'public.telegram-crawler-error-copy',
        'uses' => 'TelegramCrawlerErrorController@show',
    ])->middleware('signed');
});
