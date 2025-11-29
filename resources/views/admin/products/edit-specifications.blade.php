@extends('layouts.admin')

@section('title', 'Edit Spesifikasi: ' . $product->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    {{-- Header --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Edit Kategori & Spesifikasi</h1>
            <p class="text-sm text-gray-500">Produk: <span class="font-semibold">{{ $product->name }}</span></p>
        </div>
        <a href="{{ route('admin.products.edit', $product->slug) }}" class="text-gray-500 hover:text-indigo-600 font-medium text-sm flex items-center">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Edit Produk
        </a>
    </div>

    <form action="{{ route('admin.products.update.specifications', $product->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- === LEFT COLUMN (Main Content: SKU, Tags, Specs) === --}}
            <div class="lg:col-span-8 space-y-6">
                
                {{-- 1. PENGATURAN DATA (SKU, Kategori, Tags) --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Informasi Dasar</h2>
    
    <div class="space-y-4">
        {{-- SKU --}}
        <div>
            <label for="sku" class="block text-sm font-medium text-gray-700 mb-1">SKU Induk</label>
            <div class="relative">
                <input type="text" 
                       name="sku" 
                       id="sku" 
                       value="{{ old('sku', $product->sku) }}" 
                       class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 placeholder-gray-400"
                       placeholder="Contoh: PRD-2023-001 (Kosongkan untuk Auto-Generate)">
                
                {{-- Indikator Visual (Opsional) --}}
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-magic text-gray-300"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                <i class="fa-solid fa-circle-info text-blue-500"></i>
                <span>Biarkan kosong jika ingin sistem membuat SKU otomatis.</span>
            </p>
        </div>

        {{-- Tags (Tetap sama) --}}
        <div>
            <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
            <input type="text" name="tags" id="tags" value="{{ old('tags', $product->tags) }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Jasa, Perizinan, Cepat">
            <p class="text-xs text-gray-500 mt-1">Pisahkan dengan koma.</p>
        </div>
    </div>
</div>

                {{-- 2. SPESIFIKASI DINAMIS --}}
                <div id="attributes-card" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hidden">
                    <div class="flex items-center justify-between mb-4 border-b pb-2">
                        <h2 class="text-lg font-semibold text-gray-800">Spesifikasi Tambahan</h2>
                        <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded">Sesuai Kategori</span>
                    </div>
                    
                    <div id="dynamic-attributes-container" class="space-y-4">
                        {{-- Diisi via JS --}}
                    </div>
                </div>
            </div>

            {{-- === RIGHT COLUMN (Sidebar: Category & Actions) === --}}
            <div class="lg:col-span-4 space-y-6">

                {{-- CARD: SAVE ACTIONS --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-6">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Aksi</h3>
                    <button type="submit" class="w-full py-2.5 bg-indigo-600 text-white rounded-lg font-medium shadow-md hover:bg-indigo-700 transition flex items-center justify-center mb-3">
                        <i class="fa-solid fa-save mr-2"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.products.edit', $product->slug) }}" class="w-full block text-center py-2.5 bg-white border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition">
                        Batal
                    </a>
                </div>

                {{-- CARD: KATEGORI --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Kategori</h2>
                        
                        {{-- Tombol Toggle Tambah --}}
                        <button type="button" id="btn-toggle-add-cat" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded transition" title="Tambah Kategori Baru">
                            <i class="fa-solid fa-plus"></i> Baru
                        </button>
                    </div>

                    {{-- Form Tambah Kategori (Hidden by default) --}}
                    <div id="add-category-wrapper" class="hidden mb-4 p-3 bg-indigo-50 rounded-lg border border-indigo-100">
                        <label class="text-xs font-semibold text-indigo-700 mb-1 block">Nama Kategori Baru</label>
                        <div class="flex gap-2">
                            <input type="text" id="new_category_name" class="w-full text-sm border-gray-300 rounded shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Misal: Elektronik">
                            <button type="button" id="btn-save-new-cat" class="bg-indigo-600 text-white px-3 rounded hover:bg-indigo-700">
                                <i class="fa-solid fa-check"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Dropdown Kategori --}}
                    <div class="relative">
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1 required-label">Pilih Kategori <span class="text-red-500">*</span></label>
                        
                        <div class="flex gap-2">
                            <select name="category_id" id="category_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">-- Pilih --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                        data-attributes-url="{{ route('admin.categories.attributes', $category->id) }}" 
                                        {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>

                            {{-- Tombol Hapus Kategori --}}
                            <button type="button" id="btn-delete-cat" class="px-3 py-2 bg-red-50 text-red-600 border border-red-200 rounded-lg hover:bg-red-100 transition" title="Hapus Kategori Terpilih">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mt-3 p-3 bg-yellow-50 rounded-lg border border-yellow-100">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fa-solid fa-triangle-exclamation text-yellow-500 mt-0.5"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs text-yellow-700">
                                    Mengubah kategori akan <strong>mereset</strong> form spesifikasi di kolom kiri.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // ============================================================
    // 1. TERIMA DATA DARI CONTROLLER
    // ============================================================
    // Perhatikan: Kita menggunakan variable $existingAttributesJson (bukan $product->...)
    const rawData = {!! $existingAttributesJson ?? '{}' !!}; 
    const existingAttributes = typeof rawData === 'string' ? JSON.parse(rawData) : rawData;

    // DEBUG: Cek di Console Browser (F12)
    console.log("🔥 DATA LAMA DARI CONTROLLER:", existingAttributes);

    // ============================================================
    // 2. SETUP DOM ELEMENTS
    // ============================================================
    const categorySelect = document.getElementById('category_id');
    const attributesCard = document.getElementById('attributes-card');
    const attributesContainer = document.getElementById('dynamic-attributes-container');

    // ============================================================
    // 3. FUNGSI UTAMA (FETCH & FILL)
    // ============================================================
    async function fetchAndRenderAttributes() {
        // Ambil URL dari option yang terpilih
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            attributesCard.classList.add('hidden');
            return;
        }

        const url = selectedOption.dataset.attributesUrl;
        if (!url) return;

        // Tampilkan Card & Loading
        attributesCard.classList.remove('hidden');
        attributesContainer.innerHTML = `
            <div class="text-center py-6 text-gray-500">
                <i class="fas fa-circle-notch fa-spin text-indigo-500 mb-2 text-xl"></i>
                <p>Memuat formulir...</p>
            </div>`;

        try {
            // Fetch struktur form dari server
            const response = await fetch(url);
            const attributesStructure = await response.json();
            
            attributesContainer.innerHTML = ''; // Hapus loading

            if (attributesStructure && attributesStructure.length > 0) {
                attributesStructure.forEach(attr => {
                    // A. Render Input HTML
                    const fieldElement = createAttributeField(attr);
                    attributesContainer.appendChild(fieldElement);

                    // B. Isi Nilai (Auto-Fill)
                    // Cek slug asli
                    let dbValue = existingAttributes[attr.slug];

                    // Fallback: Cek slug dengan format berbeda (misal database "jenis_izin" vs form "jenis-izin")
                    if (dbValue === undefined) {
                         const normalizedSlug = attr.slug.replace(/-/g, '_'); // coba ubah - jadi _
                         dbValue = existingAttributes[normalizedSlug];
                    }

                    if (dbValue !== undefined && dbValue !== null) {
                        console.log(`✅ Mengisi [${attr.slug}] dengan nilai:`, dbValue);
                        fillAttributeValue(fieldElement, attr, dbValue);
                    } else {
                        console.log(`❌ Data kosong untuk [${attr.slug}]`);
                    }
                });
            } else {
                attributesContainer.innerHTML = '<p class="text-gray-400 italic text-center">Tidak ada spesifikasi khusus.</p>';
            }

        } catch (error) {
            console.error("Error:", error);
            attributesContainer.innerHTML = '<p class="text-red-500 text-center">Gagal memuat form.</p>';
        }
    }

    //Helper: Membuat HTML Field
    function createAttributeField(attr) {
        const wrapper = document.createElement('div');
        const inputName = `attributes[${attr.slug}]`;
        const commonClass = "w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500";
        let inputHtml = '';

        if (attr.type === 'select') {
            const opts = (attr.options || '').split(',').map(o=>o.trim()).map(o=>`<option value="${o}">${o}</option>`).join('');
            inputHtml = `<select name="${inputName}" class="${commonClass}"><option value="">-- Pilih --</option>${opts}</select>`;
        } else if (attr.type === 'textarea') {
            inputHtml = `<textarea name="${inputName}" rows="3" class="${commonClass}"></textarea>`;
        } else if (attr.type === 'checkbox') {
             // Checkbox mockup sederhana
             inputHtml = `<input type="text" name="${inputName}" class="${commonClass}" placeholder="Isi manual (Multi-select belum aktif)">`;
        } else {
            inputHtml = `<input type="text" name="${inputName}" class="${commonClass}">`;
        }

        const req = attr.is_required ? '<span class="text-red-500">*</span>' : '';
        wrapper.innerHTML = `<label class="block text-sm font-medium text-gray-700 mb-1">${attr.name} ${req}</label>${inputHtml}`;
        return wrapper;
    }

    // Helper: Mengisi Nilai ke Input
    function fillAttributeValue(wrapper, attr, val) {
        const input = wrapper.querySelector(`[name="attributes[${attr.slug}]"]`);
        if (input) {
            input.value = val;
        }
    }

    // ============================================================
    // 4. EVENT LISTENER
    // ============================================================
    if (categorySelect) {
        categorySelect.addEventListener('change', fetchAndRenderAttributes);
        
        // Trigger otomatis saat halaman dimuat jika kategori sudah terpilih
        if (categorySelect.value) {
            fetchAndRenderAttributes();
        }
    }
});
</script>
@endpush