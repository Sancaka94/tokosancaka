<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    {{-- [PWA META TAGS] Agar Full Screen & Rasa Native App --}}
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#ffffff">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kasir POS - Sancaka</title>

    <link rel="icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">

    {{-- CSS & FONTS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- [LOGIC LAMA: KONEKSI SERVER REALTIME] --}}
    <script src="{{ asset('libs/pusher.min.js') }}"></script>
    <script src="{{ asset('libs/echo.js') }}"></script>

    <script>
        // Setup Pusher Official (LOGIC ASLI ANDA)
        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: '2accb7f700717ceb9424', // Key Asli Anda
            cluster: 'ap1',
            forceTLS: true
        });
        console.log("🚀 Sancaka Realtime: Connected.");
    </script>

    {{-- JS LIBRARIES --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* [STYLE BARU] Hilangkan Scrollbar agar bersih seperti App */
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* [STYLE BARU] Area aman untuk HP Poni (iPhone X dll) */
        .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
        .pt-safe { padding-top: env(safe-area-inset-top); }

        /* Animasi */
        .animate-fadeIn { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>

{{-- BODY: Tambahkan pb-safe agar tidak tertutup nav bar --}}
<body class="bg-slate-50 font-sans text-slate-800 h-screen overflow-hidden select-none pb-safe"
      x-data="posSystem()">

    @php
        // [LOGIC PHP ASLI ANDA - TIDAK DIRUBAH SAMA SEKALI]
        $user = Auth::user();
        if (!$user) { echo '<script>window.location.href = "/login";</script>'; exit; }

        $isSuperAdmin = ($user->role === 'super_admin');
        $targetTenantId = $user->tenant_id;
        $allTenants = [];

        if ($isSuperAdmin) {
            $allTenants = \App\Models\Tenant::orderBy('name', 'asc')->get();
            if (request()->has('view_tenant')) {
                $targetTenantId = request('view_tenant');
                // LOGIC TIMPA DATA PRODUK
                $products = \App\Models\Product::where('tenant_id', $targetTenantId)->with('category')->get();
                $categories = \App\Models\Category::where('tenant_id', $targetTenantId)->get();
            }
        }
        $tenant = \App\Models\Tenant::find($targetTenantId);
        if (!$tenant && $isSuperAdmin) { $tenant = \App\Models\Tenant::first(); }

        $isExpired = ($tenant->expired_at && now()->gt($tenant->expired_at));
        $isActive = ($tenant->status === 'active' || !$isExpired);
        $onSuspendedPage = request()->is('*account-suspended*');
    @endphp

    {{-- [LOGIC REDIRECT ASLI ANDA] --}}
    @if($isActive && $onSuspendedPage)
        <script>window.location.href = "https://{{ $tenant->subdomain }}.tokosancaka.com/orders/create";</script> @php exit; @endphp
    @endif
    @if($isExpired && !$onSuspendedPage)
        <script>window.location.href = "https://{{ $tenant->subdomain }}.tokosancaka.com/account-suspended";</script> @php exit; @endphp
    @endif

    {{-- MAIN LAYOUT WRAPPER --}}
    <div class="flex h-full w-full flex-col lg:flex-row overflow-hidden">

        {{-- ========================================================= --}}
        {{-- BAGIAN KIRI: DAFTAR PRODUK --}}
        {{-- ========================================================= --}}
        <div class="flex-1 flex flex-col h-full relative border-r border-slate-200 pb-16 lg:pb-0">

            {{-- 1. TOP BAR (STICKY) --}}
            <div class="h-14 px-4 bg-white shadow-sm z-30 flex items-center justify-between shrink-0 sticky top-0 pt-safe">

                {{-- Logo & Nama Toko --}}
                <div class="flex items-center gap-2 shrink-0">
                    <div class="h-8 w-8 bg-red-600 rounded-lg flex items-center justify-center text-white text-sm shadow-red-200 shadow-lg">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <div>
                        <h1 class="text-sm font-black text-slate-800 tracking-tight leading-none">Sancaka<span class="text-red-600">POS</span></h1>
                        <div class="text-[9px] text-slate-400 font-mono">{{ $tenant->subdomain }}</div>
                    </div>
                </div>

                {{-- Kolom Pencarian (Compact Style) --}}
                <div class="flex-1 max-w-[180px] mx-3">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" x-model="search" @keydown.enter.prevent="scanProduct()" placeholder="Cari..."
                            class="w-full bg-slate-100 border-none rounded-full py-1.5 pl-8 pr-2 text-xs font-bold focus:ring-1 focus:ring-red-500 transition-all placeholder-slate-400 text-slate-700">
                        {{-- Tombol Clear --}}
                        <button x-show="search.length > 0" @click="search = ''" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-500">
                            <i class="fas fa-times-circle text-xs"></i>
                        </button>
                    </div>
                </div>

                 {{-- Button Super Admin (Logic Asli) --}}
                 @if($isSuperAdmin)
                 <div class="relative z-50" x-data="{ openTenant: false }" @click.outside="openTenant = false">
                     <button @click="openTenant = !openTenant" class="h-8 w-8 bg-purple-50 text-purple-600 rounded-full flex items-center justify-center shadow-sm active:scale-95 transition-transform">
                         <i class="fas fa-store text-xs"></i>
                     </button>

                     <div x-show="openTenant" class="absolute right-0 mt-2 w-64 bg-white border border-slate-200 rounded-xl shadow-xl z-[9999] overflow-hidden" style="display: none;">
                        <div class="p-3 bg-purple-50 border-b border-purple-100"><h4 class="text-[10px] font-bold text-purple-700">Pilih Toko</h4></div>
                        <div class="p-2 max-h-60 overflow-y-auto custom-scrollbar">
                             <form action="{{ url()->current() }}" method="GET">
                                 @foreach($allTenants as $t)
                                     <button type="submit" name="view_tenant" value="{{ $t->id }}" class="w-full text-left px-3 py-2 text-xs hover:bg-purple-50 rounded-lg {{ $tenant->id == $t->id ? 'font-bold text-purple-700 bg-purple-50' : '' }}">
                                         {{ $t->name }}
                                     </button>
                                 @endforeach
                             </form>
                        </div>
                     </div>
                 </div>
                 @endif
            </div>

            {{-- 2. KATEGORI (HORIZONTAL SCROLL) --}}
            <div class="bg-white px-4 py-2 border-b border-slate-100 shrink-0">
                <div class="flex overflow-x-auto gap-2 hide-scrollbar pb-1">
                    <button @click="activeCategory = 'all'"
                        class="flex-shrink-0 px-4 py-1.5 rounded-full text-[10px] font-bold transition-all border"
                        :class="activeCategory === 'all' ? 'bg-red-600 text-white border-red-600 shadow-sm' : 'bg-white text-slate-500 border-slate-200'">
                        Semua
                    </button>
                    @if(isset($categories))
                        @foreach($categories as $cat)
                        <button @click="activeCategory = '{{ $cat->slug }}'"
                            class="flex-shrink-0 px-4 py-1.5 rounded-full text-[10px] font-bold transition-all border whitespace-nowrap"
                            :class="activeCategory === '{{ $cat->slug }}' ? 'bg-red-600 text-white border-red-600 shadow-sm' : 'bg-white text-slate-500 border-slate-200'">
                            {{ $cat->name }}
                        </button>
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- 3. GRID PRODUK (SCROLLABLE AREA) --}}
            <div class="flex-1 overflow-y-auto hide-scrollbar bg-slate-50 p-3">

                {{-- Info Promo --}}
                <div x-data="{ showInfo: true }" x-show="showInfo" class="mb-3 bg-red-50 border border-red-100 rounded-xl p-3 flex gap-3 relative shadow-sm">
                    <div class="bg-red-100 text-red-600 h-8 w-8 rounded-lg flex items-center justify-center shrink-0"><i class="fas fa-bullhorn text-xs"></i></div>
                    <div class="text-[10px] text-red-800 leading-tight">
                         Kode Promo: <span class="font-bold bg-white px-1 rounded border border-red-200">KUPON</span> untuk diskon 30%.
                    </div>
                    <button @click="showInfo = false" class="absolute top-1 right-2 text-red-300"><i class="fas fa-times text-xs"></i></button>
                </div>

                {{-- Grid Produk --}}
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 pb-safe">
                    @forelse($products as $product)
                    @php $prodCatSlug = $product->category->slug ?? 'retail'; @endphp

                    <template x-if="itemMatchesSearch('{{ addslashes($product->name) }}') && (activeCategory === 'all' || activeCategory === '{{ $prodCatSlug }}')">

                        {{-- CARD PRODUK (NATIVE FEEL) --}}
                        {{-- Menggunakan active:scale-95 untuk efek tekan --}}
                        <div @click="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->sell_price }}, {{ $product->stock }}, {{ $product->weight ?? 0 }}, '{{ $product->image ? asset('storage/'.$product->image) : '' }}', {{ $product->has_variant ? 'true' : 'false' }}, '{{ $prodCatSlug }}')"
                             class="bg-white rounded-xl shadow-[0_2px_8px_rgba(0,0,0,0.04)] border border-slate-100 overflow-hidden relative active:scale-95 transition-transform duration-100 h-full flex flex-col cursor-pointer">

                            {{-- Gambar --}}
                            <div class="h-32 bg-slate-50 relative flex items-center justify-center overflow-hidden">
                                @if(!empty($product->image) && Storage::disk('public')->exists($product->image))
                                    <img src="{{ asset('storage/' . $product->image) }}" class="w-full h-full object-cover">
                                @else
                                    <i class="fas fa-box-open text-3xl text-slate-300"></i>
                                @endif

                                {{-- Stok Badge --}}
                                <div class="absolute top-2 left-2">
                                     @if($product->stock <= 0) <span class="bg-slate-800 text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow-sm">HABIS</span>
                                     @elseif($product->stock <= 5) <span class="bg-amber-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded animate-pulse shadow-sm">{{ $product->stock }} Sisa</span>
                                     @endif
                                </div>
                                {{-- Qty Badge --}}
                                <div x-show="getItemQty({{ $product->id }}) > 0" class="absolute top-2 right-2 bg-green-600 text-white text-[9px] font-bold h-5 w-5 rounded-full flex items-center justify-center shadow-md border-2 border-white" x-text="getItemQty({{ $product->id }})"></div>
                            </div>

                            {{-- Text Info --}}
                            <div class="p-2.5 flex-1 flex flex-col justify-between">
                                <div>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase">{{ $product->category->name ?? 'Umum' }}</span>
                                    <h3 class="font-bold text-xs text-slate-700 leading-tight line-clamp-2 mb-1">{{ $product->name }}</h3>
                                </div>
                                <div class="flex items-end justify-between mt-1">
                                    <p class="font-black text-xs text-slate-900">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</p>
                                    <span class="text-[9px] text-slate-400">{{ $product->unit }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                    @empty
                        <div class="col-span-full text-center text-slate-400 py-10 text-xs">Belum ada produk.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- BAGIAN KANAN: KERANJANG (BOTTOM SHEET MOBILE) --}}
        {{-- ========================================================= --}}

        <div class="fixed inset-x-0 bottom-0 z-[60] lg:static lg:w-[400px] lg:h-full lg:z-0 flex flex-col transition-transform duration-300 ease-out h-[85vh] lg:h-auto border-l border-slate-200 shadow-2xl lg:shadow-none bg-white"
             :class="mobileCartOpen ? 'translate-y-0' : 'translate-y-[110%] lg:translate-y-0'">

            {{-- Backdrop Gelap (Hanya Mobile) --}}
            <div x-show="mobileCartOpen" @click="mobileCartOpen = false" class="fixed inset-0 bg-black/60 backdrop-blur-sm lg:hidden -z-10 h-screen mt-[-15vh]"></div>

            {{-- Container Cart --}}
            <div class="w-full h-full rounded-t-3xl lg:rounded-none flex flex-col overflow-hidden bg-white">

                {{-- Handle Bar (Visual Only for Mobile) --}}
                <div class="w-full flex justify-center pt-3 pb-1 lg:hidden" @click="mobileCartOpen = false">
                    <div class="w-12 h-1.5 bg-slate-200 rounded-full"></div>
                </div>

                {{-- Header Cart --}}
                <div class="px-5 py-3 border-b border-slate-100 flex justify-between items-center bg-white shrink-0">
                    <div>
                        <h2 class="text-sm font-bold text-slate-800">Keranjang Belanja</h2>
                        <span class="text-[10px] text-slate-400 font-mono font-medium">No: #{{ date('ymd') }}-{{ rand(100,999) }}</span>
                    </div>
                    <button x-show="cart.length > 0" @click="confirmClearCart()" class="text-[10px] font-bold text-red-500 bg-red-50 px-2 py-1 rounded-lg hover:bg-red-100 transition">
                        <i class="fas fa-trash-alt mr-1"></i> Reset
                    </button>
                </div>

                {{-- List Item Keranjang --}}
                <div class="flex-1 overflow-y-auto hide-scrollbar bg-slate-50 p-4 space-y-3">

                    {{-- State Kosong --}}
                    <template x-if="cart.length === 0">
                        <div class="flex flex-col items-center justify-center h-full text-slate-400 opacity-50">
                            <div class="bg-slate-100 rounded-full p-6 mb-3">
                                <i class="fas fa-shopping-basket text-4xl text-slate-300"></i>
                            </div>
                            <p class="text-xs font-bold">Keranjang Kosong</p>
                            <p class="text-[10px]">Pilih produk di menu sebelah kiri</p>
                        </div>
                    </template>

                    {{-- Loop Item --}}
                    <template x-for="item in cart" :key="item.id">
                        <div class="flex items-center gap-3 bg-white p-3 rounded-xl border border-slate-100 shadow-sm relative overflow-hidden group">

                             {{-- Qty Controls --}}
                             <div class="flex flex-col items-center gap-1 bg-slate-100 rounded-lg p-0.5 shrink-0">
                                <button @click="updateQty(item.id, 1)" class="w-7 h-7 bg-white rounded shadow-sm text-green-600 text-[10px] flex items-center justify-center font-bold active:bg-green-50"><i class="fas fa-plus"></i></button>
                                <input type="number" x-model="item.qty" @change="validateManualQty(item.id)" class="text-xs font-bold text-slate-700 w-7 text-center bg-transparent border-none p-0 focus:ring-0">
                                <button @click="updateQty(item.id, -1)" class="w-7 h-7 bg-white rounded shadow-sm text-red-500 text-[10px] flex items-center justify-center font-bold active:bg-red-50"><i class="fas fa-minus"></i></button>
                            </div>

                            {{-- Image Mini --}}
                            <div class="h-10 w-10 rounded bg-slate-50 shrink-0 overflow-hidden flex items-center justify-center border border-slate-100">
                                <template x-if="item.image"><img :src="item.image" class="w-full h-full object-cover"></template>
                                <template x-if="!item.image"><i class="fas fa-box text-slate-300 text-xs"></i></template>
                            </div>

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <h4 class="text-xs font-bold text-slate-800 truncate" x-text="item.name"></h4>
                                <div class="flex justify-between items-center mt-1">
                                     <span class="text-[10px] text-slate-400 bg-slate-50 px-1 rounded">@ <span x-text="rupiah(item.price)"></span></span>
                                     {{-- Input Manual Total --}}
                                     <input type="number" :value="item.price * item.qty" @change="updateByTotal(item.id, $event.target.value)"
                                            class="w-20 text-xs font-black text-slate-800 text-right border-none bg-transparent p-0 focus:ring-0 placeholder-transparent">
                                </div>
                            </div>

                            {{-- Delete --}}
                            <button @click="removeFromCart(item.id)" class="text-slate-300 hover:text-red-500 px-2 lg:opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-trash-alt text-xs"></i></button>
                        </div>
                    </template>
                </div>

                {{-- Footer Cart (Summary & Pay) --}}
                <div class="p-4 bg-white border-t border-slate-100 pb-safe shadow-[0_-5px_15px_rgba(0,0,0,0.02)] z-20">

                    {{-- Summary Bar --}}
                     <div class="space-y-1 mb-3">
                        <div class="flex justify-between items-end text-[10px] text-slate-500">
                            <span>Subtotal</span> <span x-text="'Rp ' + rupiah(subtotal)"></span>
                        </div>
                        <div x-show="discountAmount > 0" class="flex justify-between items-end text-[10px] text-emerald-600 font-bold">
                            <span>Diskon</span> <span x-text="'- Rp ' + rupiah(discountAmount)"></span>
                        </div>
                        <div class="flex justify-between items-end mt-2 pt-2 border-t border-dashed border-slate-200">
                            <span class="text-xs font-bold text-slate-800">Total Tagihan</span>
                            <span class="text-xl font-black text-slate-800 tracking-tight" x-text="'Rp ' + rupiah(grandTotal)"></span>
                        </div>
                    </div>

                    {{-- Tombol Note & Pay --}}
                    <div class="flex gap-2">
                        <button @click="noteModalOpen = true" class="w-12 h-12 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-blue-50 hover:text-blue-600 transition" :class="customerNote ? 'text-blue-600 bg-blue-50 ring-2 ring-blue-100' : ''">
                            <i class="fas fa-edit"></i>
                        </button>

                        <button @click="openPaymentModal()" :disabled="cart.length === 0"
                                class="flex-1 bg-red-600 text-white py-3 rounded-xl font-bold text-sm shadow-lg shadow-red-200 active:scale-95 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                            <span>Bayar Sekarang</span> <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ========================================================= --}}
    {{-- BOTTOM NAVIGATION BAR (FIXED - MOBILE ONLY) --}}
    {{-- ========================================================= --}}
    <div class="fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 h-16 pb-safe flex items-center justify-around z-50 lg:hidden shadow-[0_-5px_20px_rgba(0,0,0,0.05)]">

        {{-- Home --}}
        <button class="flex flex-col items-center justify-center w-16 gap-1 text-red-600">
            <i class="fas fa-home text-lg"></i>
            <span class="text-[9px] font-bold">Kasir</span>
        </button>

        {{-- Scan (Floating Center) --}}
        <button @click="openScanner = true"
                class="w-14 h-14 bg-slate-800 text-white rounded-full flex items-center justify-center -mt-8 border-4 border-slate-50 shadow-xl active:scale-90 transition-transform">
            <i class="fas fa-qrcode text-xl"></i>
        </button>

        {{-- Cart --}}
        <button @click="mobileCartOpen = true" class="flex flex-col items-center justify-center w-16 gap-1 relative text-slate-400">
            <i class="fas fa-shopping-bag text-lg" :class="cartTotalQty > 0 ? 'text-red-600' : ''"></i>
            <span class="text-[9px] font-bold" :class="cartTotalQty > 0 ? 'text-red-600' : ''">Keranjang</span>
            {{-- Badge --}}
            <div x-show="cartTotalQty > 0"
                 class="absolute top-0 right-3 bg-red-600 text-white text-[9px] font-bold h-4 w-4 rounded-full flex items-center justify-center border-2 border-white"
                 x-text="cartTotalQty"></div>
        </button>
    </div>

    {{-- ========================================================= --}}
    {{-- MODAL & PARTIALS LAINNYA --}}
    {{-- ========================================================= --}}

    @include('orders.partials.variantModal')
    @include('orders.partials.noteModal')
    @include('orders.partials.payment-modal')

    <audio id="audio-success" src="https://tokosancaka.com/public/sound/beep.mp3" preload="auto"></audio>
    <audio id="audio-error" src="https://tokosancaka.com/public/sound/beep-gagal.mp3" preload="auto"></audio>

    {{-- SCANNER MODAL (FULL SCREEN OVERLAY) --}}
    <div x-show="scannerOpen" style="display: none;" class="fixed inset-0 z-[100] bg-black flex flex-col" x-transition.opacity>
        <div class="h-14 flex justify-between items-center px-4 bg-black/50 absolute top-0 inset-x-0 z-20 pt-safe backdrop-blur-sm">
            <span class="text-white font-bold text-sm"><i class="fas fa-qrcode text-red-500 mr-2"></i> Scan Barcode</span>
            <button @click="stopScanner()" class="h-8 w-8 bg-white/20 rounded-full text-white flex items-center justify-center"><i class="fas fa-times"></i></button>
        </div>

        {{-- Area Kamera --}}
        <div id="reader-modal" class="w-full h-full bg-black"></div>

        {{-- Input Manual --}}
        <div class="absolute bottom-10 inset-x-0 px-6 z-20 pb-safe">
            <div class="bg-black/60 p-2 rounded-xl backdrop-blur-md flex gap-2">
                <input type="text" x-model="tempManualCode" @keydown.enter="handleManualModalInput()" placeholder="Ketik kode manual..." class="w-full bg-transparent border-none text-white placeholder-white/50 focus:ring-0 text-sm">
                <button @click="handleManualModalInput()" class="bg-red-600 text-white px-4 rounded-lg text-xs font-bold">OK</button>
            </div>
        </div>
    </div>

    {{-- TOP UP MODAL (AlpineJS) --}}
    <div x-data="topUpSystem()" @open-topup-modal.window="openModal()"
         x-show="isOpen" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         x-cloak style="display: none;" x-transition.opacity>

         <div class="bg-white rounded-2xl p-6 w-full max-w-sm relative" @click.outside="isOpen = false" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100">

             <div class="text-center mb-4">
                 <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-2 text-xl"><i class="fas fa-wallet"></i></div>
                 <h3 class="text-lg font-bold text-slate-800">Top Up Saldo</h3>
                 <p class="text-xs text-slate-500">Saldo minimal Rp 10.000</p>
             </div>

             <div class="relative mb-4">
                 <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">Rp</span>
                 <input type="number" x-model="amount" class="w-full pl-10 border border-slate-200 rounded-xl p-3 text-lg font-bold text-slate-800 focus:ring-green-500 focus:border-green-500" placeholder="0">
             </div>

             <div class="grid grid-cols-3 gap-2 mb-6">
                 <button @click="amount = 50000" class="border border-slate-200 rounded-lg py-2 text-xs font-bold hover:bg-green-50 hover:text-green-600 hover:border-green-200">50rb</button>
                 <button @click="amount = 100000" class="border border-slate-200 rounded-lg py-2 text-xs font-bold hover:bg-green-50 hover:text-green-600 hover:border-green-200">100rb</button>
                 <button @click="amount = 200000" class="border border-slate-200 rounded-lg py-2 text-xs font-bold hover:bg-green-50 hover:text-green-600 hover:border-green-200">200rb</button>
             </div>

             <div class="flex gap-3">
                 <button @click="isOpen=false" class="w-1/2 py-3 bg-slate-100 text-slate-600 rounded-xl font-bold text-sm hover:bg-slate-200">Batal</button>
                 <button @click="processTopUp()" :disabled="isLoading" class="w-1/2 py-3 bg-green-600 text-white rounded-xl font-bold text-sm hover:bg-green-700 disabled:opacity-50">
                     <span x-show="!isLoading">Bayar</span>
                     <span x-show="isLoading"><i class="fas fa-circle-notch fa-spin"></i></span>
                 </button>
             </div>
         </div>
    </div>

    {{-- SCRIPT UTAMA POS (DARI FILE INCLUDE) --}}
    <script>
        @include('orders.partials.pos-script')
    </script>

    {{-- SCRIPT KHUSUS TOPUP (LOGIC LENGKAP) --}}
    <script>
        function topUpSystem() {
            return {
                isOpen: false,
                amount: '',
                isLoading: false,

                openModal() {
                    this.isOpen = true;
                    this.amount = '';
                    setTimeout(() => { this.$el.querySelector('input[type="number"]')?.focus(); }, 100);
                },

                async processTopUp() {
                    if (!this.amount || this.amount < 10000) {
                        if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'warning', title: 'Minimal Rp 10.000', timer: 1500, showConfirmButton: false }); }
                        else { alert('Minimal Top Up Rp 10.000'); }
                        return;
                    }

                    this.isLoading = true;
                    try {
                        // FETCH KE BACKEND
                        const response = await fetch("{{ route('tenant.payment.url') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                "Accept": "application/json"
                            },
                            body: JSON.stringify({ amount: this.amount })
                        });

                        const result = await response.json();

                        if (result.success && result.url) {
                            window.open(result.url, '_blank');
                            this.isOpen = false;
                            if(typeof Swal !== 'undefined') {
                                Swal.fire({ title: 'Halaman Pembayaran Terbuka', html: 'Silakan selesaikan pembayaran di tab baru.', icon: 'info', confirmButtonText: 'Oke' });
                            }
                        } else {
                            alert('Gagal: ' + (result.error || 'Terjadi kesalahan'));
                        }
                    } catch (error) {
                        console.error(error);
                        alert('Terjadi kesalahan koneksi.');
                    } finally {
                        this.isLoading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
