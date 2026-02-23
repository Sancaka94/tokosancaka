@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')

{{-- 1. LOAD LIBRARY VISUAL BARCODE --}}
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<div class="bg-black text-white p-5">
    Status Login: {{ Auth::check() ? 'Login' : 'Logout' }} <br>
    ID Produk: {{ $product->id }}
</div>

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

    {{-- MENGGUNAKAN DIV UNTUK BYPASS ERROR NESTED FORM DI LAYOUT --}}
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

                        <input type="file" name="image" accept="image/*"
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
                            <input type="text" name="name" value="{{ $product->name }}" class="w-full px-4 py-3 rounded-xl border-slate-300 focus:ring-indigo-500 font-bold text-slate-700">
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
                                <input type="text" name="barcode" x-model="code" @input.debounce.300ms="renderBarcode()" @keydown.enter.prevent
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
                                    <input type="number" name="base_price" value="{{ $product->base_price }}" class="w-full pl-9 pr-4 py-3 rounded-xl border-slate-300 bg-slate-50 focus:bg-white transition text-sm font-medium">
                                </div>
                            </div>

                            <div :class="isService ? 'col-span-2' : ''">
                                <label class="block text-[10px] font-bold text-emerald-600 uppercase mb-1">Harga Jual <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-3 top-3 text-xs text-emerald-600 font-bold">Rp</span>
                                    <input type="number" name="sell_price" value="{{ $product->sell_price }}" :readonly="hasVariant"
                                           :class="hasVariant ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-emerald-50 text-emerald-700'"
                                           class="w-full pl-9 pr-4 py-3 rounded-xl border-emerald-200 font-bold focus:ring-emerald-500 transition text-sm">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div x-show="!isService">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Stok Total</label>
                                <input type="number" name="stock" value="{{ $product->stock }}" :readonly="hasVariant"
                                       :class="hasVariant ? 'bg-slate-100 text-slate-400' : 'bg-white text-slate-700'"
                                       class="w-full px-4 py-3 rounded-xl border-slate-300 font-bold text-sm">
                            </div>

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
                            <input type="number" name="weight" value="{{ $product->weight }}" class="w-full px-4 py-3 rounded-xl border-slate-300 focus:ring-indigo-500 transition text-sm font-medium text-slate-700">
                        </div>

                        <div class="mb-4">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Deskripsi Singkat</label>
                            <textarea name="description" rows="3" class="w-full px-4 py-3 rounded-xl border-slate-300 focus:ring-indigo-500 transition text-sm text-slate-700">{{ $product->description }}</textarea>
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
                    </div>
                </div>

                {{-- CARD VARIAN --}}
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="font-bold text-slate-800 text-lg">Varian & Sub Varian</h3>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="has_variant" value="1" x-model="hasVariant" class="sr-only peer">
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
                            <div class="grid grid-cols-12 gap-3 text-[10px] font-bold text-slate-400 uppercase px-1">
                                <div class="col-span-3">Nama Varian</div><div class="col-span-3">Barcode</div>
                                <div class="col-span-3">Harga Induk</div><div class="col-span-2">Stok</div>
                                <div class="col-span-1 text-center">Del</div>
                            </div>

                            <template x-for="(variant, index) in variants" :key="index">
                                <div class="bg-white rounded-xl border border-slate-200 mb-4 overflow-hidden shadow-sm">
                                    <div class="grid grid-cols-12 gap-3 items-center p-3 bg-slate-50 border-b border-slate-200">
                                        <input type="hidden" :name="'variants['+index+'][id]'" x-model="variant.id">

                                        <div class="col-span-3">
                                            <input type="text" :name="'variants['+index+'][name]'" x-model="variant.name" placeholder="Nama" class="w-full px-3 py-2 rounded-lg border-slate-300 text-sm font-bold text-slate-700 bg-white">
                                        </div>
                                        <div class="col-span-3 relative">
                                            <input type="text" :name="'variants['+index+'][barcode]'" x-model="variant.barcode" placeholder="Barcode" class="w-full px-3 py-2 rounded-lg border-slate-300 text-xs font-mono bg-white">
                                        </div>
                                        <div class="col-span-3">
                                            <input type="number" :name="'variants['+index+'][price]'" x-model="variant.price" placeholder="0" class="w-full px-3 py-2 rounded-lg border-emerald-200 text-sm font-bold text-emerald-700 bg-white text-right">
                                        </div>
                                        <div class="col-span-2">
                                            <input type="number" :name="'variants['+index+'][stock]'" x-model="variant.stock" placeholder="0" :readonly="variant.sub_variants && variant.sub_variants.length > 0" class="w-full px-3 py-2 rounded-lg border-slate-300 text-sm text-center">
                                        </div>
                                        <div class="col-span-1 text-center">
                                            <button type="button" @click="removeVariantRow(index)" class="h-8 w-8 text-slate-400 hover:text-red-500"><i class="fas fa-trash-alt"></i></button>
                                        </div>
                                    </div>

                                    <div class="p-3 bg-white">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-[10px] font-bold text-orange-500 uppercase"><i class="fas fa-code-branch"></i> Sub Varian</span>
                                            <button type="button" @click="addSubVariantRow(index)" class="text-[10px] bg-orange-50 text-orange-600 px-2 py-1 rounded border border-orange-200 font-bold">+ Anak</button>
                                        </div>
                                        <div class="space-y-2 pl-4 border-l-2 border-orange-100">
                                            <template x-for="(sub, subIndex) in variant.sub_variants" :key="subIndex">
                                                <div class="grid grid-cols-12 gap-2 items-center">
                                                    <input type="hidden" :name="'variants['+index+'][sub_variants]['+subIndex+'][id]'" x-model="sub.id">
                                                    <div class="col-span-3"><input type="text" :name="'variants['+index+'][sub_variants]['+subIndex+'][name]'" x-model="sub.name" placeholder="Ukuran" class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded"></div>
                                                    <div class="col-span-3"><input type="text" :name="'variants['+index+'][sub_variants]['+subIndex+'][barcode]'" x-model="sub.barcode" placeholder="Barcode" class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded"></div>
                                                    <div class="col-span-2"><input type="number" :name="'variants['+index+'][sub_variants]['+subIndex+'][price]'" x-model="sub.price" placeholder="Harga" class="w-full px-2 py-1.5 text-xs border border-emerald-300 text-emerald-700 text-right rounded"></div>
                                                    <div class="col-span-2"><input type="number" :name="'variants['+index+'][sub_variants]['+subIndex+'][stock]'" x-model="sub.stock" @input="calculateTotalStock(index)" placeholder="Stok" class="w-full px-2 py-1.5 text-xs border border-slate-300 text-center rounded"></div>
                                                    <div class="col-span-1"><input type="number" :name="'variants['+index+'][sub_variants]['+subIndex+'][weight]'" x-model="sub.weight" placeholder="Gram" class="w-full px-2 py-1.5 text-xs border border-slate-300 text-center rounded"></div>
                                                    <div class="col-span-1 text-center"><button type="button" @click="removeSubVariantRow(index, subIndex); calculateTotalStock(index)" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button></div>
                                                </div>
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

            variants: {!! $product->variants->map(function($v) {
                return [
                    'id' => $v->id, 'name' => $v->name, 'price' => $v->price, 'stock' => $v->stock, 'barcode' => $v->barcode ?? '',
                    'sub_variants' => $v->subVariants->map(function($sub) {
                        return [
                            'id' => $sub->id, 'name' => $sub->name, 'price' => $sub->price, 'stock' => $sub->stock, 'weight' => $sub->weight ?? 0, 'barcode' => $sub->barcode ?? ''
                        ];
                    })->toArray()
                ];
            })->toJson() !!},

            handleCategoryChange(event) {
                const option = event.target.options[event.target.selectedIndex];
                this.isService = option.dataset.type === 'service';
            },
            addVariantRow() {
                this.variants.push({ id: null, name: '', price: 0, stock: 0, barcode: '', sub_variants: [] });
            },
            removeVariantRow(index) { this.variants.splice(index, 1); },
            addSubVariantRow(variantIndex) {
                if(!this.variants[variantIndex].sub_variants) this.variants[variantIndex].sub_variants = [];
                this.variants[variantIndex].sub_variants.push({ id: null, name: '', price: this.variants[variantIndex].price, stock: 0, weight: 0, barcode: '' });
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

            // JURUS BYPASS NESTED FORM
            async submitTembusTembok() {
                this.isSubmitting = true;

                let container = this.$refs.formContainer;
                let formData = new FormData();
                let inputs = container.querySelectorAll('input, select, textarea');

                inputs.forEach(input => {
                    if (input.name) {
                        if (input.type === 'file') {
                            if (input.files.length > 0) formData.append(input.name, input.files[0]);
                        } else if (input.type === 'checkbox' || input.type === 'radio') {
                            if (input.checked) formData.append(input.name, input.value);
                        } else {
                            formData.append(input.name, input.value);
                        }
                    }
                });

                try {
                    let response = await fetch("{{ route('products.update', $product->id) }}", {
                        method: 'POST',
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
