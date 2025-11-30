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

            {{-- 2. MEDIA / GAMBAR (MULTI UPLOAD 5X) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fa-solid fa-images text-indigo-500 mr-2"></i> Media Produk (Maks. 5 Gambar)
                    </h2>
                </div>
                <div class="p-6">
                    {{-- Grid 5 Kolom --}}
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        
                        @for ($i = 1; $i <= 5; $i++)
                            @php
                                // Tentukan nama kolom di database: image_url, image_url_2, image_url_3, dst.
                                // Asumsi: Kolom di DB bernama product_image_1 s/d product_image_5 atau image_url_1...
                                // Tapi biasanya di Laravel pakai array product_images[].
                                // Di sini saya asumsikan Anda akan handle di controller.
                                // Untuk tampilan, kita pakai logic sederhana.
                                
                                // Ganti logika ini sesuai nama kolom di DB Anda.
                                // Contoh: Jika kolomnya image_url (utama), image_url_2, image_url_3...
                                $imgKey = ($i == 1) ? 'image_url' : 'image_url_' . $i;
                                $existingImg = $product->$imgKey ?? null;
                                $inputId = 'product_image_' . $i;
                            @endphp

                            <div class="relative group">
                                {{-- Label Kecil --}}
                                <span class="absolute top-2 left-2 z-10 bg-black/50 text-white text-[10px] px-2 py-0.5 rounded backdrop-blur-sm">
                                    {{ $i == 1 ? 'Utama' : 'Gbr ' . $i }}
                                </span>

                                {{-- Area Upload (Klik Wrapper) --}}
                                <div id="uploader-{{ $i }}" 
                                     class="relative w-full aspect-square bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl overflow-hidden cursor-pointer hover:border-indigo-500 hover:bg-indigo-50 transition-all flex flex-col items-center justify-center text-center p-2"
                                     onclick="document.getElementById('{{ $inputId }}').click();">
                                    
                                    {{-- Preview Gambar (Anti Pecah + Placeholder Default) --}}
                                    <img id="preview-{{ $i }}" 
                                         src="{{ $existingImg ? asset('public/storage/' . $existingImg) : 'https://tokosancaka.com/storage/uploads/sancaka.png' }}" 
                                         class="w-full h-full object-contain p-1 {{ $existingImg ? '' : 'opacity-50 grayscale' }} transition-all duration-300"
                                         alt="Preview {{ $i }}"
                                         onerror="this.onerror=null; this.src='https://tokosancaka.com/storage/uploads/sancaka.png';">

                                    {{-- Overlay Icon Upload saat Hover --}}
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all flex items-center justify-center">
                                        <i class="fa-solid fa-cloud-arrow-up text-white opacity-0 group-hover:opacity-100 text-2xl drop-shadow-md transform scale-75 group-hover:scale-100 transition-transform"></i>
                                    </div>
                                </div>

                                {{-- Input File Hidden --}}
                                <input type="file" name="{{ $i == 1 ? 'product_image' : 'product_image_' . $i }}" 
                                       id="{{ $inputId }}" class="hidden" accept="image/png, image/jpeg, image/webp"
                                       onchange="previewImage(this, 'preview-{{ $i }}', 'uploader-{{ $i }}')">
                                
                                {{-- Tombol Hapus (Opsional, jika ingin fitur hapus) --}}
                                @if($existingImg)
                                    <button type="button" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center shadow-md hover:bg-red-600 transition transform hover:scale-110 z-20"
                                            onclick="alert('Fitur hapus gambar ke-' + {{ $i }} + ' bisa ditambahkan di controller');">
                                        <i class="fa-solid fa-times text-xs"></i>
                                    </button>
                                @endif
                            </div>
                        @endfor

                    </div>
                    
                    <p class="text-xs text-gray-400 mt-4 text-center">
                        <i class="fa-solid fa-circle-info mr-1"></i> Klik kotak untuk mengunggah. Gambar pertama akan menjadi cover produk.
                    </p>
                    @error('product_image') <p class="mt-2 text-sm text-red-500 font-medium text-center">{{ $message }}</p> @enderror
                </div>
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
        <span class="bg-indigo-100 text-indigo-600 p-1.5 rounded-lg">
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
                    class="currency-input block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors font-semibold text-gray-900 placeholder-gray-300"
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
                    class="currency-input block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-gray-700 placeholder-gray-300"
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
                        class="block w-full pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
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
                        class="block w-full min-w-0 flex-1 rounded-none rounded-l-lg border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-2.5 pl-3 transition-colors"
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
                <i class="fa-solid fa-ruler-combined text-gray-300"></i>
            </label>
            <div class="grid grid-cols-3 gap-4">
                {{-- Panjang --}}
                <div class="relative rounded-lg shadow-sm">
                    <input type="number" name="length" placeholder="0" min="0"
                        class="block w-full pr-8 pl-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        value="{{ old('length', $product->length) }}">
                    <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none">
                        <span class="text-gray-400 text-xs">cm</span>
                    </div>
                    <div class="absolute -top-2 left-2 bg-white px-1 text-[10px] text-gray-400">Panjang</div>
                </div>

                {{-- Lebar --}}
                <div class="relative rounded-lg shadow-sm">
                    <input type="number" name="width" placeholder="0" min="0"
                        class="block w-full pr-8 pl-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        value="{{ old('width', $product->width) }}">
                    <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none">
                        <span class="text-gray-400 text-xs">cm</span>
                    </div>
                    <div class="absolute -top-2 left-2 bg-white px-1 text-[10px] text-gray-400">Lebar</div>
                </div>

                {{-- Tinggi --}}
                <div class="relative rounded-lg shadow-sm">
                    <input type="number" name="height" placeholder="0" min="0"
                        class="block w-full pr-8 pl-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
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

            {{-- DEBUG START --}}
<div class="bg-yellow-100 p-4 mb-4">
    <pre>{{ json_encode($product->productAttributes, JSON_PRETTY_PRINT) }}</pre>
</div>
{{-- DEBUG END --}}

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

 // Seleksi semua input dengan class 'currency-input'
    const currencyInputs = document.querySelectorAll('.currency-input');

    currencyInputs.forEach(function(input) {
        // Format saat halaman pertama kali diload (jika ada error validasi old input)
        input.value = formatRupiah(input.value);

        // Format saat user mengetik
        input.addEventListener('keyup', function(e) {
            input.value = formatRupiah(this.value);
        });
    });

    function formatRupiah(angka, prefix) {
        if (!angka) return '';
        
        // Hapus karakter selain angka
        var number_string = angka.replace(/[^,\d]/g, '').toString(),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        // Tambahkan titik jika yang diinput sudah menjadi ribuan
        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return rupiah;
    }

    // 2. Event Listener: Format Saat Mengetik
    currencyInputs.forEach(input => {
        // A. FORMAT SAAT HALAMAN DIMUAT (Supaya angka dari DB langsung rapi)
        // Ini yang memperbaiki bug "angka mentah"
        input.value = formatRupiah(input.value);

        // B. Format saat mengetik
        input.addEventListener('input', function(e) {
            this.value = formatRupiah(this.value);
        });
    });

    // 3. Bersihkan Titik Sebelum Submit
    const productForm = document.getElementById('product-form');
    if(productForm) {
        productForm.addEventListener('submit', function() {
            currencyInputs.forEach(input => {
                // Hapus titik sebelum kirim ke server
                input.value = input.value.replace(/\./g, '');
            });
        });
    }
});

    // Fungsi Preview Gambar Multi
    function previewImage(input, previewId, uploaderId) {
        const file = input.files[0];
        const preview = document.getElementById(previewId);
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                // Hapus efek grayscale/opacity placeholder saat gambar dipilih
                preview.classList.remove('opacity-50', 'grayscale');
            }
            reader.readAsDataURL(file);
        }
    }

</script>
@endpush