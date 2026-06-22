<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Models\AiImageGenerationLog;
use Botble\JobBoard\Models\Job;
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
                $response = Http::timeout(30)->get($pathOrUrl);

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
