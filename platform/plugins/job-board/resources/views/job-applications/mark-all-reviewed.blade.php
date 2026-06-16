<x-core::modal.action
    type="success"
    class="mark-all-reviewed-modal"
    title="Mark all applications reviewed?"
    description="Every application that is not currently reviewed will be changed to Reviewed. No emails will be sent."
    submit-button-label="Mark all reviewed"
    :submit-button-attrs="['class' => 'confirm-mark-all-reviewed-button']"
/>

<script>
    function refreshJobBoardMenuCounts() {
        return $httpClient
            .make()
            .get('{{ route('menu-items-count') }}')
            .then(function (response) {
                var counts = response.data.data || [];

                localStorage.setItem('menu_items_count_data', JSON.stringify(counts));
                localStorage.setItem('menu_items_count_check_time', Date.now().toString());

                counts.forEach(function (count) {
                    var badge = $('.menu-item-count.' + count.key);

                    if (count.value > 0) {
                        badge.text(count.value).show().removeClass('hidden');
                    } else {
                        badge.text('').hide().addClass('hidden');
                    }
                });
            });
    }

    $(document).on('click', '.mark-all-reviewed-button', function (event) {
        event.preventDefault();

        var tableId = $(event.currentTarget)
            .closest('.table-wrapper')
            .find('.table')
            .prop('id');

        $('.confirm-mark-all-reviewed-button').data('table-id', tableId);
        $('.mark-all-reviewed-modal').modal('show');
    });

    $(document).on('click', '.confirm-mark-all-reviewed-button', function (event) {
        event.preventDefault();

        var button = $(event.currentTarget);
        var tableId = button.data('table-id');

        Botble.showButtonLoading(button);

        $httpClient
            .make()
            .post('{{ route('job-applications.mark-all-reviewed') }}')
            .then(function (response) {
                Botble.showSuccess(response.data.message);
                window.LaravelDataTables[tableId].draw(false);
                button.closest('.modal').modal('hide');

                return refreshJobBoardMenuCounts();
            })
            .finally(function () {
                Botble.hideButtonLoading(button);
            });
    });

    refreshJobBoardMenuCounts();
</script>
