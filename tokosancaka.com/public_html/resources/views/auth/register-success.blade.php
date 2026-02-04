@extends('layouts.app')

@push('styles')

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
<style>
    /* Variabel Warna */
    :root {
        --primary-blue: #1a73e8;
        --success-green: #10b981;
        --whatsapp-green: #25d366;
        --bg-light: #f8f9fa;
        --shadow-strong: rgba(0, 0, 0, 0.15);
    }
    
    body, html {
        height: 100%;
        margin: 0;
        font-family: 'Poppins', sans-serif;
        background-color: var(--bg-light);
    }
    .success-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 3rem 1rem;
    }
    .success-card {
        max-width: 450px; /* Lebih kompak */
        width: 100%;
        border: none;
        text-align: center;
        box-shadow: 0 10px 30px var(--shadow-strong); /* Shadow lebih menonjol */
    }
    .success-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: #d1fae5;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem; /* Jarak lebih baik */
    }
    .success-icon svg {
        width: 40px;
        height: 40px;
        color: var(--success-green);
    }
    .btn-custom {
        padding: 0.75rem 1.25rem;
        font-weight: 600;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
        text-decoration: none; /* Penting untuk tag <a> */
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-whatsapp {
        background-color: var(--whatsapp-green);
        color: white;
        margin-right: 10px; /* Jarak antar tombol */
    }
    .btn-whatsapp:hover {
        background-color: #1ebe5d;
        color: white;
        box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4);
        transform: translateY(-2px);
    }
    .btn-profile { /* Mengganti nama kelas dari .btn-dashboard */
        background-color: var(--primary-blue);
        color: white;
        border: 1px solid var(--primary-blue);
    }
    .btn-profile:hover { /* Mengganti nama kelas dari .btn-dashboard */
        background-color: #1669c1;
        color: white;
        box-shadow: 0 4px 15px rgba(26, 115, 232, 0.4);
        transform: translateY(-2px);
    }
    .text-account {
        font-size: 1.5rem; /* Lebih menonjol */
        color: #1f2937;
        padding: 0.5rem 1rem;
        background-color: #eef2ff;
        border-radius: 0.5rem;
        display: inline-block;
        margin-top: 0.5rem;
        margin-bottom: 1rem;
    }
    .info-setup {
        background-color: #f0f8ff; /* Background ringan */
        border: 1px solid #cce5ff;
        color: #004085;
        padding: 1rem;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        border-radius: 0.5rem;
        font-size: 0.9rem;
    }
</style>
@endpush

@section('content')

<div class="success-wrapper">
    <div class="container bg-white rounded-4 shadow p-5 success-card">
        
        {{-- Icon Success --}}
        <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        {{-- Title --}}
        <h2 class="fw-bold text-dark mb-4 fs-3">✅ Registrasi Berhasil!</h2>

        {{-- Description --}}
        <p class="text-muted mb-2 fs-6">
            Terima kasih sudah mendaftar. Kami telah mengirimkan detail verifikasi ke:
        </p>
        
        {{-- Nomor WA yang Didaftarkan --}}
        <p class="fw-semibold text-account">
            {{ $no_wa }}
        </p>

        <p class="text-muted mb-4">
            Silakan buka WhatsApp Anda untuk melihat <strong>TOKEN RAHASIA</strong> dan melanjutkan proses pendaftaran toko Anda.
        </p>

        {{-- START: Teks Instruksi Tambahan --}}
        <div class="info-setup">
            <p class="fw-bold mb-1">Penting:</p>
            <p class="mb-0">Untuk melanjutkan pendaftaran member Sancaka, Anda <strong>WAJIB</strong> melengkapi data pribadi dan alamat pengiriman dengan mengklik tombol di bawah ini.</p>
        </div>
        {{-- END: Teks Instruksi Tambahan --}}

        {{-- Group Tombol --}}
        <div class="d-flex justify-content-center flex-wrap gap-2">
            
            {{-- Button WhatsApp --}}
            <a href="https://wa.me/{{ preg_replace('/^0/', '62', $no_wa) }}" target="_blank"
                class="btn-custom btn-whatsapp">
                <i class="fab fa-whatsapp me-2"></i> Buka WhatsApp
            </a>
            
            {{-- Button Setup Profile --}}
            <a href="https://tokosancaka.com/customer/profile"
                class="btn-custom btn-profile">
                <i class="fas fa-user-edit me-2"></i> Lengkapi Data
            </a>
        </div>
        
        <p class="text-center text-muted small mt-5 mb-0">
            &copy; {{ date('Y') }} Sancaka Express. All Rights Reserved.
        </p>

    </div>
</div>

@endsection