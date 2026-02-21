<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak | Sancaka POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 h-screen flex items-center justify-center relative overflow-hidden">
    <div class="absolute inset-0 flex justify-center items-center -z-10">
        <div class="w-96 h-96 bg-red-200 rounded-full blur-3xl opacity-30 animate-pulse"></div>
    </div>

    <div class="max-w-lg w-full text-center px-6">
        <div class="flex justify-center mb-6">
            <div class="bg-red-100 p-5 rounded-full text-red-600 shadow-sm border border-red-200">
                <i data-lucide="shield-alert" class="w-16 h-16"></i>
            </div>
        </div>
        <h1 class="text-7xl md:text-8xl font-black text-gray-900 mb-2 tracking-tight">403</h1>
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Akses Ditolak!</h2>
        <p class="text-gray-500 mb-8 leading-relaxed">
            Maaf, Anda tidak memiliki izin untuk mengakses halaman ini. Mungkin sesi Anda telah habis atau akun Anda tidak memiliki hak akses administrator.
        </p>
        <a href="{{ url('/') }}" class="inline-flex items-center justify-center gap-2 bg-[#1e3a8a] text-white px-8 py-3.5 rounded-xl font-bold hover:bg-blue-800 transition shadow-lg w-full sm:w-auto">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
            Kembali ke Dashboard
        </a>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
