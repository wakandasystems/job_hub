@php
    PageTitle::setTitle(trans('core/base::errors.500_internal_server_error'));
@endphp

@extends('core/base::errors.master')

@section('content')
    <div class="empty">
        <div class="empty-header">500</div>
        <p class="empty-title">{{ ! empty($adminErrorDetails) ? 'Wakanda Systems' : trans('core/base::errors.500_internal_server_error_description') }}</p>
        <p class="empty-subtitle text-secondary">
            {{ ! empty($adminErrorDetails) ? 'Service update' : trans('core/base::errors.500_description') }}
        </p>
        @if (! empty($adminErrorDetails))
            <div class="mx-auto text-start mt-4" style="max-width: 860px;">
                <div class="card">
                    <div class="card-body">
                        <p class="mb-2 fw-semibold">We will be back shortly.</p>
                        <p class="text-secondary small mb-3">We are making a few improvements at the moment. Please check back soon.</p>
                        <div class="small text-secondary mb-2">Admin error details</div>
                        <textarea id="adminErrorDetailsBox" class="form-control font-monospace small" rows="14" readonly>@php
{{ 'Message: ' . ($adminErrorDetails['message'] ?? '') }}
{{ 'Exception: ' . ($adminErrorDetails['exception'] ?? '') }}
{{ 'File: ' . ($adminErrorDetails['file'] ?? '') }}
{{ 'URL: ' . ($adminErrorDetails['url'] ?? '') }}
{{ 'Method: ' . ($adminErrorDetails['method'] ?? '') }}

{{ 'Trace:' }}
{{ $adminErrorDetails['trace'] ?? '' }}
                        @endphp</textarea>
                        <div class="d-flex gap-2 justify-content-center mt-3">
                            <x-core::button tag="button" type="button" color="primary" id="copyAdminErrorDetails" icon="ti ti-copy">
                                Copy Error
                            </x-core::button>
                            <x-core::button tag="button" type="button" color="secondary" onclick="window.location.reload()" icon="ti ti-refresh">
                                Refresh Page
                            </x-core::button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="empty-action">
            <x-core::button
                tag="a"
                href="{{ route('dashboard.index') }}"
                color="primary"
                icon="ti ti-arrow-left"
            >
                {{ trans('core/base::errors.take_me_home') }}
            </x-core::button>
        </div>
    </div>
    @if (! empty($adminErrorDetails))
        <script>
            document.getElementById('copyAdminErrorDetails')?.addEventListener('click', async function () {
                var box = document.getElementById('adminErrorDetailsBox');

                if (! box) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(box.value);
                    this.textContent = 'Copied';
                } catch (error) {
                    box.focus();
                    box.select();
                    document.execCommand('copy');
                    this.textContent = 'Copied';
                }
            });
        </script>
    @endif
@endsection
