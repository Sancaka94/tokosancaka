<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Seminar Sancaka</title>

    {{-- 1. FAVICON (Logo di Tab Browser) --}}
    <link rel="icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .form-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        }
        .logo-img {
            transition: transform 0.3s ease;
        }
        .logo-img:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">

                {{-- 2. LOGO SANCAKA DI ATAS FORM --}}
                <div class="text-center mb-4">
                    <img src="https://tokosancaka.com/storage/uploads/sancaka.png"
                         alt="Logo Sancaka"
                         class="logo-img img-fluid"
                         style="width: 120px; height: auto; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">
                    <h5 class="mt-3 fw-bold text-secondary text-uppercase ls-1">CV. Sancaka Karya Hutama</h5>
                </div>

                <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                    {{-- Header Card --}}
                    <div class="form-header text-white text-center py-4 relative">
                        <h4 class="mb-1 fw-bold"><i class="fas fa-file-signature me-2"></i>FORMULIR SEMINAR</h4>
                        <p class="mb-0 small opacity-75">Silakan lengkapi data diri Anda di bawah ini.</p>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <form action="{{ route('seminar.store') }}" method="POST">
                            @csrf

                            {{-- Nama --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-secondary">Nama Lengkap</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-primary"></i></span>
                                    <input type="text" name="nama" class="form-control border-start-0 ps-0" required placeholder="Nama sesuai KTP">
                                </div>
                            </div>

                            {{-- Email --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-secondary">Email Aktif</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-primary"></i></span>
                                    <input type="email" name="email" class="form-control border-start-0 ps-0" required placeholder="contoh@email.com">
                                </div>
                            </div>

                            {{-- WhatsApp --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-secondary">Nomor WhatsApp</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white border-end-0"><i class="fab fa-whatsapp"></i></span>
                                    <input type="number" name="no_wa" class="form-control border-start-0 ps-2" required placeholder="08xxxxxxxxxx">
                                </div>
                            </div>

                            {{-- Instansi --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-secondary">Instansi / Asal</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-building text-primary"></i></span>
                                    <input type="text" name="instansi" class="form-control border-start-0 ps-0" placeholder="Nama PT / CV / Kampus / Umum">
                                </div>
                            </div>

                            <hr class="my-4 border-secondary-subtle">

                            {{-- 3. PERTANYAAN NIB --}}
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark mb-2">Apakah Anda sudah memiliki NIB?</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check card p-3 flex-fill border-primary-subtle bg-blue-50" style="background-color: #f0f7ff;">
                                        <input class="form-check-input" type="radio" name="nib_status" id="nib_sudah" value="Sudah" required>
                                        <label class="form-check-label fw-bold text-primary cursor-pointer stretched-link" for="nib_sudah">
                                            ✅ SUDAH
                                        </label>
                                    </div>
                                    <div class="form-check card p-3 flex-fill border-secondary-subtle bg-light">
                                        <input class="form-check-input" type="radio" name="nib_status" id="nib_belum" value="Belum">
                                        <label class="form-check-label fw-bold text-secondary cursor-pointer stretched-link" for="nib_belum">
                                            ❌ BELUM
                                        </label>
                                    </div>
                                </div>
                                <div class="form-text text-muted small mt-2">
                                    <i class="fas fa-info-circle me-1"></i> NIB: Nomor Induk Berusaha (OSS)
                                </div>
                            </div>

                            {{-- Submit Button --}}
                            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow hover-shadow-lg transition-all text-uppercase">
                                Daftar Sekarang <i class="fas fa-paper-plane ms-2"></i>
                            </button>

                        </form>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <p class="text-muted small mb-0">&copy; {{ date('Y') }} Event Sancaka Express.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
