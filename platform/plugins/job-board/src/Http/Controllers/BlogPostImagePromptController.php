<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Blog\Models\Post;

class BlogPostImagePromptController extends BaseController
{
    public function show(int $id): \Illuminate\Http\Response
    {
        $post = Post::with(['categories'])->findOrFail($id);

        $prompt    = $this->buildPrompt($post);
        $escaped   = htmlspecialchars($prompt, ENT_QUOTES, 'UTF-8');
        $title     = htmlspecialchars($post->name, ENT_QUOTES, 'UTF-8');
        $promptJson = json_encode($prompt, JSON_UNESCAPED_UNICODE);
        $editUrl   = route('posts.edit', $post->getKey());

        $html = <<<HTML
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Blog Image Prompt — {$title}</title>
            <style>
                *{box-sizing:border-box;margin:0;padding:0}
                body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;background:#f1f5f9;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px}
                .card{background:#fff;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,.1);width:100%;max-width:680px;overflow:hidden}
                .header{background:linear-gradient(135deg,#3b0764 0%,#6d28d9 60%,#7c3aed 100%);padding:22px 24px 18px}
                .header-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.18);color:#fff;font-size:11px;font-weight:700;padding:3px 11px;border-radius:20px;border:1px solid rgba(255,255,255,.25);margin-bottom:8px}
                .header h1{color:#fff;font-size:13px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;opacity:.7;margin-bottom:4px}
                .header-title{color:#fff;font-size:18px;font-weight:800;line-height:1.3}
                .body{padding:24px}
                .label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#7c3aed;margin-bottom:10px}
                .tip{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:11px 14px;font-size:12px;color:#92400e;line-height:1.55;margin-bottom:16px}
                .tip strong{font-weight:700}
                .logo-link{color:#7c3aed;font-weight:600}
                textarea{width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:13px 14px;font-size:12.5px;font-family:inherit;resize:vertical;color:#334155;background:#f8fafc;line-height:1.65;min-height:260px}
                textarea:focus{outline:none;border-color:#7c3aed;background:#fff}
                .btn-row{display:flex;gap:10px;margin-top:14px;flex-wrap:wrap}
                .btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:11px 20px;border:none;border-radius:10px;font-size:13.5px;font-weight:700;cursor:pointer;transition:all .15s;text-decoration:none;flex:1}
                .btn-purple{background:#7c3aed;color:#fff}
                .btn-purple:hover{background:#6d28d9}
                .btn-purple.ok{background:#16a34a}
                .btn-back{background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0}
                .btn-back:hover{background:#e2e8f0;color:#1e293b}
            </style>
        </head>
        <body>
            <div class="card">
                <div class="header">
                    <div class="header-badge">✨ AI Image Prompt</div>
                    <h1>Blog Cover Image</h1>
                    <div class="header-title">{$title}</div>
                </div>
                <div class="body">
                    <div class="label">Image Generation Prompt</div>
                    <div class="tip">
                        💡 <strong>Paste this into ChatGPT (GPT-4o), DALL·E 3, or Midjourney.</strong><br>
                        Attach the <a href="https://www.wakandajobs.com/storage/gemini-generated-image-s1e9dgs1e9dgs1e9.png" target="_blank" class="logo-link">Wakanda Jobs logo →</a> before pasting if you want it watermarked. Generated image should be <strong>1200 × 630 px</strong> (standard blog Open Graph size).
                    </div>
                    <textarea id="prompt-ta" readonly>{$escaped}</textarea>
                    <div class="btn-row">
                        <button class="btn btn-purple" id="copy-btn" onclick="copyPrompt(this)">📋 Copy Prompt</button>
                        <a class="btn btn-back" href="{$editUrl}">← Back to Post</a>
                    </div>
                </div>
            </div>
            <script>
                const promptText = {$promptJson};
                function copyPrompt(btn) {
                    navigator.clipboard.writeText(promptText).then(function() {
                        btn.textContent = '✅ Copied!';
                        btn.classList.add('ok');
                        setTimeout(function() {
                            btn.textContent = '📋 Copy Prompt';
                            btn.classList.remove('ok');
                        }, 2500);
                    }).catch(function() {
                        document.getElementById('prompt-ta').select();
                        document.execCommand('copy');
                        btn.textContent = '✅ Copied!';
                        btn.classList.add('ok');
                        setTimeout(function() {
                            btn.textContent = '📋 Copy Prompt';
                            btn.classList.remove('ok');
                        }, 2500);
                    });
                }
            </script>
        </body>
        </html>
        HTML;

        return response($html);
    }

    public function buildPromptPublic(Post $post): string
    {
        return $this->buildPrompt($post);
    }

    private function buildPrompt(Post $post): string
    {
        $title       = $post->name;
        $description = $post->description ?? '';
        $category    = $post->categories->first()?->name ?? 'Career & Jobs';

        // Strip HTML and trim content snippet for context
        $contentSnippet = strip_tags((string) $post->content);
        $contentSnippet = preg_replace('/\s+/', ' ', $contentSnippet);
        $contentSnippet = mb_substr(trim($contentSnippet), 0, 300);

        return <<<PROMPT
        A professional, photorealistic editorial photograph for a blog cover image on Wakanda Jobs — Africa's leading job board.

        Article title: "{$title}"
        Category: {$category}
        Context: {$description}

        Visual concept: Create an image that authentically represents the article's theme in an African professional context. Show real working environments, modern offices, or scenes of career growth — natural skin tones, contemporary but realistic African settings, no stock-photo clichés. The image should feel like it belongs in a quality career publication.

        Format: 1200 × 630 px (landscape, standard blog Open Graph ratio).

        Composition: Keep the centre and lower-right area visually calm and uncluttered — text overlay will be placed there. Place the main visual interest in the upper-left or upper portion of the frame. Ensure there is enough breathing room for a title and subtitle.

        Style: warm natural lighting, shallow depth of field, editorial/documentary photography look, vibrant but not oversaturated colours, wide and spacious framing.

        Strictly avoid: any text, letters, numbers, logos, watermarks, or UI elements baked into the image; cartoon/illustrated/3D-render styles; cluttered or overly busy backgrounds; people staring directly into the camera.
        PROMPT;
    }
}
