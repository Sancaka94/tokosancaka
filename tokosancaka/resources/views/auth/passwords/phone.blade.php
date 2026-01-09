{{-- 
    File: resources/views/auth/passwords/phone.blade.php
    Simpan file ini di folder: resources/views/auth/passwords/
    
    Ini adalah halaman formulir untuk meminta token reset password via WhatsApp (Fonnte).
--}}
@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    body, .gradient-background {
        background: linear-gradient(135deg, #e0c3fc 0%, #ffffff 50%, #8ec5fc 100%);
        min-height: 100vh; /* Pastikan background cover layar tapi tidak maksa vh-100 di konten */
    }
    .card-custom {
        border: none;
        border-radius: 1.25rem;
        box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;
        overflow: hidden; /* Agar radius sudut tidak tertutup background anak */
    }
    .btn-lg-custom {
        padding: 0.75rem 1.25rem;
        font-size: 1.1rem;
    }
    /* Bagian Kiri (Desktop) */
    .info-side {
        background-color: #f8f9fa; /* Warna abu muda */
    }
    /* Tambahan style untuk input phone */
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
</style>
@endpush

@section('content')
{{-- Hapus vh-100, ganti dengan py-5 my-5 agar ada jarak dengan footer --}}
<div class="gradient-background py-5 d-flex align-items-center">
    <div class="container my-4">
        <div class="row justify-content-center">
            {{-- Lebarkan kolom agar muat mode mendatar (col-lg-10) --}}
            <div class="col-12 col-md-10 col-lg-10 col-xl-9">
                <div class="card card-custom">
                    <div class="row g-0">
                        
                        {{-- BAGIAN 1: SISI KIRI (INFO) --}}
                        {{-- Tampil di kiri pada Desktop, Sembunyi/Atas pada Mobile --}}
                        {{-- d-none d-md-flex artinya: Hilang di HP, Muncul flex di Tablet/Desktop --}}
                        <div class="col-md-6 d-none d-md-flex flex-column align-items-center justify-content-center info-side p-5 text-center">
                            <i class="bi bi-whatsapp text-success mb-3" style="font-size: 4rem;"></i>
                            <h2 class="h3 fw-bold">Reset Password</h2>
                            <p class="text-muted">
                                Lupa kata sandi Anda?<br>
                                Jangan khawatir, kami akan mengirimkan link reset ke WhatsApp Anda.
                            </p>
                        </div>

                        {{-- BAGIAN 2: SISI KANAN (FORM) --}}
                        <div class="col-md-6 p-4 p-md-5 bg-white">
                            
                            {{-- Header Khusus Mobile (Muncul hanya di HP) --}}
                            <div class="d-md-none text-center mb-4">
                                <i class="bi bi-whatsapp text-success" style="font-size: 3rem;"></i>
                                <h2 class="h4 fw-bold mt-2">Reset Password</h2>
                                <p class="text-muted small">Masukkan Nomor WhatsApp Anda.</p>
                            </div>

                            {{-- Alert Status Berhasil --}}
                            @if (session('status'))
                                <div class="alert alert-success d-flex align-items-center" role="alert">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    <div>
                                        {{ session('status') }}
                                    </div>
                                </div>
                            @endif

                            {{-- Alert Error Manual --}}
                            @if ($errors->any())
                                <div class="alert alert-danger d-flex align-items-center" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <div>
                                        {{ $errors->first() }}
                                    </div>
                                </div>
                            @endif

                            @php
                                $formAction = route('customer.password.email'); 
                                $loginRoute = route('customer.login');
                            @endphp

                            <form action="{{ $formAction }}" method="POST">
                                @csrf
                                
                                <div class="mb-4">
                                    <label for="phone" class="form-label fw-medium">Nomor WhatsApp</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white text-muted border-end-0">
                                            +62
                                        </span>
                                        <input id="phone" 
                                               name="phone" 
                                               type="text" 
                                               inputmode="numeric"
                                               value="{{ old('phone') }}" 
                                               required 
                                               class="form-control form-control-lg border-start-0 ps-0 @error('phone') is-invalid @enderror" 
                                               placeholder="81234567890">
                                    </div>
                                    
                                    @error('phone')
                                        <div class="text-danger small mt-1">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success btn-lg-custom text-white">
                                        Kirim Link WhatsApp <i class="bi bi-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </form>

                            <div class="text-center mt-4 pt-2">
                                <a href="{{ $loginRoute }}" class="text-decoration-none fw-medium text-secondary">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Login
                                </a>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection