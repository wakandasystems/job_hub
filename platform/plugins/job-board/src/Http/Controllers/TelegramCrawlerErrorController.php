<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TelegramCrawlerErrorController extends BaseController
{
    public function show(Request $request)
    {
        $error       = Cache::get((string) $request->query('cache_key', '')) ?? '';
        $crawlerName = (string) $request->query('crawler_name', 'Crawler');

        $escaped  = htmlspecialchars($error, ENT_QUOTES, 'UTF-8');
        $jsonText = json_encode($error);

        $html = <<<HTML
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Crawler Error</title>
            <style>
                *{box-sizing:border-box;margin:0;padding:0}
                body{font-family:system-ui,sans-serif;background:#f0f4f8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}
                .card{background:#fff;border-radius:16px;padding:32px 24px;max-width:600px;width:100%;box-shadow:0 4px 20px rgba(0,0,0,.08);text-align:center}
                .icon{font-size:48px;margin-bottom:12px}
                h2{font-size:18px;color:#1a1a2e;margin-bottom:6px}
                .sub{color:#666;font-size:13px;margin-bottom:18px}
                textarea{width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:12px;font-size:12px;font-family:monospace;resize:vertical;min-height:140px;color:#444;background:#f8fafc;line-height:1.5;text-align:left}
                .copy-btn{display:inline-block;margin-top:14px;padding:11px 28px;background:#dc2626;color:#fff;border:none;border-radius:10px;font-size:15px;cursor:pointer;transition:background .15s}
                .copy-btn:hover{background:#b91c1c}
                .ok{color:#16a34a;font-weight:600;font-size:13px;margin-top:10px;display:none}
            </style>
        </head>
        <body>
            <div class="card">
                <div class="icon">🚨</div>
                <h2>{$crawlerName}</h2>
                <p class="sub">Tap Copy to copy the full error to clipboard</p>
                <textarea id="err" readonly>{$escaped}</textarea>
                <p class="ok" id="ok">✅ Copied to clipboard!</p>
                <button class="copy-btn" id="copy-btn" onclick="doCopy()">📋 Copy Error</button>
            </div>
            <script>
                const text = {$jsonText};
                function doCopy() {
                    if (!text) return;
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text).then(onCopied).catch(legacyCopy);
                    } else {
                        legacyCopy();
                    }
                }
                function legacyCopy() {
                    const ta = document.getElementById('err');
                    ta.focus(); ta.select(); ta.setSelectionRange(0, 99999);
                    document.execCommand('copy') ? onCopied() : null;
                }
                function onCopied() {
                    document.getElementById('ok').style.display = 'block';
                    const btn = document.getElementById('copy-btn');
                    btn.textContent = '✅ Copied';
                    setTimeout(function () { btn.textContent = '📋 Copy Error'; }, 2500);
                }
            </script>
        </body>
        </html>
        HTML;

        return response($html);
    }
}
