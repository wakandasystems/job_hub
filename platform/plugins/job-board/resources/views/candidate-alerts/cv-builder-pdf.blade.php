<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    @php($design = $design ?? 'premium')
    <style>
        @page { margin: 34px 38px 42px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11.5px; line-height: 1.5; }
        .hero { background: #111827; color: #fff; padding: 18px 20px 16px; margin: -34px -38px 18px; }
        h1 { font-size: 27px; margin: 0 0 5px; color: #fff; letter-spacing: .03em; text-transform: uppercase; }
        h2 { font-size: 12px; margin: 18px 0 8px; padding: 6px 8px; color: #111827; background: #eef2ff; border-left: 4px solid #2563eb; text-transform: uppercase; letter-spacing: .08em; }
        .headline { font-size: 13px; color: #dbeafe; margin-bottom: 8px; }
        .contact { color: #e5e7eb; font-size: 10.5px; }
        .item { margin-bottom: 11px; page-break-inside: avoid; }
        .item-title { font-weight: 700; color: #111827; font-size: 12px; }
        .muted { color: #6b7280; font-size: 10.5px; margin-top: 1px; }
        ul { margin: 5px 0 0 18px; padding: 0; }
        li { margin-bottom: 3px; }
        p { margin: 0 0 8px; }
        .skills span { display: inline-block; border: 1px solid #bfdbfe; border-radius: 12px; padding: 4px 8px; margin: 0 5px 5px 0; background: #eff6ff; color: #1e3a8a; font-size: 10.5px; }
        .footer { position: fixed; bottom: -22px; left: 0; right: 0; color: #9ca3af; font-size: 9px; text-align: center; }
        @if($design === 'academic')
            .hero { background: #fff; color: #111827; text-align: center; border-bottom: 2px solid #111827; margin: -10px 0 18px; padding: 0 0 14px; }
            h1 { color: #111827; font-size: 30px; letter-spacing: .16em; margin-bottom: 12px; }
            .headline { color: #374151; font-size: 14px; }
            .contact { color: #4b5563; }
            h2 { background: transparent; border-left: 0; border-bottom: 1px solid #111827; padding: 0 0 4px; letter-spacing: .12em; }
            .skills span { border-radius: 3px; background: #f9fafb; color: #111827; border-color: #d1d5db; }
        @elseif($design === 'creative')
            .hero { background: #0f766e; color: #fff; padding: 20px 22px 18px; border-bottom: 8px solid #f59e0b; }
            h1 { color: #fff; font-size: 28px; letter-spacing: .08em; }
            .headline { color: #ccfbf1; }
            .contact { color: #fef3c7; }
            h2 { background: #fef3c7; border-left-color: #0f766e; color: #134e4a; }
            .skills span { background: #ccfbf1; border-color: #5eead4; color: #134e4a; }
        @endif
    </style>
</head>
<body>
    <div class="hero">
        <h1>{{ $design === 'academic' ? 'CURRICULUM VITAE' : ($cv['full_name'] ?? 'Candidate CV') }}</h1>

        @if($design === 'academic')
            <div class="headline">{{ $cv['full_name'] ?? 'Candidate' }}</div>
        @elseif(! empty($cv['headline']))
            <div class="headline">{{ $cv['headline'] }}</div>
        @endif

        <div class="contact">
            {{ implode(' | ', array_filter([$cv['phone'] ?? null, $cv['email'] ?? null, $cv['location'] ?? null])) }}
        </div>
    </div>

    @if(! empty($cv['summary']))
        <h2>Professional Profile</h2>
        <p>{{ $cv['summary'] }}</p>
    @endif

    @if(! empty($cv['skills']))
        <h2>Key Skills</h2>
        <div class="skills">
            @foreach($cv['skills'] as $skill)
                <span>{{ $skill }}</span>
            @endforeach
        </div>
    @endif

    @if(! empty($cv['experience']))
        <h2>Work Experience</h2>
        @foreach($cv['experience'] as $item)
            <div class="item">
                <div class="item-title">{{ implode(' - ', array_filter([$item['job_title'] ?? null, $item['company'] ?? null])) }}</div>
                <div class="muted">{{ implode(' | ', array_filter([$item['location'] ?? null, trim(implode(' to ', array_filter([$item['start_date'] ?? null, $item['end_date'] ?? null])))])) }}</div>
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

    @if(! empty($cv['education']))
        <h2>Education</h2>
        @foreach($cv['education'] as $item)
            <div class="item">
                <div class="item-title">{{ implode(' - ', array_filter([$item['qualification'] ?? null, $item['field'] ?? null])) }}</div>
                <div class="muted">{{ implode(' | ', array_filter([$item['institution'] ?? null, trim(implode(' - ', array_filter([$item['start_year'] ?? null, $item['end_year'] ?? null])))])) }}</div>
            </div>
        @endforeach
    @endif

    @if(! empty($cv['projects']))
        <h2>Projects and Volunteer Work</h2>
        @foreach($cv['projects'] as $item)
            <div class="item">
                <div class="item-title">{{ $item['name'] ?? '' }}</div>
                @if(! empty($item['description']))
                    <div>{{ $item['description'] }}</div>
                @endif
            </div>
        @endforeach
    @endif

    @if(! empty($cv['certifications']))
        <h2>Certifications and Training</h2>
        <ul>
            @foreach($cv['certifications'] as $certification)
                <li>{{ $certification }}</li>
            @endforeach
        </ul>
    @endif

    @if(! empty($cv['languages']))
        <h2>Languages</h2>
        <ul>
            @foreach($cv['languages'] as $language)
                <li>{{ $language }}</li>
            @endforeach
        </ul>
    @endif

    @if(! empty($cv['references']))
        <h2>References</h2>
        @foreach($cv['references'] as $item)
            <div class="item">
                {{ implode(' | ', array_filter([$item['name'] ?? null, $item['role'] ?? null, $item['company'] ?? null, $item['phone'] ?? null, $item['email'] ?? null])) }}
            </div>
        @endforeach
    @endif

    <div class="footer">Generated by Wakanda Jobs on {{ $generatedAt }}</div>
</body>
</html>
