<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$targetJobIds = array_values(array_filter(array_map('intval', array_slice($argv, 1))));

$expandRedirectUrl = static function (string $url): string {
    $url = trim($url);

    if ($url === '' || ! preg_match('#^https?://#i', $url)) {
        return $url;
    }

    try {
        $response = Http::timeout(20)
            ->withOptions([
                'allow_redirects' => [
                    'max' => 10,
                    'track_redirects' => true,
                ],
            ])
            ->get($url);

        $history = $response->header('X-Guzzle-Redirect-History');

        if (is_array($history) && $history !== []) {
            return trim((string) end($history)) ?: $url;
        }

        if (is_string($history) && trim($history) !== '') {
            $parts = array_values(array_filter(array_map('trim', explode(',', $history))));

            if ($parts !== []) {
                return (string) end($parts);
            }
        }
    } catch (Throwable) {
        return $url;
    }

    return $url;
};

$extractApplicationContact = static function (string $html) use ($expandRedirectUrl): array {
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (! $loaded) {
        return ['apply_email' => '', 'apply_url' => ''];
    }

    $xpath = new DOMXPath($document);

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

    return ['apply_email' => $applyEmail, 'apply_url' => $expandRedirectUrl($href)];
};

$jobs = DB::table('jb_jobs')
    ->when(
        $targetJobIds !== [],
        fn ($query) => $query->whereIn('id', $targetJobIds),
        fn ($query) => $query->where('external_source_url', 'like', 'https://jobsearchzm.com/%')
    )
    ->get(['id', 'name', 'external_source_url', 'apply_url', 'apply_email']);

$updated = 0;
$skipped = 0;

foreach ($jobs as $job) {
    $url = trim((string) $job->external_source_url);

    if ($url === '') {
        $skipped++;
        continue;
    }

    try {
        $response = Http::timeout(20)
            ->withHeaders([
                'User-Agent' => 'WakandaJobsCrawler/1.0 (+https://www.wakandajobs.com)',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])
            ->get($url);
    } catch (Throwable $e) {
        $skipped++;
        echo "ERROR {$job->id} {$job->name}: {$e->getMessage()}\n";
        continue;
    }

    if (! $response->successful()) {
        $skipped++;
        echo "HTTP {$job->id} {$job->name}: {$response->status()}\n";
        continue;
    }

    $contact = $extractApplicationContact($response->body());
    $applyEmail = trim((string) ($contact['apply_email'] ?? ''));
    $applyUrl = trim((string) ($contact['apply_url'] ?? ''));
    $resolvedApplyUrl = $applyUrl !== '' ? $applyUrl : $url;

    $changed = false;
    $payload = ['updated_at' => now()];

    if ($applyEmail !== '' && $applyEmail !== trim((string) $job->apply_email)) {
        $payload['apply_email'] = $applyEmail;
        $changed = true;
    }

    if ($resolvedApplyUrl !== '' && $resolvedApplyUrl !== trim((string) $job->apply_url)) {
        $payload['apply_url'] = $resolvedApplyUrl;
        $changed = true;
    }

    if (! $changed) {
        $skipped++;
        continue;
    }

    DB::table('jb_jobs')
        ->where('id', $job->id)
        ->update($payload);

    $updated++;
    echo "UPDATED {$job->id} {$job->name}";
    if ($applyEmail !== '') {
        echo " email={$applyEmail}";
    }
    if ($resolvedApplyUrl !== '') {
        echo " url={$resolvedApplyUrl}";
    }
    echo "\n";
}

echo "DONE updated={$updated} skipped={$skipped} total=" . $jobs->count() . "\n";
