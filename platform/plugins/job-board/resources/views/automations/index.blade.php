@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    @php
        $platforms = [
            'facebook' => [
                'label' => 'Facebook Page',
                'icon'  => 'ti ti-brand-facebook',
                'color' => '#1877F2',
                'fields' => [
                    'page_id'      => ['label' => 'Page ID', 'placeholder' => 'e.g. 123456789012345', 'help' => 'Found in your Facebook Page settings → About'],
                    'access_token' => ['label' => 'Page Access Token', 'placeholder' => 'Paste your long-lived page token', 'type' => 'password', 'help' => 'Get from Meta for Developers → Graph API Explorer → generate a Page token with pages_manage_posts scope'],
                ],
            ],
            'linkedin' => [
                'label' => 'LinkedIn Company Page',
                'icon'  => 'ti ti-brand-linkedin',
                'color' => '#0A66C2',
                'fields' => [
                    'org_id'       => ['label' => 'Company ID', 'placeholder' => 'e.g. 12345678', 'help' => 'Found in your Company Page URL: linkedin.com/company/your-page/admin → the numeric ID'],
                    'access_token' => ['label' => 'Access Token', 'placeholder' => 'OAuth 2.0 access token', 'type' => 'password', 'help' => 'Generate via LinkedIn Developer Portal with w_organization_social scope'],
                ],
            ],
            'whatsapp' => [
                'label' => 'WhatsApp',
                'icon'  => 'ti ti-brand-whatsapp',
                'color' => '#25D366',
                'fields' => [
                    'phone_number_id' => ['label' => 'Phone Number ID', 'placeholder' => 'e.g. 123456789012345', 'help' => 'Found in Meta Business → WhatsApp → Phone Numbers'],
                    'access_token'    => ['label' => 'Access Token', 'placeholder' => 'System user access token', 'type' => 'password', 'help' => 'Create a System User in Meta Business Manager and generate a token'],
                    'recipient'       => ['label' => 'Recipient Number', 'placeholder' => '+260977000000', 'help' => 'Target phone number in international format (for broadcasts/channels, use the group JID)'],
                ],
            ],
            'telegram' => [
                'label' => 'Telegram Copy Queue',
                'icon'  => 'ti ti-brand-telegram',
                'color' => '#229ED9',
                'fields' => [
                    'chat_id'        => ['label' => 'Chat ID', 'placeholder' => 'e.g. 123456789', 'help' => 'Use the Telegram chat ID already receiving alerts.'],
                    'bot_token'      => ['label' => 'Bot Token Override', 'placeholder' => 'Leave blank to use the saved Job Alert bot token', 'type' => 'password', 'required' => false, 'help' => 'Optional. Defaults to the Telegram Bot Token under Job Alert Settings.'],
                    'country_id'     => ['label' => 'Country Filter (optional)', 'type' => 'country_select', 'required' => false, 'help' => 'Only posts for jobs in this country will be sent. Leave blank to send jobs from all countries.'],
                    'generate_image' => ['label' => 'Generate AI image with each post', 'type' => 'checkbox', 'required' => false, 'help' => 'Uses DALL-E 3 to create a job banner image. Requires OPENAI_API_KEY in .env or openai_api_key in site settings. ~$0.04 per post.'],
                ],
            ],
            'whapi' => [
                'label' => 'WhatsApp Channel (Whapi)',
                'icon'  => 'ti ti-brand-whatsapp',
                'color' => '#25D366',
                'fields' => [
                    'token'       => ['label' => 'Whapi API Token', 'placeholder' => 'Paste your Whapi channel/instance token', 'type' => 'password', 'help' => 'Found in your Whapi dashboard → Channels → your channel → API Token.'],
                    'channel_id'  => ['label' => 'Channel / Newsletter', 'type' => 'whapi_channel_select', 'help' => 'Enter the token above, then click <strong>Fetch</strong> to load your newsletters as a dropdown.'],
                    'country_id'  => ['label' => 'Country Filter (optional)', 'type' => 'country_select', 'required' => false, 'help' => 'Only jobs matching this country will be posted. Leave blank to post jobs from all countries.'],
                    'gateway_url' => ['label' => 'Gateway URL (optional)', 'placeholder' => 'https://gate.whapi.cloud', 'required' => false, 'help' => 'Your Whapi gateway URL. Leave blank to use the default https://gate.whapi.cloud'],
                    'send_image'  => ['label' => 'Attach job image with each post', 'type' => 'checkbox', 'required' => false, 'help' => 'Sends the job image (company logo or generated banner) alongside the message.'],
                ],
            ],
        ];
    @endphp

    <div class="row g-4">

        {{-- Platform stat cards --}}
        <div class="col-12">
            <x-core::stat-widget class="mb-0">
                @foreach($platforms as $key => $platform)
                    @php $count = ($automations[$key] ?? collect())->count(); $active = ($automations[$key] ?? collect())->where('is_active', true)->count(); @endphp
                    <x-core::stat-widget.item
                        :label="$platform['label']"
                        :value="$active . ' / ' . $count . ' active'"
                        :icon="$platform['icon']"
                        color="{{ $active > 0 ? 'success' : 'secondary' }}"
                    />
                @endforeach
            </x-core::stat-widget>
        </div>

        {{-- How it works banner --}}
        <div class="col-12">
            <x-core::card>
                <x-core::card.body class="py-3">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <i class="fas fa-info-circle text-primary fs-4 flex-shrink-0"></i>
                        <div class="flex-grow-1">
                            <strong>How automations work:</strong>
                            When a job is published — whether posted directly by an admin or imported by a crawler agent — it is automatically shared to all <strong>active</strong> automations below.
                            Toggle the switch on each automation to enable or disable it.
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="button" class="btn btn-sm btn-outline-danger" id="btn-clear-chats"
                                    data-url="{{ route('job-board.automations.clear-all-chats') }}">
                                <i class="fas fa-trash me-1"></i> Delete All Chats
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" id="btn-regen-today"
                                    data-url="{{ route('job-board.automations.regenerate-today') }}">
                                <i class="fas fa-sync me-1"></i> Regenerate Today's Jobs
                            </button>
                            <button type="button" class="btn btn-sm btn-success" id="btn-whapi-yesterday"
                                    data-url="{{ route('job-board.automations.whapi-send-yesterday') }}">
                                <i class="fab fa-whatsapp me-1"></i> WhatsApp: Yesterday's Jobs
                            </button>
                        </div>
                    </div>
                </x-core::card.body>
            </x-core::card>
        </div>

        {{-- Per-platform columns --}}
        @foreach($platforms as $platformKey => $platform)
            <div class="col-md-4">
                <x-core::card class="h-100">
                    <x-core::card.header>
                        <div class="d-flex align-items-center gap-2 w-100">
                            <i class="{{ $platform['icon'] }} fs-4" style="color: {{ $platform['color'] }}"></i>
                            <h5 class="mb-0 flex-grow-1">{{ $platform['label'] }}</h5>
                            <button type="button"
                                class="btn btn-primary btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#modal-add-{{ $platformKey }}">
                                <i class="fas fa-plus me-1"></i> Add
                            </button>
                        </div>
                    </x-core::card.header>

                    <x-core::card.body class="p-0">
                        @forelse($automations[$platformKey] ?? [] as $automation)
                            <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom automation-row" data-id="{{ $automation->id }}">
                                {{-- Toggle --}}
                                <div class="form-check form-switch mb-0">
                                    <input
                                        class="form-check-input automation-toggle"
                                        type="checkbox"
                                        role="switch"
                                        data-url="{{ route('job-board.automations.toggle', $automation->id) }}"
                                        {{ $automation->is_active ? 'checked' : '' }}
                                        title="{{ $automation->is_active ? 'Active — click to disable' : 'Inactive — click to enable' }}">
                                </div>

                                {{-- Name + status --}}
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate small">{{ $automation->name }}</div>
                                    <div class="text-muted" style="font-size:.75rem">
                                        <span class="automation-status-badge badge {{ $automation->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                            {{ $automation->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="d-flex gap-1">
                                    <button type="button"
                                        class="btn btn-ghost-secondary btn-icon btn-sm"
                                        title="Edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modal-edit-{{ $automation->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @if($platformKey === 'whapi')
                                    <button type="button"
                                        class="btn btn-ghost-success btn-icon btn-sm automation-send-jobs"
                                        title="Send jobs to this channel"
                                        data-url="{{ route('job-board.automations.send-jobs', $automation->id) }}"
                                        data-name="{{ $automation->name }}">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                    <button type="button"
                                        class="btn btn-ghost-primary btn-icon btn-sm automation-duplicate"
                                        title="Duplicate (creates disabled copy)"
                                        data-url="{{ route('job-board.automations.duplicate', $automation->id) }}"
                                        data-name="{{ $automation->name }}">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    @endif
                                    <button type="button"
                                        class="btn btn-ghost-danger btn-icon btn-sm automation-delete"
                                        title="Delete"
                                        data-url="{{ route('job-board.automations.destroy', $automation->id) }}"
                                        data-name="{{ $automation->name }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4 small">
                                <i class="fas fa-plug d-block mb-1 fs-4"></i>
                                No automations configured yet.
                            </div>
                        @endforelse
                    </x-core::card.body>
                </x-core::card>
            </div>

            {{-- Add modal --}}
            <div class="modal fade" id="modal-add-{{ $platformKey }}" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('job-board.automations.store') }}">
                            @csrf
                            <input type="hidden" name="platform" value="{{ $platformKey }}">
                            <div class="modal-header">
                                <h5 class="modal-title d-flex align-items-center gap-2">
                                    <i class="{{ $platform['icon'] }}" style="color: {{ $platform['color'] }}"></i>
                                    Add {{ $platform['label'] }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Display Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" placeholder="e.g. Wakanda Jobs Facebook Page" required>
                                    <div class="form-text">A friendly label to identify this automation.</div>
                                </div>
                                @foreach($platform['fields'] as $fieldKey => $field)
                                    <div class="mb-3">
                                        @if(($field['type'] ?? 'text') === 'checkbox')
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"
                                                    name="settings[{{ $fieldKey }}]"
                                                    value="1" id="add-{{ $platformKey }}-{{ $fieldKey }}">
                                                <label class="form-check-label fw-semibold" for="add-{{ $platformKey }}-{{ $fieldKey }}">
                                                    {{ $field['label'] }}
                                                </label>
                                            </div>
                                        @elseif(($field['type'] ?? 'text') === 'country_select')
                                            <label class="form-label fw-semibold">{{ $field['label'] }}</label>
                                            <select name="settings[{{ $fieldKey }}]" class="form-select">
                                                <option value="">— All countries —</option>
                                                @foreach($countries as $cId => $cName)
                                                    <option value="{{ $cId }}">{{ $cName }}</option>
                                                @endforeach
                                            </select>
                                        @elseif(($field['type'] ?? 'text') === 'whapi_channel_select')
                                            <label class="form-label fw-semibold">{{ $field['label'] }} <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <select name="settings[{{ $fieldKey }}]"
                                                        class="form-select whapi-channel-select"
                                                        data-automation-id=""
                                                        data-fetch-url="{{ route('job-board.automations.whapi-fetch-channels') }}"
                                                        required>
                                                    <option value="">— Enter token above, then click Fetch —</option>
                                                </select>
                                                <button type="button" class="btn btn-outline-secondary whapi-fetch-btn" title="Load newsletters from Whapi using the token above">
                                                    <i class="fas fa-sync me-1"></i> Fetch
                                                </button>
                                            </div>
                                        @else
                                            <label class="form-label fw-semibold">{{ $field['label'] }} @if(($field['required'] ?? true) !== false)<span class="text-danger">*</span>@endif</label>
                                            <input
                                                type="{{ $field['type'] ?? 'text' }}"
                                                name="settings[{{ $fieldKey }}]"
                                                class="form-control"
                                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                                @if(($field['required'] ?? true) !== false) required @endif>
                                        @endif
                                        @if(!empty($field['help']))
                                            <div class="form-text text-muted">{!! $field['help'] !!}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Add Automation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Edit modals for each automation of this platform --}}
            @foreach($automations[$platformKey] ?? [] as $automation)
                <div class="modal fade" id="modal-edit-{{ $automation->id }}" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('job-board.automations.update', $automation->id) }}">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title d-flex align-items-center gap-2">
                                        <i class="{{ $platform['icon'] }}" style="color: {{ $platform['color'] }}"></i>
                                        Edit: {{ $automation->name }}
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Display Name</label>
                                        <input type="text" name="name" class="form-control" value="{{ $automation->name }}" required>
                                    </div>
                                    @foreach($platform['fields'] as $fieldKey => $field)
                                        <div class="mb-3">
                                            @if(($field['type'] ?? 'text') === 'checkbox')
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="settings[{{ $fieldKey }}]"
                                                        value="1" id="edit-{{ $automation->id }}-{{ $fieldKey }}"
                                                        {{ !empty($automation->settings[$fieldKey]) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="edit-{{ $automation->id }}-{{ $fieldKey }}">
                                                        {{ $field['label'] }}
                                                    </label>
                                                </div>
                                            @elseif(($field['type'] ?? 'text') === 'country_select')
                                                <label class="form-label fw-semibold">{{ $field['label'] }}</label>
                                                <select name="settings[{{ $fieldKey }}]" class="form-select">
                                                    <option value="">— All countries —</option>
                                                    @foreach($countries as $cId => $cName)
                                                        <option value="{{ $cId }}" {{ (string)($automation->settings[$fieldKey] ?? '') === (string)$cId ? 'selected' : '' }}>{{ $cName }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif(($field['type'] ?? 'text') === 'whapi_channel_select')
                                                @php $savedJid = $automation->settings[$fieldKey] ?? ''; @endphp
                                                <label class="form-label fw-semibold">{{ $field['label'] }}</label>
                                                <div class="input-group">
                                                    <select name="settings[{{ $fieldKey }}]"
                                                            class="form-select whapi-channel-select"
                                                            data-automation-id="{{ $automation->id }}"
                                                            data-fetch-url="{{ route('job-board.automations.whapi-fetch-channels') }}"
                                                            data-saved-jid="{{ $savedJid }}">
                                                        @if($savedJid)
                                                            <option value="{{ $savedJid }}" selected>{{ $savedJid }}</option>
                                                        @else
                                                            <option value="">— Click Fetch to load newsletters —</option>
                                                        @endif
                                                    </select>
                                                    <button type="button" class="btn btn-outline-secondary whapi-fetch-btn" title="Load newsletters from Whapi using the saved token">
                                                        <i class="fas fa-sync me-1"></i> Fetch
                                                    </button>
                                                </div>
                                                @if($savedJid)
                                                    <div class="form-text text-success small"><i class="fas fa-check me-1"></i>Saved: <code>{{ $savedJid }}</code>. Click Fetch to pick a different channel.</div>
                                                @endif
                                            @else
                                                <label class="form-label fw-semibold">{{ $field['label'] }}</label>
                                                <input
                                                    type="{{ $field['type'] ?? 'text' }}"
                                                    name="settings[{{ $fieldKey }}]"
                                                    class="form-control"
                                                    placeholder="{{ $field['placeholder'] ?? '' }}"
                                                    value="{{ ($field['type'] ?? 'text') === 'password' ? '' : ($automation->settings[$fieldKey] ?? '') }}">
                                                @if(($field['type'] ?? 'text') === 'password' && !empty($automation->settings[$fieldKey]))
                                                    <div class="form-text text-success"><i class="fas fa-check me-1"></i>Token is saved. Enter a new value to replace it.</div>
                                                @endif
                                            @endif
                                            @if(!empty($field['help']))
                                                <div class="form-text text-muted">{!! $field['help'] !!}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @endforeach

        {{-- Delete confirmation modal --}}
        <div class="modal fade" id="deleteAutomationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-body text-center py-4 px-4">
                        <div class="mb-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                                <i class="fas fa-trash text-danger fs-3"></i>
                            </span>
                        </div>
                        <h6 class="fw-semibold mb-1">Delete this automation?</h6>
                        <p class="text-muted small mb-4" id="deleteAutomationLabel"></p>
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger px-4" id="confirmDeleteAutomation">
                                <i class="fas fa-trash me-1"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Send jobs modal --}}
        <div class="modal fade" id="sendJobsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title d-flex align-items-center gap-2">
                            <i class="fas fa-paper-plane text-success"></i>
                            <span>Send Jobs — <span id="sendJobsModalName"></span></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">Choose which jobs to send to this WhatsApp channel. Jobs are filtered by the automation's country setting.</p>
                        <div class="d-flex flex-column gap-2">
                            @foreach([
                                'today'     => ['Today',                   'Jobs posted today',                         'ti ti-calendar-event text-primary'],
                                'yesterday' => ['Yesterday',               'Jobs posted yesterday',                     'ti ti-calendar-minus text-info'],
                                '7days'     => ['Last 7 days',             'Jobs posted in the past week',              'ti ti-calendar-week text-warning'],
                                '30days'    => ['Last 30 days',            'Jobs posted in the past month',             'ti ti-calendar-month text-orange'],
                                'active'    => ['All active (not expired)','All published jobs that haven\'t closed yet','ti ti-briefcase text-success'],
                            ] as $val => [$label, $desc, $icon])
                            <label class="d-flex align-items-center gap-3 p-3 border rounded cursor-pointer send-period-option {{ $val === 'yesterday' ? 'border-primary bg-primary-subtle' : '' }}" style="cursor:pointer">
                                <input type="radio" name="sendPeriod" value="{{ $val }}" class="form-check-input mt-0 flex-shrink-0" {{ $val === 'yesterday' ? 'checked' : '' }}>
                                <i class="{{ $icon }} fs-4 flex-shrink-0"></i>
                                <div>
                                    <div class="fw-semibold small">{{ $label }}</div>
                                    <div class="text-muted" style="font-size:.78rem">{{ $desc }}</div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                        <div id="sendJobsProgress" class="mt-3" style="display:none">
                            <div class="progress" style="height:6px">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width:100%"></div>
                            </div>
                            <p class="text-muted small mt-2 mb-0 text-center" id="sendJobsProgressLabel">Sending…</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="confirmSendJobs">
                            <i class="fas fa-paper-plane me-1"></i> Send Jobs
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Duplicate confirmation modal --}}
        <div class="modal fade" id="duplicateAutomationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-body text-center py-4 px-4">
                        <div class="mb-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10" style="width:52px;height:52px;">
                                <i class="fas fa-copy text-primary fs-3"></i>
                            </span>
                        </div>
                        <h6 class="fw-semibold mb-1">Duplicate this automation?</h6>
                        <p class="text-muted small mb-4" id="duplicateAutomationLabel"></p>
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary px-4" id="confirmDuplicateAutomation">
                                <i class="fas fa-copy me-1"></i> Duplicate
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('footer')
<script>
$(function () {
    // Toggle on/off
    $(document).on('change', '.automation-toggle', function () {
        const $toggle  = $(this);
        const $row     = $toggle.closest('.automation-row');
        const $badge   = $row.find('.automation-status-badge');
        const url      = $toggle.data('url');
        const active   = $toggle.is(':checked');

        $httpClient.make().post(url)
            .then(({ data: resp }) => {
                const isActive = resp.data?.is_active ?? active;
                $badge
                    .text(isActive ? 'Active' : 'Inactive')
                    .removeClass('bg-success-subtle text-success bg-secondary-subtle text-secondary')
                    .addClass(isActive ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary');
                $toggle.prop('checked', isActive);
                Botble.showSuccess(isActive ? 'Automation enabled.' : 'Automation disabled.');
            })
            .catch(() => {
                $toggle.prop('checked', !active); // revert on error
                Botble.showError('Could not update automation.');
            });
    });

    // Send Jobs
    const sendJobsModal = new bootstrap.Modal(document.getElementById('sendJobsModal'));
    let pendingSend = null;

    // Highlight selected period option visually
    $(document).on('change', 'input[name="sendPeriod"]', function () {
        $('.send-period-option').removeClass('border-primary bg-primary-subtle');
        $(this).closest('.send-period-option').addClass('border-primary bg-primary-subtle');
    });

    $(document).on('click', '.automation-send-jobs', function () {
        pendingSend = {
            url:  $(this).data('url'),
            name: $(this).data('name'),
        };
        $('#sendJobsModalName').text(pendingSend.name);
        $('input[name="sendPeriod"][value="yesterday"]').prop('checked', true).trigger('change');
        $('#sendJobsProgress').hide();
        $('#confirmSendJobs').prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Send Jobs');
        sendJobsModal.show();
    });

    $('#confirmSendJobs').on('click', function () {
        if (!pendingSend) return;
        const $btn   = $(this);
        const period = $('input[name="sendPeriod"]:checked').val();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Sending…');
        $('#sendJobsProgress').show();
        $('#sendJobsProgressLabel').text('Sending jobs for "' + period + '" to ' + pendingSend.name + '…');

        const body = new FormData();
        body.append('_token', $('meta[name="csrf-token"]').attr('content'));
        body.append('period', period);

        fetch(pendingSend.url, { method: 'POST', body })
            .then(r => r.json())
            .then(resp => {
                if (resp.error) {
                    Botble.showError(resp.message || 'Send failed.');
                } else {
                    Botble.showSuccess(resp.message || 'Jobs sent successfully.');
                    sendJobsModal.hide();
                    pendingSend = null;
                }
            })
            .catch(() => Botble.showError('Request failed. Check server logs.'))
            .finally(() => {
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Send Jobs');
                $('#sendJobsProgress').hide();
            });
    });

    // Delete
    const deleteAutomationModal    = new bootstrap.Modal(document.getElementById('deleteAutomationModal'));
    const duplicateAutomationModal = new bootstrap.Modal(document.getElementById('duplicateAutomationModal'));
    let pendingDelete    = null;
    let pendingDuplicate = null;

    $(document).on('click', '.automation-delete', function () {
        pendingDelete = {
            url:  $(this).data('url'),
            name: $(this).data('name'),
            row:  $(this).closest('.automation-row'),
        };
        $('#deleteAutomationLabel').text('"' + pendingDelete.name + '" will be permanently removed. This cannot be undone.');
        deleteAutomationModal.show();
    });

    $('#confirmDeleteAutomation').on('click', function () {
        if (!pendingDelete) return;
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Deleting…');
        const { url, row } = pendingDelete;
        $httpClient.make().delete(url)
            .then(() => {
                row.fadeOut(200, () => row.remove());
                Botble.showSuccess('Automation deleted.');
                deleteAutomationModal.hide();
                pendingDelete = null;
            })
            .catch(() => Botble.showError('Could not delete automation.'))
            .finally(() => $btn.prop('disabled', false).html('<i class="fas fa-trash me-1"></i> Delete'));
    });

    // Duplicate
    $(document).on('click', '.automation-duplicate', function () {
        pendingDuplicate = {
            url:  $(this).data('url'),
            name: $(this).data('name'),
        };
        $('#duplicateAutomationLabel').text('A disabled copy of "' + pendingDuplicate.name + '" will be created with an incremented name.');
        duplicateAutomationModal.show();
    });

    $('#confirmDuplicateAutomation').on('click', function () {
        if (!pendingDuplicate) return;
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Duplicating…');
        $httpClient.make().post(pendingDuplicate.url)
            .then(({ data: resp }) => {
                Botble.showSuccess(resp.message || 'Duplicated successfully.');
                duplicateAutomationModal.hide();
                pendingDuplicate = null;
                setTimeout(() => location.reload(), 800);
            })
            .catch(() => Botble.showError('Could not duplicate automation.'))
            .finally(() => $btn.prop('disabled', false).html('<i class="fas fa-copy me-1"></i> Duplicate'));
    });

    // Whapi: fetch newsletters into channel dropdown
    $(document).on('click', '.whapi-fetch-btn', function () {
        const $btn          = $(this);
        const $form         = $btn.closest('.modal-content, form');
        const $select       = $form.find('.whapi-channel-select');
        const automationId  = $select.data('automation-id') || '';
        const fetchUrl      = $select.data('fetch-url');
        const savedJid      = $select.data('saved-jid') || $select.val() || '';
        const token         = $form.find('input[name="settings[token]"]').val() || '';
        const gatewayUrl    = $form.find('input[name="settings[gateway_url]"]').val() || '';

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Fetching…');

        const body = new FormData();
        body.append('_token', $('meta[name="csrf-token"]').attr('content'));
        if (token)        body.append('token', token);
        if (automationId) body.append('automation_id', automationId);
        if (gatewayUrl)   body.append('gateway_url', gatewayUrl);

        fetch(fetchUrl, { method: 'POST', body })
            .then(r => r.json())
            .then(resp => {
                if (resp.error) {
                    Botble.showError(resp.error);
                    return;
                }
                const channels = resp.channels || [];
                if (!channels.length) {
                    Botble.showError('No newsletters found for this token. Make sure the channel is connected in Whapi.');
                    return;
                }

                let opts = '<option value="">— Select newsletter —</option>';
                channels.forEach(ch => {
                    const sel = ch.id === savedJid ? ' selected' : '';
                    opts += `<option value="${ch.id}" data-channel-name="${ch.name}"${sel}>${ch.name} — ${ch.id}</option>`;
                });
                $select.html(opts);

                // Re-select saved JID if it's in the list
                if (savedJid) $select.val(savedJid);

                // Auto-fill Display Name if it's still blank
                const $nameInput = $form.find('input[name="name"]');
                const selected   = $select.find('option:selected');
                if ($nameInput.val() === '' && selected.val()) {
                    $nameInput.val(selected.data('channel-name') || selected.text().split(' — ')[0]);
                }

                Botble.showSuccess(channels.length + ' newsletter(s) loaded. Select one from the dropdown.');
            })
            .catch(() => Botble.showError('Failed to reach Whapi. Check your network and token.'))
            .finally(() => $btn.prop('disabled', false).html('<i class="fas fa-sync me-1"></i> Fetch'));
    });

    // Whapi channel select → update Display Name when user picks a different channel
    $(document).on('change', '.whapi-channel-select', function () {
        const $option    = $(this).find('option:selected');
        const channelName = $option.data('channel-name') || $option.text().split(' — ')[0];
        if (!channelName || $option.val() === '') return;
        const $nameInput = $(this).closest('.modal-content, form').find('input[name="name"]');
        $nameInput.val(channelName);
    });

    // Re-submit edit form: preserve existing password values if left blank
    $('form[action*="automations"]').on('submit', function () {
        $(this).find('input[type="password"]').each(function () {
            if (!$(this).val()) {
                $(this).removeAttr('required');
                // don't send empty password fields so the backend keeps the existing value
                $(this).prop('disabled', true);
            }
        });
    });

    // Delete All Chats
    $('#btn-clear-chats').on('click', function () {
        const $btn = $(this);
        $('#automationConfirmMsg').text('Delete all tracked Telegram messages from all chats? This cannot be undone.');
        $('#automationConfirmOkBtn').data('callback', function () {
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Deleting…');
            $httpClient.make().post($btn.data('url'))
                .then(({ data: resp }) => { Botble.showSuccess(resp.message || 'All chats cleared.'); })
                .catch(() => Botble.showError('Failed to delete chats. Check the bot token in settings.'))
                .finally(() => $btn.prop('disabled', false).html('<i class="fas fa-trash me-1"></i> Delete All Chats'));
        });
        new bootstrap.Modal(document.getElementById('automationConfirmModal')).show();
    });

    // Regenerate Today's Jobs
    $('#btn-regen-today').on('click', function () {
        const $btn = $(this);
        $('#automationConfirmMsg').text("This will re-post all of today's jobs to all active Telegram automations. Continue?");
        $('#automationConfirmOkBtn').data('callback', function () {
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Posting…');
            $httpClient.make().post($btn.data('url'))
                .then(({ data: resp }) => { Botble.showSuccess(resp.message || "Today's jobs sent."); })
                .catch(() => Botble.showError('Failed to regenerate jobs.'))
                .finally(() => $btn.prop('disabled', false).html('<i class="fas fa-sync me-1"></i> Regenerate Today\'s Jobs'));
        });
        new bootstrap.Modal(document.getElementById('automationConfirmModal')).show();
    });

    // WhatsApp Channel: Send Yesterday's Jobs — dedicated modal
    $('#btn-whapi-yesterday').on('click', function () {
        $('input[name="whapiLimit"][value="2"]').prop('checked', true);
        new bootstrap.Modal(document.getElementById('whapiSendModal')).show();
    });

    $('#whapiSendConfirmBtn').on('click', function () {
        const $btn    = $('#btn-whapi-yesterday');
        const $self   = $(this);
        const limit   = $('input[name="whapiLimit"]:checked').val() || '0';

        $self.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Sending…');

        $httpClient.make().post($btn.data('url'), { limit: limit })
            .then(({ data: resp }) => {
                Botble.showSuccess(resp.message || "Jobs sent to WhatsApp.");
                bootstrap.Modal.getInstance(document.getElementById('whapiSendModal'))?.hide();
            })
            .catch(() => Botble.showError('Failed to send WhatsApp jobs. Check Whapi settings.'))
            .finally(() => $self.prop('disabled', false).html('Send Jobs'));
    });

    $('#automationConfirmOkBtn').on('click', function () {
        bootstrap.Modal.getInstance(document.getElementById('automationConfirmModal'))?.hide();
        const cb = $(this).data('callback');
        if (typeof cb === 'function') cb();
    });
});
</script>
@endpush

@push('footer')
<div class="modal fade" id="automationConfirmModal" tabindex="-1" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="automationConfirmMsg" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="automationConfirmOkBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

{{-- Dedicated Whapi send modal with limit selector --}}
<div class="modal fade" id="whapiSendModal" tabindex="-1" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="fab fa-whatsapp text-success"></i> Send Yesterday's Jobs to WhatsApp Channel
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3 text-muted small">Jobs will be sent to all active Whapi automations filtered by their country setting.</p>
                <label class="form-label fw-semibold">How many jobs to send?</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="whapiLimit" id="whapiLimit2" value="2">
                        <label class="form-check-label" for="whapiLimit2">2 <span class="text-muted small">(test)</span></label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="whapiLimit" id="whapiLimit5" value="5">
                        <label class="form-check-label" for="whapiLimit5">5</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="whapiLimit" id="whapiLimit10" value="10">
                        <label class="form-check-label" for="whapiLimit10">10</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="whapiLimit" id="whapiLimitAll" value="0">
                        <label class="form-check-label" for="whapiLimitAll">All</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="whapiSendConfirmBtn">
                    <i class="fab fa-whatsapp me-1"></i> Send Jobs
                </button>
            </div>
        </div>
    </div>
</div>
@endpush
