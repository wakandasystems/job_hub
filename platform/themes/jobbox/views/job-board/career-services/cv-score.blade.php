<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10 col-12">
                <div class="mb-30 text-center">
                    <h3 class="fw-bold mb-2">{{ __('Free AI CV Score') }}</h3>
                    <p class="color-text-paragraph-2 mb-0">{{ __('Get a quick 0-100 CV quality score before deciding whether to book a professional review.') }}</p>
                </div>

                @if(session('error_msg'))
                    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                        <i class="fi-rr-exclamation me-2"></i>{{ session('error_msg') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('success_msg'))
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="fi-rr-check me-2"></i>{{ session('success_msg') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($score)
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                                <div>
                                    <div class="text-muted font-sm">{{ __('Your score') }}</div>
                                    <div class="display-5 fw-bold color-brand-1">{{ $score['score'] }}/100</div>
                                </div>
                                <a class="btn btn-apply btn-apply-big" href="{{ route('public.career-service.checkout', ['service' => 'cv_review']) }}">
                                    {{ __('Get Human Review') }}
                                </a>
                            </div>

                            <ul class="mb-0">
                                @foreach($score['feedback'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('public.career-service.cv-score.submit') }}" enctype="multipart/form-data">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label fw-semibold">{{ __('Paste CV text') }}</label>
                                <textarea class="form-control" name="cv_text" rows="12" placeholder="{{ __('Paste your profile, experience, education, skills and achievements here...') }}">{{ old('cv_text') }}</textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">{{ __('Or upload a text CV') }}</label>
                                <input class="form-control" type="file" name="cv_file" accept=".txt,.pdf,.doc,.docx">
                                <div class="form-text">{{ __('Text files score best in this first version. PDF/DOC uploads are accepted but may only use filename metadata until document parsing is enabled.') }}</div>
                            </div>

                            <button class="btn btn-apply btn-apply-big" type="submit">{{ __('Score My CV') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
