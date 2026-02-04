@extends('layouts.app')

@push('styles')
{{-- Menambahkan CDN Bootstrap 5 dan Bootstrap Icons --}}
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
    .btn-lg-custom {
        padding: 0.85rem 1.25rem;
        border-radius: 0.75rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-lg-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,.15);
    }
</style>
@endpush

@section('content')

{{-- ✅ PERBAIKAN: Menambahkan meta refresh sebagai metode redirect utama --}}
<meta http-equiv="refresh" content="3;url={{ route('customer.dashboard') }}">

<div class="gradient-background mt-4">
    <div class="container">
        <div class="row vh-100 justify-content-center align-items-center">
            <div class="col-11 col-sm-8 col-md-6 col-lg-5 col-xl-4">
                <div class="card card-custom text-center">
                    <div class="card-body p-4 p-sm-5">
                        
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        
                        <h2 class="h3 fw-bold mt-3">Berhasil!</h2>
                        
                        {{-- Menampilkan Notifikasi Flash (Alert) --}}
                        @if (session('status'))
                            <div class="alert alert-success mt-3">
                                {{ session('status') }}
                            </div>
                        @endif

                        <p class="text-muted mt-2">
                            Anda akan diarahkan ke dashboard dalam 
                            <strong id="countdown">3</strong> detik...
                        </p>
                        
                        <div class="d-grid mt-4">
                            <a href="{{ route('customer.dashboard') }}" class="btn btn-primary btn-lg-custom">
                                Lanjutkan ke Dashboard
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Menambahkan CDN Bootstrap 5 JS Bundle --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
{{-- Script untuk countdown visual (redirect utama ditangani oleh meta tag) --}}
<script>
    (function() {
        let seconds = 3;
        const countdownElement = document.getElementById('countdown');
        
        const interval = setInterval(function() {
            seconds--;
            if (countdownElement) {
                countdownElement.textContent = Math.max(0, seconds); // Pastikan tidak menampilkan angka negatif
            }
            if (seconds <= 0) {
                clearInterval(interval);
                // Redirect JavaScript sebagai fallback jika meta tag gagal
                window.location.href = "{{ route('customer.dashboard') }}";
            }
        }, 1000);
    })();
</script>
@endpush
