@php
$publisher = app(\Botble\JobBoard\Services\SocialPublisherService::class);
$slotPrompts = [];
if (isset($job)) {
    try { $slotPrompts['cover_image']    = $publisher->buildCoverImagePrompt($job);    } catch (\Throwable) {}
    try { $slotPrompts['tiktok_image']   = $publisher->buildTikTokImagePrompt($job);   } catch (\Throwable) {}
    try { $slotPrompts['whatsapp_image'] = $publisher->buildAiImagePrompt($job);       } catch (\Throwable) {}
    try { $slotPrompts['facebook_image'] = $publisher->buildFacebookImagePrompt($job); } catch (\Throwable) {}
    try { $slotPrompts['linkedin_image'] = $publisher->buildLinkedInImagePrompt($job); } catch (\Throwable) {}
    try { $slotPrompts['twitter_image']  = $publisher->buildTwitterImagePrompt($job);  } catch (\Throwable) {}
}
$uploadUrlsJson     = json_encode($uploadUrls,              JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$jobImagesJson      = json_encode($jobImages,               JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$companyLogoJson    = json_encode($companyLogoUrl,          JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$slotPostsJson      = json_encode($slotPosts ?? [],         JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$slotPromptsJson    = json_encode($slotPrompts,             JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$whapiSendUrlJson   = json_encode($whapiSendUrl ?? null,    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$whapiChannelJson   = json_encode($whapiChannelName ?? '',  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp

{{-- CSS injected via JS because Botble strips <style> tags from meta box content --}}

<div class="sjw">
    <div class="img-grid">

        {{-- Company Logo --}}
        <div class="img-slot full-width" id="slot-company_logo">
            <div class="img-slot-head">
                <span class="img-slot-icon">🏢</span>
                <div class="img-slot-info">
                    <span class="img-slot-label">Company Logo</span>
                    <span class="img-slot-dim">Square or landscape · PNG/WebP</span>
                </div>
            </div>
            <div class="img-slot-body">
                <div id="preview-company_logo" style="display:{{ $companyLogoUrl ? 'block' : 'none' }};">
                    <div class="img-preview-wrap" style="max-height:100px;aspect-ratio:auto">
                        <img id="img-company_logo" src="{{ $companyLogoUrl ?? '' }}" alt="Company logo" style="height:100px;width:auto;margin:0 auto;display:block;object-fit:contain;background:#f8fafc;border-radius:6px">
                        <div class="img-preview-overlay">
                            <button type="button" class="img-replace-btn" onclick="sjwTriggerUpload('company_logo')">🔄 Replace</button>
                        </div>
                    </div>
                </div>
                <div id="zone-company_logo" class="img-upload-zone" onclick="sjwTriggerUpload('company_logo')" ondragover="sjwDragOver(event,'company_logo')" ondragleave="sjwDragLeave('company_logo')" ondrop="sjwDrop(event,'company_logo')" style="display:{{ $companyLogoUrl ? 'none' : 'flex' }};">
                    <div class="img-upload-zone-icon">🖼</div>
                    <div class="img-upload-zone-label">Upload Company Logo</div>
                    <div class="img-upload-zone-sub">PNG · JPG · WebP</div>
                </div>
                <input type="file" id="file-company_logo" accept="image/*" onchange="sjwHandleFile('company_logo',this)" style="display:none">
                <div class="img-progress" id="progress-company_logo">
                    <div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-company_logo"></div></div>
                    <div class="img-progress-label" id="label-company_logo"></div>
                </div>
                <div class="img-slot-footer">
                    <a class="img-footer-btn dl" id="dl-company_logo" href="{{ $companyLogoUrl ?? '#' }}" download="company-logo" style="{{ $companyLogoUrl ? 'opacity:1;pointer-events:auto' : 'opacity:.35;pointer-events:none' }}">⬇ Download</a>
                </div>
            </div>
        </div>

        {{-- Cover Image --}}
        @php $coverUrl = $jobImages['cover_image'] ?? null; @endphp
        <div class="img-slot full-width" id="slot-cover_image">
            <div class="img-slot-head">
                <span class="img-slot-icon">🖼</span>
                <div class="img-slot-info">
                    <span class="img-slot-label">Job Cover Image</span>
                    <span class="img-slot-dim">1800 × 540 px · landscape</span>
                </div>
                @if(!empty($slotPrompts['cover_image']))
                    <button type="button" class="img-copy-btn" onclick="sjwCopyPrompt('cover_image',this)" title="Copy AI prompt">📋 Copy</button>
                @endif
            </div>
            <div class="img-slot-body">
                <div id="preview-cover_image" style="display:{{ $coverUrl ? 'block' : 'none' }};">
                    <div class="img-preview-wrap" style="aspect-ratio:10/3">
                        <img id="img-cover_image" src="{{ $coverUrl ?? '' }}" alt="Cover image">
                        <div class="img-preview-overlay"><button type="button" class="img-replace-btn" onclick="sjwTriggerUpload('cover_image')">🔄 Replace</button></div>
                    </div>
                </div>
                <div id="zone-cover_image" class="img-upload-zone" onclick="sjwTriggerUpload('cover_image')" ondragover="sjwDragOver(event,'cover_image')" ondragleave="sjwDragLeave('cover_image')" ondrop="sjwDrop(event,'cover_image')" style="display:{{ $coverUrl ? 'none' : 'flex' }};">
                    <div class="img-upload-zone-icon">🖼</div>
                    <div class="img-upload-zone-label">Upload Cover Image</div>
                    <div class="img-upload-zone-sub">1800 × 540 px</div>
                </div>
                <input type="file" id="file-cover_image" accept="image/*" onchange="sjwHandleFile('cover_image',this)" style="display:none">
                <div class="img-progress" id="progress-cover_image"><div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-cover_image"></div></div><div class="img-progress-label" id="label-cover_image"></div></div>
                <div class="img-slot-footer">
                    <a class="img-footer-btn dl" id="dl-cover_image" href="{{ $coverUrl ?? '#' }}" download="cover-image" style="{{ $coverUrl ? 'opacity:1;pointer-events:auto' : 'opacity:.35;pointer-events:none' }}">⬇ Download</a>
                </div>
            </div>
        </div>

        @foreach([
            'tiktok_image'   => ['🎵', 'TikTok',    '1080 × 1920 · 9:16', '9/16',      'tiktok'],
            'whatsapp_image' => ['💬', 'WhatsApp',  '1080 × 1920 · status','9/16',     'whatsapp'],
            'facebook_image' => ['📘', 'Facebook',  '1200 × 630 · landscape','1200/630','facebook'],
            'linkedin_image' => ['💼', 'LinkedIn',  '1200 × 627 · landscape','1200/627','linkedin'],
            'twitter_image'  => ['𝕏', 'X / Twitter','1200 × 675 · 16:9',  '16/9',      'twitter'],
        ] as $col => [$icon, $label, $dim, $ratio, $postKey])
            @php $imgUrl = $jobImages[$col] ?? null; @endphp
            <div class="img-slot" id="slot-{{ $col }}">
                <div class="img-slot-head">
                    <span class="img-slot-icon">{{ $icon }}</span>
                    <div class="img-slot-info">
                        <span class="img-slot-label">{{ $label }}</span>
                        <span class="img-slot-dim">{{ $dim }}</span>
                    </div>
                    @if(!empty($slotPrompts[$col]))
                        <button type="button" class="img-copy-btn" onclick="sjwCopyPrompt('{{ $col }}',this)" title="Copy AI image prompt">📋 Prompt</button>
                    @endif
                </div>
                <div class="img-slot-body">
                    <div id="preview-{{ $col }}" style="display:{{ $imgUrl ? 'block' : 'none' }};">
                        <div class="img-preview-wrap" style="aspect-ratio:{{ $ratio }}">
                            <img id="img-{{ $col }}" src="{{ $imgUrl ?? '' }}" alt="{{ $label }} image">
                            <div class="img-preview-overlay"><button type="button" class="img-replace-btn" onclick="sjwTriggerUpload('{{ $col }}')">🔄 Replace</button></div>
                        </div>
                    </div>
                    <div id="zone-{{ $col }}" class="img-upload-zone" onclick="sjwTriggerUpload('{{ $col }}')" ondragover="sjwDragOver(event,'{{ $col }}')" ondragleave="sjwDragLeave('{{ $col }}')" ondrop="sjwDrop(event,'{{ $col }}')" style="display:{{ $imgUrl ? 'none' : 'flex' }};">
                        <div class="img-upload-zone-icon">{{ $icon }}</div>
                        <div class="img-upload-zone-label">Upload {{ $label }}</div>
                        <div class="img-upload-zone-sub">{{ $dim }}</div>
                    </div>
                    <input type="file" id="file-{{ $col }}" accept="image/*" onchange="sjwHandleFile('{{ $col }}',this)" style="display:none">
                    <div class="img-progress" id="progress-{{ $col }}"><div class="img-progress-bar-wrap"><div class="img-progress-bar" id="bar-{{ $col }}"></div></div><div class="img-progress-label" id="label-{{ $col }}"></div></div>
                    <div class="img-slot-footer">
                        <a class="img-footer-btn dl" id="dl-{{ $col }}" href="{{ $imgUrl ?? '#' }}" download="{{ $postKey }}-image" style="{{ $imgUrl ? 'opacity:1;pointer-events:auto' : 'opacity:.35;pointer-events:none' }}">⬇ Download</a>
                        @if(!empty($slotPosts[$col]))
                            <button type="button" class="img-footer-btn" onclick="sjwCopyPost('{{ $col }}',this)">📋 Post Text</button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

    </div>{{-- .img-grid --}}

    <a href="{{ $postKitUrl }}" target="_blank" class="btn btn-sm btn-outline-primary w-100 mt-1" style="font-size:11px;">
        <i class="fa fa-magic me-1"></i> Open Full Post Kit
    </a>
</div>{{-- .sjw --}}

<script>
(function () {
    // Inject scoped CSS (Botble strips <style> tags from meta box content)
    if (!document.getElementById('sjw-styles')) {
        const s = document.createElement('style');
        s.id = 'sjw-styles';
        s.textContent = [
            '.sjw .img-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px}',
            '.sjw .img-slot{background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e2e8f0}',
            '.sjw .img-slot.full-width{grid-column:1/-1}',
            '.sjw .img-slot-head{padding:8px 10px 6px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:7px}',
            '.sjw .img-slot-icon{font-size:14px;flex-shrink:0}',
            '.sjw .img-slot-info{flex:1;min-width:0}',
            '.sjw .img-slot-label{font-size:11px;font-weight:700;color:#1e293b;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
            '.sjw .img-slot-dim{font-size:9px;color:#94a3b8;display:block}',
            '.sjw .img-copy-btn{margin-left:auto;flex-shrink:0;padding:3px 7px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;font-size:11px;font-weight:700;color:#475569;cursor:pointer;transition:all .15s;white-space:nowrap}',
            '.sjw .img-copy-btn:hover{background:#066fd1;color:#fff;border-color:#066fd1}',
            '.sjw .img-copy-btn.ok{background:#16a34a;color:#fff;border-color:#16a34a}',
            '.sjw .img-slot-body{padding:8px 10px 10px}',
            '.sjw .img-preview-wrap{position:relative;border-radius:8px;overflow:hidden;background:#0f172a;min-height:60px}',
            '.sjw .img-preview-wrap img{width:100%;height:100%;object-fit:cover;display:block}',
            '.sjw .img-preview-overlay{position:absolute;inset:0;background:rgba(0,0,0,.45);opacity:0;transition:opacity .18s;display:flex;align-items:center;justify-content:center}',
            '.sjw .img-preview-wrap:hover .img-preview-overlay{opacity:1}',
            '.sjw .img-replace-btn{background:rgba(255,255,255,.92);color:#1e293b;border:none;border-radius:6px;padding:5px 11px;font-size:11px;font-weight:700;cursor:pointer}',
            '.sjw .img-upload-zone{border:2px dashed #cbd5e1;border-radius:8px;padding:14px 10px;text-align:center;cursor:pointer;transition:all .18s;background:#f8fafc;min-height:60px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px}',
            '.sjw .img-upload-zone:hover,.sjw .img-upload-zone.dragging{border-color:#066fd1;background:#eff6ff}',
            '.sjw .img-upload-zone-icon{font-size:18px}',
            '.sjw .img-upload-zone-label{font-size:10.5px;font-weight:600;color:#475569}',
            '.sjw .img-upload-zone-sub{font-size:9px;color:#94a3b8}',
            '.sjw .img-slot-footer{display:flex;gap:6px;margin-top:8px}',
            '.sjw .img-footer-btn{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:4px;padding:6px 8px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;transition:all .15s;text-decoration:none;background:#f8fafc;color:#475569;white-space:nowrap}',
            '.sjw .img-footer-btn:hover{border-color:#066fd1;color:#066fd1;background:#eff6ff}',
            '.sjw .img-footer-btn.ok{background:#16a34a;color:#fff;border-color:#16a34a}',
            '.sjw .img-footer-btn.dl{color:#0ea5e9;border-color:#bae6fd;background:#f0f9ff}',
            '.sjw .img-footer-btn.dl:hover{background:#0ea5e9;color:#fff;border-color:#0ea5e9}',
            '.sjw .img-progress{margin-top:6px;display:none}',
            '.sjw .img-progress-bar-wrap{height:5px;background:#e2e8f0;border-radius:99px;overflow:hidden}',
            '.sjw .img-progress-bar{height:100%;width:0%;background:#066fd1;border-radius:99px;transition:width .1s linear}',
            '.sjw .img-progress-bar.done{background:#16a34a}',
            '.sjw .img-progress-bar.fail{background:#dc2626}',
            '.sjw .img-progress-label{margin-top:3px;font-size:10px;font-weight:700;text-align:center;color:#066fd1}',
            '.sjw .img-progress-label.done{color:#16a34a}',
            '.sjw .img-progress-label.fail{color:#dc2626}',
        ].join('');
        document.head.appendChild(s);
    }

    const _uploadUrls    = {!! $uploadUrlsJson !!};
    const _slotPosts     = {!! $slotPostsJson !!};
    const _slotPrompts   = {!! $slotPromptsJson !!};
    const _csrfToken     = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const _whapiSendUrl  = {!! $whapiSendUrlJson !!};
    const _whapiChannel  = {!! $whapiChannelJson !!};

    function sjwShowPreview(key, url) {
        const img  = document.getElementById('img-' + key);
        const prev = document.getElementById('preview-' + key);
        const zone = document.getElementById('zone-' + key);
        const dl   = document.getElementById('dl-' + key);
        if (!img || !prev) return;
        img.src = url;
        prev.style.display = 'block';
        if (zone) zone.style.display = 'none';
        if (dl) { dl.href = url; dl.style.opacity = '1'; dl.style.pointerEvents = 'auto'; }
    }

    function sjwSetProgress(key, pct, state, label) {
        const wrap = document.getElementById('progress-' + key);
        const bar  = document.getElementById('bar-' + key);
        const lbl  = document.getElementById('label-' + key);
        if (!wrap) return;
        wrap.style.display = 'block';
        bar.style.width = pct + '%';
        bar.className = 'img-progress-bar' + (state ? ' ' + state : '');
        lbl.textContent = label;
        lbl.className = 'img-progress-label' + (state ? ' ' + state : '');
    }

    function sjwDoUpload(key, file) {
        const url = _uploadUrls[key];
        if (!url) { sjwSetProgress(key, 100, 'fail', '❌ Upload not available.'); return; }

        const reader = new FileReader();
        reader.onload = e => sjwShowPreview(key, e.target.result);
        reader.readAsDataURL(file);

        sjwSetProgress(key, 5, '', 'Uploading…');

        const fd = new FormData();
        fd.append('image', file);
        fd.append('type', key);

        const xhr = new XMLHttpRequest();
        xhr.upload.onprogress = function(e) {
            if (!e.lengthComputable) return;
            sjwSetProgress(key, Math.round(e.loaded / e.total * 90), '', 'Uploading… ' + Math.round(e.loaded / e.total * 90) + '%');
        };
        xhr.onload = function() {
            let data = {};
            try { data = JSON.parse(xhr.responseText); } catch {}
            if (xhr.status >= 200 && xhr.status < 300 && data.ok !== false) {
                if (data.url) sjwShowPreview(key, data.url);
                sjwSetProgress(key, 100, 'done', '✅ Saved!');
                setTimeout(() => {
                    const wrap = document.getElementById('progress-' + key);
                    if (wrap) wrap.style.display = 'none';
                    // After whatsapp_image upload, offer to send to channel if automation exists
                    if (key === 'whatsapp_image' && _whapiSendUrl) {
                        sjwAskSendToChannel();
                    } else {
                        location.reload();
                    }
                }, 1200);
            } else {
                sjwSetProgress(key, 100, 'fail', '❌ ' + (data.message || 'Upload failed (' + xhr.status + ')'));
            }
        };
        xhr.onerror = function() { sjwSetProgress(key, 100, 'fail', '❌ Network error.'); };
        xhr.open('POST', url);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-CSRF-TOKEN', _csrfToken);
        xhr.send(fd);
    }

    function sjwLoadSwal() {
        return new Promise(resolve => {
            if (window.Swal) { resolve(); return; }
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = '/vendor/core/core/base/libraries/sweetalert2/sweetalert2.min.css';
            document.head.appendChild(link);
            const script = document.createElement('script');
            script.src = '/vendor/core/core/base/libraries/sweetalert2/sweetalert2.min.js';
            script.onload = resolve;
            document.head.appendChild(script);
        });
    }

    function sjwAskSendToChannel() {
        sjwLoadSwal().then(() => {
            Swal.fire({
                title: 'Send to WhatsApp Channel?',
                html: 'Image uploaded. Send this job to <strong>' + _whapiChannel + '</strong> now?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#25D366',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="fab fa-whatsapp"></i> Yes, Send Now',
                cancelButtonText: 'No, just save',
                reverseButtons: true,
            }).then(result => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Sending…',
                        html: 'Posting job to <strong>' + _whapiChannel + '</strong>',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading(),
                    });
                    const fd = new FormData();
                    fd.append('_token', _csrfToken);
                    fetch(_whapiSendUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.json())
                        .then(data => {
                            const ok = data.error !== true;
                            Swal.fire({
                                icon: ok ? 'success' : 'error',
                                title: ok ? 'Sent!' : 'Failed',
                                text: data.message || (ok ? 'Job posted to WhatsApp Channel.' : 'Could not send.'),
                                timer: 2500,
                                showConfirmButton: false,
                            }).then(() => location.reload());
                        })
                        .catch(() => {
                            Swal.fire({ icon: 'error', title: 'Network error', text: 'Could not reach the server.' })
                                .then(() => location.reload());
                        });
                } else {
                    location.reload();
                }
            });
        });
    }

    function doCopyText(text, btn, resetLabel) {
        if (!text) return;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                const prev = btn.textContent;
                btn.textContent = '✅ Copied!';
                btn.classList.add('ok');
                setTimeout(() => { btn.textContent = resetLabel; btn.classList.remove('ok'); }, 1800);
            });
        }
    }

    // Expose as globals so onclick= handlers work
    window.sjwTriggerUpload = key => document.getElementById('file-' + key)?.click();
    window.sjwHandleFile    = (key, inp) => { const f = inp.files?.[0]; if (f) sjwDoUpload(key, f); };
    window.sjwDragOver      = (e, key) => { e.preventDefault(); document.getElementById('zone-' + key)?.classList.add('dragging'); };
    window.sjwDragLeave     = key => document.getElementById('zone-' + key)?.classList.remove('dragging');
    window.sjwDrop          = (e, key) => { e.preventDefault(); window.sjwDragLeave(key); const f = e.dataTransfer?.files?.[0]; if (f) sjwDoUpload(key, f); };
    window.sjwCopyPost      = (key, btn) => doCopyText(_slotPosts[key], btn, '📋 Post Text');
    window.sjwCopyPrompt    = (key, btn) => doCopyText(_slotPrompts[key], btn, '📋 Prompt');
})();
</script>
