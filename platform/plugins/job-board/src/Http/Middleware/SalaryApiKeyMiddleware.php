<?php

namespace Botble\JobBoard\Http\Middleware;

use Botble\JobBoard\Models\SalaryApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SalaryApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawKey = $request->header('X-API-Key') ?: $request->query('api_key');

        if (! $rawKey || strlen($rawKey) < 12) {
            return response()->json(['error' => 'API key required.'], 401);
        }

        $prefix = substr($rawKey, 0, 12);
        $apiKey = SalaryApiKey::query()->where('key_prefix', $prefix)->first();

        if (! $apiKey || ! $apiKey->verify($rawKey)) {
            return response()->json(['error' => 'Invalid API key.'], 401);
        }

        if (! $apiKey->isValid()) {
            return response()->json(['error' => 'API key is inactive or expired.'], 401);
        }

        if ($apiKey->isOverLimit()) {
            return response()->json([
                'error' => 'Monthly request limit reached.',
                'limit' => $apiKey->requests_per_month,
            ], 429);
        }

        $apiKey->incrementUsage();

        $request->attributes->set('salary_api_key', $apiKey);

        return $next($request);
    }
}
