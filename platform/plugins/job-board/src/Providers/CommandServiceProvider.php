<?php

namespace Botble\JobBoard\Providers;

use Botble\JobBoard\Commands\CheckExpiredJobsSoonCommand;
use Botble\JobBoard\Commands\RegisterTelegramWebhookCommand;
use Botble\JobBoard\Commands\RenewJobsCommand;
use Botble\JobBoard\Commands\RunJobCrawlersCommand;
use Botble\JobBoard\Commands\UpdateCurrencyRatesCommand;
use Botble\JobBoard\Console\Commands\ExpireSubscriptionsCommand;
use Botble\JobBoard\Console\Commands\SendMonthlyHiringSnapshotCommand;
use Botble\JobBoard\Console\Commands\SendProfileRefreshReminderCommand;
use Botble\JobBoard\Console\Commands\SendPushNotificationsCommand;
use Botble\JobBoard\Console\Commands\SendSubscriptionRenewalReminderCommand;
use Botble\JobBoard\Console\Commands\BackfillJobSearchZmLogosCommand;
use Botble\JobBoard\Console\Commands\FixCrawledApplyEmailsCommand;
use Botble\JobBoard\Console\Commands\SplitMultiPositionJobCommand;
use Botble\JobBoard\Console\Commands\RefreshFreeCreditsCommand;
use Botble\JobBoard\Console\Commands\SendCandidateAlertsCommand;
use Botble\JobBoard\Console\Commands\CheckCandidateAlertExpiryCommand;
use Botble\JobBoard\Console\Commands\CheckFailedJobsCommand;
use Botble\JobBoard\Console\Commands\CheckStaleQueueWorkerCommand;
use Botble\JobBoard\Console\Commands\ArchiveOldCrawledJobsCommand;
use Botble\JobBoard\Console\Commands\GenerateSocialImagesCommand;
use Botble\JobBoard\Console\Commands\SendAutoApplyDigestCommand;
use Botble\JobBoard\Console\Commands\SocialPublishJobCommand;
use Botble\JobBoard\Console\Commands\CheckAutoCvBotStallCommand;
use Botble\JobBoard\Console\Commands\ProcessDueSocialBroadcastsCommand;
use Botble\JobBoard\Console\Commands\SendCandidateFilterTipsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            RegisterTelegramWebhookCommand::class,
            SendPushNotificationsCommand::class,
            RenewJobsCommand::class,
            CheckExpiredJobsSoonCommand::class,
            RunJobCrawlersCommand::class,
            UpdateCurrencyRatesCommand::class,
            ExpireSubscriptionsCommand::class,
            SendSubscriptionRenewalReminderCommand::class,
            SendMonthlyHiringSnapshotCommand::class,
            SendProfileRefreshReminderCommand::class,
            SocialPublishJobCommand::class,
            BackfillJobSearchZmLogosCommand::class,
            FixCrawledApplyEmailsCommand::class,
            SplitMultiPositionJobCommand::class,
            RefreshFreeCreditsCommand::class,
            SendCandidateAlertsCommand::class,
            CheckCandidateAlertExpiryCommand::class,
            CheckFailedJobsCommand::class,
            CheckStaleQueueWorkerCommand::class,
            ArchiveOldCrawledJobsCommand::class,
            GenerateSocialImagesCommand::class,
            SendAutoApplyDigestCommand::class,
            CheckAutoCvBotStallCommand::class,
            ProcessDueSocialBroadcastsCommand::class,
            SendCandidateFilterTipsCommand::class,
        ]);

        $this->app->afterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command(RenewJobsCommand::class)->dailyAt('23:30');
            $schedule->command(CheckExpiredJobsSoonCommand::class)->dailyAt('23:30');
            $schedule->command(RunJobCrawlersCommand::class)->everyFifteenMinutes()->withoutOverlapping();
            $schedule->command(UpdateCurrencyRatesCommand::class)->dailyAt('02:45')->withoutOverlapping();
            $schedule->command(ExpireSubscriptionsCommand::class)->dailyAt('00:05');
            $schedule->command(SendSubscriptionRenewalReminderCommand::class)->dailyAt('09:00');
            $schedule->command(SendMonthlyHiringSnapshotCommand::class)->monthlyOn(1, '08:00');
            $schedule->command(SendProfileRefreshReminderCommand::class)->weeklyOn(1, '10:00');
            $schedule->command(RefreshFreeCreditsCommand::class)->monthlyOn(1, '00:30');
            $schedule->command(CheckCandidateAlertExpiryCommand::class)->dailyAt('07:00')->withoutOverlapping();
            $schedule->command(SendCandidateAlertsCommand::class, ['--hours' => 25])
                ->dailyAt('09:00')
                ->withoutOverlapping();
            $schedule->command(CheckFailedJobsCommand::class)
                ->hourly()
                ->withoutOverlapping();
            $schedule->command(CheckStaleQueueWorkerCommand::class)
                ->everyFiveMinutes()
                ->withoutOverlapping();
            $schedule->command(ArchiveOldCrawledJobsCommand::class)
                ->dailyAt('01:30')
                ->withoutOverlapping();
            $schedule->command(SendAutoApplyDigestCommand::class)->weeklyOn(1, '09:30');
            $schedule->command('horizon:snapshot')->everyFiveMinutes();
            $schedule->command(CheckAutoCvBotStallCommand::class)->everyFifteenMinutes()->withoutOverlapping();
            $schedule->command(ProcessDueSocialBroadcastsCommand::class)->everyFiveMinutes()->withoutOverlapping();
            $schedule->command(SendCandidateFilterTipsCommand::class)->everyThirtyMinutes()->withoutOverlapping();
        });
    }
}
