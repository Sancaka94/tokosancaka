<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Selesai</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            text-align: center;
            padding: 40px 20px;
            background-color: #f3f4f6;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
        }
        .card {
            background: white;
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        .icon {
            font-size: 70px;
            margin-bottom: 15px;
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        h2 { color: #111827; margin-top: 0; margin-bottom: 10px; font-size: 24px; }
        p { color: #4b5563; line-height: 1.6; font-size: 15px; margin-bottom: 25px; }
        .highlight { color: #dc2626; font-weight: bold; font-size: 18px; }
        .btn-back {
            display: inline-block;
            background-color: #dc2626;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: bold;
            width: 80%;
            transition: background 0.3s;
        }
        .btn-back:hover { background-color: #b91c1c; }
        .footer-text { font-size: 12px; color: #9ca3af; margin-top: 20px; }

        @keyframes popIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✅</div>
        <h2>Pembayaran Berhasil!</h2>
        <p>Transaksi DANA Anda telah diterima dan sedang diproses oleh sistem Sancaka Express.</p>

        <p>Kembali ke aplikasi dalam <br><span id="timer" class="highlight">5</span> detik...</p>

        <a href="sancakaexpress://riwayatpesanan" class="btn-back" id="btnManual">Kembali ke Aplikasi Sekarang</a>

        <div class="footer-text">Jika tidak otomatis berpindah, silakan klik tombol di atas atau tutup halaman ini secara manual.</div>
    </div>

    <script>
        // Set Skema URL Aplikasi Expo/React Native Anda (Deep Link)
        // Pastikan di app.json Expo Anda terdapat konfigurasi: "scheme": "sancakaexpress"
        const appSchemeUrl = "sancakaexpress://riwayatpesanan";

        let timeLeft = 5;
        const timerElement = document.getElementById('timer');

        const countdown = setInterval(() => {
            timeLeft--;
            timerElement.textContent = timeLeft;

            if (timeLeft <= 0) {
                clearInterval(countdown);
                timerElement.textContent = "0";
                // Eksekusi redirect otomatis ke aplikasi
                window.location.href = appSchemeUrl;
            }
        }, 1000);
    </script>
</body>
</html>
