<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{
    darkMode: localStorage.getItem('darkMode') === 'true'
}" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
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

    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }

        /* === CSS UNTUK MODAL CHAT === */
        .modal-transition {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .modal-hidden {
            opacity: 0;
            transform: scale(0.95);
            pointer-events: none;
        }
        .modal-visible {
            opacity: 1;
            transform: scale(1);
            pointer-events: auto;
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-100 dark:bg-gray-900">

    <div x-data="{ sidebarOpen: false }" class="flex h-screen bg-gray-100 dark:bg-gray-900" style="
    width: 100%;
">
        
        {{-- Sidebar --}}
        @include('layouts.partials.sidebar')

        <div class="flex-1 flex flex-col overflow-hidden">
            
            {{-- Header/Topbar --}}
            @include('layouts.partials.header')

            <!-- Main content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- === HTML UNTUK TOMBOL & MODAL CHAT === -->
    <!-- Tombol Chat Floating dengan Tooltip -->
    <div class="fixed bottom-8 right-8 z-50 group flex items-center">
        <!-- Tooltip -->
        <span class="absolute right-full mr-4 bg-gray-800 text-white text-sm font-medium px-3 py-1.5 rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
            Hubungi Customer
        </span>
        <!-- Tombol Chat Floating -->
        <button id="chatButton" class="bg-gradient-to-r from-indigo-500 to-purple-500 text-white w-16 h-16 rounded-full shadow-lg flex items-center justify-center hover:scale-110 transform transition-transform duration-300 ease-in-out focus:outline-none focus:ring-4 focus:ring-indigo-300">
            <!-- Ikon Chat (SVG) -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
        </button>
    </div>

    <!-- Modal Container -->
    <div id="chatModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-[9999] modal-transition modal-hidden">
        <!-- Konten Modal -->
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-auto overflow-hidden transform">
            <div class="p-6 text-center">
                <!-- Header Modal -->
                <div class="flex justify-center items-center mx-auto bg-indigo-100 rounded-full w-20 h-20 mb-5">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>

                <!-- Judul dan Deskripsi -->
                <h3 class="text-2xl font-bold text-gray-800">Mulai Chat</h3>
                <p class="text-gray-500 mt-2 mb-6">Anda akan diarahkan ke halaman chat untuk berkomunikasi dengan customer.</p>

                <!-- Tombol Aksi -->
                <a href="{{ route('admin.chat.index') }}" target="_blank" class="w-full block bg-gradient-to-r from-indigo-500 to-purple-500 text-white font-semibold py-3 px-4 rounded-lg shadow-md hover:shadow-lg hover:from-indigo-600 hover:to-purple-600 transition-all duration-300 ease-in-out">
                    Lanjutkan ke Chat
                </a>
                <button id="closeModalButton" class="w-full mt-3 bg-gray-100 text-gray-600 font-semibold py-3 px-4 rounded-lg hover:bg-gray-200 transition-colors duration-300">
                    Batal
                </button>
            </div>
        </div>
    </div>

    {{-- ====================================================================== --}}
    {{-- == BAGIAN SCRIPT NOTIFIKASI REAL-TIME == --}}
    {{-- ====================================================================== --}}
    
    {{-- 1. Memuat library Pusher dan Echo --}}
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@if(session('success'))
<script>
    Swal.fire({
        title: 'Berhasil!',
        text: "{{ session('success') }}",
        imageUrl: "{{ asset('public/assets/logo.jpg') }}", 
        imageWidth: 80,
        imageHeight: 80,
        confirmButtonColor: '#16a34a',
    });
</script>
@endif

@if(session('error'))
<script>
    Swal.fire({
        title: 'Gagal!',
        text: "{{ session('error') }}",
        imageUrl: "{{ asset('public/assets/logo.jpg') }}", 
        imageWidth: 80,
        imageHeight: 80,
        confirmButtonColor: '#dc2626',
    });
</script>
@endif
    {{-- 2. Menjalankan skrip inisialisasi notifikasi --}}
    @auth
    @if(strtolower(Auth::user()->role) === 'admin')
    <script>
        console.log("Admin notification script loaded."); 

        document.addEventListener('DOMContentLoaded', function() {
            
            function requestNotificationPermission() {
                if (!("Notification" in window)) { return; }
                if (Notification.permission === "granted" || Notification.permission === "denied") { return; }
                Notification.requestPermission().then(permission => {
                    if (permission === "granted") {
                        new Notification("Notifikasi Diaktifkan", {
                            body: "Anda akan menerima notifikasi real-time dari admin panel.",
                            icon: 'https://tokosancaka.biz.id/storage/uploads/sancaka.png'
                        });
                    }
                });
            }

            function showNotification(title, message, url) {
                if (Notification.permission !== "granted") { return; }
                const notification = new Notification(title, {
                    body: message,
                    icon: 'https://tokosancaka.biz.id/storage/uploads/sancaka.png'
                });
                notification.onclick = (event) => {
                    event.preventDefault(); 
                    window.open(url, '_blank');
                }
            }

            if (window.EchoInitialized) {
                return;
            }

            if (typeof window.Echo !== 'undefined' && typeof window.Pusher !== 'undefined') {
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    window.Echo = new Echo({
                        broadcaster: 'pusher',
                        key: '{{ config('broadcasting.connections.pusher.key') }}',
                        cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
                        forceTLS: true,
                        authEndpoint: '/broadcasting/auth',
                        auth: {
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                            },
                        },
                    });

                    window.EchoInitialized = true;

                    const adminChannel = window.Echo.private('admin-notifications');

                    adminChannel.on('pusher:subscription_succeeded', () => {
                        console.log("SUKSES: Berhasil terhubung ke channel 'admin-notifications'!");
                    });

                    adminChannel.on('pusher:subscription_error', (status) => {
                        console.error("GAGAL: Tidak dapat terhubung ke channel 'admin-notifications'. Status:", status);
                        console.error("Pastikan Anda sudah login sebagai admin dan channel di routes/channels.php sudah benar.");
                    });

                    adminChannel.listen('AdminNotificationEvent', (e) => {
                        console.log('%cEVENT DITERIMA:', 'color: #28a745; font-weight: bold;', e);
                        showNotification(e.title, e.message, e.url);
                    });

                    requestNotificationPermission();

                } catch (error) {
                    console.error("GAGAL INISIALISASI ECHO:", error);
                }
            } else {
                console.error("GAGAL MEMUAT LIBRARY: Laravel Echo atau Pusher.js tidak ditemukan.");
            }
        });
    </script>
    @endif
    @endauth

    <!-- === JAVASCRIPT UNTUK MODAL CHAT === -->
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            const chatButton = document.getElementById('chatButton');
            const chatModal = document.getElementById('chatModal');
            const closeModalButton = document.getElementById('closeModalButton');

            if (chatButton && chatModal && closeModalButton) {
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
                    if (event.target === chatModal) {
                        closeModal();
                    }
                });
            }
        });
    </script>

    {{-- 3. Menjalankan script lain dari halaman spesifik --}}
    @stack('scripts')
</body>
</html>
