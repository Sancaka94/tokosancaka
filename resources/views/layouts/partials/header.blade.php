{{--
    Versi header ini dikembalikan ke desain awal yang lebih sederhana,
    namun tetap menggunakan Alpine.js untuk semua interaksi agar bebas dari error.
--}}
<header class="flex justify-between items-center p-4 bg-white dark:bg-gray-800 border-b dark:border-gray-700 shadow-sm sticky top-0 z-40">
    <!-- Sisi Kiri: Tombol Toggle & Judul Halaman -->
    <div class="flex items-center">
        {{-- Tombol ini berinteraksi dengan `sidebarOpen` dari `layouts/admin.blade.php` --}}
        <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
            <span class="sr-only">Toggle sidebar</span>
            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <h1 class="ml-3 text-lg font-semibold text-gray-800 dark:text-gray-200">@yield('page-title', 'Dashboard')</h1>
    </div>

    <!-- Sisi Kanan: Aksi & Profil -->
    <div class="ml-auto flex items-center space-x-2 sm:space-x-4">
        <!-- Saldo dan Tombol Top Up -->
        <div class="hidden md:flex items-center">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Saldo:</span>
            <span class="ml-2 text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 py-1 px-3 rounded-full">
                Rp {{ number_format(Auth::user()->balance ?? 0, 0, ',', '.') }}
            </span>
             {{-- PERUBAHAN: Tombol Top Up sekarang menggunakan route 'wallet.index' --}}
            <a href="{{ route('wallet.index') }}" class="ml-2 inline-flex items-center gap-x-1.5 px-2.5 py-1.5 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none">
                <svg class="w-4 h-4 text-green-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Top Up
            </a>
        </div>

        <!-- Tombol Dark Mode Toggle -->
        <button @click="darkMode = !darkMode" class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
            <span class="sr-only">Toggle Dark Mode</span>
            <i class="fa-solid fa-moon text-lg" x-show="!darkMode" x-cloak></i>
            <i class="fa-solid fa-sun text-lg" x-show="darkMode" x-cloak></i>
        </button>

        <!-- Dropdown Notifikasi -->
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
                <span class="sr-only">Lihat notifikasi</span>
                <i class="fas fa-bell text-lg"></i>
                @if(Auth::user() && Auth::user()->unreadNotifications->isNotEmpty())
                    <span class="absolute top-1 right-1 block h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white dark:ring-gray-800"></span>
                @endif
            </button>
            <div x-show="open" @click.away="open = false" x-cloak class="origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none">
                 <div class="py-1">
                    <div class="px-4 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 border-b dark:border-gray-700">Notifikasi</div>
                    <div class="max-h-80 overflow-y-auto custom-scrollbar">
                        @forelse(Auth::user()->unreadNotifications->take(5) as $notification)
                            <a href="{{ $notification->data['url'] ?? '#' }}" class="block px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border-b dark:border-gray-700">
                                <p class="font-semibold">{{ $notification->data['title'] ?? 'Notifikasi Baru' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $notification->data['message'] ?? '' }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                            </a>
                        @empty
                            <p class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center">Tidak ada notifikasi baru.</p>
                        @endforelse
                    </div>
                    <a href="#" class="block text-center px-4 py-2 text-sm text-indigo-600 dark:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">Lihat semua</a>
                </div>
            </div>
        </div>

        <!-- Dropdown Profil -->
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <span class="sr-only">Buka menu pengguna</span>
                <img class="h-8 w-8 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}">
            </button>
            <div x-show="open" @click.away="open = false" x-cloak class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none">
                <div class="px-4 py-3 border-b dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Masuk sebagai</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ Auth::user()->name }}</p>
                </div>
                <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Profil</a>
                <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf
                    <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

