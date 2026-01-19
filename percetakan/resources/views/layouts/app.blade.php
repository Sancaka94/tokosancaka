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

    {{-- 1. LOAD LIBRARIES (CDN) --}}
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.16.1/echo.iife.js"></script>

    {{-- 2. CONFIGURATION WITH DEBUGGING --}}
    <script>
        console.log("🔄 Memulai Setup Reverb...");

        // Cek apakah Library Pusher berhasil dimuat
        if (typeof Pusher === 'undefined') {
            console.error("❌ ERROR FATAL: Library 'Pusher' gagal dimuat. Cek koneksi internet Anda.");
            alert("Gagal memuat Library Pusher. Pastikan Laptop terkoneksi internet.");
        } else {
            console.log("✅ Library Pusher OK");
            window.Pusher = Pusher;
        }

        // Cek apakah Library Echo berhasil dimuat
        if (typeof Echo === 'undefined') {
            console.error("❌ ERROR FATAL: Library 'Echo' gagal dimuat. Cek CDN.");
            alert("Gagal memuat Library Laravel Echo.");
        } else {
            console.log("✅ Library Echo OK");

            try {
                // Konfigurasi Reverb
                window.Echo = new Echo({
                    broadcaster: 'reverb',
                    key: "{{ env('REVERB_APP_KEY') }}",
                    wsHost: "{{ env('REVERB_HOST', request()->getHost()) }}",
                    // Gunakan nilai default jika env kosong untuk mencegah Syntax Error
                    wsPort: {{ env('REVERB_PORT') ? env('REVERB_PORT') : 8081 }},
                    wssPort: {{ env('REVERB_PORT') ? env('REVERB_PORT') : 8081 }},
                    forceTLS: {{ env('REVERB_SCHEME', 'http') === 'https' ? 'true' : 'false' }},
                    enabledTransports: ['ws', 'wss'],
                });

                console.log("🚀 Sancaka Realtime System SIAP! (Port: {{ env('REVERB_PORT', 8081) }})");

            } catch (err) {
                console.error("❌ ERROR KONFIGURASI:", err);
                alert("Terjadi error pada script konfigurasi Reverb. Cek Console.");
            }
        }
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
</body>
</html>
