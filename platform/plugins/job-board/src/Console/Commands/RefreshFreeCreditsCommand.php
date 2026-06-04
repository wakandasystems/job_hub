<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\Transaction;
use Illuminate\Console\Command;

class RefreshFreeCreditsCommand extends Command
{
    protected $signature   = 'job-board:refresh-free-credits';
    protected $description = 'Reset employer free credits to 25 each month (non-accumulating).';

    public const FREE_CREDITS_AMOUNT = 25;

    public function handle(): void
    {
        $employers = Account::query()
            ->where('type', AccountTypeEnum::EMPLOYER)
            ->where(function ($query): void {
                $query->whereNull('free_credits_refreshed_at')
                    ->orWhere('free_credits_refreshed_at', '<', now()->subMonth());
            })
            ->get();

        foreach ($employers as $employer) {
            $employer->update([
                'free_credits'              => self::FREE_CREDITS_AMOUNT,
                'free_credits_refreshed_at' => now(),
            ]);

            Transaction::query()->create([
                'account_id'  => $employer->id,
                'user_id'     => 0,
                'credits'     => self::FREE_CREDITS_AMOUNT,
                'description' => 'Monthly free credit allocation — ' . self::FREE_CREDITS_AMOUNT . ' credits refreshed (unused credits expired)',
            ]);

            $this->line("Refreshed free credits for #{$employer->id} {$employer->email}");
        }

        $this->info("Refreshed {$employers->count()} employer account(s).");
    }
}
