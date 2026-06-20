@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .otp-wrapper { display: flex; justify-content: center; gap: 10px; margin-bottom: 20px; }
    .otp-field { width: 50px; height: 60px; font-size: 24px; font-weight: bold; text-align: center; border-radius: 8px; border: 2px solid #ced4da; text-transform: uppercase; transition: all 0.2s ease-in-out; }
    .otp-field:focus { border-color: #0d6efd; box-shadow: 0 0 5px rgba(13, 110, 253, 0.5); outline: none; }
    .otp-field.success { border-color: #198754 !important; color: #198754; }
    .otp-field.error { border-color: #dc3545 !important; color: #dc3545; }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h5 class="mb-0 fw-bold">Verifikasi Kode OTP Login</h5>
                </div>

                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                        <p class="text-muted">
                            Kami telah mengirimkan 6 karakter kode OTP ke nomor WhatsApp dan Email Anda. Silakan masukkan kode tersebut di bawah ini untuk masuk.
                        </p>
                    </div>

                    @if (session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle me-2"></i> {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- MENGARAH KE ROUTE OTP LOGIN -->
                    <form id="otp-form" method="POST" action="{{ route('login.otp.process') }}">
                        @csrf
                        <input type="hidden" name="otp" id="otp-hidden" value="">

                        <div class="mb-4">
                            <div class="otp-wrapper" id="otp-container">
                                <input type="text" class="otp-field" maxlength="1" autofocus autocomplete="off">
                                <input type="text" class="otp-field" maxlength="1" autocomplete="off">
                                <input type="text" class="otp-field" maxlength="1" autocomplete="off">
                                <input type="text" class="otp-field" maxlength="1" autocomplete="off">
                                <input type="text" class="otp-field" maxlength="1" autocomplete="off">
                                <input type="text" class="otp-field" maxlength="1" autocomplete="off">
                            </div>
                        </div>

                        <div class="d-flex justify-content-center mb-4">
                            <div class="input-group" style="width: auto;">
                                <button class="btn btn-outline-secondary bg-light" type="button" id="btn-resend" disabled onclick="window.location.href='{{ route('login') }}'">RESEND</button>
                                <span class="input-group-text bg-light text-warning fw-bold" id="timer-display">01:00</span>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" id="btn-submit" class="btn btn-primary btn-lg fw-bold d-none">Verifikasi Sekarang</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.otp-field');
    const hiddenInput = document.getElementById('otp-hidden');
    const form = document.getElementById('otp-form');
    
    inputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
            if (e.target.value !== '') { if (index < inputs.length - 1) inputs[index + 1].focus(); }
            checkOTPCompletion();
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && e.target.value === '') { if (index > 0) inputs[index - 1].focus(); }
        });
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').replace(/[^a-zA-Z0-9]/g, '').toUpperCase().slice(0, 6); 
            let dataIndex = 0;
            inputs.forEach((inpt, i) => {
                if (i >= index && dataIndex < pastedData.length) { inpt.value = pastedData[dataIndex]; dataIndex++; }
            });
            const lastFilledIndex = Math.min(index + pastedData.length - 1, inputs.length - 1);
            inputs[lastFilledIndex].focus();
            checkOTPCompletion();
        });
    });

    function checkOTPCompletion() {
        let otpValue = ''; let allFilled = true;
        inputs.forEach(input => { otpValue += input.value; if (input.value === '') allFilled = false; });
        if (allFilled && otpValue.length === 6) {
            hiddenInput.value = otpValue; 
            inputs.forEach(input => { input.classList.remove('error'); input.classList.add('success'); });
            setTimeout(() => { form.submit(); }, 300);
        } else {
            inputs.forEach(input => input.classList.remove('success', 'error'));
        }
    }

    const urlParams = new URLSearchParams(window.location.search);
    const otpFromUrl = urlParams.get('otp');
    if (otpFromUrl && otpFromUrl.length === 6) {
        const otpArray = otpFromUrl.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().split('');
        inputs.forEach((input, index) => { if (otpArray[index]) { input.value = otpArray[index]; } });
        checkOTPCompletion();
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    let timeLeft = 60;
    const timerDisplay = document.getElementById('timer-display');
    const resendBtn = document.getElementById('btn-resend');
    const countdown = setInterval(() => {
        timeLeft--;
        timerDisplay.textContent = `${Math.floor(timeLeft / 60).toString().padStart(2, '0')}:${(timeLeft % 60).toString().padStart(2, '0')}`;
        if (timeLeft <= 0) {
            clearInterval(countdown);
            resendBtn.disabled = false; resendBtn.classList.remove('btn-outline-secondary'); resendBtn.classList.add('btn-primary');
            timerDisplay.textContent = "00:00";
        }
    }, 1000);

    @if (session('error') || $errors->has('otp'))
        inputs.forEach(input => input.classList.add('error'));
        Swal.fire({
            icon: 'error', title: 'Bad Request Error',
            text: "{!! addslashes(session('error') ?? $errors->first('otp')) !!}",
            confirmButtonText: 'OK', confirmButtonColor: '#3085d6'
        });
    @endif
});
</script>
@endsection