@extends('layouts.app')

{{-- Menyisipkan style kustom ke dalam <head> di layout. --}}
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
<style>
    body, html {
        height: 100%;
        margin: 0;
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa; /* Latar belakang abu-abu muda */
    }
    .auth-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 2rem 1rem;
    }
    /* Mengubah max-width auth-card agar lebih fokus ke formulir */
    .auth-card {
        max-width: 750px; /* Sedikit lebih lebar dari login karena lebih banyak input */
        width: 100%;
        border: none;
        margin-top: 5px;
    }
    .auth-logo {
        max-height: 50px;
    }
    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
        padding: 0.75rem 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .btn-danger:hover {
        background-color: #c82333;
        border-color: #bd2130;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }
    /* Menghapus semua style terkait partner-logos-grid dan partner-logo */
    
    .form-control:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
    }
    .password-toggle-icon {
        position: absolute;
        top: 50%;
        right: 1rem;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
    }
</style>
@endpush

@section('content')

<div class="auth-wrapper">
    <div class="container bg-white rounded-4 shadow p-4 p-lg-5 auth-card">
        <div class="row align-items-center g-5">

            <div class="col-12">
                <div class="text-center mb-4">
                    <a href="{{ url('/') }}">
                        <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka Express Logo" class="auth-logo mb-2">
                    </a>
                    <h3 class="fw-bold">Buat Akun Baru</h3>
                    <p class="text-muted">Daftar dan nikmati kemudahan pengiriman.</p>
                </div>

                {{-- PENAMBAHAN: Alert untuk Pendaftaran Sukses --}}
                @if (session('success'))
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <div>
                            {{ session('success') }}
                        </div>
                    </div>
                @endif

                {{-- Alert Gagal (Error Validasi) --}}
                @if ($errors->any())
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('register') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" placeholder="Nama Lengkap" value="{{ old('nama_lengkap') }}" required>
                                <label for="nama_lengkap">Nama Lengkap</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="store_name" name="store_name" placeholder="Nama Toko" value="{{ old('store_name') }}" required>
                                <label for="store_name">Nama Toko</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" placeholder="nama@contoh.com" value="{{ old('email') }}" required>
                                <label for="email">Alamat Email</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="no_wa" name="no_wa" placeholder="08xxxxxxxxxx" value="{{ old('no_wa') }}" required>
                                <label for="no_wa">Nomor WhatsApp Aktif</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating position-relative">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                <label for="password">Password</label>
                                <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password')"></i>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating position-relative">
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Konfirmasi Password" required>
                                <label for="password_confirmation">Konfirmasi Password</label>
                                <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password_confirmation')"></i>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-danger btn-lg">Daftar Sekarang</button>
                    </div>

                    <p class="text-center mt-4 mb-0">
                        Sudah punya akun? <a href="{{ route('login') }}" class="fw-bold text-danger text-decoration-none">Masuk di sini</a>
                    </p>
                </form>
            </div>

            {{-- Menghapus Kolom Logo Partner di sini --}}

        </div>
        <p class="text-center text-muted small mt-5 mb-0">&copy; {{ date('Y') }} Sancaka Express. All Rights Reserved.</p>
    </div>
</div>

@endsection


{{-- Menyisipkan script ke bagian bawah <body> di layout --}}
@push('scripts')
<script>
    function togglePasswordVisibility(fieldId) {
        const input = document.getElementById(fieldId);
        const icon = input.nextElementSibling;
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