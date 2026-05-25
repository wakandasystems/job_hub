<?php

namespace App\Console\Commands;

use App\Mail\NewsletterMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendNewsletter extends Command
{
    protected $signature = 'newsletter:send
                            {--subject=    : Email subject/title (overrides --title-file)}
                            {--title-file= : File containing the newsletter title (used as email subject)}
                            {--message-file= : File containing the newsletter body}
                            {--image-url=  : Public URL of the banner image (optional)}
                            {--pdf-path=   : Local path to a PDF brochure to attach (optional)}
                            {--to=         : Send only to this address (testing)}';

    protected $description = 'Send a newsletter to all subscribed WakandaJobs users';

    public function handle(): int
    {
        $messageFile = $this->option('message-file');

        if (! $messageFile || ! file_exists($messageFile)) {
            $this->error('--message-file is required and must exist.');
            return 1;
        }

        $body = trim(file_get_contents($messageFile));

        if (empty($body)) {
            $this->error('Message file is empty.');
            return 1;
        }

        // Subject priority: --subject > --title-file > first line of body
        $subject = $this->option('subject');

        if (empty($subject) && ($titleFile = $this->option('title-file')) && file_exists($titleFile)) {
            $subject = trim(file_get_contents($titleFile));
        }

        if (empty($subject)) {
            $subject = strtok($body, "\n");
        }

        $imageUrl = $this->option('image-url') ?: null;
        $pdfPath  = $this->option('pdf-path') ?: null;

        if ($testTo = $this->option('to')) {
            $subscribers = collect([(object) ['id' => 0, 'email' => $testTo, 'name' => 'Test']]);
        } else {
            $subscribers = DB::table('newsletters')
                ->where('status', 'subscribed')
                ->select('id', 'email', 'name')
                ->get();
        }

        if ($subscribers->isEmpty()) {
            $this->warn('No subscribers found.');
            echo "SENT:0\n";
            return 0;
        }

        $sent   = 0;
        $failed = 0;

        foreach ($subscribers as $sub) {
            try {
                Mail::to($sub->email, $sub->name ?? '')
                    ->send(new NewsletterMail(
                        subject:      $subject,
                        body:         $body,
                        imageUrl:     $imageUrl,
                        subscriberId: $sub->id,
                        pdfPath:      $pdfPath,
                    ));
                $sent++;
            } catch (\Throwable $e) {
                $this->error("Failed to send to {$sub->email}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->info("Sent: {$sent}, Failed: {$failed}");
        echo "SENT:{$sent}\nFAILED:{$failed}\n";

        if (! $this->option('to') && $sent > 0) {
            file_put_contents('/var/opt/wakandajobs-nl-sent-week', date('o-\WW'));
        }

        return 0;
    }
}
