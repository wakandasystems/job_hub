<ul {!! BaseHelper::clean($options) !!}>
    @foreach ($menu_nodes->loadMissing('metadata') as $key => $row)
        @php
            $menuPath = trim(parse_url($row->url, PHP_URL_PATH) ?: '', '/');
            $isEmployer = auth('account')->check() && auth('account')->user()->isEmployer();
        @endphp

        @continue(in_array($menuPath, ['about-us', 'pricing-plan'], true))
        @continue($menuPath === 'candidates' && ! $isEmployer)

        <li class="@if ($row->has_child) has-children @endif {{ $row->css_class }}">
            <a>
                @if ($iconImage = $row->getMetadata('icon_image', true))
                    <img src="{{ RvMedia::getImageUrl($iconImage) }}" alt="icon image" class="menu-icon-image"/>
                @elseif ($row->icon_font) <i class="{{ trim($row->icon_font) }}"></i> @endif {{ $row->title }}
                @if ($row->has_child) <div class="arrow-down"></div> @endif
            </a>
            @if ($row->has_child)
                {!!
                    Menu::generateMenu([
                        'menu'       => $menu,
                        'menu_nodes' => $row->child,
                        'view'       => 'main-menu',
                        'options'    => ['class' => 'sub-menu'],
                    ])
                !!}
            @endif
        </li>
    @endforeach
</ul>
