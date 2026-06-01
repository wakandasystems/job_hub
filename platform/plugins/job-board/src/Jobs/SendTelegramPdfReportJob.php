<?php

namespace Botble\JobBoard\Jobs;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SendTelegramPdfReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 120;
    public int $tries   = 2;

    public function __construct(
        public readonly string $chatId,
        public readonly string $reportType = 'crawlers',
    ) {}

    public function handle(): void
    {
        $token = setting('telegram_bot_token', '');
        if ($token === '') {
            return;
        }

        [$html, $basename] = match ($this->reportType) {
            'crawlers' => [$this->crawlersHtml(), 'crawler-report'],
            default    => [$this->crawlersHtml(), 'crawler-report'],
        };

        $filename = $basename . '-' . now()->format('Y-m-d') . '.pdf';
        $path     = storage_path('app/temp/' . $filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        file_put_contents($path, $pdf->output());

        Http::timeout(60)
            ->attach('document', file_get_contents($path), $filename)
            ->post("https://api.telegram.org/bot{$token}/sendDocument", [
                'chat_id' => $this->chatId,
                'caption' => 'Crawler Report — ' . now()->format('j F Y'),
            ]);

        @unlink($path);
    }

    // -------------------------------------------------------------------------
    // Pre-fetch flags as base64 data URIs so Dompdf needs no remote access
    // -------------------------------------------------------------------------

    /** @return array<string,string> code => "data:image/png;base64,..." */
    private function prefetchFlags(array $codes): array
    {
        $flags = [];

        foreach (array_unique(array_filter($codes)) as $code) {
            $code = strtolower($code);
            $url  = "https://flagcdn.com/20x15/{$code}.png";
            try {
                $resp = Http::timeout(4)->get($url);
                if ($resp->successful() && strlen($resp->body()) > 100) {
                    $flags[$code] = 'data:image/png;base64,' . base64_encode($resp->body());
                }
            } catch (\Throwable) {
                // Skip missing flags — cell will show country name only
            }
        }

        return $flags;
    }

    private function flagImg(array $flags, ?string $code): string
    {
        if (! $code) {
            return '';
        }
        $src = $flags[strtolower($code)] ?? null;
        if (! $src) {
            return '';
        }
        return "<img src=\"{$src}\" width=\"20\" height=\"15\" style=\"vertical-align:middle;margin-right:5px\">";
    }

    // -------------------------------------------------------------------------
    // HTML for crawler report
    // -------------------------------------------------------------------------

    private function crawlersHtml(): string
    {
        $rows = DB::table('jb_job_crawlers as c')
            ->leftJoin('jb_jobs as j', function ($join) {
                $zambiaId = (int) (\Illuminate\Support\Facades\DB::table('countries')->whereRaw("LOWER(name) = 'zambia'")->value('id') ?: 7);
                $join->on('j.crawler_id', '=', 'c.id')->where('j.country_id', $zambiaId);
            })
            ->select([
                'c.name',
                'c.is_active',
                DB::raw("(
                    SELECT co.name FROM countries co
                    JOIN jb_jobs j2 ON j2.country_id = co.id
                    WHERE j2.crawler_id = c.id
                    GROUP BY co.id ORDER BY COUNT(*) DESC LIMIT 1
                ) AS country"),
                DB::raw("(
                    SELECT co.code FROM countries co
                    JOIN jb_jobs j2 ON j2.country_id = co.id
                    WHERE j2.crawler_id = c.id
                    GROUP BY co.id ORDER BY COUNT(*) DESC LIMIT 1
                ) AS country_code"),
                DB::raw("SUM(CASE WHEN DATE(j.created_at) = CURDATE() THEN 1 ELSE 0 END) AS today"),
                DB::raw("SUM(CASE WHEN j.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS two_days"),
                DB::raw("SUM(CASE WHEN j.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) THEN 1 ELSE 0 END) AS week"),
                DB::raw("SUM(CASE WHEN j.created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) THEN 1 ELSE 0 END) AS month"),
                DB::raw("COUNT(j.id) AS all_time"),
            ])
            ->groupBy('c.id', 'c.name', 'c.is_active')
            ->having(DB::raw("COUNT(j.id)"), '>', 0)
            ->orderByDesc('today')
            ->orderByDesc('week')
            ->get();


        // Pre-fetch all flags needed
        $flags = $this->prefetchFlags($rows->pluck('country_code')->toArray());

        $totals    = array_fill_keys(['today', 'two_days', 'week', 'month', 'all_time'], 0);
        $tableRows = '';

        foreach ($rows as $row) {
            foreach (['today', 'two_days', 'week', 'month', 'all_time'] as $k) {
                $totals[$k] += (int) $row->{$k};
            }

            $status     = $row->is_active ? 'Active' : 'Inactive';
            $statusColor = $row->is_active ? '#166534' : '#991b1b';
            $statusBg    = $row->is_active ? '#dcfce7' : '#fee2e2';
            $flagImg    = $this->flagImg($flags, $row->country_code);
            $country    = htmlspecialchars($row->country ?? '—');
            $name       = htmlspecialchars($row->name);

            $tableRows .= "<tr>
                <td style='color:{$statusColor};background:{$statusBg};text-align:center;font-size:8px'>{$status}</td>
                <td style='white-space:nowrap'>{$flagImg}{$country}</td>
                <td>{$name}</td>
                <td class='n'>{$row->today}</td>
                <td class='n'>{$row->two_days}</td>
                <td class='n'>{$row->week}</td>
                <td class='n'>{$row->month}</td>
                <td class='n'><b>{$row->all_time}</b></td>
            </tr>";
        }

        $date = now()->format('j F Y');

        return <<<HTML
<!DOCTYPE html><html><head><meta charset="utf-8">
<style>
body  { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; margin: 20px; }
h1    { font-size: 15px; margin-bottom: 3px; }
p.sub { font-size: 9px; color: #64748b; margin: 0 0 12px; }
table { width: 100%; border-collapse: collapse; }
th    { background: #0f172a; color: #fff; padding: 6px 8px; font-size: 9px; text-transform: uppercase; text-align: left; }
th.n, td.n { text-align: right; }
td    { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
tr:nth-child(even) td { background: #f8fafc; }
tr.total td { background: #1e293b; color: #fff; font-weight: bold; }
</style></head><body>
<h1>Wakanda Jobs - Crawler Report</h1>
<p class="sub">Generated {$date}</p>
<table>
  <thead><tr>
    <th style="width:50px">Status</th>
    <th>Country</th>
    <th>Crawler</th>
    <th class="n">Today</th>
    <th class="n">2 Days</th>
    <th class="n">Week</th>
    <th class="n">Month</th>
    <th class="n">All Time</th>
  </tr></thead>
  <tbody>
    {$tableRows}
    <tr class="total">
      <td colspan="3">TOTAL</td>
      <td class="n">{$totals['today']}</td>
      <td class="n">{$totals['two_days']}</td>
      <td class="n">{$totals['week']}</td>
      <td class="n">{$totals['month']}</td>
      <td class="n">{$totals['all_time']}</td>
    </tr>
  </tbody>
</table>
</body></html>
HTML;
    }
}
