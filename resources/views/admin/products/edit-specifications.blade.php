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

                {{-- 2. SPESIFIKASI DINAMIS --}}
                <div id="attributes-card" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hidden">
                    <div class="flex items-center justify-between mb-4 border-b pb-2">
                        <h2 class="text-lg font-semibold text-gray-800">Spesifikasi Tambahan</h2>
                        <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded">Sesuai Kategori</span>
                    </div>
                    
                    <div id="dynamic-attributes-container" class="space-y-4">
                        {{-- Diisi via JS --}}
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
    // === 1. SETUP DATA & VARIABLE ===
    const rawAttributes = {!! $existingAttributesJson ?? '{}' !!};
    const existingAttributes = typeof rawAttributes === 'string' ? JSON.parse(rawAttributes) : rawAttributes;

    const categoryInput = document.getElementById('category_id'); // Hidden Input
    const listContainer = document.getElementById('category-list');
    const searchInput = document.getElementById('cat-search');
    const deleteBtn = document.getElementById('btn-delete-cat');
    const items = document.querySelectorAll('.category-item');
    const warningBox = document.getElementById('cat-warning');

    const attributesCard = document.getElementById('attributes-card');
    const attributesContainer = document.getElementById('dynamic-attributes-container');

    // === 2. LOGIC PENCARIAN (SEARCH) ===
    searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        let hasResult = false;

        items.forEach(item => {
            const text = item.querySelector('.category-text').textContent.toLowerCase();
            if (text.includes(term)) {
                item.classList.remove('hidden');
                hasResult = true;
            } else {
                item.classList.add('hidden');
            }
        });

        // Optional: Tampilkan pesan jika tidak ada hasil
        // if(!hasResult) ...
    });

    // === 3. LOGIC SELEKSI KATEGORI (CUSTOM LIST) ===
    function selectCategoryUI(element) {
        // Reset semua style active
        items.forEach(el => {
            el.classList.remove('bg-indigo-50', 'bg-indigo-100', 'border-l-4', 'border-indigo-500');
            el.querySelector('.check-icon').classList.add('hidden');
            el.querySelector('.category-text').classList.remove('text-indigo-800', 'font-bold');
        });

        // Set style active pada element yang dipilih
        if (element) {
            element.classList.add('bg-indigo-50', 'border-l-4', 'border-indigo-500');
            element.querySelector('.check-icon').classList.remove('hidden');
            element.querySelector('.category-text').classList.add('text-indigo-800', 'font-bold');
            
            // Scroll ke element jika perlu (optional)
            // element.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            deleteBtn.disabled = false;
            deleteBtn.classList.remove('text-gray-400');
            deleteBtn.classList.add('text-red-500', 'hover:bg-red-50', 'p-1', 'rounded');
        } else {
            deleteBtn.disabled = true;
            deleteBtn.classList.add('text-gray-400');
            deleteBtn.classList.remove('text-red-500');
        }
    }

    // Event Listener untuk setiap Item List
    items.forEach(item => {
        item.addEventListener('click', () => {
            const id = item.dataset.id;
            const url = item.dataset.url;
            
            // Update Hidden Input
            categoryInput.value = id;
            
            // Update UI List
            selectCategoryUI(item);

            // Tampilkan Warning
            warningBox.classList.remove('hidden');

            // Trigger Fetch Spesifikasi
            fetchAndRenderAttributes(url);
        });
    });

    // Initial Load (Jika Edit Mode dan sudah ada kategori terpilih)
    if (categoryInput.value) {
        const selectedItem = document.querySelector(`.category-item[data-id="${categoryInput.value}"]`);
        if (selectedItem) {
            selectCategoryUI(selectedItem);
            // Panggil render tapi jangan reset value (biar auto fill jalan)
            const url = selectedItem.dataset.url;
            fetchAndRenderAttributes(url, true); // true = mode init
        }
    }

    // === 4. LOGIC FETCH SPESIFIKASI ===
    async function fetchAndRenderAttributes(url, isInit = false) {
        if (!url) return;

        attributesCard.classList.remove('hidden');
        
        // Hanya tampilkan loading jika bukan init load (agar user tidak kaget saat refresh)
        if(!isInit) {
            attributesContainer.innerHTML = '<div class="py-6 text-center text-gray-500"><i class="fas fa-circle-notch fa-spin text-indigo-500 mb-2"></i><p>Memuat form...</p></div>';
        }

        try {
            const response = await fetch(url);
            const attributesStructure = await response.json();
            
            attributesContainer.innerHTML = ''; 

            if (attributesStructure && attributesStructure.length > 0) {
                attributesStructure.forEach(attr => {
                    const fieldElement = createAttributeField(attr);
                    attributesContainer.appendChild(fieldElement);

                    // AUTO FILL LOGIC
                    let dbValue = existingAttributes[attr.slug];
                    if (dbValue === undefined) {
                         // Fallback slug format
                         dbValue = existingAttributes[attr.slug.replace(/-/g, '_')];
                    }

                    if (dbValue !== undefined && dbValue !== null) {
                        fillAttributeValue(fieldElement, attr, dbValue);
                    }
                });
            } else {
                attributesContainer.innerHTML = '<div class="text-center py-4 border-2 border-dashed border-gray-200 rounded text-gray-400 text-sm">Tidak ada spesifikasi khusus.</div>';
            }
        } catch (error) {
            console.error("Error:", error);
            attributesContainer.innerHTML = '<p class="text-red-500 text-sm">Gagal memuat data.</p>';
        }
    }

    // Helper functions (Tetap sama seperti sebelumnya)
    function createAttributeField(attr) {
        const wrapper = document.createElement('div');
        const inputName = `attributes[${attr.slug}]`;
        const commonClass = "w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm";
        let inputHtml = '';

        if (attr.type === 'select') {
            const opts = (attr.options || '').split(',').map(o=>o.trim()).map(o=>`<option value="${o}">${o}</option>`).join('');
            inputHtml = `<select name="${inputName}" class="${commonClass}"><option value="">-- Pilih --</option>${opts}</select>`;
        } else if (attr.type === 'textarea') {
            inputHtml = `<textarea name="${inputName}" rows="3" class="${commonClass}"></textarea>`;
        } else if (attr.type === 'checkbox') {
             inputHtml = `<input type="text" name="${inputName}" class="${commonClass}" placeholder="Isi manual (Multi-select)">`;
        } else {
            inputHtml = `<input type="text" name="${inputName}" class="${commonClass}">`;
        }

        const req = attr.is_required ? '<span class="text-red-500">*</span>' : '';
        wrapper.innerHTML = `<label class="block text-sm font-medium text-gray-700 mb-1">${attr.name} ${req}</label>${inputHtml}`;
        return wrapper;
    }

    function fillAttributeValue(wrapper, attr, val) {
        const input = wrapper.querySelector(`[name="attributes[${attr.slug}]"]`);
        if (input) input.value = val;
    }

    // === 5. LOGIC TAMBAH & HAPUS KATEGORI (REAL AJAX) ===
    
    const btnToggleAdd = document.getElementById('btn-toggle-add-cat');
    const addWrapper = document.getElementById('add-category-wrapper');
    const inputNewCat = document.getElementById('new_category_name');
    const btnSaveCat = document.getElementById('btn-save-new-cat');
    
    // Ambil CSRF Token dari meta tag header (bawaan Laravel)
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Toggle Tampilan Form
    btnToggleAdd.addEventListener('click', () => {
        addWrapper.classList.toggle('hidden');
        if (!addWrapper.classList.contains('hidden')) inputNewCat.focus();
    });

    // --- FUNGSI SIMPAN KATEGORI (REAL) ---
    btnSaveCat.addEventListener('click', async () => {
        const name = inputNewCat.value.trim();
        if(!name) return alert('Nama kategori wajib diisi');

        // Loading State
        const originalContent = btnSaveCat.innerHTML;
        btnSaveCat.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btnSaveCat.disabled = true;

        try {
            // 1. Kirim Request ke Laravel
            const response = await fetch("{{ route('admin.categories.storeAjax') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken, // Wajib ada
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name: name })
            });

            const result = await response.json();

            if (!response.ok) {
                // Jika error validasi atau server error
                throw new Error(result.message || 'Gagal menyimpan kategori');
            }

            // 2. Jika Sukses: Update UI List
            const newCat = result.data;
            
            const newLi = document.createElement('li');
            newLi.className = "category-item relative cursor-pointer hover:bg-indigo-50 transition-colors group";
            newLi.dataset.id = newCat.id;
            newLi.dataset.name = newCat.name;
            newLi.dataset.url = newCat.attributes_url;

            newLi.innerHTML = `
                <div class="px-4 py-3 flex items-center justify-between">
                    <span class="text-sm text-gray-700 font-medium group-hover:text-indigo-700 category-text">${newCat.name}</span>
                    <i class="fa-solid fa-check text-indigo-600 hidden check-icon"></i>
                </div>
            `;
            
            // Tambahkan event listener click ke item baru ini
            newLi.addEventListener('click', () => {
                categoryInput.value = newCat.id;
                selectCategoryUI(newLi);
                warningBox.classList.remove('hidden');
                fetchAndRenderAttributes(newCat.attributes_url);
            });

            // Masukkan ke paling atas list
            listContainer.insertBefore(newLi, listContainer.firstChild);

            // Langsung pilih kategori baru tersebut
            categoryInput.value = newCat.id;
            selectCategoryUI(newLi);
            
            // Bersihkan form
            inputNewCat.value = '';
            addWrapper.classList.add('hidden');
            
            // Tampilkan Notifikasi (Opsional)
            // alert('Kategori berhasil dibuat!'); 

        } catch (error) {
            console.error(error);
            alert('Error: ' + error.message);
        } finally {
            // Reset Tombol
            btnSaveCat.innerHTML = originalContent;
            btnSaveCat.disabled = false;
        }
    });

    // --- FUNGSI HAPUS KATEGORI (REAL) ---
    deleteBtn.addEventListener('click', async () => {
        const id = categoryInput.value;
        if(!id) return;
        
        if(!confirm('Apakah Anda yakin ingin menghapus kategori ini dari database?')) return;

        // Loading State
        const originalContent = deleteBtn.innerHTML;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        deleteBtn.disabled = true;

        try {
            // 1. Kirim Request Delete ke Laravel
            // Kita perlu URL route yang dinamis berdasarkan ID
            const url = "{{ route('admin.categories.destroyAjax', ':id') }}".replace(':id', id);

            const response = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Gagal menghapus kategori');
            }

            // 2. Jika Sukses: Hapus dari UI
            const itemToRemove = document.querySelector(`.category-item[data-id="${id}"]`);
            if(itemToRemove) {
                itemToRemove.remove();
            }
            
            // Reset Pilihan
            categoryInput.value = '';
            selectCategoryUI(null);
            attributesContainer.innerHTML = '';
            attributesCard.classList.add('hidden');
            warningBox.classList.add('hidden');

        } catch (error) {
            console.error(error);
            alert('Gagal: ' + error.message);
        } finally {
            deleteBtn.innerHTML = originalContent;
            deleteBtn.disabled = false;
        }
    });
    
</script>