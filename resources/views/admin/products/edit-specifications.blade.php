@extends('layouts.admin')

@section('title', 'Edit Spesifikasi: ' . $product->name)

@section('content')
<div class="max-w-7xl mx-auto pb-40"> {{-- Padding bottom gede biar gak ketutup tombol simpan --}}
    
    {{-- HEADER --}}
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Spesifikasi</h1>
            <p class="text-sm text-gray-500 mt-1">Produk: <span class="font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded">{{ $product->name }}</span></p>
        </div>
        <a href="{{ route('admin.products.edit', $product->slug) }}" class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition-colors">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    <form action="{{ route('admin.products.update.specifications', $product->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            {{-- === KOLOM KIRI (MAIN) === --}}
            <div class="lg:col-span-8 space-y-6">

                {{-- CARD 1: KATEGORI --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center text-blue-700 font-bold text-sm uppercase tracking-wide">
                        <i class="fa-solid fa-layer-group mr-2"></i> 1. Kategori Produk
                    </div>
                    <div class="p-6">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Kategori Terpilih <span class="text-red-500">*</span>
                            </label>
                            {{-- Select readonly, user pilih lewat sidebar --}}
                            <select name="category_id" id="category_id" class="w-full bg-gray-100 border border-gray-300 text-gray-500 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 cursor-not-allowed" readonly required>
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
                        
                        <div class="flex gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-sm">
                            <i class="fa-solid fa-circle-exclamation mt-0.5 text-amber-600"></i>
                            <div>
                                <strong class="font-bold">Perhatian:</strong> Ubah kategori melalui sidebar di kanan. Mengubah kategori akan mereset form spesifikasi.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: SPESIFIKASI (JS AREA) --}}
                <div id="attributes-card" class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden hidden transition-all">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center text-gray-700 font-bold text-sm uppercase tracking-wide">
                        <i class="fa-solid fa-list-check mr-2 text-blue-500"></i> 2. Form Spesifikasi
                    </div>
                    <div id="dynamic-attributes-container" class="p-6 space-y-5">
                        {{-- JS will render inputs here --}}
                    </div>
                </div>

                {{-- CARD 3: DATA TAMBAHAN --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center text-gray-700 font-bold text-sm uppercase tracking-wide">
                        <i class="fa-solid fa-tags mr-2 text-blue-500"></i> 3. Data Tambahan
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- SKU --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">SKU Induk</label>
                            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" 
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 font-mono uppercase placeholder-gray-400" 
                                   placeholder="AUTO-GEN">
                        </div>

                        {{-- Tags --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tags</label>
                            @php
                                $tags = $product->tags;
                                if(is_string($tags) && str_starts_with($tags, '[')) {
                                    $decoded = json_decode($tags, true);
                                    if(is_array($decoded)) $tags = implode(', ', $decoded);
                                }
                            @endphp
                            <input type="text" name="tags" value="{{ old('tags', $tags) }}" 
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 placeholder-gray-400" 
                                   placeholder="Contoh: Murah, Promo, Cepat">
                        </div>
                    </div>
                </div>

            </div>

            {{-- === KOLOM KANAN (SIDEBAR) === --}}
            <div class="lg:col-span-4">
                <div class="sticky top-6 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <span class="font-bold text-gray-700 text-sm uppercase"><i class="fa-solid fa-magnifying-glass mr-2"></i> Cari Kategori</span>
                        <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-0.5 rounded-full">{{ $categories->count() }}</span>
                    </div>
                    
                    <div class="p-3 border-b border-gray-100 bg-white">
                        <input type="text" id="searchCategory" placeholder="Ketik nama kategori..." 
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>
                    
                    {{-- Scroll Area --}}
                    <div class="overflow-y-auto p-2" style="max-height: 400px; scrollbar-width: thin;">
                        <ul id="categoryListUL" class="space-y-1">
                            @foreach($categories as $cat)
                                <li>
                                    <button type="button" onclick="selectCategory('{{ $cat->id }}')" 
                                        class="w-full text-left px-3 py-2.5 text-sm rounded-lg flex items-center justify-between group transition-all duration-200
                                        {{ $product->category_id == $cat->id 
                                            ? 'bg-blue-600 text-white font-semibold shadow-md' 
                                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                                        <span>{{ $cat->name }}</span>
                                        @if($product->category_id == $cat->id)
                                            <i class="fa-solid fa-check text-white text-xs"></i>
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
        <div class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 p-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] md:pl-[280px]">
            <div class="max-w-7xl mx-auto flex items-center justify-end gap-3">
                <a href="{{ route('admin.products.edit', $product->slug) }}" 
                   class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 transition-all">
                    Batal
                </a>
                <button type="submit" 
                        class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 shadow-lg shadow-blue-500/50 transition-all flex items-center">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Spesifikasi
                </button>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
    // --- 1. LOGIC KATEGORI & SIDEBAR ---
    function selectCategory(id) {
        const select = document.getElementById('category_id');
        select.value = id;
        select.dispatchEvent(new Event('change')); // Trigger event change

        // Update Visual Sidebar (Manual Tailwind Class Toggle)
        const buttons = document.querySelectorAll('#categoryListUL button');
        buttons.forEach(btn => {
            // Reset ke tampilan default (abu-abu)
            btn.className = "w-full text-left px-3 py-2.5 text-sm rounded-lg flex items-center justify-between group transition-all duration-200 text-gray-600 hover:bg-gray-100 hover:text-gray-900";
            
            // Remove check icon
            const icon = btn.querySelector('.fa-check');
            if(icon) icon.remove();

            // Jika tombol ini yang diklik
            if(btn.getAttribute('onclick').includes(`'${id}'`)) {
                // Set tampilan aktif (biru)
                btn.className = "w-full text-left px-3 py-2.5 text-sm rounded-lg flex items-center justify-between group transition-all duration-200 bg-blue-600 text-white font-semibold shadow-md";
                btn.innerHTML += '<i class="fa-solid fa-check text-white text-xs"></i>';
            }
        });
    }

    // Filter Pencarian
    document.getElementById('searchCategory').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let items = document.querySelectorAll('#categoryListUL li');
        items.forEach(item => {
            let text = item.textContent || item.innerText;
            item.style.display = text.toLowerCase().indexOf(filter) > -1 ? "" : "none";
        });
    });

    // --- 2. LOGIC RENDER FORM (JavaScript generate Tailwind HTML) ---
    document.addEventListener('DOMContentLoaded', () => {
        const existingAttributes = @json($existingAttributes);
        const categorySelect = document.getElementById('category_id');
        const attributesCard = document.getElementById('attributes-card');
        const attributesContainer = document.getElementById('dynamic-attributes-container');

        // Style Class Strings (Supaya konsisten)
        const labelClass = "block mb-2 text-sm font-medium text-gray-900";
        const inputClass = "bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5";
        const checkboxWrapperClass = "flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer";

        async function fetchAttributes() {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const url = selectedOption ? selectedOption.dataset.attributesUrl : null;

            if (!url) {
                attributesCard.classList.add('hidden');
                attributesContainer.innerHTML = '';
                return;
            }

            // Tampilkan Loading Spinner
            attributesCard.classList.remove('hidden');
            attributesContainer.innerHTML = `
                <div class="text-center py-10">
                    <i class="fas fa-circle-notch fa-spin text-4xl text-blue-500 mb-3"></i>
                    <p class="text-gray-500 font-medium">Memuat form spesifikasi...</p>
                </div>`;

            try {
                const res = await fetch(url);
                const data = await res.json();
                attributesContainer.innerHTML = ''; // Hapus loading

                if (data.length > 0) {
                    data.forEach(attr => {
                        const el = createField(attr);
                        attributesContainer.appendChild(el);
                        // Isi data lama jika ada
                        if (existingAttributes && existingAttributes[attr.slug]) {
                            fillData(el, attr, existingAttributes[attr.slug]);
                        }
                    });
                } else {
                    attributesContainer.innerHTML = `
                        <div class="p-4 text-sm text-blue-800 rounded-lg bg-blue-50 border border-blue-100" role="alert">
                            <span class="font-medium">Info:</span> Kategori ini tidak memiliki spesifikasi khusus.
                        </div>`;
                }
            } catch (err) {
                console.error(err);
                attributesContainer.innerHTML = `<div class="text-red-500 text-center font-bold">Gagal memuat data.</div>`;
            }
        }

        // Fungsi Generate HTML String
        function createField(attr) {
            const div = document.createElement('div');
            const requiredStar = attr.is_required ? '<span class="text-red-600 ml-1">*</span>' : '';
            const name = `attributes[${attr.slug}]`;
            
            let htmlInput = '';

            if (attr.type === 'select') {
                const options = attr.options.split(',').map(o => `<option value="${o.trim()}">${o.trim()}</option>`).join('');
                htmlInput = `
                    <select name="${name}" class="${inputClass}">
                        <option value="">-- Pilih --</option>
                        ${options}
                    </select>`;
            } 
            else if (attr.type === 'textarea') {
                htmlInput = `<textarea name="${name}" rows="4" class="${inputClass}" placeholder="Tulis deskripsi..."></textarea>`;
            } 
            else if (attr.type === 'checkbox') {
                const options = attr.options.split(',').map(o => `
                    <label class="${checkboxWrapperClass}">
                        <input type="checkbox" name="${name}[]" value="${o.trim()}" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm font-medium text-gray-900">${o.trim()}</span>
                    </label>
                `).join('');
                htmlInput = `<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">${options}</div>`;
            } 
            else {
                // Text, Number, Date
                const type = attr.type === 'number' ? 'number' : (attr.type === 'date' ? 'date' : 'text');
                htmlInput = `<input type="${type}" name="${name}" class="${inputClass}" placeholder="...">`;
            }

            div.innerHTML = `<label class="${labelClass}">${attr.name} ${requiredStar}</label>${htmlInput}`;
            return div;
        }

        // Fungsi Isi Data (Edit Mode)
        function fillData(el, attr, val) {
            if (attr.type === 'checkbox') {
                let arr = Array.isArray(val) ? val : [];
                if(typeof val === 'string' && val.startsWith('[')) { try { arr = JSON.parse(val); } catch(e){} }
                
                el.querySelectorAll('input[type="checkbox"]').forEach(chk => {
                    if (arr.includes(chk.value)) chk.checked = true;
                });
            } else {
                const input = el.querySelector(`[name^="attributes"]`);
                if(input) input.value = val;
            }
        }

        if (categorySelect) {
            categorySelect.addEventListener('change', fetchAttributes);
            // Auto load jika sudah ada value (saat edit)
            if (categorySelect.value) fetchAttributes();
        }
    });
</script>
@endpush