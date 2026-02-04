@extends('layouts.admin')

@section('title', 'Edit Produk: ' . $product->name)
@section('page-title', 'Edit Produk')

@push('styles')
<style>
    /* --- RESET & BASIC SETUP --- */
    /* Hapus html, body height 100% agar scrollbar browser bawaan muncul */
    /* Trik memindahkan panah input number ke KIRI */
    .spinner-left {
        direction: rtl;       /* Memaksa elemen UI (panah) ke kiri */
        text-align: center;   /* Teks angka tetap di tengah */
        padding-left: 10px;   /* Memberi jarak agar tidak mepet panah */
    }
    
    /* Memastikan saat mengetik angka tidak terbalik */
    .spinner-left::placeholder {
        direction: ltr;
    }
    
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

    /* --- STICKY FOOTER ACTION BAR --- */
    .sticky-action {
        position: sticky; /* Sticky ke bawah container, bukan layar */
        bottom: 0;
        z-index: 50;
        
        background-color: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(5px);
        border-top: 1px solid #e2e8f0;
        
        padding: 1rem 1.5rem;
        margin-top: 2rem;
        /* Negatif margin untuk melebar menutupi padding container parent */
        margin-left: -1.5rem; 
        margin-right: -1.5rem;
        
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

    /* CLASS ALA BOOTSTRAP 5 */
    .form-control {
        @apply w-full px-3 py-2 text-base font-normal text-gray-700 bg-white bg-clip-padding border border-gray-300 rounded-md transition ease-in-out m-0;
        /* Efek Focus (Glow Biru) */
        @apply focus:text-gray-700 focus:bg-white focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100;
    }

    /* Khusus Input Group (seperti +62 di sebelah input) */
    .input-group-text {
        @apply flex items-center px-3 py-2 text-base font-normal text-gray-700 bg-gray-100 border border-gray-300 rounded-l-md border-r-0;
    }
    
    /* Fix agar input yang nempel dengan group tidak rounded kirinya */
    .form-control.rounded-none-l {
        @apply rounded-l-none;
    }
</style>
@endpush

@section('content')
@include('layouts.partials.notifications')

<form id="product-form" action="{{ route('admin.products.update', $product->slug) }}" method="POST" enctype="multipart/form-data" novalidate>

@csrf
    @method('PUT')

    <input type="hidden" name="category_id" value="{{ $product->category_id }}">

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
                        {{-- Icon diubah jadi Blue agar seragam --}}
                        <i class="fa-solid fa-box-open text-blue-500 mr-2"></i> Informasi Dasar
                    </h2>
                </div>
                
                <div class="p-6 space-y-6">
                    {{-- Input Nama Produk --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1 required-label">Nama Produk</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" 
                               class="w-full h-11 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-4 focus:ring-blue-100 focus:border-blue-400 transition-colors text-gray-700" 
                               placeholder="Contoh: Kemeja Pria Slim Fit" required>
                        @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    
                    {{-- Textarea Deskripsi --}}
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Produk</label>
                        <textarea name="description" id="description" rows="6" 
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-4 focus:ring-blue-100 focus:border-blue-400 transition-colors text-gray-700 leading-relaxed" 
                                  placeholder="Jelaskan detail spesifikasi, keunggulan, dan fitur produk Anda...">{{ old('description', $product->description) }}</textarea>
                        @error('description') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

           <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-6">
    <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
        <span class="bg-indigo-100 text-indigo-600 p-1.5 rounded-lg">
            <i class="fa-solid fa-images text-sm"></i>
        </span>
        Media Produk (Maks. 5 Gambar)
    </h2>

    {{-- LOGIKA PENTING: Petakan gambar berdasarkan sort_order --}}
    @php
        // Mengubah Collection menjadi array dengan key 'sort_order'
        // Pastikan di Controller Anda menyimpan kolom 'sort_order' (0,1,2,3,4)
        $existingImages = $product->images->keyBy('sort_order'); 
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        @for ($i = 0; $i < 5; $i++)
            @php
                // Cek apakah ada gambar di urutan/slot ini
                $hasImage = isset($existingImages[$i]);
                $imagePath = $hasImage ? asset('public/storage/' . $existingImages[$i]->path) : '';
            @endphp

            <div class="relative w-full aspect-square group">
                
                {{-- Input File (Hidden) --}}
                <input type="file" 
                       name="product_images[{{ $i }}]" 
                       id="input-img-{{ $i }}" 
                       class="hidden" 
                       accept="image/*"
                       onchange="previewImage(this, {{ $i }})">

                {{-- Label / Kotak Klik --}}
                <label for="input-img-{{ $i }}" 
                       class="block w-full h-full border-2 {{ $i === 0 ? 'border-indigo-500' : 'border-dashed border-gray-300' }} rounded-xl cursor-pointer hover:border-indigo-400 transition relative overflow-hidden bg-gray-50 flex flex-col items-center justify-center text-center">
                    
                    {{-- 1. Placeholder (Tampil jika TIDAK ADA gambar) --}}
                    <div id="placeholder-{{ $i }}" class="p-2 {{ $hasImage ? 'hidden' : '' }}">
                        @if($i === 0)
                            <span class="absolute top-2 left-2 bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm z-10">
                                Utama
                            </span>
                        @else
                            <span class="absolute top-2 left-2 bg-gray-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm z-10">
                                Gbr {{ $i + 1 }}
                            </span>
                        @endif
                        
                        <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-300 mb-2"></i>
                        <span class="text-[10px] text-gray-400 block">Upload</span>
                    </div>

                    {{-- 2. Preview Image (Tampil jika ADA gambar) --}}
                    <img id="preview-img-{{ $i }}" 
                         src="{{ $imagePath }}" 
                         class="absolute inset-0 w-full h-full object-cover {{ $hasImage ? '' : 'hidden' }}">
                </label>

                {{-- Tombol Hapus (X) --}}
                <button type="button" 
                        id="btn-remove-{{ $i }}"
                        onclick="removeImage({{ $i }})"
                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center shadow-md hover:bg-red-600 transition z-20 {{ $hasImage ? '' : 'hidden' }}">
                    <i class="fa-solid fa-times text-xs"></i>
                </button>
            </div>
        @endfor
    </div>
    <p class="text-xs text-gray-400 mt-3 flex items-center gap-1">
        <i class="fa-solid fa-circle-info text-indigo-400"></i>
        Klik kotak untuk mengunggah. Gambar pertama akan menjadi cover produk.
    </p>
</div>

{{-- DEBUGGING TEMPORARY --}}
<div class="bg-yellow-100 p-4 mb-4 text-xs font-mono">
    DEBUG DATA GAMBAR: <br>
    Jumlah Gambar: {{ $product->images->count() }} <br>
    
    @foreach($product->images as $img)
        - Slot: {{ $img->sort_order }} | Path: {{ $img->path }} <br>
        - URL Aset: {{ asset('public/storage/' . $img->path) }} <br>
    @endforeach
</div>

            {{-- 3. INFORMASI PENJUAL --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fa-solid fa-store text-indigo-500 mr-2"></i> Informasi Toko / Penjual
                    </h2>
                </div>
                
                <div class="p-6 space-y-6">
                    
                    {{-- Baris 1: Nama Toko & Kota --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="store_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Toko</label>
                            <input type="text" name="store_name" id="store_name" value="{{ old('store_name', $product->store_name) }}" 
                                   class="w-full h-11 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-4 focus:ring-blue-100 focus:border-blue-400 transition-colors text-gray-700">
                            <p class="mt-1 text-xs text-gray-400">Biarkan kosong untuk menggunakan default admin.</p>
                        </div>
                        <div>
                            <label for="seller_city" class="block text-sm font-medium text-gray-700 mb-1">Kota Asal</label>
                            <input type="text" name="seller_city" id="seller_city" value="{{ old('seller_city', $product->seller_city) }}" 
                                   class="w-full h-11 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-4 focus:ring-blue-100 focus:border-blue-400 transition-colors text-gray-700">
                        </div>
                    </div>

                    {{-- Baris 2: Nama Penjual & WhatsApp --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="seller_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Penjual (Opsional)</label>
                            <input type="text" name="seller_name" id="seller_name" value="{{ old('seller_name', $product->seller_name) }}" 
                                   class="w-full h-11 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-4 focus:ring-blue-100 focus:border-blue-400 transition-colors text-gray-700">
                        </div>
                        <div>
                            <label for="seller_wa" class="block text-sm font-medium text-gray-700 mb-1">WhatsApp (Opsional)</label>
                            <div class="flex relative rounded-lg shadow-sm">
                                <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 font-medium text-sm">
                                    +62
                                </span>
                                <input type="text" name="seller_wa" id="seller_wa" value="{{ old('seller_wa', $product->seller_wa ? ltrim($product->seller_wa, '62') : '') }}" 
                                       class="flex-1 w-full h-11 px-3 border border-gray-300 rounded-none rounded-r-lg focus:outline-none focus:ring-4 focus:ring-blue-100 focus:border-blue-400 transition-colors text-gray-700" 
                                       placeholder="8123xxxx">
                            </div>
                        </div>
                    </div>

                    {{-- Baris 3: Logo Toko --}}
                    <div class="border-t border-gray-100 pt-4 mt-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Logo Toko (Opsional)</label>
                        
                        <div class="flex items-center gap-5">
                            {{-- Area Upload --}}
                            <div class="relative w-24 h-24 flex-shrink-0 rounded-xl overflow-hidden border border-gray-300 group cursor-pointer hover:border-blue-400 hover:ring-4 hover:ring-blue-50 transition-all duration-300 bg-white"
                                 title="Klik untuk mengganti logo"
                                 onclick="document.getElementById('seller_logo').click();">
                                
                                <img id="seller-logo-preview" 
                                     class="w-full h-full object-contain p-1"
                                     alt="Logo Toko"
                                     src="{{ $product->seller_logo ? asset('public/storage/' . $product->seller_logo) : 'https://tokosancaka.com/storage/uploads/sancaka.png' }}"
                                     onerror="this.onerror=null; this.src='https://tokosancaka.com/storage/uploads/sancaka.png';">

                                <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                    <i class="fa-solid fa-pen text-white text-lg drop-shadow-md"></i>
                                </div>
                            </div>

                            <div class="text-sm text-gray-500">
                                <p class="font-semibold text-gray-700 mb-1">Ganti Logo Toko</p>
                                <p class="text-xs text-gray-400 mb-1">Klik gambar di samping untuk memilih file baru.</p>
                                <p class="text-xs text-gray-400">Format: PNG, JPG, WEBP (Maks. 2MB)</p>
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

                    <div id="variant-groups-container" class="space-y-4"></div>

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

           <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-6">
    <h2 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
        <span class="bg-blue-100 text-blue-600 p-1.5 rounded-lg">
            <i class="fa-solid fa-tag text-sm"></i>
        </span>
        Harga & Stok
    </h2>

    <div class="space-y-6">
        
        {{-- Row 1: Harga Jual --}}
        <div>
            <label for="price" class="block text-sm font-medium text-gray-700 mb-1">
                Harga Jual <span class="text-red-500">*</span>
            </label>
            <div class="relative rounded-lg shadow-sm group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 font-medium sm:text-sm">Rp</span>
                </div>
                <input type="text" name="price" id="price" 
                    class="currency-input block w-full pl-10 pr-4 py-2.5 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors font-semibold text-gray-900 placeholder-gray-300"
                    placeholder="0"
                    value="{{ old('price', number_format($product->price ?? 0, 0, ',', '.')) }}" required>
            </div>
            <p class="text-[10px] text-gray-400 mt-1">Harga final yang akan dibayar pembeli.</p>
        </div>

        {{-- Row 2: Harga Coret --}}
        <div>
            <label for="original_price" class="block text-sm font-medium text-gray-700 mb-1">
                Harga Coret <span class="text-xs text-gray-400 font-normal">(Opsional)</span>
            </label>
            <div class="relative rounded-lg shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 font-medium sm:text-sm">Rp</span>
                </div>
                <input type="text" name="original_price" id="original_price" 
                    class="currency-input block w-full pl-10 pr-4 py-2.5 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-gray-700 placeholder-gray-300"
                    placeholder="0"
                    value="{{ old('original_price', ($product->original_price ?? 0) > 0 ? number_format($product->original_price, 0, ',', '.') : '') }}">
            </div>
            <p class="text-[10px] text-gray-400 mt-1">Isi jika ingin menampilkan diskon (harga asli lebih tinggi).</p>
        </div>

        {{-- Row 3: Stok & Berat (Grid 2 Kolom) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Stok --}}
            <div>
                <label for="stock" class="block text-sm font-medium text-gray-700 mb-1">
                    Stok <span class="text-red-500">*</span>
                </label>
                <div class="relative rounded-lg shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-box text-gray-400 text-xs"></i>
                    </div>
                    <input type="number" name="stock" id="stock" min="0"
                        class="block w-full pl-9 pr-4 py-2.5 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        value="{{ old('stock', $product->stock ?? 0) }}" required>
                </div>
            </div>

            {{-- Berat --}}
            <div>
                <label for="weight" class="block text-sm font-medium text-gray-700 mb-1">
                    Berat <span class="text-red-500">*</span>
                </label>
                <div class="flex rounded-lg shadow-sm">
                    <input type="number" name="weight" id="weight" min="0"
                        class="block w-full min-w-0 flex-1 rounded-none rounded-l-lg border-blue-300 focus:ring-2 focus:ring-blue-600 focus:border-blue-500 py-2.5 pl-3 transition-colors"
                        value="{{ old('weight', $product->weight ?? 0) }}" required>
                    <span class="inline-flex items-center rounded-r-lg border border-l-0 border-gray-300 bg-gray-50 px-3 text-gray-500 text-xs font-bold tracking-wider">
                        GRAM
                    </span>
                </div>
            </div>
        </div>

        {{-- Row 4: Dimensi (Grid 3 Kolom) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Dimensi Paket <span class="text-xs text-gray-400 font-normal">(PxLxT)</span>
                <i class="text-gray-300"></i>
            </label>
            <div class="grid grid-cols-3 gap-4">
                {{-- Panjang --}}
                <div class="relative rounded-lg shadow-sm">
                    <input type="number" name="length" placeholder="0" min="0"
                        class="block w-full pr-8 pl-3 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                        value="{{ old('length', $product->length) }}">
                    <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none">
                        <span class="text-gray-400 text-xs">cm</span>
                    </div>
                    <div class="absolute -top-2 left-2 bg-white px-1 text-[10px] text-gray-400">Panjang</div>
                </div>

                {{-- Lebar --}}
                <div class="relative rounded-lg shadow-sm">
                    <input type="number" name="width" placeholder="0" min="0"
                        class="block w-full pr-8 pl-3 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                        value="{{ old('width', $product->width) }}">
                    <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none">
                        <span class="text-gray-400 text-xs">cm</span>
                    </div>
                    <div class="absolute -top-2 left-2 bg-white px-1 text-[10px] text-gray-400">Lebar</div>
                </div>

                {{-- Tinggi --}}
                <div class="relative rounded-lg shadow-sm">
                    <input type="number" name="height" placeholder="0" min="0"
                        class="block w-full pr-8 pl-3 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                        value="{{ old('height', $product->height) }}">
                    <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none">
                        <span class="text-gray-400 text-xs">cm</span>
                    </div>
                    <div class="absolute -top-2 left-2 bg-white px-1 text-[10px] text-gray-400">Tinggi</div>
                </div>
            </div>
        </div>

    </div>
</div>

            {{-- CARD 1: KATEGORI & DATA (READ ONLY) --}}
    <div class="bg-white rounded-xl shadow-sm border border-red-100 overflow-hidden">
        {{-- Header Merah Muda --}}
        <div class="bg-red-50 px-5 py-3 border-b border-red-100 flex justify-between items-center">
            <h3 class="text-sm font-bold text-gray-800">Kategori & Data</h3>
            <span class="text-[10px] font-semibold bg-white text-red-500 px-2 py-0.5 rounded-full border border-red-200">
                Read Only
            </span>
        </div>

        <div class="p-5">
            {{-- Kategori (Teks Statis) --}}
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-500 mb-1">Kategori Saat Ini:</label>
                <div class="flex items-center gap-2 text-indigo-700 font-semibold text-base">
                    <i class="fa-solid fa-folder-open"></i>
                    <span>{{ $product->category->name ?? 'Tidak ada kategori' }}</span>
                </div>
            </div>

            {{-- SKU (Teks Statis) --}}
            <div class="mb-5">
                <label class="block text-xs font-medium text-gray-500 mb-1">SKU:</label>
                <div class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg font-mono text-sm border border-gray-200">
                    {{ $product->sku ?? '-' }}
                </div>
            </div>

            {{-- Tombol Pindah ke Halaman Edit Spesifikasi --}}
            <a href="{{ route('admin.products.edit.specifications', $product->slug) }}" 
               class="block w-full text-center py-2.5 bg-emerald-400 hover:bg-emerald-500 text-white rounded-lg font-bold shadow-sm shadow-emerald-200 transition-all transform hover:-translate-y-0.5">
                <i class="fa-solid fa-sliders mr-1"></i> Edit Kategori & Spesifikasi
            </a>
            <p class="text-[10px] text-gray-400 text-center mt-2">
                Klik tombol di atas untuk mengubah Kategori, SKU, atau Atribut.
            </p>
        </div>
    </div>

    {{-- CARD 2: PREVIEW SPESIFIKASI (READ ONLY) --}}
    <div class="bg-white rounded-xl shadow-sm border border-indigo-50 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-50 flex items-center gap-2">
            <div class="bg-indigo-100 text-indigo-600 p-1.5 rounded-md">
                <i class="fa-solid fa-clipboard-list text-xs"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-800">Preview Spesifikasi</h3>
        </div>

        <div class="p-0">
            @php
                // Logika sederhana untuk mengambil atribut
                $attributes = $product->productAttributes;
            @endphp

            @if($attributes->count() > 0)
                <div class="divide-y divide-gray-50 max-h-[300px] overflow-y-auto">
                    @foreach($attributes as $attr)
                        <div class="px-5 py-3 hover:bg-gray-50 transition">
                            <p class="text-xs text-gray-500 mb-0.5 capitalize">{{ $attr->name }}</p>
                            
                            {{-- Cek apakah value JSON (Array) atau String biasa --}}
                            @php
                                $val = $attr->value;
                                $isJson = is_string($val) && str_starts_with(trim($val), '[') && is_array(json_decode($val, true));
                            @endphp

                            @if($isJson)
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach(json_decode($val, true) as $item)
                                        <span class="inline-block px-2 py-0.5 bg-indigo-50 text-indigo-600 text-[10px] font-medium rounded border border-indigo-100">
                                            {{ $item }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm font-semibold text-gray-800 leading-tight">{{ $val }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Empty State --}}
                <div class="p-8 text-center">
                    <div class="bg-gray-50 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-300">
                        <i class="fa-solid fa-scroll text-xl"></i>
                    </div>
                    <p class="text-sm text-gray-500 font-medium">Belum ada data spesifikasi.</p>
                    <p class="text-xs text-gray-400 mt-1">Tambahkan atribut pada menu edit kategori.</p>
                </div>
            @endif
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