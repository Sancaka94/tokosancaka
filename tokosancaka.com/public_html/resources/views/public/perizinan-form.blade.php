<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Kriteria Bangunan - Sancaka</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; }
        .paper-form {
            background: #fff;
            border-top: 5px solid #ffc107; /* Warna Kuning Sancaka */
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 30px;
            background-image: radial-gradient(#e5e5e5 1px, transparent 1px);
            background-size: 20px 20px;
        }
        .form-label { font-weight: bold; color: #333; }
        .section-title {
            background-color: #0d6efd; /* Biru */
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 15px;
            margin-bottom: 20px;
            font-weight: bold;
            display: inline-block;
            box-shadow: 3px 3px 0px #ffc107;
        }
        .btn-submit {
            background-color: #0d6efd;
            border: none;
            font-weight: bold;
            padding: 14px;
            width: 100%;
            border-radius: 8px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.4);
        }
        .form-check-label { cursor: pointer; }
        .form-check-input { cursor: pointer; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm" role="alert">
                <strong>Berhasil!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
                <strong>Gagal!</strong> Mohon periksa kembali form Anda.
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <div class="paper-form">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-uppercase">Formulir Kriteria Bangunan</h2>
                    <p class="text-muted">Mohon diisi lengkap agar kami bisa menentukan harga perizinan.</p>
                </div>

                <form action="{{ route('perizinan.store') }}" method="POST">
                    @csrf

                    <div class="section-title">
                        <i class="fas fa-user-circle me-2"></i> IDENTITAS PEMOHON
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Nama Anda</label>
                            <input type="text" name="nama_pelanggan" class="form-control" placeholder="Contoh: Budi Santoso" value="{{ old('nama_pelanggan') }}" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Nomor WhatsApp</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white"><i class="fab fa-whatsapp"></i></span>
                                <input type="number" name="no_wa" class="form-control" placeholder="08xxxx" value="{{ old('no_wa') }}" required>
                            </div>
                            <div class="form-text">Notifikasi akan dikirim ke nomor ini.</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="section-title">
                        <i class="fas fa-city me-2"></i> DATA BANGUNAN
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lokasi Permohonan</label>
                        <textarea name="lokasi" class="form-control" rows="2" placeholder="Alamat lengkap lokasi bangunan..." required>{{ old('lokasi') }}</textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Lebar (m)</label>
                            <input type="number" step="0.1" name="lebar" class="form-control" placeholder="Contoh: 10" value="{{ old('lebar') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Panjang (m)</label>
                            <input type="number" step="0.1" name="panjang" class="form-control" placeholder="Contoh: 15" value="{{ old('panjang') }}" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Jumlah Lantai</label>
                            <input type="number" name="jumlah_lantai" class="form-control" placeholder="Contoh: 2" value="{{ old('jumlah_lantai') }}" required>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Penghuni/Karyawan</label>
                            <input type="number" name="jumlah_penghuni" class="form-control" placeholder="Kiraan orang" value="{{ old('jumlah_penghuni') }}">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Ada Basement?</label>
                            <select name="memiliki_basement" class="form-select">
                                <option value="0" {{ old('memiliki_basement') == '0' ? 'selected' : '' }}>Tidak Ada</option>
                                <option value="1" {{ old('memiliki_basement') == '1' ? 'selected' : '' }}>Ada</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Status Terbangun?</label>
                            <select name="status_bangunan" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <option value="Sudah Terbangun" {{ old('status_bangunan') == 'Sudah Terbangun' ? 'selected' : '' }}>Sudah Terbangun</option>
                                <option value="Belum Terbangun" {{ old('status_bangunan') == 'Belum Terbangun' ? 'selected' : '' }}>Belum Terbangun (Tanah Kosong)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Jenis Bangunan</label>
                            <select name="jenis_bangunan" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <option value="Rumah Tinggal" {{ old('jenis_bangunan') == 'Rumah Tinggal' ? 'selected' : '' }}>Rumah Tinggal</option>
                                <option value="Tempat Usaha" {{ old('jenis_bangunan') == 'Tempat Usaha' ? 'selected' : '' }}>Tempat Usaha</option>
                                <option value="Campuran" {{ old('jenis_bangunan') == 'Campuran' ? 'selected' : '' }}>Campuran (Ruko/Rukan)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fungsi Bangunan Spesifik</label>
                        <input type="text" name="fungsi_bangunan" class="form-control" placeholder="Contoh: Toko Kelontong / Gudang / Klinik / Kost" value="{{ old('fungsi_bangunan') }}" required>
                    </div>

                    <hr class="my-4">

                    <div class="section-title">
                        <i class="fas fa-file-signature me-2"></i> KELENGKAPAN PERIZINAN
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-primary">Status Tanah <span class="text-danger">*</span></label>
                            <select name="status_tanah" class="form-select border-primary" required>
                                <option value="">-- Pilih Status Tanah --</option>
                                <option value="SHM" {{ old('status_tanah') == 'SHM' ? 'selected' : '' }}>SHM (Sertifikat Hak Milik)</option>
                                <option value="HGB" {{ old('status_tanah') == 'HGB' ? 'selected' : '' }}>HGB (Hak Guna Bangunan)</option>
                                <option value="SEWA" {{ old('status_tanah') == 'SEWA' ? 'selected' : '' }}>Sewa / Kontrak</option>
                                <option value="AJB" {{ old('status_tanah') == 'AJB' ? 'selected' : '' }}>AJB (Akta Jual Beli)</option>
                                <option value="Letter C/Petok D" {{ old('status_tanah') == 'Letter C/Petok D' ? 'selected' : '' }}>Letter C / Petok D</option>
                                <option value="Lainnya" {{ old('status_tanah') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Legalitas Saat Ini <span class="text-danger">*</span></label>
                            <input type="text" name="legalitas_saat_ini" class="form-control" placeholder="Contoh: Belum ada IMB/PBG" value="{{ old('legalitas_saat_ini') }}" required>
                        </div>
                    </div>

                    <div class="mb-3 p-3 bg-light border rounded">
                        <label class="form-label mb-3 d-block border-bottom pb-2">Dokumen Izin yang <strong>Sudah Dimiliki</strong> (Centang jika ada):</label>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="rekom_dishub" value="1" id="rekom_dishub" {{ old('rekom_dishub') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="rekom_dishub">Rekom Dishub (Andalalin)</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="rekom_damkar" value="1" id="rekom_damkar" {{ old('rekom_damkar') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="rekom_damkar">Rekom Damkar</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="andalalin" value="1" id="andalalin" {{ old('andalalin') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="andalalin">Dokumen Andalalin Resmi</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="lingkungan" value="1" id="lingkungan" {{ old('lingkungan') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="lingkungan">SPPL / UKL-UPL / AMDAL</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="nib" value="1" id="nib" {{ old('nib') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="nib">NIB (Nomor Induk Berusaha)</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="siup" value="1" id="siup" {{ old('siup') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="siup">SIUP</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Apakah sudah punya KRK / PKKPR? <span class="text-danger">*</span></label>
                        <div class="d-flex gap-4 p-2 bg-light rounded border">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_krk" value="Sudah Punya" id="krk1" {{ old('status_krk') == 'Sudah Punya' ? 'checked' : '' }} required>
                                <label class="form-check-label text-success fw-bold" for="krk1">Sudah Punya</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_krk" value="Belum Punya" id="krk2" {{ old('status_krk', 'Belum Punya') == 'Belum Punya' ? 'checked' : '' }} required>
                                <label class="form-check-label text-danger fw-bold" for="krk2">Belum Punya</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Perizinan Lain-Lain (Bila Ada)</label>
                        <textarea name="perizinan_lain" class="form-control" rows="2" placeholder="Sebutkan jika ada izin lain yang sudah diurus/dimiliki...">{{ old('perizinan_lain') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-submit text-white shadow-lg mt-3">
                        <i class="fab fa-whatsapp me-2 fs-5"></i> KIRIM DATA & DAPATKAN HARGA
                    </button>
                    <p class="text-center mt-3 text-muted small fw-bold">CV. SANCAKA KARYA HUTAMA</p>

                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>