@extends('layouts.customer')

@section('title', 'Toko Sancaka - Belanja Hemat Pasti Puas')

@push('styles')
<style>
    /* Custom styles untuk tombol navigasi Swiper */
    .swiper-button-next, .swiper-button-prev {
        color: black; background-color: rgba(255, 255, 255, 0.8); width: 38px; height: 38px;
        border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.15); transition: background-color 0.3s;
    }
    .swiper-button-next:hover, .swiper-button-prev:hover { background-color: white; }
    .swiper-button-next::after, .swiper-button-prev::after { font-size: 16px; font-weight: bold; }
    .flash-sale-nav, .category-nav { top: 50%; transform: translateY(-50%); }
    .swiper-pagination-bullet-active { background-color: #ef4444 !important; }
</style>
@endpush

@section('content')
<div class="container mx-auto py-6 px-4">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md shadow-sm" role="alert">
        <p>{{ session('success') }}</p>
    </div>
    @endif

    <!-- Hero Section -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
        <div class="lg:col-span-2 rounded-lg overflow-hidden shadow-sm h-[200px] md:h-[270px]">
            <div class="swiper heroSwiper w-full h-full">
                <div class="swiper-wrapper">
                    @forelse($banners as $banner)
                        <div class="swiper-slide">
                            <img src="{{ asset($banner->image) }}" class="w-full h-full object-cover" alt="{{ $banner->title ?? 'Promo Banner' }}">
                        </div>
                    @empty
                         <div class="swiper-slide"><img src="https://placehold.co/800x400/ef4444/white?text=Sancaka+Store" class="w-full h-full object-cover" alt="Default Banner"></div>
                    @endforelse
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
        <div class="grid grid-rows-2 gap-4 h-[200px] md:h-[270px]">
             <div class="rounded-lg overflow-hidden shadow-sm">
                <img src="https://placehold.co/400x190/3b82f6/white?text=Yuk+Belanja+Di+Sancaka" class="w-full h-full object-cover" alt="Side Banner 1">
            </div>
            <div class="rounded-lg overflow-hidden shadow-sm">
                 <img src="https://placehold.co/400x190/10b981/white?text=Belanja+Hemat+Pasti+Puas" class="w-full h-full object-cover" alt="Side Banner 2">
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="bg-white p-5 rounded-lg shadow-sm mb-8 relative">
        <h2 class="text-base font-semibold mb-4 text-gray-700">Kategori Pilihan</h2>
        <div class="swiper categoriesSwiper">
            <div class="swiper-wrapper">
                @php $categoryChunks = $categories->chunk(20); @endphp
                @foreach ($categoryChunks as $chunk)
                <div class="swiper-slide">
                    <div class="grid grid-cols-5 md:grid-cols-10 gap-x-2 gap-y-4 text-center">
                        @foreach ($chunk as $category)
                        <a href="{{ route('marketplace.categories.show', $category->slug) }}" class="flex flex-col items-center space-y-2 text-gray-600 hover:text-red-500 transition-colors group">
                            <div class="w-12 h-12 flex items-center justify-center">
                                <i class="fas {{ $category->icon ?? 'fa-tag' }} text-2xl text-gray-500 group-hover:text-red-500 transition-colors"></i>
                            </div>
                            <span class="text-xs font-medium">{{ $category->name }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        <div class="swiper-button-next category-next category-nav"></div>
        <div class="swiper-button-prev category-prev category-nav"></div>
    </section>

    <!-- Flash Sale & Product Recommendations -->
    @if($flashSaleProducts->isNotEmpty())
    <section class="mb-8">
        <div class="bg-red-600 p-3 rounded-t-lg"><h2 class="text-lg font-bold text-white tracking-wide">FLASH SALE</h2></div>
        <div class="bg-white p-4 rounded-b-lg shadow-sm">
            <div class="relative">
                <div class="swiper flashSaleSwiper overflow-hidden">
                    <div class="swiper-wrapper">
                        @foreach ($flashSaleProducts as $product)
                        <div class="swiper-slide h-full py-2">
                            {{-- Menggunakan partial untuk kartu produk --}}
                            @include('marketplace.partials.product_card', ['product' => $product])
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="swiper-button-next flash-sale-next flash-sale-nav"></div>
                <div class="swiper-button-prev flash-sale-prev flash-sale-nav"></div>
            </div>
        </div>
    </section>
    @endif

    <section>
        <div class="bg-white p-3 rounded-lg shadow-sm">
            <h2 class="text-base font-semibold text-center text-gray-700 mb-4">REKOMENDASI UNTUKMU</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                @forelse ($products as $product)
                    {{-- Menggunakan partial untuk kartu produk --}}
                    @include('marketplace.partials.product_card', ['product' => $product])
                @empty
                    <div class="col-span-full text-center py-16"><p class="text-gray-500">Belum ada produk untuk ditampilkan.</p></div>
                @endforelse
            </div>
            <div class="flex justify-center mt-8">{{ $products->links() }}</div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Swiper(".heroSwiper", { loop: true, effect: "fade", autoplay: { delay: 4000, disableOnInteraction: false }, pagination: { el: ".swiper-pagination", clickable: true }, navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" } });
    new Swiper(".flashSaleSwiper", { slidesPerView: 2.2, spaceBetween: 10, navigation: { nextEl: ".flash-sale-next", prevEl: ".flash-sale-prev" }, breakpoints: { 640: { slidesPerView: 3.2 }, 768: { slidesPerView: 4.2 }, 1024: { slidesPerView: 5.2 } } });
    new Swiper(".categoriesSwiper", { loop: false, slidesPerView: 1, spaceBetween: 20, navigation: { nextEl: ".category-next", prevEl: ".category-prev" } });
});
</script>
@endpush

