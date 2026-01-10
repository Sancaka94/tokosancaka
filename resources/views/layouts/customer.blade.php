<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Dashboard Pelanggan - {{ config('app.name', 'Sancaka Express') }}</title>

    {{-- CDN & Fonts --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        /* Hilangkan scrollbar di sidebar/main tapi tetap bisa discroll (Opsional, biar rapi) */
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    
    @stack('styles')
</head>

@include('components.sandbox_alert')

{{-- 
    KUNCI UTAMA 1: 
    h-screen & overflow-hidden di BODY/WRAPPER.
    Ini memaksa website tingginya FIX 100% layar monitor. 
    Tidak akan ada scrollbar di body kanan (browser).
--}}
<body class="bg-gray-100 text-gray-800 antialiased h-screen overflow-hidden flex">

    <div x-data="{ sidebarOpen: false }" class="flex w-full h-full">
        
        {{-- 
            KUNCI UTAMA 2: SIDEBAR 
            Include Sidebar di sini. Flexbox akan otomatis membuatnya sejajar.
            Sidebar di file partial Anda harus punya class 'h-full' agar tidak kepotong.
            (Tapi secara default flex item akan stretch height, jadi aman).
        --}}
        @include('layouts.partials.customer.sidebar')

        {{-- 
            KUNCI UTAMA 3: KOLOM KANAN (Header + Main + Footer)
            flex-1: Ambil sisa lebar.
            flex-col: Susun ke bawah.
            h-full: Tinggi mentok layar.
            overflow-hidden: Cegah scrollbar ganda.
        --}}
        <div class="flex-1 flex flex-col h-full overflow-hidden relative">
            
            @include('layouts.partials.customer.topbar')

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 scroll-smooth">
                <div class="container mx-auto px-6 py-8">
                    @yield('content')
                </div>
            </main>

            <footer class="bg-white text-gray-600 border-t border-gray-200 z-20 shrink-0">
                <div class="container px-5 py-3 mx-auto flex items-center sm:flex-row flex-col">
                    <a class="flex title-font font-medium items-center md:justify-start justify-center text-gray-900">
                         <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Logo" class="w-6 h-6">
                        <span class="ml-2 text-base font-semibold">Sancaka Express</span>
                    </a>
                    <p class="text-xs text-gray-500 sm:ml-4 sm:pl-4 sm:border-l-2 sm:border-gray-200 sm:py-2 sm:mt-0 mt-2">
                        © {{ date('Y') }} Sancaka Express
                    </p>
                    <span class="inline-flex sm:ml-auto sm:mt-0 mt-2 justify-center sm:justify-start">
                        <a href="#" class="text-gray-400 hover:text-blue-600"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="ml-3 text-gray-400 hover:text-blue-400"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="ml-3 text-gray-400 hover:text-pink-600"><i class="fab fa-instagram"></i></a>
                    </span>
                </div>
            </footer>

        </div>
    </div>
    
    
   {{-- ================================================================= --}}
    {{-- KODE JAVASCRIPT UTAMA (INI YANG KITA PERBARUI) --}}
    {{-- ================================================================= --}}
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.14.1/echo.iife.js"></script>
    <script>
    document.addEventListener('alpine:init', () => {
        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: '7a59606602fcfa7ae2d5', // Key Pusher Anda
            cluster: 'mt1',             // Cluster Pusher Anda
            forceTLS: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            }
        });

        let userId = {{ auth()->id() }};

        // 1. Ambil elemen HTML dari header (topbar.blade.php)
        const notificationBadge = document.getElementById('notification-count-badge');
        const notificationList = document.getElementById('notification-list');
        const notificationEmpty = document.getElementById('notification-empty-state');
        // 👇 TAMBAHKAN ELEMEN SALDO BARU
        const saldoDesktop = document.getElementById('saldo-desktop');
        const saldoMobile = document.getElementById('saldo-mobile');
        let currentNotificationCount = 0;

        // 2. Fungsi untuk memformat 1 notifikasi (HTML)
        function formatNotificationHTML(notification) {
            const data = notification.data ? notification.data : notification;
            const icon = data.icon || 'fas fa-info-circle';
            const url = data.url || '#';
            const title = data.judul || 'Notifikasi';
            const message = data.pesan_utama || 'Anda memiliki notifikasi baru.';
            
            // TODO: Ganti 'data.id' dengan ID notifikasi yang benar (mungkin 'notification.id')
            // Ini untuk fitur "mark as read" nanti
            // const notificationId = notification.id; 

            return `
                <a href="${url}" class="flex items-start p-3 hover:bg-gray-100 transition-colors">
                    <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-indigo-100 rounded-full">
                        <i class="${icon} text-indigo-500 text-sm"></i>
                    </div>
                    <div class="ml-3 w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-800">${title}</p>
                        <p class="text-sm text-gray-500 truncate">${message}</p>
                    </div>
                </a>
            `;
        }

        // 👇 TAMBAHKAN FUNGSI BARU UNTUK FORMAT RUPIAH
        function formatRupiah(number) {
            if (isNaN(number)) {
                return 'Rp -';
            }
            // Format ke "Rp X.XXX.XXX"
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(number).replace('Rp', 'Rp '); // Pastikan ada spasi
        }

        // 3. Fungsi untuk update badge angka
        function updateCount(count) {
            currentNotificationCount = count;
            if (count > 0) {
                notificationBadge.innerText = count;
                notificationBadge.style.display = 'flex'; 
                notificationEmpty.style.display = 'none';
            } else {
                notificationBadge.style.display = 'none'; 
                notificationEmpty.style.display = 'block';
            }
        }

        // 4. Fungsi untuk menambahkan notifikasi BARU ke ATAS daftar
        function addNewNotification(notification) {
            const html = formatNotificationHTML(notification);
            notificationList.insertAdjacentHTML('afterbegin', html); 
            updateCount(currentNotificationCount + 1); 
        }

        // 5. [SAAT HALAMAN DIMUAT] Ambil notifikasi awal
        function loadInitialNotifications() {
            fetch("{{ route('customer.notifications.unread') }}") 
                .then(response => response.json())
                .then(data => {
                    if (data.notifications && data.notifications.length > 0) {
                        let html = '';
                        data.notifications.forEach(notif => {
                            html += formatNotificationHTML(notif);
                        });
                        notificationList.innerHTML = html;
                        updateCount(data.unread_count); 
                    } else {
                        updateCount(0); 
                    }
                })
                .catch(error => console.error('Gagal memuat notifikasi:', error));
        }

        // Panggil fungsi ini saat halaman dimuat
        loadInitialNotifications();

        // 6. [REAL-TIME] Listener Echo untuk Notifikasi BARU
        window.Echo.private(`App.Models.User.${userId}`)
            .notification((notification) => {
                console.log('NOTIFIKASI BARU DITERIMA:', notification);
                
                const data = notification.data ? notification.data : notification;

                if (Notification.permission === "granted") {
                    new Notification(data.judul, { // <-- Sudah diperbaiki
                        body: data.pesan_utama, // <-- Sudah diperbaiki
                        icon: 'https://tokosancaka.com/storage/uploads/sancaka.png' 
                    });
                }
                
                addNewNotification(notification);
            });

        // ==========================================================
        // 👇 LOGIKA SALDO SEKARANG DILENGKAPI
        // ==========================================================
        window.Echo.private(`customer-saldo.${userId}`)
            .listen('.SaldoUpdated', (data) => {
                
                console.log('EVENT SALDO DITERIMA:', data);

                // Periksa apakah data 'new_saldo' ada di dalam event
                if (data.new_saldo !== undefined) {
                    const formattedSaldo = formatRupiah(data.new_saldo);

                    // Update Saldo di Desktop
                    if (saldoDesktop) {
                        saldoDesktop.innerHTML = formattedSaldo;
                    }
                    
                    // Update Saldo di Mobile
                    if (saldoMobile) {
                        saldoMobile.innerHTML = formattedSaldo;
                    }

                    // Tampilkan notifikasi toast (pop-up kecil)
                    Swal.fire({
                        title: 'Saldo Diperbarui!',
                        text: data.message || `Saldo Anda sekarang ${formattedSaldo}`,
                        icon: 'success',
                        timer: 3500, // Tutup otomatis setelah 3.5 detik
                        showConfirmButton: false,
                        toast: true, // Jadikan sebagai toast
                        position: 'top-end', // Tampil di pojok kanan atas
                        timerProgressBar: true
                    });
                } else {
                    console.warn('Event SaldoUpdated diterima, tapi tidak ada data new_saldo.');
                }
            });
        // ==========================================================
        // 👆 AKHIR LOGIKA SALDO
        // ==========================================================

        // 8. Minta Izin Notifikasi (biarkan saja)
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

      <!-- sebelum </body> -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    
    @stack('scripts')
</body>
</html>

