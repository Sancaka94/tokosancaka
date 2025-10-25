{{-- resources/views/admin/products/index.blade.php --}}

<<<<<<< HEAD
@extends('layouts.admin')

@section('title', 'Manajemen Produk')
@section('page-title', 'Manajemen Produk')

@push('styles')
    {{-- CSS untuk DataTables & Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    {{-- Font Awesome (jika belum ada di layout utama) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    {{-- Tailwind (jika layout utama belum include) --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Style untuk sticky action column */
        #product-table th:last-child,
        #product-table td:last-child {
            position: sticky;
            right: 0;
            z-index: 1; /* Pastikan di atas sel lain saat scroll */
        }
        #product-table thead th:last-child {
             background-color: #f8f9fa; /* Warna default thead Bootstrap */
        }
        #product-table tbody td:last-child {
             background-color: #ffffff; /* Warna putih agar senada */
        }

        /* Atur lebar kolom */
        #product-table th.col-no { width: 50px; }
        #product-table th.col-img { width: 80px; }
        #product-table th.col-name { /* Fleksibel */ }
        #product-table th.col-category { width: 150px; } /* Lebar kolom kategori */
        #product-table th.col-price { width: 120px; }
        #product-table th.col-stock { width: 100px; text-align: center;} /* Lebar stok + icon varian */
        #product-table th.col-status { width: 100px; text-align: center;}
        #product-table th.col-action { width: 150px; text-align: center;} /* Lebar kolom aksi sticky */

        #product-table td {
            vertical-align: middle;
        }
        #product-table .badge {
             font-size: 0.8em;
        }
        /* Style untuk ikon varian */
        .variant-indicator {
            font-size: 0.8em;
            color: #6b7280; /* Gray-500 */
            margin-left: 4px;
        }
        /* Style untuk filter */
        .filter-container {
            margin-bottom: 1rem;
            display: flex;
            justify-content: flex-end; /* Posisikan filter di kanan */
            align-items: center;
            gap: 0.5rem;
        }
        .filter-container label {
            font-size: 0.875rem; /* 14px */
            font-weight: 500;
            color: #374151; /* Gray-700 */
        }
        .filter-container select {
            min-width: 200px; /* Lebar minimum dropdown */
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.375rem; /* Rounded-md */
            border: 1px solid #d1d5db; /* Gray-300 */
        }
        /* Styling untuk scrollbar */
        .table-responsive-custom {
            overflow-x: auto; /* Scroll horizontal */
            overflow-y: hidden; /* Cegah scroll vertikal ganda jika tidak perlu */
            /* Anda bisa menambahkan styling scrollbar di sini jika diinginkan */
            /* Contoh: */
            /* scrollbar-width: thin; */
            /* scrollbar-color: #a0aec0 #edf2f7; */ /* Warna thumb dan track */
        }
        /* Pastikan table wrapper tidak membatasi tinggi jika ingin scroll vertikal dari .content-wrapper */
         .dataTables_wrapper {
             /* Hapus atau sesuaikan jika ada style yang membatasi tinggi */
         }

    </style>
@endpush

@section('content')
{{-- Card container --}}
<div class="bg-white p-4 sm:p-6 rounded-lg shadow-md">
    {{-- Header: Judul dan Tombol Tambah --}}
    <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-3">
        <h2 class="text-xl font-semibold text-gray-800">Daftar Semua Produk</h2>
        <a href="{{ route('admin.products.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm font-medium inline-flex items-center gap-1 w-full sm:w-auto justify-center no-underline"> {{-- Tambah no-underline --}}
            <i class="fas fa-plus fa-sm"></i>
            Tambah Produk Baru
        </a>
    </div>

    {{-- Notifikasi --}}
    @include('layouts.partials.notifications')

    {{-- Filter Kategori --}}
    <div class="filter-container">
        <label for="category-filter">Filter Kategori:</label>
        <select id="category-filter" name="category_filter" class="form-select form-select-sm">
            <option value="">Semua Kategori</option>
            {{-- Pastikan variabel $categories dikirim dari controller --}}
            @isset($categories)
                @foreach($categories as $category)
                    <option value="{{ $category->slug }}">{{ $category->name }}</option>
                @endforeach
            @endisset
        </select>
    </div>

    {{-- Tabel Produk Wrapper (untuk scroll horizontal) --}}
    <div class="table-responsive-custom border rounded-md">
        <table class="table table-bordered table-hover w-full mb-0" id="product-table" style="min-width: 900px;"> {{-- Hapus margin bawah default tabel --}}
            <thead class="bg-gray-50">
                <tr>
                    <th class="col-no text-center">No</th>
                    <th class="col-img text-center">Gambar</th>
                    <th class="col-name">Nama Produk</th>
                    <th class="col-category">Kategori</th>
                    <th class="col-price text-end">Harga</th> {{-- Rata kanan --}}
                    <th class="col-stock text-center">Stok</th>
                    <th class="col-status text-center">Status</th>
                    <th class="col-action text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                {{-- Isi tabel akan dimuat oleh JavaScript --}}
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Restock -->
<div id="restockModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden flex items-center justify-center p-4"> {{-- Tambah padding --}}
    <div class="relative p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <form id="restockForm" action="#" method="POST">
            @csrf
            <h3 class="text-xl font-semibold mb-4 text-gray-900">Restock Produk</h3>
            <p class="mb-4 text-sm text-gray-600">Anda akan menambahkan stok untuk produk: <br><strong id="productName" class="text-indigo-700"></strong></p>
            <div class="mb-4">
                <label for="stock_amount" class="block text-sm font-medium text-gray-700 mb-1">Jumlah Stok Ditambahkan</label>
                <input type="number" name="stock" id="stock_amount" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required min="1" placeholder="Masukkan jumlah">
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal('restockModal')" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 text-sm font-medium">Batal</button>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-medium">Simpan Stok</button>
            </div>
        </form>
    </div>
=======


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



    {{-- Tabel akan diisi oleh DataTables --}}

    <div class="overflow-x-auto">

        <table class="table table-bordered product-table w-full" id="product-table">

            <thead class="bg-gray-50">

                <tr>

                    <th class="w-10">No</th>

                    <th class="w-24">Gambar</th>

                    <th>Nama Produk</th>

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

>>>>>>> 4fb5676 (TOKO)
</div>

@endsection

<<<<<<< HEAD
@push('scripts')
    {{-- JavaScript untuk jQuery & DataTables & Bootstrap --}}
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inisialisasi DataTable
            var table = $('#product-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('admin.products.data') }}",
                    data: function (d) {
                        d.category_slug = $('#category-filter').val(); // Kirim slug kategori
                    },
                    // PERBAIKAN: Tambahkan error handling untuk Ajax
                    error: function (xhr, error, thrown) {
                        console.error("DataTables Ajax Error:", error, thrown);
                        // Tampilkan pesan error yang lebih user-friendly jika perlu
                        // Misalnya: $('#product-table_processing').html('Gagal memuat data. Coba lagi nanti.');
                        alert('Gagal memuat data produk. Periksa console browser untuk detail.');
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'image', name: 'image', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'name', name: 'name' },
                    { data: 'category_name', name: 'category.name', orderable: false, searchable: false }, // Kolom kategori
                    { data: 'price', name: 'price', className: 'text-end' },
                    {
                        data: 'stock',
                        name: 'stock',
                        className: 'text-center',
                        orderable: true, // Biarkan bisa diurutkan
                        searchable: false,
                        render: function(data, type, row) {
                            // Cek tipe data sebelum menampilkan
                            let stockDisplay = (typeof data !== 'undefined' && data !== null) ? data : 0;
                            // Cek properti has_variants
                            if (row && row.has_variants === true) { // Cek boolean true
                                stockDisplay += ' <i class="fas fa-code-branch variant-indicator" title="Produk ini memiliki varian"></i>';
                            }
                            return stockDisplay;
                        }
                    },
                    { data: 'status_badge', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' },
                ],
                // Optional: Atur bahasa
                // language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json' }
            });

            // Event listener untuk filter kategori
            $('#category-filter').on('change', function() {
                table.ajax.reload(); // Muat ulang data tabel saat filter berubah
            });
        });

        // --- FUNGSI UNTUK MODAL ---
        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                const firstInput = modal.querySelector('input, select, textarea');
                if(firstInput) firstInput.focus();
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        function openRestockModal(formActionUrl, productName) {
            const form = document.getElementById('restockForm');
            const nameEl = document.getElementById('productName');
            const stockInput = document.getElementById('stock_amount');

            if (form && nameEl && stockInput) {
                form.action = formActionUrl; // Set action form
                nameEl.textContent = productName;
                stockInput.value = ''; // Kosongkan input
                openModal('restockModal');
            } else {
                console.error("Elemen modal restock tidak ditemukan.");
            }
        }

        // Tutup modal jika klik di luar area modal
        window.onclick = function(event) {
            const restockModal = document.getElementById('restockModal');
            if (restockModal && event.target == restockModal) {
                closeModal('restockModal');
            }
        }
    </script>
=======


@push('scripts')

    {{-- JavaScript untuk jQuery & DataTables --}}

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- Bootstrap Bundle JS -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



    <script>

        $('#product-table').DataTable({

    processing: true,

    serverSide: true,

    ajax: "{{ route('admin.products.data') }}", // Panggil route baru khusus JSON

    columns: [

        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },

        { data: 'image', name: 'image', orderable: false, searchable: false },

        { data: 'id', name: 'id' },

        { data: 'price', name: 'price' },

        { data: 'stock', name: 'stock' },

        { data: 'status_badge', name: 'status', orderable: false, searchable: false },

        { data: 'action', name: 'action', orderable: false, searchable: false },

    ]

});





        // --- FUNGSI UNTUK MODAL ---

        function openModal(id) { document.getElementById(id).classList.remove('hidden'); }

        function closeModal(id) { document.getElementById(id).classList.add('hidden'); }



        function openRestockModal(productId, productName) {

            const form = document.getElementById('restockForm');

            const nameEl = document.getElementById('productName');

            

            // Membuat URL action yang benar

            const url = `{{ url('admin/products') }}/${productId}/restock`;

            form.action = url;

            

            nameEl.textContent = productName;

            openModal('restockModal');

        }

    </script>

>>>>>>> 4fb5676 (TOKO)
@endpush

