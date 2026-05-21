@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<form method="POST" action="{{ route('job-board.settings.career-services.update') }}">
    @csrf @method('PUT')

    <div class="row g-3">
        <div class="col-lg-8">
            {{-- Career Service Prices --}}
            <x-core::card class="mb-3">
                <x-core::card.header>
                    <x-core::card.title>Career Service Prices</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th style="width:140px">Price (USD)</th>
                                <th style="width:140px">Delivery Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($services as $key => $svc)
                                <tr>
                                    <td>{{ $svc['label'] }}</td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                            name="services[{{ $key }}][price]"
                                            value="{{ old("services.{$key}.price", setting("career_service_price_{$key}", $svc['default_price'])) }}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm"
                                            name="services[{{ $key }}][delivery]"
                                            value="{{ old("services.{$key}.delivery", setting("career_service_delivery_{$key}", $svc['default_delivery'])) }}"
                                            placeholder="e.g. 24 hrs">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-core::card.body>
            </x-core::card>

            {{-- Job Alert Settings --}}
            <x-core::card class="mb-3">
                <x-core::card.header>
                    <x-core::card.title>Job Alert Settings</x-core::card.title>
                </x-core::card.header>
                <x-core::card.body>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Free Alerts per Month</label>
                            <input type="number" class="form-control" name="job_alert_free_monthly_limit"
                                min="1" max="100" value="{{ old('job_alert_free_monthly_limit', $freeAlertLimit) }}">
                            <div class="form-text">How many job alerts a free candidate gets per month before needing a paid package.</div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Telegram Bot Token</label>
                            <input type="text" class="form-control" name="telegram_bot_token"
                                value="{{ old('telegram_bot_token', $telegramToken) }}"
                                placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
                            <div class="form-text">
                                Get a token from <strong>@BotFather</strong> on Telegram.
                                Candidates must message your bot first and provide their Chat ID (get it via <strong>@userinfobot</strong>).
                            </div>
                        </div>
                    </div>
                </x-core::card.body>
                <x-core::card.footer>
                    <button class="btn btn-primary" type="submit">Save Settings</button>
                    <a class="btn btn-outline-secondary ms-2" href="{{ route('career-alert-packages.index') }}">
                        Manage Alert Packages →
                    </a>
                </x-core::card.footer>
            </x-core::card>
        </div>
    </div>
</form>
@endsection
