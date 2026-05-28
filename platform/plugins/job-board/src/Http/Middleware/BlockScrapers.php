<?php

namespace Botble\JobBoard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BlockScrapers
{
    protected array $blockedAgents = [
        'python-requests',
        'python-urllib',
        'python/',
        'scrapy',
        'curl/',
        'wget/',
        'go-http-client',
        'java/',
        'okhttp',
        'aiohttp',
        'httpx',
        'mechanize',
        'libwww-perl',
        'lwp-trivial',
        'petalbot',
        'semrushbot',
        'ahrefsbot',
        'mj12bot',
        'dotbot',
        'blexbot',
        'sistrix',
        'dataforseo',
        'serpstatbot',
        'bytespider',
        'phantomjs',
        'headlesschrome',
        'selenium',
        'webdriver',
        'htmlunit',
        'apachehttpclient',
        'http_request2',
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        $ip = $request->ip();

        if (Cache::has("blocked_scraper_{$ip}")) {
            return response('', 403);
        }

        $ua = strtolower($request->userAgent() ?? '');

        if (strlen($ua) < 10) {
            return response('', 403);
        }

        foreach ($this->blockedAgents as $agent) {
            if (str_contains($ua, $agent)) {
                return response('', 403);
            }
        }

        return $next($request);
    }
}
