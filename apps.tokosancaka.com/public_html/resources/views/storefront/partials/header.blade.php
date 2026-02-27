<header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">

            {{-- 1. BAGIAN LOGO & NAMA TOKO --}}
            <a href="{{ route('storefront.index', $subdomain) }}" class="flex items-center gap-3">
                @php
                    // AMAN DARI ERROR: Cari data admin/pemilik dari toko ini saja
                    $tokoAdmin = \App\Models\User::where('tenant_id', $tenant->id)->first();
                @endphp

                @if($tokoAdmin && !empty($tokoAdmin->logo))
                    <div class="w-10 h-10 flex-shrink-0">
                        <img class="h-full w-full object-cover rounded-[12px] border border-gray-100 shadow-sm"
                             src="{{ asset('storage/' . $tokoAdmin->logo) }}"
                             alt="{{ $tenant->name ?? 'Toko Sancaka' }}">
                    </div>
                @else
                    <div class="w-10 h-10 bg-blue-600 rounded-[12px] flex-shrink-0 flex justify-center items-center text-white font-black text-xl shadow-inner">
                        {{ strtoupper(substr($tenant->name ?? 'S', 0, 1)) }}
                    </div>
                @endif

                <div>
                    <h1 class="font-bold text-gray-900 text-lg leading-tight">{{ $tenant->name ?? 'Toko Anda' }}</h1>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wider font-semibold">Official Store</p>
                </div>
            </a>

            {{-- 2. BAGIAN PENCARIAN (SEARCH) --}}
            <div class="hidden md:flex flex-1 max-w-lg mx-8">
                <form action="{{ route('storefront.index', $subdomain) }}" method="GET" class="w-full relative">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari produk di toko ini..."
                           class="w-full bg-gray-100 border-none rounded-full py-2.5 pl-5 pr-12 focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                    <button type="submit" class="absolute right-3 top-2.5 text-gray-400 hover:text-blue-600">
                        <i data-lucide="search" class="w-5 h-5"></i>
                    </button>
                </form>
            </div>

            {{-- 3. BAGIAN KANAN (MENU, CART, LOGIN/DASHBOARD) --}}
            <div class="flex items-center gap-2 md:gap-4">

                {{-- KODE BARU: MEGA MENU KATEGORI --}}
                <div x-data="{ openCategory: false }" class="relative">
                    <button @click="openCategory = !openCategory" @click.away="openCategory = false"
                            class="flex items-center gap-1.5 p-2 text-gray-600 hover:text-blue-600 transition font-semibold text-sm">
                        <i data-lucide="layout-grid" class="w-5 h-5"></i>
                        <span class="hidden sm:block">Kategori</span>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200" :class="openCategory ? 'rotate-180' : ''"></i>
                    </button>

                    {{-- Dropdown Panel Mega Menu --}}
                    <div x-show="openCategory"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-2"
                         class="absolute right-0 sm:right-auto sm:left-0 mt-3 w-[280px] sm:w-[400px] bg-white rounded-xl shadow-xl border border-gray-100 z-50 p-4"
                         style="display: none;">

                        <div class="flex justify-between items-center mb-3 pb-2 border-b border-gray-50">
                            <h3 class="font-bold text-gray-800 text-sm">Semua Kategori</h3>
                            <a href="{{ route('storefront.index', $subdomain) }}" class="text-[10px] text-blue-600 font-bold hover:underline">Lihat Semua</a>
                        </div>

                        {{-- Grid Kategori --}}
                        <div class="grid grid-cols-2 gap-2 max-h-[60vh] overflow-y-auto pr-1">
                            @php
                                // Ambil Kategori Aktif untuk Tenant ini
                                $navCategories = \App\Models\Category::where('tenant_id', $tenant->id)
                                                    ->where('is_active', true)
                                                    ->orderBy('name', 'asc')
                                                    ->get();
                            @endphp

                            @forelse($navCategories as $cat)
                                <a href="{{ route('storefront.index', ['subdomain' => $subdomain, 'category' => $cat->id]) }}"
                                   class="flex items-center gap-2 p-2 hover:bg-blue-50 rounded-lg transition group">
                                    <div class="w-8 h-8 rounded-md bg-gray-100 flex items-center justify-center text-gray-500 group-hover:bg-blue-100 group-hover:text-blue-600 transition">
                                        <i data-lucide="folder" class="w-4 h-4"></i>
                                    </div>
                                    <span class="text-xs font-medium text-gray-600 group-hover:text-blue-700 truncate">
                                        {{ $cat->name }}
                                    </span>
                                </a>
                            @empty
                                <div class="col-span-2 text-center py-4 text-xs text-gray-400 italic">
                                    Belum ada kategori tersedia.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                {{-- AKHIR MEGA MENU --}}

                {{-- Garis Pemisah (Divider) --}}
                <div class="h-6 w-px bg-gray-200 hidden sm:block"></div>

                {{-- Cart Icon --}}
                <a href="{{ route('storefront.cart', $subdomain) }}" class="relative p-2 text-gray-600 hover:text-blue-600 transition group">
                    <i data-lucide="shopping-bag" class="w-6 h-6 group-hover:scale-110 transition transform"></i>
                    <span x-cloak x-show="cartCount > 0" x-text="cartCount" class="absolute top-0 right-0 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full border-2 border-white transform translate-x-1/4 -translate-y-1/4"></span>
                </a>

                <div class="h-6 w-px bg-gray-200 hidden sm:block"></div>

                {{-- Tombol Login / Dashboard --}}
                @auth
                    <a href="{{ url('/dashboard') }}" class="flex items-center gap-1.5 bg-blue-600 text-white px-3 py-1.5 sm:px-4 sm:py-2 rounded-[12px] text-xs sm:text-sm font-bold hover:bg-blue-700 shadow-sm transition active:scale-95">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 hidden sm:block"></i>
                        <span>Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="flex items-center gap-1.5 bg-white border border-blue-600 text-blue-600 px-3 py-1.5 sm:px-4 sm:py-2 rounded-[12px] text-xs sm:text-sm font-bold hover:bg-blue-50 transition active:scale-95">
                        <i data-lucide="log-in" class="w-4 h-4 hidden sm:block"></i>
                        <span>Login</span>
                    </a>
                @endauth
            </div>

        </div>
    </div>
</header>
