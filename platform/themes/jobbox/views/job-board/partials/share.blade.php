<div class="{{ $containerClass ?? 'col-md-7 text-lg-end social-share' }}">
    <h6 class="{{ $headingClass ?? 'color-text-paragraph-2 d-inline-block d-baseline mr-10' }}">{{ __('Share this') }}</h6>

    @php
        $shareTitle = trim($job->name . ($job->company->name ? ' - ' . $job->company->name : ''));
        $shareDescription = \Illuminate\Support\Str::limit(trim(strip_tags((string) ($job->description ?: SeoHelper::getDescription()))), 180);
        $shareText = $shareDescription ? $shareTitle . ' | ' . $shareDescription : $shareTitle;
    @endphp

    {!! Theme::renderSocialSharing($job->url, $shareText, $job->image) !!}
</div>
