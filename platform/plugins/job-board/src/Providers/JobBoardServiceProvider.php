<?php

namespace Botble\JobBoard\Providers;

use Botble\Api\Facades\ApiHelper;
use Botble\Base\Facades\DashboardMenu;
use Botble\Base\Facades\EmailHandler;
use Botble\Base\Facades\Form;
use Botble\Base\Facades\MacroableModels;
use Botble\Base\Facades\PanelSectionManager;
use Botble\Base\Models\BaseModel;
use Botble\Base\PanelSections\PanelSectionItem;
use Botble\Base\Supports\DashboardMenu as DashboardMenuSupport;
use Botble\Base\Supports\Helper;
use Botble\Base\Supports\Language as BaseLanguage;
use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\Captcha\Facades\Captcha;
use Botble\DataSynchronize\Importer\Importer;
use Botble\DataSynchronize\PanelSections\ExportPanelSection;
use Botble\DataSynchronize\PanelSections\ImportPanelSection;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Forms\Fronts\Auth\ForgotPasswordForm;
use Botble\JobBoard\Forms\Fronts\Auth\LoginForm;
use Botble\JobBoard\Forms\Fronts\Auth\RegisterForm;
use Botble\JobBoard\Forms\Fronts\Auth\ResetPasswordForm;
use Botble\JobBoard\Http\Middleware\EnabledCreditsSystem;
use Botble\JobBoard\Http\Middleware\RedirectIfAccount;
use Botble\JobBoard\Http\Middleware\RedirectIfNotAccount;
use Botble\JobBoard\Http\Requests\Fronts\Auth\ForgotPasswordRequest;
use Botble\JobBoard\Http\Requests\Fronts\Auth\LoginRequest;
use Botble\JobBoard\Http\Requests\Fronts\Auth\RegisterRequest;
use Botble\JobBoard\Http\Requests\Fronts\Auth\ResetPasswordRequest;
use Botble\JobBoard\Importers\AccountImporter;
use Botble\JobBoard\Importers\CompanyImporter;
use Botble\JobBoard\Importers\JobImporter;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AccountActivityLog;
use Botble\JobBoard\Models\Analytics;
use Botble\JobBoard\Models\CareerLevel;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\Currency;
use Botble\JobBoard\Models\CustomField;
use Botble\JobBoard\Models\CustomFieldOption;
use Botble\JobBoard\Models\CustomFieldValue;
use Botble\JobBoard\Models\DegreeLevel;
use Botble\JobBoard\Models\DegreeType;
use Botble\JobBoard\Models\FunctionalArea;
use Botble\JobBoard\Models\Invoice;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobApplication;
use Botble\JobBoard\Models\JobExperience;
use Botble\JobBoard\Models\JobShift;
use Botble\JobBoard\Models\JobSkill;
use Botble\JobBoard\Models\JobType;
use Botble\JobBoard\Models\LanguageLevel;
use Botble\JobBoard\Models\Package;
use Botble\JobBoard\Models\Review;
use Botble\JobBoard\Models\Tag;
use Botble\JobBoard\Models\Transaction;
use Botble\JobBoard\PanelSections\SettingJobBoardPanelSection;
use Botble\JobBoard\Repositories\Eloquent\AccountActivityLogRepository;
use Botble\JobBoard\Repositories\Eloquent\AccountRepository;
use Botble\JobBoard\Repositories\Eloquent\AnalyticsRepository;
use Botble\JobBoard\Repositories\Eloquent\CareerLevelRepository;
use Botble\JobBoard\Repositories\Eloquent\CategoryRepository;
use Botble\JobBoard\Repositories\Eloquent\CompanyRepository;
use Botble\JobBoard\Repositories\Eloquent\CurrencyRepository;
use Botble\JobBoard\Repositories\Eloquent\CustomFieldRepository;
use Botble\JobBoard\Repositories\Eloquent\DegreeLevelRepository;
use Botble\JobBoard\Repositories\Eloquent\DegreeTypeRepository;
use Botble\JobBoard\Repositories\Eloquent\FunctionalAreaRepository;
use Botble\JobBoard\Repositories\Eloquent\InvoiceRepository;
use Botble\JobBoard\Repositories\Eloquent\JobApplicationRepository;
use Botble\JobBoard\Repositories\Eloquent\JobExperienceRepository;
use Botble\JobBoard\Repositories\Eloquent\JobRepository;
use Botble\JobBoard\Repositories\Eloquent\JobShiftRepository;
use Botble\JobBoard\Repositories\Eloquent\JobSkillRepository;
use Botble\JobBoard\Repositories\Eloquent\JobTypeRepository;
use Botble\JobBoard\Repositories\Eloquent\LanguageLevelRepository;
use Botble\JobBoard\Repositories\Eloquent\PackageRepository;
use Botble\JobBoard\Repositories\Eloquent\ReviewRepository;
use Botble\JobBoard\Repositories\Eloquent\TagRepository;
use Botble\JobBoard\Repositories\Eloquent\TransactionRepository;
use Botble\JobBoard\Repositories\Interfaces\AccountActivityLogInterface;
use Botble\JobBoard\Repositories\Interfaces\AccountInterface;
use Botble\JobBoard\Repositories\Interfaces\AnalyticsInterface;
use Botble\JobBoard\Repositories\Interfaces\CareerLevelInterface;
use Botble\JobBoard\Repositories\Interfaces\CategoryInterface;
use Botble\JobBoard\Repositories\Interfaces\CompanyInterface;
use Botble\JobBoard\Repositories\Interfaces\CurrencyInterface;
use Botble\JobBoard\Repositories\Interfaces\CustomFieldInterface;
use Botble\JobBoard\Repositories\Interfaces\DegreeLevelInterface;
use Botble\JobBoard\Repositories\Interfaces\DegreeTypeInterface;
use Botble\JobBoard\Repositories\Interfaces\FunctionalAreaInterface;
use Botble\JobBoard\Repositories\Interfaces\InvoiceInterface;
use Botble\JobBoard\Repositories\Interfaces\JobApplicationInterface;
use Botble\JobBoard\Repositories\Interfaces\JobExperienceInterface;
use Botble\JobBoard\Repositories\Interfaces\JobInterface;
use Botble\JobBoard\Repositories\Interfaces\JobShiftInterface;
use Botble\JobBoard\Repositories\Interfaces\JobSkillInterface;
use Botble\JobBoard\Repositories\Interfaces\JobTypeInterface;
use Botble\JobBoard\Repositories\Interfaces\LanguageLevelInterface;
use Botble\JobBoard\Repositories\Interfaces\PackageInterface;
use Botble\JobBoard\Repositories\Interfaces\ReviewInterface;
use Botble\JobBoard\Repositories\Interfaces\TagInterface;
use Botble\JobBoard\Repositories\Interfaces\TransactionInterface;
use Botble\LanguageAdvanced\Supports\LanguageAdvancedManager;
use Botble\Location\Facades\Location;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Slug\Facades\SlugHelper;
use Botble\SocialLogin\Facades\SocialService;
use Botble\Theme\Facades\SiteMapManager;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class JobBoardServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        $this->app->register(CommandServiceProvider::class);

        $this->app->singleton(\Botble\JobBoard\Supports\SubscriptionService::class);
        $this->app->singleton(\Botble\JobBoard\Supports\CvRevealService::class);

        $this->app->singleton(JobInterface::class, function () {
            return new JobRepository(new Job());
        });

        $this->app->bind(JobTypeInterface::class, function () {
            return new JobTypeRepository(new JobType());
        });

        $this->app->bind(JobSkillInterface::class, function () {
            return new JobSkillRepository(new JobSkill());
        });

        $this->app->bind(JobShiftInterface::class, function () {
            return new JobShiftRepository(new JobShift());
        });

        $this->app->bind(JobExperienceInterface::class, function () {
            return new JobExperienceRepository(new JobExperience());
        });

        $this->app->bind(LanguageLevelInterface::class, function () {
            return new LanguageLevelRepository(new LanguageLevel());
        });

        $this->app->bind(CareerLevelInterface::class, function () {
            return new CareerLevelRepository(new CareerLevel());
        });

        $this->app->bind(FunctionalAreaInterface::class, function () {
            return new FunctionalAreaRepository(new FunctionalArea());
        });

        $this->app->bind(CategoryInterface::class, function () {
            return new CategoryRepository(new Category());
        });

        $this->app->bind(DegreeTypeInterface::class, function () {
            return new DegreeTypeRepository(new DegreeType());
        });

        $this->app->bind(DegreeLevelInterface::class, function () {
            return new DegreeLevelRepository(new DegreeLevel());
        });

        $this->app->bind(CurrencyInterface::class, function () {
            return new CurrencyRepository(new Currency());
        });

        $this->app->singleton(JobApplicationInterface::class, function () {
            return new JobApplicationRepository(new JobApplication());
        });

        $this->app->singleton(AnalyticsInterface::class, function () {
            return new AnalyticsRepository(new Analytics());
        });

        $this->app->bind(TagInterface::class, function () {
            return new TagRepository(new Tag());
        });

        $this->app->bind(InvoiceInterface::class, function () {
            return new InvoiceRepository(new Invoice());
        });

        $this->app->bind(ReviewInterface::class, function () {
            return new ReviewRepository(new Review());
        });

        $this->app->bind(CustomFieldInterface::class, function () {
            return new CustomFieldRepository(new CustomField());
        });

        $this->app->bind(AccountInterface::class, function () {
            return new AccountRepository(new Account());
        });

        $this->app->bind(AccountActivityLogInterface::class, function () {
            return new AccountActivityLogRepository(new AccountActivityLog());
        });

        $this->app->bind(PackageInterface::class, function () {
            return new PackageRepository(new Package());
        });

        $this->app->bind(CompanyInterface::class, function () {
            return new CompanyRepository(new Company());
        });

        $this->app->singleton(TransactionInterface::class, function () {
            return new TransactionRepository(new Transaction());
        });

        config([
            'auth.guards.account' => [
                'driver' => 'session',
                'provider' => 'accounts',
            ],
            'auth.providers.accounts' => [
                'driver' => 'eloquent',
                'model' => Account::class,
            ],
            'auth.passwords.accounts' => [
                'provider' => 'accounts',
                'table' => 'jb_account_password_resets',
                'expire' => 60,
            ],
        ]);

        $loader = AliasLoader::getInstance();
        $loader->alias('JobBoardHelper', JobBoardHelper::class);

        Helper::autoload(__DIR__ . '/../../helpers');
    }

    public function boot(): void
    {
        $this
            ->setNamespace('plugins/job-board')
            ->loadAndPublishConfigurations(['permissions', 'email', 'general'])
            ->loadMigrations()
            ->loadAndPublishViews()
            ->loadAndPublishTranslations()
            ->loadRoutes(['web', 'api', 'account', 'public', 'review'])
            ->publishAssets()
            ->loadJsonTranslationsFrom($this->getPath() . '/resources/lang');

        RateLimiter::for('job-pages', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Botble\JobBoard\Console\Commands\CrawlRunCommand::class,
                \Botble\JobBoard\Console\Commands\CrawlRefreshExistingCommand::class,
                \Botble\JobBoard\Console\Commands\FixCrawledJobCategoriesCommand::class,
                \Botble\JobBoard\Console\Commands\SocialPublishJobCommand::class,
            ]);
        }

        PanelSectionManager::beforeRendering(function (): void {
            PanelSectionManager::default()
                ->register(SettingJobBoardPanelSection::class);
        });

        PanelSectionManager::setGroupId('data-synchronize')->beforeRendering(function (): void {
            PanelSectionManager::default()
                ->registerItem(
                    ExportPanelSection::class,
                    fn () => PanelSectionItem::make('companies')
                        ->setTitle(trans('plugins/job-board::company.name'))
                        ->withDescription(trans('plugins/job-board::export.companies.description'))
                        ->withPriority(120)
                        ->withPermission('companies.export')
                        ->withRoute('tools.data-synchronize.export.companies.index')
                )
                ->registerItem(
                    ImportPanelSection::class,
                    fn () => PanelSectionItem::make('companies')
                        ->setTitle(trans('plugins/job-board::company.name'))
                        ->withDescription(trans('plugins/job-board::import.company.description'))
                        ->withPriority(120)
                        ->withPermission('companies.import')
                        ->withRoute('tools.data-synchronize.import.companies.index')
                )
                ->registerItem(
                    ExportPanelSection::class,
                    fn () => PanelSectionItem::make('accounts')
                        ->setTitle(trans('plugins/job-board::account.name'))
                        ->withDescription(trans('plugins/job-board::account.export.description'))
                        ->withPriority(121)
                        ->withPermission('accounts.export')
                        ->withRoute('tools.data-synchronize.export.accounts.index')
                )
                ->registerItem(
                    ImportPanelSection::class,
                    fn () => PanelSectionItem::make('accounts')
                        ->setTitle(trans('plugins/job-board::account.name'))
                        ->withDescription(trans('plugins/job-board::account.import.description'))
                        ->withPriority(121)
                        ->withPermission('accounts.import')
                        ->withRoute('tools.data-synchronize.import.accounts.index')
                )
                ->registerItem(
                    ExportPanelSection::class,
                    fn () => PanelSectionItem::make('jobs')
                        ->setTitle(trans('plugins/job-board::job.name'))
                        ->withDescription(trans('plugins/job-board::export.jobs.description'))
                        ->withPriority(122)
                        ->withPermission('jobs.export')
                        ->withRoute('tools.data-synchronize.export.jobs.index')
                )
                ->registerItem(
                    ImportPanelSection::class,
                    fn () => PanelSectionItem::make('jobs')
                        ->setTitle(trans('plugins/job-board::job.name'))
                        ->withDescription(trans('plugins/job-board::import.job.description'))
                        ->withPriority(122)
                        ->withPermission('jobs.import')
                        ->withRoute('tools.data-synchronize.import.jobs.index')
                );
        });

        add_filter('data_synchronize_import_form_before', function (?string $html, Importer $importer): ?string {
            if ($importer instanceof CompanyImporter) {
                return $html . view('plugins/job-board::companies.import-extra-fields')->render();
            }

            if ($importer instanceof AccountImporter) {
                return $html . view('plugins/job-board::accounts.import-extra-fields')->render();
            }

            if ($importer instanceof JobImporter) {
                return $html . view('plugins/job-board::jobs.import-extra-fields')->render();
            }

            return $html;
        }, 999, 2);

        SlugHelper::registering(function (): void {
            SlugHelper::registerModule(Job::class, fn () => trans('plugins/job-board::job.jobs'));
            SlugHelper::registerModule(Category::class, fn () => trans('plugins/job-board::job-category.job_categories'));
            SlugHelper::registerModule(Company::class, fn () => trans('plugins/job-board::company.companies'));
            SlugHelper::registerModule(Tag::class, fn () => trans('plugins/job-board::tag.job_tags'));

            SlugHelper::setPrefix(Job::class, 'jobs');
            SlugHelper::setPrefix(Category::class, 'job-categories');
            SlugHelper::setPrefix(Company::class, 'companies');
            SlugHelper::setPrefix(Tag::class, 'job-tags');

            if (! setting('job_board_disabled_public_profile')) {
                SlugHelper::registerModule(Account::class, 'Candidates');
                SlugHelper::setPrefix(Account::class, 'candidates');
                SlugHelper::setColumnUsedForSlugGenerator(Account::class, 'first_name');
            }
        });

        DashboardMenu::beforeRetrieving(function (): void {
            DashboardMenu::make()
                ->registerItem([
                    'id' => 'cms-plugins-job-board-main',
                    'priority' => 0,
                    'parent_id' => null,
                    'name' => 'plugins/job-board::job-board.name',
                    'icon' => 'ti ti-briefcase',
                    'url' => route('jobs.index'),
                    'permissions' => ['jobs.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-jobs',
                    'priority' => 1,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'plugins/job-board::job.name',
                    'icon' => 'ti ti-briefcase',
                    'url' => route('jobs.index'),
                    'permissions' => ['jobs.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-reviews',
                    'priority' => 1,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'plugins/job-board::review.name',
                    'icon' => 'ti ti-message',
                    'url' => route('reviews.index'),
                    'permissions' => ['reviews.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-application',
                    'priority' => 2,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => trans('plugins/job-board::job-application.name'),
                    'icon' => 'ti ti-file-check',
                    'url' => route('job-applications.index'),
                    'permissions' => ['job-applications.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-accounts',
                    'priority' => 3,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'plugins/job-board::account.name',
                    'icon' => 'ti ti-users',
                    'url' => route('accounts.index'),
                    'permissions' => ['accounts.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-package',
                    'priority' => 4,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'plugins/job-board::package.name',
                    'icon' => 'ti ti-packages',
                    'url' => route('packages.index'),
                    'permissions' => ['packages.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-company',
                    'priority' => 5,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'plugins/job-board::company.name',
                    'icon' => 'ti ti-building',
                    'url' => route('companies.index'),
                    'permissions' => ['companies.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-automations',
                    'priority' => 6,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'Automations',
                    'icon' => 'ti ti-share',
                    'url' => route('job-board.automations.index'),
                    'permissions' => ['job-board.automations.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-automations-all',
                    'priority' => 1,
                    'parent_id' => 'cms-plugins-job-board-automations',
                    'name' => 'Config',
                    'url' => route('job-board.automations.index'),
                    'permissions' => ['job-board.automations.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-publer',
                    'priority' => 2,
                    'parent_id' => 'cms-plugins-job-board-automations',
                    'name' => 'Publer',
                    'url' => route('job-board.publer.index'),
                    'permissions' => ['job-board.automations.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-broadcast',
                    'priority' => 3,
                    'parent_id' => 'cms-plugins-job-board-automations',
                    'name' => 'Broadcast',
                    'url' => route('job-board.automations.broadcast'),
                    'permissions' => ['job-board.automations.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-agents',
                    'priority' => 7,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'Agents',
                    'icon' => 'ti ti-robot',
                    'url' => route('job-board.crawlers.index'),
                    'permissions' => ['job-board.crawlers.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-agent-runs',
                    'priority' => 7,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'Agent Runs',
                    'icon' => 'ti ti-history',
                    'url' => route('job-board.crawler-runs.index'),
                    'permissions' => ['job-board.crawler-runs.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-wakanda-verification',
                    'priority' => 7,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'Wakanda Verification',
                    'icon' => 'ti ti-award',
                    'url' => route('wakanda-verification.index'),
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-career-service-orders',
                    'priority' => 8,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'Career Services',
                    'icon' => 'ti ti-user-star',
                    'url' => route('career-service-orders.index'),
                    'permissions' => ['career-service-orders.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-job-alert-orders',
                    'priority' => 7,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'Job Alert Requests',
                    'icon' => 'ti ti-bell-dollar',
                    'url' => route('job-alert-orders.index'),
                    'permissions' => ['job-alert-orders.index'],
                ])
                ->registerItem([
                    'id'          => 'cms-plugins-job-board-vip-alert-orders',
                    'priority'    => 7,
                    'parent_id'   => 'cms-plugins-job-board-main',
                    'name'        => 'VIP Alert Orders',
                    'icon'        => 'ti ti-star-filled',
                    'url'         => route('vip-alert-orders.index'),
                    'permissions' => ['vip-alert-orders.index'],
                ])
                ->registerItem([
                    'id'          => 'cms-plugins-job-board-vip-alert-plans',
                    'priority'    => 7,
                    'parent_id'   => 'cms-plugins-job-board-main',
                    'name'        => 'VIP Alert Plans',
                    'icon'        => 'ti ti-settings-dollar',
                    'url'         => route('job-board.settings.vip-alert-plans'),
                    'permissions' => ['vip-alert-orders.index'],
                ])
                ->registerItem([
                    'id'          => 'cms-plugins-job-board-ai-images',
                    'priority'    => 7,
                    'parent_id'   => 'cms-plugins-job-board-main',
                    'name'        => 'AI Image Generation',
                    'icon'        => 'ti ti-photo-ai',
                    'url'         => route('job-board.settings.ai-images'),
                    'permissions' => ['job-board.automations.index'],
                ])
                ->registerItem([
                    'id'          => 'cms-plugins-job-board-auto-apply-orders',
                    'priority'    => 8,
                    'parent_id'   => 'cms-plugins-job-board-main',
                    'name'        => 'Auto Apply Orders',
                    'icon'        => 'ti ti-paper-airplane',
                    'url'         => route('auto-apply-orders.index'),
                    'permissions' => ['auto-apply-orders.index'],
                ])
                ->registerItem([
                    'id'          => 'cms-plugins-job-board-auto-apply-plans',
                    'priority'    => 8,
                    'parent_id'   => 'cms-plugins-job-board-main',
                    'name'        => 'Auto Apply Plans',
                    'icon'        => 'ti ti-settings-automation',
                    'url'         => route('job-board.settings.auto-apply-plans'),
                    'permissions' => ['auto-apply-orders.index'],
                ])
                ->registerItem([
                    'id'          => 'cms-plugins-job-board-auto-apply-logs',
                    'priority'    => 8,
                    'parent_id'   => 'cms-plugins-job-board-main',
                    'name'        => 'Auto Apply Logs',
                    'icon'        => 'ti ti-list-details',
                    'url'         => route('auto-apply-logs.index'),
                    'permissions' => ['auto-apply-orders.index'],
                ])
                ->registerItem([
                    'id'          => 'cms-plugins-job-board-candidate-alerts',
                    'priority'    => 7,
                    'parent_id'   => 'cms-plugins-job-board-main',
                    'name'        => 'VIP Job Alerts',
                    'icon'        => 'ti ti-device-mobile-message',
                    'url'         => route('job-board.candidate-alerts.index'),
                    'permissions' => ['job-board.candidate-alerts.index'],
                ])
                ->registerItem([
                    'id'          => 'cms-plugins-credit-orders',
                    'priority'    => 5,
                    'parent_id'   => 'cms-plugins-job-board-main',
                    'name'        => 'Credit Orders',
                    'icon'        => null,
                    'url'         => fn () => route('credit-orders.index'),
                    'permissions' => ['credit-orders.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-featured-orders',
                    'priority' => 7,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'Featured Job Orders',
                    'icon' => 'ti ti-star',
                    'url' => route('featured-orders.index'),
                    'permissions' => ['featured-orders.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-featured-packages',
                    'priority' => 4,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'Featured Packages',
                    'icon' => 'ti ti-star-half',
                    'url' => route('featured-packages.index'),
                    'permissions' => ['featured-packages.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-ad-placements',
                    'priority' => 3,
                    'parent_id' => 'cms-plugins-ads',
                    'name' => 'Ad Pricing',
                    'icon' => 'ti ti-currency-dollar',
                    'url' => route('ad-placements.index'),
                    'permissions' => ['ad-placements.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-ad-orders',
                    'priority' => 3,
                    'parent_id' => 'cms-plugins-ads',
                    'name' => 'Ad Requests',
                    'icon' => 'ti ti-receipt',
                    'url' => route('ad-orders.index'),
                    'permissions' => ['ad-orders.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-ad-pricing-tiers',
                    'priority' => 3,
                    'parent_id' => 'cms-plugins-ads',
                    'name' => 'Pricing Tiers',
                    'icon' => 'ti ti-world',
                    'url' => route('ad-pricing-tiers.index'),
                    'permissions' => ['ad-pricing-tiers.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-employer-subscriptions',
                    'priority' => 7,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'Employer Subscriptions',
                    'icon' => 'ti ti-credit-card',
                    'url' => route('employer-subscriptions.index'),
                    'permissions' => ['employer-subscriptions.index'],
                ])
                ->when(JobBoardHelper::isEnabledCreditsSystem(), static function (DashboardMenuSupport $dashboardMenu): void {
                    $dashboardMenu
                        ->registerItem([
                            'id' => 'cms-plugins-job-board-invoice',
                            'priority' => 8,
                            'parent_id' => 'cms-plugins-job-board-main',
                            'name' => 'plugins/job-board::invoice.name',
                            'icon' => 'ti ti-file-invoice',
                            'url' => route('invoice.index'),
                            'permissions' => ['invoice.index'],
                        ]);
                })
                ->registerItem([
                    'id' => 'cms-plugins-job-board-salary-analytics',
                    'priority' => 15,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'Salary Analytics',
                    'icon' => 'ti ti-chart-bar',
                    'url' => route('salary-analytics.index'),
                    'permissions' => ['salary-analytics.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-salary-reports',
                    'priority' => 16,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'Salary Reports',
                    'icon' => 'ti ti-file-type-pdf',
                    'url' => route('salary-reports.index'),
                    'permissions' => ['salary-reports.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-salary-api-keys',
                    'priority' => 17,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'Salary API Keys',
                    'icon' => 'ti ti-key',
                    'url' => route('salary-api-keys.index'),
                    'permissions' => ['salary-api-keys.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-reports',
                    'priority' => 999,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'plugins/job-board::job-board.reports.title',
                    'icon' => 'ti ti-chart-bar',
                    'url' => route('job-board.reports'),
                    'permissions' => ['job-board.reports'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-custom-fields',
                    'priority' => 8,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'plugins/job-board::custom-fields.name',
                    'icon' => 'ti ti-table-options',
                    'url' => route('job-board.custom-fields.index'),
                    'permissions' => ['job-board.custom-fields.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-coupons',
                    'priority' => 9,
                    'parent_id' => 'cms-plugins-job-board-main',
                    'name' => 'plugins/job-board::coupon.name',
                    'icon' => 'ti ti-discount-2',
                    'url' => route('coupons.index'),
                    'permissions' => ['job-board.coupons.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-job-attributes',
                    'priority' => 1,
                    'parent_id' => null,
                    'name' => 'plugins/job-board::job-board.job-attributes',
                    'icon' => 'ti ti-tags',
                    'url' => null,
                    'permissions' => ['job-attributes.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-job-type',
                    'priority' => 0,
                    'parent_id' => 'cms-plugins-job-board-job-attributes',
                    'name' => 'plugins/job-board::job-type.name',
                    'icon' => 'ti ti-clock',
                    'url' => route('job-types.index'),
                    'permissions' => ['job-types.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-job-skill',
                    'priority' => 1,
                    'parent_id' => 'cms-plugins-job-board-job-attributes',
                    'name' => 'plugins/job-board::job-skill.name',
                    'icon' => 'ti ti-tools',
                    'url' => route('job-skills.index'),
                    'permissions' => ['job-skills.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-job-shift',
                    'priority' => 2,
                    'parent_id' => 'cms-plugins-job-board-job-attributes',
                    'name' => 'plugins/job-board::job-shift.name',
                    'icon' => 'ti ti-calendar-time',
                    'url' => route('job-shifts.index'),
                    'permissions' => ['job-shifts.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-job-experience',
                    'priority' => 3,
                    'parent_id' => 'cms-plugins-job-board-job-attributes',
                    'name' => 'plugins/job-board::job-experience.name',
                    'icon' => 'ti ti-trophy',
                    'url' => route('job-experiences.index'),
                    'permissions' => ['job-experiences.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-language-level',
                    'priority' => 4,
                    'parent_id' => 'cms-plugins-job-board-job-attributes',
                    'name' => 'plugins/job-board::language-level.name',
                    'icon' => 'ti ti-language',
                    'url' => route('language-levels.index'),
                    'permissions' => ['language-levels.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-career-level',
                    'priority' => 5,
                    'parent_id' => 'cms-plugins-job-board-job-attributes',
                    'name' => 'plugins/job-board::career-level.name',
                    'icon' => 'ti ti-stairs-up',
                    'url' => route('career-levels.index'),
                    'permissions' => ['career-levels.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-functional-area',
                    'priority' => 6,
                    'parent_id' => 'cms-plugins-job-board-job-attributes',
                    'name' => 'plugins/job-board::functional-area.name',
                    'icon' => 'ti ti-layout-grid',
                    'url' => route('functional-areas.index'),
                    'permissions' => ['functional-areas.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-job-category',
                    'priority' => 7,
                    'parent_id' => 'cms-plugins-job-board-job-attributes',
                    'name' => 'plugins/job-board::job-category.name',
                    'icon' => 'ti ti-category',
                    'url' => route('job-categories.index'),
                    'permissions' => ['job-categories.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-job-tag',
                    'priority' => 8,
                    'parent_id' => 'cms-plugins-job-board-job-attributes',
                    'name' => 'plugins/job-board::tag.name',
                    'icon' => 'ti ti-tag',
                    'url' => route('job-board.tag.index'),
                    'permissions' => ['job-board.tag.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-degree-level',
                    'priority' => 9,
                    'parent_id' => 'cms-plugins-job-board-job-attributes',
                    'name' => 'plugins/job-board::degree-level.name',
                    'icon' => 'ti ti-school',
                    'url' => route('degree-levels.index'),
                    'permissions' => ['degree-levels.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-degree-type',
                    'priority' => 10,
                    'parent_id' => 'cms-plugins-job-board-job-attributes',
                    'name' => 'plugins/job-board::degree-type.name',
                    'icon' => 'ti ti-certificate',
                    'url' => route('degree-types.index'),
                    'permissions' => ['degree-types.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-job-board-documentation',
                    'priority' => 999,
                    'parent_id' => null,
                    'name' => 'Documentation',
                    'icon' => 'ti ti-book',
                    'url' => route('documentation.index'),
                    'permissions' => [],
                ]);
        });

        DashboardMenu::for('account')->beforeRetrieving(function (): void {
            /** @var Account|null $account */
            $account = auth('account')->user();

            $dashboardMenu = DashboardMenu::make();

            if ($account?->isEmployer()) {
                $dashboardMenu
                    ->registerItem([
                        'id' => 'cms-account-dashboard',
                        'priority' => 1,
                        'parent_id' => null,
                        'name' => 'plugins/job-board::dashboard.menu.dashboard',
                        'url' => fn () => route('public.account.dashboard'),
                        'icon' => 'ti ti-home',
                    ])
                    ->registerItem([
                        'id' => 'cms-account-jobs',
                        'priority' => 4,
                        'parent_id' => null,
                        'name' => 'plugins/job-board::dashboard.menu.jobs',
                        'url' => fn () => route('public.account.jobs.index'),
                        'icon' => 'ti ti-briefcase',
                        'badge' => fn () => Job::query()
                            ->where('author_id', auth('account')->id())
                            ->where('author_type', Account::class)
                            ->where('status', 'published')
                            ->count() ?: null,
                    ])
                    ->when(JobBoardHelper::employerManageCompanyInfo(), function (DashboardMenuSupport $dashboardMenu): void {
                        $dashboardMenu
                            ->registerItem([
                                'id' => 'cms-account-companies',
                                'priority' => 5,
                                'parent_id' => null,
                                'name' => 'plugins/job-board::dashboard.menu.companies',
                                'url' => fn () => route('public.account.companies.index'),
                                'icon' => 'ti ti-building',
                                'badge' => fn () => auth('account')->user()?->companies()->count() ?: null,
                            ]);
                    })
                    ->when(JobBoardHelper::isEnabledReview(), function (DashboardMenuSupport $dashboardMenu): void {
                        $dashboardMenu
                            ->registerItem([
                                'id' => 'cms-account-reviews',
                                'priority' => 6,
                                'parent_id' => null,
                                'name' => 'plugins/job-board::dashboard.menu.reviews',
                                'url' => fn () => route('public.account.reviews.index'),
                                'icon' => 'ti ti-star',
                                'badge' => fn () => auth('account')->user()?->reviews()->count() ?: null,
                            ]);
                    })
                    ->registerItem([
                        'id' => 'cms-account-applicants',
                        'priority' => 8,
                        'parent_id' => null,
                        'name' => 'plugins/job-board::dashboard.menu.applicants',
                        'url' => fn () => route('public.account.applicants.index'),
                        'icon' => 'ti ti-users-group',
                        'badge' => fn () => auth('account')->user()?->applicants()->count() ?: null,
                    ])
                    ->when(JobBoardHelper::isEnabledCreditsSystem(), static function (DashboardMenuSupport $dashboardMenu): void {
                        $dashboardMenu
                            ->registerItem([
                                'id' => 'cms-account-invoices',
                                'priority' => 9,
                                'parent_id' => null,
                                'name' => 'plugins/job-board::dashboard.menu.invoices',
                                'url' => fn () => route('public.account.invoices.index'),
                                'icon' => 'ti ti-file-invoice',
                                'badge' => static fn () => Invoice::query()
                                    ->whereHas('payment', static fn ($q) => $q->where('customer_id', auth('account')->id()))
                                    ->where('status', 'pending')
                                    ->count() ?: null,
                            ])
                            ->registerItem([
                                'id' => 'cms-account-credits',
                                'priority' => 8,
                                'parent_id' => null,
                                'name' => 'plugins/job-board::dashboard.credits',
                                'url' => fn () => route('public.account.credits'),
                                'icon' => 'ti ti-coins',
                            ]);
                    })
                    ->registerItem([
                        'id' => 'cms-account-featured-jobs',
                        'priority' => 75,
                        'parent_id' => null,
                        'name' => 'Feature a Job',
                        'url' => fn () => route('public.account.featured-jobs.index'),
                        'icon' => 'ti ti-star',
                    ])
                    ->registerItem([
                        'id' => 'cms-account-advertise',
                        'priority' => 76,
                        'parent_id' => null,
                        'name' => 'Advertise',
                        'url' => fn () => route('public.account.ads.index'),
                        'icon' => 'ti ti-ad',
                    ])
                    ->registerItem([
                        'id' => 'cms-account-subscription',
                        'priority' => 72,
                        'parent_id' => null,
                        'name' => 'Subscription',
                        'url' => fn () => route('public.account.subscription.index'),
                        'icon' => 'ti ti-crown',
                    ])
                    ->registerItem([
                        'id' => 'cms-account-candidates',
                        'priority' => 80,
                        'parent_id' => null,
                        'name' => 'Find Talent',
                        'url' => fn () => route('public.account.candidates.search'),
                        'icon' => 'ti ti-users-group',
                    ])
                    ->registerItem([
                        'id' => 'cms-account-talent-pool',
                        'priority' => 82,
                        'parent_id' => null,
                        'name' => 'Talent Pool',
                        'url' => fn () => route('public.account.talent-pool.index'),
                        'icon' => 'ti ti-award',
                    ]);
            } else {
                $dashboardMenu
                    ->registerItem([
                        'id' => 'cms-account-dashboard',
                        'priority' => 1,
                        'parent_id' => null,
                        'name' => 'plugins/job-board::dashboard.menu.dashboard',
                        'url' => fn () => route('public.account.dashboard'),
                        'icon' => 'ti ti-home',
                    ])
                    ->registerItem([
                        'id' => 'cms-account-overview',
                        'priority' => 2,
                        'parent_id' => null,
                        'name' => 'Overview',
                        'url' => fn () => route('public.account.overview'),
                        'icon' => 'ti ti-id',
                    ])
                    ->registerItem([
                        'id' => 'cms-account-applied-jobs',
                        'priority' => 4,
                        'parent_id' => null,
                        'name' => 'Applied Jobs',
                        'url' => fn () => route('public.account.jobs.applied-jobs'),
                        'icon' => 'ti ti-briefcase',
                    ])
                    ->registerItem([
                        'id' => 'cms-account-saved-jobs',
                        'priority' => 5,
                        'parent_id' => null,
                        'name' => 'Saved Jobs',
                        'url' => fn () => route('public.account.jobs.saved'),
                        'icon' => 'ti ti-bookmark',
                    ])
                    ->registerItem([
                        'id' => 'cms-account-experiences',
                        'priority' => 6,
                        'parent_id' => null,
                        'name' => 'Experiences',
                        'url' => fn () => route('public.account.experiences.index'),
                        'icon' => 'ti ti-briefcase-2',
                    ])
                    ->registerItem([
                        'id' => 'cms-account-educations',
                        'priority' => 7,
                        'parent_id' => null,
                        'name' => 'Educations',
                        'url' => fn () => route('public.account.educations.index'),
                        'icon' => 'ti ti-school',
                    ])
                    ->registerItem([
                        'id' => 'cms-account-career-services',
                        'priority' => 8,
                        'parent_id' => null,
                        'name' => 'Career Services',
                        'url' => fn () => route('public.account.career-services'),
                        'icon' => 'ti ti-writing-sign',
                    ])
                    ->registerItem([
                        'id' => 'cms-account-job-alerts',
                        'priority' => 10,
                        'parent_id' => null,
                        'name' => 'Job Alerts',
                        'url' => fn () => route('public.account.job-alerts.index'),
                        'icon' => 'ti ti-bell',
                    ])
                    ->registerItem([
                        'id' => 'cms-account-alert-packages',
                        'priority' => 11,
                        'parent_id' => null,
                        'name' => 'Alert Packages',
                        'url' => fn () => route('public.account.job-alert.packages.index'),
                        'icon' => 'ti ti-package',
                    ])
                    ->when(JobBoardHelper::isEnabledCreditsSystem(), static function (DashboardMenuSupport $dashboardMenu): void {
                        $dashboardMenu
                            ->registerItem([
                                'id' => 'cms-account-credits',
                                'priority' => 12,
                                'parent_id' => null,
                                'name' => 'plugins/job-board::dashboard.credits',
                                'url' => fn () => route('public.account.credits'),
                                'icon' => 'ti ti-coins',
                            ]);
                    });
            }

            $dashboardMenu
                ->registerItem([
                    'id' => 'cms-account-my-profile',
                    'priority' => 3,
                    'parent_id' => null,
                    'name' => 'plugins/job-board::messages.my_profile',
                    'url' => fn () => route('public.account.settings'),
                    'icon' => 'ti ti-user',
                ])
                ->registerItem([
                    'id' => 'cms-account-security',
                    'priority' => 10,
                    'parent_id' => null,
                    'name' => 'plugins/job-board::messages.security',
                    'url' => fn () => route('public.account.security'),
                    'icon' => 'ti ti-shield-lock',
                ]);
        });

        DashboardMenu::default();

        $this->app['events']->listen(RouteMatched::class, function (): void {
            $router = $this->app['router'];

            $router->aliasMiddleware('account', RedirectIfNotAccount::class);
            $router->aliasMiddleware('account.guest', RedirectIfAccount::class);
            $router->aliasMiddleware('enable-credits', EnabledCreditsSystem::class);
        });

        SiteMapManager::registerKey([
            'job-categories-((?:19|20|21|22)\d{2})-(0?[1-9]|1[012])',
            'job-tags-((?:19|20|21|22)\d{2})-(0?[1-9]|1[012])',
            'jobs-((?:19|20|21|22)\d{2})-(0?[1-9]|1[012])',
            'jobs-city-((?:19|20|21|22)\d{2})-(0?[1-9]|1[012])',
            'jobs-state-((?:19|20|21|22)\d{2})-(0?[1-9]|1[012])',
            'job-companies-((?:19|20|21|22)\d{2})-(0?[1-9]|1[012])',
        ]);

        if (class_exists('ApiHelper') && ApiHelper::enabled()) {
            ApiHelper::setConfig([
                'model' => Account::class,
                'guard' => 'account',
                'password_broker' => 'accounts',
                'verify_email' => setting('verify_account_email', 0),
            ]);
        }

        if (File::exists(storage_path('app/invoices/template.blade.php'))) {
            $this->loadViewsFrom(storage_path('app/invoices'), 'plugins/job-board/invoice');
        }

        if (defined('LANGUAGE_MODULE_SCREEN_NAME') && defined('LANGUAGE_ADVANCED_MODULE_SCREEN_NAME')) {
            $this->loadRoutes(['language-advanced']);

            LanguageAdvancedManager::registerModule(Job::class, [
                'name',
                'description',
                'content',
                'address',
            ]);

            LanguageAdvancedManager::registerModule(CareerLevel::class, [
                'name',
            ]);

            LanguageAdvancedManager::registerModule(Category::class, [
                'name',
                'description',
            ]);

            LanguageAdvancedManager::registerModule(DegreeLevel::class, [
                'name',
            ]);

            LanguageAdvancedManager::registerModule(DegreeType::class, [
                'name',
            ]);

            LanguageAdvancedManager::registerModule(FunctionalArea::class, [
                'name',
            ]);

            LanguageAdvancedManager::registerModule(JobExperience::class, [
                'name',
            ]);

            LanguageAdvancedManager::registerModule(JobShift::class, [
                'name',
            ]);

            LanguageAdvancedManager::registerModule(JobSkill::class, [
                'name',
            ]);

            LanguageAdvancedManager::registerModule(JobType::class, [
                'name',
            ]);

            LanguageAdvancedManager::registerModule(LanguageLevel::class, [
                'name',
            ]);

            LanguageAdvancedManager::registerModule(Package::class, [
                'name',
                'description',
                'features',
            ]);

            LanguageAdvancedManager::registerModule(Tag::class, [
                'name',
            ]);

            LanguageAdvancedManager::registerModule(CustomField::class, [
                'name',
                'type',
            ]);

            LanguageAdvancedManager::registerModule(CustomFieldOption::class, [
                'label',
                'value',
            ]);

            LanguageAdvancedManager::registerModule(CustomFieldValue::class, [
                'name',
                'value',
            ]);

            LanguageAdvancedManager::registerModule(Account::class, [
                'first_name',
                'last_name',
                'description',
            ]);

            LanguageAdvancedManager::registerModule(Company::class, [
                'name',
                'description',
                'content',
            ]);

            LanguageAdvancedManager::addTranslatableMetaBox('custom_fields_box');

            add_action(LANGUAGE_ADVANCED_ACTION_SAVED, function ($data, $request): void {
                switch (get_class($data)) {
                    case Job::class:
                        $options = $request->input('custom_fields', []) ?: [];

                        if (! $options) {
                            return;
                        }

                        foreach ($options as $value) {
                            $newRequest = new Request();

                            $newRequest->replace([
                                'language' => $request->input('language'),
                                'ref_lang' => $request->input('ref_lang'),
                            ]);

                            if (! $value['id']) {
                                continue;
                            }

                            $optionValue = CustomFieldValue::find($value['id']);

                            if ($optionValue) {
                                $newRequest->merge([
                                    'name' => $value['name'],
                                    'value' => $value['value'],
                                ]);

                                LanguageAdvancedManager::save($optionValue, $newRequest);
                            }
                        }

                        break;
                    case CustomField::class:

                        $customFieldOptions = $request->input('options', []) ?: [];

                        if (! $customFieldOptions) {
                            return;
                        }

                        $newRequest = new Request();

                        $newRequest->replace([
                            'language' => $request->input('language'),
                            'ref_lang' => $request->input('ref_lang'),
                        ]);

                        foreach ($customFieldOptions as $option) {
                            if (empty($option['id'])) {
                                continue;
                            }

                            $customFieldOption = CustomFieldOption::query()->find($option['id']);

                            if ($customFieldOption) {
                                $newRequest->merge([
                                    'label' => $option['label'],
                                    'value' => $option['value'],
                                ]);

                                LanguageAdvancedManager::save($customFieldOption, $newRequest);
                            }
                        }

                        break;
                }
            }, 1234, 2);
        }

        if (is_plugin_active('location')) {
            Location::registerModule(Job::class);
            Location::registerModule(Company::class);
            Location::registerModule(Account::class);
        } else {
            MacroableModels::addMacro(Job::class, 'getFullAddressAttribute', function () {
                /**
                 * @var BaseModel $this
                 */
                return $this->address;
            });

            MacroableModels::addMacro(Company::class, 'getFullAddressAttribute', function () {
                /**
                 * @var BaseModel $this
                 */
                return $this->address;
            });
        }

        $this->app->booted(function (): void {
            SeoHelper::registerModule([Job::class, Category::class, Tag::class, Company::class, Account::class]);

            EmailHandler::addTemplateSettings(JOB_BOARD_MODULE_SCREEN_NAME, config('plugins.job-board.email'));

            if (defined('SOCIAL_LOGIN_MODULE_SCREEN_NAME') && Route::has('public.account.login')) {
                SocialService::registerModule([
                    'guard' => 'account',
                    'model' => Account::class,
                    'login_url' => route('public.account.login'),
                    'redirect_url' => route('public.account.dashboard'),
                ]);
            }

            $this->app->register(EventServiceProvider::class);
            $this->app->register(HookServiceProvider::class);

            add_filter(IS_IN_ADMIN_FILTER, [$this, 'setInAdmin'], 128);
        });

        Form::component('customEditor', JobBoardHelper::viewPath('dashboard.forms.partials.custom-editor'), [
            'name',
            'value' => null,
            'attributes' => [],
        ]);

        if (is_plugin_active('captcha')) {
            Captcha::registerFormSupport(LoginForm::class, LoginRequest::class, trans('plugins/job-board::job-board.login_form'));
            Captcha::registerFormSupport(RegisterForm::class, RegisterRequest::class, trans('plugins/job-board::job-board.register_form'));
            Captcha::registerFormSupport(ForgotPasswordForm::class, ForgotPasswordRequest::class, trans('plugins/job-board::job-board.forgot_password_form'));
            Captcha::registerFormSupport(ResetPasswordForm::class, ResetPasswordRequest::class, trans('plugins/job-board::job-board.reset_password_form'));
        }
    }

    public function setInAdmin(bool $isInAdmin): bool
    {
        $segment = request()->segment(1);

        if ($segment && in_array($segment, BaseLanguage::getLocaleKeys()) && $segment !== App::getLocale()) {
            $segment = request()->segment(2);
        }

        if (in_array(Route::currentRouteName(), [
            'public.account.jobs.saved',
            'public.account.jobs.applied-jobs',
            'public.account.security',
            'public.account.overview',
            'public.account.experiences.index',
            'public.account.educations.index',
            'public.account.settings',
        ])) {
            return false;
        }

        return $segment === 'account' || $isInAdmin;
    }
}
