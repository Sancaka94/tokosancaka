@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white p-8 rounded-xl shadow-md border border-slate-200">
            <form action="{{ route('products.update', $product->id) }}" method="POST" class="space-y-6">
                @csrf @method('PUT')
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Nama Produk</label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="w-full px-4 py-3 rounded-xl border-slate-300 focus:ring-indigo-500 text-sm font-bold text-slate-700">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Modal (Rp)</label>
                        <input type="number" name="base_price" value="{{ old('base_price', $product->base_price) }}" required class="w-full px-4 py-3 rounded-xl border-slate-300 bg-slate-50 font-bold text-slate-600">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-emerald-600 uppercase tracking-wide mb-2">Jual (Rp)</label>
                        <input type="number" name="sell_price" value="{{ old('sell_price', $product->sell_price) }}" required class="w-full px-4 py-3 rounded-xl border-emerald-300 bg-emerald-50 font-bold text-emerald-700">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Sisa Stok</label>
                        <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" required class="w-full px-4 py-3 rounded-xl border-slate-300 focus:ring-indigo-500 font-bold text-slate-700">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Satuan</label>
                        <select name="unit" class="w-full px-4 py-3 rounded-xl border-slate-300 bg-white">
                            @foreach(['pcs', 'box', 'rim', 'meter', 'paket'] as $opt)
                                <option value="{{ $opt }}" {{ $product->unit == $opt ? 'selected' : '' }}>{{ ucfirst($opt) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Supplier</label>
                        <input type="text" name="supplier" value="{{ old('supplier', $product->supplier) }}" class="w-full px-4 py-3 rounded-xl border-slate-300 focus:ring-indigo-500 text-sm">
                    </div>
                </div>

                <div class="pt-4 flex gap-4">
                    <button type="submit" class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-md transition">Simpan Perubahan</button>
                    <a href="{{ route('products.index') }}" class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold transition">Batal</a>
                </div>
            </form>
        </div>
    </div>
@endsection