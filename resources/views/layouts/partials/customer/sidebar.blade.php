{{--

    File: resources/views/layouts/partials/customer/sidebar.blade.php

    Deskripsi: Sidebar navigasi interaktif untuk dashboard pelanggan.

--}}



<!-- Latar belakang gelap saat sidebar mobile terbuka -->

<div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-black bg-opacity-50 transition-opacity lg:hidden" x-cloak></div>



<!-- Sidebar -->

<aside

    :class="sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'"

    class="fixed inset-y-0 left-0 z-30 w-64 overflow-y-auto bg-gray-800 text-white transition duration-300 transform lg:translate-x-0 lg:static lg:inset-0"

    x-cloak>



    {{-- Header Sidebar --}}

    <div class="flex items-center justify-center py-6">

        <a href="{{ route('customer.profile.show') }}" class="flex items-center text-lg font-bold text-white">

            <img class="object-cover w-10 h-10 rounded-full mr-4 border-2 border-gray-600" 

                 src="{{ Auth::user()->store_logo_path ? asset('storage/' . Auth::user()->store_logo_path) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->store_name ?? 'T') . '&background=4f46e5&color=fff' }}" 

                 alt="Logo Toko" />

            <span>{{ Auth::user()->store_name ?? 'Toko Pelanggan' }}</span>

        </a>

    </div>



    <!-- Menu Navigasi -->

    <nav class="mt-4 px-4 space-y-2">

        {{-- MENU UTAMA --}}

        <a href="{{ route('customer.dashboard') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.dashboard') ? 'bg-gray-900 text-white' : '' }}">

            <i class="fas fa-tachometer-alt fa-fw w-6"></i>

            <span class="ml-3">Dashboard Monitor</span>

        </a>

        @php

            $user = auth()->user();

            $hideMenus = $user->setup_token !== null 

                         && $user->profile_setup_at === null 

                         && $user->status === 'Tidak Aktif';

        @endphp

        

         @if(!$hideMenus)

        {{-- GRUP MANAJEMEN PESANAN --}}

        <p class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Manajemen Pesanan</p>



        <a href="{{ route('customer.pesanan.create') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.pesanan.create') ? 'bg-gray-900 text-white' : '' }}">

            <i class="fas fa-plus-circle fa-fw w-6"></i>

            <span class="ml-3">Buat Pesanan</span>

        </a>

        

        <a href="{{ route('customer.pesanan.index') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.pesanan.index') ? 'bg-gray-900 text-white' : '' }}">

            <i class="fas fa-table fa-fw w-6"></i>

            <span class="ml-3">Data Pesanan</span>

        </a>



        <a href="{{ route('customer.lacak.index') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.lacak.index') ? 'bg-gray-900 text-white' : '' }}">

            <i class="fas fa-search-location fa-fw w-6"></i>

            <span class="ml-3">Lacak Paket</span>

        </a>

        

       {{-- === MENU BARU DITAMBAHKAN DI SINI === --}}

        <a href="{{ route('customer.ongkir.index') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.ongkir.index') ? 'bg-gray-900 text-white' : '' }}">

            <i class="fas fa-truck fa-fw w-6"></i>

            <span class="ml-3">Cek Ongkir</span>

        </a>



        {{-- GRUP OPERASIONAL --}}

        <p class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Operasional</p>



        <a href="{{ route('customer.scan.spx') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.scan.spx') ? 'bg-gray-900 text-white' : '' }}">

            <i class="fas fa-qrcode fa-fw w-6"></i>

            <span class="ml-3">Scan Paket SPX</span>

        </a>



        <a href="{{ route('customer.scan.index') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.scan.index') ? 'bg-gray-900 text-white' : '' }}">

            <i class="fas fa-database fa-fw w-6"></i>

            <span class="ml-3">Riwayat Scan</span>

        </a>



        {{-- GRUP KEUANGAN --}}

        <p class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Keuangan</p>



        <a href="{{ route('customer.topup.index') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.topup.*') ? 'bg-gray-900 text-white' : '' }}">

            <i class="fas fa-wallet fa-fw w-6"></i>

            <span class="ml-3">Top Up Saldo</span>

        </a>



        <a href="{{ route('customer.laporan.index') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.laporan.index') ? 'bg-gray-900 text-white' : '' }}">

            <i class="fas fa-chart-area fa-fw w-6"></i>

            <span class="ml-3">Laporan Keuangan</span>

        </a>



        {{-- GRUP LAINNYA --}}

        <p class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Lainnya</p>



        

        <!-- Parent Menu: Marketplace -->

<div x-data="{ open: false }" class="space-y-1">

    <button @click="open = !open" 

        class="flex items-center w-full px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200">

        <i class="fas fa-store fa-fw w-6"></i>

        <span class="ml-3 flex-1 text-left">Belanja Disini</span>

        <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="ml-auto"></i>

    </button>



    <!-- Submenu -->

    <div x-show="open" x-cloak class="ml-10 space-y-1">

    {{-- DIUBAH: Menggunakan nama dan rute baru --}}
        <a href="{{ route('katalog.index') }}" target="_blank"
                   class="block px-4 py-2 text-gray-300 hover:bg-gray-600 hover:text-white rounded-md transition-colors duration-200">
                   <i class="fas fa-book-open fa-fw w-4"></i>
                   <span class="ml-2">Katalog</span>
                </a>

        <a href="{{ route('etalase.index') }}" target="_blank"

           class="block px-4 py-2 text-gray-300 hover:bg-gray-600 hover:text-white rounded-md transition-colors duration-200">

           <i class="fas fa-store fa-fw w-4"></i>

           <span class="ml-2">Etalase</span>

        </a>

        <a href="/pesanan/riwayat"

           class="block px-4 py-2 text-gray-300 hover:bg-gray-600 hover:text-white rounded-md transition-colors duration-200">

           <i class="fas fa-history fa-fw w-4"></i>

           <span class="ml-2">Riwayat Belanja</span>

        </a>

        



    </div>

</div>



    



{{-- ... (Menu-menu customer yang sudah ada seperti Dashboard, Pesanan Saya, dll) ... --}}





{{-- ====================================================== --}}

{{-- == MENU KHUSUS UNTUK SELLER == --}}

{{-- ====================================================== --}}

{{-- Cek apakah role pengguna adalah 'Seller' --}}

@if (auth()->user()->role === 'Seller')



<!-- Parent Menu: Toko Saya -->

<div x-data="{ open: {{ request()->routeIs('seller.*') ? 'true' : 'false' }} }" class="space-y-1">

    <button @click="open = !open"

        class="flex items-center w-full px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200">

        <i class="fas fa-store fa-fw w-6"></i>

        <span class="ml-3 flex-1 text-left">Toko Saya</span>

        <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="ml-auto text-xs"></i>

    </button>



    <!-- Submenu -->

    <div x-show="open" x-cloak class="ml-10 space-y-1">

        {{-- Link ke Dashboard Seller --}}

        <a href="{{ route('seller.dashboard') }}"

           class="block px-4 py-2 text-gray-300 hover:bg-gray-600 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('seller.dashboard') ? 'bg-gray-600' : '' }}">

            <i class="fas fa-tachometer-alt fa-fw w-4"></i>

            <span class="ml-2">Dashboard Toko</span>

        </a>



        {{-- Link ke Manajemen Produk --}}

        <a href="{{ route('seller.produk.index') }}"

           class="block px-4 py-2 text-gray-300 hover:bg-gray-600 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('seller.produk.*') ? 'bg-gray-600' : '' }}">

            <i class="fas fa-boxes fa-fw w-4"></i>

            <span class="ml-2">Kelola Produk</span>

        </a>



        {{-- Link ke Pesanan Masuk --}}

        <a href="{{-- route('seller.pesanan.index') --}}"

           class="block px-4 py-2 text-gray-300 hover:bg-gray-600 hover:text-white rounded-md transition-colors duration-200 {{-- request()->routeIs('seller.pesanan.*') ? 'bg-gray-600' : '' --}}">

            <i class="fas fa-inbox fa-fw w-4"></i>

            <span class="ml-2">Pesanan Masuk</span>

        </a>

        

         {{-- Di dalam file sidebar Anda --}}

        <a href="{{ route('seller.profile.edit') }}"

            class="block px-4 py-2 text-gray-300 hover:bg-gray-600 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('seller.profile.edit') ? 'bg-gray-600' : '' }}">

            <i class="fas fa-user-edit fa-fw w-4"></i>

            <span class="ml-2">Profil Toko</span>

        </a>

        

        {{-- Catatan: Route untuk pesanan seller belum kita buat, jadi saya beri komentar dulu --}}

    </div>

</div>



@endif

{{-- ====================================================== --}}

{{-- == AKHIR DARI MENU SELLER == --}}

{{-- ====================================================== --}}







        <a href="{{ route('customer.profile.edit') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.profile.edit') ? 'bg-gray-900 text-white' : '' }}">

            <i class="fas fa-cog fa-fw w-6"></i>

            <span class="ml-3">Pengaturan</span>

        </a>

 @endif

        <a href="{{ route('logout') }}"

           onclick="event.preventDefault(); document.getElementById('logout-form-customer').submit();"

           class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200">

            <i class="fas fa-sign-out-alt fa-fw w-6"></i>

            <span class="ml-3">Logout</span>

        </a>

        <form id="logout-form-customer" action="{{ route('logout') }}" method="POST" class="hidden">

            @csrf

        </form>

    </nav>

</aside>

