<!DOCTYPE html>
<html>
<head>
    <title>Verifikasi Sancaka</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-lg text-center max-w-sm">
        @if($status == 'success')
            <div class="text-green-500 text-6xl mb-4">✓</div>
            <h2 class="text-2xl font-bold mb-2">Verifikasi Sukses!</h2>
            <p class="mb-6 text-gray-600">Aplikasi akan terbuka otomatis dalam 2 detik...</p>

            <a href="sancakaexpress://dashboard" class="block bg-red-600 text-white py-3 rounded-xl font-bold">
                Buka Aplikasi Sekarang
            </a>

            <script>
                setTimeout(function() {
                    window.location.href = "sancakaexpress://dashboard";
                }, 2000);
            </script>
        @else
            <div class="text-red-500 text-6xl mb-4">✕</div>
            <h2 class="text-2xl font-bold mb-2">Verifikasi Gagal</h2>
            <p class="mb-6 text-gray-600">{{ $message }}</p>
            <a href="sancakaexpress://dashboard" class="block bg-gray-600 text-white py-3 rounded-xl font-bold">
                Kembali ke Aplikasi
            </a>
        @endif
    </div>
</body>
</html>
