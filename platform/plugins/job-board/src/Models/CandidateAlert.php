<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateAlert extends BaseModel
{
    protected $table = 'jb_candidate_alerts';

    private const QUICK_ADD_PRESETS_SETTING = 'candidate_alert_keyword_presets';

    private const DEFAULT_KEYWORD_PRESETS = [
        ['label' => '🎓 Grade 12 / Entry Level', 'keywords' => ['grade 12', 'grade twelve', 'form five', 'O level', 'GCSE', 'school leaver', 'entry level', 'no experience required', 'minimum qualification', 'junior', 'trainee']],
        ['label' => '🎓 Intern / Attachment', 'keywords' => ['intern', 'internship', 'graduate trainee', 'attachment', 'industrial attachment', 'graduate program', 'apprentice', 'trainee', 'vacation work']],
        ['label' => '⏰ Part Time / Casual', 'keywords' => ['part time', 'part-time', 'casual', 'weekend', 'evening', 'flexible hours', 'temporary', 'contract', 'freelance', 'remote']],
        ['label' => '🧹 Service & Hospitality', 'keywords' => ['waiter', 'waitress', 'bartender', 'cleaner', 'housekeeper', 'domestic worker', 'caretaker', 'cook', 'kitchen assistant', 'hotel staff']],
        ['label' => '🔒 Security & General Labour', 'keywords' => ['security guard', 'security officer', 'driver', 'gardener', 'general hand', 'labourer', 'casual worker', 'messenger', 'forklift operator']],
        ['label' => '💰 Accounting & Finance', 'keywords' => ['accountant', 'auditor', 'bookkeeper', 'finance officer', 'accounts clerk', 'financial analyst', 'cashier', 'payroll officer', 'credit analyst']],
        ['label' => '🏦 Banking & Insurance', 'keywords' => ['bank teller', 'banking officer', 'relationship manager', 'underwriter', 'claims officer', 'insurance agent', 'banker']],
        ['label' => '🏥 Nursing & Health', 'keywords' => ['nurse', 'nursing officer', 'clinical officer', 'midwife', 'pharmacist', 'doctor', 'health worker', 'radiographer', 'physiotherapist', 'medical officer']],
        ['label' => '💻 IT & Technology', 'keywords' => ['software developer', 'programmer', 'IT officer', 'systems administrator', 'web developer', 'data analyst', 'network engineer', 'database administrator', 'ICT officer']],
        ['label' => '📚 Education & Teaching', 'keywords' => ['teacher', 'lecturer', 'tutor', 'school administrator', 'early childhood', 'education officer', 'head teacher']],
        ['label' => '⚙️ Engineering', 'keywords' => ['engineer', 'civil engineer', 'electrical engineer', 'mechanical engineer', 'structural engineer', 'project manager', 'quantity surveyor', 'site engineer']],
        ['label' => '👥 HR & Administration', 'keywords' => ['human resources', 'HR officer', 'HR manager', 'recruitment officer', 'administrative officer', 'secretary', 'receptionist', 'office manager']],
        ['label' => '📣 Sales & Marketing', 'keywords' => ['sales representative', 'marketing officer', 'business development', 'sales executive', 'brand ambassador', 'sales manager', 'digital marketing']],
        ['label' => '⚖️ Legal', 'keywords' => ['lawyer', 'advocate', 'legal officer', 'paralegal', 'legal assistant', 'compliance officer', 'attorney']],
        ['label' => '🚚 Supply Chain & Logistics', 'keywords' => ['procurement officer', 'supply chain', 'logistics officer', 'warehouse officer', 'inventory manager', 'purchasing officer', 'stores officer']],
    ];

    protected $fillable = [
        'label',
        'candidate_name',
        'candidate_phone',
        'candidate_phone_2',
        'candidate_email',
        'account_id',
        'filters',
        'duration_days',
        'price',
        'is_active',
        'status',
        'activated_at',
        'expires_at',
        'expiry_warning_sent',
        'expiry_sameday_sent',
        'expiry_notice_sent',
        'notes',
        'cv_path',
        'cv_analysis',
    ];

    protected $casts = [
        'filters'             => 'array',
        'is_active'           => 'bool',
        'account_id'          => 'integer',
        'expiry_warning_sent'  => 'bool',
        'expiry_sameday_sent'  => 'bool',
        'expiry_notice_sent'   => 'bool',
        'activated_at'        => 'datetime',
        'expires_at'          => 'datetime',
        'price'               => 'decimal:2',
        'cv_analysis'         => 'array',
    ];

    public static array $durations = [
        7  => ['label' => '1 Week',   'price' => 40.00,  'badge' => 'bg-info text-white'],
        30 => ['label' => '1 Month',  'price' => 100.00, 'badge' => 'bg-primary text-white'],
        60 => ['label' => '2 Months', 'price' => 150.00, 'badge' => 'bg-success text-white'],
    ];

    public static function defaultKeywordPresets(): array
    {
        return self::DEFAULT_KEYWORD_PRESETS;
    }

    public static function keywordPresets(): array
    {
        $saved = json_decode((string) setting(self::QUICK_ADD_PRESETS_SETTING, ''), true);

        if (! is_array($saved)) {
            return self::DEFAULT_KEYWORD_PRESETS;
        }

        $presets = self::normalizeKeywordPresets($saved);

        return $presets ?: self::DEFAULT_KEYWORD_PRESETS;
    }

    public static function saveKeywordPresets(array $presets): void
    {
        setting()->set(self::QUICK_ADD_PRESETS_SETTING, json_encode(
            self::normalizeKeywordPresets($presets),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
        setting()->save();
    }

    public static function normalizeKeywordPresets(array $presets): array
    {
        $normalized = [];

        foreach ($presets as $preset) {
            $label = trim((string) ($preset['label'] ?? ''));
            $keywords = $preset['keywords'] ?? [];

            if (is_string($keywords)) {
                $keywords = preg_split('/[\r\n,]+/', $keywords) ?: [];
            }

            $keywords = array_values(array_unique(array_filter(array_map(
                fn ($keyword) => trim((string) $keyword),
                (array) $keywords
            ))));

            if ($label === '' || $keywords === []) {
                continue;
            }

            $normalized[] = [
                'label' => mb_substr($label, 0, 80),
                'keywords' => array_slice($keywords, 0, 40),
            ];
        }

        return array_slice($normalized, 0, 40);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CandidateAlertLog::class, 'candidate_alert_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function cvAnalysisLogs(): HasMany
    {
        return $this->hasMany(CandidateAlertCvAnalysisLog::class, 'candidate_alert_id');
    }

    public function cvBuilderSessions(): HasMany
    {
        return $this->hasMany(CandidateAlertCvBuilderSession::class, 'candidate_alert_id');
    }

    public function daysRemaining(): int
    {
        if (! $this->expires_at || $this->status === 'expired') {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->expires_at, false));
    }

    public function recipientJid(): string
    {
        return preg_replace('/\D/', '', $this->candidate_phone) . '@s.whatsapp.net';
    }

    /**
     * All WhatsApp JIDs alerts should be sent to (primary + optional second number).
     */
    public function recipientJids(): array
    {
        $jids = [];

        foreach ([$this->candidate_phone, $this->candidate_phone_2] as $phone) {
            $phone = trim((string) $phone);
            if ($phone === '') {
                continue;
            }

            $jids[] = preg_replace('/\D/', '', $phone) . '@s.whatsapp.net';
        }

        return array_values(array_unique($jids));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    public function hasLinkedAccountCv(): bool
    {
        return (bool) ($this->account?->resume);
    }

    public function hasStoredCv(): bool
    {
        return $this->cv_path !== null || $this->hasLinkedAccountCv();
    }

    public function cvSourceLabel(): ?string
    {
        if ($this->hasLinkedAccountCv()) {
            return 'account';
        }

        if ($this->cv_path) {
            return 'vip';
        }

        return null;
    }

    public function cvDisplayName(): ?string
    {
        if ($this->hasLinkedAccountCv()) {
            return $this->account?->resume_name ?: basename((string) $this->account?->resume);
        }

        return $this->cv_path ? basename($this->cv_path) : null;
    }
}
