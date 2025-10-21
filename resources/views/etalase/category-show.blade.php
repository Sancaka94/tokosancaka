{{-- resources/views/etalase/category-show.blade.php --}}

@extends('layouts.marketplace')

@section('title', 'Kategori: ' . $category->name)

@section('content')
<div class="container mx-auto py-6 px-4">

    <!-- Hero Section Banner (opsional, bisa disesuaikan) -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
        <div class="lg:col-span-2 rounded-2xl overflow-hidden shadow-lg h-[200px] md:h-[300px] lg:h-[420px]">
            <div class="swiper heroSwiper w-full h-full">
                <div class="swiper-wrapper">
                    @forelse($banners as $banner)
                        <div class="swiper-slide">
                            <img src="{{ asset('storage/' . $banner->image) }}" class="w-full h-full object-fill" alt="{{ $banner->title ?? 'Promo' }}">
                        </div>
                    @empty
                        <div class="swiper-slide">
                            <img src="[https://placehold.co/800x400/ef4444/white?text=Sancaka+Store](https://placehold.co/800x400/ef4444/white?text=Sancaka+Store)" class="w-full h-full object-cover" alt="Default Banner">
                        </div>
                    @endforelse
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            </div>
        </div>
        <div class="grid grid-rows-2 gap-6 h-[400px] sm:h-[250px] md:h-[300px] lg:h-[420px]">
            @if(isset($settings['banner_2']))
            <div class="rounded-2xl overflow-hidden shadow-lg"><img src="{{ asset('storage/' . $settings['banner_2']) }}" class="w-full h-full object-fill" alt="Banner 2"></div>
            @endif
            @if(isset($settings['banner_3']))
            <div class="rounded-2xl overflow-hidden shadow-lg"><img src="{{ asset('storage/' . $settings['banner_3']) }}" class="w-full h-full object-fill" alt="Banner 3"></div>
            @endif
        </div>
    </section>

    <!-- Header Halaman Kategori -->
    <div class="bg-white p-5 rounded-2xl shadow-md mb-6 border-l-4 border-red-500">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas {{ $category->icon ?? 'fa-tag' }} mr-3 text-red-500"></i>
            Kategori: {{ $category->name }}
        </h1>
    </div>

    <!-- Daftar Produk Kategori -->
    <section>
        <div class="p-5 bg-white rounded-2xl shadow-md">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-5">
                @forelse ($products as $product)
                    <a href="{{ route('products.show', $product->slug) }}" class="bg-white border rounded-xl overflow-hidden group hover:shadow-2xl transition-all duration-300 flex flex-col">
                        <div class="h-48 bg-gray-50 relative">
                            @php
                                $imageUrl = $product->image_url ? asset('storage/' . $product->image_url) : '[https://placehold.co/400x400/EFEFEF/333333?text=N/A](https://placehold.co/400x400/EFEFEF/333333?text=N/A)';
                            @endphp
                            <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="w-full h-full object-fill group-hover:scale-105 transition-transform">
                        </div>
                        <div class="p-4 flex flex-col flex-grow">
                            <h3 class="text-sm font-semibold text-gray-800 mb-1 h-10">{{ Str::limit($product->name, 50) }}</h3>
                            <div class="flex items-center text-xs text-gray-500 mb-2">
                                <i class="fas fa-store w-3 h-3 mr-1.5 text-gray-400"></i>
                                <span class="truncate">{{ $product->store->name ?? 'Toko Sancaka' }}</span>
                            </div>
                            <p class="text-lg font-extrabold text-red-500">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
                            @if($product->original_price)
                            <div class="flex items-center space-x-2 mt-1">
                                <span class="text-xs text-gray-400 line-through">Rp{{ number_format($product->original_price, 0, ',', '.') }}</span>
                                <span class="text-xs font-bold text-red-600 bg-red-100 px-1.5 py-0.5 rounded">{{ round($product->discount_percentage) }}%</span>
                            </div>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="col-span-full text-center py-20">
                         <i class="fas fa-box-open text-5xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg">Oops! Belum ada produk untuk kategori ini.</p>
                    </div>
                @endforelse
            </div>
            <div class="text-center mt-10">{{ $products->links() }}</div>
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
        navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
    });
});
</script>
@endpush