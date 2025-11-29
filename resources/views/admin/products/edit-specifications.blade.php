@extends('layouts.admin')

@section('title', 'Edit Spesifikasi: ' . $product->name)

@section('styles')
<style>
    /* --- UTILITIES & COMPONENTS --- */
    .card-base { @apply bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden; }
    .card-header { @apply px-6 py-4 bg-gray-50/50 border-b border-gray-100 flex items-center gap-3 font-semibold text-gray-800; }
    .card-body { @apply p-6; }
    
    .form-group { @apply mb-5; }
    .form-label { @apply block mb-2 text-sm font-semibold text-gray-700 tracking-wide; }
    .form-input { @apply w-full bg-white border border-gray-300 text-gray-800 text-sm rounded-xl p-3 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 placeholder-gray-400; }
    .form-select { @apply w-full bg-white border border-gray-300 text-gray-800 text-sm rounded-xl p-3 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200; }
    
    .sidebar-wrapper { @apply bg-white border border-gray-200 rounded-2xl shadow-sm sticky top-6 overflow-hidden flex flex-col max-h-[calc(100vh-3rem)]; }
    .sidebar-item { @apply w-full text-left px-4 py-3 rounded-xl text-sm font-medium flex justify-between items-center transition-all duration-200 border border-transparent mb-1 text-gray-600 hover:bg-gray-50 hover:text-gray-900; }
    .sidebar-item.active { @apply bg-blue-50 text-blue-700 border-blue-200 shadow-sm ring-1 ring-blue-200; }

    .btn-primary { @apply inline-flex justify-center items-center px-6 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition-all shadow-lg shadow-blue-500/30; }
    .btn-secondary { @apply inline-flex justify-center items-center px-5 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 focus:ring-4 focus:ring-gray-100 transition-all; }

    /* Custom Scrollbar for Sidebar */
    .custom-scroll::-webkit-scrollbar { width: 5px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 20px; }
    .custom-scroll:hover::-webkit-scrollbar-thumb { background-color: #d1d5db; }
</style>
@endsection

@section('content')
<div class="max-w-7xl mx-auto pb-32">

    {{-- === HEADER PAGE === --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <span>Produk</span>
                <i class="fa-solid fa-chevron-right text-xs"></i>
                <span>Edit Spesifikasi</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">
                Spesifikasi Produk
            </h1>
            <p class="mt-2 text-gray-600">
                Mengelola detail teknis untuk <span class="font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-100">{{ $product->name }}</span>
            </p>
        </div>

        <a href="{{ route('admin.products.edit', $product->slug) }}" class="btn-secondary">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Produk
        </a>
    </div>

    <form action="{{ route('admin.products.update.specifications', $product->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            {{-- === LEFT COLUMN (MAIN FORM) === --}}
            <div class="lg:col-span-8 space-y-6">

                {{-- SECTION 1: KATEGORI TERPILIH --}}
                <div class="card-base">
                    <div class="card-header">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <span>Kategori Produk</span>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Kategori Saat Ini <span class="text-red-500">*</span></label>
                            <div class="relative group">
                                <select name="category_id" id="category_id"
                                    class="form-select bg-gray-50 text-gray-500 cursor-not-allowed pl-4 pr-10"
                                    readonly required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}"
                                            data-attributes-url="{{ route('admin.categories.attributes', $category->id) }}"
                                            {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <i class="fa-solid fa-lock text-sm"></i>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-3 p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-sm items-start">
                            <i class="fa-solid fa-lightbulb mt-0.5 text-amber-600"></i>
                            <div>
                                <strong class="font-semibold block mb-1">Perhatian</strong>
                                Ubah kategori melalui panel di sebelah kanan. Mengganti kategori akan mereset form spesifikasi di bawah.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECTION 2: FORM SPESIFIKASI DINAMIS --}}
                <div id="attributes-card" class="card-base hidden relative">
                    <div class="card-header">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
                            <i class="fa-solid fa-list-check"></i>
                        </div>
                        <span>Isi Spesifikasi</span>
                    </div>
                    
                    {{-- Loading Overlay --}}
                    <div id="loading-overlay" class="hidden absolute inset-0 bg-white/80 z-10 flex items-center justify-center backdrop-blur-sm rounded-2xl">
                        <div class="text-center">
                            <i class="fa-solid fa-circle-notch fa-spin text-3xl text-blue-600 mb-2"></i>
                            <p class="text-sm font-medium text-gray-600">Memuat form...</p>
                        </div>
                    </div>

                    <div id="dynamic-attributes-container" class="card-body space-y-5 min-h-[200px]">
                        {{-- JS akan menyuntikkan input di sini --}}
                    </div>
                </div>

                {{-- SECTION 3: DATA TAMBAHAN --}}
                <div class="card-base">
                    <div class="card-header">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <i class="fa-solid fa-tags"></i>
                        </div>
                        <span>Informasi Tambahan</span>
                    </div>
                    <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- SKU --}}
                        <div>
                            <label class="form-label">SKU Induk</label>
                            <div class="relative">
                                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <i class="fa-solid fa-barcode"></i>
                                </div>
                                <input type="text" name="sku" value="{{ old('sku', $product->sku) }}"
                                    class="form-input pl-10 font-mono uppercase tracking-wider"
                                    placeholder="AUTO-GEN">
                            </div>
                        </div>

                        {{-- Tags --}}
                        <div>
                            <label class="form-label">Tags Produk</label>
                            @php
                                $tags = $product->tags;
                                if(is_string($tags) && str_starts_with($tags, '[')) {
                                    $decoded = json_decode($tags, true);
                                    $tags = is_array($decoded) ? implode(', ', $decoded) : $tags;
                                }
                            @endphp
                            <div class="relative">
                                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <i class="fa-solid fa-hashtag"></i>
                                </div>
                                <input type="text" name="tags" value="{{ old('tags', $tags) }}"
                                    class="form-input pl-10"
                                    placeholder="Contoh: Promo, Terbaru, Best Seller">
                            </div>
                            <p class="text-xs text-gray-500 mt-1.5 ml-1">Pisahkan dengan koma.</p>
                        </div>
                    </div>
                </div>

            </div>

            {{-- === RIGHT COLUMN (SIDEBAR) === --}}
            <div class="lg:col-span-4">
                <div class="sidebar-wrapper">
                    
                    {{-- Header Sidebar --}}
                    <div class="px-5 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="font-bold text-gray-800 flex items-center gap-2">
                            <i class="fa-solid fa-magnifying-glass text-blue-500"></i> Pilih Kategori
                        </h3>
                        <span class="text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded-md font-semibold">{{ $categories->count() }}</span>
                    </div>

                    {{-- Search --}}
                    <div class="p-3 border-b border-gray-100 bg-white">
                        <div class="relative">
                            <input id="searchCategory" type="text" 
                                placeholder="Cari kategori..." 
                                class="w-full bg-gray-50 border border-gray-200 rounded-lg py-2 px-3 pl-9 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-colors">
                            <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        </div>
                    </div>

                    {{-- Category List --}}
                    <div class="flex-1 overflow-y-auto custom-scroll p-2 bg-white" style="min-height: 300px;">
                        <ul id="categoryListUL" class="space-y-1">
                            @foreach($categories as $cat)
                                <li>
                                    <button type="button" onclick="selectCategory('{{ $cat->id }}')"
                                        class="sidebar-item group {{ $product->category_id == $cat->id ? 'active' : '' }}">
                                        <span class="group-hover:translate-x-1 transition-transform">{{ $cat->name }}</span>
                                        @if($product->category_id == $cat->id)
                                            <i class="fa-solid fa-check text-blue-600 check-icon"></i>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Add New Category --}}
                    <div class="p-4 bg-gray-50 border-t border-gray-200">
                        <label class="text-xs font-bold text-gray-500 uppercase flex items-center gap-1 mb-2">
                            <i class="fa-solid fa-plus-circle"></i> Tambah Baru
                        </label>
                        <div class="flex gap-2">
                            <input type="text" id="new_category_name" 
                                class="flex-1 text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                                placeholder="Nama kategori...">
                            <button type="button" onclick="addNewCategory()" 
                                class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-md">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        <p id="add-cat-msg" class="text-xs mt-2 hidden"></p>
                    </div>
                </div>
            </div>

        </div>

        {{-- === FLOATING FOOTER === --}}
        <div class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 backdrop-blur-md border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] md:pl-[280px] transition-all duration-300">
            <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
                <div class="hidden sm:block text-sm text-gray-500">
                    <span id="status-text">Pastikan semua field wajib terisi.</span>
                </div>
                <div class="flex gap-3 w-full sm:w-auto justify-end">
                    <a href="{{ route('admin.products.edit', $product->slug) }}" class="btn-secondary">
                        Batal
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
    /* --- CONFIG & STATE --- */
    const existingAttributes = @json($existingAttributes);
    const categorySelect = document.getElementById('category_id');
    const attributesCard = document.getElementById('attributes-card');
    const attributesContainer = document.getElementById('dynamic-attributes-container');
    const loadingOverlay = document.getElementById('loading-overlay');

    /* --- STYLE CLASSES FOR DYNAMIC JS --- */
    const STYLES = {
        label: "block mb-2 text-sm font-semibold text-gray-700",
        input: "w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl p-3 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all placeholder-gray-400",
        checkboxWrapper: "grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2",
        checkboxLabel: "flex items-center p-3 border border-gray-200 rounded-xl hover:bg-blue-50 hover:border-blue-200 cursor-pointer transition-all bg-white group",
        checkboxInput: "w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2",
        checkboxText: "ml-2.5 text-sm font-medium text-gray-600 group-hover:text-blue-700 select-none"
    };

    /* --- 1. ADD NEW CATEGORY --- */
    async function addNewCategory() {
        const input = document.getElementById('new_category_name');
        const msg = document.getElementById('add-cat-msg');
        const btn = input.nextElementSibling;
        const name = input.value.trim();

        if(!name) {
            input.classList.add('border-red-500', 'ring-1', 'ring-red-500');
            setTimeout(() => input.classList.remove('border-red-500', 'ring-1', 'ring-red-500'), 2000);
            return;
        }

        // Loading state
        const originalIcon = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        try {
            // UNCOMMENT BELOW FOR REAL FETCH
            /*
            const response = await fetch("{{ route('admin.categories.store') }}", { // Adjust route
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({ name: name })
            });
            if (!response.ok) throw new Error('Network response was not ok');
            */

            // SIMULATION
            await new Promise(r => setTimeout(r, 600));
            
            // Success Logic
            msg.className = "text-xs mt-2 text-emerald-600 font-semibold flex items-center gap-1";
            msg.innerHTML = '<i class="fa-solid fa-check-circle"></i> Berhasil disimpan!';
            msg.classList.remove('hidden');
            input.value = '';
            
            // Note: Ideally reload or append to list here
            // location.reload();

        } catch (error) {
            console.error(error);
            msg.className = "text-xs mt-2 text-red-600";
            msg.innerText = "Gagal menyimpan.";
            msg.classList.remove('hidden');
        } finally {
            btn.innerHTML = originalIcon;
            btn.disabled = false;
            setTimeout(() => msg.classList.add('hidden'), 3000);
        }
    }

    /* --- 2. SELECT CATEGORY LOGIC --- */
    function selectCategory(id) {
        // Update hidden select
        categorySelect.value = id;
        categorySelect.dispatchEvent(new Event('change'));

        // Update UI Visuals
        document.querySelectorAll('#categoryListUL .sidebar-item').forEach(btn => {
            // Reset all
            btn.className = "sidebar-item group";
            const icon = btn.querySelector('.check-icon');
            if(icon) icon.remove();

            // Set Active
            if(btn.getAttribute('onclick').includes(`'${id}'`)) {
                btn.className = "sidebar-item active";
                if(!btn.querySelector('.check-icon')) {
                    btn.innerHTML += '<i class="fa-solid fa-check text-blue-600 check-icon"></i>';
                }
            }
        });
    }

    // Search Filter
    document.getElementById('searchCategory').addEventListener('input', function(e) {
        const filter = e.target.value.toLowerCase();
        const items = document.querySelectorAll('#categoryListUL li');
        
        items.forEach(item => {
            const text = item.textContent || item.innerText;
            item.style.display = text.toLowerCase().indexOf(filter) > -1 ? "" : "none";
        });
    });

    /* --- 3. DYNAMIC ATTRIBUTES FORM --- */
    document.addEventListener('DOMContentLoaded', () => {
        
        async function fetchAttributes() {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const url = selectedOption ? selectedOption.dataset.attributesUrl : null;

            if (!url) {
                attributesCard.classList.add('hidden');
                attributesContainer.innerHTML = '';
                return;
            }

            // Show UI & Loading
            attributesCard.classList.remove('hidden');
            loadingOverlay.classList.remove('hidden');

            try {
                const res = await fetch(url);
                const data = await res.json();
                
                // Clear container
                attributesContainer.innerHTML = '';

                if (data.length > 0) {
                    data.forEach(attr => {
                        const field = createField(attr);
                        attributesContainer.appendChild(field);
                        
                        // Fill existing data if available
                        if (existingAttributes && existingAttributes[attr.slug]) {
                            fillData(field, attr, existingAttributes[attr.slug]);
                        }
                    });
                    document.getElementById('status-text').innerText = `${data.length} spesifikasi perlu diisi.`;
                } else {
                    attributesContainer.innerHTML = `
                        <div class="text-center py-8 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                            <i class="fa-regular fa-folder-open text-4xl text-gray-300 mb-3 block"></i>
                            <span class="text-gray-500 text-sm">Tidak ada spesifikasi khusus untuk kategori ini.</span>
                        </div>`;
                    document.getElementById('status-text').innerText = "Kategori ini tidak memiliki spesifikasi khusus.";
                }
            } catch (err) {
                console.error(err);
                attributesContainer.innerHTML = `
                    <div class="bg-red-50 text-red-600 p-4 rounded-xl text-center text-sm border border-red-100">
                        <i class="fa-solid fa-triangle-exclamation mb-1 text-lg"></i><br>
                        Gagal memuat form spesifikasi. Silakan coba lagi.
                    </div>`;
            } finally {
                // Fake delay for smooth UI
                setTimeout(() => {
                    loadingOverlay.classList.add('hidden');
                }, 300);
            }
        }

        function createField(attr) {
            const div = document.createElement('div');
            div.className = "form-group";
            const requiredMark = attr.is_required ? '<span class="text-red-500 ml-1" title="Wajib">*</span>' : '';
            const fieldName = `attributes[${attr.slug}]`;
            
            let inputHtml = '';

            if (attr.type === 'select') {
                const options = attr.options.split(',').map(o => `<option value="${o.trim()}">${o.trim()}</option>`).join('');
                inputHtml = `
                    <div class="relative">
                        <select name="${fieldName}" class="${STYLES.input} cursor-pointer appearance-none">
                            <option value="">-- Pilih Opsi --</option>
                            ${options}
                        </select>
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </div>
                    </div>`;
            } 
            else if (attr.type === 'textarea') {
                inputHtml = `<textarea name="${fieldName}" rows="3" class="${STYLES.input}" placeholder="Masukkan deskripsi..."></textarea>`;
            } 
            else if (attr.type === 'checkbox') {
                const options = attr.options.split(',').map(o => `
                    <label class="${STYLES.checkboxLabel}">
                        <input type="checkbox" name="${fieldName}[]" value="${o.trim()}" class="${STYLES.checkboxInput}">
                        <span class="${STYLES.checkboxText}">${o.trim()}</span>
                    </label>
                `).join('');
                inputHtml = `<div class="${STYLES.checkboxWrapper}">${options}</div>`;
            } 
            else {
                const type = attr.type === 'number' ? 'number' : (attr.type === 'date' ? 'date' : 'text');
                inputHtml = `<input type="${type}" name="${fieldName}" class="${STYLES.input}" placeholder="Isi ${attr.name}...">`;
            }

            div.innerHTML = `<label class="${STYLES.label}">${attr.name} ${requiredMark}</label>${inputHtml}`;
            return div;
        }

        function fillData(el, attr, val) {
            if (attr.type === 'checkbox') {
                let arr = Array.isArray(val) ? val : [];
                if(typeof val === 'string' && val.startsWith('[')) { try { arr = JSON.parse(val); } catch(e){} }
                el.querySelectorAll('input[type="checkbox"]').forEach(chk => {
                    if (arr.includes(chk.value)) chk.checked = true;
                });
            } else {
                const inp = el.querySelector(`[name^="attributes"]`);
                if(inp) inp.value = val;
            }
        }

        // Init
        categorySelect.addEventListener('change', fetchAttributes);
        if (categorySelect.value) fetchAttributes();
    });
</script>
@endpush