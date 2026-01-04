<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download QR Code - {{ $affiliate->name }}</title>

    <link rel="icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">
    <link rel="shortcut icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">

    <link rel="apple-touch-icon" href="https://tokosancaka.com/storage/uploads/sancaka.png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f0f2f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
            padding: 20px;
        }

        /* --- 1. Area yang akan MENJADI GAMBAR (Desain Story) --- */
        #area-capture {
            /* Background gradasi agar bagus di Story WA/IG */
            background: linear-gradient(135deg, #0066ff 0%, #0099ff 100%);
            width: 375px; /* Lebar standar HP */
            min-height: 667px; /* Tinggi standar HP (16:9 vertical) */
            padding: 40px 25px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            text-align: center;
            position: relative;
            /* Pastikan elemen ini tidak kena overflow hidden */
            overflow: visible; 
        }

        /* Kartu Putih di dalam gambar */
        .card-inner {
            background: white;
            border-radius: 20px;
            padding: 30px 20px;
            width: 100%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .qr-container svg {
            width: 100%;
            height: auto;
            max-width: 220px;
            margin-bottom: 15px;
        }

        .coupon-box {
            background-color: #ecfdf5;
            color: #059669;
            border: 2px dashed #059669;
            padding: 12px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 1.5rem;
            margin: 15px 0;
        }

        .link-text {
            font-size: 0.75rem;
            color: #6c757d;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 6px;
            word-break: break-all; /* Agar link panjang turun ke bawah */
            border: 1px solid #dee2e6;
        }

        /* Hiasan Footer di dalam gambar */
        .story-footer {
            margin-top: 30px;
            color: white;
            font-weight: 500;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* --- 2. Tombol Action (Tidak ikut didownload) --- */
        .action-area {
            margin-top: 25px;
            width: 375px; /* Samakan lebar dengan area capture */
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-download {
            background-color: #198754; /* Warna hijau WA */
            color: white;
            font-weight: bold;
            padding: 12px;
        }
        
        .btn-download:hover { background-color: #157347; color: white; }

        /* Sembunyikan tombol saat mode Print (Ctrl+P) */
        @media print {
            body { background: white; align-items: flex-start; }
            #area-capture { box-shadow: none; border: 1px solid #ddd; }
            .action-area { display: none !important; }
        }
    </style>
</head>
<body>

    <div id="area-capture">
        
        <div class="mb-4 text-white">
            <h3 class="fw-bold m-0">PROMO SPESIAL 30%</h3>
            <small>Scan & Dapatkan Diskon!</small>
        </div>

        <div class="card-inner">
            <h5 class="fw-bold text-dark mb-1">{{ $affiliate->name }}</h5>
            <p class="text-muted small mb-3">Partner Resmi TokoSancaka.Com/p>

            <div class="qr-container">
                {!! $qrCode !!}
            </div>

            <p class="small text-dark mb-1 fw-bold">Kode Kupon:</p>
            <div class="coupon-box">
                {{ $affiliate->coupon_code }}
            </div>

            <p class="small text-secondary mb-1">Link Order:</p>
            <div class="link-text">
                {{ $shopLinkWithCoupon }}
            </div>
        </div>

        <div class="story-footer">
            <i class="fas fa-shopping-cart me-1"></i> Belanja Hemat Sekarang di TOKO SANCAKA!
        </div>
    </div>
    <div class="action-area">
        <button onclick="downloadImage()" class="btn btn-download w-100 shadow-sm">
            <i class="fas fa-download me-2"></i> Download Gambar (Untuk WA/IG)
        </button>

        <button onclick="window.print()" class="btn btn-primary w-100 shadow-sm">
            <i class="fas fa-print me-2"></i> Cetak / Simpan PDF
        </button>

        <button onclick="window.close()" class="btn btn-outline-secondary w-100">
            Tutup
        </button>
    </div>


    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>

    <script>
        function downloadImage() {
            // Ubah teks tombol biar user tahu sedang proses
            const btn = document.querySelector('.btn-download');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sedang Memproses...';

            // Ambil elemen #area-capture
            const element = document.getElementById("area-capture");

            // Proses convert ke gambar
            html2canvas(element, {
                scale: 2, // Biar gambar tajam (HD)
                useCORS: true, // Izinkan gambar cross-origin
                backgroundColor: null // Pakai background CSS
            }).then(canvas => {
                // Buat link download virtual
                var link = document.createElement("a");
                document.body.appendChild(link);
                link.download = "Promo-{{ $affiliate->name }}.jpg"; // Nama file
                link.href = canvas.toDataURL("image/jpeg", 0.9); // Format JPG quality 90%
                link.target = '_blank';
                link.click();
                document.body.removeChild(link);

                // Kembalikan teks tombol
                btn.innerHTML = originalText;
            }).catch(err => {
                console.log(err);
                alert("Gagal membuat gambar. Silakan coba lagi.");
                btn.innerHTML = originalText;
            });
        }
    </script>
</body>
</html>