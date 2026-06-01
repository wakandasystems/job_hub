@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-9">
            @if(session('success_msg'))
                <div class="alert alert-success">{{ session('success_msg') }}</div>
            @endif

            @if(session('error_msg'))
                <div class="alert alert-danger">{{ session('error_msg') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <strong>Please fix the form and try again.</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>Send Newsletter</x-core::card.title>
                </x-core::card.header>

                <x-core::card.body>
                    <div class="alert alert-info mb-4">
                        This will send to {{ number_format($subscriberCount) }} subscribed newsletter recipient(s). Add a test email to send only one preview first.
                    </div>

                    <form method="POST" action="{{ route('newsletter.send.post') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="subject">Subject</label>
                            <input class="form-control" id="subject" name="subject" value="{{ old('subject') }}" required maxlength="180">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="message">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="12" required>{{ old('message') }}</textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="image_url">Banner image URL</label>
                                <input class="form-control" id="image_url" name="image_url" value="{{ old('image_url') }}" placeholder="https://...">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="pdf">Attach PDF</label>
                                <input class="form-control" id="pdf" name="pdf" type="file" accept="application/pdf">
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label" for="test_to">Test email only</label>
                            <input class="form-control" id="test_to" name="test_to" value="{{ old('test_to') }}" placeholder="name@example.com">
                            <div class="form-text">Leave this blank to send to all subscribed newsletter recipients.</div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <a class="btn btn-outline-secondary" href="{{ route('newsletter.index') }}">Back to subscribers</a>
                            <button class="btn btn-primary" type="submit">Send Newsletter</button>
                        </div>
                    </form>
                </x-core::card.body>
            </x-core::card>
        </div>
    </div>
@endsection
