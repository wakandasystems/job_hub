class Location {
    // Show error message - works on both admin and frontend
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

    // Refresh Select2 instance after options update
    static refreshSelect2($el) {
        if (jQuery().select2 && $el.hasClass('select-search-location') && $el.hasClass('select2-hidden-accessible')) {
            // Destroy and reinitialize to refresh options
            $el.select2('destroy')
            Location.initSelect2($el)
        }
    }

    // Initialize Select2 on a single element
    static initSelect2($el) {
        if (!jQuery().select2 || !$el.hasClass('select-search-location')) {
            return
        }

        // Skip if already initialized
        if ($el.hasClass('select2-hidden-accessible')) {
            return
        }

        const placeholder = $el.find('option:first').text() || 'Select...'

        let options = {
            width: '100%',
            placeholder: placeholder,
            allowClear: false,
            minimumResultsForSearch: 0, // Always show search box
        }

        // Find parent for dropdown positioning
        let parent = $el.closest('div[data-select2-dropdown-parent]') || $el.closest('.modal')
        if (parent.length) {
            options.dropdownParent = parent
        }

        $el.select2(options)
    }

    static getStates($el, countryId, $button = null) {
        $.ajax({
            url: $el.data('url'),
            data: {
                country_id: countryId,
            },
            type: 'GET',
            beforeSend: () => {
                $button && $button.prop('disabled', true)
            },
            success: (res) => {
                if (res.error) {
                    Location.showError(res.message)
                } else {
                    let options = ''
                    $.each(res.data, (index, item) => {
                        options += '<option value="' + (item.id || '') + '">' + item.name + '</option>'
                    })

                    $el.html(options)
                    Location.refreshSelect2($el)
                }
            },
            complete: () => {
                $button && $button.prop('disabled', false)
            },
        })
    }

    static getCities($el, stateId, $button = null, countryId = null) {
        $.ajax({
            url: $el.data('url'),
            data: {
                state_id: stateId,
                country_id: countryId,
            },
            type: 'GET',
            beforeSend: () => {
                $button && $button.prop('disabled', true)
            },
            success: (res) => {
                if (res.error) {
                    Location.showError(res.message)
                } else {
                    let options = ''
                    $.each(res.data, (index, item) => {
                        options += '<option value="' + (item.id || '') + '">' + item.name + '</option>'
                    })

                    $el.html(options)
                    Location.refreshSelect2($el)
                    $el.trigger('change')
                }
            },
            complete: () => {
                $button && $button.prop('disabled', false)
            },
        })
    }

    init() {
        const country = 'select[data-type="country"]'
        const state = 'select[data-type="state"]'
        const city = 'select[data-type="city"]'

        // Initialize Select2 on all location selects with select-search-location class
        function initLocationSelect2() {
            if (!jQuery().select2) {
                return
            }

            $(document).find('select.select-search-location[data-type]').each(function (index, el) {
                Location.initSelect2($(el))
            })
        }

        // Initialize on page load
        initLocationSelect2()

        $(document).on('change', country, function (e) {
            e.preventDefault()

            const $parent = getParent($(e.currentTarget))

            const $state = $parent.find(state)
            const $city = $parent.find(city)

            $state.find('option:not([value=""]):not([value="0"])').remove()
            $city.find('option:not([value=""]):not([value="0"])').remove()

            // Refresh Select2 for cleared dropdowns
            Location.refreshSelect2($state)
            Location.refreshSelect2($city)

            const $button = $(e.currentTarget).closest('form').find('button[type=submit], input[type=submit]')
            const countryId = $(e.currentTarget).val()

            if (countryId) {
                if ($state.length) {
                    Location.getStates($state, countryId, $button)
                    Location.getCities($city, null, $button, countryId)
                } else {
                    Location.getCities($city, null, $button, countryId)
                }
            }
        })

        $(document).on('change', state, function (e) {
            e.preventDefault()

            const $parent = getParent($(e.currentTarget))
            const $city = $parent.find(city)

            if ($city.length) {
                $city.find('option:not([value=""]):not([value="0"])').remove()
                Location.refreshSelect2($city)

                const stateId = $(e.currentTarget).val()
                const $button = $(e.currentTarget).closest('form').find('button[type=submit], input[type=submit]')

                if (stateId) {
                    Location.getCities($city, stateId, $button)
                } else {
                    const countryId = $parent.find(country).val()

                    Location.getCities($city, null, $button, countryId)
                }
            }
        })

        function getParent($el) {
            let $parent = $(document)
            let formParent = $el.data('form-parent')
            if (formParent && $(formParent).length) {
                $parent = $(formParent)
            }

            return $parent
        }
    }
}

$(() => {
    new Location().init()
})
