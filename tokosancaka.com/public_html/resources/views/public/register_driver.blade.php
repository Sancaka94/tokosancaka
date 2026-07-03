@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-5">
                    <h3 class="fw-bold mb-4 text-center">Gabung Menjadi Driver Sancaka</h3>
                    
                    @if(session('success'))
                        <div class="alert alert-success rounded-3">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger rounded-3">{{ session('error') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger rounded-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('driver.register.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <h5 class="fw-bold mb-3 border-bottom pb-2">Informasi Pribadi</h5>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label text-muted fw-semibold">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" class="form-control form-control-lg rounded-3 bg-light border-0 @error('nama_lengkap') is-invalid @enderror" value="{{ old('nama_lengkap') }}" required placeholder="Masukkan nama sesuai KTP">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted fw-semibold">Nomor NIK</label>
                                <input type="number" name="nomor_nik" class="form-control form-control-lg rounded-3 bg-light border-0 @error('nomor_nik') is-invalid @enderror" value="{{ old('nomor_nik') }}" required placeholder="16 Digit NIK KTP">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted fw-semibold">Nomor KK</label>
                                <input type="number" name="nomor_kk" class="form-control form-control-lg rounded-3 bg-light border-0 @error('nomor_kk') is-invalid @enderror" value="{{ old('nomor_kk') }}" required placeholder="16 Digit Kartu Keluarga">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label text-muted fw-semibold">Nomor WhatsApp</label>
                                <input type="text" name="nomor_wa" class="form-control form-control-lg rounded-3 bg-light border-0 @error('nomor_wa') is-invalid @enderror" value="{{ old('nomor_wa') }}" required placeholder="08xxxxxxxx">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label text-muted fw-semibold">Alamat Lengkap</label>
                                <textarea name="alamat_lengkap" class="form-control rounded-3 bg-light border-0 @error('alamat_lengkap') is-invalid @enderror" rows="3" required placeholder="Masukkan alamat domisili saat ini">{{ old('alamat_lengkap') }}</textarea>
                            </div>
                        </div>

                        <h5 class="fw-bold mt-4 mb-3 border-bottom pb-2">Titik Koordinat Lokasi (Opsional)</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted fw-semibold">Latitude</label>
                                <input type="text" name="latitude" class="form-control form-control-lg rounded-3 bg-light border-0" value="{{ old('latitude') }}" placeholder="Contoh: -7.401981">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted fw-semibold">Longitude</label>
                                <input type="text" name="longitude" class="form-control form-control-lg rounded-3 bg-light border-0" value="{{ old('longitude') }}" placeholder="Contoh: 111.446131">
                            </div>
                        </div>

                        <h5 class="fw-bold mt-4 mb-3 border-bottom pb-2">Upload Dokumen (JPG/PNG/PDF)</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted fw-semibold">KTP (Wajib)</label>
                                <input type="file" name="file_ktp" class="form-control form-control-lg rounded-3 bg-light border-0" required accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted fw-semibold">Kartu Keluarga (Wajib)</label>
                                <input type="file" name="file_kk" class="form-control form-control-lg rounded-3 bg-light border-0" required accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted fw-semibold">STNK Kendaraan (Wajib)</label>
                                <input type="file" name="file_stnk" class="form-control form-control-lg rounded-3 bg-light border-0" required accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted fw-semibold">BPKB (Wajib)</label>
                                <input type="file" name="file_bpkb" class="form-control form-control-lg rounded-3 bg-light border-0" required accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted fw-semibold">Foto Motor Tampak Samping (Wajib)</label>
                                <input type="file" name="foto_motor" class="form-control form-control-lg rounded-3 bg-light border-0" required accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted fw-semibold">Foto Wajah / Selfie (Wajib)</label>
                                <input type="file" name="foto_wajah" class="form-control form-control-lg rounded-3 bg-light border-0" required accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                            <div class="col-md-12 mb-4">
                                <label class="form-label text-muted fw-semibold">Buku Nikah (Opsional)</label>
                                <input type="file" name="file_buku_nikah" class="form-control form-control-lg rounded-3 bg-light border-0" accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold mt-2 shadow-sm">Kirim Pendaftaran</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection