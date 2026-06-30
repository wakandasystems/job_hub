@php
    $campaign = $campaign ?? new \Botble\JobBoard\Models\SalesAgentCampaign();
    $promptPlaceholders = \Botble\JobBoard\Models\SalesAgentCampaign::promptPlaceholderDescriptions();
    $sharePlaceholders = \Botble\JobBoard\Models\SalesAgentCampaign::sharePlaceholderDescriptions();
    $currentInspirationImage = old('current_inspiration_image', $campaign->inspirationImages()[0] ?? null);
    $currentReconstructionLayout = old(
        'reconstruction_layout',
        $campaign->reconstruction_layout ? json_encode($campaign->reconstruction_layout, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : ''
    );
    $inspirationAnalyzeUrl = \Illuminate\Support\Facades\Route::has('sales-agent-campaigns.analyze-inspiration')
        ? route('sales-agent-campaigns.analyze-inspiration')
        : '';
    $recommendedPromptTemplate = implode("\n", [
        'Create a Wakanda Jobs poster using the inspiration image as the layout master.',
        '{text_layout_brief}',
        'Main headline text: {landing_headline}',
        'Supporting copy: {landing_body}',
        'Price line: {price_line}',
        'Promo deadline: {promo_deadline_line}',
        'CTA text: {cta}',
        'Product label: {product_label}',
        'Brand placement: {logo_zone}',
        'Keep the design style, typography mood, spacing, and badge treatment from the inspiration poster while replacing all text with this campaign content.',
    ]);
@endphp

<div class="row">
    <div class="col-md-8">
        <x-core::card class="mb-3">
            <x-core::card.body>
                <x-core::form.text-input
                    label="Campaign Name"
                    name="name"
                    placeholder="e.g. Christmas 2026 Promo"
                    :value="old('name', $campaign->name)"
                />

                <x-core::form.select
                    label="Product"
                    name="product_type"
                    :options="\Botble\JobBoard\Models\SalesAgentCampaign::productTypeOptions()"
                    :value="old('product_type', $campaign->product_type ?: 'auto_apply')"
                />

                <x-core::form.text-input
                    label="Public Product Label"
                    name="product_label"
                    placeholder="e.g. Auto Application"
                    :value="old('product_label', $campaign->product_label)"
                    helper-text="Optional. Leave blank to use the standard product name."
                />

                <x-core::form.text-input
                    label="Landing Headline"
                    name="landing_headline"
                    placeholder="e.g. Get Auto Apply for K100"
                    :value="old('landing_headline', $campaign->landing_headline)"
                />

                <x-core::form.textarea
                    label="Landing Copy"
                    name="landing_body"
                    rows="5"
                    :value="old('landing_body', $campaign->landing_body)"
                    helper-text="Shown on the public landing page above the form."
                />

                <x-core::form.text-input
                    label="CTA Button Text"
                    name="landing_cta_text"
                    placeholder="e.g. Request Activation"
                    :value="old('landing_cta_text', $campaign->landing_cta_text)"
                />

                <x-core::form.textarea
                    label="Agent Share Message Template"
                    name="share_message_template"
                    rows="6"
                    :value="old('share_message_template', $campaign->share_message_template)"
                    helper-text="Optional. Use the system placeholders listed on the right."
                />

                <x-core::form.textarea
                    label="Image Prompt Template"
                    name="prompt_template"
                    rows="18"
                    :value="old('prompt_template', $campaign->prompt_template)"
                    helper-text="Use the system placeholders listed on the right. The human subject comes from the selected generation mode: Nakia, Agent, or Both. For inspiration-led posters, use the layout placeholders to tell the model where headline, body, price, CTA, and logo should sit."
                />

                <x-core::form.textarea
                    label="Reconstruction Layout JSON"
                    name="reconstruction_layout"
                    rows="12"
                    :value="$currentReconstructionLayout"
                    helper-text="Normalized 0..1000 editable boxes used by strict reconstruction mode. This is auto-filled from inspiration analysis, but you can manually tune it for exact poster matching."
                />
            </x-core::card.body>
        </x-core::card>

        <x-core::card>
            <x-core::card.header>
                <x-core::card.title>Design Inspiration</x-core::card.title>
            </x-core::card.header>
            <x-core::card.body>
                <p class="text-muted small mb-3">
                    Upload one full-quality poster reference the AI should study for layout, style, hierarchy, and creative direction.
                    If the reference includes a person, generation will still use the selected Wakanda subject mode: Nakia, Agent, or Both.
                </p>

                <div class="mb-3">
                    <label for="inspiration_image_file" class="form-label">Inspiration Poster</label>
                    <input
                        type="file"
                        class="form-control"
                        id="inspiration_image_file"
                        name="inspiration_image_file"
                        accept="image/jpeg,image/png,image/webp"
                        data-analyze-url="{{ $inspirationAnalyzeUrl }}"
                    >
                    <div class="form-text">This uses a direct upload path and stores one original reference image for the campaign.</div>
                </div>

                <div class="border rounded p-3 bg-light d-none mb-3" id="inspirationImageAnalysisPanel">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                        <div class="fw-semibold">Inspiration Analysis</div>
                        <div class="small text-muted" id="inspirationImageAnalysisStage">Waiting for image</div>
                    </div>
                    <div class="progress mb-2" style="height: 10px;">
                        <div
                            class="progress-bar progress-bar-striped progress-bar-animated"
                            id="inspirationImageAnalysisProgress"
                            role="progressbar"
                            style="width: 0%;"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        ></div>
                    </div>
                    <div class="small text-muted mb-3" id="inspirationImageAnalysisMessage">Select an image to preview and analyze it.</div>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="small text-muted mb-2">Selected image preview</div>
                            <div class="border rounded bg-white p-2 text-center">
                                <img
                                    id="inspirationImageLivePreview"
                                    src="{{ $currentInspirationImage ? RvMedia::getImageUrl($currentInspirationImage) : RvMedia::getDefaultImage() }}"
                                    alt="Inspiration preview"
                                    class="rounded"
                                    style="max-width:100%;max-height:320px;object-fit:contain;"
                                >
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="small text-muted mb-2">Detected poster brief</div>
                            <div class="border rounded bg-white p-3 small" id="inspirationImageAnalysisSummary">
                                No analysis yet.
                            </div>
                        </div>
                    </div>
                </div>

                @if ($currentInspirationImage)
                    <div class="border rounded p-3 bg-light mb-3">
                        <div class="small text-muted mb-2">Current inspiration image</div>
                        <img
                            src="{{ RvMedia::getImageUrl($currentInspirationImage) }}"
                            alt="Campaign inspiration"
                            class="rounded border"
                            style="max-width:100%;max-height:320px;object-fit:contain;background:#fff;"
                        >
                    </div>

                    <x-core::form.checkbox
                        label="Remove current inspiration image"
                        name="remove_inspiration_image"
                        value="1"
                        :checked="old('remove_inspiration_image', false)"
                    />
                @endif
            </x-core::card.body>
        </x-core::card>
    </div>

    <div class="col-md-4">
        <x-core::card class="mb-3">
            <x-core::card.body>
                <x-core::form.select
                    label="Format"
                    name="aspect_ratio"
                    :options="[
                        'portrait_4_5' => 'Portrait 4:5 (Facebook/Instagram feed)',
                        'square_1_1' => 'Square 1:1 (WhatsApp/general)',
                        'landscape_16_9' => 'Landscape 16:9 (banner/header)',
                    ]"
                    :value="old('aspect_ratio', $campaign->aspect_ratio ?: 'portrait_4_5')"
                />

                <x-core::form.text-input
                    label="Display Price"
                    name="promo_price"
                    placeholder="e.g. K100"
                    :value="old('promo_price', $campaign->promo_price)"
                    helper-text="Saved even for non-promo posters."
                />

                <x-core::form.text-input
                    label="Original Price (crossed out)"
                    name="promo_original_price"
                    placeholder="e.g. K250"
                    :value="old('promo_original_price', $campaign->promo_original_price)"
                    helper-text="Leave blank if this poster is not a promo."
                />

                <x-core::form-group>
                    <x-core::form.label label="Promo Ends" for="promo_end_date" />
                    {!! Form::datePicker('promo_end_date', old('promo_end_date', $campaign->promo_end_date?->format(BaseHelper::getDateFormat())), [
                        'id' => 'promo_end_date',
                    ]) !!}
                    <div class="form-text">Leave empty if there is no promo deadline.</div>
                </x-core::form-group>

                <x-core::form.checkbox
                    label="Active"
                    name="is_active"
                    value="1"
                    :checked="old('is_active', $campaign->exists ? $campaign->is_active : true)"
                />
            </x-core::card.body>
        </x-core::card>

        <x-core::card class="mb-3">
            <x-core::card.header>
                <x-core::card.title>Promo Rules</x-core::card.title>
            </x-core::card.header>
            <x-core::card.body>
                <p class="small text-muted mb-2">A poster counts as a promo only when all three are filled:</p>
                <ul class="small text-muted ps-3 mb-0">
                    <li>Display price</li>
                    <li>Original crossed-out price</li>
                    <li>Promo end date</li>
                </ul>
                <p class="small text-muted mt-3 mb-0">If original price or promo end is empty, the poster is treated as a normal non-promo poster and deadline placeholders stay blank.</p>
            </x-core::card.body>
        </x-core::card>

        <x-core::card class="mb-3">
            <x-core::card.header>
                <x-core::card.title>System Placeholders</x-core::card.title>
            </x-core::card.header>
            <x-core::card.body>
                <div class="small fw-semibold mb-2">Recommended prompt skeleton</div>
                <textarea class="form-control font-monospace small mb-3" rows="10" readonly>{{ $recommendedPromptTemplate }}</textarea>

                <div class="small fw-semibold mb-2">Prompt template</div>
                <div class="small text-muted mb-3">
                    Use either <code>{placeholder}</code> or <code>@{{placeholder}}</code>.
                </div>
                <div class="small mb-3">
                    @foreach ($promptPlaceholders as $token => $description)
                        <div class="mb-2"><code>{{ '{' . $token . '}' }}</code> {{ $description }}</div>
                    @endforeach
                </div>

                <div class="small fw-semibold mb-2">Share message template</div>
                <div class="small">
                    @foreach ($sharePlaceholders as $token => $description)
                        <div class="mb-2"><code>{{ '{' . $token . '}' }}</code> {{ $description }}</div>
                    @endforeach
                </div>
            </x-core::card.body>
        </x-core::card>

        <x-core::card>
            <x-core::card.body>
                <x-core::button type="submit" color="primary">Save</x-core::button>
            </x-core::card.body>
        </x-core::card>
    </div>
</div>

@push('footer')
    <script>
        (function () {
            var fileInput = document.getElementById('inspiration_image_file');
            var analysisPanel = document.getElementById('inspirationImageAnalysisPanel');
            var progressBar = document.getElementById('inspirationImageAnalysisProgress');
            var stage = document.getElementById('inspirationImageAnalysisStage');
            var message = document.getElementById('inspirationImageAnalysisMessage');
            var livePreview = document.getElementById('inspirationImageLivePreview');
            var summary = document.getElementById('inspirationImageAnalysisSummary');
            var promptField = document.querySelector('[name="prompt_template"]');
            var reconstructionLayoutField = document.querySelector('[name="reconstruction_layout"]');
            var audioContext = null;

            if (!fileInput || !analysisPanel || !progressBar || !stage || !message || !livePreview || !summary || !promptField || !reconstructionLayoutField) {
                return;
            }

            function getAudioContext() {
                var AudioContextClass = window.AudioContext || window.webkitAudioContext;

                if (!AudioContextClass) {
                    return null;
                }

                if (!audioContext) {
                    audioContext = new AudioContextClass();
                }

                if (audioContext.state === 'suspended' && typeof audioContext.resume === 'function') {
                    audioContext.resume().catch(function () {});
                }

                return audioContext;
            }

            function playToneSequence(sequence) {
                var context = getAudioContext();

                if (!context || !Array.isArray(sequence) || !sequence.length) {
                    return;
                }

                var startAt = context.currentTime + 0.02;

                sequence.forEach(function (tone, index) {
                    var oscillator = context.createOscillator();
                    var gainNode = context.createGain();
                    var toneStart = startAt + index * ((tone.duration || 0.12) + 0.03);
                    var toneDuration = tone.duration || 0.12;

                    oscillator.type = tone.type || 'sine';
                    oscillator.frequency.setValueAtTime(tone.frequency || 660, toneStart);

                    gainNode.gain.setValueAtTime(0.0001, toneStart);
                    gainNode.gain.exponentialRampToValueAtTime(tone.volume || 0.05, toneStart + 0.01);
                    gainNode.gain.exponentialRampToValueAtTime(0.0001, toneStart + toneDuration);

                    oscillator.connect(gainNode);
                    gainNode.connect(context.destination);
                    oscillator.start(toneStart);
                    oscillator.stop(toneStart + toneDuration + 0.02);
                });
            }

            function playAnalysisStartedSound() {
                playToneSequence([
                    { frequency: 520, duration: 0.08, volume: 0.03, type: 'triangle' },
                    { frequency: 660, duration: 0.1, volume: 0.04, type: 'triangle' }
                ]);
            }

            function playAnalysisCompleteSound() {
                playToneSequence([
                    { frequency: 660, duration: 0.08, volume: 0.04, type: 'sine' },
                    { frequency: 880, duration: 0.1, volume: 0.05, type: 'sine' },
                    { frequency: 1100, duration: 0.14, volume: 0.05, type: 'sine' }
                ]);
            }

            function escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function setProgress(percent, label, detail) {
                analysisPanel.classList.remove('d-none');
                progressBar.style.width = percent + '%';
                progressBar.setAttribute('aria-valuenow', String(percent));
                stage.textContent = label;
                message.textContent = detail;
            }

            function renderSummary(data) {
                var zones = Array.isArray(data.text_zones) ? data.text_zones : [];
                var elements = Array.isArray(data.key_elements) ? data.key_elements : [];

                summary.innerHTML = [
                    data.layout ? '<div class="mb-2"><strong>Layout:</strong> ' + escapeHtml(data.layout) + '</div>' : '',
                    data.style ? '<div class="mb-2"><strong>Style:</strong> ' + escapeHtml(data.style) + '</div>' : '',
                    data.colors ? '<div class="mb-2"><strong>Colors:</strong> ' + escapeHtml(data.colors) + '</div>' : '',
                    data.typography ? '<div class="mb-2"><strong>Typography:</strong> ' + escapeHtml(data.typography) + '</div>' : '',
                    data.background_treatment ? '<div class="mb-2"><strong>Background:</strong> ' + escapeHtml(data.background_treatment) + '</div>' : '',
                    data.offer_treatment ? '<div class="mb-2"><strong>Offer treatment:</strong> ' + escapeHtml(data.offer_treatment) + '</div>' : '',
                    data.spacing_rhythm ? '<div class="mb-2"><strong>Spacing rhythm:</strong> ' + escapeHtml(data.spacing_rhythm) + '</div>' : '',
                    data.image_crop_style ? '<div class="mb-2"><strong>Image crop:</strong> ' + escapeHtml(data.image_crop_style) + '</div>' : '',
                    zones.length ? '<div class="mb-2"><strong>Text zones:</strong> ' + escapeHtml(zones.join(' | ')) + '</div>' : '',
                    data.cta_zone ? '<div class="mb-2"><strong>CTA zone:</strong> ' + escapeHtml(data.cta_zone) + '</div>' : '',
                    data.logo_zone ? '<div class="mb-2"><strong>Logo zone:</strong> ' + escapeHtml(data.logo_zone) + '</div>' : '',
                    elements.length ? '<div><strong>Key elements:</strong> ' + escapeHtml(elements.join(' | ')) + '</div>' : ''
                ].filter(Boolean).join('') || 'Analysis complete.';
            }

            fileInput.addEventListener('change', function () {
                var file = fileInput.files && fileInput.files[0];

                if (!file) {
                    return;
                }

                analysisPanel.classList.remove('d-none');
                livePreview.src = URL.createObjectURL(file);
                summary.textContent = 'Reading poster structure and generating a reusable prompt...';
                setProgress(5, 'Preview ready', 'Selected image loaded. Preparing analysis upload...');
                playAnalysisStartedSound();

                var formData = new FormData();
                formData.append('inspiration_image_file', file);

                var xhr = new XMLHttpRequest();
                xhr.open('POST', fileInput.dataset.analyzeUrl || '', true);
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.upload.addEventListener('progress', function (event) {
                    if (!event.lengthComputable) {
                        return;
                    }

                    var percent = Math.min(45, Math.round((event.loaded / event.total) * 45));
                    setProgress(percent, 'Uploading image', 'Uploading inspiration image for AI analysis...');
                });

                xhr.addEventListener('loadstart', function () {
                    setProgress(10, 'Uploading image', 'Uploading inspiration image for AI analysis...');
                });

                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 2) {
                        setProgress(60, 'Analyzing poster', 'OpenAI is extracting the poster style, layout, and text zones...');
                    }

                    if (xhr.readyState === 3) {
                        setProgress(80, 'Generating prompt', 'Building a reusable prompt template from the detected design...');
                    }
                };

                xhr.onload = function () {
                    var payload = {};

                    try {
                        payload = JSON.parse(xhr.responseText || '{}');
                    } catch (error) {
                        payload = {};
                    }

                    if (xhr.status < 200 || xhr.status >= 300 || !payload.data) {
                        setProgress(100, 'Analysis failed', payload.error || 'Could not analyze this inspiration image.');
                        summary.textContent = payload.error || 'Could not analyze this inspiration image.';

                        if (window.Botble && Botble.showError) {
                            Botble.showError(payload.error || 'Could not analyze this inspiration image.');
                        }

                        return;
                    }

                    promptField.value = payload.data.prompt_template || promptField.value;
                    reconstructionLayoutField.value = JSON.stringify(payload.data.editable_regions || {}, null, 2);
                    renderSummary(payload.data.summary || {});
                    setProgress(100, 'Analysis complete', 'Prompt template updated from the inspiration poster.');
                    playAnalysisCompleteSound();

                    if (window.Botble && Botble.showSuccess) {
                        Botble.showSuccess('Image Prompt Template updated from the inspiration image.');
                    }
                };

                xhr.onerror = function () {
                    setProgress(100, 'Analysis failed', 'Could not analyze this inspiration image.');
                    summary.textContent = 'Could not analyze this inspiration image.';

                    if (window.Botble && Botble.showError) {
                        Botble.showError('Could not analyze this inspiration image.');
                    }
                };

                xhr.send(formData);
            });
        })();
    </script>
@endpush
