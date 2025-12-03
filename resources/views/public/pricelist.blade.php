@extends('layouts.marketplace')

@section('title', 'Daftar Harga & Promo Terbaru')

@push('styles')
    {{-- CSS Swiper (Slider) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .swiper-pagination-bullet-active { background-color: #ffffff !important; width: 20px; border-radius: 5px; }
    </style>
@endpush

@section('content')
<div class="bg-gray-50 min-h-screen pb-20">
    
    {{-- Hero Header & Banner --}}
    <div class="bg-gradient-to-b from-blue-900 via-blue-800 to-blue-600 pt-8 pb-24 px-4 relative overflow-hidden">
        {{-- Background Decoration --}}
        <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
            <i class="fas fa-wifi text-9xl absolute -top-10 -left-10 text-white"></i>
            <i class="fas fa-bolt text-9xl absolute bottom-20 right-0 text-white"></i>
        </div>

        <div class="relative z-10 max-w-4xl mx-auto">
            
            {{-- Header Text --}}
            <div class="text-center text-white mb-6">
                <h1 class="text-2xl md:text-4xl font-bold mb-2">Pricelist Sancaka Express</h1>
                <p class="text-blue-200 text-sm md:text-base">Daftar harga termurah, terlengkap, dan update otomatis.</p>
            </div>

            {{-- BANNER SLIDER SECTION --}}
            <div class="mb-8 rounded-2xl overflow-hidden shadow-2xl border-4 border-white/20">
                <div class="swiper bannerSwiper w-full h-[180px] md:h-[320px] bg-gray-200">
                    <div class="swiper-wrapper">
                        @forelse($banners as $banner)
                            <div class="swiper-slide">
                                <img src="{{ asset('storage/' . $banner->image) }}" 
                                     class="w-full h-full object-cover" 
                                     alt="Promo Banner"
                                     onerror="this.src='https://placehold.co/800x320/1e3a8a/ffffff?text=Promo+Spesial'">
                            </div>
                        @empty
                            <div class="swiper-slide">
                                <img src="https://placehold.co/800x320/1e3a8a/ffffff?text=Promo+Sancaka+Express" class="w-full h-full object-cover">
                            </div>
                            <div class="swiper-slide">
                                <img src="https://placehold.co/800x320/dc2626/ffffff?text=Diskon+Pulsa+Murah" class="w-full h-full object-cover">
                            </div>
                        @endforelse
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
            
            {{-- Search Bar --}}
            <div class="relative max-w-xl mx-auto -mb-8">
                <div class="relative group">
                    <div class="absolute -inset-1 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Cari Produk (Telkomsel, Token, Dana)..." 
                            class="w-full py-4 pl-14 pr-6 rounded-full text-gray-800 shadow-xl border-none focus:ring-4 focus:ring-blue-300 outline-none transition text-base md:text-lg bg-white placeholder-gray-400">
                        <div class="absolute top-1/2 left-5 transform -translate-y-1/2 text-blue-600">
                            <i class="fas fa-search text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Main Content --}}
    <div class="container mx-auto px-4 mt-8 relative z-20 max-w-5xl">
        
        {{-- Category Filters --}}
        <div class="bg-white p-2 rounded-xl shadow-md mb-6 overflow-x-auto whitespace-nowrap scrollbar-hide flex gap-2 sticky top-4 z-30 border border-gray-100">
            <button onclick="filterCategory('all')" class="cat-btn active px-5 py-2 rounded-lg font-bold text-xs md:text-sm transition bg-blue-600 text-white shadow-md">
                SEMUA
            </button>
            @foreach($categories as $cat)
                <button onclick="filterCategory('{{ $cat }}')" class="cat-btn px-5 py-2 rounded-lg font-bold text-xs md:text-sm transition bg-gray-50 text-gray-600 hover:bg-blue-50 hover:text-blue-600">
                    {{ strtoupper($cat) }}
                </button>
            @endforeach
        </div>

        {{-- Product Table --}}
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 min-h-[300px]">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-[10px] md:text-xs font-bold tracking-wider">
                        <tr>
                            <th class="p-4 border-b">Produk</th>
                            <th class="p-4 border-b">Detail</th>
                            <th class="p-4 border-b text-center hidden sm:table-cell">Status</th>
                            <th class="p-4 border-b text-right">Harga</th>
                        </tr>
                    </thead>
                    <tbody id="productTableBody" class="text-sm divide-y divide-gray-100">
                        @foreach($products as $product)
                        <tr class="product-row hover:bg-blue-50/50 transition duration-150 cursor-default" data-category="{{ $product->category }}" data-name="{{ strtolower($product->product_name . ' ' . $product->brand . ' ' . $product->buyer_sku_code) }}">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    
                                    {{-- ==================================================== --}}
                                    {{-- 🖼️ BAGIAN LOGO: MENGGUNAKAN HELPER --}}
                                    {{-- ==================================================== --}}
                                    <div class="h-10 w-10 flex-shrink-0 relative">
                                        {{-- 1. Coba Tampilkan Gambar dari Helper --}}
                                        <img src="{{ get_operator_logo($product->brand) }}" 
                                             alt="{{ $product->brand }}" 
                                             class="h-full w-full object-contain bg-white rounded-xl p-1 shadow-sm border border-gray-100"
                                             loading="lazy"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        
                                        {{-- 2. Fallback Teks (Jika gambar error/tidak ada) --}}
                                        <span class="hidden h-full w-full items-center justify-center bg-gray-100 text-gray-500 text-[10px] font-bold rounded-xl border border-gray-200 uppercase">
                                            {{ substr($product->brand, 0, 3) }}
                                        </span>
                                    </div>
                                    {{-- ==================================================== --}}

                                    <div class="block sm:hidden">
                                        <p class="font-bold text-gray-800 text-sm line-clamp-1">{{ $product->product_name }}</p>
                                        <p class="text-[10px] text-gray-400">{{ $product->brand }}</p>
                                    </div>
                                    <div class="hidden sm:block">
                                        <p class="font-bold text-gray-800 text-sm">{{ $product->brand }}</p>
                                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">{{ $product->category }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 hidden sm:table-cell">
                                <div class="font-medium text-gray-700">{{ $product->product_name }}</div>
                                <div class="text-xs text-gray-400 mt-0.5 font-mono">SKU: {{ $product->buyer_sku_code }}</div>
                            </td>
                            <td class="p-4 text-center hidden sm:table-cell">
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-[10px] font-bold">
                                    Ready
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <div class="font-extrabold text-blue-600 text-base">
                                    Rp{{ number_format($product->sell_price, 0, ',', '.') }}
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            {{-- Empty State --}}
            <div id="emptyState" class="hidden py-16 text-center">
                <div class="inline-block p-4 rounded-full bg-blue-50 mb-3 animate-bounce">
                    <i class="fas fa-search text-3xl text-blue-300"></i>
                </div>
                <h3 class="text-base font-bold text-gray-700">Produk tidak ditemukan</h3>
                <p class="text-sm text-gray-400">Coba kata kunci lain atau pilih kategori berbeda.</p>
            </div>
        </div>
        
        <div class="mt-8 text-center">
            <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-full text-white bg-blue-600 hover:bg-blue-700 shadow-lg hover:shadow-xl transition transform hover:-translate-y-1">
                <i class="fas fa-sign-in-alt mr-2"></i> Login untuk Transaksi
            </a>
            <p class="text-gray-400 text-xs mt-4">Update Terakhir: {{ date('d F Y H:i') }} WIB</p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
{{-- Load Swiper JS --}}
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
    // 1. Inisialisasi Slider Banner
    var swiper = new Swiper(".bannerSwiper", {
        spaceBetween: 0,
        centeredSlides: true,
        loop: true,
        autoplay: {
            delay: 3500,
            disableOnInteraction: false,
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
        },
    });

    // 2. Logika Search & Filter
    const searchInput = document.getElementById('searchInput');
    const rows = document.getElementById('productTableBody').getElementsByTagName('tr');
    const emptyState = document.getElementById('emptyState');
    let currentCategory = 'all';

    searchInput.addEventListener('keyup', filterTable);

    function filterCategory(category) {
        currentCategory = category;
        document.querySelectorAll('.cat-btn').forEach(btn => {
            btn.classList.remove('bg-blue-600', 'text-white', 'shadow-md');
            btn.classList.add('bg-gray-50', 'text-gray-600');
        });
        event.target.classList.remove('bg-gray-50', 'text-gray-600');
        event.target.classList.add('bg-blue-600', 'text-white', 'shadow-md');
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