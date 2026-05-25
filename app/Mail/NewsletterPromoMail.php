<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterPromoMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailSubject;
    public string $recipientName;
    public string $accountType;
    public string $subscribeUrl;

    public function __construct(
        string $recipientName,
        string $accountType,
        string $subscribeUrl,
    ) {
        $this->emailSubject  = $accountType === 'employer'
            ? 'Stay Ahead — Get Weekly Hiring Insights from WakandaJobs'
            : 'Never Miss a Job — Subscribe to the WakandaJobs Newsletter';
        $this->recipientName = $recipientName;
        $this->accountType   = $accountType;
        $this->subscribeUrl  = $subscribeUrl;
    }

    public function build(): static
    {
        return $this
            ->subject($this->emailSubject)
            ->view('emails.newsletter-promo')
            ->with([
                'recipientName' => $this->recipientName,
                'accountType'   => $this->accountType,
                'subscribeUrl'  => $this->subscribeUrl,
            ]);
    }
}
