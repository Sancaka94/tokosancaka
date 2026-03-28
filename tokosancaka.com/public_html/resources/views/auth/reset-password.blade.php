{{--
    File: resources/views/auth/reset-password.blade.php
    Halaman reset password yang senada dengan desain login Sancaka Express.
--}}

@extends('layouts.app')

@section('title', 'Reset Password - Sancaka Express')

@push('styles')
{{-- Menyisipkan style kustom dan library ikon --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
<style>
    body, html {
        height: 100%;
        margin: 0;
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
    }
    .auth-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 2rem 1rem;
    }
    .auth-card {
        max-width: 500px;
        width: 100%;
        border: none;
        margin-top: 5px;
    }
    .auth-logo {
        max-height: 50px;
    }
    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
        padding: 0.75rem 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .btn-danger:hover {
        background-color: #c82333;
        border-color: #bd2130;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }
    .form-control:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
    }
    .password-toggle-icon {
        position: absolute;
        top: 50%;
        right: 1rem;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
    }
</style>
@endpush

@section('content')
<div class="auth-wrapper">
    <div class="container bg-white rounded-4 shadow p-4 p-lg-5 auth-card">
        <div class="row">
            <div class="col-12">
                <div class="text-center mb-4">
                    <a href="{{ url('/') }}">
                        <img src="{{ asset('storage/uploads/sancaka.png') }}" alt="Logo Sancaka Express" class="auth-logo mb-2" onerror="this.src='https://placehold.co/150x50?text=Sancaka'">
                    </a>
                    <h3 class="fw-bold">Buat Password Baru</h3>
                    <p class="text-muted small">Silakan masukkan password baru untuk akun Anda.</p>
                </div>

                {{-- Menampilkan Error Validasi (Standar Breeze) --}}
                @if ($errors->any())
                    <div class="alert alert-danger py-2 small mb-3">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.store') }}">
                    @csrf

                    {{-- Token Reset Password (Hidden) --}}
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    {{-- Email Address (Biasanya otomatis terisi dari link email) --}}
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="Email Anda" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
                        <label for="email">Alamat Email</label>
                    </div>

                    {{-- Password Baru --}}
                    <div class="form-floating mb-3 position-relative">
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Password Baru" required autocomplete="new-password">
                        <label for="password">Password Baru</label>
                        <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password')"></i>
                    </div>

                    {{-- Konfirmasi Password Baru --}}
                    <div class="form-floating mb-4 position-relative">
                        <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation" placeholder="Konfirmasi Password" required autocomplete="new-password">
                        <label for="password_confirmation">Konfirmasi Password Baru</label>
                        <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password_confirmation')"></i>
                    </div>

                    {{-- Submit Button --}}
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-danger btn-lg">Simpan Password Baru</button>
                    </div>
                </form>
            </div>
        </div>
        <p class="text-center text-muted small mt-5 mb-0">&copy; {{ date('Y') }} Sancaka Express. All Rights Reserved.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function togglePasswordVisibility(fieldId) {
        const input = document.getElementById(fieldId);
        // Mengambil icon yang berada tepat setelah input di dalam parent yang sama
        const icon = input.parentElement.querySelector('.password-toggle-icon');

        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = "password";
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
@endpush
