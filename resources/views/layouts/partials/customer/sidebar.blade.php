{{-- File: resources/views/layouts/partials/customer/sidebar.blade.php --}}

{{-- ✅ CSS SCROLLBAR KHUSUS (Updated: Lebih Kontras & Wajib Muncul) --}}
<style>
    /* Pastikan scrollbar muncul di Webkit (Chrome, Edge, Opera) */
    .custom-sidebar-scroll::-webkit-scrollbar {
        width: 8px !important; /* Lebar scrollbar */
        display: block !important;
    }

    /* Warna Track (Jalur Scrollbar) */
    .custom-sidebar-scroll::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.1) !important; /* Gelap transparan */
        border-radius: 4px;
    }

    /* Warna Thumb (Batang Geser) - Putih agak terang agar terlihat di background biru */
    .custom-sidebar-scroll::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.4) !important;
        border-radius: 10px;
        border: 2px solid rgba(30, 58, 138, 0); /* Trik agar terlihat lebih kecil dari track */
        background-clip: padding-box;
    }

    /* Saat Mouse Hover di Scrollbar -> Lebih Putih */
    .custom-sidebar-scroll::-webkit-scrollbar-thumb:hover {
        background-color: rgba(255, 255, 255, 0.7) !important;
    }

    /* Untuk Firefox */
    .custom-sidebar-scroll {
        scrollbar-width: thin !important;
        scrollbar-color: rgba(255, 255, 255, 0.4) rgba(0, 0, 0, 0.1) !important;
    }
</style>

<div x-data="{ isExpanded: false, isHovered: false }" class="h-full">

    {{-- Overlay untuk Mobile --}}
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-[90] bg-blue-900 bg-opacity-50 transition-opacity lg:hidden" x-cloak></div>

    <aside
        {{-- Event Hover untuk Desktop --}}
        @mouseenter="isHovered = true"
        @mouseleave="isHovered = false"
        {{-- Logika Lebar --}}
        :class="[
            sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in',
            (isExpanded || isHovered) ? 'w-64' : 'w-64 lg:w-20'
        ]"
        {{--
            ✅ PERBAIKAN CLASS:
            1. 'h-screen' & 'sticky' dihilangkan, ganti 'fixed' agar tinggi konsisten.
            2. 'custom-sidebar-scroll' diterapkan.
            3. 'overflow-y-auto' WAJIB ada.
            4. 'bottom-0' ditambahkan agar tingginya mentok bawah layar.
        --}}
        class="fixed inset-y-0 left-0 bottom-0 z-[100] overflow-y-auto bg-blue-900 text-white transition-all duration-300 transform lg:translate-x-0 lg:static lg:h-screen shadow-xl custom-sidebar-scroll"
        x-cloak>

        {{-- Header Sidebar (Sticky di dalam Sidebar) --}}
        <div class="flex flex-col items-center justify-center py-6 border-b border-blue-800/50 sticky top-0 bg-blue-900 z-10 transition-all duration-300">
            {{-- Tombol Toggle Manual (Desktop) --}}
            <button @click="isExpanded = !isExpanded"
                    class="absolute top-2 right-2 p-1 rounded-md hover:bg-blue-800 text-blue-200 focus:outline-none hidden lg:block"
                    title="Kunci Sidebar (Agar tetap terbuka)">
                {{-- Icon berubah: Jika terkunci (isExpanded) pakai panah, jika tidak pakai bars --}}
                <i :class="isExpanded ? 'fas fa-chevron-left' : 'fas fa-bars'" class="fa-fw"></i>
            </button>

            <a href="{{ route('customer.profile.show') }}" class="flex flex-col items-center text-lg font-bold text-white text-center group">
                @php
                    $user = Auth::user();
                    $logoPath = $user?->store_logo_path;
                    $storeName = $user?->store_name ?? 'Pelanggan';
                    $logoSrc = $logoPath ? asset('public/storage/' . $logoPath) : 'https://ui-avatars.com/api/?name=' . urlencode($storeName) . '&background=4f46e5&color=fff';
                @endphp

                {{-- Logo: Besar saat expanded/hover, Kecil saat mini --}}
                <img :class="(isExpanded || isHovered) ? 'w-16 h-16' : 'w-16 h-16 lg:w-10 lg:h-10 lg:mt-6'"
                     class="object-cover rounded-full mb-3 border-4 border-gray-700 shadow-md transition-all duration-300"
                     src="{{ $logoSrc }}"
                     alt="Logo Toko" />

                {{-- Nama Toko --}}
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="truncate w-40 block transition-opacity duration-200">
                    {{ $storeName }}
                </span>
            </a>
        </div>

        <nav class="mt-4 px-2 space-y-2 pb-20">

            {{-- 1. DASHBOARD MONITOR --}}
            <a href="{{ route('customer.dashboard') }}"
               :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
               class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.dashboard') ? 'bg-gray-900 text-white' : '' }}">
                <i class="fas fa-tachometer-alt fa-fw w-6 text-xl"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 whitespace-nowrap">Dashboard Monitor</span>
            </a>

            @php
                $user = auth()->user();
                $hideMenus = $user->setup_token !== null && $user->profile_setup_at === null && $user->status === 'Tidak Aktif';
            @endphp

            @if(!$hideMenus)

                {{-- ========================================================== --}}
                {{-- MENU BARU: PUSAT BISNIS (Menu Utama untuk Daftar-Daftar) --}}
                {{-- ========================================================== --}}
                <a href="{{ route('customer.business.index') }}"
                :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
                class="flex items-center py-2.5 mt-2 mb-2 bg-gradient-to-r from-green-600 to-green-500 text-white rounded-md shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5 {{ request()->routeIs('customer.business.index') ? 'ring-2 ring-white' : '' }}">
                    <i class="fas fa-briefcase fa-fw w-6 text-xl"></i>
                    <div :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3">
                        <span class="block font-bold whitespace-nowrap">Upgrade Akun Bisnis</span>
                        <span class="block text-[10px] text-yellow-100 leading-none mt-0.5">Daftar Agen / Toko / DANA</span>
                    </div>
                </a>

                <hr class="border-gray-700 my-2 opacity-50">

                {{--
                    CATATAN:
                    Kamu bisa MENGHAPUS atau MENYEMBUNYIKAN tombol "Upgrade Akun"
                    dan "Daftar Merchant DANA" yang lama di sidebar,
                    karena sekarang user diarahkan lewat tombol "Pusat Bisnis" di atas.
                --}}

                {{-- BAGIAN 3: PRODUK DIGITAL --}}
                <p :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Produk Digital</p>
                <hr x-show="!(isExpanded || isHovered)" class="border-gray-700 my-4 hidden lg:block">

                <div x-data="{ open: {{ request()->routeIs('customer.ppob.*') ? 'true' : 'false' }} }" class="space-y-1">
                    <button @click="if(!(isExpanded || isHovered) && window.innerWidth >= 1024) { isExpanded = true; open = true; } else { open = !open; }"
                        :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
                        class="flex items-center w-full py-2.5 text-white hover:bg-red-700 bg-red-600 hover:text-white rounded-md transition-colors duration-200 shadow-sm">

                        {{-- 1. ICON (Selalu Muncul) --}}
                        <i class="fas fa-mobile-alt fa-fw w-6 text-xl"></i>

                        {{-- 2. WRAPPER TEKS (Judul + Deskripsi) --}}
                        {{-- Kita pindahkan logic :class hidden ke DIV pembungkus ini --}}
                        <div :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 flex-1 text-left">

                            {{-- Judul Utama --}}
                            <span class="block font-bold whitespace-nowrap leading-tight">
                                LOKET PPOB
                            </span>

                            {{-- Deskripsi Kecil di Bawahnya --}}
                            <span class="block text-[10px] text-red-100 leading-tight mt-0.5">
                                Token Listrik, PDAM & Pulsa
                            </span>

                        </div>

                        {{-- 3. ICON PANAH (Hanya Muncul saat Expand) --}}
                        <i :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" x-show="open" class="fas fa-chevron-up ml-auto text-xs"></i>
                        <i :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" x-show="!open" class="fas fa-chevron-down ml-auto text-xs"></i>

                    </button>

                    <div x-show="open" x-cloak class="ml-4 pl-4 border-l border-gray-700 space-y-1 mt-1" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                        <a href="{{ route('public.pricelist') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200">
                            <i class="fas fa-sim-card fa-fw w-4 mr-2"></i> Isi Pulsa / Data
                        </a>
                        <a href="https://tokosancaka.com/etalase/ppob/digital/pln-pascabayar" target="_blank" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200">
                            <i class="fas fa-file-invoice-dollar fa-fw w-4 mr-2"></i> Bayar Tagihan
                        </a>
                        <a href="{{ route('customer.ppob.history') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200 {{ request()->routeIs('customer.ppob.history') ? 'bg-gray-800 text-white' : '' }}">
                            <i class="fas fa-history fa-fw w-4 mr-2"></i> Riwayat Transaksi
                        </a>
                    </div>
                </div>

                {{-- BAGIAN 4: PENGIRIMAN --}}
                <p :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Manajemen Pengiriman</p>
                <hr x-show="!(isExpanded || isHovered)" class="border-gray-700 my-4 hidden lg:block">

                <a href="https://tokosancaka.com/customer/chat"
                   :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
                   class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->is('customer/chat*') ? 'bg-gray-900 text-white' : '' }}">
                    <i class="fas fa-comment-dots fa-fw w-6 text-xl"></i>
                    <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 whitespace-nowrap">Chat CS ADMIN</span>
                </a>

                <a href="{{ route('customer.pesanan.create') }}"
                   :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
                   class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.pesanan.create') ? 'bg-gray-900 text-white' : '' }}">
                    <i class="fas fa-plus-circle fa-fw w-6 text-xl"></i>
                    <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 whitespace-nowrap">Kirim Paket (Satuan)</span>
                </a>

                <a href="{{ route('customer.koli.create') }}"
                   :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
                   class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.koli.create') ? 'bg-gray-900 text-white' : '' }}">
                    <i class="fas fa-boxes fa-fw w-6 text-xl"></i>
                    <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 flex items-center w-full whitespace-nowrap">
                        Kirim Massal
                        <span class="ml-auto bg-red-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded">HOT</span>
                    </span>
                </a>

                <a href="{{ route('customer.pesanan.index') }}"
                   :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
                   class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.pesanan.index') ? 'bg-gray-900 text-white' : '' }}">
                    <i class="fas fa-table fa-fw w-6 text-xl"></i>
                    <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 whitespace-nowrap">Data Pengiriman</span>
                </a>

                <a href="{{ route('customer.kontak.index') }}"
                   :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
                   class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.kontak.*') ? 'bg-gray-900 text-white' : '' }}">
                    <i class="fas fa-address-book fa-fw w-6 text-xl"></i>
                    <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 whitespace-nowrap">Data Kontak</span>
                </a>

                <a href="{{ route('customer.lacak.index') }}"
                   :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
                   class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.lacak.index') ? 'bg-gray-900 text-white' : '' }}">
                    <i class="fas fa-search-location fa-fw w-6 text-xl"></i>
                    <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 whitespace-nowrap">Lacak Paket</span>
                </a>

                {{--
                Cari blok menu dropdown "Belanja Disini".
                Ganti baris <a> yang lama dengan yang baru di bawah ini:
                --}}

                {{-- MENU RIWAYAT BELANJA (Fixed Style) --}}
                <a href="{{ route('customer.pesanan.riwayat_belanja') }}"
                   :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
                   class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.pesanan.riwayat_belanja') ? 'bg-gray-900 text-white' : '' }}">
                    <i class="fas fa-history fa-fw w-6 text-xl"></i>
                    <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 whitespace-nowrap">Riwayat Belanja</span>
                </a>

                <a href="{{ route('customer.ongkir.index') }}"
                   :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
                   class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.ongkir.index') ? 'bg-gray-900 text-white' : '' }}">
                    <i class="fas fa-truck fa-fw w-6 text-xl"></i>
                    <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 whitespace-nowrap">Cek Ongkir</span>
                </a>

                {{-- BAGIAN 5: OPERASIONAL --}}
                <p :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Operasional</p>
                <hr x-show="!(isExpanded || isHovered)" class="border-gray-700 my-4 hidden lg:block">

                <a href="{{ route('customer.scan.spx') }}"
                   :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
                   class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.scan.spx') ? 'bg-gray-900 text-white' : '' }}">
                    <i class="fas fa-qrcode fa-fw w-6 text-xl"></i>
                    <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 whitespace-nowrap">Scan Paket SPX</span>
                </a>

                <a href="{{ route('customer.scan.index') }}"
                   :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
                   class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.scan.index') ? 'bg-gray-900 text-white' : '' }}">
                    <i class="fas fa-database fa-fw w-6 text-xl"></i>
                    <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 whitespace-nowrap">Riwayat Scan</span>
                </a>

                {{-- BAGIAN 6: KEUANGAN --}}
                <p :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Keuangan</p>
                <hr x-show="!(isExpanded || isHovered)" class="border-gray-700 my-4 hidden lg:block">

                <a href="{{ route('customer.topup.index') }}"
                   :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
                   class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.topup.*') ? 'bg-gray-900 text-white' : '' }}">
                    <i class="fas fa-wallet fa-fw w-6 text-xl"></i>
                    <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 whitespace-nowrap">Top Up Saldo</span>
                </a>

                <a href="{{ route('customer.laporan.index') }}"
                   :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
                   class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.laporan.index') ? 'bg-gray-900 text-white' : '' }}">
                    <i class="fas fa-chart-area fa-fw w-6 text-xl"></i>
                    <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 whitespace-nowrap">Laporan Keuangan</span>
                </a>

                {{-- BAGIAN 7: BELANJA --}}
                <p :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Lainnya</p>
                <hr x-show="!(isExpanded || isHovered)" class="border-gray-700 my-4 hidden lg:block">

                <div x-data="{ open: false }" class="space-y-1">
                    <button @click="if(!(isExpanded || isHovered) && window.innerWidth >= 1024) { isExpanded = true; open = true; } else { open = !open; }"
                        :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
                        class="flex items-center w-full py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200">
                        <i class="fas fa-store fa-fw w-6 text-xl"></i>
                        <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 flex-1 text-left whitespace-nowrap">Belanja Disini</span>
                        <i :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" x-show="open" class="fas fa-chevron-up ml-auto text-xs"></i>
                        <i :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" x-show="!open" class="fas fa-chevron-down ml-auto text-xs"></i>
                    </button>

                    <div x-show="open" x-cloak class="ml-4 pl-4 border-l border-gray-700 space-y-1 mt-1" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                        <a href="{{ route('katalog.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200">
                            <i class="fas fa-book-open fa-fw w-4 mr-2"></i> Katalog
                        </a>
                        <a href="{{ url('/etalase') }}" target="_blank" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200">
                            <i class="fas fa-store fa-fw w-4 mr-2"></i> Etalase
                        </a>
                        <a href="/customer/pesanan/riwayat" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200">
                            <i class="fas fa-history fa-fw w-4 mr-2"></i> Riwayat Belanja
                        </a>
                    </div>
                </div>

                {{-- BAGIAN 8: KHUSUS SELLER --}}
                @if (auth()->user()->role === 'Seller' || auth()->user()->role === 'agent' || auth()->user()->role === 'admin')
                    <p :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="px-4 pt-4 pb-2 text-xs text-gray-400 uppercase tracking-wider">Toko Saya</p>
                    <hr x-show="!(isExpanded || isHovered)" class="border-gray-700 my-4 hidden lg:block">

                    <div x-data="{ open: {{ request()->routeIs('seller.*') ? 'true' : 'false' }} }" class="space-y-1">
                        <button @click="if(!(isExpanded || isHovered) && window.innerWidth >= 1024) { isExpanded = true; open = true; } else { open = !open; }"
                            :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
                            class="flex items-center w-full py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200">
                            <i class="fas fa-store-alt fa-fw w-6 text-xl"></i>
                            <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 flex-1 text-left whitespace-nowrap">Kelola Toko</span>
                            <i :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" x-show="open" class="fas fa-chevron-up ml-auto text-xs"></i>
                            <i :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" x-show="!open" class="fas fa-chevron-down ml-auto text-xs"></i>
                        </button>

                        <div x-show="open" x-cloak class="ml-4 pl-4 border-l border-gray-700 space-y-1 mt-1" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                            <a href="{{ route('seller.dashboard') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200 {{ request()->routeIs('seller.dashboard') ? 'bg-gray-800 text-white' : '' }}">
                                <i class="fas fa-tachometer-alt fa-fw w-4 mr-2"></i> Dashboard Toko
                            </a>
                            {{-- [MENU BARU]: CREATE SHOP DANA --}}
                            <a href="{{ route('customer.merchant.create') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200 {{ request()->routeIs('customer.merchant.create') ? 'bg-gray-800 text-white' : '' }}">
                                <i class="fas fa-id-card fa-fw w-4 mr-2 text-blue-400"></i> Daftar Merchant DANA
                            </a>
                            {{-- END MENU BARU --}}
                            <a href="{{ route('customer.merchant.index') }}"
                            class="{{ request()->routeIs('customer.merchant.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                <i class="fas fa-store mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('customer.merchant.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}"></i>
                                Data Toko DANA
                            </a>
                            <a href="{{ route('seller.doku.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200 {{ request()->routeIs('seller.doku.*') ? 'bg-gray-800 text-white' : '' }}">
                                <i class="fas fa-wallet fa-fw w-4 mr-2"></i> Dompet Sancaka
                            </a>
                            <a href="{{ route('seller.produk.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200 {{ request()->routeIs('seller.produk.*') ? 'bg-gray-800 text-white' : '' }}">
                                <i class="fas fa-boxes fa-fw w-4 mr-2"></i> Kelola Produk
                            </a>
                            <a href="{{ route('seller.reviews.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200 {{ request()->routeIs('seller.reviews.*') ? 'bg-gray-800 text-white' : '' }}">
                                <i class="fas fa-star fa-fw w-4 mr-2"></i> Ulasan Produk
                            </a>
                            <a href="{{ route('seller.pesanan.marketplace.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200 {{ request()->routeIs('seller.pesanan.marketplace.*') ? 'bg-gray-800 text-white' : '' }}">
                                <i class="fas fa-shopping-basket fa-fw w-4 mr-2"></i> Pesanan Marketplace
                            </a>
                            <a href="{{ route('seller.profile.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md transition-colors duration-200 {{ request()->routeIs('seller.profile.edit') ? 'bg-gray-800 text-white' : '' }}">
                                <i class="fas fa-user-edit fa-fw w-4 mr-2"></i> Profil Toko
                            </a>
                        </div>
                    </div>
                @endif

                {{-- BAGIAN 9: PENGATURAN USER --}}
                <div class="mt-auto">
                    <a href="{{ route('customer.profile.edit') }}"
                    :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
                    class="flex items-center py-2.5 mt-4 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.profile.edit') ? 'bg-gray-900 text-white' : '' }}">
                        <i class="fas fa-cog fa-fw w-6 text-xl"></i>
                        <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 whitespace-nowrap">Pengaturan Akun</span>
                    </a>
                </div>

            @endif

            {{-- LOGOUT --}}
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form-customer').submit();"
               :class="(isExpanded || isHovered) ? 'justify-start px-4' : 'justify-start px-4 lg:justify-center lg:px-0'"
               class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200">
                <i class="fas fa-sign-out-alt fa-fw w-6 text-xl text-red-400"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="ml-3 whitespace-nowrap">Logout</span>
            </a>
            <form id="logout-form-customer" action="{{ route('logout') }}" method="POST" class="hidden">
                @csrf
            </form>

        </nav>
    </aside>

    {{-- TOMBOL CLOSE FLOATING (Tetap hanya untuk Mobile) --}}
    <div x-show="sidebarOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-x-10"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 -translate-x-10"
         class="fixed top-4 left-64 z-[110] ml-2 lg:hidden"
         x-cloak>

        <button @click="sidebarOpen = false"
                class="flex items-center justify-center w-10 h-10 bg-blue-900 text-white rounded-full shadow-lg border-2 border-white/20 hover:bg-red-600 hover:scale-110 transition-all duration-300 focus:outline-none ring-2 ring-black/10">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
    </div>

</div>
