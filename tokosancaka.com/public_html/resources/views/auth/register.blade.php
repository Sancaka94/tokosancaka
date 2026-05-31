{{--
    File: resources/views/auth/register.blade.php
    Ini adalah halaman formulir pendaftaran custom (Responsive Grid)
    (Versi Full Responsive - Input di Kiri, Aksi di Kanan)
--}}

@extends('layouts.app')

@section('title', 'Daftar - Sancaka Express')

@push('styles')
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
        max-width: 1000px; /* Lebar seragam dengan halaman login */
        width: 100%;
        border: none;
        border-radius: 1.25rem;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }
    .auth-action-side {
        background-color: #fcfcfc;
        border-left: 1px solid #f0f0f0;
    }
    /* ------------------------------------------ */
    
    .auth-logo {
        max-height: 55px;
    }
    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
        padding: 0.85rem 1rem;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        border-radius: 0.5rem;
    }
    .btn-danger:hover {
        background-color: #c82333;
        border-color: #bd2130;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
    }
    .form-control {
        border-radius: 0.5rem;
    }
    .form-control:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
    }
    .password-toggle-icon {
        position: absolute;
        top: 50%;
        right: 1.2rem;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
        z-index: 10;
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
    
    {{-- Kartu Utama --}}
    <div class="card auth-card-split bg-white">
        
        {{-- Form membungkus seluruh grid --}}
        <form action="{{ route('register') }}" method="POST" class="m-0">
            @csrf
            <div class="row g-0">
                
                {{-- 1. SISI KIRI (Logo & Form Input) --}}
                <div class="col-12 col-md-8 p-4 p-lg-5">
                    
                    <div class="text-center text-md-start mb-4 pb-2">
                        <a href="{{ url('/') }}">
                            <img src="{{ asset('storage/uploads/sancaka.png') }}" alt="Sancaka Express Logo" class="auth-logo mb-3" onerror="this.src='https://placehold.co/150x50?text=Sancaka'">
                        </a>
                        <h2 class="fw-bold text-dark">Buat Akun Baru</h2>
                        <p class="text-secondary">Daftar sekarang dan nikmati kemudahan pengiriman paket Anda.</p>
                    </div>

                    {{-- Alert Sukses --}}
                    @if (session('success'))
                        <div class="alert alert-success d-flex align-items-center mb-4 rounded-3 border-0 shadow-sm" role="alert">
                            <i class="fas fa-check-circle fs-4 me-3 text-success"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                    @endif

                    {{-- Alert Error --}}
                    @if ($errors->any())
                        <div class="alert alert-danger mb-4 rounded-3 border-0 shadow-sm">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-exclamation-circle fs-5 me-2 text-danger"></i>
                                <strong>Mohon periksa kembali data Anda:</strong>
                            </div>
                            <ul class="mb-0 small ps-4">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    {{-- Mulai Grid Form --}}
                    <div class="row g-3 mb-2">
                        
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" placeholder="Nama Lengkap" value="{{ old('nama_lengkap') }}" required>
                                <label for="nama_lengkap"><i class="fas fa-user me-2 text-muted"></i>Nama Lengkap</label>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="store_name" name="store_name" placeholder="Nama Toko" value="{{ old('store_name') }}" required>
                                <label for="store_name"><i class="fas fa-store me-2 text-muted"></i>Nama Toko</label>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" placeholder="nama@contoh.com" value="{{ old('email') }}" required>
                                <label for="email"><i class="fas fa-envelope me-2 text-muted"></i>Alamat Email</label>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input type="tel" class="form-control" id="no_wa" name="no_wa" placeholder="08xxxxxxxxxx" value="{{ old('no_wa') }}" required>
                                <label for="no_wa"><i class="fab fa-whatsapp me-2 text-muted"></i>Nomor WhatsApp Aktif</label>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="form-floating position-relative">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                <label for="password"><i class="fas fa-lock me-2 text-muted"></i>Password</label>
                                <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password')"></i>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="form-floating position-relative">
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Konfirmasi Password" required>
                                <label for="password_confirmation"><i class="fas fa-check-double me-2 text-muted"></i>Konfirmasi Password</label>
                                <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password_confirmation')"></i>
                            </div>

                          {{-- Copyright di posisi Kanan Bawah --}}
                        <p class="text-muted small mb-0 copyright-text text-end">&copy; {{ date('Y') }} Sancaka Express. All Rights Reserved.</p>
                        </div>

                    

                    </div>
                    {{-- Akhir Grid Form --}}


                </div>

                {{-- 2. SISI KANAN (Aksi: Register & Login Link) --}}
                <div class="col-12 col-md-4 auth-action-side p-4 p-lg-5 d-flex flex-column justify-content-center text-center">
                    
                    <div class="mb-4 mt-auto">
                        <i class="fas fa-rocket fa-3x text-danger mb-3 opacity-75"></i>
                        <h5 class="fw-bold mb-3">Siap Bergabung?</h5>
                        <p class="text-muted small">Pastikan semua data Anda sudah terisi dengan benar sebelum melanjutkan.</p>
                    </div>

                    {{-- Submit Button --}}
                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-danger btn-lg shadow-sm">Daftar Sekarang</button>
                    </div>

                    {{-- Login Link --}}
                    <p class="text-center mb-0 mt-auto text-secondary">
                        Sudah punya akun? <br>
                        <a href="{{ route('login') }}" class="fw-bold text-danger text-decoration-none">Masuk di sini</a>
                    </p>

                </div>
                {{-- Akhir Sisi Kanan --}}

            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function togglePasswordVisibility(fieldId) {
        const input = document.getElementById(fieldId);
        // Memperbaiki seleksi icon agar akurat
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