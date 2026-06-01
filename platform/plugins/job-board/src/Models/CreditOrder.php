<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Botble\JobBoard\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditOrder extends BaseModel
{
    protected $table = 'jb_credit_orders';

    protected $fillable = [
        'account_id',
        'package_id',
        'credits',
        'amount',
        'currency',
        'payment_method',
        'charge_id',
        'payment_reference',
        'status',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'amount'      => 'float',
        'approved_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function approve(): void
    {
        if ($this->status !== 'pending') {
            return;
        }

        $this->account->increment('credits', $this->credits);
        $this->account->packages()->syncWithoutDetaching([$this->package_id]);

        $this->update([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);

        $description = 'Purchased ' . number_format($this->credits) . ' credits';
        if ($this->package?->name) {
            $description .= ' — ' . $this->package->name;
        }
        $description .= ' (' . ucwords(str_replace('_', ' ', $this->payment_method ?? 'manual')) . ')';
        if ($this->payment_reference) {
            $description .= ' · Ref: ' . $this->payment_reference;
        }

        Transaction::query()->create([
            'user_id'    => 0,
            'account_id' => $this->account_id,
            'credits'    => $this->credits,
            'payment_id' => null,
            'description' => $description,
        ]);
    }

    public function reject(string $notes = ''): void
    {
        $this->update([
            'status' => 'rejected',
            'notes'  => $notes,
        ]);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public static function statuses(): array
    {
        return [
            'pending'  => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
    }
}
