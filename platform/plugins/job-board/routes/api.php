<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'api',
    'prefix' => 'api/v1',
    'namespace' => 'Botble\JobBoard\Http\Controllers\API',
], function (): void {
    // Public Jobs API
    Route::group(['prefix' => 'jobs'], function (): void {
        Route::get('/', 'JobController@index');
        Route::get('/{id}', 'JobController@show')->wherePrimaryKey();
        Route::get('/{id}/related', 'JobController@related')->wherePrimaryKey();
        Route::get('/featured', 'JobController@featured');
        Route::get('/recent', 'JobController@recent');
        Route::get('/popular', 'JobController@popular');
    });

    // Protected Jobs API (requires authentication)
    Route::group(['prefix' => 'jobs', 'middleware' => ['auth:sanctum']], function (): void {
        Route::post('/{id}/apply', 'JobController@apply')->wherePrimaryKey();
    });

    // Public Companies API
    Route::group(['prefix' => 'companies'], function (): void {
        Route::get('/', 'CompanyController@index');
        Route::get('/{id}', 'CompanyController@show')->wherePrimaryKey();
        Route::get('/{id}/jobs', 'CompanyController@jobs')->wherePrimaryKey();
        Route::get('/featured', 'CompanyController@featured');
        Route::get('/search', 'CompanyController@search');
    });

    // Public Categories API
    Route::group(['prefix' => 'categories'], function (): void {
        Route::get('/', 'CategoryController@index');
        Route::get('/{id}', 'CategoryController@show')->wherePrimaryKey();
        Route::get('/{id}/jobs', 'CategoryController@jobs')->wherePrimaryKey();
        Route::get('/featured', 'CategoryController@featured');
    });

    // Public Job Types API
    Route::group(['prefix' => 'job-types'], function (): void {
        Route::get('/', 'JobTypeController@index');
        Route::get('/{id}', 'JobTypeController@show')->wherePrimaryKey();
    });

    // Public Job Skills API
    Route::group(['prefix' => 'job-skills'], function (): void {
        Route::get('/', 'JobSkillController@index');
        Route::get('/{id}', 'JobSkillController@show')->wherePrimaryKey();
    });

    // Public Job Experiences API
    Route::group(['prefix' => 'job-experiences'], function (): void {
        Route::get('/', 'JobExperienceController@index');
        Route::get('/{id}', 'JobExperienceController@show')->wherePrimaryKey();
    });

    // Public Career Levels API
    Route::group(['prefix' => 'career-levels'], function (): void {
        Route::get('/', 'CareerLevelController@index');
        Route::get('/{id}', 'CareerLevelController@show')->wherePrimaryKey();
    });

    // Public Job Shifts API
    Route::group(['prefix' => 'job-shifts'], function (): void {
        Route::get('/', 'JobShiftController@index');
        Route::get('/{id}', 'JobShiftController@show')->wherePrimaryKey();
    });

    // Public Functional Areas API
    Route::group(['prefix' => 'functional-areas'], function (): void {
        Route::get('/', 'FunctionalAreaController@index');
        Route::get('/{id}', 'FunctionalAreaController@show')->wherePrimaryKey();
    });

    // Public Tags API
    Route::group(['prefix' => 'tags'], function (): void {
        Route::get('/', 'TagController@index');
        Route::get('/{id}', 'TagController@show')->wherePrimaryKey();
        Route::get('/{id}/jobs', 'TagController@jobs')->wherePrimaryKey();
    });

    // Public Currencies API
    Route::group(['prefix' => 'currencies'], function (): void {
        Route::get('/', 'CurrencyController@index');
        Route::get('/{id}', 'CurrencyController@show')->wherePrimaryKey();
    });

    // Public Packages API
    Route::group(['prefix' => 'packages'], function (): void {
        Route::get('/', 'PackageController@index');
        Route::get('/{id}', 'PackageController@show')->wherePrimaryKey();
    });

    // Public Candidates/Accounts API (Job Seekers)
    Route::group(['prefix' => 'candidates'], function (): void {
        Route::get('/', 'CandidateController@index');
        Route::get('/{id}', 'CandidateController@show')->wherePrimaryKey();
        Route::get('/search', 'CandidateController@search');
    });

    // Public Reviews API (read-only)
    Route::group(['prefix' => 'reviews'], function (): void {
        Route::get('/', 'ReviewController@index');
        Route::get('/{id}', 'ReviewController@show')->wherePrimaryKey();
    });

    // Protected Reviews API (requires authentication)
    Route::group(['prefix' => 'reviews', 'middleware' => ['auth:sanctum']], function (): void {
        Route::post('/', 'ReviewController@store');
    });

    // Protected Analytics API (requires authentication)
    Route::group(['prefix' => 'analytics', 'middleware' => ['auth:sanctum']], function (): void {
        Route::get('/jobs/{id}', 'AnalyticsController@jobAnalytics')->wherePrimaryKey();
        Route::get('/companies/{id}', 'AnalyticsController@companyAnalytics')->wherePrimaryKey();
    });

    // Protected Account Management API (requires authentication)
    Route::group(['prefix' => 'account', 'middleware' => ['auth:sanctum']], function (): void {
        Route::get('/profile', 'AccountController@profile');
        Route::put('/profile', 'AccountController@updateProfile');
        Route::post('/avatar', 'AccountController@uploadAvatar');
        Route::get('/applications', 'AccountController@applications');
        Route::get('/applications/{id}', 'AccountController@showApplication')->wherePrimaryKey();
        Route::delete('/applications/{id}', 'AccountController@deleteApplication')->wherePrimaryKey();
        Route::get('/saved-jobs', 'AccountController@savedJobs');
        Route::post('/saved-jobs/{jobId}', 'AccountController@saveJob')->wherePrimaryKey('jobId');
        Route::delete('/saved-jobs/{jobId}', 'AccountController@unsaveJob')->wherePrimaryKey('jobId');
        Route::get('/companies', 'AccountController@companies');
        Route::get('/jobs', 'AccountController@jobs'); // For employers
        Route::post('/jobs', 'AccountController@createJob'); // For employers
        Route::put('/jobs/{id}', 'AccountController@updateJob')->wherePrimaryKey(); // For employers
        Route::delete('/jobs/{id}', 'AccountController@deleteJob')->wherePrimaryKey(); // For employers
        Route::get('/transactions', 'AccountController@transactions');
        Route::get('/invoices', 'AccountController@invoices');
        Route::get('/invoices/{id}', 'AccountController@showInvoice')->wherePrimaryKey();
    });

    // Protected Job Applications Management API (requires authentication)
    Route::group(['prefix' => 'job-applications', 'middleware' => ['auth:sanctum']], function (): void {
        Route::get('/', 'JobApplicationController@index'); // For employers to view applications
        Route::get('/{id}', 'JobApplicationController@show')->wherePrimaryKey();
        Route::put('/{id}', 'JobApplicationController@update')->wherePrimaryKey(); // Update application status
        Route::delete('/{id}', 'JobApplicationController@destroy')->wherePrimaryKey();
        Route::get('/{id}/download-cv', 'JobApplicationController@downloadCv')->wherePrimaryKey();
    });

    // Public Location-based endpoints (if location plugin is active)
    if (is_plugin_active('location')) {
        Route::group(['prefix' => 'locations'], function (): void {
            Route::get('/countries', 'LocationController@countries');
            Route::get('/states/{countryId}', 'LocationController@states')->wherePrimaryKey('countryId');
            Route::get('/cities/{stateId}', 'LocationController@cities')->wherePrimaryKey('stateId');
        });
    }
});
