<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 text-center">

                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4"
                     style="width:76px;height:76px;background:linear-gradient(135deg,#003087,#009cde);box-shadow:0 12px 24px -8px rgba(0,48,135,.35);">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="#fff"/>
                    </svg>
                </div>

                <h1 class="section-title mb-3">Support Wakanda Jobs</h1>

                <p class="font-md color-text-paragraph-2 mb-4">
                    Wakanda Jobs is a free platform connecting job seekers with opportunities across Africa.
                    If we've helped you find work or talent, consider making a small donation to keep us running and growing.
                </p>

                <div class="row justify-content-center g-3 mb-5">
                    @foreach([
                        ['icon' => 'fi-rr-shield-check', 'text' => 'Keep the platform free for job seekers'],
                        ['icon' => 'fi-rr-rocket',       'text' => 'Fund new features and improvements'],
                        ['icon' => 'fi-rr-world',        'text' => 'Expand to more African countries'],
                    ] as $item)
                        <div class="col-12 col-sm-4">
                            <div class="p-3 rounded-3 border h-100 d-flex flex-column align-items-center gap-2 text-center"
                                 style="border-color:#e0e6f6!important;background:#fff;">
                                <i class="{{ $item['icon'] }} fs-4" style="color:#003087;"></i>
                                <span class="font-xs color-text-paragraph-2">{{ $item['text'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="box-shadow-bdrd-15 p-4 p-md-5 bg-white">
                    <p class="font-sm color-text-paragraph-2 mb-4">
                        Payments are processed securely by PayPal. You do not need a PayPal account — card payments are accepted.
                    </p>

                    <div id="paypal-container-BMNE47BXH3PWG" style="width:100%;"></div>
                </div>

                <p class="mt-4 font-xs color-text-paragraph-2">
                    Have questions? <a href="mailto:{{ setting('admin_email') ?: config('mail.from.address') }}">Contact us</a>
                </p>

            </div>
        </div>
    </div>
</section>

<script src="https://www.paypal.com/sdk/js?client-id=BAAclcYj6pjy7HlvKnW3yFaDQPFrw8gnrkgzWVVG3eYgo75mDvysqttByn4njpn0zpz0YvVv96gglvtw7c&components=hosted-buttons&disable-funding=venmo&currency=USD"></script>
<script>
    paypal.HostedButtons({
        hostedButtonId: "BMNE47BXH3PWG",
    }).render("#paypal-container-BMNE47BXH3PWG");
</script>
