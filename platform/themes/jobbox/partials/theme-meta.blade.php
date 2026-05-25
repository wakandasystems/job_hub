{!! BaseHelper::googleFonts('https://fonts.googleapis.com/css2?family=' . urlencode(theme_option('primary_font') ?: 'Plus Jakarta Sans') . ':wght@400;500;600;700;800&display=swap') !!}

<style>
    :root {
        --primary-color: {{ theme_option('primary_color', '#581090') }};
        --primary-color-hover: {{ theme_option('primary_color_hover', '#B89BD3') }};
        --secondary-color: {{ theme_option('secondary_color', '#08080A') }};
        --border-color-2: {{ theme_option('border_color_2', '#E0E6F7') }};
        --primary-font: '{{ theme_option('primary_font') ?: 'Plus Jakarta Sans' }}', sans-serif;
        --primary-color-rgb: {{ implode(', ', BaseHelper::hexToRgb(theme_option('primary_color', '#581090'))) }};
    }
    /* Pre-hide WOW.js targets so JS init doesn't flash visible→hidden→animate */
    .wow { visibility: hidden; }
</style>
