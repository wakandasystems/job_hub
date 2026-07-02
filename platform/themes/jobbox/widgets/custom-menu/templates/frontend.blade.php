<div class="footer-col-2 col-md-2 col-xs-6">
    <div class="h6 mb-20">{!! BaseHelper::clean($config['name']) !!}</div>
    @php
        $menuHtml = Menu::generateMenu([
            'slug'    => $config['menu_id'],
            'view'    => 'footer-menu',
            'options' => ['class' => 'menu-footer']
        ]);

        $isMoreFooterColumn = trim(strip_tags((string) ($config['name'] ?? ''))) === 'More';
        $shouldAppendPrivacyPolicy = $isMoreFooterColumn
            && str_contains($menuHtml, '/cookie-policy')
            && str_contains($menuHtml, '/terms')
            && str_contains($menuHtml, '/faqs')
            && ! str_contains($menuHtml, '/privacy-policy');

        if ($shouldAppendPrivacyPolicy) {
            $menuHtml = str_replace(
                '</ul>',
                '<li><a href="' . e(url('/privacy-policy')) . '">' . e(__('Privacy Policy')) . '</a></li></ul>',
                $menuHtml
            );
        }
    @endphp
    {!! $menuHtml !!}
</div>
