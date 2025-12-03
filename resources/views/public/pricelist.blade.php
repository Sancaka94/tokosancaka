@extends('layouts.marketplace')

@php
    // --- 1. SETUP DATA KATEGORI (Sesuai List Kamu) ---
    $urlSlug = request()->segment(4); 
    $pageInfo = $pageInfo ?? [];
    $currentSlug = $pageInfo['slug'] ?? $urlSlug ?? 'pulsa'; 

    // Daftar Slug Pascabayar untuk deteksi otomatis halaman
    $postpaidSlugs = [
        'pln-pascabayar', 'pdam', 'bpjs-kesehatan', 'bpjs-ketenagakerjaan', 
        'hp-pascabayar', 'internet-pascabayar', 'tv-pascabayar', 
        'multifinance', 'pbb', 'samsat', 'gas-negara', 'pln-nontaglis'
    ];
    
    $isPostpaid = ($pageInfo['is_postpaid'] ?? false) || in_array($currentSlug, $postpaidSlugs);
    $pageTitle = $pageInfo['title'] ?? ucwords(str_replace('-', ' ', $currentSlug));

    // --- ARRAY MENU PRABAYAR (LENGKAP) ---
    $prepaidMenus = [
        ['slug' => 'pulsa', 'name' => 'Pulsa', 'icon' => 'fa-mobile-alt', 'style' => 'text-red-500 bg-red-50 border-red-100'],
        ['slug' => 'data', 'name' => 'Paket Data', 'icon' => 'fa-wifi', 'style' => 'text-blue-500 bg-blue-50 border-blue-100'],
        ['slug' => 'pln-token', 'name' => 'Token PLN', 'icon' => 'fa-bolt', 'style' => 'text-yellow-500 bg-yellow-50 border-yellow-100'],
        ['slug' => 'e-money', 'name' => 'E-Money', 'icon' => 'fa-wallet', 'style' => 'text-purple-500 bg-purple-50 border-purple-100'],
        ['slug' => 'voucher-game', 'name' => 'Games', 'icon' => 'fa-gamepad', 'style' => 'text-indigo-500 bg-indigo-50 border-indigo-100'],
        ['slug' => 'voucher', 'name' => 'Voucher', 'icon' => 'fa-ticket-alt', 'style' => 'text-pink-500 bg-pink-50 border-pink-100'],
        ['slug' => 'paket-sms-telpon', 'name' => 'SMS & Telpon', 'icon' => 'fa-phone-volume', 'style' => 'text-green-500 bg-green-50 border-green-100'],
        ['slug' => 'masa-aktif', 'name' => 'Masa Aktif', 'icon' => 'fa-hourglass-half', 'style' => 'text-orange-500 bg-orange-50 border-orange-100'],
        ['slug' => 'aktivasi-voucher', 'name' => 'Akt. Voucher', 'icon' => 'fa-barcode', 'style' => 'text-gray-600 bg-gray-50 border-gray-200'],
        ['slug' => 'aktivasi-perdana', 'name' => 'Akt. Perdana', 'icon' => 'fa-sim-card', 'style' => 'text-slate-600 bg-slate-50 border-slate-200'],
        ['slug' => 'streaming', 'name' => 'Streaming', 'icon' => 'fa-play-circle', 'style' => 'text-red-600 bg-red-50 border-red-100'],
        ['slug' => 'tv', 'name' => 'TV Prabayar', 'icon' => 'fa-tv', 'style' => 'text-teal-500 bg-teal-50 border-teal-100'],
        ['slug' => 'china-topup', 'name' => 'China Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-red-500 bg-red-50 border-red-100'],
        ['slug' => 'malaysia-topup', 'name' => 'Mly. Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-blue-600 bg-blue-50 border-blue-100'],
        ['slug' => 'philippines-topup', 'name' => 'Phil. Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-yellow-500 bg-yellow-50 border-yellow-100'],
        ['slug' => 'singapore-topup', 'name' => 'S\'pore Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-red-600 bg-red-50 border-red-100'],
        ['slug' => 'thailand-topup', 'name' => 'Thai Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-blue-500 bg-blue-50 border-blue-100'],
        ['slug' => 'vietnam-topup', 'name' => 'Viet. Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-red-500 bg-red-50 border-red-100'],
        ['slug' => 'bundling', 'name' => 'Bundling', 'icon' => 'fa-box-open', 'style' => 'text-cyan-500 bg-cyan-50 border-cyan-100'],
        ['slug' => 'gas', 'name' => 'Gas Token', 'icon' => 'fa-burn', 'style' => 'text-orange-600 bg-orange-50 border-orange-100'],
        ['slug' => 'esim', 'name' => 'eSIM', 'icon' => 'fa-qrcode', 'style' => 'text-indigo-600 bg-indigo-50 border-indigo-100'],
        ['slug' => 'media-sosial', 'name' => 'Media Sosial', 'icon' => 'fa-thumbs-up', 'style' => 'text-blue-500 bg-blue-50 border-blue-100'],
        ['slug' => 'telkomsel-omni', 'name' => 'Tsel Omni', 'icon' => 'fa-tower-cell', 'style' => 'text-red-600 bg-red-50 border-red-100'],
        ['slug' => 'indosat-only4u', 'name' => 'Isat Only4u', 'icon' => 'fa-star', 'style' => 'text-yellow-500 bg-yellow-50 border-yellow-100'],
        ['slug' => 'tri-cuanmax', 'name' => 'Tri CuanMax', 'icon' => 'fa-percent', 'style' => 'text-purple-600 bg-purple-50 border-purple-100'],
        ['slug' => 'xl-axis-cuanku', 'name' => 'XL Cuanku', 'icon' => 'fa-gift', 'style' => 'text-blue-600 bg-blue-50 border-blue-100'],
        ['slug' => 'by-u', 'name' => 'by.U', 'icon' => 'fa-ghost', 'style' => 'text-orange-500 bg-orange-50 border-orange-100'],
    ];

    // --- ARRAY MENU PASCABAYAR ---
    $postpaidMenus = [
        ['slug' => 'pln-pascabayar', 'name' => 'PLN Pasca', 'icon' => 'fa-file-invoice-dollar', 'style' => 'text-yellow-600 bg-yellow-50 border-yellow-100'],
        ['slug' => 'pdam', 'name' => 'PDAM', 'icon' => 'fa-faucet', 'style' => 'text-cyan-600 bg-cyan-50 border-cyan-100'],
        ['slug' => 'bpjs-kesehatan', 'name' => 'BPJS Kes.', 'icon' => 'fa-heartbeat', 'style' => 'text-green-600 bg-green-50 border-green-100'],
        ['slug' => 'bpjs-ketenagakerjaan', 'name' => 'BPJS TK', 'icon' => 'fa-hard-hat', 'style' => 'text-green-700 bg-green-50 border-green-100'],
        ['slug' => 'hp-pascabayar', 'name' => 'HP Pasca', 'icon' => 'fa-mobile-screen', 'style' => 'text-blue-600 bg-blue-50 border-blue-100'],
        ['slug' => 'internet-pascabayar', 'name' => 'Internet', 'icon' => 'fa-network-wired', 'style' => 'text-indigo-600 bg-indigo-50 border-indigo-100'],
        ['slug' => 'tv-pascabayar', 'name' => 'TV Kabel', 'icon' => 'fa-satellite-dish', 'style' => 'text-pink-600 bg-pink-50 border-pink-100'],
        ['slug' => 'multifinance', 'name' => 'Cicilan', 'icon' => 'fa-money-bill-wave', 'style' => 'text-emerald-600 bg-emerald-50 border-emerald-100'],
        ['slug' => 'pbb', 'name' => 'Pajak PBB', 'icon' => 'fa-building', 'style' => 'text-gray-600 bg-gray-50 border-gray-200'],
        ['slug' => 'samsat', 'name' => 'SAMSAT', 'icon' => 'fa-car', 'style' => 'text-blue-700 bg-blue-50 border-blue-100'],
        ['slug' => 'gas-negara', 'name' => 'Gas Negara', 'icon' => 'fa-fire', 'style' => 'text-orange-600 bg-orange-50 border-orange-100'],
        ['slug' => 'pln-nontaglis', 'name' => 'PLN NonTag', 'icon' => 'fa-plug', 'style' => 'text-yellow-600 bg-yellow-50 border-yellow-100'],
    ];

    // --- LOGIKA LABEL INPUT DINAMIS ---
    $dynamicLabel = 'Nomor Pelanggan';
    $dynamicPlaceholder = 'Masukkan Nomor...';
    
    if (str_contains($currentSlug, 'game')) {
        $dynamicLabel = 'User ID / Zone ID';
        $dynamicPlaceholder = '12345678 (1234)';
    } elseif (str_contains($currentSlug, 'pulsa') || str_contains($currentSlug, 'data') || str_contains($currentSlug, 'paket') || str_contains($currentSlug, 'hp')) {
        $dynamicLabel = 'Nomor HP';
        $dynamicPlaceholder = '08xxxxxxxxxx';
    } elseif (str_contains($currentSlug, 'pln')) {
        $dynamicLabel = 'No. Meter / ID Pelanggan';
        $dynamicPlaceholder = '5123xxxxxxxx';
    }
@endphp

@section('title', $pageTitle)

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .animate-fade { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .loader { border: 3px solid #f3f3f3; border-radius: 50%; border-top: 3px solid #3b82f6; width: 20px; height: 20px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
@endpush

@section('content')
<div class="bg-gray-50 min-h-screen pb-20 font-sans">
    <div class="container mx-auto px-4 pt-6 relative z-10 max-w-7xl">
        
        {{-- HERO SECTION (BANNER) --}}
        <section class="mb-6">
            <div class="rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative group h-[180px] md:h-[320px]">
                <div class="swiper heroSwiper w-full h-full bg-gray-200">
                    <div class="swiper-wrapper">
                        @forelse($banners ?? [] as $banner)
                            <div class="swiper-slide"><img src="{{ asset('storage/' . $banner->image) }}" class="w-full h-full object-cover"></div>
                        @empty
                            <div class="swiper-slide"><img src="https://placehold.co/800x320/3b82f6/ffffff?text=Promo+Spesial" class="w-full h-full object-cover"></div>
                            <div class="swiper-slide"><img src="https://placehold.co/800x320/ec4899/ffffff?text=Diskon+Akhir+Tahun" class="w-full h-full object-cover"></div>
                        @endforelse
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </section>

        {{-- === KATEGORI MENU SECTION (REVISI SESUAI GAMBAR) === --}}
        <section class="mb-8" data-aos="fade-up">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                
                {{-- 1. TAB HEADER (Prabayar vs Pascabayar) --}}
                <div class="flex border-b border-gray-200">
                    <button onclick="switchTab('prepaid')" id="tab-prepaid" class="flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-blue-600 text-blue-600 transition hover:bg-gray-50 flex items-center justify-center gap-2">
                        <i class="fas fa-mobile-alt"></i> Prabayar / Topup
                    </button>
                    <button onclick="switchTab('postpaid')" id="tab-postpaid" class="flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition hover:bg-gray-50 flex items-center justify-center gap-2">
                        <i class="fas fa-file-invoice"></i> Pascabayar / Tagihan
                    </button>
                </div>

                <div class="p-6 md:p-8">
                    {{-- 2. KONTEN PRABAYAR (GRID 8 KOLOM) --}}
                    <div id="content-prepaid" class="animate-fade">
                        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-y-8 gap-x-4" id="grid-prepaid-wrapper">
                            @foreach($prepaidMenus as $index => $menu)
                                {{-- Item Menu --}}
                                <a href="{{ url('/etalase/ppob/digital/' . $menu['slug']) }}" 
                                   class="menu-item-prepaid group flex flex-col items-center gap-3 {{ $index >= 16 ? 'hidden' : '' }}">
                                    
                                    {{-- Icon Container (Rounded) --}}
                                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-2xl flex items-center justify-center transition border {{ $menu['style'] }} {{ $currentSlug == $menu['slug'] ? 'ring-2 ring-offset-2 ring-blue-400' : 'group-hover:scale-105' }}">
                                        <i class="fas {{ $menu['icon'] }} text-xl md:text-2xl"></i>
                                    </div>
                                    
                                    {{-- Text Label --}}
                                    <span class="text-[11px] md:text-xs font-medium text-gray-600 text-center leading-tight px-1">{{ $menu['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                        
                        {{-- Tombol Lihat Semua (Accordion) --}}
                        @if(count($prepaidMenus) > 16)
                            <div class="mt-8 text-center">
                                <button onclick="toggleExpand('prepaid')" id="btn-expand-prepaid" class="inline-flex items-center gap-2 px-6 py-2 bg-blue-50 text-blue-600 text-xs font-bold rounded-full hover:bg-blue-100 transition">
                                    <span>Lihat Semua Layanan</span> 
                                    <i class="fas fa-chevron-down text-[10px]"></i>
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- 3. KONTEN PASCABAYAR (GRID 8 KOLOM) --}}
                    <div id="content-postpaid" class="hidden animate-fade">
                        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-y-8 gap-x-4">
                            @foreach($postpaidMenus as $menu)
                                <a href="{{ url('/etalase/ppob/digital/' . $menu['slug']) }}" class="group flex flex-col items-center gap-3">
                                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-2xl flex items-center justify-center transition border {{ $menu['style'] }} {{ $currentSlug == $menu['slug'] ? 'ring-2 ring-offset-2 ring-blue-400' : 'group-hover:scale-105' }}">
                                        <i class="fas {{ $menu['icon'] }} text-xl md:text-2xl"></i>
                                    </div>
                                    <span class="text-[11px] md:text-xs font-medium text-gray-600 text-center leading-tight px-1">{{ $menu['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>
        </section>

        {{-- INPUT & TRANSACTION SECTION (TIDAK BERUBAH) --}}
        <section class="mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row gap-8">
                
                {{-- LEFT: INPUT FORM --}}
                <div class="w-full md:w-1/3">
                    <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center">
                        <span class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-2"><i class="fas {{ $isPostpaid ? 'fa-file-invoice' : 'fa-shopping-cart' }}"></i></span>
                        {{ $pageTitle }}
                    </h3>

                    <div class="mb-5">
                        <label class="block text-xs font-extrabold text-gray-500 uppercase tracking-wide mb-2">{{ $dynamicLabel }}</label>
                        <div class="relative group">
                            <input type="text" id="customer_no" 
                                class="w-full border border-gray-300 rounded-xl px-4 py-3.5 pl-12 font-bold text-lg text-gray-800 placeholder-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition shadow-sm" 
                                placeholder="{{ $dynamicPlaceholder }}">
                            
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-blue-500 transition">
                                <i class="fas fa-keyboard text-lg"></i>
                            </div>
                            
                            <div id="operator-badge" class="absolute right-4 top-1/2 transform -translate-y-1/2 hidden bg-gray-100 text-gray-600 text-[10px] font-bold px-2 py-1 rounded border border-gray-200 uppercase">
                                -
                            </div>
                        </div>
                    </div>

                    @if($isPostpaid)
                        <button onclick="cekTagihan()" id="btn-cek-tagihan" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 px-4 rounded-xl transition shadow-lg shadow-blue-100 flex justify-center items-center gap-2">
                            <span id="btn-text">Cek Tagihan</span>
                            <div id="loading-spinner" class="loader hidden !border-t-white"></div>
                        </button>
                        <div id="bill_error_msg" class="hidden mt-3 text-red-600 text-sm bg-red-50 p-2 rounded border border-red-100 text-center"></div>
                    @else
                        @if(str_contains($currentSlug, 'pln-token'))
                             <button onclick="cekPlnPrabayar()" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 rounded-xl mb-3">Cek Meteran</button>
                             <div id="pln_info" class="hidden text-sm bg-yellow-50 p-3 rounded border border-yellow-200 mb-3"></div>
                        @endif
                    @endif
                </div>

                {{-- RIGHT: RESULT / TABLE --}}
                <div class="w-full md:w-2/3 md:border-l border-gray-100 md:pl-8">
                    @if($isPostpaid)
                        {{-- UI HASIL PASCABAYAR --}}
                        <div class="h-full flex flex-col">
                            <div id="bill_empty" class="flex-1 flex flex-col items-center justify-center min-h-[200px] text-gray-400 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                                <i class="fas fa-search-dollar text-4xl mb-3 text-gray-300"></i>
                                <p class="text-sm">Masukkan ID Pelanggan untuk cek tagihan.</p>
                            </div>
                            <div id="bill_result" class="hidden animate-fade">
                                <div class="bg-blue-50 p-5 rounded-xl border border-blue-100 mb-5">
                                    <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                                        <div><p class="text-xs text-gray-500 uppercase font-bold">Nama</p><p class="font-bold text-gray-800 text-lg" id="bill_name">-</p></div>
                                        <div><p class="text-xs text-gray-500 uppercase font-bold">ID Pelanggan</p><p class="font-bold text-gray-800 font-mono" id="bill_id">-</p></div>
                                    </div>
                                    <div class="pt-4 border-t border-blue-200 flex justify-between items-end">
                                        <span class="text-sm font-medium text-gray-600">Total Tagihan</span>
                                        <span class="text-2xl font-extrabold text-blue-600" id="bill_amount">-</span>
                                    </div>
                                </div>
                                <button onclick="bayarTagihan()" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3.5 rounded-xl shadow-lg transition">Bayar Sekarang</button>
                            </div>
                        </div>
                    @else
                        {{-- UI HASIL PRABAYAR (LIST PRODUK) --}}
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Cari produk..." class="w-full py-3 pl-4 pr-10 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50 text-sm mb-4">
                            <i class="fas fa-search absolute right-4 top-3.5 text-gray-400"></i>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden min-h-[300px]">
                            <div class="overflow-y-auto max-h-[500px] custom-scrollbar">
                                <table class="w-full text-left">
                                    <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm">
                                        <tr><th class="p-4 text-xs font-bold text-gray-500 uppercase">Produk</th><th class="p-4 text-xs font-bold text-gray-500 uppercase text-right">Harga</th></tr>
                                    </thead>
                                    <tbody id="productTableBody" class="divide-y divide-gray-50">
                                        @forelse($products ?? [] as $product)
                                        <tr class="hover:bg-blue-50 cursor-pointer transition group" onclick="selectProduct('{{ $product->product_name }}', '{{ $product->sell_price }}', '{{ $product->buyer_sku_code }}')">
                                            <td class="p-4">
                                                <div class="font-bold text-gray-700 text-sm group-hover:text-blue-600">{{ $product->product_name }}</div>
                                                <div class="text-[10px] text-gray-400 mt-0.5">{{ $product->brand }} • {{ $product->buyer_sku_code }}</div>
                                            </td>
                                            <td class="p-4 text-right">
                                                <div class="font-bold text-blue-600">Rp{{ number_format($product->sell_price, 0, ',', '.') }}</div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="2" class="p-8 text-center text-gray-400 text-sm">Produk tidak tersedia.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>
</div>

{{-- MODAL KONFIRMASI (TIDAK BERUBAH) --}}
<div id="confirmModal" class="fixed inset-0 z-[999] hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 text-center">Konfirmasi Pembayaran</h3>
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 mb-4 space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Produk</span><span class="font-bold text-right w-2/3" id="modal_product">-</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Tujuan</span><span class="font-bold font-mono" id="modal_no">-</span></div>
                    <div class="flex justify-between border-t pt-2 mt-2"><span class="text-gray-500">Total</span><span class="font-extrabold text-blue-600 text-lg" id="modal_price">-</span></div>
                </div>
                <div class="flex gap-3">
                    <button onclick="closeModal()" class="flex-1 py-3 rounded-xl border border-gray-300 font-bold text-gray-600 hover:bg-gray-50">Batal</button>
                    <form action="{{ route('ppob.store') }}" method="POST" class="flex-1">
                        @csrf
                        <input type="hidden" name="buyer_sku_code" id="form_sku">
                        <input type="hidden" name="customer_no" id="form_no">
                        <button type="submit" class="w-full py-3 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg shadow-blue-200">Bayar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    // 1. Swiper Banner
    var swiper = new Swiper(".heroSwiper", { loop: true, autoplay: { delay: 4000 }, pagination: { el: ".swiper-pagination", clickable: true } });

    // 2. Logic Tabs (Prabayar vs Pascabayar) - SESUAI DESAIN BARU
    function switchTab(type) {
        const btnPre = document.getElementById('tab-prepaid');
        const btnPost = document.getElementById('tab-postpaid');
        const conPre = document.getElementById('content-prepaid');
        const conPost = document.getElementById('content-postpaid');

        if(type === 'prepaid') {
            btnPre.className = "flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-blue-600 text-blue-600 transition hover:bg-gray-50 flex items-center justify-center gap-2";
            btnPost.className = "flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition hover:bg-gray-50 flex items-center justify-center gap-2";
            
            conPre.classList.remove('hidden');
            conPost.classList.add('hidden');
        } else {
            btnPost.className = "flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-blue-600 text-blue-600 transition hover:bg-gray-50 flex items-center justify-center gap-2";
            btnPre.className = "flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition hover:bg-gray-50 flex items-center justify-center gap-2";

            conPost.classList.remove('hidden');
            conPre.classList.add('hidden');
        }
    }

    // 3. Logic "Lihat Semua" (Expand Grid)
    function toggleExpand(type) {
        const wrapper = document.getElementById('grid-' + type + '-wrapper');
        const btn = document.getElementById('btn-expand-' + type);
        const items = wrapper.querySelectorAll('.menu-item-' + type);
        const isExpanded = btn.getAttribute('data-expanded') === 'true';

        items.forEach((item, index) => {
            if (index >= 16) {
                if (isExpanded) item.classList.add('hidden');
                else item.classList.remove('hidden');
            }
        });

        if (isExpanded) {
            btn.innerHTML = '<span>Lihat Semua Layanan</span> <i class="fas fa-chevron-down text-[10px]"></i>';
            btn.setAttribute('data-expanded', 'false');
        } else {
            btn.innerHTML = '<span>Sembunyikan</span> <i class="fas fa-chevron-up text-[10px]"></i>';
            btn.setAttribute('data-expanded', 'true');
        }
    }

    // Default: Set active tab berdasarkan halaman (Jika halaman pascabayar, buka tab pascabayar)
    @if($isPostpaid) switchTab('postpaid'); @endif

    // 4. Input & Search Logic (TIDAK BERUBAH)
    const inputNo = document.getElementById('customer_no');
    const tableRows = document.querySelectorAll('#productTableBody tr');
    const searchInput = document.getElementById('searchInput');

    if(inputNo) {
        inputNo.addEventListener('input', function() {
            const val = this.value;
            const badge = document.getElementById('operator-badge');
            let op = '';
            
            if(/^08(11|12|13|21|22|23|51|52|53)/.test(val)) op = 'TELKOMSEL';
            else if(/^08(14|15|16|55|56|57|58)/.test(val)) op = 'INDOSAT';
            else if(/^08(17|18|19|59|77|78)/.test(val)) op = 'XL';
            else if(/^08(31|32|33|38)/.test(val)) op = 'AXIS';
            else if(/^08(95|96|97|98|99)/.test(val)) op = 'TRI';
            else if(/^08(81|82|83|84|85|86|87|88|89)/.test(val)) op = 'SMARTFREN';

            if(op && badge) {
                badge.innerText = op;
                badge.classList.remove('hidden');
                if(searchInput) {
                    searchInput.value = op;
                    filterTable(op);
                }
            } else if(badge) {
                badge.classList.add('hidden');
                if(val.length < 4 && searchInput) { 
                    searchInput.value = ''; 
                    filterTable(''); 
                }
            }
        });
    }

    if(searchInput) {
        searchInput.addEventListener('keyup', (e) => filterTable(e.target.value));
    }

    function filterTable(query) {
        const lower = query.toLowerCase();
        tableRows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(lower) ? '' : 'none';
        });
    }

    // 5. Cek Tagihan Pascabayar
    function cekTagihan() {
        const no = inputNo.value;
        if(no.length < 5) { alert('Masukkan Nomor Pelanggan!'); return; }
        
        const btn = document.getElementById('btn-cek-tagihan');
        const spinner = document.getElementById('loading-spinner');
        const txt = document.getElementById('btn-text');
        
        btn.disabled = true; spinner.classList.remove('hidden'); txt.innerText = 'Mengecek...';
        document.getElementById('bill_error_msg').classList.add('hidden');

        fetch('{{ route("ppob.check.bill") }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify({ customer_no: no, sku: '{{ $currentSlug }}' })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false; spinner.classList.add('hidden'); txt.innerText = 'Cek Tagihan';
            if(data.status === 'success') {
                document.getElementById('bill_empty').classList.add('hidden');
                document.getElementById('bill_result').classList.remove('hidden');
                document.getElementById('bill_name').innerText = data.customer_name;
                document.getElementById('bill_id').innerText = data.customer_no;
                document.getElementById('bill_amount').innerText = 'Rp ' + parseInt(data.amount).toLocaleString('id-ID');
            } else {
                document.getElementById('bill_error_msg').innerText = data.message;
                document.getElementById('bill_error_msg').classList.remove('hidden');
            }
        })
        .catch(() => {
            btn.disabled = false; spinner.classList.add('hidden'); txt.innerText = 'Cek Tagihan';
            alert('Gagal koneksi server.');
        });
    }

    // 6. Modal Logic
    function selectProduct(name, price, sku) {
        const no = inputNo.value;
        if(no.length < 4) { alert('Masukkan nomor tujuan dulu!'); inputNo.focus(); return; }
        document.getElementById('modal_product').innerText = name;
        document.getElementById('modal_no').innerText = no;
        document.getElementById('modal_price').innerText = 'Rp ' + parseInt(price).toLocaleString('id-ID');
        document.getElementById('form_sku').value = sku;
        document.getElementById('form_no').value = no;
        document.getElementById('confirmModal').classList.remove('hidden');
    }
    function closeModal() { document.getElementById('confirmModal').classList.add('hidden'); }
</script>
@endpush