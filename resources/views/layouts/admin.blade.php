<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Admin Panel') - {{ config('app.name', 'Sancaka Express') }}</title>

    <!-- Favicon -->
    <link rel="icon" href="https://tokosancaka.biz.id/storage/uploads/sancaka.png" type="image/png">
    
    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <!-- AlpineJS -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }

        /* Custom scrollbar styles */
        .custom-scrollbar::-webkit-scrollbar { width: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #64748b; }
        .dark .custom-scrollbar::-webkit-scrollbar-track { background: #1e293b; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #475569; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #64748b; }

        /* CSS untuk transisi modal chat */
        .modal-transition { transition: opacity 0.3s ease, transform 0.3s ease; }
        .modal-hidden { opacity: 0; transform: scale(0.95); pointer-events: none; }
        .modal-visible { opacity: 1; transform: scale(1); pointer-events: auto; }

        
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">

@if(isset($error_message))
    <div class="alert alert-danger text-center">
        {{ $error_message }}
    </div>
@endif

    <div x-data="{ sidebarOpen: window.innerWidth > 1024 ? true : false }" @resize.window="sidebarOpen = window.innerWidth > 1024 ? true : false" class="flex h-screen bg-gray-100 dark:bg-gray-800">
        
        @include('layouts.partials.sidebar')

        <div class="flex-1 flex flex-col overflow-hidden">
            
            @include('layouts.partials.header')

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900 custom-scrollbar">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    
    <!-- Tombol & Modal Chat (Global) -->
    <div class="fixed bottom-8 right-8 z-50 group flex items-center">
        <span class="absolute right-full mr-4 bg-gray-800 text-white text-sm font-medium px-3 py-1.5 rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
            Hubungi Customer
        </span>
        <button id="chatButton" class="bg-gradient-to-r from-indigo-500 to-purple-500 text-white w-16 h-16 rounded-full shadow-lg flex items-center justify-center hover:scale-110 transform transition-transform duration-300 ease-in-out focus:outline-none focus:ring-4 focus:ring-indigo-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
        </button>
    </div>

    <div id="chatModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-[9999] modal-transition modal-hidden">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm mx-auto overflow-hidden transform">
            <div class="p-6 text-center">
                <div class="flex justify-center items-center mx-auto bg-indigo-100 rounded-full w-20 h-20 mb-5">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white">Mulai Chat</h3>
                <p class="text-gray-500 dark:text-gray-400 mt-2 mb-6">Anda akan diarahkan ke halaman chat untuk berkomunikasi dengan customer.</p>
                <a href="{{ route('admin.chat.index') }}" target="_blank" class="w-full block bg-gradient-to-r from-indigo-500 to-purple-500 text-white font-semibold py-3 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300">
                    Lanjutkan ke Chat
                </a>
                <button id="closeModalButton" class="w-full mt-3 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-semibold py-3 px-4 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    Batal
                </button>
            </div>
        </div>
    </div>
    
    {{-- SweetAlert Scripts --}}
    @if(session('success'))
    <script>
        Swal.fire({ title: 'Berhasil!', text: "{{ session('success') }}", icon: 'success', confirmButtonColor: '#16a34a' });
    </script>
    @endif
    @if(session('error'))
    <script>
        Swal.fire({ title: 'Gagal!', text: "{{ session('error') }}", icon: 'error', confirmButtonColor: '#dc2626' });
    </script>
    @endif

    {{-- Pusher and Echo Scripts --}}
    @auth
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>
    
    @if(strtolower(Auth::user()->role) === 'admin')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ======================================================================
            // == BAGIAN BARU: Fungsi untuk Notifikasi Browser & Meminta Izin ==
            // ======================================================================
            function requestNotificationPermission() {
                if ('Notification' in window) {
                    if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
                        Notification.requestPermission().then(permission => {
                            if (permission === 'granted') {
                                console.log('Izin notifikasi browser diberikan.');
                                new Notification('Terima Kasih!', {
                                    body: 'Anda akan menerima notifikasi di sini.',
                                    icon: 'https://tokosancaka.biz.id/storage/uploads/sancaka.png'
                                });
                            }
                        });
                    }
                }
            }
            
            function showBrowserNotification(title, message, url) {
                if (!('Notification' in window) || Notification.permission !== 'granted') {
                    return; // Jangan lakukan apa-apa jika tidak diizinkan
                }

                const notification = new Notification(title, {
                    body: message,
                    icon: 'https://tokosancaka.biz.id/storage/uploads/sancaka.png' // Icon notifikasi
                });

                // Jika ada URL, buat notifikasi bisa diklik
                if (url) {
                    notification.onclick = function() {
                        window.open(url, '_blank');
                    };
                }
            }
            
            // Meminta izin saat halaman pertama kali dimuat
            requestNotificationPermission();

            // ======================================================================

            if (window.EchoInitialized) return;

            if (typeof window.Echo !== 'undefined' && typeof window.Pusher !== 'undefined') {
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    window.Echo = new Echo({
                        broadcaster: 'pusher',
                        key: '{{ config('broadcasting.connections.pusher.key') }}',
                        cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
                        forceTLS: true,
                        authEndpoint: '/broadcasting/auth',
                        auth: { headers: { 'X-CSRF-TOKEN': csrfToken } },
                    });
                    
                    window.EchoInitialized = true;
                    console.log("Laravel Echo initialized for admin.");

                    window.Echo.private('admin-notifications')
                        .on('pusher:subscription_succeeded', () => console.log("Subscribed to 'admin-notifications' channel!"))
                        .on('pusher:subscription_error', (status) => console.error("Subscription to 'admin-notifications' failed. Status:", status))
                        
                        // ======================================================================
                        // == PERUBAHAN: Memanggil KEDUA jenis notifikasi ==
                        // ======================================================================
                        .listen('AdminNotificationEvent', (e) => {
                            console.log('Notifikasi diterima:', e);

                            // 1. Tampilkan Notifikasi Browser (Pojok Kanan Bawah)
                            showBrowserNotification(e.title, e.message, e.url);

                            // 2. Tampilkan Notifikasi Pop-up (Tengah Layar)
                            Swal.fire({
                                title: e.title || 'Notifikasi Baru',
                                text: e.message,
                                icon: 'info',
                                showCancelButton: true,
                                confirmButtonText: 'Lihat Detail',
                                cancelButtonText: 'Tutup',
                                confirmButtonColor: '#4f46e5',
                                cancelButtonColor: '#6b7280',
                            }).then((result) => {
                                if (result.isConfirmed && e.url) {
                                    window.location.href = e.url;
                                }
                            });
                        });
                        // ======================================================================

                } catch (error) { console.error("Failed to initialize Echo:", error); }
            } else { console.error("Echo or Pusher.js not found."); }
        });
    </script>
    @endif
    @endauth

    {{-- Chat Modal Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            const chatButton = document.getElementById('chatButton');
            const chatModal = document.getElementById('chatModal');
            const closeModalButton = document.getElementById('closeModalButton');
            if (chatButton && chatModal && closeModalButton) {
                const openModal = () => chatModal.classList.replace('modal-hidden', 'modal-visible');
                const closeModal = () => chatModal.classList.replace('modal-visible', 'modal-hidden');
                chatButton.addEventListener('click', openModal);
                closeModalButton.addEventListener('click', closeModal);
                chatModal.addEventListener('click', (event) => {
                    if (event.target === chatModal) closeModal();
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>

