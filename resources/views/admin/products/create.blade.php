@extends('layouts.admin')

@section('title', 'Tambah Produk Baru')
@section('page-title', 'Tambah Produk Baru')

@push('styles')
<style>
    /* Style untuk form-form input */
    .image-uploader {
        border: 2px dashed #d1d5db;
        border-radius: 0.5rem;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.3s ease;
    }

    .image-uploader:hover,
    .image-uploader.dragging {
        border-color: #4f46e5;
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
        vertical-align: text-bottom;
        border: .2em solid currentColor;
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

    /*
    FIX v2: Mengatasi masalah double scrollbar & tombol yang tidak terlihat.
    */
    /* 1. Mencegah scrollbar utama (di body) dan membiarkan layout admin
          mengontrol penuh tinggi viewport. */
    html, body {
        height: 100%;
        overflow: hidden;
    }

    /* 2. Menjadikan area konten utama sebagai satu-satunya area yang bisa di-scroll.
          Container ini akan mengisi tinggi layar dan menampilkan scrollbar jika kontennya panjang.
          Selector ini didasarkan pada file layout 'admin' Anda. */
    .main-content, .content-wrapper, .page-content, main, .content {
        height: 100vh; /* Mengisi tinggi viewport. Sesuaikan (misal: calc(100vh - 60px)) jika ada header fix. */
        overflow-y: auto; /* Hanya area ini yang akan punya scrollbar vertikal. */
        padding-bottom: 96px; /* Memberi ruang di bagian bawah agar tidak terpotong oleh tombol sticky. */
    }
</style>
@endpush

@section('content')
@include('layouts.partials.notifications')

{{-- Form utama di-submit menggunakan tombol pada section 'footer' --}}
<form id="product-form" action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Kolom Kiri (Informasi Utama) --}}
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
                <div id="image-uploader" class="image-uploader">
                    <p class="font-semibold text-indigo-600">Klik untuk upload</p> atau seret file ke sini
                    <p class="text-xs text-gray-500">PNG, JPG, GIF hingga 5MB</p>
                </div>
                <input type="file" name="product_image" id="product_image" class="hidden" accept="image/*">
                @error('product_image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <img id="image-preview" src="#" alt="Pratinjau Gambar" class="image-preview" />
            </div>

            {{-- Informasi Penjual --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Penjual</h2>
                <div class="space-y-4">
                    <div>
                        <label for="store_name" class="block text-sm font-medium text-gray-700">Nama Toko</label>
                        <input type="text" name="store_name" id="store_name" value="{{ old('store_name') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('store_name') border-red-500 @enderror">
                        @error('store_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="seller_city" class="block text-sm font-medium text-gray-700">Kota Penjual</label>
                        <input type="text" name="seller_city" id="seller_city" value="{{ old('seller_city') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('seller_city') border-red-500 @enderror">
                        @error('seller_city') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="seller_wa" class="block text-sm font-medium text-gray-700">WhatsApp Toko</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 select-none">+62</span>
                            <input type="tel" name="seller_wa" id="seller_wa" inputmode="numeric" placeholder="81234567890" value="{{ old('seller_wa') }}" pattern="^(\+?62|0)?8[1-9][0-9]{6,11}$" class="block w-full rounded-md border border-gray-300 pl-12 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('seller_wa') border-red-500 @enderror" aria-describedby="seller_wa_help">
                        </div>
                        <p id="seller_wa_help" class="mt-1 text-xs text-gray-500">Format: 8xxxxxxxxxx (kami tambahkan +62 otomatis).</p>
                        @error('seller_wa') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Logo Toko</label>
                        <input id="seller_logo" name="seller_logo" type="file" accept="image/*" class="sr-only">
                        <label for="seller_logo" id="seller_logo_dropzone" class="mt-1 flex flex-col items-center justify-center gap-2 w-full rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center cursor-pointer transition hover:border-indigo-400 hover:bg-indigo-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 15a4 4 0 004 4h10a4 4 0 004-4m-4-6l-4-4m0 0L9 9m4-4v12"/></svg>
                            <div class="text-sm text-gray-700"><span class="font-medium">Tarik & lepas</span> atau <span class="font-medium text-indigo-600 underline">klik</span></div>
                            <p class="text-xs text-gray-500">PNG, JPG (maks. 2MB)</p>

                            {{-- Elemen untuk menampilkan pesan error validasi di sisi client --}}
                            <p id="seller_logo_error" class="text-xs text-red-600 font-medium hidden"></p>

                            <div id="seller_logo_preview" class="mt-3 hidden">
                                <img alt="Preview logo" class="mx-auto h-20 w-20 rounded-full object-cover ring-1 ring-gray-200">
                                <p class="mt-2 text-xs text-gray-500" id="seller_logo_filename"></p>
                            </div>
                        </label>
                        <small class="text-gray-500">Rekomendasi: 250x250 piksel.</small>
                        @error('seller_logo') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan (Harga, Stok, Organisasi) --}}
        <div class="space-y-6">
            {{-- Harga & Stok --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Harga & Stok</h2>
                <div class="space-y-4">
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700">Harga Jual (Rp)</label>
                        <input type="number" name="price" id="price" value="{{ old('price') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('price') border-red-500 @enderror" required>
                        @error('price') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="original_price" class="block text-sm font-medium text-gray-700">Harga Coret (Opsional)</label>
                        <input type="number" name="original_price" id="original_price" value="{{ old('original_price') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('original_price') border-red-500 @enderror">
                        @error('original_price') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="stock" class="block text-sm font-medium text-gray-700">Jumlah Stok</label>
                        <input type="number" name="stock" id="stock" value="{{ old('stock', 0) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('stock') border-red-500 @enderror" required>
                        @error('stock') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="weight" class="block text-sm font-medium text-gray-700">Berat (gram)</label>
                        <input type="number" name="weight" id="weight" value="{{ old('weight', 0) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('weight') border-red-500 @enderror" required>
                        @error('weight') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Organisasi Produk --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Organisasi Produk</h2>
                <div class="space-y-4">
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700">SKU</label>
                        <input type="text" name="sku" id="sku" value="{{ old('sku') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('sku') border-red-500 @enderror" required>
                        @error('sku') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Kategori</label>
                        <select name="category_id" id="category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('category_id') border-red-500 @enderror" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="tags" class="block text-sm font-medium text-gray-700">Tags (pisahkan koma)</label>
                        <input type="text" name="tags" id="tags" value="{{ old('tags') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('tags') border-red-500 @enderror">
                        @error('tags') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Status & Label --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Status & Label</h2>
                <div class="space-y-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status Produk</label>
                        <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Aktif (Dijual)</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif (Disimpan)</option>
                        </select>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_new" id="is_new" value="1" {{ old('is_new') ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="is_new" class="ml-2 block text-sm text-gray-900">Tandai sebagai Produk Baru</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_bestseller" id="is_bestseller" value="1" {{ old('is_bestseller') ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="is_bestseller" class="ml-2 block text-sm text-gray-900">Tandai sebagai Bestseller</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer')
{{-- Tombol Aksi dibuat sticky di bagian bawah area konten --}}
<div class="sticky bottom-0 z-10 flex justify-end bg-white p-4 rounded-lg shadow-md border-t">
    <a href="{{ route('admin.products.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium mr-2 hover:bg-gray-300">Batal</a>
    <button id="submit-button" type="submit" form="product-form" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 flex items-center disabled:opacity-50">
        <span id="button-text">Simpan Produk</span>
        <span id="button-spinner" class="spinner ml-2 hidden" role="status" aria-hidden="true"></span>
    </button>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Script untuk Image Uploader Utama
        const uploader = document.getElementById('image-uploader');
        const fileInput = document.getElementById('product_image');
        const preview = document.getElementById('image-preview');

        if (uploader && fileInput && preview) {
            uploader.addEventListener('click', () => fileInput.click());

            uploader.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploader.classList.add('dragging');
            });

            uploader.addEventListener('dragleave', () => {
                uploader.classList.remove('dragging');
            });

            uploader.addEventListener('drop', (e) => {
                e.preventDefault();
                uploader.classList.remove('dragging');
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    previewMainImage();
                }
            });

            fileInput.addEventListener('change', previewMainImage);

            function previewMainImage() {
                const file = fileInput.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                }
            }
        }

        // Script untuk menampilkan loading pada tombol submit
        const productForm = document.getElementById('product-form');
        const submitButton = document.getElementById('submit-button');
        const buttonText = document.getElementById('button-text');
        const buttonSpinner = document.getElementById('button-spinner');

        if (productForm && submitButton) {
            productForm.addEventListener('submit', function() {
                submitButton.disabled = true;
                buttonText.textContent = 'Menyimpan...';
                buttonSpinner.classList.remove('hidden');
            });
        }

        // Script untuk Logo Toko Dropzone dengan validasi inline
        (function setupSellerLogoUploader() {
            const dz = document.getElementById('seller_logo_dropzone');
            const input = document.getElementById('seller_logo');
            const previewWrap = document.getElementById('seller_logo_preview');
            const errorEl = document.getElementById('seller_logo_error');

            if (!dz || !input || !previewWrap || !errorEl) return;

            const previewImg = previewWrap.querySelector('img');
            const previewName = document.getElementById('seller_logo_filename');

            dz.addEventListener('click', () => input.click());

            ['dragenter', 'dragover'].forEach(eventName => {
                dz.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dz.classList.add('dropzone--over');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dz.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dz.classList.remove('dropzone--over');
                });
            });

            dz.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                if (files && files.length) {
                    input.files = files;
                    handleFiles(files);
                }
            });

            input.addEventListener('change', (e) => {
                handleFiles(e.target.files);
            });

            function handleFiles(files) {
                // Reset status error dan preview setiap kali ada file baru
                errorEl.classList.add('hidden');
                errorEl.textContent = '';
                previewWrap.classList.add('hidden');

                const file = files[0];
                if (!file) return;

                // Validasi tipe file
                if (!file.type.startsWith('image/')) {
                    showError('File harus berupa gambar (PNG/JPG/JPEG).');
                    input.value = ''; // Hapus file dari input jika tidak valid
                    return;
                }

                // Validasi ukuran file (maks 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showError('Ukuran maksimum file adalah 2MB.');
                    input.value = ''; // Hapus file dari input jika tidak valid
                    return;
                }

                // Tampilkan preview jika file valid
                const reader = new FileReader();
                reader.onload = (ev) => {
                    previewImg.src = ev.target.result;
                    previewName.textContent = file.name;
                    previewWrap.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }

            function showError(message) {
                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
            }
        })();
    });
</script>
@endpush

