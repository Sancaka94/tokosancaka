{{--
    File: resources/views/auth/login.blade.php

    Ini adalah halaman formulir login untuk semua pengguna.
--}}

@extends('layouts.app')

@push('styles')
{{-- Menyisipkan style kustom dan library ikon --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
<style>
    body, html {
        height: 100%;
        margin: 0;
        font-family: 'Poppins', sans-serif; /* Menggunakan font Poppins */
        background-color: #f8f9fa;
    }
    .auth-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 2rem 1rem;
    }
    /* Mengubah max-width auth-card agar lebih fokus ke formulir */
    .auth-card {
        max-width: 500px; /* Lebar lebih kecil untuk fokus ke form */
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
    /* Menghapus semua style terkait partner-logos-grid dan partner-logo */
    
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
    {{-- Card login tunggal --}}
    <div class="container bg-white rounded-4 shadow p-4 p-lg-5 auth-card">
        <div class="row">
            {{-- Kolom Form Login Tunggal (Hanya 1 kolom, mengambil 100% lebar card) --}}
            <div class="col-12">
                <div class="text-center mb-4">
                    <a href="{{ url('/') }}">
                        <img src="{{ asset('storage/uploads/sancaka.png') }}" alt="Logo Sancaka Express" class="auth-logo mb-2">
                    </a>
                    <h3 class="fw-bold">Selamat Datang Kembali</h3>
                    <p class="text-muted">Masuk untuk melanjutkan ke akun Anda.</p>
                </div>

                {{-- Menampilkan error validasi --}}
                @if (session('success'))
                    <div class="alert alert-success py-2 small mb-2">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger py-2 small mb-2">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Menentukan action form secara dinamis --}}
                @php
                    $formAction = request()->is('admin/*') ? route('admin.login') : route('login');
                    $passwordRequestRoute = request()->is('admin/*') ? '#' : route('password.request');
                    $registerRoute = request()->is('admin/*') ? '#' : route('register');
                @endphp

                <form action="{{ $formAction }}" method="POST">
                    @csrf
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="login" name="login" placeholder="Email atau Nomor WhatsApp" value="{{ old('login') }}" required autofocus>
                        <label for="login">Email atau Nomor WhatsApp</label>
                    </div>

                    <div class="form-floating mb-3 position-relative">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password">Password</label>
                        <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password')"></i>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">
                                Ingat Saya
                            </label>
                        </div>
                        <a href="{{ $passwordRequestRoute }}" class="small text-danger text-decoration-none">Lupa password?</a>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-danger btn-lg">Masuk</button>
                    </div>

                    {{-- Hanya tampilkan link register di halaman login customer --}}
                    @if (!request()->is('admin/*'))
                        <p class="text-center mt-4 mb-0">
                            Belum punya akun? <a href="{{ $registerRoute }}" class="fw-bold text-danger text-decoration-none">Daftar di sini</a>
                        </p>
                    @endif
                </form>
            </div>
            
            {{-- Menghapus Kolom Logo Partner (col-lg-6 text-center d-none d-lg-block) dan semua isinya --}}
            
        </div>
        <p class="text-center text-muted small mt-5 mb-0">&copy; {{ date('Y') }} Sancaka Express. All Rights Reserved.</p>
    </div>
</div>
@endsection

@push('scripts')
{{-- Menyisipkan script ke bagian bawah <body> di layout --}}
<script>
    function togglePasswordVisibility(fieldId) {
        const input = document.getElementById(fieldId);
        const icon = input.nextElementSibling;
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