@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('content')
    <div class="col-lg-12">
        <div class="mb-3 mt-10">
            <a href="{{ route('public.account.experiences.create') }}" class="btn btn-default btn-brand icon-tick">{{ __('Add Experience') }}</a>
        </div>
    </div>
    <div class="box-timeline mt-50">
        @forelse($experiences as $experience)
            <div class="item-timeline">
                <div class="timeline-year">
                    <span>{{ $experience->started_at->format('Y') }} -
                       {{ $experience->ended_at ? $experience->ended_at->format('Y') : __('Now') }}
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
        document.getElementById('deleteExperienceModal')?.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('deleteExperienceForm').action = button.dataset.action;
            document.getElementById('deleteExperienceModalLabel').textContent = button.dataset.label || '{{ __('This cannot be undone.') }}';
        });
    </script>
@endpush
