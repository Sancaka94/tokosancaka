@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
<style>
    body, html {
        height: 100%;
        margin: 0;
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
    }
    .success-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 2rem 1rem;
    }
    .success-card {
        max-width: 600px;
        width: 100%;
        border: none;
        text-align: center;
    }
    .success-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: #d1fae5;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }
    .success-icon svg {
        width: 40px;
        height: 40px;
        color: #16a34a;
    }
    .btn-whatsapp {
        background-color: #25d366;
        border: none;
        padding: 0.75rem 1.25rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .btn-whatsapp:hover {
        background-color: #1ebe5d;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
    }
</style>
@endpush

@section('content')
<div class="success-wrapper mt-5">
    <div class="container bg-white rounded-4 shadow p-5 success-card">
        
        {{-- Icon Success --}}
        <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        {{-- Title --}}
        <h2 class="fw-bold text-dark mb-3">Registrasi Berhasil 🎉</h2>

        {{-- Description --}}
        <p class="text-muted mb-2">
            Terima kasih sudah mendaftar.<br>
            Kami telah mengirimkan pesan ke nomor WhatsApp yang Anda daftarkan:
        </p>
        <p class="fw-semibold text-success fs-5">
            {{ $no_wa }}
        </p>

        <p class="text-muted">
            Silakan buka WhatsApp Anda untuk melihat informasi lebih lanjut dan melanjutkan proses pendaftaran.
        </p>

        {{-- Button WhatsApp --}}
        <a href="https://wa.me/{{ preg_replace('/^0/', '62', $no_wa) }}" target="_blank"
           class="btn btn-whatsapp text-white mt-3">
            <i class="fab fa-whatsapp me-2"></i> Buka WhatsApp
        </a>

        <p class="text-center text-muted small mt-5 mb-0">
            &copy; {{ date('Y') }} Sancaka Express. All Rights Reserved.
        </p>
    </div>
</div>
@endsection
