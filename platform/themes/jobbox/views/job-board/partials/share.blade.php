<div class="col-md-7 text-lg-end social-share">
    <h6 class="color-text-paragraph-2 d-inline-block d-baseline mr-10">{{ __('Share this') }}</h6>

    {!! Theme::renderSocialSharing($job->url, SeoHelper::getDescription(), $job->image) !!}
</div>
