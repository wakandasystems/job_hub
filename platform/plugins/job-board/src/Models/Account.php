<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Botble\Base\Supports\Avatar;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\Concerns\HasActiveJobsRelation;
use Botble\JobBoard\Models\Concerns\UniqueId;
use Botble\JobBoard\Notifications\ConfirmEmailNotification;
use Botble\JobBoard\Notifications\ResetPasswordNotification;
use Botble\Media\Facades\RvMedia;
use Botble\Media\Models\MediaFile;
use Exception;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Account extends BaseModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;
    use HasActiveJobsRelation;
    use HasApiTokens;
    use MustVerifyEmail;
    use Notifiable;
    use UniqueId;

    protected $table = 'jb_accounts';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'avatar_id',
        'dob',
        'phone',
        'call_numbers',
        'description',
        'linkedin',
        'gender',
        'package_id',
        'type',
        'credits',
        'free_credits',
        'free_credits_refreshed_at',
        'resume',
        'address',
        'bio',
        'is_public_profile',
        'hide_cv',
        'available_for_hiring',
        'country_id',
        'state_id',
        'city_id',
        'cover_letter',
        'unique_id',
        'whatsapp_number',
        'whatsapp_numbers',
        'telegram_chat_id',
        'cv_score',
        'cv_score_data',
        'cv_score_history',
        'desired_salary_from',
        'desired_salary_to',
        'experience_years',
        'education_level',
        'availability',
        'talent_hub_consent',
        'profile_updated_at',
        'wakanda_verified',
        'wakanda_score',
        'wakanda_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'type'               => AccountTypeEnum::class,
        'dob'                => 'datetime',
        'cv_score_data'      => 'array',
        'cv_score_history'   => 'array',
        'call_numbers'       => 'array',
        'whatsapp_numbers'   => 'array',
        'talent_hub_consent'  => 'boolean',
        'profile_updated_at'  => 'datetime',
        'wakanda_verified'        => 'boolean',
        'wakanda_verified_at'     => 'datetime',
        'free_credits_refreshed_at' => 'datetime',
    ];

    public static function experienceYearsOptions(): array
    {
        return [
            ''   => '— Any —',
            '0'  => 'No experience',
            '1'  => 'Less than 1 year',
            '2'  => '1–2 years',
            '3'  => '3–5 years',
            '5'  => '5–10 years',
            '10' => '10+ years',
        ];
    }

    public static function educationLevelOptions(): array
    {
        return [
            ''           => '— Any —',
            'high_school'=> 'High School',
            'diploma'    => 'Diploma / Certificate',
            'bachelor'   => "Bachelor's Degree",
            'masters'    => "Master's Degree",
            'phd'        => 'PhD / Doctorate',
        ];
    }

    public static function availabilityOptions(): array
    {
        return [
            ''           => '— Any —',
            'immediate'  => 'Immediately',
            'one_week'   => 'Within 1 week',
            'two_weeks'  => 'Within 2 weeks',
            'one_month'  => 'Within 1 month',
            'not_looking'=> 'Not actively looking',
        ];
    }

    public function isProfileStale(int $days = 90): bool
    {
        $lastUpdated = $this->profile_updated_at ?? $this->updated_at;
        return $lastUpdated !== null && $lastUpdated->lt(now()->subDays($days));
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new ConfirmEmailNotification());
    }

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class)->withDefault();
    }

    public function salesAgentProfile(): HasOne
    {
        return $this->hasOne(SalesAgent::class, 'candidate_account_id');
    }

    public function resumeDownloadUrl(): Attribute
    {
        return Attribute::get(
            fn () => route('public.candidate.download-cv', ['account' => $this->slug, 'path' => $this->resume])
        );
    }

    protected function firstName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ucfirst((string) $value),
            set: fn ($value) => ucfirst((string) $value),
        );
    }

    protected function lastName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ucfirst((string) $value),
            set: fn ($value) => ucfirst((string) $value),
        );
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn () => $this->first_name . ' ' . $this->last_name);
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->avatar->url) {
                    return RvMedia::url($this->avatar->url);
                }

                try {
                    if (setting('job_board_default_account_avatar')) {
                        return RvMedia::getImageUrl(setting('job_board_default_account_avatar'));
                    }

                    return (new Avatar())->create($this->name)->toBase64();
                } catch (Exception) {
                    return RvMedia::getDefaultImage();
                }
            },
        );
    }

    protected function avatarThumbUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->avatar->url) {
                    return RvMedia::getImageUrl($this->avatar->url, 'thumb');
                }

                try {
                    if (setting('job_board_default_account_avatar')) {
                        return RvMedia::getImageUrl(setting('job_board_default_account_avatar'), 'thumb');
                    }

                    return (new Avatar())->create($this->name)->toBase64();
                } catch (Exception) {
                    return RvMedia::getDefaultImage();
                }
            },
        );
    }

    protected function credits(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (! JobBoardHelper::isEnabledCreditsSystem()) {
                    return 0;
                }

                return $value ?: 0;
            }
        );
    }

    protected function resumeUrl(): Attribute
    {
        return Attribute::get(fn () => $this->resume ? RvMedia::url($this->resume) : '');
    }

    protected function resumeName(): Attribute
    {
        return Attribute::get(fn () => $this->resume ? basename($this->resume_url) : '');
    }

    public function canPost(): bool
    {
        return ($this->credits + ($this->free_credits ?? 0)) > 0 || ! JobBoardHelper::isEnabledCreditsSystem();
    }

    public function spendCredits(int $amount = 1): bool
    {
        if (! JobBoardHelper::isEnabledCreditsSystem()) {
            return true;
        }

        $freeBalance = (int) ($this->free_credits ?? 0);
        $paidBalance = (int) ($this->credits ?? 0);

        if ($freeBalance + $paidBalance < $amount) {
            return false;
        }

        $fromFree = min($freeBalance, $amount);
        $fromPaid = $amount - $fromFree;

        $this->free_credits = $freeBalance - $fromFree;
        $this->credits      = $paidBalance - $fromPaid;
        $this->save();

        return true;
    }

    public function isEmployer(): bool
    {
        return $this->type == AccountTypeEnum::EMPLOYER;
    }

    public function isJobSeeker(): bool
    {
        return $this->type == AccountTypeEnum::JOB_SEEKER;
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'jb_companies_accounts', 'account_id', 'company_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'account_id');
    }

    public function educations(): HasMany
    {
        return $this->hasMany(AccountEducation::class, 'account_id');
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(AccountExperience::class, 'account_id');
    }

    public function jobs(): MorphMany
    {
        return $this->morphMany(Job::class, 'author');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'account_id');
    }

    public function wakandaBadgeHtml(): string
    {
        if (! $this->wakanda_verified) {
            return '';
        }

        static $cssInjected = false;
        $css = '';
        if (! $cssInjected) {
            $cssInjected = true;
            $css = '<style>'
                . '.wk-v-badge{position:absolute;top:-4px;left:-4px;display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;background:#6f42c1;color:#fff;border-radius:50%;font-size:12px;line-height:1;z-index:2;border:2px solid #fff;cursor:default}'
                . '.wk-v-badge__tip{visibility:hidden;opacity:0;position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:#6f42c1;color:#fff;padding:3px 8px;border-radius:5px;font-size:12px;white-space:nowrap;pointer-events:none;transition:opacity .15s;z-index:9999}'
                . '.wk-v-badge__tip::after{content:"";position:absolute;top:100%;left:50%;transform:translateX(-50%);border:4px solid transparent;border-top-color:#6f42c1}'
                . '.wk-v-badge:hover .wk-v-badge__tip{visibility:visible;opacity:1}'
                . '</style>';
        }

        $score = (int) $this->wakanda_score;
        $stars = str_repeat('★', $score) . str_repeat('☆', max(0, 5 - $score));

        return $css
            . '<span class="wk-v-badge" aria-label="Wakanda Verified — Score: ' . $score . '/5">'
            . '★'
            . '<span class="wk-v-badge__tip">' . $stars . ' ' . $score . '/5</span>'
            . '</span>';
    }

    public function applicants(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'account_id')
            ->whereIn('job_id', $this->jobs()->pluck('id')->all());
    }

    public function savedJobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'jb_saved_jobs', 'account_id', 'job_id');
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'jb_account_packages', 'account_id', 'package_id');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function myReviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'created_by');
    }

    public function completedCompanyProfile(): bool
    {
        foreach ($this->companies()->get() as $company) {
            if ($company->completedProfile()) {
                return true;
            }
        }

        return false;
    }

    public function canReview(BaseModel $reviewable): bool
    {
        if ($reviewable instanceof Company) {
            return $this->isJobSeeker() && $this->myReviews()
                ->where('reviewable_id', $reviewable->getKey())
                ->where('reviewable_type', get_class($reviewable))
                ->doesntExist();
        }

        return $this->isEmployer() && $this->companies()->exists();
    }

    public function favoriteSkills(): BelongsToMany
    {
        return $this->belongsToMany(JobSkill::class, 'jb_account_favorite_skills', 'account_id', 'skill_id');
    }

    public function favoriteTags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'jb_account_favorite_tags', 'account_id', 'tag_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(AccountActivityLog::class, 'account_id');
    }

    public function languages(): HasMany
    {
        return $this->hasMany(AccountLanguage::class);
    }

    public function jobAlerts(): HasMany
    {
        return $this->hasMany(JobAlert::class, 'account_id');
    }

    protected function uploadFolder(): Attribute
    {
        return Attribute::make(
            get: function () {
                $folder = $this->id ? 'accounts/' . $this->id : 'accounts';

                return apply_filters('job_board_account_upload_folder', $folder, $this);
            }
        );
    }

    public function languageText(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->languages->isEmpty()) {
                    return '';
                }

                return $this->languages->map(fn ($language) => $language->language_name)->implode(', ');
            }
        );
    }

    protected static function booted(): void
    {
        static::saving(function (Account $account): void {
            $callNumbers = $account->call_numbers ?? [];
            $whatsAppNumbers = $account->whatsapp_numbers ?? [];

            $account->call_numbers = $account->normalizeContactNumbers($callNumbers, $account->phone);
            $account->whatsapp_numbers = $account->normalizeContactNumbers($whatsAppNumbers, $account->whatsapp_number ?: $account->phone);
            $account->phone = $account->call_numbers[0] ?? null;
            $account->whatsapp_number = $account->whatsapp_numbers[0] ?? ($account->phone ?: null);
        });

        static::deleting(function (Account $account): void {
            $account->companies()->detach();
            $account->activityLogs()->delete();
            $account->transactions()->delete();
            $account->applications()->delete();
            $account->reviews()->delete();
            $account->myReviews()->delete();
            $account->savedJobs()->detach();
            $account->packages()->detach();
        });

        static::deleted(function (Account $account): void {
            $folder = Storage::path($account->upload_folder);
            if (File::isDirectory($folder) && Str::endsWith($account->upload_folder, '/' . $account->id)) {
                File::deleteDirectory($folder);
            }
        });
    }

    protected function normalizeContactNumbers(mixed $values, ?string $fallback = null): array
    {
        $values = is_array($values) ? $values : (array) $values;

        if ($fallback !== null && trim($fallback) !== '') {
            $values[] = $fallback;
        }

        $unique = [];
        $normalized = [];

        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            $key = preg_replace('/\D+/', '', $value) ?: mb_strtolower($value);
            if (isset($unique[$key])) {
                continue;
            }

            $unique[$key] = true;
            $normalized[] = $value;
        }

        return array_values($normalized);
    }
}
