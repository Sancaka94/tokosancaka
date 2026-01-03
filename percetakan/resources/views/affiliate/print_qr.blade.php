<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Code - {{ $affiliate->name }}</title>
    {{-- Kita pakai Bootstrap lewat CDN agar tampilan rapi saat diprint --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: sans-serif;
        }
        .card-qr {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            text-align: center;
            width: 100%;
            max-width: 400px;
            border: 1px solid #e5e7eb;
        }
        .qr-container svg {
            width: 100%;
            height: auto;
        }
        .coupon-badge {
            background-color: #ecfdf5;
            color: #059669;
            border: 2px dashed #059669;
            padding: 15px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 1.5rem;
            margin: 20px 0;
            letter-spacing: 1px;
        }
        .shop-link {
            font-size: 0.75rem;
            color: #6b7280;
            word-break: break-all;
            margin-top: 10px;
        }
        
        /* Styling khusus saat diprint (Ctrl+P) */
        @media print {
            body { background: white; }
            .card-qr { 
                box-shadow: none; 
                border: 2px solid #000; 
                width: 100%;
                max-width: 100%;
            }
            .btn-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="card-qr">
        <h4 class="fw-bold text-dark mb-1">{{ $affiliate->name }}</h4>
        <p class="text-muted small mb-4">Partner Resmi Sancaka Express</p>

        <div class="qr-container mb-3">
            {!! $qrCode !!}
        </div>

        <p class="small text-secondary mb-2">Scan untuk belanja hemat dengan kupon:</p>

        <div class="coupon-badge">
            {{ $affiliate->coupon_code }}
        </div>

        <p class="shop-link">{{ $shopLinkWithCoupon }}</p>

        <button onclick="window.print()" class="btn btn-primary w-100 btn-print mt-3 fw-bold">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
        
        <button onclick="window.close()" class="btn btn-link w-100 btn-print mt-2 text-decoration-none text-secondary">
            Tutup
        </button>
    </div>

</body>
</html>