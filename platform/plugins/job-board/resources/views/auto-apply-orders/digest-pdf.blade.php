<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1a1a2e; background: #fff; }

  .header { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%); color: #fff; padding: 32px 36px 26px; text-align: center; }
  .header-logo-wrap { margin-bottom: 14px; }
  .header-logo-wrap img { max-height: 64px; max-width: 220px; }
  .header-logo-name { font-size: 26px; font-weight: 700; letter-spacing: -0.5px; }
  .header-logo-name span { color: #e94560; }
  .header-title { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
  .header-period { font-size: 11px; color: #90cdf4; }

  .greeting { padding: 22px 36px 0; font-size: 14px; color: #2d3748; }

  .summary { margin: 18px 36px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 22px; }
  .summary-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #718096; margin-bottom: 12px; }
  .summary-grid { display: table; width: 100%; }
  .summary-cell { display: table-cell; text-align: center; padding: 6px 0; border-right: 1px solid #e2e8f0; }
  .summary-cell:last-child { border-right: none; }
  .summary-number { font-size: 26px; font-weight: 700; line-height: 1; }
  .summary-number.green { color: #38a169; }
  .summary-number.orange { color: #dd6b20; }
  .summary-number.red { color: #e53e3e; }
  .summary-label { font-size: 10px; color: #718096; margin-top: 4px; }

  .section { margin: 18px 36px 0; }
  .section-header { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #718096; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-bottom: 10px; }

  table.jobs { width: 100%; border-collapse: collapse; }
  table.jobs th { background: #f8fafc; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #718096; padding: 7px 8px; text-align: left; border-bottom: 1px solid #e2e8f0; }
  table.jobs td { padding: 8px 8px; border-bottom: 1px solid #f0f4f8; font-size: 10.5px; vertical-align: top; }
  table.jobs tr:last-child td { border-bottom: none; }
  table.jobs tr:nth-child(even) td { background: #fafcff; }

  a { color: #0f3460; text-decoration: underline; }

  .badge { display: inline-block; padding: 2px 7px; border-radius: 9px; font-size: 9px; font-weight: 700; }
  .badge-green  { background: #c6f6d5; color: #22543d; }
  .badge-blue   { background: #bee3f8; color: #2a4365; }
  .badge-orange { background: #feebc8; color: #7b341e; }
  .badge-purple { background: #e9d8fd; color: #553c9a; }
  .badge-gray   { background: #e2e8f0; color: #4a5568; }

  .footer { margin-top: 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 36px; font-size: 10px; color: #a0aec0; text-align: center; }
  .footer strong { color: #718096; }

  .no-activity { text-align: center; padding: 24px 0; color: #a0aec0; font-size: 12px; }

  .datetime { font-size: 9.5px; color: #718096; white-space: nowrap; }
</style>
</head>
<body>

<div class="header">
  <div class="header-logo-wrap">
    @if($logoUrl)
      <img src="{{ $logoUrl }}" alt="Wakanda Jobs">
    @else
      <div class="header-logo-name">Wakanda<span>Jobs</span></div>
    @endif
  </div>
  <div class="header-title">Auto Apply Weekly Digest</div>
  <div class="header-period">{{ $periodLabel }}</div>
</div>

<div class="greeting">
  Hi {{ $account->first_name }}, here's your Auto Apply activity for the past week.
</div>

<div class="summary">
  <div class="summary-title">This Week's Summary</div>
  <div class="summary-grid">
    <div class="summary-cell">
      <div class="summary-number green">{{ $sentCount }}</div>
      <div class="summary-label">Applications Sent</div>
    </div>
    <div class="summary-cell">
      <div class="summary-number orange">{{ $skippedCount }}</div>
      <div class="summary-label">Skipped (Low Score)</div>
    </div>
    @if($manualCount > 0)
    <div class="summary-cell">
      <div class="summary-number" style="color:#553c9a;">{{ $manualCount }}</div>
      <div class="summary-label">Manual Apply</div>
    </div>
    @endif
    @if($failedCount > 0)
    <div class="summary-cell">
      <div class="summary-number red">{{ $failedCount }}</div>
      <div class="summary-label">Failed</div>
    </div>
    @endif
  </div>
</div>

@if($sentLogs->isNotEmpty())
<div class="section">
  <div class="section-header">Applications Sent ({{ $sentCount }})</div>
  <table class="jobs">
    <thead>
      <tr>
        <th style="width:46%">Job Title</th>
        <th style="width:13%">Score</th>
        <th style="width:13%">Type</th>
        <th style="width:28%">Date &amp; Time</th>
      </tr>
    </thead>
    <tbody>
      @foreach($sentLogs as $log)
      @php
        $jobName  = $log->job?->name ?? 'Unknown Job';
        $jobUrl   = $jobUrls[$log->job_id] ?? null;
        $score    = $log->match_score;
        $ts       = $log->sent_at ?? $log->created_at;
        $isManual = $log->email_sent_to === 'manual-apply-notice';
      @endphp
      <tr>
        <td>
          @if($jobUrl)
            <a href="{{ $jobUrl }}">{{ $jobName }}</a>
          @else
            {{ $jobName }}
          @endif
        </td>
        <td>
          <span class="badge {{ $score >= 80 ? 'badge-green' : ($score >= 60 ? 'badge-blue' : 'badge-orange') }}">
            {{ $score }}%
          </span>
        </td>
        <td>
          @if($isManual)
            <span class="badge badge-purple">Manual</span>
          @else
            <span class="badge badge-green">Auto</span>
          @endif
        </td>
        <td class="datetime">
          {{ $ts?->format('d M Y') }}<br>
          <span style="color:#a0aec0;">{{ $ts?->format('H:i') }} UTC</span>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endif

@if($skippedLogs->isNotEmpty())
<div class="section" style="margin-top:18px;">
  <div class="section-header">Skipped — Below Match Threshold ({{ $skippedCount }})</div>
  <table class="jobs">
    <thead>
      <tr>
        <th style="width:59%">Job Title</th>
        <th style="width:13%">Score</th>
        <th style="width:28%">Date &amp; Time</th>
      </tr>
    </thead>
    <tbody>
      @foreach($skippedLogs as $log)
      @php
        $jobName = $log->job?->name ?? 'Unknown Job';
        $jobUrl  = $jobUrls[$log->job_id] ?? null;
        $ts      = $log->sent_at ?? $log->created_at;
      @endphp
      <tr>
        <td>
          @if($jobUrl)
            <a href="{{ $jobUrl }}">{{ $jobName }}</a>
          @else
            {{ $jobName }}
          @endif
        </td>
        <td><span class="badge badge-orange">{{ $log->match_score }}%</span></td>
        <td class="datetime">
          {{ $ts?->format('d M Y') }}<br>
          <span style="color:#a0aec0;">{{ $ts?->format('H:i') }} UTC</span>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endif

@if($sentLogs->isEmpty() && $skippedLogs->isEmpty())
<div class="no-activity">No auto-apply activity recorded in the past 7 days.</div>
@endif

<div class="footer">
  <strong>Wakanda Jobs</strong> &mdash; wakandajobs.com<br>
  Report covers {{ $periodLabel }} &nbsp;&bull;&nbsp; Generated {{ now()->format('d M Y, H:i') }} UTC
</div>

</body>
</html>
