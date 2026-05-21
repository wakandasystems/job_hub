<section class="section-box mt-80 mb-80">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-6 col-md-8 col-12">
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:80px;height:80px;">
                        <i class="fi-rr-check fs-1 text-success"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-2">Booking Confirmed!</h3>
                <p class="color-text-paragraph-2 mb-4">
                    Your <strong>{{ $order->service_name }}</strong> has been booked successfully.
                    A career coach will reach out to <strong>{{ $order->customer_email }}</strong> within 2–4 hours.
                </p>
                <div class="card border-0 bg-light text-start mb-4">
                    <div class="card-body px-4 py-3">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Service</span>
                            <strong>{{ $order->service_name }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Amount Paid</span>
                            <strong>{{ $order->currency }} {{ number_format($order->amount, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">Reference</span>
                            <strong class="text-muted">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</strong>
                        </div>
                    </div>
                </div>

                {{-- CV upload step (skip for interview coaching which needs no document) --}}
                @if($order->service_type !== 'interview_coaching')
                    <div class="card border-0 shadow-sm text-start mb-4">
                        <div class="card-body p-4">
                            @if($order->candidate_cv_path)
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:42px;height:42px;flex-shrink:0;">
                                        <i class="fi-rr-check text-success fs-5"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-semibold">CV uploaded</h6>
                                        <p class="mb-0 text-muted font-xs">Your coach has your CV and will begin work shortly.</p>
                                    </div>
                                </div>
                                <p class="mb-3 text-muted font-sm">Need to replace it? Upload a new file below.</p>
                            @else
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10" style="width:42px;height:42px;flex-shrink:0;">
                                        <i class="fi-rr-file-upload text-warning fs-5"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-semibold">Step 2 — Upload your CV</h6>
                                        <p class="mb-0 text-muted font-xs">Send your CV so the coach can get started.</p>
                                    </div>
                                </div>
                                <p class="mb-3 text-muted font-sm">
                                    Upload your CV as a <strong>DOCX</strong> (preferred) or PDF so your coach has an editable copy to work from.
                                </p>
                            @endif

                            @if(session('success_msg'))
                                <div class="alert alert-success py-2 px-3 font-sm mb-3">{{ session('success_msg') }}</div>
                            @endif
                            @if(session('error_msg'))
                                <div class="alert alert-danger py-2 px-3 font-sm mb-3">{{ session('error_msg') }}</div>
                            @endif
                            @if($errors->any())
                                <div class="alert alert-danger py-2 px-3 font-sm mb-3">{{ $errors->first() }}</div>
                            @endif

                            <form method="POST" action="{{ route('public.career-service.upload-cv', ['order' => $order->id]) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Your CV file <span class="text-danger">*</span></label>
                                    <input type="file" name="candidate_cv" class="form-control" accept=".docx,.doc,.pdf" required>
                                    <div class="form-text">DOCX is preferred so the coach can edit directly. Max 10 MB.</div>
                                </div>
                                <button type="submit" class="btn btn-apply w-100">
                                    <i class="fi-rr-file-upload me-2"></i>Upload CV
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                <a href="{{ route('public.index') }}" class="btn btn-apply btn-apply-big me-2">Back to Home</a>
                <a href="{{ \Botble\JobBoard\Facades\JobBoardHelper::getJobsPageURL() }}" class="btn btn-outline-primary btn-apply-big">Browse Jobs</a>
            </div>
        </div>
    </div>
</section>
