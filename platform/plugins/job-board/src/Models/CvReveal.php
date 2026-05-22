<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvReveal extends BaseModel
{
    protected $table = 'jb_cv_reveals';

    protected $fillable = [
        'employer_id',
        'candidate_id',
        'reveal_type',
        'amount_charged',
        'charge_id',
    ];

    protected $casts = [
        'amount_charged' => 'float',
    ];

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'employer_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'candidate_id');
    }
}
