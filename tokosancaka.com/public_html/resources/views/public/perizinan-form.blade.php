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
            margin-bottom: 20px;
            font-weight: bold;
            display: inline-block;
            box-shadow: 3px 3px 0px #ffc107;
        }
        .btn-submit {
            background-color: #0d6efd;
            border: none;
            font-weight: bold;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(13, 110, 253, 0.3);
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <strong>Berhasil!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <div class="paper-form">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-uppercase">Formulir Kriteria Bangunan</h2>
                    <p class="text-muted">Mohon diisi lengkap agar kami bisa menentukan harga.</p>
                </div>

                <div class="section-title">
                    <i class="fas fa-clipboard-list me-2"></i> DATA BANGUNAN
                </div>

                <form action="{{ route('perizinan.store') }}" method="POST">
                    @csrf

                    <div class="row mb-3">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Nama Anda</label>
                            <input type="text" name="nama_pelanggan" class="form-control" placeholder="Contoh: Budi Santoso" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Nomor WhatsApp</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white"><i class="fab fa-whatsapp"></i></span>
                                <input type="number" name="no_wa" class="form-control" placeholder="08xxxx" required>
                            </div>
                            <div class="form-text">Notifikasi akan dikirim ke nomor ini.</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Lebar (m)</label>
                            <input type="number" step="0.1" name="lebar" class="form-control" placeholder="0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Panjang (m)</label>
                            <input type="number" step="0.1" name="panjang" class="form-control" placeholder="0" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bangunan sudah terbangun/belum?</label>
                        <select name="status_bangunan" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <option value="Sudah Terbangun">Sudah Terbangun</option>
                            <option value="Belum Terbangun">Belum Terbangun (Tanah Kosong)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bangunan tempat usaha / rumah tinggal?</label>
                        <select name="jenis_bangunan" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <option value="Rumah Tinggal">Rumah Tinggal</option>
                            <option value="Tempat Usaha">Tempat Usaha</option>
                            <option value="Campuran">Campuran (Ruko/Rukan)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lokasi Permohonan</label>
                        <textarea name="lokasi" class="form-control" rows="2" placeholder="Alamat lengkap lokasi bangunan..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jumlah Lantai</label>
                        <input type="number" name="jumlah_lantai" class="form-control" placeholder="Contoh: 1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fungsi Bangunan</label>
                        <input type="text" name="fungsi_bangunan" class="form-control" placeholder="Contoh: Toko Kelontong / Gudang / Rumah Pribadi" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Legalitas apa yang sudah dimiliki?</label>
                        <input type="text" name="legalitas_saat_ini" class="form-control" placeholder="Contoh: SHM / AJB / Letter C" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Sudah punya KRK / PKKPR?</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_krk" value="Sudah Punya" id="krk1">
                                <label class="form-check-label" for="krk1">Sudah Punya</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_krk" value="Belum Punya" id="krk2" checked>
                                <label class="form-check-label" for="krk2">Belum Punya</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-submit text-white shadow">
                        <i class="fab fa-whatsapp me-2"></i> KIRIM DATA & DAPATKAN HARGA
                    </button>
                    <p class="text-center mt-3 text-muted small">CV. SANCAKA KARYA HUTAMA</p>

                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
