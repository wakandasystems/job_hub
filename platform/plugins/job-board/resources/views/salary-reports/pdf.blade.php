<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a1a2e; line-height: 1.5; }

    /* Cover page */
    .cover { page-break-after: always; min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; background: #1a3c5e; color: white; padding: 60px 40px; }
    .cover-logo { font-size: 22px; font-weight: bold; letter-spacing: 2px; margin-bottom: 60px; color: #f9a826; }
    .cover-title { font-size: 32px; font-weight: bold; margin-bottom: 16px; }
    .cover-subtitle { font-size: 16px; opacity: 0.8; margin-bottom: 40px; }
    .cover-meta { font-size: 12px; opacity: 0.6; }

    /* Content */
    .content { padding: 30px 40px; }
    h1 { font-size: 20px; color: #1a3c5e; border-bottom: 2px solid #f9a826; padding-bottom: 8px; margin-bottom: 20px; margin-top: 30px; }
    h2 { font-size: 15px; color: #1a3c5e; margin-bottom: 12px; margin-top: 24px; }
    p { margin-bottom: 10px; }

    /* Tables */
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 10px; }
    thead { background: #1a3c5e; color: white; }
    thead th { padding: 7px 8px; text-align: left; font-weight: bold; }
    thead th.text-right { text-align: right; }
    tbody tr:nth-child(even) { background: #f8f9fb; }
    tbody td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
    tbody td.text-right { text-align: right; }
    tbody td.bold { font-weight: bold; }

    /* Stat boxes */
    .stat-grid { display: table; width: 100%; margin-bottom: 20px; }
    .stat-box { display: table-cell; width: 25%; border: 1px solid #e2e8f0; padding: 12px; text-align: center; vertical-align: middle; }
    .stat-label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
    .stat-value { font-size: 18px; font-weight: bold; color: #1a3c5e; }

    /* Page break */
    .page-break { page-break-before: always; }

    /* Footer */
    .footer { margin-top: 30px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; text-align: center; }

    /* Disclaimer */
    .disclaimer { background: #f8f9fb; border-left: 3px solid #f9a826; padding: 10px 14px; font-size: 9.5px; color: #475569; margin-bottom: 20px; }
</style>
</head>
<body>

{{-- Cover page --}}
<div class="cover">
    <div class="cover-logo">WAKANDA JOBS</div>
    <div class="cover-title">{{ $report->title }}</div>
    <div class="cover-subtitle">
        Zambia Salary Benchmarking Report{{ $report->sector ? ' — ' . $report->sector : '' }}
    </div>
    <div class="cover-meta">
        Published {{ $generatedAt }} &nbsp;|&nbsp; www.wakandajobs.com<br>
        Based on {{ $overall['count'] ?? 0 }} salary data points
    </div>
</div>

{{-- Content --}}
<div class="content">

    <h1>Executive Summary</h1>

    <div class="disclaimer">
        All salaries are expressed in Zambian Kwacha (ZMW) and normalised to a monthly equivalent. Data is derived from
        job postings on Wakanda Jobs and candidate self-reported salary expectations. Figures represent market-observed
        ranges and are provided for benchmarking purposes only.
    </div>

    @if(isset($overall['count']) && $overall['count'] > 0)
    <div class="stat-grid">
        <div class="stat-box">
            <div class="stat-label">Data Points</div>
            <div class="stat-value">{{ number_format($overall['count']) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Overall Median</div>
            <div class="stat-value">K{{ number_format($overall['median']) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">25th Percentile</div>
            <div class="stat-value">K{{ number_format($overall['p25']) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">75th Percentile</div>
            <div class="stat-value">K{{ number_format($overall['p75']) }}</div>
        </div>
    </div>
    @endif

    <p>
        This report provides salary benchmarking data for {{ $report->year }} across the Zambian job market
        {{ $report->sector ? 'with a focus on the ' . $report->sector . ' sector' : 'across all sectors' }}.
        It is designed to help HR professionals, hiring managers, and business leaders make informed decisions
        about compensation packages.
    </p>

    {{-- Top paying titles --}}
    <h1 class="page-break">Top 20 Paying Job Titles</h1>
    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="40%">Job Title</th>
                <th class="text-right" width="15%">Min (ZMW)</th>
                <th class="text-right" width="20%">Median (ZMW)</th>
                <th class="text-right" width="15%">Max (ZMW)</th>
                <th class="text-right" width="5%">N</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topTitles as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row['title'] }}</td>
                <td class="text-right">{{ number_format($row['min']) }}</td>
                <td class="text-right bold">{{ number_format($row['median']) }}</td>
                <td class="text-right">{{ number_format($row['max']) }}</td>
                <td class="text-right">{{ $row['count'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- By sector --}}
    <h1 class="page-break">Salary by Sector</h1>
    <table>
        <thead>
            <tr>
                <th width="35%">Sector</th>
                <th class="text-right" width="17%">Min (ZMW)</th>
                <th class="text-right" width="17%">Avg (ZMW)</th>
                <th class="text-right" width="17%">Median (ZMW)</th>
                <th class="text-right" width="17%">Max (ZMW)</th>
                <th class="text-right" width="7%">N</th>
            </tr>
        </thead>
        <tbody>
            @foreach($byCategory as $row)
            <tr>
                <td>{{ $row['name'] }}</td>
                <td class="text-right">{{ number_format($row['min']) }}</td>
                <td class="text-right">{{ number_format($row['avg']) }}</td>
                <td class="text-right bold">{{ number_format($row['median']) }}</td>
                <td class="text-right">{{ number_format($row['max']) }}</td>
                <td class="text-right">{{ $row['count'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- By city --}}
    <h1>Salary by Location</h1>
    <table>
        <thead>
            <tr>
                <th width="35%">City / Region</th>
                <th class="text-right" width="20%">Min (ZMW)</th>
                <th class="text-right" width="20%">Median (ZMW)</th>
                <th class="text-right" width="20%">Max (ZMW)</th>
                <th class="text-right" width="5%">N</th>
            </tr>
        </thead>
        <tbody>
            @foreach($byCity->take(20) as $row)
            <tr>
                <td>{{ $row['city'] }}</td>
                <td class="text-right">{{ number_format($row['min']) }}</td>
                <td class="text-right bold">{{ number_format($row['median']) }}</td>
                <td class="text-right">{{ number_format($row['max']) }}</td>
                <td class="text-right">{{ $row['count'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Methodology --}}
    <h1 class="page-break">Methodology</h1>
    <p>
        <strong>Data source:</strong> Job postings on Wakanda Jobs published in the 12 months prior to the report
        generation date. Postings with "negotiable," "competitive," or hidden salaries are excluded.
    </p>
    <p>
        <strong>Normalisation:</strong> All salaries are converted to monthly ZMW equivalents using the following
        multipliers: hourly ×160, daily ×22, weekly ×4.33, monthly ×1, annual ÷12. Foreign currency amounts are
        converted using platform exchange rates at the time of data collection.
    </p>
    <p>
        <strong>Statistics:</strong> Minimum, maximum, mean, and median (50th percentile) are calculated from the
        normalised monthly salary distribution. The 25th and 75th percentiles represent the "typical range."
    </p>
    <p>
        <strong>Limitations:</strong> Data coverage varies by sector and location. Roles with fewer than 3 postings
        are excluded from title-level benchmarks. This report is a point-in-time snapshot and should be used as
        one of several inputs in compensation decisions.
    </p>

    <div class="footer">
        © {{ $report->year }} Wakanda Jobs &nbsp;|&nbsp; www.wakandajobs.com &nbsp;|&nbsp;
        Generated {{ $generatedAt }} &nbsp;|&nbsp; Confidential — for licensed use only
    </div>

</div>
</body>
</html>
