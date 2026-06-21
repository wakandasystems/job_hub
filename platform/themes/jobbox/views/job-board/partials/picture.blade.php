@php
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    $isLazy = $lazy ?? true;
@endphp
<picture @if($class ?? null) class="{{ $class }}" @endif>
    @if (! empty($variants['avif']))
        <source srcset="{{ $disk->url($variants['avif']) }}" type="image/avif">
    @endif
    @if (! empty($variants['webp']))
        <source srcset="{{ $disk->url($variants['webp']) }}" type="image/webp">
    @endif
    <img
        src="{{ $disk->url($src) }}"
        alt="{{ $alt }}"
        @if($isLazy) loading="lazy" @endif
        decoding="async"
        @if(! empty($variants['lqip']))
            style="background-image:url('{{ $variants['lqip'] }}');background-size:cover;background-position:center"
            onload="this.style.backgroundImage='none'"
        @endif
    >
</picture>
