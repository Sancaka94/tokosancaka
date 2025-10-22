@extends('layouts.admin')

@section('title', 'Tambah Produk Baru')
@section('page-title', 'Tambah Produk Baru')

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
        display: none;
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

<form id="product-form" action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Kolom Kiri --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Informasi Produk --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Produk</h2>
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nama Produk</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('name') border-red-500 @enderror" required>
                        @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                        <textarea name="description" id="description" rows="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                        @error('description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Gambar Produk --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Gambar Produk</h2>
                <div id="image-uploader" class="image-uploader" tabindex="0">
                    <p class="font-semibold text-indigo-600">Klik untuk upload</p>
                    <p class="text-xs text-gray-500">atau seret file ke sini (PNG, JPG, GIF hingga 5MB)</p>
                </div>
                <input type="file" name="product_image" id="product_image" class="hidden" accept="image/*">
                <img id="image-preview" alt="Pratinjau Gambar" class="image-preview" />
                @error('product_image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Informasi Penjual --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Penjual</h2>
                <div class="space-y-4">
                    {{-- Form Fields for Seller Info --}}
                </div>
            </div>
        </div>

        {{-- Kolom Kanan --}}
        <div class="space-y-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Harga & Stok</h2>
                <div class="space-y-4">
                     {{-- Form Fields for Price & Stock --}}
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Organisasi Produk</h2>
                <div class="space-y-4">
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700">SKU</label>
                        <input type="text" name="sku" id="sku" value="{{ old('sku') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Kategori</label>
                        <select name="category_id" id="category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" data-attributes-url="{{ route('admin.categories.attributes', $category->id) }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="tags" class="block text-sm font-medium text-gray-700">Tags (pisahkan koma)</label>
                        <input type="text" name="tags" id="tags" value="{{ old('tags') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>
            </div>

            {{-- Card untuk Atribut Dinamis --}}
            <div id="attributes-card" class="bg-white p-6 rounded-lg shadow-md hidden">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Spesifikasi Produk</h2>
                <div id="dynamic-attributes-container" class="space-y-4">
                    {{-- Field dinamis akan muncul di sini --}}
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Status & Label</h2>
                <div class="space-y-4">
                    {{-- Form Fields for Status & Labels --}}
                </div>
            </div>
        </div>
    </div>
</form>

<div class="sticky-action">
    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Batal</a>
    <button id="submit-button" type="submit" form="product-form" class="btn btn-primary">Simpan Produk</button>
</div>
@endsection

@push('scripts')
{{-- Script untuk image uploader & loading button bisa ditambahkan di sini --}}

<script>
document.addEventListener('DOMContentLoaded', () => {
    const categorySelect = document.getElementById('category_id');
    const attributesCard = document.getElementById('attributes-card');
    const attributesContainer = document.getElementById('dynamic-attributes-container');

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
                    const field = createAttributeField(attr);
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

    function createAttributeField(attribute) {
        const wrapper = document.createElement('div');
        let fieldHtml = '';
        const isRequired = attribute.is_required ? 'required' : '';
        const requiredAsterisk = attribute.is_required ? '<span class="text-red-500">*</span>' : '';
        const label = `<label for="attr_${attribute.slug}" class="block text-sm font-medium text-gray-700">${attribute.name} ${requiredAsterisk}</label>`;
        const inputName = `attributes[${attribute.slug}]`;

        switch (attribute.type) {
            case 'number':
            case 'text':
                fieldHtml = `${label}<input type="${attribute.type}" name="${inputName}" id="attr_${attribute.slug}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired}>`;
                break;
            case 'textarea':
                fieldHtml = `${label}<textarea name="${inputName}" id="attr_${attribute.slug}" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired}></textarea>`;
                break;
            case 'select':
                const options = (attribute.options || '').split(',').map(opt => `<option value="${opt.trim()}">${opt.trim()}</option>`).join('');
                fieldHtml = `${label}<select name="${inputName}" id="attr_${attribute.slug}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired}><option value="">-- Pilih ${attribute.name} --</option>${options}</select>`;
                break;
            case 'checkbox':
                const checkboxes = (attribute.options || '').split(',').map((opt, index) => `
                    <div class="flex items-center">
                        <input type="checkbox" name="${inputName}[]" id="attr_${attribute.slug}_${index}" value="${opt.trim()}" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        <label for="attr_${attribute.slug}_${index}" class="ml-2 block text-sm text-gray-900">${opt.trim()}</label>
                    </div>`).join('');
                fieldHtml = `<label class="block text-sm font-medium text-gray-700">${attribute.name} ${requiredAsterisk}</label><div class="mt-2 space-y-2">${checkboxes}</div>`;
                break;
        }
        wrapper.innerHTML = fieldHtml;
        return wrapper;
    }

    categorySelect.addEventListener('change', fetchAndRenderAttributes);
    if(categorySelect.value) {
        fetchAndRenderAttributes();
    }
});
</script>
@endpush

