@php
    $defaultCompanyLogo = theme_option('default_company_logo', true);
@endphp

<div class="quick-search-result">
    @foreach($jobs as $job)
        @php
            $companyLogo = $job->company->logo;
        @endphp

        <a href="{{ $job->url }}" class="quick-search-result__item">
            @if($defaultCompanyLogo || $companyLogo)
                <div class="quick-search-result__item__image">
                    {{ RvMedia::image($companyLogo ?: $defaultCompanyLogo, $job->company->name, 'thumb') }}
                </div>
            @endif

            <div class="quick-search-result__item__content text-truncate">
                <h3 class="quick-search-result__item__content__title text-truncate">{{ $job->name }}</h3>
                <div class="quick-search-result__item__content__location">
                    <i class="fa fa-map-marker"></i>
                    {{ $job->location }}
                </div>
            </div>
        </a>
    @endforeach
</div>
