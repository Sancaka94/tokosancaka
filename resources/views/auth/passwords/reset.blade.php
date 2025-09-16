{{--
    File: resources/views/auth/passwords/reset.blade.php
    Ini adalah halaman formulir untuk memasukkan password baru.
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
                            <p class="text-muted">Masukkan password baru Anda di bawah ini.</p>
                        </div>

                        {{-- ✅ PERBAIKAN: Menggunakan route() dan menghapus index.php --}}
                        <form action="{{ route('password.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">

                            <div class="mb-3">
                                <label for="email" class="form-label fw-medium">Alamat Email</label>
                                <input id="email" name="email" type="email" value="{{ $email ?? old('email') }}" required readonly class="form-control form-control-lg bg-light @error('email') is-invalid @enderror">
                                @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-medium">Password Baru</label>
                                <div class="input-group">
                                    <input id="password" name="password" type="password" required class="form-control form-control-lg @error('password') is-invalid @enderror" placeholder="••••••••">
                                    <span class="input-group-text password-toggle-icon" onclick="togglePasswordVisibility('password', 'password-toggle-icon-1')">
                                        <i id="password-toggle-icon-1" class="bi bi-eye-fill"></i>
                                    </span>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="password-confirm" class="form-label fw-medium">Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <input id="password-confirm" name="password_confirmation" type="password" required class="form-control form-control-lg" placeholder="••••••••">
                                    <span class="input-group-text password-toggle-icon" onclick="togglePasswordVisibility('password-confirm', 'password-toggle-icon-2')">
                                        <i id="password-toggle-icon-2" class="bi bi-eye-fill"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg-custom">
                                    Simpan Password Baru
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
<script>
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
</script>
@endpush
