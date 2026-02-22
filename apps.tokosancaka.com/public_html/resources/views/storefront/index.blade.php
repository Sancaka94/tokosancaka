@extends('layouts.marketplace')

@section('title', 'Beranda')

@section('content')
<div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24 text-center md:text-left flex flex-col md:flex-row items-center">
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

<div id="katalog" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <div class="mb-10">
        <h3 class="text-xl font-bold mb-4 text-gray-900">Kategori Pilihan</h3>
        <div class="flex overflow-x-auto gap-4 pb-4 snap-x hide-scrollbar">
            <a href="{{ route('storefront.index', $subdomain) }}" class="flex-shrink-0 snap-start bg-white border border-blue-500 text-blue-600 px-6 py-2 rounded-full text-sm font-bold shadow-sm">Semua</a>
            @foreach($categories as $cat)
                <a href="{{ route('storefront.category', ['subdomain' => $subdomain, 'slug' => $cat->slug]) }}"
                   class="flex-shrink-0 snap-start bg-white border border-gray-200 text-gray-600 hover:border-blue-500 hover:text-blue-600 px-6 py-2 rounded-full text-sm font-semibold shadow-sm transition">
                    {{ $cat->name }}
                </a>
            @endforeach
        </div>
    </div>

    <h3 class="text-xl font-bold mb-6 text-gray-900">Produk Terbaru</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
        @forelse($products as $product)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition flex flex-col h-full group">
                <div class="aspect-square bg-gray-100 relative overflow-hidden flex items-center justify-center">
                    @if($product->image)
                        <img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    @else
                        <i data-lucide="package" class="w-12 h-12 text-gray-300"></i>
                    @endif
                    @if($product->stock < 5 && $product->stock > 0)
                        <span class="absolute top-2 left-2 bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded">Sisa {{ $product->stock }}!</span>
                    @endif
                </div>

                <div class="p-4 flex flex-col flex-grow">
                    <h4 class="text-sm font-semibold text-gray-800 line-clamp-2 mb-2 flex-grow">{{ $product->name }}</h4>
                    <p class="text-blue-600 font-black mb-4">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</p>

                    <button @click="addToCart({{ json_encode([
                                'id' => $product->id,
                                'name' => $product->name,
                                'sell_price' => $product->sell_price,
                                'image' => $product->image ? asset('storage/'.$product->image) : ''
                            ]) }})"
                            class="w-full bg-blue-50 text-blue-700 border border-blue-100 py-2 rounded-xl text-sm font-bold hover:bg-blue-600 hover:text-white hover:border-blue-600 transition flex justify-center items-center gap-2 active:scale-95">
                        <i data-lucide="plus" class="w-4 h-4"></i> Keranjang
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-20 text-gray-500">
                <i data-lucide="package-open" class="w-16 h-16 mx-auto mb-4 opacity-50"></i>
                <p>Belum ada produk yang dijual di toko ini.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-10">
        {{ $products->links() }}
    </div>
</div>
@endsection
