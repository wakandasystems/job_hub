@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-4 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Total Keys</div>
                    <div class="h2 mb-0">{{ number_format($stats['total']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-4 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Active</div>
                    <div class="h2 mb-0">{{ number_format($stats['active']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
        <div class="col-md-4 col-6">
            <x-core::card>
                <x-core::card.body>
                    <div class="text-muted">Requests This Month</div>
                    <div class="h2 mb-0">{{ number_format($stats['total_reqs']) }}</div>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>

    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>Salary API Keys</x-core::card.title>
            <div class="card-options">
                <a href="{{ route('salary-api-keys.create') }}" class="btn btn-primary btn-sm">
                    <i class="ti ti-plus me-1"></i>New API Key
                </a>
            </div>
        </x-core::card.header>
        <x-core::card.body>
            <div class="alert alert-info mb-3">
                <i class="ti ti-info-circle me-1"></i>
                API clients must send <code>X-API-Key: &lt;key&gt;</code> header with requests to
                <code>{{ url('/api/v1/salary/') }}*</code>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter table-striped">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th>Name / Contact</th>
                            <th>Key Prefix</th>
                            <th>Plan</th>
                            <th>Usage (this month)</th>
                            <th>Status</th>
                            <th>Expires</th>
                            <th width="100"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($keys as $key)
                            <tr>
                                <td>#{{ $key->id }}</td>
                                <td>
                                    <strong>{{ $key->name }}</strong>
                                    @if($key->contact_name)
                                        <div class="text-muted small">{{ $key->contact_name }} — {{ $key->contact_email }}</div>
                                    @endif
                                </td>
                                <td><code>{{ $key->key_prefix }}…</code></td>
                                <td>
                                    <span class="badge bg-{{ ['basic' => 'secondary', 'pro' => 'primary', 'enterprise' => 'warning'][$key->plan] ?? 'secondary' }} text-white">
                                        {{ ucfirst($key->plan) }}
                                    </span>
                                </td>
                                <td>
                                    {{ number_format($key->requests_this_month) }} / {{ number_format($key->requests_per_month) }}
                                    <div class="progress mt-1" style="height:4px">
                                        @php $pct = $key->requests_per_month > 0 ? min(100, round($key->requests_this_month / $key->requests_per_month * 100)) : 0; @endphp
                                        <div class="progress-bar bg-{{ $pct >= 90 ? 'danger' : ($pct >= 70 ? 'warning' : 'success') }}"
                                            style="width:{{ $pct }}%"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $key->is_active ? 'success' : 'danger' }} text-white">
                                        {{ $key->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $key->expires_at?->toDateString() ?: 'Never' }}</td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('salary-api-keys.edit', $key) }}"
                                            class="btn btn-sm btn-primary btn-icon"
                                            data-bs-toggle="tooltip" data-bs-title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-danger btn-icon"
                                            data-bs-toggle="modal"
                                            data-bs-target="#delete-key-modal"
                                            data-action="{{ route('salary-api-keys.destroy', $key) }}"
                                            data-label="{{ $key->name }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No API keys yet. <a href="{{ route('salary-api-keys.create') }}">Create one</a>.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $keys->links() }}
        </x-core::card.body>
    </x-core::card>
@endsection

<div class="modal fade" id="delete-key-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger">Revoke API Key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Permanently revoke and delete key for:</p>
                <p class="fw-semibold" id="delete-key-label"></p>
                <p class="text-muted small mb-0">The client will immediately lose API access. This cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="delete-key-form" method="POST" action="">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">Yes, revoke it</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('footer')
<script>
    document.getElementById('delete-key-modal').addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        document.getElementById('delete-key-form').action = btn.dataset.action;
        document.getElementById('delete-key-label').textContent = btn.dataset.label;
    });
</script>
@endpush
