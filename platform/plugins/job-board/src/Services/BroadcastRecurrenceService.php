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
     * bot. The window is split into N equal segments and we pick a random point in
     * whichever segment is next, which naturally spaces sends out through the day
     * instead of letting them cluster.
     */
    private function nextRandomPerDay(SocialBroadcast $broadcast, Carbon $after): array
    {
        $perDay    = max(1, (int) ($broadcast->recurrence_times_per_day ?: 2));
        $startHour = (int) ($broadcast->recurrence_window_start ?? 8);
        $endHour   = (int) ($broadcast->recurrence_window_end ?? 20);
        if ($endHour <= $startHour) {
            $endHour = $startHour + 1;
        }

        $today      = $after->copy()->startOfDay();
        $sameDay    = $broadcast->today_date && $broadcast->today_date->isSameDay($today);
        $doneToday  = $sameDay ? (int) $broadcast->today_occurrences : 0;

        if ($doneToday < $perDay) {
            $segment = $doneToday;
            $day     = $today;
        } else {
            $segment   = 0;
            $day       = $today->copy()->addDay();
            $doneToday = 0;
        }

        $windowMinutes  = ($endHour - $startHour) * 60;
        $segmentMinutes = intdiv($windowMinutes, $perDay);
        $segmentStart   = $startHour * 60 + $segment * $segmentMinutes;
        $offsetMinutes  = random_int(0, max(1, $segmentMinutes - 1));

        $next = $day->copy()->addMinutes($segmentStart + $offsetMinutes);

        // Never land in the past or stack right on top of "now".
        if ($next->lessThanOrEqualTo($after)) {
            $next = $after->copy()->addMinutes(random_int(5, 20));
        }

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
