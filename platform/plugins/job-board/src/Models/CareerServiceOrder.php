<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerServiceOrder extends BaseModel
{
    protected $table = 'jb_career_service_orders';

    protected $fillable = [
        'service_type',
        'service_name',
        'amount',
        'currency',
        'customer_name',
        'customer_email',
        'customer_phone',
        'candidate_id',
        'charge_id',
        'payment_method',
        'status',
        'notes',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'candidate_id');
    }

    public static function services(): array
    {
        return [
            'cv_review' => [
                'name' => 'Basic CV Review',
                'price' => 12,
                'delivery' => '24 hrs',
                'description' => 'Professional feedback on structure, content and impact.',
                'icon' => 'fi-rr-document',
            ],
            'cv_rewrite' => [
                'name' => 'Professional CV Rewrite',
                'price' => 35,
                'delivery' => '48 hrs',
                'description' => 'Full rewrite tailored to your target role.',
                'icon' => 'fi-rr-pencil',
            ],
            'linkedin' => [
                'name' => 'LinkedIn Optimisation',
                'price' => 25,
                'delivery' => '48 hrs',
                'description' => 'Headline, summary and skills tuned to attract recruiters.',
                'icon' => 'fi-rr-network',
            ],
            'cover_letter' => [
                'name' => 'Cover Letter Writing',
                'price' => 10,
                'delivery' => '24 hrs',
                'description' => 'Compelling cover letter matched to your application.',
                'icon' => 'fi-rr-envelope',
            ],
            'interview_coaching' => [
                'name' => 'Interview Coaching (1 hr)',
                'price' => 45,
                'delivery' => '72 hrs',
                'description' => 'Live 1-on-1 session with a career coach via video call.',
                'icon' => 'fi-rr-user-headset',
            ],
            'bundle' => [
                'name' => 'Complete Bundle',
                'price' => 75,
                'delivery' => '72 hrs',
                'description' => 'CV Rewrite + Cover Letter + LinkedIn — best value.',
                'icon' => 'fi-rr-stars',
                'badge' => 'Best Value',
            ],
        ];
    }
}
