{{--

    File: resources/views/layouts/partials/customer/sidebar.blade.php

    Deskripsi: Sidebar navigasi interaktif untuk dashboard pelanggan.

--}}



<!-- Latar belakang gelap saat sidebar mobile terbuka -->

<div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-blue-900 bg-opacity-50 transition-opacity lg:hidden" x-cloak></div>



<!-- Sidebar -->

<aside

    :class="sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'"

    class="fixed inset-y-0 left-0 z-30 w-64 overflow-y-auto bg-blue-900 text-white transition duration-300 transform lg:translate-x-0 lg:static lg:inset-0"

    x-cloak>



    {{-- Header Sidebar --}}

    <div class="flex items-center justify-center py-6">

        <a href="{{ route('customer.profile.show') }}" class="flex items-center text-lg font-bold text-white">

            <img class="object-cover w-10 h-10 rounded-full mr-4 border-2 border-gray-600" 

                 src="{{ Auth::user()->store_logo_path ? asset('public/storage/' . Auth::user()->store_logo_path) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->store_name ?? 'T') . '&background=4f46e5&color=fff' }}" 

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

        {{-- ================================================= --}}
{{-- MENU KHUSUS AGEN (Hanya muncul jika Role = Agent) --}}
{{-- ================================================= --}}
@if(auth()->user()->role === 'agent' || auth()->user()->role === 'admin')
    
    {{-- Separator Menu Agen --}}
    <div class="mt-4 mb-2 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
        Menu Agen
    </div>

    {{-- Link Jualan / Kelola Harga --}}
    <a href="{{ route('agent.products.index') }}" 
       class="flex items-center px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors {{ request()->routeIs('agent.products.*') ? 'bg-blue-50 text-blue-600 font-bold border-r-4 border-blue-600' : '' }}">
        
        <div class="mr-3 text-lg">
            <i class="fas fa-store"></i> {{-- Icon Toko --}}
        </div>
        <span>Kelola Harga Jual</span>
    </a>

    {{-- Link Transaksi Agen (Opsional) --}}
    <a href="{{ route('ppob.pricelist') }}" 
       class="flex items-center px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
        
        <div class="mr-3 text-lg">
            <i class="fas fa-mobile-alt"></i> {{-- Icon HP --}}
        </div>
        <span>Jualan Pulsa (Kasir)</span>
    </a>

@endif

{{-- ================================================= --}}
{{-- MENU UPGRADE (Hanya muncul jika BELUM jadi Agent) --}}
{{-- ================================================= --}}
@if(auth()->user()->role !== 'agent' && auth()->user()->role !== 'admin')
    <div class="mt-4 px-4">
        <a href="{{ route('agent.register.index') }}" class="block bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl p-4 shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 text-center">
            <i class="fas fa-rocket text-2xl mb-2"></i>
            <div class="font-bold">Upgrade jadi Agen</div>
            <div class="text-xs opacity-90 mt-1">Dapatkan harga lebih murah!</div>
        </a>
    </div>
@endif
        

         @if(!$hideMenus)

        {{-- ========================================================== --}}
        {{-- MENU PPOB (PRODUK DIGITAL) - DITAMBAHKAN DISINI --}}
        {{-- ========================================================== --}}
        
        <p class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Produk Digital</p>

        <div x-data="{ open: {{ request()->routeIs('customer.ppob.*') ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="open = !open" 
                class="flex items-center w-full px-4 py-2.5 text-white hover:bg-red-700 bg-red-600 hover:text-white rounded-md transition-colors duration-200">
                <i class="fas fa-mobile-alt fa-fw w-6"></i>
                <span class="ml-3 flex-1 text-left">Payment PPOB</span>
                <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="ml-auto text-xs"></i>
            </button>

            <div x-show="open" x-cloak class="ml-10 space-y-1">
                {{-- Link Beli Pulsa --}}
                <a href="https://tokosancaka.com/etalase/ppob/digital/pulsa" target="_blank"
                   class="block px-4 py-2 text-gray-300 hover:bg-red-600 hover:text-white rounded-md transition-colors duration-200">
                    <i class="fas fa-sim-card fa-fw w-4"></i>
                    <span class="ml-2">Isi Pulsa / Data</span>
                </a>
                
                {{-- Link Bayar Tagihan (PLN) --}}
                <a href="https://tokosancaka.com/etalase/ppob/digital/pln-pascabayar" target="_blank"
                   class="block px-4 py-2 text-gray-300 hover:bg-red-600 hover:text-white rounded-md transition-colors duration-200">
                    <i class="fas fa-file-invoice-dollar fa-fw w-4"></i>
                    <span class="ml-2">Bayar Tagihan</span>
                </a>

                {{-- Link Riwayat PPOB --}}
                <a href="{{ route('customer.ppob.history') }}"
                   class="block px-4 py-2 text-gray-300 hover:bg-green-600 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.ppob.history') ? 'bg-gray-600 text-white' : '' }}">
                    <i class="fas fa-history fa-fw w-4"></i>
                    <span class="ml-2">Riwayat Transaksi</span>
                </a>
            </div>
        </div> 

        {{-- GRUP MANAJEMEN PESANAN --}}

        <p class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Manajemen Pengiriman</p>
        
        {{-- START: LINK CHAT BARU DITAMBAHKAN --}}
        <a href="https://tokosancaka.com/customer/chat" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->is('customer/chat*') ? 'bg-gray-900 text-white' : '' }}">
            <i class="fas fa-comment-dots fa-fw w-6"></i>
            <span class="ml-3">Chat CS ADMIN</span>
        </a>



        <a href="{{ route('customer.pesanan.create') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.pesanan.create') ? 'bg-gray-900 text-white' : '' }}">
            <i class="fas fa-plus-circle fa-fw w-6"></i>
            <span class="ml-3">Kirim Paket (Satuan)</span>
        </a>
        
        {{-- [BARU] Link Halaman Multi Koli --}}
<a href="{{ route('customer.koli.create') }}"
   class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200
          {{ request()->routeIs('customer.koli.create') ? 'bg-gray-900 text-white' : '' }}">

    <i class="fas fa-boxes fa-fw w-6"></i>

    <span class="ml-3 flex items-center">
        Kirim Massal
        <span class="ml-2 bg-red-600 text-white text-[10px] px-1.5 py-0.5 rounded">
            BARU
        </span>
    </span>

</a>


        

        <a href="{{ route('customer.pesanan.index') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.pesanan.index') ? 'bg-gray-900 text-white' : '' }}">

            <i class="fas fa-table fa-fw w-6"></i>

            <span class="ml-3">Data Pengiriman</span>

        </a>
        
        
        
        <a href="{{ route('customer.kontak.index') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.kontak.*') ? 'bg-gray-900 text-white' : '' }}">
            <i class="fas fa-address-book fa-fw w-6"></i>
            <span class="ml-3">Data Penerima</span>
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
        <a href="{{ route('katalog.index') }}" 
                   class="block px-4 py-2 text-gray-300 hover:bg-gray-600 hover:text-white rounded-md transition-colors duration-200">
                   <i class="fas fa-book-open fa-fw w-4"></i>
                   <span class="ml-2">Katalog</span>
                </a>

        <a href="{{ route('etalase.index') }}" target="_blank"

           class="block px-4 py-2 text-gray-300 hover:bg-gray-600 hover:text-white rounded-md transition-colors duration-200">

           <i class="fas fa-store fa-fw w-4"></i>

           <span class="ml-2">Etalase</span>

        </a>

        <a href="/customer/pesanan/riwayat"

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
        
        
        {{-- === TAMBAHAN UNTUK REGISTRASI DOMPET === --}}
<a href="{{ route('seller.doku.index') }}"
   class="block px-4 py-2 text-gray-300 hover:bg-gray-600 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('seller.doku.*') ? 'bg-gray-600' : '' }}">
    <i class="fas fa-wallet fa-fw w-4"></i>
    <span class="ml-2">Dompet Sancaka</span>
</a>
        {{-- === AKHIR TAMBAHAN === --}}



        {{-- Link ke Manajemen Produk --}}

        <a href="{{ route('seller.produk.index') }}"

           class="block px-4 py-2 text-gray-300 hover:bg-gray-600 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('seller.produk.*') ? 'bg-gray-600' : '' }}">

            <i class="fas fa-boxes fa-fw w-4"></i>

            <span class="ml-2">Kelola Produk</span>

        </a>

        {{-- Link ke Ulasan Produk (DIPERBAIKI STYLENYA) --}}
                <a href="{{ route('seller.reviews.index') }}"
                   class="block px-4 py-2 text-gray-300 hover:bg-gray-600 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('seller.reviews.*') ? 'bg-gray-600' : '' }}">
                    <i class="fas fa-star fa-fw w-4"></i>
                    <span class="ml-2">Ulasan Produk</span>
                </a>



        
        
        {{-- Link ke Pesanan Marketplace (YANG BARU) --}}
<a href="{{ route('seller.pesanan.marketplace.index') }}"
   class="block px-4 py-2 text-gray-300 hover:bg-gray-600 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('seller.pesanan.marketplace.*') ? 'bg-gray-600' : '' }}">
    <i class="fas fa-shopping-basket fa-fw w-4"></i>
    <span class="ml-2">Pesanan Marketplace</span>
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

