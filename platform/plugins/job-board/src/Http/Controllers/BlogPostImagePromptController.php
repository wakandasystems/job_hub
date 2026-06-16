<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Blog\Models\Post;

class BlogPostImagePromptController extends BaseController
{
    public function show(int $id): \Illuminate\Http\Response
    {
        $post = Post::with(['categories'])->findOrFail($id);

        $imagePrompt      = $this->buildImagePrompt($post);
        $coverImagePrompt = $this->buildCoverImagePrompt($post);
        $title            = htmlspecialchars($post->name, ENT_QUOTES, 'UTF-8');
        $escapedImage     = htmlspecialchars($imagePrompt, ENT_QUOTES, 'UTF-8');
        $escapedCover     = htmlspecialchars($coverImagePrompt, ENT_QUOTES, 'UTF-8');
        $imageJson        = json_encode($imagePrompt, JSON_UNESCAPED_UNICODE);
        $coverJson        = json_encode($coverImagePrompt, JSON_UNESCAPED_UNICODE);
        $editUrl          = route('posts.edit', $post->getKey());

        $html = <<<HTML
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Blog Image Prompts — {$title}</title>
            <style>
                *{box-sizing:border-box;margin:0;padding:0}
                body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;background:#f1f5f9;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;padding:24px;gap:20px}
                .card{background:#fff;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,.1);width:100%;max-width:720px;overflow:hidden}
                .header{background:linear-gradient(135deg,#3b0764 0%,#6d28d9 60%,#7c3aed 100%);padding:22px 24px 18px}
                .header-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.18);color:#fff;font-size:11px;font-weight:700;padding:3px 11px;border-radius:20px;border:1px solid rgba(255,255,255,.25);margin-bottom:8px}
                .header h1{color:#fff;font-size:13px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;opacity:.7;margin-bottom:4px}
                .header-title{color:#fff;font-size:18px;font-weight:800;line-height:1.3}
                .body{padding:24px}
                .section-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#7c3aed;margin-bottom:10px;margin-top:20px}
                .section-label:first-child{margin-top:0}
                .tip{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:11px 14px;font-size:12px;color:#92400e;line-height:1.55;margin-bottom:10px}
                .tip strong{font-weight:700}
                .logo-link{color:#7c3aed;font-weight:600}
                textarea{width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:13px 14px;font-size:12.5px;font-family:inherit;resize:vertical;color:#334155;background:#f8fafc;line-height:1.65;min-height:200px}
                textarea:focus{outline:none;border-color:#7c3aed;background:#fff}
                .btn-row{display:flex;gap:10px;margin-top:14px;flex-wrap:wrap}
                .btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:11px 20px;border:none;border-radius:10px;font-size:13.5px;font-weight:700;cursor:pointer;transition:all .15s;text-decoration:none;flex:1}
                .btn-purple{background:#7c3aed;color:#fff}
                .btn-purple:hover{background:#6d28d9}
                .btn-purple.ok{background:#16a34a}
                .btn-back{background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0}
                .btn-back:hover{background:#e2e8f0;color:#1e293b}
                .divider{border:none;border-top:1.5px solid #e2e8f0;margin:20px 0}
            </style>
        </head>
        <body>
            <div class="card">
                <div class="header">
                    <div class="header-badge">✨ AI Image Prompts</div>
                    <h1>Blog Post Images</h1>
                    <div class="header-title">{$title}</div>
                </div>
                <div class="body">

                    <div class="section-label">📷 Thumbnail / Listing Image — 1200 × 630 px</div>
                    <div class="tip">
                        💡 <strong>Paste into ChatGPT (GPT-4o), DALL·E 3, or Midjourney.</strong><br>
                        Used on the blog listing page and Open Graph sharing preview. Generate at <strong>1200 × 630 px</strong>.
                    </div>
                    <textarea id="image-ta" readonly>{$escapedImage}</textarea>
                    <div class="btn-row">
                        <button class="btn btn-purple" onclick="copyPrompt(this,'image')">📋 Copy Thumbnail Prompt</button>
                    </div>

                    <hr class="divider">

                    <div class="section-label">🖼 Cover Banner Image — 1800 × 540 px</div>
                    <div class="tip">
                        💡 <strong>Wide landscape banner for the blog post detail page header.</strong><br>
                        Displayed at full width at the top of the article. Generate at <strong>1800 × 540 px</strong>.
                    </div>
                    <textarea id="cover-ta" readonly>{$escapedCover}</textarea>
                    <div class="btn-row">
                        <button class="btn btn-purple" onclick="copyPrompt(this,'cover')">📋 Copy Cover Prompt</button>
                    </div>

                    <hr class="divider">
                    <div class="btn-row">
                        <a class="btn btn-back" href="{$editUrl}">← Back to Post</a>
                    </div>
                </div>
            </div>
            <script>
                const prompts = { image: {$imageJson}, cover: {$coverJson} };
                function copyPrompt(btn, key) {
                    var txt = prompts[key];
                    var orig = btn.textContent;
                    navigator.clipboard.writeText(txt).then(done).catch(function() {
                        document.getElementById(key + '-ta').select();
                        document.execCommand('copy');
                        done();
                    });
                    function done() {
                        btn.textContent = '✅ Copied!';
                        btn.classList.add('ok');
                        setTimeout(function() { btn.textContent = orig; btn.classList.remove('ok'); }, 2500);
                    }
                }
            </script>
        </body>
        </html>
        HTML;

        return response($html);
    }

    public function buildImagePromptPublic(Post $post): string
    {
        return $this->buildImagePrompt($post);
    }

    public function buildCoverImagePromptPublic(Post $post): string
    {
        return $this->buildCoverImagePrompt($post);
    }

    private function buildImagePrompt(Post $post): string
    {
        $title    = $post->name;
        $category = $post->categories->first()?->name ?? 'Career & Jobs';
        $desc     = $post->description ?? '';

        return <<<PROMPT
        A professional, photorealistic editorial photograph for a blog thumbnail on Wakanda Jobs — Africa's leading job board.

        Article title: "{$title}"
        Category: {$category}
        Context: {$desc}

        Visual concept: An image that authentically represents the article's theme in an African professional context. Show real working environments, modern offices, or scenes of career growth — natural skin tones, contemporary but realistic African settings, no stock-photo clichés.

        Format: 1200 × 630 px (landscape, standard blog Open Graph ratio).

        Composition: Keep the lower-right area visually calm — text overlay will be placed there. Place the main visual interest in the upper-left. Ensure breathing room for a title and subtitle.

        Style: warm natural lighting, shallow depth of field, editorial/documentary photography look, vibrant but not oversaturated colours.

        Strictly avoid: any text, letters, numbers, logos, watermarks, or UI elements baked into the image; cartoon/illustrated/3D-render styles; cluttered backgrounds; people staring directly into the camera.
        PROMPT;
    }

    private function buildCoverImagePrompt(Post $post): string
    {
        $title    = $post->name;
        $category = $post->categories->first()?->name ?? 'Career & Jobs';
        $desc     = $post->description ?? '';

        return <<<PROMPT
        A wide cinematic landscape photograph for a blog article cover banner on Wakanda Jobs — Africa's leading job board.

        Article title: "{$title}"
        Category: {$category}
        Context: {$desc}

        Visual concept: A broad, panoramic scene that sets the mood for the article in an African professional context. Think modern city skylines, expansive co-working spaces, wide open-plan offices, or landscapes that evoke opportunity and growth — natural skin tones, contemporary African settings.

        Format: 1800 × 540 px (extra-wide landscape banner, roughly 10:3 ratio). The image must be ultra-wide — do NOT crop it into a square or 16:9 frame.

        Composition: Very wide and cinematic. Keep the left-centre area calm and uncluttered for title text overlay. Spread visual interest across the full width of the frame. Avoid dark or busy areas in the upper third where text may appear.

        Style: warm natural lighting, epic wide-angle composition, editorial/documentary photography, vibrant but not oversaturated colours, spacious and airy feel.

        Strictly avoid: any text, letters, numbers, logos, watermarks, or UI elements baked into the image; cartoon/illustrated/3D-render styles; heavily cropped or portrait-oriented compositions; people staring directly into the camera.
        PROMPT;
    }
}
