@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')

{{-- 1. LOAD LIBRARY VISUAL BARCODE --}}
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<div class="max-w-4xl mx-auto" x-data="productEditForm()">

    {{-- HEADER --}}
    <div class="flex justify-between items-end mb-6">
        <div>
            <h1 class="text-3xl font-black text-slate-800">Edit Produk</h1>
            <p class="text-sm text-slate-500 mt-1">Perbarui informasi produk dan varian.</p>
        </div>

        {{-- BADGE MODE --}}
        <div class="px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wide flex items-center gap-2"
             :class="isService ? 'bg-purple-100 text-purple-700' : 'bg-orange-100 text-orange-700'">
            <i class="fas" :class="isService ? 'fa-concierge-bell' : 'fa-box'"></i>
            <span x-text="isService ? 'Mode Jasa / Layanan' : 'Mode Barang Fisik'"></span>
        </div>
    </div>

    <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

            {{-- KOLOM KIRI: GAMBAR & KATEGORI --}}
            <div class="lg:col-span-1 space-y-6">

                {{-- CARD GAMBAR --}}
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-3 tracking-wider">Foto Produk</label>

                    <div class="relative w-full aspect-square rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 overflow-hidden group hover:border-indigo-400 transition-colors">
                        {{-- Preview Gambar --}}
                        <template x-if="imgPreview">
                            <img :src="imgPreview" class="w-full h-full object-contain p-2">
                        </template>
                        <template x-if="!imgPreview">
                            <div class="flex flex-col items-center justify-center h-full text-slate-300">
                                <i class="fas fa-image text-4xl mb-2"></i>
                                <span class="text-[10px] font-bold">Belum ada foto</span>
                            </div>
                        </template>

                        {{-- Input File --}}
                        <input type="file" name="image" accept="image/*"
                               @change="imgPreview = URL.createObjectURL($event.target.files[0])"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                        {{-- Overlay Hover --}}
                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                            <span class="text-white text-xs font-bold bg-black/50 px-3 py-1.5 rounded-full backdrop-blur-sm">
                                <i class="fas fa-pen"></i> Ubah Foto
                            </span>
                        </div>
                    </div>
                    <p class="text-[10px] text-center text-slate-400 mt-2">Format: JPG, PNG (Max 2MB)</p>
                </div>

                {{-- CARD KATEGORI --}}
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Kategori</label>
                    <select name="category_id" x-model="selectedCategory" @change="handleCategoryChange($event)"
                            class="w-full px-4 py-3 rounded-xl border-slate-300 bg-slate-50 font-bold text-slate-700 focus:ring-indigo-500 text-sm">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}"
                                    data-type="{{ $cat->type }}"
                                    {{ $product->category_id == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- KOLOM KANAN: INFORMASI UTAMA --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- CARD DATA DASAR --}}
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <h3 class="font-bold text-slate-800 text-lg mb-4 border-b border-slate-100 pb-3">Informasi Dasar</h3>

                    <div class="space-y-4">
                        {{-- Nama Produk --}}
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Nama Produk <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                                   class="w-full px-4 py-3 rounded-xl border-slate-300 focus:ring-indigo-500 font-bold text-slate-700">
                        </div>

                        {{-- [BARU] Barcode Produk Utama dengan Visual Generator --}}
                        <div x-show="!hasVariant && !isService" x-data="{
                                code: '{{ $product->barcode }}',
                                init() { this.renderBarcode(); },
                                renderBarcode() {
                                    if(this.code && this.code.length > 0) {
                                        this.$nextTick(() => {
                                            try {
                                                JsBarcode(this.$refs.barcodeCanvasEdit, this.code, {
                                                    format: 'CODE128',
                                                    lineColor: '#334155',
                                                    width: 2,
                                                    height: 50,
                                                    displayValue: true
                                                });
                                            } catch(e) {}
                                        });
                                    }
                                }
                            }">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Barcode / SKU</label>

                            <div class="relative">
                                <span class="absolute left-3 top-3 text-slate-400"><i class="fas fa-barcode"></i></span>
                                <input type="text" name="barcode"
                                       x-model="code"
                                       @input.debounce.300ms="renderBarcode()"
                                       @keydown.enter.prevent="renderBarcode()"
                                       placeholder="Scan Barcode..."
                                       class="w-full pl-9 pr-4 py-3 rounded-xl border-slate-300 font-mono tracking-wide focus:ring-indigo-500 transition text-sm">
                            </div>

                            {{-- VISUAL BARCODE --}}
                            <div class="mt-2 flex items-center justify-center p-2 bg-slate-50 border border-slate-200 rounded-lg"
                                 x-show="code && code.length > 0">
                                <svg x-ref="barcodeCanvasEdit"></svg>
                            </div>

                            <p class="text-[9px] text-slate-400 mt-1 italic pl-1" x-show="!code || code.length === 0">*Kosongkan untuk auto-generate</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            {{-- Modal --}}
                            <div x-show="!isService">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Modal (Rp)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-3 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" name="base_price" value="{{ old('base_price', $product->base_price) }}"
                                           class="w-full pl-9 pr-4 py-3 rounded-xl border-slate-300 bg-slate-50 focus:bg-white transition text-sm font-medium">
                                </div>
                            </div>

                            {{-- Harga Jual (Jika Single Product) --}}
                            <div :class="isService ? 'col-span-2' : ''">
                                <label class="block text-[10px] font-bold text-emerald-600 uppercase mb-1">Harga Jual <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-3 top-3 text-xs text-emerald-600 font-bold">Rp</span>
                                    <input type="number" name="sell_price" value="{{ old('sell_price', $product->sell_price) }}"
                                           :readonly="hasVariant"
                                           :class="hasVariant ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-emerald-50 text-emerald-700'"
                                           class="w-full pl-9 pr-4 py-3 rounded-xl border-emerald-200 font-bold focus:ring-emerald-500 transition text-sm">
                                </div>
                                <p x-show="hasVariant" class="text-[10px] text-amber-600 mt-1"><i class="fas fa-info-circle"></i> Harga diambil dari varian.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            {{-- Stok (Jika Single Product) --}}
                            <div x-show="!isService">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Stok Total</label>
                                <input type="number" name="stock" value="{{ old('stock', $product->stock) }}"
                                       :readonly="hasVariant"
                                       :class="hasVariant ? 'bg-slate-100 text-slate-400' : 'bg-white text-slate-700'"
                                       class="w-full px-4 py-3 rounded-xl border-slate-300 font-bold text-sm">
                            </div>

                            {{-- Satuan --}}
                            <div :class="isService ? 'col-span-2' : ''">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Satuan</label>
                                <select name="unit" class="w-full px-4 py-3 rounded-xl border-slate-300 bg-white text-sm font-medium">
                                    @foreach(['pcs','kg','box','lembar','paket','meter'] as $u)
                                        <option value="{{ $u }}" {{ $product->unit == $u ? 'selected' : '' }}>{{ ucfirst($u) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div x-show="!isService">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Supplier</label>
                            <input type="text" name="supplier" value="{{ old('supplier', $product->supplier) }}"
                                   class="w-full px-4 py-3 rounded-xl border-slate-300 text-sm">
                        </div>
                    </div>
                </div>

                {{-- CARD VARIAN --}}
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="font-bold text-slate-800 text-lg">Varian & Ukuran</h3>
                            <p class="text-xs text-slate-400">Aktifkan jika produk memiliki banyak jenis harga.</p>
                        </div>

                        {{-- Toggle Switch Varian --}}
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="has_variant" value="1" x-model="hasVariant" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    {{-- AREA VARIAN --}}
                    <div x-show="hasVariant" x-transition.opacity>

                        <div class="flex justify-end mb-3">
                            <button type="button" @click="addVariantRow()" class="text-xs font-bold text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-100 hover:bg-indigo-100 transition">
                                <i class="fas fa-plus mr-1"></i> Tambah Varian
                            </button>
                        </div>

                        <div class="space-y-3">
                            {{-- Header Tabel Kecil --}}
                            <div class="grid grid-cols-12 gap-3 text-[10px] font-bold text-slate-400 uppercase px-1">
                                <div class="col-span-3">Nama Varian</div>
                                <div class="col-span-3">Barcode</div>
                                <div class="col-span-3">Harga</div>
                                <div class="col-span-2">Stok</div>
                                <div class="col-span-1 text-center">Hapus</div>
                            </div>

                            {{-- Looping Input Varian --}}
                            <template x-for="(variant, index) in variants" :key="index">
                                <div class="grid grid-cols-12 gap-3 items-center group animate-fade-in-down">
                                    {{-- ID Varian (Hidden) --}}
                                    <input type="hidden" :name="'variants['+index+'][id]'" x-model="variant.id">

                                    {{-- Nama --}}
                                    <div class="col-span-3">
                                        <input type="text" :name="'variants['+index+'][name]'" x-model="variant.name" placeholder="Cth: XL" required
                                               class="w-full px-3 py-2 rounded-lg border-slate-300 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500">
                                    </div>

                                    {{-- Barcode Varian --}}
                                    <div class="col-span-3 relative">
                                        <span class="absolute left-2 top-2.5 text-slate-400"><i class="fas fa-barcode text-xs"></i></span>
                                        <input type="text" :name="'variants['+index+'][barcode]'" x-model="variant.barcode"
                                               @keydown.enter.prevent
                                               placeholder="Scan..."
                                               class="w-full pl-6 pr-2 py-2 rounded-lg border-slate-300 text-xs font-mono bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                                    </div>

                                    {{-- Harga --}}
                                    <div class="col-span-3">
                                        <input type="number" :name="'variants['+index+'][price]'" x-model="variant.price" placeholder="0" required
                                               class="w-full px-3 py-2 rounded-lg border-emerald-200 text-sm font-bold text-emerald-700 text-right focus:ring-2 focus:ring-emerald-500">
                                    </div>

                                    {{-- Stok --}}
                                    <div class="col-span-2">
                                        <input type="number" :name="'variants['+index+'][stock]'" x-model="variant.stock" placeholder="0" required
                                               class="w-full px-3 py-2 rounded-lg border-slate-300 text-sm text-center focus:ring-2 focus:ring-indigo-500">
                                    </div>

                                    {{-- Hapus --}}
                                    <div class="col-span-1 text-center">
                                        <button type="button" @click="removeVariantRow(index)" class="text-slate-300 hover:text-red-500 transition">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Pesan Kosong --}}
                        <div x-show="variants.length === 0" class="text-center py-6 bg-slate-50 rounded-xl border border-dashed border-slate-300 mt-2">
                            <p class="text-xs text-slate-400">Belum ada varian. Klik tombol tambah di atas.</p>
                        </div>
                    </div>

                    {{-- Jika Varian Mati --}}
                    <div x-show="!hasVariant" class="text-center py-4 bg-slate-50 rounded-xl border border-slate-100">
                        <p class="text-xs text-slate-400">Mode Single Product Aktif (Harga & Stok tunggal).</p>
                    </div>
                </div>

                {{-- TOMBOL AKSI --}}
                <div class="flex gap-4 pt-4">
                    <button type="submit" class="flex-1 py-4 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold shadow-lg shadow-slate-200 transition-all flex justify-center items-center gap-2 transform active:scale-[0.98]">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('products.index') }}" class="px-8 py-4 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-xl font-bold transition">
                        Batal
                    </a>
                </div>

            </div>
        </div>
    </form>
</div>

{{-- SCRIPT ALPINE --}}
<script>
    function productEditForm() {
        return {
            // Data Awal dari Backend
            imgPreview: '{{ $product->image ? asset("storage/".$product->image) : "" }}',
            selectedCategory: '{{ $product->category_id }}',
            isService: {{ $product->type === 'service' ? 'true' : 'false' }},
            hasVariant: {{ $product->has_variant ? 'true' : 'false' }},

            // Data Varian (Load dari DB dengan mapping barcode yang benar)
            variants: {!! $product->variants->map(function($v) {
                return [
                    'id' => $v->id,
                    'name' => $v->name,
                    'price' => $v->price,
                    'stock' => $v->stock,
                    'barcode' => $v->barcode ?? '' // Pastikan kolom barcode terload
                ];
            })->toJson() !!},

            handleCategoryChange(event) {
                const option = event.target.options[event.target.selectedIndex];
                this.isService = option.dataset.type === 'service';
            },

            addVariantRow() {
                this.variants.push({
                    id: null, // Baru, belum ada ID
                    name: '',
                    price: 0,
                    stock: 0,
                    barcode: '' // Field kosong
                });
            },

            removeVariantRow(index) {
                // Opsional: Bisa tambahkan input hidden 'delete_ids[]' jika ingin hapus di DB
                this.variants.splice(index, 1);
            }
        }
    }
</script>
@endsection
