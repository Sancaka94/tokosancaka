<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Tiket Seminar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .ticket-card { border: 2px dashed #0d6efd; background: #fff; position: relative; overflow: hidden; }
        .ticket-card::before, .ticket-card::after {
            content: ''; position: absolute; width: 30px; height: 30px; background: #f8f9fa; border-radius: 50%; top: 50%; transform: translateY(-50%);
        }
        .ticket-card::before { left: -15px; border-right: 2px dashed #0d6efd; }
        .ticket-card::after { right: -15px; border-left: 2px dashed #0d6efd; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5 text-center">
        <h3 class="mb-4">Tiket Seminar Anda</h3>

        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="ticket-card p-4 rounded shadow-sm">
                    <h5 class="text-primary fw-bold">SANCAKA SEMINAR 2026</h5>
                    <hr>
                    <div class="mb-4">
                        {!! $qrcode !!}
                        <p class="mt-2 fw-bold text-dark fs-5">{{ $participant->ticket_number }}</p>
                    </div>

                    <div class="text-start ps-3">
                        <p class="mb-1 text-muted small">Nama Peserta:</p>
                        <h5 class="fw-bold">{{ $participant->nama }}</h5>

                        <p class="mb-1 text-muted small mt-2">Instansi:</p>
                        <p class="fw-bold mb-0">{{ $participant->instansi ?? '-' }}</p>
                    </div>

                    <div class="alert alert-info mt-4 small mb-0">
                        Simpan QR Code ini untuk ditunjukkan kepada panitia saat registrasi ulang.
                    </div>
                </div>

                <button onclick="window.print()" class="btn btn-outline-dark mt-3">
                    <i class="fas fa-print"></i> Cetak / Simpan PDF
                </button>
            </div>
        </div>
    </div>
</body>
</html>
