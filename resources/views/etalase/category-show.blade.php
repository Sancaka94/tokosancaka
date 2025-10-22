@extends('layouts.marketplace')

@section('title', 'Kategori: ' . $category->name)

@push('styles')
<style>
    /* Custom styles for Swiper navigation buttons */
    .swiper-button-next, .swiper-button-prev {
        color: white; background-color: rgba(0, 0, 0, 0.3); width: 40px; height: 40px;
        border-radius: 50%; transition: background-color: 0.3s;
    }
    .swiper-button-next:hover, .swiper-button-prev:hover { background-color: rgba(0, 0, 0, 0.5); }
    .swiper-button-next::after, .swiper-button-prev::after { font-size: 18px; font-weight: bold; }
    .swiper-pagination-bullet-active { background-color: #ef4444 !important; }
</style>
@endpush

@section('content')
<div class="container mx-auto py-6 px-4">

    <!-- Hero Section Banner -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
        <!-- Main Carousel -->
        <div class="lg:col-span-2 rounded-2xl overflow-hidden shadow-lg h-[200px] md:h-[300px] lg:h-[420px]" data-aos="fade-right">
            <div class="swiper heroSwiper w-full h-full">
                <div class="swiper-wrapper">
                    @forelse($banners as $banner)
                        <div class="swiper-slide">
                            <img src="{{ asset('storage/' . $banner->image) }}" class="w-full h-full object-fill" alt="{{ $banner->title ?? 'Promo Banner' }}">
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

    <!-- Header Halaman Kategori -->
    <div class="bg-white p-5 rounded-2xl shadow-md mb-8 border-l-8 border-red-500" data-aos="fade-down">
        <h1 class="text-3xl font-bold text-gray-800">
            <i class="fas {{ $category->icon ?? 'fa-tag' }} mr-3 text-red-500"></i>
            Kategori: {{ $category->name }}
        </h1>
    </div>

    <!-- Daftar Produk dalam Kategori -->
    <section data-aos="fade-up">
        <div class="p-5 bg-white rounded-2xl shadow-md">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-5">
                @forelse ($products as $product)
                    <div class="bg-white border rounded-xl overflow-hidden group hover:shadow-2xl transition-all duration-300 flex flex-col">
                        <a href="{{ route('etalase.show', $product->slug) }}">
                            <div class="h-48 bg-gray-50 relative">
                               @php
                                    $imageUrl = $product->image_url ? asset('storage/' . $product->image_url) : 'https://placehold.co/400x400/EFEFEF/333333?text=N/A';
                                @endphp
                                <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="w-full h-full object-fill group-hover:scale-105 transition-transform">
                            </div>
                        </a>
                        <div class="p-4 flex flex-col flex-grow">
                            <h3 class="text-sm font-semibold text-gray-800 mb-1 h-10">{{ Str::limit($product->name, 50) }}</h3>
                            
                            <div class="flex items-center text-xs text-gray-500 mb-2">
                                <i class="fas fa-store w-3 h-3 mr-1.5 text-gray-400"></i>
                                @if ($product->store_name)
                                    <span class="truncate">{{ $product->store_name }}</span>
                                @else
                                    <span class="truncate">{{ 'Toko Sancaka' }}</span>
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
                    </div>
                @empty
                    <div class="col-span-full text-center py-20">
                        <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-700">Belum Ada Produk</h3>
                        <p class="text-gray-500 mt-2">Coba lihat kategori lainnya.</p>
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
    // Inisialisasi Hero Swiper
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
