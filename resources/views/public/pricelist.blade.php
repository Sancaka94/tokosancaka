@extends('layouts.marketplace')

@section('title', 'Daftar Harga & Promo Terbaru')

@push('styles')
    {{-- CSS Swiper (Slider) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
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
        {{-- 1. HERO SECTION (LAYOUT GRID SESUAI REQUEST)      --}}
        {{-- ================================================= --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
            
            {{-- KOLOM 1: SLIDER UTAMA (Lebar 2/3) --}}
            <div class="lg:col-span-2 rounded-2xl shadow-lg overflow-hidden relative group h-[200px] md:h-[350px] lg:h-[420px]">
                <div class="swiper heroSwiper w-full h-full">
                    <div class="swiper-wrapper">
                        @forelse($banners as $banner)
                            <div class="swiper-slide">
                                {{-- Gunakan object-fill agar gambar FULL 100% terlihat (tidak terpotong) --}}
                                <img src="{{ asset('public/storage/' . $banner->image) }}" 
                                     class="w-full h-full object-fill" 
                                     alt="Promo Banner">
                            </div>
                        @empty
                            <div class="swiper-slide">
                                <img src="https://placehold.co/800x420/ee4d2d/ffffff?text=Promo+Sancaka+1" class="w-full h-full object-fill">
                            </div>
                            <div class="swiper-slide">
                                <img src="https://placehold.co/800x420/1e3a8a/ffffff?text=Promo+Sancaka+2" class="w-full h-full object-fill">
                            </div>
                        @endforelse
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next opacity-0 group-hover:opacity-100"></div>
                    <div class="swiper-button-prev opacity-0 group-hover:opacity-100"></div>
                </div>
            </div>
    
            {{-- KOLOM 2: BANNER SAMPING (Lebar 1/3) --}}
            <div class="grid grid-cols-2 lg:grid-cols-1 lg:grid-rows-2 gap-4 h-auto lg:h-[420px]">
                {{-- Banner Samping Atas --}}
                <div class="rounded-2xl shadow-lg overflow-hidden h-[100px] md:h-[170px] lg:h-full w-full">
                    <img src="{{ isset($settings['banner_2']) ? asset('storage/' . $settings['banner_2']) : 'https://placehold.co/400x200/fbbf24/ffffff?text=Promo+Samping+1' }}" 
                         class="w-full h-full object-fill hover:scale-105 transition duration-500">
                </div>
                
                {{-- Banner Samping Bawah --}}
                <div class="rounded-2xl shadow-lg overflow-hidden h-[100px] md:h-[170px] lg:h-full w-full">
                    <img src="{{ isset($settings['banner_3']) ? asset('storage/' . $settings['banner_3']) : 'https://placehold.co/400x200/10b981/ffffff?text=Promo+Samping+2' }}" 
                         class="w-full h-full object-fill hover:scale-105 transition duration-500">
                </div>
            </div>
        </section>
        {{-- ================================================= --}}


        {{-- Search Bar --}}
        <div class="bg-white p-2 rounded-2xl shadow-md mb-8 border border-gray-100 max-w-4xl mx-auto -mt-4 relative z-20">
            <div class="relative">
                <input type="text" id="searchInput" placeholder="Cari Produk (Telkomsel, Token, Dana)..." 
                    class="w-full py-4 pl-14 pr-6 rounded-xl bg-transparent text-gray-800 focus:outline-none text-lg">
                <div class="absolute top-1/2 left-5 transform -translate-y-1/2 text-blue-600">
                    <i class="fas fa-search text-2xl"></i>
                </div>
                <button class="absolute right-2 top-2 bottom-2 bg-blue-600 text-white px-6 rounded-lg font-bold hover:bg-blue-700 transition">
                    Cari
                </button>
            </div>
        </div>

        {{-- Category Filters --}}
        <div class="bg-white p-3 rounded-2xl shadow-sm mb-6 overflow-x-auto whitespace-nowrap scrollbar-hide flex gap-3 border border-gray-100 sticky top-4 z-30">
            <button onclick="filterCategory('all')" class="cat-btn active px-6 py-2.5 rounded-xl font-bold text-sm transition bg-blue-600 text-white shadow-md transform hover:scale-105">
                SEMUA
            </button>
            @foreach($categories as $cat)
                <button onclick="filterCategory('{{ $cat }}')" class="cat-btn px-6 py-2.5 rounded-xl font-bold text-sm transition bg-gray-50 text-gray-600 hover:bg-blue-50 hover:text-blue-600 border border-gray-200">
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
                                    {{-- LOGO DENGAN HELPER (FULL & RAPI) --}}
                                    <div class="h-12 w-12 flex-shrink-0 relative bg-white rounded-xl p-1 shadow-sm border border-gray-100">
                                        <img src="{{ get_operator_logo($product->brand) }}" 
                                             alt="{{ $product->brand }}" 
                                             class="h-full w-full object-contain"
                                             loading="lazy"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        
                                        <span class="hidden h-full w-full items-center justify-center bg-gray-100 text-gray-500 text-[10px] font-bold rounded-lg uppercase">
                                            {{ substr($product->brand, 0, 3) }}
                                        </span>
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
                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200">
                                    Tersedia
                                </span>
                            </td>
                            <td class="p-5 text-right">
                                <div class="font-extrabold text-blue-600 text-lg">
                                    Rp{{ number_format($product->sell_price, 0, ',', '.') }}
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            {{-- Empty State --}}
            <div id="emptyState" class="hidden py-20 text-center">
                <div class="inline-block p-5 rounded-full bg-blue-50 mb-4 animate-bounce">
                    <i class="fas fa-search text-4xl text-blue-300"></i>
                </div>
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
{{-- Load Swiper JS --}}
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
    // 1. Inisialisasi Slider Banner
    var swiper = new Swiper(".heroSwiper", {
        loop: true,
        effect: "fade", // Efek fade agar transisi halus
        autoplay: {
            delay: 4000,
            disableOnInteraction: false,
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

    // 2. Logika Search & Filter
    const searchInput = document.getElementById('searchInput');
    const rows = document.getElementById('productTableBody').getElementsByTagName('tr');
    const emptyState = document.getElementById('emptyState');
    let currentCategory = 'all';

    searchInput.addEventListener('keyup', filterTable);

    function filterCategory(category) {
        currentCategory = category;
        document.querySelectorAll('.cat-btn').forEach(btn => {
            btn.classList.remove('bg-blue-600', 'text-white', 'shadow-md', 'transform', 'scale-105');
            btn.classList.add('bg-gray-50', 'text-gray-600');
        });
        event.target.classList.remove('bg-gray-50', 'text-gray-600');
        event.target.classList.add('bg-blue-600', 'text-white', 'shadow-md', 'transform', 'scale-105');
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