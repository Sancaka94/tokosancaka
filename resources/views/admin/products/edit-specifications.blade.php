@extends('layouts.admin')

@section('title', 'Edit Spesifikasi: ' . $product->name)

@push('styles')
<style>
    /* --- CUSTOM UI SYSTEM (Bootstrap-ish via Tailwind) --- */

    /* 1. Card Container */
    .bs-card {
        @apply bg-white border border-gray-200 rounded-xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] overflow-hidden mb-6 transition-all hover:shadow-md;
    }
    .bs-card-header {
        @apply px-6 py-4 bg-gray-50/50 border-b border-gray-100 font-bold text-gray-700 flex items-center text-sm uppercase tracking-wide;
    }
    .bs-card-body {
        @apply p-6;
    }

    /* 2. Form Inputs */
    .form-control, .form-select {
        @apply w-full px-4 py-2.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg transition duration-200 ease-in-out;
        @apply focus:text-gray-900 focus:bg-white focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10;
        min-height: 46px; /* Tinggi ergonomis */
    }

    /* Checkbox Style */
    .form-check-input {
        @apply w-5 h-5 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 focus:ring-2 cursor-pointer;
    }

    /* Label */
    .form-label {
        @apply block text-sm font-semibold text-gray-700 mb-2;
    }

    /* 3. Custom Scrollbar untuk Sidebar */
    .custom-scroll::-webkit-scrollbar {
        width: 5px;
    }
    .custom-scroll::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    .custom-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    .custom-scroll::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Utils */
    .required-mark { @apply text-red-500 ml-0.5 font-bold; }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto pb-32">
    
    {{-- HEADER SECTION --}}
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-800 tracking-tight">Edit Spesifikasi & Kategori</h1>
            <p class="text-sm text-gray-500 mt-1">Mengedit produk: <span class="font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded">{{ $product->name }}</span></p>
        </div>
        <a href="{{ route('admin.products.edit', $product->slug) }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 hover:border-blue-300 transition shadow-sm group">
            <i class="fa-solid fa-arrow-left mr-2 text-gray-400 group-hover:text-blue-500 transition"></i> Kembali ke Produk
        </a>
    </div>

    <form action="{{ route('admin.products.update.specifications', $product->id) }}" method="POST" id="specForm">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            {{-- === KOLOM KIRI (MAIN FORM) === --}}
            <div class="lg:col-span-8 space-y-6">

                {{-- CARD 1: KATEGORI --}}
                <div class="bs-card border-l-4 border-l-blue-500">
                    <div class="bs-card-header text-blue-700">
                        <i class="fa-solid fa-layer-group mr-3"></i> 1. Kategori Produk
                    </div>
                    <div class="bs-card-body">
                        <div class="mb-4">
                            <label class="form-label">Kategori Terpilih <span class="required-mark">*</span></label>
                            
                            {{-- Select Asli (Hidden/Functional) --}}
                            <select name="category_id" id="category_id" class="form-select bg-gray-50 cursor-not-allowed" required>
                                <option value="">-- Pilih Kategori dari Sidebar Kanan --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                            data-attributes-url="{{ route('admin.categories.attributes', $category->id) }}" 
                                            {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-2"><i class="fa-solid fa-circle-info mr-1"></i> Gunakan sidebar di sebelah kanan untuk mengganti kategori dengan cepat.</p>
                        </div>
                        
                        {{-- Alert Info --}}
                        <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-100 rounded-lg text-amber-800 text-sm">
                            <i class="fa-solid fa-triangle-exclamation text-lg mt-0.5 text-amber-500"></i>
                            <div>
                                <strong class="block mb-1 font-semibold">Perhatian Penting:</strong>
                                Mengubah kategori akan <u>menghapus</u> data spesifikasi yang sudah diisi sebelumnya karena kolom spesifikasi berbeda tiap kategori.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: SPESIFIKASI (DINAMIS) --}}
                <div id="attributes-card" class="bs-card hidden transition-all duration-500">
                    <div class="bs-card-header text-gray-800">
                        <i class="fa-solid fa-list-check mr-3 text-blue-500"></i> 2. Form Spesifikasi
                    </div>
                    <div id="dynamic-attributes-container" class="bs-card-body space-y-6">
                        {{-- JS will inject inputs here --}}
                    </div>
                </div>

                {{-- CARD 3: DATA TAMBAHAN --}}
                <div class="bs-card">
                    <div class="bs-card-header text-gray-800">
                        <i class="fa-solid fa-tags mr-3 text-blue-500"></i> 3. Data Organisasi
                    </div>
                    <div class="bs-card-body space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- SKU --}}
                            <div>
                                <label class="form-label">SKU Induk</label>
                                <div class="relative group">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition">
                                        <i class="fa-solid fa-barcode"></i>
                                    </span>
                                    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" 
                                           class="form-control pl-10 font-mono text-sm tracking-wider uppercase" 
                                           placeholder="AUTO-GEN">
                                </div>
                            </div>

                            {{-- Tags --}}
                            <div>
                                <label class="form-label">Tags Pencarian</label>
                                @php
                                    // Fix JSON Tag format
                                    $cleanTags = $product->tags;
                                    if (is_string($cleanTags) && \Illuminate\Support\Str::startsWith($cleanTags, '[')) {
                                        $decoded = json_decode($cleanTags, true);
                                        if (is_array($decoded)) $cleanTags = implode(', ', $decoded);
                                    }
                                @endphp
                                <div class="relative group">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition">
                                        <i class="fa-solid fa-hashtag"></i>
                                    </span>
                                    <input type="text" name="tags" value="{{ old('tags', $cleanTags) }}" 
                                           class="form-control pl-10" 
                                           placeholder="Contoh: Murah, Cepat, Promo">
                                </div>
                                <p class="mt-1.5 text-xs text-gray-400">Pisahkan dengan koma (,).</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- === KOLOM KANAN (SIDEBAR) === --}}
            <div class="lg:col-span-4 relative">
                <div class="bs-card sticky top-6 z-10 border-t-4 border-t-indigo-500">
                    <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center bg-white">
                        <h3 class="font-bold text-gray-700 text-sm uppercase"><i class="fa-solid fa-filter mr-2 text-indigo-500"></i> Pilih Kategori</h3>
                        <span class="bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-md font-bold">{{ $categories->count() }}</span>
                    </div>
                    
                    <div class="p-4 bg-gray-50 border-b border-gray-100">
                        <div class="relative">
                            <input type="text" id="searchCategory" placeholder="Cari nama kategori..." 
                                   class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition shadow-sm">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                        </div>
                    </div>
                    
                    <div class="custom-scroll p-2 overflow-y-auto bg-white" style="max-height: 500px;">
                        <ul class="space-y-1" id="categoryListUL">
                            @foreach($categories as $cat)
                                <li>
                                    <button type="button" 
                                            onclick="selectCategory('{{ $cat->id }}')"
                                            class="w-full text-left px-3 py-2.5 rounded-lg text-sm transition-all flex items-center justify-between group border border-transparent
                                            {{ $product->category_id == $cat->id 
                                                ? 'bg-blue-50 text-blue-700 font-semibold border-blue-200 shadow-sm' 
                                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 hover:border-gray-200' }}">
                                        <span class="truncate pr-2">{{ $cat->name }}</span>
                                        
                                        @if($product->category_id == $cat->id)
                                            <i class="category-icon fa-solid fa-circle-check text-blue-600"></i>
                                        @else
                                            <i class="category-icon fa-solid fa-chevron-right text-gray-300 text-xs group-hover:text-gray-500"></i>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

        </div>

        {{-- STICKY FOOTER ACTION --}}
        <div class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 px-6 py-4 shadow-[0_-5px_20px_rgba(0,0,0,0.05)] md:pl-[280px] flex items-center justify-between animate-fade-in-up">
            <div class="hidden sm:block text-sm text-gray-500">
                <i class="fa-solid fa-circle-info mr-1 text-blue-500"></i> Pastikan semua field bertanda <span class="text-red-500">*</span> sudah terisi.
            </div>
            <div class="flex items-center gap-3 ml-auto">
                <a href="{{ route('admin.products.edit', $product->slug) }}" class="px-6 py-2.5 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 transition shadow-sm text-sm">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-lg shadow-lg shadow-blue-500/30 hover:bg-blue-700 hover:shadow-blue-600/40 transition-all flex items-center text-sm transform active:scale-95">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Spesifikasi
                </button>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
    // 1. Logic Pencarian & Seleksi Kategori
    function selectCategory(id) {
        const select = document.getElementById('category_id');
        select.value = id;
        select.dispatchEvent(new Event('change')); // Trigger event change untuk load form
        
        // Update Tampilan List
        document.querySelectorAll('#categoryListUL button').forEach(btn => {
            // Reset style ke default
            btn.className = "w-full text-left px-3 py-2.5 rounded-lg text-sm transition-all flex items-center justify-between group border border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900 hover:border-gray-200";
            
            // Reset icon
            const icon = btn.querySelector('.category-icon');
            if(icon) {
                icon.className = "category-icon fa-solid fa-chevron-right text-gray-300 text-xs group-hover:text-gray-500";
            }
            
            // Apply style active jika diklik
            if (btn.getAttribute('onclick').includes(`'${id}'`)) {
                btn.className = "w-full text-left px-3 py-2.5 rounded-lg text-sm transition-all flex items-center justify-between group border border-blue-200 bg-blue-50 text-blue-700 font-semibold shadow-sm";
                if(icon) {
                    icon.className = "category-icon fa-solid fa-circle-check text-blue-600";
                }
            }
        });
    }

    document.getElementById('searchCategory').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let list = document.getElementById('categoryListUL');
        let items = list.getElementsByTagName('li');
        for (let i = 0; i < items.length; i++) {
            let txt = items[i].textContent || items[i].innerText;
            items[i].style.display = txt.toLowerCase().indexOf(filter) > -1 ? "" : "none";
        }
    });

    // 2. Logic Form Dinamis (Attributes)
    document.addEventListener('DOMContentLoaded', () => {
        const existingAttributes = @json($existingAttributes); 
        const categorySelect = document.getElementById('category_id');
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
                // Tampilkan Card dengan Loading Spinner
                attributesCard.classList.remove('hidden');
                attributesContainer.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-12 text-gray-400 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                        <i class="fas fa-circle-notch fa-spin text-4xl text-blue-500 mb-4"></i> 
                        <span class="text-sm font-semibold text-gray-600">Memuat Formulir Spesifikasi...</span>
                    </div>`;
                
                const response = await fetch(url);
                if (!response.ok) throw new Error('Network error');
                
                const attributeDefinitions = await response.json();
                attributesContainer.innerHTML = ''; // Clear loading

                if (attributeDefinitions && attributeDefinitions.length > 0) {
                    attributeDefinitions.forEach(attrDef => {
                        const fieldElement = createAttributeField(attrDef);
                        attributesContainer.appendChild(fieldElement);
                        
                        // Isi data jika sedang edit
                        if (existingAttributes && existingAttributes[attrDef.slug] !== undefined) {
                            fillAttributeValue(fieldElement, attrDef, existingAttributes[attrDef.slug]);
                        }
                    });
                } else {
                    attributesContainer.innerHTML = `
                        <div class="text-center py-8 bg-gray-50 rounded-xl border border-gray-200">
                            <i class="fa-solid fa-clipboard-check text-green-500 text-3xl mb-2"></i>
                            <p class="text-gray-600 font-medium">Kategori ini tidak memerlukan spesifikasi khusus.</p>
                            <p class="text-xs text-gray-400">Silakan lanjutkan simpan.</p>
                        </div>`;
                }

            } catch (error) {
                console.error(error);
                attributesContainer.innerHTML = `
                    <div class="text-center py-6 bg-red-50 rounded-lg border border-red-100 text-red-600">
                        <i class="fa-solid fa-circle-xmark text-xl mb-2"></i>
                        <p>Gagal memuat data. <button type="button" onclick="selectCategory('${categorySelect.value}')" class="underline font-bold hover:text-red-800">Coba lagi</button></p>
                    </div>`;
            }
        }

        // Helper: Buat HTML Element Input
        function createAttributeField(attribute) {
            const wrapper = document.createElement('div');
            // Style wrapper agar rapi
            wrapper.className = "bg-gray-50/50 p-4 rounded-lg border border-gray-100 hover:border-gray-300 transition-colors";
            
            const isRequired = attribute.is_required ? 'required' : '';
            const requiredMark = attribute.is_required ? '<span class="required-mark">*</span>' : '';
            const inputName = `attributes[${attribute.slug}]`;
            
            // Re-use CSS classes defined in @push('styles')
            const baseInputClass = "form-control"; 

            let inputHtml = '';

            if (attribute.type === 'select') {
                const options = (attribute.options || '').split(',').map(o=>o.trim());
                const optsHtml = options.map(o => `<option value="${o}">${o}</option>`).join('');
                inputHtml = `
                    <select name="${inputName}" class="form-select cursor-pointer" ${isRequired}>
                        <option value="">-- Pilih ${attribute.name} --</option>
                        ${optsHtml}
                    </select>`;
            } 
            else if (attribute.type === 'textarea') {
                inputHtml = `<textarea name="${inputName}" rows="3" class="form-control min-h-[100px]" placeholder="Masukkan deskripsi..." ${isRequired}></textarea>`;
            } 
            else if (attribute.type === 'checkbox') {
                const options = (attribute.options || '').split(',').map(o=>o.trim());
                const checksHtml = options.map(o => `
                    <label class="flex items-center p-3 bg-white border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-300 cursor-pointer transition-all shadow-sm group">
                        <input type="checkbox" name="${inputName}[]" value="${o}" class="form-check-input">
                        <span class="ml-3 text-sm font-medium text-gray-700 group-hover:text-blue-700 select-none">${o}</span>
                    </label>
                `).join('');
                inputHtml = `<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">${checksHtml}</div>`;
            } 
            else {
                // Text / Number / Date
                const type = attribute.type === 'number' ? 'number' : (attribute.type === 'date' ? 'date' : 'text');
                inputHtml = `<input type="${type}" name="${inputName}" class="${baseInputClass}" placeholder="Masukkan ${attribute.name}..." ${isRequired}>`;
            }

            wrapper.innerHTML = `<label class="form-label text-gray-800">${attribute.name} ${requiredMark}</label>${inputHtml}`;
            return wrapper;
        }

        function fillAttributeValue(el, attrDef, value) {
            if (value === null || value === undefined) return;
            
            if (attrDef.type === 'checkbox') {
                let arr = Array.isArray(value) ? value : [value];
                // Handle JSON string if stored as string
                if(typeof value === 'string' && value.startsWith('[')) { try { arr = JSON.parse(value); } catch(e){} }
                
                el.querySelectorAll('input[type="checkbox"]').forEach(chk => {
                    if(arr.includes(chk.value)) chk.checked = true;
                });
            } else {
                const inp = el.querySelector(`[name^="attributes"]`);
                if(inp) inp.value = value;
            }
        }

        if (categorySelect) {
            categorySelect.addEventListener('change', fetchAndRenderAttributes);
            // Trigger load pertama kali jika sudah ada kategori
            if(categorySelect.value) fetchAndRenderAttributes();
        }
    });
</script>
@endpush