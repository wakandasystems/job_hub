<?php

namespace Botble\JobBoard\Http\Controllers\Settings;

use Botble\JobBoard\Models\AiImageGenerationLog;
use Botble\JobBoard\Services\OpenAiImageService;
use Botble\Setting\Http\Controllers\SettingController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AiImageSettingController extends SettingController
{
    /** Platforms eligible for automatic generation (slot type => label). */
    public const PLATFORMS = [
        'cover_image'    => 'Job Cover',
        'tiktok_image'   => 'TikTok',
        'whatsapp_image' => 'WhatsApp',
        'facebook_image' => 'Facebook',
        'linkedin_image' => 'LinkedIn',
    ];

    public const MODEL_OPTIONS = [
        'gpt-image-2'   => 'GPT Image 2',
        'gpt-image-1.5' => 'GPT Image 1.5',
        'gpt-image-1'   => 'GPT Image 1',
    ];

    public const QUALITY_OPTIONS = [
        'auto'   => 'Auto',
        'high'   => 'High',
        'medium' => 'Medium',
        'low'    => 'Low',
    ];

    public const OUTPUT_FORMAT_OPTIONS = [
        'png'  => 'PNG',
        'jpeg' => 'JPEG',
        'webp' => 'WebP',
    ];

    public const BACKGROUND_OPTIONS = [
        'opaque' => 'Opaque',
        'auto'   => 'Auto',
    ];

    public function edit(Request $request)
    {
        $this->pageTitle('AI Image Generation');

        $countries = DB::table('countries')->orderBy('name')->pluck('name', 'id');

        $settings = [
            'openai_api_key'                     => (string) setting('openai_api_key', ''),
            'ai_social_image_enabled'            => (bool) setting('ai_social_image_enabled', false),
            'ai_social_image_country_ids'        => $this->decodeIds(setting('ai_social_image_country_ids', '')),
            'ai_social_image_without_logo'       => (bool) setting('ai_social_image_without_logo', false),
            'ai_social_image_skip_multi_position' => (bool) setting('ai_social_image_skip_multi_position', true),
            'ai_social_image_reuse_selected_platform_images' => (bool) setting('ai_social_image_reuse_selected_platform_images', false),
            'ai_social_image_platforms'          => $this->decodePlatforms(setting('ai_social_image_platforms', '')),
            'ai_social_image_model'              => $this->decodeOption(
                setting('ai_social_image_model', ''),
                array_keys(self::MODEL_OPTIONS),
                'gpt-image-2'
            ),
            'ai_social_image_quality'            => $this->decodeOption(
                setting('ai_social_image_quality', ''),
                array_keys(self::QUALITY_OPTIONS),
                'high'
            ),
            'ai_social_image_output_format'      => $this->decodeOption(
                setting('ai_social_image_output_format', ''),
                array_keys(self::OUTPUT_FORMAT_OPTIONS),
                'png'
            ),
            'ai_social_image_background'         => $this->decodeOption(
                setting('ai_social_image_background', ''),
                array_keys(self::BACKGROUND_OPTIONS),
                'opaque'
            ),
            'ai_social_image_output_compression' => max(0, min(100, (int) setting('ai_social_image_output_compression', 10))),
        ];

        $platforms = self::PLATFORMS;
        $models = self::MODEL_OPTIONS;
        $qualities = self::QUALITY_OPTIONS;
        $outputFormats = self::OUTPUT_FORMAT_OPTIONS;
        $backgrounds = self::BACKGROUND_OPTIONS;
        $logFilters = $this->logFilters($request);
        $logsQuery = $this->logsQuery($logFilters);
        $logs = $logsQuery
            ->with([
                'job:id,name',
                'company:id,name',
            ])
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();
        $stats = $this->buildStats(clone $logsQuery);

        return view('plugins/job-board::settings.ai-images', compact(
            'countries',
            'settings',
            'platforms',
            'models',
            'qualities',
            'outputFormats',
            'backgrounds',
            'logs',
            'stats',
            'logFilters'
        ));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'openai_api_key'                      => ['nullable', 'string', 'max:255'],
            'ai_social_image_enabled'             => ['nullable', 'boolean'],
            'ai_social_image_without_logo'        => ['nullable', 'boolean'],
            'ai_social_image_skip_multi_position' => ['nullable', 'boolean'],
            'ai_social_image_reuse_selected_platform_images' => ['nullable', 'boolean'],
            'country_ids'                         => ['nullable', 'array'],
            'country_ids.*'                       => ['integer'],
            'platforms'                           => ['nullable', 'array'],
            'platforms.*'                         => ['string'],
            'ai_social_image_model'               => ['nullable', 'string'],
            'ai_social_image_quality'             => ['nullable', 'string'],
            'ai_social_image_output_format'       => ['nullable', 'string'],
            'ai_social_image_background'          => ['nullable', 'string'],
            'ai_social_image_output_compression'  => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        // Only overwrite the key when a non-empty value is submitted (avoid wiping it on blank).
        if (! empty($data['openai_api_key'])) {
            setting()->set('openai_api_key', trim($data['openai_api_key']));
        }

        $countryIds = array_values(array_unique(array_map('intval', $data['country_ids'] ?? [])));
        $platforms  = array_values(array_intersect(array_keys(self::PLATFORMS), $data['platforms'] ?? []));
        $model      = $this->decodeOption($data['ai_social_image_model'] ?? '', array_keys(self::MODEL_OPTIONS), 'gpt-image-2');
        $quality    = $this->decodeOption($data['ai_social_image_quality'] ?? '', array_keys(self::QUALITY_OPTIONS), 'high');
        $format     = $this->decodeOption($data['ai_social_image_output_format'] ?? '', array_keys(self::OUTPUT_FORMAT_OPTIONS), 'png');
        $background = $this->decodeOption($data['ai_social_image_background'] ?? '', array_keys(self::BACKGROUND_OPTIONS), 'opaque');
        $compression = max(0, min(100, (int) ($data['ai_social_image_output_compression'] ?? 10)));

        setting()->set('ai_social_image_enabled', ! empty($data['ai_social_image_enabled']) ? '1' : '0');
        setting()->set('ai_social_image_country_ids', json_encode($countryIds));
        setting()->set('ai_social_image_without_logo', ! empty($data['ai_social_image_without_logo']) ? '1' : '0');
        setting()->set('ai_social_image_skip_multi_position', ! empty($data['ai_social_image_skip_multi_position']) ? '1' : '0');
        setting()->set('ai_social_image_reuse_selected_platform_images', ! empty($data['ai_social_image_reuse_selected_platform_images']) ? '1' : '0');
        setting()->set('ai_social_image_platforms', json_encode($platforms));
        setting()->set('ai_social_image_model', $model);
        setting()->set('ai_social_image_quality', $quality);
        setting()->set('ai_social_image_output_format', $format);
        setting()->set('ai_social_image_background', $background);
        setting()->set('ai_social_image_output_compression', (string) $compression);

        setting()->save();

        return $this->httpResponse()
            ->setNextUrl(route('job-board.settings.ai-images'))
            ->setMessage('AI image generation settings saved successfully.');
    }

    private function decodeIds(mixed $value): array
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;

        return is_array($decoded) ? array_map('intval', $decoded) : [];
    }

    private function decodePlatforms(mixed $value): array
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;

        return is_array($decoded) ? array_values(array_intersect(array_keys(self::PLATFORMS), $decoded)) : [];
    }

    private function decodeOption(mixed $value, array $allowed, string $default): string
    {
        $value = trim((string) $value);

        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function logFilters(Request $request): array
    {
        return [
            'status' => trim((string) $request->query('log_status', '')),
            'slot_type' => trim((string) $request->query('log_slot_type', '')),
            'model' => trim((string) $request->query('log_model', '')),
            'source_type' => trim((string) $request->query('log_source_type', '')),
            'job_id' => $request->filled('log_job_id') ? (int) $request->query('log_job_id') : null,
            'date_from' => trim((string) $request->query('log_date_from', '')),
            'date_to' => trim((string) $request->query('log_date_to', '')),
        ];
    }

    private function logsQuery(array $filters): Builder
    {
        return AiImageGenerationLog::query()
            ->when($filters['status'] !== '', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['slot_type'] !== '', fn (Builder $query) => $query->where('slot_type', $filters['slot_type']))
            ->when($filters['model'] !== '', fn (Builder $query) => $query->where('model', $filters['model']))
            ->when($filters['source_type'] !== '', fn (Builder $query) => $query->where('source_type', $filters['source_type']))
            ->when($filters['job_id'], fn (Builder $query, int $jobId) => $query->where('job_id', $jobId))
            ->when($filters['date_from'] !== '', function (Builder $query) use ($filters): void {
                try {
                    $query->whereDate('created_at', '>=', Carbon::parse($filters['date_from'])->toDateString());
                } catch (\Throwable) {
                }
            })
            ->when($filters['date_to'] !== '', function (Builder $query) use ($filters): void {
                try {
                    $query->whereDate('created_at', '<=', Carbon::parse($filters['date_to'])->toDateString());
                } catch (\Throwable) {
                }
            });
    }

    private function buildStats(Builder $query): array
    {
        $rows = (clone $query)->get([
            'status',
            'latency_ms',
            'estimated_cost_usd',
            'input_tokens',
            'input_text_tokens',
            'input_image_tokens',
            'output_tokens',
            'total_tokens',
        ]);

        $totalAttempts = $rows->count();
        $successes = $rows->where('status', 'success')->count();
        $failures = $rows->where('status', 'failed')->count();
        $avgLatency = $rows->whereNotNull('latency_ms')->avg('latency_ms');

        return [
            'total_attempts' => $totalAttempts,
            'successes' => $successes,
            'failures' => $failures,
            'success_rate' => $totalAttempts > 0 ? round(($successes / $totalAttempts) * 100, 1) : null,
            'avg_latency_ms' => $avgLatency !== null ? (int) round($avgLatency) : null,
            'estimated_cost_usd' => round((float) $rows->sum(fn ($row) => (float) ($row->estimated_cost_usd ?? 0)), 6),
            'input_tokens' => (int) $rows->sum(fn ($row) => (int) ($row->input_tokens ?? 0)),
            'input_text_tokens' => (int) $rows->sum(fn ($row) => (int) ($row->input_text_tokens ?? 0)),
            'input_image_tokens' => (int) $rows->sum(fn ($row) => (int) ($row->input_image_tokens ?? 0)),
            'output_tokens' => (int) $rows->sum(fn ($row) => (int) ($row->output_tokens ?? 0)),
            'total_tokens' => (int) $rows->sum(fn ($row) => (int) ($row->total_tokens ?? 0)),
        ];
    }
}
