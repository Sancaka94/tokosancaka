{{--
    File: resources/views/profile/setup.blade.php
    Deskripsi: Halaman formulir untuk pengguna baru melengkapi profil.
    Versi ini menggunakan input teks manual untuk semua data alamat.
--}}
@extends('layouts.app') {{-- Sesuaikan dengan file layout utama Anda --}}

@section('styles')
{{-- CDN untuk styling, Anda bisa memindahkannya ke layout utama jika perlu --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
<style>
    body, html {
        background-color: #f8f9fa;
    }
    .setup-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 2rem 1rem;
    }
    .setup-card {
        max-width: 850px;
        width: 100%;
    }
</style>
@endsection

@section('content')
<div class="setup-wrapper">
    <div class="container">
        <div class="card shadow-lg border-0 rounded-4 setup-card mx-auto">
            <div class="card-body p-4 p-lg-5">
                <div class="text-center mb-4">
                    {{-- Ganti dengan URL logo Anda --}}
                    <img src="https://tokosancaka.biz.id/wp-content/uploads/2024/05/sancaka-express-logo-1.png" alt="Logo Sancaka Express" style="max-height: 60px;">
                    <h3 class="fw-bold mt-3">Selesaikan Pendaftaran Anda</h3>
                    <p class="text-muted">Selamat datang, <strong>{{ $user->nama_lengkap }}</strong>! Silakan lengkapi data dan atur password baru Anda.</p>
                </div>

                {{-- Blok untuk menampilkan semua error validasi --}}
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Formulir menunjuk ke route 'profile.setup.update' dengan metode PUT --}}
                <form action="{{ route('profile.setup.update', $user->id_pengguna) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row g-4">
                        {{-- Kolom Kiri: Informasi Akun --}}
                        <div class="col-md-6">
                            <h5 class="fw-bold"><i class="fas fa-user-circle me-2 text-primary"></i>Informasi Akun</h5>
                            <hr class="mt-2 mb-3">
                            <div class="mb-3">
                                <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="no_wa" class="form-label">Nomor WhatsApp <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="no_wa" name="no_wa" value="{{ old('no_wa', $user->no_wa) }}" required placeholder="Contoh: 081234567890">
                            </div>
                            <div class="mb-3">
                                <label for="store_name" class="form-label">Nama Toko (Opsional)</label>
                                <input type="text" class="form-control" id="store_name" name="store_name" value="{{ old('store_name', $user->store_name) }}">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password Baru <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Minimal 8 karakter.</div>
                            </div>
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                            </div>
                        </div>

                        {{-- Kolom Kanan: Alamat (Input Manual) --}}
                        <div class="col-md-6">
                            <h5 class="fw-bold"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Alamat Utama</h5>
                            <hr class="mt-2 mb-3">
                            <div class="mb-3">
                                <label for="province" class="form-label">Provinsi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="province" name="province" value="{{ old('province') }}" required placeholder="Contoh: Jawa Timur">
                            </div>
                            <div class="mb-3">
                                <label for="regency" class="form-label">Kabupaten/Kota <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="regency" name="regency" value="{{ old('regency') }}" required placeholder="Contoh: Kota Surabaya">
                            </div>
                            <div class="mb-3">
                                <label for="district" class="form-label">Kecamatan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="district" name="district" value="{{ old('district') }}" required placeholder="Contoh: Kecamatan Gayungan">
                            </div>
                             <div class="mb-3">
                                <label for="village" class="form-label">Desa/Kelurahan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="village" name="village" value="{{ old('village') }}" required placeholder="Contoh: Kelurahan Menanggal">
                            </div>
                            <div class="mb-3">
                                <label for="address_detail" class="form-label">Alamat Detail <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="address_detail" name="address_detail" rows="2" required placeholder="Contoh: Jl. Merdeka No. 10, RT 01/02">{{ old('address_detail') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Simpan dan Selesaikan Pendaftaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Tidak ada section 'scripts' karena tidak ada JavaScript yang dibutuhkan --}}
