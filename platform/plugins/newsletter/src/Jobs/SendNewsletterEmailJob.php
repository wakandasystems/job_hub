<?php

namespace Botble\Newsletter\Jobs;

use App\Mail\NewsletterMail;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendNewsletterEmailJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 120, 300];

    public function __construct(
        private readonly int $sendId,
        private readonly int $subscriberId,
        private readonly string $email,
        private readonly ?string $name,
        private readonly string $subject,
        private readonly string $body,
        private readonly ?string $imageUrl,
        private readonly ?string $pdfPath,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        Mail::to($this->email, $this->name ?? '')->send(new NewsletterMail(
            subject: $this->subject,
            body: $this->body,
            imageUrl: $this->imageUrl,
            subscriberId: $this->subscriberId,
            pdfPath: $this->pdfPath,
        ));

        DB::table('newsletter_send_recipients')->insert([
            'newsletter_send_id' => $this->sendId,
            'email'              => strtolower($this->email),
            'name'               => $this->name,
            'status'             => 'sent',
            'created_at'         => now(),
        ]);
    }
}
