{{-- 
    File: resources/views/auth/passwords/email.blade.php 
--}}
@extends('layouts.app')

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
    }
    .auth-card {
        max-width: 600px; 
        width: 100%;
        border: none;
        border-radius: 1rem;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
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
    .btn-danger {
        background-color: #dc3545;
        border: none;
        padding: 0.85rem;
        font-weight: 600;
        border-radius: 0.5rem;
        transition: 0.3s;
    }
    .btn-danger:hover:not(:disabled) {
        background-color: #bd2130;
        transform: translateY(-2px);
    }
    .btn:disabled, .btn[disabled] {
        background-color: #6c757d !important;
        border-color: #6c757d !important;
        color: #ffffff !important;
        opacity: 0.65;
        cursor: not-allowed;
    }
    .disabled-btn-wrapper {
        cursor: not-allowed;
    }
</style>
@endpush


@section('content')
<div class="auth-wrapper">
    <div class="card auth-card bg-white p-4 p-md-5">
        
        <div class="text-center mb-4">
            <img src="{{ asset('storage/uploads/sancaka.png') }}" alt="Sancaka Express" style="max-height: 50px;" class="mb-3">
            <h3 class="fw-bold text-danger">Lupa Password?</h3>
            <p class="text-muted small">Masukkan Email Anda. Kami akan mengirimkan kode OTP untuk mengatur ulang password.</p>
        </div>

        {{-- Alert Info GPS --}}
        <div id="gps-status-alert" class="alert alert-warning py-2 small mb-3 text-center">
            <i class="fas fa-map-marker-alt me-1"></i> Sistem mendeteksi keamanan. Mohon aktifkan/izinkan GPS perangkat Anda untuk melanjutkan.
        </div>

        @if (session('status'))
            <div class="alert alert-success py-2 small mb-3 text-center">
                <i class="fas fa-check-circle me-1"></i> {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger py-2 small mb-3">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('customer.password.email') }}" method="POST">
            @csrf
            
            {{-- Form Koordinat GPS (Hidden / Readonly) --}}
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <input type="text" class="form-control form-control-sm text-center" id="latitude" name="latitude" placeholder="Latitude" readonly>
                </div>
                <div class="col-6">
                    <input type="text" class="form-control form-control-sm text-center" id="longitude" name="longitude" placeholder="Longitude" readonly>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-floating">
                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" value="{{ old('email') }}" required autocomplete="email">
                    <label for="email" class="text-muted">Alamat Email</label>
                </div>
            </div>

            {{-- Keamanan Captcha --}}
            <div class="mb-3 p-3 bg-light rounded-3 border">
                <label class="form-label text-muted small mb-2 d-block text-center">Keamanan: Ketik karakter pada gambar</label>
                <div class="text-center mb-2 captcha-wrapper">
                    {!! captcha_img('flat') !!}
                </div>
                <input type="text" class="form-control text-center" name="captcha" placeholder="Masukkan karakter di atas" required autocomplete="off">
            </div>

            {{-- WIDGET CLOUDFLARE TURNSTILE --}}
            <div class="mb-4 d-flex justify-content-center">
                <div class="cf-turnstile" data-sitekey="{{ env('TURNSTILE_SITE_KEY') }}" data-callback="onTurnstileSuccess"></div>
            </div>

            <div class="d-grid mb-4 disabled-btn-wrapper" onclick="checkGpsClick()">
                <button type="submit" id="btn-submit-manual" class="btn btn-danger btn-lg text-uppercase" disabled>Kirim Kode OTP</button>
            </div>

            <div class="text-center">
                <a href="{{ route('customer.login') }}" class="text-decoration-none text-muted small">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Login
                </a>
            </div>
        </form>
        
        <div class="text-center mt-4">
            <p class="text-muted small mb-0">&copy; {{ date('Y') }} Sancaka Express. All Rights Reserved.</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<script>
    let isGpsActive = false;
    let isTurnstileSuccess = false;

    function checkAllValidations() {
        if (isGpsActive && isTurnstileSuccess) {
            const btnManual = document.getElementById('btn-submit-manual');
            if(btnManual) btnManual.removeAttribute('disabled');
            
            const statusAlert = document.getElementById('gps-status-alert');
            if(statusAlert) {
                statusAlert.classList.replace('alert-warning', 'alert-success');
                statusAlert.innerHTML = '<i class="fas fa-check-circle me-1"></i> Keamanan tervalidasi: GPS & Cloudflare Berhasil.';
            }
        }
    }

    function onTurnstileSuccess(token) {
        isTurnstileSuccess = true;
        checkAllValidations();
    }

    function checkGpsClick() {
        if (!isGpsActive || !isTurnstileSuccess) {
            let alertMsg = "Akses Ditolak!\n";
            if (!isGpsActive) alertMsg += "- Anda wajib mengaktifkan dan mengizinkan GPS lokasi.\n";
            if (!isTurnstileSuccess) alertMsg += "- Anda wajib menyelesaikan verifikasi keamanan (Cloudflare).\n";
            
            alert(alertMsg);
            if (!isGpsActive) requestLocation();
        }
    }

    function requestLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    isGpsActive = true;
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                    checkAllValidations();
                },
                function(error) {
                    isGpsActive = false;
                    console.warn("Akses GPS ditolak/bermasalah: ", error.message);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        } else {
            alert("Browser Anda tidak mendukung fitur deteksi lokasi keamanan (Geolocation).");
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        requestLocation();
    });
</script>
@endpush