@extends('layouts.customer')

@section('title', 'Toko Sancaka - Belanja Hemat Pasti Puas')

@push('styles')
<style>
    /* Custom styles untuk tombol navigasi Swiper agar lebih terlihat */
    .swiper-button-next, .swiper-button-prev {
        color: black;
        background-color: rgba(255, 255, 255, 0.8);
        width: 38px;
        height: 38px;
        border-radius: 50%;
        box-shadow: 0 2px 5px rgba(0,0,0,0.15);
        transition: background-color 0.3s;
    }
    .swiper-button-next:hover, .swiper-button-prev:hover {
        background-color: white;
    }
    .swiper-button-next::after, .swiper-button-prev::after {
        font-size: 16px;
        font-weight: bold;
    }
    /* Menargetkan tombol navigasi flash sale secara spesifik */
    .flash-sale-nav {
        top: 50%;
        transform: translateY(-50%);
    }
    .swiper-pagination-bullet-active {
        background-color: #ef4444 !important;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto py-6 px-4">

    <!-- Hero Section -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
        <!-- Main Carousel -->
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
    
        <!-- Side Banners -->
        <div class="grid grid-rows-2 gap-4 h-[200px] md:h-[270px]">
            <div class="rounded-lg overflow-hidden shadow-sm">
                <img src="https://placehold.co/400x190/3b82f6/white?text=Yuk+Belanja+Di+Sancaka" class="w-full h-full object-cover" alt="Side Banner 1">
            </div>
            <div class="rounded-lg overflow-hidden shadow-sm">
                 <img src="https://placehold.co/400x190/10b981/white?text=Belanja+Hemat+Pasti+Puas" class="w-full h-full object-cover" alt="Side Banner 2">
            </div>
        </div>
    </section>

    <!-- Categories -->
    <section class="bg-white p-5 rounded-lg shadow-sm mb-8">
        <h2 class="text-base font-semibold mb-4 text-gray-700">Kategori Pilihan</h2>
        <div class="grid grid-cols-5 md:grid-cols-10 gap-x-2 gap-y-4 text-center">
            @php
                $categories = [
                    ['name' => 'Elektronik', 'icon' => 'fa-mobile-alt'], ['name' => 'Fashion', 'icon' => 'fa-tshirt'],
                    ['name' => 'Rumah', 'icon' => 'fa-home'], ['name' => 'Kesehatan', 'icon' => 'fa-heartbeat'],
                    ['name' => 'Tagihan', 'icon' => 'fa-receipt'], ['name' => 'Voucher', 'icon' => 'fa-ticket-alt'],
                    ['name' => 'Travel', 'icon' => 'fa-plane'], ['name' => 'Keuangan', 'icon' => 'fa-gem'],
                    ['name' => 'Hobi', 'icon' => 'fa-gamepad'], ['name' => 'Lainnya', 'icon' => 'fa-th-large'],
                ];
            @endphp
            @foreach ($categories as $category)
                <a href="#" class="flex flex-col items-center space-y-2 text-gray-600 hover:text-red-500 transition-colors group">
                    <div class="w-12 h-12 flex items-center justify-center">
                        <i class="fas {{ $category['icon'] }} text-2xl text-gray-500 group-hover:text-red-500 transition-colors"></i>
                    </div>
                    <span class="text-xs font-medium">{{ $category['name'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <!-- Flash Sale Section -->
    @if($flashSaleProducts->isNotEmpty())
    <section class="mb-8">
        <div class="bg-red-600 p-3 rounded-t-lg">
            <h2 class="text-lg font-bold text-white tracking-wide">FLASH SALE</h2>
        </div>
        <div class="bg-white p-4 rounded-b-lg shadow-sm">
            <div class="relative">
                <!-- Tambahkan class 'overflow-hidden' agar slider tidak meluber keluar container -->
                <div class="swiper flashSaleSwiper overflow-hidden">
                    <div class="swiper-wrapper">
                        @foreach ($flashSaleProducts as $product)
                        <!-- Pastikan slide menggunakan 'h-full' agar tingginya seragam -->
                        <div class="swiper-slide h-full py-2">
                            <a href="#" class="block border rounded-lg overflow-hidden group h-full flex flex-col bg-white text-left transition-shadow duration-300 hover:shadow-md">
                                <div class="relative">
                                    <!-- 'aspect-square' membuat gambar selalu persegi dan rapi -->
                                    <div class="aspect-square bg-gray-100">
                                        <img src="{{ $product->image_url ? asset($product->image_url) : 'https://placehold.co/400' }}" alt="{{ $product->name }}" class="w-full h-full object-contain p-2">
                                    </div>
                                    <span class="absolute top-2 left-2 bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded-full">50%</span>
                                </div>
                                <div class="p-3 flex flex-col flex-grow">
                                    <h3 class="text-sm font-medium text-gray-800 leading-tight h-10">{{ Str::limit($product->name, 45) }}</h3>
                                    <p class="text-base font-bold text-red-600 mt-2">Rp{{ number_format($product->price) }}</p>
                                    <s class="text-xs text-gray-400">Rp{{ number_format($product->price * 2) }}</s>
                                    <div class="mt-auto pt-2 text-xs text-gray-500">Terjual {{ rand(10, 100) }}</div>
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
                <!-- Tombol Navigasi untuk Flash Sale -->
                <div class="swiper-button-next flash-sale-next flash-sale-nav"></div>
                <div class="swiper-button-prev flash-sale-prev flash-sale-nav"></div>
            </div>
        </div>
    </section>
    @endif

    <!-- Product Recommendations -->
    <section>
        <div class="bg-white p-3 rounded-lg shadow-sm">
            <h2 class="text-base font-semibold text-center text-gray-700 mb-4">REKOMENDASI UNTUKMU</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                @forelse ($products as $product)
                    <a href="#" class="bg-white border rounded-lg overflow-hidden group hover:shadow-lg transition-shadow flex flex-col text-left">
                        <div class="h-40 bg-white">
                            <img src="{{ $product->image_url ? asset($product->image_url) : 'https://placehold.co/400' }}" alt="{{ $product->name }}" class="w-full h-full object-contain p-2">
                        </div>
                        <div class="p-3 flex flex-col flex-grow">
                            <h3 class="text-sm font-medium text-gray-800 leading-tight h-10">{{ Str::limit($product->name, 45) }}</h3>
                            <p class="text-base font-bold text-red-600 mt-2">Rp{{ number_format($product->price) }}</p>
                            <div class="mt-auto pt-3">
                                <button class="w-full bg-red-500 text-white font-bold py-2 rounded-md text-sm hover:bg-red-600 transition-colors">
                                    + Keranjang
                                </button>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full text-center py-16">
                        <p class="text-gray-500">Belum ada produk untuk ditampilkan.</p>
                    </div>
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
    new Swiper(".heroSwiper", { 
        loop: true, 
        effect: "fade",
        autoplay: { delay: 4000, disableOnInteraction: false }, 
        pagination: { el: ".swiper-pagination", clickable: true },
        navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" }
    });
    new Swiper(".flashSaleSwiper", { 
        slidesPerView: 2.2, 
        spaceBetween: 10, 
        // Menambahkan navigasi ke Flash Sale Swiper
        navigation: {
            nextEl: ".flash-sale-next",
            prevEl: ".flash-sale-prev",
        },
        breakpoints: { 
            640: { slidesPerView: 3.2, spaceBetween: 15 }, 
            768: { slidesPerView: 4.2, spaceBetween: 15 }, 
            1024: { slidesPerView: 5.2, spaceBetween: 15 }
        }
    });
});
</script>
@endpush

