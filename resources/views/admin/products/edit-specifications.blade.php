@extends('layouts.admin')

@section('title', 'Edit Spesifikasi: ' . $product->name)

@section('content')
<div class="max-w-4xl mx-auto">
    
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
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

        <div class="space-y-6">
            
            {{-- 1. PENGATURAN DATA (SKU, Kategori, Tags) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Pengaturan Data</h2>
                
                <div class="space-y-4">
                    {{-- SKU --}}
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700 mb-1">SKU Induk</label>
                        <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    {{-- Kategori --}}
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1 required-label">Kategori <span class="text-red-500">*</span></label>
                        <select name="category_id" id="category_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" 
                                    data-attributes-url="{{ route('admin.categories.attributes', $category->id) }}" 
                                    {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-yellow-600 mt-1"><i class="fa-solid fa-triangle-exclamation"></i> Mengubah kategori akan mereset form spesifikasi di bawah.</p>
                    </div>

                    {{-- Tags --}}
                    <div>
                        <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                        <input type="text" name="tags" id="tags" value="{{ old('tags', $product->tags) }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Jasa, Perizinan, Cepat">
                    </div>
                </div>
            </div>

            {{-- 2. SPESIFIKASI TAMBAHAN (Dinamis) --}}
            <div id="attributes-card" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hidden">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Spesifikasi Tambahan</h2>
                <div id="dynamic-attributes-container" class="space-y-4">
                    {{-- Diisi via JS --}}
                </div>
            </div>

        </div>

        {{-- Action Buttons --}}
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.products.edit', $product->slug) }}" class="px-5 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition">
                Batal
            </a>
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg font-medium shadow-md hover:bg-indigo-700 transition flex items-center">
                <i class="fa-solid fa-save mr-2"></i> Simpan Spesifikasi
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Ambil data atribut lama (values) dari database
    const existingAttributes = {!! $product->existing_attributes_json ?? '{}' !!};
    
    const categorySelect = document.getElementById('category_id');
    const attributesCard = document.getElementById('attributes-card');
    const attributesContainer = document.getElementById('dynamic-attributes-container');

    // === LOGIC FETCH ATRIBUT DINAMIS ===
    async function fetchAndRenderAttributes() {
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        const url = selectedOption ? selectedOption.dataset.attributesUrl : null;

        if (!url) {
            attributesCard.classList.add('hidden');
            attributesContainer.innerHTML = '';
            return;
        }

        try {
            attributesContainer.innerHTML = '<div class="text-center py-4 text-gray-500"><i class="fas fa-circle-notch fa-spin text-indigo-500 mr-2"></i> Memuat form spesifikasi...</div>';
            attributesCard.classList.remove('hidden');
            
            const response = await fetch(url);
            if (!response.ok) throw new Error('Gagal mengambil data');
            const attributes = await response.json();
            
            attributesContainer.innerHTML = ''; // Clear loading

            if (attributes && attributes.length > 0) {
                attributes.forEach(attr => {
                    const field = createAttributeField(attr);
                    attributesContainer.appendChild(field);
                    
                    // Isi value jika ada di database (Mode Edit)
                    if (existingAttributes && existingAttributes[attr.slug] !== undefined) {
                        fillAttributeValue(field, attr, existingAttributes[attr.slug]);
                    }
                });
            } else {
                attributesContainer.innerHTML = '<p class="text-gray-400 italic text-center">Tidak ada spesifikasi khusus untuk kategori ini.</p>';
            }

        } catch (error) {
            console.error(error);
            attributesContainer.innerHTML = '<p class="text-red-500 text-sm">Gagal memuat spesifikasi.</p>';
        }
    }

    // Fungsi Helper Membuat HTML Field
    function createAttributeField(attribute) {
        const wrapper = document.createElement('div');
        const isRequired = attribute.is_required ? 'required' : '';
        const requiredMark = attribute.is_required ? '<span class="text-red-500">*</span>' : '';
        const inputName = `attributes[${attribute.slug}]`;
        const commonClass = "w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition";

        let inputHtml = '';

        if (attribute.type === 'select') {
            const opts = (attribute.options || '').split(',').map(o=>o.trim()).map(o=>`<option value="${o}">${o}</option>`).join('');
            inputHtml = `<select name="${inputName}" class="${commonClass}" ${isRequired}><option value="">-- Pilih --</option>${opts}</select>`;
        } else if (attribute.type === 'textarea') {
            inputHtml = `<textarea name="${inputName}" rows="3" class="${commonClass}" ${isRequired}></textarea>`;
        } else if (attribute.type === 'checkbox') {
             const checks = (attribute.options || '').split(',').map(o=>o.trim()).map(o => `
                <label class="inline-flex items-center mr-4 mb-2">
                    <input type="checkbox" name="${inputName}[]" value="${o}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700">${o}</span>
                </label>
            `).join('');
            inputHtml = `<div class="mt-2 p-3 bg-gray-50 rounded border border-gray-200">${checks}</div>`;
        } else {
            // Text / Number
            const type = attribute.type === 'number' ? 'number' : 'text';
            inputHtml = `<input type="${type}" name="${inputName}" class="${commonClass}" ${isRequired}>`;
        }

        wrapper.innerHTML = `<label class="block text-sm font-medium text-gray-700 mb-1">${attribute.name} ${requiredMark}</label>${inputHtml}`;
        return wrapper;
    }

    // Fungsi Helper Mengisi Nilai (Edit Mode)
    function fillAttributeValue(el, attr, val) {
        if (val === null || val === undefined) return;
        
        if (attr.type === 'checkbox') {
            let arr = Array.isArray(val) ? val : [val];
            // Handle JSON string if necessary
            if(typeof val === 'string' && val.startsWith('[')) { try { arr = JSON.parse(val); } catch(e){} }
            
            el.querySelectorAll('input[type="checkbox"]').forEach(chk => {
                if(arr.includes(chk.value)) chk.checked = true;
            });
        } else {
            const inp = el.querySelector(`[name^="attributes"]`);
            if(inp) inp.value = val;
        }
    }

    // Event Listener
    if (categorySelect) {
        categorySelect.addEventListener('change', fetchAndRenderAttributes);
        // Load on start
        fetchAndRenderAttributes();
    }
});
</script>
@endpush