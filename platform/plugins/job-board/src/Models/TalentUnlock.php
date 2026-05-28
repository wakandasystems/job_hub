<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentUnlock extends BaseModel
{
    protected $table = 'jb_talent_unlocks';

    protected $fillable = [
        'employer_account_id',
        'candidate_account_id',
        'credits_spent',
    ];

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'employer_account_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'candidate_account_id');
    }
}
