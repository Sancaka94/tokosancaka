@extends('layouts.admin')

@section('title', 'Edit Produk')
@section('page-title', 'Edit Produk: ' . $product->name)

@push('styles')
<style>
    /* =============================
        STYLE TAMBAH PRODUK - FIXED
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
        display: block; /* Tampilkan gambar yang sudah ada */
    }

    .spinner {
        display: inline-block;
        width: 1rem;
        height: 1rem;
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

    .dropzone--over {
        outline: 2px dashed #6366f1;
        background-color: #eef2ff;
    }

    /* =============================
        FIX LAYOUT SCROLLING
        ============================= */
    html, body {
        height: 100%;
        overflow: hidden;
    }

    main.content, .main-content, .content-wrapper, .page-content {
        height: calc(100vh - 60px); /* asumsi ada header tinggi 60px */
        overflow-y: auto;
        padding-bottom: 120px; /* agar tidak ketimpa tombol sticky */
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

<form id="product-form" action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" novalidate>
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
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Ganti Gambar Produk (Opsional)</h2>
                <div id="image-uploader" class="image-uploader" tabindex="0">
                    <p class="font-semibold text-indigo-600">Klik untuk upload</p>
                    <p class="text-xs text-gray-500">atau seret file ke sini (PNG, JPG, GIF hingga 5MB)</p>
                </div>
                <input type="file" name="product_image" id="product_image" class="hidden" accept="image/*">
                <img id="image-preview" src="{{ $product->image_url ? asset('storage/' . $product->image_url) : '' }}" alt="Pratinjau Gambar" class="image-preview {{ $product->image_url ? '' : 'hidden' }}" />
                @error('product_image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Informasi Penjual --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Penjual</h2>
                <div class="space-y-4">
                     <div>
                        <label for="store_name" class="block text-sm font-medium text-gray-700">Nama Toko</label>
                        <input type="text" name="store_name" id="store_name" value="{{ old('store_name', $product->store_name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('store_name') border-red-500 @enderror" required>
                        @error('store_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="seller_city" class="block text-sm font-medium text-gray-700">Kota Penjual</label>
                        <input type="text" name="seller_city" id="seller_city" value="{{ old('seller_city', $product->seller_city) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('seller_city') border-red-500 @enderror" required>
                        @error('seller_city') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="seller_wa" class="block text-sm font-medium text-gray-700">WhatsApp Toko</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 select-none">+62</span>
                            <input type="tel" name="seller_wa" id="seller_wa" placeholder="81234567890"
                                   class="block w-full rounded-md border border-gray-300 pl-12 pr-3 py-2 focus:ring-2 focus:ring-indigo-500"
                                   pattern="^(\+?62|0)?8[1-9][0-9]{6,11}$"
                                   value="{{ old('seller_wa', $product->seller_wa) }}">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Format: 8xxxxxxxxxx (otomatis +62)</p>
                         @error('seller_wa') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Logo Toko</label>
                        <input id="seller_logo" name="seller_logo" type="file" accept="image/*" class="sr-only">
                        <label for="seller_logo" id="seller_logo_dropzone"
                               class="mt-1 flex flex-col items-center justify-center gap-2 w-full rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center cursor-pointer transition hover:border-indigo-400 hover:bg-indigo-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 15a4 4 0 004 4h10a4 4 0 004-4m-4-6l-4-4m0 0L9 9m4-4v12"/></svg>
                            <p class="text-sm text-gray-700"><span class="font-medium">Tarik & lepas</span> atau <span class="font-medium text-indigo-600 underline">klik</span></p>
                            <p class="text-xs text-gray-500">PNG, JPG (maks. 2MB)</p>
                            <p id="seller_logo_error" class="text-xs text-red-600 font-medium hidden"></p>
                            <div id="seller_logo_preview" class="mt-3 {{ $product->seller_logo ? '' : 'hidden' }}">
                                <img src="{{ $product->seller_logo ? asset('storage/' . $product->seller_logo) : '' }}" alt="Preview logo" class="mx-auto h-20 w-20 rounded-full object-cover ring-1 ring-gray-200">
                                <p class="mt-2 text-xs text-gray-500" id="seller_logo_filename">{{ basename($product->seller_logo) }}</p>
                            </div>
                        </label>
                        <small class="text-gray-500">Rekomendasi: 250x250 piksel.</small>
                         @error('seller_logo') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan --}}
        <div class="space-y-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Harga & Stok</h2>
                <div class="space-y-4">
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700">Harga Jual (Rp)</label>
                        <input type="number" name="price" id="price" value="{{ old('price', $product->price) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('price') border-red-500 @enderror" required>
                        @error('price') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="original_price" class="block text-sm font-medium text-gray-700">Harga Coret (Opsional)</label>
                        <input type="number" name="original_price" id="original_price" value="{{ old('original_price', $product->original_price) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('original_price') border-red-500 @enderror">
                        @error('original_price') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="stock" class="block text-sm font-medium text-gray-700">Jumlah Stok</label>
                        <input type="number" name="stock" id="stock" value="{{ old('stock', $product->stock) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('stock') border-red-500 @enderror" required>
                        @error('stock') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="weight" class="block text-sm font-medium text-gray-700">Berat (gram)</label>
                        <input type="number" name="weight" id="weight" value="{{ old('weight', $product->weight) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('weight') border-red-500 @enderror" required>
                        @error('weight') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Organisasi Produk</h2>
                <div class="space-y-4">
                     <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700">SKU</label>
                        <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('sku') border-red-500 @enderror" required>
                        @error('sku') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Kategori</label>
                        <select name="category_id" id="category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('category_id') border-red-500 @enderror" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }} data-attributes-url="{{ route('admin.categories.attributes', $category->id) }}">
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                         @error('category_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="tags" class="block text-sm font-medium text-gray-700">Tags (pisahkan koma)</label>
                        <input type="text" name="tags" id="tags" value="{{ old('tags', is_array($product->tags) ? implode(', ', $product->tags) : $product->tags) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('tags') border-red-500 @enderror">
                        @error('tags') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div id="attributes-card" class="bg-white p-6 rounded-lg shadow-md hidden">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Spesifikasi Produk</h2>
                <div id="dynamic-attributes-container" class="space-y-4">
                    {{-- Atribut dinamis akan dimuat di sini --}}
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Status & Label</h2>
                <div class="space-y-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status Produk</label>
                        <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>Aktif (Dijual)</option>
                            <option value="inactive" {{ old('status', $product->status) == 'inactive' ? 'selected' : '' }}>Tidak Aktif (Disimpan)</option>
                        </select>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_new" id="is_new" value="1" {{ old('is_new', $product->is_new) ? 'checked' : '' }}
                               class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500 rounded">
                        <label for="is_new" class="ml-2 text-sm text-gray-900">Tandai sebagai Produk Baru</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_bestseller" id="is_bestseller" value="1" {{ old('is_bestseller', $product->is_bestseller) ? 'checked' : '' }}
                               class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500 rounded">
                        <label for="is_bestseller" class="ml-2 text-sm text-gray-900">Tandai sebagai Bestseller</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="sticky-action">
    <a href="{{ route('admin.products.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-300">Batal</a>
    <button id="submit-button" type="submit" form="product-form"
            class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 flex items-center disabled:opacity-50">
        <span id="button-text">Perbarui Produk</span>
        <span id="button-spinner" class="spinner ml-2 hidden" role="status" aria-hidden="true"></span>
    </button>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // === 1. Preview Gambar Utama ===
    const uploader = document.getElementById('image-uploader');
    const fileInput = document.getElementById('product_image');
    const preview = document.getElementById('image-preview');

    if (uploader && fileInput && preview) {
        uploader.addEventListener('click', () => fileInput.click());
        uploader.addEventListener('dragover', (e) => { e.preventDefault(); uploader.classList.add('dragging'); });
        uploader.addEventListener('dragleave', () => uploader.classList.remove('dragging'));
        uploader.addEventListener('drop', (e) => {
            e.preventDefault();
            uploader.classList.remove('dragging');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                previewFile();
            }
        });

        fileInput.addEventListener('change', previewFile);
        function previewFile() {
            const file = fileInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        }
    }

    // === 2. Loading Button ===
    const form = document.getElementById('product-form');
    const btn = document.getElementById('submit-button');
    const btnText = document.getElementById('button-text');
    const spinner = document.getElementById('button-spinner');
    if (form && btn) {
        form.addEventListener('submit', () => {
            btn.disabled = true;
            btnText.textContent = 'Menyimpan...';
            spinner.classList.remove('hidden');
        });
    }

    // === 3. Dropzone Logo ===
    const dz = document.getElementById('seller_logo_dropzone');
    const input = document.getElementById('seller_logo');
    // ... (sisa logika dropzone bisa ditambahkan di sini jika diperlukan)

    // === 4. Atribut Dinamis ===
    const categorySelect = document.getElementById('category_id');
    const attributesCard = document.getElementById('attributes-card');
    const attributesContainer = document.getElementById('dynamic-attributes-container');
    const existingAttributes = @json($product->attributes_data ?? []);

    async function fetchAndRenderAttributes() {
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        const url = selectedOption.dataset.attributesUrl;

        if (!url) {
            attributesCard.classList.add('hidden');
            attributesContainer.innerHTML = '';
            return;
        }

        try {
            attributesContainer.innerHTML = '<p class="text-gray-500">Memuat spesifikasi...</p>';
            const response = await fetch(url);
            if (!response.ok) throw new Error('Gagal memuat atribut.');
            
            const attributes = await response.json();

            attributesContainer.innerHTML = ''; 

            if (attributes.length > 0) {
                attributesCard.classList.remove('hidden');
                attributes.forEach(attr => {
                    const existingValue = existingAttributes[attr.slug] || null;
                    const field = createAttributeField(attr, existingValue);
                    attributesContainer.appendChild(field);
                });
            } else {
                attributesCard.classList.add('hidden');
            }
        } catch (error) {
            console.error('Error:', error);
            attributesCard.classList.remove('hidden');
            attributesContainer.innerHTML = '<p class="text-red-500">Gagal memuat spesifikasi.</p>';
        }
    }

    function createAttributeField(attribute, value) {
        const wrapper = document.createElement('div');
        let fieldHtml = '';
        const isRequired = attribute.is_required ? 'required' : '';
        const requiredAsterisk = attribute.is_required ? '<span class="text-red-500">*</span>' : '';
        const label = `<label for="attr_${attribute.slug}" class="block text-sm font-medium text-gray-700">${attribute.name} ${requiredAsterisk}</label>`;
        const inputName = `attributes[${attribute.slug}]`;
        const valueAttribute = (value !== null && typeof value !== 'object') ? `value="${String(value).replace(/"/g, '&quot;')}"` : '';

        switch (attribute.type) {
            case 'number':
            case 'text':
                fieldHtml = `${label}<input type="${attribute.type}" name="${inputName}" id="attr_${attribute.slug}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired} ${valueAttribute}>`;
                break;
            case 'textarea':
                fieldHtml = `${label}<textarea name="${inputName}" id="attr_${attribute.slug}" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired}>${value || ''}</textarea>`;
                break;
            case 'select':
                const options = (attribute.options || '').split(',').map(opt => {
                    const trimmedOpt = opt.trim();
                    const selected = trimmedOpt == value ? 'selected' : '';
                    return `<option value="${trimmedOpt}" ${selected}>${trimmedOpt}</option>`;
                }).join('');
                fieldHtml = `${label}<select name="${inputName}" id="attr_${attribute.slug}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired}><option value="">-- Pilih ${attribute.name} --</option>${options}</select>`;
                break;
            case 'checkbox':
                const valueArray = Array.isArray(value) ? value : (value ? [value] : []);
                const checkboxes = (attribute.options || '').split(',').map((opt, index) => {
                    const trimmedOpt = opt.trim();
                    const checked = valueArray.includes(trimmedOpt) ? 'checked' : '';
                    return `<div class="flex items-center"><input type="checkbox" name="${inputName}[]" id="attr_${attribute.slug}_${index}" value="${trimmedOpt}" class="h-4 w-4 text-indigo-600 border-gray-300 rounded" ${checked}><label for="attr_${attribute.slug}_${index}" class="ml-2 block text-sm text-gray-900">${trimmedOpt}</label></div>`;
                }).join('');
                fieldHtml = `<label class="block text-sm font-medium text-gray-700">${attribute.name} ${requiredAsterisk}</label><div class="mt-2 space-y-2">${checkboxes}</div>`;
                break;
        }
        wrapper.innerHTML = fieldHtml;
        return wrapper;
    }

    categorySelect.addEventListener('change', fetchAndRenderAttributes);

    if (categorySelect.value) {
        fetchAndRenderAttributes();
    }
});
</script>
@endpush

