@php
    $imageFields = ['facebook_image', 'linkedin_image', 'twitter_image', 'whatsapp_image', 'tiktok_image'];
@endphp

@if ($jobs->isNotEmpty())
    <style>
        .job-social-spotlight-grid .spotlight-card {
            display: block;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            background: #f1f1f1;
            text-decoration: none;
        }

        .job-social-spotlight-grid .spotlight-card img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform .3s ease;
        }

        .job-social-spotlight-grid .spotlight-card:hover img {
            transform: scale(1.05);
        }

        .job-social-spotlight-grid .spotlight-caption {
            position: absolute;
            inset: auto 0 0 0;
            padding: 14px 16px;
            background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,.75) 100%);
        }

        .job-social-spotlight-grid .spotlight-caption h6 {
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .job-social-spotlight-grid .spotlight-caption span {
            color: rgba(255,255,255,.85);
            font-size: 12px;
        }
    </style>

    <section class="section-box mt-50 job-social-spotlight">
        <div class="container">
            <div class="text-start">
                <h2 class="section-title mb-10 wow animate__animated animate__fadeInUp">
                    {!! BaseHelper::clean($shortcode->title ?: __('Fresh Job Spotlights')) !!}
                </h2>
                @if ($shortcode->subtitle)
                    <p class="font-lg color-text-paragraph-2 wow animate__animated animate__fadeInUp">
                        {!! BaseHelper::clean($shortcode->subtitle) !!}
                    </p>
                @endif
            </div>

            <div class="row mt-30 job-social-spotlight-grid">
                @foreach ($jobs as $job)
                    @php
                        $imagePath = null;
                        foreach ($imageFields as $field) {
                            if (! empty($job->{$field})) {
                                $imagePath = $job->{$field};
                                break;
                            }
                        }
                    @endphp

                    @if ($imagePath)
                        <div class="col-lg-3 col-md-4 col-6 mb-24">
                            <a class="spotlight-card hover-up" href="{{ $job->url }}">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($imagePath) }}" alt="{{ $job->name }}" loading="lazy">
                                <div class="spotlight-caption">
                                    <h6>{{ $job->name }}</h6>
                                    @if (! $job->hide_company && $job->company?->name)
                                        <span>{{ $job->company->name }}</span>
                                    @endif
                                </div>
                            </a>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endif
