@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>Wakanda Verification Requests</x-core::card.title>
            <div class="ms-auto d-flex gap-2">
                @foreach(['pending' => 'warning', 'pending_payment' => 'info', 'approved' => 'success', 'rejected' => 'danger', 'all' => 'secondary'] as $s => $color)
                    <a href="{{ route('wakanda-verification.index', ['status' => $s]) }}"
                       class="btn btn-sm btn-{{ $status === $s ? $color : 'outline-' . $color }}">
                        {{ ucfirst($s) }}
                    </a>
                @endforeach
            </div>
        </x-core::card.header>
        <x-core::card.body>
            @if ($requests->isEmpty())
                <p class="text-muted text-center py-4">No requests found.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Candidate</th>
                                <th>Payment</th>
                                <th>Reference</th>
                                <th>Requested</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requests as $req)
                                <tr>
                                    <td>{{ $req->id }}</td>
                                    <td>
                                        <strong>{{ $req->account->name ?? '—' }}</strong> {!! $req->account?->wakandaBadgeHtml() !!}
                                    </td>
                                    <td>
                                        <div class="small">{{ $req->account->email ?? '—' }}</div>
                                        @if($req->payment_method)
                                            <div class="text-muted small">{{ ucwords(str_replace('_', ' ', $req->payment_method)) }}</div>
                                            @if($req->charge_id)
                                                <div class="text-muted small font-monospace">{{ $req->charge_id }}</div>
                                            @endif
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($req->payment_reference)
                                            <span class="badge bg-blue-lt" title="{{ $req->payment_reference }}">{{ $req->payment_reference }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $req->created_at->diffForHumans() }}</td>
                                    <td>
                                        @php $badges = ['pending' => 'warning', 'pending_payment' => 'info', 'approved' => 'success', 'rejected' => 'danger']; @endphp
                                        <span class="badge bg-{{ $badges[$req->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $req->status)) }}</span>
                                        @if ($req->status === 'approved')
                                            <small class="text-muted ms-1">Score: {{ $req->score }}/5</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($req->status === 'pending')
                                            <button class="btn btn-xs btn-success me-1"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#approveModal"
                                                    data-url="{{ route('wakanda-verification.approve', $req->id) }}"
                                                    data-name="{{ $req->account->name ?? '' }}">
                                                Approve
                                            </button>
                                            <button class="btn btn-xs btn-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#rejectModal"
                                                    data-url="{{ route('wakanda-verification.reject', $req->id) }}"
                                                    data-name="{{ $req->account->name ?? '' }}">
                                                Reject
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $requests->withQueryString()->links() }}
            @endif
        </x-core::card.body>
    </x-core::card>

    {{-- Approve Modal --}}
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Approve Verification</h5></div>
                <form id="approveForm" method="POST" action="">
                    @csrf
                    <div class="modal-body">
                        <p>Approve <strong id="approveName"></strong> and award the Wakanda badge.</p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Score (1–5)</label>
                            <select name="score" class="form-select">
                                @for ($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes (optional)</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Award Badge</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Reject Verification</h5></div>
                <form id="rejectForm" method="POST" action="">
                    @csrf
                    <div class="modal-body">
                        <p>Reject <strong id="rejectName"></strong>'s verification request.</p>
                        <div class="mb-3">
                            <label class="form-label">Reason (optional)</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('footer')
<script>
document.getElementById('approveModal').addEventListener('show.bs.modal', function (e) {
    var btn = e.relatedTarget;
    document.getElementById('approveForm').action = btn.dataset.url;
    document.getElementById('approveName').textContent = btn.dataset.name;
});
document.getElementById('rejectModal').addEventListener('show.bs.modal', function (e) {
    var btn = e.relatedTarget;
    document.getElementById('rejectForm').action = btn.dataset.url;
    document.getElementById('rejectName').textContent = btn.dataset.name;
});
</script>
@endpush
