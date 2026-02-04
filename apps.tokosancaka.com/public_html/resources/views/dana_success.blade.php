<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil | SANCAKA POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        * { margin: 0; padding: 0; box-box: border-box; font-family: 'Inter', sans-serif; }

        body {
            background-color: #f4f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            max-width: 450px;
            width: 90%;
            text-align: center;
        }

        .logo {
            font-weight: 800;
            font-size: 24px;
            color: #1a4da1; /* Biru khas Sancaka */
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: -1px;
        }

        .logo span { color: #118eea; } /* Biru muda aksen DANA */

        .success-icon {
            width: 80px;
            height: 80px;
            background: #e6f7ff;
            color: #118eea;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 40px;
            margin: 0 auto 20px;
        }

        h1 { font-weight: 800; font-size: 22px; margin-bottom: 12px; color: #111; }

        p { color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 8px; }

        .redirect-text {
            font-size: 12px;
            color: #999;
            margin-top: 25px;
        }

        #countdown { font-weight: 600; color: #118eea; }

        .btn {
            display: block;
            margin-top: 30px;
            padding: 16px;
            background: #118eea;
            color: white;
            text-decoration: none;
            border-radius: 14px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(17, 142, 234, 0.3);
        }

        .btn:hover {
            background: #0d79c9;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(17, 142, 234, 0.4);
        }

        /* Dekorasi gelombang halus mirip footer DANA */
        .wave-decoration {
            margin-top: 30px;
            opacity: 0.5;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="logo">SANCAKA <span>POS</span></div>

        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>

        <h1>Pembayaran Selesai!</h1>
        <p>Terima kasih, pembayaran Anda sedang kami proses.</p>
        <p>Invoice dan detail pesanan dapat dilihat di menu Riwayat Pesanan.</p>

        <a href="{{ url('/orders/create') }}" class="btn">
            Kembali ke Beranda
        </a>

        <div class="redirect-text">
            Mengarahkan otomatis dalam <span id="countdown">10</span> detik...
        </div>

        <div class="wave-decoration">
            <svg viewBox="0 0 120 28" fill="#118eea" xmlns="http://www.w3.org/2000/svg" style="width: 100px;">
                <path d="M0 28h120V0c-20 0-40 12-60 12S20 0 0 0v28z"/>
            </svg>
        </div>
    </div>

    <script>
        let seconds = 10;
        const countdownEl = document.getElementById('countdown');
        const targetUrl = "{{ url('/orders/create') }}";

        // Fungsi hitung mundur
        const timer = setInterval(() => {
            seconds--;
            countdownEl.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.href = targetUrl;
            }
        }, 1000);
    </script>
</body>
</html>
