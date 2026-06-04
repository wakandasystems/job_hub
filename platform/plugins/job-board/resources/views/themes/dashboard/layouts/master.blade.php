<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="format-detection" content="telephone=no">
        <meta name="apple-mobile-web-app-capable" content="yes">

        @if (theme_option('favicon'))
            <link href="{{ RvMedia::getImageUrl(theme_option('favicon')) }}" rel="shortcut icon">
        @endif

        <title>{{ PageTitle::getTitle(false) }}</title>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        @yield('header', view(JobBoardHelper::viewPath('dashboard.layouts.header')))

        <script type="text/javascript">
            'use strict';
            window.trans = Object.assign(window.trans || {}, JSON.parse('{!! addslashes(json_encode(trans('plugins/job-board::job-board.themes'))) !!}'));

            var BotbleVariables = BotbleVariables || {};
            BotbleVariables.languages = {
                tables: {!! json_encode(trans('core/base::tables'), JSON_HEX_APOS) !!},
                notices_msg: {!! json_encode(trans('core/base::notices'), JSON_HEX_APOS) !!},
                pagination: {!! json_encode(trans('pagination'), JSON_HEX_APOS) !!},
                system: {
                    character_remain: '{{ trans('plugins/job-board::job-board.character_remain') }}'
                }
            };

            window.siteEditorLocale = "{{ apply_filters('cms_site_editor_locale', App::getLocale()) }}";
        </script>
    </head>

    <body @if (session('locale_direction', 'ltr') == 'rtl') dir="rtl" @endif>
        @yield('body', view(JobBoardHelper::viewPath('dashboard.layouts.body')))

        @include('plugins/job-board::themes.dashboard.layouts.footer')

        {!! Assets::renderFooter() !!}
        @stack('scripts')
        @stack('footer')
        {!! apply_filters(THEME_FRONT_FOOTER, null) !!}

        @if (Session::has('company_setup_required'))
            <div class="modal modal-blur fade" id="companySetupModal" tabindex="-1" role="dialog" aria-modal="true">
                <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="modal-title text-center mb-2" style="font-size:2.5rem;">🏢</div>
                            <h3 class="text-center mb-1">Set up your company first</h3>
                            <div class="text-center text-muted mb-0">You need a company profile before you can post jobs. It only takes a minute.</div>
                        </div>
                        <div class="modal-footer">
                            <div class="w-100">
                                <div class="row">
                                    <div class="col">
                                        <button type="button" class="btn w-100" data-bs-dismiss="modal">Maybe later</button>
                                    </div>
                                    <div class="col">
                                        <a href="{{ route('public.account.companies.create') }}" class="btn btn-primary w-100">
                                            Create Company
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var modal = new bootstrap.Modal(document.getElementById('companySetupModal'));
                    modal.show();
                });
            </script>
        @endif

        @if (Session::has('success_msg') || Session::has('error_msg') || (isset($errors) && $errors->any()) || isset($error_msg))
            <script type="text/javascript">
                $(function() {
                    @if (Session::has('success_msg'))
                        Botble.showSuccess('{{ session('success_msg') }}');
                    @endif
                    @if (Session::has('error_msg'))
                        Botble.showError('{{ session('error_msg') }}');
                    @endif
                    @if (isset($error_msg))
                        Botble.showError('{{ $error_msg }}');
                    @endif
                    @if (isset($errors))
                        @foreach ($errors->all() as $error)
                            Botble.showError('{{ $error }}');
                        @endforeach
                    @endif
                });
            </script>
        @endif
    </body>
</html>
