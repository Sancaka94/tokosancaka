@extends('layouts.app') {{-- Asumsi Anda punya layout utama --}}

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    body, .gradient-background {
        background: linear-gradient(135deg, #f5d0d0 0%, #ffffff 50%, #f8e5e5 100%);
    }
</style>
@endpush

@section('content')
<div class="gradient-background">
    <div class="container">
        <div class="row vh-100 justify-content-center align-items-center">
            <div class="col-11 col-md-8 col-lg-6 text-center">
                
                <i class="bi bi-shield-slash-fill text-danger" style="font-size: 6rem;"></i>
                
                <h1 class="display-1 fw-bold text-danger">403</h1>
                
                <h2 class="h3 fw-bold mt-3">Akses Ditolak</h2>
                
                <p class="lead text-muted mt-3">
                    Maaf, Anda tidak memiliki izin yang diperlukan untuk mengakses halaman ini.
                    Silakan hubungi administrator jika Anda merasa ini adalah sebuah kesalahan.
                </p>
                
                <div class="mt-4">
                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                    <a href="{{ route('home') }}" class="btn btn-primary">
                        <i class="bi bi-house-door-fill me-1"></i> Ke Halaman Utama
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@endpush