@extends('layouts.marketplace')

{{-- 1. LOGIC PHP: Pastikan Variable Aman & Deteksi Otomatis Postpaid --}}
@php
    // Cegah error undefined variable
    $pageInfo = $pageInfo ?? [];
    $currentSlug = $pageInfo['slug'] ?? 'pulsa'; // Default pulsa
    
    // Deteksi apakah ini layanan Pascabayar (Tagihan)
    // Cek dari controller ATAU cek manual berdasarkan slug URL
    $isPostpaid = ($pageInfo['is_postpaid'] ?? false) || 
                  in_array($currentSlug, ['pln-pascabayar', 'pdam', 'bpjs', 'gas', 'pbb', 'internet-pasca']);
    
    $pageTitle = $pageInfo['title'] ?? ucfirst(str_replace('-', ' ', $currentSlug));
@endphp

@section('title', $pageTitle)

@push('styles')
    {{-- CSS Swiper (Slider) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        /* Utility */
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Custom Scrollbar (Desktop) */
        .custom-scrollbar::-webkit-scrollbar { width: 8px; height: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .swiper-pagination-bullet-active { background-color: #ef4444 !important; width: 20px; border-radius: 5px; }
        
        /* Navigasi Slider */
        .swiper-button-next, .swiper-button-prev { color: white; background-color: rgba(0,0,0,0.3); width: 40px; height: 40px; border-radius: 50%; transition: all 0.3s; }
        .swiper-button-next:hover, .swiper-button-prev:hover { background-color: rgba(0,0,0,0.6); }
        .swiper-button-next:after, .swiper-button-prev:after { font-size: 16px; font-weight: bold; }

        /* Loader Spinner */
        .loader { border: 3px solid #f3f3f3; border-radius: 50%; border-top: 3px solid #ef4444; width: 20px; height: 20px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
@endpush

@section('content')
<div class="bg-gray-50 min-h-screen pb-20">
    
    <div class="container mx-auto px-4 pt-6 relative z-10 max-w-7xl">
        
        {{-- ================================================= --}}
        {{-- 1. HERO SECTION (BANNER)                          --}}
        {{-- ================================================= --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
            <div class="lg:col-span-2 rounded-2xl shadow-lg overflow-hidden relative group h-[200px] md:h-[350px] lg:h-[420px]">
                <div class="swiper heroSwiper w-full h-full">
                    <div class="swiper-wrapper">
                        @forelse($banners as $banner)
                            <div class="swiper-slide"><img src="{{ asset('public/storage/' . $banner->image) }}" class="w-full h-full object-fill" alt="Promo"></div>
                        @empty
                            <div class="swiper-slide"><img src="https://placehold.co/800x420/ee4d2d/ffffff?text=Promo+Sancaka+1" class="w-full h-full object-fill"></div>
                        @endforelse
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next opacity-0 group-hover:opacity-100"></div>
                    <div class="swiper-button-prev opacity-0 group-hover:opacity-100"></div>
                </div>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-1 lg:grid-rows-2 gap-4 h-auto lg:h-[420px]">
                <div class="rounded-2xl shadow-lg overflow-hidden h-[100px] md:h-[170px] lg:h-full w-full">
                    <img src="{{ isset($settings['banner_2']) ? asset('public/storage/' . $settings['banner_2']) : 'https://placehold.co/400x200/fbbf24/ffffff?text=Promo+1' }}" class="w-full h-full object-fill hover:scale-105 transition duration-500">
                </div>
                <div class="rounded-2xl shadow-lg overflow-hidden h-[100px] md:h-[170px] lg:h-full w-full">
                    <img src="{{ isset($settings['banner_3']) ? asset('public/storage/' . $settings['banner_3']) : 'https://placehold.co/400x200/10b981/ffffff?text=Promo+2' }}" class="w-full h-full object-fill hover:scale-105 transition duration-500">
                </div>
            </div>
        </section>

        {{-- ================================================= --}}
        {{-- 2. MENU GRID                                      --}}
        {{-- ================================================= --}}
        <section class="mb-10" data-aos="fade-up">
            <div class="bg-white p-6 rounded-2xl shadow-md border-t-4 border-red-500">
                <h2 class="text-lg font-bold mb-5 text-gray-800 flex items-center">
                    <i class="fas fa-mobile-alt text-blue-500 mr-2"></i> Layanan Digital
                </h2>
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
                    <a href="{{ url('/etalase/ppob/digital/pulsa') }}" class="ppob-icon flex flex-col items-center p-3 border rounded-xl hover:shadow-md hover:border-blue-400 transition bg-blue-50"><i class="fas fa-mobile-screen-button text-2xl text-blue-600 mb-1"></i><span class="text-xs font-semibold text-gray-700 text-center">Pulsa</span></a>
                    <a href="{{ url('/etalase/ppob/digital/data') }}" class="ppob-icon flex flex-col items-center p-3 border rounded-xl hover:shadow-md hover:border-green-400 transition bg-green-50"><i class="fas fa-wifi text-2xl text-green-600 mb-1"></i><span class="text-xs font-semibold text-gray-700 text-center">Data</span></a>
                    <a href="{{ url('/etalase/ppob/digital/pln-token') }}" class="ppob-icon flex flex-col items-center p-3 border rounded-xl hover:shadow-md hover:border-yellow-400 transition bg-yellow-50"><i class="fas fa-bolt text-2xl text-yellow-500 mb-1"></i><span class="text-xs font-semibold text-gray-700 text-center">Token PLN</span></a>
                    <a href="{{ url('/etalase/ppob/digital/pln-pascabayar') }}" class="ppob-icon flex flex-col items-center p-3 border rounded-xl hover:shadow-md hover:border-orange-400 transition bg-orange-50"><i class="fas fa-file-invoice-dollar text-2xl text-orange-500 mb-1"></i><span class="text-xs font-semibold text-gray-700 text-center">PLN Pasca</span></a>
                    <a href="{{ url('/etalase/ppob/digital/pdam') }}" class="ppob-icon flex flex-col items-center p-3 border rounded-xl hover:shadow-md hover:border-cyan-400 transition bg-cyan-50"><i class="fas fa-faucet text-2xl text-cyan-600 mb-1"></i><span class="text-xs font-semibold text-gray-700 text-center">PDAM</span></a>
                    <a href="{{ url('/etalase/ppob/digital/e-money') }}" class="ppob-icon flex flex-col items-center p-3 border rounded-xl hover:shadow-md hover:border-purple-400 transition bg-purple-50"><i class="fas fa-wallet text-2xl text-purple-600 mb-1"></i><span class="text-xs font-semibold text-gray-700 text-center">E-Wallet</span></a>
                    <a href="{{ url('/etalase/ppob/digital/voucher-game') }}" class="ppob-icon flex flex-col items-center p-3 border rounded-xl hover:shadow-md hover:border-red-400 transition bg-red-50"><i class="fas fa-gamepad text-2xl text-red-600 mb-1"></i><span class="text-xs font-semibold text-gray-700 text-center">Games</span></a>
                    <a href="{{ url('/etalase/ppob/digital/streaming') }}" class="ppob-icon flex flex-col items-center p-3 border rounded-xl hover:shadow-md hover:border-pink-400 transition bg-pink-50"><i class="fas fa-tv text-2xl text-pink-600 mb-1"></i><span class="text-xs font-semibold text-gray-700 text-center">TV Kabel</span></a>
                </div>
            </div>
        </section>

        {{-- ================================================= --}}
        {{-- 3. TRANSACTION / INPUT SECTION (RESTORED)         --}}
        {{-- ================================================= --}}
        <section class="mb-8" id="transaction-section">
            <div class="bg-white rounded-2xl shadow-md p-6 border-l-4 border-blue-600 flex flex-col md:flex-row gap-6">
                {{-- Input Area --}}
                <div class="w-full md:w-1/3">
                    <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center">
                        <i class="fas {{ $pageInfo['icon'] ?? 'fa-edit' }} text-blue-600 mr-2"></i> 
                        {{ $pageTitle }}
                    </h3>
                    
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">{{ $pageInfo['input_label'] ?? 'Nomor Tujuan / ID Pelanggan' }}</label>
                        <div class="relative group">
                            <input type="text" id="customer_no" 
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 pl-12 font-bold text-lg text-gray-700 focus:ring-2 focus:ring-blue-500 outline-none transition" 
                                placeholder="{{ $pageInfo['input_place'] ?? 'Masukkan Nomor...' }}">
                            {{-- Icon Prefix: Diberi ID untuk diganti JS --}}
                            <div id="prefix-icon-container" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-blue-500 flex items-center justify-center w-6 h-6">
                                <i class="fas fa-keyboard text-lg"></i>
                            </div>
                            {{-- Badge Nama Operator (Hidden Default) --}}
                            <div id="operator-badge" class="absolute right-4 top-1/2 transform -translate-y-1/2 hidden bg-blue-100 text-blue-700 text-xs font-bold px-2 py-1 rounded">
                                -
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Khusus: Cek PLN Token (Prabayar) --}}
                    @if($currentSlug == 'pln-token')
                        <button onclick="cekPlnPrabayar()" id="btn-cek-pln" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 px-4 rounded-xl transition shadow flex items-center justify-center gap-2 mb-3">
                            <i class="fas fa-search"></i> Cek Nama Pelanggan
                        </button>
                        <div id="pln_info" class="hidden bg-yellow-50 p-3 rounded-lg border border-yellow-200 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Nama:</span><span class="font-bold" id="pln_name">-</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Daya:</span><span class="font-bold" id="pln_power">-</span></div>
                        </div>
                    @endif

                    {{-- Tombol Khusus: Cek Tagihan (PASCABAYAR: PLN Pasca, PDAM, BPJS, dll) --}}
                    {{-- Kita menggunakan variabel $isPostpaid yang sudah kita hitung di atas --}}
                    @if($isPostpaid)
                        <button onclick="cekTagihan()" id="btn-cek-tagihan" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition flex justify-center items-center gap-2 shadow-lg">
                            <span id="btn-text">Cek Tagihan</span>
                            <div id="loading-spinner" class="loader hidden !border-t-white"></div>
                        </button>
                        <div id="bill_error_msg" class="hidden mt-3 text-red-600 text-sm text-center bg-red-50 p-2 rounded border border-red-200"></div>
                    @endif
                </div>

                {{-- Result Area (Untuk Pascabayar) --}}
                @if($isPostpaid)
                    <div class="w-full md:w-2/3 border-t md:border-t-0 md:border-l border-gray-100 md:pl-6 pt-6 md:pt-0">
                        <h4 class="font-bold text-gray-700 mb-4">Rincian Tagihan</h4>
                        
                        {{-- State Kosong --}}
                        <div id="bill_empty" class="flex flex-col items-center justify-center h-40 text-gray-400 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                            <i class="fas fa-receipt text-3xl mb-2"></i>
                            <p class="text-sm">Silakan masukkan ID Pelanggan dan klik Cek Tagihan</p>
                        </div>

                        {{-- State Hasil --}}
                        <div id="bill_result" class="hidden animate-fade-in">
                            <div class="bg-blue-50 p-5 rounded-xl border border-blue-100 mb-4">
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div><p class="text-xs text-gray-500 uppercase">Nama</p><p class="font-bold text-gray-800 text-lg" id="bill_name">-</p></div>
                                    <div><p class="text-xs text-gray-500 uppercase">ID Pelanggan</p><p class="font-bold text-gray-800 text-lg" id="bill_id">-</p></div>
                                </div>
                                <div class="mt-4 pt-4 border-t border-blue-200 flex justify-between items-end">
                                    <span class="text-sm text-gray-600">Total Tagihan</span>
                                    <span class="text-2xl font-extrabold text-blue-600" id="bill_amount">-</span>
                                </div>
                            </div>
                            <button onclick="bayarTagihan()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl shadow-lg transition">
                                <i class="fas fa-check-circle mr-2"></i> Bayar Sekarang
                            </button>
                        </div>
                    </div>
                @else
                    {{-- Banner/Info Area (Untuk Prabayar) --}}
                    <div class="hidden md:flex w-2/3 items-center justify-center bg-blue-50 rounded-xl p-6 text-center text-blue-800">
                        <div>
                            <i class="fas fa-info-circle text-2xl mb-2"></i>
                            <p class="font-medium">Pilih produk di bawah ini untuk melakukan pembelian.</p>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        {{-- ================================================= --}}
        {{-- 4. FILTER & PRODUCT TABLE (PRABAYAR ONLY)         --}}
        {{-- ================================================= --}}
        @if(!$isPostpaid)
            
            {{-- Filter Mobile (Dropdown) --}}
            <div class="lg:hidden mb-6 sticky top-4 z-30" id="mobileDropdownContainer">
                <div class="relative">
                    <button onclick="toggleMobileDropdown()" class="w-full bg-white rounded-xl shadow-md border border-gray-100 px-4 py-3.5 flex items-center justify-between text-left">
                        <span id="mobileCategoryLabel" class="text-sm font-bold text-gray-700 uppercase">SEMUA KATEGORI</span>
                        <i class="fas fa-chevron-down text-gray-500 text-xs transition-transform duration-200" id="dropdownArrow"></i>
                    </button>
                    <div id="mobileCategoryList" class="hidden absolute w-full z-50 bg-white shadow-xl rounded-xl mt-2 border border-gray-100 overflow-hidden">
                        <div class="max-h-60 overflow-y-auto custom-scrollbar">
                            <div onclick="filterCategory('all')" class="px-4 py-3 border-b border-gray-50 hover:bg-blue-50 cursor-pointer text-sm font-bold text-gray-600">SEMUA KATEGORI</div>
                            @foreach($categories as $cat)
                                <div onclick="filterCategory('{{ $cat->slug ?? $cat }}')" class="px-4 py-3 border-b border-gray-50 hover:bg-blue-50 cursor-pointer text-sm font-bold text-gray-600 uppercase">{{ $cat->name ?? $cat }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter Desktop (Horizontal Scroll) --}}
            <div class="hidden lg:flex bg-white p-3 rounded-2xl shadow-sm mb-6 overflow-x-auto whitespace-nowrap custom-scrollbar gap-3 border border-gray-100 sticky top-4 z-30 pb-4">
                <button onclick="filterCategory('all')" data-cat="all" class="cat-btn active px-6 py-2.5 rounded-xl font-bold text-sm transition bg-blue-600 text-white shadow-md transform hover:scale-105">SEMUA</button>
                @foreach($categories as $cat)
                    <button onclick="filterCategory('{{ $cat->slug ?? $cat }}')" data-cat="{{ $cat->slug ?? $cat }}" class="cat-btn px-6 py-2.5 rounded-xl font-bold text-sm transition bg-gray-50 text-gray-600 hover:bg-blue-50 hover:text-blue-600 border border-gray-200">{{ strtoupper($cat->name ?? $cat) }}</button>
                @endforeach
            </div>

            {{-- Search Bar Table --}}
            <div class="mb-4 relative">
                <input type="text" id="searchInput" placeholder="Cari nama produk..." class="w-full py-3 pl-10 pr-4 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>

            {{-- Product Table --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 min-h-[400px]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-bold tracking-wider">
                            <tr>
                                <th class="p-5 border-b">Produk</th>
                                <th class="p-5 border-b">Keterangan</th>
                                <th class="p-5 border-b text-center hidden sm:table-cell">Status</th>
                                <th class="p-5 border-b text-right">Harga</th>
                            </tr>
                        </thead>
                        <tbody id="productTableBody" class="text-sm divide-y divide-gray-100">
                            @forelse($products as $product)
                            <tr class="product-row hover:bg-blue-50/50 transition duration-150 cursor-pointer" 
                                data-category="{{ $product->category }}" 
                                data-name="{{ strtolower($product->product_name . ' ' . $product->brand . ' ' . $product->buyer_sku_code) }}"
                                onclick="selectProduct('{{ $product->product_name }}', '{{ $product->sell_price }}', '{{ $product->buyer_sku_code }}')">
                                <td class="p-5">
                                    <div class="flex items-center gap-4">
                                        <div class="h-12 w-12 flex-shrink-0 relative bg-white rounded-xl p-1 shadow-sm border border-gray-100">
                                            <img src="{{ get_operator_logo($product->brand) }}" alt="{{ $product->brand }}" class="h-full w-full object-contain" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <span class="hidden h-full w-full items-center justify-center bg-gray-100 text-gray-500 text-[10px] font-bold rounded-lg uppercase">{{ substr($product->brand, 0, 3) }}</span>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-800 text-sm md:text-base">{{ $product->brand }}</p>
                                            <p class="text-xs text-gray-400 uppercase tracking-wide">{{ $product->category }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-5">
                                    <div class="font-medium text-gray-700 line-clamp-2">{{ $product->product_name }}</div>
                                    <div class="text-xs text-gray-400 mt-1 font-mono bg-gray-100 inline-block px-2 py-0.5 rounded">SKU: {{ $product->buyer_sku_code }}</div>
                                </td>
                                <td class="p-5 text-center hidden sm:table-cell">
                                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200">Tersedia</span>
                                </td>
                                <td class="p-5 text-right">
                                    <div class="font-extrabold text-blue-600 text-lg">Rp{{ number_format($product->sell_price, 0, ',', '.') }}</div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="p-10 text-center text-gray-400">Belum ada produk.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="hidden py-20 text-center">
                    <h3 class="text-lg font-bold text-gray-700">Produk tidak ditemukan</h3>
                </div>
            </div>
        @endif

        {{-- Login Prompt --}}
        @if(!Auth::check())
        <div class="mt-10 text-center pb-8">
            <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-bold rounded-full text-white bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 shadow-lg hover:shadow-xl transition transform hover:-translate-y-1">
                <i class="fas fa-lock mr-2"></i> Login untuk Transaksi
            </a>
        </div>
        @endif
    </div>
</div>

{{-- MODAL KONFIRMASI (PRABAYAR) --}}
<div id="confirmModal" class="fixed inset-0 z-[999] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full">
            <div class="bg-white p-6">
                <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4">Konfirmasi Pembayaran</h3>
                <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 mb-4">
                    <p class="text-xs text-blue-500 uppercase font-bold mb-1">Produk</p>
                    <p class="text-gray-800 font-bold text-lg leading-tight" id="modal_product">-</p>
                </div>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between border-b border-gray-100 pb-2"><span class="text-gray-500">Nomor Tujuan</span><span class="font-bold text-gray-800 font-mono text-base" id="modal_no">-</span></div>
                    <div class="flex justify-between items-center pt-2"><span class="text-gray-500">Total Harga</span><span class="font-extrabold text-blue-600 text-2xl" id="modal_price">-</span></div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <button type="button" onclick="closeModal()" class="w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-3 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:w-auto sm:text-sm">Batal</button>
                <form action="{{ route('ppob.store') }}" method="POST" id="checkoutForm" class="w-full sm:w-auto">
                    @csrf
                    <input type="hidden" name="buyer_sku_code" id="form_sku">
                    <input type="hidden" name="customer_no" id="form_no">
                    <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-lg shadow-blue-200 px-6 py-3 bg-blue-600 text-base font-bold text-white hover:bg-blue-700 sm:text-sm">Bayar Sekarang</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
    // 1. Slider Init
    var swiper = new Swiper(".heroSwiper", { loop: true, effect: "fade", autoplay: { delay: 4000 }, pagination: { el: ".swiper-pagination", clickable: true }, navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" } });

    // 2. Transaksi Logic Variables
    const inputNo = document.getElementById('customer_no');
    
    // --- SMART DETECTION LOGIC ---
    if(inputNo) {
        inputNo.addEventListener('input', function() {
            const val = this.value;
            const operator = detectOperator(val);
            const searchInput = document.getElementById('searchInput');
            const iconContainer = document.getElementById('prefix-icon-container');
            const operatorBadge = document.getElementById('operator-badge');

            if(operator) {
                // 1. Auto Filter Search (Only if not in Postpaid Page like PLN Pasca)
                if(searchInput && operator !== 'PLN' && !{{ $isPostpaid ? 'true' : 'false' }}) {
                    searchInput.value = operator;
                    filterTable(); // Trigger filter produk
                }

                // 2. Visual Feedback (Icon/Badge)
                if(operatorBadge) {
                    operatorBadge.innerText = operator.toUpperCase();
                    operatorBadge.classList.remove('hidden');
                }
                
                // Ubah Icon
                if(iconContainer) {
                    iconContainer.innerHTML = '<i class="fas fa-check-circle text-green-500 text-lg"></i>';
                }

            } else {
                // Reset jika tidak terdeteksi atau kosong
                if(val.length < 4 && searchInput && !{{ $isPostpaid ? 'true' : 'false' }}) {
                    searchInput.value = '';
                    filterTable();
                }
                if(operatorBadge) operatorBadge.classList.add('hidden');
                if(iconContainer) iconContainer.innerHTML = '<i class="fas fa-keyboard text-lg"></i>';
            }
        });
    }

    function detectOperator(number) {
        if (!number || number.length < 4) return null;

        const prefix = number.substring(0, 4);
        
        // TELKOMSEL
        if (/^08(11|12|13|21|22|23|51|52|53)/.test(prefix)) return 'telkomsel';
        // INDOSAT
        if (/^08(14|15|16|55|56|57|58)/.test(prefix)) return 'indosat';
        // XL
        if (/^08(17|18|19|59|77|78)/.test(prefix)) return 'xl';
        // AXIS
        if (/^08(31|32|33|38)/.test(prefix)) return 'axis';
        // TRI (3)
        if (/^08(95|96|97|98|99)/.test(prefix)) return 'tri';
        // SMARTFREN
        if (/^08(81|82|83|84|85|86|87|88|89)/.test(prefix)) return 'smartfren';
        // DETEKSI PLN
        if (!number.startsWith('08') && number.length >= 11) return 'PLN';

        return null;
    }

    // --- LOGIKA FILTER KATEGORI (DROPDOWN & SCROLL) ---
    const searchInput = document.getElementById('searchInput');
    const rows = document.getElementById('productTableBody') ? document.getElementById('productTableBody').getElementsByTagName('tr') : [];
    const emptyState = document.getElementById('emptyState');
    let currentCategory = 'all';

    if(searchInput) searchInput.addEventListener('keyup', filterTable);

    function toggleMobileDropdown() {
        const list = document.getElementById('mobileCategoryList');
        const arrow = document.getElementById('dropdownArrow');
        if (list.classList.contains('hidden')) { list.classList.remove('hidden'); arrow.classList.add('rotate-180'); }
        else { list.classList.add('hidden'); arrow.classList.remove('rotate-180'); }
    }

    document.addEventListener('click', function(e) {
        const dropdownContainer = document.getElementById('mobileDropdownContainer');
        if (dropdownContainer && !dropdownContainer.contains(e.target)) {
            document.getElementById('mobileCategoryList').classList.add('hidden');
            document.getElementById('dropdownArrow').classList.remove('rotate-180');
        }
    });

    function filterCategory(category) {
        currentCategory = category;
        // Desktop
        document.querySelectorAll('.cat-btn').forEach(btn => {
            btn.classList.remove('bg-blue-600', 'text-white', 'shadow-md', 'transform', 'scale-105');
            btn.classList.add('bg-gray-50', 'text-gray-600');
            if (btn.getAttribute('data-cat') === category) {
                btn.classList.remove('bg-gray-50', 'text-gray-600');
                btn.classList.add('bg-blue-600', 'text-white', 'shadow-md', 'transform', 'scale-105');
            }
        });
        // Mobile Label
        const label = document.getElementById('mobileCategoryLabel');
        if(label) label.innerText = (category === 'all') ? 'SEMUA KATEGORI' : category.toUpperCase();
        
        document.getElementById('mobileCategoryList').classList.add('hidden');
        filterTable();
    }

    function filterTable() {
        const filter = searchInput ? searchInput.value.toLowerCase() : '';
        let visibleCount = 0;
        for (let row of rows) {
            const name = row.getAttribute('data-name');
            const category = row.getAttribute('data-category');
            const matchesSearch = name.includes(filter);
            const matchesCategory = currentCategory === 'all' || category === currentCategory;
            if (matchesSearch && matchesCategory) { row.style.display = ""; visibleCount++; } else { row.style.display = "none"; }
        }
        if(emptyState) emptyState.classList.toggle('hidden', visibleCount > 0);
    }

    // --- LOGIKA TRANSAKSI (MODAL, CEK TAGIHAN, CEK PLN) ---
    
    // Modal Prabayar
    function selectProduct(name, price, sku) {
        const no = inputNo.value;
        if(no.length < 5) { 
            alert("Mohon masukkan nomor tujuan / ID Pelanggan terlebih dahulu."); 
            inputNo.focus(); 
            return; 
        }
        document.getElementById('modal_no').innerText = no;
        document.getElementById('modal_product').innerText = name;
        document.getElementById('modal_price').innerText = 'Rp ' + parseInt(price).toLocaleString('id-ID');
        document.getElementById('form_sku').value = sku;
        document.getElementById('form_no').value = no;
        document.getElementById('confirmModal').classList.remove('hidden');
    }
    function closeModal() { document.getElementById('confirmModal').classList.add('hidden'); }

    // Logic Pascabayar
    let inquiryRefId = null;
    function cekTagihan() {
        const no = inputNo.value;
        if(no.length < 5) { alert("Masukkan ID Pelanggan!"); return; }

        const btn = document.getElementById('btn-cek-tagihan');
        const spinner = document.getElementById('loading-spinner');
        const text = document.getElementById('btn-text');
        const resultDiv = document.getElementById('bill_result');
        const emptyDiv = document.getElementById('bill_empty');
        const errorMsg = document.getElementById('bill_error_msg');

        btn.disabled = true; spinner.classList.remove('hidden'); text.innerText = "Mengecek...";
        resultDiv.classList.add('hidden'); emptyDiv.classList.add('hidden'); errorMsg.classList.add('hidden');

        // AUTO MAP SLUG -> SKU (Agar Dinamis untuk BPJS, PDAM, dll)
        const currentSlug = '{{ $currentSlug }}';
        let skuPasca = 'pln'; // Default

        if (currentSlug.includes('pdam')) skuPasca = 'pdam';
        else if (currentSlug.includes('bpjs')) skuPasca = 'bpjs';
        else if (currentSlug.includes('gas')) skuPasca = 'gas';
        else if (currentSlug === 'pln-pascabayar') skuPasca = 'pln';
        
        fetch('{{ route("ppob.check.bill") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ customer_no: no, sku: skuPasca })
        })
        .then(res => {
            if(!res.ok) throw new Error("Server Error");
            return res.json();
        })
        .then(data => {
            btn.disabled = false; spinner.classList.add('hidden'); text.innerText = "Cek Tagihan";
            if(data.status === 'success') {
                document.getElementById('bill_name').innerText = data.customer_name;
                document.getElementById('bill_id').innerText = data.customer_no;
                document.getElementById('bill_amount').innerText = 'Rp ' + parseInt(data.amount).toLocaleString('id-ID');
                inquiryRefId = data.ref_id;
                resultDiv.classList.remove('hidden');
            } else {
                errorMsg.innerText = data.message;
                errorMsg.classList.remove('hidden');
                emptyDiv.classList.remove('hidden');
            }
        })
        .catch(err => {
            btn.disabled = false; spinner.classList.add('hidden'); text.innerText = "Cek Tagihan";
            errorMsg.innerText = "Gagal terhubung ke server. Pastikan koneksi internet stabil.";
            errorMsg.classList.remove('hidden');
            console.error(err);
        });
    }

    function bayarTagihan() {
        if(!inquiryRefId) return;
        // Redirect ke login jika belum login, di handle middleware auth di controller store
        alert("Silakan login untuk melanjutkan.");
        window.location.href = "{{ route('login') }}";
    }

    // Logic Cek PLN Prabayar
    function cekPlnPrabayar() {
        const no = inputNo.value;
        if(no.length < 10) { alert("Masukkan Nomor Meter!"); return; }
        const btn = document.getElementById('btn-cek-pln');
        const infoBox = document.getElementById('pln_info');
        const oriText = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengecek...'; btn.disabled = true; infoBox.classList.add('hidden');

        fetch('{{ route("ppob.check.pln.prabayar") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ customer_no: no })
        })
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = oriText; btn.disabled = false;
            if(data.status === 'success') {
                document.getElementById('pln_name').innerText = data.name;
                document.getElementById('pln_power').innerText = data.segment_power;
                infoBox.classList.remove('hidden');
            } else { alert(data.message); }
        })
        .catch(err => {
            btn.innerHTML = oriText; btn.disabled = false;
            alert("Gagal koneksi server.");
        });
    }
</script>
@endpush