@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
    <div class="col-lg-12">
        <div class="mb-3 mt-10 d-flex flex-wrap gap-2">
            <a href="{{ route('public.account.experiences.create') }}" class="btn btn-default btn-brand icon-tick">{{ __('Add Experience') }}</a>
            <button
                type="button"
                class="btn btn-outline-primary js-prefill-experiences-from-cv"
                data-url="{{ route('public.account.prefill-from-resume') }}"
                data-next-url="{{ url()->current() }}"
            >
                {{ __('Fill Experiences From CV / CV Builder') }}
            </button>
        </div>
        <p class="text-muted small mb-0">{{ __('This adds missing experience entries from your linked CV Builder data first, or from your uploaded CV if no builder profile is linked.') }}</p>
    </div>
    <div class="box-timeline mt-50">
        @forelse($experiences as $experience)
            <div class="item-timeline">
                <div class="timeline-year">
                    <span>{{ $experience->started_at?->format('Y') ?: __('N/A') }} -
                       {{ $experience->ended_at?->format('Y') ?: __('Now') }}
                    </span>
                </div>
                <div class="timeline-info">
                    <h5 class="color-brand-1 mb-20">
                        {{ $experience->company }}
                        @if($experience->position)
                            <span class="ml-5 text-muted">
                             ({{ $experience->position }})
                        </span>
                        @endif
                    </h5>
                    <p class="color-text-paragraph-2 mb-15">{!! BaseHelper::clean($experience->description) !!}</p>
                </div>
                <div class="timeline-actions">
                    <a href="{{ route('public.account.experiences.edit', $experience->id) }}" class="btn btn-editor"></a>
                    <button
                        type="button"
                        class="btn btn-remove"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteExperienceModal"
                        data-action="{{ route('public.account.experiences.destroy', $experience->id) }}"
                        data-label="{{ $experience->company }}"
                    ></button>
                </div>
            </div>
        @empty
        @endforelse
    </div>

    <div class="modal fade" id="deleteExperienceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                            <i class="ti ti-trash text-danger fs-3"></i>
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">{{ __('Delete this experience?') }}</h6>
                    <p class="text-muted small mb-4" id="deleteExperienceModalLabel">{{ __('This cannot be undone.') }}</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <form id="deleteExperienceForm" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger px-4">{{ __('Delete') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('footer')
    <script>
        function showAccountActionModal(options, onAccept, onReject) {
            var modalId = 'accountActionModal';
            var $modal = $('#' + modalId);

            if (! $modal.length) {
                $('body').append(
                    '<div class="modal fade" id="' + modalId + '" tabindex="-1" aria-hidden="true">' +
                        '<div class="modal-dialog modal-dialog-centered modal-sm">' +
                            '<div class="modal-content">' +
                                '<div class="modal-body text-center py-4 px-4">' +
                                    '<div class="mb-3"><span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10" style="width:52px;height:52px;"><i class="ti ti-help text-primary fs-3"></i></span></div>' +
                                    '<h6 class="fw-semibold mb-2" data-action-title></h6>' +
                                    '<p class="text-muted small mb-4" data-action-text></p>' +
                                    '<div class="d-flex gap-2 justify-content-center">' +
                                        '<button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" data-action-cancel>{{ __('Cancel') }}</button>' +
                                        '<button type="button" class="btn btn-primary px-4" data-action-confirm></button>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
                $modal = $('#' + modalId);
            }

            $modal.find('[data-action-title]').text(options.title || '');
            $modal.find('[data-action-text]').text(options.text || '');
            $modal.find('[data-action-confirm]').text(options.confirmText || '{{ __('Confirm') }}');

            var modal = bootstrap.Modal.getOrCreateInstance($modal[0]);
            $modal.off('click.accountAction', '[data-action-confirm]');
            $modal.off('hidden.bs.modal.accountAction');
            $modal.data('confirmed', false);
            $modal.on('click.accountAction', '[data-action-confirm]', function () {
                $modal.data('confirmed', true);
                modal.hide();
                if (typeof onAccept === 'function') {
                    onAccept();
                }
            });
            $modal.on('hidden.bs.modal.accountAction', function () {
                if (! $modal.data('confirmed') && typeof onReject === 'function') {
                    onReject();
                }
            });

            modal.show();
        }

        function showAccountStatusModal(options) {
            var modalId = 'accountStatusModal';
            var $modal = $('#' + modalId);

            if (! $modal.length) {
                $('body').append(
                    '<div class="modal fade" id="' + modalId + '" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">' +
                        '<div class="modal-dialog modal-dialog-centered modal-sm">' +
                            '<div class="modal-content">' +
                                '<div class="modal-body text-center py-4 px-4">' +
                                    '<div class="mb-3" data-status-icon></div>' +
                                    '<h6 class="fw-semibold mb-2" data-status-title></h6>' +
                                    '<p class="text-muted small mb-4" data-status-text></p>' +
                                    '<button type="button" class="btn btn-primary px-4 d-none" data-status-close>{{ __('Close') }}</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
                $modal = $('#' + modalId);
            }

            var iconHtml = options.state === 'loading'
                ? '<span class="spinner-border text-primary" role="status" style="width:2.5rem;height:2.5rem;"></span>'
                : (options.state === 'success'
                    ? '<span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:52px;height:52px;"><i class="ti ti-check text-success fs-3"></i></span>'
                    : '<span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;"><i class="ti ti-alert-circle text-danger fs-3"></i></span>');

            $modal.find('[data-status-icon]').html(iconHtml);
            $modal.find('[data-status-title]').text(options.title || '');
            $modal.find('[data-status-text]').text(options.text || '');
            $modal.find('[data-status-close]')
                .toggleClass('d-none', options.state === 'loading')
                .removeClass('btn-danger btn-success btn-primary')
                .addClass(options.state === 'error' ? 'btn-danger' : (options.state === 'success' ? 'btn-success' : 'btn-primary'));

            var modal = bootstrap.Modal.getOrCreateInstance($modal[0]);
            $modal.off('click.accountStatus', '[data-status-close]');
            $modal.on('click.accountStatus', '[data-status-close]', function () {
                modal.hide();
                if (typeof options.onClose === 'function') {
                    options.onClose();
                }
            });

            modal.show();
        }

        document.getElementById('deleteExperienceModal')?.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('deleteExperienceForm').action = button.dataset.action;
            document.getElementById('deleteExperienceModalLabel').textContent = button.dataset.label || '{{ __('This cannot be undone.') }}';
        });

        $(document).on('click', '.js-prefill-experiences-from-cv', function (event) {
            event.preventDefault();

            var button = this;
            var url = button.getAttribute('data-url');
            var nextUrl = button.getAttribute('data-next-url');
            var tokenMeta = document.querySelector('meta[name="csrf-token"]');
            var tokenInput = document.querySelector('input[name="_token"]');
            var token = (tokenMeta ? tokenMeta.getAttribute('content') : '') || (tokenInput ? tokenInput.value : '');

            if (! url || ! token) {
                return;
            }

            showAccountActionModal({
                title: @json(__('Fill experiences from CV data?')),
                text: @json(__('We will add any missing experience records from your linked CV Builder profile first, or from your uploaded CV if needed.')),
                confirmText: @json(__('Yes, fill experiences')),
            }, function () {
                button.disabled = true;

                showAccountStatusModal({
                    state: 'loading',
                    title: @json(__('Updating experiences…')),
                    text: @json(__('Please wait while we process your CV data.')),
                });

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ next_url: nextUrl })
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            if (! response.ok || data.error) {
                                throw new Error(data.message || @json(__('We could not update your experiences from the current CV.')));
                            }

                            return data;
                        });
                    })
                    .then(function (data) {
                        showAccountStatusModal({
                            state: 'success',
                            title: @json(__('Experiences updated')),
                            text: data.message || @json(__('Your experiences were updated from CV data.')),
                            onClose: function () {
                                window.location.href = (data.data && data.data.next_url) || nextUrl || window.location.href;
                            }
                        });
                    })
                    .catch(function (error) {
                        showAccountStatusModal({
                            state: 'error',
                            title: @json(__('Update failed')),
                            text: error.message || @json(__('We could not update your experiences from the current CV.')),
                        });
                    })
                    .finally(function () {
                        button.disabled = false;
                    });
            }, function () {});
        });
    </script>
@endpush
