{{--
    File: resources/views/layouts/partials/header.blade.php
    Deskripsi: Header admin panel dengan dropdown notifikasi dinamis (ID-based).
--}}
<header class="flex justify-between items-center p-4 bg-red-500 dark:bg-gray-800 border-b dark:border-gray-700 shadow-sm sticky top-0 z-40">
    <!-- Kiri: Toggle sidebar + judul halaman -->
    <div class="flex items-center">
        {{-- Tombol toggle sidebar --}}
        {{-- Tombol toggle sidebar --}}
        {{-- Tombol toggle sidebar --}}
        {{-- PENTING: Kelas text-white agar ikon terlihat di header merah/gelap --}}
        <button @click="sidebarOpen = !sidebarOpen"
            class="p-2 rounded-md text-white dark:text-gray-400 hover:bg-red-700 focus:outline-none lg:hidden">
            <span class="sr-only">Toggle sidebar</span>
            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        {{-- Judul halaman --}}
        <h1 class="ml-3 text-lg font-semibold text-white dark:text-white">
            @yield('page-title', 'Dashboard')
        </h1>
    </div>

    <!-- Kanan: Aksi -->
    <div class="ml-auto flex items-center space-x-2 sm:space-x-4 mr-6">

        {{-- Saldo + tombol top up --}}
        <div class="hidden md:flex items-center">
            <!-- ... existing code ... -->
            <span class="text-sm font-medium text-white dark:text-white"><strong>Saldo:</strong></span>
            <span
                class="ml-2 text-sm font-semibold bg-green-500 text-white dark:bg-green-900/50 dark:text-green-300 py-1 px-3 rounded-full border:white">
                {{-- TODO: Saldo ini mungkin perlu di-refresh juga, tapi untuk sekarang kita biarkan --}}
                <strong>Rp {{ number_format(Auth::user()->saldo ?? 0, 0, ',', '.') }}</strong>
            </span>

<a href="{{ route('admin.saldo.requests.index') }}"
    class="ml-2 inline-flex items-center gap-x-1.5 px-3 py-1.5
           bg-blue-600 hover:bg-blue-700 
           text-white text-sm font-medium rounded-md
           focus:outline-none">

    <i class="fas fa-money-bill-wave text-white text-base"></i>

    <strong>Top Up</strong>
</a>


        <a href="https://tokosancaka.com/admin/products"
    class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:bg-red-700 dark:hover:bg-red-700 focus:outline-none relative">
    <span class="sr-only">Lihat Produk</span>
    <i class="fas fa-store text-lg text-white"></i>
</a>



        </div>
        

        <!-- =================================================================== -->
        <!-- == BLOK NOTIFIKASI (DIPERBAIKI DENGAN TABEL) == -->
        <!-- =================================================================== -->
        {{-- Dropdown Notifikasi --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open; if(open) loadInitialNotifications();"
                    class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:bg-red-700 dark:hover:bg-red-700 focus:outline-none relative">
                    <span class="sr-only">Lihat notifikasi</span>
                    <i class="fas fa-bell text-lg text-white"></i>


                {{-- Badge notifikasi dinamis --}}
                <span id="notification-count-badge"
                    class="absolute top-1 right-1 flex items-center justify-center text-[10px] text-white bg-red-600 rounded-full w-4 h-4"
                    style="display: none;">
                    0
                </span>
            </button>

            {{-- Dropdown body --}}
            <div x-show="open" @click.away="open = false" x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="origin-top-right absolute right-0 mt-2 w-80 sm:w-96 rounded-xl shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                
                <div class="py-1">
                    <div
                        class="px-4 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 border-b dark:border-gray-700">
                        Notifikasi
                    </div>

                    {{-- [PERBAIKAN] Area scroll sekarang berisi kerangka tabel --}}
                    <div id="notification-scroll-area" class="max-h-96 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 table-fixed"> {{-- <-- DITAMBAHKAN 'table-fixed' --}}
                            {{-- 
                              tbody ini akan diisi oleh JavaScript.
                              Kita tidak pakai thead agar lebih minimalis di dropdown.
                            --}}
                            <tbody id="notification-list-body" class="divide-y divide-gray-100 dark:divide-gray-700">
                                {{-- JavaScript akan mengisi baris (<tr>) di sini --}}
                            </tbody>

                            {{-- [PERBAIKAN] Status kosong sekarang adalah bagian dari tabel --}}
                            <tbody id="notification-empty-state" style="display: none;">
                                <tr>
                                    <td class="px-4 py-10 text-sm text-gray-500 dark:text-gray-400 text-center">
                                        Tidak ada notifikasi baru.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Link "Lihat Semua" --}}
                    <a href="{{ route('admin.notifications.index') }}"
                        class="block text-center px-4 py-2 text-sm text-indigo-600 dark:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-b-xl border-t dark:border-gray-700">
                        Lihat semua notifikasi
                    </a>
                </div>
            </div>
        </div>
        <!-- =================================================================== -->
        <!-- == AKHIR BLOK NOTIFIKASI == -->
        <!-- =================================================================== -->


        {{-- Dropdown Profil --}}
        <div x-data="{ open: false }" class="relative">
            <!-- ... existing code ... -->
            <button @click="open = !open"
                class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                <span class="sr-only">Buka menu pengguna</span>
                <img class="h-8 w-8 rounded-full object-cover"
                    src="{{ Auth::user()->store_logo_path
                    ? asset('public/storage/' . Auth::user()->store_logo_path) 
                    : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->nama_lengkap ?? 'User') . '&color=7F9CF5&background=EBF4FF' }}">
            </button>

            <div x-show="open" @click.away="open = false" x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none z-50">

                <div class="px-4 py-3 border-b dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Masuk sebagai</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                        {{ Auth::user()->nama_lengkap ?? 'User' }}
                    </p>
                </div>

                <a href="{{ route('admin.settings.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    Profil & Pengaturan
                </a>

                <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full text-left block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>