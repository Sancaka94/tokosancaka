<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kasir POS - Sancaka</title>

    <link rel="icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">

    {{-- CSS & FONTS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- ========================================================= --}}
    {{-- [BAGIAN BARU: KONEKSI SERVER REALTIME (SCANNER HP)]       --}}
    {{-- ========================================================= --}}

   {{-- LIBRARY JS (Tetap pakai file lokal yang sudah Anda download, aman) --}}
    <script src="{{ asset('libs/pusher.min.js') }}"></script>
    <script src="{{ asset('libs/echo.js') }}"></script>

    <script>
        // Setup Pusher Official
        window.Pusher = Pusher;

        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: '2accb7f700717ceb9424', // <-- Key dari Anda
            cluster: 'ap1',             // <-- Cluster Singapura
            forceTLS: true              // <-- Wajib TRUE karena website Anda HTTPS
        });

        console.log("🚀 Sancaka Realtime: Connected to Pusher.com (Singapore Cloud)");

        // Debugging: Cek koneksi
        window.Echo.connector.pusher.connection.bind('connected', () => {
            console.log("✅ KONEKSI SUKSES! Siap menerima scan.");
        });
        window.Echo.connector.pusher.connection.bind('failed', () => {
            console.error("❌ Koneksi Gagal.");
        });
    </script>
    {{-- ========================================================= --}}

    {{-- JS LIBRARIES (AlpineJS Wajib ditaruh SETELAH Reverb) --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Library Scanner Kamera (Laptop) --}}
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }

        .animate-fadeIn {
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

    </style>
</head>
{{--
    PERUBAHAN DI SINI:
    Cukup panggil posSystem() karena startScanner sudah ada di dalamnya.
--}}
<body class="bg-slate-100 font-sans text-slate-800 h-screen overflow-hidden select-none"
      x-data="posSystem()">


    @php
        // --- PERBAIKAN DI SINI ---
        $user = Auth::user();

        // 1. Cek apakah user login? Jika tidak, lempar ke halaman login
        if (!$user) {
            echo '<script>window.location.href = "/login";</script>';
            exit; // Stop loading halaman biar tidak error
        }

        // --- [MULAI KODE BARU: LOGIKA SUPER ADMIN] ---
            $isSuperAdmin = ($user->role === 'super_admin');
            $targetTenantId = $user->tenant_id;
            $allTenants = [];

            if ($isSuperAdmin) {
                // Ambil semua toko untuk isi dropdown
                $allTenants = \App\Models\Tenant::orderBy('name', 'asc')->get();

                // Jika Super Admin memilih toko lewat dropdown
                if (request()->has('view_tenant')) {
                    $targetTenantId = request('view_tenant');

                    // TIMPA DATA PRODUK & KATEGORI SESUAI TOKO YG DIPILIH
                    $products = \App\Models\Product::where('tenant_id', $targetTenantId)->with('category')->get();
                    $categories = \App\Models\Category::where('tenant_id', $targetTenantId)->get();
                }
            }

            // Ambil Data Tenant (Milik sendiri atau Pilihan Super Admin)
            $tenant = \App\Models\Tenant::find($targetTenantId);

            // Fallback jika null
            if (!$tenant && $isSuperAdmin) {
                $tenant = \App\Models\Tenant::first();
            }
            // --- [SELESAI KODE BARU] ---

        // 3. Cek validasi tenant (kode asli Anda)
        $isExpired = ($tenant->expired_at && now()->gt($tenant->expired_at));
        $isActive = ($tenant->status === 'active' || !$isExpired);
        $onSuspendedPage = request()->is('*account-suspended*');
    @endphp

{{-- ============================================================ --}}
{{-- SKENARIO 1: SUDAH LUNAS/AKTIF TAPI MASIH DI HALAMAN SUSPENDED --}}
{{-- ============================================================ --}}
@if($isActive && $onSuspendedPage)
    <script>
        var subdomain = "{{ $tenant->subdomain }}";
        // Redirect Balik ke Dashboard
        window.location.href = "https://" + subdomain + ".tokosancaka.com/orders/create";
    </script>
    @php exit; @endphp
@endif

{{-- ============================================================ --}}
{{-- SKENARIO 2: EXPIRED TAPI MASIH MAKSA BUKA DASHBOARD --}}
{{-- ============================================================ --}}
@if($isExpired && !$onSuspendedPage)
    <script>
        var subdomain = "{{ $tenant->subdomain }}";
        // Redirect Lempar ke Suspended
        window.location.href = "https://" + subdomain + ".tokosancaka.com/account-suspended";
    </script>
    @php exit; @endphp
@endif

    <div class="flex h-full w-full flex-col lg:flex-row overflow-hidden">

        {{-- BAGIAN KIRI (PRODUK) --}}
        <div class="flex-1 flex flex-col h-full relative border-r border-slate-200">

            {{-- TOP BAR --}}
            <div class="h-16 px-3 bg-white shadow-sm z-[100] flex items-center justify-between shrink-0 border-b border-slate-200 gap-3">
                {{-- LOGO (Hidden on very small mobile to save space) --}}
                <div class="flex items-center gap-2 shrink-0">
                    <div class="h-9 w-9 bg-red-600 rounded-lg flex items-center justify-center text-white text-lg shadow-red-200 shadow-lg">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <div class="hidden md:block">
                        <h1 class="text-lg font-black text-slate-800 tracking-tight">Sancaka<span class="text-red-600">POS</span></h1>
                        {{-- Tampilkan Subdomain Toko yang sedang dilihat --}}
                        <div class="text-[10px] text-slate-400 font-mono -mt-1">{{ $tenant->subdomain }}.tokosancaka.com</div>
                    </div>
                    {{-- <h1 class="text-lg font-black text-slate-800 tracking-tight hidden md:block">Sancaka<span class="text-red-600">POS</span></h1> --}}
                </div>

             {{-- [KODE BARU: DROPDOWN SUPER ADMIN ICON MODE] --}}
                @if($isSuperAdmin)
                <div class="relative z-[9999]" x-data="{ openTenant: false }" @click.outside="openTenant = false">

                    {{-- Tombol Utama (Hanya Icon) --}}
                    <button @click="openTenant = !openTenant"
                            class="h-10 w-10 bg-purple-50 border-2 border-purple-100 text-purple-600 hover:bg-purple-600 hover:text-white rounded-xl transition flex items-center justify-center shadow-sm relative z-50"
                            title="Ganti Toko (Super Admin)">
                        <i class="fas fa-store text-lg"></i>
                    </button>

                    {{-- Menu List Dropdown --}}
                    <div x-show="openTenant"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        class="absolute left-0 mt-2 w-72 bg-white border border-slate-200 rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.3)] z-[9999] overflow-hidden"
                        style="display: none;">

                        <div class="p-3 bg-purple-50 border-b border-purple-100">
                            <h4 class="text-[10px] font-black text-purple-700 uppercase tracking-widest">Pilih Toko Pantauan</h4>
                            <p class="text-[9px] text-purple-400 font-medium leading-tight">Klik nama toko untuk berpindah produk & kategori</p>
                        </div>

                        <div class="p-2 max-h-80 overflow-y-auto custom-scrollbar bg-white">
                            <form action="{{ url()->current() }}" method="GET" id="tenantSwitchForm">
                                @foreach($allTenants as $t)
                                    <button type="submit" name="view_tenant" value="{{ $t->id }}"
                                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition group text-left
                                            {{ $tenant->id == $t->id ? 'bg-purple-600 text-white' : 'hover:bg-purple-50 text-slate-700' }}">

                                        <div class="h-8 w-8 rounded-lg flex items-center justify-center shrink-0
                                            {{ $tenant->id == $t->id ? 'bg-white/20' : 'bg-purple-100 text-purple-600' }}">
                                            <i class="fas fa-shop text-xs"></i>
                                        </div>

                                        <div class="leading-tight">
                                            <p class="text-xs font-black uppercase">{{ $t->name }}</p>
                                            <span class="text-[9px] font-medium {{ $tenant->id == $t->id ? 'text-purple-100' : 'text-slate-400' }}">
                                                {{ $t->subdomain }}.tokosancaka.com
                                            </span>
                                        </div>

                                        @if($tenant->id == $t->id)
                                            <div class="ml-auto">
                                                <i class="fas fa-check-circle text-xs"></i>
                                            </div>
                                        @endif
                                    </button>
                                @endforeach
                            </form>
                        </div>
                    </div>
                </div>
                @endif
                {{-- [SELESAI KODE BARU] --}}

                {{-- KOLOM PENCARIAN --}}
                <div class="flex-1 max-w-xl">
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-slate-400 group-focus-within:text-red-500 transition-colors"></i>
                        </div>

                        {{-- MODIFIKASI DI SINI --}}
                        <input type="text"
                            x-ref="searchInput"
                            x-init="$el.focus()"
                            autofocus
                            x-model="search"
                            @keydown.enter.prevent="scanProduct()"
                            placeholder="Cari item..."
                            class="block w-full pl-10 pr-8 py-2.5 bg-slate-100 border-2 border-transparent text-slate-700 placeholder-slate-400 focus:bg-white focus:border-red-500 focus:ring-0 rounded-xl text-sm font-medium transition-all shadow-inner focus:shadow-lg">
                        {{-- AKHIR MODIFIKASI --}}

                        {{-- Tombol Clear Text --}}
                        <button x-show="search.length > 0"
                                @click="search = ''; $el.parentElement.querySelector('input').focus()"
                                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition"
                                x-transition>
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>
                </div>

                {{-- GROUP ICONS (Scanner, User, Cart) --}}
                <div class="flex items-center gap-2 shrink-0">

                    {{-- PEMBUNGKUS DROP_DOWN SCANNER DENGAN Z-INDEX TERTINGGI --}}
                    <div class="relative z-[9999]" x-data="{ openScanner: false }" @click.outside="openScanner = false">

                        {{-- Tombol Utama --}}
                        <button @click="openScanner = !openScanner"
                                class="h-10 w-10 bg-white border-2 border-slate-100 text-slate-600 hover:text-red-600 hover:border-red-200 hover:bg-red-50 rounded-xl transition flex items-center justify-center shadow-sm relative z-50">
                            <i class="fas fa-qrcode text-lg"></i>
                        </button>

                        {{-- Menu List Dropdown --}}
                        <div x-show="openScanner"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            class="absolute right-0 mt-2 w-64 bg-white border border-slate-200 rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.3)] z-[9999] overflow-hidden"
                            style="display: none;">

                            <div class="p-2 space-y-1 bg-white">
                                {{-- Opsi 1: Scanner Kamera --}}
                                <button @click="startScanner(); openScanner = false"
                                        class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-slate-700 hover:bg-red-50 hover:text-red-600 rounded-xl transition cursor-pointer">
                                    <div class="w-10 h-10 bg-red-100 text-red-600 rounded-lg flex items-center justify-center shrink-0">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                    <div class="text-left leading-tight">
                                        <p class="text-slate-800">Kamera Perangkat</p>
                                        <span class="text-[10px] text-slate-400 font-medium">Gunakan webcam laptop</span>
                                    </div>
                                </button>

                                <div class="border-b border-slate-100 my-1 mx-2"></div>

                                {{-- Opsi 2: Scanner HP --}}
                                <a href="{{ url('/mobile-scanner') }}"
                                target="_blank"
                                @click="openScanner = false"
                                class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-slate-700 hover:bg-blue-50 hover:text-blue-600 rounded-xl transition cursor-pointer">
                                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center shrink-0">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <div class="text-left leading-tight">
                                        <p class="text-slate-800">Scanner HP (Remote)</p>
                                        <span class="text-[10px] text-slate-400 font-medium">Gunakan Realtime Scan</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- 2. USER LOGIN --}}
                    <a href="{{ url('/member/login') }}"
                       class="h-10 w-10 bg-white border-2 border-slate-100 text-slate-600 hover:text-red-600 hover:border-red-200 hover:bg-red-50 rounded-xl transition flex items-center justify-center shadow-sm"
                       title="Member Login">
                        <i class="far fa-user text-lg"></i>
                    </a>

                    <button @click="mobileCartOpen = !mobileCartOpen"
                            class="lg:hidden relative h-10 w-10 bg-red-50 text-red-600 border border-red-100 rounded-xl flex items-center justify-center hover:bg-red-100 transition shadow-sm">
                        <i class="fas fa-shopping-bag text-lg"></i>

                        {{-- BADGE MERAH (ANGKA) --}}
                        <span x-show="cartTotalQty > 0"
                            x-transition.scale
                            class="absolute -top-1 -right-1 bg-red-600 text-white text-[9px] font-bold h-5 w-5 flex items-center justify-center rounded-full border-2 border-white shadow-sm"
                            x-text="cartTotalQty">
                            0
                        </span>
                    </button>
                </div>

            </div>

            {{-- BAGIAN ISI PRODUK --}}
            <div class="flex-1 overflow-y-auto custom-scrollbar bg-slate-50 relative">

                {{-- [MULAI KODE BARU: BANNER SUPER ADMIN] --}}
                @if($isSuperAdmin)
                <div class="bg-purple-100 border-b border-purple-200 px-4 py-2 flex items-center justify-center gap-2 text-xs font-bold text-purple-800">
                    <i class="fas fa-eye"></i> Mode Pemantauan: Anda sedang melihat produk toko <span class="underline">{{ $tenant->name }}</span>
                </div>
                @endif
                {{-- [SELESAI KODE BARU] --}}

                {{-- INFO PROMO --}}
                <div x-data="{ showInfo: true }" x-show="showInfo" x-transition.opacity.duration.300ms
                     class="m-4 bg-red-50 border border-red-200 rounded-xl p-3 flex items-start gap-3 shadow-sm relative group">
                    <div class="bg-red-100 text-red-600 rounded-lg h-8 w-8 flex items-center justify-center shrink-0">
                        <i class="fas fa-bullhorn text-sm"></i>
                    </div>
                    <div class="flex-1 pr-6">
                        <h4 class="text-xs font-bold text-red-800 uppercase tracking-wide mb-0.5">Info Promo & Affiliasi</h4>
                        <p class="text-[11px] text-red-700 leading-relaxed">
                            Ingin diskon <span class="font-bold">30%</span>? Masukan kode <span class="font-bold bg-white px-1 rounded border border-red-200">KUPON</span>. Dan kakak juga bisa menjadi affiliator dengan mendaftar di <a href="https://tokosancaka.com/join-partner" target="_blank" class="underline font-bold">sini</a>!
                        </p>
                    </div>
                    <button @click="showInfo = false" class="absolute top-2 right-2 text-red-400 hover:text-red-700 hover:bg-red-100 rounded-full h-6 w-6 flex items-center justify-center transition-all">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                {{-- STICKY HEADER KATEGORI --}}
                <div class="sticky top-0 z-30 bg-slate-50 px-4 py-2 border-b border-slate-200 shadow-sm mb-3">
                    <div class="flex overflow-x-auto gap-2 custom-scrollbar pb-1">
                        <button @click="activeCategory = 'all'"
                            class="flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold transition-all border shadow-sm flex items-center gap-2"
                            :class="activeCategory === 'all'
                                ? 'bg-red-600 text-white border-red-600 ring-2 ring-red-100'
                                : 'bg-white text-slate-600 border-slate-200 hover:border-red-300 hover:text-red-600'">
                            <i class="fas fa-th-large"></i> Semua
                        </button>

                        @if(isset($categories))
                            @foreach($categories as $cat)
                            <button @click="activeCategory = '{{ $cat->slug }}'"
                                class="flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold transition-all border shadow-sm whitespace-nowrap"
                                :class="activeCategory === '{{ $cat->slug }}'
                                    ? 'bg-red-600 text-white border-red-600 ring-2 ring-red-100'
                                    : 'bg-white text-slate-600 border-slate-200 hover:border-red-300 hover:text-red-600'">
                                {{ $cat->name }}
                            </button>
                            @endforeach
                        @endif
                    </div>
                </div>

                {{-- GRID PRODUK --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 xl:grid-cols-4 gap-3 px-4 pb-4">
                    @forelse($products as $product)

                    @php $prodCatSlug = $product->category->slug ?? 'retail'; @endphp

                    <template x-if="itemMatchesSearch('{{ addslashes($product->name) }}') && (activeCategory === 'all' || activeCategory === '{{ $prodCatSlug }}')">

                        <div @click="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->sell_price }}, {{ $product->stock }}, {{ $product->weight ?? 0 }}, '{{ $product->image ? asset('storage/'.$product->image) : '' }}', {{ $product->has_variant ? 'true' : 'false' }}, '{{ $prodCatSlug }}')"
                        class="relative bg-white rounded-2xl p-3 shadow-sm border border-slate-100 flex flex-col h-full group
                             {{ $product->stock <= 0 ? 'opacity-60 grayscale cursor-not-allowed' : 'cursor-pointer active:scale-95 hover:border-red-300 hover:shadow-md' }} transition-all duration-200">

                            {{-- LABEL STOK (MODIFIKASI: TAMPIL TERUS) --}}
                        <div class="absolute top-2 left-2 z-10 flex gap-1">
                            @if($product->stock <= 0)
                                <span class="bg-slate-700 text-white text-[9px] font-black uppercase px-2 py-0.5 rounded-md shadow-sm">Habis</span>
                            @else
                                {{-- Jika stok sedikit (<= 5), warna OREN dan kedip-kedip --}}
                                @if($product->stock <= 5)
                                    <span class="bg-amber-500 text-white text-[9px] font-black uppercase px-2 py-0.5 rounded-md animate-pulse shadow-sm">
                                        Sisa {{ $product->stock }}
                                    </span>
                                {{-- Jika stok banyak (> 5), warna HIJAU dan diam (tapi tetap muncul) --}}
                                @else
                                    <span class="bg-red-600 text-white text-[9px] font-bold uppercase px-2 py-0.5 rounded-md shadow-sm">
                                        Stok {{ $product->stock }}
                                    </span>
                                @endif
                            @endif
                        </div>


                            <div x-show="getItemQty({{ $product->id }}) > 0"
                                 class="absolute top-2 right-2 bg-green-600 text-white text-[10px] font-bold h-6 w-6 rounded-full flex items-center justify-center shadow-md z-10 ring-2 ring-green-50"
                                 x-text="getItemQty({{ $product->id }})" x-transition.scale>
                            </div>

                            <div class="h-40 bg-slate-50 rounded-xl flex items-center justify-center mb-3 overflow-hidden relative group-hover:bg-red-50 transition-colors p-2">
                                @if(!empty($product->image) && Storage::disk('public')->exists($product->image))
                                    <img src="{{ asset('storage/' . $product->image) }}"
                                         alt="{{ $product->name }}"
                                         class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-105">
                                @else
                                    <div class="text-3xl text-slate-300 group-hover:text-red-400 transition-colors">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                @endif
                            </div>

                            <div class="flex-1 flex flex-col">
                                <span class="text-[9px] text-slate-400 font-bold uppercase mb-0.5">{{ $product->category->name ?? 'Umum' }}</span>

                                <h3 class="font-bold text-slate-700 text-xs leading-tight mb-1 line-clamp-2 group-hover:text-red-600 transition-colors">{{ $product->name }}</h3>
                                <div class="mt-auto flex justify-between items-end">
                                    <p class="text-xs font-black text-slate-800">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">{{ $product->unit }}</p>
                                </div>
                            </div>
                        </div>
                    </template>
                    @empty
                    <div class="col-span-full flex flex-col items-center justify-center text-slate-400 mt-20">
                        <i class="fas fa-box-open text-5xl mb-3 opacity-20"></i>
                        <p class="text-sm font-medium">Belum ada produk tersedia.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- OVERLAY MOBILE CART --}}
        <div x-show="mobileCartOpen" class="fixed inset-0 bg-black/50 z-30 lg:hidden backdrop-blur-sm" @click="mobileCartOpen = false" x-transition.opacity></div>

        {{-- BAGIAN KANAN (KERANJANG) --}}
        <div class="fixed inset-y-0 right-0 w-[90%] sm:w-[420px] lg:static lg:w-[400px] bg-white shadow-2xl lg:shadow-none z-40 transform transition-transform duration-300 ease-out flex flex-col h-full border-l border-slate-200"
             :class="mobileCartOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'">

            <div class="h-16 px-5 border-b border-slate-100 flex justify-between items-center bg-green-50 shrink-0">
                <div class="flex flex-col">
                    <span class="text-[10px] font-bold text-green-600 uppercase tracking-widest">Pesanan Baru</span>
                    <span class="font-black text-green-700 text-lg">#{{ date('ymd') }}-{{ rand(100,999) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <button x-show="cart.length > 0" @click="confirmClearCart()" class="hidden lg:flex items-center gap-1 text-[10px] font-bold text-red-500 hover:bg-red-50 px-3 py-1.5 rounded-lg transition">
                        <i class="fas fa-trash-alt"></i> Reset
                    </button>
                    <button @click="mobileCartOpen = false" class="lg:hidden p-2 text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar bg-white">

                <div class="p-4 border-b border-slate-100 bg-slate-50/50"
                     x-show="activeCategory === 'all' || (!activeCategory.includes('laundry') && !activeCategory.includes('fnb') && !activeCategory.includes('ppob'))"
                     x-transition.opacity>

                    <div class="flex justify-between items-center mb-2">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                            Berkas Cetak (<span x-text="uploadedFiles.length"></span>/10)
                        </span>
                        <button x-show="uploadedFiles.length > 0" @click="uploadedFiles = []" class="text-[10px] text-red-500 hover:underline">
                            Reset Semua
                        </button>
                    </div>

                    <div x-show="uploadedFiles.length > 0" class="space-y-3 mb-3" x-transition>
                        <template x-for="(item, index) in uploadedFiles" :key="index">
                            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-3 hover:border-blue-300 transition-all">
                                <div class="flex items-center gap-2 mb-3 pb-2 border-b border-dashed border-slate-100">
                                    <div class="h-8 w-8 rounded bg-red-50 flex items-center justify-center text-red-500 text-xs shrink-0">
                                        <i class="fas fa-file-pdf" x-show="item.file.type.includes('pdf')"></i>
                                        <i class="fas fa-image" x-show="item.file.type.includes('image')"></i>
                                        <i class="fas fa-file" x-show="!item.file.type.includes('pdf') && !item.file.type.includes('image')"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[11px] font-bold text-slate-700 truncate" x-text="item.file.name"></p>
                                        <p class="text-[9px] text-slate-400" x-text="formatFileSize(item.file.size)"></p>
                                    </div>
                                    <button @click="removeFile(index)" class="text-slate-300 hover:text-red-500 transition px-2">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="col-span-1">
                                        <label class="flex items-center gap-2 cursor-pointer bg-slate-50 p-1.5 rounded-lg border border-slate-100 h-full">
                                            <input type="checkbox" x-model="item.isColor" class="rounded text-red-600 focus:ring-red-500 w-4 h-4 border-slate-300">
                                            <span class="text-[10px] font-bold leading-tight" :class="item.isColor ? 'text-slate-800' : 'text-slate-400'">
                                                <span x-text="item.isColor ? 'Berwarna' : 'Hitam Putih'"></span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="col-span-1">
                                        <select x-model="item.paperSize" class="w-full text-[10px] font-bold py-1.5 px-1 rounded-lg border-slate-200 bg-slate-50 focus:ring-red-500 focus:border-red-500">
                                            <option value="A4">A4</option>
                                            <option value="F4">F4</option>
                                            <option value="A3">A3</option>
                                        </select>
                                    </div>
                                    <div class="col-span-1 relative">
                                        <div class="flex items-center border border-slate-200 rounded-lg bg-slate-50 overflow-hidden h-full">
                                            <input type="number" x-model="item.qty" min="1" class="w-full text-center text-[10px] font-bold bg-transparent border-none p-0 focus:ring-0" placeholder="1">
                                            <span class="text-[9px] text-slate-400 pr-1.5">lbr</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="uploadedFiles.length < 10" x-transition>
                        <div class="relative border-2 border-dashed border-red-300 rounded-xl bg-white hover:border-green-400 hover:bg-green-50 transition-all cursor-pointer group h-12 flex items-center justify-center">
                            <input type="file" multiple @change="handleFileUpload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                   accept=".doc,.docx,.pdf,.xls,.xlsx,.jpg,.jpeg,.png">
                            <div class="flex items-center gap-2 pointer-events-none">
                                <i class="fas" :class="uploadedFiles.length > 0 ? 'fa-plus text-green-500' : 'fa-cloud-upload-alt text-red-400'"></i>
                                <p class="text-[10px] font-bold" :class="uploadedFiles.length > 0 ? 'text-green-600' : 'text-slate-500'">
                                    <span x-text="uploadedFiles.length === 0 ? 'Upload Berkas Pertama' : 'Tambah File Lain'"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 space-y-3 min-h-[200px]">
                    <template x-if="cart.length === 0">
                        <div class="flex flex-col items-center justify-center h-40 text-slate-300">
                            <i class="fas fa-shopping-basket text-4xl mb-2 opacity-50"></i>
                            <p class="text-xs font-bold">Keranjang Kosong</p>
                        </div>
                    </template>

                    <template x-for="item in cart" :key="item.id">
                        <div class="flex items-start gap-3 p-3 bg-white border border-slate-100 rounded-xl shadow-sm hover:border-red-200 transition-colors group">

                            {{-- Kolom Qty --}}
                            <div class="flex flex-col items-center bg-slate-50 rounded-lg border border-slate-200 shrink-0 w-12">
                                <button @click="updateQty(item.id, 1)" class="w-full h-7 flex items-center justify-center text-slate-500 hover:text-white hover:bg-green-500 rounded-t-lg transition border-b border-slate-200">
                                    <i class="fas fa-plus text-[10px]"></i>
                                </button>

                                <input type="number" step="0.01" x-model="item.qty" @change="validateManualQty(item.id)"
                                    class="w-full text-center text-xs font-bold bg-transparent border-none p-0 focus:ring-0 text-slate-800 h-8"
                                    title="Jumlah (Kg/Pcs)">

                                <button @click="updateQty(item.id, -1)" class="w-full h-7 flex items-center justify-center text-slate-500 hover:text-white hover:bg-red-500 rounded-b-lg transition border-t border-slate-200">
                                    <i class="fas fa-minus text-[10px]"></i>
                                </button>
                            </div>

                            {{-- Gambar --}}
                            <div class="h-12 w-12 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center p-0.5">
                                <template x-if="item.image">
                                    <img :src="item.image" class="h-full w-full object-contain">
                                </template>
                                <template x-if="!item.image">
                                    <i class="fas fa-box text-slate-300 text-sm"></i>
                                </template>
                            </div>

                            {{-- Detail Produk --}}
                            <div class="flex-1 min-w-0 py-0.5">
                                <div class="font-bold text-slate-700 text-xs leading-tight mb-2 truncate" x-text="item.name"></div>

                                <div class="flex items-center gap-2">
                                    <div class="text-[9px] text-slate-400 bg-slate-50 px-2 py-1 rounded border border-slate-100">
                                        @ <span x-text="rupiah(item.price)"></span>
                                    </div>

                                    <i class="fas fa-arrow-right text-[8px] text-slate-300"></i>

                                    {{-- INPUT TOTAL RUPIAH --}}
                                    <div class="flex-1 relative">
                                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[9px] font-bold text-slate-400">Rp</span>

                                        <input type="number"
                                            step="any"
                                            :value="(item.price * item.qty) ? (item.price * item.qty) : ''"
                                            @change="updateByTotal(item.id, $event.target.value)"
                                            class="w-full pl-6 pr-2 py-1 text-xs font-black text-slate-800 bg-white border border-slate-200 rounded focus:ring-1 focus:ring-red-500 focus:border-red-500 text-right shadow-sm placeholder-white"
                                            placeholder="">
                                    </div>
                                </div>
                            </div>

                            {{-- Tombol Hapus --}}
                            <button @click="removeFromCart(item.id)" class="text-slate-300 hover:text-red-500 p-1 self-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fas fa-trash-alt text-sm"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-200 z-20 shrink-0 shadow-[0_-5px_15px_rgba(0,0,0,0.02)]">

                <div x-show="activeCategory.includes('laundry')"
                     x-data="{ isOpen: false }"
                     x-transition.opacity.duration.300ms
                     class="mb-3 border border-indigo-100 rounded-xl bg-white shadow-sm">

                    <button @click="isOpen = !isOpen" class="w-full flex rounded-t-xl items-center justify-between p-3 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                        <div class="flex items-center gap-2">
                            <div class="h-6 w-6 rounded bg-indigo-200 text-indigo-700 flex items-center justify-center text-xs">
                                <i class="fas fa-tshirt"></i>
                            </div>
                            <div class="text-left">
                                <h3 class="text-[10px] font-bold text-indigo-700 uppercase tracking-widest leading-tight">Data Pelanggan</h3>
                                <p class="text-[9px] text-indigo-500 font-medium" x-show="!isOpen && (customerName || customerPhone)">
                                    <span x-text="customerName || 'Tanpa Nama'"></span>
                                    <span x-show="customerPhone" x-text="'(' + customerPhone + ')'"></span>
                                </p>
                                <p class="text-[9px] text-indigo-400 font-medium italic" x-show="!isOpen && !customerName && !customerPhone">Klik untuk isi data...</p>
                            </div>
                        </div>
                        <i class="fas text-indigo-400 text-xs transition-transform duration-300" :class="isOpen ? 'fa-chevron-up rotate-180' : 'fa-chevron-down'"></i>
                    </button>

                    <div x-show="isOpen" class="p-3 space-y-2 bg-white">

                        <div class="relative">
                            <label class="block text-[9px] font-bold text-slate-400 mb-1 uppercase">Nama Pelanggan</label>
                            <div class="relative">
                                <input type="text" x-model="customerName" @input.debounce.500ms="searchCustomerByName()" placeholder="Contoh: Budi Santoso"
                                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 bg-slate-50 placeholder-slate-400 transition-all">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-2" x-show="isSearchingCustomer && customerName.length > 2">
                                    <i class="fas fa-circle-notch fa-spin text-slate-400 text-xs"></i>
                                </div>
                            </div>
                            <div x-show="customerNameSearchResults.length > 0" @click.outside="customerNameSearchResults = []"
                                 class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-xl max-h-40 overflow-y-auto">
                                <template x-for="cust in customerNameSearchResults" :key="cust.id">
                                    <div @click="fillCustomerData(cust); customerNameSearchResults = []"
                                         class="px-3 py-2 text-xs border-b cursor-pointer hover:bg-indigo-50 border-slate-50 flex flex-col">
                                        <span class="font-bold text-slate-700" x-text="cust.name"></span>
                                        <span class="text-[10px] text-slate-500" x-text="cust.whatsapp"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="relative">
                            <label class="block text-[9px] font-bold text-slate-400 mb-1 uppercase">WhatsApp (Wajib)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs"><i class="fab fa-whatsapp"></i></span>
                                <input type="tel" x-model="customerPhone" @input.debounce.500ms="searchCustomerByPhone()" @blur="sanitizePhone()" placeholder="08xxxxxxxxxx"
                                    class="w-full pl-8 pr-3 py-2 text-xs rounded-lg border border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 bg-slate-50 placeholder-slate-400 transition-all">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-2" x-show="isCustomerFound">
                                    <i class="fas fa-check-circle text-emerald-500 text-xs"></i>
                                </div>
                            </div>
                            <div x-show="customerSearchResults.length > 0" @click.outside="customerSearchResults = []"
                                 class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-xl max-h-40 overflow-y-auto">
                                <template x-for="cust in customerSearchResults" :key="cust.id">
                                    <div @click="fillCustomerData(cust)" class="px-3 py-2 text-xs border-b cursor-pointer hover:bg-indigo-50 border-slate-50 flex flex-col">
                                        <span class="font-bold text-slate-700" x-text="cust.name"></span>
                                        <span class="text-[10px] text-slate-500" x-text="cust.whatsapp"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[9px] font-bold text-slate-400 mb-1 uppercase">Alamat / Catatan</label>
                            <textarea x-model="customerAddressDetail" rows="2" placeholder="Alamat lengkap..."
                                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 bg-slate-50 placeholder-slate-400 resize-none transition-all"></textarea>
                        </div>

                        <div class="mt-2 mb-2">
                            <button @click="getGeoLocation()"
                                    class="w-full py-1.5 border border-dashed border-green-500 text-green-600 rounded-lg text-[10px] font-bold hover:bg-green-50 flex items-center justify-center gap-1 transition">
                                <span x-show="isGettingLocation"><i class="fas fa-circle-notch fa-spin"></i> Mencari GPS...</span>
                                <span x-show="!isGettingLocation">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span x-text="latitude ? 'Update Lokasi GPS' : 'Ambil Lokasi GPS'"></span>
                                </span>
                            </button>
                            <div x-show="latitude" class="text-[9px] text-slate-400 mt-1 text-center">
                                Lat: <span x-text="latitude"></span>, Long: <span x-text="longitude"></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <button @click="saveCustomerToDB()"
                                    :disabled="isSavingCustomer || !customerName || !customerPhone"
                                    class="w-full py-2 bg-green-600 text-white text-[10px] font-bold rounded-lg hover:bg-green-700 transition flex items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm">
                                <span x-show="!isSavingCustomer"><i class="fas fa-save"></i> Simpan Ke Database</span>
                                <span x-show="isSavingCustomer" style="display: none;"><i class="fas fa-circle-notch fa-spin"></i> Proses...</span>
                            </button>

                            <button @click="isOpen = false"
                                    class="w-full py-2 bg-indigo-50 text-indigo-600 text-[10px] font-bold rounded-lg hover:bg-indigo-100 transition border border-indigo-100">
                                Simpan Ke Checkout
                            </button>
                        </div>

                        <div x-show="isCustomerFound" class="pt-2 border-t border-slate-100 mt-2">
                            <button @click="resetCustomerData()" class="w-full text-[10px] text-red-400 hover:text-red-600 hover:underline text-center">
                                Reset / Input Data Baru
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="relative">
                        <input type="text"
                               name="no_cache_promo_{{ \Illuminate\Support\Str::random(10) }}"
                               id="promo_{{ \Illuminate\Support\Str::random(10) }}"
                               autocomplete="new-password"
                               readonly
                               onfocus="this.removeAttribute('readonly');"
                               spellcheck="false"
                               x-model="couponCode"
                               @input.debounce.500ms="checkCoupon()"
                               placeholder="KODE PROMO..."
                               class="w-full pl-3 pr-10 py-2 text-sm rounded-lg border border-slate-200 focus:ring-red-500 uppercase font-bold text-slate-700 transition-colors placeholder-slate-400"
                               :class="{
                                   'border-emerald-500 bg-emerald-50 text-emerald-700': discountAmount > 0,
                                   'border-red-300 bg-red-50 text-red-700': couponMessage && discountAmount === 0,
                                   'bg-white': !couponMessage
                               }">

                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i x-show="isValidatingCoupon" class="fas fa-circle-notch fa-spin text-slate-400"></i>
                            <i x-show="!isValidatingCoupon && discountAmount > 0" class="fas fa-check-circle text-emerald-500 text-lg"></i>
                            <i x-show="!isValidatingCoupon && couponMessage && discountAmount === 0" class="fas fa-times-circle text-red-500 text-lg"></i>
                        </div>
                    </div>

                    <p x-show="couponMessage" x-text="couponMessage" class="text-[10px] font-bold mt-1 ml-1"
                       :class="discountAmount > 0 ? 'text-emerald-600' : 'text-red-500'"></p>
                </div>

                <div class="space-y-1 mb-4">
                    <div class="flex justify-between items-end text-xs text-slate-500">
                        <span>Subtotal</span>
                        <span x-text="'Rp ' + rupiah(subtotal)"></span>
                    </div>

                    <div class="flex justify-between items-center py-2 border-b border-dashed border-slate-200">
                        <button @click="noteModalOpen = true" class="text-[11px] font-bold flex items-center gap-1 transition-colors focus:outline-none"
                                :class="customerNote ? 'text-blue-600' : 'text-slate-400 hover:text-blue-500'">
                            <i class="fas" :class="customerNote ? 'fa-edit' : 'fa-plus-circle'"></i>
                            <span x-text="customerNote ? 'Edit Catatan Pesanan' : 'Tambah Catatan Pesanan'"></span>
                        </button>
                        <span x-show="customerNote" class="text-[10px] text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full font-bold">Ada Catatan</span>
                    </div>

                    <div x-show="discountAmount > 0" class="flex justify-between items-end text-xs text-emerald-600 font-bold" x-transition>
                        <span>Diskon</span>
                        <span x-text="'- Rp ' + rupiah(discountAmount)"></span>
                    </div>
                    <div class="flex justify-between items-end pt-2 border-t border-dashed border-slate-300">
                        <span class="text-sm font-bold text-slate-800">Total Tagihan</span>
                        <span class="text-2xl font-black text-slate-800 tracking-tight" x-text="'Rp ' + rupiah(grandTotal)"></span>
                    </div>
                </div>

                <button @click="openPaymentModal()"
                        :disabled="cart.length === 0"
                        class="w-full bg-red-600 text-white py-4 rounded-xl font-bold text-base shadow-lg hover:bg-green-800 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 group">
                    <span class="flex items-center gap-2">
                        <span>Bayar Sekarang</span> <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <div x-data="{
            showPromo: false,
            init() {
                if (!localStorage.getItem('seenPromoSancaka_v1')) {
                    setTimeout(() => { this.showPromo = true }, 1500);
                }
            },
            closePromo() {
                this.showPromo = false;
                localStorage.setItem('seenPromoSancaka_v1', 'true');
            }
        }"
        x-show="showPromo" style="display: none;"
        class="fixed inset-0 z-[100] flex items-center justify-center px-4 sm:px-0 font-sans">

        <div x-show="showPromo" x-transition.opacity @click="closePromo()" class="fixed inset-0 bg-slate-900/70 backdrop-blur-[2px]"></div>

        <div x-show="showPromo" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-90 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="relative bg-white rounded-2xl shadow-2xl w-full max-w-[450px] overflow-hidden flex flex-col z-10 border border-slate-100">

            <button @click="closePromo()" class="absolute top-3 right-3 z-20 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-full h-8 w-8 flex items-center justify-center transition-all">
                <i class="fas fa-times text-lg"></i>
            </button>

            <div class="relative bg-slate-50 w-full h-40 flex items-center justify-center p-6 border-b border-slate-100">
                <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 10px 10px;"></div>
                <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka Promo" class="relative w-full h-full object-contain drop-shadow-md transform hover:scale-105 transition-transform duration-500">
            </div>

            <div class="p-6 text-center">
                <h2 class="text-xl font-black text-slate-800 mb-3 leading-tight">Ingin mendapatkan <span class="text-red-600">Diskon 30%?</span></h2>
                <p class="text-slate-600 text-sm leading-relaxed mb-5">
                    Masukan kode <span class="font-bold bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded border border-amber-200 text-xs">KUPON</span> dari teman atau saudara Anda.
                </p>
                <div class="space-y-3">
                    <a href="https://tokosancaka.com/join-partner" target="_blank" class="flex items-center justify-center w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-red-200 hover:shadow-red-300 transform active:scale-95 transition-all group">
                        <span>Gabung Sekarang</span>
                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    <button @click="closePromo()" class="text-slate-400 font-bold text-xs hover:text-slate-600 py-1">Tutup Informasi</button>
                </div>
            </div>
        </div>
    </div>

    {{-- === MODAL OVERLAY KAMERA === --}}
    {{-- Ini akan otomatis membaca scannerOpen dari posSystem() di JS --}}
    <div x-show="scannerOpen" style="display: none;"
         class="fixed inset-0 z-[100] bg-slate-900/95 backdrop-blur-sm flex flex-col items-center justify-center p-4"
         x-transition.opacity>

        <div class="w-full max-w-md bg-white rounded-3xl overflow-hidden shadow-2xl relative border-4 border-slate-800">

            {{-- Header Modal --}}
            <div class="absolute top-0 inset-x-0 p-4 flex justify-between items-center z-10 bg-gradient-to-b from-black/50 to-transparent">
                <span class="text-white font-bold text-sm tracking-wide flex items-center gap-2">
                    <i class="fas fa-expand text-red-400"></i> SCANNER AKTIF
                </span>
                <button @click="stopScanner()" class="h-8 w-8 bg-black/40 text-white rounded-full backdrop-blur-md flex items-center justify-center hover:bg-red-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Area Kamera --}}
            <div id="reader" class="w-full h-auto bg-black min-h-[300px]"></div>

            {{-- Footer Modal --}}
            <div class="bg-slate-900 p-5 text-center">
                <p class="text-slate-300 text-xs mb-3">Arahkan kamera ke barcode produk</p>
                <div class="flex justify-center gap-2">
                    <span class="h-1 w-1 bg-red-500 rounded-full animate-ping"></span>
                    <span class="text-red-500 text-xs font-bold uppercase tracking-widest">Mencari Kode...</span>
                </div>
            </div>
        </div>
    </div>

    @include('orders.partials.variantModal')
    @include('orders.partials.noteModal')
    @include('orders.partials.payment-modal')

    <script>
        @include('orders.partials.pos-script')
    </script>

    {{-- AUDIO ELEMENTS --}}
    <audio id="audio-success" src="https://tokosancaka.com/public/sound/beep.mp3" preload="auto"></audio>
    <audio id="audio-error" src="https://tokosancaka.com/public/sound/beep-gagal.mp3" preload="auto"></audio>

<div x-show="scannerModalOpen"
     style="display: none;"
     class="fixed inset-0 z-[100] flex items-center justify-center px-4"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" @click="stopScanner()"></div>

    <div class="relative bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-slate-200 transform transition-all"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100">

        <div class="bg-slate-50 border-b border-slate-100 p-4 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-qrcode text-red-500"></i> Scan Barcode
            </h3>
            <button @click="stopScanner()" class="h-8 w-8 rounded-full bg-slate-200 text-slate-500 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-4 bg-black relative">
            <div id="reader-modal" class="w-full rounded-lg overflow-hidden border-2 border-slate-700 bg-black" style="min-height: 300px;"></div>

            <div class="absolute top-1/2 left-4 right-4 h-0.5 bg-red-600/80 shadow-[0_0_10px_rgba(220,38,38,0.8)] z-10 animate-pulse"></div>
        </div>

        <div class="p-4 bg-white text-center space-y-3">
            <p class="text-xs text-slate-500 font-medium">
                Arahkan kamera ke barcode produk. <br>
                Pastikan cahaya cukup terang.
            </p>

            <div class="flex gap-2">
                <input type="text" x-model="tempManualCode"
                       @keydown.enter="handleManualModalInput()"
                       class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                       placeholder="Atau ketik kode manual disini...">
                <button @click="handleManualModalInput()" class="bg-slate-800 text-white px-4 py-2 rounded-lg hover:bg-slate-700 text-sm font-bold">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
