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

   {{-- [PERBAIKAN PATH: HARDCODE AGAR TIDAK NYASAR] --}}
    {{-- Kita gunakan helper url() yang biasanya lebih akurat di subfolder --}}
    <script src="{{ url('libs/pusher.min.js') }}"></script>
    <script src="{{ url('libs/echo.js') }}"></script>

    <script>
        // DEBUGGING: Cek apakah file berhasil dimuat
        if (typeof Pusher === 'undefined' || typeof Echo === 'undefined') {
            console.error("❌ ERROR PATH: File JS tidak ketemu.");
            console.error("Coba buka link ini di tab baru untuk cek:");
            console.error("1. {{ url('libs/pusher.min.js') }}");
            console.error("2. {{ url('libs/echo.js') }}");
            alert("File Library tidak ditemukan. Cek Console (F12) untuk link yang error.");
        } else {
            // Setup Global
            window.Pusher = Pusher;

            try {
                window.Echo = new Echo({
                    broadcaster: 'reverb',
                    key: "{{ env('REVERB_APP_KEY') }}",
                    wsHost: window.location.hostname,
                    wsPort: 8081,
                    wssPort: 8081,
                    forceTLS: false,
                    disableStats: true,
                    enabledTransports: ['ws', 'wss'],
                });
                console.log("🚀 Sancaka Realtime: SIAP (Local Mode)");
            } catch (err) {
                console.error("❌ Error Config:", err);
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
