<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Dashboard Pelanggan - {{ config('app.name', 'Sancaka Express') }}</title>

    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="icon" href="https://tokosancaka.biz.id/storage/uploads/sancaka.png" type="image/png">
    <link rel="shortcut icon" href="https://tokosancaka.biz.id/storage/uploads/sancaka.png" type="image/png">
    <link rel="apple-touch-icon" href="https://tokosancaka.biz.id/storage/uploads/sancaka.png">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <!-- AlpineJS -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        .modal-transition { transition: opacity 0.3s ease, transform 0.3s ease; }
        .modal-hidden { opacity: 0; transform: scale(0.95); pointer-events: none; }
        .modal-visible { opacity: 1; transform: scale(1); pointer-events: auto; }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-100 text-gray-800">

    {{-- ✅ DIUBAH: Inisialisasi Alpine.js untuk semua state UI di satu tempat --}}
    <div x-data="{ sidebarOpen: false, isNotificationsMenuOpen: false, isProfileMenuOpen: false }" class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        @include('layouts.partials.customer.sidebar')

        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Topbar -->
            @include('layouts.partials.customer.topbar')

            <!-- Main content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
                <div class="container mx-auto px-6 py-8">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Tombol Chat Floating dengan Tooltip -->
    <div class="fixed bottom-8 right-8 z-50 group flex items-center">
        <span class="absolute right-full mr-4 bg-gray-800 text-white text-sm font-medium px-3 py-1.5 rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
            Hubungi Admin
        </span>
        <button id="chatButton" class="bg-gradient-to-r from-cyan-500 to-blue-500 text-white w-16 h-16 rounded-full shadow-lg flex items-center justify-center hover:scale-110 transform transition-transform duration-300 ease-in-out focus:outline-none focus:ring-4 focus:ring-blue-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
        </button>
    </div>

    <!-- Modal Container -->
    <div id="chatModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 modal-transition modal-hidden">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-auto overflow-hidden transform">
            <div class="p-6 text-center">
                <div class="flex justify-center items-center mx-auto bg-blue-100 rounded-full w-20 h-20 mb-5">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-800">Hubungi Admin</h3>
                <p class="text-gray-500 mt-2 mb-6">Anda akan diarahkan ke halaman chat untuk berbicara langsung dengan customer service kami.</p>
                <a href="https://tokosancaka.biz.id/customer/chat" target="_blank" class="w-full block bg-gradient-to-r from-cyan-500 to-blue-500 text-white font-semibold py-3 px-4 rounded-lg shadow-md hover:shadow-lg hover:from-cyan-600 hover:to-blue-600 transition-all duration-300 ease-in-out">
                    Lanjutkan ke Chat
                </a>
                <button id="closeModalButton" class="w-full mt-3 bg-gray-100 text-gray-600 font-semibold py-3 px-4 rounded-lg hover:bg-gray-200 transition-colors duration-300">
                    Batal
                </button>
            </div>
        </div>
    </div>

    {{-- Kode JavaScript dan SweetAlert Anda tetap dipertahankan --}}
    <script>
        const chatButton = document.getElementById('chatButton');
        const chatModal = document.getElementById('chatModal');
        const closeModalButton = document.getElementById('closeModalButton');
        const openModal = () => {
            chatModal.classList.remove('modal-hidden');
            chatModal.classList.add('modal-visible');
        };
        const closeModal = () => {
            chatModal.classList.remove('modal-visible');
            chatModal.classList.add('modal-hidden');
        };
        chatButton.addEventListener('click', openModal);
        closeModalButton.addEventListener('click', closeModal);
        chatModal.addEventListener('click', (event) => {
            if (event.target === chatModal) closeModal();
        });
    </script>
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.14.1/echo.iife.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            window.Pusher = Pusher;
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: '7a59606602fcfa7ae2d5',
                cluster: 'mt1',
                forceTLS: true,
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                }
            });
            let userId = {{ auth()->id() }};
            window.Echo.private(`customer-saldo.${userId}`)
                .listen('.SaldoUpdated', (data) => {
                    // Logika notifikasi Anda tetap di sini
                });
            if (Notification.permission !== "granted") {
                Notification.requestPermission();
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if(session('success'))
    <script>
        Swal.fire({
            title: 'Berhasil!',
            text: "{{ session('success') }}",
            icon: 'success',
            confirmButtonColor: '#16a34a',
        });
    </script>
    @endif
    @if(session('error'))
    <script>
        Swal.fire({
            title: 'Gagal!',
            text: "{{ session('error') }}",
            icon: 'error',
            confirmButtonColor: '#dc2626',
        });
    </script>
    @endif
    @stack('scripts')
</body>
</html>

