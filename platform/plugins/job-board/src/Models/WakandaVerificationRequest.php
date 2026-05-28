<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WakandaVerificationRequest extends BaseModel
{
    protected $table = 'jb_wakanda_verification_requests';

    protected $fillable = [
        'account_id',
        'status',
        'admin_notes',
        'score',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
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
}
