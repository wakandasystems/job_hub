@php
    Theme::asset()->add('avatar-css', 'vendor/core/plugins/job-board/css/avatar.css');
    Theme::asset()->add('tagify-css', 'vendor/core/core/base/libraries/tagify/tagify.css');
    Theme::asset()->container('footer')->add('cropper-js', 'vendor/core/plugins/job-board/libraries/cropper.js', ['jquery']);
    Theme::asset()->container('footer')->add('avatar-js', 'vendor/core/plugins/job-board/js/avatar.js');
    Theme::asset()->container('footer')->add('editor-lib-js', config('core.base.general.editor.' . BaseHelper::getRichEditor() . '.js'));
    Theme::asset()->container('footer')->add('editor-js', 'vendor/core/core/base/js/editor.js');
    Theme::asset()->container('footer')->add('tagify-js', 'vendor/core/core/base/libraries/tagify/tagify.js');
    Theme::asset()->container('footer')->add('tag-js', 'vendor/core/core/base/js/tags.js');
    Theme::asset()->container('footer')->add('location-js', asset('vendor/core/plugins/location/js/location.js'), ['jquery']);
@endphp

@extends(JobBoardHelper::viewPath('dashboard.layouts.master'))

@section('header')
    @include('plugins/job-board::themes.dashboard.layouts.header')

    <style>
        label.required::after {
            content: ' *';
            color: #e74c3c;
            font-weight: bold;
        }

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
@endsection

@section('content')
    <div class="crop-avatar user-profile-section">
        <x-core::card>
            <x-core::card.header>
                <x-core::card.title>{{ __('My Profile') }}</x-core::card.title>
            </x-core::card.header>

            <x-core::card.body>
                @if ($account->avatar_id)
                    <form id="delete-avatar-form" method="POST" action="{{ route('public.account.avatar.destroy') }}">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif

                {!! Form::open(['route' => 'public.account.post.settings', 'method' => 'POST', 'files' => true]) !!}
                    <div class="avatar-view d-flex align-items-center gap-3 mb-4">
                        <img src="{{ $account->avatar_url }}" id="profile-img" alt="{{ $account->name }}" class="avatar avatar-xl rounded-circle">
                        <button type="button" class="btn btn-primary">{{ __('Upload Avatar') }}</button>

                        @if ($account->avatar_id)
                            <button type="button" class="btn btn-danger btn-remove-avatar" data-confirm="{{  __('Are you sure you want to remove this avatar?') }}">
                                <x-core::icon name="ti ti-trash"/>
                            </button>
                        @endif
                    </div>

                    @php
                        $rawFormHtml = $form
                            ->when($account->type->getValue() === 'job-seeker', function ($form) use ($languages, $languageForm) {
                                return $form->addAfter('favorite_tags', 'languages', 'html', \Botble\Base\Forms\FieldOptions\HtmlFieldOption::make()->content(
                                    view(Theme::getThemeNamespace('views.job-board.account.partials.languages'), compact('languages', 'languageForm'))->render()
                                ));
                            })
                            ->contentOnly()
                            ->renderForm(showStart: false, showEnd: false);

                        if ($account->isJobSeeker()) {
                            [$personalHtml, $rest]      = array_pad(explode('<!-- SECTION:skills -->', $rawFormHtml, 2), 2, '');
                            [$skillsHtml, $rest]        = array_pad(explode('<!-- SECTION:profile -->', $rest, 2), 2, '');
                            [$profileHtml, $docsHtml]   = array_pad(explode('<!-- SECTION:documents -->', $rest, 2), 2, '');
                        } else {
                            $personalHtml = $rawFormHtml;
                            $skillsHtml = $profileHtml = $docsHtml = '';
                        }
                    @endphp

                    @if($account->isJobSeeker())
                        <ul class="nav nav-tabs mb-4 border-bottom" id="settingsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-semibold" id="tab-personal-btn" data-bs-toggle="tab" data-bs-target="#tab-personal" type="button" role="tab" aria-controls="tab-personal" aria-selected="true">
                                    <i class="fi-rr-user me-1"></i>{{ __('Personal') }}
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold" id="tab-skills-btn" data-bs-toggle="tab" data-bs-target="#tab-skills" type="button" role="tab" aria-controls="tab-skills" aria-selected="false">
                                    <i class="fi-rr-star me-1"></i>{{ __('Skills') }}
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold" id="tab-profile-btn" data-bs-toggle="tab" data-bs-target="#tab-profile" type="button" role="tab" aria-controls="tab-profile" aria-selected="false">
                                    <i class="fi-rr-settings me-1"></i>{{ __('Profile') }}
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold" id="tab-documents-btn" data-bs-toggle="tab" data-bs-target="#tab-documents" type="button" role="tab" aria-controls="tab-documents" aria-selected="false">
                                    <i class="fi-rr-document me-1"></i>{{ __('CV & Bio') }}
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content" id="settingsTabContent">
                            <div class="tab-pane fade show active" id="tab-personal" role="tabpanel" aria-labelledby="tab-personal-btn">
                                {!! $personalHtml !!}
                            </div>
                            <div class="tab-pane fade" id="tab-skills" role="tabpanel" aria-labelledby="tab-skills-btn">
                                {!! $skillsHtml !!}
                            </div>
                            <div class="tab-pane fade" id="tab-profile" role="tabpanel" aria-labelledby="tab-profile-btn">
                                {!! $profileHtml !!}
                            </div>
                            <div class="tab-pane fade" id="tab-documents" role="tabpanel" aria-labelledby="tab-documents-btn">
                                {!! $docsHtml !!}
                            </div>
                        </div>
                    @else
                        {!! $personalHtml !!}
                    @endif

                    <div class="text-end mt-3">
                        <x-core::button type="submit" color="primary">{{ __('Save All Changes') }}</x-core::button>
                    </div>
                {!! Form::close() !!}
            </x-core::card.body>
        </x-core::card>

        {{-- CV Upload / Scoring Modal --}}
        <div class="modal fade" id="cvUploadModal" tabindex="-1" aria-labelledby="cvUploadModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-semibold" id="cvUploadModalLabel">{{ __('CV Analysis') }}</h5>
                    </div>
                    <div class="modal-body px-4 pb-4">

                        {{-- Stage 1: uploading --}}
                        <div id="cv-stage-uploading" class="text-center py-4">
                            <div class="spinner-border text-primary mb-3" role="status" style="width:3rem;height:3rem;"></div>
                            <p class="fw-semibold mb-1">{{ __('Uploading your CV…') }}</p>
                            <p class="color-text-paragraph-2 font-sm mb-0">{{ __('Please wait') }}</p>
                        </div>

                        {{-- Stage 2: analyzing --}}
                        <div id="cv-stage-analyzing" class="text-center py-4" style="display:none!important">
                            <div class="mb-3" style="position:relative;display:inline-block;">
                                <div class="spinner-border text-success" role="status" style="width:3rem;height:3rem;"></div>
                                <i class="fi-rr-brain-circuit" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#22c55e;"></i>
                            </div>
                            <p class="fw-semibold mb-1">{{ __('Analyzing with AI…') }}</p>
                            <p class="color-text-paragraph-2 font-sm mb-0">{{ __('Scoring your skills, experience &amp; keywords') }}</p>
                        </div>

                        {{-- Stage 3: results --}}
                        <div id="cv-stage-results" style="display:none!important">
                            <div id="cv-score-card-modal"></div>
                            <div class="d-flex justify-content-end mt-3">
                                <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">{{ __('Done') }}</button>
                            </div>
                        </div>

                        {{-- Error state --}}
                        <div id="cv-stage-error" class="text-center py-4" style="display:none!important">
                            <i class="fi-rr-exclamation text-danger" style="font-size:2.5rem;"></i>
                            <p class="fw-semibold mt-3 mb-1">{{ __('Upload failed') }}</p>
                            <p id="cv-error-msg" class="color-text-paragraph-2 font-sm mb-3"></p>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="cvScoreHistoryModal" tabindex="-1" aria-labelledby="cvScoreHistoryModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold" id="cvScoreHistoryModalLabel">{{ __('CV Score History') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div id="cv-history-loading" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status" style="width:2rem;height:2rem;"></div>
                        </div>
                        <div id="cv-history-empty" class="text-center py-4" style="display:none">
                            <p class="color-text-paragraph-2 mb-0">{{ __('No previous CV scores found.') }}</p>
                        </div>
                        <div id="cv-history-list" style="display:none"></div>
                        <nav id="cv-history-pagination" class="mt-3" style="display:none">
                            <ul class="pagination justify-content-center mb-0" id="cv-history-pages"></ul>
                        </nav>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    </div>
                </div>
            </div>
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
                        <button type="button" id="account-language-submit" class="btn btn-primary">{{ __('Add') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="avatar-modal" tabindex="-1" role="dialog" aria-labelledby="avatar-modal-label" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <form class="avatar-form" method="post" action="{{ route('public.account.avatar') }}" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h4 class="modal-title" id="avatar-modal-label">
                                <strong>{{ __('Profile Image') }}</strong>
                            </h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="avatar-body">
                                <div class="avatar-upload">
                                    <input class="avatar-src" name="avatar_src" type="hidden">
                                    <input class="avatar-data" name="avatar_data" type="hidden">
                                    @csrf
                                    <label for="avatarInput">{{ __('New image') }}</label>
                                    <input class="avatar-input" id="avatarInput" name="avatar_file" type="file">
                                </div>

                                <div class="loading" tabindex="-1" role="img" aria-label="{{ __('Loading') }}"></div>

                                <div class="row">
                                    <div class="col-md-9">
                                        <div class="avatar-wrapper"></div>
                                        <div class="error-message text-danger" style="display: none"></div>
                                    </div>
                                    <div class="col-md-3 avatar-preview-wrapper">
                                        <div class="avatar-preview preview-lg"></div>
                                        <div class="avatar-preview preview-md"></div>
                                        <div class="avatar-preview preview-sm"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{ __('Close') }}</button>
                            <button class="btn btn-outline-primary avatar-save" type="submit">{{ __('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        'use strict';

        var RV_MEDIA_URL = {
            base: '{{ url('') }}',
            filebrowserImageBrowseUrl: false,
            media_upload_from_editor: '{{ route('public.account.upload-from-editor') }}'
        }

        function setImageValue(file) {
            $('.mce-btn.mce-open').parent().find('.mce-textbox').val(file);
        }
    </script>

    <iframe id="form_target" name="form_target" style="display:none"></iframe>
    <form id="tinymce_form" action="{{ route('public.account.upload-from-editor') }}" target="form_target" method="post" enctype="multipart/form-data"
          style="width:0; height:0; overflow:hidden; display: none;">
        @csrf
        <input name="upload" id="upload_file" type="file" onchange="$('#tinymce_form').submit();this.value='';">
        <input type="hidden" value="tinymce" name="upload_type">
    </form>

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
                $(document).on('select2:open', '#addLanguageModal select[name="language"]', function () {
                    setTimeout(function () {
                        var searchInput = document.querySelector('.select2-dropdown .select2-search__field');

                        if (searchInput) {
                            searchInput.focus();
                            searchInput.select();
                        }
                    }, 50);
                });

                var existingAccountLanguages = @json($languages->pluck('language')->values());

                $('#addLanguageModal').on('shown.bs.modal', function () {
                    var $language = $('#account-language-form select[name="language"]');

                    existingAccountLanguages.forEach(function (language) {
                        $language.find('option[value="' + language + '"]').prop('disabled', true);
                    });

                    if (existingAccountLanguages.indexOf($language.val()) !== -1) {
                        var firstAvailable = $language.find('option:not(:disabled)').first().val() || '';
                        $language.val(firstAvailable).trigger('change');
                    }
                });

                $('#account-language-submit').on('click', function (e) {
                    e.preventDefault();
                    if ($(this).prop('disabled')) return;
                    $('#account-language-form').trigger('submit');
                });

                $(document).on('submit', '#account-language-form', function (e) {
                    e.preventDefault();

                    var $button = $('#account-language-submit');
                    var $form = $(this);
                    var $language = $form.find('select[name="language"]');

                    if (existingAccountLanguages.indexOf($language.val()) !== -1) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: @json(__('Language already added')),
                                text: @json(__('Choose a different language to add.')),
                            });
                        } else {
                            alert(@json(__('This language is already added. Choose a different language to add.')));
                        }

                        return;
                    }

                    if (! $language.val()) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: @json(__('No language available')),
                                text: @json(__('All available languages have already been added.')),
                            });
                        } else {
                            alert(@json(__('All available languages have already been added.')));
                        }

                        return;
                    }

                    $form.find('.invalid-feedback').text('').hide();
                    $form.find('.is-invalid').removeClass('is-invalid');
                    $button.prop('disabled', true).addClass('button-loading');

                    $.ajax({
                        url: $form.attr('action'),
                        type: 'POST',
                        data: $form.serialize(),
                        dataType: 'json',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        success: function () {
                            $('form.dirty-check').removeClass('dirty').trigger('reinitialize.areYouSure');
                            window.location.reload();
                        },
                        error: function (xhr) {
                            var errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {};
                            var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : @json(__('Unable to add language.'));
                            var hasFieldError = false;

                            Object.keys(errors).forEach(function (field) {
                                var fieldName = field.replace(/\./g, '_');
                                var $input = $form.find('[name="' + fieldName + '"]');
                                var text = errors[field][0] || message;

                                if ($input.length) {
                                    hasFieldError = true;
                                    $input.addClass('is-invalid');
                                    $('#' + $input.attr('id') + '-error').text(text).show();

                                    if ($input.hasClass('select2-hidden-accessible')) {
                                        $input.next('.select2').find('.select2-selection').addClass('is-invalid');
                                    }
                                }
                            });

                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: hasFieldError ? 'warning' : 'error',
                                    title: hasFieldError ? @json(__('Please check the language form.')) : @json(__('Unable to add language.')),
                                    text: message,
                                });
                            } else if (! hasFieldError && typeof Botble !== 'undefined' && Botble.showError) {
                                Botble.showError(message);
                            } else if (! hasFieldError && typeof Theme !== 'undefined' && Theme.showError) {
                                Theme.showError(message);
                            } else if (! hasFieldError) {
                                alert(message);
                            }
                        },
                        complete: function () {
                            $button.prop('disabled', false).removeClass('button-loading');
                        }
                    });
                });
                // ── CV auto-upload & scoring ──────────────────────────────────────
                (function () {
                    var UPLOAD_URL   = @json(route('public.account.upload-resume-score'));
                    var DELETE_URL   = @json(route('public.account.delete-resume'));
                    var HISTORY_URL  = @json(route('public.account.cv-score-history'));
                    var CSRF_TOKEN   = $('input[name="_token"]').first().val();
                    var SERVICES_URL = @json(route('public.account.career-services'));

                    function showStage(name) {
                        $('#cv-stage-uploading, #cv-stage-analyzing, #cv-stage-results, #cv-stage-error')
                            .css('display', 'none');
                        $('#cv-stage-' + name).css('display', '');
                    }

                    function scoreColor(score) {
                        if (score >= 88) return { color: '#22c55e', label: '{{ __('Excellent') }}' };
                        if (score >= 75) return { color: '#3b82f6', label: '{{ __('Good') }}' };
                        if (score >= 60) return { color: '#f59e0b', label: '{{ __('Fair') }}' };
                        return { color: '#ef4444', label: '{{ __('Needs improvement') }}' };
                    }

                    function buildScoreCard(data) {
                        var score    = parseInt(data.score) || 0;
                        var feedback = data.feedback || [];
                        var scoredAt = data.scored_at || '';
                        var meta     = scoreColor(score);
                        var color    = meta.color;
                        var label    = meta.label;
                        var dash     = score;

                        var timeAgo = '';
                        if (scoredAt) {
                            try {
                                var d = new Date(scoredAt);
                                var diff = Math.round((Date.now() - d.getTime()) / 60000);
                                timeAgo = diff < 2 ? '{{ __('just now') }}' : diff + ' {{ __('min ago') }}';
                            } catch(e) {}
                        }

                        var feedbackHtml = feedback.map(function (item) {
                            return '<div class="color-text-paragraph-2 font-xs mb-1"><i class="fi-rr-angle-right me-1"></i>' + $('<div>').text(item).html() + '</div>';
                        }).join('');

                        var upsell = score < 75
                            ? '<div class="alert alert-warning d-flex align-items-center gap-3 py-2 px-3 mb-0 mt-3"><i class="fi-rr-star fs-5 text-warning flex-shrink-0"></i><div class="font-sm"><strong>{{ __('Boost your chances') }}</strong> — {{ __('have a career coach professionally review and rewrite your CV.') }} <a href="' + SERVICES_URL + '" class="fw-semibold ms-1">{{ __('View Career Services') }} →</a></div></div>'
                            : '';

                        return '<div class="card border-0 shadow-sm mb-3">' +
                            '<div class="card-body p-4">' +
                              '<div class="d-flex align-items-center justify-content-between mb-3">' +
                                '<h5 class="fw-semibold mb-0">{{ __('Your CV Score') }}</h5>' +
                                '<div class="d-flex align-items-center gap-2">' +
                                  '<a href="#" class="font-xs text-muted text-decoration-underline" data-bs-toggle="modal" data-bs-target="#cvScoreHistoryModal">{{ __('View history') }}</a>' +
                                  '<span class="color-text-paragraph-2 font-xs">' + timeAgo + '</span>' +
                                '</div>' +
                              '</div>' +
                              '<div class="d-flex align-items-center gap-4 mb-0">' +
                                '<div style="position:relative;width:80px;height:80px;flex-shrink:0;">' +
                                  '<svg viewBox="0 0 36 36" width="80" height="80">' +
                                    '<circle cx="18" cy="18" r="15.9" fill="none" stroke="#e5e7eb" stroke-width="3"></circle>' +
                                    '<circle cx="18" cy="18" r="15.9" fill="none" stroke="' + color + '" stroke-width="3" stroke-dasharray="' + dash + ', 100" stroke-linecap="round" transform="rotate(-90 18 18)"></circle>' +
                                  '</svg>' +
                                  '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;">' +
                                    '<span class="fw-bold" style="font-size:16px;color:' + color + '">' + score + '</span>' +
                                    '<span style="font-size:9px;color:#6b7280">/ 100</span>' +
                                  '</div>' +
                                '</div>' +
                                '<div class="flex-grow-1">' +
                                  '<div class="fw-semibold mb-1" style="color:' + color + '">' + label + '</div>' +
                                  feedbackHtml +
                                '</div>' +
                              '</div>' +
                              upsell +
                            '</div>' +
                          '</div>';
                    }

                    function refreshInPageScoreCard(data) {
                        var card = buildScoreCard(data);
                        var $existing = $('#cv-score-card-inpage');
                        if ($existing.length) {
                            $existing.html(card);
                        } else {
                            var $resumeField = $('input[name="resume"]').closest('.form-group, .mb-3');
                            $resumeField.before('<div id="cv-score-card-inpage">' + card + '</div>');
                        }
                    }

                    function injectRemoveButton() {
                        if ($('#btn-remove-cv').length) return;
                        var $resumeField = $('input[name="resume"]').closest('.form-group, .mb-3');
                        $resumeField.append(
                            '<button type="button" id="btn-remove-cv" class="btn btn-sm btn-outline-danger mt-2">' +
                            '<i class="fi-rr-trash me-1"></i>{{ __('Remove CV') }}</button>'
                        );
                    }

                    function removeRemoveButton() {
                        $('#btn-remove-cv').remove();
                    }

                    // Show remove button if CV already exists on page load
                    @if($account->resume)
                    $(document).ready(function () { injectRemoveButton(); });
                    @endif

                    function uploadResumeFile(file, input) {
                        var formData = new FormData();
                        formData.append('file', file);
                        formData.append('_token', CSRF_TOKEN);
                        formData.append('cv_upload_consent', '1');

                        var $modal = $('#cvUploadModal');
                        showStage('uploading');
                        $modal.modal('show');

                        $.ajax({
                            url: UPLOAD_URL,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            xhr: function () {
                                // Switch to "analyzing" stage once upload bytes are sent
                                var xhr = new window.XMLHttpRequest();
                                xhr.upload.addEventListener('load', function () {
                                    showStage('analyzing');
                                });
                                return xhr;
                            },
                            success: function (response) {
                                if (response.error) {
                                    $('#cv-error-msg').text(response.message || '{{ __('An error occurred.') }}');
                                    showStage('error');
                                    return;
                                }
                                var data = response.data || {};
                                $('#cv-score-card-modal').html(buildScoreCard(data));
                                showStage('results');
                                refreshInPageScoreCard(data);
                                injectRemoveButton();
                                // Neutralise the file input so postSettings won't re-upload
                                $(input).val('');
                            },
                            error: function (xhr) {
                                var msg = (xhr.responseJSON && xhr.responseJSON.message) || '{{ __('Upload failed. Please try again.') }}';
                                $('#cv-error-msg').text(msg);
                                showStage('error');
                            }
                        });
                    }

                    function askCvUploadConsent(onAccept, onReject) {
                        var title = @json(__('Allow employers to view this CV?'));
                        var text = @json(__('Your CV may be used to show your experience to verified employers and improve your job matches. This helps employers assess you faster and can increase your chances of being contacted. Only upload a CV you are comfortable sharing under the platform terms.'));

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'info',
                                title: title,
                                text: text,
                                showCancelButton: true,
                                confirmButtonText: @json(__('I Accept, Upload CV')),
                                cancelButtonText: @json(__('Cancel')),
                                confirmButtonColor: '#3c65f5',
                            }).then(function (result) {
                                if (result.isConfirmed) {
                                    onAccept();
                                } else {
                                    onReject();
                                }
                            });

                            return;
                        }

                        if (window.confirm(title + '\n\n' + text)) {
                            onAccept();
                        } else {
                            onReject();
                        }
                    }

                    // Intercept file input → consent, auto-upload, then score
                    $(document).on('change', 'input[name="resume"]', function () {
                        var input = this;
                        var file = input.files[0];
                        if (!file) return;

                        askCvUploadConsent(function () {
                            uploadResumeFile(file, input);
                        }, function () {
                            $(input).val('');
                        });
                    });

                    // CV Score History modal
                    (function () {
                        var historyPage = 1;

                        function scoreColorMini(score) {
                            if (score >= 88) return '#22c55e';
                            if (score >= 75) return '#3b82f6';
                            if (score >= 60) return '#f59e0b';
                            return '#ef4444';
                        }

                        function scoreLabelMini(score) {
                            if (score >= 88) return '{{ __('Excellent') }}';
                            if (score >= 75) return '{{ __('Good') }}';
                            if (score >= 60) return '{{ __('Fair') }}';
                            return '{{ __('Needs improvement') }}';
                        }

                        function renderHistoryItem(entry) {
                            var score   = parseInt(entry.score) || 0;
                            var data    = entry.data || {};
                            var color   = scoreColorMini(score);
                            var label   = scoreLabelMini(score);
                            var archivedAt = entry.archived_at || '';
                            var dateStr = '';
                            if (archivedAt) {
                                try { dateStr = new Date(archivedAt).toLocaleDateString(undefined, {year:'numeric',month:'short',day:'numeric'}); } catch(e) {}
                            }
                            var feedback = data.feedback || [];
                            var feedbackHtml = feedback.slice(0, 3).map(function (f) {
                                return '<div class="color-text-paragraph-2 font-xs mb-1"><i class="fi-rr-angle-right me-1"></i>' + $('<div>').text(f).html() + '</div>';
                            }).join('');
                            if (feedback.length > 3) {
                                feedbackHtml += '<div class="color-text-paragraph-2 font-xs text-muted">+' + (feedback.length - 3) + ' more</div>';
                            }

                            return '<div class="d-flex align-items-start gap-3 py-3 border-bottom">' +
                                '<div style="position:relative;width:60px;height:60px;flex-shrink:0;">' +
                                  '<svg viewBox="0 0 36 36" width="60" height="60">' +
                                    '<circle cx="18" cy="18" r="15.9" fill="none" stroke="#e5e7eb" stroke-width="3"></circle>' +
                                    '<circle cx="18" cy="18" r="15.9" fill="none" stroke="' + color + '" stroke-width="3" stroke-dasharray="' + score + ', 100" stroke-linecap="round" transform="rotate(-90 18 18)"></circle>' +
                                  '</svg>' +
                                  '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;">' +
                                    '<span class="fw-bold" style="font-size:13px;color:' + color + '">' + score + '</span>' +
                                    '<span style="font-size:8px;color:#6b7280">/ 100</span>' +
                                  '</div>' +
                                '</div>' +
                                '<div class="flex-grow-1">' +
                                  '<div class="d-flex align-items-center justify-content-between mb-1">' +
                                    '<span class="fw-semibold font-sm" style="color:' + color + '">' + label + '</span>' +
                                    '<span class="color-text-paragraph-2 font-xs">' + dateStr + '</span>' +
                                  '</div>' +
                                  feedbackHtml +
                                '</div>' +
                              '</div>';
                        }

                        function loadHistory(page) {
                            historyPage = page;
                            $('#cv-history-loading').show();
                            $('#cv-history-list, #cv-history-empty, #cv-history-pagination').hide();

                            $.get(HISTORY_URL, { page: page }, function (response) {
                                $('#cv-history-loading').hide();
                                var d = response.data || {};
                                var items = d.items || [];
                                if (!items.length) {
                                    $('#cv-history-empty').show();
                                    return;
                                }

                                var html = items.map(renderHistoryItem).join('');
                                $('#cv-history-list').html(html).show();

                                // Pagination
                                var lastPage = d.last_page || 1;
                                if (lastPage > 1) {
                                    var pages = '';
                                    for (var i = 1; i <= lastPage; i++) {
                                        pages += '<li class="page-item' + (i === page ? ' active' : '') + '">' +
                                            '<a class="page-link cv-history-page" href="#" data-page="' + i + '">' + i + '</a>' +
                                        '</li>';
                                    }
                                    $('#cv-history-pages').html(pages);
                                    $('#cv-history-pagination').show();
                                }
                            });
                        }

                        $('#cvScoreHistoryModal').on('show.bs.modal', function () {
                            loadHistory(1);
                        });

                        $(document).on('click', '.cv-history-page', function (e) {
                            e.preventDefault();
                            loadHistory(parseInt($(this).data('page')));
                        });
                    })();

                    // Remove CV
                    $(document).on('click', '#btn-remove-cv', function () {
                        if (! confirm('{{ __('Remove your uploaded CV? This cannot be undone.') }}')) return;
                        var $btn = $(this).prop('disabled', true);
                        $.ajax({
                            url: DELETE_URL,
                            type: 'POST',
                            data: { _method: 'DELETE', _token: CSRF_TOKEN },
                            success: function (response) {
                                if (response.error) { $btn.prop('disabled', false); return; }
                                $('#cv-score-card-inpage').remove();
                                $('input[name="resume"]').closest('.form-group, .mb-3').find('.form-text, small').remove();
                                removeRemoveButton();
                            },
                            error: function () { $btn.prop('disabled', false); }
                        });
                    });
                })();
                // ─────────────────────────────────────────────────────────────────

                $(document).on('click', '[data-bb-toggle="delete-language"]', function (e) {
                    e.preventDefault();
                    var url = $(this).data('url');
                    var $this = $(this);
                    var language = $this.data('language');

                    var deleteLanguage = function () {
                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: {
                                _method: 'DELETE',
                                _token: $this.closest('form').find('input[name="_token"]').val(),
                            },
                            success: function (response) {
                                if (response.error) {
                                    return;
                                }
                                $this.closest('li').remove();
                                existingAccountLanguages = existingAccountLanguages.filter(function (item) {
                                    return item !== language;
                                });
                                $('#account-language-form select[name="language"]')
                                    .find('option[value="' + language + '"]')
                                    .prop('disabled', false);

                                if (! $('.list-group li').length) {
                                    $('.list-group').html('<div class="alert alert-warning mb-0"><small>{{ __('You have not added any language yet!') }}</small></div>');
                                }
                            }
                        });
                    };

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: @json(__('Delete this language?')),
                            text: @json(__('This cannot be undone.')),
                            showCancelButton: true,
                            confirmButtonText: @json(__('Delete')),
                            cancelButtonText: @json(__('Cancel')),
                            confirmButtonColor: '#d33',
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                deleteLanguage();
                            }
                        });

                        return;
                    }

                    deleteLanguage();
                });
            });
        });
    </script>

@endsection
