{{--
    File: resources/views/layouts/partials/customer/sidebar.blade.php
    Deskripsi: Sidebar navigasi interaktif dengan fitur Mini Sidebar (Icon Only).
--}}

{{-- Inisialisasi Alpine Data untuk Sidebar --}}
<div x-data="{ isExpanded: false }" class="h-full">

    {{-- Overlay untuk Mobile --}}
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-[90] bg-blue-900 bg-opacity-50 transition-opacity lg:hidden" x-cloak></div>

    <aside
        {{-- Logika Lebar: Jika expanded w-64, jika tidak (default desktop) w-20. Mobile tetap w-64 saat dibuka. --}}
        :class="[
            sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in',
            isExpanded ? 'w-64' : 'w-64 lg:w-20'
        ]"
        class="fixed inset-y-0 left-0 z-[100] overflow-y-auto bg-blue-900 text-white transition-all duration-300 transform lg:translate-x-0 lg:static lg:inset-0 shadow-xl scrollbar-hide"
        x-cloak>

        {{-- Header Sidebar (Logo & Toggle) --}}
        <div class="flex flex-col items-center justify-center py-6 border-b border-blue-800/50 sticky top-0 bg-blue-900 z-10 transition-all duration-300">
            
            {{-- Tombol Toggle (Hamburger) --}}
            <button @click="isExpanded = !isExpanded" 
                    class="absolute top-2 right-2 p-1 rounded-md hover:bg-blue-800 text-blue-200 focus:outline-none hidden lg:block"
                    title="Toggle Sidebar">
                <i :class="isExpanded ? 'fas fa-chevron-left' : 'fas fa-bars'" class="fa-fw"></i>
            </button>

            <a href="{{ route('customer.profile.show') }}" class="flex flex-col items-center text-center group">
                @php
                    $user = Auth::user();
                    $logoPath = $user?->store_logo_path;
                    $storeName = $user?->store_name ?? 'Pelanggan';
                    $logoSrc = $logoPath ? asset('public/storage/' . $logoPath) : 'https://ui-avatars.com/api/?name=' . urlencode($storeName) . '&background=4f46e5&color=fff';
                @endphp

                {{-- Logo: Ukuran berubah saat collapse --}}
                <img :class="isExpanded ? 'w-16 h-16' : 'w-10 h-10 lg:mt-6'" 
                     class="object-cover rounded-full border-4 border-gray-700 shadow-md transition-all duration-300" 
                     src="{{ $logoSrc }}" 
                     alt="Logo Toko" />
                
                {{-- Nama Toko: Hilang saat collapse --}}
                <div x-show="isExpanded" x-transition class="mt-3">
                    <span class="truncate w-40 block text-lg font-bold">{{ $storeName }}</span>
                </div>
            </a>
        </div>

        <nav class="mt-4 space-y-2 pb-20 px-2">

            {{-- 1. DASHBOARD MONITOR --}}
            <a href="{{ route('customer.dashboard') }}" 
               :class="isExpanded ? 'justify-start px-4' : 'lg:justify-center lg:px-0 justify-start px-4'"
               class="flex items-center py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.dashboard') ? 'bg-gray-900 text-white' : '' }}">
                <i class="fas fa-tachometer-alt fa-fw text-xl"></i>
                <span x-show="isExpanded" class="ml-3 whitespace-nowrap transition-all duration-200">Dashboard Monitor</span>
            </a>

            @php
                $user = auth()->user();
                $hideMenus = $user->setup_token !== null && $user->profile_setup_at === null && $user->status === 'Tidak Aktif';
            @endphp
            
            @if(!$hideMenus)

                {{-- MENU AGEN --}}
                @if($user->role === 'agent' || $user->role === 'admin')
                    <div x-show="isExpanded" class="pt-4 pb-2 transition-opacity duration-200">
                        <p class="px-2 text-xs text-yellow-400 uppercase tracking-wider font-bold">Menu Agen</p>
                    </div>

                    <a href="{{ route('agent.products.index') }}" 
                       :class="isExpanded ? 'justify-start px-4' : 'lg:justify-center lg:px-0 justify-start px-4'"
                       class="flex items-center py-2.5 mb-1 bg-gradient-to-r from-blue-700 to-blue-800 text-white rounded-md shadow-md border border-blue-600 hover:from-blue-600 hover:to-blue-700 transition">
                        <i class="fas fa-store fa-fw text-xl text-yellow-400"></i>
                        <div x-show="isExpanded" class="ml-3">
                            <span class="block font-bold text-sm">Kelola Agen</span>
                            <span class="block text-[10px] text-blue-200 font-normal">Atur Harga</span>
                        </div>
                    </a>
                @else
                    {{-- Upgrade Button (Hanya muncul full jika expanded, atau icon roket jika collapsed) --}}
                    <div class="py-4">
                        <a href="{{ route('agent.register.index') }}" 
                           :class="isExpanded ? 'px-4' : 'justify-center px-0'"
                           class="flex items-center py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 group border border-purple-400/30">
                            <div class="p-1.5 bg-white/20 rounded-full group-hover:bg-white/30 transition">
                                <i class="fas fa-rocket fa-fw"></i>
                            </div>
                            <div x-show="isExpanded" class="ml-3">
                                <span class="block text-[10px] uppercase font-bold text-indigo-100 tracking-wider">Upgrade</span>
                                <span class="block font-bold text-white leading-tight">Jadi Agen</span>
                            </div>
                        </a>
                    </div>
                @endif

                {{-- BAGIAN 3: PRODUK DIGITAL --}}
                <div x-show="isExpanded" class="pt-4 pb-2 px-2 transition-opacity">
                    <p class="text-xs text-gray-400 uppercase tracking-wider">Produk Digital</p>
                </div>
                {{-- Garis pemisah saat collapsed --}}
                <hr x-show="!isExpanded" class="border-gray-700 my-4 lg:block hidden">

                <div x-data="{ open: {{ request()->routeIs('customer.ppob.*') ? 'true' : 'false' }} }" class="space-y-1">
                    <button @click="if(!isExpanded) { isExpanded = true; open = true; } else { open = !open; }" 
                            :class="isExpanded ? 'justify-start px-4' : 'lg:justify-center lg:px-0 justify-start px-4'"
                            class="flex items-center w-full py-2.5 text-white hover:bg-red-700 bg-red-600 hover:text-white rounded-md transition-colors duration-200 shadow-sm">
                        <i class="fas fa-mobile-alt fa-fw text-xl"></i>
                        <span x-show="isExpanded" class="ml-3 flex-1 text-left whitespace-nowrap">LOKET PPOB</span>
                        <i x-show="isExpanded" :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="ml-auto text-xs"></i>
                    </button>

                    <div x-show="open && isExpanded" x-cloak class="ml-4 pl-4 border-l border-gray-700 space-y-1 mt-1">
                        <a href="{{ route('public.pricelist') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md">
                            <i class="fas fa-sim-card fa-fw w-4 mr-2"></i> Isi Pulsa
                        </a>
                        <a href="https://tokosancaka.com/etalase/ppob/digital/pln-pascabayar" target="_blank" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md">
                            <i class="fas fa-file-invoice-dollar fa-fw w-4 mr-2"></i> Bayar Tagihan
                        </a>
                        <a href="{{ route('customer.ppob.history') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded-md {{ request()->routeIs('customer.ppob.history') ? 'bg-gray-800 text-white' : '' }}">
                            <i class="fas fa-history fa-fw w-4 mr-2"></i> Riwayat
                        </a>
                    </div>
                </div>

                {{-- BAGIAN 4: MANAJEMEN PENGIRIMAN --}}
                <div x-show="isExpanded" class="pt-4 pb-2 px-2 transition-opacity">
                    <p class="text-xs text-gray-400 uppercase tracking-wider">Pengiriman</p>
                </div>
                <hr x-show="!isExpanded" class="border-gray-700 my-4 lg:block hidden">

                {{-- Item Menu Pengiriman (Looping style for consistency) --}}
                @foreach([
                    ['route' => 'customer.pesanan.create', 'icon' => 'fas fa-plus-circle', 'label' => 'Kirim Satuan'],
                    ['route' => 'customer.koli.create', 'icon' => 'fas fa-boxes', 'label' => 'Kirim Massal', 'hot' => true],
                    ['route' => 'customer.pesanan.index', 'icon' => 'fas fa-table', 'label' => 'Data Pengiriman'],
                    ['route' => 'customer.kontak.index', 'icon' => 'fas fa-address-book', 'label' => 'Data Kontak'],
                    ['route' => 'customer.lacak.index', 'icon' => 'fas fa-search-location', 'label' => 'Lacak Paket'],
                    ['route' => 'customer.ongkir.index', 'icon' => 'fas fa-truck', 'label' => 'Cek Ongkir'],
                ] as $menu)
                    <a href="{{ route($menu['route']) }}" 
                       :class="isExpanded ? 'justify-start px-4' : 'lg:justify-center lg:px-0 justify-start px-4'"
                       class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs($menu['route']) ? 'bg-gray-900 text-white' : '' }}">
                        <i class="{{ $menu['icon'] }} fa-fw text-xl"></i>
                        <span x-show="isExpanded" class="ml-3 flex items-center w-full whitespace-nowrap">
                            {{ $menu['label'] }}
                            @if(isset($menu['hot'])) <span class="ml-auto bg-red-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded">HOT</span> @endif
                        </span>
                    </a>
                @endforeach

                {{-- BAGIAN 5: OPERASIONAL --}}
                <div x-show="isExpanded" class="pt-4 pb-2 px-2 transition-opacity">
                    <p class="text-xs text-gray-400 uppercase tracking-wider">Operasional</p>
                </div>
                <hr x-show="!isExpanded" class="border-gray-700 my-4 lg:block hidden">

                <a href="{{ route('customer.scan.spx') }}" 
                   :class="isExpanded ? 'justify-start px-4' : 'lg:justify-center lg:px-0 justify-start px-4'"
                   class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.scan.spx') ? 'bg-gray-900 text-white' : '' }}">
                    <i class="fas fa-qrcode fa-fw text-xl"></i>
                    <span x-show="isExpanded" class="ml-3 whitespace-nowrap">Scan SPX</span>
                </a>
                
                {{-- BAGIAN 6: KEUANGAN --}}
                <div x-show="isExpanded" class="pt-4 pb-2 px-2 transition-opacity">
                    <p class="text-xs text-gray-400 uppercase tracking-wider">Keuangan</p>
                </div>
                <hr x-show="!isExpanded" class="border-gray-700 my-4 lg:block hidden">

                <a href="{{ route('customer.topup.index') }}" 
                   :class="isExpanded ? 'justify-start px-4' : 'lg:justify-center lg:px-0 justify-start px-4'"
                   class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.topup.*') ? 'bg-gray-900 text-white' : '' }}">
                    <i class="fas fa-wallet fa-fw text-xl"></i>
                    <span x-show="isExpanded" class="ml-3 whitespace-nowrap">Top Up</span>
                </a>

                <a href="{{ route('customer.laporan.index') }}" 
                   :class="isExpanded ? 'justify-start px-4' : 'lg:justify-center lg:px-0 justify-start px-4'"
                   class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.laporan.index') ? 'bg-gray-900 text-white' : '' }}">
                    <i class="fas fa-chart-area fa-fw text-xl"></i>
                    <span x-show="isExpanded" class="ml-3 whitespace-nowrap">Laporan</span>
                </a>

                {{-- PENGATURAN USER --}}
                <div class="mt-auto pt-10">
                    <a href="{{ route('customer.profile.edit') }}" 
                       :class="isExpanded ? 'justify-start px-4' : 'lg:justify-center lg:px-0 justify-start px-4'"
                       class="flex items-center py-2.5 mt-4 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('customer.profile.edit') ? 'bg-gray-900 text-white' : '' }}">
                        <i class="fas fa-cog fa-fw text-xl"></i>
                        <span x-show="isExpanded" class="ml-3 whitespace-nowrap">Pengaturan</span>
                    </a>
                </div>

            @endif

            {{-- LOGOUT --}}
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form-customer').submit();"
               :class="isExpanded ? 'justify-start px-4' : 'lg:justify-center lg:px-0 justify-start px-4'"
               class="flex items-center py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200">
                <i class="fas fa-sign-out-alt fa-fw text-xl text-red-400"></i>
                <span x-show="isExpanded" class="ml-3 whitespace-nowrap">Logout</span>
            </a>
            <form id="logout-form-customer" action="{{ route('logout') }}" method="POST" class="hidden">
                @csrf
            </form>

        </nav>
    </aside>

    {{-- Tombol Close Floating (Hanya untuk Mobile) --}}
    <div x-show="sidebarOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-x-10"
         x-transition:enter-end="opacity-100 translate-x-0"
         class="fixed top-4 left-64 z-[110] ml-2 lg:hidden" 
         x-cloak>
        <button @click="sidebarOpen = false" class="flex items-center justify-center w-10 h-10 bg-blue-900 text-white rounded-full shadow-lg border-2 border-white/20 hover:bg-red-600 transition-all focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
    </div>

</div>