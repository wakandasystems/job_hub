@php
    $imageJson = json_encode($imagePrompt, JSON_UNESCAPED_UNICODE);
    $coverJson = json_encode($coverImagePrompt, JSON_UNESCAPED_UNICODE);
@endphp
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h4 class="card-title mb-0" style="font-size:14px">
            <i class="ti ti-wand me-1" style="color:#7c3aed"></i>
            AI Image Prompts
        </h4>
        <a href="{{ $promptUrl }}"
           target="_blank"
           rel="noopener"
           class="btn btn-sm btn-light border"
           style="font-size:11px;font-weight:600">
            Open Full Page →
        </a>
    </div>
    <div class="card-body">

        {{-- Thumbnail / listing image --}}
        <p class="text-muted mb-1" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#7c3aed!important">
            📷 Thumbnail — 1200 × 630 px
        </p>
        <textarea
            id="blog-img-prompt-ta"
            class="form-control mb-2"
            rows="5"
            readonly
            style="font-size:11px;line-height:1.6;background:#f8fafc;resize:vertical"
        >{{ $imagePrompt }}</textarea>
        <div class="mb-3">
            <button type="button"
                    class="btn btn-sm"
                    onclick="blogCopyPrompt(this,'image')"
                    style="background:#7c3aed;border-color:#7c3aed;color:#fff;font-size:11px;font-weight:700">
                📋 Copy Thumbnail Prompt
            </button>
        </div>

        {{-- Cover banner image --}}
        <p class="text-muted mb-1" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#0369a1!important">
            🖼 Cover Banner — 1800 × 540 px
        </p>
        <textarea
            id="blog-cover-prompt-ta"
            class="form-control mb-2"
            rows="5"
            readonly
            style="font-size:11px;line-height:1.6;background:#f8fafc;resize:vertical"
        >{{ $coverImagePrompt }}</textarea>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button"
                    class="btn btn-sm"
                    onclick="blogCopyPrompt(this,'cover')"
                    style="background:#0369a1;border-color:#0369a1;color:#fff;font-size:11px;font-weight:700">
                📋 Copy Cover Prompt
            </button>
            <a href="{{ $promptUrl }}"
               target="_blank"
               rel="noopener"
               class="btn btn-sm btn-outline-secondary"
               style="font-size:11px">
                ✨ Open Full Page
            </a>
        </div>
    </div>
</div>

<script>
var _blogPrompts = { image: {!! $imageJson !!}, cover: {!! $coverJson !!} };
function blogCopyPrompt(btn, key) {
    var txt = _blogPrompts[key];
    var orig = btn.innerHTML;
    var origBg = btn.style.background;
    var origBorder = btn.style.borderColor;
    function onCopied() {
        btn.innerHTML = '✅ Copied!';
        btn.style.background = '#16a34a';
        btn.style.borderColor = '#16a34a';
        setTimeout(function () {
            btn.innerHTML = orig;
            btn.style.background = origBg;
            btn.style.borderColor = origBorder;
        }, 2500);
    }
    if (navigator.clipboard) {
        navigator.clipboard.writeText(txt).then(onCopied).catch(function () {
            var ta = document.getElementById(key === 'image' ? 'blog-img-prompt-ta' : 'blog-cover-prompt-ta');
            ta.select(); document.execCommand('copy'); onCopied();
        });
    } else {
        var ta = document.getElementById(key === 'image' ? 'blog-img-prompt-ta' : 'blog-cover-prompt-ta');
        ta.select(); document.execCommand('copy'); onCopied();
    }
}
</script>
