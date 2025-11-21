{{-- 
    File: resources/views/admin/marketplace/index.blade.php
    Deskripsi: Halaman utama untuk manajemen produk marketplace (CRUD).
--}}
@extends('layouts.admin')

@section('title', 'Manajemen Produk Marketplace')
@section('page-title', 'Daftar Produk')

@section('content')
<div class="bg-white p-6 rounded-lg shadow-md">
    
    <!-- Header: Search and Actions -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        {{-- Form sekarang berisi filter dan pencarian --}}
        <form id="filterForm" action="{{ route('admin.marketplace.index') }}" method="GET" class="flex flex-col md:flex-row items-center gap-4 w-full md:w-2/3">
            {{-- Filter Kategori --}}
            <div class="w-full md:w-1/2">
                <select name="category_filter" id="categoryFilter" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="all">Semua Kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_filter') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            {{-- Kolom Pencarian --}}
            <div class="relative w-full md:w-1/2">
                <input type="text" name="search" id="searchInput" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Cari Produk..." value="{{ request('search') }}">
                <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
        </form>
        <div class="flex items-center gap-2 w-full md:w-auto justify-end">
            <button type="button" onclick="openAddModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 whitespace-nowrap">Tambah Produk</button>
        </div>
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p>{{ session('success') }}</p></div>
    @endif
    @if ($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <!-- Product Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gambar</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Produk</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Flash Sale</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody id="product-table-body" class="bg-white divide-y divide-gray-200">
                @include('admin.marketplace.partials.product_rows', ['products' => $products])
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div id="pagination-links" class="mt-4">
        {{ $products->links() }}
    </div>
</div>

<!-- Modal Tambah/Edit -->
@include('admin.marketplace.partials.modal', ['categories' => $categories])

@endsection

@push('scripts')
<script>
// ======================================================================
// == PENGELOLAAN MODAL DAN FORM
// ======================================================================
const modal = document.getElementById('productModal');
const form = document.getElementById('productForm');
const modalTitle = document.getElementById('modalTitle');
const formMethod = document.getElementById('formMethod');
const submitButton = document.getElementById('submitButton');
const errorContainer = document.getElementById('error-container');
const errorList = document.getElementById('error-list');

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    form.reset();
    resetErrors();
    document.getElementById('image-preview').src = '';
    document.getElementById('image-preview').classList.add('hidden');
    document.getElementById('image-placeholder').classList.remove('hidden');
}

function openAddModal() {
    form.reset();
    form.action = "{{ route('admin.marketplace.store') }}";
    formMethod.value = 'POST';
    modalTitle.innerText = 'Tambah Produk Baru';
    submitButton.innerText = 'Simpan';
    document.getElementById('product_id').value = '';
    openModal('productModal');
}

function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function(){
        const output = document.getElementById('image-preview');
        output.src = reader.result;
        output.classList.remove('hidden');
        document.getElementById('image-placeholder').classList.add('hidden');
    };
    reader.readAsDataURL(event.target.files[0]);
}

// ======================================================================
// == FUNGSI CRUD (CREATE, READ, UPDATE, DELETE) DENGAN FETCH API
// ======================================================================
async function editProduct(id) {
    openAddModal(); // Menggunakan kembali modal tambah
    modalTitle.innerText = 'Edit Produk';
    submitButton.innerText = 'Perbarui';
    form.action = `/admin/marketplace/${id}`;
    formMethod.value = 'PUT';
    document.getElementById('product_id').value = id;

    try {
        const response = await fetch(`/admin/marketplace/${id}`);
        if (!response.ok) throw new Error('Gagal mengambil data produk.');
        const product = await response.json();

        // Mengisi form dengan data
        document.getElementById('name').value = product.name || '';
        document.getElementById('category_id').value = product.category_id || '';
        document.getElementById('price').value = product.price || '';
        document.getElementById('stock').value = product.stock || '';
        document.getElementById('description').value = product.description || '';
        document.getElementById('is_flash_sale').checked = product.is_flash_sale;

        const imagePreview = document.getElementById('image-preview');
        if (product.image_url) {
            imagePreview.src = `/storage/${product.image_url}`;
            imagePreview.classList.remove('hidden');
            document.getElementById('image-placeholder').classList.add('hidden');
        } else {
            imagePreview.src = '';
            imagePreview.classList.add('hidden');
            document.getElementById('image-placeholder').classList.remove('hidden');
        }

    } catch (error) {
        console.error('Error:', error);
        alert('Gagal memuat data produk untuk diedit.');
        closeModal('productModal');
    }
}

form.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const url = this.action;
    
    if (formMethod.value === 'PUT') {
        formData.append('_method', 'PUT');
    }

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        });

        const result = await response.json();

        if (!response.ok) {
            if (response.status === 422) {
                displayErrors(result.errors);
            } else {
                throw new Error(result.message || 'Terjadi kesalahan saat menyimpan data.');
            }
            return;
        }
        
        await loadTable(window.location.href); // Muat ulang tabel dengan filter saat ini
        closeModal('productModal');
        alert(result.message || 'Data berhasil disimpan!');

    } catch (error) {
        console.error('Error:', error);
        alert(error.message);
    }
});

async function deleteProduct(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus produk ini?')) return;

    try {
        const response = await fetch(`/admin/marketplace/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        });

        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Gagal menghapus produk.');

        document.getElementById(`product-row-${id}`).remove();
        alert('Produk berhasil dihapus.');

    } catch (error) {
        console.error('Error:', error);
        alert(error.message);
    }
}

// ======================================================================
// == PENCARIAN, PAGINASI & FILTER DINAMIS
// ======================================================================
const searchInput = document.getElementById('searchInput');
const categoryFilter = document.getElementById('categoryFilter');
const tableBody = document.getElementById('product-table-body');
const paginationLinks = document.getElementById('pagination-links');
const filterForm = document.getElementById('filterForm');

async function loadTable(url) {
    try {
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const html = await response.text();
        
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        tableBody.innerHTML = tempDiv.querySelector('#product-table-body')?.innerHTML || '<tr><td colspan="7" class="text-center p-4">Tidak ada data ditemukan.</td></tr>';
        paginationLinks.innerHTML = tempDiv.querySelector('#pagination-links')?.innerHTML || '';

    } catch (error) {
        console.error('Gagal memuat data tabel:', error);
    }
}

function handleFilterChange() {
    const params = new URLSearchParams(new FormData(filterForm)).toString();
    const searchUrl = `{{ route('admin.marketplace.index') }}?${params}`;
    window.history.pushState({}, '', searchUrl); // Update URL di browser
    loadTable(searchUrl);
}

let searchTimeout;
searchInput.addEventListener('keyup', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(handleFilterChange, 300);
});

categoryFilter.addEventListener('change', handleFilterChange);

paginationLinks.addEventListener('click', function(e) {
    e.preventDefault();
    const link = e.target.closest('a');
    if (link) {
        const url = link.href;
        window.history.pushState({}, '', url);
        loadTable(url);
    }
});

// ======================================================================
// == PENANGANAN ERROR VALIDASI
// ======================================================================
function displayErrors(errors) {
    errorList.innerHTML = '';
    for (const field in errors) {
        errors[field].forEach(error => {
            const li = document.createElement('li');
            li.textContent = error;
            errorList.appendChild(li);
        });
    }
    errorContainer.classList.remove('hidden');
}

function resetErrors() {
    errorContainer.classList.add('hidden');
    errorList.innerHTML = '';
}
</script>
@endpush
