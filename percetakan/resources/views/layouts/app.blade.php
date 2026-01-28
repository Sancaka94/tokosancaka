<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Sancaka POS') - Dashboard</title>

    <link rel="icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">
    <link rel="shortcut icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">

    <link rel="apple-touch-icon" href="https://tokosancaka.com/storage/uploads/sancaka.png">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

   {{-- [SOLUSI FINAL: LINK HARDCODE SESUAI BROWSER] --}}

    {{-- 1. Pusher JS (Kita asumsikan satu folder dengan echo.js) --}}
    <script src="https://tokosancaka.com/percetakan/public/libs/pusher.min.js"></script>

    {{-- 2. Laravel Echo (URL yang berhasil Anda buka tadi) --}}
    <script src="https://tokosancaka.com/percetakan/public/libs/echo.js"></script>

    <script>
        // Cek lagi untuk memastikan
        if (typeof Echo === 'undefined') {
            alert("❌ Gawat! Browser masih menolak file JS. Coba matikan AdBlock/Shield di browser.");
        } else {
            // Setup Global
            window.Pusher = Pusher;

            try {
                window.Echo = new Echo({
                    broadcaster: 'reverb',
                    key: "{{ env('REVERB_APP_KEY') }}",

                    // Hostname otomatis (tokosancaka.com)
                    wsHost: window.location.hostname,

                    // Port sesuai terminal (8081)
                    wsPort: 8081,
                    wssPort: 8081,

                    forceTLS: false,
                    disableStats: true,
                    enabledTransports: ['ws', 'wss'],
                });

                console.log("🚀 Sancaka Realtime: SIAP (Via URL Manual)");

            } catch (err) {
                console.error("❌ Error Config:", err);
            }
        }

    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/lucide@latest"></script> {{-- TAMBAHKAN INI --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>

    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-800 antialiased" x-data="{ sidebarOpen: false }">

    @if(session('subscription_expired'))
        <div id="freezeModal" class="fixed inset-0 z-[999] flex items-center justify-center bg-black bg-opacity-80 backdrop-blur-sm">
            <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl border-4 border-red-500 animate-bounce-short">
                <div class="text-center">
                    <div class="bg-red-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="lock" class="text-red-600 w-10 h-10"></i>
                    </div>
                    <h2 class="text-2xl font-black text-gray-800 mb-2">AKUN DIBEKUKAN!</h2>
                    <p class="text-gray-600 mb-6">Masa aktif layanan <strong>{{ Auth::user()->tenant->name }}</strong> telah berakhir pada {{ Auth::user()->tenant->expired_at->format('d M Y') }}.</p>

                    <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left border border-gray-200">
                        <p class="text-xs text-gray-500 uppercase font-bold mb-1">Status Tagihan:</p>
                        <p class="text-red-600 font-bold">Menunggu Pembayaran Perpanjangan</p>
                    </div>

                    <a href="https://tokosancaka.com/billing" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg text-center">
                        BAYAR SEKARANG
                    </a>
                    <p class="mt-4 text-[10px] text-gray-400 italic">Hubungi Admin: 085745808809 jika ada kendala.</p>
                </div>
            </div>
        </div>
        @endif



    <div class="flex h-screen overflow-hidden">

        @include('layouts.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden transition-all duration-300">

            @include('layouts.partials.header')

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 lg:p-8 custom-scrollbar">
                @if(session('success'))
                    <div class="mb-4 bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Berhasil!</strong>
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @yield('content')

                <div class="mt-10 text-center text-xs text-slate-400 pb-4">
                    &copy; {{ date('Y') }} Sancaka POS. All rights reserved.
                </div>
            </main>

        </div>
    </div>

    @stack('scripts')

      <script>
            // Refresh icon Lucide jika diperlukan
            lucide.createIcons();
        </script>

</body>
</html>
