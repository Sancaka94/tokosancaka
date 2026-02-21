<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 - Sesi Kadaluarsa | Sancaka POS</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 h-screen flex items-center justify-center relative overflow-hidden"
      x-data="{
          countdown: 5,
          init() {
              // Redirect otomatis ke halaman login di subdomain aktif
              // Jika route login kamu berbeda namanya, ubah value redirectUrl ini.
              // Menggunakan window.location.origin memastikan subdomain tenant tidak hilang.
              let redirectUrl = window.location.origin + '/login';

              let timer = setInterval(() => {
                  if (this.countdown > 1) {
                      this.countdown--;
                  } else {
                      clearInterval(timer);
                      window.location.href = redirectUrl;
                  }
              }, 1000);
          }
      }">

    <div class="absolute inset-0 flex justify-center items-center -z-10">
        <div class="w-96 h-96 bg-yellow-200 rounded-full blur-3xl opacity-30 animate-pulse"></div>
    </div>

    <div class="max-w-lg w-full text-center px-6">
        <div class="flex justify-center mb-6">
            <div class="bg-yellow-100 p-5 rounded-full text-yellow-600 shadow-sm border border-yellow-200">
                <i data-lucide="hourglass" class="w-16 h-16"></i>
            </div>
        </div>
        <h1 class="text-7xl md:text-8xl font-black text-gray-900 mb-2 tracking-tight">419</h1>
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Sesi Telah Berakhir</h2>
        <p class="text-gray-500 mb-6 leading-relaxed">
            Halaman ini sudah kadaluarsa karena Anda terlalu lama tidak melakukan aktivitas. Demi keamanan, silakan login kembali.
        </p>

        <div class="bg-white border border-yellow-200 rounded-xl p-4 mb-8 shadow-sm flex items-center justify-center gap-3">
            <i data-lucide="loader-circle" class="w-5 h-5 text-yellow-600 animate-spin"></i>
            <p class="text-sm font-semibold text-gray-700">
                Mengarahkan ke halaman login dalam <span x-text="countdown" class="text-yellow-600 font-bold text-lg mx-1"></span> detik...
            </p>
        </div>

        <button @click="window.location.href = window.location.origin + '/login'" class="inline-flex items-center justify-center gap-2 bg-[#1e3a8a] text-white px-8 py-3.5 rounded-xl font-bold hover:bg-blue-800 transition shadow-lg w-full sm:w-auto">
            <i data-lucide="log-in" class="w-5 h-5"></i>
            Login Sekarang
        </button>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
