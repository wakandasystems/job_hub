@extends($layout ?? BaseHelper::getAdminMasterLayoutTemplate())

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-10">

        {{-- Back + title --}}
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="{{ route('newsletter.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-arrow-left me-1"></i>Back
            </a>
            <div>
                <h4 class="mb-0">Recipients — {{ $send->subject }}</h4>
                <div class="text-muted small">
                    Send #{{ $send->id }} &middot; {{ \Carbon\Carbon::parse($send->sent_at)->format('d M Y, H:i') }}
                </div>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="row g-3 mb-4">
            <div class="col-4">
                <div class="card text-center border-0 shadow-sm py-3">
                    <div class="fs-3 fw-bold text-primary">{{ number_format($send->recipient_count ?? 0) }}</div>
                    <div class="text-muted small">Total Recipients</div>
                </div>
            </div>
            <div class="col-4">
                <div class="card text-center border-0 shadow-sm py-3">
                    <div class="fs-3 fw-bold text-success">{{ number_format($counts['sent'] ?? 0) }}</div>
                    <div class="text-muted small">Delivered</div>
                </div>
            </div>
            <div class="col-4">
                <div class="card text-center border-0 shadow-sm py-3">
                    <div class="fs-3 fw-bold text-danger">{{ number_format($counts['failed'] ?? 0) }}</div>
                    <div class="text-muted small">Failed</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                {{-- Filter tabs --}}
                <ul class="nav nav-pills nav-sm mb-0">
                    <li class="nav-item">
                        <a class="nav-link py-1 px-3 @if($status === 'all') active @endif"
                           href="{{ route('newsletter.send.recipients', $send->id) }}">
                            All
                            <span class="badge bg-secondary ms-1 text-white">
                                {{ number_format(($counts['sent'] ?? 0) + ($counts['failed'] ?? 0)) }}
                            </span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-1 px-3 @if($status === 'sent') active @endif"
                           href="{{ route('newsletter.send.recipients', [$send->id, 'status' => 'sent']) }}">
                            Delivered
                            <span class="badge bg-success ms-1 text-white">{{ number_format($counts['sent'] ?? 0) }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-1 px-3 @if($status === 'failed') active @endif"
                           href="{{ route('newsletter.send.recipients', [$send->id, 'status' => 'failed']) }}">
                            Failed
                            <span class="badge bg-danger ms-1 text-white">{{ number_format($counts['failed'] ?? 0) }}</span>
                        </a>
                    </li>
                </ul>

                @if(($counts['failed'] ?? 0) > 0)
                <a href="{{ route('newsletter.send.recipients', [$send->id, 'export' => 'csv']) }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-download me-1"></i>Export Failed CSV
                </a>
                @endif
            </div>

            @if($recipients->isNotEmpty())
            <div class="card-table">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Email</th>
                                <th>Name</th>
                                <th class="text-center" style="width:90px">Status</th>
                                <th>Error</th>
                                <th style="width:140px">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recipients as $r)
                            <tr>
                                <td class="fw-medium">{{ $r->email }}</td>
                                <td class="text-muted">{{ $r->name ?: '—' }}</td>
                                <td class="text-center">
                                    @if($r->status === 'sent')
                                        <span class="badge bg-success text-white">Sent</span>
                                    @else
                                        <span class="badge bg-danger text-white">Failed</span>
                                    @endif
                                </td>
                                <td>
                                    @if($r->error_message)
                                        <span class="text-danger small font-monospace"
                                              title="{{ $r->error_message }}"
                                              style="cursor:help">
                                            {{ \Illuminate\Support\Str::limit($r->error_message, 80) }}
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ \Carbon\Carbon::parse($r->created_at)->format('d M Y, H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($recipients->hasPages())
            <div class="card-footer d-flex justify-content-between align-items-center gap-2">
                <div class="text-muted small">
                    Showing {{ $recipients->firstItem() }}–{{ $recipients->lastItem() }} of {{ $recipients->total() }}
                </div>
                {{ $recipients->links('pagination::bootstrap-5') }}
            </div>
            @endif

            @else
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-inbox fs-1 opacity-25 d-block mb-2"></i>
                No recipients found for this filter.
            </div>
            @endif
        </div>

    </div>
</div>

@endsection
