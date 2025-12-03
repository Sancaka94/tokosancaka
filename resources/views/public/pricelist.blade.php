@extends('layouts.marketplace')

@section('title', 'Daftar Harga & Promo Terbaru')

@push('styles')
    {{-- CSS Swiper (Slider) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .swiper-pagination-bullet-active { background-color: #ef4444 !important; width: 20px; border-radius: 5px; }
        
        /* Navigasi Custom */
        .swiper-button-next, .swiper-button-prev {
            color: white; 
            background-color: rgba(0,0,0,0.3); 
            width: 30px; height: 30px; 
            border-radius: 50%;
        }
        .swiper-button-next:after, .swiper-button-prev:after { font-size: 12px; font-weight: bold; }
    </style>
@endpush

@section('content')
<div class="bg-gray-50 min-h-screen pb-20">
    
    <div class="container mx-auto px-4 pt-4 relative z-10 max-w-6xl">
        
        {{-- ================================================= --}}
        {{-- 1. HERO SECTION (SLIDER GRID SESUAI REQUEST)      --}}
        {{-- ================================================= --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-2 mb-6">
            {{-- Slider Utama --}}
            <div class="lg:col-span-2 rounded-xl shadow-md overflow-hidden h-[200px] md:h-[300px] lg:h-[350px] w-full relative group">
                <div class="swiper heroSwiper w-full h-full">
                    <div class="swiper-wrapper">
                        @forelse($banners as $banner)
                            <div class="swiper-slide">
                                {{-- Sesuaikan path storage Anda --}}
                                <img src="{{ asset('public/storage/' . $banner->image) }}" class="w-full h-full object-cover" alt="Banner">
                            </div>
                        @empty
                            <div class="swiper-slide">
                                <img src="https://placehold.co/800x400/ee4d2d/ffffff?text=Promo+Spesial+Hari+Ini" class="w-full h-full object-cover">
                            </div>
                            <div class="swiper-slide">
                                <img src="https://placehold.co/800x400/1e3a8a/ffffff?text=Diskon+Pulsa+Murah" class="w-full h-full object-cover">
                            </div>
                        @endforelse
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next opacity-0 group-hover:opacity-100 transition"></div>
                    <div class="swiper-button-prev opacity-0 group-hover:opacity-100 transition"></div>
                </div>
            </div>
    
            {{-- Banner Samping (Kanan) --}}
            <div class="grid grid-cols-2 lg:grid-cols-1 lg:grid-rows-2 gap-2 h-auto lg:h-[350px]">
                <div class="rounded-xl shadow-md overflow-hidden h-[100px] md:h-[150px] lg:h-full w-full">
                    <img src="{{ isset($settings['banner_2']) ? asset('storage/' . $settings['banner_2']) : 'https://placehold.co/400x200/fbbf24/ffffff?text=Promo+2' }}" class="w-full h-full object-cover hover:scale-105 transition duration-500">
                </div>
                
                <div class="rounded-xl shadow-md overflow-hidden h-[100px] md:h-[150px] lg:h-full w-full">
                    <img src="{{ isset($settings['banner_3']) ? asset('storage/' . $settings['banner_3']) : 'https://placehold.co/400x200/10b981/ffffff?text=Promo+3' }}" class="w-full h-full object-cover hover:scale-105 transition duration-500">
                </div>
            </div>
        </section>
        {{-- ================================================= --}}


        {{-- Search Bar --}}
        <div class="bg-white p-4 rounded-xl shadow-sm mb-6 border border-gray-100">
            <div class="relative">
                <input type="text" id="searchInput" placeholder="Cari Produk (Telkomsel, Token, Dana, Mobile Legends)..." 
                    class="w-full py-3 pl-12 pr-4 rounded-lg bg-gray-50 border border-gray-200 text-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                <div class="absolute top-1/2 left-4 transform -translate-y-1/2 text-gray-400">
                    <i class="fas fa-search text-lg"></i>
                </div>
            </div>
        </div>

        {{-- Category Filters --}}
        <div class="bg-white p-2 rounded-xl shadow-sm mb-6 overflow-x-auto whitespace-nowrap scrollbar-hide flex gap-2 border border-gray-100 sticky top-2 z-30">
            <button onclick="filterCategory('all')" class="cat-btn active px-5 py-2 rounded-lg font-bold text-xs md:text-sm transition bg-blue-600 text-white shadow-md">
                SEMUA
            </button>
            @foreach($categories as $cat)
                <button onclick="filterCategory('{{ $cat }}')" class="cat-btn px-5 py-2 rounded-lg font-bold text-xs md:text-sm transition bg-gray-50 text-gray-600 hover:bg-blue-50 hover:text-blue-600 border border-gray-100">
                    {{ strtoupper($cat) }}
                </button>
            @endforeach
        </div>

        {{-- Product Table --}}
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 min-h-[300px]">
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
                                    
                                    {{-- 🖼️ LOGO DENGAN HELPER --}}
                                    <div class="h-10 w-10 flex-shrink-0 relative">
                                        <img src="{{ get_operator_logo($product->brand) }}" 
                                             alt="{{ $product->brand }}" 
                                             class="h-full w-full object-contain bg-white rounded-lg p-1 shadow-sm border border-gray-100"
                                             loading="lazy"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        
                                        <span class="hidden h-full w-full items-center justify-center bg-gray-100 text-gray-500 text-[10px] font-bold rounded-lg border border-gray-200 uppercase">
                                            {{ substr($product->brand, 0, 3) }}
                                        </span>
                                    </div>

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
        
        <div class="mt-8 text-center pb-8">
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
    // 1. Inisialisasi Slider Banner (Class diganti jadi heroSwiper sesuai request)
    var swiper = new Swiper(".heroSwiper", {
        loop: true,
        effect: "fade",
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