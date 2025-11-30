@extends('layouts.admin')

@section('title', 'Edit Produk: ' . $product->name)
@section('page-title', 'Edit Produk')

@push('styles')
<style>
    /* --- CUSTOM CSS: UPLOAD, VARIANT TABLE, & INPUT GROUP --- */
    .image-uploader-box {
        position: relative;
        width: 100%;
        aspect-ratio: 1/1;
        border: 2px dashed #cbd5e1;
        border-radius: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
        background-color: #f8fafc;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .image-uploader-box:hover {
        border-color: #6366f1;
        background-color: #eef2ff;
    }
    .image-uploader-box.has-image {
        border-style: solid;
        border-color: #e2e8f0;
    }
    
    .sticky-action {
        position: sticky;
        bottom: 0;
        z-index: 50;
        background-color: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(5px);
        border-top: 1px solid #e2e8f0;
        padding: 1rem 1.5rem;
        margin-top: 2rem;
        margin-left: -1.5rem; 
        margin-right: -1.5rem;
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.05);
    }

    .variant-table th { @apply bg-gray-100 text-gray-600 font-semibold text-xs uppercase px-4 py-3 border-b; }
    .variant-table td { @apply px-4 py-3 border-b; }
    
    /* Tombol Hapus Gambar */
    .btn-remove-img {
        position: absolute;
        top: -8px;
        right: -8px;
        width: 24px;
        height: 24px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        z-index: 20;
        transition: background 0.2s;
    }
    .btn-remove-img:hover { background: #dc2626; }
</style>
@endpush

@section('content')
@include('layouts.partials.notifications')

<form id="product-form" action="{{ route('admin.products.update', $product->slug) }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')

    {{-- Hidden Inputs (PENTING untuk menjaga data read-only agar tidak hilang) --}}
    <input type="hidden" name="category_id" value="{{ $product->category_id }}">
    <input type="hidden" name="sku" value="{{ $product->sku }}">

    {{-- Header --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Edit Produk</h1>
            <p class="text-sm text-gray-500 mt-1">Perbarui informasi: <span class="font-semibold">{{ $product->name }}</span></p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('admin.products.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase hover:bg-gray-50 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

        {{-- KOLOM KIRI (UTAMA) --}}
        <div class="xl:col-span-2 space-y-8">

            {{-- 1. INFORMASI DASAR --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fa-solid fa-box-open text-blue-500 mr-2"></i> Informasi Dasar
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Produk <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" required>
                        @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                        <textarea name="description" id="description" rows="6" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- 2. MEDIA PRODUK (5 GAMBAR) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <span class="bg-indigo-100 text-indigo-600 p-1.5 rounded-lg"><i class="fa-solid fa-images text-sm"></i></span>
                    Media Produk (Maks. 5)
                </h2>

                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    @for ($i = 0; $i < 5; $i++)
                        <div class="relative w-full aspect-square group">
                            <input type="file" name="product_images[]" id="input-img-{{ $i }}" class="hidden" accept="image/*" onchange="previewImage(this, {{ $i }})">
                            
                            <label for="input-img-{{ $i }}" class="image-uploader-box {{ isset($product->images[$i]) ? 'has-image' : '' }}">
                                {{-- Placeholder --}}
                                <div id="placeholder-{{ $i }}" class="flex flex-col items-center {{ isset($product->images[$i]) ? 'hidden' : '' }}">
                                    @if($i === 0)
                                        <span class="absolute top-2 left-2 bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded z-10">Utama</span>
                                    @else
                                        <span class="absolute top-2 left-2 bg-gray-500 text-white text-[10px] font-bold px-2 py-0.5 rounded z-10">Gbr {{ $i+1 }}</span>
                                    @endif
                                    <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-300 mb-2"></i>
                                    <span class="text-[10px] text-gray-400">Upload</span>
                                </div>

                                {{-- Preview Image --}}
                                <img id="preview-img-{{ $i }}" 
                                     src="{{ isset($product->images[$i]) ? asset('storage/'.$product->images[$i]->path) : '' }}" 
                                     class="absolute inset-0 w-full h-full object-cover {{ isset($product->images[$i]) ? '' : 'hidden' }}">
                            </label>

                            {{-- Tombol Hapus --}}
                            <button type="button" id="btn-remove-{{ $i }}" onclick="removeImage({{ $i }})" class="btn-remove-img {{ isset($product->images[$i]) ? '' : 'hidden' }}">
                                <i class="fa-solid fa-times"></i>
                            </button>
                        </div>
                    @endfor
                </div>
                <p class="text-xs text-gray-400 mt-3 flex items-center gap-1">
                    <i class="fa-solid fa-circle-info text-indigo-400"></i> Klik kotak untuk mengunggah. Gambar pertama jadi cover.
                </p>
            </div>

            {{-- 3. INFORMASI TOKO --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fa-solid fa-store text-indigo-500 mr-2"></i> Informasi Toko
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Toko</label>
                            <input type="text" name="store_name" value="{{ old('store_name', $product->store_name) }}" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kota Asal</label>
                            <input type="text" name="seller_city" value="{{ old('seller_city', $product->seller_city) }}" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Penjual</label>
                            <input type="text" name="seller_name" value="{{ old('seller_name', $product->seller_name) }}" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
                            <div class="flex rounded-lg shadow-sm">
                                <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm font-medium">+62</span>
                                <input type="text" name="seller_wa" id="seller_wa" value="{{ old('seller_wa', $product->seller_wa ? ltrim($product->seller_wa, '62') : '') }}" class="flex-1 w-full px-3 py-2.5 border border-gray-300 rounded-none rounded-r-lg focus:ring-2 focus:ring-blue-500 transition" placeholder="8123xxxx">
                            </div>
                        </div>
                    </div>
                    
                    {{-- Logo Toko --}}
                    <div class="border-t pt-4 flex items-center gap-4">
                        <div class="relative w-20 h-20 rounded-lg border border-gray-300 overflow-hidden cursor-pointer hover:border-blue-500" onclick="document.getElementById('seller_logo').click();">
                            <img id="seller-logo-preview" class="w-full h-full object-contain p-1" src="{{ $product->seller_logo ? asset('storage/' . $product->seller_logo) : 'https://placehold.co/100x100?text=Logo' }}">
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 hover:opacity-100 transition"><i class="fa-solid fa-pen text-white"></i></div>
                        </div>
                        <div class="text-sm text-gray-500">
                            <p class="font-semibold text-gray-700">Logo Toko</p>
                            <p class="text-xs">Klik gambar untuk ganti (Max 2MB)</p>
                        </div>
                        <input type="file" name="seller_logo" id="seller_logo" class="hidden" accept="image/*" onchange="previewSingleImage(this, 'seller-logo-preview')">
                    </div>
                </div>
            </div>

            {{-- 4. VARIAN PRODUK --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fa-solid fa-layer-group text-indigo-500 mr-2"></i> Varian Produk
                    </h2>
                    <button type="button" id="add-variant-group" class="px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded text-sm hover:bg-indigo-100 transition"><i class="fa-solid fa-plus mr-1"></i> Tambah</button>
                </div>
                <div class="p-6">
                    <div id="variant-groups-container" class="space-y-4"></div>
                    <div id="variant-combinations-section" class="hidden mt-6">
                        <h3 class="text-sm font-bold text-gray-700 mb-3 border-l-4 border-indigo-500 pl-2">Atur Harga & Stok Varian</h3>
                        <div class="border rounded-lg overflow-hidden">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr id="variant-table-headers"></tr>
                                </thead>
                                <tbody id="variant-combinations-body" class="divide-y divide-gray-100"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- KOLOM KANAN (SIDEBAR) --}}
        <div class="space-y-8">

            {{-- CARD: HARGA & STOK --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <span class="bg-blue-100 text-blue-600 p-1.5 rounded-lg"><i class="fa-solid fa-tag text-sm"></i></span> Harga & Stok
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Harga Jual <span class="text-red-500">*</span></label>
                        <div class="relative rounded-lg shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><span class="text-gray-500 text-sm">Rp</span></div>
                            <input type="text" name="price" id="price" class="currency-input block w-full pl-10 pr-4 py-2.5 border border-blue-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 font-semibold" placeholder="0" value="{{ old('price', $product->price) }}" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Harga Coret</label>
                        <div class="relative rounded-lg shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><span class="text-gray-500 text-sm">Rp</span></div>
                            <input type="text" name="original_price" id="original_price" class="currency-input block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="0" value="{{ old('original_price', $product->original_price) }}">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stok</label>
                            <input type="number" name="stock" id="stock" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg" value="{{ old('stock', $product->stock) }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Berat</label>
                            <div class="flex">
                                <input type="number" name="weight" class="w-full px-3 py-2.5 border border-gray-300 rounded-l-lg" value="{{ old('weight', $product->weight) }}">
                                <span class="inline-flex items-center px-3 rounded-r-lg border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-xs font-bold">GRAM</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD: KATEGORI (READ ONLY) --}}
            <div class="bg-white rounded-xl shadow-sm border border-red-100 overflow-hidden">
                <div class="bg-red-50 px-5 py-3 border-b border-red-100 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-800">Kategori & Data</h3>
                    <span class="text-[10px] font-semibold bg-white text-red-500 px-2 py-0.5 rounded-full border border-red-200">Read Only</span>
                </div>
                <div class="p-5">
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Kategori:</label>
                        <div class="flex items-center gap-2 text-indigo-700 font-semibold text-base">
                            <i class="fa-solid fa-folder-open"></i> <span>{{ $product->category->name ?? 'Tidak ada' }}</span>
                        </div>
                    </div>
                    <div class="mb-5">
                        <label class="block text-xs font-medium text-gray-500 mb-1">SKU:</label>
                        <div class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg font-mono text-sm border border-gray-200">{{ $product->sku ?? '-' }}</div>
                    </div>
                    <a href="{{ route('admin.products.edit.specifications', $product->slug) }}" class="block w-full text-center py-2.5 bg-emerald-400 hover:bg-emerald-500 text-white rounded-lg font-bold shadow-sm transition">
                        <i class="fa-solid fa-sliders mr-1"></i> Edit Kategori & Spesifikasi
                    </a>
                </div>
            </div>

            {{-- CARD: STATUS --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Status</h2>
                <select name="status" class="w-full border-gray-300 rounded-lg px-3 py-2">
                    <option value="active" {{ $product->status == 'active' ? 'selected' : '' }}>✅ Aktif</option>
                    <option value="inactive" {{ $product->status == 'inactive' ? 'selected' : '' }}>⛔ Nonaktif</option>
                </select>
                <div class="mt-4 space-y-2">
                    <label class="flex items-center"><input type="checkbox" name="is_bestseller" value="1" {{ $product->is_bestseller ? 'checked' : '' }} class="rounded text-blue-600 mr-2"> Bestseller</label>
                    <label class="flex items-center"><input type="checkbox" name="is_new" value="1" {{ $product->is_new ? 'checked' : '' }} class="rounded text-blue-600 mr-2"> Produk Baru</label>
                </div>
            </div>

        </div>
    </div>

    {{-- ACTION BAR --}}
    <div class="sticky-action">
        <a href="{{ route('admin.products.index') }}" class="px-6 py-2.5 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 transition">Batal</a>
        <button id="submit-button" type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition flex items-center">
            <i class="fa-solid fa-save mr-2"></i> Simpan Perubahan
        </button>
    </div>

</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    
    // --- 1. GLOBAL VARIABLES & INIT ---
    const product = @json($product);
    const existingVariantTypes = {!! $product->existing_variant_types_json ?? '[]' !!};
    const existingVariantCombinations = {!! $product->existing_variant_combinations_json ?? '{}' !!};
    
    // --- 2. FORMAT RUPIAH (INPUT HARGA) ---
    const currencyInputs = document.querySelectorAll('.currency-input');

    function formatRupiah(angka) {
        if (!angka) return '';
        let number_string = angka.toString().replace(/[^,\d]/g, '');
        let split = number_string.split(',');
        let sisa = split[0].length % 3;
        let rupiah = split[0].substr(0, sisa);
        let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    }

    currencyInputs.forEach(input => {
        // Format saat load (data dari DB)
        input.value = formatRupiah(input.value);
        // Format saat ketik
        input.addEventListener('keyup', function(e) {
            this.value = formatRupiah(this.value);
        });
    });

    // --- 3. PREVIEW GAMBAR (MULTI & SINGLE) ---
    // Fungsi ini dipanggil langsung dari atribut HTML onchange="..."
    window.previewImage = function(input, index) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById(`preview-img-${index}`);
                const placeholder = document.getElementById(`placeholder-${index}`);
                const btnRemove = document.getElementById(`btn-remove-${index}`);
                
                img.src = e.target.result;
                img.classList.remove('hidden');
                placeholder.classList.add('hidden');
                btnRemove.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    };

    window.removeImage = function(index) {
        const input = document.getElementById(`input-img-${index}`);
        const img = document.getElementById(`preview-img-${index}`);
        const placeholder = document.getElementById(`placeholder-${index}`);
        const btnRemove = document.getElementById(`btn-remove-${index}`);

        input.value = "";
        img.src = "";
        img.classList.add('hidden');
        placeholder.classList.remove('hidden');
        btnRemove.classList.add('hidden');
    };

    window.previewSingleImage = function(input, imgId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(imgId).src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    };

    // --- 4. VARIANT LOGIC ---
    const variantContainer = document.getElementById('variant-groups-container');
    const addVariantBtn = document.getElementById('add-variant-group');
    const mainStock = document.getElementById('stock');
    const mainPrice = document.getElementById('price');
    const mainOriginalPrice = document.getElementById('original_price');
    const variantSection = document.getElementById('variant-combinations-section');
    const variantHead = document.getElementById('variant-table-headers');
    const variantBody = document.getElementById('variant-combinations-body');
    let variantIndex = 0;

    function createGroup(idx, data = {name:'', options:''}) {
        const div = document.createElement('div');
        div.className = 'variant-group-item bg-gray-50 border border-gray-200 rounded-lg p-4 relative';
        div.innerHTML = `
            <button type="button" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 remove-group transition"><i class="fa-solid fa-times"></i></button>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Varian</label>
                    <input type="text" name="variant_types[${idx}][name]" value="${data.name}" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pilihan (Koma)</label>
                    <input type="text" name="variant_types[${idx}][options]" value="${data.options}" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500">
                </div>
            </div>`;
        
        div.querySelector('.remove-group').addEventListener('click', () => { div.remove(); updateVariants(); });
        div.querySelectorAll('input').forEach(i => i.addEventListener('input', updateVariants));
        return div;
    }

    function updateVariants() {
        const types = [];
        variantContainer.querySelectorAll('.variant-group-item').forEach(el => {
            const n = el.querySelector('input[name$="[name]"]').value.trim();
            const o = el.querySelector('input[name$="[options]"]').value.trim();
            if(n && o) types.push({ name: n, options: o.split(',').map(x=>x.trim()).filter(x=>x) });
        });

        generateTable(types);
        toggleMainInputs(types.length > 0);
    }

    function generateTable(types) {
        variantHead.innerHTML = '';
        variantBody.innerHTML = '';
        
        if(types.length === 0) { variantSection.classList.add('hidden'); return; }
        variantSection.classList.remove('hidden');

        // Headers
        types.forEach(t => variantHead.innerHTML += `<th>${t.name}</th>`);
        variantHead.innerHTML += `<th>Harga</th><th>Stok</th><th>SKU</th>`;

        // Rows (Combinations)
        const combos = getCombos(types.map(t => t.options));
        combos.forEach((combo, cIdx) => {
            const tr = document.createElement('tr');
            let comboKey = combo.map((v, i) => `${types[i].name}:${v}`).sort().join(';');
            let old = existingVariantCombinations[comboKey] || { price: mainPrice.value.replace(/\./g,''), stock: 0, sku_code: '' };

            combo.forEach((val, i) => {
                tr.innerHTML += `<td class="bg-gray-50 text-gray-600 font-medium">${val}
                    <input type="hidden" name="product_variants[${cIdx}][variant_options][${i}][type_name]" value="${types[i].name}">
                    <input type="hidden" name="product_variants[${cIdx}][variant_options][${i}][value]" value="${val}">
                </td>`;
            });

            tr.innerHTML += `
                <td><input type="text" name="product_variants[${cIdx}][price]" value="${old.price}" class="w-full border-gray-300 rounded text-sm min-w-[100px]" required></td>
                <td><input type="number" name="product_variants[${cIdx}][stock]" value="${old.stock}" class="w-full border-gray-300 rounded text-sm min-w-[80px]" required></td>
                <td><input type="text" name="product_variants[${cIdx}][sku_code]" value="${old.sku_code || ''}" class="w-full border-gray-300 rounded text-sm min-w-[100px]"></td>
            `;
            variantBody.appendChild(tr);
        });
    }

    function getCombos(arrays) {
        if(arrays.length === 0) return [];
        return arrays.reduce((a, b) => a.flatMap(d => b.map(e => [d, e].flat())));
    }

    function toggleMainInputs(hasVar) {
        if(hasVar) {
            mainStock.disabled = true; mainStock.value = ''; mainStock.placeholder = "Diatur di varian";
            mainPrice.readOnly = true; mainPrice.classList.add('bg-gray-100');
        } else {
            mainStock.disabled = false; mainStock.placeholder = '0';
            mainPrice.readOnly = false; mainPrice.classList.remove('bg-gray-100');
        }
    }

    if(addVariantBtn) {
        addVariantBtn.addEventListener('click', () => {
            variantContainer.appendChild(createGroup(variantIndex++));
            updateVariants();
        });
    }

    // Init Existing Variants
    existingVariantTypes.forEach(t => {
        const g = createGroup(variantIndex++, { name: t.name, options: t.options });
        variantContainer.appendChild(g);
    });
    updateVariants();

    // --- 5. FORM SUBMIT HANDLER ---
    const form = document.getElementById('product-form');
    form.addEventListener('submit', (e) => {
        // Hapus titik dari input currency sebelum submit
        currencyInputs.forEach(input => {
            input.value = input.value.replace(/\./g, '');
        });
        
        const btn = document.getElementById('submit-button');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Menyimpan...';
    });

    // Format WA
    const waInput = document.getElementById('seller_wa');
    if(waInput) waInput.addEventListener('input', (e) => e.target.value = e.target.value.replace(/\D/g, ''));
});
</script>
@endpush