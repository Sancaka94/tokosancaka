{{--
    File: resources/views/layouts/partials/customer/topbar.blade.php
    Deskripsi: Topbar/Header untuk panel customer dengan desain baru.
--}}
<header class="z-10 py-4 bg-white shadow-md">
    <div class="container flex items-center justify-between h-full px-6 mx-auto">
        
        {{-- ✅ DIPERBAIKI: Bagian Kiri Topbar --}}
        <div class="flex items-center">
            <!-- Tombol Buka/Tutup Sidebar (Mobile) -->
            <button class="p-1 mr-3 -ml-1 rounded-md md:hidden focus:outline-none" @click="sidebarOpen = !sidebarOpen" aria-label="Menu">
                <i class="fas fa-bars w-6 h-6 text-gray-600"></i>
            </button>
            <!-- Judul Halaman -->
            <h2 class="hidden md:block text-lg font-semibold text-gray-700">
                Dashboard
            </h2>
        </div>
        
        {{-- ✅ DIPERBAIKI: Bagian Kanan Topbar --}}
        <ul class="flex items-center flex-shrink-0 space-x-4">
            
            {{-- Saldo & Tombol Top Up --}}
            @if(Auth::user()->role === 'Pelanggan')
            <li class="flex items-center space-x-2">
                <span class="font-semibold text-sm text-gray-600">Saldo:</span>
                <span class="px-3 py-1 font-bold text-green-800 bg-green-100 rounded-full">
                    Rp {{ number_format(Auth::user()->saldo ?? 0, 0, ',', '.') }}
                </span>
                {{-- Tombol Top Up diubah menjadi merah --}}
                <a href="{{ route('customer.topup.create') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700">
                    <i class="fas fa-plus mr-2"></i>
                    Top Up
                </a>
            </li>
            @endif

            <!-- Tombol Notifikasi -->
            <li class="relative">
                <button class="relative align-middle rounded-md focus:outline-none" @click="isNotificationsMenuOpen = !isNotificationsMenuOpen" aria-label="Notifications" aria-haspopup="true">
                    <i class="fas fa-bell w-5 h-5 text-gray-600"></i>
                </button>
                <div x-show="isNotificationsMenuOpen" @click.away="isNotificationsMenuOpen = false" class="absolute right-0 w-56 mt-2 origin-top-right bg-white rounded-md shadow-lg">
                    <div class="p-2">
                        <p class="p-2 text-sm text-center text-gray-500">Tidak ada notifikasi baru.</p>
                    </div>
                </div>
            </li>

            <!-- Menu Profil Pengguna -->
            <li class="relative flex items-center space-x-2">
    <button 
        class="align-middle rounded-full focus:outline-none" 
        @click="isProfileMenuOpen = !isProfileMenuOpen" 
        aria-label="Account" aria-haspopup="true"
    >
        <img 
            class="object-cover w-8 h-8 rounded-full" 
            src="{{ Auth::user()->store_logo_path 
                ? asset('storage/' . Auth::user()->store_logo_path) 
                : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->nama_lengkap) . '&background=random&color=fff' }}" 
            alt="Avatar Pengguna" 
        />
    </button>

    <!-- Nama toko muncul di desktop -->
    <span class="hidden md:block text-sm font-medium text-gray-900">
        {{ Auth::user()->store_name ?? 'Toko Pelanggan' }}
    </span>

    <!-- Dropdown -->
    <div 
    x-show="isProfileMenuOpen" 
    @click.away="isProfileMenuOpen = false" 
    class="absolute right-0 top-full mt-2 w-56 origin-top-right bg-white rounded-md shadow-lg z-50"
>
    <div class="p-2">
        <a href="{{ route('customer.profile.show') }}" 
           class="block px-4 py-2 text-sm text-gray-700 rounded-md hover:bg-indigo-600 hover:text-white">
            Profil
        </a>
        <a href="{{ route('logout') }}"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
           class="block px-4 py-2 text-sm text-gray-700 rounded-md hover:bg-indigo-600 hover:text-white">
            Keluar
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>
    </div>
</div>
</li>
        </ul>
    </div>
</header>
