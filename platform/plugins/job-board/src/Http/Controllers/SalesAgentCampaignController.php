<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Assets;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Jobs\GenerateSalesAgentPosterJob;
use Botble\JobBoard\Models\SalesAgent;
use Botble\JobBoard\Models\SalesAgentCampaign;
use Botble\JobBoard\Models\SalesAgentMarketingImage;
use Botble\Media\Facades\RvMedia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

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

        return view('plugins/job-board::sales-agent-campaigns.edit', compact('campaign'));
    }

    public function update(SalesAgentCampaign $salesAgentCampaign, Request $request): BaseHttpResponse
    {
        $salesAgentCampaign->update($this->validatedData($request));

        return $this
            ->httpResponse()
            ->setMessage('Campaign updated.')
            ->setNextUrl(route('sales-agent-campaigns.index'));
    }

    public function destroy(SalesAgentCampaign $salesAgentCampaign): BaseHttpResponse
    {
        $salesAgentCampaign->delete();

        return $this
            ->httpResponse()
            ->setMessage('Campaign deleted.');
    }

    public function generateSample(SalesAgentCampaign $salesAgentCampaign): BaseHttpResponse
    {
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

        $lockKey = sprintf('sales-agent-campaign:%d:sample-generate', $salesAgentCampaign->getKey());
        $queued = false;

        $image = Cache::lock($lockKey, 10)->block(3, function () use ($agent, $salesAgentCampaign, &$queued) {
            $image = SalesAgentMarketingImage::query()
                ->where('sales_agent_id', $agent->getKey())
                ->where('campaign_id', $salesAgentCampaign->getKey())
                ->where('subject_mode', 'nakia')
                ->where('status', 'generating')
                ->latest('id')
                ->first();

            if ($image) {
                return $image;
            }

            $image = SalesAgentMarketingImage::query()->create([
                'sales_agent_id' => $agent->getKey(),
                'campaign_id' => $salesAgentCampaign->getKey(),
                'subject_mode' => 'nakia',
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
            'error_message' => $image->error_message,
            'image_url' => $imageUrl,
            'download_url' => $imageUrl ? route('sales-agents.marketing-images.download', [$image->sales_agent_id, $image->getKey()]) : null,
            'status_url' => route('sales-agent-campaigns.sample-status', [$campaign->getKey(), $image->getKey()]),
        ];
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'prompt_template' => ['required', 'string'],
            'aspect_ratio' => ['required', 'in:portrait_4_5,square_1_1,landscape_16_9'],
            'promo_price' => ['nullable', 'string', 'max:30'],
            'promo_original_price' => ['nullable', 'string', 'max:30'],
            'promo_end_date' => ['nullable', 'date_format:' . BaseHelper::getDateFormat()],
        ], [
            'promo_end_date.date_format' => 'The promo end date must be a valid date in ' . BaseHelper::getDateFormat() . ' format.',
        ]);

        $data['promo_end_date'] = BaseHelper::parseDate($data['promo_end_date'] ?? null)?->toDateString();
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
