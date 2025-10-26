@extends('layouts.marketplace')

@section('title', 'Sancaka Marketplace - Diskon Gila-gilaan, Harga Paling Murah!')

@push('styles')
<style>
    /* Custom gradient for flash sale background */
    .flash-sale-bg {
        background: linear-gradient(90deg, rgba(220,38,38,1) 0%, rgba(239,68,68,1) 100%);
    }

    /* Custom styles for Swiper navigation buttons */
    .swiper-button-next, .swiper-button-prev {
        color: white;
        background-color: rgba(0, 0, 0, 0.3);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        transition: background-color 0.3s;
    }
    .swiper-button-next:hover, .swiper-button-prev:hover {
        background-color: rgba(0, 0, 0, 0.5);
    }
    .swiper-button-next::after, .swiper-button-prev::after {
        font-size: 18px;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto py-6 px-4">

    <!-- Hero Section -->
   <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
        <!-- Main Carousel -->
        <div class="lg:col-span-2 rounded-2xl overflow-hidden shadow-lg h-[200px] md:h-[300px] lg:h-[420px]" data-aos="fade-right">
            <div class="swiper heroSwiper w-full h-full">
                <div class="swiper-wrapper">
                    @forelse($banners as $banner)
                        <div class="swiper-slide">
                            <img src="{{ asset('public/storage/' . $banner->image) }}" class="w-full h-full object-fill" alt="{{ $banner->title ?? 'Promo Banner' }}">
                        </div>
                    @empty
                        <div class="swiper-slide">
                            <img src="https://placehold.co/800x420/e2e8f0/94a3b8?text=Sancaka+Express" class="w-full h-full object-fill" alt="Default Banner">
                        </div>
                    @endforelse
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    
        <!-- Side Banners -->
       <div class="grid grid-rows-2 gap-6 h-[400px] sm:h-[250px] md:h-[300px] lg:h-[420px]" data-aos="fade-left">
        @if(isset($settings['banner_2']))
        <div class="rounded-2xl overflow-hidden shadow-lg">
            <img src="{{ asset('storage/' . $settings['banner_2']) }}" class="w-full h-full object-fill" alt="Banner 2">
        </div>
        @endif

        @if(isset($settings['banner_3']))
        <div class="rounded-2xl overflow-hidden shadow-lg">
            <img src="{{ asset('storage/' . $settings['banner_3']) }}" class="w-full h-full object-fill" alt="Banner 3">
        </div>
        @endif
    </div>
    </section>

    <!-- Kategori Pilihan -->
    <section class="bg-white p-6 rounded-2xl shadow-md mb-10 relative" data-aos="fade-up">
        <h2 class="text-xl font-bold mb-5 text-gray-800">Kategori Pilihan</h2>
        
        <div class="swiper categoriesSwiper">
            <div class="swiper-wrapper">
                @php
                    $categoryChunks = $categories->chunk(20);
                @endphp

                @forelse ($categoryChunks as $chunk)
                <div class="swiper-slide">
                    <div class="grid grid-cols-5 md:grid-cols-10 gap-x-4 gap-y-6 text-center">
                        @foreach ($chunk as $category)
                        {{-- PERBAIKAN: Menggunakan URL langsung untuk menghindari cache route --}}
                        <a href="{{ url('/etalase/category/' . $category->slug) }}" class="flex flex-col items-center space-y-2 text-gray-600 hover:text-red-500 transition-colors group">
                            <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center group-hover:bg-red-100 transition-colors">
                                <i class="fas {{ $category->icon ?? 'fa-tag' }} text-3xl text-gray-500 group-hover:text-red-500 transition-colors"></i>
                            </div>
                            <span class="text-sm font-medium">{{ $category->name }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
                @empty
                <div class="swiper-slide">
                    <p class="w-full text-center text-gray-500">Tidak ada kategori untuk ditampilkan.</p>
                </div>
                @endforelse
            </div>
        </div>
        
        <div class="swiper-button-next category-next !text-gray-600 !bg-white !shadow-md !w-10 !h-10"></div>
        <div class="swiper-button-prev category-prev !text-gray-600 !bg-white !shadow-md !w-10 !h-10"></div>
    </section>


    <!-- Flash Sale Section -->
    @if($flashSaleProducts->isNotEmpty())
    <section class="mb-10" data-aos="fade-up">
        <div class="flash-sale-bg p-5 rounded-t-2xl flex justify-between items-center">
            <h2 class="text-3xl font-extrabold text-white tracking-wide">FLASH SALE</h2>
        </div>
        <div class="bg-white p-5 rounded-b-2xl shadow-md">
            <div class="relative">
                <div class="swiper flashSaleSwiper">
                    <div class="swiper-wrapper py-2">
                        @foreach ($flashSaleProducts as $product)
                        <div class="swiper-slide h-auto pb-2">
                            {{-- PERBAIKAN: Menggunakan URL langsung untuk menghindari cache route --}}
                            <a href="{{ url('/products/' . $product->slug) }}" class="block border rounded-xl overflow-hidden group hover:shadow-xl transition-all duration-300 h-full flex flex-col bg-white">
                                <div class="relative">
                                    <div class="h-48 bg-gray-50">
                                        @php
                                            $imageUrl = $product->image_url ? asset('storage/' . $product->image_url) : 'https://placehold.co/400x400/EFEFEF/333333?text=N/A';
                                        @endphp
                                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="w-full h-full object-fill group-hover:scale-105 transition-transform">
                                    </div>
                                    <span class="absolute top-2 left-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded-full">{{ round($product->discount_percentage) }}%</span>
                                </div>
                                <div class="p-3 flex flex-col flex-grow">
                                    <h3 class="text-sm font-semibold text-gray-800 truncate">{{ $product->name }}</h3>
                                    <p class="text-lg font-extrabold text-red-500 mt-1">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
                                    @if($product->original_price)
                                    <span class="text-xs text-gray-400 line-through">Rp{{ number_format($product->original_price, 0, ',', '.') }}</span>
                                    @endif
                                    <div class="mt-auto pt-2 text-xs text-gray-500">Terjual {{ $product->sold_count ?? 0 }}</div>
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="swiper-button-next flash-sale-next !text-gray-600 !bg-white !shadow-md !w-10 !h-10"></div>
                <div class="swiper-button-prev flash-sale-prev !text-gray-600 !bg-white !shadow-md !w-10 !h-10"></div>
            </div>
        </div>
    </section>
    @endif

    <!-- Product Recommendations -->
    <section data-aos="fade-up">
        <div class="bg-white p-5 rounded-t-2xl border-b-2 border-gray-100">
            <h2 class="text-xl font-bold text-center text-gray-800">REKOMENDASI UNTUKMU</h2>
        </div>
        <div class="p-5 bg-white rounded-b-2xl shadow-md">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-5">
                @forelse ($products as $product)
                    {{-- PERBAIKAN: Menggunakan URL langsung untuk menghindari cache route --}}
                    <a href="{{ url('/products/' . $product->slug) }}" class="bg-white border rounded-xl overflow-hidden group hover:shadow-2xl transition-all duration-300 flex flex-col">
                        <div>
                            <div class="h-48 bg-gray-50 relative">
                               @php
                                    $imageUrl = $product->image_url ? asset('storage/' . $product->image_url) : 'https://placehold.co/400x400/EFEFEF/333333?text=N/A';
                                @endphp
                                <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="w-full h-full object-fill group-hover:scale-105 transition-transform">
                            </div>
                        </div>
                        <div class="p-4 flex flex-col flex-grow">
                            <h3 class="text-sm font-semibold text-gray-800 mb-1 h-10">{{ Str::limit($product->name, 50) }}</h3>
                            
                            <div class="flex items-center text-xs text-gray-500 mb-2">
                                <i class="fas fa-store w-3 h-3 mr-1.5 text-gray-400"></i>
                                @if ($product->store)
                                    <span class="truncate">{{ $product->store->name }}</span>
                                @else
                                    <span class="truncate">{{ $product->store_name ?? 'Toko Sancaka' }}</span>
                                @endif
                            </div>
                            
                            <p class="text-lg font-extrabold text-red-500">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
                            @if($product->original_price)
                            <div class="flex items-center space-x-2 mt-1">
                                <span class="text-xs text-gray-400 line-through">Rp{{ number_format($product->original_price, 0, ',', '.') }}</span>
                                <span class="text-xs font-bold text-red-600 bg-red-100 px-1.5 py-0.5 rounded">{{ round($product->discount_percentage) }}%</span>
                            </div>
                            @endif
                            <div class="mt-auto pt-3">
                                <form action="{{ route('cart.add', ['product' => $product->id]) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="w-full bg-red-500 text-white font-bold py-2.5 rounded-lg text-sm hover:bg-red-600 transition-colors flex items-center justify-center gap-2">
                                        <i class="fas fa-cart-plus"></i>
                                        <span>Keranjang</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full text-center py-16">
                        <p class="text-gray-500">Oops! Belum ada produk yang bisa ditampilkan.</p>
                    </div>
                @endforelse
            </div>
            <div class="text-center mt-10">{{ $products->links() }}</div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
{{-- Script Swiper (tidak berubah) --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Swiper(".heroSwiper", { loop: true, effect: "fade", autoplay: { delay: 4000, disableOnInteraction: false }, pagination: { el: ".swiper-pagination", clickable: true }, navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" }, });
    new Swiper(".flashSaleSwiper", { slidesPerView: 2, spaceBetween: 10, navigation: { nextEl: ".flash-sale-next", prevEl: ".flash-sale-prev", }, breakpoints: { 640: { slidesPerView: 3, spaceBetween: 15 }, 768: { slidesPerView: 4, spaceBetween: 20 }, 1024: { slidesPerView: 5, spaceBetween: 20 }, }, });
    new Swiper(".categoriesSwiper", { loop: false, slidesPerView: 1, spaceBetween: 20, navigation: { nextEl: ".category-next", prevEl: ".category-prev", }, });
});
</script>
@endpush

