@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    @php
        $platforms = [
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
                    'channel_id'  => ['label' => 'Channel / Newsletter', 'type' => 'whapi_channel_select', 'help' => 'Save the shared Whapi token, then click <strong>Fetch</strong> to load your newsletters.'],
                    'country_id'  => ['label' => 'Country Filter (optional)', 'type' => 'country_select', 'required' => false, 'help' => 'Only jobs matching this country will be posted. Leave blank to post jobs from all countries.'],
                    'gateway_url' => ['label' => 'Gateway URL (optional)', 'placeholder' => 'https://gate.whapi.cloud', 'required' => false, 'help' => 'Your Whapi gateway URL. Leave blank to use the default https://gate.whapi.cloud'],
                    'send_image'  => ['label' => 'Attach job image with each post', 'type' => 'checkbox', 'required' => false, 'help' => 'Sends the job image (company logo or generated banner) alongside the message.'],
                ],
            ],
            'publer' => [
                'label' => 'Publer',
                'icon'  => 'ti ti-share',
                'color' => '#5B4FE9',
                'fields' => [
                    'api_key'      => ['label' => 'API Key', 'placeholder' => 'Leave blank to use the global PUBLER_API_KEY', 'type' => 'password', 'required' => false, 'help' => 'Your Publer API key. Found at <a href="https://publer.com/settings/api" target="_blank" rel="noopener">publer.com → Settings → Access & Login → API Keys</a>. Leave blank to use the site-wide key from <code>.env</code>.'],
                    'workspace_id' => ['label' => 'Workspace ID', 'placeholder' => 'Auto-filled when you Fetch accounts', 'required' => false, 'help' => 'Leave blank — it is filled automatically when you click Fetch.'],
                    'account_ids'  => ['label' => 'Connected Accounts (Facebook, LinkedIn, TikTok…)', 'type' => 'publer_account_select', 'help' => 'Click <strong>Fetch</strong> to load your Publer-connected social accounts. Hold Ctrl / Cmd to select multiple.'],
                    'country_id'   => ['label' => 'Country Filter (optional)', 'type' => 'country_select', 'required' => false, 'help' => 'Only jobs for this country will be posted. Leave blank to post jobs from all countries.'],
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
                            @if($platformKey === 'whapi')
                                <button type="button"
                                    class="btn btn-outline-success btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#whapiTokenModal">
                                    <i class="fas fa-key me-1"></i> Token
                                </button>
                            @endif
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
                                    @if($platformKey === 'publer')
                                    <button type="button"
                                        class="btn btn-ghost-info btn-icon btn-sm publer-test-job"
                                        title="Test: send one job to Publer"
                                        data-url="{{ route('job-board.automations.publer-test-job', $automation->id) }}"
                                        data-name="{{ $automation->name }}"
                                        data-country-id="{{ $automation->settings['country_id'] ?? '' }}">
                                        <i class="fas fa-flask"></i>
                                    </button>
                                    <button type="button"
                                        class="btn btn-ghost-success btn-icon btn-sm publer-send-jobs"
                                        title="Publish jobs via Publer"
                                        data-url="{{ route('job-board.automations.publer-send-jobs', $automation->id) }}"
                                        data-name="{{ $automation->name }}">
                                        <i class="fas fa-paper-plane"></i>
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
                                        @elseif(($field['type'] ?? 'text') === 'publer_account_select')
                                            @php $pCtxNew = 'pacc-new'; @endphp
                                            <button type="button"
                                                    class="btn btn-link text-decoration-none text-dark p-0 d-flex align-items-center gap-2 w-100 mb-1"
                                                    data-bs-toggle="collapse" data-bs-target="#{{ $pCtxNew }}-collapse" aria-expanded="true">
                                                <i class="ti ti-share text-muted" style="width:14px"></i>
                                                <span class="fw-semibold small">{{ $field['label'] }}</span>
                                                <span class="badge bg-secondary ms-1" id="{{ $pCtxNew }}-badge">0</span>
                                                <i class="fas fa-chevron-down ms-auto text-muted small"></i>
                                            </button>
                                            <div class="collapse show" id="{{ $pCtxNew }}-collapse">
                                                <div class="pt-1 ps-3">
                                                    <div class="text-muted small mb-2">Jobs are published to all selected accounts. Click <strong>Fetch Accounts</strong> first to load your Publer connections.</div>
                                                    <div class="publer-accounts-list mb-2" id="{{ $pCtxNew }}-list"
                                                         data-context="{{ $pCtxNew }}" data-field-key="{{ $fieldKey }}">
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm publer-fetch-btn"
                                                                data-context="{{ $pCtxNew }}"
                                                                data-automation-id=""
                                                                data-fetch-url="{{ route('job-board.automations.publer-fetch-accounts') }}">
                                                            <i class="fas fa-sync me-1"></i> Fetch Accounts
                                                        </button>
                                                        <div class="dropdown publer-add-dropdown d-none" data-context="{{ $pCtxNew }}">
                                                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                                                <i class="fas fa-plus me-1"></i> Add Account
                                                            </button>
                                                            <ul class="dropdown-menu shadow-sm publer-add-menu" style="min-width:280px;max-height:300px;overflow-y:auto"></ul>
                                                        </div>
                                                        <div class="dropdown publer-quick-dropdown d-none" data-context="{{ $pCtxNew }}">
                                                            <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                                                <i class="fas fa-magic me-1"></i> Quick Add
                                                            </button>
                                                            <ul class="dropdown-menu shadow-sm publer-quick-menu" style="min-width:290px;max-height:300px;overflow-y:auto">
                                                                <li><h6 class="dropdown-header small">Accounts matching selected country</h6></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
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
                                            @elseif(($field['type'] ?? 'text') === 'publer_account_select')
                                                @php
                                                    $savedAccountIds = array_values(array_filter((array) ($automation->settings[$fieldKey] ?? [])));
                                                    $pCtxEdit = 'pacc-' . $automation->id;
                                                @endphp
                                                <button type="button"
                                                        class="btn btn-link text-decoration-none text-dark p-0 d-flex align-items-center gap-2 w-100 mb-1"
                                                        data-bs-toggle="collapse" data-bs-target="#{{ $pCtxEdit }}-collapse" aria-expanded="true">
                                                    <i class="ti ti-share text-muted" style="width:14px"></i>
                                                    <span class="fw-semibold small">{{ $field['label'] }}</span>
                                                    <span class="badge {{ count($savedAccountIds) ? 'bg-success' : 'bg-secondary' }} ms-1" id="{{ $pCtxEdit }}-badge">{{ count($savedAccountIds) }}</span>
                                                    <i class="fas fa-chevron-down ms-auto text-muted small"></i>
                                                </button>
                                                <div class="collapse show" id="{{ $pCtxEdit }}-collapse">
                                                    <div class="pt-1 ps-3">
                                                        <div class="text-muted small mb-2">Jobs are published to all selected accounts. Click <strong>Fetch Accounts</strong> to refresh names or add more.</div>
                                                        <div class="publer-accounts-list mb-2" id="{{ $pCtxEdit }}-list"
                                                             data-context="{{ $pCtxEdit }}" data-field-key="{{ $fieldKey }}"
                                                             data-saved-ids="{{ json_encode($savedAccountIds) }}">
                                                            @foreach($savedAccountIds as $accId)
                                                                <div class="input-group input-group-sm mb-1 publer-account-row" data-acc-id="{{ $accId }}">
                                                                    <span class="input-group-text publer-acc-icon"><i class="ti ti-share text-muted"></i></span>
                                                                    <span class="form-control form-control-sm publer-acc-label text-truncate text-muted fst-italic">{{ $accId }}</span>
                                                                    <input type="hidden" name="settings[{{ $fieldKey }}][]" value="{{ $accId }}">
                                                                    <button type="button" class="btn btn-outline-danger btn-sm publer-remove-acc" title="Remove">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                                            <button type="button" class="btn btn-outline-secondary btn-sm publer-fetch-btn"
                                                                    data-context="{{ $pCtxEdit }}"
                                                                    data-automation-id="{{ $automation->id }}"
                                                                    data-fetch-url="{{ route('job-board.automations.publer-fetch-accounts') }}">
                                                                <i class="fas fa-sync me-1"></i> Fetch Accounts
                                                            </button>
                                                            <div class="dropdown publer-add-dropdown d-none" data-context="{{ $pCtxEdit }}">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                                                    <i class="fas fa-plus me-1"></i> Add Account
                                                                </button>
                                                                <ul class="dropdown-menu shadow-sm publer-add-menu" style="min-width:280px;max-height:300px;overflow-y:auto"></ul>
                                                            </div>
                                                            <div class="dropdown publer-quick-dropdown d-none" data-context="{{ $pCtxEdit }}">
                                                                <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                                                    <i class="fas fa-magic me-1"></i> Quick Add
                                                                </button>
                                                                <ul class="dropdown-menu shadow-sm publer-quick-menu" style="min-width:290px;max-height:300px;overflow-y:auto">
                                                                    <li><h6 class="dropdown-header small">Accounts matching selected country</h6></li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
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

        <div class="modal fade" id="whapiTokenModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <form id="whapiTokenForm" action="{{ route('job-board.automations.whapi-token') }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-key text-success me-2"></i>Shared Whapi Token</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label fw-semibold">Whapi API Token</label>
                            <input type="password" name="token" class="form-control"
                                placeholder="{{ setting('whapi_api_token') ? 'Enter a new token to replace the saved token' : 'Paste your Whapi API token' }}"
                                required autocomplete="new-password">
                            @if(setting('whapi_api_token'))
                                <div class="form-text text-success">
                                    <i class="fas fa-check me-1"></i>A shared token is saved.
                                </div>
                            @endif
                            <div class="form-text">This token is used by every Whapi channel automation.</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i> Save Token
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

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

        fetch(pendingSend.url, {
            method: 'POST',
            body,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
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

    $('#whapiTokenForm').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const body = new FormData(this);

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving…');

        fetch($form.attr('action'), {
            method: 'POST',
            body,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(resp => {
                if (resp.error) {
                    Botble.showError(resp.message || 'Could not save the Whapi token.');
                    return;
                }

                Botble.showSuccess(resp.message || 'Shared Whapi token saved.');
                bootstrap.Modal.getInstance(document.getElementById('whapiTokenModal'))?.hide();
                $form[0].reset();
                setTimeout(() => location.reload(), 500);
            })
            .catch(() => Botble.showError('Could not save the Whapi token.'))
            .finally(() => $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Token'));
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

        fetch(fetchUrl, {
            method: 'POST',
            body,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(resp => {
                if (resp.error) {
                    Botble.showError(resp.error);
                    return;
                }
                const channels = resp.channels || [];
                if (!channels.length) {
                    const message = (resp.excluded_count || 0) > 0
                        ? 'All newsletters returned by Whapi are already configured.'
                        : 'No newsletters found for this token. Make sure the channel is connected in Whapi.';
                    Botble.showError(message);
                    return;
                }

                $select.empty().append(
                    $('<option>', {
                        value: '',
                        text: '— Select newsletter —',
                    })
                );

                channels.forEach(ch => {
                    const $option = $('<option>', {
                        value: ch.id,
                        text: `${ch.name} — ${ch.id}`,
                    }).attr('data-channel-name', ch.name);

                    if (ch.id === savedJid) {
                        $option.prop('selected', true);
                    }

                    $select.append($option);
                });

                // Re-select saved JID if it's in the list
                if (savedJid) $select.val(savedJid);

                // Auto-fill Display Name if it's still blank
                const $nameInput = $form.find('input[name="name"]');
                const selected   = $select.find('option:selected');
                if ($nameInput.val() === '' && selected.val()) {
                    $nameInput.val(selected.data('channel-name') || selected.text().split(' — ')[0]);
                }

                const excluded = resp.excluded_count || 0;
                const excludedText = excluded > 0 ? ` ${excluded} already configured newsletter(s) hidden.` : '';
                Botble.showSuccess(channels.length + ' newsletter(s) loaded.' + excludedText + ' Select one from the dropdown.');
            })
            .catch(() => Botble.showError('Failed to reach Whapi. Check your network and token.'))
            .finally(() => $btn.prop('disabled', false).html('<i class="fas fa-sync me-1"></i> Fetch'));
    });

    $('#modal-add-whapi').on('shown.bs.modal', function () {
        const $fetchButton = $(this).find('.whapi-fetch-btn');

        if (! $fetchButton.prop('disabled')) {
            $fetchButton.trigger('click');
        }
    });

    function normalizeCountryName(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim()
            .toLowerCase();
    }

    // Whapi channel select → update Display Name and country filter.
    $(document).on('change', '.whapi-channel-select', function () {
        const $select     = $(this);
        const $option     = $select.find('option:selected');
        const channelName = $option.data('channel-name') || $option.text().split(' — ')[0];
        if (!channelName || $option.val() === '') return;

        const $form = $select.closest('.modal-content, form');
        const $nameInput = $form.find('input[name="name"]');
        $nameInput.val(channelName);

        const countryMatch = channelName.match(/["“”']([^"“”']+)["“”']/);
        if (!countryMatch) return;

        const countryName = normalizeCountryName(countryMatch[1]);
        const $countrySelect = $form.find('select[name="settings[country_id]"]');
        const matchingOption = $countrySelect.find('option').filter(function () {
            return normalizeCountryName($(this).text()) === countryName;
        }).first();

        if (matchingOption.length) {
            $countrySelect.val(matchingOption.val()).trigger('change');
        }
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

    // ── Publer: account picker helpers ───────────────────────────────────────
    const publerFetchedAccounts = {};

    function publerPlatformIcon(typeLabel) {
        const t = (typeLabel || '').toLowerCase();
        if (t.includes('facebook'))  return '<i class="fab fa-facebook" style="color:#1877f2;width:14px"></i>';
        if (t.includes('linkedin'))  return '<i class="fab fa-linkedin" style="color:#0a66c2;width:14px"></i>';
        if (t.includes('tiktok'))    return '<i class="fab fa-tiktok" style="width:14px"></i>';
        if (t.includes('twitter') || t.includes(' x ') || t.includes('x:')) return '<i class="fab fa-twitter" style="color:#1da1f2;width:14px"></i>';
        if (t.includes('instagram')) return '<i class="fab fa-instagram" style="color:#e1306c;width:14px"></i>';
        return '<i class="ti ti-share text-muted" style="width:14px"></i>';
    }

    function publerAddAccountRow($list, acc) {
        const ctx = $list.data('context');
        const fk  = $list.data('field-key');
        if ($list.find(`.publer-account-row[data-acc-id="${acc.id}"]`).length) return;
        const label = (acc.type_label ? acc.type_label + ': ' : '') + acc.name;
        $list.append(`
            <div class="input-group input-group-sm mb-1 publer-account-row" data-acc-id="${acc.id}">
                <span class="input-group-text publer-acc-icon">${publerPlatformIcon(acc.type_label)}</span>
                <span class="form-control form-control-sm publer-acc-label text-truncate">${label}</span>
                <input type="hidden" name="settings[${fk}][]" value="${acc.id}">
                <button type="button" class="btn btn-outline-danger btn-sm publer-remove-acc" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            </div>`);
        publerUpdateBadge(ctx);
    }

    function publerUpdateBadge(ctx) {
        const count = $(`#${ctx}-list .publer-account-row`).length;
        $(`#${ctx}-badge`)
            .text(count)
            .removeClass('bg-secondary bg-success')
            .addClass(count > 0 ? 'bg-success' : 'bg-secondary');
    }

    function publerRebuildMenus(ctx, accounts) {
        const $addMenu = $(`.publer-add-dropdown[data-context="${ctx}"] .publer-add-menu`);
        $addMenu.empty();
        accounts.forEach(acc => {
            const locked = acc.locked ? ' 🔒' : '';
            $addMenu.append(`<li>
                <button type="button" class="dropdown-item small py-2 publer-add-acc-btn"
                        data-context="${ctx}" data-acc-id="${acc.id}">
                    ${publerPlatformIcon(acc.type_label)}
                    <span class="ms-2">${(acc.type_label ? acc.type_label + ': ' : '') + acc.name + locked}</span>
                </button></li>`);
        });
        $(`.publer-add-dropdown[data-context="${ctx}"]`).removeClass('d-none');
        publerRefreshQuickMenu(ctx, accounts);
        $(`.publer-quick-dropdown[data-context="${ctx}"]`).removeClass('d-none');
    }

    function publerRefreshQuickMenu(ctx, accounts) {
        if (!accounts) accounts = publerFetchedAccounts[ctx] || [];
        const $quickMenu = $(`.publer-quick-dropdown[data-context="${ctx}"] .publer-quick-menu`);
        const $form      = $(`#${ctx}-list`).closest('.modal-content, form');
        const $cSel      = $form.find('select[name="settings[country_id]"]');
        const countryName = $cSel.find('option:selected').text().trim();

        $quickMenu.empty().append('<li><h6 class="dropdown-header small">Accounts matching selected country</h6></li>');

        if (!countryName || countryName === '— All countries —') {
            $quickMenu.append('<li><span class="dropdown-item-text small text-muted">Select a country above to auto-match accounts.</span></li>');
            return;
        }

        const matched = accounts.filter(a => a.name.toLowerCase().includes(countryName.toLowerCase()));
        if (!matched.length) {
            $quickMenu.append(`<li><span class="dropdown-item-text small text-muted">No accounts found matching "${countryName}".</span></li>`);
            return;
        }

        $quickMenu.append(`<li>
            <button type="button" class="dropdown-item small py-2 fw-semibold publer-quick-all-btn"
                    data-context="${ctx}" data-country="${countryName}">
                <i class="fas fa-check-double me-1 text-success"></i>
                Add all ${matched.length} "${countryName}" account(s)
            </button></li><li><hr class="dropdown-divider"></li>`);

        matched.forEach(acc => {
            $quickMenu.append(`<li>
                <button type="button" class="dropdown-item small py-2 publer-add-acc-btn"
                        data-context="${ctx}" data-acc-id="${acc.id}">
                    ${publerPlatformIcon(acc.type_label)}
                    <span class="ms-2">${(acc.type_label ? acc.type_label + ': ' : '') + acc.name}</span>
                </button></li>`);
        });
    }

    // ── Publer: fetch connected accounts ─────────────────────────────────────
    $(document).on('click', '.publer-fetch-btn', function () {
        const $btn         = $(this);
        const ctx          = $btn.data('context');
        const $form        = $btn.closest('.modal-content, form');
        const fetchUrl     = $btn.data('fetch-url');
        const automationId = $btn.data('automation-id') || '';
        const apiKey       = $form.find('input[name="settings[api_key]"]').val() || '';

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Fetching…');

        const body = new FormData();
        body.append('_token', $('meta[name="csrf-token"]').attr('content'));
        if (apiKey)        body.append('api_key', apiKey);
        if (automationId)  body.append('automation_id', automationId);

        fetch(fetchUrl, {
            method: 'POST',
            body,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(resp => {
                if (resp.error) { Botble.showError(resp.error); return; }
                const accounts = resp.accounts || [];
                if (!accounts.length) {
                    Botble.showError('No accounts found in Publer. Connect social accounts at publer.com first.');
                    return;
                }
                if (resp.workspace_id) $form.find('input[name="settings[workspace_id]"]').val(resp.workspace_id);

                publerFetchedAccounts[ctx] = accounts;

                // Update labels on existing saved-ID rows that show raw IDs
                const $list = $(`#${ctx}-list`);
                $list.find('.publer-account-row').each(function () {
                    const accId = $(this).data('acc-id');
                    const acc   = accounts.find(a => a.id === String(accId));
                    if (acc) {
                        $(this).find('.publer-acc-icon').html(publerPlatformIcon(acc.type_label));
                        $(this).find('.publer-acc-label')
                            .text((acc.type_label ? acc.type_label + ': ' : '') + acc.name)
                            .removeClass('text-muted fst-italic');
                    }
                });

                publerRebuildMenus(ctx, accounts);
                Botble.showSuccess(accounts.length + ' account(s) loaded from Publer.');
            })
            .catch(() => Botble.showError('Failed to reach Publer. Check your API key and network.'))
            .finally(() => $btn.prop('disabled', false).html('<i class="fas fa-sync me-1"></i> Fetch Accounts'));
    });

    // Add single account
    $(document).on('click', '.publer-add-acc-btn', function () {
        const ctx     = $(this).data('context');
        const accId   = $(this).data('acc-id');
        const accounts = publerFetchedAccounts[ctx] || [];
        const acc = accounts.find(a => a.id === accId);
        if (acc) publerAddAccountRow($(`#${ctx}-list`), acc);
        $(this).closest('.dropdown').find('[data-bs-toggle="dropdown"]').dropdown('hide');
    });

    // Quick add: all country-matching accounts
    $(document).on('click', '.publer-quick-all-btn', function () {
        const ctx         = $(this).data('context');
        const countryName = $(this).data('country');
        const accounts    = publerFetchedAccounts[ctx] || [];
        const $list       = $(`#${ctx}-list`);
        const matched     = accounts.filter(a => a.name.toLowerCase().includes(countryName.toLowerCase()));
        matched.forEach(acc => publerAddAccountRow($list, acc));
        $(this).closest('.dropdown').find('[data-bs-toggle="dropdown"]').dropdown('hide');
        if (matched.length) Botble.showSuccess(matched.length + ' account(s) added for ' + countryName + '.');
    });

    // Remove account row
    $(document).on('click', '.publer-remove-acc', function () {
        const $list = $(this).closest('.publer-accounts-list');
        const ctx   = $list.data('context');
        $(this).closest('.publer-account-row').remove();
        publerUpdateBadge(ctx);
    });

    // Refresh Quick Add when country selection changes
    $(document).on('change', 'select[name="settings[country_id]"]', function () {
        $(this).closest('.modal-content, form').find('.publer-accounts-list').each(function () {
            publerRefreshQuickMenu($(this).data('context'));
        });
    });

    // ── Publer: test single job (search UI) ──────────────────────────────────
    const publerTestModal = new bootstrap.Modal(document.getElementById('publerTestJobModal'));
    const SEARCH_URL = '{{ route('job-board.automations.search-jobs') }}';
    let pendingTestUrl  = null;
    let pendingCountry  = null;
    let selectedJobId   = null;
    let searchTimer     = null;

    $(document).on('click', '.publer-test-job', function () {
        pendingTestUrl = $(this).data('url');
        pendingCountry = $(this).data('country-id') || null;

        $('#publerTestJobModalName').text($(this).data('name'));
        $('#publerTestJobSearch').val('');
        $('#publerTestJobList').empty();
        $('#publerTestJobPlaceholder').show();
        $('#publerTestJobError').addClass('d-none').text('');
        $('#publerTestJobSelected').addClass('d-none');
        $('#publerTestJobConfirmBtn').prop('disabled', true).html('<i class="fas fa-flask me-1"></i> Send Test');
        selectedJobId = null;

        publerTestModal.show();
        setTimeout(() => document.getElementById('publerTestJobSearch').focus(), 400);

        // Load recent jobs immediately so the list isn't empty
        publerJobSearch('');
    });

    function publerJobSearch(q) {
        const params = new URLSearchParams({ q });
        if (pendingCountry) params.append('country_id', pendingCountry);

        fetch(SEARCH_URL + '?' + params.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(resp => {
                const jobs = resp.jobs || [];
                const $list = $('#publerTestJobList');
                $('#publerTestJobPlaceholder').hide();
                $list.empty();

                if (!jobs.length) {
                    $list.html('<div class="text-muted small text-center py-3">No jobs found.</div>');
                    return;
                }

                jobs.forEach(function (job) {
                    const meta = [job.company, job.country].filter(Boolean).join(' · ');
                    const isSelected = job.id === selectedJobId;
                    $list.append(`
                        <div class="publer-job-result d-flex align-items-start gap-2 px-3 py-2 border-bottom
                                    ${isSelected ? 'bg-info-subtle' : ''}"
                             style="cursor:pointer" data-job-id="${job.id}"
                             data-title="${job.title}" data-meta="${meta}">
                            <span class="badge bg-secondary mt-1" style="font-size:.65rem;min-width:38px">#${job.id}</span>
                            <div class="min-w-0">
                                <div class="small fw-semibold text-truncate">${job.title}</div>
                                <div class="text-muted" style="font-size:.7rem">${meta}</div>
                            </div>
                            ${isSelected ? '<i class="fas fa-check-circle text-info ms-auto mt-1"></i>' : ''}
                        </div>`);
                });
            })
            .catch(() => {});
    }

    $('#publerTestJobSearch').on('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        searchTimer = setTimeout(() => publerJobSearch(q), 280);
    });

    $('#publerTestJobClear').on('click', function () {
        $('#publerTestJobSearch').val('').trigger('input').focus();
    });

    $(document).on('click', '.publer-job-result', function () {
        selectedJobId = $(this).data('job-id');
        const title   = $(this).data('title');
        const meta    = $(this).data('meta');

        // Highlight in list
        $('.publer-job-result').removeClass('bg-info-subtle').find('.fa-check-circle').remove();
        $(this).addClass('bg-info-subtle').append('<i class="fas fa-check-circle text-info ms-auto mt-1"></i>');

        // Show selected pill
        $('#publerTestSelectedTitle').text(title);
        $('#publerTestSelectedMeta').text('#' + selectedJobId + (meta ? '  ·  ' + meta : ''));
        $('#publerTestJobSelected').removeClass('d-none');

        $('#publerTestJobConfirmBtn').prop('disabled', false);
        $('#publerTestJobError').addClass('d-none');
    });

    $('#publerTestDeselectBtn').on('click', function () {
        selectedJobId = null;
        $('.publer-job-result').removeClass('bg-info-subtle').find('.fa-check-circle').remove();
        $('#publerTestJobSelected').addClass('d-none');
        $('#publerTestJobConfirmBtn').prop('disabled', true);
    });

    $('#publerTestJobConfirmBtn').on('click', function () {
        if (!selectedJobId) return;
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Sending…');
        $('#publerTestJobError').addClass('d-none');

        const body = new FormData();
        body.append('_token', $('meta[name="csrf-token"]').attr('content'));
        body.append('job_id', selectedJobId);

        fetch(pendingTestUrl, {
            method: 'POST',
            body,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(resp => {
                if (resp.error_code || resp.error) {
                    $('#publerTestJobError').removeClass('d-none').text(resp.message || 'Test failed.');
                    $btn.prop('disabled', false).html('<i class="fas fa-flask me-1"></i> Send Test');
                } else {
                    publerTestModal.hide();
                    Botble.showSuccess(resp.message || 'Test post sent via Publer!');
                }
            })
            .catch(() => {
                $('#publerTestJobError').removeClass('d-none').text('Request failed. Check server logs.');
                $btn.prop('disabled', false).html('<i class="fas fa-flask me-1"></i> Send Test');
            });
    });

    // ── Publer: send jobs modal ───────────────────────────────────────────────
    const publerSendModal = new bootstrap.Modal(document.getElementById('publerSendModal'));
    let pendingPublerSend = null;

    $(document).on('click', '.publer-send-jobs', function () {
        pendingPublerSend = {
            url:  $(this).data('url'),
            name: $(this).data('name'),
        };
        $('#publerSendModalName').text(pendingPublerSend.name);
        $('input[name="publerPeriod"][value="yesterday"]').prop('checked', true).trigger('change');
        $('#publerSendProgress').hide();
        $('#publerSendConfirmBtn').prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Publish Jobs');
        publerSendModal.show();
    });

    $(document).on('change', 'input[name="publerPeriod"]', function () {
        $('.publer-period-option').removeClass('border-primary bg-primary-subtle');
        $(this).closest('.publer-period-option').addClass('border-primary bg-primary-subtle');
    });

    $('#publerSendConfirmBtn').on('click', function () {
        if (!pendingPublerSend) return;
        const $btn   = $(this);
        const period = $('input[name="publerPeriod"]:checked').val();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Publishing…');
        $('#publerSendProgress').show();

        const body = new FormData();
        body.append('_token', $('meta[name="csrf-token"]').attr('content'));
        body.append('period', period);

        fetch(pendingPublerSend.url, {
            method: 'POST',
            body,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(resp => {
                if (resp.error) {
                    Botble.showError(resp.message || 'Publish failed.');
                } else {
                    Botble.showSuccess(resp.message || 'Jobs published via Publer.');
                    publerSendModal.hide();
                    pendingPublerSend = null;
                }
            })
            .catch(() => Botble.showError('Request failed. Check server logs.'))
            .finally(() => {
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Publish Jobs');
                $('#publerSendProgress').hide();
            });
    });
});
</script>
@endpush

@push('footer')
<div class="modal fade" id="publerTestJobModal" tabindex="-1" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title d-flex align-items-center gap-2">
                    <i class="fas fa-flask text-info"></i>
                    Test: <span id="publerTestJobModalName" class="text-truncate"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pb-2">
                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="publerTestJobSearch"
                           class="form-control"
                           placeholder="Search by job title, company, or ID…"
                           autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary" id="publerTestJobClear" title="Clear">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                {{-- Results list --}}
                <div id="publerTestJobResults"
                     style="max-height:280px;overflow-y:auto;border:1px solid #dee2e6;border-radius:.375rem">
                    <div class="text-muted small text-center py-4" id="publerTestJobPlaceholder">
                        <i class="fas fa-search d-block mb-1 opacity-25 fs-4"></i>
                        Type to search jobs
                    </div>
                    <div id="publerTestJobList"></div>
                </div>

                {{-- Selected job pill --}}
                <div id="publerTestJobSelected" class="d-none mt-2">
                    <div class="d-flex align-items-center gap-2 p-2 rounded border border-info bg-info-subtle">
                        <i class="fas fa-check-circle text-info"></i>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold small text-truncate" id="publerTestSelectedTitle"></div>
                            <div class="text-muted" style="font-size:.7rem" id="publerTestSelectedMeta"></div>
                        </div>
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted" id="publerTestDeselectBtn" title="Deselect">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div id="publerTestJobError" class="alert alert-danger mt-2 mb-0 py-2 small d-none" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info btn-sm text-white" id="publerTestJobConfirmBtn" disabled>
                    <i class="fas fa-flask me-1"></i> Send Test
                </button>
            </div>
        </div>
    </div>
</div>

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

{{-- Publer: send jobs period modal --}}
<div class="modal fade" id="publerSendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="ti ti-share text-primary"></i>
                    <span>Publish via Publer — <span id="publerSendModalName"></span></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Choose which jobs to publish through Publer to your connected social accounts.</p>
                <div class="d-flex flex-column gap-2">
                    @foreach([
                        'today'     => ['Today',                   'Jobs posted today',                          'ti ti-calendar-event text-primary'],
                        'yesterday' => ['Yesterday',               'Jobs posted yesterday',                      'ti ti-calendar-minus text-info'],
                        '7days'     => ['Last 7 days',             'Jobs posted in the past week',               'ti ti-calendar-week text-warning'],
                        '30days'    => ['Last 30 days',            'Jobs posted in the past month',              'ti ti-calendar-month text-orange'],
                        'active'    => ['All active (not expired)','All published jobs that haven\'t closed yet','ti ti-briefcase text-success'],
                    ] as $val => [$label, $desc, $icon])
                    <label class="d-flex align-items-center gap-3 p-3 border rounded cursor-pointer publer-period-option {{ $val === 'yesterday' ? 'border-primary bg-primary-subtle' : '' }}" style="cursor:pointer">
                        <input type="radio" name="publerPeriod" value="{{ $val }}" class="form-check-input mt-0 flex-shrink-0" {{ $val === 'yesterday' ? 'checked' : '' }}>
                        <i class="{{ $icon }} fs-4 flex-shrink-0"></i>
                        <div>
                            <div class="fw-semibold small">{{ $label }}</div>
                            <div class="text-muted" style="font-size:.78rem">{{ $desc }}</div>
                        </div>
                    </label>
                    @endforeach
                </div>
                <div id="publerSendProgress" class="mt-3" style="display:none">
                    <div class="progress" style="height:6px">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%;background:#5B4FE9"></div>
                    </div>
                    <p class="text-muted small mt-2 mb-0 text-center">Publishing to Publer…</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="publerSendConfirmBtn">
                    <i class="fas fa-paper-plane me-1"></i> Publish Jobs
                </button>
            </div>
        </div>
    </div>
</div>
@endpush
