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
                <span class="font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-100">
                    {{ $product->name }}
                </span>
            </p>
        </div>

        <a href="{{ route('admin.products.edit', $product->slug) }}"
           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition-colors">
            <i class="fa-solid fa-arrow-left mr-2 text-gray-400"></i> Kembali
        </a>
    </div>

    {{-- FORM UTAMA (Update Spesifikasi) --}}
    <form action="{{ route('admin.products.update.specifications', $product->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            {{-- === KOLOM KIRI (FORM INPUT) === --}}
            <div class="lg:col-span-8 space-y-6">

                {{-- CARD 1: KATEGORI --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 text-blue-800 font-bold text-sm uppercase tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-layer-group"></i> 1. Kategori Produk
                    </div>

                    <div class="p-6">
                        {{-- SELECT READONLY --}}
                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-semibold text-gray-700">
                                Kategori Terpilih <span class="text-red-600">*</span>
                            </label>
                            <div class="relative">
                                <select name="category_id" id="category_id"
                                    class="w-full bg-gray-100 border border-gray-300 text-gray-500 text-sm rounded-lg p-2.5 cursor-not-allowed appearance-none"
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
                                <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-gray-400">
                                    <i class="fa-solid fa-lock text-xs"></i>
                                </div>
                            </div>
                        </div>

                        {{-- INFO BOX --}}
                        <div class="flex gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-sm items-start">
                            <i class="fa-solid fa-circle-info mt-1 text-amber-600"></i>
                            <div>
                                <span class="font-bold">Info:</span> Ubah kategori melalui sidebar di kanan. Perubahan kategori akan mereset form spesifikasi di bawah.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: FORM SPESIFIKASI (DINAMIS) --}}
                <div id="attributes-card" class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden hidden transition-all duration-300">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 text-gray-800 font-bold text-sm uppercase tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-blue-600"></i> 2. Isi Spesifikasi
                    </div>
                    <div id="dynamic-attributes-container" class="p-6 space-y-6">
                        {{-- JS Injects Inputs Here --}}
                    </div>
                </div>

                {{-- CARD 3: DATA TAMBAHAN --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 text-gray-800 font-bold text-sm uppercase tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-tags text-blue-600"></i> 3. Data Tambahan
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- SKU --}}
                        <div>
                            <label class="block mb-2 text-sm font-semibold text-gray-700">SKU Induk</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                                    <i class="fa-solid fa-barcode"></i>
                                </span>
                                <input type="text" name="sku" value="{{ old('sku', $product->sku) }}"
                                    class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5 font-mono uppercase placeholder-gray-400"
                                    placeholder="AUTO-GEN">
                            </div>
                        </div>

                        {{-- TAGS --}}
                        <div>
                            <label class="block mb-2 text-sm font-semibold text-gray-700">Tags</label>
                            @php
                                $tags = $product->tags;
                                if(is_string($tags) && str_starts_with($tags, '[')) {
                                    $decoded = json_decode($tags, true);
                                    if(is_array($decoded)) $tags = implode(', ', $decoded);
                                }
                            @endphp
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                                    <i class="fa-solid fa-hashtag"></i>
                                </span>
                                <input type="text" name="tags" value="{{ old('tags', $tags) }}"
                                    class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5 placeholder-gray-400"
                                    placeholder="Contoh: Promo, Terbaru">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- === KOLOM KANAN (SIDEBAR PILIH KATEGORI) === --}}
            <div class="lg:col-span-4">
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm sticky top-6 overflow-hidden">

                    {{-- HEADER SIDEBAR --}}
                    <div class="px-5 py-3 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                        <span class="font-bold text-sm text-gray-700 uppercase flex items-center gap-2">
                            <i class="fa-solid fa-magnifying-glass text-blue-500"></i> Pilih Kategori
                        </span>
                        <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2.5 py-0.5 rounded-full border border-blue-200">
                            {{ $categories->count() }}
                        </span>
                    </div>

                    {{-- INPUT PENCARIAN --}}
                    <div class="p-3 border-b border-gray-100 bg-white">
                        <input id="searchCategory" type="text" placeholder="Ketik nama kategori..."
                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 transition-all">
                    </div>

                    {{-- LIST KATEGORI --}}
                    <div class="p-2 overflow-y-auto bg-white custom-scrollbar" style="max-height: 350px;">
                        <ul id="categoryListUL" class="space-y-1">
                            @foreach($categories as $cat)
                                <li>
                                    <button type="button" onclick="selectCategory('{{ $cat->id }}')"
                                        class="w-full text-left px-3 py-2.5 rounded-lg text-sm transition-all duration-200 flex items-center justify-between group border border-transparent
                                        {{ $product->category_id == $cat->id
                                            ? 'bg-blue-600 text-white font-semibold shadow-md'
                                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 hover:border-gray-200' }}">
                                        <span>{{ $cat->name }}</span>
                                        @if($product->category_id == $cat->id)
                                            <i class="fa-solid fa-check text-white text-xs"></i>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- === FORM TAMBAH KATEGORI BARU (BAWAH) === --}}
                    <div class="p-4 border-t border-gray-200 bg-gray-50">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">
                            <i class="fa-solid fa-plus-circle mr-1"></i> Buat Kategori Baru
                        </label>
                        <div class="flex gap-2">
                            {{-- Input tidak boleh punya 'name' agar tidak tersubmit form utama --}}
                            <input type="text" id="new_category_name" 
                                class="flex-1 bg-white border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2" 
                                placeholder="Nama kategori...">
                            
                            <button type="button" onclick="addNewCategory()" 
                                class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-sm flex items-center justify-center">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        <p id="add-cat-msg" class="text-xs mt-1 hidden"></p>
                    </div>

                </div>
            </div>

        </div>

        {{-- STICKY FOOTER ACTION --}}
        <div class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 p-4 shadow-[0_-4px_10px_rgba(0,0,0,0.05)] md:pl-[280px]">
            <div class="max-w-7xl mx-auto flex justify-end gap-3">
                <a href="{{ route('admin.products.edit', $product->slug) }}"
                    class="px-5 py-2.5 text-sm font-bold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-900 focus:ring-4 focus:ring-gray-100 transition-all">
                    Batal
                </a>
                <button type="submit"
                    class="px-6 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all flex items-center gap-2 transform active:scale-95">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Simpan Spesifikasi
                </button>
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