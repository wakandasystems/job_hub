<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\SocialPublisherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TelegramSocialMessageController extends BaseController
{
    // -------------------------------------------------------------------------
    // Step 1: AI image prompt + platform posts (does NOT delete Telegram message)
    // -------------------------------------------------------------------------

    public function show(Request $request)
    {
        $cacheKey = (string) $request->query('cache_key', '');
        $jobId    = $request->query('job_id');

        $cached = $cacheKey ? Cache::get($cacheKey) : null;

        $aiPrompt      = null;
        $step2Url      = null;
        $platformPosts = [];

        if (is_array($cached)) {
            $aiPrompt      = $cached['ai_prompt'] ?? null;
            $step2Url      = $cached['step2_url'] ?? null;
            $platformPosts = $cached['platform_posts'] ?? [];
        }

        // Fallback: regenerate from job record if cache missed
        if ((! $aiPrompt || empty($platformPosts)) && $jobId) {
            $job = Job::with(['company', 'slugable', 'country', 'currency', 'jobTypes'])->find($jobId);
            if ($job) {
                $publisher = app(SocialPublisherService::class);
                if (! $aiPrompt) {
                    try { $aiPrompt = $publisher->buildAiImagePrompt($job); } catch (\Throwable) {}
                }
                if (empty($platformPosts)) {
                    try { $platformPosts = $publisher->buildPlatformPosts($job); } catch (\Throwable) {}
                }
            }
        }

        if (! $aiPrompt) {
            return response($this->expiredHtml());
        }

        $step2Html = $step2Url
            ? '<a href="' . htmlspecialchars($step2Url, ENT_QUOTES) . '" class="next-btn">Next: Get Telegram Post Text →</a>'
            : '';

        // Build platform cards HTML
        $platforms = [
            'tiktok'    => ['🎵', 'TikTok',           '#010101', $platformPosts['tiktok']    ?? ''],
            'twitter'   => ['𝕏',  'X / Twitter',      '#000000', $platformPosts['twitter']   ?? ''],
            'linkedin'  => ['in', 'LinkedIn',          '#0A66C2', $platformPosts['linkedin']  ?? ''],
            'facebook'  => ['f',  'Facebook',          '#1877F2', $platformPosts['facebook']  ?? ''],
            'whatsapp'  => ['💬', 'WhatsApp Channel',  '#25D366', $platformPosts['whatsapp']  ?? ''],
        ];

        $platformsJson  = json_encode(array_map(fn($p) => $p[3], $platforms), JSON_UNESCAPED_UNICODE);
        $aiPromptJson   = json_encode($aiPrompt, JSON_UNESCAPED_UNICODE);
        $step2UrlJson   = json_encode($step2Url ?? '', JSON_UNESCAPED_UNICODE);

        $platformCardsHtml = '';
        $idx = 0;
        foreach ($platforms as $key => [$icon, $label, $color, $text]) {
            if (! $text) continue;
            $esc   = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            $rows  = min(12, max(5, substr_count($text, "\n") + 2));
            $platformCardsHtml .= <<<CARD
            <div class="platform-card" id="card-{$key}">
                <div class="platform-header" style="--c:{$color}">
                    <span class="platform-icon">{$icon}</span>
                    <span class="platform-name">{$label}</span>
                    <button class="copy-platform-btn" onclick="copyPlatform('{$key}', this)">📋 Copy</button>
                </div>
                <textarea id="ta-{$key}" readonly rows="{$rows}">{$esc}</textarea>
            </div>
            CARD;
            $idx++;
        }

        $escapedPrompt = htmlspecialchars($aiPrompt, ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Post Kit — AI Image + Social Posts</title>
            <style>
                *{box-sizing:border-box;margin:0;padding:0}
                body{font-family:system-ui,sans-serif;background:#f0f4f8;padding:16px 12px 40px}
                .page{max-width:640px;margin:0 auto}
                .step-badge{display:inline-flex;align-items:center;gap:6px;background:#7c3aed;color:#fff;font-size:12px;font-weight:600;padding:5px 14px;border-radius:20px;margin-bottom:14px}
                h1{font-size:20px;color:#1a1a2e;margin-bottom:4px}
                .sub{color:#666;font-size:13px;margin-bottom:20px}
                /* AI card */
                .ai-card{background:#fff;border-radius:14px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-bottom:16px}
                .section-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#7c3aed;margin-bottom:8px}
                textarea{width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;font-size:12.5px;font-family:inherit;resize:vertical;color:#333;background:#f8fafc;line-height:1.55}
                .btn-row{display:flex;gap:10px;margin-top:12px;flex-wrap:wrap}
                .copy-btn{flex:1;padding:10px 18px;background:#7c3aed;color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s;min-width:130px}
                .copy-btn:hover{background:#6d28d9}
                .copy-btn.ok{background:#16a34a}
                .next-btn{flex:1;display:flex;align-items:center;justify-content:center;padding:10px 18px;background:#0f172a;color:#fff;border-radius:9px;font-size:14px;font-weight:600;text-decoration:none;min-width:130px}
                .next-btn:hover{background:#1e293b;color:#fff}
                /* Platform cards */
                .section-title{font-size:15px;font-weight:700;color:#1a1a2e;margin:20px 0 10px}
                .platform-card{background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-bottom:12px}
                .platform-header{display:flex;align-items:center;gap:10px;padding:10px 14px;background:color-mix(in srgb, var(--c) 10%, #fff)}
                .platform-icon{width:30px;height:30px;background:var(--c);color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:900;flex-shrink:0;text-align:center;line-height:30px}
                .platform-name{font-weight:700;font-size:14px;flex-grow:1;color:#1a1a2e}
                .copy-platform-btn{padding:6px 14px;background:var(--c);color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;transition:opacity .15s;white-space:nowrap}
                .copy-platform-btn:hover{opacity:.85}
                .copy-platform-btn.ok{background:#16a34a !important}
                .platform-card textarea{border:none;border-radius:0;background:#fff;padding:12px 14px;font-size:12.5px;border-top:1px solid #f0f4f8}
                .tip{margin-top:6px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:9px 13px;font-size:12px;color:#92400e}
                /* Dismiss */
                .dismiss-bar{margin-top:28px;padding:18px;background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.07);text-align:center}
                .dismiss-bar p{font-size:13px;color:#666;margin-bottom:12px}
                .dismiss-btn{width:100%;padding:13px;background:#dc2626;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;transition:background .15s}
                .dismiss-btn:hover{background:#b91c1c}
                .dismiss-btn.done{background:#16a34a;cursor:default}
                .close-tip{margin-top:10px;font-size:12px;color:#999;display:none}
            </style>
        </head>
        <body>
            <div class="page">
                <div class="step-badge">✨ Step 1 of 2 — Post Kit</div>
                <h1>AI Image Prompt + Social Posts</h1>
                <p class="sub">Everything you need. Copy each section and paste where needed.</p>

                <!-- AI Image Prompt -->
                <div class="ai-card">
                    <div class="section-label">🎨 AI Image Prompt (ChatGPT / DALL·E / Midjourney)</div>
                    <textarea id="ai-prompt" readonly rows="9">{$escapedPrompt}</textarea>
                    <div class="tip">💡 Attach the Wakanda Jobs logo to ChatGPT for the color palette, then paste this prompt.</div>
                    <div class="btn-row">
                        <button class="copy-btn" id="ai-copy-btn" onclick="copyAi()">📋 Copy AI Prompt</button>
                        {$step2Html}
                    </div>
                </div>

                <!-- Platform Posts -->
                <div class="section-title">📲 Ready-to-Post Social Content</div>
                {$platformCardsHtml}

                <!-- Dismiss -->
                <div class="dismiss-bar">
                    <p>Done posting? Remove the Telegram notification and close this tab.</p>
                    <button class="dismiss-btn" id="dismiss-btn" onclick="dismiss()">🗑 Dismiss from Telegram &amp; Close</button>
                    <div class="close-tip" id="close-tip">✅ Telegram message deleted — you can close this tab.</div>
                </div>
            </div>

            <script>
                const posts = {$platformsJson};
                const aiPromptText = {$aiPromptJson};
                const step2Url = {$step2UrlJson};

                function dismiss() {
                    const btn = document.getElementById('dismiss-btn');
                    const tip = document.getElementById('close-tip');
                    btn.disabled = true;
                    btn.textContent = '⏳ Dismissing…';
                    if (!step2Url) {
                        btn.textContent = '✅ Done';
                        btn.classList.add('done');
                        tip.style.display = 'block';
                        window.close();
                        return;
                    }
                    fetch(step2Url, {credentials: 'same-origin'})
                        .then(() => {
                            btn.textContent = '✅ Dismissed';
                            btn.classList.add('done');
                            tip.style.display = 'block';
                            setTimeout(() => window.close(), 600);
                        })
                        .catch(() => {
                            btn.textContent = '✅ Done';
                            btn.classList.add('done');
                            tip.style.display = 'block';
                            window.close();
                        });
                }

                function copyAi() {
                    const btn = document.getElementById('ai-copy-btn');
                    doCopy(aiPromptText, btn, '📋 Copy AI Prompt');
                }

                function copyPlatform(key, btn) {
                    const ta = document.getElementById('ta-' + key);
                    doCopy(ta.value, btn, '📋 Copy');
                }

                function doCopy(text, btn, resetLabel) {
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text).then(() => showOk(btn, resetLabel)).catch(() => legacyCopy(text, btn, resetLabel));
                    } else {
                        legacyCopy(text, btn, resetLabel);
                    }
                }

                function legacyCopy(text, btn, resetLabel) {
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.focus(); ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    showOk(btn, resetLabel);
                }

                function showOk(btn, resetLabel) {
                    btn.textContent = '✅ Copied!';
                    btn.classList.add('ok');
                    setTimeout(() => { btn.textContent = resetLabel; btn.classList.remove('ok'); }, 2200);
                }
            </script>
        </body>
        </html>
        HTML;

        return response($html);
    }

    // -------------------------------------------------------------------------
    // Step 2: Copy post text & delete the Telegram message
    // -------------------------------------------------------------------------

    public function destroy(Request $request)
    {
        $automationId = $request->query('automation_id');
        $automation   = $automationId ? SocialAutomation::query()->find($automationId) : null;
        $settings     = $automation?->settings ?? [];
        $token        = trim((string) ($settings['bot_token'] ?? setting('telegram_bot_token')));
        $chatId       = (string) $request->query('chat_id');
        $messageId    = (string) $request->query('message_id');
        $cacheKey     = (string) $request->query('cache_key', '');
        $jobId        = $request->query('job_id');

        // Try cache first; support both old string format and new array format
        $cached   = $cacheKey ? Cache::get($cacheKey) : null;
        $postText = is_array($cached) ? ($cached['text'] ?? null) : $cached;

        if (! $postText && $jobId) {
            $job = Job::with(['company', 'slugable', 'country'])->find($jobId);
            if ($job) {
                $postText = app(SocialPublisherService::class)->buildManualSocialPost($job);
                if ($cacheKey) {
                    Cache::put($cacheKey, $postText, now()->addDays(7));
                }
            }
        }

        if (! $postText) {
            return response($this->expiredHtml());
        }

        // Delete the Telegram message now that we're on step 2
        if ($token && $chatId !== '' && $messageId !== '') {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/deleteMessage", [
                'chat_id'    => $chatId,
                'message_id' => $messageId,
            ]);
        }

        $escapedText = htmlspecialchars((string) $postText, ENT_QUOTES, 'UTF-8');
        $jsonText    = json_encode((string) $postText);

        $html = <<<HTML
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Step 2 — Copy Post Text</title>
            <style>
                *{box-sizing:border-box;margin:0;padding:0}
                body{font-family:system-ui,sans-serif;background:#f0f4f8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}
                .card{background:#fff;border-radius:16px;padding:32px 24px;max-width:560px;width:100%;box-shadow:0 4px 20px rgba(0,0,0,.08);text-align:center}
                .step-badge{display:inline-flex;align-items:center;gap:6px;background:#0088cc;color:#fff;font-size:12px;font-weight:600;padding:4px 12px;border-radius:20px;margin-bottom:14px}
                .icon{font-size:48px;margin-bottom:12px}
                h2{font-size:20px;color:#1a1a2e;margin-bottom:8px}
                .sub{color:#666;font-size:14px;margin-bottom:20px}
                textarea{width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:12px;font-size:13px;font-family:inherit;resize:vertical;min-height:110px;color:#444;background:#f8fafc;line-height:1.5;text-align:left}
                .copy-btn{display:inline-block;margin-top:16px;padding:11px 28px;background:#0088cc;color:#fff;border:none;border-radius:10px;font-size:15px;cursor:pointer;transition:background .15s}
                .copy-btn:hover{background:#006da8}
                .copy-btn.copied{background:#16a34a}
            </style>
        </head>
        <body>
            <div class="card">
                <div class="step-badge">✅ Step 2 of 2 — Post Text</div>
                <div class="icon">📝</div>
                <h2>Telegram message removed</h2>
                <p class="sub">Copy this text and paste it on LinkedIn, Facebook, or WhatsApp.</p>
                <textarea id="pt" readonly>{$escapedText}</textarea>
                <br>
                <button class="copy-btn" id="copy-btn" onclick="doCopy()">📋 Copy Text</button>
            </div>
            <script>
                const text = {$jsonText};
                function doCopy() {
                    const btn = document.getElementById('copy-btn');
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text).then(() => showCopied(btn)).catch(() => legacyCopy(btn));
                    } else {
                        legacyCopy(btn);
                    }
                }
                function legacyCopy(btn) {
                    const ta = document.getElementById('pt');
                    ta.focus(); ta.select(); ta.setSelectionRange(0, 99999);
                    document.execCommand('copy');
                    showCopied(btn);
                }
                function showCopied(btn) {
                    btn.textContent = '✅ Copied!';
                    btn.classList.add('copied');
                    setTimeout(() => { btn.textContent = '📋 Copy Text'; btn.classList.remove('copied'); }, 2500);
                }
                window.addEventListener('load', doCopy);
            </script>
        </body>
        </html>
        HTML;

        return response($html);
    }

    private function expiredHtml(): string
    {
        return <<<HTML
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Link Expired</title>
            <style>
                *{box-sizing:border-box;margin:0;padding:0}
                body{font-family:system-ui,sans-serif;background:#f0f4f8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}
                .card{background:#fff;border-radius:16px;padding:32px 24px;max-width:480px;width:100%;box-shadow:0 4px 20px rgba(0,0,0,.08);text-align:center}
                .icon{font-size:52px;margin-bottom:16px}
                h2{font-size:20px;color:#1a1a2e;margin-bottom:10px}
                p{color:#666;font-size:14px;line-height:1.6}
            </style>
        </head>
        <body>
            <div class="card">
                <div class="icon">⏳</div>
                <h2>This link has expired</h2>
                <p>The post text is no longer available — the job may have been deleted or the link has expired.<br><br>Generate a new post from the social automation panel if needed.</p>
            </div>
        </body>
        </html>
        HTML;
    }
}
