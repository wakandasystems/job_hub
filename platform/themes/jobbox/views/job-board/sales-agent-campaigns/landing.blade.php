@php
    Theme::layout('default');
@endphp

<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="box-border-single px-4 py-5 rounded-4">
                    <div class="text-center mb-4">
                        <span class="btn btn-tag mb-3">{{ $campaign->resolvedProductLabel() }}</span>
                        <h1 class="mb-2">{{ $campaign->resolvedLandingHeadline() }}</h1>
                        <p class="text-muted mb-0">Shared by {{ $agent->name }} from Wakanda Jobs.</p>
                    </div>

                    @if (!empty($marketingImage) && $marketingImage->imageUrl())
                        <div class="mb-4 text-center">
                            <img src="{{ $marketingImage->imageUrl() }}" alt="{{ $campaign->name }}" class="rounded-3 img-fluid" style="width:100%;opacity:0.92;">
                        </div>
                    @endif

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100 text-center">
                                <div class="text-muted small">Promo Price</div>
                                <div class="fw-bold fs-4">{{ $campaign->promo_price ?: 'Contact us' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100 text-center">
                                <div class="text-muted small">Usual Price</div>
                                <div class="fw-bold fs-5 text-decoration-line-through">{{ $campaign->promo_original_price ?: '—' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100 text-center">
                                <div class="text-muted small">Offer Ends</div>
                                <div class="fw-bold fs-5">{{ $campaign->promo_end_date?->format('d M Y') ?: 'Limited offer' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="mb-2">Request onboarding</h5>
                        <p class="text-muted mb-0">{{ $campaign->resolvedLandingBody() }}</p>
                    </div>

                    <form method="POST" action="{{ route('public.sales-agent-campaigns.store', [$agent->code, $campaign->getKey()]) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="candidate_name" class="form-control" value="{{ old('candidate_name') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone / WhatsApp</label>
                                @php
                                    $oldPhone = old('candidate_phone', '');
                                    // Detect if old value already has a prefix so we can split it back out
                                    $oldPrefix = '+260';
                                    $oldDigits = $oldPhone;
                                    foreach (['+260','+27','+263','+265','+255','+254','+256','+258','+267','+264','+234','+233','+251','+243','+44','+1','+61'] as $p) {
                                        if (str_starts_with($oldPhone, $p)) { $oldPrefix = $p; $oldDigits = substr($oldPhone, strlen($p)); break; }
                                    }
                                @endphp
                                <div class="input-group">
                                    <select name="candidate_phone_prefix" class="form-select" style="max-width:130px" id="phonePrefix">
                                        @foreach (['+260'=>'🇿🇲 +260','+27'=>'🇿🇦 +27','+263'=>'🇿🇼 +263','+265'=>'🇲🇼 +265','+255'=>'🇹🇿 +255','+254'=>'🇰🇪 +254','+256'=>'🇺🇬 +256','+258'=>'🇲🇿 +258','+267'=>'🇧🇼 +267','+264'=>'🇳🇦 +264','+234'=>'🇳🇬 +234','+233'=>'🇬🇭 +233','+251'=>'🇪🇹 +251','+243'=>'🇨🇩 +243','+44'=>'🇬🇧 +44','+1'=>'🇺🇸 +1','+61'=>'🇦🇺 +61'] as $code => $label)
                                            <option value="{{ $code }}" {{ $oldPrefix === $code ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input type="tel" name="candidate_phone_digits" id="phoneDigits"
                                           class="form-control @error('candidate_phone') is-invalid @enderror"
                                           value="{{ $oldDigits }}"
                                           placeholder="970766123"
                                           inputmode="numeric"
                                           pattern="[0-9]+"
                                           required>
                                    {{-- Hidden field combines prefix + digits before submit --}}
                                    <input type="hidden" name="candidate_phone" id="candidatePhoneFull" value="{{ $oldPhone }}">
                                </div>
                                @error('candidate_phone')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Digits only — no leading zero needed.</div>
                                <script>
                                    (function () {
                                        var prefix = document.getElementById('phonePrefix');
                                        var digits = document.getElementById('phoneDigits');
                                        var full   = document.getElementById('candidatePhoneFull');
                                        function sync() {
                                            var d = digits.value.replace(/\D/g, '').replace(/^0+/, '');
                                            full.value = prefix.value + d;
                                        }
                                        prefix.addEventListener('change', sync);
                                        digits.addEventListener('input', function () {
                                            this.value = this.value.replace(/\D/g, '');
                                            sync();
                                        });
                                        sync();
                                    })();
                                </script>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email (optional)</label>
                                <input type="email" name="candidate_email" class="form-control" value="{{ old('candidate_email') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes (optional)</label>
                                <textarea name="customer_notes" class="form-control" rows="4" placeholder="Anything we should know before onboarding you?">{{ old('customer_notes') }}</textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="confirm_campaign" id="confirm_campaign" value="1" {{ old('confirm_campaign') ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="confirm_campaign">
                                        I confirm that I want to activate <strong>{{ $campaign->resolvedProductLabel() }}</strong> under the <strong>{{ $campaign->name }}</strong> offer shared by {{ $agent->name }}.
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-default btn-brand icon-hover">{{ $campaign->resolvedLandingCtaText() }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
