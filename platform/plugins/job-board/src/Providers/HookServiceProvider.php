<?php

namespace Botble\JobBoard\Providers;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Facades\AdminHelper;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\EmailHandler;
use Botble\Base\Facades\Form;
use Botble\Base\Facades\Html;
use Botble\Base\Facades\MetaBox;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Models\BaseModel;
use Botble\Base\Supports\TwigCompiler;
use Botble\JobBoard\Enums\InvoiceStatusEnum;
use Botble\JobBoard\Enums\JobApplicationStatusEnum;
use Botble\JobBoard\Enums\ModerationStatusEnum;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\Category;
use Botble\JobBoard\Models\CareerServiceOrder;
use Botble\JobBoard\Models\CreditOrder;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\EmployerSubscription;
use Botble\JobBoard\Models\FeaturedOrder;
use Botble\JobBoard\Models\JobAlertOrder;
use Botble\JobBoard\Models\JobAlertQuota;
use Botble\JobBoard\Models\Invoice;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobApplication;
use Botble\JobBoard\Models\JobExperience;
use Botble\JobBoard\Models\JobSkill;
use Botble\JobBoard\Models\JobType;
use Botble\JobBoard\Models\Package;
use Botble\JobBoard\Models\SalaryReport;
use Botble\JobBoard\Models\SalaryReportPurchase;
use Botble\JobBoard\Models\CandidateAlert;
use Botble\JobBoard\Models\WakandaVerificationRequest;
use Botble\JobBoard\Services\CouponService;
use Botble\JobBoard\Supports\InvoiceHelper;
use Botble\JobBoard\Supports\TwigExtension;
use Botble\Dashboard\Events\RenderingDashboardWidgets;
use Botble\Dashboard\Supports\DashboardWidgetInstance;
use Botble\Media\Facades\RvMedia;
use Botble\Menu\Events\RenderingMenuOptions;
use Botble\Menu\Facades\Menu;
use Botble\Page\Models\Page;
use Illuminate\Support\Collection;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Botble\Payment\Supports\PaymentHelper;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Supports\ThemeSupport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_filter(BASE_FILTER_APPEND_MENU_NAME, [$this, 'countPendingApplications'], 26, 2);
        add_filter(BASE_FILTER_MENU_ITEMS_COUNT, [$this, 'getMenuItemCount'], 26);

        add_filter('cms_email_settings_validation_rules', function (array $rules) {
            $rules['job_board_newsletter_broadcast_enabled'] = ['nullable', 'boolean'];
            return $rules;
        });

        // Restart queue workers whenever email settings are saved so they pick up the new mailer config
        add_action('core_after_update_settings', function (array $data): void {
            $emailKeys = ['email_driver', 'email_host', 'email_port', 'email_encryption', 'email_username', 'email_password'];
            if (count(array_intersect($emailKeys, array_keys($data))) > 0) {
                \Illuminate\Support\Facades\Artisan::call('queue:restart');
            }
        });

        $this->app['events']->listen(RenderingDashboardWidgets::class, function (): void {
            add_filter(DASHBOARD_FILTER_ADMIN_LIST, [$this, 'registerDashboardStats'], 5, 2);
        });

        add_filter('social_login_view_path', function (string $view) {
            if (Route::currentRouteName() === 'public.account.register') {
                return 'plugins/job-board::partials.social-login-register-options';
            }
            return $view;
        });
        if (function_exists('theme_option')) {
            add_action(RENDERING_THEME_OPTIONS_PAGE, [$this, 'addThemeOptions'], 55);
        }

        add_filter('cms_twig_compiler', function (TwigCompiler $twigCompiler) {
            if (! array_key_exists(TwigExtension::class, $twigCompiler->getExtensions())) {
                $twigCompiler->addExtension(new TwigExtension());
            }

            return $twigCompiler;
        }, 130);

        if (defined('PAYMENT_FILTER_PAYMENT_PARAMETERS')) {
            add_filter(PAYMENT_FILTER_PAYMENT_PARAMETERS, function ($html) {
                if (! auth('account')->check()) {
                    return $html;
                }

                $refField = '<div id="payment-reference-wrap" style="display:none;" class="mt-3">'
                    . '<label class="form-label fw-medium" for="payment_reference">'
                    . __('Payment Reference Number')
                    . ' <span class="text-danger">*</span></label>'
                    . '<input type="text" id="payment_reference" name="payment_reference" class="form-control"'
                    . ' placeholder="' . __('e.g. Bank transaction ID, receipt number') . '"'
                    . ' maxlength="120">'
                    . '<div class="form-text text-muted">' . __('Include your bank reference or transfer ID so we can match your payment.') . '</div>'
                    . '</div>'
                    . '<script>'
                    . '(function(){var manualMethods=["bank_transfer","cod"];'
                    . 'function toggleRef(){var sel=document.querySelector(".list_payment_method input[type=radio]:checked");'
                    . 'var wrap=document.getElementById("payment-reference-wrap");'
                    . 'if(!wrap)return;'
                    . 'var isManual=sel&&manualMethods.indexOf(sel.value)!==-1;'
                    . 'wrap.style.display=isManual?"block":"none";'
                    . 'var inp=document.getElementById("payment_reference");'
                    . 'if(inp)inp.required=isManual;}'
                    . 'document.addEventListener("change",function(e){if(e.target&&e.target.name==="payment_method")toggleRef();});'
                    . 'document.addEventListener("DOMContentLoaded",toggleRef);'
                    . 'setTimeout(toggleRef,300);'
                    . '})();'
                    . '</script>';

                return $html
                    . Form::hidden('customer_id', auth('account')->id())->toHtml()
                    . Form::hidden('customer_type', Account::class)->toHtml()
                    . $refField;
            }, 123);
        }

        if (defined('PAYMENT_ACTION_PAYMENT_PROCESSED')) {
            add_action(PAYMENT_ACTION_PAYMENT_PROCESSED, function ($data): void {
                $payment = PaymentHelper::storeLocalPayment($data);

                // Handle job alert package orders
                if ($jobAlertOrderId = session('job_alert_order_id')) {
                    $order = JobAlertOrder::query()->with('package')->find($jobAlertOrderId);
                    if ($order && $order->status === 'pending') {
                        $order->update([
                            'charge_id'      => $data['charge_id'],
                            'payment_method' => $data['payment_channel'],
                        ]);

                        $isManual = in_array($data['payment_channel'], [
                            PaymentMethodEnum::BANK_TRANSFER,
                            PaymentMethodEnum::COD,
                        ]);

                        if (! $isManual) {
                            $order->approve();
                        }

                        $this->sendJobAlertOrderAdminEmail($order->fresh(['package', 'account']), $isManual);
                    }

                    return;
                }

                // Handle employer subscription orders
                if ($subscriptionOrderId = session('subscription_order_id')) {
                    $sub = EmployerSubscription::query()->with('package')->find($subscriptionOrderId);
                    if ($sub && $sub->status === 'pending') {
                        $sub->update([
                            'charge_id'      => $data['charge_id'],
                            'payment_method' => $data['payment_channel'],
                        ]);

                        $isManual = in_array($data['payment_channel'], [
                            PaymentMethodEnum::BANK_TRANSFER,
                            PaymentMethodEnum::COD,
                        ]);

                        if (! $isManual) {
                            $sub->activate();
                        }

                        $this->sendSubscriptionAdminEmail($sub->fresh(['package', 'account']), $isManual);
                    }

                    return;
                }

                // Handle featured job orders
                if ($featuredOrderId = session('featured_order_id')) {
                    $featuredOrder = FeaturedOrder::query()->with('package')->find($featuredOrderId);
                    if ($featuredOrder && $featuredOrder->status === 'pending') {
                        $featuredOrder->update([
                            'charge_id'      => $data['charge_id'],
                            'payment_method' => $data['payment_channel'],
                        ]);

                        $isManual = in_array($data['payment_channel'], [
                            PaymentMethodEnum::BANK_TRANSFER,
                            PaymentMethodEnum::COD,
                        ]);

                        if (! $isManual) {
                            $featuredOrder->approve();
                        }

                        $this->sendFeaturedOrderAdminEmail($featuredOrder->fresh(['package', 'account', 'job']), $isManual);
                    }

                    return;
                }

                if (session('career_service_order_id') || request()->input('career_service_order_id')) {
                    return;
                }

                // Handle salary report purchases
                if ($salaryReportId = session('salary_report_id')) {
                    $report = SalaryReport::query()->find($salaryReportId);
                    if ($report) {
                        $accessToken = (string) \Illuminate\Support\Str::uuid();
                        $purchase = SalaryReportPurchase::query()->create([
                            'report_id'       => $report->getKey(),
                            'buyer_name'      => session('salary_report_buyer_name', ''),
                            'buyer_email'     => session('salary_report_buyer_email', ''),
                            'buyer_company'   => session('salary_report_buyer_company'),
                            'amount_paid'     => $report->price,
                            'currency_code'   => $report->currency_code,
                            'payment_channel' => $data['payment_channel'] ?? null,
                            'charge_id'       => $data['charge_id'] ?? null,
                            'access_token'    => $accessToken,
                            'expires_at'      => now()->addYear(),
                        ]);

                        $downloadUrl = route('salary-reports.public.download', $accessToken);

                        try {
                            \Illuminate\Support\Facades\Mail::raw(
                                "Thank you for purchasing \"{$report->title}\".\n\nDownload your report here (valid for 12 months):\n{$downloadUrl}\n\nWakanda Jobs",
                                function ($message) use ($purchase, $report) {
                                    $message->to($purchase->buyer_email, $purchase->buyer_name)
                                        ->subject("Your Salary Report: {$report->title}");
                                }
                            );
                        } catch (\Throwable) {
                        }

                        session()->forget(['salary_report_id', 'salary_report_callback_url', 'salary_report_return_url', 'salary_report_buyer_name', 'salary_report_buyer_email', 'salary_report_buyer_company']);
                    }

                    return;
                }

                // Handle credit package (one-time) purchases
                if ($subscribedPackageId = session('subscribed_packaged_id')) {
                    $creditPackage = Package::query()->find($subscribedPackageId);
                    if ($creditPackage && $creditPackage->billing_cycle === 'one_time') {
                        $account = auth('account')->user();
                        if ($account) {
                            $isManual = in_array($data['payment_channel'], [
                                PaymentMethodEnum::BANK_TRANSFER,
                                PaymentMethodEnum::COD,
                            ]);

                            CreditOrder::query()->create([
                                'account_id'       => $account->getKey(),
                                'package_id'       => $creditPackage->getKey(),
                                'credits'          => $creditPackage->number_of_listings,
                                'amount'           => (float) ($data['amount'] ?? $creditPackage->price),
                                'currency'         => strtoupper($data['currency'] ?? 'ZMW'),
                                'payment_method'   => $data['payment_channel'],
                                'charge_id'        => $data['charge_id'] ?? null,
                                'payment_reference' => session('subscribed_package_payment_reference'),
                                'status'           => $isManual ? 'pending' : 'approved',
                                'approved_at'      => $isManual ? null : now(),
                            ]);
                        }
                    }
                }

                InvoiceHelper::store([
                    ...$data,
                    'discount_amount' => Session::get('coupon_discount_amount', 0),
                    'coupon_code' => Session::get('applied_coupon_code'),
                ]);

                if ($payment instanceof BaseModel) {
                    MetaBox::saveMetaBoxData($payment, 'subscribed_packaged_id', session('subscribed_packaged_id'));
                }
            }, 123);

            add_action(BASE_ACTION_META_BOXES, function ($context, $payment): void {
                if (get_class($payment) == Payment::class && $context == 'advanced' && Route::currentRouteName() == 'payments.show') {
                    MetaBox::addMetaBox('additional_payment_data', trans('plugins/job-board::forms.package_information'), function () use ($payment) {
                        $subscribedPackageId = MetaBox::getMetaData($payment, 'subscribed_packaged_id', true);

                        if (! $subscribedPackageId) {
                            return null;
                        }

                        $package = Package::query()->find($subscribedPackageId);

                        if (! $package) {
                            return null;
                        }

                        return view('plugins/job-board::partials.payment-extras', compact('package'));
                    }, get_class($payment), $context);
                }
            }, 128, 2);

            if (! $this->app->runningInConsole()) {
                add_action(INVOICE_PAYMENT_CREATED, function (Invoice $invoice): void {
                    $customer = $invoice->payment->customer;
                    $localDisk = Storage::disk('local');
                    $invoiceName = sprintf('invoice-%s.pdf', $invoice->code);
                    $localDisk->put($invoiceName, (new InvoiceHelper())->makeInvoice($invoice)->output());

                    EmailHandler::setModule(JOB_BOARD_MODULE_SCREEN_NAME)
                        ->setVariableValues([
                            'account_name' => $customer->name,
                            'invoice_code' => $invoice->code,
                            'invoice_link' => route('public.account.invoices.show', $invoice->getKey()),
                        ])
                        ->sendUsingTemplate('invoice-payment-created', $customer->email, [
                            'attachments' => [$localDisk->path($invoiceName)],
                        ]);

                    $localDisk->delete($invoiceName);

                    $this->app->make(CouponService::class)->forgotCouponSession();
                });
            }
        }

        if (defined('PAYMENT_FILTER_REDIRECT_URL')) {
            add_filter(PAYMENT_FILTER_REDIRECT_URL, function ($checkoutToken) {
                if ($subCallbackUrl = session('subscription_callback_url')) {
                    session()->forget(['subscription_order_id', 'subscription_callback_url', 'subscription_return_url']);
                    return $subCallbackUrl;
                }

                if ($featuredCallbackUrl = session('featured_callback_url')) {
                    session()->forget(['featured_order_id', 'featured_callback_url', 'featured_return_url']);
                    return $featuredCallbackUrl;
                }

                if ($jobAlertCallbackUrl = session('job_alert_callback_url')) {
                    session()->forget(['job_alert_order_id', 'job_alert_callback_url', 'job_alert_return_url']);
                    return $jobAlertCallbackUrl;
                }

                if ($careerServiceCallbackUrl = session('career_service_callback_url')) {
                    return $careerServiceCallbackUrl;
                }

                if ($salaryReportCallbackUrl = session('salary_report_callback_url')) {
                    session()->forget(['salary_report_id', 'salary_report_callback_url', 'salary_report_return_url', 'salary_report_buyer_name', 'salary_report_buyer_email', 'salary_report_buyer_company']);
                    return $salaryReportCallbackUrl;
                }

                $checkoutToken = $checkoutToken ?: session('subscribed_packaged_id');

                if (! $checkoutToken) {
                    return route('public.index');
                }

                if (str_contains($checkoutToken, url(''))) {
                    return $checkoutToken;
                }

                return route('public.account.package.subscribe.callback', $checkoutToken);
            }, 123);
        }

        if (defined('PAYMENT_FILTER_CANCEL_URL')) {
            add_filter(PAYMENT_FILTER_CANCEL_URL, function ($checkoutToken) {
                if ($subReturnUrl = session('subscription_return_url')) {
                    session()->forget(['subscription_order_id', 'subscription_callback_url', 'subscription_return_url']);
                    return $subReturnUrl;
                }

                if ($featuredReturnUrl = session('featured_return_url')) {
                    session()->forget(['featured_order_id', 'featured_callback_url', 'featured_return_url']);
                    return $featuredReturnUrl;
                }

                if ($jobAlertReturnUrl = session('job_alert_return_url')) {
                    session()->forget(['job_alert_order_id', 'job_alert_callback_url', 'job_alert_return_url']);
                    return $jobAlertReturnUrl;
                }

                if ($careerServiceReturnUrl = session('career_service_return_url')) {
                    return $careerServiceReturnUrl;
                }

                if ($salaryReportReturnUrl = session('salary_report_return_url')) {
                    session()->forget(['salary_report_id', 'salary_report_callback_url', 'salary_report_return_url', 'salary_report_buyer_name', 'salary_report_buyer_email', 'salary_report_buyer_company']);
                    return $salaryReportReturnUrl;
                }

                $checkoutToken = $checkoutToken ?: session('subscribed_packaged_id');

                if (! $checkoutToken) {
                    return route('public.index');
                }

                if (str_contains($checkoutToken, url(''))) {
                    return $checkoutToken;
                }

                return route('public.account.package.subscribe', $checkoutToken) . '?' . http_build_query(['error' => true, 'error_type' => 'payment']);
            }, 123);
        }

        if (defined('ACTION_AFTER_UPDATE_PAYMENT')) {
            add_action(ACTION_AFTER_UPDATE_PAYMENT, function ($request, $payment): void {
                if (in_array($payment->payment_channel, [PaymentMethodEnum::COD, PaymentMethodEnum::BANK_TRANSFER])
                    && $request->input('status') == PaymentStatusEnum::COMPLETED
                ) {
                    // Auto-approve matching job alert order when bank transfer is confirmed
                    if ($payment->charge_id) {
                        $alertOrder = JobAlertOrder::query()
                            ->where('charge_id', $payment->charge_id)
                            ->where('status', 'pending')
                            ->first();

                        if ($alertOrder) {
                            $alertOrder->approve();
                        }

                        $featuredOrder = FeaturedOrder::query()
                            ->where('charge_id', $payment->charge_id)
                            ->where('status', 'pending')
                            ->first();

                        if ($featuredOrder) {
                            $featuredOrder->approve();
                        }

                        $pendingSub = EmployerSubscription::query()
                            ->where('charge_id', $payment->charge_id)
                            ->where('status', 'pending')
                            ->first();

                        if ($pendingSub) {
                            $pendingSub->activate();
                        }

                    }

                    $subscribedPackageId = MetaBox::getMetaData($payment, 'subscribed_packaged_id', true);

                    if (! $subscribedPackageId) {
                        return;
                    }

                    $package = Package::query()->find($subscribedPackageId);

                    if (! $package) {
                        return;
                    }

                    /**
                     * @var Account $account
                     */
                    $account = Account::query()->find($payment->customer_id);

                    if (! $account) {
                        return;
                    }

                    $account->credits += $package->number_of_listings;
                    $account->save();

                    $account->packages()->attach($package);

                    Invoice::query()
                        ->where('reference_id', $package->getKey())
                        ->where('reference_type', Package::class)
                        ->update(['status' => InvoiceStatusEnum::COMPLETED]);

                    // Update matching CreditOrder status (credits already awarded above)
                    if ($payment->charge_id) {
                        $pendingCreditOrder = CreditOrder::query()
                            ->where('charge_id', $payment->charge_id)
                            ->where('status', 'pending')
                            ->first();

                        if ($pendingCreditOrder) {
                            $pendingCreditOrder->update(['status' => 'approved', 'approved_at' => now()]);
                        }
                    }
                }
            }, 123, 2);
        }

        if (defined('PAYMENT_FILTER_PAYMENT_DATA')) {
            add_filter(PAYMENT_FILTER_PAYMENT_DATA, function (array $data, Request $request) {
                // Always capture reference number so it's available after redirect
                if ($ref = trim((string) $request->input('payment_reference', ''))) {
                    session(['subscribed_package_payment_reference' => $ref]);
                }

                if ($subscriptionOrderId = $request->input('subscription_order_id')) {
                    $sub = EmployerSubscription::query()->with('package')->find($subscriptionOrderId);

                    if (! $sub || ! $sub->package?->is_active) {
                        return $data;
                    }

                    /** @var Account $account */
                    $account = auth('account')->user();
                    $package = $sub->package;

                    session([
                        'subscription_order_id'    => $sub->getKey(),
                        'subscription_callback_url' => $request->input('callback_url'),
                        'subscription_return_url'  => $request->input('return_url'),
                    ]);

                    $cycleName = $sub->billing_cycle === 'annual' ? 'Annual' : 'Monthly';

                    return [
                        'amount'          => (float) $sub->amount,
                        'shipping_amount' => 0,
                        'shipping_method' => null,
                        'tax_amount'      => 0,
                        'currency'        => strtoupper($sub->currency),
                        'order_id'        => [$sub->getKey()],
                        'description'     => trans('plugins/payment::payment.payment_description', [
                            'order_id' => $sub->getKey(),
                            'site_url' => $request->getHost(),
                        ]),
                        'customer_id'     => $account?->getKey(),
                        'customer_type'   => $account ? Account::class : null,
                        'email'           => $request->input('customer_email') ?: $account?->email,
                        'return_url'      => $request->input('return_url'),
                        'callback_url'    => $request->input('callback_url'),
                        'products'        => [
                            [
                                'id'              => $package->getKey(),
                                'name'            => $package->name . " — {$cycleName} Subscription",
                                'price'           => (float) $sub->amount,
                                'price_per_order' => (float) $sub->amount,
                                'qty'             => 1,
                            ],
                        ],
                        'orders'          => [$sub],
                        'address'         => [
                            'name'    => $request->input('customer_name') ?: $account?->name,
                            'email'   => $request->input('customer_email') ?: $account?->email,
                            'phone'   => $account?->phone,
                            'country' => null, 'state' => null, 'city' => null, 'address' => null, 'zip' => null,
                        ],
                        'checkout_token'  => $request->input('callback_url'),
                    ];
                }

                if ($featuredOrderId = $request->input('featured_order_id')) {
                    $order = FeaturedOrder::query()->with(['package', 'job'])->find($featuredOrderId);

                    if (! $order || ! $order->package?->is_active) {
                        return $data;
                    }

                    /** @var Account $account */
                    $account = auth('account')->user();
                    $package = $order->package;

                    session([
                        'featured_order_id'    => $order->getKey(),
                        'featured_callback_url' => $request->input('callback_url'),
                        'featured_return_url'  => $request->input('return_url'),
                    ]);

                    return [
                        'amount'          => (float) $order->amount,
                        'shipping_amount' => 0,
                        'shipping_method' => null,
                        'tax_amount'      => 0,
                        'currency'        => strtoupper($order->currency),
                        'order_id'        => [$order->getKey()],
                        'description'     => trans('plugins/payment::payment.payment_description', [
                            'order_id' => $order->getKey(),
                            'site_url' => $request->getHost(),
                        ]),
                        'customer_id'     => $account?->getKey(),
                        'customer_type'   => $account ? Account::class : null,
                        'email'           => $request->input('customer_email') ?: $account?->email,
                        'return_url'      => $request->input('return_url'),
                        'callback_url'    => $request->input('callback_url'),
                        'products'        => [
                            [
                                'id'              => $package->getKey(),
                                'name'            => $package->name . ' — ' . ($order->job?->name ?? 'Featured Job'),
                                'price'           => (float) $order->amount,
                                'price_per_order' => (float) $order->amount,
                                'qty'             => 1,
                            ],
                        ],
                        'orders'          => [$order],
                        'address'         => [
                            'name'    => $request->input('customer_name') ?: $account?->name,
                            'email'   => $request->input('customer_email') ?: $account?->email,
                            'phone'   => $account?->phone,
                            'country' => null,
                            'state'   => null,
                            'city'    => null,
                            'address' => null,
                            'zip'     => null,
                        ],
                        'checkout_token'  => $request->input('callback_url'),
                    ];
                }

                if ($jobAlertOrderId = $request->input('job_alert_order_id')) {
                    $order = JobAlertOrder::query()->with('package')->find($jobAlertOrderId);

                    if (! $order || ! $order->package?->is_active) {
                        return $data;
                    }

                    /** @var Account $account */
                    $account = auth('account')->user();
                    $package = $order->package;

                    session([
                        'job_alert_order_id'       => $order->getKey(),
                        'job_alert_callback_url'   => $request->input('callback_url'),
                        'job_alert_return_url'     => $request->input('return_url'),
                    ]);

                    return [
                        'amount'          => (float) $order->amount,
                        'shipping_amount' => 0,
                        'shipping_method' => null,
                        'tax_amount'      => 0,
                        'currency'        => strtoupper($order->currency),
                        'order_id'        => [$order->getKey()],
                        'description'     => trans('plugins/payment::payment.payment_description', [
                            'order_id' => $order->getKey(),
                            'site_url' => $request->getHost(),
                        ]),
                        'customer_id'     => $account?->getKey(),
                        'customer_type'   => $account ? Account::class : null,
                        'email'           => $request->input('customer_email') ?: $account?->email,
                        'return_url'      => $request->input('return_url'),
                        'callback_url'    => $request->input('callback_url'),
                        'products'        => [
                            [
                                'id'              => $package->getKey(),
                                'name'            => $package->name . ' — Job Alerts',
                                'price'           => (float) $order->amount,
                                'price_per_order' => (float) $order->amount,
                                'qty'             => 1,
                            ],
                        ],
                        'orders'          => [$order],
                        'address'         => [
                            'name'    => $request->input('customer_name') ?: $account?->name,
                            'email'   => $request->input('customer_email') ?: $account?->email,
                            'phone'   => $account?->phone,
                            'country' => null,
                            'state'   => null,
                            'city'    => null,
                            'address' => null,
                            'zip'     => null,
                        ],
                        'checkout_token'  => $request->input('callback_url'),
                    ];
                }

                if ($careerServiceOrderId = $request->input('career_service_order_id')) {
                    $order = CareerServiceOrder::query()->find($careerServiceOrderId);

                    if (! $order) {
                        return $data;
                    }

                    $account = auth('account')->user();
                    $customerId = $account?->getKey() ?: $order->candidate_id;
                    $customerType = $customerId ? Account::class : null;
                    $customerName = $request->input('customer_name') ?: $account?->name ?: $order->customer_name;
                    $customerEmail = $request->input('customer_email') ?: $account?->email ?: $order->customer_email;
                    $customerPhone = $request->input('customer_phone') ?: $account?->phone ?: $order->customer_phone;

                    $order->update([
                        'customer_name' => $customerName,
                        'customer_email' => $customerEmail,
                        'customer_phone' => $customerPhone,
                    ]);

                    session([
                        'career_service_order_id' => $order->getKey(),
                        'career_service_callback_url' => $request->input('callback_url'),
                        'career_service_return_url' => $request->input('return_url'),
                    ]);

                    return [
                        'amount' => (float) $order->amount,
                        'shipping_amount' => 0,
                        'shipping_method' => null,
                        'tax_amount' => 0,
                        'currency' => strtoupper($order->currency),
                        'order_id' => [$order->getKey()],
                        'description' => trans('plugins/payment::payment.payment_description', [
                            'order_id' => $order->getKey(),
                            'site_url' => $request->getHost(),
                        ]),
                        'customer_id' => $customerId,
                        'customer_type' => $customerType,
                        'email' => $customerEmail,
                        'return_url' => $request->input('return_url'),
                        'callback_url' => $request->input('callback_url'),
                        'products' => [
                            [
                                'id' => $order->getKey(),
                                'name' => $order->service_name,
                                'price' => (float) $order->amount,
                                'price_per_order' => (float) $order->amount,
                                'qty' => 1,
                            ],
                        ],
                        'orders' => [$order],
                        'address' => [
                            'name' => $customerName,
                            'email' => $customerEmail,
                            'phone' => $customerPhone,
                            'country' => null,
                            'state' => null,
                            'city' => null,
                            'address' => null,
                            'zip' => null,
                        ],
                        'checkout_token' => $request->input('callback_url'),
                    ];
                }

                // Handle salary report purchase checkout
                if ($salaryReportId = $request->input('salary_report_id')) {
                    $report = SalaryReport::query()->find($salaryReportId);

                    if (! $report || ! $report->is_published) {
                        return $data;
                    }

                    $buyerName    = $request->input('buyer_name', '');
                    $buyerEmail   = $request->input('buyer_email', '');
                    $buyerCompany = $request->input('buyer_company');

                    session([
                        'salary_report_id'           => $report->getKey(),
                        'salary_report_buyer_name'   => $buyerName,
                        'salary_report_buyer_email'  => $buyerEmail,
                        'salary_report_buyer_company'=> $buyerCompany,
                        'salary_report_callback_url' => $request->input('callback_url', route('salary-reports.public.index')),
                        'salary_report_return_url'   => $request->input('return_url', route('salary-reports.public.show', $report->slug)),
                    ]);

                    return [
                        'amount'          => (float) $report->price,
                        'shipping_amount' => 0,
                        'shipping_method' => null,
                        'tax_amount'      => 0,
                        'currency'        => strtoupper($report->currency_code),
                        'order_id'        => [$report->getKey()],
                        'description'     => "Purchase: {$report->title}",
                        'customer_id'     => null,
                        'customer_type'   => null,
                        'email'           => $buyerEmail,
                        'return_url'      => $request->input('return_url', route('salary-reports.public.show', $report->slug)),
                        'callback_url'    => $request->input('callback_url', route('salary-reports.public.index')),
                        'products'        => [
                            [
                                'id'              => $report->getKey(),
                                'name'            => $report->title,
                                'price'           => (float) $report->price,
                                'price_per_order' => (float) $report->price,
                                'qty'             => 1,
                            ],
                        ],
                        'orders'   => [$report],
                        'address'  => [
                            'name'    => $buyerName,
                            'email'   => $buyerEmail,
                            'phone'   => null,
                            'country' => null,
                            'state'   => null,
                            'city'    => null,
                            'address' => null,
                            'zip'     => null,
                        ],
                        'checkout_token' => $request->input('callback_url', route('salary-reports.public.index')),
                    ];
                }

                if (session('subscribed_packaged_id')) {
                    session()->forget([
                        'career_service_order_id',
                        'career_service_callback_url',
                        'career_service_return_url',
                    ]);
                }

                $orderIds = [session('subscribed_packaged_id')];

                $package = Package::query()->whereIn('id', $orderIds)->first();

                if (! $package) {
                    return $data;
                }

                $discountAmount = 0;

                $couponService = $this->app->make(CouponService::class);

                if (Session::has('applied_coupon_code')) {
                    $coupon = $couponService->getCouponByCode(Session::get('applied_coupon_code'));

                    if ($coupon) {
                        $discountAmount = $couponService->getDiscountAmount(
                            $coupon->type->getValue(),
                            $coupon->value,
                            $package->price
                        );

                        $coupon->increment('total_used');
                    }
                }

                $price = $couponService->getAmountAfterDiscount($discountAmount, $package->price);

                $products = [
                    [
                        'id' => $package->id,
                        'name' => $package->name,
                        'price' => $this->convertOrderAmount($package->price - $discountAmount),
                        'price_per_order' => $this->convertOrderAmount($package->price - $discountAmount),
                        'qty' => 1,
                    ],
                ];

                $account = auth('account')->user();

                $address = [
                    'name' => $account->name,
                    'email' => $account->email,
                    'phone' => $account->phone,
                    'country' => null,
                    'state' => null,
                    'city' => null,
                    'address' => null,
                    'zip' => null,
                ];

                return [
                    'amount' => $this->convertOrderAmount($price),
                    'shipping_amount' => 0,
                    'shipping_method' => null,
                    'tax_amount' => 0,
                    'currency' => strtoupper(get_application_currency()->title),
                    'order_id' => $orderIds,
                    'description' => trans('plugins/payment::payment.payment_description', ['order_id' => Arr::first($orderIds), 'site_url' => request()->getHost()]),
                    'customer_id' => $account->getKey(),
                    'customer_type' => Account::class,
                    'email' => $account->email,
                    'return_url' => $request->input('return_url'),
                    'callback_url' => $request->input('callback_url'),
                    'products' => $products,
                    'orders' => [$package],
                    'address' => $address,
                    'checkout_token' => session('subscribed_packaged_id'),
                ];
            }, 120, 2);
        }

        add_action(BASE_ACTION_META_BOXES, function ($context, $object): void {
            if (request()->segment(1) == 'account') {
                MetaBox::removeMetaBox('seo_wrap', Job::class, 'advanced');
            }

            if (get_class($object) == Account::class && $object->isEmployer()) {
                MetaBox::removeMetaBox('seo_wrap', Account::class, 'advanced');
            }
        }, 11, 2);

        add_filter(BASE_FILTER_SLUG_AREA, function (?string $html, $object) {
            if (get_class($object) == Account::class && $object->isEmployer()) {
                return '';
            }

            return $html;
        }, 27, 2);

        if (defined('THEME_FRONT_HEADER')) {
            add_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, function ($screen, $job): void {
                add_filter(THEME_FRONT_HEADER, function ($html) use ($job) {
                    if (get_class($job) != Job::class) {
                        return $html;
                    }

                    // Jobbox renders its own complete JobPosting schema in the job view.
                    // Emitting this legacy schema as well creates two conflicting job items.
                    if (Theme::getThemeName() === 'jobbox') {
                        return $html;
                    }

                    $expiredAt = Carbon::now()->toDateString();

                    if (! $job->is_expired) {
                        if ($job->expire_date) {
                            $expiredAt = $job->expire_date->toDateString();
                        } else {
                            $expiredAt = Carbon::now()->addDays(JobBoardHelper::jobExpiredDays())->toDateString();
                        }
                    }

                    $address = [
                        '@type' => 'PostalAddress',
                        'addressLocality' => $job->city_name . ', ' . $job->state_name,
                        'addressRegion' => $job->state_name,
                        'addressCountry' => $job->country_name,
                    ];

                    if (! empty($job->address)) {
                        $address['streetAddress'] = $job->address;
                    }

                    $postalCode = $job->zip_code;
                    if (empty($postalCode) && $job->relationLoaded('city') && $job->city && ! empty($job->city->zip_code)) {
                        $postalCode = $job->city->zip_code;
                    }

                    if (! empty($postalCode)) {
                        $address['postalCode'] = $postalCode;
                    }

                    $schema = [
                        '@context' => 'https://schema.org',
                        '@type' => 'JobPosting',
                        'title' => $job->name,
                        'url' => $job->url,
                        'image' => [
                            '@type' => 'ImageObject',
                            'url' => RvMedia::getImageUrl(theme_option('logo')),
                        ],
                        'description' => BaseHelper::clean($job->content),
                        'employmentType' => implode(', ', $job->jobTypes->pluck('name')->all()),
                        'jobLocation' => [
                            '@type' => 'Place',
                            'address' => $address,
                        ],
                        'hiringOrganization' => [
                            '@type' => 'Organization',
                            'name' => $job->company->name,
                            'url' => $job->company->website,
                            'logo' => [
                                '@type' => 'ImageObject',
                                'url' => RvMedia::getImageUrl($job->company->logo, null, false, theme_option('logo')),
                            ],
                        ],
                        'baseSalary' => [
                            '@type' => 'MonetaryAmount',
                            'currency' => strtoupper(get_application_currency()->title),
                            'minValue' => $job->salary_from,
                            'maxValue' => $job->salary_to,
                            'unitText' => strtoupper($job->salary_range),
                        ],
                        'validThrough' => $expiredAt,
                        'datePosted' => $job->created_at->toIso8601String(),
                    ];

                    return $html . Html::tag('script', json_encode($schema), ['type' => 'application/ld+json'])
                            ->toHtml();
                }, 30);
            }, 30, 2);
        }

        add_filter('account_dashboard_header', function ($html) {
            $customCSSFile = public_path(Theme::path() . '/css/style.integration.css');
            if (File::exists($customCSSFile)) {
                $html .= Html::style(Theme::asset()
                    ->url('css/style.integration.css?v=' . filectime($customCSSFile)));
            }

            return $html . ThemeSupport::getCustomJS('header');
        }, 15);

        if (is_plugin_active('payment')) {
            add_filter(PAYMENT_FILTER_PAYMENT_INFO_DETAIL, function ($data, $payment) {
                $invoice = Invoice::query()->where('payment_id', $payment->id)->first();

                if (! $invoice) {
                    return $data;
                }

                $button = view('plugins/job-board::partials.invoice-buttons', compact('invoice'))->render();

                return $data . $button;
            }, 3, 2);
        }

        if (defined('PAGE_MODULE_SCREEN_NAME')) {
            add_filter(PAGE_FILTER_PAGE_NAME_IN_ADMIN_LIST, [$this, 'addAdditionNameToPageName'], 124, 2);
        }

        add_filter('social_login_before_saving_account', function ($data, $oAuth, $providerData) {
            if (Arr::get($providerData, 'model') == Account::class && Arr::get($providerData, 'guard') == 'account') {
                $firstName = implode(' ', explode(' ', $oAuth->getName(), -1));
                Arr::forget($data, 'name');

                $data = array_merge($data, [
                    'first_name' => $firstName,
                    'last_name'  => trim(str_replace($firstName, '', $oAuth->getName())),
                ]);

                // New social accounts must choose their account type after OAuth.
                $isNew = ! Account::query()->where('email', $oAuth->getEmail())->exists();
                if ($isNew) {
                    $data['type'] = '';
                    session(['url.intended' => route('public.account.choose-type')]);
                }
            }

            return $data;
        }, 49, 3);

        add_filter('social_login_before_creating_account', function ($data) {
            if (! JobBoardHelper::isRegisterEnabled()) {
                return (new BaseHttpResponse())
                    ->setError()
                    ->setMessage(trans('auth.failed'));
            }

            return $data;
        }, 49);

        add_action(BASE_ACTION_TOP_FORM_CONTENT_NOTIFICATION, function (Request $request, BaseModel|string|null $model = null): void {
            if (! $model instanceof Job || Route::currentRouteName() !== 'public.account.jobs.edit') {
                return;
            }

            if ($request->user('account')->companies()->exists()) {
                return;
            }

            echo view('plugins/job-board::partials.no-companies-alert')->render();
        }, 0, 2);

        add_filter('core_slug_can_be_reviewed', function (bool $canBeReviewed) {
            return $canBeReviewed || (auth('account')->check() && AdminHelper::isInAdmin());
        }, 999, 2);

        Menu::addMenuOptionModel(Category::class);

        $this->app['events']->listen(RenderingMenuOptions::class, function (): void {
            add_action(MENU_ACTION_SIDEBAR_OPTIONS, [$this, 'registerMenuOptions'], 2);
        });

        $clearFilterCache = function (): void {
            Cache::forget('job_filter_data');
        };

        Category::saved($clearFilterCache);
        Category::deleted($clearFilterCache);
        JobType::saved($clearFilterCache);
        JobType::deleted($clearFilterCache);
        JobExperience::saved($clearFilterCache);
        JobExperience::deleted($clearFilterCache);
        JobSkill::saved($clearFilterCache);
        JobSkill::deleted($clearFilterCache);
        Job::saved($clearFilterCache);
        Job::deleted($clearFilterCache);
    }

    public function registerMenuOptions(): void
    {
        Menu::registerMenuOptions(Category::class, trans('plugins/job-board::job-category.job_categories'));
    }

    public function countPendingApplications(?string $number, string $menuId): ?string
    {
        if ($menuId === 'cms-plugins-job-board-main'
            && ! Auth::user()->hasPermission('job-applications.index')) {
            $className = null;
        } else {
            $className = match ($menuId) {
                'cms-plugins-job-board-main' => 'job-board-count',
                'cms-plugins-job-board-application' => 'pending-applications',
                'cms-plugins-job-board-company' => 'pending-companies',
                'cms-plugins-job-board-jobs' => 'pending-jobs',
                'cms-plugins-job-board-accounts' => 'pending-accounts',
                'cms-plugins-job-board-career-service-orders' => 'pending-career-services',
                'cms-plugins-job-board-job-alert-orders' => 'pending-job-alert-orders',
                'cms-plugins-job-board-featured-orders'           => 'pending-featured-orders',
                'cms-plugins-job-board-employer-subscriptions'    => 'pending-employer-subscriptions',
                'cms-plugins-job-board-wakanda-verification'      => 'pending-wakanda-verifications',
                'cms-plugins-job-board-candidate-alerts'          => 'active-candidate-alerts',
                default => null,
            };
        }

        return $className ? view('core/base::partials.navbar.badge-count', ['class' => $className])->render() : $number;
    }

    public function getMenuItemCount(array $data = []): array
    {
        if (Auth::user()->hasPermission('job-applications.index')) {
            $pendingApplications = JobApplication::query()
                ->where('status', JobApplicationStatusEnum::PENDING)
                ->count();

            $data[] = [
                'key' => 'pending-applications',
                'value' => $pendingApplications,
            ];

            $pendingCompanies = Company::query()
                ->where('status', BaseStatusEnum::PENDING)
                ->count();

            $data[] = [
                'key' => 'pending-companies',
                'value' => $pendingCompanies,
            ];

            $pendingJobs = Job::query()
                ->where('moderation_status', ModerationStatusEnum::PENDING)
                ->count();

            $data[] = [
                'key' => 'pending-jobs',
                'value' => $pendingJobs,
            ];

            $pendingAccounts = Account::query()
                ->whereNull('confirmed_at')
                ->count();

            $data[] = [
                'key' => 'pending-accounts',
                'value' => $pendingAccounts,
            ];

            $pendingCareerServices = Auth::user()->hasPermission('career-service-orders.index')
                ? CareerServiceOrder::query()->where('delivery_status', 'unassigned')->count()
                : 0;

            $data[] = [
                'key' => 'pending-career-services',
                'value' => $pendingCareerServices,
            ];

            $pendingJobAlertOrders = Auth::user()->hasPermission('job-alert-orders.index')
                ? JobAlertOrder::query()->where('status', 'pending')->count()
                : 0;

            $data[] = [
                'key' => 'pending-job-alert-orders',
                'value' => $pendingJobAlertOrders,
            ];

            $pendingFeaturedOrders = Auth::user()->hasPermission('featured-orders.index')
                ? FeaturedOrder::query()->where('status', 'pending')->count()
                : 0;

            $data[] = [
                'key'   => 'pending-featured-orders',
                'value' => $pendingFeaturedOrders,
            ];

            $pendingSubscriptions = Auth::user()->hasPermission('employer-subscriptions.index')
                ? EmployerSubscription::query()->where('status', 'pending')->count()
                : 0;

            $data[] = [
                'key'   => 'pending-employer-subscriptions',
                'value' => $pendingSubscriptions,
            ];

            $pendingWakandaVerifications = WakandaVerificationRequest::query()
                ->where('status', 'pending')
                ->count();

            $data[] = [
                'key'   => 'pending-wakanda-verifications',
                'value' => $pendingWakandaVerifications,
            ];

            $activeCandidateAlerts = CandidateAlert::where('is_active', true)->where('status', 'active')->count();

            $data[] = [
                'key'   => 'active-candidate-alerts',
                'value' => $activeCandidateAlerts,
            ];

            $data[] = [
                'key'   => 'job-board-count',
                'value' => $pendingApplications + $pendingCompanies + $pendingJobs + $pendingAccounts + $pendingCareerServices + $pendingJobAlertOrders + $pendingFeaturedOrders + $pendingSubscriptions + $pendingWakandaVerifications,
            ];
        }

        return $data;
    }

    public function addThemeOptions(): void
    {
        $pages = Page::query()
            ->wherePublished()
            ->pluck('name', 'id')
            ->all();

        theme_option()
            ->setSection([
                'title' => trans('plugins/job-board::job-board.theme_options.name'),
                'desc' => trans('plugins/job-board::job-board.theme_options.description'),
                'id' => 'opt-text-subsection-job-board',
                'subsection' => true,
                'icon' => 'ti ti-briefcase',
                'fields' => [
                    [
                        'id' => 'logo_employer_dashboard',
                        'type' => 'mediaImage',
                        'label' => trans('plugins/job-board::job-board.theme_options.logo_employer_dashboard'),
                        'attributes' => [
                            'name' => 'logo_employer_dashboard',
                            'value' => null,
                        ],
                    ],
                    [
                        'id' => 'default_company_cover_image',
                        'type' => 'mediaImage',
                        'label' => trans('plugins/job-board::job-board.theme_options.default_company_cover_image'),
                        'attributes' => [
                            'name' => 'default_company_cover_image',
                            'value' => null,
                        ],
                    ],
                    [
                        'id' => 'default_company_logo',
                        'type' => 'mediaImage',
                        'label' => trans('plugins/job-board::job-board.theme_options.default_company_logo'),
                        'attributes' => [
                            'name' => 'default_company_logo',
                            'value' => null,
                        ],
                    ],
                    [
                        'id' => 'job_companies_page_id',
                        'type' => 'customSelect',
                        'label' => trans('plugins/job-board::job-board.theme_options.job_companies_page_id'),
                        'attributes' => [
                            'name' => 'job_companies_page_id',
                            'list' => ['' => trans('plugins/job-board::forms.select_placeholder')] + $pages,
                            'value' => '',
                            'options' => [
                                'class' => 'form-control',
                            ],
                        ],
                    ],
                    [
                        'id' => 'job_categories_page_id',
                        'type' => 'customSelect',
                        'label' => trans('plugins/job-board::job-board.theme_options.job_categories_page_id'),
                        'attributes' => [
                            'name' => 'job_categories_page_id',
                            'list' => ['' => trans('plugins/job-board::forms.select_placeholder')] + $pages,
                            'value' => '',
                            'options' => [
                                'class' => 'form-control',
                            ],
                        ],
                    ],
                    [
                        'id' => 'job_candidates_page_id',
                        'type' => 'customSelect',
                        'label' => trans('plugins/job-board::job-board.theme_options.job_candidates_page_id'),
                        'attributes' => [
                            'name' => 'job_candidates_page_id',
                            'list' => ['' => trans('plugins/job-board::forms.select_placeholder')] + $pages,
                            'value' => '',
                            'options' => [
                                'class' => 'form-control',
                            ],
                        ],
                    ],
                    [
                        'id' => 'job_list_page_id',
                        'type' => 'customSelect',
                        'label' => trans('plugins/job-board::job-board.theme_options.job_list_page_id'),
                        'attributes' => [
                            'name' => 'job_list_page_id',
                            'list' => ['' => trans('plugins/job-board::forms.select_placeholder')] + $pages,
                            'value' => '',
                            'options' => [
                                'class' => 'form-control',
                            ],
                        ],
                    ],
                ],
            ])
            ->setField([
                'id' => 'show_map_on_jobs_page',
                'section_id' => 'opt-text-subsection-job-board',
                'type' => 'customSelect',
                'label' => trans('plugins/job-board::forms.show_map_on_jobs_page'),
                'attributes' => [
                    'name' => 'show_map_on_jobs_page',
                    'list' => [
                        'yes' => trans('core/base::base.yes'),
                        'no' => trans('core/base::base.no'),
                    ],
                    'value' => 'yes',
                ],
            ])
            ->setField([
                'id' => 'latitude_longitude_center_on_jobs_page',
                'section_id' => 'opt-text-subsection-job-board',
                'type' => 'text',
                'label' => trans('plugins/job-board::forms.latitude_longitude_center'),
                'attributes' => [
                    'name' => 'latitude_longitude_center_on_jobs_page',
                    'value' => '43.615134, -76.393186',
                    'options' => [
                        'class' => 'form-control',
                    ],
                ],
            ]);
    }

    public function addAdditionNameToPageName(?string $name, Page $page): ?string
    {
        $subTitle = null;

        switch ($page->getKey()) {
            case theme_option('job_list_page_id'):
                $subTitle = trans('plugins/job-board::job-board.jobs_page');

                break;
            case theme_option('job_categories_page_id'):
                $subTitle = trans('plugins/job-board::job-board.categories_page');

                break;
            case theme_option('job_companies_page_id'):
                $subTitle = trans('plugins/job-board::job-board.companies_page');

                break;
            case theme_option('job_candidates_page_id'):
                $subTitle = trans('plugins/job-board::job-board.candidates_page');

                break;
        }

        if ($subTitle) {
            $subTitle = Html::tag('span', $subTitle, ['class' => 'additional-page-name'])
                ->toHtml();

            if (Str::contains($name, ' —')) {
                return $name . ', ' . $subTitle;
            }

            return $name . ' —' . $subTitle;
        }

        return $name;
    }

    protected function convertOrderAmount(float $amount): float
    {
        $currentCurrency = get_application_currency();

        if ($currentCurrency->is_default) {
            return $amount;
        }

        return (float) format_price($amount * $currentCurrency->exchange_rate, $currentCurrency, true);
    }

    protected function sendSubscriptionAdminEmail(EmployerSubscription $sub, bool $isManual): void
    {
        $adminEmail = setting('admin_email') ?: config('mail.from.address');
        if (! $adminEmail) {
            return;
        }

        $packageName   = $sub->package?->name ?? 'Unknown plan';
        $customerName  = $sub->account?->name ?? 'Unknown';
        $customerEmail = $sub->account?->email ?? '';
        $cycle         = ucfirst($sub->billing_cycle);
        $status        = $isManual ? 'Pending manual verification' : 'Auto-activated';
        $adminUrl      = url('/admin/employer-subscriptions');

        try {
            Mail::raw(
                "New Employer Subscription\n\n" .
                "Subscription #: {$sub->id}\n" .
                "Plan: {$packageName} ({$cycle})\n" .
                "Amount: {$sub->currency} {$sub->amount}\n" .
                "Customer: {$customerName} ({$customerEmail})\n" .
                "Payment method: " . ucwords(str_replace('_', ' ', $sub->payment_method ?? '')) . "\n" .
                "Charge ID: {$sub->charge_id}\n" .
                "Status: {$status}\n\n" .
                ($isManual ? "Action required — verify payment and activate at:\n{$adminUrl}" : "No action required."),
                function ($msg) use ($adminEmail, $packageName, $isManual): void {
                    $subject = $isManual
                        ? "Subscription Pending Approval: {$packageName}"
                        : "New Subscription Activated: {$packageName}";
                    $msg->to($adminEmail)->subject($subject);
                }
            );
        } catch (\Throwable) {
        }
    }

    protected function sendFeaturedOrderAdminEmail(FeaturedOrder $order, bool $isManual): void
    {
        $adminEmail = setting('admin_email') ?: config('mail.from.address');
        if (! $adminEmail) {
            return;
        }

        $packageName   = $order->package?->name ?? 'Unknown package';
        $jobName       = $order->job?->name ?? 'Unknown job';
        $customerName  = $order->account?->name ?? 'Unknown';
        $customerEmail = $order->account?->email ?? '';
        $paymentMethod = ucwords(str_replace('_', ' ', $order->payment_method ?? ''));
        $status        = $isManual ? 'Pending — awaiting manual payment verification' : 'Auto-approved';
        $adminUrl      = url('/admin/featured-orders');

        try {
            Mail::raw(
                "New Featured Job Order\n\n" .
                "Order #: {$order->id}\n" .
                "Package: {$packageName}\n" .
                "Job: {$jobName}\n" .
                "Amount: {$order->currency} {$order->amount}\n" .
                "Customer: {$customerName} ({$customerEmail})\n" .
                "Payment method: {$paymentMethod}\n" .
                "Charge ID: {$order->charge_id}\n" .
                "Status: {$status}\n\n" .
                ($isManual ? "Action required — verify payment and approve at:\n{$adminUrl}" : "No action required."),
                function ($msg) use ($adminEmail, $packageName, $isManual): void {
                    $subject = $isManual
                        ? "Featured Job Order Pending Approval: {$packageName}"
                        : "Featured Job Order Received: {$packageName}";
                    $msg->to($adminEmail)->subject($subject);
                }
            );
        } catch (\Throwable) {
            // Non-fatal
        }
    }

    protected function sendJobAlertOrderAdminEmail(JobAlertOrder $order, bool $isManual): void
    {
        $adminEmail = setting('admin_email') ?: config('mail.from.address');
        if (! $adminEmail) {
            return;
        }

        $packageName = $order->package?->name ?? 'Unknown package';
        $customerName = $order->account?->name ?? 'Unknown';
        $customerEmail = $order->account?->email ?? '';
        $paymentMethod = ucwords(str_replace('_', ' ', $order->payment_method ?? ''));
        $status = $isManual ? 'Pending — awaiting manual payment verification' : 'Auto-approved';
        $adminUrl = url('/admin/job-alert-orders');

        try {
            Mail::raw(
                "New Job Alert Package Order\n\n" .
                "Order #: {$order->id}\n" .
                "Package: {$packageName}\n" .
                "Amount: {$order->currency} {$order->amount}\n" .
                "Customer: {$customerName} ({$customerEmail})\n" .
                "Payment method: {$paymentMethod}\n" .
                "Charge ID: {$order->charge_id}\n" .
                "Status: {$status}\n\n" .
                ($isManual ? "Action required — verify payment and approve at:\n{$adminUrl}" : "No action required."),
                function ($msg) use ($adminEmail, $packageName, $isManual): void {
                    $subject = $isManual
                        ? "Job Alert Order Pending Approval: {$packageName}"
                        : "Job Alert Order Received: {$packageName}";
                    $msg->to($adminEmail)->subject($subject);
                }
            );
        } catch (\Throwable) {
            // Non-fatal
        }
    }

    public function registerDashboardStats(array $widgets, Collection $widgetSettings): array
    {
        (new DashboardWidgetInstance())
            ->setType('stats')
            ->setPermission('jobs.index')
            ->setTitle('Total Jobs')
            ->setKey('widget_total_jobs')
            ->setIcon('ti ti-briefcase')
            ->setColor('blue')
            ->setStatsTotal(fn () => Job::query()->count())
            ->setRoute(route('jobs.index'))
            ->setColumn('col-12 col-md-6 col-lg-2')
            ->setPriority(1)
            ->init($widgets, $widgetSettings);

        (new DashboardWidgetInstance())
            ->setType('stats')
            ->setPermission('job-applications.index')
            ->setTitle('Applications')
            ->setKey('widget_total_applications')
            ->setIcon('ti ti-file-description')
            ->setColor('cyan')
            ->setStatsTotal(fn () => JobApplication::query()->count())
            ->setRoute(route('job-applications.index'))
            ->setColumn('col-12 col-md-6 col-lg-2')
            ->setPriority(2)
            ->init($widgets, $widgetSettings);

        (new DashboardWidgetInstance())
            ->setType('stats')
            ->setPermission('payments.logs.index')
            ->setTitle('Payments')
            ->setKey('widget_total_payments')
            ->setIcon('ti ti-credit-card')
            ->setColor('green')
            ->setStatsTotal(fn () => Payment::query()->where('status', PaymentStatusEnum::COMPLETED)->count())
            ->setRoute(route('payments.logs.index'))
            ->setColumn('col-12 col-md-6 col-lg-2')
            ->setPriority(3)
            ->init($widgets, $widgetSettings);

        (new DashboardWidgetInstance())
            ->setType('stats')
            ->setPermission('newsletter.index')
            ->setTitle('Newsletter Subscribers')
            ->setKey('widget_total_newsletter_subscribers')
            ->setIcon('ti ti-mail')
            ->setColor('purple')
            ->setStatsTotal(fn () => \DB::table('newsletters')->count())
            ->setRoute(route('newsletter.index'))
            ->setColumn('col-12 col-md-6 col-lg-2')
            ->setPriority(4)
            ->init($widgets, $widgetSettings);

        (new DashboardWidgetInstance())
            ->setType('stats')
            ->setPermission('job-alert-orders.index')
            ->setTitle('Job Alert Orders')
            ->setKey('widget_total_job_alert_orders')
            ->setIcon('ti ti-bell-dollar')
            ->setColor('indigo')
            ->setStatsTotal(fn () => \DB::table('jb_job_alert_orders')->count())
            ->setRoute(route('job-alert-orders.index'))
            ->setColumn('col-12 col-md-6 col-lg-2')
            ->setPriority(5)
            ->init($widgets, $widgetSettings);

        (new DashboardWidgetInstance())
            ->setType('stats')
            ->setPermission('accounts.index')
            ->setTitle('Candidates')
            ->setKey('widget_total_candidates')
            ->setIcon('ti ti-users')
            ->setColor('orange')
            ->setStatsTotal(fn () => Account::query()->count())
            ->setRoute(route('accounts.index'))
            ->setColumn('col-12 col-md-6 col-lg-2')
            ->setPriority(6)
            ->init($widgets, $widgetSettings);

        (new DashboardWidgetInstance())
            ->setType('stats')
            ->setPermission('companies.index')
            ->setTitle('Active Now')
            ->setKey('widget_active_sessions')
            ->setIcon('ti ti-activity')
            ->setColor('teal')
            ->setStatsTotal(function () {
                $sessionPath = storage_path('framework/sessions');
                if (! is_dir($sessionPath)) {
                    return 0;
                }
                $cutoff = time() - 900;

                return count(array_filter((array) glob($sessionPath . '/*'), fn ($f) => @filemtime($f) >= $cutoff));
            })
            ->setRoute(route('companies.index'))
            ->setColumn('col-12 col-md-6 col-lg-2')
            ->setPriority(7)
            ->init($widgets, $widgetSettings);

        (new DashboardWidgetInstance())
            ->setType('stats')
            ->setPermission('job-board.automations.index')
            ->setTitle('📢 Broadcast to Channels')
            ->setKey('widget_social_broadcast_shortcut')
            ->setIcon('ti ti-speakerphone')
            ->setColor('pink')
            ->setStatsTotal(fn () => \Botble\JobBoard\Models\SocialAutomation::query()
                ->whereIn('platform', ['facebook', 'linkedin', 'whatsapp', 'whapi', 'publer'])
                ->where('is_active', true)
                ->count())
            ->setRoute(route('job-board.automations.broadcast'))
            ->setColumn('col-12 col-md-6 col-lg-2')
            ->setPriority(8)
            ->init($widgets, $widgetSettings);

        return $widgets;
    }
}
