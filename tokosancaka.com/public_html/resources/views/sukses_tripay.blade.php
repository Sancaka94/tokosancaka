<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Transaksi - Sancaka Express</title>
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
        .highlight { font-weight: bold; font-size: 18px; }

        .status-sukses .highlight { color: #16a34a; }
        .status-sukses .btn-back { background-color: #16a34a; }
        .status-sukses .btn-back:hover { background-color: #15803d; }

        .status-pending .highlight { color: #ea580c; }
        .status-pending .btn-back { background-color: #ea580c; }
        .status-pending .btn-back:hover { background-color: #c2410c; }

        .btn-back {
            display: inline-block;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: bold;
            width: 80%;
            transition: background 0.3s;
            cursor: pointer;
            border: none;
        }
        .footer-text { font-size: 12px; color: #9ca3af; margin-top: 20px; }

        @keyframes popIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body class="{{ $statusPembayaran == 'sukses' ? 'status-sukses' : 'status-pending' }}">
    <div class="card">
        @if($statusPembayaran == 'sukses')
            <div class="icon">✅</div>
            <h2>Pesanan Dibuat!</h2>
            <p>Transaksi <b>{{ $refNo }}</b> Anda telah tercatat. Silakan selesaikan pembayaran sesuai instruksi.</p>
        @else
            <div class="icon">⏳</div>
            <h2>Sedang Memproses...</h2>
            <p>Transaksi <b>{{ $refNo }}</b> Anda sedang menunggu konfirmasi.</p>
        @endif

        <p>Kembali ke aplikasi dalam <br><span id="timer" class="highlight">5</span> detik...</p>

        <button class="btn-back" id="btnManual">Kembali ke Aplikasi</button>

        <div class="footer-text">Jika tidak otomatis berpindah, silakan klik tombol di atas.</div>
    </div>

    <script>
        // Variabel dari Controller/Route
        const refNo = "{{ $refNo }}";
        const isMobile = {{ $isMobile ? 'true' : 'false' }};
        const jenisTransaksi = "{{ $jenisTransaksi }}";

        // Logika IF Cerdas untuk menentukan URL Tujuan
        let targetUrl = "";

        if (isMobile) {
            // REDIRECT UNTUK APLIKASI (DEEP LINK) DENGAN PARAMETER ?id=
            if (jenisTransaksi === 'pesanan_ekspedisi') {
                targetUrl = "sancakaexpress://riwayatpesanan?id=" + refNo;
            } else if (jenisTransaksi === 'pesanan_marketplace') {
                targetUrl = "sancakaexpress://riwayatbelanja?id=" + refNo;
            } else if (jenisTransaksi === 'ppob') {
                targetUrl = "sancakaexpress://riwayatppob?id=" + refNo;
            } else if (jenisTransaksi === 'topup') {
                targetUrl = "sancakaexpress://dashboard";
            } else {
                targetUrl = "sancakaexpress://"; // Fallback aman
            }
        } else {
            // REDIRECT UNTUK WEBSITE JIKA DIBUKA DI LAPTOP
            if (jenisTransaksi === 'pesanan_ekspedisi') {
                targetUrl = "{{ url('/customer/pesanan') }}";
            } else if (jenisTransaksi === 'pesanan_marketplace') {
                targetUrl = "{{ url('/customer/pesanan/riwayat-belanja') }}";
            } else if (jenisTransaksi === 'ppob') {
                targetUrl = "{{ url('/') }}";
            } else if (jenisTransaksi === 'topup') {
                targetUrl = "{{ url('/customer/topup') }}";
            } else {
                targetUrl = "{{ url('/') }}"; // Fallback aman
            }
        }

        // Aksi Tombol Manual
        document.getElementById('btnManual').addEventListener('click', function() {
            window.location.href = targetUrl;
        });

        // Hitung Mundur Otomatis
        let timeLeft = 5;
        const timerElement = document.getElementById('timer');

        const countdown = setInterval(() => {
            timeLeft--;
            timerElement.textContent = timeLeft;

            if (timeLeft <= 0) {
                clearInterval(countdown);
                timerElement.textContent = "0";
                window.location.href = targetUrl; // Eksekusi Redirect Cerdas
            }
        }, 1000);
    </script>
</body>
</html>
