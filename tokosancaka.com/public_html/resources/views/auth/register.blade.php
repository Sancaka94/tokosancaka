{{--
    File: resources/views/auth/register.blade.php
    Ini adalah halaman formulir pendaftaran custom (Responsive Grid)
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
    }
    .auth-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 2rem 1rem;
    }
    
    /* Lebar maksimal diperbesar sedikit agar dua kolom di desktop tidak sesak */
    .auth-card {
        max-width: 800px; 
        width: 100%;
        border: none;
        border-radius: 1.25rem;
    }
    
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
</style>
@endpush

@section('content')
<div class="auth-wrapper">
    <div class="container bg-white shadow-lg p-4 p-md-5 auth-card position-relative">
        
        <div class="text-center mb-4 pb-2">
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

        <form action="{{ route('register') }}" method="POST">
            @csrf
            
            {{-- Mulai Grid Form (g-3 adalah jarak antar kolom) --}}
            <div class="row g-3 mb-4">
                
                {{-- col-md-6: Lebar 50% di Desktop/Tablet, 100% di HP --}}
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
                </div>

            </div>
            {{-- Akhir Grid Form --}}

            <div class="d-grid mt-2">
                <button type="submit" class="btn btn-danger btn-lg shadow-sm">Daftar Sekarang</button>
            </div>

            <p class="text-center mt-4 mb-0 text-secondary">
                Sudah punya akun? <a href="{{ route('login') }}" class="fw-bold text-danger text-decoration-none border-bottom border-danger">Masuk di sini</a>
            </p>
        </form>

        <div class="text-center mt-5 pt-3 border-top border-light">
            <p class="text-muted small mb-0">&copy; {{ date('Y') }} Sancaka Express. All Rights Reserved.</p>
        </div>

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