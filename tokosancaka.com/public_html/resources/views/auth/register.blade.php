{{--
    File: resources/views/auth/register.blade.php
    Desain Split Layout (Kiri Branding, Kanan Form Grid)
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
        background-color: #f0f4f8;
    }
    .auth-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 2rem 1rem;
        position: relative;
    }
    
    .auth-card {
        max-width: 1050px; /* Sedikit lebih lebar untuk form grid */
        width: 100%;
        border: none;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }

    .auth-brand-side {
        background: linear-gradient(135deg, #dc3545 0%, #8b0000 100%);
        position: relative;
        overflow: hidden;
    }
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
    
    /* MODIFIKASI: Style tombol aktif dan tidak aktif */
    .btn-danger {
        background-color: #dc3545;
        border: none;
        padding: 0.85rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        border-radius: 0.5rem;
        transition: 0.3s;
    }
    .btn-danger:hover:not(:disabled) {
        background-color: #bd2130;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }

    /* Tombol saat disabled (Abu-abu) */
    .btn:disabled, .btn[disabled] {
        background-color: #6c757d !important;
        border-color: #6c757d !important;
        color: #ffffff !important;
        opacity: 0.65;
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none !important;
    }
    
    /* Wrapper untuk menangkap klik saat tombol disabled */
    .disabled-btn-wrapper {
        cursor: not-allowed;
    }

    .password-toggle-icon {
        position: absolute;
        top: 50%;
        right: 1.2rem;
        transform: translateY(-50%);
        cursor: pointer;
        color: #adb5bd;
        z-index: 5;
    }
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
    
    <div class="card auth-card bg-white">
        <div class="row g-0 h-100">
            
            {{-- 1. SISI KIRI (Branding) --}}
            <div class="col-md-5 d-none d-md-flex auth-brand-side flex-column">
                <div class="brand-logo-container">
                    <img src="{{ asset('storage/uploads/sancaka.png') }}" alt="Sancaka Express" style="max-height: 40px; filter: brightness(0) invert(1);" onerror="this.src='https://placehold.co/150x40?text=Sancaka'">
                </div>
                
                <div class="my-auto px-4 px-lg-5 text-center text-white position-relative z-2">
                    <p class="mb-2 fw-medium text-uppercase tracking-wider" style="letter-spacing: 1px; font-size: 0.9rem;">Bergabung Bersama Kami</p>
                    <h2 class="fw-bold mb-4" style="font-size: 2.5rem;">BUAT AKUN</h2>
                    <hr class="w-25 mx-auto opacity-100 border-2 rounded">
                    <p class="mt-4 small text-white-50">Lengkapi data Anda dan mulai nikmati layanan pengiriman paket tercepat.</p>
                </div>
            </div>

            {{-- 2. SISI KANAN (Form Grid) --}}
            <div class="col-12 col-md-7 p-4 p-md-5 d-flex flex-column justify-content-center">
                
                <div class="text-center d-md-none mb-4">
                    <img src="{{ asset('storage/uploads/sancaka.png') }}" alt="Sancaka Express" style="max-height: 45px;">
                </div>

                <div class="text-center mb-4">
                    <h3 class="fw-bold text-danger">Register Account</h3>
                    <p class="text-muted small">Isi formulir di bawah ini dengan lengkap.</p>
                </div>

                {{-- Alert Info GPS untuk Pengguna --}}
                <div id="gps-status-alert" class="alert alert-warning py-2 small mb-3 text-center">
                    <i class="fas fa-map-marker-alt me-1"></i> Sistem mendeteksi keamanan. Mohon aktifkan/izinkan GPS perangkat Anda untuk mendaftar.
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger py-2 small mb-3">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('register') }}" method="POST" class="px-xl-2">
                    @csrf
                    
                    {{-- TAMBAHAN: Hidden Input untuk koordinat --}}
                    <input type="hidden" name="latitude" id="latitude" value="">
                    <input type="hidden" name="longitude" id="longitude" value="">

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" placeholder="Nama Lengkap" value="{{ old('nama_lengkap') }}" required>
                                <label for="nama_lengkap" class="text-muted">Nama Lengkap</label>
                            </div>
                        </div>
                        
                        <div class="col-12 col-sm-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="store_name" name="store_name" placeholder="Nama Toko" value="{{ old('store_name') }}" required>
                                <label for="store_name" class="text-muted">Nama Toko</label>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" placeholder="Email" value="{{ old('email') }}" required autocomplete="off">
                                <label for="email" class="text-muted">Alamat Email</label>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <div class="form-floating">
                                <input type="tel" class="form-control" id="no_wa" name="no_wa" placeholder="No WA" value="{{ old('no_wa') }}" required>
                                <label for="no_wa" class="text-muted">Nomor WhatsApp</label>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <div class="form-floating position-relative">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required autocomplete="new-password">
                                <label for="password" class="text-muted">Password</label>
                                <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password')"></i>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <div class="form-floating position-relative">
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Konfirmasi Password" required autocomplete="new-password">
                                <label for="password_confirmation" class="text-muted">Konfirmasi Password</label>
                                <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password_confirmation')"></i>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mb-4">
                        <a href="{{ route('login') }}" class="small text-danger text-decoration-none fw-medium">Already have an account? Login</a>
                    </div>

                    {{-- MODIFIKASI: Tombol Submit di-disabled secara default --}}
                    <div class="d-grid mb-3 disabled-btn-wrapper" onclick="checkGpsClick()">
                        <button type="submit" id="btn-submit-manual" class="btn btn-danger btn-lg text-uppercase" disabled>Register</button>
                    </div>

                    {{-- ========================================== --}}
                    {{-- TAMBAHAN: TOMBOL DAFTAR GOOGLE --}}
                    {{-- ========================================== --}}
                    <div class="d-flex align-items-center mb-3">
                        <hr class="flex-grow-1 text-muted opacity-25">
                        <span class="mx-2 text-muted small">ATAU</span>
                        <hr class="flex-grow-1 text-muted opacity-25">
                    </div>
                    {{-- MODIFIKASI: Tombol Google di-disabled secara default --}}
                    <div class="d-grid mb-4 disabled-btn-wrapper" onclick="checkGpsClick()">
                        <a href="{{ route('register.google') }}" id="btn-submit-google" class="btn btn-outline-dark btn-lg d-flex justify-content-center align-items-center disabled" role="button" aria-disabled="true">
                            <img src="https://tokosancaka.com/public/assets/google.png" alt="Google Logo" style="width: 24px; height: 24px; object-fit: contain;" class="me-2"> 
                            Daftar dengan Google
                        </a>
                    </div>
                    {{-- ========================================== --}}

                </form>

                  <div class="text-center mt-3">
                    <p class="text-muted small mb-0">
                        &copy; {{ date('Y') }} Sancaka Express. All Rights Reserved.
                    </p>
                  </div>

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

    // Melacak status aktivasi GPS
    let isGpsActive = false;

    // Fungsi memunculkan alert jika tombol abu-abu diklik
    function checkGpsClick() {
        if (!isGpsActive) {
            alert("Akses Ditolak! Anda wajib mengaktifkan GPS dan mengizinkan lokasi pada browser/perangkat Anda sebelum dapat menekan tombol Register.");
            requestLocation();
        }
    }

    // Fungsi utama meminta izin lokasi/GPS
    function requestLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    isGpsActive = true;
                    
                    // Masukkan koordinat ke hidden input
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                    
                    // Aktifkan tombol manual register
                    const btnManual = document.getElementById('btn-submit-manual');
                    if(btnManual) btnManual.removeAttribute('disabled');
                    
                    // Aktifkan tombol Google register & pasang parameter koordinat
                    const btnGoogle = document.getElementById('btn-submit-google');
                    if(btnGoogle) {
                        btnGoogle.classList.remove('disabled');
                        btnGoogle.removeAttribute('aria-disabled');
                        
                        // Dinamis menyelipkan koordinat ke URL redirect Google agar terbaca di Controller intermediate
                        let baseUrl = "{{ route('register.google') }}";
                        btnGoogle.href = baseUrl + "?latitude=" + position.coords.latitude + "&longitude=" + position.coords.longitude;
                    }
                    
                    // Ubah box status alert menjadi sukses hijau
                    const statusAlert = document.getElementById('gps-status-alert');
                    if(statusAlert) {
                        statusAlert.classList.replace('alert-warning', 'alert-success');
                        statusAlert.innerHTML = '<i class="fas fa-check-circle me-1"></i> Keamanan tervalidasi: GPS Berhasil Aktif. Silakan melanjutkan pendaftaran.';
                    }
                },
                function(error) {
                    isGpsActive = false;
                    console.warn("Akses GPS ditolak/bermasalah: ", error.message);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        } else {
            alert("Browser Anda tidak mendukung fitur deteksi lokasi keamanan (Geolocation).");
        }
    }

    // Trigger izin GPS sesaat setelah dokumen selesai diload
    document.addEventListener("DOMContentLoaded", function() {
        requestLocation();
    });
</script>
@endpush