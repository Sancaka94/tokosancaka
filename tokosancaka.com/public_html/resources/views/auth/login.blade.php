{{--
    File: resources/views/auth/login.blade.php
    Ini adalah halaman formulir login custom yang kompatibel dengan Breeze.
    (Versi Full Responsive Horizontal/Vertical)
--}}

@extends('layouts.app')

@section('title', 'Login - Sancaka Express')

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
    
    /* ---- STYLE BARU UNTUK KARTU RESPONSIVE ---- */
    .auth-card-split {
        max-width: 900px; /* Lebar maksimal kartu saat mendatar di desktop */
        width: 100%;
        border: none;
        border-radius: 1.25rem;
        overflow: hidden; /* Memastikan sudut tumpul tidak tertabrak warna background */
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }
    .auth-brand-side {
        background: linear-gradient(135deg, #dc3545 0%, #8b0000 100%); /* Warna Merah Sancaka */
        color: white;
    }
    .auth-logo-mobile {
        max-height: 50px;
    }
    /* ------------------------------------------ */

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
    
    {{-- Kartu Utama dengan Grid (row g-0 menghilangkan jarak antar kolom) --}}
    <div class="card auth-card-split bg-white">
        <div class="row g-0">
            
            {{-- 1. SISI KIRI (Branding Banner) --}}
            {{-- col-md-6 artinya lebarnya 50% di tablet/desktop. d-none d-md-flex artinya disembunyikan di HP --}}
            <div class="col-md-6 auth-brand-side d-none d-md-flex flex-column justify-content-center align-items-center p-5 text-center">
                <img src="{{ asset('storage/uploads/sancaka.png') }}" alt="Logo Sancaka Express" class="mb-4" style="max-height: 80px; filter: brightness(0) invert(1);" onerror="this.style.display='none'">
                <h2 class="fw-bold mb-3">Sancaka Express</h2>
                <p class="mb-0 text-white-50">Solusi pengiriman paket cepat, aman, dan terpercaya ke seluruh penjuru negeri.</p>
            </div>

            {{-- 2. SISI KANAN (Form Login) --}}
            {{-- col-12 artinya 100% lebar di HP, col-md-6 artinya 50% di tablet/desktop --}}
            <div class="col-12 col-md-6 p-4 p-lg-5">
                
                <div class="text-center mb-4">
                    {{-- Logo ini HANYA muncul di HP (d-md-none) karena di Desktop logonya ada di sisi kiri --}}
                    <a href="{{ url('/') }}" class="d-md-none">
                        <img src="{{ asset('storage/uploads/sancaka.png') }}" alt="Logo Sancaka Express" class="auth-logo-mobile mb-3" onerror="this.src='https://placehold.co/150x50?text=Sancaka'">
                    </a>
                    <h3 class="fw-bold">Selamat Datang Kembali</h3>
                    <p class="text-muted">Masuk untuk melanjutkan ke akun Anda.</p>
                </div>

                {{-- Menampilkan Session Status (Standar Breeze) --}}
                @if (session('status'))
                    <div class="alert alert-success py-2 small mb-3">
                        {{ session('status') }}
                    </div>
                @endif

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

                {{-- Menentukan action form --}}
                @php
                    $formAction = request()->is('admin/*') ? route('admin.login') : route('login');
                    $passwordRequestRoute = Route::has('password.request') ? route('password.request') : '#';
                    $registerRoute = Route::has('register') ? route('register') : '#';
                @endphp

                <form method="POST" action="{{ $formAction }}">
                    @csrf

                    {{-- Email Address --}}
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control @error('email') is-invalid @enderror" id="email" name="login" placeholder="Email atau Nomor WhatsApp" value="{{ old('email') }}" required autofocus>
                        <label for="email">Email atau Nomor WhatsApp</label>
                    </div>

                    {{-- Password --}}
                    <div class="form-floating mb-3 position-relative">
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Password" required autocomplete="current-password">
                        <label for="password">Password</label>
                        <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password')"></i>
                    </div>

                    {{-- Captcha Gambar Mews --}}
                    <div class="mb-3">
                        <label for="captcha" class="form-label text-muted small d-block">
                            Keamanan: Ketik karakter pada gambar di bawah
                        </label>
                        
                        <div class="mb-3 text-center">
                            {!! captcha_img('flat') !!}
                        </div>

                        <input type="text" class="form-control @error('captcha') is-invalid @enderror" id="captcha" name="captcha" placeholder="Masukkan karakter pada gambar" required autocomplete="off">
                        
                        @error('captcha')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- Remember Me & Forgot Password --}}
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                            <label class="form-check-label" for="remember_me">
                                Ingat Saya
                            </label>
                        </div>
                        @if (Route::has('password.request') && !request()->is('admin/*'))
                            <a href="https://tokosancaka.com/password/reset" class="small text-danger text-decoration-none">Lupa password?</a>
                        @endif
                    </div>

                    {{-- Submit Button --}}
                    <div class="d-grid">
                        <button type="submit" class="btn btn-danger btn-lg">Masuk</button>
                    </div>

                    {{-- Register Link --}}
                    @if (!request()->is('admin/*'))
                        <p class="text-center mt-4 mb-0">
                            Belum punya akun? <a href="{{ $registerRoute }}" class="fw-bold text-danger text-decoration-none">Daftar di sini</a>
                        </p>
                    @endif
                </form>
            </div>
            {{-- Akhir Sisi Kanan --}}

        </div>
    </div>
    
    <p class="text-center text-muted small mt-4 mb-0 position-absolute bottom-0 mb-3">&copy; {{ date('Y') }} Sancaka Express. All Rights Reserved.</p>
</div>
@endsection

@push('scripts')
<script>
    function togglePasswordVisibility(fieldId) {
        const input = document.getElementById(fieldId);
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