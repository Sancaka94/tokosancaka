{{-- Halaman ini adalah view untuk 'AdminOrderController@index' --}}
{{-- Menggunakan layout admin Tailwind CSS --}}
@extends('layouts.admin') {{-- Pastikan nama layout ini benar dan sudah menggunakan Tailwind --}}

{{-- Kirim CSS tambahan (jika perlu) --}}
@push('styles')
    <!-- CSS Toastr (untuk notifikasi real-time) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- CSS DataTables Tailwind Adaptor (Opsional, tapi direkomendasikan) -->
    {{-- <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.13.6/datatables.min.css"/> --}}
    {{-- <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css"> --}}
    <style>
        /* Style tambahan jika diperlukan, misal untuk DataTables pagination agar lebih mirip Tailwind */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5em 1em;
            margin-left: 2px;
            border-radius: 0.25rem;
            border: 1px solid transparent;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current, 
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background-color: #4f46e5; /* Indigo-600 */
            color: white !important;
            border-color: #4f46e5; /* Indigo-600 */
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background-color: #e5e7eb; /* Gray-200 */
            border-color: #d1d5db; /* Gray-300 */
            color: black !important;
        }
         .dataTables_wrapper .dataTables_paginate .paginate_button.disabled, 
         .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
             color: #9ca3af !important; /* Gray-400 */
             background-color: transparent !important;
             border-color: transparent !important;
         }
         /* Style untuk Toastr agar lebih cocok (opsional) */
         .toast {
            opacity: 0.95 !important;
         }
    </style>
@endpush

{{-- Konten Utama Halaman --}}
@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Judul Halaman -->
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Data Pesanan Masuk</h1>

    <!-- Card Utama -->
    <div class="bg-white shadow rounded-lg mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
            
            <!-- Formulir Pencarian Custom -->
            <form id="search-form" class="w-full sm:w-1/2">
                <div class="relative flex items-stretch w-full">
                    {{-- Input pencarian dengan gaya Tailwind --}}
                    <input type="text" id="search-query" class="block w-full px-4 py-2 text-sm text-gray-700 bg-gray-100 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Cari Resi, Invoice, Nama, atau No. HP..." aria-label="Search">
                    {{-- Tombol submit pencarian dengan gaya Tailwind --}}
                    <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-r-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" type="submit">
                        <i class="fas fa-search fa-sm"></i>
                    </button>
                </div>
            </form>

            <!-- Tombol Aksi Kanan Atas -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <!-- Tombol Trigger Modal Export PDF -->
                <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="fas fa-download fa-sm mr-2 opacity-75"></i> Export Laporan
                </button>
                
                <!-- Tombol Tambah Pesanan (jika diperlukan) -->
                {{-- <a href="{{ route('admin.orders.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-plus fa-sm mr-2 opacity-75"></i> Tambah Pesanan
                </a> --}}
            </div>
        </div>

        <div class="p-6">
            
            <!-- Tab Filter Status (versi Tailwind) -->
            <div class="border-b border-gray-200 mb-5">
                <nav class="-mb-px flex space-x-6 overflow-x-auto" id="status-tabs" role="tablist">
                    {{-- Tab 'Semua' --}}
                    <button data-status="" type="button" role="tab" class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm text-indigo-600 border-indigo-500 focus:outline-none" aria-current="page">
                        Semua
                    </button>
                    {{-- Tab 'Menunggu Bayar' --}}
                    <button data-status="pending" type="button" role="tab" class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 border-transparent hover:border-gray-300 focus:outline-none">
                        Menunggu Bayar
                    </button>
                    {{-- Tab 'Menunggu Pickup' --}}
                    <button data-status="processing" type="button" role="tab" class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 border-transparent hover:border-gray-300 focus:outline-none">
                        Menunggu Pickup
                    </button>
                    {{-- Tab 'Diproses' --}}
                    <button data-status="shipping" type="button" role="tab" class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 border-transparent hover:border-gray-300 focus:outline-none">
                        Diproses
                    </button>
                    {{-- Tab 'Terkirim' --}}
                    <button data-status="delivered" type="button" role="tab" class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 border-transparent hover:border-gray-300 focus:outline-none">
                        Terkirim
                    </button>
                     {{-- Tab 'Selesai' --}}
                    <button data-status="completed" type="button" role="tab" class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 border-transparent hover:border-gray-300 focus:outline-none">
                        Selesai
                    </button>
                    {{-- Tab 'Batal' --}}
                    <button data-status="cancelled" type="button" role="tab" class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 border-transparent hover:border-gray-300 focus:outline-none">
                        Batal
                    </button>
                </nav>
            </div>

            <!-- Tabel Data Pesanan (perlu styling Tailwind untuk DataTables) -->
            <div class="overflow-x-auto">
                {{-- DataTables akan menambahkan kelasnya sendiri, kita bisa override sedikit dengan style --}}
                <table class="min-w-full divide-y divide-gray-200" id="orders-table" width="100%">
                    <thead class="bg-gray-50">
                        <tr>
                            {{-- Header Kolom --}}
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
                        {{-- Contoh struktur row (akan digenerate JS): --}}
                        {{-- <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">...</td>
                            <td class="px-6 py-4 text-sm text-gray-500">...</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">...</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">...</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">...</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">...</td>
                        </tr> --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Export Laporan PDF (versi Tailwind) -->
{{-- Perlu Alpine.js atau stimulus/livewire untuk state show/hide, atau gunakan library modal Tailwind --}}
{{-- Contoh sederhana dengan show/hide via JS (perlu ditambahkan event listener untuk tombol) --}}
{{-- Atau, jika Anda masih memuat Bootstrap JS, modal lama mungkin masih berfungsi --}}
{{-- Saya akan tetap gunakan data-bs-toggle/target Bootstrap agar lebih mudah --}}
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog"> {{-- Kelas Bootstrap masih digunakan untuk struktur dasar modal --}}
        <div class="modal-content"> {{-- Kelas Bootstrap masih digunakan --}}
            <div class="modal-header"> {{-- Kelas Bootstrap masih digunakan --}}
                <h5 class="modal-title text-lg font-medium text-gray-900" id="exportModalLabel">Export Laporan Penjualan</h5>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center" data-bs-dismiss="modal" aria-label="Close">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                </button>
            </div>
            <form action="{{ route('admin.orders.report.pdf') }}" method="GET" target="_blank">
                <div class="modal-body p-6 space-y-4"> {{-- Padding dan spacing Tailwind --}}
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                        <input type="date" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" id="start_date" name="start_date" value="{{ \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">Tanggal Selesai</label>
                        <input type="date" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" id="end_date" name="end_date" value="{{ \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer flex items-center justify-end p-6 border-t border-gray-200 rounded-b"> {{-- Flexbox Tailwind --}}
                    <button type="button" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="ml-3 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Export PDF</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

{{-- Kirim JavaScript tambahan ke layout utama --}}
@push('scripts')
    <!-- jQuery (Pastikan sudah ada di layout utama Anda) -->
    <!-- <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script> -->
    
    <!-- JavaScript DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    {{-- Anda mungkin perlu JS adaptor DataTables untuk Bootstrap/Tailwind jika styling default kurang pas --}}
    {{-- <script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script> --}}
    {{-- Jika masih pakai Bootstrap JS untuk modal, load juga --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script> --}} 
    
    <!-- JavaScript Toastr (untuk notifikasi) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <!-- JavaScript Laravel Echo (Jika Anda menggunakan real-time) -->
    {{-- <script src="{{ mix('js/app.js') }}"></script> --}} 

    <script>
        $(document).ready(function() {
            
            // 1. Inisialisasi DataTables
            var table = $('#orders-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                order: [],
                ajax: {
                    url: "{{ route('admin.orders.data') }}",
                    type: "GET",
                    data: function(d) {
                        // Ambil status dari tab Tailwind yang aktif
                        // Selector diubah untuk mencari border-indigo-500
                        d.status = $('#status-tabs button[aria-current="page"]').data('status') || $('#status-tabs button.border-indigo-500').data('status') || ''; 
                        d.search_query = $('#search-query').val(); 
                        return d;
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false }, 
                    { data: 'transaksi', name: 'transaksi', orderable: false, searchable: false }, 
                    { data: 'alamat', name: 'alamat', orderable: false, searchable: false },
                    { data: 'ekspedisi', name: 'ekspedisi', orderable: false, searchable: false },
                    { data: 'isi_paket', name: 'isi_paket', orderable: false, searchable: false },
                    { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false } 
                ],
                 language: { // Sesuaikan bahasa jika perlu
                    processing: "Memuat data...",
                    search: "_INPUT_", 
                    searchPlaceholder: "Cari...", 
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_-_END_ dari _TOTAL_ data",
                    infoEmpty: "Tidak ada data",
                    infoFiltered: "(difilter dari _MAX_ total data)",
                    zeroRecords: "Data tidak ditemukan",
                    emptyTable: "Belum ada pesanan masuk",
                    paginate: {
                        first: "<i class='fas fa-angle-double-left'></i>",
                        last: "<i class='fas fa-angle-double-right'></i>",
                        next: "<i class='fas fa-angle-right'></i>",
                        previous: "<i class='fas fa-angle-left'></i>"
                    }
                },
                // Tambahkan class Tailwind ke wrapper DataTables (opsional)
                // drawCallback: function( settings ) {
                //     $('#orders-table_wrapper').addClass('p-4'); 
                // }
            });

            // 2. Event Listener untuk Tab Filter Status (versi Tailwind)
            $('#status-tabs button').on('click', function (e) {
                e.preventDefault();
                const currentButton = $(this);
                
                // Jangan lakukan apa-apa jika sudah aktif
                if (currentButton.attr('aria-current') === 'page' || currentButton.hasClass('border-indigo-500')) {
                    return;
                }

                // Hapus styling aktif dari semua tombol
                $('#status-tabs button').removeClass('text-indigo-600 border-indigo-500').addClass('text-gray-500 hover:text-gray-700 border-transparent hover:border-gray-300').removeAttr('aria-current');
                
                // Tambahkan styling aktif ke tombol yang diklik
                currentButton.removeClass('text-gray-500 hover:text-gray-700 border-transparent hover:border-gray-300').addClass('text-indigo-600 border-indigo-500').attr('aria-current', 'page');
                
                // Muat ulang tabel
                table.ajax.reload();
            });

            // 3. Event Listener untuk Form Pencarian Custom (tetap sama)
            $('#search-form').on('submit', function(e) {
                e.preventDefault(); 
                table.ajax.reload();
            });


            // 4. Inisialisasi Laravel Echo (Real-time Notification - Kode tetap sama)
            if (typeof Echo !== 'undefined') {
                console.log('Laravel Echo siap, mendengarkan notifikasi...'); 
                Echo.channel('admin-notifications') 
                    .listen('AdminNotificationEvent', (e) => {
                        console.log('Notifikasi diterima:', e); 
                        toastr.options = { 
                            "closeButton": true,       
                            "progressBar": true,       
                            "positionClass": "toast-top-right", 
                            "timeOut": "5000",         
                        };
                        toastr.info(e.message || 'Ada data pesanan baru!', e.title || 'Notifikasi Pesanan');
                        table.ajax.reload(null, false); 
                    });
            } else {
                console.warn('Laravel Echo tidak terdefinisi. Fitur notifikasi real-time tidak akan aktif.');
            }
            
            // Konfigurasi default untuk Toastr (tetap sama)
            toastr.options = {
                "positionClass": "toast-top-right",
                "progressBar": true,
                "timeOut": "3000", 
            };

            // Inisialisasi modal Bootstrap jika masih digunakan
            // var exportModal = new bootstrap.Modal(document.getElementById('exportModal')); // Uncomment jika pakai Bootstrap JS
        });
    </script>
@endpush

