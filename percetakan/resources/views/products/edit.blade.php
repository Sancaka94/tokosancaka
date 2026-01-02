@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Edit Produk</h1>
            <p class="text-slate-500 font-medium text-sm">Perbarui harga modal atau harga jual.</p>
        </div>
        <a href="{{ route('products.index') }}" class="flex items-center gap-2 px-4 py-2 bg-white text-slate-600 border border-slate-200 rounded-lg font-bold shadow-sm hover:bg-slate-50 transition">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white p-8 rounded-xl shadow-md border border-slate-200">
            
            <div class="flex items-center gap-4 mb-8 pb-6 border-b border-slate-100">
                <div class="h-12 w-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-500 text-xl"><i class="fas fa-edit"></i></div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Form Perubahan Data</h2>
                    <p class="text-xs text-slate-400">Pastikan margin keuntungan sudah sesuai.</p>
                </div>
            </div>

            <form action="{{ route('products.update', $product->id) }}" method="POST" class="space-y-6">
                @csrf @method('PUT')
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Nama Produk</label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required 
                           class="w-full px-4 py-3 rounded-xl border-slate-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition text-sm font-bold text-slate-700">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Harga Modal (Rp)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 font-bold">Rp</span>
                            <input type="number" name="base_price" value="{{ old('base_price', $product->base_price) }}" required 
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border-slate-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition text-sm font-bold text-slate-600 bg-slate-50">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-emerald-600 uppercase tracking-wide mb-2">Harga Jual (Rp)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-emerald-500 font-bold">Rp</span>
                            <input type="number" name="sell_price" value="{{ old('sell_price', $product->sell_price) }}" required 
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border-emerald-300 focus:border-emerald-500 focus:ring focus:ring-emerald-200 transition text-sm font-bold text-emerald-700 bg-emerald-50">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Satuan Unit</label>
                    <select name="unit" class="w-full px-4 py-3 rounded-xl border-slate-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition text-sm font-medium bg-white">
                        @foreach(['pcs', 'lembar', 'meter', 'box', 'paket'] as $opt)
                            <option value="{{ $opt }}" {{ $product->unit == $opt ? 'selected' : '' }}>{{ ucfirst($opt) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-4 flex gap-4">
                    <button type="submit" class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-md transition gap-2 flex justify-center items-center">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('products.index') }}" class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold transition">Batal</a>
                </div>

            </form>
        </div>
    </div>
@endsection