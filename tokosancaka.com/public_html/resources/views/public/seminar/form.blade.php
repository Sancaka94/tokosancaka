<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Seminar Sancaka</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow border-0">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h4 class="mb-0 fw-bold">FORMULIR SEMINAR</h4>
                        <p class="mb-0 small">Silakan isi data untuk mendapatkan tiket.</p>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('seminar.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control" required placeholder="Sesuai sertifikat">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nomor WhatsApp</label>
                                <input type="number" name="no_wa" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Instansi / Umum (Opsional)</label>
                                <input type="text" name="instansi" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">DAFTAR & DAPATKAN TIKET</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
