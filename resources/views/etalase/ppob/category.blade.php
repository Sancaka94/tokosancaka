@extends('layouts.marketplace')

@section('title', $pageInfo['title'] ?? 'Layanan Digital')

@push('styles')
    {{-- Jika layout marketplace Anda belum ada Tailwind, un-comment baris bawah ini --}}
    {{-- <script src="https://cdn.tailwindcss.com"></script> --}}
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <style>
        .brand-radio:checked + div { 
            border-color: #ef4444; /* Merah Sancaka */
            background-color: #fef2f2; 
            color: #b91c1c; 
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2);
        }
        .product-item:hover { 
            transform: translateY(-4px); 
            border-color: #ef4444;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); 
        }
        /* Loading Spinner */
        .loader { border: 3px solid #f3f3f3; border-radius: 50%; border-top: 3px solid #ef4444; width: 20px; height: 20px; -webkit-animation: spin 1s linear infinite; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        /* Custom Scrollbar for Brand Filter */
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

        /* Styling Navigasi Custom agar tidak menabrak konten */
    .swiper-button-next::after, .swiper-button-prev::after { font-size: 18px; font-weight: bold; }
    .flash-sale-next, .flash-sale-prev, .category-next, .category-prev {
        width: 35px; height: 35px; background: white; border-radius: 50%;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1); color: #333;
    }
    .flash-sale-next:after, .flash-sale-prev:after { font-size: 14px; }
    </style>
@endpush

@section('content')

<section class="grid grid-cols-1 lg:grid-cols-3 gap-2 mb-4">
        
        <div class="lg:col-span-2 rounded shadow-sm overflow-hidden h-full sm:h-full md:h-full w-full">
            <div class="swiper heroSwiper w-full h-full">
                <div class="swiper-wrapper">
                    @forelse($banners as $banner)
                        <div class="swiper-slide">
                            <img src="{{ asset('public/storage/' . $banner->image) }}" class="w-full h-full object-cover" alt="Banner">
                        </div>
                    @empty
                        <div class="swiper-slide">
                            <img src="https://placehold.co/800x400/ee4d2d/ffffff?text=Sancaka+Promo" class="w-full h-full object-cover">
                        </div>
                    @endforelse
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-1 lg:grid-rows-2 gap-2 h-auto lg:h-full">
            
            <div class="rounded shadow-sm overflow-hidden h-full sm:h-full lg:h-full w-full">
                <img src="{{ isset($settings['banner_2']) ? asset('public/storage/' . $settings['banner_2']) : 'https://placehold.co/400x200/ee4d2d/ffffff?text=Promo+1' }}" class="w-full h-full object-cover">
            </div>
            
            <div class="rounded shadow-sm overflow-hidden h-full sm:h-full lg:h-full w-full">
                <img src="{{ isset($settings['banner_3']) ? asset('public/storage/' . $settings['banner_3']) : 'https://placehold.co/400x200/d0011b/ffffff?text=Promo+2' }}" class="w-full h-full object-cover">
            </div>
        </div>
    </section>

<div class="bg-gray-50 min-h-screen py-10">

    {{-- ============================================================ --}}
    {{-- ⚡ UPDATE: MENU PPOB TAMPIL PUBLIC (TANPA SYARAT) ⚡ --}}
    {{-- ============================================================ --}}
    
    <section class="mb-10" data-aos="fade-up">
        <div class="bg-white p-6 rounded-2xl shadow-md border-t-4 border-red-500">
            <h2 class="text-xl font-bold mb-6 text-gray-800 flex items-center">
                <i class="fas fa-mobile-alt text-blue-500 mr-2"></i> Layanan Top Up & Tagihan
            </h2>
            
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-4">
                {{-- MENU PULSA --}}
                <a href="{{ url('/etalase/ppob/digital/pulsa') }}" class="ppob-icon flex flex-col items-center p-4 border rounded-xl hover:shadow-lg hover:border-blue-500 transition bg-blue-50">
                    <i class="fas fa-mobile-screen-button text-3xl text-blue-600 mb-2"></i>
                    <span class="text-sm font-bold text-gray-700 text-center">Pulsa</span>
                </a>

                {{-- MENU DATA --}}
                <a href="{{ url('/etalase/ppob/digital/data') }}" class="ppob-icon flex flex-col items-center p-4 border rounded-xl hover:shadow-lg hover:border-green-500 transition bg-green-50">
                    <i class="fas fa-wifi text-3xl text-green-600 mb-2"></i>
                    <span class="text-sm font-bold text-gray-700 text-center">Paket Data</span>
                </a>

                {{-- MENU PLN TOKEN --}}
                <a href="{{ url('/etalase/ppob/digital/pln-token') }}" class="ppob-icon flex flex-col items-center p-4 border rounded-xl hover:shadow-lg hover:border-yellow-500 transition bg-yellow-50">
                    <i class="fas fa-bolt text-3xl text-yellow-500 mb-2"></i>
                    <span class="text-sm font-bold text-gray-700 text-center">Token PLN</span>
                </a>

                {{-- MENU PLN PASCABAYAR --}}
                <a href="{{ url('/etalase/ppob/digital/pln-pascabayar') }}" class="ppob-icon flex flex-col items-center p-4 border rounded-xl hover:shadow-lg hover:border-orange-500 transition bg-orange-50">
                    <i class="fas fa-file-invoice-dollar text-3xl text-orange-500 mb-2"></i>
                    <span class="text-sm font-bold text-gray-700 text-center">PLN Pasca</span>
                </a>

                {{-- MENU PDAM --}}
                <a href="{{ url('/etalase/ppob/digital/pdam') }}" class="ppob-icon flex flex-col items-center p-4 border rounded-xl hover:shadow-lg hover:border-cyan-500 transition bg-cyan-50">
                    <i class="fas fa-faucet text-3xl text-cyan-600 mb-2"></i>
                    <span class="text-sm font-bold text-gray-700 text-center">PDAM</span>
                </a>

                {{-- MENU E-MONEY --}}
                <a href="{{ url('/etalase/ppob/digital/e-money') }}" class="ppob-icon flex flex-col items-center p-4 border rounded-xl hover:shadow-lg hover:border-purple-500 transition bg-purple-50">
                    <i class="fas fa-wallet text-3xl text-purple-600 mb-2"></i>
                    <span class="text-sm font-bold text-gray-700 text-center">E-Wallet</span>
                </a>

                {{-- MENU GAMES --}}
                <a href="{{ url('/etalase/ppob/digital/voucher-game') }}" class="ppob-icon flex flex-col items-center p-4 border rounded-xl hover:shadow-lg hover:border-red-500 transition bg-red-50">
                    <i class="fas fa-gamepad text-3xl text-red-600 mb-2"></i>
                    <span class="text-sm font-bold text-gray-700 text-center">Voucher Game</span>
                </a>

                {{-- MENU TV / STREAMING --}}
                <a href="{{ url('/etalase/ppob/digital/streaming') }}" class="ppob-icon flex flex-col items-center p-4 border rounded-xl hover:shadow-lg hover:border-pink-500 transition bg-pink-50">
                    <i class="fas fa-tv text-3xl text-pink-600 mb-2"></i>
                    <span class="text-sm font-bold text-gray-700 text-center">TV Kabel</span>
                </a>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4">
        
        {{-- Breadcrumb Simple --}}
        <div class="text-sm text-gray-500 mb-6 flex items-center gap-2">
            <a href="https://tokosancaka.com/etalase" class="hover:text-red-500 transition">Beranda</a> 
            <i class="fas fa-chevron-right text-xs text-gray-300"></i>
            <a href="{{ url('/etalase/category/e-wallet-pulsa') }}" class="hover:text-red-500 transition">Digital</a>
            <i class="fas fa-chevron-right text-xs text-gray-300"></i>
            <span class="font-bold text-gray-700">{{ $pageInfo['title'] }}</span>
        </div>

        {{-- Alert Notification --}}
        @if(session('success')) 
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm mb-6 flex items-center">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            </div> 
        @endif
        @if(session('error')) 
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm mb-6 flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
            </div> 
        @endif

        <div class="flex flex-col lg:flex-row gap-8">
            
            {{-- KOLOM KIRI: Input Nomor & Filter --}}
            <div class="lg:w-1/3 space-y-6">
                
                {{-- Card Input Nomor --}}
                <div class="bg-white rounded-2xl shadow-md p-6 border-t-4 border-red-500 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                        <i class="fas {{ $pageInfo['icon'] }} text-9xl text-red-500"></i>
                    </div>

                    <h1 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2 relative z-10">
                        <span class="bg-red-100 text-red-600 w-10 h-10 flex items-center justify-center rounded-lg shadow-sm">
                            <i class="fas {{ $pageInfo['icon'] }}"></i>
                        </span>
                        {{ $pageInfo['title'] }}
                    </h1>

                    <div class="mb-6 relative z-10">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">{{ $pageInfo['input_label'] }}</label>
                        <div class="relative group">
                            <input type="text" id="customer_no" 
                                   class="w-full border border-gray-300 rounded-xl px-4 py-4 pl-12 font-bold text-lg text-gray-700 focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none shadow-sm transition"
                                   placeholder="{{ $pageInfo['input_place'] }}">
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-red-500 transition">
                                <i class="fas fa-phone"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2 ml-1 flex items-center gap-1">
                            <i class="fas fa-info-circle"></i> Pastikan nomor tujuan benar.
                        </p>
                    </div>

                    {{-- KHUSUS PLN TOKEN: Cek ID --}}
                    @if($pageInfo['slug'] == 'pln-token')
                        <button onclick="cekPlnPrabayar()" id="btn-cek-pln" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 px-4 rounded-xl transition mb-4 shadow-lg shadow-yellow-200/50 flex items-center justify-center gap-2">
                            <i class="fas fa-search"></i> Cek Nama Pelanggan
                        </button>

                        <div id="pln_info" class="hidden bg-yellow-50 p-4 rounded-xl border border-yellow-200 text-sm mb-4 animate-fade-in">
                            <div class="flex justify-between mb-2 border-b border-yellow-200 pb-2">
                                <span class="text-gray-500">Nama:</span>
                                <span class="font-bold text-gray-800" id="pln_name">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Tarif/Daya:</span>
                                <span class="font-bold text-gray-800" id="pln_power">-</span>
                            </div>
                        </div>
                    @endif

                    {{-- KHUSUS PASCABAYAR: Cek Tagihan --}}
                    @if(isset($pageInfo['is_postpaid']) && $pageInfo['is_postpaid'])
                        <button onclick="cekTagihan()" id="btn-cek-tagihan" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition flex justify-center items-center gap-2 shadow-lg shadow-blue-200/50">
                            <span id="btn-text">Cek Tagihan</span>
                            <div id="loading-spinner" class="loader hidden !border-t-white"></div>
                        </button>
                    @endif
                </div>

                {{-- Card Filter Operator (Hanya Prabayar) --}}
                @if(!isset($pageInfo['is_postpaid']) || !$pageInfo['is_postpaid'])
                <div class="bg-white rounded-2xl shadow-md p-6">
                    <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center justify-between">
                        <span>Pilih Provider / Kategori</span>
                        <span class="text-xs font-normal text-gray-400 bg-gray-100 px-2 py-1 rounded-full">Scroll &darr;</span>
                    </h2>
                    <div class="grid grid-cols-3 sm:grid-cols-2 gap-2 max-h-80 overflow-y-auto pr-1 custom-scrollbar">
                        <label class="cursor-pointer">
                            <input type="radio" name="brand_filter" value="all" class="brand-radio hidden" checked onchange="filterProducts('all')">
                            <div class="border border-gray-200 rounded-lg p-3 text-center transition h-full flex items-center justify-center hover:bg-gray-50">
                                <span class="text-xs font-bold text-gray-600">SEMUA</span>
                            </div>
                        </label>
                        @foreach($brands as $brand)
                        <label class="cursor-pointer">
                            <input type="radio" name="brand_filter" value="{{ $brand }}" class="brand-radio hidden" onchange="filterProducts('{{ $brand }}')">
                            <div class="border border-gray-200 rounded-lg p-2 text-center transition h-full flex flex-col items-center justify-center hover:bg-gray-50 gap-1">
                                {{-- Tampilkan Logo Kecil di Filter --}}
                                <img src="{{ get_operator_logo($brand) }}" class="h-6 w-auto object-contain mb-1" alt="{{ $brand }}" onerror="this.style.display='none'">
                                <span class="text-[10px] font-bold text-gray-600 uppercase">{{ $brand }}</span>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- KOLOM KANAN: Daftar Produk --}}
            <div class="lg:w-2/3">
                <div class="bg-white rounded-2xl shadow-md p-6 min-h-[600px]">
                    
                    {{-- 1. MODE PRABAYAR --}}
                    @if(!isset($pageInfo['is_postpaid']) || !$pageInfo['is_postpaid'])
                        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
                            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-list text-gray-400"></i> Daftar Produk
                            </h2>
                        </div>

                        <div id="product_list" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @forelse($products as $product)
                            <div class="product-item border border-gray-200 rounded-xl p-4 cursor-pointer relative bg-white transition-all duration-300 group"
                                 data-brand="{{ $product->brand }}"
                                 data-sku="{{ $product->buyer_sku_code }}"
                                 data-name="{{ $product->product_name }}"
                                 data-price="{{ $product->sell_price }}"
                                 onclick="selectProduct(this)">
                                
                                <div class="flex justify-between items-start mb-3">
                                    {{-- Logo Operator dengan Helper --}}
                                    <div class="h-8 w-auto">
                                        <img src="{{ get_operator_logo($product->brand) }}" 
                                             alt="{{ $product->brand }}" 
                                             class="h-full object-contain"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                        <span class="bg-gray-100 text-gray-600 text-[10px] font-bold px-2 py-1 rounded uppercase hidden">
                                            {{ $product->brand }}
                                        </span>
                                    </div>

                                    @if($product->stock < 5 && !$product->unlimited_stock)
                                        <span class="bg-red-50 text-red-600 text-[10px] font-bold px-2 py-1 rounded border border-red-100 animate-pulse">Sisa {{ $product->stock }}</span>
                                    @elseif($product->multi)
                                        <span class="bg-blue-50 text-blue-600 text-[10px] font-bold px-2 py-1 rounded border border-blue-100">Promo</span>
                                    @endif
                                </div>

                                <h3 class="text-sm font-bold text-gray-800 mb-2 leading-snug group-hover:text-red-600 transition">{{ $product->product_name }}</h3>
                                <p class="text-xs text-gray-400 mb-4 line-clamp-2 h-8">{{ $product->desc }}</p>
                                
                                <div class="flex justify-between items-end border-t border-dashed border-gray-200 pt-3">
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Harga</p>
                                        <p class="text-lg font-extrabold text-red-500">Rp{{ number_format($product->sell_price, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="bg-red-50 text-red-500 w-10 h-10 rounded-full flex items-center justify-center group-hover:bg-red-500 group-hover:text-white transition shadow-sm transform group-hover:rotate-12">
                                        <i class="fas fa-shopping-cart text-sm"></i>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="col-span-full text-center py-20">
                                <div class="bg-gray-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-box-open text-gray-300 text-4xl"></i>
                                </div>
                                <h3 class="text-gray-800 font-bold text-lg">Produk Kosong</h3>
                                <p class="text-gray-500 text-sm">Belum ada produk untuk kategori ini.</p>
                            </div>
                            @endforelse
                        </div>
                    
                    {{-- 2. MODE PASCABAYAR --}}
                    @else
                        <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Rincian Tagihan</h2>
                        
                        <div id="bill_result" class="hidden">
                            <div class="bg-blue-50 p-6 rounded-2xl mb-6 border border-blue-100 relative overflow-hidden">
                                <div class="absolute -right-6 -top-6 bg-blue-100 w-24 h-24 rounded-full opacity-50"></div>
                                
                                <div class="relative z-10 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <p class="text-gray-500 text-xs uppercase">Nama Pelanggan</p>
                                        <p class="font-bold text-gray-800 text-lg" id="bill_name">-</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 text-xs uppercase">ID Pelanggan</p>
                                        <p class="font-bold text-gray-800 text-lg" id="bill_id">-</p>
                                    </div>
                                    <div class="md:col-span-2 border-t border-blue-200 my-2 pt-2">
                                        <p class="text-gray-500 text-xs uppercase text-right">Total Tagihan</p>
                                        <p class="font-extrabold text-right text-3xl text-blue-600" id="bill_amount">-</p>
                                    </div>
                                </div>
                            </div>
                            <button onclick="bayarTagihan()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg shadow-green-200 transition transform hover:-translate-y-1">
                                <i class="fas fa-check-circle mr-2"></i> Bayar Sekarang
                            </button>
                        </div>

                        <div id="bill_empty" class="flex flex-col items-center justify-center py-20 text-gray-400">
                            <div class="bg-gray-100 p-6 rounded-full mb-4">
                                <i class="fas fa-file-invoice-dollar text-4xl text-gray-300"></i>
                            </div>
                            <p class="font-medium">Masukkan nomor pelanggan di kolom kiri lalu klik "Cek Tagihan"</p>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL KONFIRMASI (PRABAYAR) --}}
<div id="confirmModal" class="fixed inset-0 z-[999] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="closeModal()"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full animate-bounce-in">
            <div class="bg-white p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Konfirmasi Pembayaran</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center transition"><i class="fas fa-times"></i></button>
                </div>
                
                <div class="mt-2">
                    <div class="bg-red-50 p-4 rounded-xl border border-red-100 mb-4">
                        <p class="text-xs text-red-500 uppercase font-bold mb-1">Produk</p>
                        <p class="text-gray-800 font-bold text-lg leading-tight" id="modal_product">-</p>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-500">Nomor Tujuan</span>
                            <span class="font-bold text-gray-800 font-mono text-base" id="modal_no">-</span>
                        </div>
                        <div class="flex justify-between items-center pt-2">
                            <span class="text-gray-500">Total Harga</span>
                            <span class="font-extrabold text-red-600 text-2xl" id="modal_price">-</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <button type="button" onclick="closeModal()" class="w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-3 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:w-auto sm:text-sm transition">
                    Batal
                </button>
                <form action="{{ route('ppob.store') }}" method="POST" id="checkoutForm" class="w-full sm:w-auto">
                    @csrf
                    <input type="hidden" name="buyer_sku_code" id="form_sku">
                    <input type="hidden" name="customer_no" id="form_no">
                    <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-lg shadow-red-200 px-6 py-3 bg-red-600 text-base font-bold text-white hover:bg-red-700 focus:outline-none sm:text-sm transition transform hover:scale-[1.02]">
                        Bayar Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const inputNo = document.getElementById('customer_no');
    
    // ==========================================
    // LOGIKA UNTUK PRABAYAR (TOKEN, PULSA, GAME)
    // ==========================================
    @if(!isset($pageInfo['is_postpaid']) || !$pageInfo['is_postpaid'])
        const items = document.querySelectorAll('.product-item');
        
        function filterProducts(brand) {
            items.forEach(item => {
                if (brand === 'all' || item.dataset.brand === brand) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        }

        // Auto Detect Operator (Hanya untuk Pulsa & Data)
        @if($pageInfo['slug'] == 'pulsa' || $pageInfo['slug'] == 'data')
        inputNo.addEventListener('input', function(e) {
            const val = e.target.value;
            if(val.length >= 4) {
                let operator = null;
                // Regex Operator Indonesia
                if(/^08(11|12|13|21|22|52|53|51)/.test(val)) operator = 'TELKOMSEL';
                else if(/^08(14|15|16|55|56|57|58)/.test(val)) operator = 'INDOSAT';
                else if(/^08(17|18|19|59|77|78)/.test(val)) operator = 'XL';
                else if(/^08(95|96|97|98|99)/.test(val)) operator = 'TRI';
                else if(/^08(81|82|83|84|85|86|87|88|89)/.test(val)) operator = 'SMARTFREN';
                else if(/^08(31|32|33|38)/.test(val)) operator = 'AXIS';

                if(operator) {
                    // Cari radio button yang sesuai
                    const radio = document.querySelector(`input[name="brand_filter"][value="${operator}"]`);
                    if(radio) {
                        radio.checked = true;
                        filterProducts(operator);
                    }
                }
            }
        });
        @endif

        // Modal Checkout Prabayar
        function selectProduct(el) {
            const no = inputNo.value;
            // Validasi sederhana panjang nomor
            if(no.length < 5) {
                // Efek getar/merah pada input
                inputNo.classList.add('border-red-500', 'animate-pulse');
                setTimeout(() => inputNo.classList.remove('border-red-500', 'animate-pulse'), 1000);
                inputNo.focus();
                alert("Mohon isi nomor tujuan / ID Pelanggan terlebih dahulu.");
                return;
            }
            
            document.getElementById('modal_no').innerText = no;
            document.getElementById('modal_product').innerText = el.dataset.name;
            document.getElementById('modal_price').innerText = 'Rp ' + parseInt(el.dataset.price).toLocaleString('id-ID');
            document.getElementById('form_sku').value = el.dataset.sku;
            document.getElementById('form_no').value = no;
            document.getElementById('confirmModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('confirmModal').classList.add('hidden');
        }

    // ==========================================
    // LOGIKA UNTUK PASCABAYAR
    // ==========================================
    @else
        let inquiryRefId = null;

        function cekTagihan() {
            const no = inputNo.value;
            if(no.length < 5) { alert("Masukkan ID Pelanggan!"); return; }

            const btn = document.getElementById('btn-cek-tagihan');
            const spinner = document.getElementById('loading-spinner');
            const text = document.getElementById('btn-text');
            const resultDiv = document.getElementById('bill_result');
            const emptyDiv = document.getElementById('bill_empty');

            btn.disabled = true;
            spinner.classList.remove('hidden');
            text.innerText = "Mengecek...";
            resultDiv.classList.add('hidden');
            emptyDiv.classList.add('hidden');

            fetch('{{ route("ppob.check.bill") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    customer_no: no,
                    sku: 'PLN' // Default PLN, nanti bisa dibuat dinamis
                })
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                spinner.classList.add('hidden');
                text.innerText = "Cek Tagihan";

                if(data.status === 'success') {
                    document.getElementById('bill_name').innerText = data.customer_name;
                    document.getElementById('bill_id').innerText = data.customer_no;
                    document.getElementById('bill_amount').innerText = 'Rp ' + parseInt(data.amount).toLocaleString('id-ID');
                    inquiryRefId = data.ref_id;
                    resultDiv.classList.remove('hidden');
                } else {
                    alert(data.message);
                    emptyDiv.classList.remove('hidden');
                }
            })
            .catch(err => {
                btn.disabled = false;
                spinner.classList.add('hidden');
                text.innerText = "Cek Tagihan";
                alert("Terjadi kesalahan koneksi.");
            });
        }

        function bayarTagihan() {
            if(!inquiryRefId) return;
            // Arahkan ke route bayar (logic pascabayar)
             alert("Silakan login untuk melanjutkan pembayaran.");
        }
    @endif

    // Logic Cek Nama PLN Prabayar
    @if($pageInfo['slug'] == 'pln-token')
    function cekPlnPrabayar() {
        const no = inputNo.value;
        if(no.length < 10) { alert("Masukkan Nomor Meter dengan benar!"); return; }

        const btn = document.getElementById('btn-cek-pln');
        const infoBox = document.getElementById('pln_info');
        
        const oriText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengecek...';
        btn.disabled = true;
        infoBox.classList.add('hidden');

        fetch('{{ route("ppob.check.pln.prabayar") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ customer_no: no })
        })
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = oriText;
            btn.disabled = false;

            if(data.status === 'success') {
                document.getElementById('pln_name').innerText = data.name;
                document.getElementById('pln_power').innerText = data.segment_power;
                infoBox.classList.remove('hidden');
            } else {
                alert(data.message);
            }
        })
        .catch(err => {
            btn.innerHTML = oriText;
            btn.disabled = false;
            alert("Gagal koneksi server.");
        });
    }
    @endif
    
    document.addEventListener('DOMContentLoaded', function () {
        
        // ==========================================
        // 1. HERO SLIDER (BANNER UTAMA)
        // ==========================================
        var heroSwiper = new Swiper(".heroSwiper", {
            loop: true,                 // Looping terus menerus
            effect: "fade",             // Efek transisi fade (opsional, ganti 'slide' jika ingin geser)
            speed: 1000,                // Kecepatan transisi
            autoplay: {
                delay: 4000,            // Pindah setiap 4 detik
                disableOnInteraction: false, // Tetap autoplay meski diklik user
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
        });

        // ==========================================
        // 2. FLASH SALE SLIDER (RESPONSIF)
        // ==========================================
        var flashSaleSwiper = new Swiper(".flashSaleSwiper", {
            slidesPerView: 2,           // Tampilan HP (Default)
            spaceBetween: 10,           // Jarak antar slide
            navigation: {
                nextEl: ".flash-sale-next", // Class tombol next custom kita
                prevEl: ".flash-sale-prev", // Class tombol prev custom kita
            },
            breakpoints: {
                640: {
                    slidesPerView: 3,   // Tablet Kecil
                    spaceBetween: 15,
                },
                768: {
                    slidesPerView: 4,   // Tablet
                    spaceBetween: 20,
                },
                1024: {
                    slidesPerView: 5,   // Desktop / Laptop
                    spaceBetween: 20,
                },
            },
        });

        // ==========================================
        // 3. CATEGORIES SLIDER (Jika ada icon kategori)
        // ==========================================
        var categoriesSwiper = new Swiper(".categoriesSwiper", {
            slidesPerView: 1,
            spaceBetween: 20,
            navigation: {
                nextEl: ".category-next",
                prevEl: ".category-prev",
            },
        });

    });
</script>

@endpush