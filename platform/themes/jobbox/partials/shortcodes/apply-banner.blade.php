<div class="section-box mb-30">
    <style>
        .box-we-hiring {
            overflow: hidden;
        }

        .box-we-hiring .box-we-hiring-after {
            background-position: center center !important;
            background-repeat: no-repeat !important;
            background-size: contain !important;
            bottom: 15px;
            height: 120px;
            right: 15px;
            width: 220px;
        }
    </style>
    <div class="container">
        <div class="box-we-hiring">
            @if ($shortcode->apply_image_left)
                <div class="box-we-hiring-before" style="background: url({{ RvMedia::getImageUrl($shortcode->apply_image_left) }}) no-repeat 0 0"></div>
            @endif
            <div class="text-1">
                <span class="text-we-are">{!! BaseHelper::clean($shortcode->title_1) !!}</span>
                <span class="text-hiring">{!! BaseHelper::clean($shortcode->title_2) !!}</span>
            </div>
            <div class="text-2">
                {!! BaseHelper::clean($subtitleText) !!}
            </div>
            @if ($shortcode->button_apply_link)
                <div class="text-3">
                    <a href="{{ $shortcode->button_apply_link }}">
                        <div class="btn btn-apply btn-apply-icon" >{!! BaseHelper::clean($shortcode->button_apply_text) !!}</div>
                    </a>
                </div>
            @endif

            @if ($shortcode->apply_image_right)
                <div class="box-we-hiring-after" style="background-image: url({{ RvMedia::getImageUrl($shortcode->apply_image_right) }})"></div>
            @endif
        </div>
    </div>
</div>
