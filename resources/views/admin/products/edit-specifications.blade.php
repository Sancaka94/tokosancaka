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

                {{-- CARD: KATEGORI --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Kategori</h2>
                        
                        {{-- Tombol Toggle Tambah --}}
                        <button type="button" id="btn-toggle-add-cat" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded transition" title="Tambah Kategori Baru">
                            <i class="fa-solid fa-plus"></i> Baru
                        </button>
                    </div>

                    {{-- Form Tambah Kategori (Hidden by default) --}}
                    <div id="add-category-wrapper" class="hidden mb-4 p-3 bg-indigo-50 rounded-lg border border-indigo-100">
                        <label class="text-xs font-semibold text-indigo-700 mb-1 block">Nama Kategori Baru</label>
                        <div class="flex gap-2">
                            <input type="text" id="new_category_name" class="w-full text-sm border-gray-300 rounded shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Misal: Elektronik">
                            <button type="button" id="btn-save-new-cat" class="bg-indigo-600 text-white px-3 rounded hover:bg-indigo-700">
                                <i class="fa-solid fa-check"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Dropdown Kategori --}}
                    <div class="relative">
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1 required-label">Pilih Kategori <span class="text-red-500">*</span></label>
                        
                        <div class="flex gap-2">
                            <select name="category_id" id="category_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">-- Pilih --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                        data-attributes-url="{{ route('admin.categories.attributes', $category->id) }}" 
                                        {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>

                            {{-- Tombol Hapus Kategori --}}
                            <button type="button" id="btn-delete-cat" class="px-3 py-2 bg-red-50 text-red-600 border border-red-200 rounded-lg hover:bg-red-100 transition" title="Hapus Kategori Terpilih">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mt-3 p-3 bg-yellow-50 rounded-lg border border-yellow-100">
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
    // 1. Ambil Data Lama
    const rawAttributes = {!! $product->existing_attributes_json ?? '{}' !!};
    const existingAttributes = Array.isArray(rawAttributes) && rawAttributes.length === 0 ? {} : rawAttributes;

    // === DEBUGGING: Cek Data di Console Browser (Tekan F12 -> Console) ===
    console.group("DEBUG SPESIFIKASI");
    console.log("Data dari Database (JSON):", existingAttributes);
    console.groupEnd();

    const categorySelect = document.getElementById('category_id');
    const attributesCard = document.getElementById('attributes-card');
    const attributesContainer = document.getElementById('dynamic-attributes-container');

    // Fungsi Normalisasi Slug (Ubah underscore jadi dash agar cocok)
    function normalizeSlug(str) {
        return str.toLowerCase().replace(/_/g, '-').replace(/\s+/g, '-');
    }

    async function fetchAndRenderAttributes() {
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        const url = selectedOption ? selectedOption.dataset.attributesUrl : null;

        if (!url) return;

        try {
            // Tampilkan Loading jika container kosong
            if(attributesContainer.innerHTML.trim() === '') {
                attributesContainer.innerHTML = '<div class="py-4 text-center text-gray-500"><i class="fas fa-spinner fa-spin"></i> Memuat form...</div>';
                attributesCard.classList.remove('hidden');
            }

            const response = await fetch(url);
            const attributesStructure = await response.json();

            attributesContainer.innerHTML = ''; // Reset Form

            if (attributesStructure && attributesStructure.length > 0) {
                attributesStructure.forEach(attr => {
                    // Render HTML
                    const fieldHtml = createAttributeField(attr);
                    attributesContainer.appendChild(fieldHtml);

                    // === LOGIKA PENGISIAN NILAI (AUTO-FILL) YANG LEBIH KUAT ===
                    
                    // 1. Cek Exact Match (slug asli)
                    let value = existingAttributes[attr.slug];

                    // 2. Jika kosong, Cek Normalized Match (antisipasi beda _ dan -)
                    if (value === undefined) {
                        const normalizedKey = normalizeSlug(attr.slug);
                        // Cari key di existingAttributes yang jika dinormalisasi sama dengan slug ini
                        const matchingKey = Object.keys(existingAttributes).find(key => normalizeSlug(key) === normalizedKey);
                        if (matchingKey) {
                            value = existingAttributes[matchingKey];
                        }
                    }

                    // 3. Isi Nilai
                    if (value !== undefined && value !== null) {
                        console.log(`✅ MATCH: Field [${attr.slug}] diisi dengan nilai:`, value);
                        fillAttributeValue(fieldHtml, attr, value);
                    } else {
                        console.warn(`❌ NO MATCH: Field [${attr.slug}] tidak punya data tersimpan.`);
                    }
                });
                attributesCard.classList.remove('hidden');
            } else {
                attributesContainer.innerHTML = '<p class="text-gray-400 italic">Tidak ada spesifikasi khusus.</p>';
            }

        } catch (error) {
            console.error("Error Fetching:", error);
            attributesContainer.innerHTML = '<p class="text-red-500">Gagal memuat data.</p>';
        }
    }

    function createAttributeField(attr) {
        const wrapper = document.createElement('div');
        const inputName = `attributes[${attr.slug}]`; 
        const commonClass = "w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500";
        let inputHtml = '';

        if (attr.type === 'select') {
            const opts = (attr.options||'').split(',').map(o => `<option value="${o.trim()}">${o.trim()}</option>`).join('');
            inputHtml = `<select name="${inputName}" class="${commonClass}"><option value="">-- Pilih --</option>${opts}</select>`;
        } else if (attr.type === 'textarea') {
             inputHtml = `<textarea name="${inputName}" rows="2" class="${commonClass}"></textarea>`;
        } else if (attr.type === 'checkbox') {
             // Logic checkbox khusus
             const opts = (attr.options||'').split(',').map(o => `
                <label class="inline-flex items-center mr-4 mt-2">
                    <input type="checkbox" name="${inputName}[]" value="${o.trim()}" class="rounded border-gray-300 text-indigo-600 shadow-sm">
                    <span class="ml-2 text-sm">${o.trim()}</span>
                </label>`).join('');
             inputHtml = `<div class="block">${opts}</div>`;
        } else {
            inputHtml = `<input type="text" name="${inputName}" class="${commonClass}">`;
        }
        
        // Tandai Required
        const reqLabel = attr.is_required ? ' <span class="text-red-500">*</span>' : '';
        wrapper.innerHTML = `<label class="block text-sm font-medium text-gray-700 mb-1">${attr.name}${reqLabel}</label>${inputHtml}`;
        return wrapper;
    }

    function fillAttributeValue(wrapper, attr, val) {
        // Handle Checkbox (Array)
        if (attr.type === 'checkbox') {
            const arr = Array.isArray(val) ? val : [val];
            wrapper.querySelectorAll('input[type="checkbox"]').forEach(chk => {
                if(arr.includes(chk.value)) chk.checked = true;
            });
        } 
        // Handle Select/Text/Textarea
        else {
            const inp = wrapper.querySelector(`[name="attributes[${attr.slug}]"]`);
            if(inp) inp.value = val;
        }
    }

    // Jalankan saat load
    if (categorySelect && categorySelect.value) {
        fetchAndRenderAttributes();
    }
    // Jalankan saat ganti kategori
    if (categorySelect) {
        categorySelect.addEventListener('change', fetchAndRenderAttributes);
    }
});
    // === 2. LOGIC TAMBAH / HAPUS KATEGORI (AJAX Mockup) ===
    
    // Toggle Form Tambah
    const btnToggleAdd = document.getElementById('btn-toggle-add-cat');
    const addWrapper = document.getElementById('add-category-wrapper');
    const btnSaveCat = document.getElementById('btn-save-new-cat');
    const inputNewCat = document.getElementById('new_category_name');
    const btnDeleteCat = document.getElementById('btn-delete-cat');

    btnToggleAdd.addEventListener('click', () => {
        addWrapper.classList.toggle('hidden');
        if(!addWrapper.classList.contains('hidden')) inputNewCat.focus();
    });

    // Action Tambah Kategori
    btnSaveCat.addEventListener('click', async () => {
        const name = inputNewCat.value.trim();
        if(!name) { alert('Nama kategori tidak boleh kosong'); return; }

        // Button Loading State
        const originalHtml = btnSaveCat.innerHTML;
        btnSaveCat.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btnSaveCat.disabled = true;

        try {
            // TODO: Ganti URL ini dengan route store kategori Anda yang sebenarnya
            // const res = await fetch("{{ route('admin.categories.store') }}", { 
            //    method: 'POST', 
            //    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            //    body: JSON.stringify({ name: name }) 
            // });
            
            // --- MOCKUP SUCCESS (Hapus blok ini jika sudah ada route backend) ---
            // Simulasi delay server
            await new Promise(r => setTimeout(r, 500)); 
            // Simulasi response data baru
            const newCat = { id: Date.now(), name: name, attributes_url: '' }; 
            // ------------------------------------------------------------------

            // Jika Backend Response OK:
            // const newCat = await res.json(); 

            // Tambahkan ke dropdown dan pilih
            const option = new Option(newCat.name, newCat.id);
            // option.dataset.attributesUrl = newCat.attributes_url; // Set URL atribut jika ada
            categorySelect.add(option);
            categorySelect.value = newCat.id;
            
            // Trigger change event agar spesifikasi mereset/update
            categorySelect.dispatchEvent(new Event('change'));

            // Reset Form
            inputNewCat.value = '';
            addWrapper.classList.add('hidden');
            alert('Kategori berhasil ditambahkan!');

        } catch (e) {
            console.error(e);
            alert('Gagal menambah kategori.');
        } finally {
            btnSaveCat.innerHTML = originalHtml;
            btnSaveCat.disabled = false;
        }
    });

    // Action Hapus Kategori
    btnDeleteCat.addEventListener('click', async () => {
        const id = categorySelect.value;
        const text = categorySelect.options[categorySelect.selectedIndex]?.text;

        if(!id) { alert('Pilih kategori yang ingin dihapus.'); return; }
        
        if(!confirm(`Apakah Anda yakin ingin menghapus kategori "${text}" secara permanen?`)) return;

        // Button Loading State
        const originalHtml = btnDeleteCat.innerHTML;
        btnDeleteCat.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btnDeleteCat.disabled = true;

        try {
            // TODO: Ganti URL dengan route destroy kategori Anda
            // await fetch(`/admin/categories/${id}`, { 
            //    method: 'DELETE',
            //    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            // });

            // --- MOCKUP SUCCESS (Hapus jika sudah ada route) ---
            await new Promise(r => setTimeout(r, 500));
            // ---------------------------------------------------

            // Hapus dari dropdown
            categorySelect.remove(categorySelect.selectedIndex);
            
            // Reset ke default
            categorySelect.value = "";
            categorySelect.dispatchEvent(new Event('change')); // Clear specs panel
            
            alert('Kategori berhasil dihapus.');

        } catch (e) {
            console.error(e);
            alert('Gagal menghapus kategori.');
        } finally {
            btnDeleteCat.innerHTML = originalHtml;
            btnDeleteCat.disabled = false;
        }
    });
});
</script>
@endpush