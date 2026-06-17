@extends(BaseHelper::getAdminMasterLayoutTemplate())

@php
    $slotOptions = [
        'tiktok_image' => 'TikTok',
        'whatsapp_image' => 'WhatsApp',
        'facebook_image' => 'Facebook',
        'linkedin_image' => 'LinkedIn',
        'twitter_image' => 'X / Twitter',
        'cover_image' => 'Cover Image',
        'employer_image' => 'Employer Pitch',
    ];
    $statusOptions = [
        'success' => 'Success',
        'failed' => 'Failed',
    ];
    $sourceTypeOptions = [
        'job' => 'Jobs',
        'blog_post' => 'Blog Posts',
    ];
@endphp

@section('content')
<x-core::card>
    <x-core::card.header>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <x-core::card.title>AI Image Generation</x-core::card.title>

            <x-core::tab class="card-header-tabs">
                <x-core::tab.item id="ai-image-settings-tab" label="Settings" :isActive="request('tab', 'settings') !== 'logs'" />
                <x-core::tab.item id="ai-image-logs-tab" label="Logs & Stats" :isActive="request('tab') === 'logs'" />
            </x-core::tab>
        </div>
    </x-core::card.header>

    <x-core::card.body>
        <x-core::tab.content>
            <x-core::tab.pane id="ai-image-settings-tab" :isActive="request('tab', 'settings') !== 'logs'">
                <form method="POST" action="{{ route('job-board.settings.ai-images.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="alert alert-info">
                        Auto-generated job social images use the OpenAI Images API edit workflow, sending the Wakanda Jobs logo and
                        the company logo as reference images together with the prompt. The prompt page you copy from and the
                        automatic generator use the same prompt builder, but the generator now also follows the model, quality,
                        output-format, and background settings configured here.
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">OpenAI API Key</label>
                        <input type="password" class="form-control" name="openai_api_key" autocomplete="off"
                            placeholder="{{ $settings['openai_api_key'] ? '•••••••••• (leave blank to keep current)' : 'sk-...' }}">
                        <div class="form-text">
                            Stored securely. Leave blank to keep the existing key.
                            @if($settings['openai_api_key'])
                                <span class="badge bg-success-subtle text-success ms-1">A key is currently set</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger ms-1">No key set yet</span>
                            @endif
                        </div>
                    </div>

                    <div class="border rounded p-3 mb-4 {{ old('ai_social_image_enabled', $settings['ai_social_image_enabled']) ? 'border-success bg-success-subtle' : 'border-warning bg-warning-subtle' }}">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="form-check form-switch mb-0">
                                <input type="hidden" name="ai_social_image_enabled" value="0">
                                <input class="form-check-input" type="checkbox" id="ai_social_image_enabled"
                                    name="ai_social_image_enabled" value="1"
                                    {{ old('ai_social_image_enabled', $settings['ai_social_image_enabled']) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold fs-5" for="ai_social_image_enabled">
                                    Enable AI image generation <span class="text-muted fw-normal">(master switch)</span>
                                </label>
                            </div>
                            @if(old('ai_social_image_enabled', $settings['ai_social_image_enabled']))
                                <span class="badge bg-success">ON</span>
                            @else
                                <span class="badge bg-warning text-dark">OFF</span>
                            @endif
                        </div>
                        <div class="form-text mt-2 mb-0">
                            When <strong>off</strong>, no images are generated automatically when a job is published — zero OpenAI spend.
                            When <strong>on</strong>, every published job that matches the platform and country filters below is processed in the background.
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Model</label>
                            <select class="form-select" name="ai_social_image_model">
                                @foreach($models as $value => $label)
                                    <option value="{{ $value }}" {{ old('ai_social_image_model', $settings['ai_social_image_model']) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Recommended: <code>gpt-image-2</code> for better prompt-following and stronger logo/reference fidelity.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Quality</label>
                            <select class="form-select" name="ai_social_image_quality">
                                @foreach($qualities as $value => $label)
                                    <option value="{{ $value }}" {{ old('ai_social_image_quality', $settings['ai_social_image_quality']) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text"><code>High</code> is best for final ads. <code>Low</code> is cheaper and faster for drafts.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Output Format</label>
                            <select class="form-select" name="ai_social_image_output_format" id="ai_social_image_output_format">
                                @foreach($outputFormats as $value => $label)
                                    <option value="{{ $value }}" {{ old('ai_social_image_output_format', $settings['ai_social_image_output_format']) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text"><code>PNG</code> preserves sharp text overlays best. <code>JPEG</code> is smaller and faster.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Background</label>
                            <select class="form-select" name="ai_social_image_background">
                                @foreach($backgrounds as $value => $label)
                                    <option value="{{ $value }}" {{ old('ai_social_image_background', $settings['ai_social_image_background']) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Use <code>Opaque</code> for social ads. <code>Auto</code> lets the model decide.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">JPEG / WebP Compression</label>
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <input
                                    type="range"
                                    class="form-range"
                                    min="0"
                                    max="100"
                                    step="1"
                                    id="ai_social_image_output_compression"
                                    name="ai_social_image_output_compression"
                                    value="{{ old('ai_social_image_output_compression', $settings['ai_social_image_output_compression']) }}"
                                >
                            </div>
                            <div class="col-md-2">
                                <input
                                    type="number"
                                    class="form-control"
                                    min="0"
                                    max="100"
                                    step="1"
                                    id="ai_social_image_output_compression_number"
                                    value="{{ old('ai_social_image_output_compression', $settings['ai_social_image_output_compression']) }}"
                                >
                            </div>
                            <div class="col-md-6">
                                <div class="form-text mb-0">
                                    Used only for <code>JPEG</code> and <code>WebP</code>. Higher values compress more. <code>10</code> is a good starting point for social ads.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input type="hidden" name="ai_social_image_without_logo" value="0">
                                <input class="form-check-input" type="checkbox" id="ai_social_image_without_logo"
                                    name="ai_social_image_without_logo" value="1"
                                    {{ old('ai_social_image_without_logo', $settings['ai_social_image_without_logo']) ? 'checked' : '' }}>
                                <label class="form-check-label" for="ai_social_image_without_logo">
                                    Also generate for companies <strong>without</strong> a logo
                                </label>
                            </div>
                            <div class="form-text">When off, jobs whose company has no logo are skipped.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input type="hidden" name="ai_social_image_skip_multi_position" value="0">
                                <input class="form-check-input" type="checkbox" id="ai_social_image_skip_multi_position"
                                    name="ai_social_image_skip_multi_position" value="1"
                                    {{ old('ai_social_image_skip_multi_position', $settings['ai_social_image_skip_multi_position']) ? 'checked' : '' }}>
                                <label class="form-check-label" for="ai_social_image_skip_multi_position">
                                    <strong>Skip</strong> titles with multiple positions
                                </label>
                            </div>
                            <div class="form-text">e.g. "Driver x3", "Teacher (5)", "Accountant, Cleaner &amp; Guard".</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch mb-2">
                            <input type="hidden" name="ai_social_image_reuse_selected_platform_images" value="0">
                            <input class="form-check-input" type="checkbox" id="ai_social_image_reuse_selected_platform_images"
                                name="ai_social_image_reuse_selected_platform_images" value="1"
                                {{ old('ai_social_image_reuse_selected_platform_images', $settings['ai_social_image_reuse_selected_platform_images']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="ai_social_image_reuse_selected_platform_images">
                                Reuse selected platform images for unselected platforms
                            </label>
                        </div>
                        <div class="form-text">
                            Example: if only <strong>WhatsApp</strong> is selected, that same image is copied to TikTok, Facebook, and LinkedIn.
                            If <strong>WhatsApp</strong> and <strong>TikTok</strong> are selected, Facebook and LinkedIn will still reuse the WhatsApp image first.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold d-block">Platforms to generate</label>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach($platforms as $slot => $label)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="platform_{{ $slot }}"
                                        name="platforms[]" value="{{ $slot }}"
                                        {{ in_array($slot, old('platforms', $settings['ai_social_image_platforms']), true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="platform_{{ $slot }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold d-block">Countries to auto-generate for</label>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <input type="text" class="form-control form-control-sm w-auto" id="country-filter"
                                placeholder="Filter countries…">
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="country-select-all">Select all</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="country-clear-all">Clear</button>
                            </div>
                        </div>
                        <div class="row" id="country-grid" style="max-height:340px;overflow-y:auto;">
                            @foreach($countries as $id => $name)
                                <div class="col-md-4 col-sm-6 country-item">
                                    <div class="form-check">
                                        <input class="form-check-input country-checkbox" type="checkbox"
                                            id="country_{{ $id }}" name="country_ids[]" value="{{ $id }}"
                                            {{ in_array((int) $id, old('country_ids', $settings['ai_social_image_country_ids']), true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="country_{{ $id }}">{{ $name }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i> Save Settings
                        </button>
                    </div>
                </form>
            </x-core::tab.pane>

            <x-core::tab.pane id="ai-image-logs-tab" :isActive="request('tab') === 'logs'">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">Attempts</div>
                            <div class="fs-3 fw-semibold">{{ number_format($stats['total_attempts']) }}</div>
                            <div class="small text-muted mt-1">
                                {{ number_format($stats['successes']) }} success / {{ number_format($stats['failures']) }} failed
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">Estimated Cost</div>
                            <div class="fs-3 fw-semibold">${{ number_format($stats['estimated_cost_usd'], 4) }}</div>
                            <div class="small text-muted mt-1">Filtered result set</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">Avg Latency</div>
                            <div class="fs-3 fw-semibold">
                                {{ $stats['avg_latency_ms'] !== null ? number_format($stats['avg_latency_ms']) . ' ms' : '—' }}
                            </div>
                            <div class="small text-muted mt-1">
                                Success rate: {{ $stats['success_rate'] !== null ? number_format($stats['success_rate'], 1) . '%' : '—' }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">Tokens</div>
                            <div class="fs-3 fw-semibold">{{ number_format($stats['total_tokens']) }}</div>
                            <div class="small text-muted mt-1">
                                In: {{ number_format($stats['input_tokens']) }} / Out: {{ number_format($stats['output_tokens']) }}
                            </div>
                        </div>
                    </div>
                </div>

                <form method="GET" action="{{ route('job-board.settings.ai-images') }}" class="mb-4">
                    <input type="hidden" name="tab" value="logs">

                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="log_status" class="form-select">
                                <option value="">All</option>
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $logFilters['status'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Slot</label>
                            <select name="log_slot_type" class="form-select">
                                <option value="">All</option>
                                @foreach($slotOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $logFilters['slot_type'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Model</label>
                            <select name="log_model" class="form-select">
                                <option value="">All</option>
                                @foreach($models as $value => $label)
                                    <option value="{{ $value }}" {{ $logFilters['model'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Source</label>
                            <select name="log_source_type" class="form-select">
                                <option value="">All</option>
                                @foreach($sourceTypeOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $logFilters['source_type'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Job ID</label>
                            <input type="number" class="form-control" name="log_job_id" value="{{ $logFilters['job_id'] }}">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label fw-semibold">From</label>
                            <input type="date" class="form-control" name="log_date_from" value="{{ $logFilters['date_from'] }}">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label fw-semibold">To</label>
                            <input type="date" class="form-control" name="log_date_to" value="{{ $logFilters['date_to'] }}">
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-filter me-1"></i> Apply Filters
                        </button>
                        <a href="{{ route('job-board.settings.ai-images', ['tab' => 'logs']) }}" class="btn btn-outline-secondary">
                            Clear
                        </a>
                    </div>
                </form>

                <div class="table-responsive">
                    <x-core::table>
                        <x-core::table.header>
                            <x-core::table.header.cell>Time</x-core::table.header.cell>
                            <x-core::table.header.cell>Status</x-core::table.header.cell>
                            <x-core::table.header.cell>Source</x-core::table.header.cell>
                            <x-core::table.header.cell>Slot</x-core::table.header.cell>
                            <x-core::table.header.cell>Model</x-core::table.header.cell>
                            <x-core::table.header.cell>Cost</x-core::table.header.cell>
                            <x-core::table.header.cell>Tokens</x-core::table.header.cell>
                            <x-core::table.header.cell>Latency</x-core::table.header.cell>
                            <x-core::table.header.cell>Details</x-core::table.header.cell>
                        </x-core::table.header>
                        <x-core::table.body>
                            @forelse($logs as $log)
                                <x-core::table.body.row>
                                    <x-core::table.body.cell>
                                        <div class="fw-semibold">{{ $log->created_at?->format('d M Y H:i:s') }}</div>
                                        <div class="text-muted small">ID {{ $log->id }}</div>
                                    </x-core::table.body.cell>
                                    <x-core::table.body.cell>
                                        <span class="badge {{ $log->status === 'success' ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' }}">
                                            {{ ucfirst($log->status) }}
                                        </span>
                                    </x-core::table.body.cell>
                                    <x-core::table.body.cell>
                                        <div class="fw-semibold">{{ $log->source_title ?: $log->job?->name ?: 'Unknown source' }}</div>
                                        <div class="text-muted small">
                                            {{ $sourceTypeOptions[$log->source_type] ?? ucfirst(str_replace('_', ' ', $log->source_type ?: 'job')) }}
                                            #{{ $log->source_id ?: $log->job_id ?: '—' }}
                                            @if($log->company_id)
                                                · {{ $log->company?->name ?: 'Company #' . $log->company_id }}
                                            @endif
                                        </div>
                                    </x-core::table.body.cell>
                                    <x-core::table.body.cell>
                                        <div class="fw-semibold">{{ $slotOptions[$log->slot_type] ?? $log->slot_type }}</div>
                                        <div class="text-muted small">{{ $log->request_size ?: '—' }} → {{ $log->target_width && $log->target_height ? $log->target_width . '×' . $log->target_height : '—' }}</div>
                                    </x-core::table.body.cell>
                                    <x-core::table.body.cell>
                                        <div class="fw-semibold">{{ $models[$log->model] ?? $log->model ?: '—' }}</div>
                                        <div class="text-muted small">
                                            {{ $log->quality ?: '—' }} · {{ $log->output_format ?: '—' }}
                                        </div>
                                    </x-core::table.body.cell>
                                    <x-core::table.body.cell>
                                        <div class="fw-semibold">
                                            {{ $log->estimated_cost_usd !== null ? '$' . number_format((float) $log->estimated_cost_usd, 4) : '—' }}
                                        </div>
                                        <div class="text-muted small">{{ $log->api_request_id ?: 'No request id' }}</div>
                                    </x-core::table.body.cell>
                                    <x-core::table.body.cell>
                                        <div class="fw-semibold">{{ $log->total_tokens !== null ? number_format($log->total_tokens) : '—' }}</div>
                                        <div class="text-muted small">
                                            In {{ $log->input_tokens !== null ? number_format($log->input_tokens) : '—' }}
                                            / Out {{ $log->output_tokens !== null ? number_format($log->output_tokens) : '—' }}
                                        </div>
                                    </x-core::table.body.cell>
                                    <x-core::table.body.cell>
                                        {{ $log->latency_ms !== null ? number_format($log->latency_ms) . ' ms' : '—' }}
                                    </x-core::table.body.cell>
                                    <x-core::table.body.cell>
                                        @if($log->stored_path)
                                            <div class="small mb-2">
                                                <code>{{ $log->stored_path }}</code>
                                            </div>
                                        @endif

                                        @if($log->error_message)
                                            <div class="small text-danger mb-2">{{ $log->error_message }}</div>
                                        @endif

                                        @if($log->response_meta)
                                            <details>
                                                <summary class="small">Meta</summary>
                                                <pre class="small bg-light border rounded p-2 mt-2 mb-0" style="max-width:420px;max-height:220px;overflow:auto;white-space:pre-wrap;">{{ json_encode($log->response_meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </details>
                                        @endif
                                    </x-core::table.body.cell>
                                </x-core::table.body.row>
                            @empty
                                <x-core::table.body.row>
                                    <x-core::table.body.cell colspan="9" class="text-center text-muted py-4">
                                        No AI image generation logs found for the current filters.
                                    </x-core::table.body.cell>
                                </x-core::table.body.row>
                            @endforelse
                        </x-core::table.body>
                    </x-core::table>
                </div>

                @if($logs->hasPages())
                    <div class="mt-3">
                        {{ $logs->links() }}
                    </div>
                @endif
            </x-core::tab.pane>
        </x-core::tab.content>
    </x-core::card.body>
</x-core::card>

<script>
(function () {
    var filter = document.getElementById('country-filter');
    if (filter) {
        filter.addEventListener('input', function () {
            var q = this.value.toLowerCase();
            document.querySelectorAll('#country-grid .country-item').forEach(function (item) {
                var label = item.querySelector('.form-check-label').textContent.toLowerCase();
                item.style.display = label.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }
    var setAll = function (checked) {
        document.querySelectorAll('#country-grid .country-item').forEach(function (item) {
            if (item.style.display === 'none') return;
            item.querySelector('.country-checkbox').checked = checked;
        });
    };
    var selectAll = document.getElementById('country-select-all');
    var clearAll = document.getElementById('country-clear-all');
    if (selectAll) selectAll.addEventListener('click', function () { setAll(true); });
    if (clearAll) clearAll.addEventListener('click', function () { setAll(false); });

    var compression = document.getElementById('ai_social_image_output_compression');
    var compressionNumber = document.getElementById('ai_social_image_output_compression_number');
    var format = document.getElementById('ai_social_image_output_format');

    var syncCompression = function (value, source) {
        var normalized = Math.max(0, Math.min(100, parseInt(value || '0', 10) || 0));
        if (source !== compression && compression) compression.value = normalized;
        if (source !== compressionNumber && compressionNumber) compressionNumber.value = normalized;
    };

    if (compression) {
        compression.addEventListener('input', function () {
            syncCompression(this.value, compression);
        });
    }

    if (compressionNumber) {
        compressionNumber.addEventListener('input', function () {
            syncCompression(this.value, compressionNumber);
        });
    }

    var toggleCompressionState = function () {
        var enabled = format && (format.value === 'jpeg' || format.value === 'webp');
        if (compression) compression.disabled = ! enabled;
        if (compressionNumber) compressionNumber.disabled = ! enabled;
    };

    if (format) {
        format.addEventListener('change', toggleCompressionState);
        toggleCompressionState();
    }
})();
</script>
@endsection
