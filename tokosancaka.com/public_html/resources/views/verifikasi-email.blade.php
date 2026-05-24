<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Akun - Sancaka Express</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #dc2626; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; }
    </style>
</head>
<body class="bg-gray-50 h-screen flex items-center justify-center p-4">

    <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 max-w-sm w-full text-center">
        <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Logo" class="w-32 mx-auto mb-6">

        <div id="status-box">
            @if(session('success'))
                <div class="mb-6">
                    <div class="text-green-500 mb-2 text-5xl">✓</div>
                    <h2 class="text-xl font-bold text-gray-800 mb-2">Verifikasi Berhasil!</h2>
                    <p class="text-gray-600 mb-6">{{ session('success') }}</p>

                    <a href="sancaka-express://dashboard"
                    class="block w-full bg-red-600 text-white font-bold py-3 rounded-xl hover:bg-red-700 transition mb-3">
                    Buka Aplikasi Sancaka
                    </a>

                    <script>
                        setTimeout(() => {
                            window.location.href = "sancaka-express://dashboard";
                        }, 2000);
                    </script>
                </div>
            @else
                <div class="mb-6">
                    <div class="text-red-500 mb-2 text-5xl">✗</div>
                    <h2 class="text-xl font-bold text-gray-800 mb-2">Verifikasi Gagal!</h2>
                    <p class="text-gray-600 mb-6">Terjadi kesalahan saat memverifikasi akun Anda.</p>
                </div>
            @endif
        </div>
    </div>

</body>
</html>
