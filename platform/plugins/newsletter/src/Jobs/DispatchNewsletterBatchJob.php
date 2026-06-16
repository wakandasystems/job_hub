<?php

namespace Botble\Newsletter\Jobs;

use Botble\JobBoard\Supports\EmployerContactAudience;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class DispatchNewsletterBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Max emails per wave — stays under Hostinger's ~500/hr session limit
    private const WAVE_SIZE = 500;

    public function __construct(private readonly int $sendId) {}

    public function handle(EmployerContactAudience $audience): void
    {
        $send = DB::table('newsletter_sends')->find($this->sendId);

        if (! $send || ! in_array($send->status, ['scheduled', 'running', 'paused'])) {
            return;
        }

        $allSubscribers = ($send->audience ?? 'subscribers') === 'employers'
            ? $audience->emails()
            : DB::table('newsletters')
                ->where('status', 'subscribed')
                ->select('id', 'email', 'name')
                ->get();

        // Dedup check only on the very first wave to avoid resending to recent recipients
        if ($send->status === 'scheduled' && $send->dedup_minutes > 0) {
            $since        = now()->subMinutes($send->dedup_minutes);
            $recentEmails = DB::table('newsletter_send_recipients')
                ->where('status', 'sent')
                ->where('created_at', '>=', $since)
                ->pluck('email')
                ->map(fn ($e) => strtolower($e))
                ->all();

            if (! empty($recentEmails)) {
                $allSubscribers = $allSubscribers
                    ->reject(fn ($s) => in_array(strtolower($s->email), $recentEmails, true))
                    ->values();
            }
        }

        // Remove globally blocked emails
        $blockedEmails = DB::table('newsletter_blocked_emails')
            ->pluck('email')
            ->map(fn ($e) => strtolower($e))
            ->all();

        if (! empty($blockedEmails)) {
            $allSubscribers = $allSubscribers
                ->reject(fn ($s) => in_array(strtolower($s->email), $blockedEmails, true))
                ->values();
        }

        // Remove recipients already successfully sent in a previous wave of this send
        $alreadySent = DB::table('newsletter_send_recipients')
            ->where('newsletter_send_id', $this->sendId)
            ->where('status', 'sent')
            ->pluck('email')
            ->map(fn ($e) => strtolower($e))
            ->all();

        $remaining = $allSubscribers
            ->reject(fn ($s) => in_array(strtolower($s->email), $alreadySent, true))
            ->values();

        // Nothing left to send — mark complete
        if ($remaining->isEmpty()) {
            $sentCount   = count($alreadySent);
            $failedCount = DB::table('newsletter_send_recipients')
                ->where('newsletter_send_id', $this->sendId)
                ->where('status', 'failed')
                ->count();
            DB::table('newsletter_sends')->where('id', $this->sendId)->update([
                'status'       => 'completed',
                'sent_count'   => $sentCount,
                'failed_count' => $failedCount,
                'next_wave_at' => null,
            ]);
            return;
        }

        // Set total recipient_count once on the first wave
        if ($send->status === 'scheduled') {
            DB::table('newsletter_sends')->where('id', $this->sendId)->update([
                'status'          => 'running',
                'recipient_count' => $allSubscribers->count(),
            ]);
        } else {
            DB::table('newsletter_sends')->where('id', $this->sendId)->update([
                'status'      => 'running',
                'next_wave_at' => null,
            ]);
        }

        $wave      = $remaining->take(self::WAVE_SIZE);
        $hasMore   = $remaining->count() > self::WAVE_SIZE;
        $sendId    = $this->sendId;
        $sentBefore = count($alreadySent);

        $jobs = $wave->map(fn ($s) => new SendNewsletterEmailJob(
            sendId:       $sendId,
            subscriberId: $s->id ? (int) $s->id : null,
            email:        $s->email,
            name:         $s->name,
            subject:      $send->subject,
            body:         $send->body,
            imageUrl:     $send->image_url,
            pdfPath:      $send->pdf_path,
        ));

        $batch = Bus::batch($jobs->all())
            ->name('newsletter-' . $sendId . '-' . now()->timestamp)
            ->finally(function (Batch $batch) use ($sendId, $hasMore, $sentBefore) {
                $sentCount = DB::table('newsletter_send_recipients')
                    ->where('newsletter_send_id', $sendId)
                    ->where('status', 'sent')
                    ->count();

                $failedCount = DB::table('newsletter_send_recipients')
                    ->where('newsletter_send_id', $sendId)
                    ->where('status', 'failed')
                    ->count();

                $newlySent = $sentCount - $sentBefore;

                // Schedule next wave if there are more AND we made progress this wave
                if ($hasMore && $newlySent > 0) {
                    $nextWaveAt = now()->addHour();
                    DB::table('newsletter_sends')->where('id', $sendId)->update([
                        'status'       => 'paused',
                        'sent_count'   => $sentCount,
                        'failed_count' => $failedCount,
                        'next_wave_at' => $nextWaveAt,
                    ]);
                    DispatchNewsletterBatchJob::dispatch($sendId)->delay($nextWaveAt);
                } else {
                    DB::table('newsletter_sends')->where('id', $sendId)->update([
                        'status'       => 'completed',
                        'sent_count'   => $sentCount,
                        'failed_count' => $failedCount,
                        'next_wave_at' => null,
                    ]);
                }
            })
            ->onQueue('emails')
            ->dispatch();

        DB::table('newsletter_sends')->where('id', $this->sendId)->update(['batch_id' => $batch->id]);
    }
}
