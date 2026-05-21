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
        'assigned_coach_name',
        'assigned_coach_email',
        'charge_id',
        'payment_method',
        'delivery_status',
        'delivered_at',
        'ai_cv_score',
        'ai_cv_feedback',
        'status',
        'notes',
        'candidate_cv_path',
        'reviewed_cv_path',
    ];

    protected $casts = [
        'amount' => 'float',
        'delivered_at' => 'datetime',
        'ai_cv_feedback' => 'array',
    ];

    public static function deliveryStatuses(): array
    {
        return [
            'unassigned' => 'Unassigned',
            'assigned' => 'Assigned',
            'in_progress' => 'In progress',
            'delivered' => 'Delivered',
            'revision_requested' => 'Revision requested',
            'cancelled' => 'Cancelled',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'candidate_id');
    }

    public static function services(): array
    {
        return [
            'cv_review' => [
                'name'        => 'Basic CV Review',
                'price'       => (float) setting('career_service_price_cv_review', 12),
                'delivery'    => setting('career_service_delivery_cv_review', '24 hrs'),
                'description' => 'Professional feedback on structure, content and impact.',
                'icon'        => 'fi-rr-document',
            ],
            'cv_rewrite' => [
                'name'        => 'Professional CV Rewrite',
                'price'       => (float) setting('career_service_price_cv_rewrite', 35),
                'delivery'    => setting('career_service_delivery_cv_rewrite', '48 hrs'),
                'description' => 'Full rewrite tailored to your target role.',
                'icon'        => 'fi-rr-pencil',
            ],
            'linkedin' => [
                'name'        => 'LinkedIn Optimisation',
                'price'       => (float) setting('career_service_price_linkedin', 25),
                'delivery'    => setting('career_service_delivery_linkedin', '48 hrs'),
                'description' => 'Headline, summary and skills tuned to attract recruiters.',
                'icon'        => 'fi-rr-network',
            ],
            'cover_letter' => [
                'name'        => 'Cover Letter Writing',
                'price'       => (float) setting('career_service_price_cover_letter', 10),
                'delivery'    => setting('career_service_delivery_cover_letter', '24 hrs'),
                'description' => 'Compelling cover letter matched to your application.',
                'icon'        => 'fi-rr-envelope',
            ],
            'interview_coaching' => [
                'name'        => 'Interview Coaching (1 hr)',
                'price'       => (float) setting('career_service_price_interview_coaching', 45),
                'delivery'    => setting('career_service_delivery_interview_coaching', '72 hrs'),
                'description' => 'Live 1-on-1 session with a career coach via video call.',
                'icon'        => 'fi-rr-user-headset',
            ],
            'bundle' => [
                'name'        => 'Complete Bundle',
                'price'       => (float) setting('career_service_price_bundle', 75),
                'delivery'    => setting('career_service_delivery_bundle', '72 hrs'),
                'description' => 'CV Rewrite + Cover Letter + LinkedIn — best value.',
                'icon'        => 'fi-rr-stars',
                'badge'       => 'Best Value',
            ],
        ];
    }
}
