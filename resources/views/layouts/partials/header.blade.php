{{-- resources/views/layouts/partials/header.blade.php --}}
<header class="bg-white shadow-sm sticky top-0 z-40">
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Sisi Kiri: Tombol Toggle & Judul Halaman -->
            <div class="flex items-center">
                <!-- Tombol toggle untuk desktop (lg dan lebih besar) -->
                <button id="desktopSidebarToggle" class="hidden lg:inline-flex items-center justify-center -ml-2 mr-3 p-2 rounded-md text-gray-500 hover:text-gray-600 hover:bg-gray-100">
                    <span class="sr-only">Toggle sidebar</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                
                <!-- Tombol toggle untuk mobile (di bawah lg) -->
                <button id="sidebarToggle" class="lg:hidden inline-flex items-center justify-center -ml-2 mr-3 p-2 rounded-md text-gray-500 hover:text-gray-600 hover:bg-gray-100">
                    <span class="sr-only">Buka menu</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>

                <h1 class="text-lg font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h1>
            </div>

            <!-- Sisi Kanan: Aksi & Profil -->
            <div class="ml-auto flex items-center space-x-2 sm:space-x-4">
                <!-- Saldo dan Tombol Top Up -->
                <div class="hidden md:flex items-center">
                    <span class="text-sm font-medium text-gray-600">Saldo:</span>
                    <span class="ml-2 text-sm font-semibold bg-green-100 text-green-800 py-1 px-3 rounded-full">
                        {{-- ✅ FIX: Menampilkan saldo dari database --}}
                        Rp {{ number_format($saldo, 0, ',', '.') }}
                    </span>
                    <button type="button" onclick="openTopupModal()" class="ml-2 inline-flex items-center gap-x-1.5 px-2.5 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                        <svg class="w-4 h-4 text-green-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        Top Up
                    </button>
                </div>

                <!-- Dropdown Notifikasi -->
                <div class="relative">
                    <button type="button" class="p-2 rounded-full text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none" onclick="toggleDropdown('notification-dropdown')">
                        <span class="sr-only">Lihat notifikasi</span>
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        {{-- ✅ FIX: Indikator notifikasi hanya muncul jika ada notifikasi baru --}}
                        @if(count($notifications) > 0)
                            <span class="absolute top-1 right-1 block h-2.5 w-2.5 rounded-full bg-green-500 ring-2 ring-white"></span>
                        @endif
                    </button>
                    <div id="notification-dropdown" class="hidden origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none">
                        <div class="py-1" role="menu" aria-orientation="vertical">
                            <div class="px-4 py-2 text-sm font-semibold text-gray-900 border-b">Notifikasi</div>
                            {{-- ✅ FIX: Menampilkan notifikasi dari database --}}
                            @forelse($notifications as $notification)
                                <a href="{{ $notification->url ?? '#' }}" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 border-b" role="menuitem">
                                    <p class="font-semibold">{{ $notification->title }}</p>
                                    <p class="text-xs text-gray-500">{{ $notification->message }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                </a>
                            @empty
                                <p class="px-4 py-3 text-sm text-gray-500 text-center">Tidak ada notifikasi baru.</p>
                            @endforelse
                            <a href="#" class="block text-center px-4 py-2 text-sm text-indigo-600 hover:bg-gray-100" role="menuitem">Lihat semua</a>
                        </div>
                    </div>
                </div>

                <!-- Dropdown Profil -->
                <div class="relative">
                    <button type="button" class="flex items-center text-sm rounded-full focus:outline-none" onclick="toggleDropdown('profile-dropdown')">
                        <span class="sr-only">Buka menu pengguna</span>
                        <img class="h-8 w-8 rounded-full" src="https://tokosancaka.biz.id/storage/uploads/sancaka.png" alt="User Image">
                    </button>
                    <div id="profile-dropdown" class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none">
                        <div class="px-4 py-3 border-b">
                            <p class="text-sm">Masuk sebagai</p>
                            <p class="text-sm font-medium text-gray-900 truncate">{{ Auth::user()->name ?? 'Admin Sancaka' }}</p>
                        </div>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profil</a>
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Keluar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
