@foreach($data as $item)
    @if ($item->ads_type === 'google_adsense' && $item->google_adsense_slot_id)
        <div {!! Html::attributes($attributes) !!}>
            @include('plugins/ads::partials.google-adsense.unit-ads-slot', ['slotId' => $item->google_adsense_slot_id])
        </div>
        @continue
    @endif

    @continue(! $item->image)

    <div {!! Html::attributes($attributes) !!}>
        @if ($item->url)
            <a href="{{ $item->click_url }}" @if ($item->open_in_new_tab) target="_blank" @endif title="{{ trans('plugins/ads::ads.banner') }}" style="position:relative;display:inline-block;max-width:100%;">
        @else
            <span style="position:relative;display:inline-block;max-width:100%;">
        @endif
                <picture>
                    <source
                        srcset="{{ $item->image_url }}"
                        media="(min-width: 1200px)"
                    />
                    <source
                        srcset="{{ $item->tablet_image_url }}"
                        media="(min-width: 768px)"
                    />
                    <source
                        srcset="{{ $item->mobile_image_url }}"
                        media="(max-width: 767px)"
                    />

                    {{ RvMedia::image($item->image_url, $item->name, attributes: ['style' => 'max-width: 100%; display: block;']) }}
                </picture>
                {{-- AdSense-style "Ad" badge --}}
                <span style="position:absolute;bottom:8px;left:8px;display:inline-flex;align-items:center;gap:4px;background:rgba(0,0,0,.52);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px);color:#fff;font-size:10px;font-weight:700;letter-spacing:.06em;padding:3px 8px;border-radius:4px;line-height:1.5;pointer-events:none;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;user-select:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;opacity:.85;"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                    Ad
                </span>
        @if ($item->url)
            </a>
        @else
            </span>
        @endif
    </div>
@endforeach
