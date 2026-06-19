@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<div class="row g-4">
    <div class="col-12">
        <x-core::card>
            <x-core::card.body class="py-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <i class="ti ti-info-circle text-primary fs-4 flex-shrink-0"></i>
                    <div class="flex-grow-1">
                        <strong>Merging two companies</strong> moves jobs, reviews and the linked employer
                        login from one record onto the other, fills in any blank profile fields from the
                        duplicate, then deletes the duplicate. We recommend keeping whichever company has a
                        real linked employer login — that's pre-selected below when it's clear. This can be
                        undone from the Recent Merges list further down, as long as the company hasn't since
                        been merged again.
                    </div>
                </div>
            </x-core::card.body>
        </x-core::card>
    </div>

    <div class="col-12">
        <x-core::card>
            <x-core::card.header>
                <h5 class="mb-0">1. Pick the two companies</h5>
            </x-core::card.header>
            <x-core::card.body>
                <div class="row g-3">
                    @foreach (['a', 'b'] as $slot)
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Company {{ strtoupper($slot) }}</label>
                            <input type="text"
                                class="form-control company-merge-search-input"
                                data-slot="{{ $slot }}"
                                placeholder="Search by company name, email or website">
                            <div class="company-merge-results mt-1 d-none" data-slot="{{ $slot }}"></div>
                            <div class="company-merge-selected mt-2 d-none" data-slot="{{ $slot }}"></div>
                        </div>
                    @endforeach
                </div>
            </x-core::card.body>
        </x-core::card>
    </div>

    <div class="col-12 d-none" id="company-merge-comparison-wrap">
        <x-core::card>
            <x-core::card.header>
                <h5 class="mb-0">2. Compare &amp; choose which one survives</h5>
            </x-core::card.header>
            <x-core::card.body>
                <div id="company-merge-comparison"></div>
                <div class="mt-3 d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-danger" id="btn-confirm-merge" disabled>
                        <i class="ti ti-git-merge me-1"></i> Merge Now
                    </button>
                    <span class="text-muted small" id="company-merge-hint">Choose which company should stay first.</span>
                </div>
            </x-core::card.body>
        </x-core::card>
    </div>

    <div class="col-12">
        <x-core::card>
            <x-core::card.header>
                <h5 class="mb-0">Recent Merges</h5>
            </x-core::card.header>
            <x-core::card.body class="p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Merged</th>
                                <th>Into</th>
                                <th>Moved</th>
                                <th>By</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentLogs as $log)
                                <tr>
                                    <td class="text-nowrap">{{ $log->created_at?->format('d M Y H:i') }}</td>
                                    <td>{{ $log->loser_name }}</td>
                                    <td>
                                        @if ($log->winner)
                                            <a href="{{ route('companies.edit', $log->winner_company_id) }}">{{ $log->winner->name }}</a>
                                        @else
                                            <span class="text-muted">#{{ $log->winner_company_id }} (deleted)</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">
                                        {{ count($log->moved_job_ids ?? []) }} job(s),
                                        {{ count($log->moved_review_ids ?? []) }} review(s),
                                        {{ count($log->moved_account_ids ?? []) }} account(s)
                                    </td>
                                    <td>{{ $log->mergedBy?->name ?: '—' }}</td>
                                    <td>
                                        @if ($log->isUndone())
                                            <span class="badge bg-secondary-subtle text-secondary">Undone</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if (! $log->isUndone())
                                            <button type="button"
                                                class="btn btn-sm btn-outline-warning btn-undo-merge"
                                                data-bs-toggle="tooltip"
                                                title="{{ $log->isUndoableSafely() ? 'Restore the deleted company' : 'This company has since been merged again — undo that merge first' }}"
                                                data-url="{{ route('companies.merge.undo', $log->id) }}"
                                                @disabled(! $log->isUndoableSafely())>
                                                <i class="ti ti-arrow-back-up"></i> Undo
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No merges yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-core::card.body>
        </x-core::card>
    </div>
</div>

@push('footer')
<script>
    (function ($) {
        var searchUrl = '{{ route('companies.merge.search') }}';
        var compareUrl = '{{ route('companies.merge.compare') }}';
        var mergeUrl = '{{ route('companies.merge.store') }}';
        var selected = { a: null, b: null };
        var recommendedWinnerId = null;

        var preselected = {!! $preselected->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'email' => $c->email])->values()->toJson() !!};

        function renderSelected(slot, company) {
            selected[slot] = company;
            $('.company-merge-selected[data-slot="' + slot + '"]')
                .removeClass('d-none')
                .html(
                    '<div class="border rounded px-3 py-2 bg-light-subtle d-flex align-items-center justify-content-between gap-2">' +
                    '<div><div class="fw-semibold">' + $('<div>').text(company.name).html() + '</div>' +
                    '<div class="text-muted small">' + $('<div>').text(company.email || '').html() + '</div></div>' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary btn-clear-merge-slot" data-slot="' + slot + '"><i class="ti ti-x"></i></button>' +
                    '</div>'
                );
            $('.company-merge-search-input[data-slot="' + slot + '"]').val('').closest('.col-md-6').find('.company-merge-results').addClass('d-none');
            maybeCompare();
        }

        function clearSelected(slot) {
            selected[slot] = null;
            $('.company-merge-selected[data-slot="' + slot + '"]').addClass('d-none').empty();
            $('#company-merge-comparison-wrap').addClass('d-none');
        }

        function maybeCompare() {
            if (!selected.a || !selected.b) {
                return;
            }

            if (selected.a.id === selected.b.id) {
                Botble.showError('Pick two different companies.');
                return;
            }

            $.get(compareUrl, { ids: selected.a.id + ',' + selected.b.id }, function (data) {
                if (data.error) {
                    Botble.showError(data.message);
                    return;
                }

                recommendedWinnerId = data.recommended_winner_id;
                renderComparison(data.companies);
            });
        }

        function fieldRow(label, key, companies, formatter) {
            var format = formatter || function (v) { return v || '<span class="text-muted">—</span>'; };
            return '<tr><th class="text-muted small">' + label + '</th>' +
                companies.map(function (c) { return '<td>' + format(c[key], c) + '</td>'; }).join('') +
                '</tr>';
        }

        function renderComparison(companies) {
            var rows = '';
            rows += fieldRow('Logo', 'logo_thumb', companies, function (v) {
                return '<img src="' + v + '" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb">';
            });
            rows += fieldRow('Name', 'name', companies);
            rows += fieldRow('Email', 'email', companies);
            rows += fieldRow('Phone', 'phone', companies);
            rows += fieldRow('Website', 'website', companies);
            rows += fieldRow('Address', 'address', companies);
            rows += fieldRow('Jobs', 'jobs_count', companies);
            rows += fieldRow('Linked employer login?', 'has_account', companies, function (v) {
                return v ? '<span class="badge bg-success-subtle text-success">Yes</span>' : '<span class="badge bg-secondary-subtle text-secondary">No</span>';
            });
            rows += fieldRow('Verified?', 'is_verified', companies, function (v) {
                return v ? '<span class="badge bg-success-subtle text-success">Yes</span>' : '<span class="badge bg-secondary-subtle text-secondary">No</span>';
            });
            rows += fieldRow('Profile complete?', 'completed_profile', companies, function (v) {
                return v ? 'Yes' : 'No';
            });
            rows += fieldRow('Created', 'created_at', companies);

            var radioRow = '<tr><th class="text-muted small">Keep this one</th>' +
                companies.map(function (c) {
                    var checked = c.id === recommendedWinnerId ? 'checked' : '';
                    return '<td><input type="radio" name="merge_winner" class="form-check-input" value="' + c.id + '" ' + checked + '></td>';
                }).join('') + '</tr>';

            var html = '<div class="table-responsive"><table class="table table-bordered align-middle">' +
                '<thead><tr><th></th>' + companies.map(function (c) { return '<th>' + $('<div>').text(c.name).html() + '</th>'; }).join('') + '</tr></thead>' +
                '<tbody>' + rows + radioRow + '</tbody></table></div>';

            $('#company-merge-comparison').html(html);
            $('#company-merge-comparison-wrap').removeClass('d-none');

            window.__companyMergeCompanies = companies;
            updateMergeButtonState();
        }

        function updateMergeButtonState() {
            var winnerId = $('input[name="merge_winner"]:checked').val();
            $('#btn-confirm-merge').prop('disabled', !winnerId);
            $('#company-merge-hint').text(winnerId ? 'Ready to merge.' : 'Choose which company should stay first.');
        }

        $(document).on('change', 'input[name="merge_winner"]', updateMergeButtonState);

        $(document).on('click', '.btn-clear-merge-slot', function () {
            clearSelected($(this).data('slot'));
        });

        var searchTimers = {};
        $(document).on('input', '.company-merge-search-input', function () {
            var $input = $(this);
            var slot = $input.data('slot');
            var term = $input.val().trim();

            clearTimeout(searchTimers[slot]);

            if (term.length < 2) {
                $('.company-merge-results[data-slot="' + slot + '"]').addClass('d-none').empty();
                return;
            }

            searchTimers[slot] = setTimeout(function () {
                $.get(searchUrl, { q: term }, function (data) {
                    var $results = $('.company-merge-results[data-slot="' + slot + '"]');

                    if (!data.data || !data.data.length) {
                        $results.removeClass('d-none').html('<div class="text-muted small p-2">No matches.</div>');
                        return;
                    }

                    var html = '<div class="list-group">' + data.data.map(function (c) {
                        return '<button type="button" class="list-group-item list-group-item-action btn-pick-merge-company" data-slot="' + slot + '" data-company=\'' + JSON.stringify(c).replace(/'/g, '&#39;') + '\'>' +
                            '<div class="fw-semibold">' + $('<div>').text(c.name).html() + '</div>' +
                            '<div class="text-muted small">' + $('<div>').text(c.email || '').html() + (c.has_account ? ' &middot; <span class="text-success">has login</span>' : '') + '</div>' +
                            '</button>';
                    }).join('') + '</div>';

                    $results.removeClass('d-none').html(html);
                });
            }, 300);
        });

        $(document).on('click', '.btn-pick-merge-company', function () {
            renderSelected($(this).data('slot'), $(this).data('company'));
        });

        $(document).on('click', '#btn-confirm-merge', function () {
            var winnerId = $('input[name="merge_winner"]:checked').val();
            var companies = window.__companyMergeCompanies || [];
            var loser = companies.find(function (c) { return String(c.id) !== String(winnerId); });

            if (!winnerId || !loser) {
                return;
            }

            var $form = $('<form>', { method: 'POST', action: mergeUrl })
                .append($('<input>', { type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }))
                .append($('<input>', { type: 'hidden', name: 'winner_id', value: winnerId }))
                .append($('<input>', { type: 'hidden', name: 'loser_id', value: loser.id }));

            $('body').append($form);
            $form.submit();
        });

        $(document).on('click', '.btn-undo-merge', function () {
            var $btn = $(this);
            var $form = $('<form>', { method: 'POST', action: $btn.data('url') })
                .append($('<input>', { type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }));

            $('body').append($form);
            $form.submit();
        });

        if (preselected.length === 2) {
            renderSelected('a', preselected[0]);
            renderSelected('b', preselected[1]);
        }
    })(jQuery);
</script>
@endpush
@endsection
