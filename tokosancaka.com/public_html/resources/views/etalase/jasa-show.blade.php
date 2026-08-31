@extends('layouts.marketplace')

@section('title', 'Jasa ' . $layanan->nama_layanan . ' - Sancaka')

@section('content')
<div class="container mx-auto py-4 px-2 md:px-4 max-w-7xl">

    {{-- Banner Jasa --}}
    <div class="bg-red-600 rounded-lg shadow-md p-6 md:p-8 mb-6 text-white flex flex-col md:flex-row items-center justify-between">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold mb-2">{{ $layanan->nama_layanan }}</h1>
            <p class="text-sm md:text-base opacity-90"><i class="fas fa-tag"></i> {{ $layanan->nama_bidang }} / {{ $layanan->nama_sub_bidang }}</p>
        </div>
        <div class="mt-4 md:mt-0 text-right bg-white text-red-600 px-4 py-2 rounded-lg font-bold">
            <span class="text-xs text-gray-500 block">Tarif Dasar Mulai:</span>
            Rp{{ number_format($layanan->tarif_dasar, 0, ',', '.') }} <span class="text-xs font-normal">/ {{ $layanan->tipe_satuan }}</span>
        </div>
    </div>

    {{-- List Teknisi / Mitra --}}
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-800">Daftar Teknisi / Mitra Tersedia</h2>
        <span class="text-sm text-gray-500">{{ $products->total() }} Mitra Ditemukan</span>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2 md:gap-3">
        @forelse ($products as $product)
            @php
                $imgSrc = $product->image_url ? asset('public/storage/' . $product->image_url) : 'https://placehold.co/300x300?text=Logo+Mitra';
            @endphp
            
            <div class="product-card group bg-white border border-gray-100 rounded-lg shadow-sm hover:shadow-md transition overflow-hidden">
                <a href="{{ url('/products/' . $product->slug) }}" class="block relative p-2">
                    <img src="{{ $imgSrc }}" alt="{{ $product->name }}" class="w-full h-32 object-cover rounded-md">
                    <div class="absolute bottom-2 left-2 bg-green-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded">Verified</div>
                </a>
                
                <div class="p-3">
                    <a href="{{ url('/products/' . $product->slug) }}" class="block">
                        <h3 class="text-xs md:text-sm font-bold text-gray-800 line-clamp-2" title="{{ $product->name }}">
                            {{ $product->name }}
                        </h3>
                        <p class="text-[10px] text-gray-500 mt-1"><i class="fas fa-store text-red-500"></i> {{ $product->store_name ?? 'Sancaka Mitra' }}</p>
                    </a>
                    
                    <div class="mt-3 flex justify-between items-end">
                        <span class="text-red-600 font-bold text-sm">Rp{{ number_format($product->price, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center text-gray-500 bg-white rounded-lg shadow-sm">
                <i class="fas fa-tools text-4xl mb-3 text-gray-300"></i>
                <p>Belum ada teknisi/mitra yang mendaftar untuk layanan ini.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $products->links('pagination::tailwind') }}
    </div>

</div>
@endsection