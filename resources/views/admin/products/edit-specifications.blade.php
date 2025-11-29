@extends('layouts.admin')

@section('title', 'Edit Spesifikasi: ' . $product->name)

@section('content')
<div class="max-w-7xl mx-auto pb-40">

    {{-- HEADER --}}
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Edit Spesifikasi</h1>
            <p class="text-sm text-gray-500 mt-1">
                Produk:
                <span class="font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-lg border border-blue-100">
                    {{ $product->name }}
                </span>
            </p>
        </div>

        <a href="{{ route('admin.products.edit', $product->slug) }}"
           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition">
            <i class="fa-solid fa-arrow-left mr-2 text-gray-400"></i> Kembali
        </a>
    </div>

    {{-- FORM --}}
    <form action="{{ route('admin.products.update.specifications', $product->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            {{-- ===================== KOLOM KIRI ===================== --}}
            <div class="lg:col-span-8 space-y-8">

                {{-- CARD 1: KATEGORI --}}
                <div class="card-wrapper">
                    <div class="card-header">
                        <i class="fa-solid fa-layer-group text-blue-600"></i>
                        <span>1. Kategori Produk</span>
                    </div>

                    <div class="card-body space-y-6">

                        {{-- Kategori Readonly --}}
                        <div>
                            <label class="form-label">Kategori Terpilih <span class="text-red-600">*</span></label>

                            <div class="relative">
                                <select name="category_id" id="category_id"
                                    class="form-select bg-gray-100 text-gray-500 cursor-not-allowed"
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

                                <span class="icon-right"><i class="fa-solid fa-lock text-xs"></i></span>
                            </div>
                        </div>

                        {{-- Info --}}
                        <div class="info-box">
                            <i class="fa-solid fa-circle-info text-lg mt-0.5"></i>
                            <p><strong>Info:</strong> Ubah kategori melalui panel kanan. Mengubah kategori akan mereset form spesifikasi.</p>
                        </div>

                    </div>
                </div>

                {{-- CARD 2: SPESIFIKASI --}}
                <div id="attributes-card" class="card-wrapper hidden">
                    <div class="card-header">
                        <i class="fa-solid fa-list-check text-blue-600"></i>
                        <span>2. Isi Spesifikasi</span>
                    </div>

                    <div id="dynamic-attributes-container" class="card-body space-y-6">
                        {{-- JS Inject --}}
                    </div>
                </div>

                {{-- CARD 3: DATA TAMBAHAN --}}
                <div class="card-wrapper">
                    <div class="card-header">
                        <i class="fa-solid fa-tags text-blue-600"></i>
                        <span>3. Data Tambahan</span>
                    </div>

                    <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">

                        {{-- SKU --}}
                        <div>
                            <label class="form-label">SKU Induk</label>

                            <div class="relative">
                                <i class="fa-solid fa-barcode icon-left"></i>
                                <input type="text" name="sku"
                                    value="{{ old('sku', $product->sku) }}"
                                    class="form-input pl-10 font-mono uppercase"
                                    placeholder="AUTO-GEN">
                            </div>
                        </div>

                        {{-- Tags --}}
                        <div>
                            <label class="form-label">Tags</label>

                            @php
                                $tags = $product->tags;
                                if(is_string($tags) && str_starts_with($tags, '[')) {
                                    $decoded = json_decode($tags, true);
                                    $tags = is_array($decoded) ? implode(', ', $decoded) : $tags;
                                }
                            @endphp

                            <div class="relative">
                                <i class="fa-solid fa-hashtag icon-left"></i>
                                <input type="text" name="tags"
                                    value="{{ old('tags', $tags) }}"
                                    class="form-input pl-10"
                                    placeholder="Contoh: Promo, Terbaru">
                            </div>
                        </div>

                    </div>
                </div>

                {{-- FOOTER FIXED --}}
                <div class="footer-fixed">
                    <div class="flex justify-end gap-3 max-w-7xl mx-auto px-4">

                        <a href="{{ route('admin.products.edit', $product->slug) }}"
                           class="btn-cancel">Batal</a>

                        <button type="submit" class="btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Spesifikasi
                        </button>
                    </div>
                </div>

            </div>

            {{-- ===================== KOLOM KANAN ===================== --}}
            <div class="lg:col-span-4">

                <div class="card-sidebar">

                    {{-- Header --}}
                    <div class="sidebar-header">
                        <h3><i class="fa-solid fa-magnifying-glass text-blue-600"></i> Pilih Kategori</h3>
                        <span class="badge-count">{{ $categories->count() }}</span>
                    </div>

                    {{-- SEARCH --}}
                    <div class="sidebar-search">
                        <input id="searchCategory" type="text" placeholder="Cari kategori..." class="form-input">
                    </div>

                    {{-- LIST --}}
                    <div class="sidebar-list">
                        <ul id="categoryListUL" class="space-y-1">
                            @foreach($categories as $cat)
                                <li>
                                    <button type="button" onclick="selectCategory('{{ $cat->id }}')"
                                        class="sidebar-item {{ $product->category_id == $cat->id ? 'active' : '' }}">
                                        <span>{{ $cat->name }}</span>
                                        @if($product->category_id == $cat->id)
                                            <i class="fa-solid fa-check text-white text-xs"></i>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- ADD CATEGORY --}}
                    <div class="sidebar-add">
                        <label class="add-label">
                            <i class="fa-solid fa-plus-circle"></i> Tambah Kategori Baru
                        </label>

                        <div class="flex gap-2">
                            <input type="text" id="new_category_name" placeholder="Nama kategori..." class="form-input">

                            <button type="button" onclick="addNewCategory()" class="btn-primary px-3">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>

                        <p id="add-cat-msg" class="text-xs mt-1 hidden"></p>

                    </div>

                </div>

            </div>

        </div>
    </form>
</div>
@endsection


@push('scripts')
<script>
    // --- 1. LOGIC TAMBAH KATEGORI BARU ---
    async function addNewCategory() {
        const input = document.getElementById('new_category_name');
        const msg = document.getElementById('add-cat-msg');
        const name = input.value.trim();

        if(!name) {
            input.classList.add('border-red-500');
            return;
        }

        // Tampilkan loading (visual)
        const btn = input.nextElementSibling;
        const originalBtnContent = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        try {
            // CONTOH FETCH: Sesuaikan URL route store category Anda
            // const response = await fetch("{{-- route('admin.categories.storeAjax') --}}", {
            //     method: 'POST',
            //     headers: {
            //         'Content-Type': 'application/json',
            //         'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            //     },
            //     body: JSON.stringify({ name: name })
            // });
            
            // SIMULASI SUKSES (Hapus blok ini dan buka blok fetch di atas jika backend sudah siap)
            await new Promise(r => setTimeout(r, 800)); // Delay palsu
            
            // Jika sukses:
            msg.className = "text-xs mt-1 text-green-600 font-semibold";
            msg.innerText = "Kategori berhasil ditambahkan!";
            msg.classList.remove('hidden');
            
            // Reset Input
            input.value = '';
            input.classList.remove('border-red-500');

            // Opsional: Reload halaman atau tambahkan elemen ke list secara manual
            // location.reload(); 

        } catch (error) {
            console.error(error);
            msg.className = "text-xs mt-1 text-red-600";
            msg.innerText = "Gagal menambahkan kategori.";
            msg.classList.remove('hidden');
        } finally {
            btn.innerHTML = originalBtnContent;
            btn.disabled = false;
            setTimeout(() => msg.classList.add('hidden'), 3000);
        }
    }

    // --- 2. LOGIC PILIH KATEGORI ---
    function selectCategory(id) {
        const select = document.getElementById('category_id');
        select.value = id;
        select.dispatchEvent(new Event('change'));

        // Update UI Sidebar
        const buttons = document.querySelectorAll('#categoryListUL button');
        buttons.forEach(btn => {
            btn.className = "w-full text-left px-3 py-2.5 rounded-lg text-sm transition-all duration-200 flex items-center justify-between group border border-transparent text-gray-600 hover:bg-gray-100 hover:text-gray-900 hover:border-gray-200";
            const icon = btn.querySelector('.fa-check');
            if(icon) icon.remove();

            if(btn.getAttribute('onclick').includes(`'${id}'`)) {
                btn.className = "w-full text-left px-3 py-2.5 rounded-lg text-sm transition-all duration-200 flex items-center justify-between group border border-transparent bg-blue-600 text-white font-semibold shadow-md";
                btn.innerHTML += '<i class="fa-solid fa-check text-white text-xs"></i>';
            }
        });
    }

    // Filter Search
    document.getElementById('searchCategory').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let items = document.querySelectorAll('#categoryListUL li');
        items.forEach(item => {
            let text = item.textContent || item.innerText;
            item.style.display = text.toLowerCase().indexOf(filter) > -1 ? "" : "none";
        });
    });

    // --- 3. LOGIC LOAD ATTRIBUTES ---
    document.addEventListener('DOMContentLoaded', () => {
        const existingAttributes = @json($existingAttributes);
        const categorySelect = document.getElementById('category_id');
        const attributesCard = document.getElementById('attributes-card');
        const attributesContainer = document.getElementById('dynamic-attributes-container');

        // Style Constants
        const labelClass = "block mb-2 text-sm font-semibold text-gray-800";
        const inputClass = "bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 transition-colors";
        const checkboxClass = "flex items-center p-3 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-200 cursor-pointer transition-all bg-white";

        async function fetchAttributes() {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const url = selectedOption ? selectedOption.dataset.attributesUrl : null;

            if (!url) {
                attributesCard.classList.add('hidden');
                attributesContainer.innerHTML = '';
                return;
            }

            // Show Loading
            attributesCard.classList.remove('hidden');
            attributesContainer.innerHTML = `
                <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                    <i class="fas fa-circle-notch fa-spin text-3xl text-blue-500 mb-3"></i>
                    <p class="text-sm font-medium">Memuat form spesifikasi...</p>
                </div>`;

            try {
                const res = await fetch(url);
                const data = await res.json();
                attributesContainer.innerHTML = '';

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
                        <div class="p-4 text-sm text-blue-700 bg-blue-50 border border-blue-200 rounded-lg flex items-center gap-3">
                            <i class="fa-solid fa-circle-info text-lg"></i>
                            <span>Kategori ini tidak membutuhkan spesifikasi khusus.</span>
                        </div>`;
                }
            } catch (err) {
                console.error(err);
                attributesContainer.innerHTML = `<div class="text-red-500 text-center py-4 font-bold">Gagal memuat data.</div>`;
            }
        }

        function createField(attr) {
            const div = document.createElement('div');
            const requiredStar = attr.is_required ? '<span class="text-red-500 ml-1">*</span>' : '';
            const name = `attributes[${attr.slug}]`;
            let htmlInput = '';

            if (attr.type === 'select') {
                const options = attr.options.split(',').map(o => `<option value="${o.trim()}">${o.trim()}</option>`).join('');
                htmlInput = `<select name="${name}" class="${inputClass} cursor-pointer"><option value="">-- Pilih --</option>${options}</select>`;
            } 
            else if (attr.type === 'textarea') {
                htmlInput = `<textarea name="${name}" rows="3" class="${inputClass}" placeholder="Deskripsi..."></textarea>`;
            } 
            else if (attr.type === 'checkbox') {
                const options = attr.options.split(',').map(o => `
                    <label class="${checkboxClass}">
                        <input type="checkbox" name="${name}[]" value="${o.trim()}" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                        <span class="ml-2 text-sm font-medium text-gray-700 select-none">${o.trim()}</span>
                    </label>
                `).join('');
                htmlInput = `<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1">${options}</div>`;
            } 
            else {
                const type = attr.type === 'number' ? 'number' : (attr.type === 'date' ? 'date' : 'text');
                htmlInput = `<input type="${type}" name="${name}" class="${inputClass}" placeholder="...">`;
            }

            div.innerHTML = `<label class="${labelClass}">${attr.name} ${requiredStar}</label>${htmlInput}`;
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

        if (categorySelect) {
            categorySelect.addEventListener('change', fetchAttributes);
            if (categorySelect.value) fetchAttributes();
        }
    });
</script>
@endpush