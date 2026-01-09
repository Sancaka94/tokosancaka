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

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
    background-color: #ffffff; /* Latar belakang putih bersih */
    z-index: 9999;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    transition: opacity 0.5s ease; /* Mencegah tampilan konten 'melompat' saat preloader aktif */
}

.loader-logo {
    width: 120px; /* Ukuran logo Sancaka */
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
    background-color: #ee4d2d; /* Warna orange kemerahan */
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
    
    <!-- Tombol & Modal Chat (Global) -->
    {{-- ... (Modal Chat Anda, biarkan saja) ... --}}
    
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
            // == FUNGSI NOTIFIKASI BROWSER (Ini sudah benar, biarkan)
            // ======================================================================
            function requestNotificationPermission() {
                if ('Notification' in window) {
                    if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
                        Notification.requestPermission().then(permission => {
                            if (permission === 'granted') {
                                console.log('Izin notifikasi browser diberikan.');
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
                    return; // Jangan lakukan apa-apa jika tidak diizinkan
                }

                const notification = new Notification(title, {
                    body: message,
                    icon: 'https://tokosancaka.com/storage/uploads/sancaka.png' // Icon notifikasi
                });

                if (url) {
                    notification.onclick = function() {
                        window.open(url, '_blank');
                    };
                }
            }
            
            // Meminta izin saat halaman pertama kali dimuat
            requestNotificationPermission();

            // ======================================================================
            // == [MULAI] LOGIKA BARU NOTIFIKASI DROPDOWN (MENGGANTIKAN KODE LAMA)
            // ======================================================================
            
            /**
             * [BARU] Fungsi untuk menandai notifikasi sebagai dibaca, lalu mengarahkan.
             */
            async function markAndRedirect(notificationId, targetUrl) {
                try {
                    // 1. Tandai sebagai dibaca di server
                    const response = await fetch(`/admin/notifications/mark-as-read/${notificationId}`, {
                        method: 'POST', // Pastikan rute Anda menerima POST
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                    });
                    
                    const result = await response.json();

                    if (result.status === 'success') {
                        // 2. Perbarui badge hitungan secara manual
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
                    // 3. Arahkan pengguna ke URL tujuan, tidak peduli sukses atau gagal
                    window.location.href = targetUrl;
                }
            }

            /**
             * Helper untuk format 'time ago' (disederhanakan)
             */
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

            /**
             * Memuat notifikasi (5 terakhir) dan mengisinya ke dalam TABEL dropdown.
             * (Ini akan dipanggil oleh Alpine.js @click di header)
             */
            async function loadInitialNotifications() {
                try {
                    // Pastikan rute ini benar
                    const response = await fetch('{{ route('admin.notifications.getUnread') }}'); 
                    if (!response.ok) throw new Error('Network response was not ok');
                    
                    const data = await response.json();

                    // [PENTING] Ini adalah ID baru dari HTML Anda
                    const listBody = document.getElementById('notification-list-body');
                    const emptyState = document.getElementById('notification-empty-state');
                    const badge = document.getElementById('notification-count-badge');

                    // Pengaman jika elemen tidak ditemukan
                    if (!listBody || !emptyState || !badge) {
                        console.error('Elemen notifikasi (list-body/empty/badge) tidak ditemukan di DOM.');
                        return;
                    }

                    listBody.innerHTML = ''; // Selalu kosongkan list

                    // Update badge
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }

                    // Tampilkan status kosong jika tidak ada notifikasi
                    if (data.notifications.length === 0) {
                        emptyState.style.display = 'table-row-group'; // Tipe display untuk tbody
                    } else {
                        emptyState.style.display = 'none';

                        // Isi tabel dengan data notifikasi
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
                                        <i class="fas fa-map-marker-alt w-3 h-3"></i>
                                        Lacak
                                    </a>`;
                            }
                            
                             // Tombol "Lihat"
                            const lihatButtonHtml = `
                                <button onclick="event.preventDefault(); markAndRedirect('${notification.id}', '${url}')"
                                   class="inline-flex items-center gap-1.5 text-xs px-2 py-1 bg-green border border-gray-300 text-gray-700 rounded-md hover:bg-green-50 transition-colors font-medium">
                                    <i class="fas fa-eye w-3 h-3"></i>
                                    Lihat
                                </button>`;

                            // Buat baris tabel (tr)
                            const row = document.createElement('tr');
                            row.className = 'hover:bg-gray-50';
                            
                            // [PERBAIKAN DARI ANDA] Menggunakan break-words alih-alih truncate
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

            /**
             * Fungsi terpisah HANYA untuk mengambil jumlah (untuk badge)
             */
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
            
            // Panggil hitungan saat halaman dimuat
            fetchNotificationCount();
            
            // [PENTING] Buat fungsi loadInitialNotifications TERSEDIA SECARA GLOBAL
            // agar Alpine.js di header.blade.php bisa memanggilnya
            // (Tombol @click di header ada di file lain, jadi fungsi ini harus global)
            window.loadInitialNotifications = loadInitialNotifications;

            // ======================================================================
            // == [AKHIR] LOGIKA BARU NOTIFIKASI
            // ======================================================================
            

            // ======================================================================
            // == INISIALISASI LARAVEL ECHO (Ini sudah benar, biarkan)
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

                    // Listener untuk 'AdminNotificationEvent' (Sudah ada)
                    window.Echo.private('admin-notifications')
                        .on('pusher:subscription_succeeded', () => console.log("Subscribed to 'admin-notifications' channel!"))
                        .on('pusher:subscription_error', (status) => console.error("Subscription to 'admin-notifications' failed. Status:", status))
                        .listen('AdminNotificationEvent', (e) => {
                            console.log('Notifikasi (AdminEvent) diterima:', e);
                            showBrowserNotification(e.title, e.message, e.url);
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
                            // [PERBAIKAN] Saat notifikasi baru masuk, cukup update angkanya
                            fetchNotificationCount();
                        });
                        
                    // Listener untuk Notifikasi Umum (Database)
                    const userId = {{ auth()->id() }};
                    window.Echo.private(`App.Models.User.${userId}`)
                        .on('pusher:subscription_succeeded', () => console.log(`Subscribed to 'App.Models.User.${userId}' channel!`))
                        .on('pusher:subscription_error', (status) => console.error(`Subscription to 'App.Models.User.${userId}' failed. Status:`, status))
                        .notification((notification) => {
                            
                            console.log('NOTIFIKASI BARU DITERIMA (dari NotifikasiUmum):', notification);
                            
                            const data = notification.data ? notification.data : notification;

                            // Tampilkan notifikasi di browser (Pop-up Desktop)
                            showBrowserNotification(
                                data.judul,      // <-- DIPERBAIKI
                                data.pesan_utama, // <-- DIPERBAIKI
                                data.url          // <-- DIPERBAIKI
                            );
                            
                            // [PERBAIKAN] Update angka badge. 
                            // Daftar lengkap akan di-refresh saat user mengklik lonceng.
                            fetchNotificationCount(); 
                        });

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

    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnToggle = document.getElementById('btn-toggle-sidebar'); // Tombol hamburger (opsional)
        const btnClose = document.getElementById('btn-close-sidebar');   // Tombol bulat floating
        const sidebar = document.getElementById('main-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const icon = document.getElementById('toggle-icon'); // <--- Ambil element Icon

        function toggleSidebar() {
            if (!sidebar) return;

            // 1. Buka/Tutup Sidebar
            sidebar.classList.toggle('-translate-x-full');
            
            // 2. Tampilkan/Sembunyikan Overlay
            if (overlay) overlay.classList.toggle('hidden');

            // 3. LOGIKA ROTASI PANAH
            if (icon) {
                // Cek apakah sidebar sedang SEMBUNYI (ada class -translate-x-full)
                if (sidebar.classList.contains('-translate-x-full')) {
                    // Jika sembunyi, putar panah jadi KANAN (tambah class rotate-180)
                    icon.classList.add('rotate-180');
                } else {
                    // Jika terbuka, kembalikan panah jadi KIRI (hapus class rotate-180)
                    icon.classList.remove('rotate-180');
                }
            }
        }

        // Jalankan logika rotasi sekali saat halaman dimuat 
        // untuk memastikan arah panah sesuai status awal sidebar
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
    // Fungsi untuk mengontrol preloader agar hanya muncul sekali per sesi
    (function() {
        const preloader = document.getElementById('preloader');
        
        // Cek apakah user sudah pernah melihat loading di sesi ini
        if (sessionStorage.getItem('sancaka_loaded')) {
            // Jika sudah pernah, langsung hilangkan preloader tanpa animasi
            preloader.style.display = 'none';
        } else {
            // Jika ini kunjungan pertama di sesi ini, jalankan animasi loading
            window.addEventListener('load', function() {
                setTimeout(() => {
                    preloader.style.opacity = '0';
                    setTimeout(() => {
                        preloader.style.display = 'none';
                        // Simpan status agar tidak muncul lagi saat pindah menu
                        sessionStorage.setItem('sancaka_loaded', 'true');
                    }, 1000);
                }, 1000); // Durasi loading awal (bisa dikurangi jika dirasa kelamaan)
            });
        }
    })();
</script>

</body>
</html>