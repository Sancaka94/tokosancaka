<aside
    x-cloak
    :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', isExpanded ? 'w-64' : 'w-20']"
    class="fixed inset-y-0 left-0 z-50 bg-white border-r border-slate-200 transition-all duration-300 transform lg:relative lg:translate-x-0 shadow-sm flex flex-col h-full">
    {{-- TOMBOL TOGGLE BUKA/TUTUP --}}
    <button @click="isExpanded = !isExpanded" type="button"
        class="absolute -right-3 top-7 w-6 h-6 bg-white border border-slate-200 rounded-full flex items-center justify-center text-slate-400 hover:text-blue-600 shadow-sm z-50 transition-transform">
        <i class="fas text-[10px]" :class="isExpanded ? 'fa-chevron-left' : 'fa-chevron-right'"></i>
    </button>

    {{-- ================================================================= --}}
    {{-- [LOGIC PENYELAMAT] AMBIL SUBDOMAIN MANUAL DARI URL BROWSER        --}}
    {{-- ================================================================= --}}
    @php
        // Ambil host (contoh: toko1.tokosancaka.com)
        $host = request()->getHost();
        // Pecah jadi array
        $parts = explode('.', $host);
        // Ambil bagian depan (toko1), jika gagal pakai 'admin'
        $currentSubdomain = $parts[0] ?? 'admin';

        \Illuminate\Support\Facades\URL::defaults(['subdomain' => $currentSubdomain]);

        // Fungsi bantu agar tidak capek ngetik array parameter
        // Kita suntikkan subdomain ke semua route
        $params = ['subdomain' => $currentSubdomain];
    @endphp

    <div class="flex items-center h-20 border-b border-slate-50 flex-shrink-0 transition-all duration-300" :class="isExpanded ? 'px-6' : 'px-0 justify-center'">
        {{-- LOGO: Gunakan wire:navigate --}}
        <a wire:navigate href="{{ route('dashboard', $params) }}" class="flex items-center group" :class="isExpanded ? 'gap-3' : 'justify-center'">
            <div class="w-9 h-9 flex-shrink-0 bg-gradient-to-tr from-blue-600 to-blue-500 rounded-xl flex items-center justify-center shadow-lg shadow-blue-100 group-hover:scale-105 transition-transform">
                <img src="https://tokosancaka.com/storage/uploads/sancaka.png" class="h-6 w-6 object-contain invert" alt="Logo">
            </div>
            <span x-show="isExpanded" style="display: none;" x-transition class="text-xl font-bold tracking-tight text-slate-800 whitespace-nowrap">
                SANCAKA<span class="text-blue-600">POS</span>
            </span>
        </a>
    </div>

    <div class="pb-4 pt-2 text-center transition-all duration-300" :class="isExpanded ? 'px-6' : 'px-2'">
        {{-- VERSION: Gunakan wire:navigate --}}
        <a wire:navigate href="{{ route('admin.changelog') }}"
        class="group block bg-blue-50 hover:bg-blue-100 border border-blue-100 rounded-lg transition-colors cursor-pointer"
        :class="isExpanded ? 'py-2' : 'py-1.5'"
        title="Lihat Riwayat Update">

            {{-- Mode Terbuka --}}
            <p x-show="isExpanded" style="display: none;" class="text-[10px] text-slate-500 font-medium group-hover:text-blue-600 whitespace-nowrap">
                Version <span class="text-slate-700 font-bold group-hover:text-blue-800">{{ $app_version ?? '1.0' }}</span>
            </p>
            <p x-show="isExpanded" style="display: none;" class="text-[9px] text-slate-400 mt-0.5 flex items-center justify-center gap-1 group-hover:text-blue-500 whitespace-nowrap">
                {{-- Pastikan variabel ini sesuai dengan AppServiceProvider ($app_last_update) --}}
                <span>Updated: {{ $app_last_update ?? '-' }}</span>
                <i class="fas fa-history text-[8px] opacity-0 group-hover:opacity-100 transition-opacity"></i>
            </p>

            {{-- Mode Tertutup --}}
            <p x-show="!isExpanded" class="text-[10px] text-blue-600 font-bold">
                v{{ $app_version ?? '1.0' }}
            </p>
        </a>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto custom-scrollbar">

        <a wire:navigate.hover href="{{ route('dashboard', $params) }}"
           class="flex items-center rounded-xl text-sm font-semibold transition-all duration-200
           {{ request()->routeIs('dashboard')
             ? 'bg-blue-600 text-white shadow-lg shadow-blue-200'
             : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}"
           :class="isExpanded ? 'px-3 py-2.5 gap-3 justify-start' : 'p-2.5 justify-center'">
            <i class="fas fa-th-large w-5 text-center flex-shrink-0 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-slate-400' }}"></i>
            <span x-show="isExpanded" style="display: none;" x-transition class="whitespace-nowrap">Dashboard</span>
        </a>

        {{-- STATUS LANGGANAN --}}
        @if(in_array(Auth::user()->role, ['super_admin', 'admin']))
            {{-- Pastikan route ini ada, jika tidak, arahkan ke dashboard --}}
            @if(Route::has('tenant.dashboard'))
            <a wire:navigate.hover href="{{ route('tenant.dashboard', $params) }}"
               class="flex items-center rounded-xl text-sm font-medium transition-all duration-200 mt-1
               {{ request()->routeIs('tenant.dashboard')
                 ? 'bg-emerald-50 text-emerald-600 font-bold border border-emerald-100'
                 : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-600' }}"
               :class="isExpanded ? 'px-3 py-2.5 gap-3 justify-start' : 'p-2.5 justify-center'">
                <i class="fas fa-crown w-5 text-center flex-shrink-0 {{ request()->routeIs('tenant.dashboard') ? 'text-emerald-600' : 'text-slate-400' }}"></i>
                <span x-show="isExpanded" style="display: none;" x-transition class="whitespace-nowrap">Status Langganan</span>
            </a>
            @endif
        @endif

        {{-- AREA TRANSAKSI --}}
        @if(in_array(Auth::user()->role, ['super_admin', 'admin', 'staff', 'operator']))
            <div x-show="isExpanded" style="display: none;" class="pt-5 pb-2 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-[0.15em] whitespace-nowrap">
                Transaksi
            </div>
            <div x-show="!isExpanded" class="pt-5 pb-2 text-center text-[10px] font-bold text-slate-400">
                <i class="fas fa-ellipsis-h"></i>
            </div>

            <a wire:navigate.hover href="{{ route('orders.create', $params) }}"
               class="flex items-center rounded-xl text-sm font-medium transition-all duration-200
               {{ request()->routeIs('orders.create')
                 ? 'bg-red-600 text-white shadow-lg shadow-red-200'
                 : 'text-slate-600 hover:bg-slate-50 hover:text-red-600' }}"
               :class="isExpanded ? 'px-3 py-2.5 gap-3 justify-start' : 'p-2.5 justify-center'">
                <i class="fas fa-plus-circle w-5 text-center flex-shrink-0"></i>
                <span x-show="isExpanded" style="display: none;" x-transition class="whitespace-nowrap">Buat Pesanan</span>
            </a>

            <a wire:navigate.hover href="{{ route('orders.index', $params) }}"
               class="flex items-center rounded-xl text-sm font-medium transition-all duration-200
               {{ request()->routeIs('orders.index') || request()->routeIs('orders.show')
                 ? 'bg-blue-50 text-blue-600'
                 : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}"
               :class="isExpanded ? 'px-3 py-2.5 gap-3 justify-start' : 'p-2.5 justify-center'">
                <i class="fas fa-shopping-bag w-5 text-center flex-shrink-0"></i>
                <span x-show="isExpanded" style="display: none;" x-transition class="whitespace-nowrap">Riwayat Pesanan</span>
            </a>
        @endif

        {{-- AREA LAPORAN --}}
        @if(in_array(Auth::user()->role, ['super_admin', 'admin', 'keuangan', 'finance']))
            <a wire:navigate.hover href="{{ route('reports.index', $params) }}"
               class="flex items-center rounded-xl text-sm font-medium transition-all duration-200
               {{ request()->routeIs('reports.index')
                 ? 'bg-blue-50 text-blue-600'
                 : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}"
               :class="isExpanded ? 'px-3 py-2.5 gap-3 justify-start' : 'p-2.5 justify-center'">
                <i class="fas fa-chart-pie w-5 text-center flex-shrink-0"></i>
                <span x-show="isExpanded" style="display: none;" x-transition class="whitespace-nowrap">Laporan Penjualan</span>
            </a>
        @endif

        {{-- AREA SISTEM --}}
        <div x-show="isExpanded" style="display: none;" class="pt-5 pb-2 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-[0.15em] whitespace-nowrap">
            Sistem & Relasi
        </div>
        <div x-show="!isExpanded" class="pt-5 pb-2 text-center text-[10px] font-bold text-slate-400">
            <i class="fas fa-ellipsis-h"></i>
        </div>

        {{-- DANA DASHBOARD --}}
        @if(in_array(Auth::user()->role, ['super_admin', 'admin', 'keuangan', 'finance']))
            <a wire:navigate.hover href="{{ route('dana.dashboard', $params) }}"
               class="flex items-center rounded-xl text-sm font-medium transition-all duration-200
               {{ request()->routeIs('dana.dashboard')
                 ? 'bg-sky-50 text-sky-600'
                 : 'text-slate-600 hover:bg-slate-50 hover:text-sky-600' }}"
               :class="isExpanded ? 'px-3 py-2.5 gap-3 justify-start' : 'p-2.5 justify-center'">
                <i class="fas fa-wallet w-5 text-center flex-shrink-0"></i>
                <span x-show="isExpanded" style="display: none;" x-transition class="whitespace-nowrap">DANA Dashboard</span>
            </a>
        @endif

        {{-- AFFILIATE --}}
        @if(in_array(Auth::user()->role, ['super_admin', 'admin']))
            <a wire:navigate.hover href="{{ route('affiliate.index', $params) }}"
               class="flex items-center rounded-xl text-sm font-medium transition-all duration-200
               {{ request()->routeIs('affiliate.*')
                 ? 'bg-blue-50 text-blue-600'
                 : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}"
               :class="isExpanded ? 'px-3 py-2.5 gap-3 justify-start' : 'p-2.5 justify-center'">
                <i class="fas fa-user-friends w-5 text-center flex-shrink-0"></i>
                <span x-show="isExpanded" style="display: none;" x-transition class="whitespace-nowrap">Partner Afiliasi</span>
            </a>
        @endif

        {{-- DATA TENANT (LINK MANUAL) --}}
        @if(in_array(Auth::user()->role, ['super_admin']))
            <a wire:navigate.hover href="{{ url('admin/list-customer') }}"
               class="flex items-center rounded-xl text-sm font-medium transition-all duration-200
               {{ Request::is('admin/list-customer*')
                 ? 'bg-blue-50 text-blue-600'
                 : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}"
               :class="isExpanded ? 'px-3 py-2.5 gap-3 justify-start' : 'p-2.5 justify-center'">
                <i class="fas fa-store w-5 text-center flex-shrink-0"></i>
                <span x-show="isExpanded" style="display: none;" x-transition class="whitespace-nowrap">Data Tenant</span>
            </a>
        @endif

        {{-- MENU LISENSI (BARU - DROPDOWN KHUSUS SUPERADMIN) --}}
        @if(in_array(Auth::user()->role, ['super_admin']))
            <div x-data="{ open: {{ request()->routeIs('superadmin.license.*') ? 'true' : 'false' }} }">
                <button @click="if(!isExpanded) { isExpanded = true; open = true; } else { open = !open; }" type="button"
                    class="w-full flex items-center transition-all duration-200 rounded-xl text-sm font-semibold
                    {{ request()->routeIs('superadmin.license.*') ? 'bg-purple-50 text-purple-600' : 'text-slate-600 hover:bg-slate-50 hover:text-purple-600' }}"
                    :class="isExpanded ? 'justify-between px-3 py-2.5 gap-3' : 'justify-center p-2.5'">
                    <div class="flex items-center" :class="isExpanded ? 'gap-3' : ''">
                        <div class="w-8 h-8 flex-shrink-0 rounded-lg {{ request()->routeIs('superadmin.license.*') ? 'bg-purple-600 text-white shadow-md shadow-purple-100' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center transition-all">
                            <i class="fas fa-key text-xs"></i>
                        </div>
                        <span x-show="isExpanded" style="display: none;" x-transition class="whitespace-nowrap">Manajemen Lisensi</span>
                    </div>
                    <i x-show="isExpanded" style="display: none;" class="fas fa-chevron-down text-[10px] transition-transform duration-300 flex-shrink-0" :class="open ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="isExpanded && open" style="display: none;" x-collapse class="mt-1 ml-7 border-l-2 border-slate-100 space-y-1">
                    <a wire:navigate.hover href="{{ route('superadmin.license.index', $params) }}"
                       class="block pl-6 pr-3 py-2 text-xs font-medium rounded-r-xl transition-all
                       {{ request()->routeIs('superadmin.license.index') ? 'text-purple-600 bg-purple-50/50 border-l-2 border-purple-600 -ml-[2px]' : 'text-slate-500 hover:text-purple-600 hover:bg-slate-50' }}">
                        Daftar Lisensi
                    </a>
                    <a wire:navigate.hover href="{{ route('superadmin.license.redeem', $params) }}"
                       class="block pl-6 pr-3 py-2 text-xs font-medium rounded-r-xl transition-all
                       {{ request()->routeIs('superadmin.license.redeem') ? 'text-purple-600 bg-purple-50/50 border-l-2 border-purple-600 -ml-[2px]' : 'text-slate-500 hover:text-purple-600 hover:bg-slate-50' }}">
                        Redeem Kode
                    </a>
                </div>
            </div>
        @endif

        {{-- PRODUK --}}
        @if(in_array(Auth::user()->role, ['super_admin', 'admin', 'staff', 'operator']))
            <a wire:navigate.hover href="{{ route('products.index', $params) }}"
               class="flex items-center rounded-xl text-sm font-medium transition-all duration-200
               {{ request()->routeIs('products.*')
                 ? 'bg-blue-50 text-blue-600'
                 : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}"
               :class="isExpanded ? 'px-3 py-2.5 gap-3 justify-start' : 'p-2.5 justify-center'">
                <i class="fas fa-box w-5 text-center flex-shrink-0"></i>
                <span x-show="isExpanded" style="display: none;" x-transition class="whitespace-nowrap">Data Produk</span>
            </a>
        @endif

        {{-- [BARU] MENU ANALISA HPP --}}
        @if(in_array(Auth::user()->role, ['super_admin', 'admin']))
            <a wire:navigate.hover href="{{ route('hpp.index', $params) }}"
               class="flex items-center rounded-xl text-sm font-medium transition-all duration-200
               {{ request()->routeIs('hpp.*')
                 ? 'bg-blue-50 text-blue-600'
                 : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}"
               :class="isExpanded ? 'px-3 py-2.5 gap-3 justify-start' : 'p-2.5 justify-center'">
                <i class="fas fa-calculator w-5 text-center flex-shrink-0"></i>
                <span x-show="isExpanded" style="display: none;" x-transition class="whitespace-nowrap">Kalkulator HPP</span>
            </a>
        @endif

        {{-- KEUANGAN --}}
        @if(in_array(Auth::user()->role, ['super_admin', 'admin', 'keuangan', 'finance']))
            <div x-show="isExpanded" style="display: none;" class="pt-5 pb-2 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-[0.15em] whitespace-nowrap">
                Keuangan & Laporan
            </div>
            <div x-show="!isExpanded" class="pt-5 pb-2 text-center text-[10px] font-bold text-slate-400">
                <i class="fas fa-ellipsis-h"></i>
            </div>

            {{-- [BARU] MENU CASHFLOW & HUTANG --}}
            <a wire:navigate.hover href="{{ route('cashflow.index', $params) }}"
               class="flex items-center rounded-xl text-sm font-medium transition-all duration-200
               {{ request()->routeIs('cashflow.*')
                 ? 'bg-blue-50 text-blue-600'
                 : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}"
               :class="isExpanded ? 'px-3 py-2.5 gap-3 justify-start' : 'p-2.5 justify-center'">
                <i class="fas fa-money-bill-wave w-5 text-center flex-shrink-0"></i>
                <span x-show="isExpanded" style="display: none;" x-transition class="whitespace-nowrap">Cashflow Harian</span>
            </a>

            <a wire:navigate.hover href="{{ route('contacts.index', $params) }}"
               class="flex items-center rounded-xl text-sm font-medium transition-all duration-200
               {{ request()->routeIs('contacts.*')
                 ? 'bg-blue-50 text-blue-600'
                 : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}"
               :class="isExpanded ? 'px-3 py-2.5 gap-3 justify-start' : 'p-2.5 justify-center'">
                <i class="fas fa-address-book w-5 text-center flex-shrink-0"></i>
                <span x-show="isExpanded" style="display: none;" x-transition class="whitespace-nowrap">Data Kontak & Hutang</span>
            </a>

            <div x-data="{ open: {{ request()->routeIs('finance.*') ? 'true' : 'false' }} }">
                <button @click="if(!isExpanded) { isExpanded = true; open = true; } else { open = !open; }" type="button"
                    class="w-full flex items-center transition-all duration-200 rounded-xl text-sm font-semibold
                    {{ request()->routeIs('finance.*') ? 'bg-blue-50 text-blue-600' : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}"
                    :class="isExpanded ? 'justify-between px-3 py-2.5 gap-3' : 'justify-center p-2.5'">
                    <div class="flex items-center" :class="isExpanded ? 'gap-3' : ''">
                        <div class="w-8 h-8 flex-shrink-0 rounded-lg {{ request()->routeIs('finance.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-100' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center transition-all">
                            <i class="fas fa-chart-line text-xs"></i>
                        </div>
                        <span x-show="isExpanded" style="display: none;" x-transition class="whitespace-nowrap">Finansial & Laba</span>
                    </div>
                    <i x-show="isExpanded" style="display: none;" class="fas fa-chevron-down text-[10px] transition-transform duration-300 flex-shrink-0" :class="open ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="isExpanded && open" style="display: none;" x-collapse class="mt-1 ml-7 border-l-2 border-slate-100 space-y-1">
                    <a wire:navigate.hover href="{{ route('finance.index', $params) }}"
                       class="block pl-6 pr-3 py-2 text-xs font-medium rounded-r-xl transition-all
                       {{ request()->routeIs('finance.index') ? 'text-blue-600 bg-blue-50/50 border-l-2 border-blue-600 -ml-[2px]' : 'text-slate-500 hover:text-blue-600 hover:bg-slate-50' }}">
                        Ringkasan Arus Kas
                    </a>
                    <a wire:navigate.hover href="{{ route('finance.laba_rugi', $params) }}"
                       class="block pl-6 pr-3 py-2 text-xs font-medium rounded-r-xl transition-all
                       {{ request()->routeIs('finance.laba_rugi') ? 'text-blue-600 bg-blue-50/50 border-l-2 border-blue-600 -ml-[2px]' : 'text-slate-500 hover:text-blue-600 hover:bg-slate-50' }}">
                        Laporan Laba Rugi
                    </a>
                    <a wire:navigate.hover href="{{ route('finance.tahunan', $params) }}"
                       class="block pl-6 pr-3 py-2 text-xs font-medium rounded-r-xl transition-all
                       {{ request()->routeIs('finance.tahunan') ? 'text-blue-600 bg-blue-50/50 border-l-2 border-blue-600 -ml-[2px]' : 'text-slate-500 hover:text-blue-600 hover:bg-slate-50' }}">
                        Analisis Tahunan
                    </a>
                    <a wire:navigate.hover href="{{ route('finance.sync', $params) }}"
                       class="flex items-center justify-between pl-6 pr-4 py-2 text-xs font-medium rounded-r-xl transition-all
                       {{ request()->routeIs('finance.sync') ? 'text-emerald-600 bg-emerald-50 border-l-2 border-emerald-600 -ml-[2px]' : 'text-slate-500 hover:text-emerald-600 hover:bg-emerald-50' }}">
                        <span>Sinkronisasi Data</span>
                        <i class="fas fa-sync-alt text-[10px] {{ request()->routeIs('finance.sync') ? 'animate-spin' : '' }}"></i>
                    </a>
                </div>
            </div>
        @endif

        {{-- PEGAWAI --}}
        @if(in_array(Auth::user()->role, ['super_admin', 'admin']))
            <div x-data="{ open: {{ request()->routeIs('employees.*') ? 'true' : 'false' }} }">
                <button @click="if(!isExpanded) { isExpanded = true; open = true; } else { open = !open; }" type="button"
                    class="w-full flex items-center transition-all duration-200 rounded-xl text-sm font-semibold
                    {{ request()->routeIs('employees.*') ? 'bg-blue-50 text-blue-600' : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}"
                    :class="isExpanded ? 'justify-between px-3 py-2.5 gap-3' : 'justify-center p-2.5'">
                    <div class="flex items-center" :class="isExpanded ? 'gap-3' : ''">
                        <div class="w-8 h-8 flex-shrink-0 rounded-lg {{ request()->routeIs('employees.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-100' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center transition-all">
                            <i class="fas fa-users-cog text-xs"></i>
                        </div>
                        <span x-show="isExpanded" style="display: none;" x-transition class="whitespace-nowrap">Data Pegawai</span>
                    </div>
                    <i x-show="isExpanded" style="display: none;" class="fas fa-chevron-down text-[10px] transition-transform duration-300 flex-shrink-0" :class="open ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="isExpanded && open" style="display: none;" x-collapse class="mt-1 ml-7 border-l-2 border-slate-100 space-y-1">
                    <a wire:navigate.hover href="{{ route('employees.index', $params) }}"
                       class="block pl-6 pr-3 py-2 text-xs font-medium rounded-r-xl transition-all
                       {{ request()->routeIs('employees.index') ? 'text-blue-600 bg-blue-50/50 border-l-2 border-blue-600 -ml-[2px]' : 'text-slate-500 hover:text-blue-600 hover:bg-slate-50' }}">
                        Daftar Pegawai
                    </a>
                    <a wire:navigate.hover href="{{ route('employees.create', $params) }}"
                       class="block pl-6 pr-3 py-2 text-xs font-medium rounded-r-xl transition-all
                       {{ request()->routeIs('employees.create') ? 'text-blue-600 bg-blue-50/50 border-l-2 border-blue-600 -ml-[2px]' : 'text-slate-500 hover:text-blue-600 hover:bg-slate-50' }}">
                        + Tambah Baru
                    </a>
                </div>
            </div>
        @endif

        {{-- PENGATURAN --}}
        <div x-show="isExpanded" style="display: none;" class="pt-5 pb-2 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-[0.15em] whitespace-nowrap">
            Pengaturan
        </div>
        <div x-show="!isExpanded" class="pt-5 pb-2 text-center text-[10px] font-bold text-slate-400">
            <i class="fas fa-ellipsis-h"></i>
        </div>

        <div x-data="{ open: {{ request()->routeIs('profile.*') ? 'true' : 'false' }} }">
            <button @click="if(!isExpanded) { isExpanded = true; open = true; } else { open = !open; }" type="button"
                class="w-full flex items-center transition-all duration-200 rounded-xl text-sm font-semibold
                {{ request()->routeIs('profile.*') ? 'bg-blue-50 text-blue-600' : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}"
                :class="isExpanded ? 'justify-between px-3 py-2.5 gap-3' : 'justify-center p-2.5'">
                <div class="flex items-center" :class="isExpanded ? 'gap-3' : ''">
                    <div class="w-8 h-8 flex-shrink-0 rounded-lg {{ request()->routeIs('profile.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-100' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center transition-all">
                        <i class="fas fa-user-cog text-xs"></i>
                    </div>
                    <span x-show="isExpanded" style="display: none;" x-transition class="whitespace-nowrap">Akun & Profile</span>
                </div>
                <i x-show="isExpanded" style="display: none;" class="fas fa-chevron-down text-[10px] transition-transform duration-300 flex-shrink-0" :class="open ? 'rotate-180' : ''"></i>
            </button>

            <div x-show="isExpanded && open" style="display: none;" x-collapse class="mt-1 ml-7 border-l-2 border-slate-100 space-y-1">
                <a wire:navigate.hover href="{{ route('profile.index', $params) }}"
                   class="block pl-6 pr-3 py-2 text-xs font-medium rounded-r-xl transition-all
                   {{ request()->routeIs('profile.index') ? 'text-blue-600 bg-blue-50/50 border-l-2 border-blue-600 -ml-[2px]' : 'text-slate-500 hover:text-blue-600 hover:bg-slate-50' }}">
                    Data Profile
                </a>
                <a wire:navigate.hover href="{{ route('profile.edit', $params) }}"
                   class="block pl-6 pr-3 py-2 text-xs font-medium rounded-r-xl transition-all
                   {{ request()->routeIs('profile.edit') ? 'text-blue-600 bg-blue-50/50 border-l-2 border-blue-600 -ml-[2px]' : 'text-slate-500 hover:text-blue-600 hover:bg-slate-50' }}">
                    Edit Profile
                </a>

                @if(in_array(Auth::user()->role, ['super_admin']))
                    {{-- Target Blank TIDAK BOLEH pakai wire:navigate --}}
                    <a href="{{ url('admin/logs') }}" target="_blank"
                       class="block pl-6 pr-3 py-2 text-xs font-medium rounded-r-xl text-red-500 hover:bg-red-50 transition-colors">
                        System Log
                    </a>
                @endif
            </div>
        </div>
    </nav>

    <div class="py-4 border-t border-slate-50 flex-shrink-0 transition-all duration-300" :class="isExpanded ? 'px-4' : 'px-2'">
        <div class="bg-slate-50 rounded-2xl mb-3 flex items-center transition-all duration-300" :class="isExpanded ? 'p-3 gap-3' : 'p-2 justify-center'">
            {{-- BAGIAN FOTO/LOGO --}}
            <div class="w-10 h-10 rounded-xl flex-shrink-0 overflow-hidden flex items-center justify-center border border-blue-50">
                @if(Auth::user()->logo)
                    <img src="{{ asset('storage/' . Auth::user()->logo) }}"
                        class="w-full h-full object-cover"
                        alt="User Logo">
                @else
                    <span class="text-blue-600 font-bold text-sm uppercase">
                        {{ substr(Auth::user()->name ?? 'AD', 0, 2) }}
                    </span>
                @endif
            </div>

            {{-- BAGIAN NAMA & ROLE --}}
            <div x-show="isExpanded" style="display: none;" x-transition class="overflow-hidden text-ellipsis whitespace-nowrap">
                <p class="text-xs font-bold text-slate-800 leading-none mb-1 truncate max-w-[140px]">
                    {{ Auth::user()->name ?? 'Administrator' }}
                </p>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium
                    @if(Auth::user()->role === 'super_admin') bg-purple-100 text-purple-800
                    @elseif(Auth::user()->role === 'admin') bg-blue-100 text-blue-800
                    @else bg-gray-100 text-gray-800 @endif uppercase">
                    {{ str_replace('_', ' ', Auth::user()->role ?? 'User') }}
                </span>
            </div>
        </div>

        {{-- Form LOGOUT --}}
        <form method="POST" action="{{ route('logout', $params) }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-2 text-xs font-bold text-red-600 bg-white border border-red-100 rounded-xl hover:bg-red-600 hover:text-white hover:border-red-600 transition-all duration-300 group shadow-sm shadow-red-50"
            :class="isExpanded ? 'px-4 py-2.5 justify-center' : 'p-2.5 justify-center'">
                <i class="fas fa-power-off group-hover:rotate-12 transition-transform"></i>
                <span x-show="isExpanded" style="display: none;">LOGOUT</span>
            </button>
        </form>
    </div>

</aside>
