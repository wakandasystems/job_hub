class Location {
    static showError(message) {
        if (typeof Botble !== 'undefined' && Botble.showError) {
            Botble.showError(message)
        } else if (typeof Theme !== 'undefined' && Theme.showError) {
            Theme.showError(message)
        } else if (typeof toastr !== 'undefined') {
            toastr.error(message)
        } else {
            console.error(message)
        }
    }

    static initSelect2Ajax($el) {
        if (!jQuery().select2) return

        const ajaxUrl = $el.data('ajax-search-url')
        if (!ajaxUrl) return

        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy')
        }

        const placeholder = $el.find('option[value=""]').text() || 'Select...'
        const $form = $el.closest('form')
        const type = $el.data('type')

        const opts = {
            width: '100%',
            placeholder: placeholder,
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: ajaxUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    const d = { q: params.term || '', page: params.page || 1 }

                    const $country = $form.find('select[data-type="country"]')
                    const countryId = ($country.length && $country.val()) ? $country.val() : $el.data('country-id')

                    if (type === 'state') {
                        if (!countryId) return false   // No country → abort AJAX
                        d.country_id = countryId
                    }

                    if (type === 'city') {
                        const $state = $form.find('select[data-type="state"]')
                        const stateId = ($state.length && $state.val()) ? $state.val() : $el.data('state-id')
                        if (!stateId) return false     // No state → abort AJAX
                        d.state_id = stateId
                        if (countryId) d.country_id = countryId
                    }

                    return d
                },
                processResults: function (data) {
                    return {
                        results: data.results || [],
                        pagination: data.pagination || { more: false },
                    }
                },
                cache: true,
            },
        }

        const $parent = $el.closest('div[data-select2-dropdown-parent]') || $el.closest('.modal')
        if ($parent.length) opts.dropdownParent = $parent

        $el.select2(opts)
    }

    static initSelect2($el) {
        if (!jQuery().select2 || !$el.hasClass('select-search-location') || $el.hasClass('select2-hidden-accessible')) return

        const opts = {
            width: '100%',
            placeholder: $el.find('option:first').text() || 'Select...',
            allowClear: false,
            minimumResultsForSearch: 0,
        }
        const $parent = $el.closest('div[data-select2-dropdown-parent]') || $el.closest('.modal')
        if ($parent.length) opts.dropdownParent = $parent
        $el.select2(opts)
    }

    init() {
        const country = 'select[data-type="country"]'
        const state   = 'select[data-type="state"]'
        const city    = 'select[data-type="city"]'

        if (jQuery().select2) {
            $(document).find('select.select-search-location[data-type]').each(function () {
                Location.initSelect2($(this))
            })
        }

        // Init AJAX selects + enforce disabled state based on dependencies
        $(document).find('select[data-ajax-search-url]').each(function () {
            const $el = $(this)
            const type = $el.data('type')
            const $form = $el.closest('form')

            if (type === 'state') {
                const hasCountry = !!($form.find('select[data-type="country"]').val() || $el.data('country-id'))
                $el.prop('disabled', !hasCountry)
            }

            if (type === 'city') {
                const hasState = !!($form.find('select[data-type="state"]').val() || $el.data('state-id'))
                $el.prop('disabled', !hasState)
            }

            Location.initSelect2Ajax($el)
        })

        // Country changes
        $(document).on('change', country, function (e) {
            e.preventDefault()
            const $p       = getParent($(e.currentTarget))
            const $state   = $p.find(state)
            const $city    = $p.find(city)
            const countryId = $(e.currentTarget).val()

            $state.attr('data-country-id', countryId || '')
            $city.attr('data-country-id', countryId || '').attr('data-state-id', '')

            // Enable/disable state based on whether country is selected
            $state.prop('disabled', !countryId)

            // Always disable city when country changes (state must be picked first)
            $city.prop('disabled', true)
            if ($city.hasClass('select2-hidden-accessible')) $city.val(null).trigger('change')

            // Clear + reinit state
            if ($state.data('ajax-search-url')) {
                if ($state.hasClass('select2-hidden-accessible')) $state.val(null).trigger('change')
                Location.initSelect2Ajax($state)
            }

            // Clear + reinit city
            if ($city.data('ajax-search-url')) {
                Location.initSelect2Ajax($city)
            }
        })

        // State changes
        $(document).on('change', state, function (e) {
            e.preventDefault()
            const $p      = getParent($(e.currentTarget))
            const $city   = $p.find(city)
            if (!$city.length) return

            const stateId = $(e.currentTarget).val()

            $city.attr('data-state-id', stateId || '')

            // Enable/disable city based on whether state is selected
            $city.prop('disabled', !stateId)

            if ($city.data('ajax-search-url')) {
                if ($city.hasClass('select2-hidden-accessible')) $city.val(null).trigger('change')
                Location.initSelect2Ajax($city)
            }
        })

        function getParent($el) {
            let $parent = $(document)
            const fp = $el.data('form-parent')
            if (fp && $(fp).length) $parent = $(fp)
            return $parent
        }
    }
}

$(() => { new Location().init() })
