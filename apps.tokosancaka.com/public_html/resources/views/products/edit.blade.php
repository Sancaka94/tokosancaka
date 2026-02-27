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

    {{-- KONTANER FORM --}}
    <div x-ref="formContainer" class="space-y-6">
        @csrf
        <input type="hidden" name="_method" value="PUT">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

            {{-- KOLOM KIRI: GAMBAR & KATEGORI --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- CARD GAMBAR --}}
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-3 tracking-wider">Foto Produk</label>

                    <div class="relative w-full aspect-square rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 overflow-hidden group hover:border-indigo-400 transition-colors">
                        <template x-if="imgPreview">
                            <img :src="imgPreview" class="w-full h-full object-contain p-2">
                        </template>
                        <template x-if="!imgPreview">
                            <div class="flex flex-col items-center justify-center h-full text-slate-300">
                                <i class="fas fa-image text-4xl mb-2"></i>
                                <span class="text-[10px] font-bold">Belum ada foto</span>
                            </div>
                        </template>

                        <input type="file" name="image" accept="image/*" x-ref="fileInput"
                               @change="imgPreview = URL.createObjectURL($event.target.files[0])"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

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
                            <option value="{{ $cat->id }}" data-type="{{ $cat->type }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>
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
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Nama Produk <span class="text-red-500">*</span></label>
                            <input type="text" x-model="productData.name" class="w-full px-4 py-3 rounded-xl border-slate-300 focus:ring-indigo-500 font-bold text-slate-700">
                        </div>

                        <div x-show="!hasVariant && !isService" x-data="{
                                code: '{{ $product->barcode }}',
                                init() { this.renderBarcode(); },
                                renderBarcode() {
                                    if(this.code && this.code.length > 0) {
                                        this.$nextTick(() => {
                                            try {
                                                JsBarcode(this.$refs.barcodeCanvasEdit, this.code, {
                                                    format: 'CODE128', lineColor: '#334155', width: 2, height: 50, displayValue: true
                                                });
                                            } catch(e) {}
                                        });
                                    }
                                }
                            }">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Barcode / SKU</label>
                            <div class="relative">
                                <span class="absolute left-3 top-3 text-slate-400"><i class="fas fa-barcode"></i></span>
                                <input type="text" x-model="productData.barcode" @input.debounce.300ms="code = productData.barcode; renderBarcode()" @keydown.enter.prevent
                                       placeholder="Scan Barcode..." class="w-full pl-9 pr-4 py-3 rounded-xl border-slate-300 font-mono tracking-wide focus:ring-indigo-500 transition text-sm">
                            </div>
                            <div class="mt-2 flex items-center justify-center p-2 bg-slate-50 border border-slate-200 rounded-lg" x-show="code && code.length > 0">
                                <svg x-ref="barcodeCanvasEdit"></svg>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div x-show="!isService">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Modal (Rp)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-3 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" x-model="productData.base_price" class="w-full pl-9 pr-4 py-3 rounded-xl border-slate-300 bg-slate-50 focus:bg-white transition text-sm font-medium">
                                </div>
                            </div>

                            <div :class="isService ? 'col-span-2' : ''">
                                <label class="block text-[10px] font-bold text-emerald-600 uppercase mb-1">Harga Jual <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-3 top-3 text-xs text-emerald-600 font-bold">Rp</span>
                                    <input type="number" x-model="productData.sell_price" :readonly="hasVariant"
                                           :class="hasVariant ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-emerald-50 text-emerald-700'"
                                           class="w-full pl-9 pr-4 py-3 rounded-xl border-emerald-200 font-bold focus:ring-emerald-500 transition text-sm">
                                </div>
                            </div>
                        </div>

                        {{-- Diskon Produk Utama --}}
                        <div class="mb-4 mt-4">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Diskon (Opsional)</label>
                            <div class="flex gap-3">
                                <div class="relative w-1/3">
                                    <select x-model="productData.discount_type" class="w-full pl-4 pr-8 py-3 rounded-xl border-slate-300 bg-white focus:ring-indigo-500 transition text-sm appearance-none font-bold text-slate-700">
                                        <option value="percent">Persen (%)</option>
                                        <option value="nominal">Nominal (Rp)</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400"><i class="fas fa-chevron-down text-xs"></i></div>
                                </div>
                                <input type="number" x-model="productData.discount_value" placeholder="0" min="0" step="any" class="w-2/3 px-4 py-3 rounded-xl border-slate-300 bg-white focus:ring-indigo-500 transition text-sm font-bold text-slate-700">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div x-show="!isService">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Stok Total</label>
                                <input type="number" x-model="productData.stock" :readonly="hasVariant"
                                       :class="hasVariant ? 'bg-slate-100 text-slate-400' : 'bg-white text-slate-700'"
                                       class="w-full px-4 py-3 rounded-xl border-slate-300 font-bold text-sm">
                            </div>

                            <div :class="isService ? 'col-span-2' : ''">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Satuan</label>
                                <select x-model="productData.unit" class="w-full px-4 py-3 rounded-xl border-slate-300 bg-white text-sm font-medium">
                                    @foreach(['pcs','kg','box','lembar','paket','meter'] as $u)
                                        <option value="{{ $u }}">{{ ucfirst($u) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div x-show="!isService" class="mb-4">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Berat Keseluruhan (Gram)</label>
                            <input type="number" x-model="productData.weight" class="w-full px-4 py-3 rounded-xl border-slate-300 focus:ring-indigo-500 transition text-sm font-medium text-slate-700">
                        </div>

                        <div class="mb-4">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Deskripsi Singkat</label>
                            <textarea x-model="productData.description" rows="3" class="w-full px-4 py-3 rounded-xl border-slate-300 focus:ring-indigo-500 transition text-sm text-slate-700"></textarea>
                        </div>

                        <div class="mb-5 bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-3 tracking-wider">Badge Produk (Marketplace)</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="productData.is_best_seller" class="rounded text-indigo-600 focus:ring-indigo-500 bg-white border-slate-300">
                                    <span class="text-xs font-semibold text-slate-700">Best Seller</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="productData.is_free_ongkir" class="rounded text-teal-600 focus:ring-teal-500 bg-white border-slate-300">
                                    <span class="text-xs font-semibold text-slate-700">Gratis Ongkir</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="productData.is_cashback_extra" class="rounded text-red-600 focus:ring-red-500 bg-white border-slate-300">
                                    <span class="text-xs font-semibold text-slate-700">Cashback Extra</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="productData.is_terlaris" class="rounded text-indigo-600 focus:ring-indigo-500 bg-white border-slate-300">
                                    <span class="text-xs font-semibold text-slate-700">Terlaris</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="productData.is_new_arrival" class="rounded text-indigo-600 focus:ring-indigo-500 bg-white border-slate-300">
                                    <span class="text-xs font-semibold text-slate-700">Produk Baru</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="productData.is_flash_sale" class="rounded text-orange-500 focus:ring-orange-500 bg-white border-slate-300">
                                    <span class="text-xs font-semibold text-slate-700">Flash Sale</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD VARIAN --}}
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="font-bold text-slate-800 text-lg">Varian & Sub Varian</h3>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="hasVariant" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    <div x-show="hasVariant" x-transition.opacity>
                        <div class="flex justify-end mb-3">
                            <button type="button" @click="addVariantRow()" class="text-xs font-bold text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-100 hover:bg-indigo-100">
                                <i class="fas fa-plus mr-1"></i> Tambah Varian Utama
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-12 gap-2 text-[9px] font-bold text-slate-400 uppercase px-1 tracking-wider">
                                <div class="col-span-2">Nama Varian</div><div class="col-span-2">Barcode</div>
                                <div class="col-span-2">Harga Induk</div><div class="col-span-3 text-center">Diskon</div>
                                <div class="col-span-2">Stok</div><div class="col-span-1 text-center">Del</div>
                            </div>

                            <template x-for="(variant, index) in variants" :key="index">
                                <div class="bg-white rounded-xl border border-slate-200 mb-4 overflow-hidden shadow-sm">
                                    {{-- BARIS VARIAN UTAMA --}}
                                    <div class="grid grid-cols-12 gap-2 items-center p-3 bg-slate-50 border-b border-slate-200">
                                        <div class="col-span-2">
                                            <input type="text" x-model="variant.name" placeholder="Nama" class="w-full px-2 py-2 rounded-lg border-slate-300 text-xs font-bold text-slate-700 bg-white">
                                        </div>
                                        <div class="col-span-2 relative">
                                            <input type="text" x-model="variant.barcode" placeholder="Barcode" class="w-full px-2 py-2 rounded-lg border-slate-300 text-xs font-mono bg-white">
                                        </div>
                                        <div class="col-span-2">
                                            <input type="number" x-model="variant.price" placeholder="0" class="w-full px-2 py-2 rounded-lg border-emerald-200 text-xs font-bold text-emerald-700 bg-white text-right">
                                        </div>

                                        {{-- Input Diskon Varian --}}
                                        <div class="col-span-3 flex gap-1">
                                            <select x-model="variant.discount_type" class="w-1/2 px-1 py-2 rounded-lg border-slate-300 text-xs bg-white text-center font-bold text-slate-600 appearance-none">
                                                <option value="percent">%</option>
                                                <option value="nominal">Rp</option>
                                            </select>
                                            <input type="number" x-model="variant.discount_value" placeholder="Diskon" class="w-1/2 px-2 py-2 rounded-lg border-slate-300 text-xs bg-white text-center">
                                        </div>

                                        <div class="col-span-2">
                                            <input type="number" x-model="variant.stock" placeholder="0" :readonly="variant.sub_variants && variant.sub_variants.length > 0" class="w-full px-2 py-2 rounded-lg border-slate-300 text-xs text-center">
                                        </div>
                                        <div class="col-span-1 text-center">
                                            <button type="button" @click="removeVariantRow(index)" class="h-8 w-8 text-slate-400 hover:text-red-500 bg-white rounded-md shadow-sm border border-slate-200"><i class="fas fa-trash-alt text-xs"></i></button>
                                        </div>
                                    </div>

                                    {{-- AREA SUB VARIAN --}}
                                    <div class="p-3 bg-white">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-[10px] font-bold text-orange-500 uppercase"><i class="fas fa-code-branch"></i> Sub Varian</span>
                                            <button type="button" @click="addSubVariantRow(index)" class="text-[10px] bg-orange-50 text-orange-600 px-2 py-1 rounded border border-orange-200 font-bold hover:bg-orange-100">+ Anak</button>
                                        </div>
                                        <div class="space-y-2 pl-4 border-l-2 border-orange-100">
                                            <template x-for="(sub, subIndex) in variant.sub_variants" :key="subIndex">
                                                <div class="grid grid-cols-12 gap-2 items-center">
                                                    <div class="col-span-2"><input type="text" x-model="sub.name" placeholder="Nama" class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded"></div>
                                                    <div class="col-span-2"><input type="text" x-model="sub.barcode" placeholder="Barcode" class="w-full px-2 py-1.5 text-xs font-mono border border-slate-300 rounded"></div>
                                                    <div class="col-span-2"><input type="number" x-model="sub.price" placeholder="Harga" class="w-full px-2 py-1.5 text-xs border border-emerald-300 text-emerald-700 font-bold text-right rounded"></div>

                                                    {{-- Input Diskon Sub Varian --}}
                                                    <div class="col-span-3 flex gap-1">
                                                        <select x-model="sub.discount_type" class="w-1/2 px-1 py-1.5 border border-slate-300 rounded text-xs text-center font-bold text-slate-600 appearance-none">
                                                            <option value="percent">%</option>
                                                            <option value="nominal">Rp</option>
                                                        </select>
                                                        <input type="number" x-model="sub.discount_value" placeholder="Diskon" class="w-1/2 px-2 py-1.5 border border-slate-300 rounded text-xs text-center">
                                                    </div>

                                                    <div class="col-span-1"><input type="number" x-model="sub.stock" @input="calculateTotalStock(index)" placeholder="Stok" class="w-full px-2 py-1.5 text-xs border border-slate-300 text-center rounded"></div>
                                                    <div class="col-span-1"><input type="number" x-model="sub.weight" placeholder="Gram" class="w-full px-2 py-1.5 text-xs border border-slate-300 text-center rounded"></div>
                                                    <div class="col-span-1 text-center"><button type="button" @click="removeSubVariantRow(index, subIndex); calculateTotalStock(index)" class="text-slate-400 hover:text-red-500"><i class="fas fa-times text-xs"></i></button></div>
                                                </div>
                                            </template>
                                            <template x-if="!variant.sub_variants || variant.sub_variants.length === 0">
                                                <p class="text-[9px] text-slate-400 italic">Tidak ada sub varian. Harga & stok mengikuti varian utama di atas.</p>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- TOMBOL AKSI BYPASS --}}
                <div class="flex gap-4 pt-4">
                    <button type="button" @click="submitTembusTembok()" :disabled="isSubmitting" class="flex-1 py-4 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold shadow-lg shadow-slate-200 transition-all flex justify-center items-center gap-2">
                        <i class="fas fa-save" x-show="!isSubmitting"></i>
                        <i class="fas fa-spinner fa-spin" style="display: none;" x-show="isSubmitting"></i>
                        <span x-text="isSubmitting ? 'Menyimpan Data...' : 'Simpan Perubahan'"></span>
                    </button>
                    <a href="{{ route('products.index') }}" class="px-8 py-4 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-xl font-bold transition">Batal</a>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    function productEditForm() {
        return {
            imgPreview: '{{ $product->image ? asset("storage/".$product->image) : "" }}',
            selectedCategory: '{{ $product->category_id }}',
            isService: {{ $product->type === 'service' ? 'true' : 'false' }},
            hasVariant: {{ $product->has_variant ? 'true' : 'false' }},
            isSubmitting: false,

            // DATA BINDING UNTUK PRODUK UTAMA
            productData: {
                name: '{{ addslashes($product->name) }}',
                barcode: '{{ $product->barcode }}',
                base_price: {{ $product->base_price ?? 0 }},
                sell_price: {{ $product->sell_price ?? 0 }},
                discount_type: '{{ $product->discount_type ?? "percent" }}',
                discount_value: {{ $product->discount_value ?? 0 }},
                stock: {{ $product->stock ?? 0 }},
                unit: '{{ $product->unit ?? "pcs" }}',
                weight: {{ $product->weight ?? 0 }},
                description: '{{ addslashes($product->description) }}',
                is_best_seller: {{ $product->is_best_seller ? 'true' : 'false' }},
                is_terlaris: {{ $product->is_terlaris ? 'true' : 'false' }},
                is_new_arrival: {{ $product->is_new_arrival ? 'true' : 'false' }},
                is_flash_sale: {{ $product->is_flash_sale ? 'true' : 'false' }},
                is_free_ongkir: {{ $product->is_free_ongkir ? 'true' : 'false' }},
                is_cashback_extra: {{ $product->is_cashback_extra ? 'true' : 'false' }}
            },

            variants: {!! $product->variants->map(function($v) {
                return [
                    'id' => $v->id, 'name' => $v->name, 'price' => $v->price, 'stock' => $v->stock, 'barcode' => $v->barcode ?? '',
                    'discount_type' => $v->discount_type ?? 'percent', 'discount_value' => $v->discount_value ?? 0,
                    'sub_variants' => $v->subVariants->map(function($sub) {
                        return [
                            'id' => $sub->id, 'name' => $sub->name, 'price' => $sub->price, 'stock' => $sub->stock, 'weight' => $sub->weight ?? 0, 'barcode' => $sub->barcode ?? '',
                            'discount_type' => $sub->discount_type ?? 'percent', 'discount_value' => $sub->discount_value ?? 0
                        ];
                    })->toArray()
                ];
            })->toJson() !!},

            handleCategoryChange(event) {
                const option = event.target.options[event.target.selectedIndex];
                this.isService = option.dataset.type === 'service';
            },

            addVariantRow() {
                this.variants.push({ id: null, name: '', price: 0, stock: 0, barcode: '', discount_type: 'percent', discount_value: 0, sub_variants: [] });
            },
            removeVariantRow(index) { this.variants.splice(index, 1); },
            addSubVariantRow(variantIndex) {
                if(!this.variants[variantIndex].sub_variants) this.variants[variantIndex].sub_variants = [];
                this.variants[variantIndex].sub_variants.push({ id: null, name: '', price: this.variants[variantIndex].price, stock: 0, weight: 0, barcode: '', discount_type: 'percent', discount_value: 0 });
            },
            removeSubVariantRow(vIndex, sIndex) { this.variants[vIndex].sub_variants.splice(sIndex, 1); },
            calculateTotalStock(vIndex) {
                let variant = this.variants[vIndex];
                if (variant.sub_variants && variant.sub_variants.length > 0) {
                    let total = 0;
                    variant.sub_variants.forEach(sub => total += parseInt(sub.stock) || 0);
                    variant.stock = total;
                }
            },

            async submitTembusTembok() {
                this.isSubmitting = true;

                let formData = new FormData();

                // Tambahkan token dan method
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('_method', 'PUT');

                // Tambahkan File Image jika ada perubahan
                let fileInput = this.$refs.fileInput;
                if (fileInput && fileInput.files.length > 0) {
                    formData.append('image', fileInput.files[0]);
                }

                // Tambahkan Status Mode
                formData.append('category_id', this.selectedCategory);
                formData.append('type', this.isService ? 'service' : 'physical');
                if (this.hasVariant) formData.append('has_variant', 1);

                // Tambahkan Data Produk Utama secara manual ke FormData
                for (let key in this.productData) {
                    let value = this.productData[key];
                    if(typeof value === 'boolean') {
                        if(value) formData.append(key, 1);
                    } else {
                        formData.append(key, value);
                    }
                }

                // Tambahkan Data Varian & Sub Varian secara terstruktur (Nested Array)
                if (this.hasVariant && this.variants.length > 0) {
                    this.variants.forEach((v, vIdx) => {
                        formData.append(`variants[${vIdx}][id]`, v.id || '');
                        formData.append(`variants[${vIdx}][name]`, v.name);
                        formData.append(`variants[${vIdx}][barcode]`, v.barcode);
                        formData.append(`variants[${vIdx}][price]`, v.price);
                        formData.append(`variants[${vIdx}][stock]`, v.stock);
                        formData.append(`variants[${vIdx}][discount_type]`, v.discount_type);
                        formData.append(`variants[${vIdx}][discount_value]`, v.discount_value);

                        if(v.sub_variants && v.sub_variants.length > 0) {
                            v.sub_variants.forEach((sub, sIdx) => {
                                formData.append(`variants[${vIdx}][sub_variants][${sIdx}][id]`, sub.id || '');
                                formData.append(`variants[${vIdx}][sub_variants][${sIdx}][name]`, sub.name);
                                formData.append(`variants[${vIdx}][sub_variants][${sIdx}][barcode]`, sub.barcode);
                                formData.append(`variants[${vIdx}][sub_variants][${sIdx}][price]`, sub.price);
                                formData.append(`variants[${vIdx}][sub_variants][${sIdx}][stock]`, sub.stock);
                                formData.append(`variants[${vIdx}][sub_variants][${sIdx}][weight]`, sub.weight);
                                formData.append(`variants[${vIdx}][sub_variants][${sIdx}][discount_type]`, sub.discount_type);
                                formData.append(`variants[${vIdx}][sub_variants][${sIdx}][discount_value]`, sub.discount_value);
                            });
                        }
                    });
                }

                try {
                    let response = await fetch("{{ route('products.update', $product->id) }}", {
                        method: 'POST', // Walau update, tetap POST karena kita pakai _method PUT di atas
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    let result = null;
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        result = await response.json();
                    }

                    if (response.ok) {
                        alert("SUKSES! Data berhasil diupdate.");
                        window.location.href = "{{ route('products.index') }}";
                    } else if (response.status === 422) {
                        let errorMsg = "Validasi Gagal:\n";
                        for (let key in result.errors) {
                            errorMsg += `- ${result.errors[key][0]}\n`;
                        }
                        alert(errorMsg);
                    } else {
                        alert("Error Server! Kode Status: " + response.status);
                        console.error("Detail Error:", result);
                    }
                } catch (error) {
                    alert("Gagal menghubungi server. Coba periksa koneksi internetmu.");
                    console.error("Fetch Error:", error);
                } finally {
                    this.isSubmitting = false;
                }
            }
        }
    }
</script>
@endsection
