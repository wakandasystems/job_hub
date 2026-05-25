<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\SocialAutomation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TelegramSocialMessageController extends BaseController
{
    public function destroy(Request $request)
    {
        $automationId = $request->query('automation_id');
        $automation   = $automationId ? SocialAutomation::query()->find($automationId) : null;
        $settings     = $automation?->settings ?? [];
        $token        = trim((string) ($settings['bot_token'] ?? setting('telegram_bot_token')));
        $chatId     = (string) $request->query('chat_id');
        $messageId  = (string) $request->query('message_id');
        $cacheKey   = (string) $request->query('cache_key', '');

        // Read cached post text (kept for 7 days so user can revisit the URL if copy fails).
        $postText = $cacheKey ? Cache::get($cacheKey) : null;

        if ($token && $chatId !== '' && $messageId !== '') {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/deleteMessage", [
                'chat_id'    => $chatId,
                'message_id' => $messageId,
            ]);
        }

        $escapedText = htmlspecialchars((string) ($postText ?? ''), ENT_QUOTES, 'UTF-8');
        $jsonText    = json_encode((string) ($postText ?? ''));

        $html = <<<HTML
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Copied</title>
            <style>
                *{box-sizing:border-box;margin:0;padding:0}
                body{font-family:system-ui,sans-serif;background:#f0f4f8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}
                .card{background:#fff;border-radius:16px;padding:32px 24px;max-width:560px;width:100%;box-shadow:0 4px 20px rgba(0,0,0,.08);text-align:center}
                .icon{font-size:52px;margin-bottom:16px}
                h2{font-size:20px;color:#1a1a2e;margin-bottom:8px}
                .sub{color:#666;font-size:14px;margin-bottom:20px}
                textarea{width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:12px;font-size:13px;font-family:inherit;resize:vertical;min-height:110px;color:#444;background:#f8fafc;line-height:1.5}
                .copy-btn{display:inline-block;margin-top:16px;padding:11px 28px;background:#0088cc;color:#fff;border:none;border-radius:10px;font-size:15px;cursor:pointer;transition:background .15s}
                .copy-btn:hover{background:#006da8}
                .ok{color:#16a34a;font-weight:600;font-size:14px;margin-top:12px;display:none}
            </style>
        </head>
        <body>
            <div class="card">
                <div class="icon">✅</div>
                <h2>Telegram message removed</h2>
                <p class="sub" id="status">Copying text to clipboard…</p>
                <textarea id="pt" readonly>{$escapedText}</textarea>
                <p class="ok" id="ok">Copied! Paste into LinkedIn or Facebook.</p>
                <button class="copy-btn" id="copy-btn" onclick="doCopy()">Copy text</button>
            </div>
            <script>
                const text = {$jsonText};
                function doCopy() {
                    if (!text) return;
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text).then(onCopied).catch(onFailed);
                    } else {
                        legacyCopy();
                    }
                }
                function legacyCopy() {
                    const ta = document.getElementById('pt');
                    ta.focus();
                    ta.select();
                    ta.setSelectionRange(0, 99999);
                    const ok = document.execCommand('copy');
                    ok ? onCopied() : onFailed();
                }
                function onCopied() {
                    document.getElementById('status').textContent = 'Copied! Paste into LinkedIn or Facebook.';
                    document.getElementById('ok').style.display = 'block';
                    const btn = document.getElementById('copy-btn');
                    if (btn) { btn.textContent = '✅ Copied'; setTimeout(function () { btn.textContent = 'Copy text'; }, 2500); }
                }
                function onFailed() {
                    // Auto-copy blocked (no user gesture) — select the text and prompt the user
                    const ta = document.getElementById('pt');
                    ta.focus();
                    ta.select();
                    ta.setSelectionRange(0, 99999);
                    document.getElementById('status').textContent = 'Tap “Copy” below or long-press the text to copy.';
                }
                window.addEventListener('load', function () {
                    if (text) { doCopy(); }
                });
            </script>
        </body>
        </html>
        HTML;

        return response($html);
    }
}
