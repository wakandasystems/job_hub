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

        /* Square icon buttons */
        .btn-remove-avatar,
        [data-bb-toggle="delete-language"] {
            width: 34px;
            height: 34px;
            min-width: 34px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border-radius: 6px;
            line-height: 1;
        }
        .btn-remove-avatar svg,
        .btn-remove-avatar i,
        [data-bb-toggle="delete-language"] svg,
        [data-bb-toggle="delete-language"] i {
            display: block;
            pointer-events: none;
            width: 16px;
            height: 16px;
            font-size: 16px;
            flex-shrink: 0;
        }

        /* Native badge — force white text */
        .badge.bg-primary { color: #fff !important; }
    </style>
@endsection

@section('content')
    <div class="crop-avatar user-profile-section">
        {{-- Wakanda Verification Section --}}
        @if (!$account->isEmployer() && !$account->wakanda_verified)
        <x-core::card class="mt-3">
            <x-core::card.body>
                <div class="d-flex align-items-center gap-3">
                    <div style="background:#6f42c1;border-radius:8px;padding:10px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14l-5-4.87 6.91-1.01L12 2z"/></svg>
                    </div>
                    <div class="flex-grow-1">
                        @php
                            $hasPendingRequest = \Botble\JobBoard\Models\WakandaVerificationRequest::where('account_id', $account->id)->where('status', 'pending')->exists();
                        @endphp
                        @if ($hasPendingRequest)
                            <div class="fw-semibold fs-16">{{ __('Verification Pending') }}</div>
                            <div class="text-muted fs-13">{{ __('Your request is under review. We will notify you when approved.') }}</div>
                        @else
                            <div class="fw-semibold fs-16">{{ __('Get Wakanda Verified') }}</div>
                            <div class="text-muted fs-13">{{ __('Stand out with a purple Wakanda badge. Our team will review your skills, experience, and interview you. Costs :cost credits.', ['cost' => setting('wakanda_verification_cost', 5)]) }}</div>
                            <a href="{{ route('public.account.wakanda-verification.checkout') }}"
                               class="btn btn-sm mt-2 text-white"
                               style="background:#6f42c1;">
                                {{ __('Request Verification — :cost credits', ['cost' => setting('wakanda_verification_cost', 5)]) }}
                            </a>
                        @endif
                    </div>
                </div>
            </x-core::card.body>
        </x-core::card>

        @endif


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
                            <p class="color-text-paragraph-2 font-sm mb-0">{{ __('Scoring your skills, experience & keywords') }}</p>
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

        <div class="modal fade" id="prefillProfileModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-body text-center py-4 px-4">
                        <div class="mb-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10" style="width:52px;height:52px;">
                                <i class="ti ti-sparkles text-primary fs-3"></i>
                            </span>
                        </div>
                        <h6 class="fw-semibold mb-2">{{ __('Prefill profile from CV data?') }}</h6>
                        <p class="text-muted small mb-4">{{ __('If a linked CV Builder profile exists, we will use that first. Otherwise we will analyze your uploaded CV and fill missing profile sections, skills, languages, education, and experience.') }}</p>
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="button" class="btn btn-primary px-4" id="prefillProfileConfirmBtn">{{ __('Yes, prefill my profile') }}</button>
                        </div>
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
                        <div class="mb-3">
                            <label class="form-label" for="lang-language">{{ __('Language') }}</label>
                            <select id="lang-language" name="language" class="form-select">
                                @foreach(\Botble\Base\Supports\Language::getLocales() as $code => $name)
                                    <option value="{{ $code }}" @selected($code === 'en')>{{ $name }}</option>
                                @endforeach
                            </select>
                            <div id="lang-language-error" class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="lang-level">{{ __('Level') }}</label>
                            <select id="lang-level" name="language_level_id" class="form-select">
                                @foreach(\Botble\JobBoard\Models\LanguageLevel::query()->pluck('name','id') as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" id="lang-native" name="is_native" class="form-check-input" value="1">
                            <label class="form-check-label" for="lang-native">{{ __('Is native?') }}</label>
                            <div class="form-text">{{ __('Check this if you are a native speaker of this language.') }}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="button" id="account-language-submit" class="btn btn-primary">{{ __('Add') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="avatar-modal" tabindex="-1" role="dialog" aria-labelledby="avatar-modal-label" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form class="avatar-form" method="post" action="{{ route('public.account.avatar') }}" enctype="multipart/form-data">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-semibold" id="avatar-modal-label">{{ __('Update Profile Photo') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body px-4 pb-2">
                            <input class="avatar-src" name="avatar_src" type="hidden">
                            <input class="avatar-data" name="avatar_data" type="hidden">
                            @csrf

                            {{-- Drop zone / file picker (shown before image selected) --}}
                            <div id="avatar-dropzone" class="border border-2 border-dashed rounded-3 text-center py-5 px-3 mb-3" style="cursor:pointer;border-color:#dee2e6!important;">
                                <div class="mb-2" style="font-size:2.5rem;line-height:1;">📷</div>
                                <p class="fw-semibold mb-1">{{ __('Choose a photo') }}</p>
                                <p class="text-muted small mb-3">{{ __('JPG or PNG, max 5 MB') }}</p>
                                <label for="avatarInput" class="btn btn-primary btn-sm px-4" style="cursor:pointer;">
                                    {{ __('Browse…') }}
                                </label>
                                <input class="avatar-input" id="avatarInput" name="avatar_file" type="file" accept=".jpg,.jpeg,.png" style="display:none;">
                            </div>

                            {{-- Crop area (shown after image selected) --}}
                            <div id="avatar-crop-area" style="display:none;">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="flex-grow-1">
                                        <p class="text-muted small mb-2">{{ __('Drag to reposition · Scroll to zoom') }}</p>
                                        <div class="avatar-wrapper rounded-2 overflow-hidden" style="max-height:340px;"></div>
                                    </div>
                                    <div class="flex-shrink-0 text-center" style="width:88px;">
                                        <p class="text-muted small mb-2">{{ __('Preview') }}</p>
                                        <div class="avatar-preview preview-lg rounded-circle mx-auto mb-2" style="width:80px;height:80px;overflow:hidden;border:2px solid #dee2e6;"></div>
                                        <p class="text-muted" style="font-size:10px;">80 × 80</p>
                                    </div>
                                </div>
                            </div>

                            <div class="loading text-center py-3" tabindex="-1" style="display:none;">
                                <div class="spinner-border text-primary" role="status" style="width:2rem;height:2rem;"></div>
                                <p class="text-muted small mt-2 mb-0">{{ __('Uploading…') }}</p>
                            </div>
                            <div class="error-message text-danger small mt-2" style="display:none;"></div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button class="btn btn-light" type="button" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button class="btn btn-primary avatar-save px-4" type="submit">{{ __('Save Photo') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        'use strict';

        // ── Avatar modal UX ───────────────────────────────────────────────────────
        (function () {
            // Show crop area when an image is loaded into the cropper
            var $modal    = $('#avatar-modal');
            var $dropzone = $('#avatar-dropzone');
            var $cropArea = $('#avatar-crop-area');

            // Reset to dropzone state when modal opens
            $modal.on('show.bs.modal', function () {
                $dropzone.show();
                $cropArea.hide();
                $modal.find('.error-message').hide().html('');
            });

            // Make the whole drop zone a click target for the file input
            $dropzone.on('click', function (e) {
                if (!$(e.target).is('label, input')) {
                    $('#avatarInput').trigger('click');
                }
            });

            // When a file is picked, switch to crop area
            $(document).on('change', '#avatarInput', function () {
                if (this.files && this.files.length) {
                    $dropzone.hide();
                    $cropArea.show();
                }
            });
        })();
        // ─────────────────────────────────────────────────────────────────────────

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
                var existingAccountLanguages = @json($languages->pluck('language')->values());
                var LANG_STORE_URL = @json(route('public.account.languages.store'));
                var CSRF_TOKEN_LANG = $('meta[name="csrf-token"]').attr('content')
                    || $('#account-language-form input[name="_token"]').val()
                    || $('input[name="_token"]').first().val();

                function languageField() {
                    var $field = $('#lang-language');

                    return $field.length ? $field : $('#account-language-form select[name="language"]');
                }

                function levelField() {
                    var $field = $('#lang-level');

                    return $field.length ? $field : $('#account-language-form select[name="language_level_id"]');
                }

                function nativeField() {
                    var $field = $('#lang-native');

                    return $field.length ? $field : $('#account-language-form input[name="is_native"]');
                }

                function languageFormAction() {
                    return LANG_STORE_URL;
                }

                function languageErrorElement($field) {
                    var id = $field.attr('id');
                    var $error = id ? $('#' + id + '-error') : $();

                    if (! $error.length) {
                        $error = $('#lang-language-error');
                    }

                    if (! $error.length) {
                        $error = $('<div class="invalid-feedback" id="lang-language-error"></div>');
                        $field.closest('.mb-3, .position-relative').append($error);
                    }

                    return $error;
                }

                function showLanguageError(message, $field) {
                    var text = message || {{ \Illuminate\Support\Js::from(__('Failed to save. Please try again.')) }};
                    var $error = languageErrorElement($field);

                    $field.addClass('is-invalid');
                    $field.next('.select2').find('.select2-selection').addClass('is-invalid');
                    $error.text(text).show();

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: {{ \Illuminate\Support\Js::from(__('Unable to add language.')) }},
                            text: text,
                        });
                    } else if (typeof Botble !== 'undefined' && Botble.showError) {
                        Botble.showError(text);
                    } else if (typeof Theme !== 'undefined' && Theme.showError) {
                        Theme.showError(text);
                    }
                }

                // Init Select2 on modal open — dropdownParent keeps it inside modal so typing works
                $('#addLanguageModal').on('shown.bs.modal', function () {
                    var $sel = languageField();
                    if (typeof $.fn.select2 === 'undefined') return;

                    if ($sel.hasClass('select2-hidden-accessible')) {
                        $sel.select2('destroy');
                    }

                    // Disable already-added languages
                    existingAccountLanguages.forEach(function (lang) {
                        $sel.find('option[value="' + lang + '"]').prop('disabled', true);
                    });

                    // If current value is already added, reset to English or first available
                    if (existingAccountLanguages.indexOf($sel.val()) !== -1) {
                        var fallback = existingAccountLanguages.indexOf('en') === -1 ? 'en'
                            : $sel.find('option:not(:disabled)').first().val();
                        $sel.val(fallback);
                    }

                    $sel.select2({
                        width: '100%',
                        allowClear: false,
                        minimumResultsForSearch: 0,
                        dropdownParent: $('#addLanguageModal'),
                    });

                    // Reset button state
                    var $btn = $('#account-language-submit');
                    $btn.prop('disabled', false).removeClass('btn-success').addClass('btn-primary').text({{ \Illuminate\Support\Js::from(__('Add')) }});
                    languageErrorElement($sel).text('').hide();
                    $sel.removeClass('is-invalid');
                    $sel.next('.select2').find('.select2-selection').removeClass('is-invalid');
                });

                $('#account-language-submit').on('click', function () {
                    var $btn  = $(this);
                    var $language = languageField();
                    var $level = levelField();
                    var $native = nativeField();
                    var lang  = $language.val();
                    var level = $level.val();
                    var native = $native.is(':checked') ? 1 : 0;

                    if (! lang) {
                        showLanguageError({{ \Illuminate\Support\Js::from(__('Choose a language before saving.')) }}, $language);
                        return;
                    }

                    // Duplicate check
                    if (existingAccountLanguages.indexOf(lang) !== -1) {
                        showLanguageError({{ \Illuminate\Support\Js::from(__('This language has already been added.')) }}, $language);
                        return;
                    }

                    // Step 1: Saving…
                    $btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm me-1" role="status"></span>' + {{ \Illuminate\Support\Js::from(__('Saving…')) }}
                    );
                    languageErrorElement($language).text('').hide();
                    $language.removeClass('is-invalid');
                    $language.next('.select2').find('.select2-selection').removeClass('is-invalid');

                    $.ajax({
                        url: languageFormAction(),
                        type: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': CSRF_TOKEN_LANG,
                        },
                        data: {
                            _token: CSRF_TOKEN_LANG,
                            account_language: lang,
                            language_level_id: level || null,
                            is_native: native,
                        },
                        dataType: 'json',
                        success: function () {
                            // Step 2: Saved ✓
                            $btn.removeClass('btn-primary').addClass('btn-success').html(
                                '<i class="ti ti-check me-1"></i>' + {{ \Illuminate\Support\Js::from(__('Saved!')) }}
                            );
                            if (typeof Botble !== 'undefined' && Botble.showSuccess) {
                                Botble.showSuccess({{ \Illuminate\Support\Js::from(__('Language added successfully.')) }});
                            }

                            // Step 3: close + reload
                            setTimeout(function () {
                                var modal = bootstrap.Modal.getInstance(document.getElementById('addLanguageModal'));
                                if (modal) {
                                    modal.hide();
                                }
                                window.location.reload();
                            }, 900);
                        },
                        error: function (xhr, textStatus, errorThrown) {
                            $btn.prop('disabled', false).removeClass('btn-success').addClass('btn-primary').text({{ \Illuminate\Support\Js::from(__('Add')) }});

                            var json    = xhr.responseJSON || {};
                            var responseText = xhr.responseText || '';
                            var responseSnippet = responseText.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 160);
                            var message = json.message || {{ \Illuminate\Support\Js::from(__('Failed to save. Please try again.')) }};

                            if (! json.message && textStatus === 'parsererror') {
                                message = {{ \Illuminate\Support\Js::from(__('The server returned a redirect or non-JSON response. Please refresh the page and try again.')) }};
                            } else if (! json.message && xhr.status) {
                                message = 'HTTP ' + xhr.status + ': ' + message;
                            }

                            if (window.console && console.error) {
                                console.error('Language save failed', {
                                    status: xhr.status,
                                    statusText: xhr.statusText,
                                    textStatus: textStatus,
                                    errorThrown: errorThrown,
                                    responseURL: xhr.responseURL,
                                    responseJSON: json,
                                    responseSnippet: responseSnippet,
                                });
                            }

                            if (xhr.status === 422 && json.errors && json.errors.account_language) {
                                showLanguageError(json.errors.account_language[0], $language);
                            } else if (xhr.status === 419) {
                                showLanguageError({{ \Illuminate\Support\Js::from(__('Session expired. Please refresh the page.')) }}, $language);
                            } else {
                                showLanguageError(message, $language);
                            }
                        }
                    });
                });
                // ── CV auto-upload & scoring ──────────────────────────────────────
                (function () {
                    var UPLOAD_URL   = @json(route('public.account.upload-resume-score'));
                    var DELETE_URL   = @json(route('public.account.delete-resume'));
                    var HISTORY_URL  = @json(route('public.account.cv-score-history'));
                    var CSRF_TOKEN   = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').first().val();
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
                        var missingPoints = data.missing_points || [];
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

                        if (! missingPoints.length && score < 100) {
                            missingPoints = feedback.reduce(function (items, item) {
                                var text = String(item || '');
                                var points = text.indexOf('too short') !== -1 || text.indexOf('too long') !== -1 ? 7 : 8;

                                if (
                                    text.indexOf('Add a clear ') === 0 ||
                                    text.indexOf('Quantify impact') === 0 ||
                                    text.indexOf('too short') !== -1 ||
                                    text.indexOf('too long') !== -1
                                ) {
                                    items.push({ points: points, action: text });
                                }

                                return items;
                            }, []);
                        }

                        var missingHtml = '';
                        var listedPoints = missingPoints.reduce(function (total, item) {
                            return total + (parseInt(item.points) || 0);
                        }, 0);
                        var pointsTo100 = listedPoints > 0 ? listedPoints : Math.max(0, 100 - score);
                        if (pointsTo100 > 0 && missingPoints.length) {
                            missingHtml = '<div class="border rounded-2 p-3 mt-3 bg-light">' +
                                '<div class="fw-semibold font-sm mb-2">{{ __('What is missing to reach 100?') }} <span class="text-muted fw-normal">' + pointsTo100 + ' {{ __('points available') }}</span></div>' +
                                missingPoints.map(function (item) {
                                    var points = parseInt(item.points) || 0;
                                    var action = $('<div>').text(item.action || '{{ __('Improve this CV section.') }}').html();
                                    var badge = points > 0 ? '<span class="badge bg-light text-dark border flex-shrink-0">+' + points + '</span>' : '';

                                    return '<div class="d-flex align-items-start gap-2 font-xs color-text-paragraph-2 mb-1">' + badge + '<span>' + action + '</span></div>';
                                }).join('') +
                            '</div>';
                        }

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
	                              missingHtml +
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
                        var ext = file.name.split('.').pop().toLowerCase();
                        if (ext !== 'pdf') {
                            Swal.fire({ icon: 'error', title: '{{ __('Invalid file type') }}', text: '{{ __('Your CV must be a PDF file.') }}', confirmButtonColor: '#6f42c1' });
                            if (input) $(input).val('');
                            return;
                        }
                        if (file.size > 10 * 1024 * 1024) {
                            Swal.fire({ icon: 'error', title: '{{ __('File too large') }}', text: '{{ __('Your CV must not exceed 10 MB.') }}', confirmButtonColor: '#6f42c1' });
                            if (input) $(input).val('');
                            return;
                        }

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

                    function showCvConfirmation(options, onAccept, onReject) {
                        var modalId = 'cvConfirmModal';
                        var $modal = $('#' + modalId);

                        if (! $modal.length) {
                            $('body').append(
                                '<div class="modal fade" id="' + modalId + '" tabindex="-1" aria-hidden="true">' +
                                    '<div class="modal-dialog modal-dialog-centered modal-sm">' +
                                        '<div class="modal-content">' +
                                            '<div class="modal-body text-center py-4 px-4">' +
                                                '<div class="mb-3"><span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10" style="width:52px;height:52px;"><i class="fi-rr-info text-primary fs-3"></i></span></div>' +
                                                '<h6 class="fw-semibold mb-2" data-cv-confirm-title></h6>' +
                                                '<p class="text-muted small mb-4" data-cv-confirm-text></p>' +
                                                '<div class="d-flex gap-2 justify-content-center">' +
                                                    '<button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" data-cv-confirm-cancel></button>' +
                                                    '<button type="button" class="btn btn-primary px-4" data-cv-confirm-ok></button>' +
                                                '</div>' +
                                            '</div>' +
                                        '</div>' +
                                    '</div>' +
                                '</div>'
                            );
                            $modal = $('#' + modalId);
                        }

                        $modal.find('[data-cv-confirm-title]').text(options.title);
                        $modal.find('[data-cv-confirm-text]').text(options.text);
                        $modal.find('[data-cv-confirm-ok]').text(options.confirmText);
                        $modal.find('[data-cv-confirm-cancel]').text(options.cancelText || {{ \Illuminate\Support\Js::from(__('Cancel')) }});

                        var modal = bootstrap.Modal.getOrCreateInstance($modal[0]);
                        $modal.off('click.cvConfirm', '[data-cv-confirm-ok]');
                        $modal.off('hidden.bs.modal.cvConfirm');
                        $modal.data('confirmed', false);
                        $modal.on('click.cvConfirm', '[data-cv-confirm-ok]', function () {
                            $modal.data('confirmed', true);
                            modal.hide();
                            onAccept();
                        });
                        $modal.on('hidden.bs.modal.cvConfirm', function () {
                            if (! $modal.data('confirmed')) {
                                onReject();
                            }
                        });
                        modal.show();
                    }

                    function showAccountActionStatus(options) {
                        var modalId = 'accountActionStatusModal';
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

                        var iconHtml = '';
                        if (options.state === 'loading') {
                            iconHtml = '<span class="spinner-border text-primary" role="status" style="width:2.5rem;height:2.5rem;"></span>';
                        } else if (options.state === 'success') {
                            iconHtml = '<span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:52px;height:52px;"><i class="ti ti-check text-success fs-3"></i></span>';
                        } else {
                            iconHtml = '<span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;"><i class="ti ti-alert-circle text-danger fs-3"></i></span>';
                        }

                        $modal.find('[data-status-icon]').html(iconHtml);
                        $modal.find('[data-status-title]').text(options.title || '');
                        $modal.find('[data-status-text]').text(options.text || '');
                        $modal.find('[data-status-close]')
                            .toggleClass('d-none', options.state === 'loading')
                            .removeClass('btn-danger btn-success btn-primary')
                            .addClass(options.state === 'error' ? 'btn-danger' : (options.state === 'success' ? 'btn-success' : 'btn-primary'));

                        var modal = bootstrap.Modal.getOrCreateInstance($modal[0]);
                        $modal.off('click.statusClose', '[data-status-close]');
                        $modal.on('click.statusClose', '[data-status-close]', function () {
                            modal.hide();
                            if (typeof options.onClose === 'function') {
                                options.onClose();
                            }
                        });

                        modal.show();

                        return {
                            hide: function () {
                                modal.hide();
                            },
                            setState: function (nextOptions) {
                                showAccountActionStatus(nextOptions);
                            }
                        };
                    }

                    function askCvUploadConsent(onAccept, onReject) {
                        showCvConfirmation({
                            icon: 'info',
                            title: {{ \Illuminate\Support\Js::from(__('Allow employers to view this CV?')) }},
                            text: {{ \Illuminate\Support\Js::from(__('Your CV may be used to show your experience to verified employers and improve your job matches. This helps employers assess you faster and can increase your chances of being contacted. Only upload a CV you are comfortable sharing under the platform terms.')) }},
                            confirmText: {{ \Illuminate\Support\Js::from(__('I Accept, Upload CV')) }},
                            cancelText: {{ \Illuminate\Support\Js::from(__('Cancel')) }},
                        }, onAccept, onReject);
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
                        var $btn = $(this).prop('disabled', true);

                        showCvConfirmation({
                            icon: 'warning',
                            title: {{ \Illuminate\Support\Js::from(__('Remove your uploaded CV?')) }},
                            text: {{ \Illuminate\Support\Js::from(__('This cannot be undone.')) }},
                            confirmText: {{ \Illuminate\Support\Js::from(__('Remove')) }},
                            cancelText: {{ \Illuminate\Support\Js::from(__('Cancel')) }},
                        }, function () {
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
                        }, function () {
                            $btn.prop('disabled', false);
                        });
                    });
                })();
                // ── File input client-side validation ─────────────────────────────
                (function () {
                    var rules = {
                        'resume':       { types: ['pdf'],              maxMb: 10, label: 'CV' },
                        'cover_letter': { types: ['pdf'],              maxMb: 10, label: '{{ __('cover letter') }}' },
                        'cover_image':  { types: ['jpg','jpeg','png'], maxMb: 5,  label: '{{ __('cover image') }}' },
                    };

                    $.each(rules, function (name, rule) {
                        $(document).on('change', 'input[name="' + name + '"]', function () {
                            var file = this.files && this.files[0];
                            if (! file) return;
                            var ext = file.name.split('.').pop().toLowerCase();
                            if (rule.types.indexOf(ext) === -1) {
                                var typeList = rule.types.map(function(t){ return t.toUpperCase(); }).join(' or ');
                                Swal.fire({ icon: 'error', title: '{{ __('Invalid file type') }}', text: rule.label.charAt(0).toUpperCase() + rule.label.slice(1) + ' must be ' + typeList + '.', confirmButtonColor: '#6f42c1' });
                                $(this).val('');
                                return;
                            }
                            if (file.size > rule.maxMb * 1024 * 1024) {
                                Swal.fire({ icon: 'error', title: '{{ __('File too large') }}', text: rule.label.charAt(0).toUpperCase() + rule.label.slice(1) + ' must not exceed ' + rule.maxMb + ' MB.', confirmButtonColor: '#6f42c1' });
                                $(this).val('');
                            }
                        });
                    });

                    // Avatar: jpg/png only, max 5 MB
                    $(document).on('change', '#avatarInput', function () {
                        var file = this.files && this.files[0];
                        if (! file) return;
                        var ext = file.name.split('.').pop().toLowerCase();
                        if (['jpg','jpeg','png'].indexOf(ext) === -1) {
                            Swal.fire({ icon: 'error', title: '{{ __('Invalid file type') }}', text: '{{ __('Profile photo must be a JPG or PNG file.') }}', confirmButtonColor: '#6f42c1' });
                            $(this).val(''); return;
                        }
                        if (file.size > 5 * 1024 * 1024) {
                            Swal.fire({ icon: 'error', title: '{{ __('File too large') }}', text: '{{ __('Profile photo must not exceed 5 MB.') }}', confirmButtonColor: '#6f42c1' });
                            $(this).val('');
                        }
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

                    showCvConfirmation({
                        icon: 'warning',
                        title: {{ \Illuminate\Support\Js::from(__('Delete this language?')) }},
                        text: {{ \Illuminate\Support\Js::from(__('This cannot be undone.')) }},
                        confirmText: {{ \Illuminate\Support\Js::from(__('Delete')) }},
                        cancelText: {{ \Illuminate\Support\Js::from(__('Cancel')) }},
                    }, deleteLanguage, function () {});
                });

                (function () {
                    var storageKey = 'wakanda-account-settings-active-tab';
                    var $tabButtons = $('#settingsTabs button[data-bs-toggle="tab"]');

                    if ($tabButtons.length) {
                        var activeTab = sessionStorage.getItem(storageKey);

                        if (activeTab) {
                            var trigger = document.querySelector('#settingsTabs button[data-bs-target="' + activeTab + '"]');

                            if (trigger) {
                                bootstrap.Tab.getOrCreateInstance(trigger).show();
                            }
                        }

                        $tabButtons.on('shown.bs.tab', function (event) {
                            sessionStorage.setItem(storageKey, event.target.getAttribute('data-bs-target'));
                        });
                    }

                    var prefillProfileModal = document.getElementById('prefillProfileModal');
                    var prefillProfileConfirmBtn = document.getElementById('prefillProfileConfirmBtn');
                    var prefillProfileTrigger = null;

                    prefillProfileModal?.addEventListener('show.bs.modal', function (event) {
                        prefillProfileTrigger = event.relatedTarget || document.querySelector('.js-prefill-profile-from-cv');
                    });

                    prefillProfileConfirmBtn?.addEventListener('click', function () {
                        var button = prefillProfileTrigger;
                        var url = button ? button.getAttribute('data-url') : '';
                        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
                        var tokenInput = document.querySelector('input[name="_token"]');
                        var token = (tokenMeta ? tokenMeta.getAttribute('content') : '')
                            || (tokenInput ? tokenInput.value : '');

                        if (! url || ! token || ! button) {
                            return;
                        }

                        prefillProfileConfirmBtn.disabled = true;
                        button.disabled = true;
                        bootstrap.Modal.getOrCreateInstance(prefillProfileModal).hide();

                        showAccountActionStatus({
                            state: 'loading',
                            title: {{ \Illuminate\Support\Js::from(__('Analyzing CV…')) }},
                            text: {{ \Illuminate\Support\Js::from(__('Please wait while we prefill your profile.')) }},
                        });

                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({})
                        })
                            .then(function (response) {
                                return response.json().then(function (data) {
                                    if (! response.ok || data.error) {
                                        throw new Error(data.message || {{ \Illuminate\Support\Js::from(__('We could not prefill your profile from the current CV.')) }});
                                    }

                                    return data;
                                });
                            })
                            .then(function (data) {
                                showAccountActionStatus({
                                    state: 'success',
                                    title: {{ \Illuminate\Support\Js::from(__('Profile prefilled')) }},
                                    text: data.message || {{ \Illuminate\Support\Js::from(__('Your profile was prefilled from the uploaded CV.')) }},
                                    onClose: function () {
                                        window.location.href = (data.data && data.data.next_url) || window.location.href;
                                    }
                                });
                            })
                            .catch(function (error) {
                                showAccountActionStatus({
                                    state: 'error',
                                    title: {{ \Illuminate\Support\Js::from(__('Prefill failed')) }},
                                    text: error.message || {{ \Illuminate\Support\Js::from(__('We could not prefill your profile from the current CV.')) }},
                                });
                            })
                            .finally(function () {
                                prefillProfileConfirmBtn.disabled = false;
                                button.disabled = false;
                            });
                    });
                })();
            });
        });
    </script>

    {{-- Avatar delete confirmation modal --}}
    <div class="modal fade" id="avatarDeleteModal" tabindex="-1" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:52px;height:52px;">
                            <i class="ti ti-trash text-danger fs-3"></i>
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1">{{ __('Remove Avatar') }}</h6>
                    <p class="text-muted small mb-0">{{ __('Are you sure you want to remove this avatar?') }}</p>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-danger btn-sm" id="avatarDeleteConfirmBtn">{{ __('Remove') }}</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Override main.js avatar delete confirm → Bootstrap modal
        $(document).off('click', '.btn-remove-avatar');
        $(document).on('click', '.btn-remove-avatar', function (e) {
            e.preventDefault();
            new bootstrap.Modal(document.getElementById('avatarDeleteModal')).show();
        });
        document.getElementById('avatarDeleteConfirmBtn').addEventListener('click', function () {
            bootstrap.Modal.getInstance(document.getElementById('avatarDeleteModal'))?.hide();
            document.getElementById('delete-avatar-form').submit();
        });
    </script>

@endsection
