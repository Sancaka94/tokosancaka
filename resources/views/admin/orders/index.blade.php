{{-- Halaman ini adalah view untuk 'AdminOrderController@index' --}}
{{-- Menggunakan layout admin Tailwind CSS --}}
@extends('layouts.admin') {{-- Pastikan nama layout ini benar dan sudah menggunakan Tailwind --}}

{{-- Kirim CSS tambahan (jika perlu) --}}
@push('styles')
    <!-- CSS Toastr (untuk notifikasi real-time) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- CSS DataTables (Minimal diperlukan) -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    {{-- Opsional: Adaptor styling Tailwind untuk DataTables --}}
    {{-- <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css"> --}}

    <style>
        /* Style tambahan agar elemen DataTables lebih cocok dengan Tailwind */
        .dataTables_wrapper .dataTables_length select {
            padding-right: 2rem; /* Ruang untuk panah dropdown */
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            border-color: #d1d5db; /* gray-300 */
        }
        .dataTables_wrapper .dataTables_filter input {
            border-color: #d1d5db; /* gray-300 */
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5em 1em; margin-left: 2px; border-radius: 0.375rem; /* rounded-md */ border: 1px solid transparent;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background-color: #4f46e5 !important; /* indigo-600 */ color: white !important; border-color: #4f46e5 !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background-color: #e5e7eb !important; /* gray-200 */ border-color: #d1d5db !important; /* gray-300 */ color: black !important;
        }
         .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
         .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
             color: #9ca3af !important; /* gray-400 */ background-color: transparent !important; border-color: transparent !important;
         }
         /* Atur lebar kolom (sesuaikan jika perlu) */
         #orders-table th:nth-child(1) { width: 5%; }  /* No */
         #orders-table th:nth-child(2) { width: 15%; } /* Transaksi */
         #orders-table th:nth-child(3) { width: 25%; } /* Alamat */
         #orders-table th:nth-child(4) { width: 15%; } /* Ekspedisi */
         #orders-table th:nth-child(5) { width: 15%; } /* Isi Paket */
         #orders-table th:nth-child(6) { width: 10%; } /* Status */
         #orders-table th:nth-child(7) { width: 15%; } /* Aksi */
         #orders-table td { vertical-align: middle; } /* Vertically align cell content */
         #orders-table .action-buttons .btn { margin-right: 2px; margin-bottom: 2px; } /* Jarak tombol aksi */
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
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="orders-table" style="width:100%"> {{-- style="width:100%" penting untuk DataTables --}}
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NO</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TRANSAKSI</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ALAMAT</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">EKSPEDISI & ONGKIR</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ISI PAKET</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STATUS</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AKSI</th>
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
    {{-- Pastikan ini di-load setelah Echo diinisialisasi di bootstrap.js --}}
    {{-- Jika Anda compile JS: <script src="{{ mix('js/app.js') }}"></script> --}}

    <script>
        $(document).ready(function() {
            console.log("Document ready, initializing DataTables..."); // Debug log

            // Cek jQuery & DataTables
            if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') {
                console.error("jQuery or DataTables is not loaded!");
                alert("Error: Library tabel tidak termuat. Periksa koneksi internet atau hubungi administrator.");
                return; // Stop eksekusi jika library tidak ada
            }

            // 1. Inisialisasi DataTables
            var table; // Deklarasikan di scope luar agar bisa diakses Echo
            try {
                table = $('#orders-table').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    order: [], // Ikuti order dari server
                    ajax: {
                        url: "{{ route('admin.orders.data') }}",
                        type: "GET",
                        data: function(d) {
                            // Ambil status dari tab yang aktif (punya border-indigo-500)
                            d.status = $('#status-tabs button.border-indigo-500').data('status') || ''; // Default ke '' (Semua)
                            d.search_query = $('#search-query').val();
                            console.log("Sending AJAX data:", d); // Debug log data AJAX
                            return d;
                        },
                        // Tambahkan error handling untuk AJAX
                        error: function(jqXHR, textStatus, errorThrown) {
                             console.error("DataTables AJAX error:", textStatus, errorThrown, jqXHR.responseText);
                             // Tampilkan pesan error sederhana di tabel
                             $('#orders-table tbody').html(
                                 '<tr><td colspan="7" class="text-center text-red-500">Gagal memuat data. Periksa log server atau console browser.</td></tr>'
                             );
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'transaksi', name: 'transaksi', orderable: false, searchable: false },
                        { data: 'alamat', name: 'alamat', orderable: false, searchable: false },
                        { data: 'ekspedisi', name: 'ekspedisi', orderable: false, searchable: false },
                        { data: 'isi_paket', name: 'isi_paket', orderable: false, searchable: false },
                        { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'action-buttons' } // Tambah class untuk styling
                    ],
                    language: { /* ... Bahasa ... */ },
                    // Callback setelah tabel digambar ulang
                    drawCallback: function( settings ) {
                        console.log("DataTables draw complete."); // Debug log
                        // Re-inisialisasi tooltip atau event listener lain jika perlu
                    }
                });
                console.log("DataTables initialized successfully."); // Debug log
            } catch (error) {
                console.error("Error initializing DataTables:", error);
                alert("Terjadi error saat menginisialisasi tabel data.");
            }

            // 2. Event Listener untuk Tab Filter Status (Tailwind)
            // Gunakan delegasi event untuk memastikan listener bekerja setelah AJAX reload
            $('#status-tabs').on('click', 'button.tab-link', function (e) {
                e.preventDefault();
                const currentButton = $(this);
                console.log("Tab clicked:", currentButton.data('status')); // Debug log

                if (currentButton.hasClass('border-indigo-500')) {
                    console.log("Tab already active."); // Debug log
                    return; // Jika sudah aktif, jangan lakukan apa-apa
                }

                // Hapus styling aktif dari semua
                $('#status-tabs button.tab-link').removeClass('text-indigo-600 border-indigo-500').addClass('text-gray-500 hover:text-gray-700 border-transparent hover:border-gray-300').removeAttr('aria-current');
                // Tambah styling aktif ke yang diklik
                currentButton.removeClass('text-gray-500 hover:text-gray-700 border-transparent hover:border-gray-300').addClass('text-indigo-600 border-indigo-500').attr('aria-current', 'page');

                // Reload DataTables jika sudah terinisialisasi
                if (table) {
                     console.log("Reloading DataTable for new status..."); // Debug log
                    table.ajax.reload();
                } else {
                     console.error("DataTable instance is not available for reload.");
                }
            });

            // 3. Event Listener untuk Form Pencarian Custom
            $('#search-form').on('submit', function(e) {
                e.preventDefault();
                console.log("Search submitted:", $('#search-query').val()); // Debug log
                // Reload DataTables jika sudah terinisialisasi
                if (table) {
                    table.ajax.reload();
                } else {
                     console.error("DataTable instance is not available for reload.");
                }
            });


            // 4. Inisialisasi Laravel Echo (Real-time Notification)
            if (typeof Echo !== 'undefined') {
                console.log('Laravel Echo siap, mendengarkan notifikasi...');
                Echo.channel('admin-notifications') // Sesuaikan nama channel
                    .listen('AdminNotificationEvent', (e) => { // Sesuaikan nama event
                        console.log('Notifikasi diterima:', e);
                        toastr.options = { /* ... Opsi Toastr ... */ };
                        toastr.info(e.message || 'Ada data pesanan baru!', e.title || 'Notifikasi Pesanan');

                        // Reload DataTables jika sudah terinisialisasi
                        if (table) {
                            console.log("Reloading DataTable due to notification..."); // Debug log
                            table.ajax.reload(null, false); // false = jangan reset paging
                        } else {
                            console.error("DataTable instance is not available for reload after notification.");
                        }
                    });
            } else {
                console.warn('Laravel Echo tidak terdefinisi. Fitur notifikasi real-time tidak akan aktif.');
            }

            // Konfigurasi default Toastr
            toastr.options = {
                "positionClass": "toast-top-right",
                "progressBar": true,
                "timeOut": "4000", // Durasi notifikasi
            };

            // Inisialisasi modal Bootstrap jika masih ada
            var exportModalElement = document.getElementById('exportModal');
            if(exportModalElement && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var exportModal = new bootstrap.Modal(exportModalElement);
                 console.log("Bootstrap modal initialized."); // Debug log
                 // Jika Anda menggunakan event listener Bootstrap
                 // exportModalElement.addEventListener('shown.bs.modal', function () { ... });
            } else if (exportModalElement) {
                console.warn("Bootstrap Modal JS not found or modal element missing, modal might not work.");
            }

        }); // Akhir document ready
    </script>
@endpush

