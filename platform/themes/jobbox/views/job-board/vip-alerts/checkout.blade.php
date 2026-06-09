<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-10 col-12">

                {{-- Back link --}}
                <a href="{{ route('public.vip-alerts.plans') }}" class="d-inline-flex align-items-center gap-1 text-muted font-sm mb-4">
                    <i class="fi-rr-arrow-left"></i> View all plans
                </a>

                {{-- Plan summary --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle"
                                 style="width:52px;height:52px;background:linear-gradient(135deg,#25d366,#128c4a);">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path fill="#fff" d="M12.04 2a9.84 9.84 0 0 0-8.43 14.92L2.05 22l5.2-1.52A9.96 9.96 0 1 0 12.04 2Zm4.34 13.02c-.24-.12-1.4-.69-1.62-.77-.22-.08-.38-.12-.54.12-.16.24-.61.77-.75.93-.14.16-.28.18-.52.06-.24-.12-1-.37-1.91-1.18a7.17 7.17 0 0 1-1.32-1.64c-.14-.24-.01-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.2-.47-.4-.4-.54-.41h-.46a.88.88 0 0 0-.63.3c-.22.24-.83.81-.83 1.98s.85 2.3.97 2.46c.12.16 1.67 2.55 4.05 3.58.57.24 1.01.39 1.35.5.57.18 1.08.15 1.49.09.45-.07 1.4-.57 1.6-1.12.2-.55.2-1.03.14-1.12-.06-.1-.22-.16-.46-.28Z"/>
                                </svg>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-0 fw-bold">VIP WhatsApp Job Alerts</h5>
                                <p class="mb-0 text-muted font-sm">{{ $planData['label'] }} plan</p>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fs-3 fw-bold text-success">{{ $planData['currency'] }} {{ number_format($planData['price'], 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 1 form: collect details --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-1">Step 1 of 2 — Your Details</h6>
                        <p class="text-muted font-sm mb-4">We'll set up your alerts to the WhatsApp number below.</p>

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('public.vip-alerts.prepare-checkout', $plan) }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label fw-medium">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="candidate_name" class="form-control @error('candidate_name') is-invalid @enderror"
                                    value="{{ old('candidate_name') }}" required maxlength="100"
                                    placeholder="e.g. Thabo Mokoena">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">WhatsApp Number <span class="text-danger">*</span></label>
                                <input type="tel" name="candidate_phone" class="form-control @error('candidate_phone') is-invalid @enderror"
                                    value="{{ old('candidate_phone') }}" required maxlength="30"
                                    placeholder="+260 97x xxx xxx (include country code)">
                                <div class="form-text">Job alerts will be sent to this number on WhatsApp.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="candidate_email" class="form-control @error('candidate_email') is-invalid @enderror"
                                    value="{{ old('candidate_email') }}" required maxlength="150"
                                    placeholder="you@example.com">
                                <div class="form-text">Confirmation will be sent here once your subscription is activated.</div>
                            </div>

                            {{-- Optional filters --}}
                            <details class="mb-4">
                                <summary class="fw-medium cursor-pointer text-primary mb-3" style="cursor:pointer;">
                                    <i class="fi-rr-filter me-1"></i> Optional: Add job preferences (recommended)
                                </summary>
                                <div class="ps-3 mt-3">
                                    <div class="mb-3">
                                        <label class="form-label">Keywords (e.g. "Accountant", "Nurse", "Driver")</label>
                                        <input type="text" id="vip-keywords-input" class="form-control"
                                            placeholder="Type a keyword and press Enter">
                                        <div id="vip-keywords-tags" class="d-flex flex-wrap gap-2 mt-2"></div>
                                        <div id="vip-keywords-hidden"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Country</label>
                                        <select name="filters[country_ids][]" class="form-select">
                                            <option value="">Any country</option>
                                            @foreach(\Illuminate\Support\Facades\DB::table('countries')->where('status', 'published')->orderBy('name')->get() as $country)
                                                <option value="{{ $country->id }}" @selected(old('filters.country_ids.0') == $country->id)>{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </details>

                            <div class="form-check mb-4">
                                <input class="form-check-input @error('whatsapp_consent') is-invalid @enderror"
                                    type="checkbox" name="whatsapp_consent" value="1"
                                    id="whatsapp-consent" required @checked(old('whatsapp_consent'))>
                                <label class="form-check-label" for="whatsapp-consent">
                                    I agree to receive one personalised Wakanda Jobs WhatsApp digest per day
                                    for the duration of this plan.
                                </label>
                                @error('whatsapp_consent')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-success w-100 btn-apply-big">
                                Continue to Payment <i class="fi-rr-arrow-right ms-1"></i>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<script>
(function () {
    'use strict';
    var input   = document.getElementById('vip-keywords-input');
    var tags    = document.getElementById('vip-keywords-tags');
    var hidden  = document.getElementById('vip-keywords-hidden');
    var keywords = [];

    if (!input) return;

    function renderTags() {
        tags.innerHTML = '';
        hidden.innerHTML = '';
        keywords.forEach(function (kw, i) {
            var badge = document.createElement('span');
            badge.className = 'badge bg-light text-dark border d-inline-flex align-items-center gap-1 px-2 py-1';
            badge.style.fontSize = '.8rem';
            badge.innerHTML = kw + ' <button type="button" class="btn-close btn-close-sm ms-1" style="font-size:.6rem;" aria-label="Remove"></button>';
            badge.querySelector('button').addEventListener('click', function () {
                keywords.splice(i, 1);
                renderTags();
            });
            tags.appendChild(badge);

            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'filters[keywords][]';
            inp.value = kw;
            hidden.appendChild(inp);
        });
    }

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            var val = input.value.trim().replace(/,$/, '');
            if (val && !keywords.includes(val)) {
                keywords.push(val);
                renderTags();
            }
            input.value = '';
        }
    });
})();
</script>
