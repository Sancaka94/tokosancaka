<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; }
        .success { color: green; font-size: 24px; font-weight: bold; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1 class="success">✅ Pembayaran Selesai!</h1>
    <p>Terima kasih, pembayaran Anda sedang kami proses.</p>
    <p>Silakan cek status pesanan Anda secara berkala.</p>
    
    <a href="{{ url('/') }}" class="btn">Kembali ke Beranda</a>
</body>
</html>