<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 - Sedang Pemeliharaan | Sancaka POS</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 h-screen flex items-center justify-center relative overflow-hidden">
    <div class="absolute inset-0 flex justify-center items-center -z-10">
        <div class="w-96 h-96 bg-indigo-200 rounded-full blur-3xl opacity-30 animate-pulse"></div>
    </div>

    <div class="max-w-lg w-full text-center px-6">
        <div class="flex justify-center mb-6">
            <div class="bg-indigo-100 p-5 rounded-full text-indigo-600 shadow-sm border border-indigo-200">
                <i data-lucide="settings" class="w-16 h-16 animate-[spin_4s_linear_infinite]"></i>
            </div>
        </div>

        <h1 class="text-7xl md:text-8xl font-black text-gray-900 mb-2 tracking-tight">503</h1>
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Sistem Sedang Pemeliharaan</h2>
        <p class="text-gray-500 mb-8 leading-relaxed">
            Kami sedang melakukan pembaruan dan pemeliharaan rutin untuk meningkatkan performa Sancaka POS. Sistem akan segera kembali online dalam beberapa menit.
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <button onclick="window.location.reload()" class="inline-flex items-center justify-center gap-2 bg-[#1e3a8a] text-white px-8 py-3.5 rounded-xl font-bold hover:bg-blue-800 transition shadow-lg w-full sm:w-auto">
                <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                Coba Muat Ulang
            </button>

            <a href="https://wa.me/6285745808809" target="_blank" class="inline-flex items-center justify-center gap-2 bg-white border-2 border-gray-200 text-gray-700 px-8 py-3.5 rounded-xl font-bold hover:bg-gray-50 transition shadow-sm w-full sm:w-auto">
                <i data-lucide="messages-square" class="w-5 h-5"></i>
                Hubungi Support
            </a>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
