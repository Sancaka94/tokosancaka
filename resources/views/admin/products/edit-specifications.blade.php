@extends('layouts.admin')

@section('title', 'Edit Spesifikasi Produk')

@section('styles')
<style>
    /* 1. Custom Scrollbar Biar Gak "Asu" */
    .custom-scroll::-webkit-scrollbar {
        width: 5px;
    }
    .custom-scroll::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scroll::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 20px;
    }
    .custom-scroll:hover::-webkit-scrollbar-thumb {
        background-color: #94a3b8;
    }

    /* 2. Base Styles */
    .card-pro {
        @apply bg-white border border-gray-200 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)];
    }
    .input-pro {
        @apply w-full bg-white border border-gray-300 text-gray-800 text-sm rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition-all duration-200 outline-none;
    }
    .label-pro {
        @apply block mb-1.5 text-sm font-semibold text-gray-700;
    }
</style>
@endsection

@section('content')
<div class="max-w-7xl mx-auto pb-32">

    {{-- HEADER HALAMAN --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Spesifikasi</h1>
            <p class="text-sm text-gray-500 mt-1">Atur detail teknis untuk produk <span class="font-medium text-blue-600">{{ $product->name }}</span></p>
        </div>
        <a href="{{ url()->previous() }}" class="group flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-all shadow-sm">
            <i class="fa-solid fa-arrow-left mr-2 text-gray-400 group-hover:text-gray-600"></i> Kembali
        </a>
    </div>

    <form action="{{ route('admin.products.update.specifications', $product->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            {{-- === KOLOM KIRI (70%) === --}}
            <div class="lg:col-span-8 space-y-6">

                {{-- CARD INFO KATEGORI --}}
                <div class="card-pro overflow-hidden">
                    <div class="bg-gray-50/50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="bg-blue-100 text-blue-600 w-8 h-8 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-layer-group text-sm"></i>
                        </div>
                        <h3 class="font-bold text-gray-800">Kategori Terpilih</h3>
                    </div>
                    
                    <div class="p-6">
                        {{-- Select Readonly --}}
                        <div class="mb-4">
                            <label class="label-pro">Kategori Saat Ini <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <select id="category_id" name="category_id" class="input-pro bg-gray-50 text-gray-500 cursor-not-allowed pl-3 pr-10" readonly>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" 
                                            data-attributes-url="{{ route('admin.categories.attributes', $category->id) }}"
                                            {{ $product->category_id == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <i class="fa-solid fa-lock absolute right-3 top-3 text-gray-400 text-xs"></i>
                            </div>
                        </div>

                        {{-- Alert Info --}}
                        <div class="flex gap-3 p-4 bg-amber-50 border border-amber-100 rounded-lg text-amber-800 text-sm">
                            <i class="fa-solid fa-circle-info mt-0.5 text-amber-500 text-lg"></i>
                            <div>
                                <span class="font-bold block mb-1">Perhatian</span>
                                Ingin ganti kategori? Pilih dari panel di sebelah kanan. Hati-hati, mengganti kategori akan <b>menghapus</b> isian spesifikasi di bawah ini.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD FORM DINAMIS --}}
                <div id="attributes-card" class="card-pro relative min-h-[300px]">
                    <div class="bg-gray-50/50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="bg-indigo-100 text-indigo-600 w-8 h-8 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-list-check text-sm"></i>
                        </div>
                        <h3 class="font-bold text-gray-800">Form Spesifikasi</h3>
                    </div>

                    {{-- Loading State --}}
                    <div id="loading-overlay" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-10 hidden flex-col items-center justify-center">
                        <i class="fa-solid fa-circle-notch fa-spin text-4xl text-blue-500 mb-3"></i>
                        <span class="text-gray-500 font-medium animate-pulse">Memuat Form...</span>
                    </div>

                    <div id="dynamic-attributes-container" class="p-6 space-y-5">
                        {{-- Javascript akan menyuntikkan input di sini --}}
                    </div>
                </div>

            </div>

            {{-- === KOLOM KANAN (SIDEBAR) (30%) === --}}
            <div class="lg:col-span-4">
                <div class="card-pro sticky top-6">
                    
                    {{-- Header Sidebar --}}
                    <div class="px-5 py-4 bg-white border-b border-gray-100 flex justify-between items-center rounded-t-xl">
                        <h3 class="font-bold text-gray-800 flex items-center gap-2">
                            <i class="fa-solid fa-magnifying-glass text-blue-500"></i> Pilih Kategori
                        </h3>
                        <span class="text-xs font-bold bg-gray-100 text-gray-600 px-2 py-1 rounded-md border border-gray-200">{{ $categories->count() }}</span>
                    </div>

                    {{-- Search --}}
                    <div class="p-3 bg-gray-50 border-b border-gray-100">
                        <div class="relative">
                            <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text" id="searchCategory" placeholder="Cari nama kategori..." 
                                class="w-full pl-9 pr-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all">
                        </div>
                    </div>

                    {{-- List Items --}}
                    <div class="max-h-[450px] overflow-y-auto custom-scroll p-2 bg-white rounded-b-xl">
                        <ul id="categoryListUL" class="space-y-1">
                            @foreach($categories as $cat)
                                <li>
                                    <button type="button" onclick="selectCategory('{{ $cat->id }}')" 
                                        class="w-full text-left px-3 py-2.5 rounded-lg text-sm transition-all duration-200 flex justify-between items-center group
                                        {{ $product->category_id == $cat->id 
                                            ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30 font-semibold' 
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' 
                                        }}">
                                        <span class="truncate">{{ $cat->name }}</span>
                                        @if($product->category_id == $cat->id)
                                            <i class="fa-solid fa-check text-xs"></i>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                </div>
            </div>

        </div>

        {{-- FOOTER MELAYANG --}}
        <div class="fixed bottom-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-md border-t border-gray-200 py-4 shadow-[0_-4px_20px_rgba(0,0,0,0.05)] md:pl-[260px] transition-all">
            <div class="max-w-7xl mx-auto px-6 flex justify-between items-center">
                <div class="hidden md:block text-sm text-gray-500">
                    <i class="fa-regular fa-circle-check text-green-500 mr-1"></i> Pastikan data sudah benar.
                </div>
                <div class="flex gap-3 ml-auto">
                    <a href="{{ url()->previous() }}" class="px-6 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 transition text-sm">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg shadow-blue-500/30 active:scale-95 transition-all text-sm flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
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
    const attributesContainer = document.getElementById('dynamic-attributes-container');
    const loadingOverlay = document.getElementById('loading-overlay');

    // --- 1. LOGIC GANTI KATEGORI (SIDEBAR) ---
    function selectCategory(id) {
        // Update Select Hidden
        categorySelect.value = id;
        categorySelect.dispatchEvent(new Event('change'));

        // Update UI Sidebar (Biar keliatan aktif)
        document.querySelectorAll('#categoryListUL button').forEach(btn => {
            // Reset ke tampilan default (abu-abu)
            btn.className = "w-full text-left px-3 py-2.5 rounded-lg text-sm transition-all duration-200 flex justify-between items-center group text-gray-600 hover:bg-gray-50 hover:text-gray-900";
            
            // Hapus icon centang
            const icon = btn.querySelector('.fa-check');
            if(icon) icon.remove();

            // Jika ini tombol yang diklik
            if (btn.getAttribute('onclick').includes(`'${id}'`)) {
                // Ubah jadi biru (aktif)
                btn.className = "w-full text-left px-3 py-2.5 rounded-lg text-sm transition-all duration-200 flex justify-between items-center group bg-blue-600 text-white shadow-md shadow-blue-500/30 font-semibold";
                btn.innerHTML += '<i class="fa-solid fa-check text-xs"></i>';
            }
        });
    }

    // --- 2. LOGIC CARI KATEGORI ---
    document.getElementById('searchCategory').addEventListener('input', function(e) {
        let filter = e.target.value.toLowerCase();
        let items = document.querySelectorAll('#categoryListUL li');
        items.forEach(item => {
            let text = item.textContent.toLowerCase();
            item.style.display = text.includes(filter) ? "" : "none";
        });
    });

    // --- 3. LOGIC LOAD FORM DINAMIS ---
    document.addEventListener('DOMContentLoaded', () => {
        async function fetchAttributes() {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const url = selectedOption ? selectedOption.dataset.attributesUrl : null;

            if (!url) {
                attributesContainer.innerHTML = `
                    <div class="text-center py-10 border-2 border-dashed border-gray-200 rounded-xl">
                        <i class="fa-solid fa-arrow-pointer text-gray-300 text-3xl mb-2"></i>
                        <p class="text-gray-500">Silakan pilih kategori di sebelah kanan.</p>
                    </div>`;
                return;
            }

            // Show Loading
            loadingOverlay.classList.remove('hidden');
            loadingOverlay.classList.add('flex');

            try {
                const res = await fetch(url);
                const data = await res.json();
                
                attributesContainer.innerHTML = ''; // Kosongkan dulu

                if (data.length > 0) {
                    data.forEach(attr => {
                        const el = createField(attr);
                        attributesContainer.appendChild(el);
                        // Isi data lama jika ada (mode edit)
                        if (existingAttributes && existingAttributes[attr.slug]) {
                            fillData(el, attr, existingAttributes[attr.slug]);
                        }
                    });
                } else {
                    attributesContainer.innerHTML = `
                        <div class="text-center py-8 bg-gray-50 rounded-xl border border-gray-200">
                            <p class="text-sm text-gray-500 font-medium">Kategori ini tidak membutuhkan spesifikasi tambahan.</p>
                        </div>`;
                }
            } catch (err) {
                console.error(err);
                attributesContainer.innerHTML = '<p class="text-red-500 text-center font-bold">Gagal memuat form. Cek koneksi internet.</p>';
            } finally {
                loadingOverlay.classList.add('hidden');
                loadingOverlay.classList.remove('flex');
            }
        }

        // --- Helper Bikin Input ---
        function createField(attr) {
            const div = document.createElement('div');
            const requiredHtml = attr.is_required ? '<span class="text-red-500 ml-1" title="Wajib diisi">*</span>' : '';
            const fieldName = `attributes[${attr.slug}]`;
            const inputClass = "input-pro"; // Pake class CSS yang udah kita define di atas

            let htmlInput = '';

            if (attr.type === 'select') {
                const options = attr.options.split(',').map(o => `<option value="${o.trim()}">${o.trim()}</option>`).join('');
                htmlInput = `
                <div class="relative">
                    <select name="${fieldName}" class="${inputClass} cursor-pointer appearance-none">
                        <option value="">-- Pilih --</option>
                        ${options}
                    </select>
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-500">
                        <i class="fa-solid fa-chevron-down text-xs"></i>
                    </div>
                </div>`;
            } 
            else if (attr.type === 'textarea') {
                htmlInput = `<textarea name="${fieldName}" rows="3" class="${inputClass}" placeholder="Tulis deskripsi..."></textarea>`;
            } 
            else if (attr.type === 'checkbox') {
                const options = attr.options.split(',').map(o => `
                    <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-200 transition-all group bg-white">
                        <input type="checkbox" name="${fieldName}[]" value="${o.trim()}" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-600 group-hover:text-blue-700 font-medium select-none">${o.trim()}</span>
                    </label>
                `).join('');
                htmlInput = `<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1">${options}</div>`;
            } 
            else {
                const type = attr.type === 'number' ? 'number' : (attr.type === 'date' ? 'date' : 'text');
                htmlInput = `<input type="${type}" name="${fieldName}" class="${inputClass}" placeholder="...">`;
            }

            div.innerHTML = `<label class="label-pro">${attr.name} ${requiredHtml}</label>${htmlInput}`;
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

        categorySelect.addEventListener('change', fetchAttributes);
        if (categorySelect.value) fetchAttributes();
    });
</script>
@endpush