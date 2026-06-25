@forelse ($session->messages as $message)
    @if ($message->direction === 'admin')
        <div class="mb-2 text-center" data-message-id="{{ $message->id }}">
            <span class="badge bg-warning text-dark"><i class="ti ti-user-shield me-1"></i>Admin</span>
            <div class="d-inline-block rounded p-2 small bg-warning-subtle border" style="max-width:90%">
                {{ $message->body }}
            </div>
            <div class="text-muted" style="font-size:11px">{{ $message->created_at->format('d M H:i') }}</div>
        </div>
    @else
        <div class="mb-2 {{ $message->direction === 'outbound' ? 'text-end' : '' }}" data-message-id="{{ $message->id }}">
            <div class="d-inline-block rounded p-2 small {{ $message->direction === 'outbound' ? 'bg-dark text-white' : 'bg-light border' }}" style="max-width:90%">
                {{ $message->body }}
            </div>
            <div class="text-muted" style="font-size:11px">{{ $message->created_at->format('d M H:i') }}</div>
        </div>
    @endif
@empty
    <p class="text-muted small mb-0">No messages yet.</p>
@endforelse
