@php
    Theme::asset()->container('footer')->add('location-js', asset('vendor/core/plugins/location/js/location.js'), ['jquery']);
@endphp

<style>
    label.required::after {
        content: ' *';
        color: #e74c3c;
        font-weight: bold;
    }

    /* Make Select2 match the theme's .form-select (height:53px, padding-left:20px) */
    .select-location-fields .select2-container--default .select2-selection--single {
        height: 53px;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background-color: #fff;
        padding: 0 2.25rem 0 20px;
        display: flex;
        align-items: center;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 16px 12px;
    }
    .select-location-fields .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5;
        color: #212529;
        padding: 0;
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .select-location-fields .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #6c757d;
    }
    .select-location-fields .select2-container--default .select2-selection--single .select2-selection__arrow {
        display: none;
    }
    .select-location-fields .select2-container--default .select2-selection--single .select2-selection__clear {
        margin-right: 1.5rem;
        color: #6c757d;
    }
    .select-location-fields .select2-container--default.select2-container--focus .select2-selection--single,
    .select-location-fields .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #86b7fe;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .select-location-fields .select2-container--default.select2-container--disabled .select2-selection--single {
        background-color: #e9ecef;
        cursor: not-allowed;
    }
</style>

@extends(Theme::getThemeNamespace('views.job-board.account.partials.layout-settings'))

@section('content')
    <div>
        <h3 class="mt-0 mb-15 color-brand-1">{{ __('My Account') }}</h3>

        @if ($account->avatar_id)
            <form id="delete-avatar-form" method="POST" action="{{ route('public.account.avatar.destroy') }}">
                @csrf
                @method('DELETE')
            </form>
        @endif

        {!! Form::open(['route' => 'public.account.post.settings', 'method' => 'POST', 'files' => true]) !!}
                <div class="mt-35 mb-40 box-info-profile avatar-view d-inline-block">
                    <div class="image-profile">
                        <img src="{{ $account->avatar_url }}" id="profile-img" alt="{{ $account->name }}">
                    </div>
                    <a class="btn btn-apply">{{ __('Upload Avatar') }}</a>
                </div>

                @if ($account->avatar_id)
                    <button class="btn btn-danger btn-remove-avatar" data-confirm="{{  __('Are you sure you want to remove this avatar?') }}">
                        <x-core::icon name="ti ti-trash"/>
                    </button>
                @endif

            {!! $form
                ->when($account->type->getValue() === 'job-seeker', function ($form) use ($languages, $languageForm) {
                    return $form->addAfter('favorite_tags', 'languages', 'html', \Botble\Base\Forms\FieldOptions\HtmlFieldOption::make()->content(
                        view(Theme::getThemeNamespace('views.job-board.account.partials.languages'), compact('languages', 'languageForm'))->render()
                    ));
                })
                ->contentOnly()
                ->renderForm(showStart: false, showEnd: false) !!}

            <div class="box-button mt-15">
                <button type="submit" class="btn btn-apply-big font-md font-bold">{{ __('Save All Changes') }}</button>
            </div>
        {!! Form::close() !!}

    </div>

    <div class="modal fade" id="addLanguageModal" tabindex="-1" aria-labelledby="addLanguageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="addLanguageModalLabel">{{ __('Add a new language') }}</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {!! $languageForm->renderForm() !!}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" form="account-language-form" class="btn btn-primary">{{ __('Add') }}</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            // Derive a 2-letter country code from the selected country name
            // e.g. "Kenya" → "ke", "South Africa" → "sa"
            function _countryCode() {
                var sel = document.querySelector('select[data-type="country"]');
                if (!sel || !sel.value) return '';
                var name = (sel.options[sel.selectedIndex] || {}).text || '';
                var words = name.trim().split(/\s+/);
                if (words.length >= 2) {
                    return (words[0][0] + words[1][0]).toLowerCase();
                }
                return name.slice(0, 2).toLowerCase();
            }

            // Build the candidate slug: firstname-lastname-countrycode-NNN
            function _buildSlugValue() {
                var first = (document.querySelector('input[name="first_name"]') || {}).value || '';
                var last  = (document.querySelector('input[name="last_name"]')  || {}).value || '';
                var cc    = _countryCode();
                var num   = String(Math.floor(100 + Math.random() * 900)); // 3-digit: 100-999
                var parts = [first.trim(), last.trim(), cc, num].filter(Boolean);
                return parts.join(' '); // SlugService converts spaces to hyphens
            }

            // POST to slug.create and return the unique slug
            function _slugCheck(value, slugData, tokenInput, modelInput, cb) {
                $.ajax({
                    type: 'POST',
                    url: slugData.dataset.url,
                    data: {
                        value: value,
                        slug_id: slugData.dataset.id || '0',
                        model: modelInput ? modelInput.value : '',
                        _token: tokenInput.value,
                    },
                    success: function (slug) { if (cb) cb(slug); },
                });
            }

            function _slugFeedback(input, type, msg) {
                input.classList.remove('is-valid', 'is-invalid');
                input.classList.add(type === 'valid' ? 'is-valid' : 'is-invalid');
                var wrap = input.closest('.input-group') || input.closest('.slug-field-wrapper');
                var fb = wrap ? wrap.nextElementSibling : null;
                if (!fb || !fb.classList.contains('slug-feedback')) {
                    fb = document.createElement('div');
                    if (wrap) wrap.insertAdjacentElement('afterend', fb);
                }
                fb.className = 'slug-feedback ' + (type === 'valid' ? 'valid-feedback' : 'invalid-feedback') + ' d-block mt-1';
                fb.textContent = msg;
            }

            // Capture-phase: runs before front-slug.js to override generate button
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-bb-toggle="generate-slug"]');
                if (!btn) return;
                e.preventDefault();
                e.stopImmediatePropagation();

                var slugData   = document.querySelector('.slug-data');
                var slugInput  = document.querySelector('input[name="slug"]');
                var tokenInput = document.querySelector('input[name="_token"]');
                var modelInput = document.querySelector('input[name="model"]');
                if (!slugData || !slugInput || !tokenInput) return;

                var value = _buildSlugValue();
                if (!value) return;

                _slugCheck(value, slugData, tokenInput, modelInput, function (slug) {
                    slugInput.value = slug;
                    var sc = document.querySelector('.slug-current');
                    if (sc) sc.value = slug;
                    _slugFeedback(slugInput, 'valid', '✓ Available');
                });
            }, true);

            document.addEventListener('DOMContentLoaded', function () {
                // Always show the generate button
                var btn = document.querySelector('[data-bb-toggle="generate-slug"]');
                if (btn) btn.classList.remove('d-none');

                var slugInput  = document.querySelector('input[name="slug"]');
                var slugData   = document.querySelector('.slug-data');
                var tokenInput = document.querySelector('input[name="_token"]');
                var modelInput = document.querySelector('input[name="model"]');
                if (!slugInput || !slugData) return;

                // Auto-suggest on page load if no slug is saved yet
                if (!slugInput.value) {
                    var value = _buildSlugValue();
                    if (value) {
                        _slugCheck(value, slugData, tokenInput, modelInput, function (slug) {
                            slugInput.value = slug;
                            var sc = document.querySelector('.slug-current');
                            if (sc) sc.value = slug;
                            _slugFeedback(slugInput, 'valid', '✓ Suggested — save to confirm');
                        });
                    }
                }

                // Real-time uniqueness check as user types (debounced 600ms)
                var typingTimer;
                slugInput.addEventListener('input', function () {
                    clearTimeout(typingTimer);
                    slugInput.classList.remove('is-valid', 'is-invalid');
                    var fb = document.querySelector('.slug-feedback');
                    if (fb) fb.remove();
                    var val = slugInput.value.trim();
                    if (!val) return;
                    typingTimer = setTimeout(function () {
                        _slugCheck(val, slugData, tokenInput, modelInput, function (returned) {
                            if (returned === slugInput.value) {
                                _slugFeedback(slugInput, 'valid', '✓ Available');
                            } else {
                                _slugFeedback(slugInput, 'invalid', '✗ Taken — suggestion: ' + returned);
                            }
                        });
                    }, 600);
                });
            });
        })();

        document.addEventListener('DOMContentLoaded', function () {
            $(function () {
                $(document).on('click', '[data-bb-toggle="delete-language"]', function (e) {
                    e.preventDefault();
                    if (! confirm('{{ __('Are you sure you want to delete this language?') }}')) {
                        return;
                    }
                    var url = $(this).data('url');
                    var $this = $(this);
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _method: 'DELETE',
                            _token: $(this).closest('form').find('input[name="_token"]').val(),
                        },
                        success: function (response) {
                            if (response.error) {
                                return;
                            }
                            $this.closest('li').remove();
                            if (! $('.list-group li').length) {
                                $('.list-group').html('<div class="alert alert-warning mb-0"><small>{{ __('You have not added any language yet!') }}</small></div>');
                            }
                        }
                    });
                });
            });
        });
    </script>

@endsection
