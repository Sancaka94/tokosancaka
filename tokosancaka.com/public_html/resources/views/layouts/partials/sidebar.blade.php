{{--
    File: resources/views/layouts/partials/sidebar.blade.php
    Deskripsi: Sidebar Fixed & Responsive - Refactored & Polished.
--}}

<div x-data="{ sidebarOpen: false, isExpanded: false, isHovered: false }" class="h-full flex flex-col">

    {{-- Overlay untuk Mobile --}}
    <div x-show="sidebarOpen"
         @click="sidebarOpen = false"
         class="fixed inset-0 z-[49] bg-blue-900 bg-opacity-50 transition-opacity lg:hidden"
         style="display: none;"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    {{-- Sidebar Container (Zoom 75%) --}}
    <aside id="main-sidebar"
        style="zoom: 75%;"
        @mouseenter="if(window.innerWidth >= 1024) isHovered = true"
        @mouseleave="isHovered = false"
        :class="[
            sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in lg:translate-x-0',
            (isExpanded || isHovered) ? 'w-[280px]' : 'w-[280px] lg:w-20'
        ]"
        class="bg-blue-900 text-gray-300 flex-shrink-0 flex flex-col min-h-[133.33vh] h-full fixed inset-y-0 left-0 z-50 transform transition-all duration-300 lg:static lg:inset-auto shadow-xl overflow-hidden">

        {{-- Tombol Close untuk Mobile --}}
        <div class="flex justify-end p-4 lg:hidden">
            <div id="close-wrapper" class="absolute top-4 -right-12 lg:hidden z-50">
                <button type="button" @click="sidebarOpen = false" class="group flex items-center justify-center w-10 h-10 rounded-full bg-blue-900 backdrop-blur-md border border-white/20 text-white shadow-lg transition-all duration-300 hover:bg-blue-600 ring-1 ring-white/10">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- USER PANEL --}}
        <div class="flex flex-col items-center p-6 border-b border-gray-700 relative z-10 transition-all duration-300">
            <img src="https://tokosancaka.com/storage/uploads/sancaka.png"
                 :class="(isExpanded || isHovered) ? 'w-20 h-20' : 'w-10 h-10 lg:mt-4'"
                 class="rounded-full mb-3 transition-all duration-300 object-cover border-2 border-gray-600"
                 alt="User Image"
                 onerror="this.onerror=null;this.src='https://placehold.co/80x80/1f2937/ffffff?text=Logo';">

            <div :class="(isExpanded || isHovered) ? 'opacity-100 max-h-20' : 'opacity-0 max-h-0 lg:hidden'" class="font-bold text-lg text-white transition-all duration-300 text-center overflow-hidden whitespace-nowrap">
                {{ Auth::user()->nama_lengkap ?? 'Sancaka Express' }}
            </div>

            <div :class="(isExpanded || isHovered) ? 'opacity-100 max-h-10' : 'opacity-0 max-h-0 lg:hidden'" class="flex items-center text-sm mt-1 transition-all duration-300 overflow-hidden whitespace-nowrap">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                <span class="text-green-400">Online</span>
            </div>
        </div>

        {{-- SEARCH FORM --}}
        <div class="p-4 transition-all duration-300" :class="(isExpanded || isHovered) ? '' : 'lg:hidden'">
            <form action="#" method="get">
                <div class="relative">
                    <input type="text" name="q" class="w-full bg-red-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block pl-10 p-2.5" placeholder="Cari...">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fa-solid fa-search text-gray-400"></i>
                    </div>
                </div>
            </form>
        </div>

        {{-- MENU NAVIGATION --}}
        <nav id="sidebar-nav"
             class="flex-1 px-4 pb-4 space-y-1 overflow-x-hidden transition-all duration-300 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-600 scrollbar-track-transparent"
             :class="(isExpanded || isHovered) ? '' : 'lg:overflow-hidden'">

            {{-- ================= UTAMA ================= --}}

            {{-- Dashboard --}}
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-red-600 hover:text-white transition-colors duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-red-700 text-white' : '' }}">
                <i class="fa-solid fa-house-chimney fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Dashboard</span>
            </a>

            {{-- Email Sancaka --}}
            <a href="{{ url('admin/email') }}" class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-red-600 hover:text-white transition-colors duration-200 {{ request()->routeIs('admin.index*') ? 'bg-red-700 text-white' : '' }}">
                <i class="fa-solid fa-inbox fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Email Sancaka</span>
            </a>

            {{-- Chat Customer --}}
            <a href="https://tokosancaka.com/admin/chat" class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-red-600 hover:text-white transition-colors duration-200 {{ request()->is('admin/chat*') ? 'bg-red-700 text-white' : '' }}">
                <i class="fa-solid fa-comment-dots fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Chat Customer</span>
            </a>

            {{-- Data Pelanggan --}}
            <a href="{{ route('admin.pelanggan.index') }}" class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-red-600 hover:text-white transition-colors duration-200 {{ request()->routeIs('admin.pelanggan.*') ? 'bg-red-700 text-white' : '' }}">
                <i class="fa-solid fa-users fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Data Pelanggan</span>
            </a>

            {{-- Monitor Surat Jalan --}}
            <a href="{{ route('admin.spx_scans.monitor.index') }}" class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-red-600 hover:text-white transition-colors duration-200 {{ request()->routeIs('admin.spx_scans.monitor.index') ? 'bg-red-700 text-white' : '' }}">
                <i class="fa-solid fa-truck fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Monitor Surat Jalan</span>
            </a>

            {{-- ================= ADMIN & USER ================= --}}

            {{-- Pengguna & Role --}}
            <div>
                <button onclick="toggleMenu('menuPengguna')"
                        @click="if(window.innerWidth >= 1024 && !(isExpanded || isHovered)) { isExpanded = true; }"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-left rounded-lg hover:bg-red-600 hover:text-white focus:outline-none transition-colors duration-200 {{ request()->routeIs(['admin.registrations.*', 'admin.customers.*', 'admin.roles.*']) ? 'bg-red-700 text-white' : '' }}">
                    <span class="flex items-center">
                        <i class="fa-solid fa-users-gear fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                        <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Pengguna & Role</span>
                    </span>
                    <div :class="(isExpanded || isHovered) ? 'flex' : 'flex lg:hidden'" class="items-center ml-auto">
                        <span id="menu-pengguna-badge" class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold text-white bg-green-500 rounded-md mr-2 hidden">0</span>
                        <i id="arrow-menuPengguna" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="fa-solid fa-chevron-down w-4 h-4 transform transition-transform duration-200"></i>
                    </div>
                </button>
                <div id="menuPengguna" class="submenu mt-1" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                    <ul class="pl-8 pr-2 py-1 space-y-1">
                        <li>
                            <a href="{{ route('admin.registrations.index') }}" class="sidebar-link flex justify-between items-center px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.registrations.*') ? 'text-white' : 'text-gray-400' }}">
                                <span>Persetujuan</span>
                                <span id="persetujuan-badge" class="inline-flex items-center justify-center px-2 text-xs font-bold text-white bg-green-500 rounded-md hidden">0</span>
                            </a>
                        </li>
                        <li><a href="{{ route('admin.customers.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.customers.*') ? 'text-white' : 'text-gray-400' }}">Manajemen Pelanggan</a></li>
                        <li><a href="{{ route('admin.customers.data.pengguna.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.customers.data.pengguna.index') ? 'text-white' : 'text-gray-400' }}">Manajemen Data Pengguna</a></li>
                        <li><a href="{{ route('admin.roles.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.roles.*') ? 'text-white' : 'text-gray-400' }}">Hak Akses Role</a></li>
                    </ul>
                </div>
            </div>

            {{-- Wilayah --}}
            <a href="{{ route('admin.wilayah.index') }}" class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-red-600 hover:text-white transition-colors duration-200 {{ request()->routeIs('admin.wilayah.*') ? 'bg-red-700 text-white' : '' }}">
                <i class="fa-solid fa-map-marked-alt fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Wilayah</span>
            </a>

            {{-- Pencarian Kode Pos --}}
            <a href="{{ route('admin.kodepos.index') }}" class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-red-600 hover:text-white transition-colors duration-200 {{ request()->routeIs('admin.kodepos.*') ? 'bg-red-700 text-white' : '' }}">
                <i class="fa-solid fa-magnifying-glass-location fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Pencarian Kode Pos</span>
            </a>

            {{-- ================= MARKETPLACE ================= --}}

            {{-- Marketplace Dropdown --}}
            <div>
                <button onclick="toggleMenu('marketplaceMenu')"
                        @click="if(window.innerWidth >= 1024 && !(isExpanded || isHovered)) { isExpanded = true; }"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-left rounded-lg hover:bg-red-600 hover:text-white focus:outline-none transition-colors duration-200 {{ request()->is('admin/products*') || request()->is('admin/spx-scans*') ? 'bg-red-700 text-white' : '' }}">
                    <span class="flex items-center">
                        <i class="fa-solid fa-store fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                        <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Marketplace</span>
                    </span>
                    <div :class="(isExpanded || isHovered) ? 'flex' : 'flex lg:hidden'" class="items-center ml-auto">
                        <span id="menu-marketplace-badge" class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold text-white bg-green-500 rounded-md mr-2 hidden">0</span>
                        <i id="arrow-marketplaceMenu" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="fa-solid fa-chevron-down w-4 h-4 transform transition-transform duration-200"></i>
                    </div>
                </button>
                <div id="marketplaceMenu" class="submenu mt-1" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                    <ul class="pl-8 pr-2 py-1 space-y-1">
                        <li><a href="{{ route('admin.reviews.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.reviews.*') ? 'text-white' : 'text-gray-400' }}"><i class="fas fa-star mr-2 text-yellow-500"></i> Manajemen Ulasan</a></li>
                        <li><a href="{{ route('admin.categories.index', ['type' => 'marketplace']) }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.categories.*') && request('type') == 'marketplace' ? 'text-white' : 'text-gray-400' }}">Kategori Produk</a></li>
                        <li><a href="{{ route('admin.stores.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.stores.index') || request()->routeIs('admin.stores.edit') ? 'text-white' : 'text-gray-400' }}">Kelola Toko</a></li>
                        <li><a href="{{ route('admin.stores.create') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.stores.create') ? 'text-white' : 'text-gray-400' }}">Daftar Toko (Admin)</a></li>
                        <li><a href="{{ route('admin.customer-to-seller.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.customer-to-seller.*') ? 'text-white' : 'text-gray-400' }}">Create Penjual</a></li>
                        <li><a href="{{ route('admin.orders.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.orders.*') ? 'text-white' : 'text-gray-400' }}">Data Pesanan Masuk</a></li>
                        <li><a href="{{ route('admin.products.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.products.index') ? 'text-white' : 'text-gray-400' }}">Daftar Produk</a></li>
                        <li><a href="{{ route('admin.products.create') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.products.create') ? 'text-white' : 'text-gray-400' }}">Tambah Produk</a></li>
                        <li><a href="{{ route('admin.spx_scans.create') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('scan.spx.show') ? 'text-white' : 'text-gray-400' }}">Scan SPX</a></li>
                        <li>
                            <a href="{{ route('admin.spx_scans.index') }}" class="sidebar-link flex justify-between items-center px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.spx-scans.index') ? 'text-white' : 'text-gray-400' }}">
                                <span>Data Scan SPX</span>
                                <span id="spx-badge" class="inline-flex items-center justify-center px-2 text-xs font-bold text-white bg-green-500 rounded-md hidden">0</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Pesanan Dropdown --}}
            <div>
                <button onclick="toggleMenu('menuPesanan')"
                        @click="if(window.innerWidth >= 1024 && !(isExpanded || isHovered)) { isExpanded = true; }"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-left rounded-lg hover:bg-red-600 hover:text-white focus:outline-none transition-colors duration-200 {{ request()->is('admin/pesanan*') ? 'bg-red-700 text-white' : '' }}">
                    <span class="flex items-center">
                        <i class="fa-solid fa-cart-shopping fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                        <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Pesanan</span>
                    </span>
                    <div :class="(isExpanded || isHovered) ? 'flex' : 'flex lg:hidden'" class="items-center ml-auto">
                        <span id="menu-pesanan-badge" class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold text-white bg-green-500 rounded-md mr-2 hidden">0</span>
                        <i id="arrow-menuPesanan" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="fa-solid fa-chevron-down w-4 h-4 transform transition-transform duration-200"></i>
                    </div>
                </button>
                <div id="menuPesanan" class="submenu mt-1" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                    <ul class="pl-8 pr-2 py-1 space-y-1">
                        <li><a href="{{ route('admin.pesanan.create') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.pesanan.create') ? 'text-white' : 'text-gray-400' }}">Tambah Pesanan</a></li>
                        <li><a href="{{ route('admin.pesanan.create_multi') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.pesanan.create_multi') ? 'text-white' : 'text-gray-400' }}">Kirim Massal <span class="ml-2 bg-red-600 text-[10px] text-white px-1.5 py-0.5 rounded">BARU</span></a></li>
                        <li>
                            <a href="{{ route('admin.pesanan.index') }}" class="sidebar-link flex justify-between items-center px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.pesanan.index') ? 'text-white' : 'text-gray-400' }}">
                                <span>Data Pesanan</span>
                                <span id="pesanan-badge" class="inline-flex items-center justify-center px-2 text-xs font-bold text-white bg-green-500 rounded-md hidden">0</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.pesanan.riwayat.scan') }}" class="sidebar-link flex justify-between items-center px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.pesanan.riwayat.scan') ? 'text-white' : 'text-gray-400' }}">
                                <span>Riwayat Scan</span>
                                 <span id="riwayat-scan-badge" class="inline-flex items-center justify-center px-2 text-xs font-bold text-white bg-green-500 rounded-md hidden">0</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Produk Katalog --}}
            <a href="{{ route('admin.marketplace.index') }}" class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-red-600 hover:text-white transition-colors duration-200 {{ request()->routeIs('admin.marketplace.*') ? 'bg-red-700 text-white' : '' }}">
                <i class="fa-solid fa-store fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Produk Katalog</span>
            </a>

            {{-- ================= LOGISTIK & PPOB ================= --}}

            {{-- Master Ekspedisi (Disejajarkan dengan Menu Utama) --}}
            <a href="{{ route('admin.ekspedisi.index') }}" class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-red-600 hover:text-white transition-colors duration-200 {{ request()->routeIs('admin.ekspedisi.*') ? 'bg-red-700 text-white' : '' }}">
                <i class="fas fa-truck-moving fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Master Ekspedisi</span>
            </a>

            {{-- Manajemen Kurir --}}
            <div>
                <button onclick="toggleMenu('menuKurir')"
                        @click="if(window.innerWidth >= 1024 && !(isExpanded || isHovered)) { isExpanded = true; }"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-left rounded-lg hover:bg-red-600 hover:text-white focus:outline-none transition-colors duration-200 {{ request()->routeIs('admin.couriers.*') ? 'bg-red-700 text-white' : '' }}">
                    <span class="flex items-center">
                        <i class="fa-solid fa-truck-fast fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                        <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Manajemen Kurir</span>
                    </span>
                    <i id="arrow-menuKurir" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="fa-solid fa-chevron-down w-4 h-4 transform transition-transform duration-200"></i>
                </button>
                <div id="menuKurir" class="submenu mt-1" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                    <ul class="pl-8 pr-2 py-1 space-y-1">
                        <li><a href="{{ route('admin.couriers.create') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.couriers.create') ? 'text-white' : 'text-gray-400' }}">Tambah Kurir</a></li>
                        <li><a href="{{ route('admin.couriers.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.couriers.index') ? 'text-white' : 'text-gray-400' }}">Daftar Kurir</a></li>
                    </ul>
                </div>
            </div>

            {{-- PPOB --}}
            <div>
                <button onclick="toggleMenu('menuPpob')"
                        @click="if(window.innerWidth >= 1024 && !(isExpanded || isHovered)) { isExpanded = true; }"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-left rounded-lg hover:bg-red-600 hover:text-white focus:outline-none transition-colors duration-200 {{ request()->routeIs('admin.ppob.*') ? 'bg-red-700 text-white' : '' }}">
                    <span class="flex items-center">
                        <i class="fa-solid fa-mobile-screen-button fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                        <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">PPOB</span>
                    </span>
                    <i id="arrow-menuPpob" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="fa-solid fa-chevron-down w-4 h-4 transform transition-transform duration-200"></i>
                </button>
                <div id="menuPpob" class="submenu mt-1" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                    <ul class="pl-8 pr-2 py-1 space-y-1">
                        <li><a href="{{ route('admin.ppob.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.ppob.index') ? 'text-white' : 'text-gray-400' }}">Produk PPOB</a></li>
                        <li><a href="{{ route('admin.ppob.data.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.ppob.data.index') ? 'text-white' : 'text-gray-400' }}">Data Transaksi</a></li>
                    </ul>
                </div>
            </div>

            {{-- ================= KEUANGAN ================= --}}

            {{-- Laporan Keuangan --}}
            <div>
                <button onclick="toggleMenu('menuKeuangan')"
                        @click="if(window.innerWidth >= 1024 && !(isExpanded || isHovered)) { isExpanded = true; }"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-left rounded-lg hover:bg-red-600 hover:text-white focus:outline-none transition-colors duration-200 {{ request()->routeIs('admin.saldo.requests.*') || request()->routeIs('admin.keuangan.*') || request()->routeIs('admin.akuntansi.*') ? 'bg-red-700 text-white' : '' }}">
                    <span class="flex items-center">
                        <i class="fa-solid fa-chart-pie fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                        <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Laporan Keuangan</span>
                    </span>
                    <div :class="(isExpanded || isHovered) ? 'flex' : 'flex lg:hidden'" class="items-center ml-auto">
                        <span id="menu-keuangan-badge" class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold text-white bg-orange-500 rounded-md mr-2 hidden">0</span>
                        <i id="arrow-menuKeuangan" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="fa-solid fa-chevron-down w-4 h-4 transform transition-transform duration-200"></i>
                    </div>
                </button>
                <div id="menuKeuangan" class="submenu mt-1" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                    <ul class="pl-8 pr-2 py-1 space-y-1">

                        {{-- Jurnal & Akuntansi --}}
                        <li>
                            <a href="{{ route('admin.akuntansi.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.akuntansi.*') ? 'text-white' : 'text-gray-400' }}">
                                <i class="fas fa-book-journal-whills mr-2 text-blue-400"></i> Jurnal & Akuntansi
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('admin.saldo.requests.index') }}" class="sidebar-link flex justify-between items-center px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.saldo.requests.*') ? 'text-white' : 'text-gray-400' }}">
                                <span><i class="fas fa-hand-holding-dollar mr-2 text-yellow-500"></i> Permintaan Saldo</span>
                                <span id="saldo-requests-badge" class="inline-flex items-center justify-center px-2 text-xs font-bold text-white bg-orange-500 rounded-md hidden">0</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.keuangan.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.keuangan.index') ? 'text-white' : 'text-gray-400' }}">
                                <i class="fas fa-money-bill-transfer mr-2 text-indigo-400"></i> Transaksi Keuangan
                            </a>
                        </li>

                        {{-- Laba Rugi (Tahunan) - Disamakan Style-nya --}}
                        <li>
                            <a href="{{ route('admin.keuangan.laba_rugi') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.laporan.laba_rugi') ? 'text-white' : 'text-gray-400' }}">
                                <i class="fas fa-file-invoice-dollar mr-2 text-green-400"></i> Laba Rugi (Tahunan)
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('admin.saldo.requests.history') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.saldo.requests.history') ? 'text-white' : 'text-gray-400' }}">
                                <i class="fas fa-clock-rotate-left mr-2 text-purple-400"></i> Riwayat Top Up
                            </a>
                        </li>
                        <li>
                            <a href="{{ url('admin/wallet') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->is('admin/wallet*') ? 'text-white' : 'text-gray-400' }}">
                                <i class="fas fa-wallet mr-2 text-orange-400"></i> Dompet Pelanggan
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.pemasukan') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.laporan.pemasukan*') ? 'text-white' : 'text-gray-400' }}">
                                <i class="fas fa-arrow-trend-up mr-2 text-emerald-500"></i> Pemasukan
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.pengeluaran') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.laporan.pengeluaran*') ? 'text-white' : 'text-gray-400' }}">
                                <i class="fas fa-arrow-trend-down mr-2 text-rose-500"></i> Pengeluaran
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.labaRugi') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.laporan.labaRugi*') ? 'text-white' : 'text-gray-400' }}">
                                <i class="fas fa-chart-column mr-2 text-teal-400"></i> Laba Rugi
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.coa.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.coa.*') ? 'text-white' : 'text-gray-400' }}">
                                <i class="fas fa-list-check mr-2 text-gray-300"></i> Manajemen Akun (COA)
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.neracaSaldo') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.laporan.neracaSaldo') ? 'text-white' : 'text-gray-400' }}">
                                <i class="fas fa-scale-unbalanced mr-2 text-cyan-400"></i> Neraca Saldo
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.neraca') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.laporan.neraca') ? 'text-white' : 'text-gray-400' }}">
                                <i class="fas fa-scale-balanced mr-2 text-blue-500"></i> Neraca
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            {{-- Rekonsiliasi Bank --}}

            {{-- ================= PRODUKSI & INVENTORY ================= --}}

            {{-- Manajemen Produksi --}}

            {{-- ================= KOMUNIKASI & BLOG ================= --}}

            {{-- Buku Alamat --}}
            <a href="{{ route('admin.kontak.index') }}" class="sidebar-link flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-red-600 hover:text-white transition-colors duration-200 {{ request()->routeIs('admin.kontak.*') ? 'bg-red-700 text-white' : '' }}">
                <i class="fa-solid fa-address-book fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Buku Alamat</span>
            </a>

            {{-- Chat Whatsapp --}}
            <div>
                <button onclick="toggleMenu('menuWhatsapp')"
                        @click="if(window.innerWidth >= 1024 && !(isExpanded || isHovered)) { isExpanded = true; }"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-left rounded-lg hover:bg-red-600 hover:text-white focus:outline-none transition-colors duration-200 {{ request()->routeIs('broadcast.*') || request()->is('whatsapp*') ? 'bg-red-700 text-white' : '' }}">
                    <span class="flex items-center">
                        <i class="fa-brands fa-whatsapp fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                        <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Chat Whatsapp</span>
                    </span>
                    <i id="arrow-menuWhatsapp" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="fa-solid fa-chevron-down w-4 h-4 transform transition-transform duration-200"></i>
                </button>
                <div id="menuWhatsapp" class="submenu mt-1" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                    <ul class="pl-8 pr-2 py-1 space-y-1">
                        {{-- Menu PushWA --}}
                        <li><a href="{{ route('admin.wa.scan') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.wa.scan') ? 'text-white' : 'text-gray-400' }}">Scan PushWA</a></li>

                        {{-- Menu BARU: Scan Fonnte --}}
                        <li><a href="{{ route('admin.fonnte.scan') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.fonnte.scan') ? 'text-white' : 'text-gray-400' }}">Scan Fonnte</a></li>
                        <li><a href="{{ route('broadcast.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('broadcast.*') ? 'text-white' : 'text-gray-400' }}">Kirim Pesan (Broadcast)</a></li>
                        <li><a href="{{ url('whatsapp') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->is('whatsapp*') ? 'text-white' : 'text-gray-400' }}">Inbox (Whatsapp)</a></li>
                    </ul>
                </div>
            </div>

            {{-- Blog --}}
            <div>
                <button onclick="toggleMenu('menuBlog')"
                        @click="if(window.innerWidth >= 1024 && !(isExpanded || isHovered)) { isExpanded = true; }"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-left rounded-lg hover:bg-red-600 hover:text-white focus:outline-none transition-colors duration-200 {{ request()->is('admin/posts*') || request()->is('admin/categories*') || request()->is('admin/tags*') ? 'bg-red-700 text-white' : '' }}">
                    <span class="flex items-center">
                        <i class="fa-solid fa-newspaper fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                        <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Blog</span>
                    </span>
                    <i id="arrow-menuBlog" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="fa-solid fa-chevron-down w-4 h-4 transform transition-transform duration-200"></i>
                </button>
                <div id="menuBlog" class="submenu mt-1" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                    <ul class="pl-8 pr-2 py-1 space-y-1">
                        <li><a href="{{ route('admin.posts.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs(['admin.posts.index', 'admin.posts.edit']) ? 'text-white' : 'text-gray-400' }}">Semua Postingan</a></li>
                        <li><a href="{{ route('admin.posts.create') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.posts.create') ? 'text-white' : 'text-gray-400' }}">Tambah Baru</a></li>
                        <li><a href="{{ route('admin.categories.index', ['type' => 'blog']) }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.categories.*') ? 'text-white' : 'text-gray-400' }}">Kategori</a></li>
                        <li><a href="{{ route('admin.tags.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.tags.*') ? 'text-white' : 'text-gray-400' }}">Tag</a></li>
                    </ul>
                </div>
            </div>

            {{-- ================= PENGATURAN ================= --}}

            {{-- Pengaturan (Utilitas) --}}
            <div>
                <button onclick="toggleMenu('menuUtilitas')"
                        @click="if(window.innerWidth >= 1024 && !(isExpanded || isHovered)) { isExpanded = true; }"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-left rounded-lg hover:bg-red-600 hover:text-white focus:outline-none transition-colors duration-200">
                    <span class="flex items-center">
                        <i class="fa-solid fa-gears fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                        <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Pengaturan</span>
                    </span>
                    <i id="arrow-menuUtilitas" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="fa-solid fa-chevron-down w-4 h-4 transform transition-transform duration-200"></i>
                </button>
                <div id="menuUtilitas" class="submenu mt-1" :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                    <ul class="pl-8 pr-2 py-1 space-y-1">
                        <li><a href="{{ route('admin.logs.show') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.logs.show') ? 'text-red-400' : 'text-red-400' }}">Log Error</a></li>
                        <li><a href="{{ route('admin.activity-log.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.activity-log.index') ? 'text-white' : 'text-gray-400' }}">Log Aktivitas</a></li>
                        <li><a href="{{ route('admin.settings.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.settings.index') ? 'text-white' : 'text-gray-400' }}">Pengaturan Aplikasi</a></li>
                        <li><a href="{{ route('admin.settings.api.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.settings.api.index') ? 'text-white' : 'text-gray-400' }}">Konfigurasi API</a></li>
                        <li><a href="{{ route('admin.settings.banners.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.settings.banners.index') ? 'text-white' : 'text-gray-400' }}">Pengaturan Marketplace</a></li>
                        <li><a href="{{ route('admin.category-attributes.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.category-attributes.*') ? 'text-white' : 'text-gray-400' }}">Atribut Kategori</a></li>
                        <li><a href="{{ route('admin.sliders.index') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('admin.sliders.*') ? 'text-white' : 'text-gray-400' }}">Manajemen Slider Informasi</a></li>
                        <li><a href="{{ route('admin.info.edit') }}" class="sidebar-link block px-4 py-2 text-sm rounded-md hover:text-white hover:bg-red-600 {{ request()->routeIs('info.edit') ? 'text-white' : 'text-gray-400' }}">Info Halaman Pesanan</a></li>
                    </ul>
                </div>
            </div>

        </nav>

        {{-- LOGOUT BUTTON --}}
        <div class="mt-auto p-4 border-t border-gray-700 transition-all duration-300">
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="flex items-center w-full px-4 py-2.5 text-sm font-medium text-red-400 rounded-lg hover:bg-red-600 hover:text-white transition-colors duration-200">
                <i class="fa-solid fa-arrow-right-from-bracket fa-fw w-5 h-5 mr-3 flex-shrink-0"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap transition-opacity duration-200">Keluar</span>
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                @csrf
            </form>
        </div>

    </aside>
</div>

@push('scripts')
<script>
    @include('layouts.partials.sidebar-scripts')
</script>
@endpush
