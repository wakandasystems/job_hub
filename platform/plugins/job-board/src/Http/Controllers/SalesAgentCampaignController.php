<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Assets;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Jobs\GenerateSalesAgentPosterJob;
use Botble\JobBoard\Jobs\SendSalesAgentCampaignLinkJob;
use Botble\JobBoard\Models\SalesAgent;
use Botble\JobBoard\Models\SalesAgentCampaign;
use Botble\JobBoard\Models\SalesAgentCampaignVersion;
use Botble\JobBoard\Models\SalesAgentMarketingImage;
use Botble\JobBoard\Services\OpenAiImageService;
use Botble\Media\Facades\RvMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesAgentCampaignController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Sales Agents', route('sales-agents.index'))
            ->add('Marketing Campaigns', route('sales-agent-campaigns.index'));
    }

    public function index()
    {
        Assets::usingVueJS();

        $this->pageTitle('Marketing Campaigns');

        $campaigns = SalesAgentCampaign::query()
            ->with('latestMarketingImage')
            ->withCount('clicks')
            ->orderByDesc('is_active')
            ->latest()
            ->paginate(20);

        $nakiaImageUrl = $this->settingImageUrl('sales_agent_nakia_image') ?: $this->settingImageUrl('auto_cv_bot_persona_image');
        $logoImageUrl = $this->settingImageUrl('sales_agent_logo_image');
        $defaultCommissionRate = (float) setting('sales_agent_default_commission_rate', 10);
        $globalDiscountRate = (float) setting('sales_agent_global_discount_rate', 10);
        $samples = $campaigns->getCollection()
            ->mapWithKeys(function (SalesAgentCampaign $campaign): array {
                $image = $campaign->latestMarketingImage;

                if (! $image) {
                    return [];
                }

                return [
                    $campaign->getKey() => $this->marketingImagePayload($image, $campaign),
                ];
            })
            ->all();

        return view(
            'plugins/job-board::sales-agent-campaigns.index',
            compact('campaigns', 'nakiaImageUrl', 'logoImageUrl', 'defaultCommissionRate', 'globalDiscountRate', 'samples')
        );
    }

    public function generatedImages(Request $request)
    {
        $this->pageTitle('Generated Marketing Images');

        $query = SalesAgentMarketingImage::query()
            ->with(['salesAgent', 'campaign'])
            ->latest();

        if ($request->filled('sales_agent_id')) {
            $query->where('sales_agent_id', $request->integer('sales_agent_id'));
        }

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->integer('campaign_id'));
        }

        if ($request->filled('status') && in_array($request->input('status'), ['generating', 'completed', 'failed'], true)) {
            $query->where('status', $request->input('status'));
        }

        $images = $query->paginate(24)->withQueryString();
        $agents = SalesAgent::query()->orderBy('name')->get(['id', 'name']);
        $campaigns = SalesAgentCampaign::query()->orderBy('name')->get(['id', 'name']);

        return view('plugins/job-board::sales-agent-campaigns.generated-images', compact('images', 'agents', 'campaigns'));
    }

    public function destroyGeneratedImage(SalesAgentMarketingImage $salesAgentMarketingImage, Request $request): BaseHttpResponse
    {
        if ($salesAgentMarketingImage->image_path) {
            Storage::disk('public')->delete($salesAgentMarketingImage->image_path);
        }

        $salesAgentMarketingImage->delete();

        return $this
            ->httpResponse()
            ->setMessage('Marketing image deleted.')
            ->setNextUrl($this->generatedImagesRedirectUrl($request));
    }

    public function bulkDestroyGeneratedImages(Request $request): BaseHttpResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $images = SalesAgentMarketingImage::query()->whereIn('id', $data['ids'])->get();

        foreach ($images as $image) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        SalesAgentMarketingImage::query()
            ->whereIn('id', $images->pluck('id'))
            ->delete();

        return $this
            ->httpResponse()
            ->setNextUrl($this->generatedImagesRedirectUrl($request))
            ->setMessage($images->count() . ' marketing image(s) deleted.');
    }

    private function generatedImagesRedirectUrl(Request $request): string
    {
        $referer = $request->headers->get('referer');

        if ($referer && str_contains($referer, route('sales-agent-campaigns.generated-images'))) {
            return $referer;
        }

        return route('sales-agent-campaigns.generated-images');
    }

    public function create()
    {
        Assets::addStyles('datepicker')->addScripts('datepicker');

        $this->pageTitle('Add Marketing Campaign');

        $campaign = new SalesAgentCampaign();

        return view('plugins/job-board::sales-agent-campaigns.create', compact('campaign'));
    }

    public function store(Request $request): BaseHttpResponse
    {
        $campaign = SalesAgentCampaign::query()->create($this->validatedData($request));
        $this->recordVersion($campaign, 'Initial version');

        return $this
            ->httpResponse()
            ->setMessage('Campaign created.')
            ->setNextUrl(route('sales-agent-campaigns.index'));
    }

    public function edit(SalesAgentCampaign $salesAgentCampaign)
    {
        Assets::addStyles('datepicker')->addScripts('datepicker');

        $this->pageTitle('Edit Campaign — ' . $salesAgentCampaign->name);

        $campaign = $salesAgentCampaign;
        $versions = Schema::hasTable('jb_sales_agent_campaign_versions')
            ? $campaign->versions()->with(['creator', 'restoredFrom'])->limit(30)->get()
            : collect();
        $activeTab = request('tab', 'details');

        return view('plugins/job-board::sales-agent-campaigns.edit', compact('campaign', 'versions', 'activeTab'));
    }

    public function analyzeInspiration(Request $request, OpenAiImageService $imageService): JsonResponse
    {
        $request->validate([
            'inspiration_image_file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
        ]);

        $result = $imageService->analyzeSalesAgentInspiration($request->file('inspiration_image_file'));

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'error' => $result['message'] ?? 'Could not analyze the inspiration image.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'prompt_template' => $result['prompt_template'],
                'summary' => $result['summary'],
                'editable_regions' => $result['editable_regions'] ?? [],
            ],
        ]);
    }

    public function links(SalesAgentCampaign $salesAgentCampaign, Request $request)
    {
        $this->pageTitle('Campaign Links - ' . $salesAgentCampaign->name);

        $agents = SalesAgent::query()
            ->when($request->boolean('active_only', true), fn ($query) => $query->where('status', 'active'))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $keyword = trim((string) $request->input('q'));
                $query->where(function ($builder) use ($keyword): void {
                    $builder->where('name', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('phone', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('code', 'LIKE', '%' . $keyword . '%');
                });
            })
            ->withCount([
                'campaignClicks as campaign_clicks_count' => fn ($query) => $query->where('campaign_id', $salesAgentCampaign->getKey()),
            ])
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        $campaign = $salesAgentCampaign;

        return view('plugins/job-board::sales-agent-campaigns.links', compact('campaign', 'agents'));
    }

    public function update(SalesAgentCampaign $salesAgentCampaign, Request $request): BaseHttpResponse
    {
        $salesAgentCampaign->fill($this->validatedData($request, $salesAgentCampaign));
        $hasChanges = $salesAgentCampaign->isDirty();
        $salesAgentCampaign->save();

        if ($hasChanges) {
            $this->recordVersion($salesAgentCampaign, 'Saved changes');
        }

        return $this
            ->httpResponse()
            ->setMessage('Campaign updated.')
            ->setNextUrl(route('sales-agent-campaigns.index'));
    }

    public function restoreVersion(
        SalesAgentCampaign $salesAgentCampaign,
        SalesAgentCampaignVersion $salesAgentCampaignVersion
    ): BaseHttpResponse {
        abort_unless((int) $salesAgentCampaignVersion->campaign_id === (int) $salesAgentCampaign->getKey(), 404);

        $versionNumber = $salesAgentCampaignVersion->getKey();

        $this->recordVersion($salesAgentCampaign, 'Before restore from version #' . $versionNumber);
        $salesAgentCampaign->applySnapshot($salesAgentCampaignVersion->snapshot ?? []);
        $this->recordVersion($salesAgentCampaign, 'Restored from version #' . $versionNumber, $salesAgentCampaignVersion->getKey());

        return $this
            ->httpResponse()
            ->setMessage('Campaign restored from version #' . $versionNumber . '.')
            ->setNextUrl(route('sales-agent-campaigns.edit', [$salesAgentCampaign->getKey(), 'tab' => 'history']));
    }

    public function toggleActive(SalesAgentCampaign $salesAgentCampaign): BaseHttpResponse
    {
        $salesAgentCampaign->update(['is_active' => ! $salesAgentCampaign->is_active]);

        return $this
            ->httpResponse()
            ->setData(['is_active' => $salesAgentCampaign->is_active])
            ->setMessage($salesAgentCampaign->is_active ? 'Campaign activated.' : 'Campaign deactivated.');
    }

    public function destroy(SalesAgentCampaign $salesAgentCampaign, Request $request): BaseHttpResponse|RedirectResponse
    {
        $imagePaths = SalesAgentMarketingImage::query()
            ->where('campaign_id', $salesAgentCampaign->getKey())
            ->whereNotNull('image_path')
            ->pluck('image_path')
            ->filter()
            ->values()
            ->all();

        if ($imagePaths !== []) {
            Storage::disk('public')->delete($imagePaths);
        }

        SalesAgentMarketingImage::query()
            ->where('campaign_id', $salesAgentCampaign->getKey())
            ->delete();

        $salesAgentCampaign->delete();

        if (! $request->expectsJson()) {
            return redirect()
                ->route('sales-agent-campaigns.index')
                ->with('success_msg', 'Campaign deleted.');
        }

        return $this
            ->httpResponse()
            ->setMessage('Campaign deleted.')
            ->setNextUrl(route('sales-agent-campaigns.index'));
    }

    public function exportLinks(SalesAgentCampaign $salesAgentCampaign, Request $request): StreamedResponse
    {
        $filename = 'sales-agent-links-' . $salesAgentCampaign->getKey() . '.csv';
        $activeOnly = $request->boolean('active_only', true);
        $keyword = trim((string) $request->input('q', ''));

        return response()->streamDownload(function () use ($salesAgentCampaign, $activeOnly, $keyword): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['agent_id', 'agent_name', 'phone', 'code', 'campaign', 'product', 'clicks', 'share_link']);

            SalesAgent::query()
                ->when($activeOnly, fn ($query) => $query->where('status', 'active'))
                ->when($keyword !== '', function ($query) use ($keyword): void {
                    $query->where(function ($builder) use ($keyword): void {
                        $builder->where('name', 'LIKE', '%' . $keyword . '%')
                            ->orWhere('phone', 'LIKE', '%' . $keyword . '%')
                            ->orWhere('code', 'LIKE', '%' . $keyword . '%');
                    });
                })
                ->withCount([
                    'campaignClicks as campaign_clicks_count' => fn ($query) => $query->where('campaign_id', $salesAgentCampaign->getKey()),
                ])
                ->orderBy('name')
                ->chunkById(200, function ($agents) use ($handle, $salesAgentCampaign): void {
                    foreach ($agents as $agent) {
                        fputcsv($handle, [
                            $agent->getKey(),
                            $agent->name,
                            $agent->phone,
                            $agent->code,
                            $salesAgentCampaign->name,
                            $salesAgentCampaign->resolvedProductLabel(),
                            $agent->campaign_clicks_count,
                            $salesAgentCampaign->shareUrlForAgent($agent),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function sendLink(SalesAgentCampaign $salesAgentCampaign, SalesAgent $salesAgent): BaseHttpResponse
    {
        if ($salesAgent->status !== 'active') {
            return $this->httpResponse()->setError()->setMessage('Only active agents can receive share links.');
        }

        SendSalesAgentCampaignLinkJob::dispatch($salesAgent->getKey(), $salesAgentCampaign->getKey());

        return $this->httpResponse()->setMessage('Campaign link queued for WhatsApp send to ' . $salesAgent->name . '.');
    }

    public function sendLinksBulk(SalesAgentCampaign $salesAgentCampaign, Request $request): BaseHttpResponse
    {
        $data = $request->validate([
            'agent_ids' => ['nullable', 'array'],
            'agent_ids.*' => ['integer', 'exists:jb_sales_agents,id'],
            'active_only' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        $activeOnly = array_key_exists('active_only', $data)
            ? (bool) $data['active_only']
            : true;

        $query = SalesAgent::query()
            ->when(empty($data['agent_ids']) && $activeOnly, fn ($builder) => $builder->where('status', 'active'))
            ->when(! empty($data['agent_ids']), fn ($builder) => $builder->whereIn('id', $data['agent_ids']))
            ->when(empty($data['agent_ids']) && ! empty($data['q']), function ($builder) use ($data): void {
                $keyword = trim((string) $data['q']);
                $builder->where(function ($inner) use ($keyword): void {
                    $inner->where('name', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('phone', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('code', 'LIKE', '%' . $keyword . '%');
                });
            });

        $count = 0;

        $query->orderBy('id')->chunkById(200, function ($agents) use ($salesAgentCampaign, &$count): void {
            foreach ($agents as $agent) {
                SendSalesAgentCampaignLinkJob::dispatch($agent->getKey(), $salesAgentCampaign->getKey());
                $count++;
            }
        });

        return $this->httpResponse()->setMessage("Queued {$count} campaign link message(s) for WhatsApp send.");
    }

    public function generateSample(SalesAgentCampaign $salesAgentCampaign, Request $request): BaseHttpResponse
    {
        $data = $request->validate([
            'subject_mode' => ['nullable', Rule::in(array_keys(SalesAgentMarketingImage::subjectModes()))],
        ]);

        $subjectMode = $data['subject_mode'] ?? 'nakia';
        $agent = $this->sampleAgentForSubjectMode($subjectMode);

        if (! $agent) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage('No sales agent with a saved marketing photo is available for that subject selection.');
        }

        $lockKey = sprintf('sales-agent-campaign:%d:sample-generate:%s', $salesAgentCampaign->getKey(), $subjectMode);
        $queued = false;

        $image = Cache::lock($lockKey, 10)->block(3, function () use ($agent, $salesAgentCampaign, $subjectMode, &$queued) {
            $image = SalesAgentMarketingImage::query()
                ->where('sales_agent_id', $agent->getKey())
                ->where('campaign_id', $salesAgentCampaign->getKey())
                ->where('subject_mode', $subjectMode)
                ->where('status', 'generating')
                ->latest('id')
                ->first();

            if ($image) {
                return $image;
            }

            $image = SalesAgentMarketingImage::query()->create([
                'sales_agent_id' => $agent->getKey(),
                'campaign_id' => $salesAgentCampaign->getKey(),
                'subject_mode' => $subjectMode,
                'status' => 'generating',
            ]);

            GenerateSalesAgentPosterJob::dispatch($image->getKey());
            $queued = true;

            return $image;
        });

        return $this
            ->httpResponse()
            ->setData($this->marketingImagePayload($image, $salesAgentCampaign))
            ->setMessage($queued
                ? 'Sample image generation queued. This page will update when it is ready.'
                : 'A sample image for this campaign is already queued. This page will update when it is ready.');
    }

    public function sampleStatus(
        SalesAgentCampaign $salesAgentCampaign,
        SalesAgentMarketingImage $salesAgentMarketingImage
    ): BaseHttpResponse {
        if ((int) $salesAgentMarketingImage->campaign_id !== (int) $salesAgentCampaign->getKey()) {
            abort(404);
        }

        $salesAgentMarketingImage->refresh();

        return $this
            ->httpResponse()
            ->setData($this->marketingImagePayload($salesAgentMarketingImage, $salesAgentCampaign));
    }

    public function updateSettings(Request $request): BaseHttpResponse
    {
        $data = $request->validate([
            'default_commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'global_discount_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'nakia_image' => ['nullable', 'image', 'max:5120'],
            'logo_image' => ['nullable', 'image', 'max:5120'],
            'clear_nakia_image' => ['nullable', 'boolean'],
            'clear_logo_image' => ['nullable', 'boolean'],
        ]);

        setting()
            ->set('sales_agent_default_commission_rate', $data['default_commission_rate'])
            ->set('sales_agent_global_discount_rate', $data['global_discount_rate'])
            ->save();

        $uploaded = [];

        if ($request->boolean('clear_nakia_image')) {
            setting()->set('sales_agent_nakia_image', '')->save();
            $uploaded['nakia_image_url'] = RvMedia::getDefaultImage();
        }

        if ($request->boolean('clear_logo_image')) {
            setting()->set('sales_agent_logo_image', '')->save();
            $uploaded['logo_image_url'] = RvMedia::getDefaultImage();
        }

        if ($request->hasFile('nakia_image')) {
            $this->uploadSettingImage($request->file('nakia_image'), 'sales_agent_nakia_image');
            $uploaded['nakia_image_url'] = $this->settingImageUrl('sales_agent_nakia_image');
        }

        if ($request->hasFile('logo_image')) {
            $this->uploadSettingImage($request->file('logo_image'), 'sales_agent_logo_image');
            $uploaded['logo_image_url'] = $this->settingImageUrl('sales_agent_logo_image');
        }

        return $this
            ->httpResponse()
            ->setData($uploaded)
            ->setMessage('Sales agent settings updated.')
            ->setNextUrl(route('sales-agent-campaigns.index'));
    }

    private function uploadSettingImage(UploadedFile $file, string $settingKey): void
    {
        $result = RvMedia::handleUpload($file, 0, 'sales-agents');

        if ($result['error']) {
            return;
        }

        setting()->set($settingKey, $result['data']->url)->save();
    }

    private function settingImageUrl(string $settingKey): ?string
    {
        $url = trim((string) setting($settingKey, ''));

        return $url !== '' ? RvMedia::getImageUrl($url) : null;
    }

    private function marketingImagePayload(
        SalesAgentMarketingImage $image,
        SalesAgentCampaign $campaign
    ): array {
        $imageUrl = $image->status === 'completed' ? $image->imageUrl() : null;

        return [
            'campaign_id' => $campaign->getKey(),
            'image_id' => $image->getKey(),
            'status' => $image->status,
            'subject_mode' => $image->subject_mode,
            'subject_label' => SalesAgentMarketingImage::subjectModes()[$image->subject_mode] ?? $image->subject_mode,
            'error_message' => $image->error_message,
            'image_url' => $imageUrl,
            'download_url' => $imageUrl ? route('sales-agents.marketing-images.download', [$image->sales_agent_id, $image->getKey()]) : null,
            'status_url' => route('sales-agent-campaigns.sample-status', [$campaign->getKey(), $image->getKey()]),
        ];
    }

    private function validatedData(Request $request, ?SalesAgentCampaign $campaign = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'product_type' => ['required', 'in:' . implode(',', array_keys(SalesAgentCampaign::productTypeOptions()))],
            'product_label' => ['nullable', 'string', 'max:120'],
            'landing_headline' => ['nullable', 'string', 'max:190'],
            'landing_body' => ['nullable', 'string', 'max:5000'],
            'landing_cta_text' => ['nullable', 'string', 'max:120'],
            'share_message_template' => ['nullable', 'string', 'max:5000'],
            'prompt_template' => ['required', 'string'],
            'reconstruction_layout' => ['nullable', 'string', 'max:20000'],
            'inspiration_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
            'remove_inspiration_image' => ['nullable', 'boolean'],
            'aspect_ratio' => ['required', 'in:portrait_4_5,square_1_1,landscape_16_9'],
            'promo_price' => ['nullable', 'string', 'max:30'],
            'promo_original_price' => ['nullable', 'string', 'max:30'],
            'promo_end_date' => ['nullable', 'date_format:' . BaseHelper::getDateFormat()],
        ], [
            'promo_end_date.date_format' => 'The promo end date must be a valid date in ' . BaseHelper::getDateFormat() . ' format.',
        ]);

        $data['promo_end_date'] = BaseHelper::parseDate($data['promo_end_date'] ?? null)?->toDateString();
        $data['product_label'] = trim((string) ($data['product_label'] ?? '')) ?: null;
        $data['landing_headline'] = trim((string) ($data['landing_headline'] ?? '')) ?: null;
        $data['landing_body'] = trim((string) ($data['landing_body'] ?? '')) ?: null;
        $data['landing_cta_text'] = trim((string) ($data['landing_cta_text'] ?? '')) ?: null;
        $data['share_message_template'] = trim((string) ($data['share_message_template'] ?? '')) ?: null;
        $data['prompt_template'] = trim((string) $data['prompt_template']);
        $data['reconstruction_layout'] = $this->decodeReconstructionLayout($data['reconstruction_layout'] ?? null);
        $data['promo_price'] = trim((string) ($data['promo_price'] ?? '')) ?: null;
        $data['promo_original_price'] = trim((string) ($data['promo_original_price'] ?? '')) ?: null;
        $data['inspiration_images'] = $campaign?->inspirationImages() ?? [];

        if ($request->boolean('remove_inspiration_image')) {
            $data['inspiration_images'] = [];
        }

        if ($request->hasFile('inspiration_image_file')) {
            $result = RvMedia::handleUpload($request->file('inspiration_image_file'), 0, 'sales-agents/inspirations');

            if ($result['error']) {
                abort(422, $result['message'] ?? 'Could not upload the inspiration image.');
            }

            $storedPath = $result['data']->url ?? null;

            if ($storedPath) {
                $data['inspiration_images'] = [$storedPath];
            }
        }

        $isPromo = filled($data['promo_price']) && filled($data['promo_original_price']) && filled($data['promo_end_date']);

        if (! $isPromo) {
            $data['promo_original_price'] = null;
            $data['promo_end_date'] = null;
        }

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function decodeReconstructionLayout(?string $raw): ?array
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function recordVersion(
        SalesAgentCampaign $campaign,
        string $label,
        ?int $restoredFromVersionId = null
    ): SalesAgentCampaignVersion {
        if (! Schema::hasTable('jb_sales_agent_campaign_versions')) {
            return new SalesAgentCampaignVersion();
        }

        return $campaign->versions()->create([
            'created_by' => auth()->id(),
            'restored_from_version_id' => $restoredFromVersionId,
            'label' => $label,
            'snapshot' => $campaign->snapshotData(),
        ]);
    }

    private function sampleAgentForSubjectMode(string $subjectMode): ?SalesAgent
    {
        if ($subjectMode === 'nakia') {
            $agent = SalesAgent::query()->firstOrCreate(
                ['code' => 'NAKIA-SAMPLE'],
                [
                    'name' => 'Nakia Banda',
                    'phone' => '+260970766123',
                    'email' => 'nakia@wakandajobs.com',
                    'commission_rate' => (float) setting('sales_agent_default_commission_rate', 10),
                    'status' => 'inactive',
                    'notes' => 'Internal sample agent used for campaign preview images.',
                ]
            );

            if (! $agent->photo) {
                $nakiaPath = trim((string) setting('sales_agent_nakia_image', ''))
                    ?: trim((string) setting('auto_cv_bot_persona_image', ''));

                if ($nakiaPath !== '') {
                    $agent->update(['photo' => $nakiaPath]);
                }
            }

            return $agent;
        }

        return SalesAgent::query()
            ->where('status', 'active')
            ->whereNotNull('photo')
            ->where('photo', '!=', '')
            ->orderByDesc('updated_at')
            ->first();
    }
}
