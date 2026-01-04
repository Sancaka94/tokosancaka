<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download QR Code - {{ $affiliate->name }}</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            padding: 20px;
        }

        /* Area yang akan dijadikan Gambar (Desain Story WA/IG) */
        #capture-area {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%); /* Background gradasi agar cantik di Story */
            width: 375px; /* Lebar standar HP */
            min-height: 667px; /* Tinggi standar HP */
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            color: white;
            /* Agar tidak ada elemen yang kepotong */
            overflow: visible; 
        }

        /* Kartu Putih di tengah */
        .card-glass {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            width: 100%;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            color: #333;
        }

        .qr-container svg {
            width: 100%;
            height: auto;
            max-width: 200px;
            border: 4px solid white; /* Border putih di sekitar QR */
            border-radius: 10px;
        }

        .coupon-badge {
            background-color: #ecfdf5;
            color: #059669;
            border: 2px dashed #059669;
            padding: 15px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 1.6rem;
            margin: 20px 0;
            letter-spacing: 1px;
        }

        .shop-link {
            font-size: 0.8rem;
            color: #555;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            word-break: break-all;
            margin-top: 15px;
            border: 1px solid #dee2e6;
            font-family: monospace;
        }
        
        .brand-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .watermark {
            margin-top: 30px;
            font-size: 0.9rem;
            opacity: 0.8;
            font-weight: 500;
        }

        /* Tombol Action (Tidak ikut didownload) */
        .action-buttons {
            margin-top: 25px;
            width: 100%;
            max-width: 375px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
    </style>
</head>
<body>

    <div id="capture-area">
        <div class="mb-4 text-white">
            <h2 class="fw-bold mb-0">PROMO SPESIAL</h2>
            <p class="small opacity-75">Sancaka Express Partner</p>
        </div>

        <div class="card-glass">
            <h4 class="fw-bold text-dark mb-1">{{ $affiliate->name }}</h4>
            <hr class="my-3 opacity-25">

            <p class="small text-secondary mb-2">Scan QR atau Klik Link di Bawah:</p>

            <div class="qr-container mb-3">
                {!! $qrCode !!}
            </div>

            <p class="small text-secondary mb-0 fw-bold">Gunakan Kode Kupon:</p>
            <div class="coupon-badge">
                {{ $affiliate->coupon_code }}
            </div>

            <p class="small text-secondary mb-1">Link Belanja:</p>
            <div class="shop-link">{{ $shopLinkWithCoupon }}</div>
        </div>

        <div class="watermark">
            <i class="fas fa-shopping-bag me-1"></i> Belanja Hemat Sekarang!
        </div>
    </div>
    <div class="action-buttons">
        <button onclick="downloadImage()" class="btn btn-success btn-lg w-100 fw-bold shadow">
            <i class="fas fa-download me-2"></i> Download Gambar (Story WA/IG)
        </button>
        
        <button onclick="window.close()" class="btn btn-outline-secondary w-100">
            Tutup
        </button>
    </div>

    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>

    <script>
        function downloadImage() {
            // Ambil elemen capture-area
            const element = document.getElementById('capture-area');
            
            // Tampilkan loading (opsional, ganti text tombol)
            const btn = document.querySelector('.btn-success');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

            // Konfigurasi html2canvas
            html2canvas(element, {
                scale: 2, // Meningkatkan resolusi agar gambar tajam di HP (Retina display)
                useCORS: true, // Penting jika ada gambar dari server lain
                backgroundColor: null // Menggunakan background CSS
            }).then(canvas => {
                // Buat link download palsu
                const link = document.createElement('a');
                link.download = 'Promo-{{ $affiliate->coupon_code }}.jpg';
                link.href = canvas.toDataURL('image/jpeg', 0.9); // Format JPG kualitas 90%
                link.click();
                
                // Kembalikan tombol ke semula
                btn.innerHTML = originalText;
            }).catch(err => {
                console.error("Gagal membuat gambar:", err);
                alert("Terjadi kesalahan saat membuat gambar.");
                btn.innerHTML = originalText;
            });
        }
    </script>
</body>
</html>