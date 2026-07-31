<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Panel') - {{ config('app.name', 'Sancaka Express') }}</title>

    <link rel="icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }

        /* Custom Scrollbar yang rapi */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Utility */
        .modal-transition { transition: opacity 0.3s ease, transform 0.3s ease; }
        .modal-hidden { opacity: 0; transform: scale(0.95); pointer-events: none; }
        .modal-visible { opacity: 1; transform: scale(1); pointer-events: auto; }

        /* Teks Vertikal untuk tombol Monitor */
        .writing-vertical { writing-mode: vertical-rl; text-orientation: mixed; }

        /* Preloader Styles */
        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #ffffff;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s ease;
        }

        .loader-logo {
            width: 120px;
            margin-bottom: 20px;
            animation: pulse 2s infinite ease-in-out;
        }

        /* Animasi Loading Titik-Titik ala Shopee */
        .shopee-loader {
            display: flex;
            gap: 8px;
        }

        .shopee-loader div {
            width: 12px;
            height: 12px;
            background-color: #ee4d2d;
            border-radius: 50%;
            animation: shopee-bounce 1.4s infinite ease-in-out both;
        }

        .shopee-loader div:nth-child(1) { animation-delay: -0.32s; }
        .shopee-loader div:nth-child(2) { animation-delay: -0.16s; }

        @keyframes shopee-bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1.0); }
        }

        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }
    </style>

    @stack('styles')
</head>

<body class="bg-gray-100 text-gray-800 font-sans antialiased text-sm h-screen overflow-hidden">

    @if(isset($error_message))
        <div class="bg-red-500 text-white text-center p-2 absolute top-0 w-full z-[60]">
            {{ $error_message }}
        </div>
    @endif

    <div id="preloader">
        <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka Logo" class="loader-logo">
        <div class="shopee-loader">
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>

    {{-- WRAPPER UTAMA --}}
    <div x-data="{ sidebarOpen: window.innerWidth > 1024 }"
         x-cloak
         @resize.window="sidebarOpen = window.innerWidth > 1024"
         class="flex h-screen w-full bg-gray-100">

        {{-- 1. SIDEBAR KIRI --}}
        @include('layouts.partials.sidebar')

        {{-- 2. AREA KANAN (Header + Konten + Sidebar Monitor) --}}
        <div class="flex-1 flex flex-col h-screen overflow-hidden relative">

            {{-- Header --}}
            @include('layouts.partials.header')

            @include('layouts.partials.right-sidebar')

            {{-- Main Content Area --}}
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 custom-scrollbar p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>

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

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ======================================================================
            // == FUNGSI NOTIFIKASI BROWSER
            // ======================================================================
            function requestNotificationPermission() {
                if ('Notification' in window) {
                    if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
                        Notification.requestPermission().then(permission => {
                            if (permission === 'granted') {
                                new Notification('Terima Kasih!', {
                                    body: 'Anda akan menerima notifikasi di sini.',
                                    icon: 'https://tokosancaka.com/storage/uploads/sancaka.png'
                                });
                            }
                        });
                    }
                }
            }

          function showBrowserNotification(title, message, url) {
                if (!('Notification' in window) || Notification.permission !== 'granted') {
                    return;
                }

                const notification = new Notification(title, {
                    body: message,
                    icon: 'https://tokosancaka.com/storage/uploads/sancaka.png',
                    requireInteraction: true // ✅ Notifikasi tidak akan hilang sampai diklik
                });

                notification.onclick = function() {
                    // ✅ Matikan suara jika notifikasi OS diklik
                    const audio = document.getElementById('adminNotifAudio');
                    if (audio) {
                        audio.pause();
                        audio.currentTime = 0;
                    }

                    if (url) {
                        window.open(url, '_blank');
                    }
                    notification.close();
                };
            }

            requestNotificationPermission();

            // ======================================================================
            // == LOGIKA BARU NOTIFIKASI DROPDOWN
            // ======================================================================

            async function markAndRedirect(notificationId, targetUrl) {
                try {
                    const response = await fetch(`/admin/notifications/mark-as-read/${notificationId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        const badge = document.getElementById('notification-count-badge');
                        if (badge && result.unread_count > 0) {
                            badge.textContent = result.unread_count;
                            badge.style.display = 'flex';
                        } else if (badge) {
                            badge.style.display = 'none';
                        }
                    }

                } catch (error) {
                    console.error('Gagal menandai notifikasi:', error);
                } finally {
                    window.location.href = targetUrl;
                }
            }

            function timeAgo(dateString) {
                const date = new Date(dateString);
                const seconds = Math.floor((new Date() - date) / 1000);
                let interval = seconds / 31536000;
                if (interval > 1) return Math.floor(interval) + " tahun lalu";
                interval = seconds / 2592000;
                if (interval > 1) return Math.floor(interval) + " bulan lalu";
                interval = seconds / 86400;
                if (interval > 1) return Math.floor(interval) + " hari lalu";
                interval = seconds / 3600;
                if (interval > 1) return Math.floor(interval) + " jam lalu";
                interval = seconds / 60;
                if (interval > 1) return Math.floor(interval) + " menit lalu";
                return Math.floor(seconds) + " detik lalu";
            }

            async function loadInitialNotifications() {
                try {
                    const response = await fetch('{{ route('admin.notifications.getUnread') }}');
                    if (!response.ok) throw new Error('Network response was not ok');

                    const data = await response.json();

                    const listBody = document.getElementById('notification-list-body');
                    const emptyState = document.getElementById('notification-empty-state');
                    const badge = document.getElementById('notification-count-badge');

                    if (!listBody || !emptyState || !badge) return;

                    listBody.innerHTML = '';

                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }

                    if (data.notifications.length === 0) {
                        emptyState.style.display = 'table-row-group';
                    } else {
                        emptyState.style.display = 'none';

                        data.notifications.forEach(notification => {
                            const notifData = notification.data;
                            const title = notifData.judul || 'Notifikasi';
                            const message = notifData.pesan_utama || 'Tidak ada detail.';
                            const url = notifData.url || '#';
                            const hasLocation = notifData.latitude && notifData.longitude;
                            const locationUrl = `https://www.google.com/maps?q=${notifData.latitude},${notifData.longitude}`;

                            let lacakButtonHtml = '';
                            if (hasLocation) {
                                lacakButtonHtml = `
                                    <a href="${locationUrl}" target="_blank" onclick="event.stopPropagation()"
                                       class="inline-flex items-center gap-1.5 text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 transition-colors font-medium">
                                        <i class="fas fa-map-marker-alt w-3 h-3"></i> Lacak
                                    </a>`;
                            }

                            const lihatButtonHtml = `
                                <button onclick="event.preventDefault(); markAndRedirect('${notification.id}', '${url}')"
                                   class="inline-flex items-center gap-1.5 text-xs px-2 py-1 bg-green border border-gray-300 text-gray-700 rounded-md hover:bg-green-50 transition-colors font-medium">
                                    <i class="fas fa-eye w-3 h-3"></i> Lihat
                                </button>`;

                            const row = document.createElement('tr');
                            row.className = 'hover:bg-gray-50';

                            row.innerHTML = `
                                <td class="px-4 py-3 align-top w-2/3 overflow-hidden break-words">
                                    <p class="text-sm font-semibold text-gray-900">${title}</p>
                                    <p class="text-sm text-gray-600 mt-1">${message}</p>
                                    <p class="text-xs text-gray-400 mt-2">${timeAgo(notification.created_at)}</p>
                                </td>
                                <td class="px-4 py-3 align-top text-center w-1/3">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        ${lihatButtonHtml}
                                        ${lacakButtonHtml}
                                    </div>
                                </td>
                            `;
                            listBody.appendChild(row);
                        });
                    }
                } catch (error) {
                    console.error('Gagal memuat notifikasi:', error);
                    const listBody = document.getElementById('notification-list-body');
                    if(listBody) {
                         listBody.innerHTML = `<tr><td class="text-red-500 p-4">Gagal memuat notifikasi.</td></tr>`;
                    }
                }
            }

            async function fetchNotificationCount() {
                try {
                    const response = await fetch('{{ route('admin.notifications.count') }}');
                    if (!response.ok) return;

                    const data = await response.json();
                    const badge = document.getElementById('notification-count-badge');

                    if (data.count > 0) {
                        badge.textContent = data.count > 9 ? '9+' : data.count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                } catch (error) {
                    console.warn('Gagal mengambil hitungan notifikasi:', error);
                }
            }

            fetchNotificationCount();
            window.loadInitialNotifications = loadInitialNotifications;

            // ======================================================================
            // == INISIALISASI LARAVEL ECHO
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

                  // Listener untuk Notifikasi Umum (Database)
            const userId = {{ auth()->id() }};
            window.Echo.private(`App.Models.User.${userId}`)
                .on('pusher:subscription_succeeded', () => console.log('Subscribed to User Channel!'))
                .on('pusher:subscription_error', (status) => console.error(`Subscription failed. Status:`, status))
                .notification((notification) => {
                    const data = notification.data ? notification.data : notification;
                    showBrowserNotification(data.judul, data.pesan_utama, data.url);
                    fetchNotificationCount();

                    // ✅ Audio muter terus untuk Echo
                    if (sessionStorage.getItem('audio_allowed') === 'true') {
                        const audio = document.getElementById('adminNotifAudio');
                        if (audio) {
                            audio.currentTime = 0;
                            audio.loop = true; // ✅ Bikin suara memutar terus
                            audio.play().catch(err => console.log("Gagal play audio via Echo:", err));
                        }
                    }
                });

                } catch (error) { console.error("Failed to initialize Echo:", error); }
            } else { console.error("Echo or Pusher.js not found."); }
        });
    </script>
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

    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnToggle = document.getElementById('btn-toggle-sidebar');
        const btnClose = document.getElementById('btn-close-sidebar');
        const sidebar = document.getElementById('main-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const icon = document.getElementById('toggle-icon');

        function toggleSidebar() {
            if (!sidebar) return;
            sidebar.classList.toggle('-translate-x-full');
            if (overlay) overlay.classList.toggle('hidden');

            if (icon) {
                if (sidebar.classList.contains('-translate-x-full')) {
                    icon.classList.add('rotate-180');
                } else {
                    icon.classList.remove('rotate-180');
                }
            }
        }

        if(sidebar && icon) {
             if (sidebar.classList.contains('-translate-x-full')) {
                icon.classList.add('rotate-180');
            } else {
                icon.classList.remove('rotate-180');
            }
        }

        if(btnToggle) btnToggle.addEventListener('click', toggleSidebar);
        if(btnClose) btnClose.addEventListener('click', toggleSidebar);
        if(overlay) overlay.addEventListener('click', toggleSidebar);
    });
    </script>

    <script>
        (function() {
            const preloader = document.getElementById('preloader');
            if (sessionStorage.getItem('sancaka_loaded')) {
                preloader.style.display = 'none';
            } else {
                window.addEventListener('load', function() {
                    setTimeout(() => {
                        preloader.style.opacity = '0';
                        setTimeout(() => {
                            preloader.style.display = 'none';
                            sessionStorage.setItem('sancaka_loaded', 'true');
                        }, 1000);
                    }, 1000);
                });
            }
        })();
    </script>

    @livewireScripts

   {{-- ===================================================================== --}}
    {{-- TOMBOL AKTIVASI AUDIO (MENGATASI AUTOPLAY POLICY BROWSER) --}}
    {{-- ===================================================================== --}}
    <div id="audioActivationBanner" style="display:none;" class="fixed bottom-4 right-4 z-50 bg-yellow-500 text-white px-4 py-3 rounded-lg shadow-xl flex items-center gap-3">
        <span>⚠️ Klik untuk mengaktifkan suara notifikasi pesanan masuk!</span>
        <button id="btnEnableAudio" class="bg-black text-white px-3 py-1 rounded text-xs font-bold hover:bg-gray-800">Aktifkan</button>
    </div>

    <!-- Elemen Audio -->
    <audio id="adminNotifAudio" src="https://tokosancaka.com/public/assets/ojek.wav" preload="auto"></audio>

    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/12.17.0/firebase-app.js";
        import { getMessaging, getToken, onMessage } from "https://www.gstatic.com/firebasejs/12.17.0/firebase-messaging.js";

        const firebaseConfig = {
            apiKey: "AIzaSyBd4Rl2pnQlr-mYQSVZamWnCkvpi5anU8w",
            authDomain: "sancaka-express.firebaseapp.com",
            databaseURL: "https://sancaka-express-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "sancaka-express",
            storageBucket: "sancaka-express.firebasestorage.app",
            messagingSenderId: "960582735209",
            appId: "1:960582735209:web:710a898b750150824ad9f8",
            measurementId: "G-Z1V0BHLZ6P"
        };

        const app = initializeApp(firebaseConfig);
        const messaging = getMessaging(app);

        // --- SOLUSI AUTOPLAY AUDIO ---
        const audio = document.getElementById('adminNotifAudio');
        const banner = document.getElementById('audioActivationBanner');
        const btnEnable = document.getElementById('btnEnableAudio');

        // Cek apakah audio sudah diizinkan sebelumnya di session ini
        if (!sessionStorage.getItem('audio_allowed')) {
            banner.style.display = 'flex';
        }

        btnEnable.addEventListener('click', function() {
            audio.play().then(() => {
                audio.pause(); // Mainkan lalu langsung pause untuk "membuka kunci" izin browser
                audio.currentTime = 0;
                sessionStorage.setItem('audio_allowed', 'true');
                banner.style.display = 'none';
                console.log("Audio berhasil diaktifkan oleh user!");
            }).catch(e => {
                console.error("Gagal mengaktifkan audio: ", e);
            });
        });
        // -----------------------------

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/firebase-messaging-sw.js')
            .then(function(registration) {

                // Minta Izin Notifikasi Browser secara eksplisit
                Notification.requestPermission().then((permission) => {
                    if (permission === 'granted') {
                        getToken(messaging, {
                            vapidKey: "BGF6BWiam42tA9GQB4mdp3C01ZJ8vk9_vQ9RzkHQUG2l7P1L3niAmiFhcp3gZHYXrtXT76qGuUIZ5QkAaDqiki8",
                            serviceWorkerRegistration: registration
                        })
                        .then((currentToken) => {
                            if (currentToken) {
                                fetch("{{ url('/admin/save-fcm-token') }}", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "Accept": "application/json",
                                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    },
                                    body: JSON.stringify({ fcm_token: currentToken })
                                });
                            }
                        });
                    } else {
                        console.log('Izin notifikasi browser ditolak oleh pengguna.');
                    }
                });

            });
        }

       // Tangkap Notifikasi saat Tab Terbuka (Foreground)
        onMessage(messaging, (payload) => {
            console.log("Foreground Payload: ", payload);

            const audio = document.getElementById('adminNotifAudio');

            // Bunyikan Audio TERUS-MENERUS
            if (sessionStorage.getItem('audio_allowed') === 'true' && audio) {
                audio.currentTime = 0;
                audio.loop = true; // ✅ Bikin suara memutar terus
                audio.play().catch(err => console.log("Gagal play audio:", err));
            }

            // Tampilkan SweetAlert Toast yang TIDAK HILANG sebelum diklik
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'info',
                title: payload.notification?.title || 'Pesanan Baru Masuk!',
                text: payload.notification?.body || 'Silakan cek daftar pesanan.',
                showConfirmButton: true,
                confirmButtonText: 'Lihat',
                confirmButtonColor: '#000000',
                showCloseButton: true, // ✅ Tambah tombol silang
                allowOutsideClick: false, // ✅ Jangan hilang kalau diklik di luar kotak
                // ❌ Timer dihapus agar tidak tertutup otomatis
            }).then((result) => {
                // ✅ Matikan suara ketika admin merespon (klik Lihat atau Close)
                if (sessionStorage.getItem('audio_allowed') === 'true' && audio) {
                    audio.pause();
                    audio.currentTime = 0;
                }

                if (result.isConfirmed) {
                    window.location.href = "{{ route('admin.pesanan-autokirim.index') }}";
                }
            });

            // Munculkan juga Banner Bawaan Windows/Mac
            if (Notification.permission === 'granted') {
                const browserNotif = new Notification(payload.notification?.title || 'Pesanan Baru', {
                    body: payload.notification?.body || 'Ada pesanan masuk.',
                    icon: 'https://tokosancaka.com/storage/uploads/sancaka.png',
                    requireInteraction: true // ✅ Bikin notifikasi OS tidak hilang sendiri
                });

                // ✅ Matikan suara kalau banner OS diklik
                browserNotif.onclick = function() {
                    if (sessionStorage.getItem('audio_allowed') === 'true' && audio) {
                        audio.pause();
                        audio.currentTime = 0;
                    }
                    window.focus();
                    browserNotif.close();
                };
            }

            if (typeof window.fetchNotificationCount === "function") {
                window.fetchNotificationCount();
            }
        });
    </script>


</body>
</html>
