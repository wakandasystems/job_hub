<?php

namespace Botble\JobBoard\Providers;

use Botble\JobBoard\Commands\CheckExpiredJobsSoonCommand;
use Botble\JobBoard\Commands\RenewJobsCommand;
use Botble\JobBoard\Commands\RunJobCrawlersCommand;
use Botble\JobBoard\Commands\UpdateCurrencyRatesCommand;
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
            RenewJobsCommand::class,
            CheckExpiredJobsSoonCommand::class,
            RunJobCrawlersCommand::class,
            UpdateCurrencyRatesCommand::class,
        ]);

        $this->app->afterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command(RenewJobsCommand::class)->dailyAt('23:30');
            $schedule->command(CheckExpiredJobsSoonCommand::class)->dailyAt('23:30');
            $schedule->command(RunJobCrawlersCommand::class)->everyFifteenMinutes();
            $schedule->command(UpdateCurrencyRatesCommand::class)->dailyAt('02:45')->withoutOverlapping();
        });
    }
}
