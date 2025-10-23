@extends('layouts.admin')

@section('title', 'Manajemen Produk')
@section('page-title', 'Manajemen Produk')

@push('styles')
    {{-- CSS untuk DataTables (theme Bootstrap 5) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    {{-- DIHAPUS: Bootstrap 5 CSS - Ini bentrok dengan class Tailwind Anda --}}
    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> --}}
    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> --}}

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    {{-- DITAMBAHKAN: Style untuk menyesuaikan tampilan DataTables (Bootstrap 5 theme) dengan layout Tailwind --}}
    <style>
        #product-table_wrapper .row {
            width: 100%;
            margin-left: 0;
            margin-right: 0;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        #product-table_filter {
            display: flex;
            justify-content: flex-end;
        }
        #product-table_paginate {
            display: flex;
            justify-content: flex-end;
        }
        .dataTables_length > label, .dataTables_filter > label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-weight: normal;
        }
        .dataTables_length select, .dataTables_filter input {
            border-width: 1px;
            border-color: #d1d5db; /* gray-300 */
            border-radius: 0.375rem; /* rounded-md */
            padding: 0.25rem 0.5rem;
            font-weight: normal;
        }
        .dataTables_filter input {
            width: auto;
            display: inline-block;
        }
        .page-item.active .page-link {
            background-color: #4f46e5; /* indigo-600 */
            border-color: #4f46e5;
            z-index: 1;
        }
        .page-link {
            color: #4f46e5;
        }
        table.dataTable {
            border-collapse: collapse !important;
        }
        table.table-bordered.dataTable {
            border: 1px solid #dee2e6;
        }
    </style>
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

    {{-- Tabel akan diisi oleh DataTables --}}
    <div class="overflow-x-auto">
        {{-- PERBAIKAN: Menambahkan class 'table-striped' untuk styling B5 --}}
        <table class="table table-bordered table-striped product-table w-full" id="product-table">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-10">No</th>
                    <th class="w-24">Gambar</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th> {{-- DITAMBAHKAN --}}
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
    
    {{-- DIHAPUS: Bootstrap Bundle JS - Tidak diperlukan karena modal Anda menggunakan JS custom Tailwind --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> --}}

    <script>
        $('#product-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.products.data') }}", // Panggil route baru khusus JSON
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'image', name: 'image', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'category_name', name: 'category.name' }, // DITAMBAHKAN
                { data: 'price', name: 'price' },
                { data: 'stock', name: 'stock' },
                { data: 'status_badge', name: 'status', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ]
        });

        // --- FUNGSI UNTUK MODAL ---
        // (Ini sudah benar untuk modal berbasis class 'hidden' Tailwind)
        function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
        function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

        function openRestockModal(productId, productName) {
            const form = document.getElementById('restockForm');
            const nameEl = document.getElementById('productName');
            
            // Membuat URL action yang benar
            const url = `{{ route('admin.products.restock', ':id') }}`.replace(':id', productId);
            form.action = url;
            
            nameEl.textContent = productName;
            openModal('restockModal');
        }
    </script>
@endpush

