<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="text-center mb-40">
                    <h2 class="section-title">Free Salary Checker</h2>
                    <p class="color-text-paragraph-2">Find out what roles pay in Zambia based on real job postings.</p>
                </div>

                <div class="box-shadow-bdrd-15 p-40 mb-25">
                    <form id="salary-checker-form">
                        <div class="row">
                            <div class="col-md-6 mb-20">
                                <label class="font-sm color-text-mutted mb-5 fw-bold">Job Title / Keyword</label>
                                <input type="text" class="form-control" id="sc-keyword"
                                    placeholder="e.g. Accountant, Nurse, Software Developer" maxlength="100">
                            </div>
                            <div class="col-md-6 mb-20">
                                <label class="font-sm color-text-mutted mb-5 fw-bold">Sector / Category</label>
                                <select class="form-control" id="sc-category">
                                    <option value="">All sectors</option>
                                    @foreach($categories as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-20">
                                <label class="font-sm color-text-mutted mb-5 fw-bold">City / Location</label>
                                <input type="text" class="form-control" id="sc-city"
                                    placeholder="e.g. Lusaka, Ndola, Kitwe" maxlength="100">
                            </div>
                            <div class="col-md-6 mb-20">
                                <label class="font-sm color-text-mutted mb-5 fw-bold">Experience Level</label>
                                <select class="form-control" id="sc-career-level">
                                    <option value="">Any level</option>
                                    @foreach($careerLevels as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-default w-100" id="sc-submit-btn">
                                    <i class="fas fa-search me-2"></i>Check Salary
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Results panel --}}
                <div id="salary-results" class="d-none">
                    <div class="box-shadow-bdrd-15 p-40 mb-25" style="background:#3c65f5;color:#fff">
                        <div class="text-center">
                            <div class="font-sm mb-5 opacity-75" id="result-label">Results</div>
                            <div class="font-3xl fw-bold mb-5">
                                K<span id="result-p25">—</span> – K<span id="result-p75">—</span>
                                <span class="font-md fw-normal">/month</span>
                            </div>
                            <div class="font-xs opacity-75">Typical range (25th–75th percentile)</div>
                        </div>
                    </div>

                    <div class="box-shadow-bdrd-15 p-40 mb-25">
                        <h6 class="fw-bold mb-20">Full Salary Distribution (monthly ZMW)</h6>
                        <div class="row text-center mb-25">
                            <div class="col-3">
                                <div class="font-xs color-text-mutted">Minimum</div>
                                <div class="fw-semibold">K<span id="result-min">—</span></div>
                            </div>
                            <div class="col-3">
                                <div class="font-xs color-text-mutted">25th pct</div>
                                <div class="fw-semibold">K<span id="result-p25b">—</span></div>
                            </div>
                            <div class="col-3">
                                <div class="font-xs color-text-mutted">Median</div>
                                <div class="fw-bold color-brand-2">K<span id="result-median">—</span></div>
                            </div>
                            <div class="col-3">
                                <div class="font-xs color-text-mutted">75th pct</div>
                                <div class="fw-semibold">K<span id="result-p75b">—</span></div>
                            </div>
                        </div>

                        <div class="position-relative mb-5" style="height:10px;background:#e0e6f7;border-radius:5px;overflow:hidden">
                            <div id="result-bar-inner" class="position-absolute h-100" style="left:0;width:0;background:#3c65f5;border-radius:5px;transition:width .4s ease"></div>
                        </div>
                        <div class="d-flex justify-content-between font-xs color-text-mutted mb-20">
                            <span>K<span id="bar-min">0</span></span>
                            <span>K<span id="bar-max">0</span></span>
                        </div>

                        <div class="font-xs color-text-mutted" id="result-meta"></div>
                    </div>

                    <div class="text-center mb-25">
                        <a href="#" id="result-browse-link" class="btn btn-outline-brand-2">
                            <i class="fas fa-briefcase me-2"></i><span id="result-browse-label">Browse open jobs</span>
                        </a>
                    </div>
                </div>

                {{-- Error panel --}}
                <div id="salary-error" class="d-none">
                    <div class="box-shadow-bdrd-15 p-20 mb-25" style="border-left:4px solid #f1c40f;background:#fffbec">
                        <i class="fas fa-info-circle me-2" style="color:#f1c40f"></i>
                        <span id="salary-error-msg">Not enough data for this search.</span>
                    </div>
                </div>

                <div class="box-shadow-bdrd-15 p-20 bg-grey">
                    <p class="font-xs color-text-paragraph-2 mb-0">
                        <strong>Methodology:</strong> Salary ranges are calculated from job postings on Wakanda Jobs.
                        All salaries are normalised to monthly ZMW. A minimum of 3 postings is required to show results.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

@push('footer')
<script>
(function () {
    const form      = document.getElementById('salary-checker-form');
    const resultsEl = document.getElementById('salary-results');
    const errorEl   = document.getElementById('salary-error');
    const submitBtn = document.getElementById('sc-submit-btn');
    const fmt       = n => Number(n).toLocaleString();

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        resultsEl.classList.add('d-none');
        errorEl.classList.add('d-none');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Checking...';

        const params = new URLSearchParams();
        const keyword     = document.getElementById('sc-keyword').value.trim();
        const category    = document.getElementById('sc-category').value;
        const city        = document.getElementById('sc-city').value.trim();
        const careerLevel = document.getElementById('sc-career-level').value;
        if (keyword) params.set('keyword', keyword);
        if (category) params.set('category_id', category);
        if (city) params.set('city', city);
        if (careerLevel) params.set('career_level_id', careerLevel);

        try {
            const res  = await fetch('{{ route('salary-checker.results') }}?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();

            if (!res.ok || !json.success) {
                document.getElementById('salary-error-msg').textContent = json.message || 'Not enough data.';
                errorEl.classList.remove('d-none');
            } else {
                const d     = json.data;
                const label = [keyword, city ? 'in ' + city : null].filter(Boolean).join(' ') || 'All roles';

                document.getElementById('result-label').textContent   = label;
                document.getElementById('result-p25').textContent     = fmt(d.p25);
                document.getElementById('result-p75').textContent     = fmt(d.p75);
                document.getElementById('result-p25b').textContent    = fmt(d.p25);
                document.getElementById('result-p75b').textContent    = fmt(d.p75);
                document.getElementById('result-min').textContent     = fmt(d.min);
                document.getElementById('result-median').textContent  = fmt(d.median);
                document.getElementById('bar-min').textContent        = fmt(d.min);
                document.getElementById('bar-max').textContent        = fmt(d.max);

                const range    = d.max - d.min || 1;
                const leftPct  = ((d.p25 - d.min) / range) * 100;
                const widthPct = ((d.p75 - d.p25) / range) * 100;
                const bar      = document.getElementById('result-bar-inner');
                bar.style.left  = leftPct + '%';
                bar.style.width = widthPct + '%';

                document.getElementById('result-meta').textContent =
                    'Based on ' + d.count + ' job posting' + (d.count !== 1 ? 's' : '') + ' in the last 12 months.';

                const browseUrl = '{{ url('/jobs') }}' + (keyword ? '?q=' + encodeURIComponent(keyword) : '');
                document.getElementById('result-browse-link').href  = browseUrl;
                document.getElementById('result-browse-label').textContent =
                    'Browse open ' + (keyword || '') + ' jobs';

                resultsEl.classList.remove('d-none');
            }
        } catch (err) {
            document.getElementById('salary-error-msg').textContent = 'Something went wrong. Please try again.';
            errorEl.classList.remove('d-none');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-search me-2"></i>Check Salary';
        }
    });
})();
</script>
@endpush
