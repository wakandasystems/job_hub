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
use Botble\JobBoard\Console\Commands\SocialPublishJobCommand;
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
        });
    }
}
