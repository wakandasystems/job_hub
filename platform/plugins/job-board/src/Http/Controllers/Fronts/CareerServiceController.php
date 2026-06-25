<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\CareerServiceOrder;
use Botble\JobBoard\Models\Currency;
use Botble\JobBoard\Models\SalesAgent;
use Botble\JobBoard\Services\CvScoringService;
use Botble\JobBoard\Services\SalesAgentService;
use Botble\Payment\Services\Gateways\BankTransferPaymentService;
use Botble\Payment\Services\Gateways\CodPaymentService;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Slug\Facades\SlugHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CareerServiceController extends BaseController
{
    public function getListing()
    {
        SeoHelper::setTitle('Career Services — CV, LinkedIn & Interview Coaching');

        $services = CareerServiceOrder::services();
        $servicePrices = collect($services)
            ->mapWithKeys(fn (array $service, string $key): array => [$key => $this->careerServicePricing($service)]);

        return Theme::scope('job-board.career-services.listing', compact('services', 'servicePrices'))->render();
    }

    public function bookRedirect(string $service)
    {
        $services = CareerServiceOrder::services();
        abort_unless(array_key_exists($service, $services), 404);

        if (auth('account')->check()) {
            /** @var Account $account */
            $account = auth('account')->user();
            return redirect()->route('public.career-service.checkout', [
                'service'   => $service,
                'candidate' => $account->slug,
            ]);
        }

        session(['url.intended' => route('public.career-service.checkout', ['service' => $service])]);

        return redirect()->route('public.account.register');
    }

    public function getCheckout(string $serviceType, Request $request)
    {
        $services = CareerServiceOrder::services();

        abort_unless(array_key_exists($serviceType, $services), 404);

        $service = $services[$serviceType];

        $candidate = null;
        if ($slug = $request->query('candidate')) {
            $slug = SlugHelper::getSlug($slug, SlugHelper::getPrefix(Account::class));

            if ($slug) {
                $candidate = Account::query()->find($slug->reference_id);
            }
        }

        SeoHelper::setTitle('Book: ' . $service['name']);

        $pricing = $this->careerServicePricing($service);
        $currency = $pricing['currency_code'];
        $amount = $pricing['amount'];
        $orderId = null;

        // Create a pending order so we have an ID for the callback URL
        $order = CareerServiceOrder::create([
            'service_type'  => $serviceType,
            'service_name'  => $service['name'],
            'amount'        => $amount,
            'currency'      => $currency,
            'customer_name' => auth('account')->user()?->name ?? '',
            'customer_email'=> auth('account')->user()?->email ?? '',
            'candidate_id'  => $candidate?->id,
            'ai_cv_score' => session('career_service_cv_score.score'),
            'ai_cv_feedback' => session('career_service_cv_score.feedback'),
            'status'        => 'pending',
        ]);

        $callbackUrl = route('public.career-service.callback', ['order' => $order->id]);
        $returnUrl   = route('public.career-service.checkout', ['service' => $serviceType]);

        return Theme::scope('job-board.career-services.checkout', compact(
            'service', 'serviceType', 'order', 'candidate', 'callbackUrl', 'returnUrl', 'currency', 'amount', 'pricing'
        ))->render();
    }

    protected function careerServicePricing(array $service): array
    {
        $originCode = strtoupper((string) ($service['currency'] ?? setting('career_service_currency', 'USD')));
        $originCurrency = Currency::query()->where('title', $originCode)->first();
        $targetCurrency = get_application_currency();
        $amount = round((float) format_price($service['price'], $originCurrency, true, true, true), (int) ($targetCurrency->decimals ?? 2));
        $originMeta = function_exists('wakanda_currency_meta') ? wakanda_currency_meta($originCode) : null;
        $targetMeta = $targetCurrency && function_exists('wakanda_currency_meta') ? wakanda_currency_meta($targetCurrency->title) : null;

        return [
            'amount' => $amount,
            'display' => $originCurrency ? format_price($service['price'], $originCurrency, fullNumber: true) : number_format($service['price'], 2) . ' ' . $originCode,
            'currency_code' => strtoupper((string) ($targetCurrency->title ?? $originCode)),
            'target_country' => $targetMeta['country'] ?? null,
            'origin_country' => $originMeta['country'] ?? null,
            'origin_currency_code' => $originCode,
            'origin_display' => number_format($service['price'], 2) . ' ' . $originCode,
        ];
    }

    public function getCallback(int $orderId, Request $request)
    {
        $order = CareerServiceOrder::findOrFail($orderId);

        if ($order->status === 'paid') {
            return redirect()->route('public.career-service.thanks', ['order' => $order->id]);
        }

        $chargeId = $request->input('charge_id');

        if (! $chargeId) {
            return redirect()->back()->with('error_msg', __('Payment could not be verified. Please try again.'));
        }

        $order->update([
            'charge_id'      => $chargeId,
            'payment_method' => $request->input('type'),
            'customer_name'  => $request->input('customer_name', $order->customer_name),
            'customer_email' => $request->input('customer_email', $order->customer_email),
            'customer_phone' => $request->input('customer_phone', $order->customer_phone),
            'status'         => 'paid',
        ]);

        if ($order->sales_agent_id) {
            $agent = SalesAgent::query()->find($order->sales_agent_id);

            if ($agent) {
                $service = app(SalesAgentService::class);
                $service->recordReferral($agent, $order->customer_phone, $order->sales_agent_code, 'career_service', auth('account')->id());
                $service->creditCommission($agent, 'career_service_order', $order->getKey(), (float) $order->amount, $order->currency);
            }
        }

        session()->forget([
            'career_service_order_id',
            'career_service_callback_url',
            'career_service_return_url',
        ]);

        $this->sendConfirmationEmail($order);

        return redirect()->route('public.career-service.thanks', ['order' => $order->id]);
    }

    public function getThanks(int $orderId)
    {
        $order = CareerServiceOrder::findOrFail($orderId);

        SeoHelper::setTitle(__('Booking Confirmed'));

        return Theme::scope('job-board.career-services.thanks', compact('order'))->render();
    }

    public function postUploadCandidateCv(int $orderId, Request $request)
    {
        $order = CareerServiceOrder::findOrFail($orderId);

        abort_unless($order->status === 'paid', 403);

        $request->validate([
            'candidate_cv' => ['required', 'file', 'mimes:docx,doc,pdf', 'max:10240'],
        ]);

        $file = $request->file('candidate_cv');
        $filename = 'order-' . $orderId . '-candidate.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('career-service-cvs/' . $orderId, $filename, 'local');

        if ($order->candidate_cv_path) {
            Storage::disk('local')->delete($order->candidate_cv_path);
        }

        $order->update(['candidate_cv_path' => $path]);

        $this->sendCvUploadedNotification($order);

        return redirect()
            ->route('public.career-service.thanks', ['order' => $orderId])
            ->with('success_msg', __('Your CV has been uploaded. The coach will use it to complete your order.'));
    }

    public function downloadReviewedCv(int $orderId)
    {
        $order = CareerServiceOrder::findOrFail($orderId);

        $account = auth('account')->user();
        abort_unless($account && $order->candidate_id === $account->id, 403);
        abort_unless($order->reviewed_cv_path && Storage::disk('local')->exists($order->reviewed_cv_path), 404);

        return Storage::disk('local')->download(
            $order->reviewed_cv_path,
            'reviewed-cv-order-' . str_pad($orderId, 6, '0', STR_PAD_LEFT) . '.' . pathinfo($order->reviewed_cv_path, PATHINFO_EXTENSION)
        );
    }

    public function scoreProfileCv()
    {
        /** @var Account $account */
        $account = auth('account')->user();

        if (! $account->resume) {
            return redirect()->route('public.career-service.cv-score')
                ->with('error_msg', __('No CV found on your profile. Please upload one first.'));
        }

        $realPath = Storage::disk('public')->path($account->resume);
        $extension = strtolower(pathinfo($account->resume, PATHINFO_EXTENSION));

        $result = app(CvScoringService::class)->scoreFile($realPath, $extension);

        if (! $result) {
            return redirect()->route('public.career-service.cv-score')
                ->with('error_msg', __('We couldn\'t extract text from your profile CV. Please paste or upload it below.'));
        }

        session(['career_service_cv_score' => $result]);

        return redirect()->route('public.career-service.cv-score')
            ->with('success_msg', __('Your profile CV has been scored.'));
    }

    public function getCvScore()
    {
        SeoHelper::setTitle(__('Free AI CV Score'));

        $score = session('career_service_cv_score');

        return Theme::scope('job-board.career-services.cv-score', compact('score'))->render();
    }

    public function postCvScore(Request $request)
    {
        $validated = $request->validate([
            'cv_text' => ['nullable', 'string', 'max:30000'],
            'cv_file' => ['nullable', 'file', 'mimes:txt,pdf,doc,docx', 'max:5120'],
        ]);

        $text = trim((string) ($validated['cv_text'] ?? ''));

        if (! $text && $request->hasFile('cv_file')) {
            $file = $request->file('cv_file');

            if ($file && $file->getClientOriginalExtension() === 'txt') {
                $text = trim((string) file_get_contents($file->getRealPath()));
            } else {
                $text = trim(pathinfo($file?->getClientOriginalName() ?: '', PATHINFO_FILENAME));
            }
        }

        if (Str::length($text) < 80) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error_msg', __('Paste at least a few CV sections, or upload a text CV, so we can score it properly.'));
        }

        $score = $this->scoreCvText($text);

        session(['career_service_cv_score' => $score]);

        return redirect()->route('public.career-service.cv-score')->with('success_msg', __('Your CV score is ready.'));
    }

    protected function scoreCvText(string $text): array
    {
        $normalized = Str::lower($text);
        $score = 37;
        $feedback = [];

        $checks = [
            'contact details' => ['email', 'phone', 'linkedin'],
            'professional summary' => ['summary', 'profile', 'objective'],
            'work experience' => ['experience', 'employment', 'worked', 'responsibilities'],
            'education' => ['education', 'degree', 'diploma', 'certificate'],
            'skills' => ['skills', 'competencies', 'tools', 'technologies'],
            'achievements' => ['achieved', 'improved', 'increased', 'reduced', 'delivered'],
        ];

        foreach ($checks as $label => $keywords) {
            $matched = collect($keywords)->contains(fn (string $keyword) => str_contains($normalized, $keyword));

            if ($matched) {
                $score += 8;
            } else {
                $feedback[] = 'Add a clear ' . $label . ' section.';
            }
        }

        if (preg_match('/\b\d+%|\$\d+|\b\d+\s*(people|users|clients|projects|months|years)\b/i', $text)) {
            $score += 8;
        } else {
            $feedback[] = 'Quantify impact with numbers, percentages, revenue, team size or project volume.';
        }

        $wordCount = str_word_count($text);
        if ($wordCount >= 250 && $wordCount <= 900) {
            $score += 7;
        } elseif ($wordCount < 250) {
            $feedback[] = 'The CV looks too short. Add more detail about responsibilities, tools and outcomes.';
        } else {
            $feedback[] = 'The CV may be too long. Tighten it to the strongest, most relevant evidence.';
        }

        $score = max(0, min(100, $score));

        if ($score < 70) {
            $feedback[] = 'A human CV review is recommended before applying to competitive roles.';
        } elseif ($score < 85) {
            $feedback[] = 'The CV is workable, but a rewrite could make achievements sharper.';
        } else {
            $feedback[] = 'Strong baseline. Focus on tailoring it to each target role.';
        }

        return [
            'score' => $score,
            'feedback' => array_values(array_unique($feedback)),
            'scored_at' => now()->toDateTimeString(),
        ];
    }

    protected function adminEmail(): string
    {
        $raw = setting('admin_email') ?: config('mail.from.address');
        if (is_string($raw) && str_starts_with(trim($raw), '[')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && ! empty($decoded[0])) {
                return $decoded[0];
            }
        }
        return $raw ?: 'lemelnow@gmail.com';
    }

    protected function sendConfirmationEmail(CareerServiceOrder $order): void
    {
        $adminEmail = $this->adminEmail();

        $editUrl = url('/admin/career-service-orders/edit/' . $order->id);

        try {
            Mail::raw(
                "New Career Service Order\n\n" .
                "Service:  {$order->service_name}\n" .
                "Amount:   {$order->currency} {$order->amount}\n" .
                "Customer: {$order->customer_name} ({$order->customer_email})\n" .
                "Phone:    {$order->customer_phone}\n" .
                "Payment:  {$order->payment_method} — {$order->charge_id}\n\n" .
                "Manage order: {$editUrl}",
                function ($msg) use ($adminEmail, $order) {
                    $msg->to($adminEmail)
                        ->subject("New Career Service Order: {$order->service_name} — {$order->customer_name}");
                }
            );
        } catch (\Throwable) {
        }
    }

    protected function sendCvUploadedNotification(CareerServiceOrder $order): void
    {
        $adminEmail = $this->adminEmail();
        $editUrl = url('/admin/career-service-orders/edit/' . $order->id);

        try {
            Mail::raw(
                "CV Uploaded by Candidate\n\n" .
                "Order #:   {$order->id}\n" .
                "Service:   {$order->service_name}\n" .
                "Customer:  {$order->customer_name} ({$order->customer_email})\n\n" .
                "The candidate has uploaded their CV. You can now download it and begin the review.\n\n" .
                "Manage order: {$editUrl}",
                function ($msg) use ($adminEmail, $order) {
                    $msg->to($adminEmail)
                        ->subject("CV Uploaded — Order #{$order->id}: {$order->customer_name}");
                }
            );
        } catch (\Throwable) {
        }
    }
}
