@extends('layouts.admin')

@section('title', 'Manajemen Produk')
@section('page-title', 'Manajemen Produk')

@push('styles')
    {{-- CSS untuk DataTables (theme Bootstrap 5) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Font Awesome Icons --><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    {{-- DITAMBAHKAN: Style untuk menyesuaikan tampilan DataTables (Bootstrap 5 theme) dengan layout Tailwind/Custom Admin Anda --}}
    <style>
        /* Mengatur ulang layout DataTables */
        #product-table_wrapper .row {
            margin-left: 0;
            margin-right: 0;
            padding-top: 1rem; /* Tambah padding atas */
            padding-bottom: 0.5rem;
            align-items: center; /* Rata tengah vertikal */
        }
        #product-table_length,
        #product-table_filter {
            padding: 0 15px; /* Sesuaikan padding */
            margin-bottom: 1rem; /* Jarak bawah */
        }

        /* Styling untuk "Show X entries" */
        #product-table_length label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: normal;
            color: #4b5563; /* text-gray-700 */
        }
        #product-table_length select {
            border-width: 1px;
            border-color: #d1d5db; /* border-gray-300 */
            border-radius: 0.375rem; /* rounded-md */
            padding: 0.25rem 0.5rem;
            font-weight: normal;
            appearance: none; /* Hilangkan default arrow di select */
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
        }

        /* Styling untuk "Search" */
        #product-table_filter label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: normal;
            color: #4b5563; /* text-gray-700 */
        }
        #product-table_filter input {
            border-width: 1px;
            border-color: #d1d5db; /* border-gray-300 */
            border-radius: 0.375rem; /* rounded-md */
            padding: 0.25rem 0.75rem;
            font-weight: normal;
            width: 200px; /* Lebar input search */
        }
        #product-table_info {
            padding: 0 15px; /* Sesuaikan padding */
            color: #4b5563;
        }

        /* Styling Pagination */
        #product-table_paginate {
            padding: 0 15px; /* Sesuaikan padding */
            margin-top: 1rem;
            display: flex;
            justify-content: flex-end; /* Pindahkan ke kanan */
            align-items: center;
            gap: 0.5rem;
        }
        .pagination {
            margin: 0; /* Hapus margin default pagination */
        }
        .page-item .page-link {
            color: #4f46e5; /* indigo-600 */
            border: 1px solid #d1d5db; /* border-gray-300 */
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            margin: 0 0.125rem;
            background-color: #fff;
        }
        .page-item.active .page-link {
            background-color: #4f46e5 !important; /* indigo-600 */
            border-color: #4f46e5 !important;
            color: #fff !important;
        }
        .page-item.disabled .page-link {
            color: #9ca3af; /* gray-400 */
        }

        /* Styling Tabel */
        .product-table {
            width: 100% !important; /* Pastikan tabel mengisi lebar parent */
            border-collapse: collapse;
        }
        .product-table th, .product-table td {
            padding: 12px 15px;
            border: 1px solid #e5e7eb; /* border-gray-200 */
            text-align: left;
            vertical-align: middle;
        }
        .product-table thead th {
            background-color: #f9fafb; /* bg-gray-50 */
            color: #374151; /* text-gray-700 */
            font-weight: 600; /* font-semibold */
            text-transform: uppercase;
            font-size: 0.75rem;
        }
        .product-table tbody tr:nth-child(even) {
            background-color: #f3f4f6; /* bg-gray-50 untuk striping */
        }

        /* Styling Tombol Aksi */
        .action-buttons {
            display: flex;
            gap: 0.5rem; /* Jarak antar tombol */
            justify-content: center; /* Rata tengah untuk kolom aksi */
        }
        .action-buttons .btn-action {
            width: 34px; /* Lebar tombol */
            height: 34px; /* Tinggi tombol */
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem; /* rounded-md */
            font-size: 0.9rem;
            color: #fff; /* Warna ikon putih */
            transition: background-color 0.2s;
        }
        .btn-action.btn-success { background-color: #22c55e; } /* green-500 */
        .btn-action.btn-warning { background-color: #f59e0b; } /* yellow-500 */
        .btn-action.btn-secondary { background-color: #6b7280; } /* gray-500 */
        .btn-action.btn-danger { background-color: #ef4444; } /* red-500 */

        .btn-action.btn-success:hover { background-color: #16a34a; } /* green-600 */
        .btn-action.btn-warning:hover { background-color: #d97706; } /* yellow-600 */
        .btn-action.btn-secondary:hover { background-color: #4b5563; } /* gray-600 */
        .btn-action.btn-danger:hover { background-color: #dc2626; } /* red-600 */

        /* Styling Gambar Produk */
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 0.25rem;
        }
        /* Styling Badge Status */
        .badge-status {
            display: inline-flex; /* Gunakan flexbox untuk rata tengah ikon jika ada */
            align-items: center;
            padding: 0.3em 0.7em;
            border-radius: 9999px; /* rounded-full */
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1;
            text-transform: capitalize;
            color: #fff;
        }
        .badge-status.active { background-color: #22c55e; } /* green-500 */
        .badge-status.inactive { background-color: #6b7280; } /* gray-500 */

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
        <table class="table product-table" id="product-table"> {{-- Hapus class 'table-bordered', style sudah di custom --}}
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

<!-- Modal Restock -->{{-- Modal ini menggunakan Tailwind CSS untuk styling, jadi tidak perlu class Bootstrap 'modal fade' --}}
<div id="restockModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md mx-auto">
        <form id="restockForm" action="" method="POST">
            @csrf
            {{-- Method untuk restock adalah POST, jadi @method('POST') tidak diperlukan di sini --}}
            <h3 class="text-xl font-semibold mb-4">Restock Produk</h3>
            <p class="mb-4 text-gray-700">Anda akan menambahkan stok untuk produk: <strong id="productName"></strong></p>
            <div class="mb-4">
                <label for="stock" class="block text-sm font-medium text-gray-700">Jumlah Stok Tambahan</label>
                <input type="number" name="stock" id="stock" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required min="1">
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal('restockModal')" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 transition duration-150">Batal</button>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition duration-150">Simpan Stok</button>
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
    
    <script>
        $(document).ready(function() {
            $('#product-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.products.data') }}", // Panggil route khusus JSON
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { 
                        data: 'image', // PERBAIKAN: Hanya panggil datanya
                        name: 'image', 
                        orderable: false, 
                        searchable: false
                        // PERBAIKAN: Hapus fungsi render, karena controller sudah mengirim HTML
                    },
                    { data: 'name', name: 'name' },
                    { data: 'category_name', name: 'category.name' }, 
                    { data: 'price', name: 'price' },
                    { data: 'stock', name: 'stock' },
                    { 
                        data: 'status_badge', // PERBAIKAN: Hanya panggil datanya
                        name: 'status', 
                        orderable: false, 
                        searchable: false
                        // PERBAIKAN: Hapus fungsi render, karena controller sudah mengirim HTML
                    },
                    { 
                        data: 'action', // PERBAIKAN: Hanya panggil datanya
                        name: 'action', 
                        orderable: false, 
                        searchable: false
                        // PERBAIKAN: Hapus fungsi render, karena controller sudah mengirim HTML
                        // dan fungsi openRestockModal() akan dipanggil dari HTML yang dikirim controller
                    },
                ],
                // Pastikan DataTables menggunakan class Bootstrap 5 untuk styling
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>><"row"<"col-sm-12"tr>><"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json' // Bahasa Indonesia
                }
            });
        });

        // --- FUNGSI UNTUK MODAL (Sudah menggunakan Tailwind) ---
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
            document.body.classList.add('overflow-hidden'); // Mencegah scroll di body saat modal terbuka
        }
        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
            document.body.classList.remove('overflow-hidden'); // Mengizinkan scroll kembali
        }

        // Fungsi ini sekarang akan dipanggil oleh tombol 'Restock'
        // yang di-render oleh ProductController@getData
        function openRestockModal(productId, productName) {
            const form = document.getElementById('restockForm');
            const nameEl = document.getElementById('productName');
            const stockInput = document.getElementById('stock');
            
            // Membuat URL action yang benar
            const url = `{{ route('admin.products.restock', ':id') }}`.replace(':id', productId);
            form.action = url;
            
            nameEl.textContent = productName;
            stockInput.value = ''; // Kosongkan input stok setiap kali modal dibuka
            openModal('restockModal');
        }
    </script>
@endpush

