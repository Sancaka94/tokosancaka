@extends('layouts.marketplace')

@section('title', 'Beranda')

@section('content')
<div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20 text-center md:text-left flex flex-col md:flex-row items-center">
        <div class="md:w-1/2">
            <h2 class="text-4xl md:text-5xl font-black mb-4 leading-tight">Temukan Produk Terbaik Hanya di Sini.</h2>
            <p class="text-blue-200 text-lg mb-8">Kualitas terjamin dengan pelayanan pengiriman ke seluruh Indonesia.</p>
            <a href="#katalog" class="bg-white text-blue-800 px-8 py-3 rounded-full font-bold shadow-lg hover:bg-gray-100 transition inline-block">Mulai Belanja</a>
        </div>
        <div class="md:w-1/2 mt-10 md:mt-0 hidden md:flex justify-center">
            <i data-lucide="shopping-cart" class="w-48 h-48 text-white opacity-20"></i>
        </div>
    </div>
</div>

<div id="katalog" class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8 py-8 bg-gray-50">

    <div class="mb-8 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-bold mb-4 text-gray-800 uppercase tracking-wide">Kategori</h3>
        <div class="flex overflow-x-auto gap-3 pb-2 snap-x hide-scrollbar">
            <a href="{{ route('storefront.index', $subdomain) }}" class="flex-shrink-0 snap-start bg-blue-50 border border-blue-200 text-blue-700 px-5 py-2 rounded-lg text-sm font-bold shadow-sm">Semua</a>
            @foreach($categories as $cat)
                <a href="{{ route('storefront.category', ['subdomain' => $subdomain, 'slug' => $cat->slug]) }}"
                   class="flex-shrink-0 snap-start bg-white border border-gray-200 text-gray-600 hover:border-blue-500 hover:text-blue-600 px-5 py-2 rounded-lg text-sm font-medium shadow-sm transition">
                    {{ $cat->name }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="mb-8 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center gap-2">
                <i data-lucide="zap" class="w-6 h-6 text-orange-500 fill-current"></i>
                <h3 class="text-xl font-black text-gray-900 italic">FLASH SALE</h3>
            </div>
            <a href="#" class="text-sm text-blue-600 font-semibold hover:underline">Lihat Semua ></a>
        </div>

        <div class="flex overflow-x-auto gap-3 pb-4 snap-x hide-scrollbar">
            @foreach($products->take(5) as $product)
                @include('components.product-card', ['product' => $product, 'is_horizontal' => true])
            @endforeach
        </div>
    </div>

    <div class="mb-6 flex items-center justify-between">
        <h3 class="text-lg font-bold text-gray-900 uppercase border-b-4 border-blue-600 inline-block pb-1">Rekomendasi Untukmu</h3>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 md:gap-4 gap-2">
        @forelse($products as $product)
            @include('components.product-card', ['product' => $product, 'is_horizontal' => false])
        @empty
            <div class="col-span-full text-center py-20 text-gray-500 bg-white rounded-xl">
                <i data-lucide="package-open" class="w-16 h-16 mx-auto mb-4 opacity-50"></i>
                <p>Belum ada produk yang dijual di toko ini.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-10 flex justify-center">
        {{ $products->links() }}
    </div>
</div>
@endsection
