@extends('layouts.marketplace')

@section('title', $product->name)

@section('content')
<div class="bg-gray-50 py-8 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <nav class="flex text-sm text-gray-500 mb-6 items-center">
            <a href="{{ route('storefront.index', $subdomain) }}" class="hover:text-blue-600 font-medium">Beranda</a>
            <i data-lucide="chevron-right" class="w-4 h-4 mx-2"></i>
            <span class="text-gray-800 font-bold truncate">{{ $product->name }}</span>
        </nav>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col md:flex-row">

            <div class="md:w-2/5 p-4 md:p-8 flex justify-center items-center bg-gray-50 border-r border-gray-100">
                <div class="aspect-square w-full relative overflow-hidden rounded-lg bg-white flex justify-center items-center shadow-sm">
                    @if($product->image)
                        <img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-contain">
                    @else
                        <i data-lucide="image" class="w-24 h-24 text-gray-300"></i>
                    @endif
                </div>
            </div>

            <div class="md:w-3/5 p-6 md:p-8 flex flex-col">

                <div class="flex items-center gap-3 mb-3 text-sm">
                    @if(($product->sold ?? 0) > 50)
                        <span class="bg-orange-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm flex items-center gap-1">
                            <i data-lucide="star" class="w-3 h-3 fill-current"></i> Star+
                        </span>
                    @endif
                    <div class="flex items-center text-yellow-400">
                        <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                        <span class="text-gray-700 ml-1 font-medium">{{ $product->rating ?? '5.0' }}</span>
                    </div>
                    <span class="text-gray-300">|</span>
                    <span class="text-gray-600">{{ $product->sold ?? 0 }} Terjual</span>
                </div>

                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4 leading-snug">{{ $product->name }}</h1>

                <div class="bg-gray-50 p-5 rounded-xl mb-6 border border-gray-100">
                    @if(isset($product->base_price) && $product->base_price > $product->sell_price)
                        <div class="text-gray-400 line-through text-sm mb-1">
                            Rp {{ number_format($product->base_price, 0, ',', '.') }}
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-3xl md:text-4xl font-black text-blue-700">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</span>
                            @php
                                $discount = round((($product->base_price - $product->sell_price) / $product->base_price) * 100);
                            @endphp
                            <span class="bg-red-100 text-red-600 text-xs font-black px-2 py-1 rounded">
                                -{{ $discount }}%
                            </span>
                        </div>
                    @else
                        <div class="text-3xl md:text-4xl font-black text-blue-700">
                            Rp {{ number_format($product->sell_price, 0, ',', '.') }}
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-4 mb-3 text-sm">
                    <span class="text-gray-500 w-24">Kondisi</span>
                    <span class="font-semibold text-gray-800">Baru</span>
                </div>
                <div class="flex items-center gap-4 mb-3 text-sm">
                    <span class="text-gray-500 w-24">Berat</span>
                    <span class="font-semibold text-gray-800">{{ $product->weight ?? 1000 }} Gram</span>
                </div>
                <div class="flex items-center gap-4 mb-8 text-sm">
                    <span class="text-gray-500 w-24">Sisa Stok</span>
                    <span class="font-bold text-blue-600">{{ $product->stock }} {{ $product->unit ?? 'pcs' }}</span>
                </div>

                <div class="mt-auto flex gap-3">
                    <button @click="addToCart({{ json_encode([
                                'id' => $product->id,
                                'name' => $product->name,
                                'sell_price' => $product->sell_price,
                                'image' => $product->image ? asset('storage/'.$product->image) : ''
                            ]) }})"
                            class="flex-1 bg-blue-600 text-white px-6 py-3.5 rounded-lg font-bold text-lg hover:bg-blue-700 hover:shadow-lg transition flex items-center justify-center gap-2 active:scale-95">
                        <i data-lucide="shopping-cart" class="w-5 h-5"></i> Masukkan Keranjang
                    </button>
                </div>

            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 md:p-8 mt-6">
            <h3 class="text-lg font-bold text-gray-900 border-b border-gray-200 pb-3 mb-4">Deskripsi Produk</h3>
            <div class="prose max-w-none text-gray-700 text-sm leading-relaxed whitespace-pre-line">
                {{ $product->description ?? 'Tidak ada deskripsi detail untuk produk ini.' }}
            </div>
        </div>

    </div>
</div>
@endsection
