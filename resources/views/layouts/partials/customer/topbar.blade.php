{{--
    File: resources/views/layouts/partials/customer/topbar.blade.php
    Deskripsi: Topbar/Header untuk panel customer dengan fungsionalitas dinamis.
--}}
<header x-data="{ isNotificationsMenuOpen: false, isProfileMenuOpen: false }" class="z-10 py-4 bg-white shadow-md dark:bg-gray-800">
    <div class="container flex items-center justify-between h-full px-6 mx-auto text-indigo-600 dark:text-indigo-300">
        
        <!-- Bagian Kiri: Tombol Toggle & Judul Halaman -->
        <div class="flex items-center">
            <!-- ✅ 5. Tombol untuk membuka/menutup sidebar di tampilan mobile -->
            <button class="p-1 mr-5 -ml-1 rounded-md md:hidden focus:outline-none focus:shadow-outline-indigo" @click="sidebarOpen = !sidebarOpen" aria-label="Menu">
                <i class="fas fa-bars w-6 h-6"></i>
            </button>
            <h2 class="hidden md:block text-lg font-semibold text-gray-700 dark:text-gray-200">
                @yield('page-title', 'Dashboard')
            </h2>
        </div>
        
        <!-- Bagian Kanan: Saldo, Notifikasi & Profil -->
        <ul class="flex items-center flex-shrink-0 space-x-6">
            
            <!-- Saldo & Tombol Top Up -->
            <li class="hidden md:flex items-center space-x-2">
                <span class="font-semibold text-sm text-gray-600 dark:text-gray-300">Saldo:</span>
                {{-- ✅ 1. Saldo dinamis dari View Composer --}}
                <span id="topbar-saldo" class="px-3 py-1 font-bold text-green-800 bg-green-100 dark:bg-green-700 dark:text-green-100 rounded-full">
                    Rp {{ number_format($saldo ?? 0, 0, ',', '.') }}
                </span>
                {{-- ✅ 2. Tombol Top Up Saldo --}}
                <a href="{{-- route('customer.topup.create') --}}" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-plus mr-2"></i>
                    Top Up
                </a>
            </li>

            <!-- Tombol Notifikasi -->
            <li class="relative">
                <button class="relative align-middle rounded-md focus:outline-none focus:shadow-outline-indigo" @click="isNotificationsMenuOpen = !isNotificationsMenuOpen" aria-label="Notifications" aria-haspopup="true">
                    <i class="fas fa-bell w-5 h-5"></i>
                    {{-- ✅ 3. Indikator notifikasi baru --}}
                    @if($notifications->isNotEmpty())
                    <span class="absolute top-0 right-0 inline-block w-3 h-3 transform translate-x-1 -translate-y-1 bg-red-600 border-2 border-white rounded-full dark:border-gray-800"></span>
                    @endif
                </button>

                <div x-show="isNotificationsMenuOpen" @click.away="isNotificationsMenuOpen = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="absolute right-0 w-80 mt-2 origin-top-right bg-white rounded-md shadow-lg dark:bg-gray-800">
                    <div class="p-2">
                        <div class="flex justify-between items-center p-2 border-b dark:border-gray-700">
                             <p class="font-semibold text-gray-700 dark:text-gray-300">Notifikasi</p>
                             @if($notifications->isNotEmpty())
                             <a href="{{-- route('customer.notifications.markAllRead') --}}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Tandai semua dibaca</a>
                             @endif
                        </div>
                        <div class="max-h-80 overflow-y-auto">
                        {{-- Daftar notifikasi dinamis --}}
                        @forelse($notifications as $notification)
                        <a href="{{ $notification->data['link'] ?? '#' }}" class="block p-3 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <p class="font-medium text-gray-700 dark:text-gray-200">{{ $notification->data['title'] ?? 'Notifikasi Baru' }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $notification->data['message'] ?? '' }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                        </a>
                        @empty
                        <p class="p-4 text-sm text-center text-gray-500 dark:text-gray-400">Tidak ada notifikasi baru.</p>
                        @endforelse
                        </div>
                    </div>
                </div>
            </li>

            <!-- Menu Profil Pengguna -->
            <li class="relative">
                <button class="align-middle rounded-full focus:outline-none focus:shadow-outline-indigo" @click="isProfileMenuOpen = !isProfileMenuOpen" aria-label="Account" aria-haspopup="true">
                    {{-- ✅ 4. Path logo profil tidak diubah --}}
                    <img class="object-cover w-8 h-8 rounded-full" 
                         src="{{ Auth::user()->store_logo_path ? asset('storage/' . Auth::user()->store_logo_path) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->nama_lengkap) . '&background=random&color=fff' }}" 
                         alt="Avatar Pengguna" />
                </button>
                <div x-show="isProfileMenuOpen" @click.away="isProfileMenuOpen = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="absolute right-0 w-56 mt-2 origin-top-right bg-white rounded-md shadow-lg dark:bg-gray-800">
                    <div class="p-2">
                        {{-- ✅ 5. Nama pengguna dinamis di dropdown --}}
                        <div class="px-4 py-2 border-b dark:border-gray-700">
                             <p class="font-semibold text-gray-800 dark:text-gray-200">{{ Auth::user()->nama_lengkap }}</p>
                             <p class="text-xs text-gray-500 dark:text-gray-400">{{ Auth::user()->email }}</p>
                        </div>
                        <a href="{{-- route('customer.profile.show') --}}" class="inline-flex items-center w-full px-4 py-2 mt-1 text-sm text-gray-700 rounded-md dark:text-gray-300 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-500">
                            <i class="fas fa-user mr-3"></i> Profil
                        </a>
                        <a href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                           class="inline-flex items-center w-full px-4 py-2 text-sm text-gray-700 rounded-md dark:text-gray-300 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-500">
                           <i class="fas fa-sign-out-alt mr-3"></i> Keluar
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</header>