@extends('layouts.customer')

@section('title', 'Kategori: ' . $category->name)

@push('styles')
<style>
    /* Style tambahan untuk banner di halaman ini jika diperlukan */
    .swiper-button-next, .swiper-button-prev {
        color: black; background-color: rgba(255, 255, 255, 0.8); width: 38px; height: 38px;
        border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    }
    .swiper-button-next::after, .swiper-button-prev::after { font-size: 16px; font-weight: bold; }
    .swiper-pagination-bullet-active { background-color: #ef4444 !important; }
</style>
@endpush

@section('content')
<div class="container mx-auto py-6 px-4">

    <!-- Hero Section Banner -->
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
    
        <!-- Side Banners (Statis) -->
        <div class="grid grid-rows-2 gap-4 h-[200px] md:h-[270px]">
            <div class="rounded-lg overflow-hidden shadow-sm">
                <img src="https://placehold.co/400x190/3b82f6/white?text=Yuk+Belanja+Di+Sancaka" class="w-full h-full object-cover" alt="Side Banner 1">
            </div>
            <div class="rounded-lg overflow-hidden shadow-sm">
                 <img src="https://placehold.co/400x190/10b981/white?text=Belanja+Hemat+Pasti+Puas" class="w-full h-full object-cover" alt="Side Banner 2">
            </div>
        </div>
    </section>

    {{-- Header Halaman Kategori --}}
    <div class="bg-white p-4 rounded-lg shadow-sm mb-6 border-l-4 border-red-500">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas {{ $category->icon ?? 'fa-tag' }} mr-2 text-red-500"></i>
            Kategori: {{ $category->name }}
        </h1>
    </div>

    {{-- Bagian untuk menampilkan semua produk dalam kategori ini --}}
    <section>
        <div class="bg-white p-3 rounded-lg shadow-sm">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                
                @forelse ($products as $product)
                    <div class="bg-white border rounded-lg overflow-hidden group hover:shadow-lg transition-shadow flex flex-col text-left">
                        <a href="#">
                            <div class="aspect-square bg-white relative">
                                <img src="{{ $product->image_url ?? 'https://placehold.co/400' }}" alt="{{ $product->name }}" class="w-full h-full object-contain p-2">
                                @if(isset($product->discount_percentage) && $product->discount_percentage > 0)
                                <span class="absolute top-2 left-2 bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded-full">{{ $product->discount_percentage }}%</span>
                                @endif
                            </div>
                        </a>
                        <div class="p-3 flex flex-col flex-grow">
                            <h3 class="text-sm font-medium text-gray-800 leading-tight h-10">{{ Str::limit($product->name, 45) }}</h3>
                            <p class="text-base font-bold text-red-600 mt-2">Rp{{ number_format($product->price) }}</p>
                            @if(isset($product->original_price) && $product->original_price > $product->price)
                            <s class="text-xs text-gray-400">Rp{{ number_format($product->original_price) }}</s>
                            @endif
                            <div class="text-xs text-gray-500 mt-2">Terjual {{ $product->sold_count ?? 0 }}</div>
                            <div class="mt-auto pt-3">
                                @if($product->stock > 0)
                                <form action="{{ route('customer.cart.add', $product) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full bg-red-500 text-white font-bold py-2 rounded-md text-sm hover:bg-red-600 transition-colors">+ Keranjang</button>
                                </form>
                                @else
                                <button class="w-full bg-gray-300 text-gray-500 font-bold py-2 rounded-md text-sm cursor-not-allowed">Stok Habis</button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-16">
                        <i class="fas fa-box-open text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Belum ada produk untuk kategori ini.</p>
                    </div>
                @endforelse
            </div>
            
            <div class="flex justify-center mt-8">
                {{ $products->links() }}
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
{{-- Script untuk mengaktifkan Swiper.js pada banner --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Swiper(".heroSwiper", { 
        loop: true, 
        effect: "fade", 
        autoplay: { delay: 4000, disableOnInteraction: false }, 
        pagination: { el: ".swiper-pagination", clickable: true },
        navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" }
    });
});
</script>
@endpush

