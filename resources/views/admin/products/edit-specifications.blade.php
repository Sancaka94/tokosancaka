@extends('layouts.admin')

@section('title', 'Edit Spesifikasi Produk')

@section('styles')
<style>
    /* --- 1. Modern Scrollbar --- */
    .scrollbar-thin::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .scrollbar-thin::-webkit-scrollbar-track {
        background: transparent;
    }
    .scrollbar-thin::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 20px;
    }
    .scrollbar-thin:hover::-webkit-scrollbar-thumb {
        background-color: #94a3b8;
    }

    /* --- 2. Component Utility Classes --- */
    .card-base {
        @apply bg-white border border-gray-200/80 rounded-xl shadow-sm;
    }
    
    .input-field {
        @apply w-full bg-white border border-gray-300 text-gray-700 text-sm rounded-lg p-2.5 
        focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 
        placeholder-gray-400 transition-all duration-200 ease-in-out outline-none;
    }

    .form-label {
        @apply block mb-2 text-sm font-semibold text-gray-700;
    }

    /* Checkbox Custom Style */
    .checkbox-wrapper:hover input {
        @apply border-blue-400;
    }
    .checkbox-wrapper input:checked + span {
        @apply text-blue-700 font-semibold;
    }
    .checkbox-wrapper:hover {
        @apply bg-blue-50/50 border-blue-200;
    }
</style>
@endsection

@section('content')
<div class="max-w-7xl mx-auto pb-40 px-4 sm:px-6 lg:px-8">

    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 pt-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <span>Produk</span>
                <i class="fa-solid fa-chevron-right text-xs"></i>
                <span>Edit</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Spesifikasi Teknis</h1>
            <p class="text-sm text-gray-500 mt-1">Mengelola atribut dinamis untuk <span class="font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded">{{ $product->name }}</span></p>
        </div>
        <a href="{{ url()->previous() }}" class="inline-flex justify-center items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-all shadow-sm group">
            <i class="fa-solid fa-arrow-left mr-2 text-gray-400 group-hover:-translate-x-1 transition-transform"></i> 
            Kembali
        </a>
    </div>

    <form action="{{ route('admin.products.update.specifications', $product->id) }}" method="POST" id="specForm">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            {{-- === KOLOM KIRI (FORM AREA) === --}}
            <div class="lg:col-span-8 space-y-6">

                {{-- INFO KATEGORI & ALERT --}}
                <div class="card-base overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-50 to-white px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="bg-blue-600 text-white w-8 h-8 rounded-lg flex items-center justify-center shadow-blue-500/30 shadow-md">
                                <i class="fa-solid fa-layer-group text-sm"></i>
                            </div>
                            <h3 class="font-bold text-gray-800">Kategori Produk</h3>
                        </div>
                        @if($product->category)
                            <span class="text-xs font-medium bg-green-100 text-green-700 px-2.5 py-1 rounded-full border border-green-200">
                                Terpasang
                            </span>
                        @else
                            <span class="text-xs font-medium bg-red-100 text-red-700 px-2.5 py-1 rounded-full border border-red-200">
                                Belum dipilih
                            </span>
                        @endif
                    </div>
                    
                    <div class="p-6">
                        {{-- Hidden Input Real --}}
                        <input type="hidden" id="category_id" name="category_id" value="{{ $product->category_id }}">

                        <div class="relative mb-5">
                            <label class="form-label text-gray-500">Kategori Terpilih</label>
                            <div class="flex items-center">
                                <input type="text" id="category_display" 
                                    class="input-field bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200 font-medium pl-10" 
                                    value="{{ $product->category ? $product->category->name : 'Belum ada kategori' }}" 
                                    readonly>
                                <i class="fa-solid fa-lock absolute left-3.5 bottom-3 text-gray-400"></i>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-100 rounded-lg text-amber-800 text-sm">
                            <i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-500 text-lg flex-shrink-0"></i>
                            <div class="leading-relaxed">
                                <span class="font-bold block text-amber-900 mb-1">Ingin mengganti kategori?</span>
                                Gunakan panel di sebelah kanan. <span class="font-semibold underline">Peringatan:</span> Mengganti kategori akan mereset form dan <b>menghapus</b> data spesifikasi yang sudah diisi sebelumnya.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DYNAMIC FORM --}}
                <div id="attributes-card" class="card-base relative min-h-[400px]">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="bg-indigo-50 text-indigo-600 w-8 h-8 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-sliders text-sm"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Form Spesifikasi</h3>
                            <p class="text-xs text-gray-500">Field akan muncul otomatis berdasarkan kategori</p>
                        </div>
                    </div>

                    {{-- Loading State --}}
                    <div id="loading-overlay" class="absolute inset-0 bg-white/90 backdrop-blur-[2px] z-10 hidden flex-col items-center justify-center rounded-xl transition-all duration-300">
                        <div class="bg-white p-4 rounded-full shadow-lg border border-gray-100 mb-4">
                            <i class="fa-solid fa-circle-notch fa-spin text-3xl text-blue-600"></i>
                        </div>
                        <span class="text-gray-600 font-medium animate-pulse">Menyiapkan Form...</span>
                    </div>

                    <div id="dynamic-attributes-container" class="p-6 space-y-6">
                        {{-- JS Injects Here --}}
                    </div>
                </div>

            </div>

            {{-- === KOLOM KANAN (SIDEBAR SELECTOR) === --}}
            <div class="lg:col-span-4 space-y-4">
                <div class="card-base sticky top-6 flex flex-col max-h-[calc(100vh-100px)]">
                    
                    {{-- Header Sidebar --}}
                    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50 rounded-t-xl">
                        <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide mb-3">
                            Pustaka Kategori
                        </h3>
                        
                        {{-- Search --}}
                        <div class="relative group">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                            <input type="text" id="searchCategory" placeholder="Cari kategori..." 
                                class="w-full pl-9 pr-8 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all shadow-sm">
                            
                            {{-- Clear Button --}}
                            <button type="button" id="clearSearch" class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500 p-1">
                                <i class="fa-solid fa-times-circle"></i>
                            </button>
                        </div>
                    </div>

                    {{-- List Container --}}
                    <div class="flex-1 overflow-y-auto scrollbar-thin p-2" id="categoryListContainer">
                        <ul id="categoryListUL" class="space-y-1">
                            @foreach($categories as $cat)
                                <li>
                                    <button type="button" 
                                        onclick="selectCategory(this, '{{ $cat->id }}', '{{ $cat->name }}')" 
                                        data-name="{{ strtolower($cat->name) }}"
                                        data-id="{{ $cat->id }}"
                                        class="w-full text-left px-3 py-3 rounded-lg text-sm transition-all duration-200 flex justify-between items-center group relative border border-transparent
                                        {{ $product->category_id == $cat->id 
                                            ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25 font-semibold ring-2 ring-blue-100 ring-offset-1' 
                                            : 'text-gray-600 hover:bg-gray-50 hover:border-gray-200 hover:text-gray-900' 
                                        }}">
                                        
                                        <span class="truncate pr-4">{{ $cat->name }}</span>
                                        
                                        {{-- Icons --}}
                                        <i class="fa-solid fa-check text-xs {{ $product->category_id == $cat->id ? 'block' : 'hidden' }} check-icon"></i>
                                        <i class="fa-solid fa-chevron-right text-[10px] opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all text-gray-400 {{ $product->category_id == $cat->id ? '!hidden' : '' }}"></i>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        
                        {{-- No Results State --}}
                        <div id="noResults" class="hidden text-center py-8">
                            <i class="fa-solid fa-box-open text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">Kategori tidak ditemukan</p>
                        </div>
                    </div>
                    
                    <div class="px-4 py-2 border-t border-gray-100 bg-gray-50 text-xs text-center text-gray-400 rounded-b-xl">
                        Total {{ $categories->count() }} Kategori
                    </div>
                </div>
            </div>

        </div>

        {{-- FLOATING FOOTER --}}
        <div class="fixed bottom-0 left-0 right-0 z-40 bg-white/80 backdrop-blur-md border-t border-gray-200 py-4 shadow-[0_-4px_24px_rgba(0,0,0,0.06)] md:pl-[280px] transition-all">
            <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-sm text-gray-500 hidden sm:flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                    Mode Edit Aktif
                </div>
                <div class="flex gap-3 w-full sm:w-auto">
                    <a href="{{ url()->previous() }}" class="flex-1 sm:flex-none justify-center px-6 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 hover:border-gray-400 transition text-sm text-center">
                        Batal
                    </a>
                    <button type="submit" class="flex-1 sm:flex-none justify-center px-6 py-2.5 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg shadow-blue-500/30 hover:shadow-blue-500/40 active:scale-95 transition-all text-sm flex items-center gap-2">
                        <i class="fa-regular fa-floppy-disk"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
    const existingAttributes = @json($existingAttributes ?? []);
    const categorySelect = document.getElementById('category_id');
    const categoryDisplay = document.getElementById('category_display');
    const attributesContainer = document.getElementById('dynamic-attributes-container');
    const loadingOverlay = document.getElementById('loading-overlay');
    const noResults = document.getElementById('noResults');

    // --- 1. LOGIC GANTI KATEGORI (SIDEBAR) ---
    window.selectCategory = function(btnElement, id, name) {
        // Jangan reload jika klik kategori yang sama
        if(categorySelect.value == id) return;

        // Konfirmasi sederhana (Optional - bisa dihapus kalau ingin instant)
        if(attributesContainer.children.length > 0 && !confirm('Mengganti kategori akan mereset form yang ada. Lanjutkan?')) {
            return;
        }

        // Update Hidden Input & UI Display
        categorySelect.value = id;
        categoryDisplay.value = name;

        // Update UI Styles Sidebar
        const allBtns = document.querySelectorAll('#categoryListUL button');
        allBtns.forEach(btn => {
            // Reset styles
            btn.className = "w-full text-left px-3 py-3 rounded-lg text-sm transition-all duration-200 flex justify-between items-center group relative border border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-200 hover:text-gray-900";
            
            // Hide Check Icon & Show Chevron Logic
            const check = btn.querySelector('.check-icon');
            const chev = btn.querySelector('.fa-chevron-right');
            if(check) check.classList.add('hidden');
            if(chev) chev.classList.remove('!hidden');
        });

        // Apply Active Style to clicked button
        btnElement.className = "w-full text-left px-3 py-3 rounded-lg text-sm transition-all duration-200 flex justify-between items-center group relative border border-transparent bg-blue-600 text-white shadow-md shadow-blue-500/25 font-semibold ring-2 ring-blue-100 ring-offset-1";
        
        const activeCheck = btnElement.querySelector('.check-icon');
        const activeChev = btnElement.querySelector('.fa-chevron-right');
        if(activeCheck) activeCheck.classList.remove('hidden');
        if(activeChev) activeChev.classList.add('!hidden');

        // Trigger fetch
        fetchAttributes(id);
    }

    // --- 2. SEARCH & CLEAR ---
    const searchInput = document.getElementById('searchCategory');
    const clearBtn = document.getElementById('clearSearch');

    searchInput.addEventListener('input', function(e) {
        let filter = e.target.value.toLowerCase();
        let items = document.querySelectorAll('#categoryListUL li');
        let visibleCount = 0;

        // Toggle Clear Button
        clearBtn.style.display = filter.length > 0 ? 'block' : 'none';

        items.forEach(item => {
            let btn = item.querySelector('button');
            let text = btn.getAttribute('data-name');
            if (text.includes(filter)) {
                item.style.display = "";
                visibleCount++;
            } else {
                item.style.display = "none";
            }
        });

        noResults.style.display = visibleCount === 0 ? "block" : "none";
    });

    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
        searchInput.focus();
    });

    // --- 3. FETCH & BUILD FORM ---
    async function fetchAttributes(catId) {
        // Cari URL dari option data (karena kita ganti struktur HTML, kita construct URL manual atau ambil dari sumber data lain)
        // Asumsi route: /admin/categories/{id}/attributes
        const url = `{{ url('admin/categories') }}/${catId}/attributes`;

        // Show Loading
        loadingOverlay.classList.remove('hidden');
        loadingOverlay.classList.add('flex');
        attributesContainer.innerHTML = ''; // Clear form

        try {
            const res = await fetch(url);
            if (!res.ok) throw new Error('Network response error');
            const data = await res.json();
            
            if (data.length > 0) {
                data.forEach(attr => {
                    const el = createField(attr);
                    attributesContainer.appendChild(el);
                    // Fill data if exists and same category context (opsional logic)
                    if (existingAttributes && existingAttributes[attr.slug]) {
                        fillData(el, attr, existingAttributes[attr.slug]);
                    }
                });
            } else {
                attributesContainer.innerHTML = `
                    <div class="text-center py-12 bg-gray-50 border border-dashed border-gray-200 rounded-xl">
                        <div class="bg-white p-3 rounded-full shadow-sm inline-block mb-3">
                            <i class="fa-regular fa-clipboard text-gray-400 text-2xl"></i>
                        </div>
                        <p class="text-gray-900 font-medium">Tidak ada spesifikasi khusus</p>
                        <p class="text-xs text-gray-500 mt-1">Kategori ini tidak memerlukan input detail tambahan.</p>
                    </div>`;
            }
        } catch (err) {
            console.error(err);
            attributesContainer.innerHTML = `
                <div class="p-4 bg-red-50 text-red-700 rounded-lg text-center text-sm">
                    <i class="fa-solid fa-wifi mr-1"></i> Gagal memuat data. Periksa koneksi internet Anda.
                </div>`;
        } finally {
            // Smooth fade out loading
            loadingOverlay.classList.remove('flex');
            loadingOverlay.classList.add('hidden');
        }
    }

    // --- Helper UI Generator ---
    function createField(attr) {
        const div = document.createElement('div');
        const requiredMark = attr.is_required ? '<span class="text-red-500 ml-1" title="Wajib">*</span>' : '';
        const fieldName = `attributes[${attr.slug}]`;
        let inputHtml = '';

        if (attr.type === 'select') {
            const options = attr.options.split(',').map(o => `<option value="${o.trim()}">${o.trim()}</option>`).join('');
            inputHtml = `
            <div class="relative">
                <select name="${fieldName}" class="input-field cursor-pointer appearance-none">
                    <option value="">-- Pilih Opsi --</option>
                    ${options}
                </select>
                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-500">
                    <i class="fa-solid fa-chevron-down text-xs"></i>
                </div>
            </div>`;
        } 
        else if (attr.type === 'textarea') {
            inputHtml = `<textarea name="${fieldName}" rows="4" class="input-field" placeholder="Masukkan deskripsi detail..."></textarea>`;
        } 
        else if (attr.type === 'checkbox') {
            const options = attr.options.split(',').map(o => `
                <label class="checkbox-wrapper flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer transition-all bg-white group select-none">
                    <input type="checkbox" name="${fieldName}[]" value="${o.trim()}" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 transition-colors">
                    <span class="ml-2.5 text-sm text-gray-600 group-hover:text-blue-700 transition-colors">${o.trim()}</span>
                </label>
            `).join('');
            inputHtml = `<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">${options}</div>`;
        } 
        else {
            const type = attr.type === 'number' ? 'number' : (attr.type === 'date' ? 'date' : 'text');
            inputHtml = `<input type="${type}" name="${fieldName}" class="input-field" placeholder="Isi ${attr.name.toLowerCase()}...">`;
        }

        div.innerHTML = `<label class="form-label">${attr.name} ${requiredMark}</label>${inputHtml}`;
        return div;
    }

    function fillData(el, attr, val) {
        if (!val) return;
        
        if (attr.type === 'checkbox') {
            let arr = Array.isArray(val) ? val : [];
            // Handle if stored as JSON string
            if(typeof val === 'string' && (val.startsWith('[') || val.startsWith('{'))) { 
                try { arr = JSON.parse(val); } catch(e){} 
            }
            el.querySelectorAll('input[type="checkbox"]').forEach(chk => {
                if (arr.includes(chk.value)) chk.checked = true;
            });
        } else {
            const inp = el.querySelector(`[name^="attributes"]`);
            if(inp) inp.value = val;
        }
    }

    // --- Init: Load form based on current category ---
    document.addEventListener('DOMContentLoaded', () => {
        if(categorySelect.value) {
            fetchAttributes(categorySelect.value);
        }
    });
</script>
@endpush