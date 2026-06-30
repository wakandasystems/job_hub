<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Optionally pass specific job IDs as CLI args: php tmp_repair_jobzambia_apply_urls.php 123 456
$targetJobIds = array_values(array_filter(array_map('intval', array_slice($argv, 1))));

$extractApplicationContact = static function (string $html) use (&$expandRedirectUrl): array {
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded   = $document->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (! $loaded) {
        return ['apply_email' => '', 'apply_url' => ''];
    }

    $xpath = new DOMXPath($document);

    // Email via mailto link inside .job_application
    $emailNode = $xpath->query(
        '//div[contains(concat(" ", normalize-space(@class), " "), " job_application ")]'
        . '//a[contains(concat(" ", normalize-space(@class), " "), " job_application_email ")]'
    )?->item(0);

    $applyEmail = '';
    if ($emailNode instanceof DOMElement) {
        $mailto = trim(html_entity_decode($emailNode->getAttribute('href'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (preg_match('/^mailto:([^?]+)/i', $mailto, $matches)) {
            $applyEmail = trim(rawurldecode($matches[1]));
        }
        if ($applyEmail === '') {
            $applyEmail = trim(html_entity_decode($emailNode->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
    }

    // External apply URL inside .application_details
    $linkNode = $xpath->query(
        '//div[contains(concat(" ", normalize-space(@class), " "), " job_application ")]'
        . '//div[contains(concat(" ", normalize-space(@class), " "), " application_details ")]//a[@href]'
    )?->item(0);

    if (! $linkNode instanceof DOMElement) {
        return ['apply_email' => $applyEmail, 'apply_url' => ''];
    }

    $href = trim(html_entity_decode($linkNode->getAttribute('href'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    if ($href === '' || str_starts_with($href, 'javascript:')) {
        return ['apply_email' => $applyEmail, 'apply_url' => ''];
    }

    if (str_starts_with($href, 'mailto:')) {
        if ($applyEmail === '' && preg_match('/^mailto:([^?]+)/i', $href, $matches)) {
            $applyEmail = trim(rawurldecode($matches[1]));
        }
        return ['apply_email' => $applyEmail, 'apply_url' => $href];
    }

    // Follow redirects so we store the canonical employer URL, not a tracking hop.
    $resolved = $href;
    try {
        $resp = Http::timeout(20)
            ->withOptions(['allow_redirects' => ['max' => 10, 'track_redirects' => true]])
            ->withHeaders(['User-Agent' => 'WakandaJobsCrawler/1.0 (+https://www.wakandajobs.com)'])
            ->get($href);
        $history = $resp->header('X-Guzzle-Redirect-History');
        if (is_array($history) && $history !== []) {
            $resolved = trim((string) end($history)) ?: $href;
        } elseif (is_string($history) && trim($history) !== '') {
            $parts    = array_values(array_filter(array_map('trim', explode(',', $history))));
            $resolved = $parts !== [] ? (string) end($parts) : $href;
        }
    } catch (Throwable) {
        // keep $resolved = $href
    }

    return ['apply_email' => $applyEmail, 'apply_url' => $resolved];
};

$jobs = DB::table('jb_jobs')
    ->when(
        $targetJobIds !== [],
        fn ($q) => $q->whereIn('id', $targetJobIds),
        fn ($q) => $q->where('external_source_url', 'like', 'https://jobzambia.com/%')
    )
    ->get(['id', 'name', 'external_source_url', 'apply_url', 'apply_email']);

echo "Found " . $jobs->count() . " jobzambia.com jobs to check.\n";

$updated = 0;
$skipped = 0;

foreach ($jobs as $job) {
    $sourceUrl = trim((string) $job->external_source_url);

    if ($sourceUrl === '') {
        $skipped++;
        continue;
    }

    try {
        $response = Http::timeout(20)
            ->withHeaders([
                'User-Agent'      => 'WakandaJobsCrawler/1.0 (+https://www.wakandajobs.com)',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])
            ->get($sourceUrl);
    } catch (Throwable $e) {
        $skipped++;
        echo "ERROR {$job->id} {$job->name}: {$e->getMessage()}\n";
        continue;
    }

    if ($response->status() === 404) {
        // Source job deleted on jobzambia — close it on Wakanda Jobs.
        if ($job->status !== 'closed') {
            DB::table('jb_jobs')->where('id', $job->id)->update([
                'status'                   => 'closed',
                'expire_date'              => now()->subDay()->toDateString(),
                'application_closing_date' => now()->subDay()->toDateString(),
                'updated_at'               => now(),
            ]);
            $updated++;
            echo "CLOSED (404) {$job->id} {$job->name}\n";
        } else {
            $skipped++;
            echo "ALREADY CLOSED {$job->id} {$job->name}\n";
        }
        continue;
    }

    if (! $response->successful()) {
        $skipped++;
        echo "HTTP {$response->status()} {$job->id} {$job->name}\n";
        continue;
    }

    $contact    = $extractApplicationContact($response->body());
    $applyEmail = trim((string) ($contact['apply_email'] ?? ''));
    $applyUrl   = trim((string) ($contact['apply_url'] ?? ''));

    // If no external URL found, keep the source URL (already in apply_url); skip.
    if ($applyUrl === '' || $applyUrl === $sourceUrl) {
        // Still update email if we found one.
        if ($applyEmail !== '' && $applyEmail !== trim((string) $job->apply_email)) {
            DB::table('jb_jobs')->where('id', $job->id)->update(['apply_email' => $applyEmail, 'updated_at' => now()]);
            $updated++;
            echo "EMAIL ONLY {$job->id} {$job->name}: email={$applyEmail}\n";
        } else {
            $skipped++;
        }
        continue;
    }

    $payload = ['updated_at' => now()];
    $changed = false;

    if ($applyUrl !== trim((string) $job->apply_url)) {
        $payload['apply_url'] = $applyUrl;
        $changed = true;
    }
    if ($applyEmail !== '' && $applyEmail !== trim((string) $job->apply_email)) {
        $payload['apply_email'] = $applyEmail;
        $changed = true;
    }

    if (! $changed) {
        $skipped++;
        continue;
    }

    DB::table('jb_jobs')->where('id', $job->id)->update($payload);
    $updated++;

    $msg = "UPDATED {$job->id} {$job->name}:";
    if (isset($payload['apply_url']))   $msg .= " url={$payload['apply_url']}";
    if (isset($payload['apply_email'])) $msg .= " email={$payload['apply_email']}";
    echo $msg . "\n";
}

echo "\nDONE  updated={$updated}  skipped={$skipped}  total=" . $jobs->count() . "\n";
