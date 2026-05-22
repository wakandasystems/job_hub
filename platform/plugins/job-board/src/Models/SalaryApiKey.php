<?php

namespace Botble\JobBoard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SalaryApiKey extends Model
{
    protected $table = 'jb_salary_api_keys';

    protected $fillable = [
        'name',
        'key_prefix',
        'key_hash',
        'plan',
        'requests_per_month',
        'requests_this_month',
        'last_reset_at',
        'is_active',
        'expires_at',
        'contact_name',
        'contact_email',
        'notes',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_reset_at'=> 'datetime',
        'expires_at'   => 'datetime',
    ];

    public static function generate(): array
    {
        $raw    = 'sk_' . Str::random(32);
        $prefix = substr($raw, 0, 12);
        $hash   = hash('sha256', $raw);

        return ['raw' => $raw, 'prefix' => $prefix, 'hash' => $hash];
    }

    public function verify(string $rawKey): bool
    {
        return hash_equals($this->key_hash, hash('sha256', $rawKey));
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }
        return true;
    }

    public function isOverLimit(): bool
    {
        $this->maybeResetMonthlyCount();
        return $this->requests_this_month >= $this->requests_per_month;
    }

    public function incrementUsage(): void
    {
        $this->maybeResetMonthlyCount();
        $this->increment('requests_this_month');
    }

    protected function maybeResetMonthlyCount(): void
    {
        if ($this->last_reset_at === null || $this->last_reset_at->month !== now()->month) {
            $this->update(['requests_this_month' => 0, 'last_reset_at' => now()]);
        }
    }
}
