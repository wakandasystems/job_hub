@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <sales-agent-campaigns
        settings-url="{{ route('sales-agent-campaigns.settings.update') }}"
        csrf-token="{{ csrf_token() }}"
        nakia-image-url="{{ $nakiaImageUrl ?: RvMedia::getDefaultImage() }}"
        logo-image-url="{{ $logoImageUrl ?: RvMedia::getDefaultImage() }}"
        default-image-url="{{ RvMedia::getDefaultImage() }}"
        :initial-samples='@json($samples)'
        :default-commission-rate='@json($defaultCommissionRate)'
        :global-discount-rate='@json($globalDiscountRate)'
        :initial-active-states='@json($campaigns->getCollection()->mapWithKeys(fn ($c) => [$c->getKey() => $c->is_active])->all())'
    ></sales-agent-campaigns>
@stop

@push('footer')
    <script type="text/x-template" id="sales-agent-campaigns-template">
        <div id="salesAgentCampaignsApp">
            <x-core::card class="mb-3">
                <x-core::card.header>
                    <x-core::card.title>Sales Agent Brand Settings</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <x-core::form :url="route('sales-agent-campaigns.settings.update')" method="put" enctype="multipart/form-data" id="salesAgentSettingsForm">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="position-relative d-inline-block" role="button" tabindex="0" title="Click to replace Nakia photo" @click="chooseImage('nakia')">
                                    <img :src="images.nakia.url" alt="Nakia" class="rounded border mb-2" style="width:120px;height:120px;object-fit:cover;">
                                    <div class="position-absolute top-0 start-0 w-100 h-100 rounded border bg-white bg-opacity-75 align-items-center justify-content-center" :class="images.nakia.loading ? 'd-flex' : 'd-none'" style="width:120px;height:120px;">
                                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold d-block">Nakia Photo</label>
                                    <input type="file" name="nakia_image" accept="image/*" class="d-none" ref="nakiaInput" @change="imageChanged('nakia', $event)">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="chooseImage('nakia')">Replace</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" @click="clearImage('nakia')">Clear</button>
                                    <a :href="images.nakia.url" download="nakia.png" class="btn btn-sm btn-outline-dark" @click.stop>Download</a>
                                    <div class="small mt-1" :class="images.nakia.statusClass" v-text="images.nakia.status"></div>
                                    <div class="form-text">Used in every agent's poster. Defaults to the CV Bot's Nakia photo if left empty.</div>
                                </div>
                            </div>

                            <div class="col-md-3 text-center">
                                <div class="position-relative d-inline-block" role="button" tabindex="0" title="Click to replace logo" @click="chooseImage('logo')">
                                    <img :src="images.logo.url" alt="Logo" class="rounded border mb-2" style="width:120px;height:120px;object-fit:contain;background:#fff;">
                                    <div class="position-absolute top-0 start-0 w-100 h-100 rounded border bg-white bg-opacity-75 align-items-center justify-content-center" :class="images.logo.loading ? 'd-flex' : 'd-none'" style="width:120px;height:120px;">
                                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold d-block">Wakanda Jobs Logo</label>
                                    <input type="file" name="logo_image" accept="image/*" class="d-none" ref="logoInput" @change="imageChanged('logo', $event)">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="chooseImage('logo')">Replace</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" @click="clearImage('logo')">Clear</button>
                                    <div class="small mt-1" :class="images.logo.statusClass" v-text="images.logo.status"></div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <x-core::form.text-input
                                    label="Default Commission Rate"
                                    type="number"
                                    name="default_commission_rate"
                                    :value="$defaultCommissionRate"
                                    :group-flat="true"
                                    helper-text="Pre-fills new agents. Editable per agent."
                                    @input="queueSettingsSave"
                                >
                                    <x-slot:append>
                                        <span class="input-group-text">%</span>
                                    </x-slot:append>
                                </x-core::form.text-input>
                            </div>

                            <div class="col-md-3">
                                <x-core::form.text-input
                                    label="Referral Discount Rate"
                                    type="number"
                                    name="global_discount_rate"
                                    :value="$globalDiscountRate"
                                    :group-flat="true"
                                    helper-text="Applied to checkout when any agent code is used."
                                    @input="queueSettingsSave"
                                >
                                    <x-slot:append>
                                        <span class="input-group-text">%</span>
                                    </x-slot:append>
                                </x-core::form.text-input>
                            </div>
                        </div>

                        <div class="small" :class="settingsStatusClass" v-text="settingsStatus"></div>
                    </x-core::form>
                </x-core::card.body>
            </x-core::card>

            <x-core::card>
                <x-core::card.header>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <x-core::card.title>Marketing Campaigns</x-core::card.title>
                        <a href="{{ route('sales-agent-campaigns.create') }}" class="btn btn-primary">
                            <x-core::icon name="ti ti-plus" class="me-1" /> Add Campaign
                        </a>
                    </div>
                </x-core::card.header>
                <x-core::card.body>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th style="width:86px;">Sample</th>
                                    <th>Name</th>
                                    <th>Product</th>
                                    <th>Aspect Ratio</th>
                                    <th>Promo Price</th>
                                    <th>Ends</th>
                                    <th>Clicks</th>
                                    <th>Active</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($campaigns as $campaign)
                                    @php($sampleImage = $campaign->latestMarketingImage)
                                    <tr>
                                        <td>
                                            <button
                                                v-if="sampleFor({{ $campaign->getKey() }}) && sampleFor({{ $campaign->getKey() }}).image_url"
                                                type="button"
                                                class="btn p-0 border"
                                                data-bs-toggle="modal"
                                                data-bs-target="#campaignImagePreviewModal"
                                                @click="openPreview(sampleFor({{ $campaign->getKey() }}).image_url, sampleFor({{ $campaign->getKey() }}).download_url, @js($campaign->name))"
                                                title="Preview sample image"
                                            >
                                                <img :src="sampleFor({{ $campaign->getKey() }}).image_url" alt="{{ $campaign->name }}" class="rounded" style="width:58px;height:58px;object-fit:cover;">
                                            </button>

                                            <span
                                                v-else-if="sampleFor({{ $campaign->getKey() }}) && sampleFor({{ $campaign->getKey() }}).status === 'generating'"
                                                class="d-inline-flex align-items-center justify-content-center rounded border bg-light text-primary"
                                                style="width:58px;height:58px;"
                                                title="Generating sample image"
                                            >
                                                <span class="spinner-border spinner-border-sm"></span>
                                            </span>

                                            <button
                                                v-else-if="sampleFor({{ $campaign->getKey() }}) && sampleFor({{ $campaign->getKey() }}).status === 'failed'"
                                                type="button"
                                                class="btn d-inline-flex align-items-center justify-content-center rounded border bg-light text-danger p-0"
                                                data-bs-toggle="modal"
                                                data-bs-target="#campaignImageErrorModal"
                                                style="width:58px;height:58px;"
                                                @click="openSampleError(sampleFor({{ $campaign->getKey() }}), @js($campaign->name))"
                                                :title="sampleFor({{ $campaign->getKey() }}).error_message || 'Image generation failed'"
                                            >
                                                <x-core::icon name="ti ti-alert-circle" />
                                            </button>

                                            @if ($sampleImage?->imageUrl())
                                                <button
                                                    v-else
                                                    type="button"
                                                    class="btn p-0 border"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#campaignImagePreviewModal"
                                                    @click="openPreview(@js($sampleImage->imageUrl()), @js(route('sales-agents.marketing-images.download', [$sampleImage->sales_agent_id, $sampleImage->getKey()])), @js($campaign->name))"
                                                    title="Preview sample image"
                                                >
                                                    <img src="{{ $sampleImage->imageUrl() }}" alt="{{ $campaign->name }}" class="rounded" style="width:58px;height:58px;object-fit:cover;">
                                                </button>
                                            @else
                                                <span v-else class="d-inline-flex align-items-center justify-content-center rounded border bg-light text-muted" style="width:58px;height:58px;">
                                                    <x-core::icon name="ti ti-photo" />
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $campaign->name }}</td>
                                        <td>{{ $campaign->resolvedProductLabel() }}</td>
                                        <td>{{ str_replace('_', ' ', $campaign->aspect_ratio) }}</td>
                                        <td>{{ $campaign->promo_price ?: '—' }}</td>
                                        <td>{{ $campaign->promo_end_date?->format('Y-m-d') ?: '—' }}</td>
                                        <td>{{ number_format($campaign->clicks_count ?? 0) }}</td>
                                        <td>
                                            <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    role="switch"
                                                    :checked="activeStates[{{ $campaign->getKey() }}]"
                                                    :disabled="toggleLoading[{{ $campaign->getKey() }}]"
                                                    style="width:2.5em;height:1.25em;cursor:pointer;"
                                                    @change="toggleActive({{ $campaign->getKey() }}, @js(route('sales-agent-campaigns.toggle-active', $campaign->getKey())), @js($campaign->name))"
                                                >
                                                <span
                                                    v-if="toggleLoading[{{ $campaign->getKey() }}]"
                                                    class="spinner-border spinner-border-sm text-secondary"
                                                    style="width:.75rem;height:.75rem;"
                                                ></span>
                                                <small
                                                    v-else
                                                    :class="activeStates[{{ $campaign->getKey() }}] ? 'text-success fw-semibold' : 'text-muted'"
                                                    v-text="activeStates[{{ $campaign->getKey() }}] ? 'Active' : 'Inactive'"
                                                ></small>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                :disabled="isSampleGenerating({{ $campaign->getKey() }})"
                                                data-bs-toggle="modal"
                                                data-bs-target="#generateSampleImageModal"
                                                @click="openGenerateSample({{ $campaign->getKey() }}, @js(route('sales-agent-campaigns.generate-sample', $campaign->getKey())), @js($campaign->name))"
                                                :title="isSampleGenerating({{ $campaign->getKey() }}) ? 'Sample image is already generating' : 'Generate sample image'"
                                            >
                                                <x-core::icon name="ti ti-sparkles" />
                                            </button>
                                            <a href="{{ route('sales-agent-campaigns.edit', $campaign->getKey()) }}" class="btn btn-sm btn-outline-dark">
                                                <x-core::icon name="ti ti-edit" />
                                            </a>
                                            <a href="{{ route('sales-agent-campaigns.links', $campaign->getKey()) }}" class="btn btn-sm btn-outline-success" title="Manage campaign links">
                                                <x-core::icon name="ti ti-link" />
                                            </a>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-danger"
                                                title="Delete campaign"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteCampaignModal"
                                                data-action="{{ route('sales-agent-campaigns.destroy', $campaign->getKey()) }}"
                                                data-label="{{ $campaign->name }}"
                                            >
                                                <x-core::icon name="ti ti-trash" />
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No campaigns yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $campaigns->links() }}
                </x-core::card.body>
            </x-core::card>

            <div class="modal fade" id="generateSampleImageModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content">
                        <div class="modal-body text-center py-4 px-4">
                            <div class="mb-3">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10" style="width:52px;height:52px;">
                                    <x-core::icon name="ti ti-sparkles" class="text-primary fs-3" />
                                </span>
                            </div>
                            <h6 class="fw-semibold mb-1">Generate sample image?</h6>
                            <p class="text-muted small mb-4" v-text="generateSampleText"></p>
                            <div class="mb-4 text-start">
                                <label class="form-label small fw-semibold">Person Source</label>
                                <select class="form-select form-select-sm" v-model="generateSample.subjectMode">
                                    <option value="nakia">Nakia</option>
                                    <option value="agent">Agent photo</option>
                                    <option value="both">Nakia + Agent</option>
                                </select>
                            </div>
                            <div class="d-flex gap-2 justify-content-center">
                                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary px-4" :disabled="isGenerateSampleDisabled" @click="submitGenerateSample">
                                    <span class="spinner-border spinner-border-sm me-1" v-if="generateSample.loading"></span>
                                    <span v-text="generateSample.loading ? 'Generating...' : (isCurrentSampleGenerating ? 'Already queued' : 'Generate')"></span>
                                </button>
                            </div>
                            <div class="small mt-3" :class="generateSample.statusClass" v-text="generateSample.status"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="deleteCampaignModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content">
                        <div class="modal-body text-center py-4 px-4">
                            <div class="mb-3">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                                    <x-core::icon name="ti ti-trash" class="text-danger fs-3" />
                                </span>
                            </div>
                            <h6 class="fw-semibold mb-1">Delete this campaign?</h6>
                            <p class="text-muted small mb-4" id="deleteCampaignModalLabel">This cannot be undone.</p>
                            <div class="d-flex gap-2 justify-content-center">
                                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                                <form id="deleteCampaignForm" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger px-4">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="campaignImagePreviewModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" v-text="preview.title || 'Campaign Image'"></h5>
                            <div class="d-flex gap-2 ms-auto me-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="zoomPreview(-0.25)">
                                    <x-core::icon name="ti ti-minus" />
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="setPreviewZoom(1)" v-text="previewZoomLabel"></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="zoomPreview(0.25)">
                                    <x-core::icon name="ti ti-plus" />
                                </button>
                                <a :href="preview.download || '#'" class="btn btn-sm btn-primary">
                                    <x-core::icon name="ti ti-download" class="me-1" /> Download
                                </a>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body bg-light overflow-auto text-center" style="max-height:78vh;">
                            <img :src="preview.src" alt="" :style="{ maxWidth: '100%', transformOrigin: 'top center', transition: 'transform .15s ease', transform: 'scale(' + preview.zoom + ')' }">
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="campaignImageErrorModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" v-text="sampleError.title || 'Image generation failed'"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <textarea class="form-control font-monospace small" rows="7" readonly v-model="sampleError.message"></textarea>
                            <div class="small mt-2" :class="sampleError.copyStatusClass" v-text="sampleError.copyStatus"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" @click="copySampleError">
                                <x-core::icon name="ti ti-copy" class="me-1" /> Copy
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script>
        'use strict';

        if (typeof vueApp !== 'undefined') {
            vueApp.booting(function (vue) {
                vue.component('sales-agent-campaigns', {
                    template: '#sales-agent-campaigns-template',
                    props: {
                        settingsUrl: {
                            type: String,
                            required: true,
                        },
                        csrfToken: {
                            type: String,
                            required: true,
                        },
                        nakiaImageUrl: {
                            type: String,
                            required: true,
                        },
                        logoImageUrl: {
                            type: String,
                            required: true,
                        },
                        defaultImageUrl: {
                            type: String,
                            required: true,
                        },
                        initialSamples: {
                            type: Object,
                            default: function () {
                                return {};
                            },
                        },
                        initialActiveStates: {
                            type: Object,
                            default: function () {
                                return {};
                            },
                        },
                        defaultCommissionRate: {
                            type: Number,
                            default: 10,
                        },
                        globalDiscountRate: {
                            type: Number,
                            default: 10,
                        },
                    },
                    data: function () {
                        return {
                            settingsStatus: 'Changes save automatically.',
                            settingsStatusClass: 'text-muted',
                            settingsSaveTimer: null,
                            samples: this.initialSamples || {},
                            samplePollTimers: {},
                            activeStates: Object.assign({}, this.initialActiveStates || {}),
                            toggleLoading: {},
                            images: {
                                nakia: {
                                    url: this.nakiaImageUrl,
                                    defaultUrl: this.defaultImageUrl,
                                    inputRef: 'nakiaInput',
                                    inputName: 'nakia_image',
                                    responseKey: 'nakia_image_url',
                                    clearName: 'clear_nakia_image',
                                    loading: false,
                                    status: 'No upload selected.',
                                    statusClass: 'text-muted',
                                },
                                logo: {
                                    url: this.logoImageUrl,
                                    defaultUrl: this.defaultImageUrl,
                                    inputRef: 'logoInput',
                                    inputName: 'logo_image',
                                    responseKey: 'logo_image_url',
                                    clearName: 'clear_logo_image',
                                    loading: false,
                                    status: 'No upload selected.',
                                    statusClass: 'text-muted',
                                },
                            },
                            generateSample: {
                                action: '',
                                label: '',
                                campaignId: null,
                                subjectMode: 'nakia',
                                loading: false,
                                status: '',
                                statusClass: 'text-muted',
                            },
                            preview: {
                                src: '',
                                download: '',
                                title: 'Campaign Image',
                                zoom: 1,
                            },
                            sampleError: {
                                title: 'Image generation failed',
                                message: '',
                                copyStatus: '',
                                copyStatusClass: 'text-muted',
                            },
                        };
                    },
                    computed: {
                        generateSampleText: function () {
                            var label = this.generateSample.subjectMode === 'both'
                                ? 'Nakia + Agent'
                                : (this.generateSample.subjectMode === 'agent' ? 'Agent photo' : 'Nakia');

                            return 'This will queue a sample poster for "' + (this.generateSample.label || 'this campaign') + '" using ' + label + '.';
                        },
                        isCurrentSampleGenerating: function () {
                            return this.isSampleGenerating(this.generateSample.campaignId);
                        },
                        isGenerateSampleDisabled: function () {
                            return this.generateSample.loading || this.isCurrentSampleGenerating;
                        },
                        previewZoomLabel: function () {
                            return Math.round(this.preview.zoom * 100) + '%';
                        },
                    },
                    mounted: function () {
                        var self = this;

                        Object.keys(this.samples || {}).forEach(function (campaignId) {
                            var sample = self.samples[campaignId];

                            if (sample && sample.status === 'generating') {
                                self.pollSample(sample);
                            }
                        });
                    },
                    methods: {
                        chooseImage: function (key) {
                            this.$refs[this.images[key].inputRef].click();
                        },
                        imageChanged: function (key, event) {
                            var file = event.target.files && event.target.files[0];

                            if (!file) {
                                this.setImageStatus(key, false, 'No upload selected.', 'text-muted');
                                return;
                            }

                            this.images[key].url = URL.createObjectURL(file);
                            this.setImageStatus(key, true, 'Uploading ' + file.name + '...', 'text-primary');
                            this.saveSettings({ includeFiles: true, activeImage: key });
                        },
                        clearImage: function (key) {
                            this.$refs[this.images[key].inputRef].value = '';
                            this.images[key].url = this.images[key].defaultUrl;
                            this.setImageStatus(key, true, 'Clearing...', 'text-primary');
                            this.saveSettings({ clearName: this.images[key].clearName, activeImage: key });
                        },
                        queueSettingsSave: function () {
                            clearTimeout(this.settingsSaveTimer);
                            this.settingsSaveTimer = setTimeout(this.saveSettings, 600);
                        },
                        saveSettings: function (options) {
                            options = options || {};

                            var form = document.getElementById('salesAgentSettingsForm');
                            var formData = new FormData(form);
                            var imageKeys = Object.keys(this.images);
                            var self = this;

                            if (options.clearName) {
                                formData.append(options.clearName, '1');
                            }

                            if (!options.includeFiles) {
                                imageKeys.forEach(function (key) {
                                    formData.delete(self.images[key].inputName);
                                });
                            }

                            this.settingsStatus = 'Saving...';
                            this.settingsStatusClass = 'text-primary';

                            fetch(this.settingsUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': this.csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: formData,
                            })
                                .then(function (response) {
                                    return response.json().then(function (payload) {
                                        return { ok: response.ok, payload: payload };
                                    });
                                })
                                .then(function (result) {
                                    if (!result.ok) {
                                        throw result.payload;
                                    }

                                    var data = result.payload.data || {};

                                    imageKeys.forEach(function (key) {
                                        var image = self.images[key];

                                        if (data[image.responseKey]) {
                                            image.url = data[image.responseKey];
                                        }

                                        self.$refs[image.inputRef].value = '';
                                    });

                                    if (options.activeImage) {
                                        self.setImageStatus(options.activeImage, false, options.clearName ? 'Cleared.' : 'Upload complete.', 'text-success');
                                    }

                                    self.settingsStatus = 'Saved.';
                                    self.settingsStatusClass = 'text-success';

                                    if (window.Botble) {
                                        Botble.showSuccess(result.payload.message || 'Settings saved.');
                                    }
                                })
                                .catch(function (payload) {
                                    if (options.activeImage) {
                                        self.setImageStatus(options.activeImage, false, options.clearName ? 'Clear failed.' : 'Upload failed.', 'text-danger');
                                    }

                                    self.settingsStatus = 'Save failed.';
                                    self.settingsStatusClass = 'text-danger';

                                    if (window.Botble) {
                                        Botble.showError((payload && payload.message) || 'Could not save settings.');
                                    }
                                });
                        },
                        setImageStatus: function (key, loading, status, statusClass) {
                            this.images[key].loading = loading;
                            this.images[key].status = status;
                            this.images[key].statusClass = statusClass;
                        },
                        sampleFor: function (campaignId) {
                            return this.samples[campaignId] || null;
                        },
                        isSampleGenerating: function (campaignId) {
                            var sample = this.sampleFor(campaignId);

                            return !!(sample && sample.status === 'generating');
                        },
                        openGenerateSample: function (campaignId, action, label) {
                            var sample = this.sampleFor(campaignId);
                            this.generateSample.action = action;
                            this.generateSample.label = label;
                            this.generateSample.campaignId = campaignId;
                            this.generateSample.subjectMode = sample && sample.subject_mode ? sample.subject_mode : 'nakia';
                            this.generateSample.loading = false;
                            this.generateSample.status = this.isCurrentSampleGenerating
                                ? 'A sample image for this campaign is already queued.'
                                : '';
                            this.generateSample.statusClass = this.isCurrentSampleGenerating ? 'text-warning' : 'text-muted';
                        },
                        submitGenerateSample: function () {
                            var self = this;

                            if (!this.generateSample.action || this.isGenerateSampleDisabled) {
                                return;
                            }

                            this.generateSample.loading = true;
                            this.generateSample.status = 'Queueing sample image...';
                            this.generateSample.statusClass = 'text-primary';

                            var formData = new FormData();
                            formData.append('subject_mode', this.generateSample.subjectMode);

                            fetch(this.generateSample.action, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': this.csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: formData,
                            })
                                .then(function (response) {
                                    return response.json().then(function (payload) {
                                        return { ok: response.ok, payload: payload };
                                    });
                                })
                                .then(function (result) {
                                    if (!result.ok) {
                                        throw result.payload;
                                    }

                                    self.generateSample.loading = false;
                                    self.generateSample.status = result.payload.message || 'Sample image generation queued.';
                                    self.generateSample.statusClass = 'text-success';

                                    if (result.payload.data) {
                                        self.setSample(result.payload.data);
                                        self.pollSample(result.payload.data);
                                    }

                                    if (window.Botble) {
                                        Botble.showSuccess(self.generateSample.status);
                                    }
                                })
                                .catch(function (payload) {
                                    self.generateSample.loading = false;
                                    self.generateSample.status = (payload && payload.message) || 'Could not queue the sample image.';
                                    self.generateSample.statusClass = 'text-danger';
                                    self.openSampleError({
                                        error_message: self.formatPayloadError(payload, self.generateSample.status),
                                    }, self.generateSample.label);
                                    self.showSampleErrorModal();

                                    if (window.Botble) {
                                        Botble.showError(self.generateSample.status);
                                    }
                                });
                        },
                        setSample: function (sample) {
                            this.samples[sample.campaign_id] = sample;
                        },
                        pollSample: function (sample) {
                            var self = this;
                            var attempts = 0;
                            var campaignId = sample.campaign_id;

                            clearInterval(this.samplePollTimers[campaignId]);

                            this.samplePollTimers[campaignId] = setInterval(function () {
                                attempts++;

                                fetch(sample.status_url, {
                                    headers: {
                                        'Accept': 'application/json',
                                    },
                                })
                                    .then(function (response) {
                                        return response.json().then(function (payload) {
                                            return { ok: response.ok, payload: payload };
                                        });
                                    })
                                    .then(function (result) {
                                        if (!result.ok) {
                                            throw result.payload;
                                        }

                                        var updated = result.payload.data;

                                        self.setSample(updated);

                                        if (updated.status === 'completed' || updated.status === 'failed' || attempts >= 80) {
                                            clearInterval(self.samplePollTimers[campaignId]);
                                        }

                                        if (updated.status === 'completed') {
                                            self.generateSample.loading = false;
                                            self.generateSample.status = 'Sample image is ready.';
                                            self.generateSample.statusClass = 'text-success';
                                            self.hideGenerateSampleModal();
                                            self.openPreview(updated.image_url, updated.download_url, self.generateSample.label);
                                            self.showPreviewModal();

                                            if (window.Botble) {
                                                Botble.showSuccess('Sample image is ready.');
                                            }
                                        }

                                        if (updated.status === 'failed' && window.Botble) {
                                            Botble.showError(updated.error_message || 'Sample image generation failed.');
                                        }

                                        if (updated.status === 'failed') {
                                            self.generateSample.loading = false;
                                            self.generateSample.status = updated.error_message || 'Sample image generation failed.';
                                            self.generateSample.statusClass = 'text-danger';
                                            self.openSampleError(updated, self.generateSample.label);
                                            self.showSampleErrorModal();
                                        }
                                    })
                                    .catch(function () {
                                        if (attempts >= 10) {
                                            clearInterval(self.samplePollTimers[campaignId]);
                                        }
                                    });
                            }, 3000);
                        },
                        openPreview: function (src, download, title) {
                            this.preview.src = src;
                            this.preview.download = download;
                            this.preview.title = title || 'Campaign Image';
                            this.setPreviewZoom(1);
                        },
                        showPreviewModal: function () {
                            var modal = document.getElementById('campaignImagePreviewModal');

                            if (!modal || typeof bootstrap === 'undefined') {
                                return;
                            }

                            bootstrap.Modal.getOrCreateInstance(modal).show();
                        },
                        hideGenerateSampleModal: function () {
                            var modal = document.getElementById('generateSampleImageModal');

                            if (!modal || typeof bootstrap === 'undefined') {
                                return;
                            }

                            var instance = bootstrap.Modal.getInstance(modal);

                            if (instance) {
                                instance.hide();
                            }
                        },
                        setPreviewZoom: function (value) {
                            this.preview.zoom = Math.max(0.5, Math.min(3, value));
                        },
                        zoomPreview: function (step) {
                            this.setPreviewZoom(this.preview.zoom + step);
                        },
                        openSampleError: function (sample, campaignName) {
                            this.sampleError.title = campaignName ? 'Image generation failed: ' + campaignName : 'Image generation failed';
                            this.sampleError.message = (sample && sample.error_message) || 'Image generation failed.';
                            this.sampleError.copyStatus = '';
                            this.sampleError.copyStatusClass = 'text-muted';
                        },
                        showSampleErrorModal: function () {
                            var modal = document.getElementById('campaignImageErrorModal');

                            if (!modal || typeof bootstrap === 'undefined') {
                                return;
                            }

                            bootstrap.Modal.getOrCreateInstance(modal).show();
                        },
                        formatPayloadError: function (payload, fallback) {
                            if (!payload) {
                                return fallback || 'Request failed.';
                            }

                            if (payload.errors) {
                                return Object.keys(payload.errors)
                                    .map(function (key) {
                                        return payload.errors[key].join('\n');
                                    })
                                    .join('\n');
                            }

                            return payload.message || fallback || 'Request failed.';
                        },
                        copySampleError: function () {
                            var self = this;
                            var message = this.sampleError.message || '';

                            if (!message) {
                                return;
                            }

                            if (navigator.clipboard && window.isSecureContext) {
                                navigator.clipboard.writeText(message)
                                    .then(function () {
                                        self.sampleError.copyStatus = 'Copied.';
                                        self.sampleError.copyStatusClass = 'text-success';
                                    })
                                    .catch(function () {
                                        self.copySampleErrorFallback(message);
                                    });

                                return;
                            }

                            this.copySampleErrorFallback(message);
                        },
                        toggleActive: function (campaignId, url, campaignName) {
                            var self = this;

                            if (this.toggleLoading[campaignId]) {
                                return;
                            }

                            self.toggleLoading[campaignId] = true;

                            fetch(url, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': this.csrfToken,
                                    'Accept': 'application/json',
                                },
                            })
                                .then(function (response) {
                                    return response.json().then(function (payload) {
                                        return { ok: response.ok, payload: payload };
                                    });
                                })
                                .then(function (result) {
                                    self.toggleLoading[campaignId] = false;

                                    if (!result.ok) {
                                        throw result.payload;
                                    }

                                    var isActive = result.payload.data.is_active;
                                    self.activeStates[campaignId] = isActive;

                                    if (window.Botble) {
                                        var label = campaignName ? '"' + campaignName + '"' : 'Campaign';
                                        Botble.showSuccess(label + (isActive ? ' activated.' : ' deactivated.'));
                                    }
                                })
                                .catch(function (payload) {
                                    self.toggleLoading[campaignId] = false;

                                    if (window.Botble) {
                                        Botble.showError((payload && payload.message) || 'Could not update campaign.');
                                    }
                                });
                        },
                        copySampleErrorFallback: function (message) {
                            var textarea = document.createElement('textarea');

                            textarea.value = message;
                            textarea.setAttribute('readonly', 'readonly');
                            textarea.style.position = 'fixed';
                            textarea.style.left = '-9999px';
                            document.body.appendChild(textarea);
                            textarea.select();

                            try {
                                document.execCommand('copy');
                                this.sampleError.copyStatus = 'Copied.';
                                this.sampleError.copyStatusClass = 'text-success';
                            } catch (e) {
                                this.sampleError.copyStatus = 'Copy failed.';
                                this.sampleError.copyStatusClass = 'text-danger';
                            }

                            document.body.removeChild(textarea);
                        },
                    },
                });
            });
        }

        document.getElementById('deleteCampaignModal')?.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;

            if (!button) {
                return;
            }

            document.getElementById('deleteCampaignForm').action = button.dataset.action;
            document.getElementById('deleteCampaignModalLabel').textContent = 'Delete "' + button.dataset.label + '"? This cannot be undone.';
        });
    </script>
@endpush
