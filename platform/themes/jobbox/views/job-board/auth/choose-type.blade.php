<section class="pt-100 login-register">
    <div class="container">
        <div class="row login-register-cover justify-content-center">
            <div class="col-lg-5 col-md-7 col-sm-12">
                <div class="text-center mb-40">
                    @if($account->avatarUrl)
                        <img src="{{ $account->avatarUrl }}" class="rounded-circle mb-15" width="64" height="64" alt="{{ $account->name }}">
                    @endif
                    <h2 class="mt-10 mb-5 color-brand-1">{{ __('Welcome, :name!', ['name' => $account->first_name]) }}</h2>
                    <p class="font-sm text-muted">{{ __('One last step — tell us how you\'ll use Wakanda Jobs.') }}</p>
                </div>

                <button type="button" class="btn btn-apply btn-apply-big" data-bs-toggle="modal" data-bs-target="#chooseAccountTypeModal">
                    {{ __('Choose account type') }}
                </button>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="chooseAccountTypeModal" tabindex="-1" aria-labelledby="chooseAccountTypeModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('public.account.choose-type.save') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="chooseAccountTypeModalLabel">{{ __('Choose your account type') }}</h5>
                </div>
                <div class="modal-body">
                    <p class="font-sm text-muted mb-20">{{ __('Select how you want to use Wakanda Jobs. You can continue after choosing one option.') }}</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="d-block h-100 cursor-pointer">
                                <input type="radio" name="type" value="job-seeker" class="d-none choose-type-radio" required>
                                <div class="card border-2 h-100 choose-type-card p-4 text-center">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 mx-auto mb-3" style="width:60px;height:60px;">
                                        <i class="fi-rr-user fs-3 text-primary"></i>
                                    </div>
                                    <h5 class="fw-bold mb-1">{{ __('Job Seeker') }}</h5>
                                    <p class="color-text-paragraph-2 font-sm mb-0">{{ __('I\'m looking for job opportunities') }}</p>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="d-block h-100 cursor-pointer">
                                <input type="radio" name="type" value="employer" class="d-none choose-type-radio" required>
                                <div class="card border-2 h-100 choose-type-card p-4 text-center">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 mx-auto mb-3" style="width:60px;height:60px;">
                                        <i class="fi-rr-building fs-3 text-warning"></i>
                                    </div>
                                    <h5 class="fw-bold mb-1">{{ __('Employer') }}</h5>
                                    <p class="color-text-paragraph-2 font-sm mb-0">{{ __('I\'m hiring talented professionals') }}</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    @error('type')
                        <div class="alert alert-danger mt-20 mb-0">{{ $message }}</div>
                    @enderror
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-apply btn-apply-big" id="choose-type-submit" disabled>
                        {{ __('Continue') }} <i class="fi-rr-arrow-right ms-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .choose-type-card { border-color: #dee2e6 !important; border-radius: 8px; cursor: pointer; transition: all .2s; }
    .choose-type-card.selected { border-color: var(--primary-color) !important; background: rgba(var(--primary-color-rgb, 20,99,223), .04); }
    .cursor-pointer { cursor: pointer; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalElement = document.getElementById('chooseAccountTypeModal');

    document.querySelectorAll('.choose-type-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.choose-type-card').forEach(function (card) { card.classList.remove('selected'); });
            this.closest('label').querySelector('.choose-type-card').classList.add('selected');
            document.getElementById('choose-type-submit').disabled = false;
        });
    });

    if (modalElement && window.bootstrap) {
        new bootstrap.Modal(modalElement).show();
    }
});
</script>
