@extends('layouts.admin')
@section('title', 'Manajemen Produk')
@section('page-title', 'Daftar Produk Marketplace')

@push('styles')
<style>
    /* Animasi untuk baris baru */
    @keyframes fadeIn {
        from { background-color: #fefcbf; opacity: 0; }
        to { background-color: transparent; opacity: 1; }
    }
    .new-row {
        animation: fadeIn 1.5s ease-in-out;
    }
</style>
@endpush

@section('content')
<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <form action="{{ route('admin.marketplace.index') }}" method="GET" class="relative w-full sm:w-1/3">
             <input type="text" name="search" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Cari Produk..." value="{{ request('search') }}">
             <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
             </div>
        </form>
        <button onclick="openAddModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium w-full sm:w-auto">Tambah Produk</button>
    </div>
    
    <!-- Tabel Data Produk -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gambar</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Produk</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Harga</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stok</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flash Sale</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody id="product-table-body" class="bg-white divide-y divide-gray-200">
                @include('admin.marketplace.partials.product_rows', ['products' => $products])
            </tbody>
        </table>
    </div>
    <div id="pagination-links" class="mt-4">
        {{ $products->appends(request()->query())->links() }}
    </div>
</div>

<!-- Modal Tambah/Edit Produk -->
@include('admin.marketplace.partials.modal')

@endsection

@push('scripts')
<script>
    const modal = document.getElementById('productModal');
    const form = document.getElementById('productForm');
    const modalTitle = document.getElementById('modalTitle');
    const formMethod = document.getElementById('formMethod');
    const imagePreview = document.getElementById('image-preview');
    const tableBody = document.getElementById('product-table-body');

    function openModal() { modal.classList.remove('hidden'); }
    function closeModal() { modal.classList.add('hidden'); }
    
    function clearErrors() {
        document.querySelectorAll('[id^="error-"]').forEach(el => el.textContent = '');
    }

    // Fungsi untuk memformat angka menjadi format Rupiah
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    // Fungsi untuk membuat baris tabel baru
    function createProductRow(product) {
        const flashSaleBadge = product.is_flash_sale
            ? `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Ya</span>`
            : `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Tidak</span>`;
        
        const row = document.createElement('tr');
        row.id = `product-${product.id}`;
        row.innerHTML = `
            <td class="px-6 py-4"><img src="${product.image_url ?? 'https://placehold.co/64'}" alt="${product.name}" class="w-16 h-16 object-cover rounded"></td>
            <td class="px-6 py-4 font-medium">${product.name}</td>
            <td class="px-6 py-4">${formatRupiah(product.price)}</td>
            <td class="px-6 py-4">${product.stock}</td>
            <td class="px-6 py-4">${flashSaleBadge}</td>
            <td class="px-6 py-4">
                <button onclick="editProduct(${product.id})" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                <button onclick="deleteProduct(${product.id})" class="text-red-600 hover:text-red-900 ml-4">Hapus</button>
            </td>
        `;
        return row;
    }

    function openAddModal() {
        form.reset();
        form.action = "{{ route('admin.marketplace.store') }}";
        formMethod.value = 'POST';
        modalTitle.innerText = 'Tambah Produk Baru';
        imagePreview.classList.add('hidden');
        clearErrors();
        openModal();
    }

    async function editProduct(id) {
        try {
            const response = await fetch(`/admin/marketplace/${id}`);
            if (!response.ok) throw new Error('Gagal mengambil data produk.');
            const product = await response.json();
            
            form.reset();
            form.action = `/admin/marketplace/${id}`;
            formMethod.value = 'PUT';
            modalTitle.innerText = 'Edit Produk';
            
            document.getElementById('name').value = product.name;
            document.getElementById('price').value = product.price;
            document.getElementById('original_price').value = product.original_price;
            document.getElementById('stock').value = product.stock;
            document.getElementById('description').value = product.description;
            document.getElementById('is_flash_sale').checked = product.is_flash_sale;

            if (product.image_url) {
                imagePreview.src = product.image_url;
                imagePreview.classList.remove('hidden');
            } else {
                imagePreview.classList.add('hidden');
            }
            clearErrors();
            openModal();
        } catch (error) {
            console.error(error);
            alert(error.message);
        }
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        clearErrors();

        const formData = new FormData(this);
        const url = this.action;

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
            });

            if (response.status === 422) {
                const result = await response.json();
                Object.keys(result.errors).forEach(key => {
                    const errorEl = document.getElementById(`error-${key}`);
                    if (errorEl) errorEl.textContent = result.errors[key][0];
                });
                return;
            }

            if (!response.ok) throw new Error('Terjadi kesalahan saat menyimpan data.');
            
            const savedProduct = await response.json();
            
            if (formMethod.value === 'PUT') {
                // Update baris yang ada
                const existingRow = document.getElementById(`product-${savedProduct.id}`);
                const newRow = createProductRow(savedProduct);
                existingRow.replaceWith(newRow);
                newRow.classList.add('new-row');
            } else {
                // Tambah baris baru di atas
                const newRow = createProductRow(savedProduct);
                tableBody.prepend(newRow);
                newRow.classList.add('new-row');
            }

            closeModal();

        } catch (error) {
            console.error(error);
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

            if (!response.ok) throw new Error('Gagal menghapus produk.');
            
            document.getElementById(`product-${id}`).remove();

        } catch (error) {
            console.error(error);
            alert(error.message);
        }
    }
</script>
@endpush
