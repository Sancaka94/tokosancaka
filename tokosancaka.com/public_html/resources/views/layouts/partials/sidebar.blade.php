{{--
    File: resources/views/layouts/partials/sidebar.blade.php
    Deskripsi: Sidebar White Theme (Compact Header).
--}}

{{-- CSS Khusus Scrollbar Tipis --}}
<style>
    .sidebar-scrollbar::-webkit-scrollbar { width: 4px; }
    .sidebar-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .sidebar-scrollbar::-webkit-scrollbar-thumb { background: transparent; border-radius: 50px; }
    .sidebar-scrollbar:hover::-webkit-scrollbar-thumb { background: #cbd5e1; }
</style>

<div x-data="{
        isExpanded: false,
        isHovered: false,
        isMobile: window.innerWidth < 1024,
        searchQuery: ''
     }"
     @resize.window="isMobile = window.innerWidth < 1024"
     class="h-full flex flex-col">

    {{-- Overlay untuk Mobile --}}
    <div x-show="sidebarOpen"
         @click="sidebarOpen = false"
         class="fixed inset-0 z-[49] bg-gray-900 bg-opacity-50 transition-opacity lg:hidden"
         style="display: none;"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    {{-- Sidebar Container --}}
    <aside id="main-sidebar"
        @mouseenter="if(!isMobile) isHovered = true"
        @mouseleave="isHovered = false"
        :class="[
            sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in lg:translate-x-0',
            (isExpanded || isHovered) ? 'w-[260px]' : 'w-[260px] lg:w-[70px]'
        ]"
        class="bg-white text-gray-600 border-r border-gray-200 flex-shrink-0 flex flex-col h-full fixed inset-y-0 left-0 z-50 transform transition-[width,transform] duration-200 lg:static lg:inset-auto shadow-xl lg:shadow-none overflow-hidden will-change-transform">

        {{-- Tombol Close untuk Mobile --}}
        <div class="flex justify-end p-2 lg:hidden">
            <button type="button" @click="sidebarOpen = false" class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- USER PANEL (COMPACT VERSION) --}}
        {{-- Mengubah py-6 menjadi py-3 agar lebih rapat --}}
        <div class="flex flex-col items-center py-3 px-2 border-b border-gray-100 relative z-10">
            <img src="https://tokosancaka.com/storage/uploads/sancaka.png"
                 {{-- Mengubah mb-3 menjadi mb-1 agar jarak ke teks lebih dekat --}}
                 :class="(isExpanded || isHovered || isMobile) ? 'w-14 h-14 mb-1' : 'w-9 h-9 lg:mt-1'"
                 class="rounded-full object-cover border border-gray-200 shadow-sm transition-all duration-200"
                 alt="User Image"
                 onerror="this.onerror=null;this.src='https://placehold.co/80x80/f3f4f6/4b5563?text=Logo';">

            <div :class="(isExpanded || isHovered || isMobile) ? 'opacity-100 max-h-20' : 'opacity-0 max-h-0 lg:hidden'" class="font-bold text-sm text-gray-800 text-center overflow-hidden whitespace-nowrap transition-opacity duration-200">
                {{ Auth::user()->nama_lengkap ?? 'Sancaka Express' }}
            </div>

            <div :class="(isExpanded || isHovered || isMobile) ? 'opacity-100 max-h-10' : 'opacity-0 max-h-0 lg:hidden'" class="flex items-center text-[10px] mt-0.5 overflow-hidden whitespace-nowrap transition-opacity duration-200">
                <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1"></span>
                <span class="text-green-600 font-medium">Online</span>
            </div>
        </div>

        {{-- SEARCH FORM (COMPACT) --}}
        {{-- Mengubah p-3 menjadi p-2 --}}
        <div class="p-2" :class="(isExpanded || isHovered) ? '' : 'lg:hidden'">
            <div class="relative">
                <input type="text"
                       x-model="searchQuery"
                       class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block pl-9 p-1.5 transition-all"
                       placeholder="Cari menu...">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="fa-solid fa-search text-gray-400 text-xs"></i>
                </div>
                <div x-show="searchQuery.length > 0" @click="searchQuery = ''" class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer text-gray-400 hover:text-red-500">
                    <i class="fa-solid fa-times text-xs"></i>
                </div>
            </div>
        </div>

        {{-- MENU NAVIGATION --}}
        <nav id="sidebar-nav"
             :class="(isExpanded || isHovered || isMobile) ? 'overflow-y-auto' : 'overflow-hidden'"
             class="flex-1 px-3 pb-4 space-y-0.5 overflow-x-hidden sidebar-scrollbar transition-all duration-200">

            {{-- ================= UTAMA ================= --}}

            <a href="{{ route('admin.dashboard') }}" wire:navigate
               x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                <i class="fa-solid fa-house-chimney fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Dashboard</span>
            </a>

            <a href="{{ url('admin/email') }}" wire:navigate
               x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.index*') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                <i class="fa-solid fa-inbox fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->routeIs('admin.index*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Email Sancaka</span>
            </a>

            <a href="https://tokosancaka.com/admin/chat"
               x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->is('admin/chat*') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                <i class="fa-solid fa-comment-dots fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->is('admin/chat*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Chat Customer</span>
            </a>

            {{-- MENU BARU: FORMULIR PERIZINAN --}}
            <a href="{{ route('admin.perizinan.index') }}" wire:navigate
               x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.perizinan.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                <i class="fa-solid fa-file-contract fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->routeIs('admin.perizinan.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Formulir Perizinan</span>
            </a>

            {{-- MENU BARU: SEMINAR & ABSENSI --}}
            <div x-data="{ open: {{ request()->routeIs('admin.seminar.*') ? 'true' : 'false' }} }"
                 x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
                 x-effect="if(searchQuery && $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())) open = true">

                <button @click="open = !open; if(!isExpanded && !isHovered && !isMobile) isExpanded = true"
                        class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-left rounded-lg transition-colors duration-200 group {{ request()->routeIs('admin.seminar.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                    <span class="flex items-center">
                        <i class="fa-solid fa-users-viewfinder fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->routeIs('admin.seminar.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                        <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Event Seminar</span>
                    </span>
                    <div :class="(isExpanded || isHovered) ? 'flex' : 'flex lg:hidden'" class="items-center ml-auto">
                        <span id="menu-seminar-badge" class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold text-white bg-red-500 rounded-md mr-2 hidden">0</span>
                        <i class="fa-solid fa-chevron-down w-3 h-3 transform transition-transform duration-200"
                           :class="open && (isExpanded || isHovered || isMobile) ? 'rotate-180 text-white' : '{{ request()->routeIs('admin.seminar.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}'"></i>
                    </div>
                </button>

                <div x-show="open && (isExpanded || isHovered || isMobile)" x-cloak class="mt-1">
                    <ul class="pl-9 pr-2 py-1 space-y-1">
                        <li>
                            <a href="{{ route('admin.seminar.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.seminar.index') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">
                                Data Peserta
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.seminar.scan') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.seminar.scan') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">
                                Scanner Absensi
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <a href="{{ route('admin.pelanggan.index') }}" wire:navigate
               x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.pelanggan.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                <i class="fa-solid fa-users fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->routeIs('admin.pelanggan.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Data Pelanggan</span>
            </a>

            <a href="{{ route('admin.spx_scans.monitor.index') }}" wire:navigate
               x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.spx_scans.monitor.index') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                <i class="fa-solid fa-truck fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->routeIs('admin.spx_scans.monitor.index') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Monitor Surat Jalan</span>
            </a>

            {{-- ================= ADMIN & USER ================= --}}

            {{-- Pengguna & Role --}}
            <div x-data="{ open: {{ request()->routeIs(['admin.registrations.*', 'admin.customers.*', 'admin.roles.*']) ? 'true' : 'false' }} }"
                 x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
                 x-effect="if(searchQuery && $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())) open = true">

                <button @click="open = !open; if(!isExpanded && !isHovered && !isMobile) isExpanded = true"
                        class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-left rounded-lg transition-colors duration-200 group {{ request()->routeIs(['admin.registrations.*', 'admin.customers.*', 'admin.roles.*']) ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                    <span class="flex items-center">
                        <i class="fa-solid fa-users-gear fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->routeIs(['admin.registrations.*', 'admin.customers.*', 'admin.roles.*']) ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                        <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Pengguna & Role</span>
                    </span>
                    <div :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                        <span id="menu-pengguna-badge" class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold text-white bg-green-500 rounded-md mr-2 hidden">0</span>
                        <i class="fa-solid fa-chevron-down w-3 h-3 transform transition-transform duration-200"
                           :class="open && (isExpanded || isHovered || isMobile) ? 'rotate-180 text-white' : '{{ request()->routeIs(['admin.registrations.*', 'admin.customers.*', 'admin.roles.*']) ? 'text-white' : 'text-gray-400 group-hover:text-white' }}'"></i>
                    </div>
                </button>

                <div x-show="open && (isExpanded || isHovered || isMobile)" x-cloak class="mt-1">
                    <ul class="pl-9 pr-2 py-1 space-y-1">
                        <li><a href="{{ route('admin.registrations.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="flex justify-between items-center px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.registrations.*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}"><span>Persetujuan</span><span id="persetujuan-badge" class="inline-flex items-center justify-center px-1.5 text-[10px] font-bold text-white bg-green-500 rounded-md hidden">0</span></a></li>
                        <li><a href="{{ route('admin.customers.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.customers.*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Manajemen Pelanggan</a></li>
                        <li><a href="{{ route('admin.customers.data.pengguna.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.customers.data.pengguna.index') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Data Pengguna</a></li>
                        <li><a href="{{ route('admin.roles.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.roles.*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Hak Akses Role</a></li>
                    </ul>
                </div>
            </div>

            {{-- Wilayah --}}
            <a href="{{ route('admin.wilayah.index') }}" wire:navigate
               x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.wilayah.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                <i class="fa-solid fa-map-marked-alt fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->routeIs('admin.wilayah.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Wilayah</span>
            </a>

            {{-- Pencarian Kode Pos --}}
            <a href="{{ route('admin.kodepos.index') }}" wire:navigate
               x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.kodepos.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                <i class="fa-solid fa-magnifying-glass-location fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->routeIs('admin.kodepos.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Pencarian Kode Pos</span>
            </a>

            {{-- ================= MARKETPLACE ================= --}}

            {{-- Marketplace Dropdown --}}
            <div x-data="{ open: {{ request()->is('admin/products*') || request()->is('admin/spx-scans*') || request()->routeIs('admin.reviews.*') || request()->routeIs('admin.stores.*') || request()->routeIs('admin.orders.*') ? 'true' : 'false' }} }"
                 x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
                 x-effect="if(searchQuery && $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())) open = true">

                <button @click="open = !open; if(!isExpanded && !isHovered && !isMobile) isExpanded = true"
                        class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-left rounded-lg transition-colors duration-200 group {{ request()->is('admin/products*') || request()->is('admin/spx-scans*') || request()->routeIs('admin.reviews.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                    <span class="flex items-center">
                        <i class="fa-solid fa-store fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->is('admin/products*') || request()->is('admin/spx-scans*') || request()->routeIs('admin.reviews.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                        <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Marketplace</span>
                    </span>
                    <div :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                        <span id="menu-marketplace-badge" class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold text-white bg-green-500 rounded-md mr-2 hidden">0</span>
                        <i class="fa-solid fa-chevron-down w-3 h-3 transform transition-transform duration-200"
                           :class="open && (isExpanded || isHovered || isMobile) ? 'rotate-180 text-white' : '{{ request()->is('admin/products*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}'"></i>
                    </div>
                </button>

                <div x-show="open && (isExpanded || isHovered || isMobile)" x-cloak class="mt-1">
                    <ul class="pl-9 pr-2 py-1 space-y-1">
                        <li><a href="{{ route('admin.reviews.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.reviews.*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Manajemen Ulasan</a></li>
                        <li><a href="{{ route('admin.categories.index', ['type' => 'marketplace']) }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.categories.*') && request('type') == 'marketplace' ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Kategori Produk</a></li>
                        <li><a href="{{ route('admin.stores.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.stores.index') || request()->routeIs('admin.stores.edit') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Kelola Toko</a></li>
                        <li><a href="{{ route('admin.stores.create') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.stores.create') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Daftar Toko (Admin)</a></li>
                        <li><a href="{{ route('admin.customer-to-seller.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.customer-to-seller.*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Create Penjual</a></li>
                        <li><a href="{{ route('admin.orders.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.orders.*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Data Pesanan Masuk</a></li>
                        <li><a href="{{ route('admin.products.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.products.index') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Daftar Produk</a></li>
                        <li><a href="{{ route('admin.products.create') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.products.create') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Tambah Produk</a></li>
                        <li><a href="{{ route('admin.spx_scans.create') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('scan.spx.show') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Scan SPX</a></li>
                        <li>
                            <a href="{{ route('admin.spx_scans.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="flex justify-between items-center px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.spx-scans.index') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">
                                <span>Data Scan SPX</span>
                                <span id="spx-badge" class="inline-flex items-center justify-center px-1.5 text-[10px] font-bold text-white bg-green-500 rounded-md hidden">0</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Pesanan Dropdown --}}
            <div x-data="{ open: {{ request()->is('admin/pesanan*') ? 'true' : 'false' }} }"
                 x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
                 x-effect="if(searchQuery && $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())) open = true">

                <button @click="open = !open; if(!isExpanded && !isHovered && !isMobile) isExpanded = true"
                        class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-left rounded-lg transition-colors duration-200 group {{ request()->is('admin/pesanan*') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                    <span class="flex items-center">
                        <i class="fa-solid fa-cart-shopping fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->is('admin/pesanan*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                        <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Pesanan</span>
                    </span>
                    <div :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                        <span id="menu-pesanan-badge" class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold text-white bg-green-500 rounded-md mr-2 hidden">0</span>
                        <i class="fa-solid fa-chevron-down w-3 h-3 transform transition-transform duration-200"
                           :class="open && (isExpanded || isHovered || isMobile) ? 'rotate-180 text-white' : '{{ request()->is('admin/pesanan*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}'"></i>
                    </div>
                </button>
                <div x-show="open && (isExpanded || isHovered || isMobile)" x-cloak class="mt-1">
                    <ul class="pl-9 pr-2 py-1 space-y-1">
                        <li><a href="{{ route('admin.pesanan.create') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.pesanan.create') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Tambah Pesanan</a></li>
                        <li><a href="{{ route('admin.pesanan.create_multi') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.pesanan.create_multi') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Kirim Massal <span class="ml-2 bg-red-600 text-[10px] text-white px-1.5 py-0.5 rounded">BARU</span></a></li>
                        <li>
                            <a href="{{ route('admin.pesanan.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="flex justify-between items-center px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.pesanan.index') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">
                                <span>Data Pesanan</span>
                                <span id="pesanan-badge" class="inline-flex items-center justify-center px-1.5 text-[10px] font-bold text-white bg-green-500 rounded-md hidden">0</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.pesanan.riwayat.scan') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="flex justify-between items-center px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.pesanan.riwayat.scan') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">
                                <span>Riwayat Scan</span>
                                 <span id="riwayat-scan-badge" class="inline-flex items-center justify-center px-2 text-xs font-bold text-white bg-green-500 rounded-md hidden">0</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Produk Katalog --}}
            <a href="{{ route('admin.marketplace.index') }}" wire:navigate
               x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.marketplace.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                <i class="fa-solid fa-store fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->routeIs('admin.marketplace.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Produk Katalog</span>
            </a>

            {{-- ================= LOGISTIK & PPOB ================= --}}

            {{-- Master Ekspedisi --}}
            <a href="{{ route('admin.ekspedisi.index') }}" wire:navigate
               x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.ekspedisi.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                <i class="fas fa-truck-moving fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->routeIs('admin.ekspedisi.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Master Ekspedisi</span>
            </a>

            {{-- Manajemen Kurir --}}
            <div x-data="{ open: {{ request()->routeIs('admin.couriers.*') ? 'true' : 'false' }} }"
                 x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
                 x-effect="if(searchQuery && $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())) open = true">

                <button @click="open = !open; if(!isExpanded && !isHovered && !isMobile) isExpanded = true"
                        class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-left rounded-lg transition-colors duration-200 group {{ request()->routeIs('admin.couriers.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                    <span class="flex items-center">

                            <i class="fa-solid fa-truck-fast fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->routeIs('admin.couriers.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                            <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Manajemen Kurir</span>

                    </span>
                    <div :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                        <i class="fa-solid fa-chevron-down w-3 h-3 transform transition-transform duration-200"
                       :class="open && (isExpanded || isHovered || isMobile) ? 'rotate-180 text-white' : '{{ request()->routeIs('admin.couriers.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}'"></i>

                    </div>
                </button>
                <div x-show="open && (isExpanded || isHovered || isMobile)" x-cloak class="mt-1">
                    <ul class="pl-9 pr-2 py-1 space-y-1">
                        <li><a href="{{ route('admin.couriers.create') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.couriers.create') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Tambah Kurir</a></li>
                        <li><a href="{{ route('admin.couriers.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.couriers.index') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Daftar Kurir</a></li>
                    </ul>
                </div>
            </div>

            {{-- PPOB --}}
            <div x-data="{ open: {{ request()->routeIs('admin.ppob.*') ? 'true' : 'false' }} }"
                 x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
                 x-effect="if(searchQuery && $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())) open = true">

                <button @click="open = !open; if(!isExpanded && !isHovered && !isMobile) isExpanded = true"
                        class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-left rounded-lg transition-colors duration-200 group {{ request()->routeIs('admin.ppob.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                    <span class="flex items-center">
                        <div :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                            <i class="fa-solid fa-mobile-screen-button fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->routeIs('admin.ppob.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                            <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">PPOB</span>
                        </div>
                    </span>
                    <div :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                        <span id="menu-ppob-badge" class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold text-white bg-green-500 rounded-md mr-2 hidden">0</span>
                    <i class="fa-solid fa-chevron-down w-3 h-3 transform transition-transform duration-200"
                       :class="open && (isExpanded || isHovered || isMobile) ? 'rotate-180 text-white' : '{{ request()->routeIs('admin.ppob.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}'"></i>
                    </div>
                    </button>
                <div x-show="open && (isExpanded || isHovered || isMobile)" x-cloak class="mt-1">
                    <ul class="pl-9 pr-2 py-1 space-y-1">
                        <li><a href="{{ route('admin.ppob.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.ppob.index') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Produk PPOB</a></li>
                        <li><a href="{{ route('admin.ppob.data.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.ppob.data.index') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Data Transaksi</a></li>
                    </ul>
                </div>
            </div>

            {{-- ================= KEUANGAN ================= --}}

            {{-- Laporan Keuangan --}}
            <div x-data="{ open: {{ request()->routeIs('admin.saldo.requests.*') || request()->routeIs('admin.keuangan.*') || request()->routeIs('admin.akuntansi.*') || request()->routeIs('admin.laporan.*') || request()->is('admin/wallet*') || request()->routeIs('admin.coa.*') ? 'true' : 'false' }} }"
                 x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
                 x-effect="if(searchQuery && $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())) open = true">

                <button @click="open = !open; if(!isExpanded && !isHovered && !isMobile) isExpanded = true"
                        class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-left rounded-lg transition-colors duration-200 group {{ request()->routeIs('admin.saldo.requests.*') || request()->routeIs('admin.keuangan.*') || request()->routeIs('admin.akuntansi.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                    <span class="flex items-center">
                        <i class="fa-solid fa-chart-pie fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->routeIs('admin.saldo.requests.*') || request()->routeIs('admin.keuangan.*') || request()->routeIs('admin.akuntansi.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                        <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Laporan Keuangan</span>
                    </span>
                    <div :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                        <span id="menu-keuangan-badge" class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold text-white bg-orange-500 rounded-md mr-2 hidden">0</span>
                        <i class="fa-solid fa-chevron-down w-3 h-3 transform transition-transform duration-200"
                           :class="open && (isExpanded || isHovered || isMobile) ? 'rotate-180 text-white' : '{{ request()->routeIs('admin.saldo.requests.*') || request()->routeIs('admin.keuangan.*') || request()->routeIs('admin.akuntansi.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}'"></i>
                    </div>
                </button>
                <div x-show="open && (isExpanded || isHovered || isMobile)" x-cloak class="mt-1">
                    <ul class="pl-9 pr-2 py-1 space-y-1">

                        {{-- Jurnal & Akuntansi --}}
                        <li>
                            <a href="{{ route('admin.akuntansi.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.akuntansi.*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">
                                <i class="fas fa-book-journal-whills mr-2 text-blue-400"></i> Jurnal & Akuntansi
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('admin.saldo.requests.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="flex justify-between items-center px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.saldo.requests.*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">
                                <span><i class="fas fa-hand-holding-dollar mr-2 text-yellow-500"></i> Permintaan Saldo</span>
                                <span id="saldo-requests-badge" class="inline-flex items-center justify-center px-1.5 text-[10px] font-bold text-white bg-orange-500 rounded-md hidden">0</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.keuangan.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.keuangan.index') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">
                                <i class="fas fa-money-bill-transfer mr-2 text-indigo-400"></i> Transaksi Keuangan
                            </a>
                        </li>

                        {{-- Laba Rugi (Tahunan) --}}
                        <li>
                            <a href="{{ route('admin.keuangan.laba_rugi') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.laporan.laba_rugi') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">
                                <i class="fas fa-file-invoice-dollar mr-2 text-green-400"></i> Laba Rugi (Tahunan)
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('admin.saldo.requests.history') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.saldo.requests.history') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">
                                <i class="fas fa-clock-rotate-left mr-2 text-purple-400"></i> Riwayat Top Up
                            </a>
                        </li>
                        <li>
                            <a href="{{ url('admin/wallet') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->is('admin/wallet*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">
                                <i class="fas fa-wallet mr-2 text-orange-400"></i> Dompet Pelanggan
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.pemasukan') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.laporan.pemasukan*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">
                                <i class="fas fa-arrow-trend-up mr-2 text-emerald-500"></i> Pemasukan
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.pengeluaran') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.laporan.pengeluaran*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">
                                <i class="fas fa-arrow-trend-down mr-2 text-rose-500"></i> Pengeluaran
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.labaRugi') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.laporan.labaRugi*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">
                                <i class="fas fa-chart-column mr-2 text-teal-400"></i> Laba Rugi
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.coa.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.coa.*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">
                                <i class="fas fa-list-check mr-2 text-gray-400"></i> Manajemen Akun (COA)
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.neracaSaldo') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.laporan.neracaSaldo') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">
                                <i class="fas fa-scale-unbalanced mr-2 text-cyan-400"></i> Neraca Saldo
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.neraca') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.laporan.neraca') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">
                                <i class="fas fa-scale-balanced mr-2 text-blue-500"></i> Neraca
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- ================= KOMUNIKASI & BLOG ================= --}}

            {{-- Buku Alamat --}}
            <a href="{{ route('admin.kontak.index') }}" wire:navigate
               x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.kontak.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                <i class="fa-solid fa-address-book fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->routeIs('admin.kontak.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Buku Alamat</span>
            </a>

            {{-- Chat Whatsapp --}}
            <div x-data="{ open: {{ request()->routeIs('broadcast.*') || request()->is('whatsapp*') || request()->routeIs('admin.wa.scan') || request()->routeIs('admin.fonnte.scan') ? 'true' : 'false' }} }"
                 x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
                 x-effect="if(searchQuery && $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())) open = true">

                <button @click="open = !open; if(!isExpanded && !isHovered && !isMobile) isExpanded = true"
                        class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-left rounded-lg transition-colors duration-200 group {{ request()->routeIs('broadcast.*') || request()->is('whatsapp*') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                    <span class="flex items-center">

                            <i class="fa-brands fa-whatsapp fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->routeIs('broadcast.*') || request()->is('whatsapp*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                            <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Chat Whatsapp</span>

                    </span>
                    <div :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                        <i class="fa-solid fa-chevron-down w-3 h-3 transform transition-transform duration-200"
                       :class="open && (isExpanded || isHovered || isMobile) ? 'rotate-180 text-white' : '{{ request()->routeIs('broadcast.*') || request()->is('whatsapp*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}'"></i>
                    </div>
                    </button>
                <div x-show="open && (isExpanded || isHovered || isMobile)" x-cloak class="mt-1">
                    <ul class="pl-9 pr-2 py-1 space-y-1">
                        <li><a href="{{ route('admin.wa.scan') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.wa.scan') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Scan PushWA</a></li>
                        <li><a href="{{ route('admin.fonnte.scan') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.fonnte.scan') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Scan Fonnte</a></li>
                        <li><a href="{{ route('broadcast.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('broadcast.*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Kirim Pesan (Broadcast)</a></li>
                        <li><a href="{{ url('whatsapp') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->is('whatsapp*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Inbox (Whatsapp)</a></li>
                    </ul>
                </div>
            </div>

            {{-- Blog --}}
            <div x-data="{ open: {{ request()->is('admin/posts*') || request()->is('admin/categories*') || request()->is('admin/tags*') ? 'true' : 'false' }} }"
                 x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
                 x-effect="if(searchQuery && $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())) open = true">

                <button @click="open = !open; if(!isExpanded && !isHovered && !isMobile) isExpanded = true"
                        class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-left rounded-lg transition-colors duration-200 group {{ request()->is('admin/posts*') || request()->is('admin/categories*') || request()->is('admin/tags*') ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:bg-blue-600 hover:text-white' }}">
                    <span class="flex items-center">

                            <i class="fa-solid fa-newspaper fa-fw w-5 h-5 mr-2 flex-shrink-0 {{ request()->is('admin/posts*') || request()->is('admin/categories*') || request()->is('admin/tags*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                            <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Blog</span>

                    </span>
                    <div :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                    <i class="fa-solid fa-chevron-down w-3 h-3 transform transition-transform duration-200"
                       :class="open && (isExpanded || isHovered || isMobile) ? 'rotate-180 text-white' : '{{ request()->is('admin/posts*') || request()->is('admin/categories*') || request()->is('admin/tags*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}'"></i>

                    </div>
                </button>
                <div x-show="open && (isExpanded || isHovered || isMobile)" x-cloak class="mt-1">
                    <ul class="pl-9 pr-2 py-1 space-y-1">
                        <li><a href="{{ route('admin.posts.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs(['admin.posts.index', 'admin.posts.edit']) ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Semua Postingan</a></li>
                        <li><a href="{{ route('admin.posts.create') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.posts.create') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Tambah Baru</a></li>
                        <li><a href="{{ route('admin.categories.index', ['type' => 'blog']) }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.categories.*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Kategori</a></li>
                        <li><a href="{{ route('admin.tags.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.tags.*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Tag</a></li>
                    </ul>
                </div>
            </div>

            {{-- ================= PENGATURAN ================= --}}

            {{-- Pengaturan (Utilitas) --}}
            <div x-data="{ open: {{ request()->routeIs('admin.logs.show') || request()->routeIs('admin.activity-log.index') || request()->routeIs('admin.settings.*') || request()->routeIs('admin.category-attributes.*') || request()->routeIs('admin.sliders.*') || request()->routeIs('info.edit') ? 'true' : 'false' }} }"
                 x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())"
                 x-effect="if(searchQuery && $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())) open = true">

                <button @click="open = !open; if(!isExpanded && !isHovered && !isMobile) isExpanded = true"
                        class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-left rounded-lg transition-colors duration-200 group text-gray-600 hover:bg-blue-600 hover:text-white">
                    <span class="flex items-center">
                            <i class="fa-solid fa-gears fa-fw w-5 h-5 mr-2 flex-shrink-0 text-gray-400 group-hover:text-white"></i>
                            <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Pengaturan</span>

                    </span>
                        <div :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'">
                            <i class="fa-solid fa-chevron-down w-3 h-3 transform transition-transform duration-200 text-gray-400 group-hover:text-white"
                            :class="open && (isExpanded || isHovered || isMobile) ? 'rotate-180' : ''"></i>
                       </div>
                </button>
                <div x-show="open && (isExpanded || isHovered || isMobile)" x-cloak class="mt-1">
                    <ul class="pl-9 pr-2 py-1 space-y-1">
                        <li><a href="{{ route('admin.logs.show') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.logs.show') ? 'text-red-500 font-bold bg-red-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Log Error</a></li>
                        <li><a href="{{ route('admin.activity-log.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.activity-log.index') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Log Aktivitas</a></li>
                        <li><a href="{{ route('admin.settings.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.settings.index') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Pengaturan Aplikasi</a></li>
                        <li><a href="{{ route('admin.settings.api.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.settings.api.index') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Konfigurasi API</a></li>
                        <li><a href="{{ route('admin.settings.banners.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.settings.banners.index') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Pengaturan Marketplace</a></li>
                        <li><a href="{{ route('admin.category-attributes.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.category-attributes.*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Atribut Kategori</a></li>
                        <li><a href="{{ route('admin.sliders.index') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('admin.sliders.*') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Manajemen Slider Informasi</a></li>
                        <li><a href="{{ route('admin.info.edit') }}" wire:navigate x-show="!searchQuery || $el.textContent.toLowerCase().includes(searchQuery.toLowerCase())" class="block px-3 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('info.edit') ? 'text-blue-600 font-bold bg-blue-50' : 'text-gray-500 hover:text-blue-600 hover:bg-gray-50' }}">Info Halaman Pesanan</a></li>
                    </ul>
                </div>
            </div>

        </nav>

        {{-- LOGOUT BUTTON --}}
        <div class="mt-auto p-3 border-t border-gray-100 transition-all duration-300 bg-gray-50">
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="flex items-center w-full px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-600 hover:text-white transition-colors duration-200 group">
                <i class="fa-solid fa-arrow-right-from-bracket fa-fw w-5 h-5 mr-2 flex-shrink-0 text-gray-400 group-hover:text-white"></i>
                <span :class="(isExpanded || isHovered) ? 'block' : 'block lg:hidden'" class="whitespace-nowrap">Keluar</span>
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
