<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailSubject;
    public string $body;
    public ?string $imageUrl;
    public int $subscriberId;
    public ?string $pdfPath;
    public ?string $pdfName;

    public function __construct(
        string $subject,
        string $body,
        ?string $imageUrl,
        int $subscriberId,
        ?string $pdfPath = null,
    ) {
        $this->emailSubject = $subject;
        $this->body         = $body;
        $this->imageUrl     = $imageUrl;
        $this->subscriberId = $subscriberId;
        $this->pdfPath      = $pdfPath;
        $this->pdfName      = $pdfPath ? basename($pdfPath) : null;
    }

    public function build(): static
    {
        $mail = $this
            ->subject($this->emailSubject)
            ->view('emails.newsletter')
            ->with([
                'subject'      => $this->emailSubject,
                'pdfName'      => $this->pdfName,
            ]);

        if ($this->pdfPath && file_exists($this->pdfPath)) {
            $mail->attach($this->pdfPath, [
                'as'   => $this->pdfName,
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
