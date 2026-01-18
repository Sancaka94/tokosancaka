@extends('layouts.app')

@section('title', 'Detail Produk')

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- HEADER & NAVIGASI --}}
    <div class="flex justify-between items-start mb-6">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h1 class="text-2xl font-black text-slate-800">{{ $product->name }}</h1>

                {{-- Badge Kategori --}}
                @if($product->category)
                    <span class="px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-wide border
                        {{ $product->category->type == 'service' ? 'bg-purple-50 text-purple-600 border-purple-100' : 'bg-orange-50 text-orange-600 border-orange-100' }}">
                        {{ $product->category->name }}
                    </span>
                @endif

                {{-- Badge Varian --}}
                @if($product->has_variant)
                    <span class="px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-wide bg-indigo-50 text-indigo-600 border border-indigo-100">
                        Multi Varian
                    </span>
                @endif
            </div>
            <p class="text-sm text-slate-500">SKU: {{ $product->sku ?? '-' }}</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('products.index') }}" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition">
                &larr; Kembali
            </a>
            {{-- TOMBOL EDIT --}}
            <a href="{{ route('products.edit', $product->id) }}" class="px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-bold hover:bg-amber-600 shadow-lg shadow-amber-200 transition">
                <i class="fas fa-pencil-alt mr-1"></i> Edit
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

        {{-- KOLOM KIRI: FOTO & DESKRIPSI --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Foto Produk --}}
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
                <div class="aspect-square rounded-xl bg-slate-50 flex items-center justify-center overflow-hidden border border-slate-100">
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-contain">
                    @else
                        <div class="text-center text-slate-300">
                            <i class="fas fa-image text-5xl mb-2"></i>
                            <p class="text-xs font-bold">Tidak ada foto</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Deskripsi --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-3 mb-3">Deskripsi</h3>
                <p class="text-sm text-slate-600 leading-relaxed">
                    {{ $product->description ?? 'Tidak ada deskripsi tambahan.' }}
                </p>
            </div>
        </div>

        {{-- KOLOM KANAN: STATISTIK & VARIAN --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

                {{-- STATISTIK UTAMA --}}
                <div class="p-8 border-b border-slate-100">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        {{-- Sisa Stok --}}
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Total Stok</p>
                            @if($product->category && $product->category->type == 'service')
                                <p class="text-xl font-black text-blue-600">∞ Jasa</p>
                            @else
                                <p class="text-2xl font-black text-slate-800">{{ number_format($product->stock) }}</p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase">{{ $product->unit }}</p>
                            @endif
                        </div>

                        {{-- Terjual --}}
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Terjual</p>
                            <p class="text-2xl font-black text-slate-800">{{ number_format($product->sold) }}</p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase">Kali</p>
                        </div>

                        {{-- Profit (Hanya Tampil Jika Bukan Varian & Bukan Jasa) --}}
                        @if(!$product->has_variant && ($product->category && $product->category->type != 'service'))
                            <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-100 text-center">
                                <p class="text-[10px] font-bold text-emerald-600 uppercase mb-1">Margin Profit</p>
                                <p class="text-2xl font-black text-emerald-600">
                                    Rp {{ number_format($product->sell_price - $product->base_price, 0, ',', '.') }}
                                </p>
                                <p class="text-[10px] text-emerald-500 font-bold">Per unit</p>
                            </div>
                        @else
                            {{-- Info Alternatif untuk Varian/Jasa --}}
                            <div class="p-4 bg-indigo-50 rounded-xl border border-indigo-100 text-center">
                                <p class="text-[10px] font-bold text-indigo-500 uppercase mb-1">Status</p>
                                <p class="text-lg font-black text-indigo-600 uppercase">{{ $product->stock_status }}</p>
                                <p class="text-[10px] text-indigo-400 font-bold">Ketersediaan</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- DETAIL INFORMASI --}}
                <div class="p-8 space-y-6">

                    {{-- JIKA PRODUK TUNGGAL (SINGLE) --}}
                    @if(!$product->has_variant)
                        <div>
                            <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <i class="fas fa-tag text-slate-400"></i> Detail Harga
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between p-3 bg-slate-50 rounded-lg border border-slate-100">
                                    <span class="text-sm text-slate-500 font-medium">Harga Modal</span>
                                    <span class="text-sm text-slate-700 font-bold">Rp {{ number_format($product->base_price, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between p-3 bg-emerald-50 rounded-lg border border-emerald-100">
                                    <span class="text-sm text-emerald-700 font-bold">Harga Jual</span>
                                    <span class="text-base text-emerald-700 font-black">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</span>
                                </div>

                                {{-- [BARU] TAMPILAN BARCODE (SINGLE) --}}
                                @if($product->barcode)
                                <div class="flex justify-between p-3 border-b border-slate-100">
                                    <span class="text-sm text-slate-500 font-medium">Barcode</span>
                                    <span class="text-sm text-slate-800 font-mono font-bold tracking-wide bg-slate-100 px-2 py-0.5 rounded">
                                        <i class="fas fa-barcode mr-1"></i> {{ $product->barcode }}
                                    </span>
                                </div>
                                @endif

                                <div class="flex justify-between p-3 border-b border-slate-100">
                                    <span class="text-sm text-slate-500 font-medium">Supplier</span>
                                    <span class="text-sm text-slate-800 font-bold">{{ $product->supplier ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- JIKA PRODUK VARIAN (MULTI) --}}
                    @if($product->has_variant)
                        <div>
                            <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <i class="fas fa-layer-group text-slate-400"></i> Daftar Varian
                            </h3>
                            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                                        <tr>
                                            <th class="px-4 py-3">Nama Varian</th>
                                            <th class="px-4 py-3">Barcode</th> {{-- [BARU] KOLOM BARCODE --}}
                                            <th class="px-4 py-3 text-right">Harga</th>
                                            <th class="px-4 py-3 text-center">Stok</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($product->variants as $variant)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-4 py-3 font-bold text-slate-700">{{ $variant->name }}</td>

                                            {{-- [BARU] DATA BARCODE VARIAN --}}
                                            <td class="px-4 py-3 font-mono text-xs text-slate-500">
                                                @if($variant->barcode)
                                                    <span class="bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                                        {{ $variant->barcode }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>

                                            <td class="px-4 py-3 text-right font-bold text-emerald-600">
                                                Rp {{ number_format($variant->price, 0, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @if($product->category->type == 'service')
                                                    <span class="text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full font-bold">Jasa</span>
                                                @else
                                                    {{ number_format($variant->stock) }}
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>

    </div>
</div>
@endsection
