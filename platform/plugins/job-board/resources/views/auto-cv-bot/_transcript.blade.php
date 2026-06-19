@forelse ($session->messages as $message)
    <div class="mb-2 {{ $message->direction === 'outbound' ? 'text-end' : '' }}" data-message-id="{{ $message->id }}">
        <div class="d-inline-block rounded p-2 small {{ $message->direction === 'outbound' ? 'bg-dark text-white' : 'bg-light border' }}" style="max-width:90%">
            {{ $message->body }}
        </div>
        <div class="text-muted" style="font-size:11px">{{ $message->created_at->format('d M H:i') }}</div>
    </div>
@empty
    <p class="text-muted small mb-0">No messages yet.</p>
@endforelse
