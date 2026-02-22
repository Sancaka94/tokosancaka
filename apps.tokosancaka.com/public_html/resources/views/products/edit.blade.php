@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')

{{-- 1. LOAD LIBRARY VISUAL BARCODE --}}
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<div class="bg-black text-white p-5">
    Status Login: {{ Auth::check() ? 'Login' : 'Logout' }} <br>
    ID Produk: {{ $product->id }} <br>
    Errors: {{ $errors }}
</div>

<div class="max-w-4xl mx-auto" x-data="productEditForm()">

    {{-- SELIPKAN KODE INI DI ATAS INFORMASI DASAR --}}
    @if ($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 p-4 rounded-xl">
            <div class="flex gap-3">
                <i class="fas fa-exclamation-triangle text-red-500 mt-1"></i>
                <div>
                    <h3 class="text-sm font-bold text-red-800">Ada kesalahan input:</h3>
                    <ul class="mt-1 text-xs text-red-600 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

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

                        {{-- Barcode Produk Utama dengan Visual Generator --}}
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

                        <div x-show="!isService" class="mb-4">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Berat Keseluruhan (Gram)</label>
                            <input type="number" name="weight" value="{{ old('weight', $product->weight) }}"
                                class="w-full px-4 py-3 rounded-xl border-slate-300 focus:ring-indigo-500 transition text-sm font-medium text-slate-700">
                        </div>

                        <div class="mb-4">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Deskripsi Singkat</label>
                            <textarea name="description" rows="3" placeholder="Tuliskan detail produk di sini..."
                                      class="w-full px-4 py-3 rounded-xl border-slate-300 focus:ring-indigo-500 transition text-sm text-slate-700">{{ old('description', $product->description) }}</textarea>
                        </div>

                        <div class="mb-5 bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-3 tracking-wider">Badge Produk (Marketplace)</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_best_seller" value="1" {{ $product->is_best_seller ? 'checked' : '' }} class="rounded text-indigo-600 focus:ring-indigo-500 bg-white border-slate-300">
                                    <span class="text-xs font-semibold text-slate-700">Best Seller</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_terlaris" value="1" {{ $product->is_terlaris ? 'checked' : '' }} class="rounded text-indigo-600 focus:ring-indigo-500 bg-white border-slate-300">
                                    <span class="text-xs font-semibold text-slate-700">Terlaris</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_new_arrival" value="1" {{ $product->is_new_arrival ? 'checked' : '' }} class="rounded text-indigo-600 focus:ring-indigo-500 bg-white border-slate-300">
                                    <span class="text-xs font-semibold text-slate-700">Produk Baru</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_flash_sale" value="1" {{ $product->is_flash_sale ? 'checked' : '' }} class="rounded text-orange-500 focus:ring-orange-500 bg-white border-slate-300">
                                    <span class="text-xs font-semibold text-slate-700">Flash Sale</span>
                                </label>
                            </div>
                        </div>

                        <div x-show="!isService">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Supplier</label>
                            <input type="text" name="supplier" value="{{ old('supplier', $product->supplier) }}"
                                   class="w-full px-4 py-3 rounded-xl border-slate-300 text-sm">
                        </div>
                    </div>
                </div>

                {{-- CARD VARIAN & SUB VARIAN --}}
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="font-bold text-slate-800 text-lg">Varian & Sub Varian</h3>
                            <p class="text-xs text-slate-400">Aktifkan jika produk memiliki banyak jenis dan ukuran.</p>
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
                                <i class="fas fa-plus mr-1"></i> Tambah Varian Utama
                            </button>
                        </div>

                        <div class="space-y-4">
                            {{-- Header Tabel Kecil --}}
                            <div class="grid grid-cols-12 gap-3 text-[10px] font-bold text-slate-400 uppercase px-1">
                                <div class="col-span-3">Nama Varian</div>
                                <div class="col-span-3">Barcode</div>
                                <div class="col-span-3">Harga Induk</div>
                                <div class="col-span-2">Stok Total</div>
                                <div class="col-span-1 text-center">Hapus</div>
                            </div>

                            {{-- Looping Input Varian --}}
                            <template x-for="(variant, index) in variants" :key="index">
                                <div class="bg-white rounded-xl border border-slate-200 mb-4 overflow-hidden shadow-sm">

                                    {{-- BARIS VARIAN UTAMA --}}
                                    <div class="grid grid-cols-12 gap-3 items-center p-3 bg-slate-50 border-b border-slate-200">
                                        <input type="hidden" :name="'variants['+index+'][id]'" x-model="variant.id">

                                        <div class="col-span-3">
                                            <input type="text" :name="'variants['+index+'][name]'" x-model="variant.name" placeholder="Cth: Merah" required
                                                   class="w-full px-3 py-2 rounded-lg border-slate-300 text-sm font-bold text-slate-700 bg-white transition-all">
                                        </div>

                                        <div class="col-span-3 relative">
                                            <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-slate-400"><i class="fas fa-barcode text-xs"></i></span>
                                            <input type="text" :name="'variants['+index+'][barcode]'" x-model="variant.barcode" placeholder="Scan..." @keydown.enter.prevent
                                                   class="w-full pl-6 pr-2 py-2 rounded-lg border-slate-300 text-xs font-mono bg-white transition-all">
                                        </div>

                                        <div class="col-span-3">
                                            <input type="number" :name="'variants['+index+'][price]'" x-model="variant.price" placeholder="0" required
                                                   class="w-full px-3 py-2 rounded-lg border-emerald-200 text-sm font-bold text-emerald-700 bg-white text-right transition-all">
                                        </div>

                                        <div class="col-span-2">
                                            <input type="number" :name="'variants['+index+'][stock]'" x-model="variant.stock" placeholder="0" required
                                                   :readonly="variant.sub_variants && variant.sub_variants.length > 0"
                                                   :class="variant.sub_variants && variant.sub_variants.length > 0 ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white'"
                                                   class="w-full px-3 py-2 rounded-lg border-slate-300 text-sm text-center transition-all">
                                        </div>

                                        <div class="col-span-1 text-center">
                                            <button type="button" @click="removeVariantRow(index)" class="h-8 w-8 inline-flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition">
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- AREA SUB VARIAN --}}
                                    <div class="p-3 bg-white">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-[10px] font-bold text-orange-500 uppercase tracking-wider"><i class="fas fa-code-branch"></i> Sub Varian (Anak)</span>
                                            <button type="button" @click="addSubVariantRow(index)" class="text-[10px] bg-orange-50 text-orange-600 px-2 py-1 rounded border border-orange-200 hover:bg-orange-100 font-bold">
                                                + Tambah Anak
                                            </button>
                                        </div>

                                        <div class="space-y-2 pl-4 border-l-2 border-orange-100">
                                            <template x-for="(sub, subIndex) in variant.sub_variants" :key="subIndex">
                                                <div class="grid grid-cols-12 gap-2 items-center">
                                                    <input type="hidden" :name="'variants['+index+'][sub_variants]['+subIndex+'][id]'" x-model="sub.id">
                                                    <div class="col-span-3">
                                                        <input type="text" :name="'variants['+index+'][sub_variants]['+subIndex+'][name]'" x-model="sub.name" placeholder="Cth: Ukuran L" required class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded focus:ring-1 focus:ring-orange-500">
                                                    </div>
                                                    <div class="col-span-3">
                                                        <input type="text" :name="'variants['+index+'][sub_variants]['+subIndex+'][barcode]'" x-model="sub.barcode" placeholder="Barcode" class="w-full px-2 py-1.5 text-xs font-mono border border-slate-300 rounded focus:ring-1 focus:ring-orange-500">
                                                    </div>
                                                    <div class="col-span-2">
                                                        <input type="number" :name="'variants['+index+'][sub_variants]['+subIndex+'][price]'" x-model="sub.price" placeholder="Harga" required class="w-full px-2 py-1.5 text-xs border border-emerald-300 text-emerald-700 font-bold rounded focus:ring-1 focus:ring-emerald-500 text-right">
                                                    </div>
                                                    <div class="col-span-2">
                                                        <input type="number" :name="'variants['+index+'][sub_variants]['+subIndex+'][stock]'" x-model="sub.stock" placeholder="Stok" @input="calculateTotalStock(index)" required class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded focus:ring-1 focus:ring-orange-500 text-center">
                                                    </div>
                                                    <div class="col-span-1">
                                                        <input type="number" :name="'variants['+index+'][sub_variants]['+subIndex+'][weight]'" x-model="sub.weight" placeholder="Berat" class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded focus:ring-1 focus:ring-orange-500 text-center" title="Berat dalam Gram">
                                                    </div>
                                                    <div class="col-span-1 text-center">
                                                        <button type="button" @click="removeSubVariantRow(index, subIndex); calculateTotalStock(index)" class="text-slate-400 hover:text-red-500 p-1">
                                                            <i class="fas fa-times text-xs"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="!variant.sub_variants || variant.sub_variants.length === 0">
                                                <p class="text-[9px] text-slate-400 italic">Tidak ada sub varian. Harga & stok ikut varian induk.</p>
                                            </template>
                                        </div>
                                    </div>

                                </div>
                            </template>
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

<script>
    function productEditForm() {
        return {
            imgPreview: '{{ $product->image ? asset("storage/".$product->image) : "" }}',
            selectedCategory: '{{ $product->category_id }}',
            isService: {{ $product->type === 'service' ? 'true' : 'false' }},
            hasVariant: {{ $product->has_variant ? 'true' : 'false' }},

            variants: {!! $product->variants->map(function($v) {
                return [
                    'id' => $v->id,
                    'name' => $v->name,
                    'price' => $v->price,
                    'stock' => $v->stock,
                    'barcode' => $v->barcode ?? '',
                    'sub_variants' => $v->subVariants->map(function($sub) {
                        return [
                            'id' => $sub->id,
                            'name' => $sub->name,
                            'price' => $sub->price,
                            'stock' => $sub->stock,
                            'weight' => $sub->weight ?? 0,
                            'barcode' => $sub->barcode ?? ''
                        ];
                    })->toArray()
                ];
            })->toJson() !!},

            handleCategoryChange(event) {
                const option = event.target.options[event.target.selectedIndex];
                this.isService = option.dataset.type === 'service' || option.dataset.type === 'jasa';
            },

            addVariantRow() {
                this.variants.push({
                    id: null, name: '', price: 0, stock: 0, barcode: '', sub_variants: []
                });
            },

            removeVariantRow(index) {
                if(confirm('Hapus varian ini?')) this.variants.splice(index, 1);
            },

            addSubVariantRow(variantIndex) {
                if(!this.variants[variantIndex].sub_variants) this.variants[variantIndex].sub_variants = [];
                this.variants[variantIndex].sub_variants.push({
                    id: null, name: '', price: this.variants[variantIndex].price, stock: 0, weight: 0, barcode: ''
                });
            },

            removeSubVariantRow(vIdx, sIdx) {
                this.variants[vIdx].sub_variants.splice(sIdx, 1);
            },

            calculateTotalStock(vIdx) {
                let total = 0;
                this.variants[vIdx].sub_variants.forEach(s => total += parseInt(s.stock) || 0);
                this.variants[vIdx].stock = total;
            }
        }
    }
</script>
@endsection
