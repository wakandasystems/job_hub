<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Jobs\GenerateSalesAgentPosterJob;
use Botble\JobBoard\Jobs\SendSalesAgentMarketingImageJob;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AutoApplyOrder;
use Botble\JobBoard\Models\CareerServiceOrder;
use Botble\JobBoard\Models\JobAlertOrder;
use Botble\JobBoard\Models\SalesAgent;
use Botble\JobBoard\Models\SalesAgentCampaign;
use Botble\JobBoard\Models\SalesAgentMarketingImage;
use Botble\JobBoard\Models\VipAlertOrder;
use Botble\JobBoard\Services\OpenAiImageService;
use Botble\JobBoard\Services\SalesAgentService;
use Botble\JobBoard\Services\WhapiSenderService;
use Botble\Media\Facades\RvMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalesAgentController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Sales Agents', route('sales-agents.index'));
    }

    public function index(Request $request)
    {
        $this->pageTitle('Sales Agents');

        $agents = SalesAgent::query()
            ->with('candidateAccount.avatar')
            ->withCount('campaignClicks')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('plugins/job-board::sales-agents.index', compact('agents'));
    }

    public function create()
    {
        $this->pageTitle('Add Sales Agent');

        $agent = new SalesAgent();
        $defaultCommissionRate = (float) setting('sales_agent_default_commission_rate', 10);

        return view('plugins/job-board::sales-agents.create', compact('agent', 'defaultCommissionRate'));
    }

    public function store(Request $request, WhapiSenderService $sender): BaseHttpResponse
    {
        $data = $this->validatedData($request);

        $agent = SalesAgent::query()->create($data);

        $errorMessage = null;
        $sender->sendText($agent->phone, $this->welcomeMessage($agent), $errorMessage);

        $queued = $this->dispatchAllCampaignPosters($agent);

        $suffix = $queued > 0
            ? " {$queued} campaign poster(s) generating and will be sent to their WhatsApp on completion."
            : '';

        return $this
            ->httpResponse()
            ->setMessage('Sales agent created. Welcome message sent.' . $suffix)
            ->setNextUrl(route('sales-agents.edit', $agent->getKey()));
    }

    public function show(SalesAgent $salesAgent)
    {
        $this->pageTitle($salesAgent->name);

        $referrals = $salesAgent->referrals()->latest('first_used_at')->paginate(15, ['*'], 'referrals_page');
        $commissions = $salesAgent->commissions()->latest()->paginate(15, ['*'], 'commissions_page');
        $campaigns = SalesAgentCampaign::query()->where('is_active', true)->latest()->get();
        $marketingImages = $salesAgent->marketingImages()
            ->with('campaign')
            ->latest()
            ->paginate(12, ['*'], 'images_page');

        return view('plugins/job-board::sales-agents.show', compact('salesAgent', 'referrals', 'commissions', 'campaigns', 'marketingImages'));
    }

    public function edit(SalesAgent $salesAgent)
    {
        $this->pageTitle('Edit Sales Agent — ' . $salesAgent->name);

        $agent = $salesAgent->load('candidateAccount.avatar');
        $campaigns = SalesAgentCampaign::query()->where('is_active', true)->latest()->get();
        $latestMarketingImages = $salesAgent->marketingImages()
            ->with('campaign')
            ->latest()
            ->get()
            ->unique('campaign_id')
            ->keyBy('campaign_id');

        $clients = $salesAgent->referrals()
            ->with('account.avatar')
            ->whereNotNull('account_id')
            ->latest()
            ->paginate(15, ['*'], 'clients_page');

        return view('plugins/job-board::sales-agents.edit', compact('agent', 'campaigns', 'latestMarketingImages', 'clients'));
    }

    public function linkClient(SalesAgent $salesAgent, Request $request): BaseHttpResponse
    {
        $data = $request->validate([
            'account_id' => ['required', 'integer', 'exists:jb_accounts,id'],
        ]);

        $alreadyLinked = $salesAgent->referrals()
            ->where('account_id', $data['account_id'])
            ->exists();

        if ($alreadyLinked) {
            return $this->httpResponse()->setError()->setMessage('This candidate is already linked to the agent.');
        }

        $account = Account::query()->findOrFail($data['account_id']);

        $salesAgent->referrals()->create([
            'account_id'   => $account->getKey(),
            'phone'        => $account->whatsapp_number ?: $account->phone,
            'source'       => 'manual',
            'first_used_at' => now(),
        ]);

        return $this->httpResponse()->setMessage($account->name . ' linked as a client.');
    }

    public function createClient(SalesAgent $salesAgent, Request $request): BaseHttpResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'phone'     => ['required', 'string', 'max:30'],
            'email'     => ['nullable', 'email', 'max:120'],
        ]);

        $nameParts  = explode(' ', trim($data['full_name']), 2);
        $firstName  = $nameParts[0];
        $lastName   = $nameParts[1] ?? '';

        $account = Account::query()->create([
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'phone'      => $data['phone'],
            'email'      => $data['email'] ?? null,
            'type'       => AccountTypeEnum::JOB_SEEKER,
            'password'   => bcrypt(Str::random(24)),
        ]);

        $salesAgent->referrals()->create([
            'account_id'    => $account->getKey(),
            'phone'         => $data['phone'],
            'source'        => 'manual',
            'first_used_at' => now(),
        ]);

        return $this->httpResponse()->setMessage($account->name . ' created and linked as a client.');
    }

    public function unlinkClient(SalesAgent $salesAgent, \Botble\JobBoard\Models\SalesAgentReferral $referral): BaseHttpResponse
    {
        if ((int) $referral->sales_agent_id !== (int) $salesAgent->getKey()) {
            abort(403);
        }

        $name = $referral->account?->name ?: $referral->phone;
        $referral->delete();

        return $this->httpResponse()->setMessage($name . ' unlinked.');
    }

    public function update(SalesAgent $salesAgent, Request $request): BaseHttpResponse
    {
        $data = $this->validatedData($request, $salesAgent->getKey());

        $salesAgent->update($data);

        return $this
            ->httpResponse()
            ->setMessage('Sales agent updated.')
            ->setNextUrl(route('sales-agents.edit', $salesAgent->getKey()));
    }

    public function destroy(SalesAgent $salesAgent): BaseHttpResponse
    {
        $salesAgent->delete();

        return $this
            ->httpResponse()
            ->setMessage('Sales agent deleted.');
    }

    public function searchCandidates(Request $request, BaseHttpResponse $response): BaseHttpResponse
    {
        $keyword = trim((string) $request->query('q'));

        $query = Account::query()
            ->with('avatar')
            ->where('type', AccountTypeEnum::JOB_SEEKER);

        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword): void {
                $query
                    ->where('first_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('last_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('email', 'LIKE', "%{$keyword}%")
                    ->orWhere('phone', 'LIKE', "%{$keyword}%")
                    ->orWhere('whatsapp_number', 'LIKE', "%{$keyword}%");
            });
        }

        $candidates = $query
            ->select(['id', 'first_name', 'last_name', 'email', 'phone', 'whatsapp_number', 'avatar_id'])
            ->orderBy('first_name')
            ->limit(12)
            ->get();

        return $response->setData($candidates->map(fn (Account $account) => [
            'id' => $account->getKey(),
            'name' => trim($account->name) ?: $account->email,
            'email' => $account->email,
            'phone' => $account->whatsapp_number ?: $account->phone,
            'avatar_url' => $account->avatar_thumb_url,
            'avatar_full_url' => $account->avatar_url,
        ])->values());
    }

    public function sendWelcome(SalesAgent $salesAgent, WhapiSenderService $sender): BaseHttpResponse
    {
        $errorMessage = null;

        if (! $sender->sendText($salesAgent->phone, $this->welcomeMessage($salesAgent), $errorMessage)) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($errorMessage ?: 'Could not send welcome message.');
        }

        $queued = $this->dispatchAllCampaignPosters($salesAgent);
        $posterNote = $queued > 0 ? " {$queued} campaign poster(s) generating." : '';

        return $this
            ->httpResponse()
            ->setMessage('Welcome message sent to ' . $salesAgent->name . '.' . $posterNote);
    }

    private function welcomeMessage(SalesAgent $salesAgent): string
    {
        return "Hi {$salesAgent->name}, welcome to Wakanda Jobs Sales Agents.\n\n"
            . "Your referral code is *{$salesAgent->code}*.\n\n"
            . "Share Wakanda Jobs services with candidates and employers. When they use your code at checkout, the sale is tracked to you and your commission appears in the admin ledger.\n\n"
            . "We will send you marketing posters and campaign material you can share on WhatsApp.";
    }

    private function dispatchAllCampaignPosters(SalesAgent $salesAgent): int
    {
        $campaigns = SalesAgentCampaign::query()->where('is_active', true)->latest()->get();

        if ($campaigns->isEmpty()) {
            return 0;
        }

        $subjectMode = $salesAgent->preferredMarketingSubjectMode();
        $queued = 0;

        foreach ($campaigns as $campaign) {
            $image = SalesAgentMarketingImage::query()->create([
                'sales_agent_id' => $salesAgent->getKey(),
                'campaign_id' => $campaign->getKey(),
                'subject_mode' => $subjectMode,
                'status' => 'generating',
            ]);

            GenerateSalesAgentPosterJob::dispatch($image->getKey(), true);
            $queued++;
        }

        return $queued;
    }

    public function generateMarketingImage(SalesAgent $salesAgent, Request $request): BaseHttpResponse
    {
        $data = $this->validateMarketingImageRequest($request);

        $image = SalesAgentMarketingImage::query()->create([
            'sales_agent_id' => $salesAgent->getKey(),
            'campaign_id' => $data['campaign_id'],
            'subject_mode' => $data['subject_mode'],
            'status' => 'generating',
        ]);

        GenerateSalesAgentPosterJob::dispatch($image->getKey());

        return $this
            ->httpResponse()
            ->setData($this->marketingImagePayload($image))
            ->setMessage('Marketing image generation queued.')
            ->setNextUrl(route('sales-agents.show', $salesAgent->getKey()));
    }

    public function previewMarketingImage(
        SalesAgent $salesAgent,
        Request $request,
        BaseHttpResponse $response,
        OpenAiImageService $imageService
    ): BaseHttpResponse {
        $data = $this->validateMarketingImageRequest($request);
        $campaign = SalesAgentCampaign::query()->findOrFail($data['campaign_id']);
        $preview = $imageService->previewSalesAgentPoster($salesAgent, $campaign, $data['subject_mode']);

        if (! ($preview['ok'] ?? false)) {
            return $response
                ->setError()
                ->setMessage($preview['message'] ?? 'Could not prepare image preview.')
                ->setData([
                    'prompt' => $preview['prompt'] ?? '',
                    'references' => $preview['references'] ?? [],
                    'subject_mode' => $data['subject_mode'],
                    'subject_label' => SalesAgentMarketingImage::subjectModes()[$data['subject_mode']] ?? $data['subject_mode'],
                    'campaign_id' => $campaign->getKey(),
                    'campaign_name' => $campaign->name,
                ]);
        }

        return $response->setData([
            'prompt' => $preview['prompt'],
            'references' => $preview['references'],
            'subject_mode' => $data['subject_mode'],
            'subject_label' => SalesAgentMarketingImage::subjectModes()[$data['subject_mode']] ?? $data['subject_mode'],
            'campaign_id' => $campaign->getKey(),
            'campaign_name' => $campaign->name,
        ]);
    }

    public function sendCampaign(SalesAgent $salesAgent, Request $request): BaseHttpResponse
    {
        $data = $request->validate([
            'campaign_id' => ['required', Rule::exists('jb_sales_agent_campaigns', 'id')],
            'subject_mode' => ['required', Rule::in(array_keys(SalesAgentMarketingImage::subjectModes()))],
        ]);

        $latestCompletedImage = SalesAgentMarketingImage::query()
            ->where('sales_agent_id', $salesAgent->getKey())
            ->where('campaign_id', $data['campaign_id'])
            ->where('subject_mode', $data['subject_mode'])
            ->where('status', 'completed')
            ->latest()
            ->first();

        if ($latestCompletedImage) {
            SendSalesAgentMarketingImageJob::dispatch($latestCompletedImage->getKey());

            return $this
                ->httpResponse()
                ->setMessage('Existing campaign image queued for WhatsApp send.')
                ->setNextUrl(route('sales-agents.edit', $salesAgent->getKey()));
        }

        $image = SalesAgentMarketingImage::query()->create([
            'sales_agent_id' => $salesAgent->getKey(),
            'campaign_id' => $data['campaign_id'],
            'subject_mode' => $data['subject_mode'],
            'status' => 'generating',
        ]);

        GenerateSalesAgentPosterJob::dispatch($image->getKey(), true);

        return $this
            ->httpResponse()
            ->setMessage('No ready poster existed, so generation and WhatsApp send have been queued.')
            ->setNextUrl(route('sales-agents.edit', $salesAgent->getKey()));
    }

    public function assignOrder(SalesAgent $salesAgent, Request $request, SalesAgentService $service): BaseHttpResponse
    {
        $data = $request->validate([
            'order_type' => ['required', Rule::in(['job_alert_order', 'vip_alert_order', 'auto_apply_order', 'career_service_order'])],
            'order_id' => ['required', 'integer', 'min:1'],
        ]);

        $modelClass = match ($data['order_type']) {
            'job_alert_order' => JobAlertOrder::class,
            'vip_alert_order' => VipAlertOrder::class,
            'auto_apply_order' => AutoApplyOrder::class,
            'career_service_order' => CareerServiceOrder::class,
        };

        $order = $modelClass::query()->find($data['order_id']);

        if (! $order) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage('Order not found.');
        }

        $order->update([
            'sales_agent_id' => $salesAgent->getKey(),
            'sales_agent_original_amount' => $order->sales_agent_original_amount ?: $order->amount,
            'sales_agent_code' => $salesAgent->code,
        ]);

        $phone = match ($data['order_type']) {
            'job_alert_order', 'auto_apply_order' => $order->account?->phone,
            'vip_alert_order' => $order->candidate_phone,
            'career_service_order' => $order->customer_phone,
        };

        $accountId = match ($data['order_type']) {
            'job_alert_order', 'auto_apply_order' => $order->account_id,
            'vip_alert_order' => $order->candidateAlert?->account_id,
            'career_service_order' => $order->candidate_id,
        };

        $source = match ($data['order_type']) {
            'job_alert_order' => 'job_alert',
            'vip_alert_order' => 'vip_alert',
            'auto_apply_order' => 'auto_apply',
            'career_service_order' => 'career_service',
        };

        $service->recordReferral($salesAgent, $phone, $salesAgent->code, $source, $accountId);

        if ($this->orderHasRecognizedRevenue($order, $data['order_type'])) {
            $service->creditCommission($salesAgent, $data['order_type'], $order->getKey(), (float) $order->amount, $order->currency);
        }

        return $this
            ->httpResponse()
            ->setMessage('Order assigned to ' . $salesAgent->name . '.');
    }

    public function sendMarketingImage(
        SalesAgent $salesAgent,
        SalesAgentMarketingImage $salesAgentMarketingImage,
        WhapiSenderService $sender
    ): BaseHttpResponse {
        if ((int) $salesAgentMarketingImage->sales_agent_id !== (int) $salesAgent->getKey()) {
            abort(404);
        }

        if ($salesAgentMarketingImage->status !== 'completed' || ! $salesAgentMarketingImage->image_path) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage('This marketing image is not ready to send yet.');
        }

        $path = Storage::disk('public')->path($salesAgentMarketingImage->image_path);
        $caption = $salesAgentMarketingImage->campaign->buildShareMessage($salesAgent);

        $errorMessage = null;

        if (! $sender->sendImage($salesAgent->phone, $path, $caption, $errorMessage)) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($errorMessage ?: 'Could not send marketing image.')
                ->setNextUrl(route('sales-agents.show', $salesAgent->getKey()));
        }

        $salesAgentMarketingImage->update(['sent_at' => now()]);

        return $this
            ->httpResponse()
            ->setMessage('Marketing image sent to ' . $salesAgent->name . '.')
            ->setNextUrl(route('sales-agents.show', $salesAgent->getKey()));
    }

    public function downloadMarketingImage(SalesAgent $salesAgent, SalesAgentMarketingImage $salesAgentMarketingImage): BinaryFileResponse
    {
        if ((int) $salesAgentMarketingImage->sales_agent_id !== (int) $salesAgent->getKey()) {
            abort(404);
        }

        if (! $salesAgentMarketingImage->image_path || ! Storage::disk('public')->exists($salesAgentMarketingImage->image_path)) {
            abort(404);
        }

        $extension = pathinfo($salesAgentMarketingImage->image_path, PATHINFO_EXTENSION) ?: 'png';
        $filename = Str::slug($salesAgent->name . '-' . ($salesAgentMarketingImage->campaign?->name ?? 'campaign')) . '.' . $extension;

        return response()->download(Storage::disk('public')->path($salesAgentMarketingImage->image_path), $filename);
    }

    public function destroyMarketingImage(
        SalesAgent $salesAgent,
        SalesAgentMarketingImage $salesAgentMarketingImage
    ): BaseHttpResponse {
        if ((int) $salesAgentMarketingImage->sales_agent_id !== (int) $salesAgent->getKey()) {
            abort(404);
        }

        if ($salesAgentMarketingImage->image_path) {
            Storage::disk('public')->delete($salesAgentMarketingImage->image_path);
        }

        $salesAgentMarketingImage->delete();

        return $this
            ->httpResponse()
            ->setMessage('Marketing image deleted.')
            ->setNextUrl(route('sales-agents.show', $salesAgent->getKey()));
    }

    public function bulkDestroyMarketingImages(SalesAgent $salesAgent, Request $request): BaseHttpResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $images = SalesAgentMarketingImage::query()
            ->where('sales_agent_id', $salesAgent->getKey())
            ->whereIn('id', $data['ids'])
            ->get();

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
            ->setMessage($images->count() . ' marketing image(s) deleted.')
            ->setNextUrl(route('sales-agents.show', $salesAgent->getKey()));
    }

    public function bulkSendMarketingImages(SalesAgent $salesAgent, Request $request): BaseHttpResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $images = SalesAgentMarketingImage::query()
            ->where('sales_agent_id', $salesAgent->getKey())
            ->whereIn('id', $data['ids'])
            ->where('status', 'completed')
            ->whereNotNull('image_path')
            ->get();

        foreach ($images as $image) {
            SendSalesAgentMarketingImageJob::dispatch($image->getKey());
        }

        $skipped = count($data['ids']) - $images->count();
        $message = $images->count() . ' marketing image(s) queued for WhatsApp send to ' . $salesAgent->name . '.';

        if ($skipped > 0) {
            $message .= ' ' . $skipped . ' skipped (not completed).';
        }

        return $this
            ->httpResponse()
            ->setMessage($message)
            ->setNextUrl(route('sales-agents.show', $salesAgent->getKey()));
    }

    public function marketingImageStatus(
        SalesAgent $salesAgent,
        SalesAgentMarketingImage $salesAgentMarketingImage,
        BaseHttpResponse $response
    ): BaseHttpResponse {
        if ((int) $salesAgentMarketingImage->sales_agent_id !== (int) $salesAgent->getKey()) {
            abort(404);
        }

        $salesAgentMarketingImage->loadMissing('campaign');
        $salesAgentMarketingImage->refresh();

        return $response->setData($this->marketingImagePayload($salesAgentMarketingImage));
    }

    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        $request->merge([
            'code' => $this->normalizeAgentCode(
                $request->input('code') ?: $this->suggestAgentCode($request->input('name'), $ignoreId)
            ),
        ]);

        $data = $request->validate([
            'candidate_account_id' => ['nullable', Rule::exists('jb_accounts', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('jb_sales_agents', 'code')->ignore($ignoreId),
            ],
            'use_marketing_photo' => ['nullable', 'boolean'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        unset($data['photo']);

        $candidate = null;

        if (! empty($data['candidate_account_id'])) {
            $candidate = Account::query()->with('avatar')->find($data['candidate_account_id']);
        }

        if ($request->hasFile('photo')) {
            $result = RvMedia::handleUpload($request->file('photo'), 0, 'sales-agents');

            if (! $result['error']) {
                $data['photo'] = $result['data']->url;

                if ($candidate) {
                    $candidate->update(['avatar_id' => $result['data']->id]);
                }
            }
        } elseif ($candidate?->avatar?->url) {
            $data['photo'] = $candidate->avatar->url;
        }

        $data['use_marketing_photo'] = $request->boolean('use_marketing_photo');
        $data['commission_rate'] = $data['commission_rate'] ?? (float) setting('sales_agent_default_commission_rate', 10);

        return $data;
    }

    private function validateMarketingImageRequest(Request $request): array
    {
        return $request->validate([
            'campaign_id' => ['required', Rule::exists('jb_sales_agent_campaigns', 'id')],
            'subject_mode' => ['required', Rule::in(array_keys(SalesAgentMarketingImage::subjectModes()))],
        ]);
    }

    private function marketingImagePayload(SalesAgentMarketingImage $image): array
    {
        $image->loadMissing('campaign');

        return [
            'image_id' => $image->getKey(),
            'campaign_id' => $image->campaign_id,
            'campaign_name' => $image->campaign?->name ?: 'Campaign deleted',
            'subject_mode' => $image->subject_mode,
            'subject_label' => SalesAgentMarketingImage::subjectModes()[$image->subject_mode] ?? $image->subject_mode,
            'status' => $image->status,
            'error_message' => $image->error_message,
            'image_url' => $image->status === 'completed' ? $image->imageUrl() : null,
            'download_url' => $image->status === 'completed' && $image->image_path
                ? route('sales-agents.marketing-images.download', [$image->sales_agent_id, $image->getKey()])
                : null,
            'send_url' => $image->status === 'completed' && $image->image_path
                ? route('sales-agents.marketing-images.send', [$image->sales_agent_id, $image->getKey()])
                : null,
            'status_url' => route('sales-agents.marketing-images.status', [$image->sales_agent_id, $image->getKey()]),
            'delete_url' => route('sales-agents.marketing-images.destroy', [$image->sales_agent_id, $image->getKey()]),
            'sent_at_human' => $image->sent_at?->diffForHumans(),
            'generation_meta' => $image->generationMeta(),
        ];
    }

    private function orderHasRecognizedRevenue($order, string $orderType): bool
    {
        return match ($orderType) {
            'job_alert_order' => $order->status === 'approved',
            'vip_alert_order' => $order->admin_status === 'approved',
            'auto_apply_order' => $order->admin_status === 'approved' && $order->status === 'approved',
            'career_service_order' => filled($order->charge_id) || in_array($order->status, ['paid', 'completed'], true),
            default => false,
        };
    }

    private function normalizeAgentCode(?string $code): string
    {
        return Str::upper(preg_replace('/[^A-Z0-9]/i', '', (string) $code));
    }

    private function suggestAgentCode(?string $name, ?int $ignoreId = null): string
    {
        $base = Str::upper(preg_replace('/[^A-Z0-9]/i', '', Str::before(trim((string) $name), ' '))) ?: 'AGENT';

        for ($i = 10; $i <= 99; $i++) {
            $code = $base . $i;
            $exists = SalesAgent::query()
                ->where('code', $code)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists();

            if (! $exists) {
                return $code;
            }
        }

        return $base . random_int(100, 999);
    }
}
