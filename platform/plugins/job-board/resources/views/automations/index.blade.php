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
                    <div class="d-flex align-items-center gap-3">
                        <i class="ti ti-info-circle text-primary fs-4"></i>
                        <div>
                            <strong>How automations work:</strong>
                            When a job is published — whether posted directly by an admin or imported by a crawler agent — it is automatically shared to all <strong>active</strong> automations below.
                            Toggle the switch on each automation to enable or disable it.
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
                                <i class="ti ti-plus me-1"></i> Add
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
                                        class="btn btn-outline-secondary btn-icon btn-sm"
                                        title="Edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modal-edit-{{ $automation->id }}">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <button type="button"
                                        class="btn btn-outline-danger btn-icon btn-sm automation-delete"
                                        title="Delete"
                                        data-url="{{ route('job-board.automations.destroy', $automation->id) }}"
                                        data-name="{{ $automation->name }}">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4 small">
                                <i class="ti ti-plug-x d-block mb-1 fs-4"></i>
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
                                        <label class="form-label fw-semibold">{{ $field['label'] }} <span class="text-danger">*</span></label>
                                        <input
                                            type="{{ $field['type'] ?? 'text' }}"
                                            name="settings[{{ $fieldKey }}]"
                                            class="form-control"
                                            placeholder="{{ $field['placeholder'] }}"
                                            required>
                                        @if(!empty($field['help']))
                                            <div class="form-text text-muted">{{ $field['help'] }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-plus me-1"></i> Add Automation
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
                                            <label class="form-label fw-semibold">{{ $field['label'] }}</label>
                                            <input
                                                type="{{ $field['type'] ?? 'text' }}"
                                                name="settings[{{ $fieldKey }}]"
                                                class="form-control"
                                                placeholder="{{ $field['placeholder'] }}"
                                                value="{{ ($field['type'] ?? 'text') === 'password' ? '' : ($automation->settings[$fieldKey] ?? '') }}">
                                            @if(!empty($field['help']))
                                                <div class="form-text text-muted">{{ $field['help'] }}</div>
                                            @endif
                                            @if(($field['type'] ?? 'text') === 'password' && !empty($automation->settings[$fieldKey]))
                                                <div class="form-text text-success"><i class="ti ti-check me-1"></i>Token is saved. Enter a new value to replace it.</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-device-floppy me-1"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @endforeach

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

    // Delete
    $(document).on('click', '.automation-delete', function () {
        const url  = $(this).data('url');
        const name = $(this).data('name');
        const $row = $(this).closest('.automation-row');

        if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;

        $httpClient.make().delete(url)
            .then(() => {
                $row.fadeOut(200, () => $row.remove());
                Botble.showSuccess('Automation deleted.');
            })
            .catch(() => Botble.showError('Could not delete automation.'));
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
});
</script>
@endpush
