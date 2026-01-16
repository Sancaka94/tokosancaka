@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="bg-white p-8 rounded-xl shadow-md border border-slate-200"
             {{-- Inisialisasi Data Alpine --}}
             x-data="{
                imgPreview: '{{ $product->image ? asset('storage/'.$product->image) : '' }}',
                selectedCategory: '{{ $product->category->slug ?? 'retail' }}', // Ambil slug kategori saat ini

                // Logic Helper
                isLaundry() { return this.selectedCategory.includes('laundry'); },
                isRetail() { return !this.selectedCategory.includes('laundry') && !this.selectedCategory.includes('fnb'); }
             }">

            {{-- Header --}}
            <div class="mb-8 flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-800">Edit Produk</h1>
                    <p class="text-sm text-slate-500 mt-1">Sesuaikan informasi produk berdasarkan kategorinya.</p>
                </div>

                {{-- Badge Kategori Dinamis --}}
                <div class="px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wide transition-colors duration-300"
                     :class="isLaundry() ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700'">
                    <i class="fas" :class="isLaundry() ? 'fa-tshirt' : 'fa-box'"></i>
                    <span x-text="isLaundry() ? 'Mode Jasa Laundry' : 'Mode Barang Retail'"></span>
                </div>
            </div>

            <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-12 gap-6">

                    {{-- KOLOM KIRI: GAMBAR --}}
                    <div class="md:col-span-4 space-y-4">
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-center">
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-3">Foto Produk</label>

                            <div class="relative w-full aspect-square rounded-lg border-2 border-dashed border-slate-300 bg-white overflow-hidden group hover:border-indigo-400 transition-colors">
                                <template x-if="imgPreview">
                                    <img :src="imgPreview" class="w-full h-full object-contain p-2">
                                </template>
                                <template x-if="!imgPreview">
                                    <div class="flex flex-col items-center justify-center h-full text-slate-300">
                                        <i class="fas fa-camera text-3xl mb-2"></i>
                                        <span class="text-[10px]">Upload Foto</span>
                                    </div>
                                </template>

                                <input type="file" name="image" accept="image/*"
                                       @change="imgPreview = URL.createObjectURL($event.target.files[0])"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                                <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                    <span class="text-white text-xs font-bold"><i class="fas fa-edit"></i> Ubah</span>
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-2">Maks. 2MB (JPG/PNG)</p>
                        </div>
                    </div>

                    {{-- KOLOM KANAN: FORM DATA --}}
                    <div class="md:col-span-8 space-y-5">

                        {{-- 1. PILIH KATEGORI (Trigger Logic Cerdas) --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Kategori Produk</label>
                            <select name="category_id" x-model="selectedCategory" class="w-full px-4 py-3 rounded-xl border-slate-300 bg-slate-50 font-bold text-slate-700 focus:ring-indigo-500 transition-colors cursor-pointer hover:bg-white">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->slug }}" data-id="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            {{-- Input hidden untuk mengirim ID kategori asli ke backend (karena x-model pakai slug) --}}
                            {{-- Tips: Di controller nanti cocokkan slug atau kirim ID via value option di atas --}}
                        </div>

                        {{-- 2. INFORMASI DASAR (Selalu Muncul) --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Nama Produk / Layanan</label>
                            <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                                   class="w-full px-4 py-3 rounded-xl border-slate-300 focus:ring-indigo-500 text-sm font-bold text-slate-700 placeholder-slate-300"
                                   placeholder="Contoh: Cuci Kering atau Buku Tulis">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Modal (Rp)</label>
                                <input type="number" name="base_price" value="{{ old('base_price', $product->base_price) }}"
                                       class="w-full px-4 py-3 rounded-xl border-slate-300 bg-slate-50 font-medium text-slate-600 focus:bg-white transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-emerald-600 uppercase tracking-wide mb-2">Harga Jual (Rp)</label>
                                <input type="number" name="sell_price" value="{{ old('sell_price', $product->sell_price) }}" required
                                       class="w-full px-4 py-3 rounded-xl border-emerald-300 bg-emerald-50 font-bold text-emerald-700 focus:ring-emerald-500">
                            </div>
                        </div>

                        {{-- 3. AREA DINAMIS (Muncul/Hilang sesuai Kategori) --}}

                        {{-- KHUSUS RETAIL (Barang Fisik) --}}
                        <div x-show="!isLaundry()" x-transition.opacity.duration.300ms class="space-y-5 p-4 bg-orange-50/50 rounded-xl border border-orange-100">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="fas fa-box text-orange-400"></i>
                                <h3 class="text-xs font-bold text-orange-700 uppercase">Detail Barang Fisik</h3>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Stok Tersedia</label>
                                    <input type="number" name="stock" value="{{ old('stock', $product->stock) }}"
                                           :required="!isLaundry()"
                                           class="w-full px-4 py-2.5 rounded-lg border-slate-300 focus:ring-orange-500 text-sm font-bold">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Berat (Gram)</label>
                                    <input type="number" name="weight" value="{{ old('weight', $product->weight) }}"
                                           class="w-full px-4 py-2.5 rounded-lg border-slate-300 focus:ring-orange-500 text-sm"
                                           placeholder="Untuk ongkir">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Supplier</label>
                                <input type="text" name="supplier" value="{{ old('supplier', $product->supplier) }}"
                                       class="w-full px-4 py-2.5 rounded-lg border-slate-300 focus:ring-orange-500 text-sm"
                                       placeholder="Nama Supplier">
                            </div>
                        </div>

                        {{-- KHUSUS LAUNDRY (Jasa) --}}
                        <div x-show="isLaundry()" x-transition.opacity.duration.300ms class="p-4 bg-blue-50/50 rounded-xl border border-blue-100 text-center">
                            <div class="text-blue-400 mb-2 text-2xl"><i class="fas fa-check-circle"></i></div>
                            <h3 class="text-sm font-bold text-blue-700">Mode Jasa Aktif</h3>
                            <p class="text-xs text-blue-500 mt-1">Stok akan diatur otomatis "Unlimited" dan berat dihitung saat transaksi.</p>

                            {{-- Input Hidden untuk mengisi nilai default jika Laundry --}}
                            <input type="hidden" name="stock" :value="isLaundry() ? 9999 : '{{ $product->stock }}'">
                        </div>

                        {{-- SATUAN (Universal) --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Satuan Unit</label>
                            <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                                {{-- Opsi Satuan Retail --}}
                                <template x-if="!isLaundry()">
                                    <div class="contents">
                                        @foreach(['pcs', 'box', 'pak', 'rim', 'botol'] as $opt)
                                            <label class="cursor-pointer">
                                                <input type="radio" name="unit" value="{{ $opt }}" class="peer sr-only" {{ $product->unit == $opt ? 'checked' : '' }}>
                                                <div class="px-3 py-2 rounded-lg border border-slate-200 text-center text-xs font-bold text-slate-500 peer-checked:bg-slate-800 peer-checked:text-white peer-checked:border-slate-800 transition-all hover:bg-slate-50">
                                                    {{ ucfirst($opt) }}
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </template>

                                {{-- Opsi Satuan Laundry --}}
                                <template x-if="isLaundry()">
                                    <div class="contents">
                                        @foreach(['kg', 'm', 'set', 'helai'] as $opt)
                                            <label class="cursor-pointer">
                                                <input type="radio" name="unit" value="{{ $opt }}" class="peer sr-only" {{ $product->unit == $opt ? 'checked' : '' }}>
                                                <div class="px-3 py-2 rounded-lg border border-blue-200 text-center text-xs font-bold text-blue-500 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 transition-all hover:bg-blue-50">
                                                    {{ ucfirst($opt) }}
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- FOOTER BUTTONS --}}
                <div class="pt-6 flex gap-4 border-t border-slate-100 mt-8">
                    <button type="submit" class="flex-1 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 transition flex items-center justify-center gap-2 transform active:scale-[0.98]">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('products.index') }}" class="px-8 py-4 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-xl font-bold transition">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
