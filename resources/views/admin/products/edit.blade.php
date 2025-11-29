@extends('layouts.admin')

@section('title', 'Edit Produk: ' . $product->name)
@section('page-title', 'Edit Produk')

@push('styles')
<style>
    /* --- RESET & IMAGE UPLOADER --- */
    /* Kita hapus settingan html, body agar mengikuti template bawaan */
    
    .image-uploader {
        border: 2px dashed #cbd5e1;
        border-radius: 0.75rem;
        padding: 3rem 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background-color: #f8fafc;
        position: relative;
        overflow: hidden;
    }

    .image-uploader:hover,
    .image-uploader.dragging {
        border-color: #6366f1;
        background-color: #eef2ff;
        transform: scale-[1.01];
    }

    .image-uploader i {
        font-size: 2.5rem;
        color: #94a3b8;
        margin-bottom: 1rem;
        transition: color 0.3s;
    }

    .image-uploader:hover i {
        color: #6366f1;
    }

    .image-preview {
        margin-top: 1rem;
        width: 100%;
        height: auto;
        max-height: 350px;
        border-radius: 0.5rem;
        object-fit: contain;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        display: none;
    }
    
    .image-preview[src] {
        display: block;
    }

    /* --- PERBAIKAN FOOTER (STICKY ACTION BAR) --- */
    .sticky-action {
        /* Ubah dari fixed ke sticky agar tidak melayang sembarangan */
        position: sticky; 
        bottom: 0;
        z-index: 999; /* Pastikan di atas elemen lain */
        
        background-color: rgba(255, 255, 255, 0.95); /* Sedikit lebih solid */
        backdrop-filter: blur(5px);
        border-top: 1px solid #e2e8f0;
        
        /* Spacing */
        padding: 1rem 1.5rem;
        margin-top: 2rem; /* Jarak dari konten di atasnya */
        margin-left: -1rem; /* Kompensasi padding container admin default (sesuaikan jika perlu) */
        margin-right: -1rem; /* Kompensasi padding container admin default */
        
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.05);
    }

    /* --- VARIANT TABLE STYLING --- */
    .variant-table-container {
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        overflow: hidden;
        margin-top: 1.5rem;
    }

    .variant-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .variant-table th {
        background-color: #f1f5f9;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }

    .variant-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .variant-table tr:last-child td {
        border-bottom: none;
    }

    .variant-table input {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 0.375rem;
        padding: 0.4rem 0.6rem;
        font-size: 0.875rem;
        transition: border-color 0.2s;
    }

    .variant-table input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
    }

    /* --- BUTTONS & UTILS --- */
    .spinner {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: spinner-border .75s linear infinite;
    }

    @keyframes spinner-border { to { transform: rotate(360deg); } }

    .required-label::after {
        content: " *";
        color: #ef4444;
    }
</style>
@endpush

@section('content')
@include('layouts.partials.notifications')

<form id="product-form" action="{{ route('admin.products.update', $product->slug) }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')

    {{-- Breadcrumb / Header Kecil --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Edit Produk</h1>
            <p class="text-sm text-gray-500 mt-1">Perbarui informasi produk: <span class="font-semibold">{{ $product->name }}</span></p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('admin.products.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

        {{-- KOLOM KIRI (UTAMA) --}}
        <div class="xl:col-span-2 space-y-8">

            {{-- 1. INFORMASI PRODUK --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fa-solid fa-box-open text-indigo-500 mr-2"></i> Informasi Dasar
                    </h2>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1 required-label">Nama Produk</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors" placeholder="Contoh: Kemeja Pria Slim Fit" required>
                        @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Produk</label>
                        <textarea name="description" id="description" rows="6" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors" placeholder="Jelaskan detail produk Anda...">{{ old('description', $product->description) }}</textarea>
                        @error('description') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- 2. MEDIA / GAMBAR --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fa-solid fa-images text-indigo-500 mr-2"></i> Media Produk
                    </h2>
                </div>
                <div class="p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gambar Utama</label>
                    <div id="image-uploader" class="image-uploader" tabindex="0">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <p class="font-semibold text-indigo-600 text-lg">Klik atau Drag & Drop</p>
                        <p class="text-sm text-gray-500 mt-1">Format: PNG, JPG, WEBP (Maks. 2MB)</p>
                    </div>
                    <input type="file" name="product_image" id="product_image" class="hidden" accept="image/png, image/jpeg, image/webp">
                    
                    {{-- Preview Gambar --}}
                    @if($product->image_url)
                        <div class="mt-4 p-2 border rounded-lg bg-gray-50 inline-block">
                            <img id="image-preview" src="{{ asset('public/storage/' . $product->image_url) }}" alt="Preview" class="image-preview" style="display: block;">
                        </div>
                    @else
                        <img id="image-preview" alt="Preview" class="image-preview mt-4">
                    @endif
                    
                    @error('product_image') <p class="mt-2 text-sm text-red-500 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- 3. INFORMASI PENJUAL --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fa-solid fa-store text-indigo-500 mr-2"></i> Informasi Toko / Penjual
                    </h2>
                </div>
                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="store_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Toko</label>
                            <input type="text" name="store_name" id="store_name" value="{{ old('store_name', $product->store_name) }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-1 text-xs text-gray-400">Biarkan kosong untuk default admin.</p>
                        </div>
                        <div>
                            <label for="seller_city" class="block text-sm font-medium text-gray-700 mb-1">Kota Asal</label>
                            <input type="text" name="seller_city" id="seller_city" value="{{ old('seller_city', $product->seller_city) }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="seller_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Penjual (Opsional)</label>
                            <input type="text" name="seller_name" id="seller_name" value="{{ old('seller_name', $product->seller_name) }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="seller_wa" class="block text-sm font-medium text-gray-700 mb-1">WhatsApp (Opsional)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-bold">+62</span>
                                <input type="text" name="seller_wa" id="seller_wa" value="{{ old('seller_wa', $product->seller_wa ? ltrim($product->seller_wa, '62') : '') }}" class="w-full border-gray-300 rounded-lg shadow-sm pl-12 focus:border-indigo-500 focus:ring-indigo-500" placeholder="8123xxxx">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Logo Toko (Opsional)</label>
                        <div class="flex items-start gap-4">
                            <div id="seller-logo-uploader" class="image-uploader py-4 px-6 flex-1" tabindex="0">
                                <p class="font-semibold text-indigo-600 text-sm">Upload Logo</p>
                            </div>
                            <div class="w-24 h-24 border rounded-lg bg-gray-50 flex items-center justify-center overflow-hidden">
                                @if($product->seller_logo)
                                    <img id="seller-logo-preview" src="{{ asset('public/storage/' . $product->seller_logo) }}" class="w-full h-full object-contain">
                                @else
                                    <img id="seller-logo-preview" class="w-full h-full object-contain hidden">
                                    <span class="text-xs text-gray-400 text-center px-2">Preview Logo</span>
                                @endif
                            </div>
                        </div>
                        <input type="file" name="seller_logo" id="seller_logo" class="hidden" accept="image/png, image/jpeg, image/webp">
                    </div>
                </div>
            </div>

            {{-- 4. VARIAN PRODUK --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fa-solid fa-layer-group text-indigo-500 mr-2"></i> Varian Produk
                    </h2>
                    <button type="button" id="add-variant-group" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded text-sm font-medium hover:bg-indigo-100 transition">
                        <i class="fa-solid fa-plus mr-1"></i> Tambah Varian
                    </button>
                </div>
                <div class="p-6">
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-r-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fa-solid fa-circle-info text-blue-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    Menambahkan varian akan menonaktifkan stok utama. Stok akan dihitung berdasarkan total stok varian.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div id="variant-groups-container" class="space-y-4">
                        {{-- Container Dinamis --}}
                    </div>

                    <div id="variant-combinations-section" class="hidden mt-8">
                        <h3 class="text-md font-bold text-gray-800 mb-3 pl-1 border-l-4 border-indigo-500">Atur Harga & Stok Varian</h3>
                        <div class="variant-table-container">
                            <table id="variant-combinations-table" class="variant-table">
                                <thead>
                                    <tr id="variant-table-headers"></tr>
                                </thead>
                                <tbody id="variant-combinations-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- KOLOM KANAN (SIDEBAR) --}}
        <div class="space-y-8">

            {{-- A. HARGA & STOK --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-lg font-semibold text-gray-800">Harga & Stok</h2>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1 required-label">Harga Jual</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-bold">Rp</span>
                            <input type="number" name="price" id="price" value="{{ old('price', $product->price) }}" class="w-full border-gray-300 rounded-lg shadow-sm pl-10 focus:border-indigo-500 focus:ring-indigo-500 text-lg font-bold text-gray-800" placeholder="0" required>
                        </div>
                        @error('price') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="original_price" class="block text-sm font-medium text-gray-700 mb-1">Harga Coret (Asli)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-bold">Rp</span>
                            <input type="number" name="original_price" id="original_price" value="{{ old('original_price', $product->original_price) }}" class="w-full border-gray-300 rounded-lg shadow-sm pl-10 focus:border-indigo-500 focus:ring-indigo-500 text-gray-500 line-through" placeholder="0">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="stock" class="block text-sm font-medium text-gray-700 mb-1 required-label">Stok</label>
                            <input type="number" name="stock" id="stock" value="{{ old('stock', $product->stock) }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-semibold">
                        </div>
                        <div>
                            <label for="weight" class="block text-sm font-medium text-gray-700 mb-1 required-label">Berat</label>
                            <div class="relative">
                                <input type="number" name="weight" id="weight" value="{{ old('weight', $product->weight) }}" class="w-full border-gray-300 rounded-lg shadow-sm pr-12 focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 text-xs font-bold bg-gray-50 rounded-r-lg border-l px-2">Gram</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dimensi (PxLxT) cm</label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="length" placeholder="P" value="{{ old('length', $product->length) }}" class="w-full border-gray-300 rounded-md text-center text-sm">
                            <span class="text-gray-400">x</span>
                            <input type="number" name="width" placeholder="L" value="{{ old('width', $product->width) }}" class="w-full border-gray-300 rounded-md text-center text-sm">
                            <span class="text-gray-400">x</span>
                            <input type="number" name="height" placeholder="T" value="{{ old('height', $product->height) }}" class="w-full border-gray-300 rounded-md text-center text-sm">
                        </div>
                    </div>
                </div>
            </div>

            {{-- B. ORGANISASI --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-lg font-semibold text-gray-800">Pengaturan Data</h2>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700 mb-1">SKU Induk</label>
                        <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}" class="w-full border-gray-300 rounded-lg shadow-sm uppercase tracking-wider focus:border-indigo-500 focus:ring-indigo-500" placeholder="AUTO-GEN">
                    </div>

                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1 required-label">Kategori</label>
                        <select name="category_id" id="category_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" data-attributes-url="{{ route('admin.categories.attributes', $category->id) }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                        <input type="text" name="tags" id="tags" value="{{ old('tags', $product->tags) }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Fashion, Pria, Murah...">
                    </div>
                </div>
            </div>

            {{-- C. ATRIBUT DINAMIS (Muncul jika ada) --}}
            <div id="attributes-card" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-lg font-semibold text-gray-800">Spesifikasi Tambahan</h2>
                </div>
                <div id="dynamic-attributes-container" class="p-6 space-y-4">
                    {{-- Diisi JS --}}
                </div>
            </div>

            {{-- D. STATUS --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-lg font-semibold text-gray-800">Status & Visibilitas</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status Publikasi</label>
                        <select name="status" id="status" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>✅ Aktif (Tayang)</option>
                            <option value="inactive" {{ old('status', $product->status) == 'inactive' ? 'selected' : '' }}>⛔ Nonaktif (Gudang)</option>
                        </select>
                    </div>

                    <div class="space-y-2 pt-2">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_new" value="1" {{ old('is_new', $product->is_new) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Label "Produk Baru"</span>
                        </label>
                        <br>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_bestseller" value="1" {{ old('is_bestseller', $product->is_bestseller) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Label "Bestseller"</span>
                        </label>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- STICKY ACTION BAR --}}
    <div class="sticky-action">
        <a href="{{ route('admin.products.index') }}" class="px-6 py-2.5 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 transition-colors">
            Batal
        </a>
        <button id="submit-button" type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors flex items-center">
            <i class="fa-solid fa-save mr-2"></i> Simpan Perubahan
        </button>
    </div>

</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    const product = @json($product);
    const existingAttributes = {!! $product->existing_attributes_json ?? '{}' !!};
    const existingVariantTypes = {!! $product->existing_variant_types_json ?? '[]' !!};
    const existingVariantCombinations = {!! $product->existing_variant_combinations_json ?? '{}' !!};

    // --- SETUP IMAGE UPLOADER (Modern) ---
    function setupImageUploader(uploaderId, inputId, previewId) {
        const uploader = document.getElementById(uploaderId);
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);

        if (!uploader || !input || !preview) return;

        const openFileDialog = () => input.click();
        uploader.addEventListener('click', openFileDialog);
        
        uploader.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploader.classList.add('dragging');
        });
        uploader.addEventListener('dragleave', () => uploader.classList.remove('dragging'));
        uploader.addEventListener('drop', (e) => {
            e.preventDefault();
            uploader.classList.remove('dragging');
            if (e.dataTransfer.files.length > 0) {
                input.files = e.dataTransfer.files;
                handleFileChange({ target: input });
            }
        });

        const handleFileChange = (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                    
                    // Sembunyikan teks di uploader jika perlu, atau biarkan sebagai area ganti gambar
                    // uploader.style.display = 'none'; 
                };
                reader.readAsDataURL(file);
            }
        };
        input.addEventListener('change', handleFileChange);
    }

    setupImageUploader('image-uploader', 'product_image', 'image-preview');
    setupImageUploader('seller-logo-uploader', 'seller_logo', 'seller-logo-preview');

    // --- FORM SUBMIT LOADING ---
    const form = document.getElementById('product-form');
    const submitButton = document.getElementById('submit-button');
    if (form && submitButton) {
        form.addEventListener('submit', (e) => {
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                form.reportValidity();
                e.preventDefault();
                return;
            }
            submitButton.disabled = true;
            submitButton.innerHTML = `<span class="spinner mr-2"></span> Menyimpan...`;
        });
    }

    // --- FORMAT WA ---
    const waInput = document.getElementById('seller_wa');
    if (waInput) {
        waInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
    }

    // --- DYNAMIC ATTRIBUTES ---
    const categorySelect = document.getElementById('category_id');
    const attributesCard = document.getElementById('attributes-card');
    const attributesContainer = document.getElementById('dynamic-attributes-container');

    async function fetchAndRenderAttributes() {
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        const url = selectedOption ? selectedOption.dataset.attributesUrl : null;

        if (!url) {
            attributesCard.classList.add('hidden');
            attributesContainer.innerHTML = '';
            return;
        }

        try {
            attributesContainer.innerHTML = '<div class="text-center text-gray-500 py-4"><span class="spinner text-indigo-500"></span> Memuat spesifikasi...</div>';
            attributesCard.classList.remove('hidden');
            const response = await fetch(url);
            if (!response.ok) throw new Error(`Status: ${response.status}`);

            const attributes = await response.json();
            attributesContainer.innerHTML = '';

            if (attributes && attributes.length > 0) {
                attributes.forEach(attr => {
                    if (typeof attr === 'object' && attr !== null && attr.slug) {
                        const field = createAttributeField(attr);
                        attributesContainer.appendChild(field);
                        if (existingAttributes && existingAttributes[attr.slug] !== undefined) {
                            fillAttributeValue(field, attr, existingAttributes[attr.slug]);
                        }
                    }
                });
            } else {
                attributesContainer.innerHTML = '<p class="text-gray-400 text-sm italic text-center">Tidak ada spesifikasi khusus untuk kategori ini.</p>';
            }
        } catch (error) {
            console.error(error);
            attributesContainer.innerHTML = `<p class="text-red-500 text-sm">Gagal memuat spesifikasi.</p>`;
        }
    }

    function createAttributeField(attribute) {
        const wrapper = document.createElement('div');
        const isRequired = attribute.is_required ? 'required' : '';
        const requiredMark = attribute.is_required ? '<span class="text-red-500">*</span>' : '';
        const label = `<label class="block text-sm font-medium text-gray-700 mb-1">${attribute.name || 'Atribut'} ${requiredMark}</label>`;
        const inputName = `attributes[${attribute.slug}]`;
        let inputHtml = '';

        const commonClass = "w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500";

        if (attribute.type === 'select') {
            const opts = (attribute.options || '').split(',').map(o=>o.trim()).filter(o=>o).map(o=>`<option value="${o}">${o}</option>`).join('');
            inputHtml = `<select name="${inputName}" class="${commonClass}" ${isRequired}><option value="">-- Pilih --</option>${opts}</select>`;
        } else if (attribute.type === 'textarea') {
            inputHtml = `<textarea name="${inputName}" rows="3" class="${commonClass}" ${isRequired}></textarea>`;
        } else if (attribute.type === 'checkbox') {
            const checks = (attribute.options || '').split(',').map(o=>o.trim()).filter(o=>o).map((o,i) => `
                <label class="inline-flex items-center mr-4 mb-2">
                    <input type="checkbox" name="${inputName}[]" value="${o}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700">${o}</span>
                </label>
            `).join('');
            inputHtml = `<div class="mt-1">${checks}</div>`;
        } else {
            inputHtml = `<input type="${attribute.type === 'number' ? 'number' : 'text'}" name="${inputName}" class="${commonClass}" ${isRequired}>`;
        }

        wrapper.innerHTML = label + inputHtml;
        return wrapper;
    }

    function fillAttributeValue(el, attr, val) {
        if (val === null || val === undefined) return;
        if (attr.type === 'checkbox') {
            let arr = Array.isArray(val) ? val : (typeof val === 'string' ? [val] : []);
            // Try parse json string
            if(typeof val === 'string' && val.startsWith('[')) { try { arr = JSON.parse(val); } catch(e){} }
            
            el.querySelectorAll(`input[type="checkbox"]`).forEach(chk => {
                if(arr.includes(chk.value)) chk.checked = true;
            });
        } else {
            const inp = el.querySelector(`[name^="attributes"]`);
            if(inp) inp.value = val;
        }
    }

    if (categorySelect) {
        categorySelect.addEventListener('change', fetchAndRenderAttributes);
        fetchAndRenderAttributes();
    }

    // --- VARIANT LOGIC ---
    const variantContainer = document.getElementById('variant-groups-container');
    const addVariantBtn = document.getElementById('add-variant-group');
    const mainStock = document.getElementById('stock');
    const mainPrice = document.getElementById('price');
    const mainOriginalPrice = document.getElementById('original_price');
    const variantSection = document.getElementById('variant-combinations-section');
    const variantHead = document.getElementById('variant-table-headers');
    const variantBody = document.getElementById('variant-combinations-body');
    let variantIndex = 0;
    let currentTypes = [];

    function updateTypes() {
        currentTypes = [];
        variantContainer.querySelectorAll('.variant-group-item').forEach(el => {
            const n = el.querySelector('input[name$="[name]"]').value.trim();
            const o = el.querySelector('input[name$="[options]"]').value.trim();
            if(n && o) currentTypes.push({ name: n, options: o.split(',').map(x=>x.trim()).filter(x=>x) });
        });
        generateTable();
        toggleMainInputs();
    }

    function createGroup(idx, data = {name:'', options:''}) {
        const div = document.createElement('div');
        div.className = 'variant-group-item bg-gray-50 border border-gray-200 rounded-lg p-4 relative';
        div.innerHTML = `
            <button type="button" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 remove-group transition">
                <i class="fa-solid fa-times"></i>
            </button>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Varian</label>
                    <input type="text" name="variant_types[${idx}][name]" value="${data.name}" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Contoh: Warna">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pilihan (Koma)</label>
                    <input type="text" name="variant_types[${idx}][options]" value="${data.options}" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Merah, Biru, Hijau">
                </div>
            </div>
        `;
        div.querySelector('.remove-group').addEventListener('click', () => {
            div.remove();
            updateTypes();
        });
        div.querySelectorAll('input').forEach(i => i.addEventListener('input', updateTypes));
        return div;
    }

    if(addVariantBtn) {
        addVariantBtn.addEventListener('click', () => {
            variantContainer.appendChild(createGroup(variantIndex++));
            updateTypes();
        });
    }

    function generateTable() {
        variantHead.innerHTML = '';
        variantBody.innerHTML = '';
        
        if(currentTypes.length === 0) {
            variantSection.classList.add('hidden');
            return;
        }
        variantSection.classList.remove('hidden');

        // Headers
        currentTypes.forEach(t => {
            const th = document.createElement('th');
            th.textContent = t.name;
            variantHead.appendChild(th);
        });
        ['Harga', 'Stok', 'SKU (Opsional)'].forEach(h => {
            const th = document.createElement('th');
            th.textContent = h;
            variantHead.appendChild(th);
        });

        // Rows
        const combos = getCombos(currentTypes.map(t => t.options));
        combos.forEach((combo, cIdx) => {
            const tr = document.createElement('tr');
            
            // Kolom Nama Varian
            combo.forEach(val => {
                const td = document.createElement('td');
                td.textContent = val;
                td.className = "bg-gray-50 text-gray-600 font-medium";
                tr.appendChild(td);
            });

            // Key untuk lookup data lama
            const key = combo.map((v,i) => `${currentTypes[i].name}:${v}`).sort().join(';');
            const oldData = existingVariantCombinations[key] || { price: mainPrice.value, stock: 0, sku_code: '' };

            // Input Harga
            const tdPrice = document.createElement('td');
            tdPrice.innerHTML = `<input type="number" name="product_variants[${cIdx}][price]" value="${oldData.price}" class="w-full min-w-[100px] border-gray-300 rounded-md text-sm" required>`;
            tr.appendChild(tdPrice);

            // Input Stok
            const tdStock = document.createElement('td');
            tdStock.innerHTML = `<input type="number" name="product_variants[${cIdx}][stock]" value="${oldData.stock}" class="w-full min-w-[80px] border-gray-300 rounded-md text-sm" required>`;
            tr.appendChild(tdStock);

            // Input SKU
            const tdSku = document.createElement('td');
            tdSku.innerHTML = `<input type="text" name="product_variants[${cIdx}][sku_code]" value="${oldData.sku_code || ''}" class="w-full min-w-[100px] border-gray-300 rounded-md text-sm">`;
            tr.appendChild(tdSku);

            // Hidden Inputs
            combo.forEach((val, i) => {
                const hType = document.createElement('input'); hType.type='hidden';
                hType.name = `product_variants[${cIdx}][variant_options][${i}][type_name]`; hType.value = currentTypes[i].name;
                tr.appendChild(hType);

                const hVal = document.createElement('input'); hVal.type='hidden';
                hVal.name = `product_variants[${cIdx}][variant_options][${i}][value]`; hVal.value = val;
                tr.appendChild(hVal);
            });

            variantBody.appendChild(tr);
        });
    }

    function getCombos(arrays) {
        if(arrays.length === 0) return [];
        return arrays.reduce((a, b) => a.flatMap(d => b.map(e => [d, e].flat())));
    }

    function toggleMainInputs() {
        const hasVar = variantContainer.children.length > 0;
        const msg = "Diatur di varian";
        
        if(hasVar) {
            mainStock.disabled = true; mainStock.value = ''; mainStock.placeholder = msg;
            mainPrice.disabled = true; mainPrice.value = ''; mainPrice.placeholder = msg;
            mainOriginalPrice.disabled = true; mainOriginalPrice.value = ''; 
        } else {
            mainStock.disabled = false; mainStock.value = product.stock; mainStock.placeholder = '0';
            mainPrice.disabled = false; mainPrice.value = product.price; mainPrice.placeholder = '0';
            mainOriginalPrice.disabled = false; mainOriginalPrice.value = product.original_price;
        }
    }

    // INIT VARIANTS
    existingVariantTypes.forEach(t => {
        const g = createGroup(variantIndex++, { name: t.name, options: t.options });
        variantContainer.appendChild(g);
    });
    updateTypes();
});
</script>
@endpush