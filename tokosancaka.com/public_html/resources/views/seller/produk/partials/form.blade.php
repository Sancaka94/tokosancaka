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
            </div>
        @endif

        <form id="product-form" action="{{ route('seller.produk.store') }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- ========================================== --}}
                {{-- KOLOM KIRI (UTAMA) --}}
                {{-- ========================================== --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Informasi Produk --}}
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Produk</h2>
                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Nama Produk</label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('name') border-red-500 @enderror focus:border-red-500 focus:ring-red-500" required>
                                @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                <textarea name="description" id="description" rows="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('description') border-red-500 @enderror focus:border-red-500 focus:ring-red-500">{{ old('description') }}</textarea>
                                @error('description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Gambar Produk --}}
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Gambar Utama Produk</h2>
                        <div id="image-uploader" class="image-uploader" tabindex="0">
                            <p class="font-semibold text-red-600">Klik untuk upload</p>
                            <p class="text-xs text-gray-500">atau seret file ke sini (PNG, JPG, WEBP hingga 2MB)</p>
                        </div>
                        <input type="file" name="product_image" id="product_image" class="hidden" accept="image/png, image/jpeg, image/webp" required>
                        <img id="image-preview" alt="Pratinjau Gambar" class="image-preview" />
                        @error('product_image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Varian Produk --}}
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold text-gray-800">Varian Produk (Opsional)</h2>
                            <button type="button" id="add-variant-group" class="btn btn-sm btn-outline-primary">Tambah Varian</button>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Tambahkan varian jika produk Anda memiliki pilihan seperti warna atau ukuran. Ini akan menonaktifkan input stok utama.</p>
                        <div id="variant-groups-container" class="space-y-6"></div>
                    </div>

                    {{-- AREA PRODUK DIGITAL (Tampil Dinamis) --}}
                    <div id="digital-asset-container" class="bg-blue-50 p-6 rounded-lg shadow-md border-2 border-blue-200 hidden">
                        <h2 class="text-lg font-extrabold text-blue-800 mb-2"><i class="fas fa-cloud-download-alt mr-2"></i>Aset Produk Digital / Jasa</h2>
                        <p class="text-sm text-blue-600 mb-4">Karena kategori ini adalah produk non-fisik, silakan masukkan Link Akses ATAU Upload File E-Ticket.</p>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="digital_url" class="block text-sm font-medium text-gray-700">Link Akses Eksternal</label>
                                <input type="url" name="digital_url" id="digital_url" value="{{ old('digital_url') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Contoh: https://drive.google.com/...">
                            </div>
                            
                            <div class="relative py-2">
                                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                    <div class="w-full border-t border-blue-200"></div>
                                </div>
                                <div class="relative flex justify-center">
                                    <span class="px-2 bg-blue-50 text-xs font-bold text-blue-400">ATAU UPLOAD FILE</span>
                                </div>
                            </div>

                            <div>
                                <label for="digital_file" class="block text-sm font-medium text-gray-700">Upload File (E-Ticket, Dokumen, ZIP)</label>
                                <input type="file" name="digital_file" id="digital_file" accept=".pdf,.zip,.jpg,.jpeg,.png,.webp" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
                                <p class="mt-1 text-xs text-gray-500">Maksimal 5MB (Format: PDF, ZIP, JPG, PNG).</p>
                            </div>

                            <div class="pt-2">
                                <label for="digital_sn_list" class="block text-sm font-medium text-gray-700">Daftar Serial Number / Voucher (Opsional)</label>
                                <textarea name="digital_sn_list" id="digital_sn_list" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="SN-123, SN-456, SN-789 (Pisahkan dengan koma)">{{ old('digital_sn_list') }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Jika pembeli akan menerima kode unik satu per satu.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Card untuk Atribut Dinamis / Spesifikasi --}}
                    <div id="attributes-card" class="bg-white p-6 rounded-lg shadow-md hidden">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Spesifikasi Produk</h2>
                        <div id="dynamic-attributes-container" class="space-y-4"></div>
                    </div>

                </div>

                {{-- ========================================== --}}
                {{-- KOLOM KANAN (SIDEBAR) --}}
                {{-- ========================================== --}}
                <div class="space-y-6">

                    {{-- Harga, Stok & Pengiriman --}}
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Harga, Stok & Pengiriman</h2>
                        <div class="space-y-4">
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-700">Harga Jual</label>
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rp</span>
                                    <input type="number" name="price" id="price" value="{{ old('price') }}" class="pl-8 block w-full border-gray-300 rounded-md shadow-sm @error('price') border-red-500 @enderror focus:border-red-500 focus:ring-red-500" placeholder="100000" required>
                                </div>
                                @error('price') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="original_price" class="block text-sm font-medium text-gray-700">Harga Asli (Harga Coret)</label>
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rp</span>
                                    <input type="number" name="original_price" id="original_price" value="{{ old('original_price') }}" class="pl-8 block w-full border-gray-300 rounded-md shadow-sm @error('original_price') border-red-500 @enderror focus:border-red-500 focus:ring-red-500" placeholder="120000">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Opsional. Isi untuk menampilkan diskon.</p>
                                @error('original_price') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="stock" class="block text-sm font-medium text-gray-700">Stok</label>
                                <input type="number" name="stock" id="stock" value="{{ old('stock', 0) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('stock') border-red-500 @enderror focus:border-red-500 focus:ring-red-500" required>
                                @error('stock') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="weight" class="block text-sm font-medium text-gray-700">Berat</label>
                                <div class="relative mt-1">
                                    <input type="number" name="weight" id="weight" value="{{ old('weight') }}" class="pr-12 block w-full border-gray-300 rounded-md shadow-sm @error('weight') border-red-500 @enderror focus:border-red-500 focus:ring-red-500" placeholder="100" required>
                                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500">gram</span>
                                </div>
                                @error('weight') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Dimensi Paket (Opsional)</label>
                                <div class="grid grid-cols-3 gap-4 mt-1">
                                    <div>
                                        <label for="length" class="text-xs text-gray-500">Panjang (cm)</label>
                                        <input type="number" name="length" id="length" value="{{ old('length') }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500">
                                    </div>
                                    <div>
                                        <label for="width" class="text-xs text-gray-500">Lebar (cm)</label>
                                        <input type="number" name="width" id="width" value="{{ old('width') }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500">
                                    </div>
                                    <div>
                                        <label for="height" class="text-xs text-gray-500">Tinggi (cm)</label>
                                        <input type="number" name="height" id="height" value="{{ old('height') }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Organisasi Produk --}}
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Organisasi Produk</h2>
                        <div class="space-y-4">
                            <div>
                                <label for="sku" class="block text-sm font-medium text-gray-700">SKU (Stock Keeping Unit)</label>
                                <input type="text" name="sku" id="sku" value="{{ old('sku') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" placeholder="Otomatis jika kosong">
                            </div>
                            <div>
                                <label for="category_id" class="block text-sm font-medium text-gray-700">Kategori</label>
                                <select name="category_id" id="category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" 
                                                data-attributes-url="{{ route('seller.categories.attributes', $category->id) }}" 
                                                data-kategori-grup="{{ strtolower($category->category_group ?? $category->type ?? '') }}"
                                                {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="tags" class="block text-sm font-medium text-gray-700">Tags (pisahkan koma)</label>
                                <input type="text" name="tags" id="tags" value="{{ old('tags') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" placeholder="Otomatis dari kategori jika kosong">
                            </div>
                        </div>
                    </div>

                    {{-- Status & Label --}}
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Status & Label</h2>
                        <div class="space-y-4">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">Status Produk</label>
                                <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" required>
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Aktif (Dijual)</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Nonaktif (Disimpan)</option>
                                </select>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="is_new" id="is_new" value="1" {{ old('is_new') ? 'checked' : '' }} class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                <label for="is_new" class="ml-2 block text-sm text-gray-900">Tandai sebagai Produk Baru</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="is_bestseller" id="is_bestseller" value="1" {{ old('is_bestseller') ? 'checked' : '' }} class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                <label for="is_bestseller" class="ml-2 block text-sm text-gray-900">Tandai sebagai Bestseller</label>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Tombol Aksi --}}
                    <div class="bg-white p-6 rounded-lg shadow-md flex flex-col gap-3">
                        <button id="submit-button" type="submit" class="w-full px-5 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-300 transition flex items-center justify-center gap-2">
                            Simpan Produk
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

        uploader.addEventListener('dragover', (e) => {
            e.preventDefault(); uploader.classList.add('dragging');
        });
        uploader.addEventListener('dragleave', () => uploader.classList.remove('dragging'));
        uploader.addEventListener('drop', (e) => {
            e.preventDefault(); uploader.classList.remove('dragging');
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

    // --- Form Submission Loading Spinner ---
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
            submitButton.innerHTML = `<span class="spinner" role="status" aria-hidden="true"></span> Menyimpan...`;
        });
    }

    // --- Script Atribut Dinamis ---
    const categorySelect = document.getElementById('category_id');
    const attributesCard = document.getElementById('attributes-card');
    const attributesContainer = document.getElementById('dynamic-attributes-container');
    const digitalContainer = document.getElementById('digital-asset-container');

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
            if (!response.ok) throw new Error(`Gagal memuat atribut (status: ${response.status}). URL: ${url}`);

            const attributes = await response.json();
            attributesContainer.innerHTML = '';

            if (attributes && attributes.length > 0) { 
                attributes.forEach(attr => {
                    if (typeof attr === 'object' && attr !== null && attr.slug) {
                        const field = createAttributeField(attr);
                        attributesContainer.appendChild(field);
                    }
                });
            } else {
                attributesContainer.innerHTML = '<p class="text-gray-500">Tidak ada spesifikasi tambahan untuk kategori ini.</p>';
            }
        } catch (error) {
            console.error('Error fetching attributes:', error);
            attributesContainer.innerHTML = `<p class="text-red-500">Gagal memuat spesifikasi. ${error.message}</p>`;
        }
    }

    function createAttributeField(attribute) {
        const wrapper = document.createElement('div');
        let fieldHtml = '';
        const isRequired = attribute.is_required ? 'required' : '';
        const requiredAsterisk = attribute.is_required ? '<span class="text-red-500">*</span>' : '';
        const attributeName = attribute.name || 'Atribut Tanpa Nama';
        const label = `<label for="attr_${attribute.slug}" class="block text-sm font-medium text-gray-700">${attributeName} ${requiredAsterisk}</label>`;
        const inputName = `attributes[${attribute.slug}]`;
        const optionsString = typeof attribute.options === 'string' ? attribute.options : '';

        switch (attribute.type) {
            case 'number':
            case 'text':
                fieldHtml = `${label}<input type="${attribute.type}" name="${inputName}" id="attr_${attribute.slug}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" ${isRequired}>`;
                break;
            case 'textarea':
                fieldHtml = `${label}<textarea name="${inputName}" id="attr_${attribute.slug}" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" ${isRequired}></textarea>`;
                break;
            case 'select':
                const options = optionsString.split(',').map(opt => opt.trim()).filter(opt => opt).map(opt => `<option value="${opt}">${opt}</option>`).join('');
                fieldHtml = `${label}<select name="${inputName}" id="attr_${attribute.slug}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" ${isRequired}><option value="">-- Pilih ${attributeName} --</option>${options}</select>`;
                break;
            case 'checkbox':
                const checkboxes = optionsString.split(',').map(opt => opt.trim()).filter(opt => opt).map((opt, index) => `
                    <div class="flex items-center">
                        <input type="checkbox" name="${inputName}[]" id="attr_${attribute.slug}_${index}" value="${opt}" class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                        <label for="attr_${attribute.slug}_${index}" class="ml-2 block text-sm text-gray-900">${opt}</label>
                    </div>`).join('');
                fieldHtml = `<label class="block text-sm font-medium text-gray-700">${attributeName} ${requiredAsterisk}</label><div class="mt-2 space-y-2">${checkboxes}</div>`;
                break;
            default:
                fieldHtml = `${label}<input type="text" name="${inputName}" id="attr_${attribute.slug}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" ${isRequired}>`;
        }
        wrapper.innerHTML = fieldHtml;
        return wrapper;
    }

    // --- Fungsi Cek Digital ---
    function checkDigitalCategory() {
        if (!categorySelect) return;
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        
        if (!selectedOption || !selectedOption.value) {
            digitalContainer.classList.add('hidden');
            return;
        }

        const kategoriGrup = selectedOption.getAttribute('data-kategori-grup') || '';
        
        // DI SINI KATA KUNCI 'tiket', 'ticket', 'event' SUDAH DITAMBAHKAN
        const isDigital = ['produk_digital', 'jasa', 'digital', 'eticket', 'tiket', 'ticket', 'event'].some(keyword => kategoriGrup.includes(keyword));

        if (isDigital) {
            digitalContainer.classList.remove('hidden');
        } else {
            digitalContainer.classList.add('hidden');
        }
    }

    // --- Event Listener Kategori (Utama) ---
    if (categorySelect) { 
        categorySelect.addEventListener('change', () => {
            fetchAndRenderAttributes(); 
            checkDigitalCategory();     
        });
        
        if(categorySelect.value) {
            fetchAndRenderAttributes();
            checkDigitalCategory();
        }
    }

    // --- Script Varian Dinamis ---
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
                <button type="button" class="text-red-500 hover:text-red-700 remove-variant-group" title="Hapus Tipe Varian">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </div>
            <div>
                <label for="variant_${index}_name" class="block text-sm font-medium text-gray-700">Nama Tipe Varian</label>
                <input type="text" name="variant_types[${index}][name]" id="variant_${index}_name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" placeholder="Contoh: Warna, Ukuran" required>
            </div>
            <div>
                <label for="variant_${index}_options" class="block text-sm font-medium text-gray-700">Pilihan Varian (pisahkan koma)</label>
                <input type="text" name="variant_types[${index}][options]" id="variant_${index}_options" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" placeholder="Contoh: Merah, Biru, Hijau" required>
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
                mainStockInput.parentElement.insertAdjacentHTML('afterend', `
                    <p id="${warningId}" class="mt-1 text-xs text-red-600">
                        Stok utama dinonaktifkan. Stok akan diatur dari total varian.
                    </p>
                `);
            }
        } else {
            mainStockInput.disabled = false;
        }
    }
    toggleMainStock();

});
</script>
@endpush