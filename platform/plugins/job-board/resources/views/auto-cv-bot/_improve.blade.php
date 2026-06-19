@php
    $toImprove = collect($session->section_scores ?: [])
        ->filter(fn ($section) => (int) ($section['score'] ?? 0) < 90 && trim((string) ($section['improve'] ?? '')) !== '');
@endphp
@if ($toImprove->isEmpty())
    <p class="text-muted small mb-0">Nothing to improve yet — looking good so far.</p>
@else
    <ul class="mb-0 ps-3">
        @foreach ($toImprove as $section)
            <li class="small mb-1">
                <strong>{{ $section['label'] ?? '' }}:</strong> {{ $section['improve'] }}
            </li>
        @endforeach
    </ul>
@endif
