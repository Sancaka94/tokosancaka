{{--
    File: resources/views/auth/passwords/reset.blade.php
    Ini adalah halaman formulir untuk memasukkan OTP dan password baru.
--}}

@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    body, .gradient-background {
        background: linear-gradient(135deg, #e0c3fc 0%, #ffffff 50%, #8ec5fc 100%);
    }
    .card-custom {
        border: none;
        border-radius: 1.25rem;
        box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;
    }
    .password-toggle-icon {
        cursor: pointer;
    }

    /* Styling Kotak OTP */
    .otp-wrapper {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 10px;
    }
    .otp-field {
        width: 100%;
        height: 55px;
        font-size: 22px;
        font-weight: bold;
        text-align: center;
        border-radius: 8px;
        border: 2px solid #ced4da;
        text-transform: uppercase;
        transition: all 0.2s ease-in-out;
    }
    .otp-field:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 5px rgba(13, 110, 253, 0.5);
        outline: none;
    }
    .otp-field.success {
        border-color: #198754 !important;
        color: #198754;
    }
    .otp-field.error {
        border-color: #dc3545 !important;
        color: #dc3545;
    }
</style>
@endpush

@section('content')
<div class="gradient-background mt-4">
    <div class="container">
        <div class="row vh-100 justify-content-center align-items-center">
            <div class="col-11 col-sm-8 col-md-6 col-lg-5 col-xl-4">
                <div class="card card-custom">
                    <div class="card-body p-4 p-sm-5">
                        
                        <div class="text-center mb-4">
                            <i class="bi bi-shield-lock-fill text-primary" style="font-size: 3rem;"></i>
                            <h2 class="h3 fw-bold mt-3">Atur Password Baru</h2>
                            <p class="text-muted small">Masukkan kode OTP dari Email/WA beserta password baru Anda di bawah ini.</p>
                        </div>

                        {{-- Menampilkan Pesan Sukses (Jika ada) --}}
                        @if (session('status'))
                            <div class="alert alert-info py-2 small mb-4">
                                <i class="bi bi-info-circle-fill me-2"></i> {{ session('status') }}
                            </div>
                        @endif

                        <form id="otp-form" action="{{ route('password.update') }}" method="POST">
                            @csrf
                            
                            {{-- Hidden inputs --}}
                            <input type="hidden" name="identifier" value="{{ $identifier ?? old('identifier') }}">
                            <input type="hidden" name="otp" id="otp-hidden" value="">

                            {{-- 1. Info Akun (Readonly) --}}
                            <div class="mb-3">
                                <label class="form-label fw-medium">Akun WhatsApp / Email</label>
                                <input type="text" value="{{ $identifier ?? old('identifier') }}" readonly class="form-control form-control-lg bg-light text-muted" style="font-size: 1rem;">
                            </div>

                            {{-- 2. Input 6 Digit OTP --}}
                            <div class="mb-3">
                                <label class="form-label fw-medium">Kode OTP (6 Digit)</label>
                                <div class="otp-wrapper" id="otp-container">
                                    <input type="text" class="otp-field" maxlength="1" autofocus autocomplete="off">
                                    <input type="text" class="otp-field" maxlength="1" autocomplete="off">
                                    <input type="text" class="otp-field" maxlength="1" autocomplete="off">
                                    <input type="text" class="otp-field" maxlength="1" autocomplete="off">
                                    <input type="text" class="otp-field" maxlength="1" autocomplete="off">
                                    <input type="text" class="otp-field" maxlength="1" autocomplete="off">
                                </div>
                            </div>

                            {{-- 3. Input Password Baru --}}
                            <div class="mb-3">
                                <label for="password" class="form-label fw-medium">Password Baru</label>
                                <div class="input-group">
                                    <input id="password" name="password" type="password" required class="form-control form-control-lg @error('password') is-invalid @enderror" placeholder="••••••••">
                                    <span class="input-group-text password-toggle-icon" onclick="togglePasswordVisibility('password', 'password-toggle-icon-1')">
                                        <i id="password-toggle-icon-1" class="bi bi-eye-fill"></i>
                                    </span>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- 4. Konfirmasi Password Baru --}}
                            <div class="mb-4">
                                <label for="password-confirm" class="form-label fw-medium">Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <input id="password-confirm" name="password_confirmation" type="password" required class="form-control form-control-lg" placeholder="••••••••">
                                    <span class="input-group-text password-toggle-icon" onclick="togglePasswordVisibility('password-confirm', 'password-toggle-icon-2')">
                                        <i id="password-toggle-icon-2" class="bi bi-eye-fill"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="d-grid mt-2">
                                <button type="button" id="btn-submit" class="btn btn-primary btn-lg fw-bold" style="border-radius: 0.75rem;">
                                    Simpan & Login
                                </button>
                            </div>
                            
                        </form>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Toggle Password Visibility
    function togglePasswordVisibility(inputId, iconId) {
        const passwordInput = document.getElementById(inputId);
        const passwordIcon = document.getElementById(iconId);
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            passwordIcon.classList.remove("bi-eye-fill");
            passwordIcon.classList.add("bi-eye-slash-fill");
        } else {
            passwordInput.type = "password";
            passwordIcon.classList.remove("bi-eye-slash-fill");
            passwordIcon.classList.add("bi-eye-fill");
        }
    }

    // Logic OTP
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.otp-field');
        const hiddenInput = document.getElementById('otp-hidden');
        const form = document.getElementById('otp-form');
        const btnSubmit = document.getElementById('btn-submit');
        
        // Logika Input & Auto-Focus OTP
        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
                if (e.target.value !== '' && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
                checkOTPCompletion();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                    inputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/[^a-zA-Z0-9]/g, '').toUpperCase().slice(0, 6); 
                let dataIndex = 0;
                inputs.forEach((inpt, i) => {
                    if (i >= index && dataIndex < pastedData.length) {
                        inpt.value = pastedData[dataIndex];
                        dataIndex++;
                    }
                });
                const lastFilledIndex = Math.min(index + pastedData.length - 1, inputs.length - 1);
                inputs[lastFilledIndex].focus();
                checkOTPCompletion();
            });
        });

        function checkOTPCompletion() {
            let otpValue = '';
            let allFilled = true;
            
            inputs.forEach(input => {
                otpValue += input.value;
                if (input.value === '') allFilled = false;
            });

            if (allFilled && otpValue.length === 6) {
                hiddenInput.value = otpValue; 
                inputs.forEach(input => {
                    input.classList.remove('error');
                    input.classList.add('success');
                });
            } else {
                inputs.forEach(input => input.classList.remove('success', 'error'));
            }
        }

        // Eksekusi Submit Form
        btnSubmit.addEventListener('click', function(e) {
            checkOTPCompletion();
            if(hiddenInput.value.length < 6) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Kode OTP Belum Lengkap',
                    text: 'Harap masukkan 6 digit kode OTP terlebih dahulu.',
                    confirmButtonColor: '#0d6efd'
                });
                return;
            }
            
            // Cek apakah password terisi
            const pass = document.getElementById('password').value;
            const passConfirm = document.getElementById('password-confirm').value;
            if(!pass || !passConfirm) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Password Kosong',
                    text: 'Harap masukkan password baru dan konfirmasinya.',
                    confirmButtonColor: '#0d6efd'
                });
                return;
            }

            form.submit();
        });

        // Cek error dari Backend Laravel (Misal OTP Salah)
        @if (session('error') || $errors->has('otp'))
            inputs.forEach(input => {
                input.classList.add('error'); 
            });
            
            let errorMessage = "{!! addslashes(session('error') ?? $errors->first('otp')) !!}";
            
            Swal.fire({
                icon: 'error',
                title: 'Verifikasi Gagal',
                text: errorMessage,
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#dc3545'
            });
        @endif
    });
</script>
@endpush