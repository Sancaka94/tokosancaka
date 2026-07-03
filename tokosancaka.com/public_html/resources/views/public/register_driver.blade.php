@extends('layouts.app')

@section('content')
<style>
    /* Custom Styling untuk mempercantik form */
    .register-card {
        border-radius: 1rem;
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }
    .register-header {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: white;
        padding: 2rem 1.5rem;
        text-align: center;
    }
    .form-section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-section-title i {
        color: #dc2626; /* Sancaka Red */
    }
    .form-label {
        font-weight: 600;
        color: #475569;
        font-size: 0.9rem;
    }
    .custom-input {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        transition: all 0.2s ease-in-out;
    }
    .custom-input:focus {
        background-color: #ffffff;
        border-color: #dc2626;
        box-shadow: 0 0 0 0.25rem rgba(220, 38, 38, 0.15);
    }
    /* Memperbaiki tampilan input file agar tidak meluber */
    .custom-file-input {
        background-color: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 0.5rem;
        padding: 0.5rem;
        font-size: 0.85rem;
        transition: all 0.2s;
    }
    .custom-file-input:hover {
        border-color: #94a3b8;
        background-color: #f1f5f9;
    }
    .btn-get-location {
        background-color: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
        font-weight: 600;
        transition: all 0.2s;
    }
    .btn-get-location:hover {
        background-color: #e2e8f0;
        color: #0f172a;
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-xl-11 col-lg-12">
            
            <div class="card register-card">
                {{-- Header Form --}}
                <div class="register-header">
                    <h2 class="fw-bold mb-2">Gabung Menjadi Mitra Driver</h2>
                    <p class="mb-0 opacity-75">Lengkapi formulir di bawah ini dengan data yang valid dan sesuai.</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    
                    {{-- Alert Messages --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show rounded-3 flex items-center shadow-sm" role="alert">
                            <i class="fa-solid fa-circle-check me-2 fs-5"></i>
                            <div>{{ session('success') }}</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger rounded-3 shadow-sm">
                            <div class="fw-bold mb-2"><i class="fa-solid fa-circle-exclamation me-2"></i>Terdapat kesalahan pada input Anda:</div>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Main Form --}}
                    <form action="{{ route('driver.register.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row g-5">
                            {{-- ================= KOLOM KIRI (Informasi & Lokasi) ================= --}}
                            <div class="col-lg-6">
                                
                                {{-- Section: Informasi Pribadi --}}
                                <div class="form-section-title border-bottom pb-2">
                                    <i class="fa-solid fa-id-card"></i> Informasi Pribadi
                                </div>
                                
                                <div class="row g-3 mb-4">
                                    <div class="col-12">
                                        <label class="form-label">Nama Lengkap Sesuai KTP <span class="text-danger">*</span></label>
                                        <input type="text" name="nama_lengkap" class="form-control custom-input @error('nama_lengkap') is-invalid @enderror" value="{{ old('nama_lengkap') }}" required placeholder="Contoh: Budi Santoso">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Nomor NIK <span class="text-danger">*</span></label>
                                        <input type="number" name="nomor_nik" class="form-control custom-input @error('nomor_nik') is-invalid @enderror" value="{{ old('nomor_nik') }}" required placeholder="16 Digit NIK">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Nomor Kartu Keluarga <span class="text-danger">*</span></label>
                                        <input type="number" name="nomor_kk" class="form-control custom-input @error('nomor_kk') is-invalid @enderror" value="{{ old('nomor_kk') }}" required placeholder="16 Digit Nomor KK">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Nomor WhatsApp <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="fa-brands fa-whatsapp text-success"></i></span>
                                            <input type="text" name="nomor_wa" class="form-control custom-input border-start-0 @error('nomor_wa') is-invalid @enderror" value="{{ old('nomor_wa') }}" required placeholder="Contoh: 081234567890">
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Alamat Domisili <span class="text-danger">*</span></label>
                                        <textarea name="alamat_lengkap" class="form-control custom-input @error('alamat_lengkap') is-invalid @enderror" rows="3" required placeholder="Tuliskan alamat lengkap beserta RT/RW, Kelurahan, Kecamatan">{{ old('alamat_lengkap') }}</textarea>
                                    </div>
                                </div>

                                {{-- Section: Titik Lokasi GPS --}}
                                <div class="form-section-title border-bottom pb-2 mt-2">
                                    <i class="fa-solid fa-location-dot"></i> Titik Lokasi (Opsional)
                                </div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <button type="button" id="btnGetLocation" class="btn btn-get-location w-100 rounded-3 py-2">
                                            <i class="fa-solid fa-location-crosshairs me-2 text-danger"></i> Dapatkan Lokasi Saat Ini Otomatis
                                        </button>
                                        <div id="gpsStatus" class="form-text mt-1 text-center"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Latitude</label>
                                        <input type="text" id="latitude" name="latitude" class="form-control custom-input" value="{{ old('latitude') }}" placeholder="Contoh: -7.401981">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Longitude</label>
                                        <input type="text" id="longitude" name="longitude" class="form-control custom-input" value="{{ old('longitude') }}" placeholder="Contoh: 111.446131">
                                    </div>
                                </div>
                            </div>

                            {{-- ================= KOLOM KANAN (Dokumen) ================= --}}
                            <div class="col-lg-6">
                                
                                <div class="form-section-title border-bottom pb-2">
                                    <i class="fa-solid fa-file-arrow-up"></i> Upload Dokumen
                                </div>
                                
                                <div class="alert alert-light border rounded-3 text-muted text-center py-2 mb-4" style="font-size: 0.85rem;">
                                    <i class="fa-solid fa-circle-info me-1"></i> Format yang diizinkan: <strong>JPG, PNG, PDF</strong> (Maks: 5MB/file)
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">KTP <span class="text-danger">*</span></label>
                                        <input type="file" name="file_ktp" class="form-control custom-file-input" required accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Kartu Keluarga <span class="text-danger">*</span></label>
                                        <input type="file" name="file_kk" class="form-control custom-file-input" required accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">STNK Kendaraan <span class="text-danger">*</span></label>
                                        <input type="file" name="file_stnk" class="form-control custom-file-input" required accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">BPKB Kendaraan <span class="text-danger">*</span></label>
                                        <input type="file" name="file_bpkb" class="form-control custom-file-input" required accept=".jpg,.jpeg,.png,.pdf">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Foto Kendaraan <span class="text-danger">*</span></label>
                                        <input type="file" name="foto_motor" class="form-control custom-file-input" required accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Foto Selfie / Wajah <span class="text-danger">*</span></label>
                                        <input type="file" name="foto_wajah" class="form-control custom-file-input" required accept=".jpg,.jpeg,.png,.pdf">
                                    </div>

                                    <div class="col-12 mt-3">
                                        <label class="form-label text-muted">Buku Nikah (Bila ada / Opsional)</label>
                                        <input type="file" name="file_buku_nikah" class="form-control custom-file-input" accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                </div>

                                {{-- Tombol Submit --}}
                                <div class="mt-5">
                                    <button type="submit" class="btn btn-danger btn-lg w-100 rounded-pill fw-bold shadow">
                                        <i class="fa-solid fa-paper-plane me-2"></i> Kirim Pendaftaran
                                    </button>
                                </div>

                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</div>

{{-- Script untuk Get Geolocation GPS --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnGetLocation = document.getElementById('btnGetLocation');
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const statusText = document.getElementById('gpsStatus');

        btnGetLocation.addEventListener('click', function() {
            if (navigator.geolocation) {
                // UI merespon saat loading
                btnGetLocation.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Mencari koordinat...';
                btnGetLocation.disabled = true;
                statusText.innerHTML = '<span class="text-muted">Sedang meminta izin akses lokasi...</span>';

                navigator.geolocation.getCurrentPosition(
                    // Success Callback
                    function(position) {
                        latInput.value = position.coords.latitude;
                        lngInput.value = position.coords.longitude;
                        
                        btnGetLocation.innerHTML = '<i class="fa-solid fa-check text-success me-2"></i> Berhasil Didapatkan';
                        btnGetLocation.classList.replace('btn-get-location', 'btn-light');
                        btnGetLocation.disabled = false;
                        
                        statusText.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-check-circle"></i> Titik lokasi berhasil diisi. (Bisa diubah manual bila kurang pas atau rekomendasi dari kami gunakan HP agar koordinat akurat) Terimakasih</span>';
                        
                        // Kembalikan tombol ke teks awal setelah 3 detik
                        setTimeout(() => {
                            btnGetLocation.innerHTML = '<i class="fa-solid fa-location-crosshairs me-2 text-danger"></i> Perbarui Lokasi Saat Ini';
                            btnGetLocation.classList.replace('btn-light', 'btn-get-location');
                        }, 3000);
                    },
                    // Error Callback
                    function(error) {
                        btnGetLocation.disabled = false;
                        btnGetLocation.innerHTML = '<i class="fa-solid fa-location-crosshairs me-2 text-danger"></i> Dapatkan Lokasi Saat Ini Otomatis';
                        
                        let errorMessage = "";
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage = "Akses lokasi ditolak. Izinkan akses GPS atau isi manual.";
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage = "Informasi lokasi tidak tersedia. Silakan isi manual.";
                                break;
                            case error.TIMEOUT:
                                errorMessage = "Waktu permintaan lokasi habis. Silakan coba lagi atau isi manual.";
                                break;
                            default:
                                errorMessage = "Terjadi kesalahan tidak diketahui.";
                                break;
                        }
                        statusText.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> ' + errorMessage + '</span>';
                    },
                    // Options
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                statusText.innerHTML = '<span class="text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Browser / Perangkat Anda tidak mendukung fitur GPS Geolocation. Silakan isi manual.</span>';
            }
        });
    });
</script>
@endsection