<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Models\AiImageGenerationLog;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SalesAgent;
use Botble\JobBoard\Models\SalesAgentCampaign;
use Botble\Media\Facades\RvMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OpenAiImageService
{
    private const ENDPOINT = 'https://api.openai.com/v1/images/edits';

    // Wakanda Jobs brand logos (same assets referenced by SocialPublisherService::wakandaLogoLine()).
    private const WJ_LOGOS = [
        'https://www.wakandajobs.com/storage/gemini-generated-image-s1e9dgs1e9dgs1e9.png',
        'https://www.wakandajobs.com/storage/chatgpt-image-may-14-2026-03-00-04-pm.png',
    ];

    /** Slot type → storage folder (mirrors TelegramSocialMessageController::upload()). */
    private const FOLDERS = [
        'cover_image'    => 'job-covers',
        'tiktok_image'   => 'job-social/tiktok',
        'facebook_image' => 'job-social/facebook',
        'linkedin_image' => 'job-social/linkedin',
        'whatsapp_image' => 'job-social/whatsapp',
        'twitter_image'  => 'job-social/twitter',
        'employer_image' => 'job-social/employer',
    ];

    private const AUTO_PLATFORM_FIELDS = [
        'tiktok_image',
        'whatsapp_image',
        'facebook_image',
        'linkedin_image',
    ];

    /** Slot type → legacy GPT Image sizes. */
    private const LEGACY_SIZES = [
        'cover_image'    => '1536x1024',
        'tiktok_image'   => '1024x1536',
        'whatsapp_image' => '1024x1536',
        'facebook_image' => '1536x1024',
        'linkedin_image' => '1536x1024',
        'twitter_image'  => '1536x1024',
        'employer_image' => '1024x1536',
    ];

    /** Slot type → final exported dimensions used by the product/UI. */
    private const TARGET_DIMENSIONS = [
        'cover_image'    => [1800, 540],
        'tiktok_image'   => [1080, 1920],
        'whatsapp_image' => [1080, 1920],
        'facebook_image' => [1200, 630],
        'linkedin_image' => [1200, 627],
        'twitter_image'  => [1200, 675],
        'employer_image' => [1080, 1920],
    ];

    /** Slot type → GPT Image 2 request size chosen to be close to final export size. */
    private const GPT_IMAGE_2_SIZES = [
        'cover_image'    => '1536x512',
        'tiktok_image'   => '1088x1920',
        'whatsapp_image' => '1088x1920',
        'facebook_image' => '1216x640',
        'linkedin_image' => '1216x640',
        'twitter_image'  => '1216x688',
        'employer_image' => '1088x1920',
    ];

    private const GPT_IMAGE_2_PRICING = [
        'input_text_per_million' => 5.00,
        'input_image_per_million' => 8.00,
        'output_per_million' => 30.00,
    ];

    private const LEGACY_OUTPUT_PRICING = [
        'gpt-image-1.5' => [
            '1024x1024' => ['low' => 0.009, 'medium' => 0.034, 'high' => 0.133],
            '1024x1536' => ['low' => 0.013, 'medium' => 0.050, 'high' => 0.200],
            '1536x1024' => ['low' => 0.013, 'medium' => 0.050, 'high' => 0.200],
        ],
        'gpt-image-1' => [
            '1024x1024' => ['low' => 0.011, 'medium' => 0.042, 'high' => 0.167],
            '1024x1536' => ['low' => 0.016, 'medium' => 0.063, 'high' => 0.250],
            '1536x1024' => ['low' => 0.016, 'medium' => 0.063, 'high' => 0.250],
        ],
    ];

    private const SALES_AGENT_POSTER_FOLDER = 'sales-agents/posters';

    private const SALES_AGENT_SIZES = [
        'portrait_4_5'   => '1024x1536',
        'square_1_1'     => '1024x1024',
        'landscape_16_9' => '1536x1024',
    ];

    public function __construct(private SocialPublisherService $publisher)
    {
    }

    public function apiKey(): string
    {
        return trim((string) (setting('openai_api_key') ?: env('OPENAI_API_KEY', '')));
    }

    public function isConfigured(): bool
    {
        return $this->apiKey() !== '';
    }

    /**
     * Does this specific job pass the country/logo/multi-position gates used to decide
     * whether it gets an AI social image at all? Shared by GenerateSocialImagesCommand
     * (to decide whether to generate) and SendPushNotificationListener (to decide whether
     * to wait for the image before pushing, instead of pushing immediately).
     */
    public function qualifiesForJob(Job $job): bool
    {
        $countryIds = json_decode((string) setting('ai_social_image_country_ids', '[]'), true) ?: [];
        if (! in_array((int) $job->country_id, array_map('intval', $countryIds), true)) {
            return false;
        }

        $hasLogo = $job->company && ! empty($job->company->logo);
        if (! $hasLogo && ! setting('ai_social_image_without_logo')) {
            return false;
        }

        if (setting('ai_social_image_skip_multi_position') && $this->isMultiPositionTitle((string) $job->name)) {
            return false;
        }

        return true;
    }

    public function model(): string
    {
        $model = trim((string) setting('ai_social_image_model', ''));
        $allowed = ['gpt-image-2', 'gpt-image-1.5', 'gpt-image-1'];

        return in_array($model, $allowed, true) ? $model : 'gpt-image-2';
    }

    public function quality(): string
    {
        $quality = trim((string) setting('ai_social_image_quality', ''));
        $allowed = ['auto', 'high', 'medium', 'low'];

        return in_array($quality, $allowed, true) ? $quality : 'high';
    }

    public function outputFormat(): string
    {
        $format = trim((string) setting('ai_social_image_output_format', ''));
        $allowed = ['png', 'jpeg', 'webp'];

        return in_array($format, $allowed, true) ? $format : 'png';
    }

    public function background(): string
    {
        $background = trim((string) setting('ai_social_image_background', ''));
        $allowed = ['opaque', 'auto'];

        return in_array($background, $allowed, true) ? $background : 'opaque';
    }

    public function outputCompression(): int
    {
        return max(0, min(100, (int) setting('ai_social_image_output_compression', 10)));
    }

    /**
     * @return array{
     *   ok: bool,
     *   prompt_template?: string,
     *   summary?: array<string, mixed>,
     *   message?: string
     * }
     */
    public function analyzeSalesAgentInspiration(UploadedFile $file): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'OpenAI API key is not configured.'];
        }

        $path = $file->getRealPath();

        if (! $path || ! is_file($path)) {
            return ['ok' => false, 'message' => 'Uploaded image could not be read.'];
        }

        $mime = $file->getMimeType() ?: 'image/jpeg';
        $bytes = @file_get_contents($path);

        if ($bytes === false || $bytes === '') {
            return ['ok' => false, 'message' => 'Uploaded image could not be read.'];
        }

        return $this->analyzeSalesAgentInspirationBytes($bytes, $mime);
    }

    /**
     * @return array{
     *   ok: bool,
     *   prompt_template?: string,
     *   summary?: array<string, mixed>,
     *   editable_regions?: array<string, mixed>,
     *   message?: string
     * }
     */
    private function analyzeSalesAgentInspirationBytes(string $bytes, string $mime): array
    {
        $payload = [
            'model' => 'gpt-4o',
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You analyze a poster image and produce a Wakanda Jobs sales-agent campaign prompt template. Return JSON only.',
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => <<<'TEXT'
Analyze this poster as a reusable inspiration reference for Wakanda Jobs sales-agent campaigns.

Your goals:
1. Identify the poster's composition, hierarchy, style, color direction, typography mood, text zones, badge styles, CTA placement, logo placement, and visual rhythm.
2. Generate a prompt_template that will help an image model recreate THIS SAME STYLE and LAYOUT with 90-100% fidelity while changing only:
- the person to either Nakia, the selected agent, or both
- all poster text to Wakanda campaign placeholders
- the branding to Wakanda Jobs
3. The prompt_template must be ready to save directly into our campaign prompt field and must use these placeholders where relevant:
{campaign_name}
{product_label}
{landing_headline}
{landing_body}
{cta}
{price_line}
{auto_apply_plan_summary}
{auto_apply_plan_cards}
{promo_deadline_line}
{promo_badge}
{headline_zone}
{body_zone}
{price_zone}
{cta_zone}
{logo_zone}
{text_layout_brief}

Rules for prompt_template:
- It must treat the uploaded poster as a recreation master, not a vague inspiration.
- It must explicitly tell the model to preserve the same composition, same palette, same text hierarchy, same spacing rhythm, same badge/button styles, and same background treatment.
- It must explicitly say: change only the person, branding, and text.
- It must tell the model to fully replace all visible text from the source poster with campaign placeholders while keeping the same design system.
- It must describe all detectable design details from the image, including likely text blocks, likely offer chip positions, likely CTA zone, likely logo zone, dominant colors, accent colors, gradients, shadows, texture, and typographic feel.
- It must explicitly say to preserve the inspiration poster's style, layout, spacing, typography mood, background treatment, and text hierarchy.
- It must NOT mention any foreign brand from the reference image.
- It must tell the model to replace all visible text with campaign placeholders.
- It must leave the human subject to the separate subject-mode instructions.
- It must explicitly describe editable zones so we can reconstruct the poster by editing only those regions.
- It must be detailed and production-ready, not a short summary.

Return strict JSON with this shape:
{
  "summary": {
    "layout": "...",
    "style": "...",
    "colors": "...",
    "typography": "...",
    "text_zones": ["...", "..."],
    "cta_zone": "...",
    "logo_zone": "...",
    "key_elements": ["...", "..."],
    "background_treatment": "...",
    "offer_treatment": "...",
    "spacing_rhythm": "...",
    "image_crop_style": "..."
  },
  "editable_regions": {
    "subject_box": {"x": 0, "y": 0, "w": 0, "h": 0},
    "headline_box": {"x": 0, "y": 0, "w": 0, "h": 0},
    "body_box": {"x": 0, "y": 0, "w": 0, "h": 0},
    "price_box": {"x": 0, "y": 0, "w": 0, "h": 0},
    "cta_box": {"x": 0, "y": 0, "w": 0, "h": 0},
    "logo_box": {"x": 0, "y": 0, "w": 0, "h": 0},
    "extra_text_boxes": [
      {"x": 0, "y": 0, "w": 0, "h": 0}
    ]
  },
  "prompt_template": "..."
}

Editable region rules:
- Use normalized coordinates on a 0..1000 scale.
- Every box must target the source poster's current area that should be changed or repainted.
- subject_box must cover the main human subject area.
- headline/body/price/cta/logo boxes must cover the visible text or brand elements currently present in the source image.
- extra_text_boxes should include any remaining text chips, footer lines, eyebrow labels, promo bursts, phone numbers, or websites that also need replacement.
TEXT,
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:' . $mime . ';base64,' . base64_encode($bytes),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::timeout(120)
                ->withToken($this->apiKey())
                ->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', $payload);
        } catch (\Throwable $e) {
            Log::warning('Sales-agent inspiration analysis failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Could not reach OpenAI for inspiration analysis.'];
        }

        if (! $response->successful()) {
            Log::warning('Sales-agent inspiration analysis request failed', [
                'status' => $response->status(),
                'body_excerpt' => Str::limit($response->body(), 500, ''),
            ]);

            return ['ok' => false, 'message' => 'OpenAI inspiration analysis failed.'];
        }

        $decoded = json_decode((string) $response->json('choices.0.message.content', ''), true);

        if (! is_array($decoded) || ! is_string($decoded['prompt_template'] ?? null)) {
            Log::warning('Sales-agent inspiration analysis returned invalid JSON', [
                'content_excerpt' => Str::limit((string) $response->json('choices.0.message.content', ''), 500, ''),
            ]);

            return ['ok' => false, 'message' => 'OpenAI returned an invalid inspiration analysis response.'];
        }

        return [
            'ok' => true,
            'prompt_template' => trim((string) $decoded['prompt_template']),
            'summary' => is_array($decoded['summary'] ?? null) ? $decoded['summary'] : [],
            'editable_regions' => is_array($decoded['editable_regions'] ?? null) ? $decoded['editable_regions'] : [],
        ];
    }

    public static function slotTypes(): array
    {
        return array_keys(self::FOLDERS);
    }

    public function shouldReuseSelectedPlatformImages(): bool
    {
        return (bool) setting('ai_social_image_reuse_selected_platform_images', false);
    }

    /**
     * Generate one social image for a job via gpt-image-1, store it, and persist the path on the job.
     *
     * @return array{ok: bool, url?: string, path?: string, message?: string}
     */
    public function generateForJob(Job $job, string $type): array
    {
        if (! isset(self::FOLDERS[$type])) {
            return ['ok' => false, 'message' => 'Invalid image type.'];
        }

        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'OpenAI API key is not configured.'];
        }

        $startedAt = microtime(true);
        $model = $this->model();
        $format = $this->outputFormat();
        $background = $this->background();
        $quality = $this->quality();
        $size = $this->sizeFor($type, $model);
        [$targetWidth, $targetHeight] = self::TARGET_DIMENSIONS[$type] ?? [null, null];

        try {
            $prompt = $this->buildPrompt($job, $type);
        } catch (\Throwable $e) {
            $this->storeLog($job, [
                'slot_type' => $type,
                'status' => 'failed',
                'model' => $model,
                'quality' => $quality,
                'background' => $background,
                'output_format' => $format,
                'output_compression' => $this->outputCompression(),
                'request_size' => $size,
                'target_width' => $targetWidth,
                'target_height' => $targetHeight,
                'latency_ms' => $this->elapsedMs($startedAt),
                'error_message' => 'Could not build prompt: ' . $e->getMessage(),
            ]);

            return ['ok' => false, 'message' => 'Could not build prompt: ' . $e->getMessage()];
        }

        if (trim($prompt) === '') {
            return ['ok' => false, 'message' => 'Empty prompt for this job.'];
        }

        $references = $this->referenceImages($job);
        if (empty($references)) {
            $this->storeLog($job, [
                'slot_type' => $type,
                'status' => 'failed',
                'model' => $model,
                'quality' => $quality,
                'background' => $background,
                'output_format' => $format,
                'output_compression' => $this->outputCompression(),
                'request_size' => $size,
                'target_width' => $targetWidth,
                'target_height' => $targetHeight,
                'latency_ms' => $this->elapsedMs($startedAt),
                'error_message' => 'No reference logos could be loaded.',
            ]);

            return ['ok' => false, 'message' => 'No reference logos could be loaded.'];
        }

        try {
            $request = Http::withToken($this->apiKey())->timeout(180);

            foreach ($references as $i => $ref) {
                $request = $request->attach('image[]', $ref['content'], $ref['filename']);
            }

            $payload = [
                'model'  => $model,
                'prompt' => $prompt,
                'size'   => $size,
                'n'      => 1,
                'quality' => $quality,
                'background' => $background,
                'output_format' => $format,
            ];

            if (in_array($format, ['jpeg', 'webp'], true)) {
                $payload['output_compression'] = $this->outputCompression();
            }

            $response = $request->post(self::ENDPOINT, $payload);

            if (! $response->successful()) {
                $apiMessage = $response->json('error.message') ?: ('HTTP ' . $response->status());
                $latencyMs = $this->elapsedMs($startedAt);
                $responseMeta = $this->buildResponseMeta($response->json(), $response->headers(), $references, $prompt, $type, $latencyMs);
                Log::error('OpenAI image generation failed', [
                    'job_id' => $job->getKey(),
                    'type'   => $type,
                    'model'  => $model,
                    'size'   => $size,
                    'quality' => $quality,
                    'status' => $response->status(),
                    'error'  => $apiMessage,
                ]);

                $this->storeLog($job, [
                    'slot_type' => $type,
                    'status' => 'failed',
                    'model' => $model,
                    'quality' => $quality,
                    'background' => $background,
                    'output_format' => $format,
                    'output_compression' => $this->outputCompression(),
                    'request_size' => $size,
                    'target_width' => $targetWidth,
                    'target_height' => $targetHeight,
                    'latency_ms' => $latencyMs,
                    'api_request_id' => $response->header('x-request-id'),
                    'error_message' => 'OpenAI error: ' . $apiMessage,
                    'response_meta' => $responseMeta,
                ]);

                return [
                    'ok' => false,
                    'message' => 'OpenAI error: ' . $apiMessage,
                    'status' => $response->status(),
                    // 429 = rate limited. Surface the suggested cooldown so the caller can
                    // wait for the limit to cool and retry instead of posting image-less.
                    'rate_limited' => $response->status() === 429,
                    'retry_after' => (int) ($response->header('retry-after') ?: 0),
                ];
            }

            $b64 = $response->json('data.0.b64_json');
            if (! $b64) {
                return ['ok' => false, 'message' => 'OpenAI returned no image data.'];
            }

            $binary = base64_decode($b64, true);
            if ($binary === false || $binary === '') {
                return ['ok' => false, 'message' => 'Could not decode generated image.'];
            }

            $folder = self::FOLDERS[$type];
            $extension = $this->extensionForFormat($format);
            $path   = $folder . '/' . Str::random(40) . '.' . $extension;
            $finalBinary = $this->normalizeBinary($binary, $type, $format);

            if ($finalBinary === null || $finalBinary === '') {
                $this->storeLog($job, [
                    'slot_type' => $type,
                    'status' => 'failed',
                    'model' => $model,
                    'quality' => $quality,
                    'background' => $background,
                    'output_format' => $format,
                    'output_compression' => $this->outputCompression(),
                    'request_size' => $size,
                    'target_width' => $targetWidth,
                    'target_height' => $targetHeight,
                    'latency_ms' => $this->elapsedMs($startedAt),
                    'api_request_id' => $response->header('x-request-id'),
                    'error_message' => 'Could not prepare generated image for storage.',
                    'response_meta' => $this->buildResponseMeta($response->json(), $response->headers(), $references, $prompt, $type, $this->elapsedMs($startedAt)),
                ]);

                return ['ok' => false, 'message' => 'Could not prepare generated image for storage.'];
            }

            Storage::disk('public')->put($path, $finalBinary);

            $job->{$type} = $path;
            $job->save();

            $variants = $this->generateVariants($path, $format);
            if ($variants !== []) {
                // Atomic JSON_SET instead of read-merge-write — when cover/whatsapp/tiktok
                // are all generated for the same job around the same time, each runs in its
                // own queued job with its own stale in-memory copy of image_variants, and a
                // plain merge+save lets whichever one saves last clobber the others' entries.
                DB::statement(
                    'update jb_jobs set image_variants = JSON_SET(COALESCE(image_variants, JSON_OBJECT()), ?, CAST(? AS JSON)) where id = ?',
                    ['$.' . json_encode($type), json_encode($variants), $job->getKey()]
                );
            }

            $latencyMs = $this->elapsedMs($startedAt);
            $responseJson = $response->json();
            $usage = $this->extractUsage($responseJson);
            $cost = $this->estimateCost($model, $quality, $size, $usage);
            $responseMeta = $this->buildResponseMeta($responseJson, $response->headers(), $references, $prompt, $type, $latencyMs, $cost);

            $this->storeLog($job, [
                'slot_type' => $type,
                'status' => 'success',
                'model' => $model,
                'quality' => $quality,
                'background' => $background,
                'output_format' => $format,
                'output_compression' => $this->outputCompression(),
                'request_size' => $size,
                'target_width' => $targetWidth,
                'target_height' => $targetHeight,
                'stored_path' => $path,
                'latency_ms' => $latencyMs,
                'input_tokens' => $usage['input_tokens'],
                'input_text_tokens' => $usage['input_text_tokens'],
                'input_image_tokens' => $usage['input_image_tokens'],
                'output_tokens' => $usage['output_tokens'],
                'total_tokens' => $usage['total_tokens'],
                'estimated_cost_usd' => $cost['total_cost_usd'],
                'api_request_id' => $response->header('x-request-id'),
                'response_meta' => $responseMeta,
            ]);

            return [
                'ok'   => true,
                'url'  => Storage::disk('public')->url($path),
                'path' => $path,
                'lqip' => $variants['lqip'] ?? null,
            ];
        } catch (\Throwable $e) {
            $latencyMs = $this->elapsedMs($startedAt);
            Log::error('OpenAI image generation exception', [
                'job_id' => $job->getKey(),
                'type'   => $type,
                'model'  => $this->model(),
                'error'  => $e->getMessage(),
            ]);

            $this->storeLog($job, [
                'slot_type' => $type,
                'status' => 'failed',
                'model' => $model,
                'quality' => $quality,
                'background' => $background,
                'output_format' => $format,
                'output_compression' => $this->outputCompression(),
                'request_size' => $size,
                'target_width' => $targetWidth,
                'target_height' => $targetHeight,
                'latency_ms' => $latencyMs,
                'error_message' => 'Server error: ' . $e->getMessage(),
            ]);

            return ['ok' => false, 'message' => 'Server error: ' . $e->getMessage()];
        }
    }

    /**
     * Generate a marketing poster for a sales agent's campaign via the same image-edits
     * endpoint used for job images. $subjectMode picks which face is used as the photo
     * reference: the shared "Nakia" persona (default, consistent across every agent),
     * the agent's own uploaded photo, or both blended together.
     *
     * @return array{ok: bool, path?: string, url?: string, cost_usd?: float, message?: string}
     */
    public function generateForSalesAgentPoster(SalesAgent $agent, SalesAgentCampaign $campaign, string $subjectMode = 'nakia'): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'OpenAI API key is not configured.'];
        }

        $preview = $this->previewSalesAgentPoster($agent, $campaign, $subjectMode);
        $prompt = $preview['prompt'] ?? '';

        if (trim($prompt) === '') {
            return ['ok' => false, 'message' => 'Empty prompt template for this campaign.'];
        }

        if (! ($preview['ok'] ?? false)) {
            return ['ok' => false, 'message' => $preview['message'] ?? 'Image references are not ready.'];
        }

        $resolvedReferences = $this->resolveSalesAgentReferences($agent, $campaign, $subjectMode);
        $reconstruction = $this->prepareSalesAgentReconstructionAssets($campaign);
        $references = array_values(array_map(
            static fn (array $reference): array => [
                'content' => $reference['content'],
                'filename' => $reference['filename'],
                'key' => $reference['key'],
            ],
            array_filter($resolvedReferences, static function (array $reference) use ($reconstruction): bool {
                if (! $reference['available'] || $reference['content'] === null) {
                    return false;
                }

                if ($reconstruction && Str::startsWith($reference['key'], 'campaign_inspiration_')) {
                    return false;
                }

                return true;
            })
        ));

        if (empty($references)) {
            return [
                'ok' => false,
                'message' => 'No reference images could be loaded. Upload the Nakia photo/logo under Sales Agents → Marketing Campaigns, or a photo on this agent.',
            ];
        }

        $model = $this->model();
        $format = $this->outputFormat();
        $quality = $this->quality();
        $background = $this->background();
        $size = self::SALES_AGENT_SIZES[$campaign->aspect_ratio] ?? '1024x1536';

        try {
            $request = Http::withToken($this->apiKey())->timeout(180);

            if ($reconstruction) {
                $request = $request->attach('image[]', $reconstruction['image_bytes'], $reconstruction['filename']);
                $request = $request->attach('mask', $reconstruction['mask_bytes'], $reconstruction['mask_filename']);
            }

            foreach ($references as $ref) {
                $request = $request->attach('image[]', $ref['content'], $ref['filename']);
            }

            $payload = [
                'model'         => $model,
                'prompt'        => $prompt,
                'size'          => $size,
                'n'             => 1,
                'quality'       => $quality,
                'background'    => $background,
                'output_format' => $format,
            ];

            if (in_array($format, ['jpeg', 'webp'], true)) {
                $payload['output_compression'] = $this->outputCompression();
            }

            Log::info('OpenAI sales agent poster request prepared', [
                'agent_id' => $agent->getKey(),
                'campaign_id' => $campaign->getKey(),
                'subject_mode' => $subjectMode,
                'reference_files' => array_values(array_map(static fn (array $reference): array => [
                    'key' => $reference['key'],
                    'label' => $reference['label'],
                    'filename' => $reference['filename'],
                    'url' => $reference['url'],
                    'required' => $reference['required'],
                    'available' => $reference['available'],
                ], array_filter($resolvedReferences, static fn (array $reference): bool => $reference['available']))),
                'strict_reconstruction' => (bool) $reconstruction,
                'prompt' => $prompt,
                'payload_meta' => [
                    'model' => $model,
                    'size' => $size,
                    'quality' => $quality,
                    'background' => $background,
                    'output_format' => $format,
                ],
            ]);

            $requestStartedAt = microtime(true);
            $response = $request->post(self::ENDPOINT, $payload);
            $durationMs = (int) round((microtime(true) - $requestStartedAt) * 1000);

            if (! $response->successful()) {
                $apiMessage = $response->json('error.message') ?: ('HTTP ' . $response->status());

                Log::error('OpenAI sales agent poster generation failed', [
                    'agent_id' => $agent->getKey(),
                    'campaign_id' => $campaign->getKey(),
                    'status' => $response->status(),
                    'error' => $apiMessage,
                ]);

                return [
                    'ok' => false,
                    'message' => 'OpenAI error: ' . $apiMessage,
                    'rate_limited' => $response->status() === 429,
                    'retry_after' => (int) ($response->header('retry-after') ?: 0),
                ];
            }

            $b64 = $response->json('data.0.b64_json');
            if (! $b64) {
                return ['ok' => false, 'message' => 'OpenAI returned no image data.'];
            }

            $binary = base64_decode($b64, true);
            if ($binary === false || $binary === '') {
                return ['ok' => false, 'message' => 'Could not decode generated image.'];
            }

            if ($reconstruction) {
                $finalized = $this->finalizeSalesAgentPosterReconstruction(
                    $binary,
                    $campaign,
                    $agent,
                    is_array($reconstruction['analysis'] ?? null) ? $reconstruction['analysis'] : []
                );

                if (is_string($finalized) && $finalized !== '') {
                    $binary = $finalized;
                }
            }

            $extension = $this->extensionForFormat($format);
            $path = self::SALES_AGENT_POSTER_FOLDER . '/' . Str::random(40) . '.' . $extension;

            Storage::disk('public')->put($path, $binary);

            $usage = $this->extractUsage($response->json());
            $cost = $this->estimateCost($model, $quality, $size, $usage);

            return [
                'ok'            => true,
                'path'          => $path,
                'url'           => Storage::disk('public')->url($path),
                'cost_usd'      => $cost['total_cost_usd'],
                'duration_ms'   => $durationMs,
                'input_tokens'  => $usage['input_tokens'],
                'output_tokens' => $usage['output_tokens'],
                'total_tokens'  => $usage['total_tokens'],
            ];
        } catch (\Throwable $e) {
            Log::error('OpenAI sales agent poster generation exception', [
                'agent_id' => $agent->getKey(),
                'campaign_id' => $campaign->getKey(),
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'message' => 'Server error: ' . $e->getMessage()];
        }
    }

    /**
     * @return array{
     *   ok: bool,
     *   prompt: string,
     *   references: array<int, array{key: string, label: string, url: ?string, required: bool, available: bool}>,
     *   message?: string
     * }
     */
    public function previewSalesAgentPoster(SalesAgent $agent, SalesAgentCampaign $campaign, string $subjectMode = 'nakia'): array
    {
        $prompt = $this->buildSalesAgentPrompt($agent, $campaign, $subjectMode);
        $references = array_values(array_map(
            static fn (array $reference): array => [
                'key' => $reference['key'],
                'label' => $reference['label'],
                'url' => $reference['url'],
                'required' => $reference['required'],
                'available' => $reference['available'],
                'filename' => $reference['filename'],
            ],
            $this->resolveSalesAgentReferences($agent, $campaign, $subjectMode)
        ));
        $missingRequired = array_values(array_filter($references, static fn (array $reference): bool => $reference['required'] && ! $reference['available']));

        if ($missingRequired !== []) {
            $labels = implode(', ', array_map(static fn (array $reference): string => $reference['label'], $missingRequired));

            return [
                'ok' => false,
                'prompt' => $prompt,
                'references' => $references,
                'message' => 'Required image references are missing: ' . $labels . '.',
            ];
        }

        return [
            'ok' => true,
            'prompt' => $prompt,
            'references' => $references,
        ];
    }

    private function buildSalesAgentPrompt(SalesAgent $agent, SalesAgentCampaign $campaign, string $subjectMode): string
    {
        $campaignPrompt = $campaign->replacePromptPlaceholders($campaign->prompt_template, $agent);
        $autoApplyPlanInstruction = null;

        if ($campaign->usesAutoApplyPlanPricing() && $campaign->resolvedAutoApplyPlans() !== []) {
            $autoApplyPlanInstruction = trim(implode("\n", [
                'FINAL AUTO APPLY PLAN OVERRIDE: this takes precedence over any conflicting earlier price wording inside the campaign template.',
                'The selected Auto Apply plans below are the source of truth for every pricing card, duration label, amount, currency, and badge shown in the poster.',
                'Do not collapse multiple plans into one generic amount. Do not invent "/MONTH", "/WEEK", or any other period unless that exact selected plan label or duration supports it. Preserve each selected plan as its own pricing item inside the main offer/pricing zone.',
                'If a plan has a badge, show that badge text exactly. If a plan has no badge, do not invent one.',
                'Selected plans:',
                $campaign->autoApplyPlanCards(),
            ]));
        }

        $inspirationInstruction = $campaign->inspirationImages() !== []
            ? "STRICT EDIT-IN-PLACE MODE: the first attached image is the source poster to edit directly, and the mask marks the only regions that may change. Preserve every non-masked pixel as faithfully as possible. Keep the original composition, palette, gradients, textures, shadows, background treatment, spacing, typography mood, visual hierarchy, and poster structure intact. This is a reconstruction task, not a redesign.

INSPIRATION-LOCK RULES: Treat the attached inspiration poster as the master design to recreate. Rebuild that SAME poster design with very high fidelity. Preserve, as closely as possible, all of the following from the inspiration reference: composition, panel structure, cropping logic, subject scale, background style, gradient direction, color palette, contrast pattern, badge/chip shapes, shadows, borders, texture, typography mood, text effects, line-break behavior, alignment, spacing rhythm, headline scale, body scale, CTA treatment, offer sticker treatment, and overall premium marketing art direction. Do NOT redesign the poster. Do NOT choose a new palette. Do NOT move the text system to a different layout. Do NOT simplify the design into a generic ad.

CHANGE RULES: Change only these things:
1. Replace the person with the selected Wakanda subject mode output.
2. In every masked text or logo zone, recreate the same graphic block style but REMOVE all foreign readable text and branding so those zones are clean and ready for exact post-processing text compositing.
3. Replace foreign logos/branding/contact details with Wakanda Jobs styling only where required by the masked region.

TEXT ZONE RULES: The masked text areas must keep the same badge shapes, cards, boxes, buttons, spacing, and hierarchy from the source poster, but readable foreign wording should be removed or reduced to neutral/non-final placeholder styling. Do not invent new slogans, websites, phone numbers, or brands inside those zones. The exact final campaign text will be composited afterwards.

COLOR RULES: Preserve the inspiration poster's dominant and accent colors as closely as possible unless Wakanda branding replacement requires a minimal localized adjustment. Do not drift to unrelated colors.

LAYOUT RULES: Keep the same left/right or top/bottom balance, same spacing density, same empty-space usage, and same text/image zoning. The final result should look like the same poster designer made the same poster again for Wakanda Jobs, not like a new design inspired by it."
            : null;

        return trim(implode("\n\n", array_filter([
            "Generate one polished Wakanda Jobs sales-agent campaign poster.",
            $this->salesAgentReferencePrompt($agent, $subjectMode),
            "Use the attached Wakanda Jobs logo references as brand elements only. Keep the text fully legible, premium, modern, and WhatsApp-shareable.",
            $inspirationInstruction,
            $campaignPrompt,
            $autoApplyPlanInstruction,
            "MASK DISCIPLINE RULE: only regenerate the masked regions for subject/text/logo replacement. Every unmasked area must remain visually equivalent to the source poster, including exact palette family, lighting treatment, effects, edge styling, and decorative elements.",
            "TEXT REPLACEMENT RULE: do not preserve any foreign readable text from the inspiration image. Rebuild the same text containers and hierarchy, but leave them clean and style-consistent for deterministic final text compositing.",
            "IDENTITY RULE: this is not a loose inspiration task. This is a poster recreation task where the design language, art direction, and text layout are preserved, while only the subject, logo/brand, and text content are swapped to Wakanda Jobs campaign content.",
            "FOREIGN ELEMENT REMOVAL RULE: remove every foreign phone number, website, logo, company name, person name, and CTA from the inspiration image and replace them with Wakanda Jobs campaign equivalents only. Nothing from the original poster's branding may remain.",
            "READABILITY RULE: the final poster must remain highly legible on a phone screen. Preserve the inspiration's hierarchy, but never allow replacement text to become tiny, blurry, or unreadable. If our replacement text is longer, reflow it inside the same design system without abandoning the layout style.",
            "Do not include any document, card, contract, or paperwork prop anywhere in the image — no employment contract, no signed papers, nothing being handed over or presented. The person(s) should not be holding or gesturing toward any such object.",
            "CRITICAL REALISM REQUIREMENT: render every person's skin with natural, photographic texture — visible pores, fine skin texture, subtle natural imperfections and asymmetry, like an unedited photo straight out of a professional camera. Avoid airbrushed, plastic, glossy, overly smooth, or 'beauty filter' skin. Avoid a CGI, 3D-rendered, illustrated, or synthetic/AI-generated look. Hands and fingers should look anatomically natural, not overly perfect or smoothed. The result should look like a real photograph of a real person, not a digital render.",
            $this->salesAgentSubjectOverridePrompt($agent, $subjectMode),
        ])));
    }

    /**
     * Campaign prompt templates above may contain a literal physical description of
     * a person (written for the default Nakia persona). When a different subject_mode
     * is selected, that description otherwise out-weighs the brief reference instruction
     * and the model keeps rendering Nakia regardless of which photo was attached. Re-stating
     * the override last, after the campaign brief, makes it win.
     */
    private function salesAgentSubjectOverridePrompt(SalesAgent $agent, string $subjectMode): ?string
    {
        return match ($subjectMode) {
            'agent' => "FINAL OVERRIDE (this takes precedence over any conflicting physical description above): the person in this poster is {$agent->name}, rendered using the attached agent photo as the only face/identity AND outfit reference. Ignore any ethnicity, hairstyle, build, or clothing description above (including any purple blazer or Ndebele-pattern trim) that does not match the attached agent photo — keep ONLY the pose, layout, and composition from the brief. The face and the outfit/clothing must both come from the attached agent photo exactly as worn there; do not redress {$agent->name} in different clothing. This applies EVEN IF the attached photo shows casual clothing (e.g. a t-shirt, striped shirt, tank top, or jeans) — keep that exact casual outfit as-is. Do NOT upgrade, formalize, or replace it with a blazer, suit, or business attire, and do NOT put {$agent->name} in a purple blazer or Ndebele-pattern trim under any circumstances, regardless of gender. FACE FIDELITY: copy the face from the attached agent photo as exactly as possible — same face shape, eyes, nose, mouth, skin tone, and hairstyle. Do NOT beautify, smooth, idealize, de-age, or genericize the face into a stock-photo look; the output face must be immediately recognizable as the same person in the attached photo, not just a similar-looking model.",
            'both' => "FINAL OVERRIDE (this takes precedence over any conflicting layout or pose description above): render this as a TWO-PERSON composition in the same right-column area: Nakia (face AND outfit from the attached Nakia photo — she wears her own purple blazer look) and {$agent->name} (face AND outfit from the attached agent photo only) STANDING side by side, both facing the camera, both fully visible from roughly the waist up, both clearly recognizable as two distinct individuals, equally lit and in focus, neither hidden, cropped out, nor blended into a single face. {$agent->name}'s clothing must come ONLY from the attached agent photo exactly as worn there, even if it is casual (t-shirt, striped shirt, tank top, jeans, etc.) — do NOT put {$agent->name} in a purple blazer, Ndebele-pattern trim, or any outfit matching Nakia's; the two people must visibly wear different, distinct outfits matching their own respective photos. FACE FIDELITY: copy each person's face from their own attached photo as exactly as possible — same face shape, eyes, nose, mouth, skin tone, and hairstyle for both Nakia and {$agent->name}. Do NOT beautify, smooth, idealize, de-age, or genericize either face into a stock-photo look; both output faces must be immediately recognizable as the same people in their attached photos. Do NOT include an office desk or a laptop in this composition — there is no desk, no laptop, both are simply standing. Do NOT have either person hold, present, or gesture toward any document, card, contract, or paperwork of any kind — no such object should appear anywhere in the image. Both faces must be present in the final image; an image containing only one of them is wrong.",
            default => null,
        };
    }

    private function salesAgentReferencePrompt(SalesAgent $agent, string $subjectMode): string
    {
        return match ($subjectMode) {
            'agent' => "Use the attached agent photo as the only human reference. Preserve the agent's EXACT facial identity as closely as possible: the same face shape, eyes, nose, mouth, skin tone, hairstyle, and any distinguishing features (facial hair, glasses, marks) seen in the attached photo. Treat the face as a likeness to match, not a starting point to reinterpret — do not beautify, idealize, swap to a different ethnicity, change the apparent age, or drift toward a generic stock-photo face. Only the pose, lighting, and framing should be restyled into a polished marketing poster; the face itself must stay recognizably {$agent->name} as seen in the reference photo. Do not use Nakia, do not invent a second person, and do not replace the agent with a generic model.",
            'both' => "Use the attached Nakia photo and the attached agent photo together. Combine them into one coherent campaign visual, using Nakia as the lead Wakanda Jobs marketing persona and {$agent->name} as the featured local agent. For BOTH people, preserve their EXACT facial identity from their respective attached photos as closely as possible: same face shape, eyes, nose, mouth, skin tone, hairstyle, and any distinguishing features — do not beautify, idealize, or genericize either face. {$agent->name}'s face must stay recognizably his/her own from the agent photo, not a stylized approximation. Keep both faces recognizable, natural, and professionally integrated in the same poster. Do not omit either person.",
            default => "Use the attached Nakia photo as the main human reference for the poster. Do not use any other person as the main subject unless the campaign prompt explicitly requires it.",
        };
    }

    /**
     * @return array<int, array{
     *   key: string,
     *   label: string,
     *   url: ?string,
     *   required: bool,
     *   available: bool,
     *   filename: string,
     *   content: ?string
     * }>
     */
    private function resolveSalesAgentReferences(SalesAgent $agent, SalesAgentCampaign $campaign, string $subjectMode): array
    {
        $references = [];

        foreach (self::WJ_LOGOS as $index => $url) {
            $references[] = [
                'key' => 'wakanda_logo_' . ($index + 1),
                'label' => 'Wakanda logo ' . ($index + 1),
                'url' => $url,
                'required' => false,
                'available' => true,
                'filename' => 'wakanda-logo-' . ($index + 1) . '.png',
                'content' => $this->fetchBytes($url),
            ];
        }

        $nakiaUrl = $this->settingImageUrl('sales_agent_nakia_image')
            ?? $this->settingImageUrl('auto_cv_bot_persona_image');
        $agentUrl = $agent->photoUrl();
        $salesAgentLogoUrl = $this->settingImageUrl('sales_agent_logo_image');

        if (in_array($subjectMode, ['nakia', 'both'], true)) {
            $references[] = [
                'key' => 'nakia',
                'label' => 'Nakia reference',
                'url' => $nakiaUrl,
                'required' => true,
                'available' => filled($nakiaUrl),
                'filename' => 'nakia.png',
                'content' => $nakiaUrl ? $this->fetchBytes($nakiaUrl) : null,
            ];
        }

        if (in_array($subjectMode, ['agent', 'both'], true)) {
            $references[] = [
                'key' => 'agent',
                'label' => 'Sales agent photo',
                'url' => $agentUrl,
                'required' => true,
                'available' => filled($agentUrl),
                'filename' => 'agent-photo.png',
                'content' => $agentUrl ? $this->fetchBytes($agent->photo ?: $agentUrl) : null,
            ];
        }

        if (filled($salesAgentLogoUrl)) {
            $references[] = [
                'key' => 'sales_agent_logo',
                'label' => 'Sales agent logo',
                'url' => $salesAgentLogoUrl,
                'required' => false,
                'available' => true,
                'filename' => 'sales-agent-logo.png',
                'content' => $this->fetchBytes($salesAgentLogoUrl),
            ];
        }

        foreach ($campaign->inspirationImages() as $index => $imagePath) {
            $references[] = [
                'key' => 'campaign_inspiration_' . ($index + 1),
                'label' => 'Campaign inspiration poster ' . ($index + 1),
                'url' => RvMedia::getImageUrl($imagePath),
                'required' => false,
                'available' => true,
                'filename' => 'campaign-inspiration-' . ($index + 1) . '.png',
                'content' => $this->fetchBytes($imagePath),
            ];
        }

        foreach ($references as &$reference) {
            if ($reference['required']) {
                $reference['available'] = $reference['available'] && $reference['content'] !== null && $reference['content'] !== '';
            } else {
                $reference['available'] = $reference['content'] !== null && $reference['content'] !== '';
            }
        }
        unset($reference);

        return $references;
    }

    private function cachedCampaignInspirationAnalysis(SalesAgentCampaign $campaign): array
    {
        $manualLayout = is_array($campaign->reconstruction_layout ?? null) ? $campaign->reconstruction_layout : null;
        $path = $campaign->inspirationImages()[0] ?? null;

        if (! $path) {
            return $manualLayout ? [
                'ok' => true,
                'summary' => [],
                'editable_regions' => $manualLayout,
            ] : [];
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return [];
        }

        $lastModified = (int) ($disk->lastModified($path) ?: 0);
        $cacheKey = 'sales-agent-campaign-inspiration-analysis:' . $campaign->getKey() . ':' . md5($path . '|' . $lastModified);

        $analysis = Cache::remember($cacheKey, now()->addHours(12), function () use ($disk, $path): array {
            $bytes = $disk->get($path);

            if (! is_string($bytes) || $bytes === '') {
                return [];
            }

            $mime = $disk->mimeType($path) ?: $this->guessMimeFromPath($path);
            $result = $this->analyzeSalesAgentInspirationBytes($bytes, $mime);

            return ($result['ok'] ?? false) ? $result : [];
        });

        if ($manualLayout) {
            $analysis['editable_regions'] = $manualLayout;
            $analysis['ok'] = true;
        }

        return $analysis;
    }

    private function prepareSalesAgentReconstructionAssets(SalesAgentCampaign $campaign): ?array
    {
        $path = $campaign->inspirationImages()[0] ?? null;

        if (! $path) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        $imageBytes = $disk->get($path);

        if (! is_string($imageBytes) || $imageBytes === '') {
            return null;
        }

        $analysis = $this->cachedCampaignInspirationAnalysis($campaign);
        $regions = $analysis['editable_regions'] ?? [];
        $maskBytes = $this->buildSalesAgentReconstructionMask($imageBytes, is_array($regions) ? $regions : []);

        if (! $maskBytes) {
            return null;
        }

        return [
            'image_bytes' => $imageBytes,
            'filename' => 'campaign-inspiration-source.' . pathinfo($path, PATHINFO_EXTENSION),
            'mask_bytes' => $maskBytes,
            'mask_filename' => 'campaign-inspiration-mask.png',
            'analysis' => $analysis,
        ];
    }

    private function buildSalesAgentReconstructionMask(string $baseImage, array $regions): ?string
    {
        $source = @imagecreatefromstring($baseImage);

        if (! $source) {
            return null;
        }

        try {
            $width = imagesx($source);
            $height = imagesy($source);

            if ($width <= 0 || $height <= 0) {
                return null;
            }

            $mask = imagecreatetruecolor($width, $height);
            imagealphablending($mask, false);
            imagesavealpha($mask, true);

            $opaque = imagecolorallocatealpha($mask, 0, 0, 0, 0);
            $transparent = imagecolorallocatealpha($mask, 255, 255, 255, 127);
            imagefilledrectangle($mask, 0, 0, $width, $height, $opaque);

            $editableBoxes = array_filter([
                $regions['subject_box'] ?? null,
                $regions['headline_box'] ?? null,
                $regions['body_box'] ?? null,
                $regions['price_box'] ?? null,
                $regions['cta_box'] ?? null,
                $regions['logo_box'] ?? null,
            ], 'is_array');

            foreach ($regions['extra_text_boxes'] ?? [] as $box) {
                if (is_array($box)) {
                    $editableBoxes[] = $box;
                }
            }

            if ($editableBoxes === []) {
                return null;
            }

            foreach ($editableBoxes as $box) {
                $pixels = $this->normalizedBoxToPixels($box, $width, $height);

                if (! $pixels) {
                    continue;
                }

                [$x1, $y1, $x2, $y2] = $pixels;
                imagefilledrectangle($mask, $x1, $y1, $x2, $y2, $transparent);
            }

            ob_start();
            $written = imagepng($mask);
            $png = ob_get_clean();

            if (! $written || ! is_string($png) || $png === '') {
                return null;
            }

            imagedestroy($mask);

            return $png;
        } finally {
            imagedestroy($source);
        }
    }

    private function normalizedBoxToPixels(array $box, int $width, int $height): ?array
    {
        $x = isset($box['x']) ? (float) $box['x'] : null;
        $y = isset($box['y']) ? (float) $box['y'] : null;
        $w = isset($box['w']) ? (float) $box['w'] : null;
        $h = isset($box['h']) ? (float) $box['h'] : null;

        if ($x === null || $y === null || $w === null || $h === null || $w <= 0 || $h <= 0) {
            return null;
        }

        $paddingX = (int) round($width * 0.015);
        $paddingY = (int) round($height * 0.015);
        $x1 = max(0, (int) floor(($x / 1000) * $width) - $paddingX);
        $y1 = max(0, (int) floor(($y / 1000) * $height) - $paddingY);
        $x2 = min($width - 1, (int) ceil((($x + $w) / 1000) * $width) + $paddingX);
        $y2 = min($height - 1, (int) ceil((($y + $h) / 1000) * $height) + $paddingY);

        return ($x2 > $x1 && $y2 > $y1) ? [$x1, $y1, $x2, $y2] : null;
    }

    private function guessMimeFromPath(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }

    private function finalizeSalesAgentPosterReconstruction(
        string $binary,
        SalesAgentCampaign $campaign,
        SalesAgent $agent,
        array $analysis
    ): ?string {
        $generatedImage = @imagecreatefromstring($binary);

        if (! $generatedImage) {
            return null;
        }

        try {
            $basePath = $campaign->inspirationImages()[0] ?? null;
            $baseBytes = $basePath ? Storage::disk('public')->get($basePath) : null;
            $image = is_string($baseBytes) && $baseBytes !== '' ? @imagecreatefromstring($baseBytes) : null;

            if (! $image) {
                $image = $generatedImage;
            }

            $regions = is_array($analysis['editable_regions'] ?? null) ? $analysis['editable_regions'] : [];
            $fontPath = public_path('vendor/core/core/base/fonts/Roboto-Bold.ttf');

            if (! is_file($fontPath)) {
                return null;
            }

            $this->copyNormalizedRegion($generatedImage, $image, $regions['subject_box'] ?? null);
            $this->copyNormalizedRegion($generatedImage, $image, $regions['headline_box'] ?? null);
            $this->copyNormalizedRegion($generatedImage, $image, $regions['body_box'] ?? null);

            $this->clearNormalizedRegion($image, $regions['price_box'] ?? null);
            $this->clearNormalizedRegion($image, $regions['cta_box'] ?? null);
            $this->clearNormalizedRegion($image, $regions['logo_box'] ?? null);

            foreach ((array) ($regions['extra_text_boxes'] ?? []) as $box) {
                $this->clearNormalizedRegion($image, $box);
            }

            $headline = $campaign->resolvedLandingHeadline();
            $body = $campaign->resolvedLandingBody();
            $price = $campaign->priceLine() !== '' ? $campaign->priceLine() : $campaign->resolvedProductLabel();
            $cta = $campaign->resolvedLandingCtaText();
            $brand = 'Wakanda Jobs';

            $this->drawPosterTextRegion($image, $regions['headline_box'] ?? null, $headline, $fontPath, [
                'max_font' => 86,
                'min_font' => 26,
                'align' => 'left',
                'uppercase' => true,
                'line_spacing' => 0.88,
                'stroke_ratio' => 0.07,
                'padding_ratio' => 0.08,
            ]);

            $this->drawPosterTextRegion($image, $regions['body_box'] ?? null, $body, $fontPath, [
                'max_font' => 34,
                'min_font' => 16,
                'align' => 'left',
                'uppercase' => false,
                'line_spacing' => 1.12,
                'stroke_ratio' => 0.045,
                'padding_ratio' => 0.10,
            ]);

            $this->drawPosterTextRegion($image, $regions['price_box'] ?? null, $price, $fontPath, [
                'max_font' => 74,
                'min_font' => 24,
                'align' => 'center',
                'uppercase' => true,
                'line_spacing' => 0.92,
                'stroke_ratio' => 0.07,
                'padding_ratio' => 0.10,
            ]);

            $this->drawPosterTextRegion($image, $regions['cta_box'] ?? null, $cta, $fontPath, [
                'max_font' => 34,
                'min_font' => 16,
                'align' => 'center',
                'uppercase' => false,
                'line_spacing' => 1.0,
                'stroke_ratio' => 0.045,
                'padding_ratio' => 0.16,
            ]);

            $this->drawWakandaLogoRegion($image, $regions['logo_box'] ?? null);

            $extraTextPool = array_values(array_filter([
                $campaign->resolvedProductLabel(),
                $campaign->name,
                $campaign->promoDeadlineLine(),
                $campaign->isPromoCampaign() ? 'PROMO' : null,
                $brand,
            ], static fn (?string $value): bool => is_string($value) && trim($value) !== ''));

            foreach ((array) ($regions['extra_text_boxes'] ?? []) as $index => $box) {
                $text = $extraTextPool[$index] ?? null;

                if (! is_array($box) || ! is_string($text) || trim($text) === '') {
                    continue;
                }

                $this->drawPosterTextRegion($image, $box, $text, $fontPath, [
                    'max_font' => 42,
                    'min_font' => 14,
                    'align' => 'center',
                    'uppercase' => true,
                    'line_spacing' => 0.95,
                    'stroke_ratio' => 0.05,
                    'padding_ratio' => 0.12,
                ]);
            }

            return $this->encodeImage($image, $this->outputFormat());
        } finally {
            if ($generatedImage instanceof \GdImage) {
                imagedestroy($generatedImage);
            }

            if ($image instanceof \GdImage && $image !== $generatedImage) {
                imagedestroy($image);
            }
        }
    }

    private function copyNormalizedRegion(\GdImage $source, \GdImage $target, mixed $box): void
    {
        if (! is_array($box)) {
            return;
        }

        $width = imagesx($target);
        $height = imagesy($target);
        $pixels = $this->normalizedBoxToPixels($box, $width, $height);

        if (! $pixels) {
            return;
        }

        [$x1, $y1, $x2, $y2] = $pixels;
        $regionWidth = max(1, $x2 - $x1);
        $regionHeight = max(1, $y2 - $y1);

        imagecopy($target, $source, $x1, $y1, $x1, $y1, $regionWidth, $regionHeight);
    }

    private function clearNormalizedRegion(\GdImage $image, mixed $box): void
    {
        if (! is_array($box)) {
            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $pixels = $this->normalizedBoxToPixels($box, $width, $height);

        if (! $pixels) {
            return;
        }

        [$x1, $y1, $x2, $y2] = $pixels;
        $background = $this->allocateRegionBackgroundColor($image, $pixels);

        imagefilledrectangle($image, $x1, $y1, $x2, $y2, $background);
    }

    private function allocateRegionBackgroundColor(\GdImage $image, array $pixels): int
    {
        [$x1, $y1, $x2, $y2] = $pixels;
        $samplePoints = [
            [$x1 + 4, $y1 + 4],
            [$x2 - 4, $y1 + 4],
            [$x1 + 4, $y2 - 4],
            [$x2 - 4, $y2 - 4],
        ];
        $red = 0;
        $green = 0;
        $blue = 0;
        $count = 0;

        foreach ($samplePoints as [$x, $y]) {
            $color = imagecolorat($image, max(0, min(imagesx($image) - 1, $x)), max(0, min(imagesy($image) - 1, $y)));
            $red += ($color >> 16) & 0xFF;
            $green += ($color >> 8) & 0xFF;
            $blue += $color & 0xFF;
            $count++;
        }

        return imagecolorallocate(
            $image,
            (int) round($red / max(1, $count)),
            (int) round($green / max(1, $count)),
            (int) round($blue / max(1, $count))
        );
    }

    private function drawPosterTextRegion(
        \GdImage $image,
        mixed $box,
        string $text,
        string $fontPath,
        array $options = []
    ): void {
        if (! is_array($box) || trim($text) === '') {
            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $pixels = $this->normalizedBoxToPixels($box, $width, $height);

        if (! $pixels) {
            return;
        }

        [$x1, $y1, $x2, $y2] = $pixels;
        $paddingRatio = (float) ($options['padding_ratio'] ?? 0.1);
        $paddingX = (int) round(($x2 - $x1) * $paddingRatio);
        $paddingY = (int) round(($y2 - $y1) * $paddingRatio);
        $left = $x1 + $paddingX;
        $top = $y1 + $paddingY;
        $right = max($left + 10, $x2 - $paddingX);
        $bottom = max($top + 10, $y2 - $paddingY);
        $availableWidth = $right - $left;
        $availableHeight = $bottom - $top;

        if ($availableWidth < 24 || $availableHeight < 18) {
            return;
        }

        $preparedText = ! empty($options['uppercase']) ? mb_strtoupper($text) : $text;
        $layout = $this->fitTextToBox(
            $preparedText,
            $fontPath,
            $availableWidth,
            $availableHeight,
            (int) ($options['max_font'] ?? 64),
            (int) ($options['min_font'] ?? 16),
            (float) ($options['line_spacing'] ?? 1.0)
        );

        if (! $layout) {
            return;
        }

        $style = $this->sampleAdaptiveTextStyle($image, $pixels);
        $align = $options['align'] ?? 'left';
        $strokeWidth = max(1, (int) round($layout['font_size'] * (float) ($options['stroke_ratio'] ?? 0.05)));
        $lineHeight = (float) $layout['line_height'];
        $blockHeight = (int) round(count($layout['lines']) * $lineHeight);
        $baselineY = (int) round($top + (($availableHeight - $blockHeight) / 2) + $layout['font_size']);

        foreach ($layout['lines'] as $index => $line) {
            $lineWidth = $this->measureTextWidth($line, $fontPath, $layout['font_size']);
            $lineX = match ($align) {
                'center' => (int) round($left + (($availableWidth - $lineWidth) / 2)),
                'right' => $right - $lineWidth,
                default => $left,
            };
            $lineY = (int) round($baselineY + ($index * $lineHeight));

            $this->drawOutlinedText(
                $image,
                $layout['font_size'],
                $lineX,
                $lineY,
                $line,
                $fontPath,
                $style['fill'],
                $style['stroke'],
                $strokeWidth
            );
        }
    }

    private function drawWakandaLogoRegion(\GdImage $image, mixed $box): void
    {
        if (! is_array($box)) {
            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $pixels = $this->normalizedBoxToPixels($box, $width, $height);

        if (! $pixels) {
            return;
        }

        [$x1, $y1, $x2, $y2] = $pixels;
        $logoBytes = null;

        foreach (self::WJ_LOGOS as $logoPath) {
            $logoBytes = $this->fetchBytes($logoPath);

            if ($logoBytes) {
                break;
            }
        }

        if (! $logoBytes) {
            return;
        }

        $logo = @imagecreatefromstring($logoBytes);

        if (! $logo) {
            return;
        }

        try {
            $boxWidth = max(1, $x2 - $x1);
            $boxHeight = max(1, $y2 - $y1);
            $logoWidth = imagesx($logo);
            $logoHeight = imagesy($logo);

            if ($logoWidth <= 0 || $logoHeight <= 0) {
                return;
            }

            $scale = min($boxWidth / $logoWidth, $boxHeight / $logoHeight, 1.0);
            $targetWidth = max(1, (int) round($logoWidth * $scale));
            $targetHeight = max(1, (int) round($logoHeight * $scale));
            $destX = (int) round($x1 + (($boxWidth - $targetWidth) / 2));
            $destY = (int) round($y1 + (($boxHeight - $targetHeight) / 2));

            imagealphablending($image, true);
            imagesavealpha($image, true);
            imagecopyresampled($image, $logo, $destX, $destY, 0, 0, $targetWidth, $targetHeight, $logoWidth, $logoHeight);
        } finally {
            imagedestroy($logo);
        }
    }

    private function fitTextToBox(
        string $text,
        string $fontPath,
        int $maxWidth,
        int $maxHeight,
        int $maxFont,
        int $minFont,
        float $lineSpacing
    ): ?array {
        for ($fontSize = $maxFont; $fontSize >= $minFont; $fontSize -= 2) {
            $lines = $this->wrapTextToWidth($text, $fontPath, $fontSize, $maxWidth);

            if ($lines === []) {
                continue;
            }

            $lineHeight = $fontSize * $lineSpacing;
            $blockHeight = count($lines) * $lineHeight;

            if ($blockHeight > $maxHeight) {
                continue;
            }

            $fits = true;

            foreach ($lines as $line) {
                if ($this->measureTextWidth($line, $fontPath, $fontSize) > $maxWidth) {
                    $fits = false;
                    break;
                }
            }

            if ($fits) {
                return [
                    'font_size' => $fontSize,
                    'lines' => $lines,
                    'line_height' => $lineHeight,
                ];
            }
        }

        return null;
    }

    private function wrapTextToWidth(string $text, string $fontPath, int $fontSize, int $maxWidth): array
    {
        $paragraphs = preg_split('/\R/u', trim($text)) ?: [];
        $lines = [];

        foreach ($paragraphs as $paragraph) {
            $words = preg_split('/\s+/u', trim($paragraph)) ?: [];

            if ($words === []) {
                continue;
            }

            $current = '';

            foreach ($words as $word) {
                $candidate = trim($current === '' ? $word : $current . ' ' . $word);

                if ($candidate === '') {
                    continue;
                }

                if ($this->measureTextWidth($candidate, $fontPath, $fontSize) <= $maxWidth) {
                    $current = $candidate;
                    continue;
                }

                if ($current !== '') {
                    $lines[] = $current;
                    $current = $word;
                    continue;
                }

                $lines[] = $word;
                $current = '';
            }

            if ($current !== '') {
                $lines[] = $current;
            }
        }

        return array_values(array_filter($lines, static fn (string $line): bool => trim($line) !== ''));
    }

    private function measureTextWidth(string $text, string $fontPath, int $fontSize): int
    {
        $box = imagettfbbox($fontSize, 0, $fontPath, $text);

        if (! is_array($box)) {
            return 0;
        }

        return (int) abs(max($box[2], $box[4]) - min($box[0], $box[6]));
    }

    private function sampleAdaptiveTextStyle(\GdImage $image, array $pixels): array
    {
        [$x1, $y1, $x2, $y2] = $pixels;
        $samplePoints = [
            [$x1, $y1],
            [$x2, $y1],
            [$x1, $y2],
            [$x2, $y2],
            [(int) round(($x1 + $x2) / 2), (int) round(($y1 + $y2) / 2)],
        ];
        $luminanceTotal = 0.0;

        foreach ($samplePoints as [$x, $y]) {
            $rgb = imagecolorat($image, max(0, $x), max(0, $y));
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $luminanceTotal += (0.2126 * $r) + (0.7152 * $g) + (0.0722 * $b);
        }

        $average = $luminanceTotal / max(1, count($samplePoints));
        $useLightText = $average < 150;

        return [
            'fill' => imagecolorallocate($image, $useLightText ? 255 : 24, $useLightText ? 255 : 24, $useLightText ? 255 : 24),
            'stroke' => imagecolorallocate($image, $useLightText ? 18 : 255, $useLightText ? 18 : 255, $useLightText ? 18 : 255),
        ];
    }

    private function drawOutlinedText(
        \GdImage $image,
        int $fontSize,
        int $x,
        int $y,
        string $text,
        string $fontPath,
        int $fillColor,
        int $strokeColor,
        int $strokeWidth
    ): void {
        for ($offsetX = -$strokeWidth; $offsetX <= $strokeWidth; $offsetX++) {
            for ($offsetY = -$strokeWidth; $offsetY <= $strokeWidth; $offsetY++) {
                if ($offsetX === 0 && $offsetY === 0) {
                    continue;
                }

                imagettftext($image, $fontSize, 0, $x + $offsetX, $y + $offsetY, $strokeColor, $fontPath, $text);
            }
        }

        imagettftext($image, $fontSize, 0, $x, $y, $fillColor, $fontPath, $text);
    }

    private function fetchSettingImageBytes(string $settingKey): ?string
    {
        $url = trim((string) setting($settingKey, ''));

        if ($url === '') {
            return null;
        }

        if (! RvMedia::isUsingCloud()) {
            $path = RvMedia::getRealPath($url);

            return is_file($path) ? (@file_get_contents($path) ?: null) : null;
        }

        $contents = @file_get_contents(RvMedia::getImageUrl($url));

        return $contents !== false ? $contents : null;
    }

    private function settingImageUrl(string $settingKey): ?string
    {
        $url = trim((string) setting($settingKey, ''));

        return $url !== '' ? RvMedia::getImageUrl($url) : null;
    }

    private function buildPrompt(Job $job, string $type): string
    {
        return match ($type) {
            'tiktok_image'   => $this->publisher->buildTikTokImagePrompt($job),
            'whatsapp_image' => $this->publisher->buildAiImagePrompt($job),
            'facebook_image' => $this->publisher->buildFacebookImagePrompt($job),
            'linkedin_image' => $this->publisher->buildLinkedInImagePrompt($job),
            'cover_image'    => $this->publisher->buildCoverImagePrompt($job),
            'twitter_image'  => $this->publisher->buildTwitterImagePrompt($job),
            'employer_image' => $this->publisher->buildEmployerImagePrompt($job),
            default          => $this->publisher->buildAiImagePrompt($job),
        };
    }

    /**
     * Load the brand logos (WJ + company) as raw bytes to send as gpt-image-1 reference images.
     *
     * @return array<int, array{content: string, filename: string}>
     */
    private function referenceImages(Job $job): array
    {
        $refs = [];

        foreach (self::WJ_LOGOS as $i => $url) {
            $bytes = $this->fetchBytes($url);
            if ($bytes !== null) {
                $refs[] = ['content' => $bytes, 'filename' => 'wakanda-logo-' . ($i + 1) . '.png'];
            }
        }

        if ($job->company && ! empty($job->company->logo)) {
            $bytes = $this->fetchBytes($job->company->logo);
            if ($bytes !== null) {
                $refs[] = ['content' => $bytes, 'filename' => 'company-logo.png'];
            }
        }

        return $refs;
    }

    private function fetchBytes(string $pathOrUrl): ?string
    {
        try {
            if (Str::startsWith($pathOrUrl, ['http://', 'https://'])) {
                $response = Http::retry(2, 200)->timeout(30)->get($pathOrUrl);

                return $response->successful() ? $response->body() : null;
            }

            $disk = Storage::disk('public');

            return $disk->exists($pathOrUrl) ? $disk->get($pathOrUrl) : null;
        } catch (\Throwable $e) {
            Log::warning('OpenAI reference image fetch failed', ['src' => $pathOrUrl, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function sizeFor(string $type, string $model): string
    {
        if ($model === 'gpt-image-2') {
            return self::GPT_IMAGE_2_SIZES[$type] ?? '1024x1024';
        }

        return self::LEGACY_SIZES[$type] ?? '1024x1024';
    }

    private function extensionForFormat(string $format): string
    {
        return match ($format) {
            'jpeg' => 'jpg',
            'webp' => 'webp',
            default => 'png',
        };
    }

    private function normalizeBinary(string $binary, string $type, string $format): ?string
    {
        $image = @imagecreatefromstring($binary);
        if (! $image) {
            return null;
        }

        try {
            [$targetWidth, $targetHeight] = self::TARGET_DIMENSIONS[$type] ?? [0, 0];
            if ($targetWidth > 0 && $targetHeight > 0) {
                $image = $this->resizeAndCrop($image, $targetWidth, $targetHeight);
            }

            return $this->encodeImage($image, $format);
        } finally {
            if ($image instanceof \GdImage) {
                imagedestroy($image);
            }
        }
    }

    private function resizeAndCrop(\GdImage $source, int $targetWidth, int $targetHeight): \GdImage
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        $scale = max($targetWidth / $sourceWidth, $targetHeight / $sourceHeight);
        $scaledWidth = (int) ceil($sourceWidth * $scale);
        $scaledHeight = (int) ceil($sourceHeight * $scale);

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefill($canvas, 0, 0, $transparent);

        $dstX = (int) floor(($targetWidth - $scaledWidth) / 2);
        $dstY = (int) floor(($targetHeight - $scaledHeight) / 2);

        imagecopyresampled(
            $canvas,
            $source,
            $dstX,
            $dstY,
            0,
            0,
            $scaledWidth,
            $scaledHeight,
            $sourceWidth,
            $sourceHeight
        );

        imagedestroy($source);

        return $canvas;
    }

    private function encodeImage(\GdImage $image, string $format): ?string
    {
        ob_start();

        $written = match ($format) {
            'jpeg' => imagejpeg($image, null, max(0, min(100, 100 - $this->outputCompression()))),
            'webp' => function_exists('imagewebp')
                ? imagewebp($image, null, max(0, min(100, 100 - $this->outputCompression())))
                : imagepng($image),
            default => imagepng($image),
        };

        $data = ob_get_clean();

        return $written && is_string($data) && $data !== '' ? $data : null;
    }

    /**
     * Generate AVIF + WebP siblings and a tiny base64 LQIP placeholder for an already-stored image.
     *
     * @return array{webp?: string, avif?: string, lqip?: string}
     */
    public function generateVariants(string $path, ?string $sourceFormat = null): array
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return [];
        }

        $sourceFormat ??= $this->formatFromExtension($path);

        $image = @imagecreatefromstring((string) $disk->get($path));
        if (! $image) {
            return [];
        }

        $variants = [];

        try {
            $thumbExtension = function_exists('imagewebp') ? 'webp' : 'png';
            $thumbPath = $this->withVariantSuffix($path, 'thumb', $thumbExtension);
            if ($this->encodeThumbnail($image, $thumbExtension, $thumbPath, $disk, 160)) {
                $variants['thumb'] = $thumbPath;
            }

            if ($sourceFormat === 'webp') {
                // Already WebP — reuse as-is rather than spend CPU re-encoding an identical copy.
                $variants['webp'] = $path;
            } elseif (function_exists('imagewebp')) {
                $webpPath = $this->withExtension($path, 'webp');
                if ($this->encodeAndStore($image, 'webp', $webpPath, $disk)) {
                    $variants['webp'] = $webpPath;
                }
            }

            if (function_exists('imageavif')) {
                $avifPath = $this->withExtension($path, 'avif');
                if ($this->encodeAndStore($image, 'avif', $avifPath, $disk)) {
                    $variants['avif'] = $avifPath;
                }
            }

            $lqip = $this->buildLqip($image);
            if ($lqip !== null) {
                $variants['lqip'] = $lqip;
            }
        } catch (\Throwable $e) {
            Log::warning('Image variant generation failed', ['path' => $path, 'error' => $e->getMessage()]);
        } finally {
            imagedestroy($image);
        }

        return $variants;
    }

    private function formatFromExtension(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'webp' => 'webp',
            'jpg', 'jpeg' => 'jpeg',
            default => 'png',
        };
    }

    private function withExtension(string $path, string $extension): string
    {
        $folder = pathinfo($path, PATHINFO_DIRNAME);
        $basename = pathinfo($path, PATHINFO_FILENAME);

        return ($folder !== '.' ? $folder . '/' : '') . $basename . '.' . $extension;
    }

    private function withVariantSuffix(string $path, string $suffix, string $extension): string
    {
        $folder = pathinfo($path, PATHINFO_DIRNAME);
        $basename = pathinfo($path, PATHINFO_FILENAME);

        return ($folder !== '.' ? $folder . '/' : '') . $basename . '.' . $suffix . '.' . $extension;
    }

    private function encodeAndStore(\GdImage $image, string $format, string $path, $disk): bool
    {
        $quality = max(0, min(100, 100 - $this->outputCompression()));

        ob_start();
        $written = match ($format) {
            'webp' => imagewebp($image, null, $quality),
            'avif' => imageavif($image, null, $quality),
            default => false,
        };
        $data = ob_get_clean();

        if (! $written || ! is_string($data) || $data === '') {
            return false;
        }

        $disk->put($path, $data);

        return true;
    }

    private function encodeThumbnail(\GdImage $source, string $format, string $path, $disk, int $maxDimension = 160): bool
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            return false;
        }

        $scale = min($maxDimension / $sourceWidth, $maxDimension / $sourceHeight, 1);
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $thumb = imagescale($source, $targetWidth, $targetHeight);

        if (! $thumb) {
            return false;
        }

        imagealphablending($thumb, true);
        imagesavealpha($thumb, true);

        try {
            if ($format === 'png') {
                ob_start();
                $written = imagepng($thumb);
                $data = ob_get_clean();

                if (! $written || ! is_string($data) || $data === '') {
                    return false;
                }

                $disk->put($path, $data);

                return true;
            }

            return $this->encodeAndStore($thumb, $format, $path, $disk);
        } finally {
            imagedestroy($thumb);
        }
    }

    private function buildLqip(\GdImage $source): ?string
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            return null;
        }

        $width = 24;
        $height = max(1, (int) round($sourceHeight * ($width / $sourceWidth)));

        $thumb = imagescale($source, $width, $height);
        if (! $thumb) {
            return null;
        }

        try {
            ob_start();
            $written = function_exists('imagewebp') ? imagewebp($thumb, null, 40) : imagepng($thumb);
            $data = ob_get_clean();

            if (! $written || ! is_string($data) || $data === '') {
                return null;
            }

            $mime = function_exists('imagewebp') ? 'image/webp' : 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode($data);
        } finally {
            imagedestroy($thumb);
        }
    }

    private function elapsedMs(float $startedAt): int
    {
        return max(0, (int) round((microtime(true) - $startedAt) * 1000));
    }

    private function storeLog(Job $job, array $attributes): void
    {
        try {
            AiImageGenerationLog::query()->create(array_merge([
                'job_id' => $job->getKey(),
                'company_id' => $job->company?->getKey(),
                'source_type' => 'job',
                'source_id' => $job->getKey(),
                'source_title' => $job->name,
            ], $attributes));
        } catch (\Throwable $e) {
            Log::warning('Failed to store AI image generation log', [
                'job_id' => $job->getKey(),
                'slot_type' => $attributes['slot_type'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function extractUsage(array $responseJson): array
    {
        $usage = $responseJson['usage'] ?? [];
        $details = $usage['input_tokens_details'] ?? [];

        return [
            'input_tokens' => isset($usage['input_tokens']) ? (int) $usage['input_tokens'] : null,
            'input_text_tokens' => isset($details['text_tokens']) ? (int) $details['text_tokens'] : null,
            'input_image_tokens' => isset($details['image_tokens']) ? (int) $details['image_tokens'] : null,
            'output_tokens' => isset($usage['output_tokens']) ? (int) $usage['output_tokens'] : null,
            'total_tokens' => isset($usage['total_tokens']) ? (int) $usage['total_tokens'] : null,
        ];
    }

    private function estimateCost(string $model, string $quality, string $size, array $usage): array
    {
        $inputTextCost = null;
        $inputImageCost = null;
        $outputCost = null;

        if ($model === 'gpt-image-2') {
            $inputTextCost = $this->costFromTokens($usage['input_text_tokens'], self::GPT_IMAGE_2_PRICING['input_text_per_million']);
            $inputImageCost = $this->costFromTokens($usage['input_image_tokens'], self::GPT_IMAGE_2_PRICING['input_image_per_million']);
            $outputCost = $this->costFromTokens($usage['output_tokens'], self::GPT_IMAGE_2_PRICING['output_per_million']);
        } else {
            $outputCost = data_get(self::LEGACY_OUTPUT_PRICING, "{$model}.{$size}.{$quality}");
        }

        $total = null;
        if ($outputCost !== null || $inputTextCost !== null || $inputImageCost !== null) {
            $total = (float) (($inputTextCost ?? 0) + ($inputImageCost ?? 0) + ($outputCost ?? 0));
        }

        return [
            'input_text_cost_usd' => $inputTextCost,
            'input_image_cost_usd' => $inputImageCost,
            'output_cost_usd' => $outputCost,
            'total_cost_usd' => $total,
            'pricing_mode' => $model === 'gpt-image-2' ? 'token-based' : 'legacy-output-table',
        ];
    }

    private function costFromTokens(?int $tokens, float $ratePerMillion): ?float
    {
        if ($tokens === null) {
            return null;
        }

        return round(($tokens / 1000000) * $ratePerMillion, 6);
    }

    private function buildResponseMeta(
        array $responseJson,
        array $headers,
        array $references,
        string $prompt,
        string $type,
        int $latencyMs,
        ?array $cost = null
    ): array {
        $usage = $this->extractUsage($responseJson);

        return [
            'slot_type' => $type,
            'prompt_preview' => Str::limit(preg_replace('/\s+/', ' ', trim($prompt)), 1000, '...'),
            'reference_filenames' => array_values(array_map(fn (array $ref) => $ref['filename'], $references)),
            'usage' => $usage,
            'cost' => $cost,
            'response' => $this->sanitizeResponseJson($responseJson),
            'headers' => [
                'x-request-id' => $headers['x-request-id'][0] ?? null,
                'openai-processing-ms' => $headers['openai-processing-ms'][0] ?? null,
            ],
            'latency_ms' => $latencyMs,
        ];
    }

    private function sanitizeResponseJson(array $responseJson): array
    {
        $json = $responseJson;

        if (isset($json['data']) && is_array($json['data'])) {
            $json['data'] = array_map(function ($item) {
                if (is_array($item)) {
                    unset($item['b64_json']);
                }

                return $item;
            }, $json['data']);
        }

        return $json;
    }

    /**
     * Heuristic: does the job title describe multiple positions (so it should be skipped)?
     * Keyword + count patterns, kept conservative to avoid false skips.
     */
    public function isMultiPositionTitle(string $title): bool
    {
        $title = trim($title);
        if ($title === '') {
            return false;
        }

        // Only explicit "multiple/various/several positions" wording is treated as a
        // multi-position title. Quantity patterns ("Driver x3", "Teacher (5)",
        // "5 positions") and comma/slash/ampersand lists are NOT skipped — they
        // routinely appear in legitimate single-role titles.
        return (bool) preg_match(
            '/\b(multiple|various|several)\s+(positions?|posts?|vacancies|roles?|openings?|jobs?)\b/i',
            $title
        );
    }

    public function applyPlatformFallbacks(Job $job, ?array $selectedPlatforms = null): array
    {
        if (! $this->shouldReuseSelectedPlatformImages()) {
            return [];
        }

        $selectedPlatforms ??= array_values(array_intersect(
            self::AUTO_PLATFORM_FIELDS,
            json_decode((string) setting('ai_social_image_platforms', '[]'), true) ?: []
        ));

        if (empty($selectedPlatforms)) {
            return [];
        }

        $changes = [];

        foreach (self::AUTO_PLATFORM_FIELDS as $targetField) {
            if (in_array($targetField, $selectedPlatforms, true)) {
                continue;
            }

            $sourceField = $this->fallbackSourceField($job, $targetField, $selectedPlatforms);
            if (! $sourceField) {
                continue;
            }

            $sourcePath = trim((string) ($job->{$sourceField} ?? ''));
            if ($sourcePath === '') {
                continue;
            }

            if ((string) ($job->{$targetField} ?? '') === $sourcePath) {
                continue;
            }

            $job->{$targetField} = $sourcePath;
            $changes[$targetField] = $sourceField;

            $sourceVariants = $job->imageVariantsFor($sourceField);
            if ($sourceVariants !== []) {
                $job->image_variants = array_merge((array) $job->image_variants, [$targetField => $sourceVariants]);
            }
        }

        if ($changes !== []) {
            $job->save();
        }

        return $changes;
    }

    private function fallbackSourceField(Job $job, string $targetField, array $selectedPlatforms): ?string
    {
        $priority = match ($targetField) {
            'whatsapp_image' => ['whatsapp_image', 'tiktok_image', 'facebook_image', 'linkedin_image'],
            'tiktok_image' => ['tiktok_image', 'whatsapp_image', 'facebook_image', 'linkedin_image'],
            'facebook_image' => ['facebook_image', 'whatsapp_image', 'tiktok_image', 'linkedin_image'],
            'linkedin_image' => ['linkedin_image', 'whatsapp_image', 'tiktok_image', 'facebook_image'],
            default => self::AUTO_PLATFORM_FIELDS,
        };

        foreach ($priority as $field) {
            if (! in_array($field, $selectedPlatforms, true)) {
                continue;
            }

            if (trim((string) ($job->{$field} ?? '')) !== '') {
                return $field;
            }
        }

        return null;
    }
}
