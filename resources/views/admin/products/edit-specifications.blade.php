@extends('layouts.admin')

@section('title', 'Edit Spesifikasi: ' . $product->name)

@push('styles')
<style>
    /* --- BOOTSTRAP 5 LOOK-ALIKE (Built with Tailwind) --- */

    /* 1. Card Style (Kotak Putih) */
    .bs-card {
        @apply bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mb-6;
    }
    .bs-card-header {
        @apply px-5 py-4 bg-gray-50 border-b border-gray-200 font-semibold text-gray-700 flex items-center text-sm uppercase tracking-wide;
    }
    .bs-card-body {
        @apply p-5;
    }

    /* 2. Form Control (Input Style) */
    .form-control, .form-select {
        @apply w-full px-3 py-2 text-base text-gray-700 bg-white bg-clip-padding border border-gray-300 rounded-lg transition ease-in-out m-0;
        /* Tinggi input agar pas (44px) */
        @apply h-11; 
        /* Efek Focus (Ring Biru) */
        @apply focus:text-gray-700 focus:bg-white focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100;
    }
    
    /* Textarea tidak boleh fix height */
    textarea.form-control {
        height: auto !important;
        min-height: 100px;
    }

    /* Label Style */
    .form-label {
        @apply block text-sm font-medium text-gray-700 mb-2;
    }

    /* Scrollbar Kustom untuk Sidebar Kanan */
    .custom-scroll {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
    
    /* Utils */
    .required-mark { @apply text-red-500 ml-0.5; }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto pb-24">
    
    {{-- HEADER --}}
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Edit Kategori & Spesifikasi</h1>
            <p class="text-sm text-gray-500 mt-1">Produk: <span class="font-semibold text-blue-600">{{ $product->name }}</span></p>
        </div>
        <a href="{{ route('admin.products.edit', $product->slug) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-50 transition shadow-sm hover:text-blue-600">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Edit Produk
        </a>
    </div>

    <form action="{{ route('admin.products.update.specifications', $product->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            {{-- === KOLOM KIRI (8 BAGIAN) === --}}
            <div class="lg:col-span-8 space-y-6">

                {{-- CARD 1: PILIH KATEGORI --}}
                <div class="bs-card border-l-4 border-l-blue-500">
                    <div class="bs-card-header">
                        <i class="fa-solid fa-layer-group text-blue-500 mr-2"></i> 1. Pilih Kategori
                    </div>
                    <div class="bs-card-body">
                        <div class="mb-1">
                            <label class="form-label">Kategori Produk <span class="required-mark">*</span></label>
                            <select name="category_id" id="category_id" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                        data-attributes-url="{{ route('admin.categories.attributes', $category->id) }}" 
                                        {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mt-3 flex items-start gap-3 p-3 bg-amber-50 border border-amber-100 rounded-lg text-amber-800 text-sm">
                            <i class="fa-solid fa-circle-exclamation mt-0.5 text-amber-600"></i>
                            <p><strong>Perhatian:</strong> Mengubah kategori akan mereset form spesifikasi di bawah ini.</p>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: SPESIFIKASI PRODUK (DINAMIS) --}}
                <div id="attributes-card" class="bs-card hidden">
                    <div class="bs-card-header">
                        <i class="fa-solid fa-list-check text-blue-500 mr-2"></i> 2. Spesifikasi Produk
                    </div>
                    <div id="dynamic-attributes-container" class="bs-card-body space-y-5">
                        {{-- Form Dinamis akan muncul di sini lewat JS --}}
                    </div>
                </div>

                {{-- CARD 3: DATA ORGANISASI --}}
                <div class="bs-card">
                    <div class="bs-card-header">
                        <i class="fa-solid fa-tags text-blue-500 mr-2"></i> 3. Data Organisasi
                    </div>
                    <div class="bs-card-body space-y-5">
                        {{-- SKU --}}
                        <div>
                            <label class="form-label">SKU Induk</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fa-solid fa-barcode"></i>
                                </span>
                                <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}" 
                                       class="form-control pl-10 font-mono text-sm uppercase tracking-wider" 
                                       placeholder="AUTO-GEN">
                            </div>
                        </div>

                        {{-- Tags (Logic Fix JSON) --}}
                        <div>
                            <label class="form-label">Tags</label>
                            @php
                                // Membersihkan format JSON ["IZIN"] menjadi string biasa "IZIN"
                                $cleanTags = $product->tags;
                                if (is_string($cleanTags) && \Illuminate\Support\Str::startsWith($cleanTags, '[')) {
                                    $decoded = json_decode($cleanTags, true);
                                    if (is_array($decoded)) {
                                        $cleanTags = implode(', ', $decoded);
                                    }
                                }
                            @endphp
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fa-solid fa-hashtag"></i>
                                </span>
                                <input type="text" name="tags" id="tags" value="{{ old('tags', $cleanTags) }}" 
                                       class="form-control pl-10" 
                                       placeholder="Contoh: Jasa, Perizinan, Cepat">
                            </div>
                            <p class="mt-1 text-xs text-gray-400">Pisahkan kata kunci dengan koma (,).</p>
                        </div>
                    </div>
                </div>

            </div>

            {{-- === KOLOM KANAN (SIDEBAR KATEGORI) === --}}
            <div class="lg:col-span-4">
                <div class="bs-card sticky top-6">
                    <div class="bs-card-header justify-between bg-white">
                        <span><i class="fa-solid fa-bars text-gray-400 mr-2"></i> Semua Kategori</span>
                        <span class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full font-bold">{{ $categories->count() }}</span>
                    </div>
                    
                    <div class="p-3 border-b border-gray-100">
                        <div class="relative">
                            <input type="text" id="searchCategory" placeholder="Cari kategori..." 
                                   class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-400 outline-none transition">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                        </div>
                    </div>
                    
                    <div class="category-list custom-scroll p-2" style="max-height: 400px; overflow-y: auto;">
                        <ul class="space-y-1" id="categoryListUL">
                            @foreach($categories as $cat)
                                <li>
                                    <button type="button" 
                                        onclick="selectCategory('{{ $cat->id }}')"
                                        class="w-full text-left px-3 py-2.5 rounded-lg text-sm transition-all flex items-center justify-between group 
                                        {{ $product->category_id == $cat->id ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                        <span>{{ $cat->name }}</span>
                                        @if($product->category_id == $cat->id)
                                            <i class="fa-solid fa-check text-blue-600 text-xs"></i>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

        </div>

        {{-- STICKY ACTION BAR --}}
        <div class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 px-6 py-4 flex justify-end gap-3 shadow-[0_-4px_20px_rgba(0,0,0,0.05)] md:pl-[280px]">
             <a href="{{ route('admin.products.edit', $product->slug) }}" class="px-6 py-2.5 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors shadow-sm">
                Batal
            </a>
            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-lg shadow-lg hover:bg-blue-700 hover:shadow-blue-500/30 transition-all flex items-center">
                <i class="fa-solid fa-save mr-2"></i> Simpan Spesifikasi
            </button>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
    // Fungsi Select Category dari Sidebar Kanan
    function selectCategory(id) {
        const select = document.getElementById('category_id');
        select.value = id;
        select.dispatchEvent(new Event('change'));
        
        // Visual Feedback Sederhana
        document.querySelectorAll('#categoryListUL button').forEach(btn => {
            btn.className = "w-full text-left px-3 py-2.5 rounded-lg text-sm transition-all flex items-center justify-between group text-gray-600 hover:bg-gray-50 hover:text-gray-900";
            const icon = btn.querySelector('.fa-check');
            if(icon) icon.remove();
        });
    }

    // Search Filter
    document.getElementById('searchCategory').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let list = document.getElementById('categoryListUL');
        let items = list.getElementsByTagName('li');
        for (let i = 0; i < items.length; i++) {
            let txt = items[i].textContent || items[i].innerText;
            items[i].style.display = txt.toLowerCase().indexOf(filter) > -1 ? "" : "none";
        }
    });

document.addEventListener('DOMContentLoaded', () => {
    // Data dari Controller
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
            // Loading State Modern
            attributesContainer.innerHTML = `
                <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                    <i class="fas fa-circle-notch fa-spin text-3xl text-blue-500 mb-3"></i> 
                    <span class="text-sm font-medium">Sedang memuat formulir...</span>
                </div>`;
            attributesCard.classList.remove('hidden');
            
            const response = await fetch(url);
            if (!response.ok) throw new Error('Gagal mengambil data');
            
            const attributeDefinitions = await response.json();
            attributesContainer.innerHTML = ''; 

            if (attributeDefinitions && attributeDefinitions.length > 0) {
                attributeDefinitions.forEach(attrDef => {
                    const fieldElement = createAttributeField(attrDef);
                    attributesContainer.appendChild(fieldElement);
                    
                    if (existingAttributes && existingAttributes[attrDef.slug] !== undefined) {
                        fillAttributeValue(fieldElement, attrDef, existingAttributes[attrDef.slug]);
                    }
                });
            } else {
                attributesContainer.innerHTML = `
                    <div class="text-center py-8 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                        <span class="text-gray-500 text-sm font-medium">Tidak ada spesifikasi khusus untuk kategori ini.</span>
                    </div>`;
            }

        } catch (error) {
            console.error(error);
            attributesContainer.innerHTML = '<p class="text-red-500 text-sm text-center py-4">Gagal memuat spesifikasi. Silakan refresh halaman.</p>';
        }
    }

    // --- MEMBUAT INPUT DENGAN STYLE BOOTSTRAP ---
    function createAttributeField(attribute) {
        const wrapper = document.createElement('div');
        const isRequired = attribute.is_required ? 'required' : '';
        const requiredMark = attribute.is_required ? '<span class="required-mark">*</span>' : '';
        const inputName = `attributes[${attribute.slug}]`;
        
        // Class Bootstrap-like yang sudah didefinisikan di CSS atas
        const inputClass = "form-control"; 

        let inputHtml = '';

        if (attribute.type === 'select') {
            const opts = (attribute.options || '').split(',').map(o=>o.trim()).map(o=>`<option value="${o}">${o}</option>`).join('');
            inputHtml = `<select name="${inputName}" class="form-select" ${isRequired}>
                            <option value="">-- Pilih ${attribute.name} --</option>
                            ${opts}
                         </select>`;
        } else if (attribute.type === 'textarea') {
            inputHtml = `<textarea name="${inputName}" rows="3" class="form-control" ${isRequired}></textarea>`;
        } else if (attribute.type === 'checkbox') {
             const checks = (attribute.options || '').split(',').map(o=>o.trim()).map(o => `
                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-200 cursor-pointer transition-all">
                    <input type="checkbox" name="${inputName}[]" value="${o}" class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                    <span class="ml-3 text-sm font-medium text-gray-700">${o}</span>
                </label>
            `).join('');
            inputHtml = `<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">${checks}</div>`;
        } else {
            // Text / Number
            const type = attribute.type === 'number' ? 'number' : 'text';
            inputHtml = `<input type="${type}" name="${inputName}" class="${inputClass}" ${isRequired}>`;
        }

        wrapper.innerHTML = `<label class="form-label">${attribute.name} ${requiredMark}</label>${inputHtml}`;
        return wrapper;
    }

    // Fungsi Isi Nilai
    function fillAttributeValue(el, attrDef, value) {
        if (value === null || value === undefined) return;
        
        if (attrDef.type === 'checkbox') {
            let arr = Array.isArray(value) ? value : [value];
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
        fetchAndRenderAttributes();
    }
});
</script>
@endpush