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
                    <x-input label="Nama Produk" name="name" required />
                    <x-textarea label="Deskripsi" name="description" rows="6" />
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
                    <x-input label="Nama Toko" name="store_name" />
                    <x-input label="Kota Penjual" name="seller_city" />
                    <div>
                        <label for="seller_wa" class="block text-sm font-medium text-gray-700">WhatsApp Toko</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 select-none">+62</span>
                            <input type="tel" name="seller_wa" id="seller_wa" placeholder="81234567890"
                                   class="block w-full rounded-md border border-gray-300 pl-12 pr-3 py-2 focus:ring-2 focus:ring-indigo-500"
                                   pattern="^(\+?62|0)?8[1-9][0-9]{6,11}$"
                                   value="{{ old('seller_wa') }}">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Format: 8xxxxxxxxxx (otomatis +62)</p>
                    </div>

                    {{-- Logo Toko --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Logo Toko</label>
                        <input id="seller_logo" name="seller_logo" type="file" accept="image/*" class="sr-only">
                        <label for="seller_logo" id="seller_logo_dropzone"
                               class="mt-1 flex flex-col items-center justify-center gap-2 w-full rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center cursor-pointer transition hover:border-indigo-400 hover:bg-indigo-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 15a4 4 0 004 4h10a4 4 0 004-4m-4-6l-4-4m0 0L9 9m4-4v12"/>
                            </svg>
                            <p class="text-sm text-gray-700">
                                <span class="font-medium">Tarik & lepas</span> atau
                                <span class="font-medium text-indigo-600 underline">klik</span>
                            </p>
                            <p class="text-xs text-gray-500">PNG, JPG (maks. 2MB)</p>
                            <p id="seller_logo_error" class="text-xs text-red-600 font-medium hidden"></p>
                            <div id="seller_logo_preview" class="mt-3 hidden">
                                <img alt="Preview logo" class="mx-auto h-20 w-20 rounded-full object-cover ring-1 ring-gray-200">
                                <p class="mt-2 text-xs text-gray-500" id="seller_logo_filename"></p>
                            </div>
                        </label>
                        <small class="text-gray-500">Rekomendasi: 250x250 piksel.</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan --}}
        <div class="space-y-6">
            <x-card title="Harga & Stok">
                <x-input type="number" label="Harga Jual (Rp)" name="price" required />
                <x-input type="number" label="Harga Coret (Opsional)" name="original_price" />
                <x-input type="number" label="Jumlah Stok" name="stock" value="0" required />
                <x-input type="number" label="Berat (gram)" name="weight" value="0" required />
            </x-card>

            <x-card title="Organisasi Produk">
                <x-input label="SKU" name="sku" required />
                <div>
                    <label class="block text-sm font-medium text-gray-700">Kategori</label>
                    <select name="category_id" id="category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <x-input label="Tags (pisahkan koma)" name="tags" />
            </x-card>

            <x-card title="Status & Label">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status Produk</label>
                    <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Aktif (Dijual)</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif (Disimpan)</option>
                    </select>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_new" id="is_new" value="1" {{ old('is_new') ? 'checked' : '' }}
                           class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500 rounded">
                    <label for="is_new" class="ml-2 text-sm text-gray-900">Tandai sebagai Produk Baru</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_bestseller" id="is_bestseller" value="1" {{ old('is_bestseller') ? 'checked' : '' }}
                           class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500 rounded">
                    <label for="is_bestseller" class="ml-2 text-sm text-gray-900">Tandai sebagai Bestseller</label>
                </div>
            </x-card>
        </div>
    </div>
</form>

{{-- Tombol aksi sticky --}}
<div class="sticky-action">
    <a href="{{ route('admin.products.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-300">Batal</a>
    <button id="submit-button" type="submit" form="product-form"
            class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 flex items-center disabled:opacity-50">
        <span id="button-text">Simpan Produk</span>
        <span id="button-spinner" class="spinner ml-2 hidden" role="status" aria-hidden="true"></span>
    </button>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // === Preview Gambar Utama ===
    const uploader = document.getElementById('image-uploader');
    const fileInput = document.getElementById('product_image');
    const preview = document.getElementById('image-preview');

    if (uploader && fileInput && preview) {
        uploader.addEventListener('click', () => fileInput.click());
        ['dragenter', 'dragover'].forEach(e => uploader.addEventListener(e, ev => {
            ev.preventDefault(); uploader.classList.add('dragging');
        }));
        ['dragleave', 'drop'].forEach(e => uploader.addEventListener(e, ev => {
            ev.preventDefault(); uploader.classList.remove('dragging');
        }));
        uploader.addEventListener('drop', e => {
            e.preventDefault();
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
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }
    }

    // === Loading Button ===
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

    // === Dropzone Logo ===
    const dz = document.getElementById('seller_logo_dropzone');
    const input = document.getElementById('seller_logo');
    const previewWrap = document.getElementById('seller_logo_preview');
    const previewImg = previewWrap?.querySelector('img');
    const previewName = document.getElementById('seller_logo_filename');
    const errorEl = document.getElementById('seller_logo_error');

    if (dz && input && previewWrap && errorEl) {
        dz.addEventListener('click', () => input.click());
        ['dragenter','dragover'].forEach(ev => dz.addEventListener(ev, e => {
            e.preventDefault(); dz.classList.add('dropzone--over');
        }));
        ['dragleave','drop'].forEach(ev => dz.addEventListener(ev, e => {
            e.preventDefault(); dz.classList.remove('dropzone--over');
        }));
        dz.addEventListener('drop', e => {
            e.preventDefault();
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                handleFile(input.files[0]);
            }
        });
        input.addEventListener('change', e => handleFile(e.target.files[0]));

        function handleFile(file) {
            errorEl.classList.add('hidden');
            previewWrap.classList.add('hidden');
            if (!file) return;

            if (!file.type.startsWith('image/')) return showError('File harus berupa gambar (PNG/JPG)');
            if (file.size > 2 * 1024 * 1024) return showError('Ukuran maksimum 2MB');

            const reader = new FileReader();
            reader.onload = e => {
                previewImg.src = e.target.result;
                previewName.textContent = file.name;
                previewWrap.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }

        function showError(msg) {
            errorEl.textContent = msg;
            errorEl.classList.remove('hidden');
            input.value = '';
        }
    }
});
</script>
@endpush

