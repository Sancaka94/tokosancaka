{{-- Halaman ini adalah view untuk 'AdminOrderController@index' --}}
{{-- Menggunakan layout admin Tailwind CSS --}}
@extends('layouts.admin') {{-- Pastikan nama layout ini benar dan sudah menggunakan Tailwind --}}

{{-- Kirim CSS tambahan --}}
@push('styles')
    <!-- CSS Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- CSS DataTables (Minimal diperlukan) -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <style>
        /* Styling dasar DataTables agar cocok Tailwind */
        .dataTables_wrapper .dataTables_length select {
            padding-right: 2rem; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; -webkit-appearance: none; -moz-appearance: none; appearance: none; border-color: #d1d5db; /* gray-300 */
            width: auto; /* Agar tidak terlalu lebar */ display: inline-block; /* Agar width auto bekerja */
            @apply shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block sm:text-sm border-gray-300 rounded-md py-2 pl-3 pr-8; /* Class Tailwind untuk input */
        }
        .dataTables_wrapper .dataTables_filter input {
             @apply shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md py-2 px-3 ml-2; /* Class Tailwind untuk input */
             display: inline-block; width: auto; /* Override DataTables default */
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5em 1em; margin-left: 2px; border-radius: 0.375rem; border: 1px solid transparent; @apply focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500; /* Styling focus */
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background-color: #4f46e5 !important; color: white !important; border-color: #4f46e5 !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background-color: #e5e7eb !important; border-color: #d1d5db !important; color: black !important;
        }
         .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
         .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
             color: #9ca3af !important; background-color: transparent !important; border-color: transparent !important; cursor: default;
         }
         /* Styling untuk Toastr (opsional) */
         .toast { opacity: 0.95 !important; }

        /* == CSS untuk Sticky Column Aksi == */
        #orders-table th:last-child,
        #orders-table td:last-child {
            position: -webkit-sticky; /* Untuk Safari */
            position: sticky;       /* Posisi sticky */
            right: 0;               /* Tempel di paling kanan */
            z-index: 10;            /* Pastikan di atas kolom lain saat scroll */
            background-color: inherit; /* Warisi warna background dari row (biasanya putih atau abu-abu) */
        }
        /* Beri background solid pada header sticky agar tidak transparan */
        #orders-table thead th:last-child {
            background-color: #f9fafb; /* gray-50 */
        }
        /* Beri border kiri pada kolom sticky agar ada pemisah visual saat scroll */
         #orders-table th:last-child,
         #orders-table td:last-child {
             border-left: 1px solid #e5e7eb; /* gray-200 */
         }
         /* Pastikan container tabel bisa di-scroll horizontal */
         .dataTables_wrapper {
             overflow-x: auto;
         }
         /* == Akhir CSS Sticky Column == */


         /* Atur lebar kolom (sesuaikan jika perlu) */
         #orders-table th:nth-child(1) { width: 5%; }  /* No */
         #orders-table th:nth-child(2) { width: 15%; } /* Transaksi */
         #orders-table th:nth-child(3) { width: 25%; } /* Alamat */
         #orders-table th:nth-child(4) { width: 15%; } /* Ekspedisi */
         #orders-table th:nth-child(5) { width: 15%; } /* Isi Paket */
         #orders-table th:nth-child(6) { width: 10%; } /* Status */
         /* Kolom Aksi dibuat sedikit lebih lebar untuk sticky */
         #orders-table th:nth-child(7) { width: 15%; min-width: 180px; } /* Aksi, beri min-width */

         #orders-table td { vertical-align: middle; } /* Vertically align cell content */
         /* Beri class pada kolom action di JS agar bisa di-target spesifik */
         #orders-table td.action-buttons .d-flex {
             flex-wrap: nowrap !important; /* Paksa tombol tidak turun baris */
             justify-content: flex-end; /* Ratakan tombol ke kanan dalam sel */
         }
         #orders-table td.action-buttons .btn { margin-right: 3px; } /* Jarak tombol aksi */
         #orders-table td.action-buttons .btn:last-child { margin-right: 0; }
    </style>
@endpush

{{-- Konten Utama Halaman --}}
@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Judul Halaman -->
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Data Pesanan Masuk</h1>

    <!-- Card Utama -->
    <div class="bg-white shadow rounded-lg mb-6 overflow-hidden">
        {{-- Header Card: Pencarian dan Tombol Aksi --}}
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">

            <!-- Formulir Pencarian Custom -->
            <form id="search-form" class="w-full sm:w-1/2">
                <div class="relative flex items-stretch w-full">
                    <input type="text" id="search-query" class="block w-full px-4 py-2 text-sm text-gray-700 bg-gray-100 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Cari Resi, Invoice, Nama, atau No. HP..." aria-label="Search">
                    <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-r-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" type="submit">
                        <i class="fas fa-search fa-sm"></i>
                    </button>
                </div>
            </form>

            <!-- Tombol Aksi Kanan Atas -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="fas fa-download fa-sm mr-2 opacity-75"></i> Export Laporan
                </button>
                {{-- Tombol Tambah Pesanan (jika diperlukan) --}}
                {{-- <a href="{{ route('admin.orders.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-plus fa-sm mr-2 opacity-75"></i> Tambah Pesanan
                </a> --}}
            </div>
        </div>

        {{-- Body Card: Tab Filter dan Tabel --}}
        <div class="p-6">

            <!-- Tab Filter Status (versi Tailwind) -->
            <div class="border-b border-gray-200 mb-5">
                <nav class="-mb-px flex space-x-6 overflow-x-auto" id="status-tabs" role="tablist">
                    {{-- !! PENTING: Pastikan nilai `data-status` sesuai dengan $statusMap di Controller !! --}}
                    <button data-status="" type="button" role="tab" class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm text-indigo-600 border-indigo-500 focus:outline-none tab-link" aria-current="page">Semua</button>
                    <button data-status="pending" type="button" role="tab" class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 border-transparent hover:border-gray-300 focus:outline-none tab-link">Menunggu Bayar</button>
                    <button data-status="menunggu-pickup" type="button" role="tab" class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 border-transparent hover:border-gray-300 focus:outline-none tab-link">Menunggu Pickup</button>
                    <button data-status="diproses" type="button" role="tab" class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 border-transparent hover:border-gray-300 focus:outline-none tab-link">Diproses</button>
                    <button data-status="terkirim" type="button" role="tab" class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 border-transparent hover:border-gray-300 focus:outline-none tab-link">Terkirim</button>
                    <button data-status="selesai" type="button" role="tab" class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 border-transparent hover:border-gray-300 focus:outline-none tab-link">Selesai</button>
                    <button data-status="batal" type="button" role="tab" class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 border-transparent hover:border-gray-300 focus:outline-none tab-link">Batal</button>
                </nav>
            </div>

            <!-- Tabel Data Pesanan -->
            {{-- Div wrapper ini penting untuk scroll horizontal --}}
            <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left text-gray-500" id="orders-table"> {{-- Hapus min-w-full, ganti w-full --}}
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3">NO</th>
                            <th scope="col" class="px-6 py-3">TRANSAKSI</th>
                            <th scope="col" class="px-6 py-3">ALAMAT</th>
                            <th scope="col" class="px-6 py-3">EKSPEDISI & ONGKIR</th>
                            <th scope="col" class="px-6 py-3">ISI PAKET</th>
                            <th scope="col" class="px-6 py-3">STATUS</th>
                            {{-- Kolom Aksi (sticky) --}}
                            <th scope="col" class="px-6 py-3 sticky right-0 bg-gray-50 border-l border-gray-200">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        {{-- Data akan diisi oleh DataTables --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Export Laporan (Struktur Bootstrap, Styling Tailwind) -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    {{-- Konten Modal tidak diubah --}}
     <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-b border-gray-200">
                <h5 class="modal-title text-lg font-medium text-gray-900" id="exportModalLabel">Export Laporan Penjualan</h5>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center" data-bs-dismiss="modal" aria-label="Close">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                </button>
            </div>
            <form action="{{ route('admin.orders.report.pdf') }}" method="GET" target="_blank">
                <div class="modal-body p-6 space-y-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                        <input type="date" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" id="start_date" name="start_date" value="{{ \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">Tanggal Selesai</label>
                        <input type="date" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" id="end_date" name="end_date" value="{{ \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer flex items-center justify-end p-6 border-t border-gray-200 rounded-b">
                    <button type="button" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="ml-3 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Export PDF</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

{{-- JavaScript --}}
@push('scripts')
    <!-- jQuery (Harus ada SEBELUM DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- JavaScript DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    {{-- Opsional: Bootstrap 5 JS (jika modal masih membutuhkannya) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script> 
    
    <!-- JavaScript Toastr -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <!-- JavaScript Laravel Echo -->
    {{-- <script src="{{ mix('js/app.js') }}"></script> --}}

    <script>
        $(document).ready(function() {
            console.log("Document ready, initializing DataTables...");

            if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') {
                console.error("jQuery or DataTables is not loaded!");
                alert("Error: Library tabel tidak termuat.");
                return;
            }

            var table;
            try {
                table = $('#orders-table').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: false, // Matikan responsive default agar sticky bekerja
                    scrollX: true,    // Aktifkan scroll horizontal DataTables
                    order: [],
                    ajax: {
                        url: "{{ route('admin.orders.data') }}",
                        type: "GET",
                        data: function(d) {
                            d.status = $('#status-tabs button.border-indigo-500').data('status') || '';
                            d.search_query = $('#search-query').val();
                            console.log("Sending AJAX data:", d);
                            return d;
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                             console.error("DataTables AJAX error:", textStatus, errorThrown, jqXHR.responseText);
                             $('#orders-table tbody').html(
                                 '<tr><td colspan="7" class="text-center text-red-500 py-4">Gagal memuat data.</td></tr>' // Perbaiki colspan
                             );
                        }
                    },
                    columns: [
                        // ✅ PERBAIKAN: Tambahkan `className` untuk menargetkan kolom sticky
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'px-6 py-4 whitespace-nowrap text-sm text-gray-500' },
                        { data: 'transaksi', name: 'transaksi', orderable: false, searchable: false, className: 'px-6 py-4 whitespace-nowrap text-sm text-gray-900' },
                        { data: 'alamat', name: 'alamat', orderable: false, searchable: false, className: 'px-6 py-4 text-sm text-gray-500' }, // Hapus whitespace-nowrap
                        { data: 'ekspedisi', name: 'ekspedisi', orderable: false, searchable: false, className: 'px-6 py-4 whitespace-nowrap text-sm text-gray-500' },
                        { data: 'isi_paket', name: 'isi_paket', orderable: false, searchable: false, className: 'px-6 py-4 whitespace-nowrap text-sm text-gray-500' },
                        { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false, className: 'px-6 py-4 whitespace-nowrap text-sm text-gray-500' },
                        { data: 'action', name: 'action', orderable: false, searchable: false,
                          // ✅ PERBAIKAN: className untuk kolom Aksi (sticky)
                          className: 'px-6 py-4 whitespace-nowrap text-sm font-medium sticky right-0 bg-white border-l border-gray-200 action-buttons'
                        }
                    ],
                    language: {
                        processing: "<span class='text-indigo-600'>Memuat data...</span>", // Styling loading
                        search: "", // Hapus label search default
                        searchPlaceholder: "Cari...",
                        lengthMenu: "Tampilkan _MENU_",
                        info: "Menampilkan _START_-_END_ dari _TOTAL_ data",
                        infoEmpty: "Tidak ada data",
                        infoFiltered: "(difilter dari _MAX_ total data)",
                        zeroRecords: "Data tidak ditemukan",
                        emptyTable: "Belum ada pesanan masuk",
                        paginate: { first: "<i class='fas fa-angle-double-left'></i>", last: "<i class='fas fa-angle-double-right'></i>", next: "<i class='fas fa-angle-right'></i>", previous: "<i class='fas fa-angle-left'></i>" }
                     },
                     // ✅ PERBAIKAN: DOM layout DataTables agar lebih cocok Tailwind
                     dom: "<'flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-4'<'flex items-center gap-2'l><'flex-1'f>>" + // Length menu & Filter
                          "<'block w-full overflow-x-auto'tr>" + // Table (dengan overflow wrapper)
                          "<'flex flex-col sm:flex-row sm:items-center sm:justify-between mt-4 gap-4'<'text-sm text-gray-500'i><'mt-2 sm:mt-0'p>>", // Info & Pagination

                    // Callback setelah tabel digambar
                    drawCallback: function( settings ) {
                        console.log("DataTables draw complete.");
                        // Sembunyikan search default DataTables jika kita pakai custom search
                        $('.dataTables_filter').hide();
                        // Pastikan background kolom sticky di tbody sesuai saat redraw
                         $('#orders-table tbody td:last-child').css('background-color', '#ffffff'); // Atur background putih solid
                    }
                });
                console.log("DataTables initialized successfully.");
            } catch (error) {
                console.error("Error initializing DataTables:", error);
                alert("Terjadi error saat menginisialisasi tabel data.");
            }

            // Event Listener Tab Filter (Tetap Sama)
            $('#status-tabs').on('click', 'button.tab-link', function (e) { /* ... kode ... */ });

            // Event Listener Form Pencarian Custom (Tetap Sama)
            $('#search-form').on('submit', function(e) { /* ... kode ... */ });

            // Laravel Echo (Kode Tetap Sama)
            if (typeof Echo !== 'undefined') { /* ... kode ... */ } else { /* ... kode ... */ }

            // Toastr Config (Tetap Sama)
            toastr.options = { /* ... kode ... */ };

            // Modal Init (Tetap Sama)
            var exportModalElement = document.getElementById('exportModal'); /* ... kode ... */

        }); // Akhir document ready
    </script>
@endpush

