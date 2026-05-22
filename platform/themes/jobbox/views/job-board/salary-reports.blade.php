<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="text-center mb-50">
            <h2 class="section-title">Zambia Salary Reports</h2>
            <p class="color-text-paragraph-2">Authoritative, data-driven compensation benchmarks for HR teams and businesses.</p>
            <a href="{{ route('salary-checker') }}" class="btn btn-outline-brand-2 mt-20">
                <i class="fas fa-search me-2"></i>Try the Free Salary Checker
            </a>
        </div>

        @if($reports->isEmpty())
            <div class="text-center color-text-paragraph-2 py-5">
                <i class="fas fa-file-pdf fa-3x mb-3 d-block"></i>
                No reports are available yet. Check back soon.
            </div>
        @else
            <div class="row">
                @foreach($reports as $report)
                    <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-12">
                        <div class="card-grid-2 hover-up mb-20">
                            <div class="card-grid-2-image-left">
                                <div class="card-grid-2-image-rd">
                                    <i class="fas fa-chart-bar fa-2x" style="color:#3c65f5"></i>
                                </div>
                                <div class="card-profile pt-10">
                                    <h5>{{ $report->title }}</h5>
                                    <span class="font-xs color-text-mutted">{{ $report->year }}{{ $report->sector ? ' · ' . $report->sector : '' }}</span>
                                </div>
                            </div>
                            <div class="card-block-info">
                                @if($report->description)
                                    <p class="font-xs color-text-paragraph mt-10">{{ Str::limit($report->description, 100) }}</p>
                                @endif
                                <div class="d-flex justify-content-between align-items-center mt-20">
                                    <span class="card-briefcase font-bold color-brand-2">
                                        @if($report->price > 0)
                                            {{ $report->currency_code }} {{ number_format($report->price, 2) }}
                                        @else
                                            Free
                                        @endif
                                    </span>
                                    <a href="{{ route('salary-reports.public.show', $report->slug) }}" class="btn btn-apply-now">
                                        View Report
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
