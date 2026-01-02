@extends('layouts.app')

@section('title', 'Detail Produk')

@section('content')

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Detail Produk</h1>
            <p class="text-slate-500 font-medium text-sm">Informasi lengkap mengenai layanan/produk.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('products.index') }}" class="px-4 py-2 bg-white text-slate-600 border border-slate-200 rounded-lg font-bold shadow-sm hover:bg-slate-50 transition">
                Kembali
            </a>
            <a href="{{ route('products.edit', $product->id) }}" class="px-4 py-2 bg-amber-500 text-white rounded-lg font-bold shadow-md hover:bg-amber-600 transition flex items-center gap-2">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
    </div>

    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            
            <div class="bg-indigo-600 p-8 flex flex-col items-center text-center">
                <div class="h-20 w-20 bg-white/20 rounded-2xl flex items-center justify-center text-white text-4xl mb-4 backdrop-blur-sm shadow-inner">
                    <i class="fas fa-box-open"></i>
                </div>
                <h2 class="text-2xl font-black text-white tracking-tight">{{ $product->name }}</h2>
                <span class="mt-2 px-3 py-1 bg-white/20 text-white text-xs font-bold uppercase rounded-full tracking-widest backdrop-blur-sm">
                    Produk Aktif
                </span>
            </div>

            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Harga Dasar</p>
                        <p class="text-2xl font-black text-slate-800">
                            Rp {{ number_format($product->base_price, 0, ',', '.') }}
                        </p>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Satuan Hitung</p>
                        <p class="text-2xl font-bold text-slate-800 capitalize">
                            Per {{ $product->unit }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase">Dibuat Pada</p>
                            <p class="text-sm font-bold text-slate-700">
                                {{ \Carbon\Carbon::parse($product->created_at)->translatedFormat('d F Y, H:i') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-amber-50 flex items-center justify-center text-amber-500">
                            <i class="fas fa-history"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase">Terakhir Update</p>
                            <p class="text-sm font-bold text-slate-700">
                                {{ \Carbon\Carbon::parse($product->updated_at)->diffForHumans() }}
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            <div class="bg-slate-50 px-8 py-4 border-t border-slate-100 flex justify-between items-center">
                <span class="text-xs text-slate-400 font-medium">ID Produk: #{{ $product->id }}</span>
                
                <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus produk ini secara permanen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs font-bold text-red-500 hover:text-red-700 transition flex items-center gap-1">
                        <i class="fas fa-trash-alt"></i> Hapus Produk
                    </button>
                </form>
            </div>

        </div>
    </div>

@endsection