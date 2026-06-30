@switch($shortcode->style)
    @case('style-5')
        <section class="section-box mt-50 mb-30 bg-brand-2 pt-60 pb-60">
            <div class="container">
                <div class="row">
                    <div class="col-xl-5">
                        <div class="pt-70">
                            <h2 class="color-white mb-20">{!! BaseHelper::clean($shortcode->title) !!}</h2>
                            <p class="color-white mb-30">{!! BaseHelper::clean($shortcode->subtitle) !!}</p>
                            @if($categoriesPageURL = JobBoardHelper::getJobCategoriesPageURL())
                                <div class="mt-20">
                                    <a class="btn btn-brand-1 btn-icon-more hover-up" href="{{ $categoriesPageURL }}">{{ __('Explore') }}</a>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-xl-7 mt-40 mt-xl-0">
                        @if(isset($companies) && $companies->isNotEmpty())
                            <div class="row g-3 pt-xl-4">
                                @foreach($companies->take(18) as $company)
                                    <div class="col-4 col-sm-3 col-md-2">
                                        <a href="{{ $company->url }}" class="d-block text-center p-2 rounded hover-up" style="background: rgba(255,255,255,0.15); transition: background .2s;">
                                            <img src="{{ $company->logo_thumb }}" alt="{{ $company->name }}" loading="lazy" style="height:44px; width:auto; max-width:100%; object-fit:contain; display:block; margin:0 auto;">
                                            <small class="d-block text-truncate mt-1 color-white" style="font-size:10px; line-height:1.3;">{{ $company->name }}</small>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="box-swiper mt-50 layout-brand-1">
                                <div class="swiper-container swiper-group-3-explore mh-none swiper">
                                    <div class="swiper-wrapper pb-70 pt-5">
                                        @foreach($categories->loadMissing('metadata') as $category)
                                            <div class="swiper-slide hover-up">
                                                <div class="card-grid-5 card-category hover-up" style="background-image: url('{{ RvMedia::getImageUrl($category->getMetaData('job_category_image', true)) ?: Theme::asset()->url('imgs/page/homepage2/img-big1.png') }}')">
                                                    <a href="{{ $category->url }}">
                                                        <div class="box-cover-img">
                                                            <div class="content-bottom">
                                                                <h6 class="color-white mb-5">{{ $category->name }}</h6>
                                                                <p class="color-white font-xs">
                                                                    {!! __('<span>:count</span> <span>Jobs Available</span>', ['count' => $category->active_jobs_count ?? $category->jobs_count]) !!}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="swiper-button-next swiper-button-next-1"></div>
                                <div class="swiper-button-prev swiper-button-prev-1"> </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @break
    @default
        @php
            $popularCatJobs = \Botble\JobBoard\Models\Job::query()
                ->where('status', \Botble\JobBoard\Enums\JobStatusEnum::PUBLISHED)
                ->where('moderation_status', \Botble\JobBoard\Enums\ModerationStatusEnum::APPROVED)
                ->where(function ($q) { $q->where('never_expired', true)->orWhereNull('expire_date')->orWhere('expire_date', '>=', now()); })
                ->with(['slugable', 'company', 'company.slugable'])
                ->latest()
                ->take(10)
                ->get();
        @endphp
        <section class="section-box mt-50">
            <div class="section-box wow animate__animated animate__fadeIn">
                <div class="container">
                    <div class="text-start">
                        <h2 class="section-title mb-10 wow animate__animated animate__fadeInUp">{!! BaseHelper::clean($shortcode->title) !!}</h2>
                        <p class="font-lg color-text-paragraph-2 wow animate__animated animate__fadeInUp">{!! BaseHelper::clean($shortcode->subtitle) !!}</p>
                    </div>
                    <div class="box-swiper mt-50">
                        <div class="swiper-container swiper-group-6 mh-none swiper">
                            <div class="swiper-wrapper pb-70 pt-5">
                                @foreach($popularCatJobs as $job)
                                    <div class="swiper-slide hover-up">
                                        @php
                                            $bgImg = $job->cover_image
                                                ? RvMedia::getImageUrl($job->cover_image)
                                                : ($job->employer_image ? RvMedia::getImageUrl($job->employer_image)
                                                    : ($job->facebook_image ? RvMedia::getImageUrl($job->facebook_image)
                                                        : Theme::asset()->url('imgs/page/homepage2/img-big1.png')));
                                        @endphp
                                        <div class="card-grid-5 card-category hover-up" style="background-image: url('{{ $bgImg }}')">
                                            <a href="{{ $job->url }}">
                                                <div class="box-cover-img">
                                                    <div class="content-bottom">
                                                        <h6 class="color-white mb-5">{{ $job->name }}</h6>
                                                        <p class="color-white font-xs">{{ $job->company_name }}</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="swiper-button-next swiper-button-next-1"></div>
                        <div class="swiper-button-prev swiper-button-prev-1"></div>
                    </div>
                </div>
            </div>
        </section>
    @break
@endswitch
