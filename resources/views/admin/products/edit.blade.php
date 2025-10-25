@extends('layouts.admin')

@section('title', 'Edit Produk: ' . $product->name)
@section('page-title', 'Edit Produk: ' . $product->name)

@push('styles')
<style>
    /* =============================
        STYLE EDIT PRODUK - FIXED
        ============================= */

    .image-uploader {
        border: 2px dashed #d1d5db;
        border-radius: 0.5rem;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.3s ease;
        background-color: #fafafa;
    }

    .image-uploader:hover,
    .image-uploader.dragging {
        border-color: #4f46e5;
        background-color: #f3f4ff;
    }

    .image-preview {
        margin-top: 1rem;
        max-width: 100%;
        max-height: 300px;
        border-radius: 0.5rem;
        display: block; /* Tampilkan default jika ada gambar */
        object-fit: contain; /* Agar gambar tidak terpotong */
    }
    .image-preview:not([src]) {
        display: none; /* Sembunyikan jika tidak ada src */
    }

    .spinner {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        vertical-align: text-bottom;
        border: 0.2em solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: spinner-border .75s linear infinite;
    }

    @keyframes spinner-border {
        to {
            transform: rotate(360deg);
        }
    }

    /* Style tambahan untuk tombol varian */
    .btn {
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        font-weight: 600;
        transition: all 0.2s ease-in-out;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1.25;
    }
    .btn-primary {
        background-color: #4f46e5;
        color: white;
        border: 1px solid transparent;
    }
    .btn-primary:hover {
        background-color: #4338ca;
    }
    .btn-secondary {
        background-color: #e5e7eb;
        color: #374151;
        border: 1px solid #d1d5db;
    }
    .btn-secondary:hover {
        background-color: #d1d5db;
    }
    .btn-outline-primary {
        background-color: transparent;
        color: #4f46e5;
        border: 1px solid #4f46e5;
    }
    .btn-outline-primary:hover {
        background-color: #eef2ff;
    }
    .btn-sm {
        padding: 0.25rem 0.75rem;
        font-size: 0.875rem;
    }
    /* Mengubah cursor untuk input disabled */
    input:disabled, textarea:disabled, select:disabled {
        cursor: not-allowed;
        background-color: #f3f4f6;
    }

    /* Gaya untuk tabel varian */
    .variant-table-container {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        margin-top: 1rem;
        padding: 0.5rem;
    }
    .variant-table {
        width: 100%;
        min-width: 600px; /* Minimal lebar tabel untuk mencegah terlalu sempit */
        border-collapse: collapse;
    }
    .variant-table th, .variant-table td {
        padding: 0.75rem 1rem;
        border: 1px solid #e5e7eb;
        text-align: left;
        font-size: 0.875rem;
    }
    .variant-table th {
        background-color: #f9fafb;
        font-weight: 600;
        color: #374151;
        white-space: nowrap; /* Mencegah header wrap */
    }
    .variant-table input[type="number"],
    .variant-table input[type="text"] {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.25rem;
        font-size: 0.875rem;
    }
    .variant-table td:last-child {
        text-align: center;
    }
    .variant-table .text-red-500 {
        cursor: pointer;
    }

    /* =============================
        FIX LAYOUT SCROLLING
        ============================= */
    html, body {
        height: 100%;
        overflow: hidden;
    }

    /* Penyesuaian 'content-wrapper' agar pas dengan layout AdminLTE Anda */
    .content-wrapper {
        height: calc(100vh - (3.5rem + 1px)); /* (tinggi navbar + border) */
        overflow-y: auto;
        padding-bottom: 100px; /* Ruang untuk sticky action */
    }

    .content {
        padding-bottom: 100px; /* Fallback jika .content-wrapper tidak ada */
    }

    /* Sticky footer button agar tidak menutupi input */
    .sticky-action {
        position: sticky;
        bottom: 0;
        z-index: 10;
        background-color: #fff;
        border-top: 1px solid #e5e7eb;
        padding: 1rem 1.5rem;
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        box-shadow: 0 -2px 6px rgba(0,0,0,0.05);
    }
</style>
@endpush

@section('content')
@include('layouts.partials.notifications')

{{-- Form Anda sudah benar, enctype="multipart/form-data" sudah ada --}}
<form id="product-form" action="{{ route('admin.products.update', $product->slug) }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Kolom Kiri --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Informasi Produk --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Produk</h2>
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nama Produk</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('name') border-red-500 @enderror" required>
                        @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                        <textarea name="description" id="description" rows="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('description') border-red-500 @enderror">{{ old('description', $product->description) }}</textarea>
                        @error('description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Gambar Produk --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Gambar Utama Produk</h2>
                <div id="image-uploader" class="image-uploader" tabindex="0">
                    <p class="font-semibold text-indigo-600">Klik untuk upload</p>
                    <p class="text-xs text-gray-500">atau seret file ke sini (PNG, JPG, WEBP hingga 2MB)</p>
                </div>
                <input type="file" name="product_image" id="product_image" class="hidden" accept="image/png, image/jpeg, image/webp">
                
                {{-- [PERBAIKAN] Menggunakan $product->image_url --}}
                @if($product->image_url)
                    <img id="image-preview" src="{{ asset('storage/' . $product->image_url) }}" alt="Pratinjau Gambar Produk" class="image-preview" />
                @else
                    <img id="image-preview" alt="Pratinjau Gambar Produk" class="image-preview" />
                @endif
                {{-- [AKHIR PERBAIKAN] --}}
                
                @error('product_image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Informasi Penjual --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Penjual</h2>
                <div class="space-y-4">
                    <div>
                        <label for="store_name" class="block text-sm font-medium text-gray-700">Nama Toko</label>
                        <input type="text" name="store_name" id="store_name" value="{{ old('store_name', $product->store_name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('store_name') border-red-500 @enderror">
                        <p class="mt-1 text-xs text-gray-500">Kosongkan untuk menggunakan data admin default.</p>
                        @error('store_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="seller_name" class="block text-sm font-medium text-gray-700">Nama Penjual (Opsional)</label>
                        <input type="text" name="seller_name" id="seller_name" value="{{ old('seller_name', $product->seller_name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('seller_name') border-red-500 @enderror">
                    </div>
                    <div>
                        <label for="seller_city" class="block text-sm font-medium text-gray-700">Kota Penjual</label>
                        <input type="text" name="seller_city" id="seller_city" value="{{ old('seller_city', $product->seller_city) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('seller_city') border-red-500 @enderror">
                        <p class="mt-1 text-xs text-gray-500">Kosongkan untuk menggunakan data admin default.</p>
                        @error('seller_city') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="seller_wa" class="block text-sm font-medium text-gray-700">WhatsApp Penjual (Opsional)</label>
                        <input type="text" name="seller_wa" id="seller_wa" value="{{ old('seller_wa', $product->seller_wa ? ltrim($product->seller_wa, '62') : '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('seller_wa') border-red-500 @enderror" placeholder="Contoh: 8123456789 (tanpa 0 atau 62)">
                        <p class="mt-1 text-xs text-gray-500">Nomor akan diformat otomatis ke 62.</p>
                        @error('seller_wa') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Logo Penjual (Opsional)</label>
                        <div id="seller-logo-uploader" class="image-uploader mt-1" tabindex="0">
                            <p class="font-semibold text-indigo-600">Klik untuk upload</p>
                            <p class="text-xs text-gray-500">Logo (PNG, JPG, WEBP maks 1MB)</p>
                        </div>
                        <input type="file" name="seller_logo" id="seller_logo" class="hidden" accept="image/png, image/jpeg, image/webp">
                        
                        {{-- Bagian ini sudah benar --}}
                        @if($product->seller_logo)
                            <img id="seller-logo-preview" src="{{ asset('storage/' . $product->seller_logo) }}" alt="Pratinjau Logo Penjual" class="image-preview" />
                        @else
                            <img id="seller-logo-preview" alt="Pratinjau Logo Penjual" class="image-preview" />
                        @endif
                        @error('seller_logo') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Varian Produk (BARU) --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Varian Produk (Opsional)</h2>
                    <button type="button" id="add-variant-group" class="btn btn-sm btn-outline-primary">Tambah Varian</button>
                </div>
                <p class="text-sm text-gray-600 mb-4">Tambahkan varian jika produk Anda memiliki pilihan seperti warna atau ukuran. Ini akan menonaktifkan input stok utama.</p>
                <div id="variant-groups-container" class="space-y-6">
                    {{-- Grup varian dinamis akan ditambahkan di sini oleh JavaScript --}}
                </div>

                {{-- Tabel untuk harga/stok varian --}}
                <div id="variant-combinations-section" class="hidden">
                    <h3 class="text-md font-semibold text-gray-700 mt-6 mb-3">Harga & Stok Kombinasi Varian</h3>
                    <div class="variant-table-container">
                        <table id="variant-combinations-table" class="variant-table">
                            <thead>
                                <tr id="variant-table-headers">
                                    {{-- Headers dinamis akan ditambahkan di sini --}}
                                </tr>
                            </thead>
                            <tbody id="variant-combinations-body">
                                {{-- Rows dinamis akan ditambahkan di sini --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        {{-- Kolom Kanan --}}
        <div class="space-y-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Harga, Stok & Pengiriman</h2>
                <div class="space-y-4">
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700">Harga Jual</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rp</span>
                            <input type="number" name="price" id="price" value="{{ old('price', $product->price) }}" class="pl-8 block w-full border-gray-300 rounded-md shadow-sm @error('price') border-red-500 @enderror" placeholder="100000" required>
                        </div>
                        @error('price') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="original_price" class="block text-sm font-medium text-gray-700">Harga Asli (Harga Coret)</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rp</span>
                            <input type="number" name="original_price" id="original_price" value="{{ old('original_price', $product->original_price) }}" class="pl-8 block w-full border-gray-300 rounded-md shadow-sm @error('original_price') border-red-500 @enderror" placeholder="120000">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Opsional. Isi untuk menampilkan diskon.</p>
                        @error('original_price') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="stock" class="block text-sm font-medium text-gray-700">Stok</label>
                        <input type="number" name="stock" id="stock" value="{{ old('stock', $product->stock) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('stock') border-red-500 @enderror" required>
                        {{-- Pesan warning stok akan ditambahkan oleh JS jika ada varian --}}
                        @error('stock') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="weight" class="block text-sm font-medium text-gray-700">Berat</label>
                        <div class="relative mt-1">
                            <input type="number" name="weight" id="weight" value="{{ old('weight', $product->weight) }}" class="pr-12 block w-full border-gray-300 rounded-md shadow-sm @error('weight') border-red-500 @enderror" placeholder="100" required>
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500">gram</span>
                        </div>
                        @error('weight') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Dimensi Paket (Opsional)</label>
                        <div class="grid grid-cols-3 gap-4 mt-1">
                            <div>
                                <label for="length" class="text-xs text-gray-500">Panjang (cm)</label>
                                <input type="number" name="length" id="length" value="{{ old('length', $product->length) }}" class="block w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label for="width" class="text-xs text-gray-500">Lebar (cm)</label>
                                <input type="number" name="width" id="width" value="{{ old('width', $product->width) }}" class="block w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label for="height" class="text-xs text-gray-500">Tinggi (cm)</label>
                                <input type="number" name="height" id="height" value="{{ old('height', $product->height) }}" class="block w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Organisasi Produk</h2>
                <div class="space-y-4">
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700">SKU (Stock Keeping Unit)</label>
                        <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Otomatis jika kosong">
                    </div>
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Kategori</label>
                        <select name="category_id" id="category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" data-attributes-url="{{ route('admin.categories.attributes', $category->id) }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="tags" class="block text-sm font-medium text-gray-700">Tags (pisahkan koma)</label>
                        <input type="text" name="tags" id="tags" value="{{ old('tags', $product->tags) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Otomatis dari kategori jika kosong">
                    </div>
                </div>
            </div>

            {{-- Card untuk Atribut Dinamis --}}
            <div id="attributes-card" class="bg-white p-6 rounded-lg shadow-md hidden">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Spesifikasi Produk</h2>
                <div id="dynamic-attributes-container" class="space-y-4">
                    {{-- Field dinamis akan muncul di sini oleh JavaScript --}}
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Status & Label</h2>
                <div class="space-y-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status Produk</label>
                        <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>Aktif (Dijual)</option>
                            <option value="inactive" {{ old('status', $product->status) == 'inactive' ? 'selected' : '' }}>Nonaktif (Disimpan)</option>
                        </select>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_new" id="is_new" value="1" {{ old('is_new', $product->is_new) ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        <label for="is_new" class="ml-2 block text-sm text-gray-900">Tandai sebagai Produk Baru</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_bestseller" id="is_bestseller" value="1" {{ old('is_bestseller', $product->is_bestseller) ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        <label for="is_bestseller" class="ml-2 block text-sm text-gray-900">Tandai sebagai Bestseller</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tombol Aksi Sticky --}}
    <div class="sticky-action">
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Batal</a>
        <button id="submit-button" type="submit" class="btn btn-primary flex items-center gap-2">Update Produk</button>
    </div>
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    // Data produk dari Blade ke JavaScript
    const product = @json($product);

   
    
    // existing_attributes_json adalah object: { 'slug-name': 'value', 'slug-lain': ['val1', 'val2'] }
    const existingAttributes = {!! $product->existing_attributes_json ?? '{}' !!};
    
    // existing_variant_types_json adalah array: [ { name: 'Warna', options: 'Merah, Biru' }, ... ]
    const existingVariantTypes = {!! $product->existing_variant_types_json ?? '[]' !!};
    
    // existing_variant_combinations_json adalah object: { 'Warna:Merah;Ukuran:S': { price: 100, ... }, ... }
    const existingVariantCombinations = {!! $product->existing_variant_combinations_json ?? '{}' !!};
    // --- [AKHIR PERBAIKAN] ---


    // console.log('Product Data:', product);
    // console.log('Existing Attributes:', existingAttributes);
    // console.log('Existing Variant Types:', existingVariantTypes);
    // console.log('Existing Variant Combinations:', existingVariantCombinations);


    // --- Fungsi Reusable untuk Image Uploader ---
    function setupImageUploader(uploaderId, inputId, previewId) {
        const uploader = document.getElementById(uploaderId);
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);

        if (!uploader || !input || !preview) return;

        const openFileDialog = () => input.click();
        uploader.addEventListener('click', openFileDialog);
        uploader.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') openFileDialog();
        });

        // Drag and Drop
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
                };
                reader.readAsDataURL(file);
            } else {
                // Jika tidak ada file baru dipilih, sembunyikan preview jika src kosong
                if (!preview.getAttribute('src')) {
                    preview.style.display = 'none';
                }
            }
        };
        input.addEventListener('change', handleFileChange);
    }

    // Inisialisasi kedua uploader
    setupImageUploader('image-uploader', 'product_image', 'image-preview');
    setupImageUploader('seller-logo-uploader', 'seller_logo', 'seller-logo-preview');

    // --- Form Submission Loading Spinner ---
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
            submitButton.innerHTML = `
                <span class="spinner" role="status" aria-hidden="true"></span>
                Menyimpan...
            `;
        });
    }

    // --- WhatsApp Input Formatter ---
    const waInput = document.getElementById('seller_wa');
    if (waInput) {
        waInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
    }

    // --- Script Atribut Dinamis ---
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
            attributesContainer.innerHTML = '<p class="text-gray-500">Memuat spesifikasi...</p>';
            attributesCard.classList.remove('hidden');
            const response = await fetch(url);
            if (!response.ok) throw new Error(`Gagal memuat atribut (status: ${response.status}).`);

            const attributes = await response.json();
            attributesContainer.innerHTML = '';

            if (attributes && attributes.length > 0) {
                attributes.forEach(attr => {
                    if (typeof attr === 'object' && attr !== null && attr.slug) {
                        const field = createAttributeField(attr);
                        attributesContainer.appendChild(field);
                        // Mengisi nilai yang sudah ada
                        // [PERBAIKAN] Menggunakan existingAttributes[attr.slug]
                        if (existingAttributes && existingAttributes[attr.slug] !== undefined) {
                            fillAttributeValue(field, attr, existingAttributes[attr.slug]);
                        }
                    } else {
                        console.warn('Data atribut tidak valid:', attr);
                    }
                });
            } else {
                attributesContainer.innerHTML = '<p class="text-gray-500">Tidak ada spesifikasi tambahan untuk kategori ini.</p>';
            }
        } catch (error) {
            console.error('Error fetching attributes:', error);
            attributesContainer.innerHTML = `<p class="text-red-500">Gagal memuat spesifikasi. ${error.message}</p>`;
        }
    }

    function createAttributeField(attribute) {
        const wrapper = document.createElement('div');
        let fieldHtml = '';
        const isRequired = attribute.is_required ? 'required' : '';
        const requiredAsterisk = attribute.is_required ? '<span class="text-red-500">*</span>' : '';
        const attributeName = attribute.name || 'Atribut Tanpa Nama';
        const label = `<label for="attr_${attribute.slug}" class="block text-sm font-medium text-gray-700">${attributeName} ${requiredAsterisk}</label>`;
        const inputName = `attributes[${attribute.slug}]`;
        const optionsString = typeof attribute.options === 'string' ? attribute.options : '';

        switch (attribute.type) {
            case 'number':
            case 'text':
                fieldHtml = `${label}<input type="${attribute.type}" name="${inputName}" id="attr_${attribute.slug}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired}>`;
                break;
            case 'textarea':
                fieldHtml = `${label}<textarea name="${inputName}" id="attr_${attribute.slug}" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired}></textarea>`;
                break;
            case 'select':
                const options = optionsString.split(',')
                                        .map(opt => opt.trim())
                                        .filter(opt => opt)
                                        .map(opt => `<option value="${opt}">${opt}</option>`)
                                        .join('');
                fieldHtml = `${label}<select name="${inputName}" id="attr_${attribute.slug}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired}><option value="">-- Pilih ${attributeName} --</option>${options}</select>`;
                break;
            case 'checkbox':
                const checkboxes = optionsString.split(',')
                                        .map(opt => opt.trim())
                                        .filter(opt => opt)
                                        .map((opt, index) => `
                        <div class="flex items-center">
                            <input type="checkbox" name="${inputName}[]" id="attr_${attribute.slug}_${index}" value="${opt}" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                            <label for="attr_${attribute.slug}_${index}" class="ml-2 block text-sm text-gray-900">${opt}</label>
                        </div>`).join('');
                fieldHtml = `<label class="block text-sm font-medium text-gray-700">${attributeName} ${requiredAsterisk}</label><div class="mt-2 space-y-2">${checkboxes}</div>`;
                break;
            default:
                console.warn(`Tipe atribut tidak dikenali: ${attribute.type} untuk ${attributeName}`);
                fieldHtml = `${label}<input type="text" name="${inputName}" id="attr_${attribute.slug}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired} title="Tipe asli: ${attribute.type}">`;
        }
        wrapper.innerHTML = fieldHtml;
        return wrapper;
    }

    function fillAttributeValue(fieldElement, attribute, value) {
        if (value === null || value === undefined) return;

        const input = fieldElement.querySelector(`[name^="attributes[${attribute.slug}]"]`);

        if (input) {
            if (attribute.type === 'checkbox') {
                // Value for checkbox can be an array or a string (JSON string)
                let valuesToCheck = [];
                if (Array.isArray(value)) {
                    valuesToCheck = value;
                } else if (typeof value === 'string') {
                    try {
                        valuesToCheck = JSON.parse(value);
                        if (!Array.isArray(valuesToCheck)) valuesToCheck = [value]; // Fallback if not an array after parse
                    } catch (e) {
                        valuesToCheck = [value]; // If not JSON, treat as single string
                    }
                }

                fieldElement.querySelectorAll(`input[type="checkbox"][name^="attributes[${attribute.slug}]"]`).forEach(checkbox => {
                    if (valuesToCheck.includes(checkbox.value)) {
                        checkbox.checked = true;
                    }
                });
            } else if (input.type === 'textarea') {
                input.value = value;
            } else {
                input.value = value;
            }
        }
    }


    if (categorySelect) {
        categorySelect.addEventListener('change', fetchAndRenderAttributes);
        // Panggil saat DOMContentLoaded untuk memuat atribut saat halaman dimuat
        fetchAndRenderAttributes();
    }


    // --- Script Varian Dinamis (BARU) ---
    const variantContainer = document.getElementById('variant-groups-container');
    const addVariantBtn = document.getElementById('add-variant-group');
    const mainStockInput = document.getElementById('stock');
    const mainPriceInput = document.getElementById('price');
    const mainOriginalPriceInput = document.getElementById('original_price');
    const variantCombinationsSection = document.getElementById('variant-combinations-section');
    const variantCombinationsTable = document.getElementById('variant-combinations-table');
    const variantTableHeaders = document.getElementById('variant-table-headers');
    const variantTableBody = document.getElementById('variant-combinations-body');

    let variantIndex = 0; // Untuk ID unik saat menambahkan grup varian baru

    // Array untuk menyimpan data tipe varian saat ini
    let currentVariantTypes = []; // [{ name: 'Warna', options: ['Merah', 'Biru'] }, { name: 'Ukuran', options: ['S', 'M'] }]

    // Fungsi untuk memperbarui `currentVariantTypes` dari input form
    function updateCurrentVariantTypes() {
        currentVariantTypes = [];
        variantContainer.querySelectorAll('.border.rounded-md').forEach(groupWrapper => {
            const nameInput = groupWrapper.querySelector('input[name$="[name]"]');
            const optionsInput = groupWrapper.querySelector('input[name$="[options]"]');

            if (nameInput && nameInput.value.trim() && optionsInput && optionsInput.value.trim()) {
                currentVariantTypes.push({
                    name: nameInput.value.trim(),
                    options: optionsInput.value.split(',').map(o => o.trim()).filter(o => o !== '')
                });
            }
        });
        generateVariantCombinations();
        toggleMainStock();
    }

    function createVariantGroup(index, variantTypeData = { name: '', options: '' }) {
        const groupWrapper = document.createElement('div');
        groupWrapper.classList.add('border', 'rounded-md', 'p-4', 'space-y-3', 'bg-gray-50');
        groupWrapper.innerHTML = `
            <div class="flex justify-between items-center">
                <h3 class="font-semibold text-gray-700">Tipe Varian #${index + 1}</h3>
                <button type="button" class="text-red-500 hover:text-red-700 remove-variant-group" title="Hapus Tipe Varian">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </div>
            <div>
                <label for="variant_${index}_name" class="block text-sm font-medium text-gray-700">Nama Tipe Varian</label>
                <input type="text" name="variant_types[${index}][name]" id="variant_${index}_name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: Warna, Ukuran" value="${variantTypeData.name}" required>
            </div>
            <div>
                <label for="variant_${index}_options" class="block text-sm font-medium text-gray-700">Pilihan Varian (pisahkan koma)</label>
                <input type="text" name="variant_types[${index}][options]" id="variant_${index}_options" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: Merah, Biru, Hijau" value="${variantTypeData.options}" required>
            </div>
        `;

        // Tambahkan event listener untuk menghapus grup varian
        groupWrapper.querySelector('.remove-variant-group').addEventListener('click', (e) => {
            e.preventDefault();
            groupWrapper.remove();
            updateCurrentVariantTypes(); // Perbarui dan generate ulang kombinasi
        });

        // Tambahkan event listener untuk input agar selalu mengupdate kombinasi
        groupWrapper.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', updateCurrentVariantTypes);
        });

        return groupWrapper;
    }

    if (addVariantBtn && variantContainer && mainStockInput) {
        addVariantBtn.addEventListener('click', (e) => {
            e.preventDefault();
            variantContainer.appendChild(createVariantGroup(variantIndex));
            variantIndex++;
            updateCurrentVariantTypes(); // Panggil ini setelah menambahkan
        });
    }

    // Fungsi untuk menghasilkan kombinasi varian
    function generateVariantCombinations() {
        variantTableHeaders.innerHTML = '';
        variantTableBody.innerHTML = '';

        if (currentVariantTypes.length === 0) {
            variantCombinationsSection.classList.add('hidden');
            return;
        }

        variantCombinationsSection.classList.remove('hidden');

        // Tambahkan header untuk nama varian
        currentVariantTypes.forEach(type => {
            const th = document.createElement('th');
            th.textContent = type.name;
            variantTableHeaders.appendChild(th);
        });

        // Tambahkan header untuk harga, stok, SKU, dan aksi
        const priceTh = document.createElement('th'); priceTh.textContent = 'Harga'; variantTableHeaders.appendChild(priceTh);
        const stockTh = document.createElement('th'); stockTh.textContent = 'Stok'; variantTableHeaders.appendChild(stockTh);
        const skuTh = document.createElement('th'); skuTh.textContent = 'SKU Varian (Opsional)'; variantTableHeaders.appendChild(skuTh);
        const actionTh = document.createElement('th'); actionTh.textContent = 'Aksi'; variantTableHeaders.appendChild(actionTh);


        const combinations = generateAllCombinations(currentVariantTypes);

        combinations.forEach((combination, combIndex) => {
            const tr = document.createElement('tr');
            let combinationString = []; // Untuk kunci lookup di existingVariantCombinations

            combination.forEach((variantOption, optionIndex) => {
                const td = document.createElement('td');
                td.textContent = variantOption;
                tr.appendChild(td);

                // Buat string kombinasi untuk lookup
                combinationString.push(`${currentVariantTypes[optionIndex].name}:${variantOption}`);
            });

            // [PERBAIKAN] Menggunakan existingVariantCombinations
            // const comboKey = combinationString.join(';'); // "Warna:Merah;Ukuran:S"
            const comboKey = generateCombinationKey(combination, currentVariantTypes); // Gunakan helper
            const existingComboData = existingVariantCombinations[comboKey] || { price: mainPriceInput.value, stock: 0, sku_code: '' }; // Default values

            // Input Harga
            const priceTd = document.createElement('td');
            priceTd.innerHTML = `<div class="relative"><span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rp</span><input type="number" name="product_variants[${combIndex}][price]" class="pl-8" value="${existingComboData.price}" required></div>`;
            tr.appendChild(priceTd);

            // Input Stok
            const stockTd = document.createElement('td');
            stockTd.innerHTML = `<input type="number" name="product_variants[${combIndex}][stock]" value="${existingComboData.stock}" required>`;
            tr.appendChild(stockTd);

            // Input SKU Varian
            const skuTd = document.createElement('td');
            skuTd.innerHTML = `<input type="text" name="product_variants[${combIndex}][sku_code]" value="${existingComboData.sku_code || ''}" placeholder="SKU Varian">`; // Tambah || ''
            tr.appendChild(skuTd);

            // Hidden inputs untuk menyimpan tipe dan nilai varian
            combination.forEach((variantOption, optionIndex) => {
                const hiddenInputType = document.createElement('input');
                hiddenInputType.type = 'hidden';
                hiddenInputType.name = `product_variants[${combIndex}][variant_options][${optionIndex}][type_name]`;
                hiddenInputType.value = currentVariantTypes[optionIndex].name;
                tr.appendChild(hiddenInputType);

                const hiddenInputValue = document.createElement('input');
                hiddenInputValue.type = 'hidden';
                hiddenInputValue.name = `product_variants[${combIndex}][variant_options][${optionIndex}][value]`;
                hiddenInputValue.value = variantOption;
                tr.appendChild(hiddenInputValue);
            });

            // Aksi (Opsional: Hapus baris kombinasi, dll)
            const actionTd = document.createElement('td');
            // Untuk saat ini, tidak ada aksi langsung untuk menghapus kombinasi spesifik
            // karena mereka dihasilkan secara otomatis dari tipe varian.
            // Anda bisa menambahkan tombol untuk "reset" stok/harga, misalnya.
            actionTd.innerHTML = '<span class="text-gray-400">N/A</span>';
            tr.appendChild(actionTd);


            variantTableBody.appendChild(tr);
        });

        // Sinkronkan stok/harga utama dengan nilai default dari varian pertama jika ada
        if (combinations.length > 0) {
            const firstCombinationData = existingVariantCombinations[generateCombinationKey(combinations[0], currentVariantTypes)] || { price: product.price, stock: 0 };
            mainPriceInput.value = firstCombinationData.price !== undefined ? firstCombinationData.price : product.price;
            mainOriginalPriceInput.value = product.original_price; // Tetap pakai original_price produk utama
        } else {
            // Jika tidak ada varian, kembalikan ke harga produk utama
            mainPriceInput.value = product.price;
            mainOriginalPriceInput.value = product.original_price;
        }
    }


    // Fungsi rekursif untuk menghasilkan semua kombinasi
    function generateAllCombinations(variantTypes) {
        if (variantTypes.length === 0) {
            return [];
        }

        function combine(arr) {
            if (arr.length === 0) return [[]];
            const first = arr[0];
            const rest = arr.slice(1);
            const subCombinations = combine(rest);
            const result = [];
            for (const item of first) {
                for (const subCombo of subCombinations) {
                    result.push([item, ...subCombo]);
                }
            }
            return result;
        }

        const optionsPerType = variantTypes.map(type => type.options);
        return combine(optionsPerType);
    }

    // Fungsi helper untuk generate combination key yang sama dengan di backend
    function generateCombinationKey(combinationValues, variantTypes) {
        // [PERBAIKAN] Pastikan variantTypes tidak kosong sebelum di-map
        if (!variantTypes || variantTypes.length === 0) return '';
        return combinationValues.map((value, index) => `${variantTypes[index].name}:${value}`)
            .sort() // Sortir untuk konsistensi
            .join(';');
    }


    function toggleMainStock() {
        if (!mainStockInput || !mainPriceInput || !mainOriginalPriceInput) return;

        const warningId = 'stock-warning';
        const existingWarning = document.getElementById(warningId);
        if (existingWarning) existingWarning.remove();

        // Cek jika ada grup varian yang terdefinisi
        const hasVariantGroups = variantContainer.children.length > 0;

        if (hasVariantGroups) {
            mainStockInput.disabled = true;
            mainStockInput.value = ''; // Kosongkan stok utama
            mainPriceInput.disabled = true; // Nonaktifkan harga utama
            mainOriginalPriceInput.disabled = true; // Nonaktifkan harga coret utama

            if (mainStockInput.parentElement) {
                mainStockInput.parentElement.insertAdjacentHTML('afterend', `
                    <p id="${warningId}" class="mt-1 text-xs text-indigo-600">
                        Input stok & harga utama dinonaktifkan. Anda mengatur stok & harga per kombinasi varian.
                    </p>
                `);
            }
        } else {
            mainStockInput.disabled = false;
            // Kembalikan stok dan harga utama ke nilai produk (jika tidak ada varian)
            mainStockInput.value = product.stock;
            mainPriceInput.disabled = false;
            mainPriceInput.value = product.price;
            mainOriginalPriceInput.disabled = false;
            mainOriginalPriceInput.value = product.original_price;
        }
    }

    // Inisialisasi: Muat varian yang sudah ada
    // [PERBAIKAN] Menggunakan existingVariantTypes dari JSON
    existingVariantTypes.forEach((variantType, index) => {
        const groupWrapper = createVariantGroup(variantIndex, {
            name: variantType.name,
            options: variantType.options // 'options' di sini adalah string (Contoh: "Merah, Biru")
        });
        variantContainer.appendChild(groupWrapper);
        variantIndex++; // Tingkatkan index untuk varian baru
    });

    // Panggil updateCurrentVariantTypes setelah semua varian existing dimuat
    updateCurrentVariantTypes();

    // Pastikan toggleMainStock dipanggil terakhir setelah semua inisialisasi varian
    toggleMainStock();

});
</script>
@endpush
