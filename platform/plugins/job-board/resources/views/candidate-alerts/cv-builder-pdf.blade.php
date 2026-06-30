<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    @php($design = $design ?? 'premium')
    @php($photoDataUri = $photoDataUri ?? null)
    @php($initials = trim(implode('', array_map(fn ($part) => mb_substr($part, 0, 1), array_slice(array_filter(explode(' ', (string) ($cv['full_name'] ?? ''))), 0, 2)))))
    {{-- The ATS design drops every decorative element (photo/monogram, diamond glyphs, colour) so the
         exported text is as plain and parser-friendly as possible. --}}
    @php($tick = $design === 'ats' ? '' : '<span class="tick">&#9670;</span> ')
    <style>
        @page { margin: 0 40px 46px; }
        body {
            font-family: "DejaVu Sans", sans-serif;
            color: #3a3a3a;
            font-size: 11.5px;
            line-height: 1.55;
        }
        .hero {
            margin: 0 -40px 22px;
            padding: 38px 40px 26px;
            background: #1c1c1c;
            color: #f5f3ee;
        }
        .hero-top { width: 100%; }
        .monogram-wrap {
            float: right;
            width: 88px;
            height: 88px;
        }
        .monogram {
            display: table;
            width: 88px;
            height: 88px;
            border: 1px solid #b08d57;
            border-radius: 50%;
            overflow: hidden;
        }
        .monogram span {
            display: table-cell;
            width: 88px;
            height: 88px;
            text-align: center;
            vertical-align: middle;
            color: #d8b88a;
            font-family: "DejaVu Serif", serif;
            font-size: 26px;
            letter-spacing: .02em;
        }
        .monogram img {
            width: 88px;
            height: 88px;
            border-radius: 50%;
        }
        h1 {
            font-family: "DejaVu Serif", serif;
            font-weight: bold;
            font-size: 25px;
            margin: 4px 0 6px;
            color: #f5f3ee;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .rule-gold { width: 54px; height: 2px; background: #b08d57; margin: 0 0 10px; }
        .headline { font-family: "DejaVu Serif", serif; font-style: italic; font-size: 12.5px; color: #d8b88a; margin-bottom: 10px; }
        .contact { color: #cfcac0; font-size: 10px; letter-spacing: .02em; }
        .contact span { margin-right: 4px; }
        .contact span + span { margin-left: 4px; }

        h2 {
            font-family: "DejaVu Serif", serif;
            font-size: 12px;
            font-weight: bold;
            margin: 19px 0 9px;
            padding: 0 0 5px;
            color: #1c1c1c;
            border-bottom: 1px solid #ded7c9;
            text-transform: uppercase;
            letter-spacing: .14em;
        }
        h2 .tick { color: #b08d57; margin-right: 6px; }

        .item { margin-bottom: 12px; padding-left: 11px; border-left: 1.5px solid #e7decb; page-break-inside: avoid; }
        .item-title { font-weight: bold; color: #1c1c1c; font-size: 12px; }
        .item-meta { color: #9a9a9a; font-size: 10px; margin-top: 1px; letter-spacing: .01em; }
        ul { margin: 6px 0 0 16px; padding: 0; }
        li { margin-bottom: 4px; }
        p { margin: 0 0 8px; }

        .skills span {
            display: inline-block;
            border: 0.75px solid #c9b896;
            border-radius: 10px;
            padding: 4px 11px;
            margin: 0 6px 6px 0;
            color: #5b4a32;
            font-size: 10px;
            letter-spacing: .03em;
        }

        .footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            color: #b3aca0;
            font-size: 8.5px;
            text-align: center;
            letter-spacing: .05em;
        }

        @if($design === 'academic')
            .hero { background: #fff; color: #1e2a3a; text-align: center; border-bottom: 2.5px solid #1e2a3a; padding: 40px 40px 22px; }
            .monogram-wrap { float: none; display: inline-block; margin-bottom: 10px; }
            .monogram { border-color: #1e2a3a; }
            .monogram span { color: #1e2a3a; }
            h1 { color: #1e2a3a; font-size: 27px; letter-spacing: .2em; }
            .rule-gold { background: #1e2a3a; margin: 0 auto 12px; }
            .headline { color: #4b5a6e; font-style: normal; letter-spacing: .08em; text-transform: uppercase; font-size: 10.5px; }
            .contact { color: #5b6677; }
            h2 { text-align: left; border-bottom: 1px solid #1e2a3a; letter-spacing: .18em; }
            h2 .tick { color: #1e2a3a; }
            .item { border-left-color: #d7dce3; }
            .skills span { border-radius: 2px; border-color: #c7ccd4; color: #1e2a3a; }
        @elseif($design === 'creative')
            .side-stripe { position: fixed; left: -40px; top: 0; bottom: -46px; width: 34px; background: #1f3d34; }
            .side-accent { position: fixed; left: -6px; top: 0; bottom: -46px; width: 6px; background: #d4a373; }
            .hero { background: #1f3d34; color: #f3efe6; }
            .monogram { border-color: #d4a373; }
            .monogram span { color: #e7c9a3; }
            h1 { font-family: "DejaVu Sans", sans-serif; font-weight: bold; color: #fdfaf4; letter-spacing: .01em; }
            .rule-gold { display: none; }
            .headline { font-family: "DejaVu Sans", sans-serif; font-style: normal; font-weight: bold; color: #d4a373; text-transform: uppercase; letter-spacing: .1em; font-size: 11px; }
            .contact { color: #d7e4dc; }
            h2 {
                font-family: "DejaVu Sans", sans-serif;
                color: #1f3d34;
                border-bottom: 2px solid #d4a373;
                letter-spacing: .03em;
            }
            h2 .tick { color: #d4a373; }
            .item { border-left: 0; padding-left: 14px; position: relative; }
            .item:before { content: ''; position: absolute; left: -1px; top: 4px; width: 7px; height: 7px; border-radius: 50%; background: #b5651d; }
            .skills span { border: 0; background: #1f3d34; color: #fdfaf4; }
            .skills span:nth-child(even) { background: #b5651d; }
        @elseif($design === 'ats')
            /* Plain black-on-white, no colour, no photo/monogram, no decorative glyphs — built to
               parse cleanly in any ATS text extractor. */
            .hero { background: #fff; color: #000; padding: 0 0 14px; border-bottom: 1.5px solid #000; }
            .rule-gold { display: none; }
            h1 { color: #000; font-family: "DejaVu Sans", sans-serif; font-weight: bold; letter-spacing: 0; text-transform: none; font-size: 20px; }
            .headline { color: #000; font-style: normal; font-family: "DejaVu Sans", sans-serif; font-size: 11.5px; }
            .contact { color: #000; }
            h2 { font-family: "DejaVu Sans", sans-serif; border-bottom: 1px solid #000; text-transform: none; letter-spacing: 0; color: #000; }
            .item { border-left: 0; padding-left: 0; }
        @elseif($design === 'executive')
            /* Navy-and-gold executive style — two-panel header, gold section underlines. */
            @@page { margin: 0 0 46px; }
            body { font-family: "DejaVu Sans", sans-serif; color: #2C2C2C; font-size: 10.5px; }
            .exec-header { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
            .exec-header-left { background: #1B3A6B; padding: 18px 16px 18px 22px; width: 71%; vertical-align: middle; }
            .exec-header-right { background: #142D55; padding: 14px 12px; width: 29%; vertical-align: middle; }
            .exec-name { color: #FFFFFF; font-size: 22px; font-weight: bold; text-transform: uppercase; letter-spacing: .06em; margin: 0 0 6px; }
            .exec-headline { color: #C9A84C; font-size: 10.5px; margin: 0 0 5px; }
            .exec-credentials { color: #CCCCCC; font-size: 9px; margin: 0; }
            .exec-contact-item { color: #DDDDDD; font-size: 9px; margin-bottom: 4px; }
            .hero { display: none; }
            .rule-gold { display: none; }
            .footer { left: 0; right: 0; }
            h2 {
                font-family: "DejaVu Sans", sans-serif;
                font-size: 10px;
                font-weight: bold;
                color: #1B3A6B;
                margin: 18px 22px 0;
                padding: 0 0 4px;
                border-bottom: 1.5px solid #C9A84C;
                text-transform: uppercase;
                letter-spacing: .12em;
            }
            .exec-body { margin: 0 22px; }
            .item { margin: 6px 22px 10px; padding-left: 10px; border-left: 1.5px solid #C9A84C; page-break-inside: avoid; }
            .item-title { font-weight: bold; color: #1B3A6B; font-size: 10.5px; }
            .item-meta { color: #C9A84C; font-size: 9.5px; font-weight: bold; margin-top: 1px; }
            .item-dates { color: #666666; font-style: italic; font-size: 9px; }
            ul { margin: 4px 22px 0 36px; padding: 0; }
            li { margin-bottom: 3px; font-size: 10px; color: #2C2C2C; }
            p { margin: 6px 22px 8px; font-size: 10px; }
            .skills span { display: inline-block; border: 0.75px solid #C9A84C; border-radius: 10px; padding: 3px 9px; margin: 0 5px 5px 0; color: #1B3A6B; font-size: 9px; }
            .skills { margin: 4px 22px 0; }
        @endif
    </style>
</head>
<body>
    @if($design === 'creative')
        <div class="side-stripe"></div>
        <div class="side-accent"></div>
    @endif

    @php($execTopEd = $cv['education'][0] ?? [])
    @php($execFirstCert = $cv['certifications'][0] ?? '')
    @php($execFirstCertName = is_array($execFirstCert) ? ($execFirstCert['name'] ?? '') : $execFirstCert)
    @php($execCredentials = implode('  •  ', array_filter([trim(($execTopEd['qualification'] ?? '') . (! empty($execTopEd['institution']) ? ' (' . $execTopEd['institution'] . ')' : '')), $execFirstCertName])))
    @php($_phone = trim($cv['phone'] ?? ''))
    @php($_whatsapp = trim($cv['whatsapp'] ?? ''))
    @php($_phonePart = $_phone !== '' ? 'Tel: ' . $_phone : null)
    @php($_waPart = ($_whatsapp !== '' && $_whatsapp !== $_phone) ? 'WA: ' . $_whatsapp : ($_whatsapp !== '' && $_phone === '' ? 'WA: ' . $_whatsapp : null))
    @php($contactLine = implode('  ·  ', array_filter([$_phonePart, $_waPart, $cv['email'] ?? null, $cv['location'] ?? null])))
    @php($execContacts = array_values(array_filter([
        ! empty($cv['location'])       ? '📍 ' . $cv['location'] : '',
        ! empty($cv['address'])        ? '🏠 ' . $cv['address'] : '',
        $_phone !== ''                 ? '📞 Tel: ' . $_phone : '',
        ($_whatsapp !== '' && $_whatsapp !== $_phone) ? '💬 WA: ' . $_whatsapp : ($_whatsapp !== '' && $_phone === '' ? '💬 WA: ' . $_whatsapp : ''),
        ! empty($cv['email'])          ? '✉ ' . $cv['email'] : '',
        ! empty($cv['linkedin'])       ? '🔗 ' . $cv['linkedin'] : '',
        ! empty($cv['age'])            ? '🪪 Age: ' . $cv['age'] : '',
        ! empty($cv['marital_status']) ? '👤 ' . $cv['marital_status'] : '',
    ])))
    @php($bioLine = implode('  ·  ', array_filter([
        ! empty($cv['address']) ? 'Address: ' . $cv['address'] : '',
        ! empty($cv['linkedin']) ? $cv['linkedin'] : '',
        ! empty($cv['age']) ? 'Age: ' . $cv['age'] : '',
        ! empty($cv['marital_status']) ? 'Marital Status: ' . $cv['marital_status'] : '',
    ])))

    @if($design === 'executive')
        <table class="exec-header">
            <tr>
                <td class="exec-header-left">
                    <div class="exec-name">{{ $cv['full_name'] ?? 'Candidate' }}</div>
                    @if(! empty($cv['headline']))
                        <div class="exec-headline">{{ $cv['headline'] }}</div>
                    @endif
                    @if($execCredentials !== '')
                        <div class="exec-credentials">{{ $execCredentials }}</div>
                    @endif
                </td>
                <td class="exec-header-right">
                    @foreach($execContacts as $contactItem)
                        <div class="exec-contact-item">{{ $contactItem }}</div>
                    @endforeach
                </td>
            </tr>
        </table>
    @endif

    <div class="hero">
        <div class="hero-top">
            {{-- Academic keeps the initials monogram but never the actual photo — a formal CV
                 design convention this template otherwise breaks for the other 3 designs. --}}
            @if($design !== 'ats')
                @if($photoDataUri && $design !== 'academic')
                    <div class="monogram-wrap"><div class="monogram"><img src="{{ $photoDataUri }}" alt=""></div></div>
                @elseif($initials !== '')
                    <div class="monogram-wrap"><div class="monogram"><span>{{ $initials }}</span></div></div>
                @endif
            @endif
            <h1>{{ $design === 'academic' ? 'CURRICULUM VITAE' : ($cv['full_name'] ?? 'Candidate CV') }}</h1>
        </div>
        <div class="rule-gold"></div>

        @if($design === 'academic')
            <div class="headline">{{ $cv['full_name'] ?? 'Candidate' }}</div>
        @elseif(! empty($cv['headline']))
            <div class="headline">{{ $cv['headline'] }}</div>
        @endif

        @if($contactLine !== '')
            <div class="contact">{{ $contactLine }}</div>
        @endif
        @if($bioLine !== '')
            <div class="contact" style="margin-top:6px;">{{ $bioLine }}</div>
        @endif
    </div>

    @if(! empty($cv['summary']))
        <h2>{!! $tick !!}Professional Summary</h2>
        <p>{{ $cv['summary'] }}</p>
    @endif

    @if(! empty($cv['education']))
        <h2>{!! $tick !!}Education</h2>
        @foreach($cv['education'] as $item)
            <div class="item">
                <div class="item-title">{{ implode(' - ', array_filter([$item['qualification'] ?? null, $item['field'] ?? null])) }}</div>
                @if($design === 'executive')
                    <div class="item-meta">{{ $item['institution'] ?? '' }}</div>
                    @php($edYears = trim(implode(' – ', array_filter([$item['start_year'] ?? null, $item['end_year'] ?? null]))))
                    @if($edYears !== '')<div class="item-dates">{{ $edYears }}</div>@endif
                @else
                    <div class="item-meta">{{ implode('  ·  ', array_filter([$item['institution'] ?? null, trim(implode(' - ', array_filter([$item['start_year'] ?? null, $item['end_year'] ?? null])))])) }}</div>
                @endif
            </div>
        @endforeach
    @endif

    @if(! empty($cv['certifications']))
        <h2>{!! $tick !!}Certifications</h2>
        <ul>
            @foreach($cv['certifications'] as $certification)
                <li>{{ is_array($certification) ? trim(implode(' — ', array_filter([$certification['name'] ?? '', $certification['issuing_body'] ?? '', $certification['date'] ?? '']))) : $certification }}</li>
            @endforeach
        </ul>
    @endif

    @if(! empty($cv['experience']))
        <h2>{!! $tick !!}Work Experience</h2>
        @foreach($cv['experience'] as $item)
            <div class="item">
                @if($design === 'executive')
                    <div class="item-title">{{ $item['job_title'] ?? '' }}</div>
                    <div class="item-meta">{{ $item['company'] ?? '' }}</div>
                    @php($expDates = trim(implode(' – ', array_filter([$item['start_date'] ?? null, $item['end_date'] ?? null]))))
                    @if($expDates !== '')<div class="item-dates">{{ $expDates }}</div>@endif
                @else
                    <div class="item-title">{{ implode(' - ', array_filter([$item['job_title'] ?? null, $item['company'] ?? null])) }}</div>
                    <div class="item-meta">{{ implode('  ·  ', array_filter([$item['location'] ?? null, trim(implode(' to ', array_filter([$item['start_date'] ?? null, $item['end_date'] ?? null])))])) }}</div>
                @endif
                @if(! empty($item['responsibilities']))
                    <ul>
                        @foreach($item['responsibilities'] as $responsibility)
                            <li>{{ $responsibility }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    @endif

    @if(! empty($cv['projects']))
        <h2>{!! $tick !!}Projects and Volunteer Work</h2>
        @foreach($cv['projects'] as $item)
            <div class="item">
                <div class="item-title">{{ $item['name'] ?? '' }}</div>
                @if(! empty($item['description']))
                    <div>{{ $item['description'] }}</div>
                @endif
                @if(! empty($item['link']))
                    <div class="item-meta">{{ $item['link'] }}</div>
                @endif
            </div>
        @endforeach
    @endif

    @if(! empty($cv['skills']))
        <h2>{!! $tick !!}Skills</h2>
        @if($design === 'ats')
            <p>{{ implode(', ', $cv['skills']) }}</p>
        @else
            <div class="skills">
                @foreach($cv['skills'] as $skill)
                    <span>{{ $skill }}</span>
                @endforeach
            </div>
        @endif
    @endif

    @if(! empty($cv['languages']))
        <h2>{!! $tick !!}Languages</h2>
        <ul>
            @foreach($cv['languages'] as $language)
                <li>{{ $language }}</li>
            @endforeach
        </ul>
    @endif

    @if(! empty($cv['references']))
        <h2>{!! $tick !!}References</h2>
        @foreach($cv['references'] as $item)
            <div class="item">
                {{ implode('  ·  ', array_filter([$item['name'] ?? null, $item['role'] ?? null, $item['company'] ?? null, $item['phone'] ?? null, $item['email'] ?? null])) }}
            </div>
        @endforeach
    @endif

    <div class="footer">Generated by Wakanda Jobs &middot; {{ $generatedAt }}</div>
</body>
</html>
