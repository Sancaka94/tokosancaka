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
        display: none; /* Sembunyikan default */
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
        /* Ini akan menempel di bawah .content-wrapper */
    }
</style>
@endpush

@section('content')
@include('layouts.partials.notifications')

{{-- Form harus berada di luar grid untuk sticky footer --}}
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
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Gambar Utama Produk</h2>
                <div id="image-uploader" class="image-uploader" tabindex="0">
                    <p class="font-semibold text-indigo-600">Klik untuk upload</p>
                    <p class="text-xs text-gray-500">atau seret file ke sini (PNG, JPG, WEBP hingga 2MB)</p>
                </div>
                <input type="file" name="product_image" id="product_image" class="hidden" accept="image/png, image/jpeg, image/webp">
                <img id="image-preview" alt="Pratinjau Gambar" class="image-preview" />
                @error('product_image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Informasi Penjual --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Penjual</h2>
                <div class="space-y-4">
                    <div>
                        <label for="store_name" class="block text-sm font-medium text-gray-700">Nama Toko</label>
                        {{-- PERBAIKAN: Hapus 'required' --}}
                        <input type="text" name="store_name" id="store_name" value="{{ old('store_name') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('store_name') border-red-500 @enderror">
                        <p class="mt-1 text-xs text-gray-500">Kosongkan untuk menggunakan data admin default.</p>
                        @error('store_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="seller_name" class="block text-sm font-medium text-gray-700">Nama Penjual (Opsional)</label>
                        <input type="text" name="seller_name" id="seller_name" value="{{ old('seller_name') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('seller_name') border-red-500 @enderror">
                    </div>
                    <div>
                        <label for="seller_city" class="block text-sm font-medium text-gray-700">Kota Penjual</label>
                        {{-- PERBAIKAN: Hapus 'required' --}}
                        <input type="text" name="seller_city" id="seller_city" value="{{ old('seller_city') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('seller_city') border-red-500 @enderror">
                        <p class="mt-1 text-xs text-gray-500">Kosongkan untuk menggunakan data admin default.</p>
                        @error('seller_city') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="seller_wa" class="block text-sm font-medium text-gray-700">WhatsApp Penjual (Opsional)</label>
                        <input type="text" name="seller_wa" id="seller_wa" value="{{ old('seller_wa') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('seller_wa') border-red-500 @enderror" placeholder="Contoh: 8123456789 (tanpa 0 atau 62)">
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
                        <img id="seller-logo-preview" alt="Pratinjau Logo" class="image-preview" />
                        @error('seller_logo') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Varian Produk (BARU) --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-4"> {{-- Mengurangi margin bawah --}}
                    <h2 class="text-lg font-semibold text-gray-800">Varian Produk (Opsional)</h2>
                    <button type="button" id="add-variant-group" class="btn btn-sm btn-outline-primary">Tambah Varian</button>
                </div>
                <p class="text-sm text-gray-600 mb-4">Tambahkan varian jika produk Anda memiliki pilihan seperti warna atau ukuran. Ini akan menonaktifkan input stok utama.</p>
                <div id="variant-groups-container" class="space-y-6">
                    {{-- Grup varian dinamis akan ditambahkan di sini --}}
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
                            <input type="number" name="price" id="price" value="{{ old('price') }}" class="pl-8 block w-full border-gray-300 rounded-md shadow-sm @error('price') border-red-500 @enderror" placeholder="100000" required>
                        </div>
                        @error('price') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="original_price" class="block text-sm font-medium text-gray-700">Harga Asli (Harga Coret)</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rp</span>
                            <input type="number" name="original_price" id="original_price" value="{{ old('original_price') }}" class="pl-8 block w-full border-gray-300 rounded-md shadow-sm @error('original_price') border-red-500 @enderror" placeholder="120000">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Opsional. Isi untuk menampilkan diskon.</p>
                        @error('original_price') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="stock" class="block text-sm font-medium text-gray-700">Stok</label>
                        <input type="number" name="stock" id="stock" value="{{ old('stock', 0) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('stock') border-red-500 @enderror" required>
                        @error('stock') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="weight" class="block text-sm font-medium text-gray-700">Berat</label>
                        <div class="relative mt-1">
                            <input type="number" name="weight" id="weight" value="{{ old('weight') }}" class="pr-12 block w-full border-gray-300 rounded-md shadow-sm @error('weight') border-red-500 @enderror" placeholder="100" required> {{-- Berat tetap wajib --}}
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500">gram</span>
                        </div>
                        @error('weight') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Dimensi Paket (Opsional)</label>
                        <div class="grid grid-cols-3 gap-4 mt-1">
                            <div>
                                 <label for="length" class="text-xs text-gray-500">Panjang (cm)</label>
                                 <input type="number" name="length" id="length" value="{{ old('length') }}" class="block w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                 <label for="width" class="text-xs text-gray-500">Lebar (cm)</label>
                                 <input type="number" name="width" id="width" value="{{ old('width') }}" class="block w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                 <label for="height" class="text-xs text-gray-500">Tinggi (cm)</label>
                                 <input type="number" name="height" id="height" value="{{ old('height') }}" class="block w-full border-gray-300 rounded-md shadow-sm">
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
                        <input type="text" name="sku" id="sku" value="{{ old('sku') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Otomatis jika kosong"> {{-- Update Placeholder --}}
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
                        @error('category_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="tags" class="block text-sm font-medium text-gray-700">Tags (pisahkan koma)</label>
                        <input type="text" name="tags" id="tags" value="{{ old('tags') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Otomatis dari kategori jika kosong"> {{-- Update Placeholder --}}
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
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status Produk</label>
                        <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Aktif (Dijual)</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Nonaktif (Disimpan)</option>
                        </select>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_new" id="is_new" value="1" {{ old('is_new') ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        <label for="is_new" class="ml-2 block text-sm text-gray-900">Tandai sebagai Produk Baru</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_bestseller" id="is_bestseller" value="1" {{ old('is_bestseller') ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        <label for="is_bestseller" class="ml-2 block text-sm text-gray-900">Tandai sebagai Bestseller</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Tombol Aksi Sticky --}}
    <div class="sticky-action">
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Batal</a>
        <button id="submit-button" type="submit" class="btn btn-primary flex items-center gap-2">Simpan Produk</button>
    </div>
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

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
        form.addEventListener('submit', () => {
            // Hanya nonaktifkan jika form valid (untuk browser modern)
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                // Tampilkan pesan error bawaan browser
                form.reportValidity(); 
                return;
            }
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <span class="spinner" role="status" aria-hidden="true"></span>
                Menyimpan...
            `;
        });
    }

    // --- WhatsApp Input Formatter (Lebih Sederhana) ---
    const waInput = document.getElementById('seller_wa');
    if (waInput) {
        waInput.addEventListener('input', (e) => {
            // Hanya izinkan angka, biarkan controller format ke 62
            e.target.value = e.target.value.replace(/\D/g, ''); 
        });
    }

    // --- Script Atribut Dinamis (dari kode Anda) ---
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
            attributesCard.classList.remove('hidden'); // Tampilkan card saat loading
            const response = await fetch(url);
            if (!response.ok) throw new Error('Gagal memuat atribut.');
            
            const attributes = await response.json();
            attributesContainer.innerHTML = ''; 

            if (attributes.length > 0) {
                attributes.forEach(attr => {
                    const field = createAttributeField(attr);
                    attributesContainer.appendChild(field);
                });
            } else {
                attributesContainer.innerHTML = '<p class="text-gray-500">Tidak ada spesifikasi tambahan untuk kategori ini.</p>';
            }
        } catch (error) {
            console.error('Error:', error);
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
    // Jalankan juga saat load jika ada kategori yang sudah terpilih (misal: saat validasi error)
    if(categorySelect.value) {
        fetchAndRenderAttributes();
    }

    // --- Script Varian Dinamis (BARU) ---
    const variantContainer = document.getElementById('variant-groups-container');
    const addVariantBtn = document.getElementById('add-variant-group');
    const mainStockInput = document.getElementById('stock');
    let variantIndex = 0;

    if (addVariantBtn && variantContainer && mainStockInput) {
        addVariantBtn.addEventListener('click', (e) => {
            e.preventDefault(); // Mencegah form submit jika tombol ada di dalam form
            variantContainer.appendChild(createVariantGroup(variantIndex));
            variantIndex++;
            toggleMainStock();
        });
    }

    function createVariantGroup(index) {
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
                <input type="text" name="variants[${index}][name]" id="variant_${index}_name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: Warna, Ukuran" required>
            </div>
            <div>
                <label for="variant_${index}_options" class="block text-sm font-medium text-gray-700">Pilihan Varian (pisahkan koma)</label>
                <input type="text" name="variants[${index}][options]" id="variant_${index}_options" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: Merah, Biru, Hijau" required>
            </div>
        `;
        
        groupWrapper.querySelector('.remove-variant-group').addEventListener('click', (e) => {
            e.preventDefault();
            groupWrapper.remove();
            toggleMainStock();
        });

        return groupWrapper;
    }

    function toggleMainStock() {
        if (!mainStockInput) return;

        const warningId = 'stock-warning';
        const warningEl = document.getElementById(warningId);

        if (variantContainer.children.length > 0) {
            mainStockInput.disabled = true;
            mainStockInput.value = ''; // Kosongkan stok utama
            if (!warningEl) {
                mainStockInput.parentElement.insertAdjacentHTML('afterend', `
                    <p id="${warningId}" class="mt-1 text-xs text-indigo-600">
                        Stok utama dinonaktifkan. Anda perlu mengatur stok untuk tiap varian nanti setelah produk disimpan.
                    </p>
                `);
            }
        } else {
            mainStockInput.disabled = false;
            if (warningEl) {
                warningEl.remove();
            }
        }
    }
    
    // Panggil saat load untuk cek
    toggleMainStock();

});
</script>
@endpush

