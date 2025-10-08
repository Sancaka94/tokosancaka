@extends('layouts.customer')

@section('title', 'Sancaka Marketplace - Belanja Mudah & Cepat')

@push('styles')
<style>
    /* Custom styles untuk tombol navigasi Swiper */
    .swiper-button-next, .swiper-button-prev {
        color: #333;
        background-color: rgba(255, 255, 255, 0.7);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: background-color 0.3s;
    }
    .swiper-button-next:hover, .swiper-button-prev:hover {
        background-color: white;
    }
    .swiper-button-next::after, .swiper-button-prev::after {
        font-size: 18px;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto py-6 px-4">

    <!-- Hero Section (Banner Carousel) -->
    @if($banners->isNotEmpty())
    <section class="mb-10">
        <div class="rounded-2xl overflow-hidden shadow-lg h-[200px] md:h-[300px] lg:h-[420px]">
            <div class="swiper heroSwiper w-full h-full">
                <div class="swiper-wrapper">
                    @foreach($banners as $banner)
                        <div class="swiper-slide">
                            {{-- Menggunakan asset() untuk URL gambar yang benar --}}
                            <img src="{{ asset($banner->image) }}" class="w-full h-full object-cover" alt="{{ $banner->title ?? 'Promo Banner' }}">
                        </div>
                    @endforeach
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>
    @endif

    <!-- Flash Sale Section -->
    @if($flashSaleProducts->isNotEmpty())
    <section class="mb-10">
        <div class="bg-gradient-to-r from-red-600 to-red-500 p-5 rounded-t-2xl flex justify-between items-center">
            <h2 class="text-3xl font-extrabold text-white tracking-wide">FLASH SALE</h2>
        </div>
        <div class="bg-white p-5 rounded-b-2xl shadow-md">
            <div class="relative">
                <div class="swiper flashSaleSwiper">
                    <div class="swiper-wrapper py-2">
                        @foreach ($flashSaleProducts as $product)
                        <div class="swiper-slide h-auto pb-2">
                            <a href="#" class="block border rounded-xl overflow-hidden group hover:shadow-xl transition-all duration-300 h-full flex flex-col bg-white">
                                <div class="relative">
                                    <div class="h-48 bg-gray-50">
                                        {{-- Logika untuk menampilkan gambar dengan fallback --}}
                                        <img src="{{ $product->image_url ? asset($product->image_url) : 'https://placehold.co/400x400/EFEFEF/333333?text=Gambar' }}"
                                             alt="{{ $product->name }}"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                    </div>
                                </div>
                                <div class="p-3 flex flex-col flex-grow">
                                    <h3 class="text-sm font-semibold text-gray-800 truncate" title="{{ $product->name }}">{{ $product->name }}</h3>
                                    <p class="text-lg font-extrabold text-red-500 mt-1">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
                                    <div class="mt-auto pt-2 text-xs text-gray-500">{{ $product->stock }} tersisa</div>
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="swiper-button-next flash-sale-next"></div>
                <div class="swiper-button-prev flash-sale-prev"></div>
            </div>
        </div>
    </section>
    @endif

    <!-- Product Recommendations -->
    <section>
        <div class="bg-white p-5 rounded-t-2xl border-b-2 border-gray-100">
            <h2 class="text-xl font-bold text-center text-gray-800">REKOMENDASI UNTUKMU</h2>
        </div>
        <div class="p-5 bg-gray-50 rounded-b-2xl">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-5">
                @forelse ($products as $product)
                    <a href="#" class="bg-white border rounded-xl overflow-hidden group hover:shadow-2xl transition-all duration-300 flex flex-col">
                        <div class="h-48 bg-white relative">
                            <img src="{{ $product->image_url ? asset($product->image_url) : 'https://placehold.co/400x400/EFEFEF/333333?text=Gambar' }}"
                                 alt="{{ $product->name }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                        </div>
                        <div class="p-4 flex flex-col flex-grow">
                            <h3 class="text-sm font-semibold text-gray-800 mb-1 h-10" title="{{ $product->name }}">{{ Str::limit($product->name, 50) }}</h3>
                            <p class="text-lg font-extrabold text-red-500">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
                            <div class="mt-auto pt-3">
                                <button class="w-full bg-indigo-500 text-white font-bold py-2.5 rounded-lg text-sm hover:bg-indigo-600 transition-colors flex items-center justify-center gap-2">
                                    <i class="fas fa-cart-plus"></i>
                                    <span>Keranjang</span>
                                </button>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full text-center py-16">
                        <p class="text-gray-500">Oops! Belum ada produk yang bisa ditampilkan saat ini.</p>
                    </div>
                @endforelse
            </div>
            {{-- Menampilkan link paginasi --}}
            <div class="flex justify-center mt-10">
                {{ $products->links() }}
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Inisialisasi Hero Swiper
    new Swiper(".heroSwiper", {
        loop: true,
        effect: "fade",
        autoplay: { delay: 4000, disableOnInteraction: false },
        pagination: { el: ".swiper-pagination", clickable: true },
    });

    // Inisialisasi Flash Sale Swiper
    new Swiper(".flashSaleSwiper", {
        slidesPerView: 2,
        spaceBetween: 10,
        navigation: {
            nextEl: ".flash-sale-next",
            prevEl: ".flash-sale-prev",
        },
        breakpoints: {
            640: { slidesPerView: 3, spaceBetween: 15 },
            768: { slidesPerView: 4, spaceBetween: 20 },
            1024: { slidesPerView: 5, spaceBetween: 20 },
        },
    });
});
</script>
@endpush

