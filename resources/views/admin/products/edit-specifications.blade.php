@extends('layouts.admin')

@section('title', 'Edit Spesifikasi: ' . $product->name)

@push('styles')
<style>
    /* --- BOOTSTRAP-LIKE UTILITIES --- */
    .form-control {
        @apply w-full h-11 px-3 py-2 text-base text-gray-700 bg-white border border-gray-300 rounded-lg transition ease-in-out;
        @apply focus:text-gray-700 focus:bg-white focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100;
        @apply placeholder:text-gray-400 placeholder:text-sm;
    }
    
    textarea.form-control {
        @apply h-auto;
    }

    .card {
        @apply bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden;
    }

    .card-header {
        @apply px-5 py-3 border-b border-gray-100 bg-gray-50/50 flex items-center font-semibold text-gray-800;
    }

    .card-body {
        @apply p-5;
    }

    .required-label::after {
        content: " *";
        @apply text-red-500;
    }
    
    /* Scrollbar halus untuk list kategori */
    .category-list {
        max-height: 500px;
        overflow-y: auto;
    }
    .category-list::-webkit-scrollbar { width: 4px; }
    .category-list::-webkit-scrollbar-track { background: #f1f1f1; }
    .category-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto pb-20">
    
    {{-- Header --}}
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Edit Kategori & Spesifikasi</h1>
            <p class="text-sm text-gray-500 mt-1">Produk: <span class="font-semibold text-blue-600">{{ $product->name }}</span></p>
        </div>
        <a href="{{ route('admin.products.edit', $product->slug) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-50 transition shadow-sm">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Edit Produk
        </a>
    </div>

    <form action="{{ route('admin.products.update.specifications', $product->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            {{-- === SISI KIRI (FORM UTAMA) === --}}
            <div class="lg:col-span-8 space-y-6">

                {{-- DIV 1: PILIH KATEGORI --}}
                <div class="card border-l-4 border-l-blue-500">
                    <div class="card-header">
                        <i class="fa-solid fa-layer-group text-blue-500 mr-2"></i> 1. Pilih Kategori
                    </div>
                    <div class="card-body">
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1 required-label">Kategori Produk</label>
                        <select name="category_id" id="category_id" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" 
                                    data-attributes-url="{{ route('admin.categories.attributes', $category->id) }}" 
                                    {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        
                        <div class="mt-3 flex items-start gap-2 p-3 bg-yellow-50 border border-yellow-100 rounded-lg">
                            <i class="fa-solid fa-circle-exclamation text-yellow-600 mt-0.5 text-sm"></i>
                            <p class="text-xs text-yellow-700 leading-tight">
                                <strong>Perhatian:</strong> Mengubah kategori akan mereset form spesifikasi di bawah ini.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- DIV 2: SPESIFIKASI PRODUK (DINAMIS) --}}
                <div id="attributes-card" class="card hidden">
                    <div class="card-header">
                        <i class="fa-solid fa-clipboard-list text-blue-500 mr-2"></i> 2. Spesifikasi Produk
                    </div>
                    <div id="dynamic-attributes-container" class="card-body space-y-6">
                        {{-- Diisi via JS --}}
                    </div>
                </div>

                {{-- DIV 3: PENGATURAN DATA (SKU & TAGS) --}}
                <div class="card">
                    <div class="card-header">
                        <i class="fa-solid fa-tags text-blue-500 mr-2"></i> 3. Data Organisasi
                    </div>
                    <div class="card-body space-y-5">
                        {{-- SKU --}}
                        <div>
                            <label for="sku" class="block text-sm font-medium text-gray-700 mb-1">SKU Induk</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fa-solid fa-barcode text-gray-400"></i>
                                </span>
                                <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}" 
                                       class="form-control pl-10 font-mono text-sm uppercase tracking-wider" placeholder="AUTO-GEN">
                            </div>
                        </div>

                        {{-- Tags --}}
                        <div>
                            <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fa-solid fa-hashtag text-gray-400"></i>
                                </span>
                                <input type="text" name="tags" id="tags" value="{{ old('tags', $product->tags) }}" 
                                       class="form-control pl-10" placeholder="Contoh: Jasa, Perizinan, Cepat">
                            </div>
                            <p class="mt-1 text-xs text-gray-400">Pisahkan kata kunci dengan koma (,).</p>
                        </div>
                    </div>
                </div>

            </div>

            {{-- === SISI KANAN (DAFTAR KATEGORI) === --}}
            <div class="lg:col-span-4">
                <div class="card sticky top-6">
                    <div class="card-header justify-between">
                        <span class="flex items-center"><i class="fa-solid fa-list text-gray-500 mr-2"></i> Semua Kategori</span>
                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">{{ $categories->count() }}</span>
                    </div>
                    
                    {{-- List Kategori --}}
                    <div class="bg-white p-2">
                        <div class="relative mb-2 px-2">
                            <input type="text" id="searchCategory" placeholder="Cari kategori..." class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <i class="fa-solid fa-search absolute right-5 top-2.5 text-gray-400 text-xs"></i>
                        </div>
                        
                        <div class="category-list px-2 pb-2">
                            <ul class="space-y-1" id="categoryListUL">
                                @foreach($categories as $cat)
                                    <li>
                                        <button type="button" 
                                            onclick="selectCategory('{{ $cat->id }}')"
                                            class="w-full text-left px-3 py-2 rounded-md text-sm hover:bg-blue-50 hover:text-blue-600 transition-colors flex items-center justify-between group {{ $product->category_id == $cat->id ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600' }}">
                                            <span>{{ $cat->name }}</span>
                                            @if($product->category_id == $cat->id)
                                                <i class="fa-solid fa-check text-blue-500 text-xs"></i>
                                            @endif
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 border-t border-gray-100 text-xs text-gray-500">
                        <i class="fa-solid fa-lightbulb text-yellow-500 mr-1"></i> Tip: Klik nama kategori di atas untuk memilihnya secara cepat.
                    </div>
                </div>
            </div>

        </div>

        {{-- Action Buttons (Sticky) --}}
        <div class="sticky bottom-0 z-50 bg-white/95 backdrop-blur border-t border-gray-200 px-6 py-4 mt-8 flex justify-end gap-3 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] rounded-t-xl sm:rounded-none -mx-4 sm:-mx-0">
            <a href="{{ route('admin.products.edit', $product->slug) }}" class="px-6 py-2.5 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 transition-colors">
                Batal
            </a>
            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors flex items-center">
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
        // Trigger event change agar form spesifikasi dimuat
        select.dispatchEvent(new Event('change'));
        
        // Highlight UI di sidebar kanan (Opsional, sederhana)
        document.querySelectorAll('#categoryListUL button').forEach(btn => {
            btn.classList.remove('bg-blue-50', 'text-blue-700', 'font-semibold');
            btn.classList.add('text-gray-600');
            const icon = btn.querySelector('.fa-check');
            if(icon) icon.remove();
        });
        
        // Note: Untuk highlight yang akurat butuh logic lebih kompleks, 
        // tapi select box kiri sudah berubah itu yang utama.
    }

    // Simple Search Filter untuk Kategori Kanan
    document.getElementById('searchCategory').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let list = document.getElementById('categoryListUL');
        let items = list.getElementsByTagName('li');
        
        for (let i = 0; i < items.length; i++) {
            let txt = items[i].textContent || items[i].innerText;
            if (txt.toLowerCase().indexOf(filter) > -1) {
                items[i].style.display = "";
            } else {
                items[i].style.display = "none";
            }
        }
    });

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
            // Loading State
            attributesContainer.innerHTML = `
                <div class="flex flex-col items-center justify-center py-8 text-gray-400">
                    <i class="fas fa-circle-notch fa-spin text-3xl text-blue-500 mb-3"></i> 
                    <span class="text-sm font-medium">Memuat form spesifikasi...</span>
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
                    <div class="text-center py-8 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                        <span class="text-gray-400 text-sm italic">Tidak ada spesifikasi khusus untuk kategori ini.</span>
                    </div>`;
            }

        } catch (error) {
            console.error(error);
            attributesContainer.innerHTML = '<p class="text-red-500 text-sm text-center py-4">Gagal memuat spesifikasi. Silakan coba lagi.</p>';
        }
    }

    function createAttributeField(attribute) {
        const wrapper = document.createElement('div');
        const isRequired = attribute.is_required ? 'required' : '';
        const requiredMark = attribute.is_required ? '<span class="text-red-500">*</span>' : '';
        const inputName = `attributes[${attribute.slug}]`;
        
        const commonClass = "form-control"; // Pakai class CSS yang sudah dibuat di atas

        let inputHtml = '';

        if (attribute.type === 'select') {
            const opts = (attribute.options || '').split(',').map(o=>o.trim()).map(o=>`<option value="${o}">${o}</option>`).join('');
            inputHtml = `<select name="${inputName}" class="${commonClass}" ${isRequired}>
                            <option value="">-- Pilih --</option>
                            ${opts}
                         </select>`;
        } else if (attribute.type === 'textarea') {
            inputHtml = `<textarea name="${inputName}" rows="3" class="${commonClass}" ${isRequired}></textarea>`;
        } else if (attribute.type === 'checkbox') {
             const checks = (attribute.options || '').split(',').map(o=>o.trim()).map(o => `
                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                    <input type="checkbox" name="${inputName}[]" value="${o}" class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                    <span class="ml-3 text-sm font-medium text-gray-700">${o}</span>
                </label>
            `).join('');
            inputHtml = `<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">${checks}</div>`;
        } else {
            const type = attribute.type === 'number' ? 'number' : 'text';
            inputHtml = `<input type="${type}" name="${inputName}" class="${commonClass}" ${isRequired}>`;
        }

        wrapper.innerHTML = `<label class="block text-sm font-medium text-gray-700 mb-2">${attribute.name} ${requiredMark}</label>${inputHtml}`;
        return wrapper;
    }

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
            if(inp) {
                inp.value = value; 
            }
        }
    }

    if (categorySelect) {
        categorySelect.addEventListener('change', fetchAndRenderAttributes);
        fetchAndRenderAttributes();
    }
});
</script>
@endpush