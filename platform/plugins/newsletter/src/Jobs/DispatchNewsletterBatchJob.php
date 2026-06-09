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

    public function __construct(private readonly int $sendId) {}

    public function handle(EmployerContactAudience $audience): void
    {
        $send = DB::table('newsletter_sends')->find($this->sendId);

        if (! $send || $send->status !== 'scheduled') {
            return;
        }

        $subscribers = ($send->audience ?? 'subscribers') === 'employers'
            ? $audience->emails()
            : DB::table('newsletters')
                ->where('status', 'subscribed')
                ->select('id', 'email', 'name')
                ->get();

        if ($send->dedup_minutes > 0) {
            $since        = now()->subMinutes($send->dedup_minutes);
            $recentEmails = DB::table('newsletter_send_recipients')
                ->where('status', 'sent')
                ->where('created_at', '>=', $since)
                ->pluck('email')
                ->map(fn ($e) => strtolower($e))
                ->all();

            if (! empty($recentEmails)) {
                $subscribers = $subscribers
                    ->reject(fn ($subscriber) => in_array(strtolower($subscriber->email), $recentEmails, true))
                    ->values();
            }
        }

        if ($subscribers->isEmpty()) {
            DB::table('newsletter_sends')->where('id', $this->sendId)->update(['status' => 'completed']);
            return;
        }

        DB::table('newsletter_sends')->where('id', $this->sendId)->update([
            'status'          => 'running',
            'recipient_count' => $subscribers->count(),
        ]);

        $sendId = $this->sendId;

        $jobs = $subscribers->map(fn ($s) => new SendNewsletterEmailJob(
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
            ->name('newsletter-' . $sendId)
            ->finally(function (Batch $batch) use ($sendId) {
                $sentCount = DB::table('newsletter_send_recipients')
                    ->where('newsletter_send_id', $sendId)
                    ->where('status', 'sent')
                    ->count();

                DB::table('newsletter_sends')->where('id', $sendId)->update([
                    'status'       => 'completed',
                    'sent_count'   => $sentCount,
                    'failed_count' => $batch->failedJobs,
                ]);
            })
            ->onQueue('emails')
            ->dispatch();

        DB::table('newsletter_sends')->where('id', $this->sendId)->update(['batch_id' => $batch->id]);
    }
}
