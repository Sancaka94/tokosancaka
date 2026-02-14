<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sancaka POS') - Dashboard</title>

    <link rel="icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">

    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Font Awesome & Google Fonts --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Lucide Icons --}}
    <script src="https://unpkg.com/lucide@latest"></script>

    {{-- [PERUBAHAN] Load Alpine.js secara Manual --}}
    {{-- Karena Livewire sudah dihapus, kita butuh ini agar Sidebar & Dropdown tetap jalan --}}
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Asset Echo & Pusher --}}
    <script src="{{ asset('libs/pusher.min.js') }}"></script>
    <script src="{{ asset('libs/echo.js') }}"></script>
    <script>
        if (typeof Echo !== 'undefined') {
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
            } catch (err) { console.error("Error Config:", err); }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
    @stack('styles')
</head>
<body class="antialiased text-slate-700" x-data="{ sidebarOpen: false }">

    {{-- LOGIKA CEK EXPIRED & REDIRECT --}}
    @if(Auth::check() && Auth::user()->tenant && Auth::user()->tenant->expired_at && now()->gt(Auth::user()->tenant->expired_at))
        @if(!request()->is('*account-suspended*'))
            <script>
                var subdomain = "{{ Auth::user()->tenant->subdomain }}";
                window.location.href = "https://" + subdomain + ".tokosancaka.com/account-suspended";
            </script>
            @php exit; @endphp
        @endif
    @endif

    <div class="flex h-screen overflow-hidden">
        {{-- Sidebar Include --}}
        @include('layouts.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
            {{-- Header Include --}}
            @include('layouts.partials.header')

            {{-- Main Content --}}
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 lg:p-6 custom-scrollbar">
                <div class="max-w-7xl mx-auto">
                    {{-- Alert Flash Message --}}
                    @if(session('success'))
                    <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        <span class="text-sm font-medium">{{ session('success') }}</span>
                    </div>
                    @endif

                    {{-- Area Konten Utama (Kembali ke Standard Blade) --}}
                    @yield('content')

                    <footer class="mt-12 text-center text-xs text-slate-400 pb-6 font-medium">
                        &copy; {{ date('Y') }} <span class="text-blue-600">Sancaka</span><span class="text-red-600">POS</span>. Digitalizing Your Business.
                    </footer>
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')
    <script>lucide.createIcons();</script>
</body>
</html>