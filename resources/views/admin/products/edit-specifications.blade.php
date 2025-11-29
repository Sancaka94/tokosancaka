@extends('layouts.admin')

@section('title', 'Edit Spesifikasi: ' . $product->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    {{-- Header --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
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

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- === LEFT COLUMN (Main Content: SKU, Tags, Specs) === --}}
            <div class="lg:col-span-8 space-y-6">
                
                {{-- 1. PENGATURAN DATA (SKU, Kategori, Tags) --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Informasi Dasar</h2>
    
    <div class="space-y-4">
        {{-- SKU --}}
        <div>
            <label for="sku" class="block text-sm font-medium text-gray-700 mb-1">SKU Induk</label>
            <div class="relative">
                <input type="text" 
                       name="sku" 
                       id="sku" 
                       value="{{ old('sku', $product->sku) }}" 
                       class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 placeholder-gray-400"
                       placeholder="Contoh: PRD-2023-001 (Kosongkan untuk Auto-Generate)">
                
                {{-- Indikator Visual (Opsional) --}}
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-magic text-gray-300"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                <i class="fa-solid fa-circle-info text-blue-500"></i>
                <span>Biarkan kosong jika ingin sistem membuat SKU otomatis.</span>
            </p>
        </div>

        {{-- Tags (Tetap sama) --}}
        <div>
            <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
            <input type="text" name="tags" id="tags" value="{{ old('tags', $product->tags) }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Jasa, Perizinan, Cepat">
            <p class="text-xs text-gray-500 mt-1">Pisahkan dengan koma.</p>
        </div>
    </div>
</div>

                {{-- AREA SPESIFIKASI TAMBAHAN --}}
<div id="attributes-card" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hidden">
    {{-- Header Card --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6 border-b border-gray-100 pb-4">
        <h2 class="text-lg font-semibold text-gray-800">Spesifikasi Tambahan</h2>
        
        <div class="flex items-center gap-2">
            <span class="text-xs bg-indigo-50 text-indigo-600 px-2.5 py-1 rounded-full font-medium border border-indigo-100">
                Sesuai Kategori
            </span>
            {{-- Tombol Tambah (Pasti Muncul) --}}
            <button type="button" id="btn-show-add-attr" class="text-xs bg-white hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-lg border border-gray-300 shadow-sm transition flex items-center gap-2">
                <i class="fa-solid fa-plus text-indigo-500"></i> Tambah Field
            </button>
        </div>
    </div>
    
    {{-- Container Input Dinamis --}}
    <div id="dynamic-attributes-container" class="space-y-5">
        {{-- Diisi via JS --}}
    </div>

    {{-- === FORM TAMBAH ATRIBUT BARU (Hidden by Default) === --}}
    <div id="form-add-attribute" class="hidden mt-6 bg-gray-50 border border-indigo-100 rounded-xl p-5 relative overflow-hidden transition-all duration-300">
        <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
        
        <h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center">
            <i class="fa-solid fa-layer-group text-indigo-500 mr-2"></i> Buat Spesifikasi Baru
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-4">
            {{-- Nama Atribut --}}
            <div class="md:col-span-5">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nama Field (Label) <span class="text-red-500">*</span></label>
                <input type="text" id="new_attr_name" class="w-full text-sm border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500" placeholder="Contoh: Warna / Bahan">
            </div>

            {{-- Tipe Input --}}
            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Tipe Input</label>
                <select id="new_attr_type" class="w-full text-sm border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="text">Text Singkat</option>
                    <option value="select">Pilihan (Dropdown)</option>
                    <option value="checkbox">Checkbox (Banyak)</option>
                    <option value="number">Angka</option>
                    <option value="textarea">Text Panjang</option>
                </select>
            </div>

            {{-- Opsi (Muncul jika select/checkbox) --}}
            <div id="new_attr_options_wrapper" class="md:col-span-4 hidden">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Opsi (Pisahkan koma)</label>
                <input type="text" id="new_attr_options" class="w-full text-sm border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500" placeholder="Merah, Hijau, Biru">
            </div>
        </div>

        <div class="flex items-center justify-between pt-2 border-t border-gray-200 mt-2">
            <label class="inline-flex items-center cursor-pointer">
                <input type="checkbox" id="new_attr_required" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" checked>
                <span class="ml-2 text-xs font-medium text-gray-600">Wajib Diisi</span>
            </label>
            <div class="flex gap-2">
                <button type="button" id="btn-cancel-attr" class="px-4 py-2 text-xs font-medium text-gray-600 hover:text-gray-800 bg-white border border-gray-300 rounded-lg transition">
                    Batal
                </button>
                <button type="button" id="btn-save-attr" class="px-4 py-2 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm flex items-center gap-2 transition">
                    <i class="fa-solid fa-save"></i> Simpan Field
                </button>
            </div>
        </div>
    </div>
</div>

            {{-- === RIGHT COLUMN (Sidebar: Category & Actions) === --}}
            <div class="lg:col-span-4 space-y-6">

                {{-- CARD: SAVE ACTIONS --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-6">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Aksi</h3>
                    <button type="submit" class="w-full py-2.5 bg-indigo-600 text-white rounded-lg font-medium shadow-md hover:bg-indigo-700 transition flex items-center justify-center mb-3">
                        <i class="fa-solid fa-save mr-2"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.products.edit', $product->slug) }}" class="w-full block text-center py-2.5 bg-white border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition">
                        Batal
                    </a>
                </div>

                {{-- CARD: KATEGORI (UPDATED UI) --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Kategori</h2>
        
        {{-- Tombol Toggle Tambah --}}
        <button type="button" id="btn-toggle-add-cat" class="text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 px-3 py-1.5 rounded-full transition flex items-center gap-1 font-medium" title="Tambah Kategori Baru">
            <i class="fa-solid fa-plus"></i> Baru
        </button>
    </div>

    {{-- Form Tambah Kategori (Hidden by default) --}}
    <div id="add-category-wrapper" class="hidden mb-4 p-3 bg-indigo-50 rounded-lg border border-indigo-100 animate-fade-in-down">
        <label class="text-xs font-semibold text-indigo-700 mb-1 block">Nama Kategori Baru</label>
        <div class="flex gap-2">
            <input type="text" id="new_category_name" class="w-full text-sm border-gray-300 rounded shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Misal: Elektronik">
            <button type="button" id="btn-save-new-cat" class="bg-indigo-600 text-white px-3 rounded hover:bg-indigo-700 shadow-sm">
                <i class="fa-solid fa-check"></i>
            </button>
        </div>
    </div>

    {{-- AREA PENCARIAN & LIST --}}
    <div class="relative">
        <label class="block text-sm font-medium text-gray-700 mb-2 required-label">Pilih Kategori <span class="text-red-500">*</span></label>

        {{-- 1. Input Pencarian --}}
        <div class="relative mb-2">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fa-solid fa-magnifying-glass text-gray-400 text-xs"></i>
            </div>
            <input type="text" id="cat-search" class="block w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-150 ease-in-out" placeholder="Cari kategori...">
        </div>

        {{-- 2. Input Hidden (Menyimpan Value Asli untuk Form Submit) --}}
        <input type="hidden" name="category_id" id="category_id" value="{{ old('category_id', $product->category_id) }}" required>

        {{-- 3. Custom Scrollable List --}}
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            {{-- Header List --}}
            <div class="bg-gray-50 px-3 py-2 border-b border-gray-200 flex justify-between items-center">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Daftar Kategori</span>
                
                {{-- Tombol Hapus (Aktif jika ada yang dipilih) --}}
                <button type="button" id="btn-delete-cat" class="text-gray-400 hover:text-red-600 transition disabled:opacity-50 disabled:cursor-not-allowed" title="Hapus Kategori Terpilih" disabled>
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>

            {{-- List Item Container (Max Height + Scrollbar) --}}
            <ul id="category-list" class="max-h-[250px] overflow-y-auto divide-y divide-gray-100 bg-white">
                @foreach($categories as $category)
                    <li class="category-item relative cursor-pointer hover:bg-indigo-50 transition-colors group"
                        data-id="{{ $category->id }}"
                        data-name="{{ $category->name }}"
                        data-url="{{ route('admin.categories.attributes', $category->id) }}">
                        
                        <div class="px-4 py-3 flex items-center justify-between">
                            <span class="text-sm text-gray-700 font-medium group-hover:text-indigo-700 category-text">{{ $category->name }}</span>
                            
                            {{-- Check Icon (Visible only when selected) --}}
                            <i class="fa-solid fa-check text-indigo-600 hidden check-icon"></i>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div id="cat-warning" class="mt-3 p-3 bg-yellow-50 rounded-lg border border-yellow-100 hidden">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fa-solid fa-triangle-exclamation text-yellow-500 mt-0.5"></i>
            </div>
            <div class="ml-3">
                <p class="text-xs text-yellow-700">
                    Mengubah kategori akan <strong>mereset</strong> form spesifikasi di kolom kiri.
                </p>
            </div>
        </div>
    </div>
</div>

            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // =========================================================================
    // 1. PERSIAPAN DATA & VARIABEL
    // =========================================================================
    
    // Ambil CSRF Token untuk AJAX Request
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Parsing Data Atribut Lama dari Controller
    let existingAttributes = {};
    try {
        const rawData = {!! $existingAttributesJson ?? '{}' !!};
        existingAttributes = typeof rawData === 'string' ? JSON.parse(rawData) : rawData;
        console.log("📦 Data Atribut Tersimpan:", existingAttributes);
    } catch (e) {
        console.error("Gagal memproses data atribut:", e);
    }

    // Seleksi Element DOM
    const categoryInput = document.getElementById('category_id'); // Hidden Input
    const listContainer = document.getElementById('category-list');
    const searchInput = document.getElementById('cat-search');
    const deleteBtn = document.getElementById('btn-delete-cat');
    const items = document.querySelectorAll('.category-item'); // List awal
    const warningBox = document.getElementById('cat-warning');

    const attributesCard = document.getElementById('attributes-card');
    const attributesContainer = document.getElementById('dynamic-attributes-container');

    // Element Tambah Kategori
    const btnToggleAdd = document.getElementById('btn-toggle-add-cat');
    const addWrapper = document.getElementById('add-category-wrapper');
    const inputNewCat = document.getElementById('new_category_name');
    const btnSaveCat = document.getElementById('btn-save-new-cat');

    // =========================================================================
    // 2. FUNGSI LOGIKA UTAMA (UI & FETCH)
    // =========================================================================

    /**
     * Mengatur tampilan visual item list (Aktif/Tidak)
     */
    function selectCategoryUI(element) {
        // Reset semua item menjadi tampilan default
        const allItems = document.querySelectorAll('.category-item');
        allItems.forEach(el => {
            el.classList.remove('bg-indigo-50', 'bg-indigo-100', 'border-l-4', 'border-indigo-500');
            const icon = el.querySelector('.check-icon');
            if(icon) icon.classList.add('hidden');
            const text = el.querySelector('.category-text');
            if(text) text.classList.remove('text-indigo-800', 'font-bold');
        });

        // Highlight item yang dipilih
        if (element) {
            element.classList.add('bg-indigo-50', 'border-l-4', 'border-indigo-500');
            const icon = element.querySelector('.check-icon');
            if(icon) icon.classList.remove('hidden');
            const text = element.querySelector('.category-text');
            if(text) text.classList.add('text-indigo-800', 'font-bold');

            // Aktifkan tombol hapus
            if(deleteBtn) {
                deleteBtn.disabled = false;
                deleteBtn.classList.remove('text-gray-400');
                deleteBtn.classList.add('text-red-500', 'hover:bg-red-50', 'cursor-pointer');
            }
        } else {
            // Matikan tombol hapus jika tidak ada yang dipilih
            if(deleteBtn) {
                deleteBtn.disabled = true;
                deleteBtn.classList.add('text-gray-400');
                deleteBtn.classList.remove('text-red-500', 'hover:bg-red-50', 'cursor-pointer');
            }
        }
    }

    // =========================================================================
    // UPDATE FUNGSI FETCH: Handle Empty State
    // =========================================================================
    async function fetchAndRenderAttributes(url, isInit = false) {
        if (!url) return;
        attributesCard.classList.remove('hidden');

        if(!isInit) {
            attributesContainer.innerHTML = '<div class="py-4 text-center text-gray-500"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        }

        try {
            const response = await fetch(url);
            if(!response.ok) throw new Error("Gagal load data");
            const attributesStructure = await response.json();
            
            attributesContainer.innerHTML = ''; 

            if (attributesStructure && attributesStructure.length > 0) {
                // RENDER NORMAL
                attributesStructure.forEach(attr => {
                    const fieldElement = createAttributeField(attr);
                    attributesContainer.appendChild(fieldElement);
                    
                    // Auto Fill Logic
                    let dbValue = existingAttributes[attr.slug];
                    if (dbValue === undefined) dbValue = existingAttributes[attr.slug.replace(/-/g, '_')];
                    if (dbValue !== undefined && dbValue !== null) fillAttributeValue(fieldElement, attr, dbValue);
                });
            } else {
                // RENDER EMPTY STATE: Tampilkan tombol buat baru
                attributesContainer.innerHTML = `
                    <div class="text-center py-8 border-2 border-dashed border-gray-200 rounded-lg bg-gray-50">
                        <i class="fa-solid fa-box-open text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500 text-sm font-medium">Belum ada spesifikasi untuk kategori ini.</p>
                        <button type="button" onclick="document.getElementById('btn-show-add-attr').click()" class="mt-3 text-indigo-600 hover:text-indigo-800 text-sm font-semibold underline">
                            + Buat Spesifikasi Baru
                        </button>
                    </div>`;
            }

        } catch (error) {
            console.error(error);
            attributesContainer.innerHTML = '<p class="text-red-500 text-sm text-center">Gagal memuat data.</p>';
        }
    }

    // =========================================================================
    // BARU: LOGIC TAMBAH ATRIBUT DINAMIS
    // =========================================================================
    const btnShowAttrForm = document.getElementById('btn-show-add-attr');
    const formAttrWrapper = document.getElementById('form-add-attribute');
    const btnCancelAttr = document.getElementById('btn-cancel-attr');
    const btnSaveAttr = document.getElementById('btn-save-attr');
    const inputAttrType = document.getElementById('new_attr_type');
    const inputAttrOptionsDiv = document.getElementById('new_attr_options_wrapper');

    // 1. Toggle Form
    if(btnShowAttrForm) {
        btnShowAttrForm.addEventListener('click', () => {
            formAttrWrapper.classList.remove('hidden');
            // Scroll ke form
            formAttrWrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            document.getElementById('new_attr_name').focus();
        });
    }

    if(btnCancelAttr) {
        btnCancelAttr.addEventListener('click', () => {
            formAttrWrapper.classList.add('hidden');
        });
    }

    // 2. Tampilkan Input Opsi jika Select/Checkbox dipilih
    if(inputAttrType) {
        inputAttrType.addEventListener('change', (e) => {
            const val = e.target.value;
            if(val === 'select' || val === 'checkbox') {
                inputAttrOptionsDiv.classList.remove('hidden');
            } else {
                inputAttrOptionsDiv.classList.add('hidden');
            }
        });
    }

    // 3. Simpan Atribut Baru (AJAX)
    if(btnSaveAttr) {
        btnSaveAttr.addEventListener('click', async () => {
            const catId = categoryInput.value;
            const name = document.getElementById('new_attr_name').value.trim();
            const type = document.getElementById('new_attr_type').value;
            const options = document.getElementById('new_attr_options').value;
            const isRequired = document.getElementById('new_attr_required').checked;

            if(!catId) { alert('Pilih kategori dulu!'); return; }
            if(!name) { alert('Nama field wajib diisi!'); return; }
            if((type === 'select' || type === 'checkbox') && !options) {
                alert('Untuk tipe Pilihan, kolom Opsi wajib diisi (pisahkan dengan koma).'); return;
            }

            // UI Loading
            const originalBtnHtml = btnSaveAttr.innerHTML;
            btnSaveAttr.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            btnSaveAttr.disabled = true;

            try {
                // Generate URL dinamis: admin/category-attributes/{category_id}
                // Pastikan route ini ada di web.php: admin.category-attributes.store
                const url = "{{ route('admin.category-attributes.store', ':id') }}".replace(':id', catId);

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: name,
                        type: type,
                        options: options,
                        is_required: isRequired
                    })
                });

                const result = await response.json();

                if(!response.ok) throw new Error(result.message || 'Gagal menyimpan atribut');

                // BERHASIL:
                // 1. Hapus pesan "Belum ada spesifikasi" jika ada
                if(attributesContainer.innerText.includes('Belum ada spesifikasi')) {
                    attributesContainer.innerHTML = '';
                }

                // 2. Render field baru langsung ke layar
                const newFieldHtml = createAttributeField(result.data);
                
                // Tambahkan animasi fade-in
                newFieldHtml.classList.add('animate-fade-in-down');
                attributesContainer.appendChild(newFieldHtml);

                // 3. Reset Form & Sembunyikan
                document.getElementById('new_attr_name').value = '';
                document.getElementById('new_attr_options').value = '';
                formAttrWrapper.classList.add('hidden');

            } catch (error) {
                console.error(error);
                alert('Error: ' + error.message);
            } finally {
                btnSaveAttr.innerHTML = originalBtnHtml;
                btnSaveAttr.disabled = false;
            }
        });
    }

    /**
     * Helper: Membuat HTML Input Field
     */
    function createAttributeField(attr) {
        const wrapper = document.createElement('div');
        const inputName = `attributes[${attr.slug}]`;
        const commonClass = "w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors";
        let inputHtml = '';

        if (attr.type === 'select') {
            const opts = (attr.options || '').split(',').map(o=>o.trim()).map(o=>`<option value="${o}">${o}</option>`).join('');
            inputHtml = `<select name="${inputName}" class="${commonClass}"><option value="">-- Pilih --</option>${opts}</select>`;
        } else if (attr.type === 'textarea') {
            inputHtml = `<textarea name="${inputName}" rows="3" class="${commonClass}"></textarea>`;
        } else if (attr.type === 'checkbox') {
             // Checkbox mockup (bisa dikembangkan jadi multi-checkbox jika perlu)
             // Saat ini input text biasa untuk menampung array json
             const opts = (attr.options || '').split(',').map(o=>o.trim()).map(o=> `
                <label class="inline-flex items-center mr-4 mb-2">
                    <input type="checkbox" name="${inputName}[]" value="${o}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700">${o}</span>
                </label>
             `).join('');
             inputHtml = `<div class="mt-2 space-y-2">${opts}</div>`;
        } else {
            const type = attr.type === 'number' ? 'number' : 'text';
            inputHtml = `<input type="${type}" name="${inputName}" class="${commonClass}">`;
        }

        const req = attr.is_required ? '<span class="text-red-500">*</span>' : '';
        wrapper.innerHTML = `<label class="block text-sm font-medium text-gray-700 mb-1">${attr.name} ${req}</label>${inputHtml}`;
        return wrapper;
    }

    /**
     * Helper: Mengisi Nilai ke Input Field
     */
    function fillAttributeValue(wrapper, attr, val) {
        if (attr.type === 'checkbox') {
            const arr = Array.isArray(val) ? val : [val];
            wrapper.querySelectorAll('input[type="checkbox"]').forEach(chk => {
                if(arr.includes(chk.value)) chk.checked = true;
            });
        } else {
            const input = wrapper.querySelector(`[name="attributes[${attr.slug}]"]`);
            if (input) input.value = val;
        }
    }

    // =========================================================================
    // 3. EVENT LISTENERS & INTERAKSI
    // =========================================================================

    // A. Fitur Pencarian Kategori
    if(searchInput) {
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const currentItems = document.querySelectorAll('.category-item'); // Ambil ulang krn bisa ada item baru
            currentItems.forEach(item => {
                const text = item.querySelector('.category-text').textContent.toLowerCase();
                if (text.includes(term)) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        });
    }

    // B. Klik pada Item Kategori (Event Delegation tidak dipakai agar simple, tapi perlu re-attach utk item baru)
    function attachClickEvent(item) {
        item.addEventListener('click', () => {
            const id = item.dataset.id;
            const url = item.dataset.url;
            
            // 1. Update Hidden Input
            categoryInput.value = id;
            
            // 2. Update UI
            selectCategoryUI(item);
            
            // 3. Tampilkan Warning
            if(warningBox) warningBox.classList.remove('hidden');

            // 4. Fetch Data
            fetchAndRenderAttributes(url);
        });
    }

    // Pasang event ke semua item yang sudah ada saat load
    items.forEach(item => attachClickEvent(item));

    // C. Tombol Toggle Form Tambah
    if(btnToggleAdd) {
        btnToggleAdd.addEventListener('click', () => {
            addWrapper.classList.toggle('hidden');
            if (!addWrapper.classList.contains('hidden')) inputNewCat.focus();
        });
    }

    // =========================================================================
    // 4. AJAX: TAMBAH KATEGORI
    // =========================================================================
    if(btnSaveCat) {
        btnSaveCat.addEventListener('click', async () => {
            const name = inputNewCat.value.trim();
            if(!name) { alert('Nama kategori wajib diisi'); return; }

            // UI Loading
            const originalContent = btnSaveCat.innerHTML;
            btnSaveCat.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btnSaveCat.disabled = true;

            try {
                // Fetch ke Laravel
                const response = await fetch("{{ route('admin.categories.storeAjax') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ name: name })
                });

                const result = await response.json();

                if (!response.ok) throw new Error(result.message || 'Gagal menyimpan');

                // Sukses: Buat elemen List Item baru
                const newLi = document.createElement('li');
                newLi.className = "category-item relative cursor-pointer hover:bg-indigo-50 transition-colors group";
                newLi.dataset.id = result.data.id;
                newLi.dataset.name = result.data.name;
                newLi.dataset.url = result.data.attributes_url;

                newLi.innerHTML = `
                    <div class="px-4 py-3 flex items-center justify-between">
                        <span class="text-sm text-gray-700 font-medium group-hover:text-indigo-700 category-text">${result.data.name}</span>
                        <i class="fa-solid fa-check text-indigo-600 hidden check-icon"></i>
                    </div>
                `;

                // Pasang Click Event ke item baru
                attachClickEvent(newLi);

                // Masukkan ke paling atas list
                listContainer.insertBefore(newLi, listContainer.firstChild);

                // Langsung pilih item baru tersebut
                categoryInput.value = result.data.id;
                selectCategoryUI(newLi);
                
                // Fetch atribut kosong (karena baru)
                fetchAndRenderAttributes(result.data.attributes_url);

                // Reset Form
                inputNewCat.value = '';
                addWrapper.classList.add('hidden');

            } catch (error) {
                console.error(error);
                alert(error.message);
            } finally {
                btnSaveCat.innerHTML = originalContent;
                btnSaveCat.disabled = false;
            }
        });
    }

    // =========================================================================
    // 5. AJAX: HAPUS KATEGORI
    // =========================================================================
    if(deleteBtn) {
        deleteBtn.addEventListener('click', async () => {
            const id = categoryInput.value;
            if(!id) return;
            
            if(!confirm('Apakah Anda yakin ingin menghapus kategori ini secara permanen?')) return;

            // UI Loading
            const originalContent = deleteBtn.innerHTML;
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            deleteBtn.disabled = true;

            try {
                // Construct URL delete
                const url = "{{ route('admin.categories.destroyAjax', ':id') }}".replace(':id', id);

                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (!response.ok) throw new Error(result.message || 'Gagal menghapus');

                // Sukses: Hapus dari DOM
                const itemToRemove = document.querySelector(`.category-item[data-id="${id}"]`);
                if(itemToRemove) itemToRemove.remove();

                // Reset Selection
                categoryInput.value = '';
                selectCategoryUI(null);
                attributesContainer.innerHTML = '';
                attributesCard.classList.add('hidden');
                if(warningBox) warningBox.classList.add('hidden');

            } catch (error) {
                console.error(error);
                alert(error.message);
            } finally {
                deleteBtn.innerHTML = originalContent;
                deleteBtn.disabled = false;
            }
        });
    }

    // =========================================================================
    // 6. INITIAL LOAD (AUTO SELECT SAAT EDIT)
    // =========================================================================
    if (categoryInput.value) {
        // Cari item yang ID-nya sesuai dengan data tersimpan
        const selectedItem = document.querySelector(`.category-item[data-id="${categoryInput.value}"]`);
        
        if (selectedItem) {
            console.log("🔄 Auto-selecting category ID:", categoryInput.value);
            
            // 1. Visual Select
            selectCategoryUI(selectedItem);
            
            // 2. Scroll ke item tersebut
            selectedItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            // 3. Load Form Spesifikasi
            const url = selectedItem.dataset.url;
            fetchAndRenderAttributes(url, true); // true = mode init
        }
    }
});
</script>
@endpush