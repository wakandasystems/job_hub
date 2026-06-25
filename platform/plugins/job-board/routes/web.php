<?php

use Botble\Base\Facades\AdminHelper;
use Botble\JobBoard\Http\Controllers\CandidateAlertController;
use Botble\JobBoard\Http\Controllers\AccountEducationController;
use Botble\JobBoard\Http\Controllers\CreditOrderController;
use Botble\JobBoard\Http\Controllers\AccountExperienceController;
use Botble\JobBoard\Http\Controllers\CareerServiceOrderController;
use Botble\JobBoard\Http\Controllers\EmployerSubscriptionController;
use Botble\JobBoard\Http\Controllers\AdOrderController;
use Botble\JobBoard\Http\Controllers\AdPlacementController;
use Botble\JobBoard\Http\Controllers\AdPricingTierController;
use Botble\JobBoard\Http\Controllers\FeaturedOrderController;
use Botble\JobBoard\Http\Controllers\FeaturedPackageController;
use Botble\JobBoard\Http\Controllers\JobAlertOrderController;
use Botble\JobBoard\Http\Controllers\VipAlertOrderController;
use Botble\JobBoard\Http\Controllers\JobAlertPackageController;
use Botble\JobBoard\Http\Controllers\Settings\AiImageSettingController;
use Botble\JobBoard\Http\Controllers\Settings\CareerServiceSettingController;
use Botble\JobBoard\Http\Controllers\Settings\VipAlertPlanSettingController;
use Botble\JobBoard\Http\Controllers\Settings\AutoApplyPlanSettingController;
use Botble\JobBoard\Http\Controllers\AutoApplyOrderController;
use Botble\JobBoard\Http\Controllers\AutoApplyLogController;
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
use Botble\JobBoard\Http\Controllers\SalesAgentController;
use Botble\JobBoard\Http\Controllers\SalesAgentCampaignController;
use Botble\JobBoard\Http\Controllers\SalesAgentCommissionController;
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

    Route::group(['prefix' => 'ad-placements', 'as' => 'ad-placements.', 'middleware' => 'auth'], function (): void {
        Route::resource('', AdPlacementController::class)
            ->parameters(['' => 'adPlacement'])
            ->except('show');
    });

    Route::group(['prefix' => 'ad-orders', 'as' => 'ad-orders.', 'middleware' => 'auth'], function (): void {
        Route::get('', [AdOrderController::class, 'index'])->name('index');
        Route::post('{adOrder}/approve', [AdOrderController::class, 'approve'])->name('approve');
        Route::post('{adOrder}/reject', [AdOrderController::class, 'reject'])->name('reject');
    });

    Route::group(['prefix' => 'ad-pricing-tiers', 'as' => 'ad-pricing-tiers.', 'middleware' => 'auth'], function (): void {
        Route::resource('', AdPricingTierController::class)
            ->parameters(['' => 'adPricingTier'])
            ->except('show');
    });

    Route::group(['prefix' => 'employer-subscriptions', 'as' => 'employer-subscriptions.', 'middleware' => 'auth'], function (): void {
        Route::get('', [EmployerSubscriptionController::class, 'index'])->name('index');
        Route::post('{employerSubscription}/activate', [EmployerSubscriptionController::class, 'activate'])->name('activate');
        Route::post('{employerSubscription}/cancel', [EmployerSubscriptionController::class, 'cancel'])->name('cancel');
    });

    Route::group(['prefix' => 'job-board/settings', 'as' => 'job-board.settings.', 'middleware' => 'auth'], function (): void {
        Route::get('career-services', [CareerServiceSettingController::class, 'edit'])->name('career-services');
        Route::put('career-services', [CareerServiceSettingController::class, 'update'])->name('career-services.update');
        Route::get('vip-alert-plans', [VipAlertPlanSettingController::class, 'edit'])->name('vip-alert-plans');
        Route::put('vip-alert-plans', [VipAlertPlanSettingController::class, 'update'])->name('vip-alert-plans.update');
        Route::get('ai-images', [AiImageSettingController::class, 'edit'])->name('ai-images');
        Route::put('ai-images', [AiImageSettingController::class, 'update'])->name('ai-images.update');
        Route::post('ai-images/retry-all', [AiImageSettingController::class, 'retryAll'])->name('ai-images.retry-all');
        Route::get('ai-images/retry-progress', [AiImageSettingController::class, 'retryProgress'])->name('ai-images.retry-progress');
        Route::post('ai-images/{log}/retry', [AiImageSettingController::class, 'retry'])->name('ai-images.retry')->wherePrimaryKey('log');
        Route::get('auto-apply-plans', [AutoApplyPlanSettingController::class, 'edit'])->name('auto-apply-plans');
        Route::put('auto-apply-plans', [AutoApplyPlanSettingController::class, 'update'])->name('auto-apply-plans.update');
    });

    Route::group(['prefix' => 'job-board/candidate-alerts', 'as' => 'job-board.candidate-alerts.', 'middleware' => 'auth'], function (): void {
        Route::get('', [CandidateAlertController::class, 'index'])->name('index');
        Route::post('', [CandidateAlertController::class, 'store'])->name('store');
        Route::put('quick-add-presets', [CandidateAlertController::class, 'updateKeywordPresets'])->name('quick-add-presets.update');
        Route::get('{candidateAlert}/edit', [CandidateAlertController::class, 'edit'])->name('edit')->wherePrimaryKey('candidateAlert');
        Route::get('{candidateAlert}/edit-modal', [CandidateAlertController::class, 'editModal'])->name('edit-modal')->wherePrimaryKey('candidateAlert');
        Route::put('{candidateAlert}', [CandidateAlertController::class, 'update'])->name('update')->wherePrimaryKey('candidateAlert');
        Route::delete('{candidateAlert}', [CandidateAlertController::class, 'destroy'])->name('destroy')->wherePrimaryKey('candidateAlert');
        Route::post('{candidateAlert}/toggle', [CandidateAlertController::class, 'toggle'])->name('toggle')->wherePrimaryKey('candidateAlert');
        Route::get('{candidateAlert}/logs', [CandidateAlertController::class, 'logs'])->name('logs')->wherePrimaryKey('candidateAlert');
        Route::get('{candidateAlert}/preview', [CandidateAlertController::class, 'preview'])->name('preview')->wherePrimaryKey('candidateAlert');
        Route::post('{candidateAlert}/send-account-invite', [CandidateAlertController::class, 'sendAccountInvite'])->name('send-account-invite')->wherePrimaryKey('candidateAlert');
        Route::post('{candidateAlert}/analyze-existing-cv', [CandidateAlertController::class, 'analyzeExistingCv'])->name('analyze-existing-cv')->wherePrimaryKey('candidateAlert');
        Route::post('{candidateAlert}/send-now', [CandidateAlertController::class, 'sendNow'])->name('send-now')->wherePrimaryKey('candidateAlert');
        Route::post('{candidateAlert}/send-welcome', [CandidateAlertController::class, 'sendWelcome'])->name('send-welcome')->wherePrimaryKey('candidateAlert');
        Route::get('{candidateAlert}/cv-builder', [CandidateAlertController::class, 'cvBuilderSessions'])->name('cv-builder.sessions')->wherePrimaryKey('candidateAlert');
        Route::post('{candidateAlert}/cv-builder/start', [CandidateAlertController::class, 'startCvBuilder'])->name('cv-builder.start')->wherePrimaryKey('candidateAlert');
        Route::post('{candidateAlert}/cv-builder/{session}/send-question', [CandidateAlertController::class, 'sendCvBuilderQuestion'])->name('cv-builder.send-question')->wherePrimaryKey('candidateAlert')->whereNumber('session');
        Route::post('{candidateAlert}/cv-builder/{session}/generate', [CandidateAlertController::class, 'generateCvFromChat'])->name('cv-builder.generate')->wherePrimaryKey('candidateAlert')->whereNumber('session');
        Route::get('{candidateAlert}/cv-builder/{session}/download/{format}', [CandidateAlertController::class, 'downloadBuiltCv'])->name('cv-builder.download')->wherePrimaryKey('candidateAlert')->whereNumber('session')->whereIn('format', ['docx', 'pdf']);
        Route::post('analyze-cv', [CandidateAlertController::class, 'analyzeCv'])->name('analyze-cv');
        Route::post('analyze-account-cv', [CandidateAlertController::class, 'analyzeAccountCv'])->name('analyze-account-cv');
        Route::post('preview-filters', [CandidateAlertController::class, 'previewFilters'])->name('preview-filters');
        Route::post('send-discount-newsletter', [CandidateAlertController::class, 'sendDiscountNewsletter'])->name('send-discount-newsletter');
        Route::get('check-phone', [CandidateAlertController::class, 'checkPhone'])->name('check-phone');
        Route::get('search-accounts', [CandidateAlertController::class, 'searchAccounts'])->name('search-accounts');
        Route::get('location/states', [CandidateAlertController::class, 'locationStates'])->name('location.states');
        Route::get('location/cities', [CandidateAlertController::class, 'locationCities'])->name('location.cities');
    });

    Route::group(['prefix' => 'job-alert-orders', 'as' => 'job-alert-orders.', 'middleware' => 'auth'], function (): void {
        Route::get('', [JobAlertOrderController::class, 'index'])->name('index');
        Route::post('{jobAlertOrder}/approve', [JobAlertOrderController::class, 'approve'])->name('approve');
        Route::post('{jobAlertOrder}/reject', [JobAlertOrderController::class, 'reject'])->name('reject');
    });

    Route::group(['prefix' => 'vip-alert-orders', 'as' => 'vip-alert-orders.', 'middleware' => 'auth'], function (): void {
        Route::get('', [VipAlertOrderController::class, 'index'])->name('index');
        Route::post('{vipAlertOrder}/approve', [VipAlertOrderController::class, 'approve'])->name('approve');
        Route::post('{vipAlertOrder}/reject', [VipAlertOrderController::class, 'reject'])->name('reject');
    });

    Route::group(['prefix' => 'auto-apply-orders', 'as' => 'auto-apply-orders.', 'middleware' => 'auth'], function (): void {
        Route::get('', [AutoApplyOrderController::class, 'index'])->name('index');
        Route::put('{autoApplyOrder}', [AutoApplyOrderController::class, 'update'])->name('update');
        Route::post('{autoApplyOrder}/approve', [AutoApplyOrderController::class, 'approve'])->name('approve');
        Route::post('{autoApplyOrder}/reject', [AutoApplyOrderController::class, 'reject'])->name('reject');
        Route::post('{autoApplyOrder}/resend-invite', [AutoApplyOrderController::class, 'resendInvite'])->name('resend-invite');
        Route::post('{autoApplyOrder}/disable', [AutoApplyOrderController::class, 'disable'])->name('disable');
        Route::get('{autoApplyOrder}/active-jobs', [AutoApplyOrderController::class, 'activeJobs'])->name('active-jobs');
        Route::post('{autoApplyOrder}/send-all-active-jobs', [AutoApplyOrderController::class, 'sendAllActiveJobs'])->name('send-all-active-jobs');
        Route::post('send-job', [AutoApplyOrderController::class, 'sendJob'])->name('send-job');
        Route::delete('{autoApplyOrder}', [AutoApplyOrderController::class, 'destroy'])->name('destroy');
        Route::post('preview', [AutoApplyOrderController::class, 'preview'])->name('preview');
        Route::post('preview-setup-jobs', [AutoApplyOrderController::class, 'previewSetupJobs'])->name('preview-setup-jobs');
        Route::post('analyze-cv', [CandidateAlertController::class, 'analyzeCv'])->name('analyze-cv');
        Route::post('analyze-account-cv', [CandidateAlertController::class, 'analyzeAccountCv'])->name('analyze-account-cv');
        Route::post('setup-for-candidate', [AutoApplyOrderController::class, 'setupForCandidate'])->name('setup-for-candidate');
        Route::get('search-candidates', [AutoApplyOrderController::class, 'searchCandidates'])->name('search-candidates');
        Route::get('search-countries', [AutoApplyOrderController::class, 'searchCountries'])->name('search-countries');
        Route::get('search-categories', [AutoApplyOrderController::class, 'searchCategories'])->name('search-categories');
    });

    Route::group(['prefix' => 'auto-apply-logs', 'as' => 'auto-apply-logs.', 'middleware' => 'auth'], function (): void {
        Route::get('', [AutoApplyLogController::class, 'index'])->name('index');
        Route::delete('{autoApplyLog}', [AutoApplyLogController::class, 'destroy'])->name('destroy');
    });

    Route::group(['prefix' => 'sales-agents', 'as' => 'sales-agents.', 'middleware' => 'auth'], function (): void {
        Route::get('', [SalesAgentController::class, 'index'])->name('index');
        Route::get('create', [SalesAgentController::class, 'create'])->name('create');
        Route::post('', [SalesAgentController::class, 'store'])->name('store');
        Route::get('search-candidates', [SalesAgentController::class, 'searchCandidates'])->name('search-candidates');
        Route::post('{salesAgent}/send-welcome', [SalesAgentController::class, 'sendWelcome'])->name('send-welcome')->whereNumber('salesAgent');
        Route::post('{salesAgent}/assign-order', [SalesAgentController::class, 'assignOrder'])->name('assign-order')->whereNumber('salesAgent');
        Route::post('{salesAgent}/marketing-images/preview', [SalesAgentController::class, 'previewMarketingImage'])->name('marketing-images.preview')->whereNumber('salesAgent');
        Route::post('{salesAgent}/marketing-images', [SalesAgentController::class, 'generateMarketingImage'])->name('marketing-images.generate')->whereNumber('salesAgent');
        Route::get('{salesAgent}/marketing-images/{salesAgentMarketingImage}/status', [SalesAgentController::class, 'marketingImageStatus'])->name('marketing-images.status')->whereNumber('salesAgent')->whereNumber('salesAgentMarketingImage');
        Route::post('{salesAgent}/campaigns/send', [SalesAgentController::class, 'sendCampaign'])->name('campaigns.send')->whereNumber('salesAgent');
        Route::post('{salesAgent}/marketing-images/{salesAgentMarketingImage}/send', [SalesAgentController::class, 'sendMarketingImage'])->name('marketing-images.send')->whereNumber('salesAgent')->whereNumber('salesAgentMarketingImage');
        Route::get('{salesAgent}/marketing-images/{salesAgentMarketingImage}/download', [SalesAgentController::class, 'downloadMarketingImage'])->name('marketing-images.download')->whereNumber('salesAgent')->whereNumber('salesAgentMarketingImage');
        Route::delete('{salesAgent}/marketing-images/{salesAgentMarketingImage}', [SalesAgentController::class, 'destroyMarketingImage'])->name('marketing-images.destroy')->whereNumber('salesAgent')->whereNumber('salesAgentMarketingImage');
        Route::delete('{salesAgent}/marketing-images/bulk-destroy', [SalesAgentController::class, 'bulkDestroyMarketingImages'])->name('marketing-images.bulk-destroy')->whereNumber('salesAgent');
        Route::get('{salesAgent}', [SalesAgentController::class, 'show'])->name('show')->whereNumber('salesAgent');
        Route::get('{salesAgent}/edit', [SalesAgentController::class, 'edit'])->name('edit')->whereNumber('salesAgent');
        Route::put('{salesAgent}', [SalesAgentController::class, 'update'])->name('update')->whereNumber('salesAgent');
        Route::delete('{salesAgent}', [SalesAgentController::class, 'destroy'])->name('destroy')->whereNumber('salesAgent');
    });

    Route::group(['prefix' => 'sales-agent-campaigns', 'as' => 'sales-agent-campaigns.', 'middleware' => 'auth'], function (): void {
        Route::get('', [SalesAgentCampaignController::class, 'index'])->name('index');
        Route::get('generated-images', [SalesAgentCampaignController::class, 'generatedImages'])->name('generated-images');
        Route::delete('generated-images/bulk-destroy', [SalesAgentCampaignController::class, 'bulkDestroyGeneratedImages'])->name('generated-images.bulk-destroy');
        Route::delete('generated-images/{salesAgentMarketingImage}', [SalesAgentCampaignController::class, 'destroyGeneratedImage'])->name('generated-images.destroy')->whereNumber('salesAgentMarketingImage');
        Route::put('settings', [SalesAgentCampaignController::class, 'updateSettings'])->name('settings.update');
        Route::get('create', [SalesAgentCampaignController::class, 'create'])->name('create');
        Route::post('', [SalesAgentCampaignController::class, 'store'])->name('store');
        Route::post('{salesAgentCampaign}/generate-sample', [SalesAgentCampaignController::class, 'generateSample'])->name('generate-sample')->whereNumber('salesAgentCampaign');
        Route::get('{salesAgentCampaign}/sample-status/{salesAgentMarketingImage}', [SalesAgentCampaignController::class, 'sampleStatus'])->name('sample-status')->whereNumber('salesAgentCampaign')->whereNumber('salesAgentMarketingImage');
        Route::get('{salesAgentCampaign}/edit', [SalesAgentCampaignController::class, 'edit'])->name('edit')->whereNumber('salesAgentCampaign');
        Route::put('{salesAgentCampaign}', [SalesAgentCampaignController::class, 'update'])->name('update')->whereNumber('salesAgentCampaign');
        Route::delete('{salesAgentCampaign}', [SalesAgentCampaignController::class, 'destroy'])->name('destroy')->whereNumber('salesAgentCampaign');
    });

    Route::group(['prefix' => 'sales-agent-commissions', 'as' => 'sales-agent-commissions.', 'middleware' => 'auth'], function (): void {
        Route::get('', [SalesAgentCommissionController::class, 'index'])->name('index');
        Route::post('bulk-mark-paid', [SalesAgentCommissionController::class, 'bulkMarkPaid'])->name('bulk-mark-paid');
        Route::post('{salesAgentCommission}/mark-paid', [SalesAgentCommissionController::class, 'markPaid'])->name('mark-paid')->whereNumber('salesAgentCommission');
        Route::post('{salesAgentCommission}/mark-unpaid', [SalesAgentCommissionController::class, 'markUnpaid'])->name('mark-unpaid')->whereNumber('salesAgentCommission');
    });

    Route::prefix('credit-orders')->name('credit-orders.')->middleware('auth')->group(function (): void {
        Route::get('', [CreditOrderController::class, 'index'])->name('index');
        Route::post('{order}/approve', [CreditOrderController::class, 'approve'])->name('approve');
        Route::post('{order}/reject', [CreditOrderController::class, 'reject'])->name('reject');
    });

    Route::group(['prefix' => 'job-board/auto-cv-bot', 'as' => 'job-board.auto-cv-bot.', 'middleware' => 'auth'], function (): void {
        Route::get('', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'index'])->name('index');
        Route::get('search-agents', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'searchAgents'])->name('search-agents');
        Route::post('start', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'start'])->name('start');
        Route::post('send-sample', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'sendSampleCv'])->name('send-sample');
        Route::post('persona-image', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'uploadPersonaImage'])->name('persona-image');
        Route::post('confirmation-image', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'uploadConfirmationImage'])->name('confirmation-image');
        Route::post('ai-model', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'updateAiModel'])->name('ai-model');
        Route::get('sessions/poll', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'pollSessions'])->name('sessions.poll');
        Route::get('{autoCvSession}', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'show'])->name('show')->whereNumber('autoCvSession');
        Route::get('{autoCvSession}/poll', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'poll'])->name('poll')->whereNumber('autoCvSession');
        Route::post('{autoCvSession}/pause', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'pause'])->name('pause')->whereNumber('autoCvSession');
        Route::post('{autoCvSession}/resume', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'resume'])->name('resume')->whereNumber('autoCvSession');
        Route::post('{autoCvSession}/update-cv-field', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'updateCvField'])->name('update-cv-field')->whereNumber('autoCvSession');
        Route::post('{autoCvSession}/clear-cv-section', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'clearCvSection'])->name('clear-cv-section')->whereNumber('autoCvSession');
        Route::post('{autoCvSession}/toggle-references-available-on-request', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'toggleReferencesAvailableOnRequest'])->name('toggle-references-available-on-request')->whereNumber('autoCvSession');
        Route::post('{autoCvSession}/resend-question', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'resendQuestion'])->name('resend-question')->whereNumber('autoCvSession');
        Route::post('{autoCvSession}/request-section-information', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'requestSectionInformation'])->name('request-section-information')->whereNumber('autoCvSession');
        Route::post('{autoCvSession}/request-cv-photo', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'requestCvPhoto'])->name('request-cv-photo')->whereNumber('autoCvSession');
        Route::post('{autoCvSession}/request-cv-upload', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'requestCvUpload'])->name('request-cv-upload')->whereNumber('autoCvSession');
        Route::post('{autoCvSession}/upload-cv', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'uploadCv'])->name('upload-cv')->whereNumber('autoCvSession');
        Route::post('{autoCvSession}/retry-generation', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'retryGeneration'])->name('retry-generation')->whereNumber('autoCvSession');
        Route::post('{autoCvSession}/generate-documents', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'generateDocuments'])->name('generate-documents')->whereNumber('autoCvSession');
        Route::post('{autoCvSession}/ask-candidate-to-resend', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'askCandidateToResend'])->name('ask-candidate-to-resend')->whereNumber('autoCvSession');
        Route::post('{autoCvSession}/continue-interview', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'continueInterview'])->name('continue-interview')->whereNumber('autoCvSession');
        Route::post('{autoCvSession}/request-final-confirmation', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'requestFinalConfirmation'])->name('request-final-confirmation')->whereNumber('autoCvSession');
        Route::post('{autoCvSession}/end-conversation', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'endConversation'])->name('end-conversation')->whereNumber('autoCvSession');
        Route::post('{autoCvSession}/send-documents', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'sendDocuments'])->name('send-documents')->whereNumber('autoCvSession');
        Route::get('{autoCvSession}/download/{format}/{design?}', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'download'])->name('download')->whereNumber('autoCvSession')->whereIn('format', ['docx', 'pdf'])->whereIn('design', ['premium', 'academic', 'creative', 'ats']);
        Route::get('{autoCvSession}/preview/{design?}', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'preview'])->name('preview')->whereNumber('autoCvSession')->whereIn('design', ['premium', 'academic', 'creative', 'ats']);
        Route::delete('{autoCvSession}', [\Botble\JobBoard\Http\Controllers\AutoCvBotController::class, 'destroy'])->name('destroy')->whereNumber('autoCvSession');
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

        Route::group(['prefix' => 'blog-posts', 'as' => 'job-board.blog-posts.'], function (): void {
            Route::get('{post}/image-prompt', [
                'as'         => 'image-prompt',
                'uses'       => 'BlogPostImagePromptController@show',
                'permission' => 'posts.edit',
            ])->whereNumber('post');
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

            Route::post('actions/whapi-token', [
                'as'         => 'whapi-token',
                'uses'       => 'SocialAutomationController@saveWhapiToken',
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

            Route::post('{automation}/send-jobs', [
                'as'         => 'send-jobs',
                'uses'       => 'SocialAutomationController@sendJobs',
                'permission' => 'job-board.automations.index',
            ])->wherePrimaryKey();

            Route::post('{automation}/duplicate', [
                'as'         => 'duplicate',
                'uses'       => 'SocialAutomationController@duplicate',
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

            Route::post('actions/whapi-fetch-channels', [
                'as'         => 'whapi-fetch-channels',
                'uses'       => 'SocialAutomationController@fetchWhapiChannels',
                'permission' => 'job-board.automations.index',
            ]);

            Route::post('actions/whapi-send-job/{job}', [
                'as'         => 'whapi-send-job',
                'uses'       => 'SocialAutomationController@whapiSendJob',
                'permission' => 'job-board.automations.index',
            ])->wherePrimaryKey();

            Route::post('actions/publer-fetch-accounts', [
                'as'         => 'publer-fetch-accounts',
                'uses'       => 'SocialAutomationController@fetchPublerAccounts',
                'permission' => 'job-board.automations.index',
            ]);

            Route::get('actions/search-jobs', [
                'as'         => 'search-jobs',
                'uses'       => 'SocialAutomationController@searchJobs',
                'permission' => 'job-board.automations.index',
            ]);

            Route::post('actions/publer-send-job/{job}', [
                'as'         => 'publer-send-job',
                'uses'       => 'SocialAutomationController@publerSendJob',
                'permission' => 'job-board.automations.index',
            ])->wherePrimaryKey();

            Route::post('{automation}/publer-send-jobs', [
                'as'         => 'publer-send-jobs',
                'uses'       => 'SocialAutomationController@publerSendPeriodJobs',
                'permission' => 'job-board.automations.index',
            ])->wherePrimaryKey();

            Route::post('{automation}/publer-test-job', [
                'as'         => 'publer-test-job',
                'uses'       => 'SocialAutomationController@publerTestJob',
                'permission' => 'job-board.automations.index',
            ])->wherePrimaryKey();

            Route::get('broadcast', [
                'as'         => 'broadcast',
                'uses'       => 'SocialBroadcastController@index',
                'permission' => 'job-board.automations.index',
            ]);

            Route::get('broadcast/employer-contacts', [
                'as'         => 'broadcast-employer-contacts',
                'uses'       => 'SocialBroadcastController@employerContacts',
                'permission' => 'job-board.automations.index',
            ]);

            Route::post('broadcast/upload-image', [
                'as'         => 'broadcast-upload-image',
                'uses'       => 'SocialBroadcastController@uploadImage',
                'permission' => 'job-board.automations.index',
            ]);

            Route::post('broadcast/send', [
                'as'         => 'broadcast-send',
                'uses'       => 'SocialBroadcastController@send',
                'permission' => 'job-board.automations.index',
            ]);

            Route::post('broadcast/{broadcast}/cancel', [
                'as'         => 'broadcast-cancel',
                'uses'       => 'SocialBroadcastController@cancel',
                'permission' => 'job-board.automations.index',
            ])->wherePrimaryKey();

            Route::post('broadcast/{broadcast}/retry', [
                'as'         => 'broadcast-retry',
                'uses'       => 'SocialBroadcastController@retry',
                'permission' => 'job-board.automations.index',
            ])->wherePrimaryKey();

            Route::delete('broadcast/{broadcast}', [
                'as'         => 'broadcast-destroy',
                'uses'       => 'SocialBroadcastController@destroy',
                'permission' => 'job-board.automations.index',
            ])->wherePrimaryKey();
        });

        Route::group(['prefix' => 'publer', 'as' => 'job-board.publer.'], function (): void {
            Route::get('/', [
                'as'         => 'index',
                'uses'       => 'PublerController@index',
                'permission' => 'job-board.automations.index',
            ]);

            Route::post('fetch-accounts', [
                'as'         => 'fetch-accounts',
                'uses'       => 'PublerController@fetchAccounts',
                'permission' => 'job-board.automations.index',
            ]);

            Route::post('upsert', [
                'as'         => 'upsert',
                'uses'       => 'PublerController@upsert',
                'permission' => 'job-board.automations.index',
            ]);

            Route::post('{mapping}/toggle', [
                'as'         => 'toggle',
                'uses'       => 'PublerController@toggle',
                'permission' => 'job-board.automations.index',
            ])->wherePrimaryKey();

            Route::post('{mapping}/test', [
                'as'         => 'test',
                'uses'       => 'PublerController@testPost',
                'permission' => 'job-board.automations.index',
            ])->wherePrimaryKey();

            Route::delete('{mapping}', [
                'as'         => 'destroy',
                'uses'       => 'PublerController@destroy',
                'permission' => 'job-board.automations.index',
            ])->wherePrimaryKey();

            Route::post('{mapping}/image-settings', [
                'as'         => 'image-settings',
                'uses'       => 'PublerController@saveImageSettings',
                'permission' => 'job-board.automations.index',
            ])->wherePrimaryKey();

            Route::get('{mapping}/preview-image', [
                'as'         => 'preview-image',
                'uses'       => 'PublerController@previewImage',
                'permission' => 'job-board.automations.index',
            ])->wherePrimaryKey();

            Route::group(['prefix' => 'category-templates', 'as' => 'category-templates.'], function (): void {
                Route::get('/', [
                    'as'         => 'index',
                    'uses'       => 'PublerCategoryTemplateController@index',
                    'permission' => 'job-board.automations.index',
                ]);

                Route::post('save', [
                    'as'         => 'save',
                    'uses'       => 'PublerCategoryTemplateController@save',
                    'permission' => 'job-board.automations.index',
                ]);

                Route::post('{template}/save', [
                    'as'         => 'update',
                    'uses'       => 'PublerCategoryTemplateController@save',
                    'permission' => 'job-board.automations.index',
                ])->wherePrimaryKey();

                Route::post('{template}/toggle', [
                    'as'         => 'toggle',
                    'uses'       => 'PublerCategoryTemplateController@toggle',
                    'permission' => 'job-board.automations.index',
                ])->wherePrimaryKey();

                Route::delete('{template}', [
                    'as'         => 'destroy',
                    'uses'       => 'PublerCategoryTemplateController@destroy',
                    'permission' => 'job-board.automations.index',
                ])->wherePrimaryKey();

                Route::get('{template}/preview-image', [
                    'as'         => 'preview-image',
                    'uses'       => 'PublerCategoryTemplateController@previewImage',
                    'permission' => 'job-board.automations.index',
                ])->wherePrimaryKey();
            });
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

            Route::group(['prefix' => 'merge', 'as' => 'merge.', 'permission' => 'companies.destroy'], function (): void {
                Route::get('/', [
                    'as' => 'picker',
                    'uses' => 'CompanyMergeController@picker',
                ]);

                Route::get('search', [
                    'as' => 'search',
                    'uses' => 'CompanyMergeController@search',
                ]);

                Route::get('compare', [
                    'as' => 'compare',
                    'uses' => 'CompanyMergeController@compare',
                ]);

                Route::post('/', [
                    'as' => 'store',
                    'uses' => 'CompanyMergeController@merge',
                ]);

                Route::post('{companyMergeLog}/undo', [
                    'as' => 'undo',
                    'uses' => 'CompanyMergeController@undo',
                ])->wherePrimaryKey();
            });
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

            Route::post('mark-all-reviewed', [
                'as' => 'mark-all-reviewed',
                'uses' => 'JobApplicationController@markAllReviewed',
                'permission' => 'job-applications.edit',
            ]);

            Route::post('{jobApplication}/mark-reviewed', [
                'as' => 'mark-reviewed',
                'uses' => 'JobApplicationController@markReviewed',
                'permission' => 'job-applications.edit',
            ])->wherePrimaryKey('jobApplication');

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
