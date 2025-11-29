@extends('layouts.admin')

@section('title', 'Edit Spesifikasi Produk')

@section('styles')
<style>
    /* Custom Scrollbar yang lebih rapi untuk Sidebar Kategori */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: #94a3b8;
    }

    /* Base Card Style */
    .card-base {
        @apply bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mb-6;
    }
    .card-header {
        @apply bg-gray-50/80 px-6 py-4 border-b border-gray-100 flex items-center gap-3;
    }
    .card-title {
        @apply font-bold text-gray-800 text-base;
    }
    .card-body {
        @apply p-6;
    }

    /* Form Styles */
    .form-label {
        @apply block mb-2 text-sm font-semibold text-gray-700 tracking-wide;
    }
    .form-input {
        @apply w-full bg-white border border-gray-300 text-gray-800 text-sm rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 placeholder-gray-400;
    }
    
    /* Sidebar Item */
    .cat-item {
        @apply flex justify-between items-center w-full px-4 py-2.5 text-sm text-left rounded-lg transition-colors duration-200 hover:bg-gray-50 text-gray-600;
    }
    .cat-item.active {
        @apply bg-blue-50 text-blue-700 font-semibold border border-blue-100;
    }
</style>
@endsection

@section('content')
<div class="max-w-7xl mx-auto pb-32">
    
    {{-- HEADER PAGE --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Spesifikasi</h1>
            <p class="text-sm text-gray-500 mt-1">Mengatur detail teknis untuk produk <span class="text-blue-600 font-medium">#{{ $product->id }}</span></p>
        </div>
        <a href="{{ url()->previous() }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition shadow-sm">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    <form action="{{ route('admin.products.update.specifications', $product->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

            {{-- === KOLOM KIRI (FORM UTAMA) === --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- CARD 1: KATEGORI --}}
                <div class="card-base">
                    <div class="card-header">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                            <i class="fa-solid fa-layer-group text-sm"></i>
                        </div>
                        <h2 class="card-title">Kategori Produk</h2>
                    </div>
                    
                    <div class="card-body">
                        <label class="form-label">Kategori Saat Ini <span class="text-red-500">*</span></label>
                        
                        <div class="relative group">
                            {{-- Select ini Readonly karena diubah lewat sidebar --}}
                            <select id="category_id" name="category_id" class="form-input bg-gray-50 cursor-not-allowed pl-3 pr-10" readonly>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                        data-attributes-url="{{ route('admin.categories.attributes', $category->id) }}"
                                        {{ $product->category_id == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="absolute right-3 top-3 text-gray-400">
                                <i class="fa-solid fa-lock text-xs"></i>
                            </div>
                        </div>

                        {{-- Alert Info --}}
                        <div class="mt-4 p-4 bg-amber-50 border-l-4 border-amber-400 rounded-r-lg flex items-start gap-3">
                            <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-1"></i>
                            <div>
                                <h4 class="text-sm font-bold text-amber-800">Perhatian</h4>
                                <p class="text-sm text-amber-700 mt-0.5">
                                    Untuk mengubah kategori, silakan pilih melalui panel di sebelah kanan. Mengganti kategori akan <b>mereset</b> isian form spesifikasi di bawah.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: ISI SPESIFIKASI --}}
                <div id="attributes-card" class="card-base relative min-h-[200px]">
                    <div class="card-header">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                            <i class="fa-solid fa-list-check text-sm"></i>
                        </div>
                        <h2 class="card-title">Isi Spesifikasi</h2>
                    </div>

                    {{-- Loading Spinner (Hidden by default) --}}
                    <div id="loading-overlay" class="absolute inset-0 bg-white/90 z-10 hidden flex-col items-center justify-center">
                        <i class="fa-solid fa-circle-notch fa-spin text-3xl text-blue-500 mb-2"></i>
                        <span class="text-sm font-medium text-gray-500">Memuat form...</span>
                    </div>

                    <div id="dynamic-attributes-container" class="card-body space-y-5">
                        {{-- Field Input akan muncul di sini via JS --}}
                    </div>
                </div>

            </div>

            {{-- === KOLOM KANAN (SIDEBAR PILIH KATEGORI) === --}}
            <div class="lg:col-span-1">
                <div class="bg-white border border-gray-200 rounded-xl shadow-lg shadow-gray-200/50 sticky top-6 overflow-hidden">
                    
                    {{-- Header Sidebar --}}
                    <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                        <h3 class="font-bold text-gray-700"><i class="fa-solid fa-magnifying-glass mr-2 text-blue-500"></i> Pilih Kategori</h3>
                        <span class="text-xs font-semibold bg-gray-200 text-gray-600 px-2 py-0.5 rounded">{{ $categories->count() }}</span>
                    </div>

                    {{-- Search Input --}}
                    <div class="p-3 bg-white border-b border-gray-100">
                        <div class="relative">
                            <i class="fa-solid fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
                            <input type="text" id="searchCategory" placeholder="Cari kategori..." 
                                class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-colors">
                        </div>
                    </div>

                    {{-- List Categories --}}
                    <div class="max-h-[500px] overflow-y-auto custom-scrollbar p-2">
                        <ul id="categoryListUL" class="space-y-1">
                            @foreach($categories as $cat)
                                <li>
                                    <button type="button" onclick="selectCategory('{{ $cat->id }}')" 
                                        class="cat-item group {{ $product->category_id == $cat->id ? 'active' : '' }}">
                                        <span>{{ $cat->name }}</span>
                                        @if($product->category_id == $cat->id)
                                            <i class="fa-solid fa-check text-blue-600"></i>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

        </div>

        {{-- FLOATING ACTION FOOTER --}}
        <div class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] py-4 md:pl-64">
            <div class="max-w-7xl mx-auto px-4 flex justify-between items-center">
                <span class="text-sm text-gray-500 hidden sm:block">Pastikan semua data wajib diisi.</span>
                <div class="flex gap-3">
                    <a href="{{ url()->previous() }}" class="px-6 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 transition text-sm">Batal</a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition text-sm flex items-center gap-2">
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

    // --- 1. Logic Pilih Kategori dari Sidebar ---
    function selectCategory(id) {
        // Update nilai select box hidden
        categorySelect.value = id;
        categorySelect.dispatchEvent(new Event('change'));

        // Update tampilan Visual Sidebar
        document.querySelectorAll('#categoryListUL .cat-item').forEach(btn => {
            // Reset class
            btn.classList.remove('active', 'bg-blue-50', 'text-blue-700', 'font-semibold', 'border', 'border-blue-100');
            btn.querySelector('.fa-check')?.remove();

            // Set Active state pada item yang diklik
            if (btn.getAttribute('onclick').includes(`'${id}'`)) {
                btn.classList.add('active'); // Class active sudah diatur di style atas
                // Tambah icon check jika belum ada
                if(!btn.querySelector('.fa-check')) {
                    const icon = document.createElement('i');
                    icon.className = 'fa-solid fa-check text-blue-600';
                    btn.appendChild(icon);
                }
            }
        });
    }

    // --- 2. Filter Pencarian Kategori ---
    document.getElementById('searchCategory').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let items = document.querySelectorAll('#categoryListUL li');
        items.forEach(item => {
            let text = item.textContent.toLowerCase();
            item.style.display = text.includes(filter) ? "" : "none";
        });
    });

    // --- 3. Logic Form Dinamis (PENTING: Styling disini) ---
    document.addEventListener('DOMContentLoaded', () => {
        
        async function fetchAttributes() {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const url = selectedOption ? selectedOption.dataset.attributesUrl : null;

            if (!url) {
                attributesContainer.innerHTML = '<div class="text-center text-gray-400 py-8">Pilih kategori untuk memuat spesifikasi.</div>';
                return;
            }

            // Tampilkan Loading
            loadingOverlay.classList.remove('hidden');
            loadingOverlay.classList.add('flex');

            try {
                const res = await fetch(url);
                const data = await res.json();
                
                attributesContainer.innerHTML = ''; // Reset container

                if (data.length > 0) {
                    data.forEach(attr => {
                        const el = createField(attr);
                        attributesContainer.appendChild(el);
                        // Isi data lama jika ada (saat edit)
                        if (existingAttributes && existingAttributes[attr.slug]) {
                            fillData(el, attr, existingAttributes[attr.slug]);
                        }
                    });
                } else {
                    attributesContainer.innerHTML = `
                        <div class="text-center py-6 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                            <p class="text-sm text-gray-500">Tidak ada spesifikasi khusus untuk kategori ini.</p>
                        </div>`;
                }
            } catch (err) {
                console.error(err);
                attributesContainer.innerHTML = '<p class="text-red-500 text-sm">Gagal memuat form.</p>';
            } finally {
                loadingOverlay.classList.add('hidden');
                loadingOverlay.classList.remove('flex');
            }
        }

        // --- Fungsi Membuat Field dengan Style Tailwind ---
        function createField(attr) {
            const div = document.createElement('div');
            const requiredStar = attr.is_required ? '<span class="text-red-500 ml-1">*</span>' : '';
            const fieldName = `attributes[${attr.slug}]`;
            
            // Class standard Tailwind untuk input (sama dengan CSS .form-input di atas)
            const inputClass = "w-full bg-white border border-gray-300 text-gray-800 text-sm rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200";

            let htmlInput = '';

            if (attr.type === 'select') {
                const options = attr.options.split(',').map(o => `<option value="${o.trim()}">${o.trim()}</option>`).join('');
                htmlInput = `
                <div class="relative">
                    <select name="${fieldName}" class="${inputClass} appearance-none cursor-pointer">
                        <option value="">-- Pilih Opsi --</option>
                        ${options}
                    </select>
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-500">
                        <i class="fa-solid fa-chevron-down text-xs"></i>
                    </div>
                </div>`;
            } 
            else if (attr.type === 'textarea') {
                htmlInput = `<textarea name="${fieldName}" rows="3" class="${inputClass}" placeholder="Masukkan deskripsi..."></textarea>`;
            } 
            else if (attr.type === 'checkbox') {
                const options = attr.options.split(',').map(o => `
                    <label class="flex items-center p-3 bg-white border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 hover:border-blue-300 transition-all group">
                        <input type="checkbox" name="${fieldName}[]" value="${o.trim()}" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                        <span class="ml-2 text-sm text-gray-700 group-hover:text-blue-700">${o.trim()}</span>
                    </label>
                `).join('');
                htmlInput = `<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1">${options}</div>`;
            } 
            else {
                // Text, Number, Date
                const type = attr.type === 'number' ? 'number' : (attr.type === 'date' ? 'date' : 'text');
                htmlInput = `<input type="${type}" name="${fieldName}" class="${inputClass}" placeholder="Isi ${attr.name}...">`;
            }

            div.innerHTML = `<label class="block mb-2 text-sm font-semibold text-gray-700">${attr.name} ${requiredStar}</label>${htmlInput}`;
            return div;
        }

        // Helper mengisi data (Checkbox array handling dll)
        function fillData(el, attr, val) {
            if (attr.type === 'checkbox') {
                let arr = Array.isArray(val) ? val : [];
                if(typeof val === 'string' && val.startsWith('[')) { 
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

        categorySelect.addEventListener('change', fetchAttributes);
        // Trigger load pertama kali jika sudah ada kategori terpilih
        if (categorySelect.value) fetchAttributes();
    });
</script>
@endpush