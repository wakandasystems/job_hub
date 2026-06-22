        {!! dynamic_sidebar('pre_footer_sidebar') !!}
    </main>
    @if (is_plugin_active('ads'))
        {!! apply_filters('ads_render', null, 'footer_before', ['class' => 'my-2 text-center']) !!}
    @endif

    <footer class="footer mt-50">
        <div class="container">
            <div class="row">
                {!! dynamic_sidebar('footer_sidebar') !!}
            </div>
            @php
                $footerSelectedCountry = wakanda_selected_country();
                $footerTgChannels      = wakanda_all_telegram_channels();
                $footerVipPlans        = \Botble\JobBoard\Models\VipAlertOrder::plans();
                $footerVipStartingPlan = collect($footerVipPlans)->sortBy('price')->first();
                $footerAppCurrency     = get_application_currency();
                $footerVipCurrency     = $footerAppCurrency->title ?? ($footerVipStartingPlan['currency'] ?? 'USD');
                if ($footerVipStartingPlan) {
                    $footerPlanCurrency = \Botble\JobBoard\Models\Currency::query()->where('title', $footerVipStartingPlan['currency'])->first();
                    $footerPriceInDefault = ($footerPlanCurrency && !$footerPlanCurrency->is_default && $footerPlanCurrency->exchange_rate > 0)
                        ? $footerVipStartingPlan['price'] / $footerPlanCurrency->exchange_rate
                        : $footerVipStartingPlan['price'];
                    $footerConvertedPrice = ($footerAppCurrency && !$footerAppCurrency->is_default && $footerAppCurrency->exchange_rate > 0)
                        ? $footerPriceInDefault * $footerAppCurrency->exchange_rate
                        : $footerPriceInDefault;
                    $footerVipStartingText = $footerVipCurrency . ' ' . number_format($footerConvertedPrice, 2);
                } else {
                    $footerVipStartingText = null;
                }
            @endphp
            <div class="row border-top pt-4 mt-4">
                <div class="col-12 text-center">
                    <p class="font-xs color-text-paragraph mb-2">
                        <a href="{{ route('public.vip-alerts.plans') }}"
                           class="badge px-3 py-2 me-2 text-decoration-none"
                           style="background:linear-gradient(135deg,#25d366,#128c4a);color:#fff;font-size:.78rem;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="#fff" style="vertical-align:middle;margin-right:4px;" aria-hidden="true"><path d="M12.04 2a9.84 9.84 0 0 0-8.43 14.92L2.05 22l5.2-1.52A9.96 9.96 0 1 0 12.04 2Zm4.34 13.02c-.24-.12-1.4-.69-1.62-.77-.22-.08-.38-.12-.54.12-.16.24-.61.77-.75.93-.14.16-.28.18-.52.06-.24-.12-1-.37-1.91-1.18a7.17 7.17 0 0 1-1.32-1.64c-.14-.24-.01-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.2-.47-.4-.4-.54-.41h-.46a.88.88 0 0 0-.63.3c-.22.24-.83.81-.83 1.98s.85 2.3.97 2.46c.12.16 1.67 2.55 4.05 3.58.57.24 1.01.39 1.35.5.57.18 1.08.15 1.49.09.45-.07 1.4-.57 1.6-1.12.2-.55.2-1.03.14-1.12-.06-.1-.22-.16-.46-.28Z"/></svg>
                            VIP Alerts{{ $footerVipStartingText ? ' — from ' . $footerVipStartingText : '' }}
                        </a>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="#229ED9" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;margin-right:4px;"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L7.065 13.85l-2.947-.924c-.64-.204-.657-.64.136-.954l11.57-4.46c.532-.194.998.13.82.95l-.75-.241z"/></svg>
                        Join our Telegram channels for real-time job updates:
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        @foreach($footerTgChannels as $ch)
                            @php $isSelected = $footerSelectedCountry && (int)$footerSelectedCountry->id === (int)$ch['country_id']; @endphp
                            <a href="{{ $ch['url'] }}" target="_blank" rel="noopener"
                               class="badge text-decoration-none px-2 py-1 font-xs {{ $isSelected ? 'text-white' : 'bg-light text-dark border' }}"
                               style="{{ $isSelected ? 'background:#229ED9;' : '' }}">
                                {{ $ch['name'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="footer-bottom mt-50">
                <div class="row">
                    <div class="col-md-6">
                        <span class="font-xs color-text-paragraph">
                            {!! BaseHelper::clean(theme_option('copyright')) !!}
                        </span>
                    </div>
                    <div class="col-md-6 text-md-end text-start">
                        <div class="footer-social">
                            {!!
                                Menu::renderMenuLocation('footer-menu', [
                                    'options' => ['class' => 'footer_menu'],
                                    'view'    => 'support-menu',
                                ])
                            !!}
                        </div>
                        <div class="nav float-right language-switcher-footer">
                            @if (is_plugin_active('language'))
                                @include(JobBoardHelper::viewPath('dashboard.partials.language-switcher'))
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    @if (is_plugin_active('ads'))
        {!! apply_filters('ads_render', null, 'footer_after', ['class' => 'my-2 text-center']) !!}
    @endif
    <script>
        @if (is_plugin_active('job-board'))
            window.currencies = {!! json_encode(get_currencies_json()) !!};
        @endif

        window.alertTranslations = {
            'success': "{{ __('Success') }}",
            'errors': "{{ __('Errors') }}"
        }
    </script>

    {!! Theme::footer() !!}

    @if (! auth('account')->check() || auth('account')->user()->isJobSeeker())
        @php
            $vipPromoCountry = wakanda_selected_country();
            $vipPromoWhatsAppChannelUrl = wakanda_whatsapp_channel_url($vipPromoCountry?->id);
            $vipPromoCountryName = $vipPromoCountry?->name;
        @endphp
        <style>
            .vip-alert-public-promo {
                border-radius: 18px;
                padding: 0 0 1.5rem;
            }

            .vip-alert-public-promo .swal2-html-container {
                margin: 0;
                overflow: visible;
            }

            .vip-alert-public-promo__hero {
                background: linear-gradient(135deg, #101828 0%, #214c3d 100%);
                border-radius: 18px 18px 0 0;
                color: #fff;
                padding: 2rem 1.5rem 1.5rem;
                text-align: center;
            }

            .vip-alert-public-promo__icon {
                align-items: center;
                background: #25d366;
                border-radius: 50%;
                box-shadow: 0 8px 24px rgba(37, 211, 102, .3);
                display: inline-flex;
                height: 64px;
                justify-content: center;
                margin-bottom: 1rem;
                width: 64px;
            }

            .vip-alert-public-promo__body {
                color: #475467;
                padding: 1.25rem 1.5rem 0;
                text-align: left;
            }

            .vip-alert-public-promo__benefit {
                align-items: flex-start;
                display: flex;
                gap: .65rem;
                margin-bottom: .75rem;
            }

            .vip-alert-public-promo__check {
                color: #12b76a;
                font-weight: 700;
            }

            .vip-alert-public-promo__price {
                background: #ecfdf3;
                border: 1px solid #abefc6;
                border-radius: 8px;
                color: #067647;
                font-size: .85rem;
                font-weight: 600;
                margin-top: 1rem;
                padding: .65rem .75rem;
                text-align: center;
            }

            .vip-alert-public-promo__channel {
                border-top: 1px solid #eaecf0;
                margin-top: 1rem;
                padding-top: 1rem;
                text-align: center;
            }

            .vip-alert-public-promo__channel-link {
                align-items: center;
                color: #128c4a;
                display: inline-flex;
                font-size: .9rem;
                font-weight: 600;
                gap: .45rem;
                text-decoration: none;
            }

            .vip-alert-public-promo__channel-link:hover {
                color: #0b6b38;
                text-decoration: underline;
            }
        </style>

        <script>
            (function () {
                'use strict';

                var storageKey = 'wakanda_vip_alert_public_promo_seen_v1';
                var cooldown = 7 * 24 * 60 * 60 * 1000;
                var lastShown = 0;

                try {
                    lastShown = parseInt(localStorage.getItem(storageKey) || '0', 10);
                } catch (e) {
                    return;
                }

                if (Date.now() - lastShown < cooldown) {
                    return;
                }

                function showVipAlertPromo() {
                    if (! window.Swal || document.querySelector('.swal2-container')) {
                        return;
                    }

                    try {
                        localStorage.setItem(storageKey, Date.now().toString());
                    } catch (e) {
                        return;
                    }

                    Swal.fire({
                        width: 480,
                        padding: 0,
                        showCancelButton: true,
                        confirmButtonText: 'Yes, tell me more',
                        cancelButtonText: 'Not now',
                        confirmButtonColor: '#25d366',
                        cancelButtonColor: '#667085',
                        customClass: {
                            popup: 'vip-alert-public-promo'
                        },
                        html: `
                            <div class="vip-alert-public-promo__hero">
                                <span class="vip-alert-public-promo__icon">
                                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path fill="#fff" d="M12.04 2a9.84 9.84 0 0 0-8.43 14.92L2.05 22l5.2-1.52A9.96 9.96 0 1 0 12.04 2Zm0 17.95a8 8 0 0 1-4.08-1.12l-.29-.17-3.08.9.82-3-.19-.3a7.91 7.91 0 1 1 6.82 3.69Zm4.34-5.93c-.24-.12-1.4-.69-1.62-.77-.22-.08-.38-.12-.54.12-.16.24-.61.77-.75.93-.14.16-.28.18-.52.06-.24-.12-1-.37-1.91-1.18a7.17 7.17 0 0 1-1.32-1.64c-.14-.24-.01-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.2-.47-.4-.4-.54-.41h-.46a.88.88 0 0 0-.63.3c-.22.24-.83.81-.83 1.98s.85 2.3.97 2.46c.12.16 1.67 2.55 4.05 3.58.57.24 1.01.39 1.35.5.57.18 1.08.15 1.49.09.45-.07 1.4-.57 1.6-1.12.2-.55.2-1.03.14-1.12-.06-.1-.22-.16-.46-.28Z"/>
                                    </svg>
                                </span>
                                <h2 class="mb-2 text-white">Jobs matched to you, on WhatsApp</h2>
                                <p class="mb-0 text-white opacity-75">Try Wakanda Jobs VIP Alerts and spend less time searching.</p>
                            </div>
                            <div class="vip-alert-public-promo__body">
                                <div class="vip-alert-public-promo__benefit"><span class="vip-alert-public-promo__check">✓</span><span>Personalised jobs based on your skills and preferred roles</span></div>
                                <div class="vip-alert-public-promo__benefit"><span class="vip-alert-public-promo__check">✓</span><span>New matching opportunities delivered directly to WhatsApp</span></div>
                                <div class="vip-alert-public-promo__benefit"><span class="vip-alert-public-promo__check">✓</span><span>Choose a plan that works for your job search</span></div>
                                @if($footerVipStartingText)
                                    <div class="vip-alert-public-promo__price">VIP plans start from {{ $footerVipStartingText }}</div>
                                @endif
                                @if($vipPromoWhatsAppChannelUrl)
                                    <div class="vip-alert-public-promo__channel">
                                        <a class="vip-alert-public-promo__channel-link"
                                           href="{{ $vipPromoWhatsAppChannelUrl }}"
                                           target="_blank"
                                           rel="noopener">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="#25d366" aria-hidden="true">
                                                <path d="M12.04 2a9.84 9.84 0 0 0-8.43 14.92L2.05 22l5.2-1.52A9.96 9.96 0 1 0 12.04 2Zm0 17.95a8 8 0 0 1-4.08-1.12l-.29-.17-3.08.9.82-3-.19-.3a7.91 7.91 0 1 1 6.82 3.69Zm4.34-5.93c-.24-.12-1.4-.69-1.62-.77-.22-.08-.38-.12-.54.12-.16.24-.61.77-.75.93-.14.16-.28.18-.52.06-.24-.12-1-.37-1.91-1.18a7.17 7.17 0 0 1-1.32-1.64c-.14-.24-.01-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.2-.47-.4-.4-.54-.41h-.46a.88.88 0 0 0-.63.3c-.22.24-.83.81-.83 1.98s.85 2.3.97 2.46c.12.16 1.67 2.55 4.05 3.58.57.24 1.01.39 1.35.5.57.18 1.08.15 1.49.09.45-.07 1.4-.57 1.6-1.12.2-.55.2-1.03.14-1.12-.06-.1-.22-.16-.46-.28Z"/>
                                            </svg>
                                            Follow Wakanda Jobs {{ $vipPromoCountryName }} channel free
                                        </a>
                                    </div>
                                @endif
                            </div>
                        `
                    }).then(function (result) {
                        if (! result.isConfirmed) {
                            return;
                        }

                        window.location.href = @json('https://wa.me/260970766123?text=' . rawurlencode('Hi Wakanda Jobs, I would like more information about VIP personalised job alerts.'));
                    });
                }

                function loadSweetAlert() {
                    if (window.Swal) {
                        showVipAlertPromo();
                        return;
                    }

                    var stylesheet = document.createElement('link');
                    stylesheet.rel = 'stylesheet';
                    stylesheet.href = '{{ asset('vendor/core/core/base/libraries/sweetalert2/sweetalert2.min.css') }}';
                    document.head.appendChild(stylesheet);

                    var script = document.createElement('script');
                    script.src = '{{ asset('vendor/core/core/base/libraries/sweetalert2/sweetalert2.min.js') }}';
                    script.onload = showVipAlertPromo;
                    document.body.appendChild(script);
                }

                window.setTimeout(loadSweetAlert, 1800);
            })();
        </script>
    @endif

    {{-- Web Push Notifications --}}
    <script>
    (function () {
        'use strict';

        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            return;
        }

        var VAPID_PUBLIC_KEY = '{{ config('services.vapid.public_key') }}';
        var COUNTRY_ID = {{ (function_exists('wakanda_selected_country') && ($c = wakanda_selected_country())) ? (int)$c->id : 'null' }};

        function urlBase64ToUint8Array(base64String) {
            var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
            var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            var rawData = atob(base64);
            var outputArray = new Uint8Array(rawData.length);
            for (var i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        function getCsrfToken() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        }

        function sendSubscriptionToServer(subscription, action) {
            var keys = subscription.toJSON().keys || {};
            var payload = {
                endpoint: subscription.endpoint,
                p256dh: keys.p256dh || '',
                auth: keys.auth || ''
            };
            if (action === 'subscribe' && COUNTRY_ID) {
                payload.country_id = COUNTRY_ID;
            }
            return fetch('/push/' + action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify(payload)
            });
        }

        navigator.serviceWorker.register('/sw.js').then(function (registration) {
            // Check current permission state
            if (Notification.permission === 'granted') {
                registration.pushManager.getSubscription().then(function (sub) {
                    if (!sub) {
                        subscribeUser(registration);
                    } else {
                        sendSubscriptionToServer(sub, 'subscribe').catch(function () {});
                    }
                });
                return;
            }

            if (Notification.permission === 'denied') {
                return;
            }

            // Show a non-intrusive banner after 2 seconds
            setTimeout(function () {
                showNotificationBanner(registration);
            }, 2000);
        });

        function subscribeUser(registration) {
            registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
            }).then(function (subscription) {
                sendSubscriptionToServer(subscription, 'subscribe').catch(function () {});
            }).catch(function () {});
        }

        function showNotificationBanner(registration) {
            if (document.getElementById('push-notification-banner') || document.querySelector('.swal2-container')) return;

            var banner = document.createElement('div');
            banner.id = 'push-notification-banner';
            banner.style.cssText = [
                'position:fixed',
                'top:20px',
                'left:50%',
                'transform:translateX(-50%)',
                'background:#fff',
                'border:1px solid #e0e0e0',
                'border-radius:14px',
                'padding:20px 26px',
                'box-shadow:0 8px 30px rgba(0,0,0,0.2)',
                'z-index:99999',
                'display:flex',
                'align-items:center',
                'gap:18px',
                'max-width:560px',
                'width:92%',
                'font-family:inherit'
            ].join(';');

            banner.innerHTML = [
                '<img src="/push-icon.png" style="width:52px;height:52px;border-radius:10px;object-fit:contain;flex-shrink:0" alt="">',
                '<div style="flex:1">',
                    '<strong style="display:block;font-size:18px;color:#111">Get job alerts instantly</strong>',
                    '<span style="font-size:15px;color:#666">Be the first to know when new jobs are posted</span>',
                '</div>',
                '<button id="push-allow-btn" style="background:#3c65f5;color:#fff;border:none;border-radius:8px;padding:11px 22px;font-size:16px;font-weight:600;cursor:pointer;white-space:nowrap">Allow</button>',
                '<button id="push-dismiss-btn" style="background:none;border:none;font-size:24px;color:#999;cursor:pointer;padding:0 6px">&times;</button>'
            ].join('');

            document.body.appendChild(banner);

            document.getElementById('push-allow-btn').addEventListener('click', function () {
                banner.remove();
                Notification.requestPermission().then(function (permission) {
                    if (permission === 'granted') {
                        subscribeUser(registration);
                    }
                });
            });

            document.getElementById('push-dismiss-btn').addEventListener('click', function () {
                banner.remove();
                try { localStorage.setItem('push_banner_dismissed', '1'); } catch(e) {}
            });

            try {
                if (localStorage.getItem('push_banner_dismissed') === '1') {
                    banner.remove();
                }
            } catch(e) {}
        }
    })();
    </script>

    @if (session()->has('status') || session()->has('success_msg') || session()->has('error_msg') || (isset($errors) && $errors->count() > 0) || isset($error_msg))
        <script type="text/javascript">
            'use strict';
            window.onload = function () {
                @if (session()->has('success_msg'))
                window.showAlert('alert-success', "{{ __('Success') }}", "{!! addslashes(session('success_msg')) !!}");
                @endif
                @if (session()->has('status'))
                window.showAlert('alert-success', "{{ __('Success') }}", "{!! addslashes(session('status')) !!}");
                @endif
                @if (session()->has('error_msg'))
                window.showAlert('alert-danger', "{{ __('Errors') }}", "{!! addslashes(session('error_msg')) !!}");
                @endif
                @if (isset($error_msg))
                window.showAlert('alert-danger', "{{ __('Errors') }}", "{!! addslashes($error_msg) !!}");
                @endif
                @if (isset($errors))
                @foreach ($errors->all() as $error)
                window.showAlert('alert-danger', "{{ __('Errors') }}", "{!! addslashes($error) !!}");
                @endforeach
                @endif
            };
        </script>
    @endif

    @if(theme_option('scroll_to_top', 'yes') === 'yes')
        <script>
            'use strict';
            $(function() {
                $.scrollUp({
                    scrollText: '<i class="fi-rr-arrow-small-up"></i>',
                    easingType: "linear",
                    scrollSpeed: 900,
                    animation: "fade"
                })
            });
        </script>
    @endif
</body>
</html>
