{{--
    File: resources/views/auth/login.blade.php
    Desain mengikuti referensi Split Layout (Kiri Branding, Kanan Form Penuh)
--}}

@extends('layouts.app')

@section('title', 'Login - Sancaka Express')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
<style>
    body, html {
        height: 100%;
        margin: 0;
        font-family: 'Poppins', sans-serif;
        background-color: #f0f4f8; /* Warna background luar yang lembut */
    }
    .auth-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 2rem 1rem;
        position: relative;
    }
    
    /* Kartu Utama */
    .auth-card {
        max-width: 950px;
        width: 100%;
        border: none;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }

    /* Sisi Kiri (Branding) */
    .auth-brand-side {
        background: linear-gradient(135deg, #dc3545 0%, #8b0000 100%);
        position: relative;
        overflow: hidden;
    }
    /* Efek garis/pattern abstrak sederhana */
    .auth-brand-side::before {
        content: '';
        position: absolute;
        top: -50%; left: -50%; width: 200%; height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 10%, transparent 10%), radial-gradient(circle, rgba(255,255,255,0.1) 10%, transparent 10%);
        background-size: 50px 50px;
        background-position: 0 0, 25px 25px;
        opacity: 0.5;
    }
    
    .brand-logo-container {
        position: absolute;
        top: 2rem;
        left: 2rem;
        z-index: 2;
    }

    /* Input & Tombol */
    .form-control {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
    }
    .form-control:focus {
        background-color: #fff;
        border-color: #dc3545;
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.15);
    }
    .btn-danger {
        background-color: #dc3545;
        border: none;
        padding: 0.85rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        border-radius: 0.5rem;
        transition: 0.3s;
    }
    .btn-danger:hover {
        background-color: #bd2130;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }
    .password-toggle-icon {
        position: absolute;
        top: 50%;
        right: 1.2rem;
        transform: translateY(-50%);
        cursor: pointer;
        color: #adb5bd;
    }
    
    /* Teks Copyright di bawah tengah seperti di referensi */
    .copyright-text {
        position: absolute;
        bottom: 1.5rem;
        width: 100%;
        text-align: center;
    }
</style>
@endpush

@section('content')
<div class="auth-wrapper">
    
    @php
        $formAction = request()->is('admin/*') ? route('admin.login') : route('login');
        $registerRoute = Route::has('register') ? route('register') : '#';
    @endphp

    <div class="card auth-card bg-white">
        <div class="row g-0 h-100">
            
            {{-- 1. SISI KIRI (Branding & Sambutan) --}}
            <div class="col-md-5 d-none d-md-flex auth-brand-side flex-column">
                <div class="brand-logo-container">
                    {{-- Logo putih (invert) --}}
                    <img src="{{ asset('storage/uploads/sancaka.png') }}" alt="Sancaka Express" style="max-height: 40px; filter: brightness(0) invert(1);" onerror="this.src='https://placehold.co/150x40?text=Sancaka'">
                </div>
                
                <div class="my-auto px-4 px-lg-5 text-center text-white position-relative z-2">
                    <p class="mb-2 fw-medium text-uppercase tracking-wider" style="letter-spacing: 1px; font-size: 0.9rem;">Nice to see you again</p>
                    <h2 class="fw-bold mb-4" style="font-size: 2.5rem;">WELCOME BACK</h2>
                    <hr class="w-25 mx-auto opacity-100 border-2 rounded">
                    <p class="mt-4 small text-white-50">Solusi pengiriman paket cepat, aman, dan terpercaya ke seluruh penjuru negeri.</p>
                </div>
            </div>

            {{-- 2. SISI KANAN (Form & Aksi) --}}
            <div class="col-12 col-md-7 p-4 p-md-5 d-flex flex-column justify-content-center">
                
                {{-- Logo untuk Mobile --}}
                <div class="text-center d-md-none mb-4">
                    <img src="{{ asset('storage/uploads/sancaka.png') }}" alt="Sancaka Express" style="max-height: 45px;">
                </div>

                <div class="text-center mb-4">
                    <h3 class="fw-bold text-danger">Login Account</h3>
                    <p class="text-muted small">Masuk untuk melanjutkan ke akun Anda.</p>
                </div>

                @if (session('status'))
                    <div class="alert alert-success py-2 small mb-3 text-center">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger py-2 small mb-3">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ $formAction }}" class="px-xl-4">
                    @csrf
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="email" name="login" placeholder="Email / WA" value="{{ old('email') }}" required autofocus>
                        <label for="email" class="text-muted">Email atau Nomor WhatsApp</label>
                    </div>

                    <div class="form-floating mb-3 position-relative">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password" class="text-muted">Password</label>
                        <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password')"></i>
                    </div>

                    {{-- Keamanan Captcha --}}
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <label class="form-label text-muted small mb-2 d-block text-center">Keamanan: Ketik karakter pada gambar</label>
                        <div class="text-center mb-2">
                            {!! captcha_img('flat') !!}
                        </div>
                        <input type="text" class="form-control text-center" name="captcha" placeholder="Masukkan karakter di atas" required autocomplete="off">
                    </div>

                    {{-- Opsi Bawah Form --}}
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                            <label class="form-check-label text-muted small" for="remember_me">Keep me signed in</label>
                        </div>
                        @if (!request()->is('admin/*'))
                            <a href="{{ $registerRoute }}" class="small text-danger text-decoration-none fw-medium">Already a member?</a>
                        @endif
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-danger btn-lg text-uppercase">Login</button>
                    </div>
                    
                    @if (Route::has('password.request') && !request()->is('admin/*'))
                        <div class="text-center mt-3">
                            <a href="https://tokosancaka.com/password/reset" class="small text-muted text-decoration-none">Lupa password?</a>
                        </div>
                    @endif
                
                </form>

            </div>

              <div class="text-center">
                    <p class="text-muted small mb-0">
                        &copy; {{ date('Y') }} Sancaka Express. All Rights Reserved.
                    </p>
              </div>

        </div>
    </div>

    
</div>
@endsection

@push('scripts')
<script>
    function togglePasswordVisibility(fieldId) {
        const input = document.getElementById(fieldId);
        const icon = input.parentElement.querySelector('.password-toggle-icon');
        if (input.type === "password") {
            input.type = "text";
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = "password";
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
</script>
@endpush