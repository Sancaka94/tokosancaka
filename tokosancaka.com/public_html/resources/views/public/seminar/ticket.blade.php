<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Tiket Seminar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }

        /* Area Tiket */
        .ticket-card {
            border: 2px dashed #0d6efd;
            background: #ffffff;
            position: relative;
            overflow: hidden;
            max-width: 400px; /* Batasi lebar agar pas di HP */
            margin: 0 auto;
        }

        /* Hiasan Bulatan Sobekan Kertas */
        .ticket-card::before, .ticket-card::after {
            content: '';
            position: absolute;
            width: 30px;
            height: 30px;
            background: #f8f9fa; /* Samakan dengan warna background body */
            border-radius: 50%;
            top: 55%; /* Posisi lekukan */
            transform: translateY(-50%);
            z-index: 2;
        }
        .ticket-card::before { left: -18px; border-right: 2px dashed #0d6efd; }
        .ticket-card::after { right: -18px; border-left: 2px dashed #0d6efd; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5 text-center">
        <h3 class="mb-4 fw-bold text-dark">Tiket Seminar Anda</h3>

        <div class="row justify-content-center">
            <div class="col-md-6">

                {{-- ID "ticket-area" ini yang akan difoto jadi JPG --}}
                <div id="ticket-area" class="p-2">
                    <div class="ticket-card p-4 rounded shadow-sm text-center">
                        <h5 class="text-primary fw-bold text-uppercase mb-3">SANCAKA SEMINAR 2026</h5>

                        {{-- Garis Putus-putus --}}
                        <div style="border-bottom: 2px dashed #e0e0e0; margin: 15px -24px;"></div>

                        <div class="my-4">
                            {!! $qrcode !!}
                            <p class="mt-2 fw-bold text-dark fs-5 font-monospace tracking-wider">{{ $participant->ticket_number }}</p>
                        </div>

                        {{-- Garis Putus-putus --}}
                        <div style="border-bottom: 2px dashed #e0e0e0; margin: 15px -24px;"></div>

                        <div class="text-start ps-2">
                            <small class="text-muted d-block mb-1">Nama Peserta:</small>
                            <h5 class="fw-bold text-dark text-uppercase">{{ $participant->nama }}</h5>

                            <small class="text-muted d-block mt-3 mb-1">Instansi:</small>
                            <p class="fw-bold text-dark mb-0 text-uppercase">{{ $participant->instansi ?? '-' }}</p>
                        </div>

                        <div class="alert alert-info mt-4 small mb-0 fst-italic">
                            <i class="fas fa-info-circle me-1"></i> Tunjukkan QR Code ini kepada panitia.
                        </div>
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div class="d-flex justify-content-center gap-2 mt-4">
                    <button onclick="downloadJPG()" class="btn btn-success fw-bold shadow-sm px-4 py-2">
                        <i class="fas fa-image me-2"></i> Simpan JPG
                    </button>

                    <button onclick="window.print()" class="btn btn-outline-dark fw-bold shadow-sm px-4 py-2">
                        <i class="fas fa-print me-2"></i> Cetak PDF
                    </button>
                </div>
                <p class="text-muted small mt-2">Klik "Simpan JPG" untuk menyimpan ke Galeri.</p>

            </div>
        </div>
    </div>

    {{-- Script html2canvas untuk convert HTML ke Gambar --}}
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>

    <script>
        function downloadJPG() {
            const element = document.getElementById("ticket-area");
            const btn = document.querySelector('.btn-success');

            // Ubah teks tombol biar user tau proses berjalan
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Memproses...';
            btn.disabled = true;

            html2canvas(element, {
                scale: 2, // Biar gambar HD (High Resolution)
                backgroundColor: "#f8f9fa", // Warna background gambar
                useCORS: true // Supaya gambar (jika ada) terload aman
            }).then(canvas => {
                // Buat link download
                const link = document.createElement("a");
                link.download = "Tiket-Seminar-{{ $participant->ticket_number }}.jpg";
                link.href = canvas.toDataURL("image/jpeg", 0.9);
                link.click();

                // Balikin tombol
                btn.innerHTML = originalText;
                btn.disabled = false;
            }).catch(err => {
                alert("Gagal menyimpan gambar. Silakan coba screenshot manual.");
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>
