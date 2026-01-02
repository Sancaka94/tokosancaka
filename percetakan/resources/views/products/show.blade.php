@extends('layouts.app')

@section('title', 'Detail Produk')

@section('content')

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Detail Produk</h1>
            <p class="text-slate-500 font-medium text-sm">Analisa harga modal dan keuntungan.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('products.index') }}" class="px-4 py-2 bg-white text-slate-600 border border-slate-200 rounded-lg font-bold shadow-sm hover:bg-slate-50 transition">Kembali</a>
            <a href="{{ route('products.edit', $product->id) }}" class="px-4 py-2 bg-amber-500 text-white rounded-lg font-bold shadow-md hover:bg-amber-600 transition flex items-center gap-2"><i class="fas fa-edit"></i> Edit</a>
        </div>
    </div>

    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            
            <div class="bg-indigo-600 p-8 flex flex-col items-center text-center relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10"><i class="fas fa-box text-9xl text-white"></i></div>
                <h2 class="text-3xl font-black text-white tracking-tight relative z-10">{{ $product->name }}</h2>
                <span class="mt-2 px-3 py-1 bg-white/20 text-white text-xs font-bold uppercase rounded-full tracking-widest backdrop-blur-sm relative z-10">Produk Aktif</span>
            </div>

            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    
                    <div class="p-5 bg-slate-50 rounded-xl border border-slate-100 text-center">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Harga Modal</p>
                        <p class="text-xl font-bold text-slate-600">Rp {{ number_format($product->base_price, 0, ',', '.') }}</p>
                    </div>

                    <div class="p-5 bg-emerald-50 rounded-xl border border-emerald-100 text-center">
                        <p class="text-xs font-bold text-emerald-500 uppercase tracking-widest mb-1">Harga Jual</p>
                        <p class="text-2xl font-black text-emerald-600">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</p>
                    </div>

                    <div class="p-5 bg-blue-50 rounded-xl border border-blue-100 text-center relative overflow-hidden">
                        <div class="absolute -right-2 -top-2 text-blue-100 text-6xl opacity-50"><i class="fas fa-chart-line"></i></div>
                        <p class="text-xs font-bold text-blue-500 uppercase tracking-widest mb-1">Estimasi Profit</p>
                        <p class="text-xl font-bold text-blue-600">
                            Rp {{ number_format($product->sell_price - $product->base_price, 0, ',', '.') }}
                        </p>
                        <p class="text-[10px] text-blue-400 mt-1 font-medium">Per {{ $product->unit }}</p>
                    </div>

                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center gap-4 p-4 rounded-lg border border-slate-100">
                        <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500"><i class="fas fa-ruler"></i></div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase">Satuan Unit</p>
                            <p class="font-bold text-slate-700 capitalize">Hitungan per {{ $product->unit }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 p-4 rounded-lg border border-slate-100">
                        <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500"><i class="fas fa-calendar"></i></div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase">Terakhir Update</p>
                            <p class="font-bold text-slate-700">{{ $product->updated_at->format('d F Y, H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-50 px-8 py-4 border-t border-slate-100 flex justify-between items-center">
                <span class="text-xs text-slate-400 font-medium">ID Produk: #{{ $product->id }}</span>
                <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Hapus permanen?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs font-bold text-red-500 hover:text-red-700 transition flex items-center gap-1"><i class="fas fa-trash-alt"></i> Hapus Produk</button>
                </form>
            </div>
        </div>
    </div>
@endsection