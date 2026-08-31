@extends('layouts.customer') 
@section('title', 'Edit Produk / Jasa')

@push('styles')
<style>
    .image-uploader {
        border: 2px dashed #d1d5db; border-radius: 0.5rem; padding: 2rem;
        text-align: center; cursor: pointer; transition: border-color 0.3s ease;
        background-color: #fafafa;
    }
    .image-uploader:hover, .image-uploader.dragging {
        border-color: #dc2626; background-color: #fee2e2;
    }
    .image-preview {
        margin-top: 1rem; max-width: 100%; max-height: 300px;
        border-radius: 0.5rem; display: none;
    }
    .image-preview.has-image {
        display: block; 
    }
    .spinner {
        display: inline-block; width: 1rem; height: 1rem; vertical-align: text-bottom;
        border: 0.2em solid currentColor; border-right-color: transparent;
        border-radius: 50%; animation: spinner-border .75s linear infinite;
    }
    @keyframes spinner-border { to { transform: rotate(360deg); } }
    .btn {
        padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 600;
        transition: all 0.2s ease-in-out; display: inline-flex; align-items: center;
        justify-content: center; line-height: 1.25;
    }
    input:disabled, textarea:disabled, select:disabled {
        cursor: not-allowed; background-color: #f3f4f6;
    }
</style>
@endpush

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        @if (session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative" role="alert">
                <strong class="font-bold">Gagal Update!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif
        
        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative" role="alert">
                <strong class="font-bold">Oops!</strong>
                <span class="block sm:inline">Ada beberapa masalah dengan input Anda.</span>
                <ul class="mt-2 list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="product-form" action="{{ route('seller.produk.update', $produk->slug) }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- KOLOM KIRI (UTAMA) --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Informasi Produk --}}
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Produk / Jasa</h2>
                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Judul Produk / Layanan Jasa</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $produk->name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('name') border-red-500 @enderror focus:border-red-500 focus:ring-red-500" required>
                            </div>
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                <textarea name="description" id="description" rows="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('description') border-red-500 @enderror focus:border-red-500 focus:ring-red-500" required>{{ old('description', $produk->description) }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Gambar Produk (Utama & Pendukung) --}}
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Gambar / Foto Produk</h2>
                        
                        {{-- 1. GAMBAR UTAMA (WAJIB) --}}
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Gambar Utama (Wajib)</label>
                            <div id="image-uploader" class="image-uploader" tabindex="0">
                                <p class="font-semibold text-red-600">Klik untuk ganti Gambar Utama</p>
                                <p class="text-xs text-gray-500">Abaikan jika tidak ingin mengubah</p>
                            </div>
                            <input type="file" name="product_image" id="product_image" class="hidden" accept="image/png, image/jpeg, image/webp">
                            
                            {{-- Preview Gambar Utama Lama --}}
                            <img id="image-preview" 
                                 src="{{ $produk->image_url ? asset('public/storage/' . $produk->image_url) : '' }}" 
                                 alt="Pratinjau Gambar Utama" 
                                 class="image-preview border-2 border-red-500 p-1 {{ $produk->image_url ? 'has-image' : '' }}" 
                                 onerror="this.style.display='none'; this.src='';"/>
                        </div>

                        <hr class="border-gray-200 mb-6">

                        {{-- 2. GAMBAR PENDUKUNG (MAKS 5) --}}
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Gambar Pendukung (Maks 5 Foto)</label>
                            
                            {{-- Tampilkan Gambar Pendukung Lama Jika Ada --}}
                            @php
                                $existingSupportImages = $produk->supporting_images ? json_decode($produk->supporting_images, true) : [];
                            @endphp
                            
                            @if(!empty($existingSupportImages) && is_array($existingSupportImages))
                                <div class="mb-4 bg-gray-50 p-3 rounded border border-gray-200">
                                    <p class="text-xs font-bold text-gray-600 mb-2">Gambar Pendukung Tersimpan:</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($existingSupportImages as $img)
                                            <img src="{{ asset('public/storage/' . $img) }}" class="w-16 h-16 object-cover rounded border border-gray-300">
                                        @endforeach
                                    </div>
                                    <p class="text-[10px] text-red-500 mt-2 italic">*Jika Anda mengupload gambar baru di bawah, semua gambar lama ini akan ditimpa.</p>
                                </div>
                            @endif

                            <div id="support-image-uploader" class="image-uploader py-6" tabindex="0">
                                <p class="font-semibold text-gray-600"><i class="fas fa-images"></i> Klik / Seret gambar baru ke sini</p>
                                <p class="text-xs text-gray-500 mt-1">Abaikan jika tidak ingin mengubah foto pendukung.</p>
                            </div>
                            <input type="file" name="supporting_images[]" id="supporting_images" class="hidden" accept="image/png, image/jpeg, image/webp" multiple>
                            
                            {{-- Tempat munculnya pratinjau upload baru --}}
                            <div id="support-preview-container" class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-4"></div>
                        </div>
                    </div>

                    {{-- Varian Produk --}}
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold text-gray-800">Varian Produk (Opsional)</h2>
                            <button type="button" id="add-variant-group" class="btn btn-sm btn-outline-primary">Tambah Varian</button>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Ubah varian jika produk/jasa Anda memiliki pilihan (contoh: Ukuran Ruangan, Warna). Ini akan menonaktifkan input stok utama.</p>
                        <div id="variant-groups-container" class="space-y-6"></div>
                    </div>

                    {{-- AREA PRODUK DIGITAL / FILE JASA --}}
                    <div id="digital-asset-container" class="bg-blue-50 p-6 rounded-lg shadow-md border-2 border-blue-200 hidden">
                        <h2 class="text-lg font-extrabold text-blue-800 mb-2"><i class="fas fa-cloud-download-alt mr-2"></i>Aset Produk Digital / File Jasa</h2>
                        <p class="text-sm text-blue-600 mb-4">Karena kategori ini non-fisik, masukkan Link Akses ATAU Upload File E-Ticket baru jika ingin mengubahnya.</p>
                        <div class="space-y-4">
                            <div>
                                <label for="digital_url" class="block text-sm font-medium text-gray-700">Link Akses Eksternal</label>
                                <input type="url" name="digital_url" id="digital_url" value="{{ old('digital_url', $produk->digital_url ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="digital_file" class="block text-sm font-medium text-gray-700">Upload File (Abaikan jika tak ingin ubah)</label>
                                <input type="file" name="digital_file" id="digital_file" accept=".pdf,.zip,.jpg,.jpeg,.png,.webp" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
                                @if(!empty($produk->digital_file_path))
                                    <p class="mt-2 text-xs text-green-600 font-bold"><i class="fas fa-check-circle mr-1"></i>File E-Ticket saat ini sudah tersimpan.</p>
                                @endif
                            </div>
                            <div class="pt-2">
                                <label for="digital_sn_list" class="block text-sm font-medium text-gray-700">Daftar Serial Number / Voucher (Opsional)</label>
                                <textarea name="digital_sn_list" id="digital_sn_list" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('digital_sn_list', $produk->digital_sn_list ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Atribut Dinamis --}}
                    <div id="attributes-card" class="bg-white p-6 rounded-lg shadow-md hidden">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Spesifikasi Detail</h2>
                        <div id="dynamic-attributes-container" class="space-y-4"></div>
                    </div>

                </div>

                {{-- KOLOM KANAN (SIDEBAR) --}}
                <div class="space-y-6">

                    {{-- ⚡ KATEGORI JASA (Selalu Tampil di Atas) ⚡ --}}
                    <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-red-600" id="jasa-asset-container">
                        <h2 class="text-lg font-semibold text-gray-800 mb-2">Kategori Layanan Jasa</h2>
                        <p class="text-xs text-gray-500 mb-4">Pilih ini jika Anda menawarkan tenaga/jasa (bukan barang fisik).</p>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="id_bidang" class="block text-sm font-medium text-gray-700">Divisi Bidang</label>
                                <select id="id_bidang" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" onchange="loadSubBidang(this.value)">
                                    <option value="" selected>-- Bukan Jasa (Jual Barang Fisik) --</option>
                                    @if(isset($bidangs))
                                        @foreach($bidangs as $bidang)
                                            {{-- Tidak pre-selected default, user harus set ulang jika edit kategori jasa untuk memastikan hirarki AJAX berjalan --}}
                                            <option value="{{ $bidang->id }}">{{ $bidang->nama_bidang }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div id="sub-bidang-wrapper" class="hidden">
                                <label for="id_sub_bidang" class="block text-sm font-medium text-gray-700">Sub Bidang Kategori</label>
                                <select id="id_sub_bidang" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" onchange="loadLayanan(this.value)">
                                    <option value="" selected disabled>-- Pilih Sub Bidang --</option>
                                </select>
                            </div>
                            <div id="layanan-wrapper" class="hidden">
                                <label for="id_master_layanan" class="block text-sm font-medium text-gray-700">Pilih Layanan</label>
                                <select name="id_master_layanan" id="id_master_layanan" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500">
                                    <option value="" selected disabled>-- Pilih Layanan --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Harga & Stok --}}
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Harga & Stok</h2>
                        <div class="space-y-4">
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-700">Harga / Tarif Jasa</label>
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rp</span>
                                    <input type="number" name="price" id="price" value="{{ old('price', $produk->price) }}" class="pl-8 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" required>
                                </div>
                            </div>
                            <div>
                                <label for="original_price" class="block text-sm font-medium text-gray-700">Harga Asli (Harga Coret)</label>
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rp</span>
                                    <input type="number" name="original_price" id="original_price" value="{{ old('original_price', $produk->original_price) }}" class="pl-8 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500">
                                </div>
                            </div>
                            <div>
                                <label for="stock" class="block text-sm font-medium text-gray-700">Stok / Kapasitas</label>
                                <input type="number" name="stock" id="stock" value="{{ old('stock', $produk->stock) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" required>
                            </div>

                            {{-- ⚡ AREA FISIK (BERAT & DIMENSI) BISA DISEMBUNYIKAN ⚡ --}}
                            <div id="weight-container">
                                <label for="weight" class="block text-sm font-medium text-gray-700">Berat Barang</label>
                                <div class="relative mt-1">
                                    <input type="number" name="weight" id="weight" value="{{ old('weight', $produk->weight) }}" class="pr-12 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" required>
                                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500">gram</span>
                                </div>
                            </div>
                            <div id="dimensi-container">
                                <label class="block text-sm font-medium text-gray-700">Dimensi Paket (Opsional)</label>
                                <div class="grid grid-cols-3 gap-4 mt-1">
                                    <div>
                                        <input type="number" name="length" id="length" value="{{ old('length', $produk->length) }}" placeholder="P (cm)" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                    </div>
                                    <div>
                                        <input type="number" name="width" id="width" value="{{ old('width', $produk->width) }}" placeholder="L (cm)" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                    </div>
                                    <div>
                                        <input type="number" name="height" id="height" value="{{ old('height', $produk->height) }}" placeholder="T (cm)" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Organisasi Produk --}}
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Organisasi</h2>
                        <div class="space-y-4">
                            <div>
                                <label for="sku" class="block text-sm font-medium text-gray-700">SKU / Kode Unik</label>
                                <input type="text" name="sku" id="sku" value="{{ old('sku', $produk->sku) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500">
                            </div>

                            {{-- ⚡ KATEGORI BARANG (BISA DISEMBUNYIKAN) ⚡ --}}
                            <div id="kategori-container">
                                <label for="category_id" class="block text-sm font-medium text-gray-700">Kategori Barang</label>
                                <select name="category_id" id="category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" 
                                                data-attributes-url="{{ route('seller.categories.attributes', $category->id) }}"
                                                @selected(old('category_id', $produk->category_id) == $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="tags" class="block text-sm font-medium text-gray-700">Tags (pisahkan koma)</label>
                                @php
                                    $tags = old('tags', $produk->tags);
                                    if (is_string($tags)) {
                                        $decodedTags = json_decode($tags, true);
                                        $tags = is_array($decodedTags) ? implode(', ', $decodedTags) : $tags;
                                    } elseif (is_array($tags)) {
                                        $tags = implode(', ', $tags);
                                    }
                                @endphp
                                <input type="text" name="tags" id="tags" value="{{ $tags }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500">
                            </div>
                        </div>
                    </div>

                    {{-- Status & Label --}}
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Status & Label</h2>
                        <div class="space-y-4">
                            <div>
                                <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" required>
                                    <option value="active" @selected(old('status', $produk->status) == 'active')>Aktif (Tersedia)</option>
                                    <option value="inactive" @selected(old('status', $produk->status) == 'inactive')>Nonaktif (Disimpan)</option>
                                </select>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="is_new" id="is_new" value="1" @checked(old('is_new', $produk->is_new)) class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                <label for="is_new" class="ml-2 block text-sm text-gray-900">Tandai sebagai Baru</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="is_bestseller" id="is_bestseller" value="1" @checked(old('is_bestseller', $produk->is_bestseller)) class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                <label for="is_bestseller" class="ml-2 block text-sm text-gray-900">Tandai sebagai Bestseller</label>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Tombol Aksi --}}
                    <div class="bg-white p-6 rounded-lg shadow-md flex justify-end gap-3">
                        <a href="{{ route('seller.produk.index') }}" class="px-5 py-2.5 bg-red-100 text-red-700 font-medium rounded-lg hover:bg-red-200 transition">
                            Batal
                        </a>
                        <button id="submit-button" type="submit" class="px-5 py-2.5 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                            Update Produk
                        </button>
                    </div>

                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Injeksi data JSON dari Controller untuk Atribut dan Varian existing
    const existingAttributes = {!! $existing_attributes_json ?? '{}' !!};
    const existingVariantTypes = {!! $existing_variant_types_json ?? '[]' !!};
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {

    // ==========================================
    // 1. UPLOADER GAMBAR UTAMA (1 FOTO)
    // ==========================================
    const uploader = document.getElementById('image-uploader');
    const input = document.getElementById('product_image');
    const preview = document.getElementById('image-preview');
    if (uploader && input && preview) {
        uploader.addEventListener('click', () => input.click());
        input.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                    preview.classList.add('has-image');
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // ==========================================
    // 2. UPLOADER GAMBAR PENDUKUNG (MAKS 5 FOTO)
    // ==========================================
    const supportUploader = document.getElementById('support-image-uploader');
    const supportInput = document.getElementById('supporting_images');
    const supportPreviewContainer = document.getElementById('support-preview-container');
    let supportingFiles = []; 

    if (supportUploader && supportInput && supportPreviewContainer) {
        supportUploader.addEventListener('click', () => supportInput.click());
        supportUploader.addEventListener('dragover', (e) => { e.preventDefault(); supportUploader.classList.add('dragging'); });
        supportUploader.addEventListener('dragleave', () => supportUploader.classList.remove('dragging'));
        supportUploader.addEventListener('drop', (e) => {
            e.preventDefault(); supportUploader.classList.remove('dragging');
            handleSupportFiles(e.dataTransfer.files);
        });

        supportInput.addEventListener('change', (e) => {
            handleSupportFiles(e.target.files);
        });

        function handleSupportFiles(newFiles) {
            Array.from(newFiles).forEach(file => {
                if (!file.type.match('image.*')) return;
                if (supportingFiles.length < 5) {
                    supportingFiles.push(file);
                } else {
                    alert('Maksimal hanya 5 gambar pendukung!');
                }
            });
            renderSupportPreviews();
            syncSupportInput();
        }

        function renderSupportPreviews() {
            supportPreviewContainer.innerHTML = '';
            supportingFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.className = 'relative group rounded-md border border-gray-200 overflow-hidden shadow-sm aspect-square bg-gray-50';
                    div.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-full object-cover">
                        <button type="button" class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity" onclick="removeSupportImage(${index})" title="Hapus Gambar">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    supportPreviewContainer.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }

        window.removeSupportImage = function(index) {
            supportingFiles.splice(index, 1);
            renderSupportPreviews();
            syncSupportInput();
        }

        function syncSupportInput() {
            const dataTransfer = new DataTransfer();
            supportingFiles.forEach(file => dataTransfer.items.add(file));
            supportInput.files = dataTransfer.files;
        }
    }


    // ==========================================
    // 3. LOGIKA CERDAS: BERALIH ANTARA PRODUK FISIK & JASA
    // ==========================================
    const bidangSelect = document.getElementById('id_bidang');
    const subBidangWrapper = document.getElementById('sub-bidang-wrapper');
    const layananWrapper = document.getElementById('layanan-wrapper');
    const categorySelect = document.getElementById('category_id');
    const weightContainer = document.getElementById('weight-container');
    const dimensiContainer = document.getElementById('dimensi-container');
    const kategoriContainer = document.getElementById('kategori-container');
    const weightInput = document.getElementById('weight');
    const digitalContainer = document.getElementById('digital-asset-container');

    function toggleJasaConstraints() {
        if (bidangSelect && bidangSelect.value !== '') {
            if(weightContainer) weightContainer.style.display = 'none';
            if(dimensiContainer) dimensiContainer.style.display = 'none';
            if(kategoriContainer) kategoriContainer.style.display = 'none';
            
            if(weightInput) weightInput.removeAttribute('required');
            if(categorySelect) {
                categorySelect.removeAttribute('required');
            }

            if(digitalContainer) digitalContainer.classList.remove('hidden');
            if(subBidangWrapper) subBidangWrapper.classList.remove('hidden');
            if(layananWrapper) layananWrapper.classList.remove('hidden');

        } else {
            if(weightContainer) weightContainer.style.display = 'block';
            if(dimensiContainer) dimensiContainer.style.display = 'block';
            if(kategoriContainer) kategoriContainer.style.display = 'block';
            
            if(weightInput) weightInput.setAttribute('required', 'required');
            if(categorySelect) categorySelect.setAttribute('required', 'required');

            if(digitalContainer) digitalContainer.classList.add('hidden');
            if(subBidangWrapper) subBidangWrapper.classList.add('hidden');
            if(layananWrapper) layananWrapper.classList.add('hidden');
        }
    }

    // Pengecekan Khusus Halaman Edit (Jika Kategori Kosong, asumsikan ini Jasa)
    if (categorySelect && categorySelect.value === "") {
        // Trigger manual ke mode jasa di UI
        if(digitalContainer) digitalContainer.classList.remove('hidden');
        if(weightContainer) weightContainer.style.display = 'none';
        if(dimensiContainer) dimensiContainer.style.display = 'none';
        if(kategoriContainer) kategoriContainer.style.display = 'none';
        if(weightInput) weightInput.removeAttribute('required');
        categorySelect.removeAttribute('required');
    }

    if (bidangSelect) {
        bidangSelect.addEventListener('change', toggleJasaConstraints);
    }


    // ==========================================
    // 4. AJAX DROPDOWN JASA
    // ==========================================
    window.loadSubBidang = function(id_bidang) {
        if (!id_bidang) return;
        const subBidangSelect = document.getElementById('id_sub_bidang');
        const layananSelect = document.getElementById('id_master_layanan');
        subBidangSelect.innerHTML = '<option value="">Memuat...</option>';
        layananSelect.innerHTML = '<option value="" selected disabled>-- Pilih Layanan --</option>';

        fetch(`/get-sub-bidang/${id_bidang}`)
            .then(res => res.json())
            .then(data => {
                let html = '<option value="" selected disabled>-- Pilih Sub Bidang --</option>';
                data.forEach(item => { html += `<option value="${item.id}">${item.nama_sub_bidang}</option>`; });
                subBidangSelect.innerHTML = html;
            });
    };

    window.loadLayanan = function(id_sub_bidang) {
        if (!id_sub_bidang) return;
        const layananSelect = document.getElementById('id_master_layanan');
        layananSelect.innerHTML = '<option value="">Memuat...</option>';

        fetch(`/get-layanan/${id_sub_bidang}`)
            .then(res => res.json())
            .then(data => {
                let html = '<option value="" selected disabled>-- Pilih Layanan --</option>';
                data.forEach(item => { html += `<option value="${item.id}">${item.nama_layanan} (Rp${parseInt(item.tarif_dasar).toLocaleString('id-ID')} / ${item.tipe_satuan})</option>`; });
                layananSelect.innerHTML = html;
            });
    };

    // ==========================================
    // 5. ATRIBUT DINAMIS DARI KATEGORI
    // ==========================================
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
            if (!response.ok) throw new Error(`Gagal memuat atribut`);
            const attributes = await response.json();
            attributesContainer.innerHTML = '';
            
            if (attributes && attributes.length > 0) { 
                attributes.forEach(attr => {
                    if (typeof attr === 'object' && attr !== null && attr.slug) {
                        const existingValue = existingAttributes[attr.slug] || null;
                        const field = createAttributeField(attr, existingValue);
                        attributesContainer.appendChild(field);
                    }
                });
            } else {
                attributesContainer.innerHTML = '<p class="text-gray-500">Tidak ada spesifikasi tambahan untuk kategori ini.</p>';
            }
        } catch (error) {
            console.error('Error fetching attributes:', error);
            attributesContainer.innerHTML = `<p class="text-red-500">Gagal memuat spesifikasi.</p>`;
        }
    }

    function createAttributeField(attribute, value = null) {
        const wrapper = document.createElement('div');
        let fieldHtml = '';
        const isRequired = attribute.is_required ? 'required' : '';
        const requiredAsterisk = attribute.is_required ? '<span class="text-red-500">*</span>' : '';
        const attributeName = attribute.name || 'Atribut';
        const label = `<label for="attr_${attribute.slug}" class="block text-sm font-medium text-gray-700">${attributeName} ${requiredAsterisk}</label>`;
        const inputName = `attributes[${attribute.slug}]`;
        const optionsString = typeof attribute.options === 'string' ? attribute.options : '';
        const val = value !== null ? value : '';

        switch (attribute.type) {
            case 'number':
            case 'text':
                fieldHtml = `${label}<input type="${attribute.type}" name="${inputName}" id="attr_${attribute.slug}" value="${val}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired}>`;
                break;
            case 'textarea':
                fieldHtml = `${label}<textarea name="${inputName}" id="attr_${attribute.slug}" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired}>${val}</textarea>`;
                break;
            case 'select':
                const options = optionsString.split(',').map(opt => opt.trim()).filter(opt => opt).map(opt => `<option value="${opt}" ${val == opt ? 'selected' : ''}>${opt}</option>`).join('');
                fieldHtml = `${label}<select name="${inputName}" id="attr_${attribute.slug}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired}><option value="">-- Pilih ${attributeName} --</option>${options}</select>`;
                break;
            case 'checkbox':
                const valueArray = Array.isArray(val) ? val : (val ? [val] : []);
                const checkboxes = optionsString.split(',').map(opt => opt.trim()).filter(opt => opt).map((opt, index) => `
                    <div class="flex items-center">
                        <input type="checkbox" name="${inputName}[]" id="attr_${attribute.slug}_${index}" value="${opt}" ${valueArray.includes(opt) ? 'checked' : ''} class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        <label for="attr_${attribute.slug}_${index}" class="ml-2 block text-sm text-gray-900">${opt}</label>
                    </div>`).join('');
                fieldHtml = `<label class="block text-sm font-medium text-gray-700">${attributeName} ${requiredAsterisk}</label><div class="mt-2 space-y-2">${checkboxes}</div>`;
                break;
            default:
                fieldHtml = `${label}<input type="text" name="${inputName}" id="attr_${attribute.slug}" value="${val}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired}>`;
        }
        wrapper.innerHTML = fieldHtml;
        return wrapper;
    }

    if (categorySelect) { 
        categorySelect.addEventListener('change', fetchAndRenderAttributes);
        if(categorySelect.value) { fetchAndRenderAttributes(); }
    }


    // ==========================================
    // 6. VARIAN PRODUK DINAMIS (Load Data Lama)
    // ==========================================
    const variantContainer = document.getElementById('variant-groups-container');
    const addVariantBtn = document.getElementById('add-variant-group');
    const mainStockInput = document.getElementById('stock');
    let variantIndex = 0; 

    if (addVariantBtn && variantContainer && mainStockInput) {
        addVariantBtn.addEventListener('click', (e) => {
            e.preventDefault(); 
            variantContainer.appendChild(createVariantGroup(variantIndex));
            variantIndex++;
            toggleMainStock();
        });
    }

    function createVariantGroup(index, name = '', options = '') {
        const groupWrapper = document.createElement('div');
        groupWrapper.classList.add('border', 'rounded-md', 'p-4', 'space-y-3', 'bg-gray-50');
        groupWrapper.innerHTML = `
            <div class="flex justify-between items-center">
                <h3 class="font-semibold text-gray-700">Tipe Varian #${index + 1}</h3>
                <button type="button" class="text-red-500 hover:text-red-700 remove-variant-group" title="Hapus Tipe Varian">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </div>
            <div>
                <label for="variant_${index}_name" class="block text-sm font-medium text-gray-700">Nama Tipe Varian</label>
                <input type="text" name="variant_types[${index}][name]" id="variant_${index}_name" value="${name}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: Warna, Ukuran" required>
            </div>
            <div>
                <label for="variant_${index}_options" class="block text-sm font-medium text-gray-700">Pilihan Varian (pisahkan koma)</label>
                <input type="text" name="variant_types[${index}][options]" id="variant_${index}_options" value="${options}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: Merah, Biru, Hijau" required>
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
        const existingWarning = document.getElementById(warningId);
        if (existingWarning) existingWarning.remove();

        if (variantContainer && variantContainer.children.length > 0) { 
            mainStockInput.disabled = true;
            if (mainStockInput.parentElement) {
                mainStockInput.parentElement.insertAdjacentHTML('afterend', `
                    <p id="${warningId}" class="mt-1 text-xs text-indigo-600">
                        Stok utama dinonaktifkan. Stok akan diatur dari total varian.
                    </p>
                `);
            }
        } else {
            mainStockInput.disabled = false;
        }
    }
    
    function loadExistingVariants() {
        if (existingVariantTypes && existingVariantTypes.length > 0) {
            existingVariantTypes.forEach((variant) => {
                variantContainer.appendChild(createVariantGroup(variantIndex, variant.name, variant.options));
                variantIndex++;
            });
            toggleMainStock(); 
        }
    }

    // 7. Form Submission Loading Spinner
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
            submitButton.innerHTML = `<span class="spinner" role="status" aria-hidden="true"></span> Mengupdate...`;
        });
    }

    // Initialize Edit Data
    loadExistingVariants();
    toggleMainStock(); 

});
</script>
@endpush