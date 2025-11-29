@extends('layouts.admin')

@section('title', 'Edit Spesifikasi Produk')

@section('content')
<div class="bg-white max-w-7xl mx-auto pb-40 px-4 sm:px-6 lg:px-8 pt-6">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <span>Produk</span>
                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                <span>Edit Spesifikasi</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Spesifikasi Teknis</h1>
            <p class="text-sm text-gray-500 mt-1">
                Mengelola atribut untuk <span class="font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded">{{ $product->name }}</span>
            </p>
        </div>
        <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition-all">
            <i class="fa-solid fa-arrow-left mr-2 text-gray-400"></i> Kembali
        </a>
    </div>

    <form action="{{ route('admin.products.update.specifications', $product->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

            {{-- === KOLOM KIRI (FORM UTAMA) === --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- CARD: INFO KATEGORI --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center shadow-sm">
                                <i class="fa-solid fa-layer-group text-sm"></i>
                            </div>
                            <h3 class="font-bold text-gray-800">Kategori Produk</h3>
                        </div>
                        @if($product->category_id)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Terpasang
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Belum dipilih
                            </span>
                        @endif
                    </div>

                    <div class="p-6">
                        {{-- Hidden Input untuk ID Kategori --}}
                        <input type="hidden" id="category_id" name="category_id" value="{{ $product->category_id }}">

                        {{-- Tampilan Nama Kategori (Readonly) --}}
                        <div class="mb-5">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Kategori Terpilih</label>
                            <div class="relative">
                                <input type="text" id="category_display"
                                    class="w-full bg-gray-100 border border-gray-200 text-gray-500 text-sm rounded-lg p-2.5 pl-10 cursor-not-allowed font-medium shadow-sm"
                                    value="{{ $categories->firstWhere('id', $product->category_id)?->name ?? 'Belum ada kategori' }}"
                                    readonly>
                                <i class="fa-solid fa-lock absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                            </div>
                        </div>

                        {{-- Alert Box --}}
                        <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg text-amber-900 text-sm">
                            <i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-600 text-lg flex-shrink-0"></i>
                            <div>
                                <span class="font-bold block mb-1 text-amber-800">Ingin mengganti kategori?</span>
                                Gunakan panel di sebelah kanan. <span class="font-semibold underline">Peringatan:</span> Mengganti kategori akan mereset form dan <b>menghapus</b> data spesifikasi yang sudah diisi sebelumnya.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD: FORM INPUT DINAMIS --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm min-h-[300px] relative">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i class="fa-solid fa-sliders text-sm"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Form Spesifikasi</h3>
                            <p class="text-xs text-gray-500">Field muncul otomatis berdasarkan kategori</p>
                        </div>
                    </div>

                    {{-- Loading Animation --}}
                    <div id="loading-overlay" class="absolute inset-0 bg-white/90 z-10 hidden flex-col items-center justify-center rounded-xl backdrop-blur-sm transition-all">
                        <i class="fa-solid fa-circle-notch fa-spin text-3xl text-blue-600 mb-3"></i>
                        <span class="text-gray-500 font-medium">Memuat Form...</span>
                    </div>

                    {{-- Container Input JS --}}
                    <div id="dynamic-attributes-container" class="p-6 space-y-6">
                        {{-- JS akan menyuntikkan HTML di sini --}}
                    </div>
                </div>

            </div>

            {{-- === KOLOM KANAN (SIDEBAR PILIH KATEGORI) === --}}
            <div class="lg:col-span-1">
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm sticky top-6 flex flex-col h-[calc(100vh-6rem)]">
                    
                    {{-- Search Header --}}
                    <div class="p-4 border-b border-gray-100 bg-gray-50/50 rounded-t-xl">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 block">Pustaka Kategori</label>
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text" id="searchCategory" placeholder="Cari kategori..." 
                                class="w-full pl-9 pr-8 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block transition-all shadow-sm">
                            <button type="button" id="clearSearch" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fa-solid fa-times-circle"></i>
                            </button>
                        </div>
                    </div>

                    {{-- List Kategori Scrollable --}}
                    <div class="flex-1 overflow-y-auto p-2 scrollbar-hide" style="scrollbar-width: thin;">
                        <ul id="categoryListUL" class="space-y-1">
                            @foreach($categories as $cat)
                                <li>
                                    <button type="button" 
                                        onclick="selectCategory(this, '{{ $cat->id }}', '{{ $cat->name }}')" 
                                        data-name="{{ strtolower($cat->name) }}"
                                        class="w-full text-left px-3 py-2.5 rounded-md text-sm transition-all flex justify-between items-center group
                                        {{ $product->category_id == $cat->id 
                                            ? 'bg-blue-600 text-white shadow font-semibold' 
                                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' 
                                        }}">
                                        <span class="truncate">{{ $cat->name }}</span>
                                        @if($product->category_id == $cat->id)
                                            <i class="fa-solid fa-check text-xs check-icon"></i>
                                        @else
                                            <i class="fa-solid fa-chevron-right text-[10px] text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        
                        {{-- State Kosong --}}
                        <div id="noResults" class="hidden text-center py-10 px-4">
                            <div class="bg-gray-50 rounded-full w-10 h-10 flex items-center justify-center mx-auto mb-2">
                                <i class="fa-solid fa-box-open text-gray-400"></i>
                            </div>
                            <p class="text-xs text-gray-500">Kategori tidak ditemukan</p>
                        </div>
                    </div>

                    <div class="p-3 border-t border-gray-100 bg-gray-50 text-center text-xs text-gray-400 rounded-b-xl">
                        Total {{ $categories->count() }} Kategori
                    </div>
                </div>
            </div>

        </div>

        {{-- FOOTER BUTTONS --}}
        <div class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 py-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] md:pl-64 transition-all">
            <div class="max-w-7xl mx-auto px-6 flex justify-end items-center gap-3">
                <a href="{{ url()->previous() }}" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition text-sm">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-md hover:shadow-lg transition-all text-sm flex items-center gap-2">
                    <i class="fa-regular fa-floppy-disk"></i> Simpan Perubahan
                </button>
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

    // --- 1. PILIH KATEGORI ---
    window.selectCategory = function(btnElement, id, name) {
        if(categorySelect.value == id) return;
        
        // Reset Style Tombol Lama
        document.querySelectorAll('#categoryListUL button').forEach(btn => {
            btn.className = "w-full text-left px-3 py-2.5 rounded-md text-sm transition-all flex justify-between items-center group text-gray-600 hover:bg-gray-100 hover:text-gray-900";
            const icon = btn.querySelector('.fa-check');
            if(icon) icon.remove();
            
            // Tambah chevron balik
            if(!btn.querySelector('.fa-chevron-right')) {
                btn.innerHTML += '<i class="fa-solid fa-chevron-right text-[10px] text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity"></i>';
            }
        });

        // Set Style Tombol Baru (Aktif)
        btnElement.className = "w-full text-left px-3 py-2.5 rounded-md text-sm transition-all flex justify-between items-center group bg-blue-600 text-white shadow font-semibold";
        // Hapus chevron, ganti check
        const chevron = btnElement.querySelector('.fa-chevron-right');
        if(chevron) chevron.remove();
        if(!btnElement.querySelector('.fa-check')) {
            btnElement.innerHTML += '<i class="fa-solid fa-check text-xs check-icon"></i>';
        }

        // Update Nilai
        categorySelect.value = id;
        categoryDisplay.value = name;
        
        // Fetch Form
        fetchAttributes(id);
    }

    // --- 2. SEARCH KATEGORI ---
    const searchInput = document.getElementById('searchCategory');
    const clearBtn = document.getElementById('clearSearch');

    searchInput.addEventListener('input', function(e) {
        let filter = e.target.value.toLowerCase();
        let items = document.querySelectorAll('#categoryListUL li');
        let hasVisible = false;

        clearBtn.style.display = filter ? 'block' : 'none';

        items.forEach(item => {
            let btn = item.querySelector('button');
            let text = btn.getAttribute('data-name');
            if (text.includes(filter)) {
                item.style.display = "";
                hasVisible = true;
            } else {
                item.style.display = "none";
            }
        });

        noResults.style.display = hasVisible ? "none" : "block";
    });

    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
    });

    // --- 3. LOAD FORM (AJAX) ---
    async function fetchAttributes(catId) {
        // Ganti URL ini sesuai route backend Anda yang valid
        // Contoh: /admin/categories/5/attributes
        const url = `{{ url('admin/categories') }}/${catId}/attributes`;

        loadingOverlay.classList.remove('hidden');
        loadingOverlay.classList.add('flex');
        attributesContainer.innerHTML = '';

        try {
            const res = await fetch(url);
            const data = await res.json();
            
            if (data.length > 0) {
                data.forEach(attr => {
                    const el = createField(attr);
                    attributesContainer.appendChild(el);
                    if (existingAttributes && existingAttributes[attr.slug]) {
                        fillData(el, attr, existingAttributes[attr.slug]);
                    }
                });
            } else {
                attributesContainer.innerHTML = `
                    <div class="text-center py-12">
                        <div class="bg-gray-50 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3">
                            <i class="fa-regular fa-clipboard text-gray-400 text-xl"></i>
                        </div>
                        <p class="text-gray-900 font-medium text-sm">Tidak ada spesifikasi khusus</p>
                        <p class="text-xs text-gray-500 mt-1">Kategori ini tidak membutuhkan data tambahan.</p>
                    </div>`;
            }
        } catch (err) {
            console.error(err);
            attributesContainer.innerHTML = `<p class="text-red-500 text-sm text-center">Gagal memuat form.</p>`;
        } finally {
            loadingOverlay.classList.remove('flex');
            loadingOverlay.classList.add('hidden');
        }
    }

    // --- 4. GENERATOR FIELD (Dengan Tailwind Classes Langsung) ---
    function createField(attr) {
        const div = document.createElement('div');
        const requiredMark = attr.is_required ? '<span class="text-red-500 ml-1" title="Wajib">*</span>' : '';
        const fieldName = `attributes[${attr.slug}]`;
        
        // CLASS TAILWIND UTAMA UNTUK INPUT (AGAR TERLIHAT JELAS)
        const baseInputClass = "w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition-colors";
        
        let inputHtml = '';

        if (attr.type === 'select') {
            const options = attr.options.split(',').map(o => `<option value="${o.trim()}">${o.trim()}</option>`).join('');
            inputHtml = `
            <div class="relative">
                <select name="${fieldName}" class="${baseInputClass} appearance-none cursor-pointer">
                    <option value="">-- Pilih Opsi --</option>
                    ${options}
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                    <i class="fa-solid fa-chevron-down text-gray-500 text-xs mr-1"></i>
                </div>
            </div>`;
        } 
        else if (attr.type === 'textarea') {
            inputHtml = `<textarea name="${fieldName}" rows="3" class="${baseInputClass}" placeholder="Masukkan detail..."></textarea>`;
        } 
        else if (attr.type === 'checkbox') {
            const options = attr.options.split(',').map(o => `
                <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-200 transition-all bg-white group select-none">
                    <input type="checkbox" name="${fieldName}[]" value="${o.trim()}" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-600 group-hover:text-blue-700 font-medium">${o.trim()}</span>
                </label>
            `).join('');
            inputHtml = `<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">${options}</div>`;
        } 
        else {
            const type = attr.type === 'number' ? 'number' : (attr.type === 'date' ? 'date' : 'text');
            inputHtml = `<input type="${type}" name="${fieldName}" class="${baseInputClass}" placeholder="...">`;
        }

        div.innerHTML = `<label class="block mb-2 text-sm font-bold text-gray-700">${attr.name} ${requiredMark}</label>${inputHtml}`;
        return div;
    }

    function fillData(el, attr, val) {
        if(!val) return;
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

    // Init jika kategori sudah terpilih
    if(categorySelect.value) fetchAttributes(categorySelect.value);
</script>
@endpush