@extends('layouts.customer') 

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
    .spinner {
        display: inline-block; width: 1rem; height: 1rem; vertical-align: text-bottom;
        border: 0.2em solid currentColor; border-right-color: transparent;
        border-radius: 50%; animation: spinner-border .75s linear infinite;
    }
    @keyframes spinner-border { to { transform: rotate(360deg); } }
    .btn {
        padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 600;
        transition: all 0.2s ease-in-out; display: inline-flex;
        align-items: center; justify-content: center; line-height: 1.25;
    }
    .btn-primary { background-color: #dc2626; color: white; border: 1px solid transparent; }
    .btn-primary:hover { background-color: #b91c1c; }
    .btn-secondary { background-color: #e5e7eb; color: #374151; border: 1px solid #d1d5db; }
    .btn-secondary:hover { background-color: #d1d5db; }
    .btn-outline-primary { background-color: transparent; color: #dc2626; border: 1px solid #dc2626; }
    .btn-outline-primary:hover { background-color: #fee2e2; }
    .btn-sm { padding: 0.25rem 0.75rem; font-size: 0.875rem; }
    input:disabled, textarea:disabled, select:disabled {
        cursor: not-allowed; background-color: #f3f4f6;
    }
</style>
@endpush

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative" role="alert">
                <strong class="font-bold">Oops!</strong>
                <span class="block sm:inline">Ada beberapa masalah dengan input Anda. Silakan periksa form di bawah.</span>
                <ul class="mt-2 list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="product-form" action="{{ route('seller.produk.store') }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- KOLOM KIRI (UTAMA) --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Informasi Produk --}}
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Produk / Jasa</h2>
                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Judul Produk / Layanan Jasa</label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('name') border-red-500 @enderror focus:border-red-500 focus:ring-red-500" required>
                            </div>
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                <textarea name="description" id="description" rows="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('description') border-red-500 @enderror focus:border-red-500 focus:ring-red-500" required>{{ old('description') }}</textarea>
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
                                <p class="font-semibold text-red-600">Klik untuk upload Gambar Utama</p>
                                <p class="text-xs text-gray-500">PNG, JPG, WEBP hingga 2MB</p>
                            </div>
                            <input type="file" name="product_image" id="product_image" class="hidden" accept="image/png, image/jpeg, image/webp" required>
                            <img id="image-preview" alt="Pratinjau Gambar Utama" class="image-preview border-2 border-red-500 p-1" />
                            @error('product_image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <hr class="border-gray-200 mb-6">

                        {{-- 2. GAMBAR PENDUKUNG (MAKS 5) --}}
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Gambar Pendukung (Maks 5 Foto)</label>
                            <div id="support-image-uploader" class="image-uploader py-6" tabindex="0">
                                <p class="font-semibold text-gray-600"><i class="fas fa-images"></i> Klik / Seret hingga 5 gambar pendukung</p>
                                <p class="text-xs text-gray-500 mt-1">Gunakan foto dari sisi lain atau detail produk.</p>
                            </div>
                            <input type="file" name="supporting_images[]" id="supporting_images" class="hidden" accept="image/png, image/jpeg, image/webp" multiple>
                            
                            {{-- Tempat munculnya pratinjau 5 gambar --}}
                            <div id="support-preview-container" class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-4"></div>
                            @error('supporting_images.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Varian Produk --}}
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold text-gray-800">Varian Produk (Opsional)</h2>
                            <button type="button" id="add-variant-group" class="btn btn-sm btn-outline-primary">Tambah Varian</button>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Tambahkan varian jika produk/jasa Anda memiliki pilihan (contoh: Ukuran Ruangan, Warna). Ini akan menonaktifkan input stok utama.</p>
                        <div id="variant-groups-container" class="space-y-6"></div>
                    </div>

                    {{-- Aset Digital --}}
                    <div id="digital-asset-container" class="bg-blue-50 p-6 rounded-lg shadow-md border-2 border-blue-200 hidden">
                        <h2 class="text-lg font-extrabold text-blue-800 mb-2"><i class="fas fa-cloud-download-alt mr-2"></i>Aset Produk Digital / File Jasa</h2>
                        <p class="text-sm text-blue-600 mb-4">Opsional: Jika ada panduan, e-ticket, atau file yang ingin dikirimkan ke pelanggan.</p>
                        <div class="space-y-4">
                            <div>
                                <label for="digital_url" class="block text-sm font-medium text-gray-700">Link Akses Eksternal</label>
                                <input type="url" name="digital_url" id="digital_url" value="{{ old('digital_url') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="digital_file" class="block text-sm font-medium text-gray-700">Upload File Pendukung (PDF, ZIP, dll)</label>
                                <input type="file" name="digital_file" id="digital_file" accept=".pdf,.zip,.jpg,.jpeg,.png,.webp" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
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
                                            <option value="{{ $bidang->id }}" {{ old('id_bidang') == $bidang->id ? 'selected' : '' }}>{{ $bidang->nama_bidang }}</option>
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

                    {{-- Harga, Stok & Pengiriman --}}
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Harga & Stok</h2>
                        <div class="space-y-4">
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-700">Harga / Tarif Jasa</label>
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rp</span>
                                    <input type="number" name="price" id="price" value="{{ old('price') }}" class="pl-8 block w-full border-gray-300 rounded-md shadow-sm @error('price') border-red-500 @enderror focus:border-red-500 focus:ring-red-500" required>
                                </div>
                            </div>
                            <div>
                                <label for="original_price" class="block text-sm font-medium text-gray-700">Harga Asli (Harga Coret)</label>
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rp</span>
                                    <input type="number" name="original_price" id="original_price" value="{{ old('original_price') }}" class="pl-8 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500">
                                </div>
                            </div>
                            <div>
                                <label for="stock" class="block text-sm font-medium text-gray-700">Stok / Kapasitas</label>
                                <input type="number" name="stock" id="stock" value="{{ old('stock', 1) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" required>
                            </div>

                            {{-- ⚡ AREA FISIK (BERAT & DIMENSI) BISA DISEMBUNYIKAN ⚡ --}}
                            <div id="weight-container">
                                <label for="weight" class="block text-sm font-medium text-gray-700">Berat Barang</label>
                                <div class="relative mt-1">
                                    <input type="number" name="weight" id="weight" value="{{ old('weight') }}" class="pr-12 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" required>
                                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500">gram</span>
                                </div>
                            </div>
                            <div id="dimensi-container">
                                <label class="block text-sm font-medium text-gray-700">Dimensi Paket (Opsional)</label>
                                <div class="grid grid-cols-3 gap-4 mt-1">
                                    <div>
                                        <input type="number" name="length" id="length" value="{{ old('length') }}" placeholder="P (cm)" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                    </div>
                                    <div>
                                        <input type="number" name="width" id="width" value="{{ old('width') }}" placeholder="L (cm)" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                    </div>
                                    <div>
                                        <input type="number" name="height" id="height" value="{{ old('height') }}" placeholder="T (cm)" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
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
                                <input type="text" name="sku" id="sku" value="{{ old('sku') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" placeholder="Otomatis jika kosong">
                            </div>

                            {{-- ⚡ KATEGORI BARANG (BISA DISEMBUNYIKAN) ⚡ --}}
                            <div id="kategori-container">
                                <label for="category_id" class="block text-sm font-medium text-gray-700">Kategori Barang</label>
                                <select name="category_id" id="category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" required>
                                    <option value="">-- Pilih Kategori Barang --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" data-attributes-url="{{ route('seller.categories.attributes', $category->id) }}">
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="tags" class="block text-sm font-medium text-gray-700">Tags (pisahkan koma)</label>
                                <input type="text" name="tags" id="tags" value="{{ old('tags') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Otomatis dari kategori jika kosong">
                            </div>
                        </div>
                    </div>

                    {{-- Status & Label --}}
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Status & Label</h2>
                        <div class="space-y-4">
                            <div>
                                <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                    <option value="active" selected>Aktif (Tersedia)</option>
                                    <option value="inactive">Nonaktif (Disimpan)</option>
                                </select>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="is_new" id="is_new" value="1" class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                <label for="is_new" class="ml-2 block text-sm text-gray-900">Tandai sebagai Baru</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="is_bestseller" id="is_bestseller" value="1" class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                <label for="is_bestseller" class="ml-2 block text-sm text-gray-900">Tandai sebagai Bestseller</label>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Tombol Aksi --}}
                    <div class="bg-white p-6 rounded-lg shadow-md flex flex-col gap-3">
                        <button id="submit-button" type="submit" class="w-full px-5 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition flex items-center justify-center gap-2">
                            Simpan Data
                        </button>
                        <a href="{{ route('seller.produk.index') }}" class="w-full px-5 py-3 bg-red-100 text-red-700 font-bold rounded-lg hover:bg-red-200 transition text-center">
                            Batal
                        </a>
                    </div>

                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
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
    let supportingFiles = []; // Array penampung file asli

    if (supportUploader && supportInput && supportPreviewContainer) {
        
        // Buka dialog file saat kotak diklik
        supportUploader.addEventListener('click', () => supportInput.click());
        
        // Efek Drag & Drop
        supportUploader.addEventListener('dragover', (e) => { e.preventDefault(); supportUploader.classList.add('dragging'); });
        supportUploader.addEventListener('dragleave', () => supportUploader.classList.remove('dragging'));
        supportUploader.addEventListener('drop', (e) => {
            e.preventDefault(); supportUploader.classList.remove('dragging');
            handleSupportFiles(e.dataTransfer.files);
        });

        // Tangkap file dari input dialog
        supportInput.addEventListener('change', (e) => {
            handleSupportFiles(e.target.files);
        });

        function handleSupportFiles(newFiles) {
            Array.from(newFiles).forEach(file => {
                // Hanya izinkan format gambar
                if (!file.type.match('image.*')) return;
                
                // Cek batas maksimum 5 gambar
                if (supportingFiles.length < 5) {
                    supportingFiles.push(file);
                } else {
                    alert('Maksimal hanya 5 gambar pendukung!');
                }
            });
            renderSupportPreviews();
            syncSupportInput();
        }

        // Tampilkan pratinjau gambar (thumbnail)
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

        // Fungsi hapus gambar dari list
        window.removeSupportImage = function(index) {
            supportingFiles.splice(index, 1);
            renderSupportPreviews();
            syncSupportInput();
        }

        // Sinkronisasi array JS ke Input HTML untuk dikirim ke Backend Laravel
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
            // ⚡ MODE JASA: Sembunyikan Kategori, Berat, Dimensi
            if(weightContainer) weightContainer.style.display = 'none';
            if(dimensiContainer) dimensiContainer.style.display = 'none';
            if(kategoriContainer) kategoriContainer.style.display = 'none';
            
            // Hapus Wajib (Required)
            if(weightInput) weightInput.removeAttribute('required');
            if(categorySelect) {
                categorySelect.removeAttribute('required');
                categorySelect.value = ''; // Reset pilihan kategori
            }

            if(digitalContainer) digitalContainer.classList.remove('hidden');
            if(subBidangWrapper) subBidangWrapper.classList.remove('hidden');
            if(layananWrapper) layananWrapper.classList.remove('hidden');

        } else {
            // ⚡ MODE BARANG FISIK: Munculkan Kategori, Berat, Dimensi
            if(weightContainer) weightContainer.style.display = 'block';
            if(dimensiContainer) dimensiContainer.style.display = 'block';
            if(kategoriContainer) kategoriContainer.style.display = 'block';
            
            // Kembalikan Wajib (Required)
            if(weightInput) weightInput.setAttribute('required', 'required');
            if(categorySelect) categorySelect.setAttribute('required', 'required');

            if(digitalContainer) digitalContainer.classList.add('hidden');
            if(subBidangWrapper) subBidangWrapper.classList.add('hidden');
            if(layananWrapper) layananWrapper.classList.add('hidden');
        }
    }

    if (bidangSelect) {
        bidangSelect.addEventListener('change', toggleJasaConstraints);
        toggleJasaConstraints(); 
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
    // 5. VARIAN PRODUK DINAMIS
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

    function createVariantGroup(index) {
        const groupWrapper = document.createElement('div');
        groupWrapper.classList.add('border', 'rounded-md', 'p-4', 'space-y-3', 'bg-gray-50');
        groupWrapper.innerHTML = `
            <div class="flex justify-between items-center">
                <h3 class="font-semibold text-gray-700">Tipe Varian #${index + 1}</h3>
                <button type="button" class="text-red-500 hover:text-red-700 remove-variant-group">Hapus</button>
            </div>
            <div>
                <input type="text" name="variant_types[${index}][name]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Nama Tipe (Warna, Ukuran)" required>
            </div>
            <div>
                <input type="text" name="variant_types[${index}][options]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Opsi (Merah, Biru) - Pisahkan koma" required>
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
            mainStockInput.value = '0';
            if (mainStockInput.parentElement) {
                mainStockInput.parentElement.insertAdjacentHTML('afterend', `<p id="${warningId}" class="mt-1 text-xs text-red-600">Stok diatur via varian.</p>`);
            }
        } else {
            mainStockInput.disabled = false;
        }
    }

});
</script>
@endpush