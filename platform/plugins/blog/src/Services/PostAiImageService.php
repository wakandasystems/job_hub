<?php

namespace Botble\Blog\Services;

use Botble\Blog\Models\Post;
use Botble\JobBoard\Models\AiImageGenerationLog;
use Botble\Media\Facades\RvMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PostAiImageService
{
    private const ENDPOINT = 'https://api.openai.com/v1/images/generations';

    private const GPT_IMAGE_2_SIZES = [
        'image' => '1216x640',
        'cover_image' => '1536x512',
    ];

    private const LEGACY_SIZES = [
        'image' => '1536x1024',
        'cover_image' => '1536x1024',
    ];

    private const TARGET_DIMENSIONS = [
        'image' => [1200, 630],
        'cover_image' => [1800, 540],
    ];

    private const FOLDERS = [
        'image' => 'blog/posts/thumbnails',
        'cover_image' => 'blog/posts/covers',
    ];

    public function apiKey(): string
    {
        return trim((string) (setting('openai_api_key') ?: env('OPENAI_API_KEY', '')));
    }

    public function isConfigured(): bool
    {
        return $this->apiKey() !== '';
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

    public function generate(array $context, string $slotType): array
    {
        if (! isset(self::FOLDERS[$slotType])) {
            return ['ok' => false, 'message' => 'Unsupported blog image slot.'];
        }

        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'OpenAI API key is not configured.'];
        }

        $startedAt = microtime(true);
        $model = $this->model();
        $quality = $this->quality();
        $background = $this->background();
        $format = $this->outputFormat();
        $size = $this->sizeFor($slotType);
        [$targetWidth, $targetHeight] = self::TARGET_DIMENSIONS[$slotType];
        $sourceId = isset($context['post_id']) ? (int) $context['post_id'] : null;
        $sourceTitle = trim((string) ($context['title'] ?? '')) ?: 'Blog post';

        $prompt = $this->buildPrompt($context, $slotType);
        if (! $prompt) {
            $this->storeLog([
                'source_type' => 'blog_post',
                'source_id' => $sourceId,
                'source_title' => $sourceTitle,
                'slot_type' => $slotType,
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
                'error_message' => 'A title is required before generating a blog image.',
            ]);

            return ['ok' => false, 'message' => 'A title is required before generating a blog image.'];
        }

        $response = Http::timeout(180)
            ->withToken($this->apiKey())
            ->acceptJson()
            ->post(self::ENDPOINT, array_filter([
                'model' => $model,
                'prompt' => $prompt,
                'size' => $size,
                'quality' => $quality,
                'background' => $background,
                'output_format' => $format,
                'output_compression' => in_array($format, ['jpeg', 'webp'], true)
                    ? $this->outputCompression()
                    : null,
            ], static fn ($value) => $value !== null && $value !== ''));

        $body = $response->json();

        if (! $response->successful()) {
            $responseMeta = $this->buildResponseMeta($body, $prompt, $slotType, $this->elapsedMs($startedAt));
            $this->storeLog([
                'source_type' => 'blog_post',
                'source_id' => $sourceId,
                'source_title' => $sourceTitle,
                'slot_type' => $slotType,
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
                'error_message' => $body['error']['message'] ?? 'OpenAI image generation failed.',
                'response_meta' => $responseMeta,
            ]);

            return [
                'ok' => false,
                'message' => $body['error']['message'] ?? 'OpenAI image generation failed.',
                'request_id' => $response->header('x-request-id'),
            ];
        }

        $base64 = data_get($body, 'data.0.b64_json');
        if (! is_string($base64) || $base64 === '') {
            $this->storeLog([
                'source_type' => 'blog_post',
                'source_id' => $sourceId,
                'source_title' => $sourceTitle,
                'slot_type' => $slotType,
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
                'error_message' => 'OpenAI did not return image data.',
                'response_meta' => $this->buildResponseMeta($body, $prompt, $slotType, $this->elapsedMs($startedAt)),
            ]);

            return [
                'ok' => false,
                'message' => 'OpenAI did not return image data.',
                'request_id' => $response->header('x-request-id'),
            ];
        }

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            $this->storeLog([
                'source_type' => 'blog_post',
                'source_id' => $sourceId,
                'source_title' => $sourceTitle,
                'slot_type' => $slotType,
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
                'error_message' => 'OpenAI returned invalid image data.',
                'response_meta' => $this->buildResponseMeta($body, $prompt, $slotType, $this->elapsedMs($startedAt)),
            ]);

            return [
                'ok' => false,
                'message' => 'OpenAI returned invalid image data.',
                'request_id' => $response->header('x-request-id'),
            ];
        }

        $extension = $this->fileExtension();
        $tempPath = tempnam(sys_get_temp_dir(), 'blog-ai-');

        if (! $tempPath) {
            $this->storeLog([
                'source_type' => 'blog_post',
                'source_id' => $sourceId,
                'source_title' => $sourceTitle,
                'slot_type' => $slotType,
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
                'error_message' => 'Could not allocate a temporary file for the generated image.',
            ]);

            return ['ok' => false, 'message' => 'Could not allocate a temporary file for the generated image.'];
        }

        $finalTempPath = $tempPath . '.' . $extension;
        File::move($tempPath, $finalTempPath);

        try {
            File::put($finalTempPath, $binary);
            $this->normalizeImage($finalTempPath, $slotType);

            $uploadedFile = new UploadedFile(
                $finalTempPath,
                $this->buildFilename($context['title'] ?? null, $slotType, $extension),
                $this->mimeType($extension),
                null,
                true
            );

            $result = RvMedia::handleUpload($uploadedFile, 0, self::FOLDERS[$slotType], true);
        } finally {
            File::delete($finalTempPath);
        }

        if ($result['error'] ?? true) {
            $this->storeLog([
                'source_type' => 'blog_post',
                'source_id' => $sourceId,
                'source_title' => $sourceTitle,
                'slot_type' => $slotType,
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
                'error_message' => $result['message'] ?? 'Uploading generated image to media failed.',
                'response_meta' => $this->buildResponseMeta($body, $prompt, $slotType, $this->elapsedMs($startedAt)),
            ]);

            return [
                'ok' => false,
                'message' => $result['message'] ?? 'Uploading generated image to media failed.',
                'request_id' => $response->header('x-request-id'),
            ];
        }

        $url = RvMedia::url($result['data']->url);
        $usage = $this->extractUsage($body);
        $cost = $this->estimateCost($model, $quality, $size, $usage);

        $this->storeLog([
            'source_type' => 'blog_post',
            'source_id' => $sourceId,
            'source_title' => $sourceTitle,
            'slot_type' => $slotType,
            'status' => 'success',
            'model' => $model,
            'quality' => $quality,
            'background' => $background,
            'output_format' => $format,
            'output_compression' => $this->outputCompression(),
            'request_size' => $size,
            'target_width' => $targetWidth,
            'target_height' => $targetHeight,
            'stored_path' => $result['data']->url,
            'latency_ms' => $this->elapsedMs($startedAt),
            'input_tokens' => $usage['input_tokens'],
            'input_text_tokens' => $usage['input_text_tokens'],
            'input_image_tokens' => $usage['input_image_tokens'],
            'output_tokens' => $usage['output_tokens'],
            'total_tokens' => $usage['total_tokens'],
            'estimated_cost_usd' => $cost['total_cost_usd'],
            'api_request_id' => $response->header('x-request-id'),
            'response_meta' => $this->buildResponseMeta($body, $prompt, $slotType, $this->elapsedMs($startedAt), $cost),
        ]);

        return [
            'ok' => true,
            'path' => $result['data']->url,
            'url' => $url,
            'request_id' => $response->header('x-request-id'),
        ];
    }

    private function buildPrompt(array $context, string $slotType): ?string
    {
        $title = trim((string) ($context['title'] ?? ''));
        if ($title === '') {
            return null;
        }

        $description = $this->cleanText($context['description'] ?? '');
        $content = $this->cleanText($context['content'] ?? '');

        $slotInstruction = $slotType === 'cover_image'
            ? 'Create a panoramic blog cover hero with a wide cinematic composition and a clean focal area near the center.'
            : 'Create a blog thumbnail / featured image with a strong focal point that reads clearly in cards and social previews.';

        $contentSnippet = $content !== '' ? mb_substr($content, 0, 1200) : '';

        return trim(implode("\n\n", array_filter([
            'Create a professional editorial image for a Wakanda Jobs blog post.',
            $slotInstruction,
            'Style: modern, premium, trustworthy, visually rich, suitable for a careers and business publication.',
            'Do not include readable text, logos, watermarks, screenshots, UI chrome, or social media badges inside the image.',
            'If the article topic is about jobs, hiring, careers, skills, business, or work, lean into an African professional context. Otherwise stay faithful to the article topic.',
            'Article title: ' . $title,
            $description !== '' ? 'Article summary: ' . $description : null,
            $contentSnippet !== '' ? 'Article content context: ' . $contentSnippet : null,
        ])));
    }

    private function cleanText(?string $value): string
    {
        $text = trim(strip_tags((string) $value));
        $text = preg_replace('/\s+/u', ' ', $text) ?: '';

        return trim($text);
    }

    private function sizeFor(string $slotType): string
    {
        if ($this->model() === 'gpt-image-2') {
            return self::GPT_IMAGE_2_SIZES[$slotType];
        }

        return self::LEGACY_SIZES[$slotType];
    }

    private function fileExtension(): string
    {
        return match ($this->outputFormat()) {
            'jpeg' => 'jpg',
            default => $this->outputFormat(),
        };
    }

    private function mimeType(string $extension): string
    {
        return match ($extension) {
            'jpg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }

    private function buildFilename(?string $title, string $slotType, string $extension): string
    {
        $base = Str::slug((string) $title) ?: 'blog-post';
        $suffix = $slotType === 'cover_image' ? 'cover' : 'thumbnail';

        return "{$base}-{$suffix}.{$extension}";
    }

    private function normalizeImage(string $path, string $slotType): void
    {
        [$targetWidth, $targetHeight] = self::TARGET_DIMENSIONS[$slotType];

        $image = RvMedia::imageManager()->read($path);
        $image->cover($targetWidth, $targetHeight);
        $image->save($path);
    }

    private function elapsedMs(float $startedAt): int
    {
        return max(0, (int) round((microtime(true) - $startedAt) * 1000));
    }

    private function storeLog(array $attributes): void
    {
        try {
            AiImageGenerationLog::query()->create($attributes);
        } catch (\Throwable $e) {
            Log::warning('Failed to store blog AI image generation log', [
                'source_id' => $attributes['source_id'] ?? null,
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
            $inputTextCost = $this->costFromTokens($usage['input_text_tokens'], 5.00);
            $inputImageCost = $this->costFromTokens($usage['input_image_tokens'], 8.00);
            $outputCost = $this->costFromTokens($usage['output_tokens'], 30.00);
        } else {
            $legacyPricing = [
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

            $outputCost = data_get($legacyPricing, "{$model}.{$size}.{$quality}");
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
        ];
    }

    private function costFromTokens(?int $tokens, float $ratePerMillion): ?float
    {
        if ($tokens === null) {
            return null;
        }

        return ($tokens / 1_000_000) * $ratePerMillion;
    }

    private function buildResponseMeta(array $responseJson, string $prompt, string $slotType, int $latencyMs, ?array $cost = null): array
    {
        return array_filter([
            'slot_type' => $slotType,
            'latency_ms' => $latencyMs,
            'prompt_excerpt' => mb_substr($prompt, 0, 1000),
            'usage' => $responseJson['usage'] ?? null,
            'cost' => $cost,
            'response' => array_diff_key($responseJson, ['data' => true]),
        ], static fn ($value) => $value !== null && $value !== []);
    }
}
