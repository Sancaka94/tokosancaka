{{--
    File: resources/views/layouts/partials/customer/sidebar.blade.php
    Deskripsi: Sidebar navigasi interaktif lengkap untuk dashboard pelanggan, seller, dan agen.
--}}

<div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-[90] bg-blue-900 bg-opacity-50 transition-opacity lg:hidden" x-cloak></div>

<aside
    :class="sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'"
    class="fixed inset-y-0 left-0 z-[100] w-64 overflow-y-auto bg-blue-900 text-white transition duration-300 transform lg:translate-x-0 lg:static lg:inset-0"
    x-cloak>

   {{-- Header Sidebar (Logo & Nama Toko) --}}
<div class="flex items-center justify-center py-6">
    <a href="{{ route('customer.profile.show') }}" class="flex flex-col items-center text-lg font-bold text-white text-center">
        @php
            // Ambil objek pengguna yang sedang login, jika ada.
            $user = Auth::user();

            // Tentukan path logo
            $logoPath = $user?->store_logo_path;

            // Tentukan nama toko untuk avatar default
            $storeName = $user?->store_name ?? 'Pelanggan';

            // Logika SRC: Jika logoPath ada, gunakan asset; jika tidak, gunakan UI Avatar
            $logoSrc = $logoPath
                ? asset('public/storage/' . $logoPath) 
                : 'https://ui-avatars.com/api/?name=' . urlencode($storeName) . '&background=4f46e5&color=fff';
        @endphp

        {{-- Logo --}}
        <img class="object-cover w-16 h-16 rounded-full mb-3 border-4 border-gray-700 shadow-md" 
             src="{{ $logoSrc }}" 
             alt="Logo Toko" />
        
        {{-- Nama Toko --}}
        <span class="truncate w-40 block">{{ $storeName }}</span>
    </a>
</div>

    <nav class="mt-4 px-4 space-y-2 pb-20">

        {{-- 1. DASHBOARD MONITOR (UMUM) --}}
        <a href="{{ route('customer.dashboard') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.dashboard') ? 'bg-gray-900 text-white' : '' }}">
            <i class="fas fa-tachometer-alt fa-fw w-6"></i>
            <span class="ml-3">Dashboard Monitor</span>
        </a>

        @php
            $user = auth()->user();
            // Logika sembunyikan menu jika user belum setup profile (optional)
            $hideMenus = $user->setup_token !== null && $user->profile_setup_at === null && $user->status === 'Tidak Aktif';
        @endphp
        
        @if(!$hideMenus)

            {{-- ========================================================== --}}
            {{-- BAGIAN 2: MENU KHUSUS AGEN / UPGRADE                       --}}
            {{-- ========================================================== --}}
            
            @if($user->role === 'agent' || $user->role === 'admin')
                {{-- TAMPILAN UNTUK AGEN RESMI --}}
                <div class="pt-4 pb-2">
                    <p class="px-2 text-xs text-yellow-400 uppercase tracking-wider font-bold">Menu Agen Resmi</p>
                </div>

                <a href="{{ route('agent.products.index') }}" 
                   class="flex items-center px-4 py-2.5 mb-1 bg-gradient-to-r from-blue-700 to-blue-800 text-white rounded-md shadow-md border border-blue-600 hover:from-blue-600 hover:to-blue-700 transition">
                    <i class="fas fa-store fa-fw w-6 text-yellow-400"></i>
                    <div>
                        <span class="block font-bold text-sm">Kelola Agen Sancaka</span>
                        <span class="block text-[10px] text-blue-200 font-normal">Atur Harga Jual</span>
                    </div>
                </a>
            @else
                {{-- TAMPILAN UNTUK MEMBER BIASA (TOMBOL UPGRADE) --}}
                <div class="py-4">
                    <a href="{{ route('agent.register.index') }}" 
                       class="flex items-center px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 group border border-purple-400/30">
                        <div class="p-1.5 bg-white/20 rounded-full mr-3 group-hover:bg-white/30 transition">
                            <i class="fas fa-rocket fa-fw"></i>
                        </div>
                        <div>
                            <span class="block text-[10px] uppercase font-bold text-indigo-100 tracking-wider">Upgrade Akun</span>
                            <span class="block font-bold text-white leading-tight">Jadi Agen Resmi</span>
                        </div>
                    </a>
                </div>
            @endif


            {{-- ========================================================== --}}
            {{-- BAGIAN 3: PRODUK DIGITAL (PPOB)                            --}}
            {{-- ========================================================== --}}
            <p class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Produk Digital</p>

            <div x-data="{ open: {{ request()->routeIs('customer.ppob.*') ? 'true' : 'false' }} }" class="space-y-1">
                <button @click="open = !open" 
                    class="flex items-center w-full px-4 py-2.5 text-white hover:bg-red-700 bg-red-600 hover:text-white rounded-md transition-colors duration-200 shadow-sm">
                    <i class="fas fa-mobile-alt fa-fw w-6"></i>
                    <span class="ml-3 flex-1 text-left">Payment PPOB</span>
                    <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="ml-auto text-xs"></i>
                </button>

                <div x-show="open" x-cloak class="ml-4 pl-4 border-l border-gray-700 space-y-1 mt-1">
                    {{-- Link Jualan Pulsa (Pricelist) --}}
                    <a href="{{ route('public.pricelist') }}" 
                       class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200">
                        <i class="fas fa-sim-card fa-fw w-4 mr-2"></i>
                        Isi Pulsa / Data
                    </a>
                    
                    {{-- Link Bayar Tagihan --}}
                    <a href="https://tokosancaka.com/etalase/ppob/digital/pln-pascabayar" target="_blank"
                       class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200">
                        <i class="fas fa-file-invoice-dollar fa-fw w-4 mr-2"></i>
                        Bayar Tagihan
                    </a>

                    {{-- Link Riwayat PPOB --}}
                    <a href="{{ route('customer.ppob.history') }}"
                       class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200 {{ request()->routeIs('customer.ppob.history') ? 'bg-gray-800 text-white' : '' }}">
                        <i class="fas fa-history fa-fw w-4 mr-2"></i>
                        Riwayat Transaksi
                    </a>
                </div>
            </div> 


            {{-- ========================================================== --}}
            {{-- BAGIAN 4: MANAJEMEN PENGIRIMAN (FITUR PELANGGAN)           --}}
            {{-- ========================================================== --}}
            <p class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Manajemen Pengiriman</p>
            
            {{-- Chat CS --}}
            <a href="https://tokosancaka.com/customer/chat" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->is('customer/chat*') ? 'bg-gray-900 text-white' : '' }}">
                <i class="fas fa-comment-dots fa-fw w-6"></i>
                <span class="ml-3">Chat CS ADMIN</span>
            </a>

            {{-- Kirim Satuan --}}
            <a href="{{ route('customer.pesanan.create') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.pesanan.create') ? 'bg-gray-900 text-white' : '' }}">
                <i class="fas fa-plus-circle fa-fw w-6"></i>
                <span class="ml-3">Kirim Paket (Satuan)</span>
            </a>
            
            {{-- Kirim Massal --}}
            <a href="{{ route('customer.koli.create') }}"
               class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200
                      {{ request()->routeIs('customer.koli.create') ? 'bg-gray-900 text-white' : '' }}">
                <i class="fas fa-boxes fa-fw w-6"></i>
                <span class="ml-3 flex items-center w-full">
                    Kirim Massal
                    <span class="ml-auto bg-red-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded">HOT</span>
                </span>
            </a>

            {{-- Data Pengiriman --}}
            <a href="{{ route('customer.pesanan.index') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.pesanan.index') ? 'bg-gray-900 text-white' : '' }}">
                <i class="fas fa-table fa-fw w-6"></i>
                <span class="ml-3">Data Pengiriman</span>
            </a>
            
            {{-- Data Penerima --}}
            <a href="{{ route('customer.kontak.index') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.kontak.*') ? 'bg-gray-900 text-white' : '' }}">
                <i class="fas fa-address-book fa-fw w-6"></i>
                <span class="ml-3">Data Kontak</span>
            </a>

            {{-- Lacak Paket --}}
            <a href="{{ route('customer.lacak.index') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.lacak.index') ? 'bg-gray-900 text-white' : '' }}">
                <i class="fas fa-search-location fa-fw w-6"></i>
                <span class="ml-3">Lacak Paket</span>
            </a>

            {{-- Cek Ongkir --}}
            <a href="{{ route('customer.ongkir.index') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.ongkir.index') ? 'bg-gray-900 text-white' : '' }}">
                <i class="fas fa-truck fa-fw w-6"></i>
                <span class="ml-3">Cek Ongkir</span>
            </a>


            {{-- ========================================================== --}}
            {{-- BAGIAN 5: OPERASIONAL                                      --}}
            {{-- ========================================================== --}}
            <p class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Operasional</p>

            <a href="{{ route('customer.scan.spx') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.scan.spx') ? 'bg-gray-900 text-white' : '' }}">
                <i class="fas fa-qrcode fa-fw w-6"></i>
                <span class="ml-3">Scan Paket SPX</span>
            </a>

            <a href="{{ route('customer.scan.index') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.scan.index') ? 'bg-gray-900 text-white' : '' }}">
                <i class="fas fa-database fa-fw w-6"></i>
                <span class="ml-3">Riwayat Scan</span>
            </a>


            {{-- ========================================================== --}}
            {{-- BAGIAN 6: KEUANGAN                                         --}}
            {{-- ========================================================== --}}
            <p class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Keuangan</p>

            <a href="{{ route('customer.topup.index') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.topup.*') ? 'bg-gray-900 text-white' : '' }}">
                <i class="fas fa-wallet fa-fw w-6"></i>
                <span class="ml-3">Top Up Saldo</span>
            </a>

            <a href="{{ route('customer.laporan.index') }}" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.laporan.index') ? 'bg-gray-900 text-white' : '' }}">
                <i class="fas fa-chart-area fa-fw w-6"></i>
                <span class="ml-3">Laporan Keuangan</span>
            </a>


            {{-- ========================================================== --}}
            {{-- BAGIAN 7: BELANJA (MARKETPLACE)                            --}}
            {{-- ========================================================== --}}
            <p class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Lainnya</p>

            <div x-data="{ open: false }" class="space-y-1">
                <button @click="open = !open" 
                    class="flex items-center w-full px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200">
                    <i class="fas fa-store fa-fw w-6"></i>
                    <span class="ml-3 flex-1 text-left">Belanja Disini</span>
                    <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="ml-auto text-xs"></i>
                </button>

                <div x-show="open" x-cloak class="ml-4 pl-4 border-l border-gray-700 space-y-1 mt-1">
                    <a href="{{ route('katalog.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200">
                        <i class="fas fa-book-open fa-fw w-4 mr-2"></i>
                        Katalog
                    </a>
                    <a href="{{ route('etalase.index') }}" target="_blank" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200">
                        <i class="fas fa-store fa-fw w-4 mr-2"></i>
                        Etalase
                    </a>
                    <a href="/customer/pesanan/riwayat" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200">
                        <i class="fas fa-history fa-fw w-4 mr-2"></i>
                        Riwayat Belanja
                    </a>
                </div>
            </div>


            {{-- ========================================================== --}}
            {{-- BAGIAN 8: KHUSUS SELLER (TOKO SAYA)                        --}}
            {{-- ========================================================== --}}
            {{-- LOGIKA: Seller & Agent & Admin bisa lihat ini --}}
            @if (auth()->user()->role === 'Seller' || auth()->user()->role === 'agent' || auth()->user()->role === 'admin')

            <p class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Toko Saya</p>

            <div x-data="{ open: {{ request()->routeIs('seller.*') ? 'true' : 'false' }} }" class="space-y-1">
                <button @click="open = !open"
                    class="flex items-center w-full px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200">
                    <i class="fas fa-store-alt fa-fw w-6"></i>
                    <span class="ml-3 flex-1 text-left">Kelola Toko</span>
                    <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="ml-auto text-xs"></i>
                </button>

                <div x-show="open" x-cloak class="ml-4 pl-4 border-l border-gray-700 space-y-1 mt-1">
                    
                    {{-- Dashboard Toko --}}
                    <a href="{{ route('seller.dashboard') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200 {{ request()->routeIs('seller.dashboard') ? 'bg-gray-800 text-white' : '' }}">
                        <i class="fas fa-tachometer-alt fa-fw w-4 mr-2"></i>
                        Dashboard Toko
                    </a>

                    {{-- Dompet Sancaka --}}
                    <a href="{{ route('seller.doku.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200 {{ request()->routeIs('seller.doku.*') ? 'bg-gray-800 text-white' : '' }}">
                        <i class="fas fa-wallet fa-fw w-4 mr-2"></i>
                        Dompet Sancaka
                    </a>

                    {{-- Kelola Produk --}}
                    <a href="{{ route('seller.produk.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200 {{ request()->routeIs('seller.produk.*') ? 'bg-gray-800 text-white' : '' }}">
                        <i class="fas fa-boxes fa-fw w-4 mr-2"></i>
                        Kelola Produk
                    </a>

                    {{-- Ulasan Produk --}}
                    <a href="{{ route('seller.reviews.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200 {{ request()->routeIs('seller.reviews.*') ? 'bg-gray-800 text-white' : '' }}">
                        <i class="fas fa-star fa-fw w-4 mr-2"></i>
                        Ulasan Produk
                    </a>

                    {{-- Pesanan Marketplace --}}
                    <a href="{{ route('seller.pesanan.marketplace.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200 {{ request()->routeIs('seller.pesanan.marketplace.*') ? 'bg-gray-800 text-white' : '' }}">
                        <i class="fas fa-shopping-basket fa-fw w-4 mr-2"></i>
                        Pesanan Marketplace
                    </a>

                    {{-- Profil Toko --}}
                    <a href="{{ route('seller.profile.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200 {{ request()->routeIs('seller.profile.edit') ? 'bg-gray-800 text-white' : '' }}">
                        <i class="fas fa-user-edit fa-fw w-4 mr-2"></i>
                        Profil Toko
                    </a>
                </div>
            </div>
            @endif

            {{-- ========================================================== --}}
            {{-- BAGIAN 9: PENGATURAN USER                                  --}}
            {{-- ========================================================== --}}
            <a href="{{ route('customer.profile.edit') }}" class="flex items-center px-4 py-2.5 mt-4 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.profile.edit') ? 'bg-gray-900 text-white' : '' }}">
                <i class="fas fa-cog fa-fw w-6"></i>
                <span class="ml-3">Pengaturan Akun</span>
            </a>

        @endif

        {{-- LOGOUT --}}
        <a href="{{ route('logout') }}"
           onclick="event.preventDefault(); document.getElementById('logout-form-customer').submit();"
           class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200">
            <i class="fas fa-sign-out-alt fa-fw w-6 text-red-400"></i>
            <span class="ml-3">Logout</span>
        </a>
        <form id="logout-form-customer" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>

    </nav>
</aside>