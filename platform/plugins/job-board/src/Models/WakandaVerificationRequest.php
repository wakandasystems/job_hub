<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Throwable;

class WakandaVerificationRequest extends BaseModel
{
    protected $table = 'jb_wakanda_verification_requests';

    protected $fillable = [
        'account_id',
        'charge_id',
        'payment_method',
        'payment_reference',
        'amount',
        'currency',
        'status',
        'admin_notes',
        'score',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function isPendingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }

    public function confirmPayment(string $chargeId, string $paymentMethod, ?string $reference = null): void
    {
        $this->update([
            'charge_id'         => $chargeId,
            'payment_method'    => $paymentMethod,
            'payment_reference' => $reference,
            'status'            => 'pending',
        ]);

        $this->notifyAdminPendingReview();
    }

    public function approve(int $score, ?string $notes = null): void
    {
        $this->update([
            'status'      => 'approved',
            'score'       => $score,
            'admin_notes' => $notes,
        ]);

        $account = $this->account;
        if ($account) {
            $account->wakanda_verified    = true;
            $account->wakanda_score       = $score;
            $account->wakanda_verified_at = now();
            $account->save();
        }
    }

    public function reject(?string $notes = null): void
    {
        $this->update([
            'status'      => 'rejected',
            'admin_notes' => $notes,
        ]);
    }

    public function notifyAdminPendingReview(?int $creditsPaid = null): void
    {
        if ($this->status !== 'pending') {
            return;
        }

        $this->loadMissing('account');

        $account = $this->account;
        $reviewUrl = route('wakanda-verification.index');
        $details = [
            'Request ID' => $this->getKey(),
            'Candidate' => $account?->name ?: 'Unknown',
            'Email' => $account?->email ?: 'Unknown',
            'Phone' => $account?->phone ?: 'Not provided',
            'Credits paid' => $creditsPaid !== null ? (string) $creditsPaid : null,
            'Amount' => $this->amount ? trim($this->currency . ' ' . number_format((float) $this->amount, 2)) : null,
            'Payment method' => $this->payment_method,
            'Payment reference' => $this->payment_reference,
            'Review link' => $reviewUrl,
        ];

        $lines = collect($details)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value, $label) => "{$label}: {$value}")
            ->values()
            ->all();

        $body = "New Wakanda Verification Request\n\n" . implode("\n", $lines);

        $adminEmail = setting('admin_email') ?: config('mail.from.address');
        if ($adminEmail) {
            try {
                Mail::raw($body, fn ($msg) => $msg
                    ->to($adminEmail)
                    ->subject('New Wakanda Verification Request'));
            } catch (Throwable) {
            }
        }

        $token = trim((string) setting('telegram_bot_token', ''));
        $chatId = trim((string) setting('telegram_admin_chat_id', '5777916704'));

        if ($token === '' || $chatId === '') {
            return;
        }

        try {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $body,
                'reply_markup' => [
                    'inline_keyboard' => [[
                        ['text' => 'Open approvals', 'url' => $reviewUrl],
                    ]],
                ],
            ]);
        } catch (Throwable) {
        }
    }
}
