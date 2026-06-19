<?php

namespace Botble\JobBoard\Console\Commands;

use Botble\JobBoard\Models\AutoCvSession;
use Botble\JobBoard\Services\AutoCvBotService;
use Illuminate\Console\Command;

class CheckAutoCvBotStallCommand extends Command
{
    protected $signature = 'job-board:check-auto-cv-bot-stall';
    protected $description = 'Remind Auto CV Bot candidates who have not replied, then mark sessions stalled after repeated reminders';

    public function handle(AutoCvBotService $service): int
    {
        $minutes = max(1, (int) setting('auto_cv_reminder_minutes', 15));
        $maxReminders = max(1, (int) setting('auto_cv_max_reminders', 3));

        $sessions = AutoCvSession::query()
            ->where('status', 'collecting')
            ->whereNotNull('last_question_sent_at')
            ->get();

        $reminded = 0;
        $stalled = 0;

        foreach ($sessions as $session) {
            $lastContactAt = $session->last_candidate_reminder_sent_at ?: $session->last_question_sent_at;

            if (! $lastContactAt || $lastContactAt->gt(now()->subMinutes($minutes))) {
                continue;
            }

            if ((int) $session->candidate_reminder_count >= $maxReminders) {
                $this->line("  Stalled -> {$session->candidate_name} ({$session->whatsapp_number})");

                $session->forceFill(['status' => 'stalled'])->save();
                $service->notifyAdmin($session, 'stalled');
                $stalled++;

                continue;
            }

            if ($service->sendCandidateReminder($session)) {
                $this->line("  Reminder sent -> {$session->candidate_name} ({$session->whatsapp_number})");
                $reminded++;
            }
        }

        $this->info("Done. Reminders sent: {$reminded}. Stalled sessions: {$stalled}.");

        return self::SUCCESS;
    }
}
