<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Models\SocialBroadcast;
use Illuminate\Support\Carbon;

class BroadcastRecurrenceService
{
    /**
     * Compute the next run time for a recurring broadcast — called once to schedule
     * the very first occurrence, and again after every send to arm the next one.
     *
     * Returns the attributes to persist (next_run_at, plus day-bookkeeping for the
     * random_per_day mode).
     */
    public function nextRun(SocialBroadcast $broadcast, ?Carbon $after = null): array
    {
        $after = $after ?: now();

        return match ($broadcast->recurrence_type) {
            'fixed_daily'    => $this->nextFixedDaily($broadcast, $after),
            'daily_around'   => $this->nextDailyAround($broadcast, $after),
            'random_per_day' => $this->nextRandomPerDay($broadcast, $after),
            default          => ['next_run_at' => null],
        };
    }

    private function nextFixedDaily(SocialBroadcast $broadcast, Carbon $after): array
    {
        [$hour, $minute] = $this->timeParts($broadcast->recurrence_time);
        $next = $after->copy()->setTime($hour, $minute, 0);

        if ($next->lessThanOrEqualTo($after)) {
            $next->addDay();
        }

        return ['next_run_at' => $next];
    }

    /**
     * Same daily time, nudged by a fresh random jitter each day so it doesn't fire
     * at the exact same second every day — reads as a person posting "around" their
     * usual time rather than a clock.
     */
    private function nextDailyAround(SocialBroadcast $broadcast, Carbon $after): array
    {
        $jitter = max(1, (int) ($broadcast->recurrence_jitter_minutes ?: 45));
        [$hour, $minute] = $this->timeParts($broadcast->recurrence_time);

        $next = $after->copy()->setTime($hour, $minute, 0)->addMinutes(random_int(-$jitter, $jitter));

        if ($next->lessThanOrEqualTo($after)) {
            $next = $after->copy()->addDay()->setTime($hour, $minute, 0)->addMinutes(random_int(-$jitter, $jitter));
        }

        return ['next_run_at' => $next];
    }

    /**
     * Spreads N sends across an "active hours" window each day (default 08:00–20:00)
     * instead of pure random-across-24h, so nothing ever fires at 3am looking like a
     * bot. Rather than pre-splitting the full window into N fixed clock-time segments
     * (which left almost no room for "today" once the day was partway through — e.g.
     * a 08:00–14:00 first segment picked at 13:45 only had 15 minutes left to pick
     * from, so unrelated broadcasts created minutes apart all got squeezed into the
     * same few minutes), this divides whatever window time is still *ahead of now*
     * by however many occurrences are still due today, and picks a random point in
     * the first of those slices. That keeps later-in-the-day occurrences properly
     * spread across the remaining hours instead of clustering near "now".
     */
    private function nextRandomPerDay(SocialBroadcast $broadcast, Carbon $after): array
    {
        $perDay    = max(1, (int) ($broadcast->recurrence_times_per_day ?: 2));
        $startHour = (int) ($broadcast->recurrence_window_start ?? 8);
        $endHour   = (int) ($broadcast->recurrence_window_end ?? 20);
        if ($endHour <= $startHour) {
            $endHour = $startHour + 1;
        }

        $today     = $after->copy()->startOfDay();
        $sameDay   = $broadcast->today_date && $broadcast->today_date->isSameDay($today);
        $doneToday = $sameDay ? (int) $broadcast->today_occurrences : 0;
        $remaining = $perDay - $doneToday;

        $day          = $today;
        $windowStart  = $day->copy()->addMinutes($startHour * 60);
        $windowEnd    = $day->copy()->addMinutes($endHour * 60);
        $earliest     = $windowStart->greaterThan($after) ? $windowStart : $after->copy()->addMinute();

        // No occurrences left for today, or no time left in today's window — roll to tomorrow.
        if ($remaining <= 0 || $earliest->greaterThanOrEqualTo($windowEnd)) {
            $day         = $today->copy()->addDay();
            $doneToday   = 0;
            $remaining   = $perDay;
            $windowStart = $day->copy()->addMinutes($startHour * 60);
            $windowEnd   = $day->copy()->addMinutes($endHour * 60);
            $earliest    = $windowStart;
        }

        $sliceMinutes = max(1, intdiv($earliest->diffInMinutes($windowEnd), $remaining));
        $next         = $earliest->copy()->addMinutes(random_int(0, $sliceMinutes - 1));

        return [
            'next_run_at'       => $next,
            'today_date'        => $day->toDateString(),
            'today_occurrences' => $doneToday + 1,
        ];
    }

    private function timeParts(?string $time): array
    {
        $time = $time ?: '09:00:00';
        [$hour, $minute] = array_pad(explode(':', $time), 2, '0');

        return [(int) $hour, (int) $minute];
    }
}
