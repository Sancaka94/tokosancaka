{{-- resources/views/admin/products/index.blade.php --}}

@extends('layouts.admin')

@section('title', 'Manajemen Produk')
@section('page-title', 'Manajemen Produk')

@push('styles')
    {{-- CSS untuk DataTables --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@endpush

@section('content')
<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold">Daftar Semua Produk</h2>
        <a href="{{ route('admin.products.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm font-medium">
            Tambah Produk Baru
        </a>
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <!-- --- Filter Kategori --- -->
    <div class="mb-4">
        <label for="category_filter" class="block text-sm font-medium text-gray-700">Filter Berdasarkan Kategori:</label>
        <select id="category_filter" name="category_filter" class="form-select mt-1 block w-full md:w-1/3 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            <option value="">Semua Kategori</option>
            {{-- Asumsi variabel $categories dikirim dari Controller --}}
            @isset($categories)
                @foreach($categories as $category)
                    {{-- [DIUBAH] Menggunakan $category->slug sesuai controller baru --}}
                    <option value="{{ $category->slug }}">{{ $category->name }}</option>
                @endforeach
            @endisset
        </select>
    </div>
    <!-- --- [AKHIR] Filter Kategori --- -->


    {{-- Tabel akan diisi oleh DataTables --}}
    <div class="overflow-x-auto">
        <table class="table table-bordered product-table w-full" id="product-table">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-10">No</th>
                    <th class="w-24">Gambar</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th class="w-20">Stok</th>
                    <th class="w-24">Status</th>
                    <th class="w-48">Aksi</th>
                </tr>
            </thead>
            <tbody>
                {{-- Isi tabel akan dimuat oleh JavaScript --}}
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Restock -->
<div id="restockModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <form id="restockForm" action="" method="POST">
            @csrf
            @method('POST') {{-- Method untuk restock adalah POST --}}
            <h3 class="text-xl font-semibold mb-4">Restock Produk</h3>
            <p class="mb-4">Anda akan menambahkan stok untuk produk: <strong id="productName"></strong></p>
            <div>
                <label for="stock" class="block text-sm font-medium text-gray-700">Jumlah Stok Baru</label>
                <input type="number" name="stock" id="stock" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required min="1">
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal('restockModal')" class="bg-gray-200 px-4 py-2 rounded-md">Batal</button>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md">Simpan Stok</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    {{-- JavaScript untuk jQuery & DataTables --}}
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Simpan instance DataTables ke dalam variabel
        var table = $('#product-table').DataTable({
            processing: true,
            serverSide: true,
            // --- [PERUBAHAN] Ubah 'ajax' menjadi objek ---
            ajax: {
                url: "{{ route('admin.products.data') }}",
                data: function(d) {
                    // [DIUBAH] Mengirim 'category_slug' sesuai controller baru
                    d.category_slug = $('#category_filter').val();
                }
            },
            // --- [AKHIR PERUBAHAN] ---
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'image', name: 'image', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'category.name', name: 'category.name', defaultContent: 'Belum ada kategori', orderable: false, searchable: false }, // Ini sudah benar
                { data: 'price', name: 'price' },
                { data: 'stock', name: 'stock' },
                { data: 'status_badge', name: 'status', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ]
        });

        // --- Event listener untuk filter (Sudah Benar) ---
        $('#category_filter').on('change', function() {
            // Muat ulang data tabel saat filter diubah
            table.ajax.reload();
        });
        // --- [AKHIR BARU] ---


        // --- FUNGSI UNTUK MODAL ---
        function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
        function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

        // --- [DIUBAH] Fungsi modal disesuaikan untuk menerima URL lengkap ---
        function openRestockModal(restockUrl, productName) {
            const form = document.getElementById('restockForm');
            const nameEl = document.getElementById('productName');
            
            // Langsung gunakan URL yang dikirim dari controller
            form.action = restockUrl;
            
            nameEl.textContent = productName;
            openModal('restockModal');
        }
    </script>
@endpush

