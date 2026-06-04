<ul class="menu">
    @foreach (DashboardMenu::getAll('account') as $item)
        @continue(! $item['name'])
        @php $badge = isset($item['badge']) ? value($item['badge']) : null; @endphp
        <li>
            <a
                href="{{ $item['url']  }}"
                @class(['active' => $item['active']])
                style="display:flex;align-items:center;gap:8px;"
            >
                <x-core::icon :name="$item['icon']" />
                <span>{{ trans($item['name']) }}</span>
                @if ($badge)
                    <span style="margin-left:auto;margin-right:12px;background:var(--bb-primary);color:#fff;border-radius:999px;padding:1px 8px;font-size:11px;font-weight:600;line-height:18px;min-width:20px;text-align:center;">{{ $badge }}</span>
                @endif
            </a>
        </li>
    @endforeach
</ul>
