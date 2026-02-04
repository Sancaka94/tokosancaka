@extends('layouts.admin')

@section('title', 'Edit Spesifikasi: ' . $product->name)

@section('content')
<div class="min-h-screen bg-gray-50/50 pb-12">
    
    {{-- Header Section --}}
    <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Spesifikasi Produk</h1>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mt-1">
                        <span>Produk:</span>
                        <span class="font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100">{{ $product->name }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.products.edit', $product->slug) }}" 
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <form action="{{ route('admin.products.update.specifications', $product->slug) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                {{-- === LEFT COLUMN (Main Form Area) === --}}
                <div class="lg:col-span-8 space-y-6">
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
        <span class="bg-gray-100 text-gray-600 p-1.5 rounded-lg">
            <i class="fa-solid fa-cube text-sm"></i>
        </span>
        Informasi Dasar
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- 1. SKU Section (Kiri) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SKU Induk</label>
            <div class="relative flex items-center">
                <input type="text" value="{{ $product->sku ?? '-' }}" disabled
                    class="block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-600 sm:text-sm cursor-not-allowed pr-10">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-barcode text-gray-400"></i>
                </div>
            </div>
            <p class="mt-1 text-xs text-gray-500">
                SKU dibuat otomatis oleh sistem jika dibiarkan kosong saat pembuatan.
            </p>
        </div>

        {{-- 2. Tags Section (Kanan - PERBAIKAN DISINI) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center justify-between">
                <span>Tags</span>
                <i class="fa-solid fa-tags text-gray-400"></i>
            </label>
            
            <div class="min-h-[42px] p-2 bg-gray-50 rounded-lg border border-gray-200 flex items-center">
                @php
                    // Logika untuk decode tags:
                    // 1. Cek apakah datanya ada.
                    // 2. Coba decode JSON.
                    // 3. Jika gagal decode atau bukan array, jadikan array kosong.
                    $rawTags = $product->tags;
                    $tagsList = [];
                    
                    if (!empty($rawTags)) {
                        $decoded = json_decode($rawTags, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $tagsList = $decoded;
                        }
                    }
                @endphp

                <div class="flex flex-wrap gap-2">
                    @forelse($tagsList as $tag)
                        {{-- Tampilan Badge untuk setiap Tag --}}
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 border border-blue-200 capitalize">
                            {{ $tag }}
                        </span>
                    @empty
                        {{-- Tampilan jika tidak ada tags --}}
                        <span class="text-gray-400 text-sm italic pl-1">Tidak ada tags</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

                    {{-- 2. SPESIFIKASI DINAMIS (Main Feature) --}}
                    <div id="attributes-card" class="bg-white rounded-xl shadow-sm border border-gray-200 min-h-[400px] flex flex-col hidden">
                        {{-- Card Header --}}
                        <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">Spesifikasi Detail</h2>
                                <p class="text-sm text-gray-500 mt-0.5">Field ini akan tampil di halaman detail produk.</p>
                            </div>
                            
                            <button type="button" id="btn-show-add-attr" class="group inline-flex items-center px-3 py-1.5 text-sm font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors">
                                <span class="flex items-center justify-center w-5 h-5 mr-2 bg-indigo-200 rounded-full group-hover:bg-indigo-300 text-indigo-700">
                                    <i class="fa-solid fa-plus text-xs"></i>
                                </span>
                                Tambah Field Custom
                            </button>
                        </div>

                        {{-- Card Body --}}
                        <div class="p-6 flex-grow relative">
                            {{-- Form Builder Container --}}
                            <div id="dynamic-attributes-container" class="space-y-6">
                                {{-- JS will inject HTML here --}}
                            </div>

                            {{-- Form Tambah Atribut Baru (Overlay / Inline) --}}
                            <div id="form-add-attribute" class="hidden mt-8 bg-slate-50 border border-slate-200 rounded-xl p-5 shadow-inner relative overflow-hidden ring-1 ring-indigo-500/20">
                                <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
                                
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                        <i class="fa-solid fa-layer-group text-indigo-500"></i> Buat Field Baru
                                    </h3>
                                    <button type="button" id="btn-cancel-attr" class="text-gray-400 hover:text-gray-600 transition">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                    <div class="md:col-span-6">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Label Field <span class="text-red-500">*</span></label>
                                        <input type="text" id="new_attr_name" class="w-full text-sm border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500" placeholder="Contoh: Warna, Material">
                                    </div>
                                    <div class="md:col-span-6">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tipe Input</label>
                                        <select id="new_attr_type" class="w-full text-sm border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                                            <option value="text">Text Pendek</option>
                                            <option value="textarea">Text Panjang (Deskripsi)</option>
                                            <option value="select">Dropdown (Pilihan)</option>
                                            <option value="checkbox">Checkbox (Banyak Pilihan)</option>
                                            <option value="number">Angka</option>
                                        </select>
                                    </div>
                                    <div id="new_attr_options_wrapper" class="md:col-span-12 hidden animate-fade-in">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Opsi Pilihan (Pisahkan dengan koma)</label>
                                        <input type="text" id="new_attr_options" class="w-full text-sm border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500" placeholder="Merah, Biru, Hijau, Kuning">
                                    </div>
                                </div>

                                <div class="flex items-center justify-between mt-4 pt-3 border-t border-slate-200">
                                    <label class="inline-flex items-center cursor-pointer select-none">
                                        <input type="checkbox" id="new_attr_required" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" checked>
                                        <span class="ml-2 text-xs font-medium text-gray-600">Wajib Diisi (Required)</span>
                                    </label>
                                    <button type="button" id="btn-save-attr" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm flex items-center gap-2 transition-all">
                                        <i class="fa-solid fa-save"></i> Simpan Field
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Empty State Placeholder (Initial) --}}
                    <div id="attributes-empty-placeholder" class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <div class="mx-auto h-12 w-12 text-gray-300 mb-3">
                            <i class="fa-solid fa-list-check text-4xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">Pilih Kategori Terlebih Dahulu</h3>
                        <p class="text-gray-500 mt-1 max-w-sm mx-auto">Silakan pilih kategori di panel sebelah kanan untuk memuat atau membuat spesifikasi produk.</p>
                    </div>

                </div>

                {{-- === RIGHT COLUMN (Sidebar) === --}}
                <div class="lg:col-span-4 space-y-6">

                    {{-- ACTION CARD (Sticky) --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sticky top-24 z-20">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Aksi</h3>
                        <button type="submit" class="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                            <i class="fa-solid fa-save mr-2"></i> Simpan Perubahan
                        </button>
                        <a href="{{ route('admin.products.index') }}" class="mt-3 w-full flex justify-center items-center py-2.5 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                            Batal
                        </a>
                    </div>

                    {{-- CATEGORY SELECTOR CARD --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col h-[500px]">
                        {{-- Header --}}
                        <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 rounded-t-xl">
                            <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Kategori</h2>
                            <button type="button" id="btn-toggle-add-cat" class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold flex items-center gap-1 p-1 hover:bg-indigo-50 rounded transition">
                                <i class="fa-solid fa-plus"></i> Buat Baru
                            </button>
                        </div>

                        {{-- Add Category Form (Collapsible) --}}
                        <div id="add-category-wrapper" class="hidden p-3 bg-indigo-50 border-b border-indigo-100 animate-slide-down">
                            <label class="text-xs font-semibold text-indigo-800 mb-1 block">Nama Kategori</label>
                            <div class="flex gap-2">
                                <input type="text" id="new_category_name" class="flex-1 text-sm border-indigo-200 rounded shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Misal: Elektronik">
                                <button type="button" id="btn-save-new-cat" class="bg-indigo-600 text-white px-3 rounded shadow-sm hover:bg-indigo-700 transition">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Search & Tools --}}
                        <div class="p-3 border-b border-gray-100 bg-white">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fa-solid fa-magnifying-glass text-gray-400 text-xs"></i>
                                </div>
                                <input type="text" id="cat-search" class="block w-full pl-8 pr-10 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm transition duration-150 ease-in-out" placeholder="Cari kategori...">
                                
                                <div class="absolute inset-y-0 right-0 pr-2 flex items-center">
                                    <button type="button" id="btn-delete-cat" class="p-1 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded transition disabled:opacity-0 cursor-pointer" title="Hapus Kategori" disabled>
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Hidden Input for Form Submission --}}
                        <input type="hidden" name="category_id" id="category_id" value="{{ old('category_id', $product->category_id) }}" required>

                        {{-- Warning Box --}}
                        <div id="cat-warning" class="hidden px-4 py-2 bg-yellow-50 border-b border-yellow-100">
                            <div class="flex items-start gap-2">
                                <i class="fa-solid fa-triangle-exclamation text-yellow-500 text-xs mt-0.5"></i>
                                <p class="text-[10px] text-yellow-700 leading-tight">
                                    Mengganti kategori akan <strong>mereset</strong> form spesifikasi.
                                </p>
                            </div>
                        </div>

                        {{-- Scrollable List --}}
                        <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1" id="category-list">
                            @foreach($categories as $category)
                                <div class="category-item group relative flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg cursor-pointer transition-all duration-200 border border-transparent hover:bg-gray-50 hover:border-gray-200"
                                    data-id="{{ $category->id }}"
                                    data-name="{{ $category->name }}"
                                    data-url="{{ route('admin.categories.attributes', $category->id) }}">
                                    
                                    <div class="flex items-center gap-3">
                                        <span class="w-2 h-2 rounded-full bg-gray-300 group-hover:bg-indigo-400 transition-colors indicator-dot"></span>
                                        <span class="text-gray-600 group-hover:text-gray-900 category-text truncate max-w-[180px]">{{ $category->name }}</span>
                                    </div>
                                    
                                    <i class="fa-solid fa-check text-indigo-600 opacity-0 transform scale-75 transition-all check-icon"></i>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </main>
</div>

{{-- Custom Styles for transitions --}}
<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 20px; }
    
    /* State: Selected Category */
    .category-item.selected {
        background-color: #eff6ff; /* bg-blue-50 */
        border-color: #bfdbfe;     /* border-blue-200 */
    }
    .category-item.selected .category-text {
        color: #1e40af; /* text-blue-800 */
        font-weight: 600;
    }
    .category-item.selected .indicator-dot {
        background-color: #3b82f6; /* bg-blue-500 */
        box-shadow: 0 0 0 2px #dbeafe;
    }
    .category-item.selected .check-icon {
        opacity: 1;
        transform: scale(1);
    }
    
    .animate-fade-in { animation: fadeIn 0.3s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // =========================================================================
    // 1. GLOBAL VARIABLES & CONFIG
    // =========================================================================
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // Parse Existing Data safely
    let existingAttributes = {};
    try {
        const rawData = {!! $existingAttributesJson ?? '{}' !!};
        existingAttributes = typeof rawData === 'string' ? JSON.parse(rawData) : rawData;
    } catch (e) { console.error("Data parse error:", e); }

    // --- DOM Elements ---
    const els = {
        // Inputs & Wrappers
        catInput: document.getElementById('category_id'),
        listContainer: document.getElementById('category-list'),
        search: document.getElementById('cat-search'),
        deleteBtn: document.getElementById('btn-delete-cat'),
        warningBox: document.getElementById('cat-warning'),
        
        // Spec Cards
        attrCard: document.getElementById('attributes-card'),
        attrContainer: document.getElementById('dynamic-attributes-container'),
        emptyState: document.getElementById('attributes-empty-placeholder'),
        
        // Add Category UI
        btnAddCatToggle: document.getElementById('btn-toggle-add-cat'),
        addCatWrapper: document.getElementById('add-category-wrapper'),
        inputCatName: document.getElementById('new_category_name'),
        btnSaveCat: document.getElementById('btn-save-new-cat'),

        // Add Attribute UI
        btnShowAttr: document.getElementById('btn-show-add-attr'),
        formAttr: document.getElementById('form-add-attribute'),
        btnCancelAttr: document.getElementById('btn-cancel-attr'),
        btnSaveAttr: document.getElementById('btn-save-attr'),
        inputAttrType: document.getElementById('new_attr_type'),
        inputAttrOpts: document.getElementById('new_attr_options_wrapper')
    };

    // =========================================================================
    // 2. LOGIC: FETCH & RENDER SPECIFICATIONS
    // =========================================================================

    async function fetchAttributes(url, isInit = false) {
        if (!url) return;

        // UI Transition
        els.emptyState.classList.add('hidden');
        els.attrCard.classList.remove('hidden');
        
        if (!isInit) {
            els.attrContainer.innerHTML = `
                <div class="flex flex-col items-center justify-center py-12 text-gray-400 animate-pulse">
                    <i class="fa-solid fa-circle-notch fa-spin text-3xl text-indigo-500 mb-3"></i>
                    <span class="text-sm font-medium">Memuat formulir...</span>
                </div>`;
        }

        try {
            const res = await fetch(url);
            if (!res.ok) throw new Error("Server error");
            const data = await res.json();

            els.attrContainer.innerHTML = ''; // Clear loading

            if (data && data.length > 0) {
                data.forEach(attr => {
                    const field = createFieldHTML(attr);
                    els.attrContainer.appendChild(field);
                    
                    // Auto-fill value logic (handling slug differences)
                    let val = existingAttributes[attr.slug] 
                           || existingAttributes[attr.slug.replace(/-/g, '_')]
                           || existingAttributes[attr.slug.replace(/_/g, '-')];
                           
                    if (val !== undefined && val !== null) fillField(field, attr, val);
                });
            } else {
                renderEmptySpecState();
            }

        } catch (err) {
            console.error(err);
            els.attrContainer.innerHTML = `<div class="p-4 bg-red-50 text-red-600 rounded-lg text-sm border border-red-100 text-center">Gagal memuat data.</div>`;
        }
    }

    function renderEmptySpecState() {
        els.attrContainer.innerHTML = `
            <div class="text-center py-10 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50/50">
                <div class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-indigo-100 text-indigo-600 mb-3">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <h3 class="text-sm font-semibold text-gray-900">Belum ada spesifikasi</h3>
                <p class="text-xs text-gray-500 mt-1 mb-4">Kategori ini belum memiliki field. Tambahkan sekarang.</p>
                <button type="button" onclick="document.getElementById('btn-show-add-attr').click()" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 underline">
                    + Buat Spesifikasi Baru
                </button>
            </div>`;
    }

    // =========================================================================
    // 3. HTML GENERATORS (Tailwind Styled)
    // =========================================================================

    function createFieldHTML(attr) {
        const wrapper = document.createElement('div');
        const name = `attributes[${attr.slug}]`;
        const baseClass = "block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm transition-colors duration-200";
        
        let html = '';

        if (attr.type === 'select') {
            const opts = (attr.options || '').split(',').map(o => `<option value="${o.trim()}">${o.trim()}</option>`).join('');
            html = `<select name="${name}" class="${baseClass} cursor-pointer"><option value="">-- Pilih ${attr.name} --</option>${opts}</select>`;
        } else if (attr.type === 'textarea') {
            html = `<textarea name="${name}" rows="3" class="${baseClass}" placeholder="Masukkan detail..."></textarea>`;
        } else if (attr.type === 'checkbox') {
            const opts = (attr.options || '').split(',').map(o => `
                <label class="inline-flex items-center mr-4 mb-2 cursor-pointer group">
                    <input type="checkbox" name="${name}[]" value="${o.trim()}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 group-hover:border-indigo-400 transition">
                    <span class="ml-2 text-sm text-gray-600 group-hover:text-gray-900">${o.trim()}</span>
                </label>`).join('');
            html = `<div class="mt-2 p-3 bg-gray-50 rounded-lg border border-gray-100">${opts}</div>`;
        } else {
            const type = attr.type === 'number' ? 'number' : 'text';
            html = `<input type="${type}" name="${name}" class="${baseClass}">`;
        }

        const reqBadge = attr.is_required ? '<span class="text-red-500 ml-1" title="Wajib">*</span>' : '';
        wrapper.innerHTML = `
            <div class="group">
                <label class="block text-sm font-medium text-gray-700 mb-1.5 group-hover:text-indigo-700 transition-colors">
                    ${attr.name} ${reqBadge}
                </label>
                ${html}
            </div>`;
        return wrapper;
    }

    function fillField(wrapper, attr, val) {
        if (attr.type === 'checkbox') {
            const arr = Array.isArray(val) ? val : [val];
            wrapper.querySelectorAll('input[type="checkbox"]').forEach(chk => {
                if (arr.includes(chk.value)) chk.checked = true;
            });
        } else {
            const el = wrapper.querySelector(`[name="attributes[${attr.slug}]"]`);
            if (el) el.value = val;
        }
    }

    // =========================================================================
    // 4. UI INTERACTION HANDLERS
    // =========================================================================

    function selectCategory(el) {
        // Reset Visuals
        document.querySelectorAll('.category-item').forEach(i => i.classList.remove('selected'));
        
        if (el) {
            el.classList.add('selected');
            els.deleteBtn.disabled = false;
            els.deleteBtn.classList.remove('opacity-0');
            
            // Trigger Load
            els.catInput.value = el.dataset.id;
            els.warningBox.classList.remove('hidden');
            fetchAttributes(el.dataset.url);
        } else {
            els.deleteBtn.disabled = true;
            els.deleteBtn.classList.add('opacity-0');
        }
    }

    // --- Event Listeners ---

    // 1. Click Category List
    if (els.listContainer) {
        els.listContainer.addEventListener('click', (e) => {
            const item = e.target.closest('.category-item');
            if (item) selectCategory(item);
        });
    }

    // 2. Search Category
    if (els.search) {
        els.search.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.category-item').forEach(item => {
                const text = item.querySelector('.category-text').textContent.toLowerCase();
                item.style.display = text.includes(term) ? 'flex' : 'none';
            });
        });
    }

    // 3. Add Category Toggle
    if (els.btnAddCatToggle) {
        els.btnAddCatToggle.addEventListener('click', () => {
            els.addCatWrapper.classList.toggle('hidden');
            if(!els.addCatWrapper.classList.contains('hidden')) els.inputCatName.focus();
        });
    }

    // 4. Save Category (AJAX)
    if (els.btnSaveCat) {
        els.btnSaveCat.addEventListener('click', async () => {
            const name = els.inputCatName.value.trim();
            if (!name) return;

            els.btnSaveCat.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            try {
                const res = await fetch("{{ route('admin.categories.storeAjax') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ name })
                });
                const json = await res.json();
                
                if (!res.ok) throw new Error(json.message);

                // Add to DOM
                const newItem = document.createElement('div');
                newItem.className = "category-item group relative flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg cursor-pointer transition-all duration-200 border border-transparent hover:bg-gray-50 hover:border-gray-200";
                newItem.dataset.id = json.data.id;
                newItem.dataset.name = json.data.name;
                newItem.dataset.url = json.data.attributes_url;
                newItem.innerHTML = `
                     <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full bg-gray-300 group-hover:bg-indigo-400 transition-colors indicator-dot"></span>
                        <span class="text-gray-600 group-hover:text-gray-900 category-text truncate max-w-[180px]">${json.data.name}</span>
                    </div>
                    <i class="fa-solid fa-check text-indigo-600 opacity-0 transform scale-75 transition-all check-icon"></i>`;
                
                els.listContainer.insertBefore(newItem, els.listContainer.firstChild);
                els.inputCatName.value = '';
                els.addCatWrapper.classList.add('hidden');
                
                // Select it
                selectCategory(newItem);

            } catch (e) {
                alert('Gagal: ' + e.message);
            } finally {
                els.btnSaveCat.innerHTML = '<i class="fa-solid fa-check"></i>';
            }
        });
    }

    // 5. Add Attribute Logic
    if (els.btnShowAttr) {
        els.btnShowAttr.addEventListener('click', () => {
            els.formAttr.classList.remove('hidden');
            els.formAttr.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    }
    if (els.btnCancelAttr) els.btnCancelAttr.addEventListener('click', () => els.formAttr.classList.add('hidden'));

    if (els.inputAttrType) {
        els.inputAttrType.addEventListener('change', (e) => {
            const show = ['select', 'checkbox'].includes(e.target.value);
            if (show) els.inputAttrOpts.classList.remove('hidden');
            else els.inputAttrOpts.classList.add('hidden');
        });
    }

    if (els.btnSaveAttr) {
        els.btnSaveAttr.addEventListener('click', async () => {
            const catId = els.catInput.value;
            if(!catId) return alert('Pilih kategori dulu!');
            
            const payload = {
                name: document.getElementById('new_attr_name').value,
                type: els.inputAttrType.value,
                options: document.getElementById('new_attr_options').value,
                is_required: document.getElementById('new_attr_required').checked
            };

            if(!payload.name) return alert('Nama field wajib diisi');

            els.btnSaveAttr.disabled = true;
            const originalText = els.btnSaveAttr.innerHTML;
            els.btnSaveAttr.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...';

            try {
                const url = "{{ route('admin.category-attributes.store', ':id') }}".replace(':id', catId);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if(!res.ok) throw new Error(json.message);

                // Clear empty state if exists
                if(els.attrContainer.innerHTML.includes('Belum ada spesifikasi')) els.attrContainer.innerHTML = '';

                // Render new field
                const newField = createFieldHTML(json.data);
                newField.classList.add('animate-fade-in');
                els.attrContainer.appendChild(newField);
                
                // Reset form
                document.getElementById('new_attr_name').value = '';
                document.getElementById('new_attr_options').value = '';
                els.formAttr.classList.add('hidden');

            } catch (e) {
                alert(e.message);
            } finally {
                els.btnSaveAttr.disabled = false;
                els.btnSaveAttr.innerHTML = originalText;
            }
        });
    }

    // 6. Delete Category
    if (els.deleteBtn) {
        els.deleteBtn.addEventListener('click', async () => {
            const id = els.catInput.value;
            if(!id || !confirm('Yakin hapus kategori ini?')) return;
            
            try {
                const url = "{{ route('admin.categories.destroyAjax', ':id') }}".replace(':id', id);
                await fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken } });
                
                const item = document.querySelector(`.category-item[data-id="${id}"]`);
                if(item) item.remove();
                
                // Reset State
                els.catInput.value = '';
                selectCategory(null);
                els.attrCard.classList.add('hidden');
                els.emptyState.classList.remove('hidden');
                
            } catch(e) { alert('Gagal menghapus'); }
        });
    }

    // =========================================================================
    // 5. INITIAL LOAD
    // =========================================================================
    if (els.catInput.value) {
        const preSelected = document.querySelector(`.category-item[data-id="${els.catInput.value}"]`);
        if (preSelected) {
            selectCategory(preSelected);
            // Force fetch with isInit=true to avoid loading flash
            fetchAttributes(preSelected.dataset.url, true); 
        }
    }
});
</script>
@endpush