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

    {{-- FORM UTAMA --}}
    <form action="{{ route('admin.products.update.specifications', $product->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            {{-- ===================== KOLOM KIRI ===================== --}}
            <div class="lg:col-span-8 space-y-8">

                {{-- CARD 1: KATEGORI --}}
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 font-semibold text-gray-700 flex items-center gap-2">
                        <i class="fa-solid fa-layer-group text-blue-600"></i>
                        <span>1. Kategori Produk</span>
                    </div>

                    <div class="p-6 space-y-6">

                        {{-- Select Readonly --}}
                        <div>
                            <label class="block mb-2 text-sm font-semibold text-gray-700">
                                Kategori Terpilih <span class="text-red-600">*</span>
                            </label>
                            <div class="relative">
                                <select name="category_id" id="category_id"
                                    class="w-full bg-gray-100 border border-gray-300 text-gray-500 text-sm rounded-xl p-3 cursor-not-allowed appearance-none"
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
                                <div class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                                    <i class="fa-solid fa-lock text-xs"></i>
                                </div>
                            </div>
                        </div>

                        {{-- Info Box --}}
                        <div class="flex gap-3 p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-sm">
                            <i class="fa-solid fa-circle-info text-lg mt-0.5 text-amber-600"></i>
                            <p><strong>Info:</strong> Ubah kategori melalui daftar di sebelah kanan. Mengubah kategori akan mereset form spesifikasi.</p>
                        </div>

                    </div>
                </div>

                {{-- CARD 2: SPESIFIKASI --}}
                <div id="attributes-card" class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 font-semibold text-gray-700 flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-blue-600"></i>
                        <span>2. Isi Spesifikasi</span>
                    </div>

                    <div id="dynamic-attributes-container" class="p-6 space-y-6">
                        {{-- JS Inject Here --}}
                    </div>
                </div>

                {{-- CARD 3: DATA TAMBAHAN --}}
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 font-semibold text-gray-700 flex items-center gap-2">
                        <i class="fa-solid fa-tags text-blue-600"></i>
                        <span>3. Data Tambahan</span>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">

                        {{-- SKU --}}
                        <div>
                            <label class="block mb-2 text-sm font-semibold text-gray-700">SKU Induk</label>
                            <div class="relative">
                                <i class="fa-solid fa-barcode absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="sku"
                                    value="{{ old('sku', $product->sku) }}"
                                    class="w-full bg-white border border-gray-300 rounded-xl p-3 pl-10 text-sm focus:ring-blue-500 focus:border-blue-500 font-mono uppercase placeholder-gray-400"
                                    placeholder="AUTO-GEN">
                            </div>
                        </div>

                        {{-- Tags --}}
                        <div>
                            <label class="block mb-2 text-sm font-semibold text-gray-700">Tags</label>
                            @php
                                $tags = $product->tags;
                                if(is_string($tags) && str_starts_with($tags, '[')) {
                                    $decoded = json_decode($tags, true);
                                    $tags = is_array($decoded) ? implode(', ', $decoded) : $tags;
                                }
                            @endphp
                            <div class="relative">
                                <i class="fa-solid fa-hashtag absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="tags"
                                    value="{{ old('tags', $tags) }}"
                                    class="w-full bg-white border border-gray-300 rounded-xl p-3 pl-10 text-sm focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400"
                                    placeholder="Contoh: Promo, Terbaru">
                            </div>
                        </div>

                    </div>
                </div>

            </div>



            {{-- ===================== KOLOM KANAN (SIDEBAR) ===================== --}}
            <div class="lg:col-span-4">

                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm sticky top-6 overflow-hidden">

                    {{-- Sidebar Header --}}
                    <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-gray-700 flex items-center gap-2">
                            <i class="fa-solid fa-magnifying-glass text-blue-600"></i>
                            Pilih Kategori
                        </h3>
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full border border-blue-200 font-semibold">
                            {{ $categories->count() }}
                        </span>
                    </div>

                    {{-- Search --}}
                    <div class="p-3 border-b border-gray-100">
                        <input id="searchCategory" type="text"
                            placeholder="Cari kategori..."
                            class="w-full bg-gray-50 border border-gray-300 rounded-xl p-2.5 text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- List --}}
                    <div class="p-2 overflow-y-auto" style="max-height: 350px;">
                        <ul id="categoryListUL" class="space-y-1">
                            @foreach($categories as $cat)
                                <li>
                                    <button type="button"
                                        onclick="selectCategory('{{ $cat->id }}')"
                                        class="w-full text-left px-3 py-2.5 rounded-xl text-sm flex justify-between items-center transition border
                                            {{ $product->category_id == $cat->id
                                                ? 'bg-blue-600 text-white font-semibold shadow'
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

                    {{-- Tambah Kategori --}}
                    <div class="p-4 border-t border-gray-200 bg-gray-50">
                        <label class="text-xs font-bold text-gray-600 mb-2 block uppercase">
                            <i class="fa-solid fa-plus-circle mr-1"></i>
                            Tambah Kategori Baru
                        </label>

                        <div class="flex gap-2">
                            <input type="text" id="new_category_name"
                                class="flex-1 bg-white border border-gray-300 rounded-xl p-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Nama kategori...">

                            <button type="button" onclick="addNewCategory()"
                                class="px-3 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition text-sm shadow">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>

                        <p id="add-cat-msg" class="text-xs mt-1 hidden"></p>
                    </div>

                </div>

            </div>

        </div>

        {{-- FOOTER --}}
        <div class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-lg py-4 md:pl-[280px]">
            <div class="max-w-7xl mx-auto flex justify-end gap-3 px-4">

                <a href="{{ route('admin.products.edit', $product->slug) }}"
                    class="px-5 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50">
                    Batal
                </a>

                <button type="submit"
                    class="px-6 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-500/20 flex items-center gap-2">
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