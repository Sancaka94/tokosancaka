@extends('layouts.marketplace')

@section('title', 'Daftar Harga & Promo Terbaru')

@push('styles')
    {{-- CSS Swiper (Slider) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        /* Utility untuk menyembunyikan scrollbar (digunakan di mobile jika perlu) */
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Custom Scrollbar (Horizontal & Vertical) */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;   /* Lebar scrollbar vertical */
            height: 8px;  /* Tinggi scrollbar horizontal */
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1; /* slate-300 */
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8; /* slate-400 */
        }

        .swiper-pagination-bullet-active { background-color: #ef4444 !important; width: 20px; border-radius: 5px; }
        
        /* Tombol Navigasi Slider */
        .swiper-button-next, .swiper-button-prev {
            color: white; 
            background-color: rgba(0,0,0,0.3); 
            width: 40px; height: 40px; 
            border-radius: 50%;
            transition: all 0.3s;
        }
        .swiper-button-next:hover, .swiper-button-prev:hover { background-color: rgba(0,0,0,0.6); }
        .swiper-button-next:after, .swiper-button-prev:after { font-size: 16px; font-weight: bold; }
    </style>
@endpush

@section('content')
<div class="bg-gray-50 min-h-screen pb-20">
    
    <div class="container mx-auto px-4 pt-6 relative z-10 max-w-7xl">
        
        {{-- ================================================= --}}
        {{-- 1. HERO SECTION                                   --}}
        {{-- ================================================= --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
            <div class="lg:col-span-2 rounded-2xl shadow-lg overflow-hidden relative group h-[200px] md:h-[350px] lg:h-[420px]">
                <div class="swiper heroSwiper w-full h-full">
                    <div class="swiper-wrapper">
                        @forelse($banners as $banner)
                            <div class="swiper-slide">
                                <img src="{{ asset('public/storage/' . $banner->image) }}" class="w-full h-full object-fill" alt="Promo Banner">
                            </div>
                        @empty
                            <div class="swiper-slide"><img src="https://placehold.co/800x420/ee4d2d/ffffff?text=Promo+Sancaka+1" class="w-full h-full object-fill"></div>
                            <div class="swiper-slide"><img src="https://placehold.co/800x420/1e3a8a/ffffff?text=Promo+Sancaka+2" class="w-full h-full object-fill"></div>
                        @endforelse
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next opacity-0 group-hover:opacity-100"></div>
                    <div class="swiper-button-prev opacity-0 group-hover:opacity-100"></div>
                </div>
            </div>
    
            <div class="grid grid-cols-2 lg:grid-cols-1 lg:grid-rows-2 gap-4 h-auto lg:h-[420px]">
                <div class="rounded-2xl shadow-lg overflow-hidden h-[100px] md:h-[170px] lg:h-full w-full">
                    <img src="{{ isset($settings['banner_2']) ? asset('public/storage/' . $settings['banner_2']) : 'https://placehold.co/400x200/fbbf24/ffffff?text=Promo+Samping+1' }}" class="w-full h-full object-fill hover:scale-105 transition duration-500">
                </div>
                <div class="rounded-2xl shadow-lg overflow-hidden h-[100px] md:h-[170px] lg:h-full w-full">
                    <img src="{{ isset($settings['banner_3']) ? asset('public/storage/' . $settings['banner_3']) : 'https://placehold.co/400x200/10b981/ffffff?text=Promo+Samping+2' }}" class="w-full h-full object-fill hover:scale-105 transition duration-500">
                </div>
            </div>
        </section>

        <section class="mb-10" data-aos="fade-up">
            <div class="bg-white p-6 rounded-2xl shadow-md border-t-4 border-red-500">
                <h2 class="text-lg font-bold mb-5 text-gray-800 flex items-center">
                    <i class="fas fa-mobile-alt text-blue-500 mr-2"></i> Layanan Top Up & Tagihan
                </h2>
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
                    <a href="{{ url('/etalase/ppob/digital/pulsa') }}" class="ppob-icon flex flex-col items-center p-3 border rounded-xl hover:shadow-md hover:border-blue-400 transition bg-blue-50">
                        <i class="fas fa-mobile-screen-button text-2xl text-blue-600 mb-1"></i><span class="text-xs font-semibold text-gray-700 text-center">Pulsa</span>
                    </a>
                    <a href="{{ url('/etalase/ppob/digital/data') }}" class="ppob-icon flex flex-col items-center p-3 border rounded-xl hover:shadow-md hover:border-green-400 transition bg-green-50">
                        <i class="fas fa-wifi text-2xl text-green-600 mb-1"></i><span class="text-xs font-semibold text-gray-700 text-center">Paket Data</span>
                    </a>
                    <a href="{{ url('/etalase/ppob/digital/pln-token') }}" class="ppob-icon flex flex-col items-center p-3 border rounded-xl hover:shadow-md hover:border-yellow-400 transition bg-yellow-50">
                        <i class="fas fa-bolt text-2xl text-yellow-500 mb-1"></i><span class="text-xs font-semibold text-gray-700 text-center">Token PLN</span>
                    </a>
                    <a href="{{ url('/etalase/ppob/digital/pln-pascabayar') }}" class="ppob-icon flex flex-col items-center p-3 border rounded-xl hover:shadow-md hover:border-orange-400 transition bg-orange-50">
                        <i class="fas fa-file-invoice-dollar text-2xl text-orange-500 mb-1"></i><span class="text-xs font-semibold text-gray-700 text-center">PLN Pasca</span>
                    </a>
                    <a href="{{ url('/etalase/ppob/digital/pdam') }}" class="ppob-icon flex flex-col items-center p-3 border rounded-xl hover:shadow-md hover:border-cyan-400 transition bg-cyan-50">
                        <i class="fas fa-faucet text-2xl text-cyan-600 mb-1"></i><span class="text-xs font-semibold text-gray-700 text-center">PDAM</span>
                    </a>
                    <a href="{{ url('/etalase/ppob/digital/e-money') }}" class="ppob-icon flex flex-col items-center p-3 border rounded-xl hover:shadow-md hover:border-purple-400 transition bg-purple-50">
                        <i class="fas fa-wallet text-2xl text-purple-600 mb-1"></i><span class="text-xs font-semibold text-gray-700 text-center">E-Wallet</span>
                    </a>
                    <a href="{{ url('/etalase/ppob/digital/voucher-game') }}" class="ppob-icon flex flex-col items-center p-3 border rounded-xl hover:shadow-md hover:border-red-400 transition bg-red-50">
                        <i class="fas fa-gamepad text-2xl text-red-600 mb-1"></i><span class="text-xs font-semibold text-gray-700 text-center">Voucher Game</span>
                    </a>
                    <a href="{{ url('/etalase/ppob/digital/streaming') }}" class="ppob-icon flex flex-col items-center p-3 border rounded-xl hover:shadow-md hover:border-pink-400 transition bg-pink-50">
                        <i class="fas fa-tv text-2xl text-pink-600 mb-1"></i><span class="text-xs font-semibold text-gray-700 text-center">TV Kabel</span>
                    </a>
                </div>
                <div class="bg-white p-2 rounded-2xl shadow-md mt-10 mb-4 border border-gray-100 max-w-4xl mx-auto relative">
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Cari Produk (Telkomsel, Token, Dana)..." class="w-full py-3 pl-12 pr-6 rounded-xl bg-transparent text-gray-800 focus:outline-none text-base">
                        <div class="absolute top-1/2 left-4 transform -translate-y-1/2 text-blue-600"><i class="fas fa-search text-xl"></i></div>
                        <button class="absolute right-2 top-1.5 bottom-1.5 bg-blue-600 text-white px-5 rounded-lg font-bold hover:bg-blue-700 transition">Cari</button>
                    </div>
                </div>
            </div>
        </section>

        {{-- Category Filters --}}
        
        {{-- 1. TAMPILAN MOBILE & TABLET (CUSTOM DROPDOWN + SCROLLBAR) --}}
        {{-- Menggunakan DIV Custom agar bisa di-scroll dan distyling --}}
        <div class="lg:hidden mb-6 sticky top-4 z-30" id="mobileDropdownContainer">
            <div class="relative">
                {{-- Tombol Trigger --}}
                <button onclick="toggleMobileDropdown()" 
                        class="w-full bg-white rounded-xl shadow-md border border-gray-100 px-4 py-3.5 flex items-center justify-between text-left focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <span id="mobileCategoryLabel" class="text-sm font-bold text-gray-700 uppercase">SEMUA KATEGORI</span>
                    <i class="fas fa-chevron-down text-gray-500 text-xs transition-transform duration-200" id="dropdownArrow"></i>
                </button>

                {{-- List Kategori (Hidden by default) --}}
                {{-- Added: max-h-60 (max height), overflow-y-auto (scrollable), custom-scrollbar (styled scrollbar) --}}
                <div id="mobileCategoryList" 
                     class="hidden absolute w-full z-50 bg-white shadow-xl rounded-xl mt-2 border border-gray-100 overflow-hidden">
                    <div class="max-h-60 overflow-y-auto custom-scrollbar">
                        <div onclick="filterCategory('all')" 
                             class="px-4 py-3 border-b border-gray-50 hover:bg-blue-50 cursor-pointer transition text-sm font-bold text-gray-600 hover:text-blue-600">
                            SEMUA KATEGORI
                        </div>
                        @foreach($categories as $cat)
                            <div onclick="filterCategory('{{ $cat }}')" 
                                 class="px-4 py-3 border-b border-gray-50 hover:bg-blue-50 cursor-pointer transition text-sm font-bold text-gray-600 hover:text-blue-600 uppercase">
                                {{ $cat }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. TAMPILAN DESKTOP (TOMBOL HORIZONTAL DENGAN SCROLLBAR) --}}
        <div class="hidden lg:flex bg-white p-3 rounded-2xl shadow-sm mb-6 overflow-x-auto whitespace-nowrap custom-scrollbar gap-3 border border-gray-100 sticky top-4 z-30 pb-4">
            <button onclick="filterCategory('all')" data-cat="all" class="cat-btn active px-6 py-2.5 rounded-xl font-bold text-sm transition bg-blue-600 text-white shadow-md transform hover:scale-105">
                SEMUA
            </button>
            @foreach($categories as $cat)
                <button onclick="filterCategory('{{ $cat }}')" data-cat="{{ $cat }}" class="cat-btn px-6 py-2.5 rounded-xl font-bold text-sm transition bg-gray-50 text-gray-600 hover:bg-blue-50 hover:text-blue-600 border border-gray-200">
                    {{ strtoupper($cat) }}
                </button>
            @endforeach
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
                        @foreach($products as $product)
                        <tr class="product-row hover:bg-blue-50/50 transition duration-150 cursor-default" data-category="{{ $product->category }}" data-name="{{ strtolower($product->product_name . ' ' . $product->brand . ' ' . $product->buyer_sku_code) }}">
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
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="emptyState" class="hidden py-20 text-center">
                <div class="inline-block p-5 rounded-full bg-blue-50 mb-4 animate-bounce"><i class="fas fa-search text-4xl text-blue-300"></i></div>
                <h3 class="text-lg font-bold text-gray-700">Produk tidak ditemukan</h3>
                <p class="text-sm text-gray-400">Coba kata kunci lain atau pilih kategori berbeda.</p>
            </div>
        </div>
        
        <div class="mt-10 text-center pb-8">
            <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-bold rounded-full text-white bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 shadow-lg hover:shadow-xl transition transform hover:-translate-y-1">
                <i class="fas fa-lock mr-2"></i> Login untuk Transaksi
            </a>
            <p class="text-gray-400 text-xs mt-4">Harga update otomatis real-time.</p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
    // 1. Inisialisasi Slider Banner
    var swiper = new Swiper(".heroSwiper", {
        loop: true, effect: "fade", autoplay: { delay: 4000, disableOnInteraction: false },
        pagination: { el: ".swiper-pagination", clickable: true },
        navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
    });

    // 2. Logika Search & Filter
    const searchInput = document.getElementById('searchInput');
    const rows = document.getElementById('productTableBody').getElementsByTagName('tr');
    const emptyState = document.getElementById('emptyState');
    let currentCategory = 'all';

    searchInput.addEventListener('keyup', filterTable);

    // -- Mobile Dropdown Logic --
    function toggleMobileDropdown() {
        const list = document.getElementById('mobileCategoryList');
        const arrow = document.getElementById('dropdownArrow');
        
        if (list.classList.contains('hidden')) {
            list.classList.remove('hidden');
            arrow.classList.add('rotate-180');
        } else {
            list.classList.add('hidden');
            arrow.classList.remove('rotate-180');
        }
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdownContainer = document.getElementById('mobileDropdownContainer');
        const list = document.getElementById('mobileCategoryList');
        const arrow = document.getElementById('dropdownArrow');
        
        if (dropdownContainer && !dropdownContainer.contains(e.target)) {
            if (!list.classList.contains('hidden')) {
                list.classList.add('hidden');
                arrow.classList.remove('rotate-180');
            }
        }
    });

    function filterCategory(category) {
        currentCategory = category;

        // 1. Update UI Tombol Desktop
        document.querySelectorAll('.cat-btn').forEach(btn => {
            btn.classList.remove('bg-blue-600', 'text-white', 'shadow-md', 'transform', 'scale-105');
            btn.classList.add('bg-gray-50', 'text-gray-600');
            
            if (btn.getAttribute('data-cat') === category) {
                btn.classList.remove('bg-gray-50', 'text-gray-600');
                btn.classList.add('bg-blue-600', 'text-white', 'shadow-md', 'transform', 'scale-105');
            }
        });

        // 2. Update Label Mobile Dropdown
        const label = document.getElementById('mobileCategoryLabel');
        if(label) {
            label.innerText = (category === 'all') ? 'SEMUA KATEGORI' : category.toUpperCase();
        }
        
        // Tutup dropdown mobile setelah memilih
        const mobileList = document.getElementById('mobileCategoryList');
        const arrow = document.getElementById('dropdownArrow');
        if(mobileList && !mobileList.classList.contains('hidden')) {
            mobileList.classList.add('hidden');
            arrow.classList.remove('rotate-180');
        }

        filterTable();
    }

    function filterTable() {
        const filter = searchInput.value.toLowerCase();
        let visibleCount = 0;

        for (let row of rows) {
            const name = row.getAttribute('data-name');
            const category = row.getAttribute('data-category');
            const matchesSearch = name.includes(filter);
            const matchesCategory = currentCategory === 'all' || category === currentCategory;

            if (matchesSearch && matchesCategory) {
                row.style.display = "";
                visibleCount++;
            } else {
                row.style.display = "none";
            }
        }
        emptyState.classList.toggle('hidden', visibleCount > 0);
    }
</script>
@endpush