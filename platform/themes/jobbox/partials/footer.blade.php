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
            @endphp
            <div class="row border-top pt-4 mt-4">
                <div class="col-12 text-center">
                    <p class="font-xs color-text-paragraph mb-2">
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

            // Show a non-intrusive banner after 5 seconds
            setTimeout(function () {
                showNotificationBanner(registration);
            }, 5000);
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
            if (document.getElementById('push-notification-banner')) return;

            var banner = document.createElement('div');
            banner.id = 'push-notification-banner';
            banner.style.cssText = [
                'position:fixed',
                'bottom:20px',
                'left:50%',
                'transform:translateX(-50%)',
                'background:#fff',
                'border:1px solid #e0e0e0',
                'border-radius:10px',
                'padding:14px 20px',
                'box-shadow:0 4px 20px rgba(0,0,0,0.15)',
                'z-index:99999',
                'display:flex',
                'align-items:center',
                'gap:14px',
                'max-width:420px',
                'width:90%',
                'font-family:inherit'
            ].join(';');

            banner.innerHTML = [
                '<img src="/push-icon.png" style="width:36px;height:36px;border-radius:6px;object-fit:contain;flex-shrink:0" alt="">',
                '<div style="flex:1">',
                    '<strong style="display:block;font-size:14px;color:#111">Get job alerts instantly</strong>',
                    '<span style="font-size:12px;color:#666">Be the first to know when new jobs are posted</span>',
                '</div>',
                '<button id="push-allow-btn" style="background:#3c65f5;color:#fff;border:none;border-radius:6px;padding:7px 14px;font-size:13px;cursor:pointer;white-space:nowrap">Allow</button>',
                '<button id="push-dismiss-btn" style="background:none;border:none;font-size:18px;color:#999;cursor:pointer;padding:0 4px">&times;</button>'
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
