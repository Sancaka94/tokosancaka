{{--
    File: resources/views/auth/login.blade.php
    Ini adalah halaman formulir login custom yang kompatibel dengan Breeze.
    (Versi Full Responsive - Input di Kiri, Aksi di Kanan)
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
        position: relative;
    }
    .auth-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 2rem 1rem;
    }
    
    /* ---- STYLE KARTU RESPONSIVE ---- */
    .auth-card-split {
        max-width: 1000px; /* Diperlebar sedikit agar form dan tombol tidak terlalu sempit */
        width: 100%;
        border: none;
        border-radius: 1.25rem;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }
    .auth-action-side {
        background-color: #fcfcfc; /* Warna latar sedikit berbeda untuk membedakan sisi aksi */
        border-left: 1px solid #f0f0f0;
    }
    .auth-logo {
        max-height: 60px;
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
    
    /* Mengatur posisi copyright di kanan bawah */
    .copyright-text {
        position: absolute;
        bottom: 1rem;
        right: 2rem;
    }
    
    @media (max-width: 768px) {
        .auth-action-side {
            border-left: none;
            border-top: 1px solid #f0f0f0;
        }
        .copyright-text {
            position: static;
            text-align: center !important;
            margin-top: 2rem;
            padding-bottom: 1rem;
        }
    }
</style>
@endpush

@section('content')
<div class="auth-wrapper">
    
    {{-- Menentukan action form --}}
    @php
        $formAction = request()->is('admin/*') ? route('admin.login') : route('login');
        $passwordRequestRoute = Route::has('password.request') ? route('password.request') : '#';
        $registerRoute = Route::has('register') ? route('register') : '#';
    @endphp

    {{-- Kartu Utama --}}
    <div class="card auth-card-split bg-white">
        {{-- Form membungkus seluruh grid agar elemen di kiri dan kanan tetap dalam 1 proses submit --}}
        <form method="POST" action="{{ $formAction }}" class="m-0">
            @csrf
            <div class="row g-0">
                
                {{-- 1. SISI KIRI (Logo & Form Input) --}}
                <div class="col-12 col-md-7 p-4 p-lg-5">
                    
                    <div class="text-center text-md-start mb-4">
                        <a href="{{ url('/') }}">
                            {{-- Filter invert dihapus karena sekarang berada di background putih --}}
                            <img src="{{ asset('storage/uploads/sancaka.png') }}" alt="Logo Sancaka Express" class="auth-logo mb-3" onerror="this.src='https://placehold.co/150x50?text=Sancaka'">
                        </a>
                        <h3 class="fw-bold">Selamat Datang Kembali</h3>
                        <p class="text-muted">Masuk untuk melanjutkan ke akun Anda.</p>
                    </div>

                    {{-- Menampilkan Session Status --}}
                    @if (session('status'))
                        <div class="alert alert-success py-2 small mb-3">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{-- Menampilkan Error Validasi --}}
                    @if ($errors->any())
                        <div class="alert alert-danger py-2 small mb-3">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

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
                        
                        <div class="mb-3">
                            {!! captcha_img('flat') !!}
                        </div>

                        <input type="text" class="form-control @error('captcha') is-invalid @enderror" id="captcha" name="captcha" placeholder="Masukkan karakter pada gambar" required autocomplete="off">
                        
                        @error('captcha')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                {{-- 2. SISI KANAN (Aksi: Ceklis, Lupa Password, Submit, Register) --}}
                <div class="col-12 col-md-5 auth-action-side p-4 p-lg-5 d-flex flex-column justify-content-center">
                    
                    <div class="mb-4">
                        {{-- Remember Me --}}
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                            <label class="form-check-label" for="remember_me">
                                Ingat Saya
                            </label>
                        </div>

                        {{-- Forgot Password --}}
                        @if (Route::has('password.request') && !request()->is('admin/*'))
                            <a href="https://tokosancaka.com/password/reset" class="small text-danger text-decoration-none d-block">Lupa password?</a>
                        @endif
                    </div>

                    {{-- Submit Button --}}
                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-danger btn-lg">Masuk</button>
                    </div>

                    {{-- Register Link --}}
                    @if (!request()->is('admin/*'))
                        <p class="text-center mb-0 mt-auto">
                            Belum punya akun? <br>
                            <a href="{{ $registerRoute }}" class="fw-bold text-danger text-decoration-none">Daftar di sini</a>
                        </p>
                    @endif

                </div>
                {{-- Akhir Sisi Kanan --}}

            </div>
        </form>
    </div>
    
    {{-- Copyright di posisi Kanan Bawah --}}
    <p class="text-muted small mb-0 copyright-text text-end">&copy; {{ date('Y') }} Sancaka Express. All Rights Reserved.</p>
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