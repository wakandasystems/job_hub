@php $promptJson = json_encode($prompt, JSON_UNESCAPED_UNICODE) @endphp
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h4 class="card-title mb-0" style="font-size:14px">
            <i class="ti ti-wand me-1" style="color:#7c3aed"></i>
            AI Cover Image Prompt
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
        <p class="text-muted mb-2" style="font-size:11.5px">
            Paste into ChatGPT, DALL·E 3, or Midjourney to generate a cover image (1200 × 630 px).
        </p>
        <textarea
            id="blog-img-prompt-ta"
            class="form-control"
            rows="7"
            readonly
            style="font-size:11.5px;line-height:1.6;background:#f8fafc;resize:vertical"
        >{{ $prompt }}</textarea>
        <div class="mt-2 d-flex gap-2 flex-wrap">
            <button type="button"
                    class="btn btn-sm"
                    onclick="blogCopyPrompt(this)"
                    style="background:#7c3aed;border-color:#7c3aed;color:#fff;font-size:12px;font-weight:700">
                📋 Copy Prompt
            </button>
            <a href="{{ $promptUrl }}"
               target="_blank"
               rel="noopener"
               class="btn btn-sm btn-outline-secondary"
               style="font-size:12px">
                ✨ Open Full Page
            </a>
        </div>
    </div>
</div>

<script>
var _blogImgPrompt = {!! $promptJson !!};
function blogCopyPrompt(btn) {
    var txt = _blogImgPrompt;
    var orig = btn.innerHTML;
    function onCopied() {
        btn.innerHTML = '✅ Copied!';
        btn.style.background = '#16a34a';
        btn.style.borderColor = '#16a34a';
        setTimeout(function () {
            btn.innerHTML = orig;
            btn.style.background = '#7c3aed';
            btn.style.borderColor = '#7c3aed';
        }, 2500);
    }
    if (navigator.clipboard) {
        navigator.clipboard.writeText(txt).then(onCopied).catch(function () {
            var ta = document.getElementById('blog-img-prompt-ta');
            ta.select(); document.execCommand('copy'); onCopied();
        });
    } else {
        var ta = document.getElementById('blog-img-prompt-ta');
        ta.select(); document.execCommand('copy'); onCopied();
    }
}
</script>
