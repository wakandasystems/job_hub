@extends($layout ?? BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
@include('core/table::base-table')

@push('footer')
<script>
    (function ($) {
        $(document).on('click', '.btn-open-merge-tool', function (e) {
            e.preventDefault();

            var ids = [];
            $('.checkboxes:checked').each(function () {
                ids.push($(this).val());
            });

            if (ids.length === 1 || ids.length > 2) {
                Botble.showError('Check exactly 2 companies to merge them directly, or none to search for them on the next page.');
                return;
            }

            var url = '{{ route('companies.merge.picker') }}';
            if (ids.length === 2) {
                url += '?ids=' + ids.join(',');
            }

            window.location.href = url;
        });
    })(jQuery);
</script>
@endpush
@endsection
