@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white p-8 rounded-xl shadow-md border border-slate-200">
            
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Edit Produk</h1>
                    <p class="text-xs text-slate-400">Perbarui informasi produk dan gambar.</p>
                </div>
                <div class="h-10 w-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-pencil-alt"></i>
                </div>
            </div>

            {{-- JANGAN LUPA: enctype="multipart/form-data" --}}
            <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6" 
                  x-data="{ imgPreview: '{{ $product->image ? asset('storage/'.$product->image) : '' }}' }">
                
                @csrf 
                @method('PUT')
                
                {{-- BAGIAN UPLOAD GAMBAR --}}
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-3">Foto Produk</label>
                    <div class="flex items-start gap-4">
                        
                        <div class="h-24 w-24 rounded-lg border-2 border-dashed border-slate-300 flex items-center justify-center bg-white overflow-hidden shrink-0 shadow-sm relative group">
                            <template x-if="imgPreview">
                                <img :src="imgPreview" class="h-full w-full object-cover">
                            </template>
                            
                            <template x-if="!imgPreview">
                                <div class="text-center text-slate-300">
                                    <i class="fas fa-image text-2xl mb-1"></i>
                                    <span class="block text-[9px] font-bold">No Image</span>
                                </div>
                            </template>

                            <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                <span class="text-white text-[10px] font-bold">Ganti Foto</span>
                            </div>
                        </div>
                        
                        <div class="flex-1">
                            <input type="file" name="image" accept="image/*"
                                   @change="imgPreview = URL.createObjectURL($event.target.files[0])"
                                   class="block w-full text-xs text-slate-500 file:mr-2 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer">
                            
                            <p class="text-[10px] text-slate-400 mt-2 leading-relaxed">
                                <i class="fas fa-info-circle mr-1"></i> Biarkan kosong jika tidak ingin mengubah gambar. Format: JPG, PNG (Max 2MB).
                            </p>
                        </div>
                    </div>
                </div>

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
                        <select name="unit" class="w-full px-4 py-3 rounded-xl border-slate-300 bg-white text-sm font-medium">
                            @foreach(['pcs', 'lembar', 'box', 'rim', 'meter', 'paket'] as $opt)
                                <option value="{{ $opt }}" {{ $product->unit == $opt ? 'selected' : '' }}>{{ ucfirst($opt) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Supplier</label>
                        <input type="text" name="supplier" value="{{ old('supplier', $product->supplier) }}" class="w-full px-4 py-3 rounded-xl border-slate-300 focus:ring-indigo-500 text-sm">
                    </div>
                </div>

                <div class="pt-6 flex gap-4 border-t border-slate-100">
                    <button type="submit" class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-md transition flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('products.index') }}" class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold transition">Batal</a>
                </div>
            </form>
        </div>
    </div>
@endsection