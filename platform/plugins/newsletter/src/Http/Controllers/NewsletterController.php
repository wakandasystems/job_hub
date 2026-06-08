<?php

namespace Botble\Newsletter\Http\Controllers;

use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\Newsletter\Jobs\DispatchNewsletterBatchJob;
use Botble\Newsletter\Jobs\SendNewsletterEmailJob;
use Botble\Newsletter\Models\Newsletter;
use Botble\Newsletter\Tables\NewsletterTable;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class NewsletterController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/newsletter::newsletter.name'), route('newsletter.index'));
    }

    public function index(NewsletterTable $dataTable)
    {
        $this->pageTitle(trans('plugins/newsletter::newsletter.name'));

        $now = now();

        $stats = [
            'today'      => DB::table('newsletters')->where('status', 'subscribed')->whereDate('created_at', $now->toDateString())->count(),
            'this_week'  => DB::table('newsletters')->where('status', 'subscribed')->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])->count(),
            'this_month' => DB::table('newsletters')->where('status', 'subscribed')->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
            'all_time'   => DB::table('newsletters')->where('status', 'subscribed')->count(),
        ];

        $recentSends = DB::table('newsletter_sends')
            ->orderByDesc('sent_at')
            ->paginate(10, ['*'], 'sends_page');

        return $dataTable->renderTable(mergeData: compact('stats', 'recentSends'));
    }

    public function send()
    {
        $this->pageTitle('Send Newsletter');

        $subscriberCount = DB::table('newsletters')->where('status', 'subscribed')->count();

        // Latest non-test send that had failures — used to show inline Resend button
        $lastFailedSend = DB::table('newsletter_sends')
            ->where('failed_count', '>', 0)
            ->whereNull('test_to')
            ->whereIn('status', ['completed', 'failed'])
            ->orderByDesc('sent_at')
            ->first(['id', 'subject', 'failed_count', 'recipient_count']);

        return view('plugins/newsletter::send', compact('subscriberCount', 'lastFailedSend'));
    }

    public function sendPost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject'       => ['required', 'string', 'max:180'],
            'message'       => ['required', 'string', 'max:20000'],
            'image_file'    => ['nullable', 'file', 'image', 'max:5120'],
            'image_url'     => ['nullable', 'url', 'max:500'],
            'pdf'           => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'test_to'       => ['nullable', 'email', 'max:180'],
            'dedup_minutes' => ['nullable', 'integer', 'in:0,30,60,360,1440'],
            'scheduled_at'  => ['nullable', 'date', 'after:now'],
        ]);

        if ($request->hasFile('image_file') && $request->file('image_file')->isValid()) {
            $imgFile     = $request->file('image_file');
            $imgFilename = now()->format('YmdHis') . '-' . preg_replace('/[^A-Za-z0-9._-]/', '-', $imgFile->getClientOriginalName());
            $imgFile->move(storage_path('app/public/newsletter'), $imgFilename);
            $validated['image_url'] = rtrim(config('app.url'), '/') . '/storage/newsletter/' . $imgFilename;
        }

        $pdfPath = null;
        if ($request->hasFile('pdf')) {
            $directory = storage_path('app/newsletter-attachments');
            if (! is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
            $file     = $request->file('pdf');
            $filename = now()->format('YmdHis') . '-' . preg_replace('/[^A-Za-z0-9._-]/', '-', $file->getClientOriginalName());
            $file->move($directory, $filename);
            $pdfPath = $directory . '/' . $filename;
        }

        $dedupMinutes = (int) ($validated['dedup_minutes'] ?? 0);
        $scheduledAt  = ! empty($validated['scheduled_at']) ? $validated['scheduled_at'] : null;
        $isTest       = ! empty($validated['test_to']);

        // ── Duplicate detection (non-test, non-scheduled sends only) ──
        if (! $isTest && ! $scheduledAt) {
            $existing = DB::table('newsletter_sends')
                ->whereNull('test_to')
                ->whereIn('status', ['completed', 'running'])
                ->where('subject', $validated['subject'])
                ->where('body', $validated['message'])
                ->orderByDesc('sent_at')
                ->first(['id', 'subject', 'sent_at', 'sent_count', 'recipient_count']);

            if ($existing) {
                $newCount = DB::table('newsletters')
                    ->where('status', 'subscribed')
                    ->where('created_at', '>', $existing->sent_at)
                    ->count();

                return response()->json([
                    'duplicate'            => true,
                    'send_id'              => $existing->id,
                    'sent_at'              => $existing->sent_at,
                    'sent_count'           => (int) $existing->sent_count,
                    'recipient_count'      => (int) $existing->recipient_count,
                    'new_subscriber_count' => $newCount,
                ], 409);
            }
        }

        // ── Build subscriber list ──
        if ($isTest) {
            $subscribers = collect([(object) [
                'id'    => 0,
                'email' => $validated['test_to'],
                'name'  => 'Test',
            ]]);
        } else {
            $query = DB::table('newsletters')
                ->where('status', 'subscribed')
                ->select('id', 'email', 'name');

            if ($dedupMinutes > 0 && ! $scheduledAt) {
                $recentEmails = DB::table('newsletter_send_recipients')
                    ->where('status', 'sent')
                    ->where('created_at', '>=', now()->subMinutes($dedupMinutes))
                    ->pluck('email')
                    ->map(fn ($e) => strtolower($e))
                    ->all();

                if (! empty($recentEmails)) {
                    $query->whereNotIn(DB::raw('LOWER(email)'), $recentEmails);
                }
            }

            $subscribers = $query->get();
        }

        if ($subscribers->isEmpty()) {
            return response()->json([
                'error' => 'No recipients — everyone may have already received a newsletter within the dedup window.',
            ], 422);
        }

        $status = $scheduledAt ? 'scheduled' : 'running';

        $sendId = (int) DB::table('newsletter_sends')->insertGetId([
            'subject'          => $validated['subject'],
            'body'             => $validated['message'],
            'image_url'        => $validated['image_url'] ?? null,
            'pdf_path'         => $pdfPath,
            'recipient_count'  => $subscribers->count(),
            'sent_count'       => 0,
            'failed_count'     => 0,
            'test_to'          => $validated['test_to'] ?? null,
            'status'           => $status,
            'dedup_minutes'    => $dedupMinutes,
            'scheduled_at'     => $scheduledAt,
            'sent_at'          => now(),
        ]);

        // ── Scheduled send: delay the batch dispatcher ──
        if ($scheduledAt) {
            DispatchNewsletterBatchJob::dispatch($sendId)
                ->delay(now()->diffInSeconds(\Carbon\Carbon::parse($scheduledAt)));

            return response()->json([
                'send_id'      => $sendId,
                'scheduled'    => true,
                'scheduled_at' => $scheduledAt,
                'total'        => $subscribers->count(),
            ]);
        }

        // ── Immediate send: dispatch batch now ──
        $jobs = $subscribers->map(fn ($s) => new SendNewsletterEmailJob(
            sendId:       $sendId,
            subscriberId: (int) $s->id,
            email:        $s->email,
            name:         $s->name,
            subject:      $validated['subject'],
            body:         $validated['message'],
            imageUrl:     $validated['image_url'] ?? null,
            pdfPath:      $pdfPath,
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

        DB::table('newsletter_sends')->where('id', $sendId)->update(['batch_id' => $batch->id]);

        return response()->json([
            'send_id'   => $sendId,
            'batch_id'  => $batch->id,
            'scheduled' => false,
            'total'     => $subscribers->count(),
        ]);
    }

    public function sendStatus(int $sendId): JsonResponse
    {
        $send = DB::table('newsletter_sends')->find($sendId);

        if (! $send) {
            return response()->json(['error' => 'Send not found.'], 404);
        }

        $batch = null;
        if ($send->batch_id) {
            try {
                $batch = Bus::findBatch($send->batch_id);
            } catch (\Throwable) {}
        }

        $processedJobs = $batch ? $batch->processedJobs() : $send->recipient_count;
        $totalJobs     = $batch ? $batch->totalJobs      : $send->recipient_count;
        $failedJobs    = $batch ? $batch->failedJobs     : (int) $send->failed_count;
        $finished      = $batch ? $batch->finished()     : in_array($send->status, ['completed', 'failed', 'cancelled']);
        $progressPct   = $totalJobs > 0 ? (int) round(($processedJobs / $totalJobs) * 100) : 0;

        return response()->json([
            'status'       => $send->status,
            'progress_pct' => $progressPct,
            'processed'    => $processedJobs,
            'total'        => $totalJobs,
            'failed'       => $failedJobs,
            'sent'         => max(0, $processedJobs - $failedJobs),
            'finished'     => $finished,
            'scheduled_at' => $send->scheduled_at,
            'subject'      => $send->subject,
        ]);
    }

    public function resend(int $sendId, Request $request): JsonResponse
    {
        $send = DB::table('newsletter_sends')->find($sendId);

        if (! $send) {
            return response()->json(['error' => 'Send not found.'], 404);
        }

        $newOnly = $request->boolean('new_only');

        if ($newOnly) {
            // Only subscribers who joined after this send was dispatched
            $subscribers = DB::table('newsletters')
                ->where('status', 'subscribed')
                ->where('created_at', '>', $send->sent_at)
                ->select('id', 'email', 'name')
                ->get();
        } else {
            // Anyone not already successfully delivered in the original send
            $sentEmails = DB::table('newsletter_send_recipients')
                ->where('newsletter_send_id', $sendId)
                ->where('status', 'sent')
                ->pluck('email')
                ->map(fn ($e) => strtolower($e))
                ->all();

            $subscribers = DB::table('newsletters')
                ->where('status', 'subscribed')
                ->select('id', 'email', 'name')
                ->get()
                ->filter(fn ($s) => ! in_array(strtolower($s->email), $sentEmails))
                ->values();
        }

        if ($subscribers->isEmpty()) {
            $error = $newOnly
                ? 'No new subscribers have joined since this newsletter was sent.'
                : 'Everyone already received this newsletter — nothing to resend.';

            return response()->json(['error' => $error], 422);
        }

        $body = $send->body ?? '';

        $newSendId = (int) DB::table('newsletter_sends')->insertGetId([
            'subject'         => $send->subject,
            'body'            => $body,
            'image_url'       => $send->image_url,
            'pdf_path'        => $send->pdf_path,
            'recipient_count' => $subscribers->count(),
            'sent_count'      => 0,
            'failed_count'    => 0,
            'test_to'         => null,
            'status'          => 'running',
            'dedup_minutes'   => 0,
            'scheduled_at'    => null,
            'sent_at'         => now(),
        ]);

        try {
            $jobs = $subscribers->map(fn ($s) => new SendNewsletterEmailJob(
                sendId:       $newSendId,
                subscriberId: (int) $s->id,
                email:        $s->email,
                name:         $s->name,
                subject:      $send->subject,
                body:         $body,
                imageUrl:     $send->image_url,
                pdfPath:      $send->pdf_path,
            ));

            $batch = Bus::batch($jobs->all())
                ->name('newsletter-resend-' . $newSendId)
                ->finally(function (Batch $batch) use ($newSendId) {
                    $sentCount = DB::table('newsletter_send_recipients')
                        ->where('newsletter_send_id', $newSendId)
                        ->where('status', 'sent')
                        ->count();

                    DB::table('newsletter_sends')->where('id', $newSendId)->update([
                        'status'       => 'completed',
                        'sent_count'   => $sentCount,
                        'failed_count' => $batch->failedJobs,
                    ]);
                })
                ->onQueue('emails')
                ->dispatch();

            DB::table('newsletter_sends')->where('id', $newSendId)->update(['batch_id' => $batch->id]);
        } catch (\Throwable $e) {
            DB::table('newsletter_sends')->where('id', $newSendId)->update(['status' => 'failed']);
            throw $e;
        }

        return response()->json([
            'send_id'  => $newSendId,
            'batch_id' => $batch->id,
            'total'    => $subscribers->count(),
        ]);
    }

    public function cancel(int $sendId): JsonResponse
    {
        $send = DB::table('newsletter_sends')->find($sendId);

        if (! $send) {
            return response()->json(['error' => 'Send not found.'], 404);
        }

        if (! in_array($send->status, ['scheduled', 'running'])) {
            return response()->json(['error' => 'Only scheduled or running sends can be cancelled.'], 422);
        }

        // Cancel the Laravel batch if it exists
        if ($send->batch_id) {
            try {
                $batch = Bus::findBatch($send->batch_id);
                $batch?->cancel();
            } catch (\Throwable) {}
        }

        DB::table('newsletter_sends')->where('id', $sendId)->update(['status' => 'cancelled']);

        return response()->json(['ok' => true]);
    }

    public function recipients(int $sendId, Request $request): View|Response
    {
        $send = DB::table('newsletter_sends')->find($sendId);

        if (! $send) {
            abort(404);
        }

        if ($request->get('export') === 'csv') {
            $rows = DB::table('newsletter_send_recipients')
                ->where('newsletter_send_id', $sendId)
                ->where('status', 'failed')
                ->orderBy('email')
                ->get(['email', 'name', 'error_message', 'created_at']);

            $csv = "Email,Name,Error,Date\n";
            foreach ($rows as $r) {
                $csv .= '"' . str_replace('"', '""', $r->email ?? '') . '",';
                $csv .= '"' . str_replace('"', '""', $r->name ?? '') . '",';
                $csv .= '"' . str_replace('"', '""', $r->error_message ?? '') . '",';
                $csv .= '"' . ($r->created_at ?? '') . '"' . "\n";
            }

            return response($csv, 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="newsletter-' . $sendId . '-failed.csv"',
            ]);
        }

        $status     = $request->get('status', 'all');
        $query      = DB::table('newsletter_send_recipients')->where('newsletter_send_id', $sendId);

        if ($status === 'failed') {
            $query->where('status', 'failed');
        } elseif ($status === 'sent') {
            $query->where('status', 'sent');
        }

        $recipients = $query->orderByRaw("status = 'failed' DESC")->orderBy('email')->paginate(50)->withQueryString();

        $counts = DB::table('newsletter_send_recipients')
            ->where('newsletter_send_id', $sendId)
            ->selectRaw("status, count(*) as cnt")
            ->groupBy('status')
            ->pluck('cnt', 'status');

        return view('plugins/newsletter::recipients', compact('send', 'recipients', 'status', 'counts'));
    }

    public function destroy(Newsletter $newsletter)
    {
        return DeleteResourceAction::make($newsletter);
    }
}
