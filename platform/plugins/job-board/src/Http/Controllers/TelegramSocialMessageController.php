<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\SocialPublisherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

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

        $aiPrompt       = null;
        $step2Url       = null;
        $platformPosts  = [];
        $companyLogoUrl = null;
        $companyName    = null;

        $storyboardPrompt = null;
        $geminiPrompt     = null;

        $jobName = null;

        if (is_array($cached)) {
            $aiPrompt         = $cached['ai_prompt'] ?? null;
            $storyboardPrompt = $cached['storyboard_prompt'] ?? null;
            $geminiPrompt     = $cached['gemini_prompt'] ?? null;
            $step2Url         = $cached['step2_url'] ?? null;
            $platformPosts    = $cached['platform_posts'] ?? [];
            $companyLogoUrl   = $cached['company_logo_url'] ?? null;
            $companyName      = $cached['company_name'] ?? null;
            $jobName          = $cached['job_name'] ?? null;
        }

        // Fallback: regenerate any missing fields from the job record
        $needsRegeneration = (! $aiPrompt || empty($platformPosts) || ! $storyboardPrompt || ! $geminiPrompt);
        if ($needsRegeneration && $jobId) {
            $job = Job::with(['company', 'slugable', 'country', 'currency', 'jobTypes'])->find($jobId);
            if ($job) {
                $publisher = app(SocialPublisherService::class);
                if (! $aiPrompt) {
                    try { $aiPrompt = $publisher->buildAiImagePrompt($job); } catch (\Throwable) {}
                }
                if (! $storyboardPrompt) {
                    try { $storyboardPrompt = $publisher->buildStoryboardPrompt($job); } catch (\Throwable) {}
                }
                if (! $geminiPrompt) {
                    try { $geminiPrompt = $publisher->buildGeminiVideoPrompt($job); } catch (\Throwable) {}
                }
                if (empty($platformPosts)) {
                    try { $platformPosts = $publisher->buildPlatformPosts($job); } catch (\Throwable) {}
                }
            }
        }

        // Always fetch company logo live so it reflects the latest upload, not the cached value
        if ($jobId && ! $companyLogoUrl) {
            try {
                $logoJob = Job::with(['company'])->find($jobId);
                if ($logoJob && $logoJob->company && ! empty($logoJob->company->logo)) {
                    $companyLogoUrl = \Botble\Media\Facades\RvMedia::getImageUrl($logoJob->company->logo);
                    $companyName    = $logoJob->company->name;
                }
            } catch (\Throwable) {}
        }

        if (! $aiPrompt) {
            return response($this->expiredHtml());
        }

        // Regenerate step2Url if cache was cleared — all required params are on the Step 1 signed URL.
        if (! $step2Url) {
            $chatId       = (string) $request->query('chat_id', '');
            $messageId    = (string) $request->query('message_id', '');
            $automationId = $request->query('automation_id');

            if ($chatId !== '' && $messageId !== '') {
                $step2Params = [
                    'chat_id'    => $chatId,
                    'message_id' => $messageId,
                    'cache_key'  => $cacheKey,
                    'job_id'     => $jobId,
                ];
                if ($automationId !== null) {
                    $step2Params['automation_id'] = $automationId;
                }
                $step2Url = URL::temporarySignedRoute(
                    'public.telegram-social-delete',
                    now()->addDays(7),
                    $step2Params,
                );
            }
        }

        // Build platform cards HTML
        $platforms = [
            'tiktok'    => ['🎵', 'TikTok',           '#010101', $platformPosts['tiktok']    ?? ''],
            'twitter'   => ['𝕏',  'X / Twitter',      '#000000', $platformPosts['twitter']   ?? ''],
            'linkedin'  => ['in', 'LinkedIn',          '#0A66C2', $platformPosts['linkedin']  ?? ''],
            'facebook'  => ['f',  'Facebook',          '#1877F2', $platformPosts['facebook']  ?? ''],
            'whatsapp'  => ['💬', 'WhatsApp Channel',  '#25D366', $platformPosts['whatsapp']  ?? ''],
        ];

        $storyboardSafe = mb_convert_encoding((string) $storyboardPrompt, 'UTF-8', 'UTF-8');
        $geminiSafe     = mb_convert_encoding((string) $geminiPrompt, 'UTF-8', 'UTF-8');
        $aiPromptJson   = json_encode($aiPrompt, JSON_UNESCAPED_UNICODE);
        $storyboardJson = json_encode($storyboardSafe, JSON_UNESCAPED_UNICODE);
        $geminiJson     = json_encode($geminiSafe, JSON_UNESCAPED_UNICODE);
        $step2UrlJson   = json_encode($step2Url ?? '', JSON_UNESCAPED_UNICODE);

        $escapedPrompt     = htmlspecialchars($aiPrompt, ENT_QUOTES, 'UTF-8');
        $escapedStoryboard = htmlspecialchars($storyboardSafe, ENT_QUOTES, 'UTF-8');
        $escapedGemini     = htmlspecialchars($geminiSafe, ENT_QUOTES, 'UTF-8');

        $escapedJobName  = htmlspecialchars((string) ($jobName ?? 'New Job'), ENT_QUOTES, 'UTF-8');
        $escapedCompany  = htmlspecialchars((string) ($companyName ?? ''), ENT_QUOTES, 'UTF-8');
        $heroSubLine     = $escapedCompany ? "{$escapedJobName} &middot; {$escapedCompany}" : $escapedJobName;

        // Platform cards (Social tab)
        $platformCardsHtml = '';
        foreach ($platforms as $key => [$icon, $label, $color, $text]) {
            if (! $text) continue;
            $esc      = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            $rows     = min(14, max(5, substr_count($text, "\n") + 2));
            $charLen  = mb_strlen($text);
            $platformCardsHtml .= <<<CARD
            <div class="platform-card" id="card-{$key}">
                <div class="platform-header" style="--c:{$color}">
                    <span class="platform-icon">{$icon}</span>
                    <span class="platform-name">{$label}</span>
                    <span class="char-count">{$charLen} chars</span>
                    <button class="copy-platform-btn" onclick="copyPlatform('{$key}', this)">📋 Copy</button>
                </div>
                <textarea id="ta-{$key}" readonly rows="{$rows}">{$esc}</textarea>
            </div>
            CARD;
        }

        // Attachment tip (Image tab)
        $attachmentTipHtml = '<div class="tip-amber">💡 <strong>Before pasting into ChatGPT:</strong><br>1. Attach the <strong>Wakanda Jobs logo</strong> (colour palette reference).';
        if ($companyLogoUrl) {
            $escapedLogoUrl  = htmlspecialchars($companyLogoUrl, ENT_QUOTES, 'UTF-8');
            $escapedCompName = htmlspecialchars((string) $companyName, ENT_QUOTES, 'UTF-8');
            $attachmentTipHtml .= '<br>2. Also attach the <strong>' . $escapedCompName . ' logo</strong>: ';
            $attachmentTipHtml .= '<a href="' . $escapedLogoUrl . '" target="_blank" rel="noopener" class="logo-link">Open logo →</a>';
            $attachmentTipHtml .= ' (download &amp; attach so ChatGPT uses the real one, not a guess).';
        }
        $attachmentTipHtml .= '</div>';

        // Next button (bottom bar)
        $nextBtnHtml = $step2Url
            ? '<a href="' . htmlspecialchars($step2Url, ENT_QUOTES) . '" class="bb-next">Next: Get Post Text →</a>'
            : '<span class="bb-next bb-next--disabled">Next: Get Post Text</span>';

        $html = <<<HTML
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Post Kit — Wakanda Jobs</title>
            <style>
                *{box-sizing:border-box;margin:0;padding:0}
                :root{
                    --p:#7c3aed;--pd:#5b21b6;--pl:#a78bfa;
                    --dark:#0f172a;--dark2:#1e293b;--dark3:#334155;
                    --slate:#f1f5f9;--muted:#64748b;
                }
                body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;background:var(--slate);min-height:100vh;padding-bottom:90px}
                .page{max-width:640px;margin:0 auto}

                /* ── Hero ── */
                .hero{background:linear-gradient(135deg,#3b0764 0%,#6d28d9 55%,#7c3aed 100%);padding:22px 16px 0;position:relative;overflow:hidden}
                .hero::before{content:'';position:absolute;top:-60px;right:-60px;width:220px;height:220px;background:radial-gradient(circle,rgba(167,139,250,.25) 0%,transparent 70%);pointer-events:none}
                .hero::after{content:'';position:absolute;bottom:0;left:0;right:0;height:22px;background:var(--slate);border-radius:22px 22px 0 0}
                .hero-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.18);color:#fff;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;border:1px solid rgba(255,255,255,.3);backdrop-filter:blur(6px);margin-bottom:10px}
                .hero h1{color:#fff;font-size:21px;font-weight:800;margin-bottom:3px;position:relative}
                .hero-sub{color:rgba(255,255,255,.72);font-size:12.5px;margin-bottom:22px;position:relative}

                /* ── Tab bar ── */
                .tab-bar{position:sticky;top:0;z-index:100;background:var(--slate);padding:10px 16px 0;border-bottom:2px solid #e2e8f0}
                .tab-nav{display:flex;gap:3px;background:#e2e8f0;border-radius:12px;padding:4px}
                .tab-btn{flex:1;padding:8px 6px;background:none;border:none;border-radius:9px;font-size:13px;font-weight:600;color:var(--muted);cursor:pointer;transition:all .18s;white-space:nowrap}
                .tab-btn.active{background:#fff;color:var(--p);box-shadow:0 1px 5px rgba(0,0,0,.1)}

                /* ── Tab panes ── */
                .tab-pane{display:none;padding:16px 16px 8px}
                .tab-pane.active{display:block}

                /* ── Cards ── */
                .card{background:#fff;border-radius:16px;padding:20px;box-shadow:0 1px 8px rgba(0,0,0,.06);margin-bottom:14px}
                .section-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--p);margin-bottom:10px}

                /* ── Textareas ── */
                textarea{width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:11px 13px;font-size:12.5px;font-family:inherit;resize:vertical;color:#334155;background:#f8fafc;line-height:1.6}
                textarea:focus{outline:none;border-color:var(--p);background:#fff}

                /* ── Buttons ── */
                .btn-row{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap}
                .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 18px;border:none;border-radius:10px;font-size:13.5px;font-weight:700;cursor:pointer;transition:all .15s;text-decoration:none;flex:1}
                .btn-purple{background:var(--p);color:#fff}
                .btn-purple:hover{background:var(--pd)}
                .btn-purple.ok{background:#16a34a}
                .btn-dark{background:var(--dark);color:#fff}
                .btn-dark:hover{background:var(--dark2);color:#fff}

                /* ── Tips ── */
                .tip-amber{margin-top:12px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:11px 14px;font-size:12px;color:#92400e;line-height:1.6}
                .tip-blue{background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 14px;font-size:12px;color:#1e40af;line-height:1.6;margin-bottom:14px}
                .logo-link{color:var(--p);font-weight:600;word-break:break-all}

                /* ── Platform cards (Social tab) ── */
                .platform-card{background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 1px 8px rgba(0,0,0,.06);margin-bottom:12px}
                .platform-header{display:flex;align-items:center;gap:10px;padding:11px 14px;background:color-mix(in srgb,var(--c) 8%,#fff);border-bottom:1px solid color-mix(in srgb,var(--c) 12%,#fff)}
                .platform-icon{width:32px;height:32px;background:var(--c);color:#fff;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:900;flex-shrink:0}
                .platform-name{font-weight:700;font-size:14px;flex-grow:1;color:#1e293b}
                .char-count{font-size:11px;color:var(--muted);margin-right:2px;white-space:nowrap}
                .copy-platform-btn{padding:7px 15px;background:var(--c);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;transition:opacity .15s;white-space:nowrap}
                .copy-platform-btn:hover{opacity:.85}
                .copy-platform-btn.ok{background:#16a34a !important}
                .platform-card textarea{border:none;border-radius:0;background:#fff;padding:12px 14px;font-size:12.5px}

                /* ── Video tab ── */
                .video-hero{background:linear-gradient(160deg,#020617 0%,#0d1424 55%,#1a0a2e 100%);border-radius:18px;padding:22px 18px 20px;margin-bottom:14px;position:relative;overflow:hidden}
                .video-hero::before{content:'';position:absolute;top:-50px;right:-50px;width:200px;height:200px;background:radial-gradient(circle,rgba(124,58,237,.25) 0%,transparent 65%);pointer-events:none}
                .video-hero::after{content:'';position:absolute;bottom:-40px;left:-40px;width:160px;height:160px;background:radial-gradient(circle,rgba(245,158,11,.1) 0%,transparent 65%);pointer-events:none}
                .video-hero-eyebrow{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#a78bfa;margin-bottom:6px;position:relative}
                .video-hero-title{font-size:20px;font-weight:800;color:#fff;margin-bottom:4px;position:relative}
                .video-hero-sub{font-size:12px;color:#94a3b8;margin-bottom:18px;position:relative}

                /* Flow diagram */
                .flow{display:flex;align-items:center;gap:0;margin-bottom:18px;overflow-x:auto;padding-bottom:2px;position:relative}
                .flow-node{flex:1;min-width:68px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.14);border-radius:11px;padding:10px 6px;text-align:center}
                .flow-node-icon{font-size:20px;margin-bottom:4px}
                .flow-node-label{font-size:9.5px;color:#cbd5e1;font-weight:700;line-height:1.3;text-transform:uppercase;letter-spacing:.04em}
                .flow-node-sub{font-size:9px;color:#64748b;margin-top:2px}
                .flow-arrow{color:#a78bfa;font-size:16px;padding:0 5px;flex-shrink:0;font-weight:700}

                /* Frame strip */
                .frame-strip{display:flex;gap:6px;margin-bottom:18px;position:relative}
                .frame-card{flex:1;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:9px 4px;text-align:center}
                .frame-num{width:22px;height:22px;background:var(--p);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;margin:0 auto 4px}
                .frame-tag{font-size:8.5px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.04em;line-height:1.2}
                .frame-time{font-size:8px;color:#475569;margin-top:3px}
                .frame-connector{position:absolute;top:50%;left:0;right:0;height:1px;background:rgba(124,58,237,.3);transform:translateY(-50%);z-index:0;pointer-events:none}

                /* Video step cards */
                .vstep{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:17px 16px;margin-bottom:12px;position:relative}
                .vstep-header{display:flex;align-items:center;gap:10px;margin-bottom:8px}
                .vstep-num{width:28px;height:28px;background:var(--p);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#fff;flex-shrink:0}
                .vstep h4{font-size:13.5px;font-weight:700;color:#f1f5f9;line-height:1.3}
                .vstep-where{font-size:11px;font-weight:600;color:#a78bfa;margin-left:auto;white-space:nowrap}
                .vstep p{font-size:11.5px;color:#94a3b8;margin-bottom:10px;line-height:1.5}
                .vstep textarea{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.15);color:#e2e8f0}
                .vstep textarea:focus{background:rgba(255,255,255,.12);border-color:#a78bfa}
                .vstep .btn-row .btn-purple{background:rgba(124,58,237,.75);border:1px solid rgba(167,139,250,.4)}
                .vstep .btn-row .btn-purple:hover{background:var(--p)}
                .vstep .btn-row .btn-purple.ok{background:#16a34a}

                /* Gemini Omni step — special treatment */
                .vstep-gemini{background:linear-gradient(135deg,rgba(66,133,244,.12) 0%,rgba(52,168,83,.08) 50%,rgba(251,188,4,.08) 100%);border:1px solid rgba(66,133,244,.3)}
                .gemini-badge-wrap{margin-bottom:10px}
                .gemini-badge{display:inline-block;font-size:11px;font-weight:800;letter-spacing:.02em;padding:3px 11px;border-radius:20px;border:1px solid rgba(255,255,255,.2);background:rgba(0,0,0,.3)}
                .g-b{color:#4285F4}.g-e{color:#EA4335}.g-y{color:#FBBC04}.g-g{color:#34A853}
                .gemini-model-tip{font-size:11px;color:#93c5fd;background:rgba(37,99,235,.2);border:1px solid rgba(59,130,246,.3);border-radius:8px;padding:8px 12px;margin-bottom:10px;line-height:1.5}

                /* ── Sticky bottom bar ── */
                .bottom-bar{position:fixed;bottom:0;left:0;right:0;background:rgba(241,245,249,.96);backdrop-filter:blur(14px);border-top:1px solid #e2e8f0;padding:10px 16px 14px;z-index:200}
                .bottom-bar-inner{display:flex;gap:8px;max-width:640px;margin:0 auto;align-items:center}
                .bb-dismiss{padding:12px 16px;background:#dc2626;color:#fff;border:none;border-radius:11px;font-size:13.5px;font-weight:700;cursor:pointer;transition:background .15s;white-space:nowrap}
                .bb-dismiss:hover{background:#b91c1c}
                .bb-dismiss.done{background:#16a34a;cursor:default}
                .bb-next{flex:1;display:flex;align-items:center;justify-content:center;padding:12px 16px;background:var(--dark);color:#fff;border-radius:11px;font-size:13.5px;font-weight:700;text-decoration:none;transition:background .15s}
                .bb-next:hover{background:var(--dark2);color:#fff}
                .bb-next--disabled{opacity:.4;cursor:default;pointer-events:none}
                .bb-close-tip{font-size:11.5px;color:#666;text-align:center;margin-top:6px;max-width:640px;margin-left:auto;margin-right:auto;display:none}
            </style>
        </head>
        <body>

        <!-- ── Hero ── -->
        <div class="hero">
            <div class="page">
                <div class="hero-badge">✨ Step 1 of 2 — Post Kit</div>
                <h1>Content Creator</h1>
                <p class="hero-sub">{$heroSubLine}</p>
            </div>
        </div>

        <!-- ── Tab bar ── -->
        <div class="tab-bar">
            <div class="page">
                <div class="tab-nav">
                    <button class="tab-btn active" onclick="switchTab('image',this)">🎨 Image</button>
                    <button class="tab-btn" onclick="switchTab('video',this)">🎬 Video</button>
                    <button class="tab-btn" onclick="switchTab('social',this)">📲 Social</button>
                </div>
            </div>
        </div>

        <div class="page">

            <!-- ══════════════ IMAGE TAB ══════════════ -->
            <div id="tab-image" class="tab-pane active">
                <div class="card">
                    <div class="section-label">🎨 AI Image Prompt</div>
                    <p style="font-size:12px;color:#64748b;margin-bottom:10px">Paste into <strong>ChatGPT / DALL·E 3 / Midjourney</strong> to generate a 9:16 portrait job ad.</p>
                    <textarea id="ai-prompt" readonly rows="10">{$escapedPrompt}</textarea>
                    {$attachmentTipHtml}
                    <div class="btn-row">
                        <button class="btn btn-purple" id="ai-copy-btn" onclick="copyAi()">📋 Copy Image Prompt</button>
                    </div>
                </div>
            </div>

            <!-- ══════════════ VIDEO TAB ══════════════ -->
            <div id="tab-video" class="tab-pane">
                <div class="video-hero">

                    <div class="video-hero-eyebrow">🎬 10-Second Video Ad</div>
                    <div class="video-hero-title">TikTok · Reels · WhatsApp Status</div>
                    <div class="video-hero-sub">Two AI tools. Four frames. One scroll-stopping video.</div>

                    <!-- Workflow arrow -->
                    <div class="flow">
                        <div class="flow-node">
                            <div class="flow-node-icon">💬</div>
                            <div class="flow-node-label">ChatGPT</div>
                            <div class="flow-node-sub">4 frames</div>
                        </div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-node">
                            <div class="flow-node-icon">🖼</div>
                            <div class="flow-node-label">Download</div>
                            <div class="flow-node-sub">all 4 JPEGs</div>
                        </div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-node" style="background:rgba(66,133,244,.15);border-color:rgba(66,133,244,.3)">
                            <div class="flow-node-icon">✨</div>
                            <div class="flow-node-label">Gemini</div>
                            <div class="flow-node-sub">10s video</div>
                        </div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-node" style="background:rgba(52,168,83,.12);border-color:rgba(52,168,83,.3)">
                            <div class="flow-node-icon">🎥</div>
                            <div class="flow-node-label">MP4 done</div>
                            <div class="flow-node-sub">post it!</div>
                        </div>
                    </div>

                    <!-- Frame timeline -->
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:7px">Video timeline</div>
                    <div class="frame-strip">
                        <div class="frame-card">
                            <div class="frame-num">1</div>
                            <div class="frame-tag">Hook</div>
                            <div class="frame-time">0 – 2 s</div>
                        </div>
                        <div class="frame-card">
                            <div class="frame-num">2</div>
                            <div class="frame-tag">Oppty</div>
                            <div class="frame-time">2 – 5 s</div>
                        </div>
                        <div class="frame-card">
                            <div class="frame-num">3</div>
                            <div class="frame-tag">Details</div>
                            <div class="frame-time">5 – 8 s</div>
                        </div>
                        <div class="frame-card" style="background:rgba(124,58,237,.15);border-color:rgba(124,58,237,.35)">
                            <div class="frame-num">4</div>
                            <div class="frame-tag">CTA 🚀</div>
                            <div class="frame-time">8 – 10 s</div>
                        </div>
                    </div>

                    <!-- Step 1: Storyboard -->
                    <div class="vstep">
                        <div class="vstep-header">
                            <div class="vstep-num">1</div>
                            <h4>Storyboard — 4 portrait frames</h4>
                            <span class="vstep-where">→ ChatGPT</span>
                        </div>
                        <p>Paste this into ChatGPT (attach Wakanda Jobs logo). It generates 4 sequential 1080×1920 images — one per scene. Download all four.</p>
                        <textarea id="storyboard-ta" readonly rows="8">{$escapedStoryboard}</textarea>
                        <div class="btn-row" style="margin-top:10px">
                            <button class="btn btn-purple" onclick="copyField('storyboard-ta',this,'📋 Copy Storyboard')">📋 Copy Storyboard</button>
                        </div>
                    </div>

                    <!-- Step 2: Gemini Omni -->
                    <div class="vstep vstep-gemini">
                        <div class="gemini-badge-wrap">
                            <span class="gemini-badge"><span class="g-b">G</span><span class="g-e">e</span><span class="g-y">m</span><span class="g-g">i</span><span class="g-b">n</span><span class="g-e">i</span> <span style="color:#fff;opacity:.8">Omni</span> — Video Generation</span>
                        </div>
                        <div class="vstep-header">
                            <div class="vstep-num" style="background:linear-gradient(135deg,#4285F4,#34A853)">2</div>
                            <h4>Animate 4 frames → 10-second video</h4>
                            <span class="vstep-where" style="color:#4ade80">→ Gemini</span>
                        </div>
                        <div class="gemini-model-tip">
                            📌 <strong>Use:</strong> <strong>Gemini 2.0 Flash</strong> (Experimental) with video generation, or <strong>Google Veo 2</strong> via Gemini Advanced.<br>
                            📎 <strong>Attach in order:</strong> Frame 1 → Frame 2 → Frame 3 → Frame 4 → Wakanda Jobs logo PNG — then paste the prompt below.
                        </div>
                        <p>Gemini animates the 4 frames into a punchy 10-second MP4 with transitions, text effects, Amapiano/Afrobeats audio, and the Wakanda Jobs logo as a persistent watermark.</p>
                        <textarea id="gemini-ta" readonly rows="9">{$escapedGemini}</textarea>
                        <div class="btn-row" style="margin-top:10px">
                            <button class="btn btn-purple" onclick="copyField('gemini-ta',this,'📋 Copy Gemini Script')">📋 Copy Gemini Script</button>
                        </div>
                    </div>

                </div><!-- /video-hero -->
            </div>

            <!-- ══════════════ SOCIAL TAB ══════════════ -->
            <div id="tab-social" class="tab-pane">
                <div class="tip-blue" style="margin-bottom:14px">
                    📋 Each post is ready to paste — just copy, open the platform, and post. Character counts shown for each.
                </div>
                {$platformCardsHtml}
            </div>

        </div><!-- /page -->

        <!-- ── Sticky bottom bar ── -->
        <div class="bottom-bar">
            <div class="bottom-bar-inner">
                <button class="bb-dismiss" id="dismiss-btn" onclick="dismiss()">🗑 Dismiss</button>
                {$nextBtnHtml}
            </div>
            <div class="bb-close-tip" id="close-tip"></div>
        </div>

        <script>
            const aiPromptText   = {$aiPromptJson};
            const storyboardText = {$storyboardJson};
            const geminiText     = {$geminiJson};
            const step2Url       = {$step2UrlJson};

            function switchTab(name, btn) {
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.getElementById('tab-' + name).classList.add('active');
                btn.classList.add('active');
            }

            function copyField(id, btn, resetLabel) {
                const ta = document.getElementById(id);
                doCopy(ta.value, btn, resetLabel);
            }

            function copyAi() {
                doCopy(aiPromptText, document.getElementById('ai-copy-btn'), '📋 Copy Image Prompt');
            }

            function copyPlatform(key, btn) {
                const ta = document.getElementById('ta-' + key);
                doCopy(ta.value, btn, '📋 Copy');
            }

            function dismiss() {
                const btn = document.getElementById('dismiss-btn');
                const tip = document.getElementById('close-tip');
                btn.disabled = true;
                btn.textContent = '⏳ Dismissing…';
                if (!step2Url) {
                    btn.classList.add('done');
                    btn.textContent = '✅ Done';
                    tip.textContent = 'You can close this tab.';
                    tip.style.display = 'block';
                    setTimeout(() => window.close(), 600);
                    return;
                }
                fetch(step2Url, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(async r => {
                    const d = await r.json().catch(() => ({}));
                    if (!r.ok || d.ok === false) throw new Error(d.message || 'Telegram could not remove the message.');
                    btn.classList.add('done');
                    btn.textContent = '✅ Done';
                    tip.textContent = '✅ Telegram message deleted — you can close this tab.';
                    tip.style.display = 'block';
                    setTimeout(() => window.close(), 600);
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.textContent = '🗑 Retry Dismiss';
                    tip.textContent = err.message || 'Could not dismiss. Please try again.';
                    tip.style.color = '#b91c1c';
                    tip.style.display = 'block';
                });
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
                ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
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

        $deleted = false;
        $deleteMessage = null;

        if ($token && $chatId !== '' && $messageId !== '') {
            try {
                $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/deleteMessage", [
                    'chat_id'    => $chatId,
                    'message_id' => $messageId,
                ]);

                $payload = $response->json();
                $deleted = $response->successful() && (bool) data_get($payload, 'ok');
                $deleteMessage = data_get($payload, 'description') ?: ($deleted ? null : 'Telegram rejected the delete request.');
            } catch (\Throwable $exception) {
                $deleteMessage = $exception->getMessage();
            }
        } else {
            $deleteMessage = 'Missing Telegram token, chat ID, or message ID.';
        }

        if ($deleted) {
            DB::table('telegram_message_log')
                ->where('chat_id', $chatId)
                ->where('message_id', (string) $messageId)
                ->delete();
        } else {
            Log::warning('Telegram message delete failed from social dismiss page.', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'automation_id' => $automationId,
                'message' => $deleteMessage,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => $deleted,
                'message' => $deleted ? 'Telegram message deleted.' : ($deleteMessage ?: 'Telegram could not remove the message.'),
            ], $deleted ? 200 : 422);
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
