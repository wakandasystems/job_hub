<section class="section-box mt-50 mb-50">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">

                <div class="box-shadow-bdrd-15 p-40 mb-30">
                    <div class="color-text-mutted font-sm mb-5">{{ $report->year }}{{ $report->sector ? ' · ' . $report->sector : '' }}</div>
                    <h3 class="fw-bold mb-15">{{ $report->title }}</h3>

                    @if($report->description)
                        <p class="color-text-paragraph-2 mb-20">{{ $report->description }}</p>
                    @endif

                    <div class="row mb-20">
                        <div class="col-6">
                            <div class="color-text-mutted font-xs">Format</div>
                            <div class="font-sm font-bold"><i class="fas fa-file-pdf color-danger me-1"></i>PDF Download</div>
                        </div>
                        <div class="col-6">
                            <div class="color-text-mutted font-xs">Access</div>
                            <div class="font-sm font-bold">Instant — valid for 12 months</div>
                        </div>
                    </div>

                    <hr>

                    @if($report->price > 0)
                        <div class="d-flex align-items-center gap-3 mt-20">
                            <span class="font-xl font-bold color-brand-2">{{ $report->currency_code }} {{ number_format($report->price, 2) }}</span>
                            <button class="btn btn-default flex-grow-1" data-bs-toggle="modal" data-bs-target="#purchase-modal">
                                <i class="fas fa-shopping-cart me-2"></i>Purchase Report
                            </button>
                        </div>
                    @else
                        <a href="{{ route('salary-checker') }}" class="btn btn-default w-100 mt-20">
                            <i class="fas fa-search me-2"></i>Use the Free Salary Checker
                        </a>
                    @endif
                </div>

                <div class="box-shadow-bdrd-15 p-30 mb-20 bg-grey">
                    <p class="font-xs color-text-paragraph-2 mb-0">
                        <strong>What's included:</strong> Salary ranges by job title, sector, and city/region.
                        Based on real job postings on Wakanda Jobs. PDF delivered to your email instantly after purchase.
                    </p>
                </div>

                <div class="text-center">
                    <a href="{{ route('salary-reports.public.index') }}" class="font-sm color-text-mutted">
                        ← Browse all reports
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

@if($report->price > 0)
<div class="modal fade" id="purchase-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Purchase: {{ $report->title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('payments.checkout') }}" method="POST">
                    @csrf
                    <input type="hidden" name="salary_report_id" value="{{ $report->id }}">
                    <input type="hidden" name="amount" value="{{ $report->price }}">
                    <input type="hidden" name="currency" value="{{ $report->currency_code }}">
                    <input type="hidden" name="callback_url" value="{{ route('salary-reports.public.index') }}">
                    <input type="hidden" name="return_url" value="{{ route('salary-reports.public.show', $report->slug) }}">

                    <div class="form-group mb-20">
                        <label class="font-sm color-text-mutted mb-5">Full Name <span class="color-danger">*</span></label>
                        <input type="text" class="form-control" name="buyer_name" required placeholder="Your full name">
                    </div>
                    <div class="form-group mb-20">
                        <label class="font-sm color-text-mutted mb-5">Email Address <span class="color-danger">*</span></label>
                        <input type="email" class="form-control" name="buyer_email" required placeholder="your@email.com">
                        <span class="font-xs color-text-mutted">Download link will be sent here.</span>
                    </div>
                    <div class="form-group mb-20">
                        <label class="font-sm color-text-mutted mb-5">Company / Organisation</label>
                        <input type="text" class="form-control" name="buyer_company" placeholder="Optional">
                    </div>

                    <div class="d-flex align-items-center justify-content-between mt-20">
                        <span class="font-lg font-bold color-brand-2">Total: {{ $report->currency_code }} {{ number_format($report->price, 2) }}</span>
                        <button type="submit" class="btn btn-default">
                            <i class="fas fa-credit-card me-1"></i>Proceed to Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
