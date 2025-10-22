@extends('layouts.admin')

@section('title', 'Edit Produk')
@section('page-title', 'Edit Produk: ' . Str::limit($product->name, 40))

@push('styles')
<style>
    /* =============================
        STYLE TAMBAH/EDIT PRODUK
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

    .content-wrapper {
        height: calc(100vh - (3.5rem + 1px)); /* (tinggi navbar + border) */
        overflow-y: auto;
        padding-bottom: 100px; /* Ruang untuk sticky action */
    }
    
    .content {
        padding-bottom: 100px;
    }

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
                    <p class="text-xs text-gray-500">atau seret file ke sini (PNG, JPG, WEBP hingga 2MB)</p>
                </div>
                <input type="file" name="product_image" id="product_image" class="hidden" accept="image/png, image/jpeg, image/webp">
                <img id="image-preview" 
                     src="{{ $product->image_url ? asset('storage/' . $product->image_url) : '' }}" 
                     alt="Pratinjau Gambar" 
                     class="image-preview {{ $product->image_url ? '' : 'hidden' }}" />
                @error('product_image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Informasi Penjual (STANDARISASI) --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Penjual</h2>
                <div class="space-y-4">
                    <div>
                        <label for="store_name" class="block text-sm font-medium text-gray-700">Nama Toko</label>
                        <input type="text" name="store_name" id="store_name" value="{{ old('store_name', $product->store_name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('store_name') border-red-500 @enderror" required>
                        @error('store_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="seller_name" class="block text-sm font-medium text-gray-700">Nama Penjual (Opsional)</label>
                        <input type="text" name="seller_name" id="seller_name" value="{{ old('seller_name', $product->seller_name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('seller_name') border-red-500 @enderror">
                    </div>
                    <div>
                        <label for="seller_city" class="block text-sm font-medium text-gray-700">Kota Penjual</label>
                        <input type="text" name="seller_city" id="seller_city" value="{{ old('seller_city', $product->seller_city) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('seller_city') border-red-500 @enderror" required>
                        @error('seller_city') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="seller_wa" class="block text-sm font-medium text-gray-700">WhatsApp Penjual (Opsional)</label>
                        <input type="text" name="seller_wa" id="seller_wa" value="{{ old('seller_wa', $product->seller_wa) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('seller_wa') border-red-500 @enderror" placeholder="Contoh: 628123456789">
                        <p class="mt-1 text-xs text-gray-500">Gunakan format 62 (bukan 0). Akan diformat otomatis.</p>
                        @error('seller_wa') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Logo Penjual (Opsional)</label>
                        <div id="seller-logo-uploader" class="image-uploader mt-1" tabindex="0">
                             <p class="font-semibold text-indigo-600">Klik untuk upload</p>
                             <p class="text-xs text-gray-500">Logo (PNG, JPG, WEBP maks 1MB)</p>
                        </div>
                        <input type="file" name="seller_logo" id="seller_logo" class="hidden" accept="image/png, image/jpeg, image/webp">
                        <img id="seller-logo-preview" 
                             src="{{ $product->seller_logo ? asset('storage/' . $product->seller_logo) : '' }}" 
                             alt="Pratinjau Logo" 
                             class="image-preview {{ $product->seller_logo ? '' : 'hidden' }}" />
                        @error('seller_logo') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Varian Produk (DILENGKAPI) --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Varian Produk (Opsional)</h2>
                    <button type="button" id="add-variant-group" class="btn btn-sm btn-outline-primary">Tambah Varian</button>
                </div>
                <p class="text-sm text-gray-600 mb-4">Tambahkan varian jika produk Anda memiliki pilihan seperti warna atau ukuran. Ini akan menonaktifkan input stok utama.</p>
                
                {{-- Kontainer untuk varian yang sudah ada dan yang baru --}}
                <div id="variant-groups-container" class="space-y-6">
                    {{-- Load Varian yang Sudah Ada --}}
                    @foreach($product->variants as $index => $variant)
                    <div class="border rounded-md p-4 space-y-3 bg-gray-50 existing-variant-group">
                        <div class="flex justify-between items-center">
                            <h3 class="font-semibold text-gray-700">Tipe Varian: {{ $variant->name }}</h3>
                            <button type="button" class="text-red-500 hover:text-red-700 remove-variant-group" title="Hapus Tipe Varian">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                        
                        {{-- Input hidden untuk ID agar backend tahu mana yang diupdate --}}
                        <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant->id }}">
                        
                        <div>
                            <label for="variant_{{ $index }}_name" class="block text-sm font-medium text-gray-700">Nama Tipe Varian</label>
                            <input type="text" name="variants[{{ $index }}][name]" id="variant_{{ $index }}_name" 
                                   value="{{ old("variants.$index.name", $variant->name) }}" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: Warna, Ukuran" required>
                        </div>
                        <div>
                            @php
                                // Konversi koleksi options menjadi string yang dipisahkan koma
                                $optionsString = $variant->options->pluck('name')->implode(', ');
                            @endphp
                            <label for="variant_{{ $index }}_options" class="block text-sm font-medium text-gray-700">Pilihan Varian (pisahkan koma)</label>
                            <input type="text" name="variants[{{ $index }}][options]" id="variant_{{ $index }}_options" 
                                   value="{{ old("variants.$index.options", $optionsString) }}" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: Merah, Biru, Hijau" required>
                        </div>
                    </div>
                    @endforeach
                    {{-- Grup varian dinamis baru akan ditambahkan di sini --}}
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
                        <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Opsional">
                    </div>
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Kategori</label>
                        <select name="category_id" id="category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
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
                        <input type="text" name="tags" id="tags" value="{{ old('tags', is_array($product->tags) ? implode(', ', $product->tags) : $product->tags) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: baju, atasan, pria">
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
    
    {{-- Tombol Aksi Sticky (STANDARISASI) --}}
    <div class="sticky-action">
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Batal</a>
        <button id="submit-button" type="submit" class="btn btn-primary flex items-center gap-2">
            Perbarui Produk
            <span class="spinner hidden" role="status" aria-hidden="true"></span>
        </button>
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
                    preview.classList.remove('hidden'); // Pastikan tidak hidden
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
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                return;
            }
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <span class="spinner" role="status" aria-hidden="true"></span>
                Memperbarui...
            `;
        });
    }

    // --- WhatsApp Input Formatter ---
    const waInput = document.getElementById('seller_wa');
    if (waInput) {
        // Hapus '62' di awal jika ada, untuk konsistensi
        if (waInput.value.startsWith('62')) {
            waInput.value = waInput.value.substring(2);
        }
        
        waInput.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, ''); // Hapus non-digit
            // Jangan tambahkan 62 di sini, biarkan controller yang menangani
            // Ini agar user bisa mengedit '0812' atau '812'
            e.target.value = value;
        });
    }

    // --- Script Atribut Dinamis (Versi EDIT: Memuat data yang ada) ---
    const categorySelect = document.getElementById('category_id');
    const attributesCard = document.getElementById('attributes-card');
    const attributesContainer = document.getElementById('dynamic-attributes-container');
    // Ambil data atribut yang sudah tersimpan dari Blade
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
            attributesCard.classList.remove('hidden');
            const response = await fetch(url);
            if (!response.ok) throw new Error('Gagal memuat atribut.');
            
            const attributes = await response.json();
            attributesContainer.innerHTML = ''; 

            if (attributes.length > 0) {
                attributes.forEach(attr => {
                    // Cek apakah ada nilai yang tersimpan untuk atribut ini
                    const existingValue = existingAttributes[attr.slug] || null;
                    const field = createAttributeField(attr, existingValue);
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

    // Fungsi ini sekarang menerima 'value' untuk mengisi data yang ada
    function createAttributeField(attribute, value) {
        const wrapper = document.createElement('div');
        let fieldHtml = '';
        const isRequired = attribute.is_required ? 'required' : '';
        const requiredAsterisk = attribute.is_required ? '<span class="text-red-500">*</span>' : '';
        const label = `<label for="attr_${attribute.slug}" class="block text-sm font-medium text-gray-700">${attribute.name} ${requiredAsterisk}</label>`;
        const inputName = `attributes[${attribute.slug}]`;
        // Atur value attribute jika ada (dan bukan object/array)
        const valueAttribute = (value !== null && typeof value !== 'object') ? `value="${String(value).replace(/"/g, '&quot;')}"` : '';

        switch (attribute.type) {
            case 'number':
            case 'text':
                fieldHtml = `${label}<input type="${attribute.type}" name="${inputName}" id="attr_${attribute.slug}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired} ${valueAttribute}>`;
                break;
            case 'textarea':
                // Untuk textarea, value ditaruh di dalam tag
                fieldHtml = `${label}<textarea name="${inputName}" id="attr_${attribute.slug}" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired}>${value || ''}</textarea>`;
                break;
            case 'select':
                const options = (attribute.options || '').split(',').map(opt => {
                    const trimmedOpt = opt.trim();
                    // Tandai 'selected' jika value cocok
                    const selected = trimmedOpt == value ? 'selected' : '';
                    return `<option value="${trimmedOpt}" ${selected}>${trimmedOpt}</option>`;
                }).join('');
                fieldHtml = `${label}<select name="${inputName}" id="attr_${attribute.slug}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired}><option value="">-- Pilih ${attribute.name} --</option>${options}</select>`;
                break;
            case 'checkbox':
                // Handle jika value tersimpan adalah array (dari multiple checkbox)
                const valueArray = Array.isArray(value) ? value : (value ? [value] : []);
                const checkboxes = (attribute.options || '').split(',').map((opt, index) => {
                    const trimmedOpt = opt.trim();
                    // Tandai 'checked' jika value ada di dalam array
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
    // Jalankan saat load untuk memuat atribut dari kategori yang tersimpan
    if (categorySelect.value) {
        fetchAndRenderAttributes();
    }

    // --- Script Varian Dinamis (Versi EDIT) ---
    const variantContainer = document.getElementById('variant-groups-container');
    const addVariantBtn = document.getElementById('add-variant-group');
    const mainStockInput = document.getElementById('stock');
    // Mulai index dari jumlah varian yang sudah ada
    let variantIndex = {{ $product->variants->count() }};

    if (addVariantBtn && variantContainer && mainStockInput) {
        addVariantBtn.addEventListener('click', (e) => {
            e.preventDefault();
            // Gunakan index baru untuk varian baru
            variantContainer.appendChild(createVariantGroup(variantIndex));
            variantIndex++; // Increment index untuk varian baru berikutnya
            toggleMainStock();
        });
    }

    // Fungsi untuk membuat grup varian BARU (sama seperti create.blade.php)
    function createVariantGroup(index) {
        const groupWrapper = document.createElement('div');
        groupWrapper.classList.add('border', 'rounded-md', 'p-4', 'space-y-3', 'bg-gray-50');
        groupWrapper.innerHTML = `
            <div class="flex justify-between items-center">
                <h3 class="font-semibold text-gray-700">Tipe Varian Baru #${index + 1}</h3>
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
        
        // Listener untuk tombol hapus pada varian BARU
        groupWrapper.querySelector('.remove-variant-group').addEventListener('click', (e) => {
            e.preventDefault();
            groupWrapper.remove();
            toggleMainStock();
        });

        return groupWrapper;
    }

    // Tambahkan listener untuk tombol hapus pada varian LAMA (yang sudah ada)
    document.querySelectorAll('.remove-variant-group').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            // Dapatkan wrapper terdekat (bisa .existing-variant-group atau yg baru)
            const groupWrapper = e.target.closest('.border.rounded-md');
            if (groupWrapper) {
                groupWrapper.remove();
                toggleMainStock();
                
                // PENTING: Jika ini adalah varian yang sudah ada, Anda perlu
                // menambahkan input hidden `[delete]` agar backend tahu.
                // Untuk saat ini, logika backend Anda harus "sync"
                // (hapus semua varian lama, buat ulang dari data form).
            }
        });
    });


    function toggleMainStock() {
        if (!mainStockInput) return;

        const warningId = 'stock-warning';
        let warningEl = document.getElementById(warningId);

        // Cek apakah ada anak di dalam container varian
        if (variantContainer.children.length > 0) {
            mainStockInput.disabled = true;
            if (!warningEl) {
                mainStockInput.parentElement.insertAdjacentHTML('afterend', `
                    <p id="${warningId}" class="mt-1 text-xs text-indigo-600">
                        Stok utama dinonaktifkan. Anda perlu mengatur stok untuk tiap varian.
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
