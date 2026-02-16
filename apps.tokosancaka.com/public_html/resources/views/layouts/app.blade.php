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

    {{-- [LIVEWIRE STYLES] Wajib ada di dalam Head --}}
    @livewireStyles

    {{-- Asset Echo & Pusher (Opsional jika pakai Reverb) --}}
    <script src="{{ asset('libs/pusher.min.js') }}"></script>
    <script src="{{ asset('libs/echo.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof Echo !== 'undefined' && typeof Pusher !== 'undefined') {
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
        });
    </script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }

        /* Animasi Loading Bar Livewire */
        .nprogress-custom-parent { overflow: hidden; position: relative; }
        .nprogress-custom-parent #nprogress .spinner, .nprogress-custom-parent #nprogress .bar { position: absolute; }
    </style>
    @stack('styles')
</head>
<body class="antialiased text-slate-700" x-data="{ sidebarOpen: false }">

    {{-- [LOGIC PENYELAMAT] Cek Expired --}}
    @if(Auth::check() && optional(Auth::user()->tenant)->expired_at && now()->gt(Auth::user()->tenant->expired_at))
        @if(!request()->is('*account-suspended*'))
            <script>
                window.location.href = "https://{{ Auth::user()->tenant->subdomain }}.tokosancaka.com/account-suspended";
            </script>
            @php exit; @endphp
        @endif
    @endif

    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar Include --}}
        @include('layouts.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">

            {{-- [LIVEWIRE LOADING INDICATOR] Bar biru di atas saat pindah halaman --}}
            <div wire:loading.delay class="fixed top-0 left-0 w-full h-1 bg-blue-500 z-[9999] shadow-[0_0_10px_#3b82f6]"></div>

            {{-- Header Include --}}
            @include('layouts.partials.header')

            {{-- Main Content --}}
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 lg:p-6 custom-scrollbar relative">
                <div class="max-w-7xl mx-auto">

                    {{-- Alert Flash Message --}}
                    @if(session('success'))
                    <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl animate-fade-in-down">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        <span class="text-sm font-medium">{{ session('success') }}</span>
                    </div>
                    @endif

                    {{-- Area Konten Utama --}}
                    @yield('content')

                    {{-- Slot Tambahan jika pakai Full Livewire Component --}}
                    {{ $slot ?? '' }}

                    <footer class="mt-12 text-center text-xs text-slate-400 pb-6 font-medium">
                        &copy; {{ date('Y') }} <span class="text-blue-600">Sancaka</span><span class="text-red-600">POS</span>. Digitalizing Your Business.
                    </footer>
                </div>
            </main>
        </div>
    </div>

    {{-- [LIVEWIRE SCRIPTS] Wajib ada sebelum tutup Body --}}
    @livewireScripts

    {{-- Alpine.js (Jika Livewire v3, Alpine sudah include otomatis. Jika v2, perlu manual) --}}
    {{-- Karena Anda pakai Livewire v4.1 (modern), kita hapus load manual Alpine agar tidak bentrok --}}

    @stack('scripts')

    <script>
        lucide.createIcons();

        // Re-init Lucide Icons saat Livewire navigasi selesai (SPA Mode)
        document.addEventListener('livewire:navigated', () => {
            lucide.createIcons();
        });
    </script>
</body>
</html>
