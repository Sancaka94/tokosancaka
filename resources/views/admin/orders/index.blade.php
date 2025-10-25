{{-- Halaman ini adalah view untuk 'AdminOrderController@index' --}}
{{-- PERUBAHAN: Menggunakan layout admin.layouts.admin --}}
@extends('layouts.admin')

{{-- Kirim CSS tambahan ke layout utama --}}
@push('styles')
    <!-- CSS DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- CSS Toastr (untuk notifikasi real-time) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    <style>
        /* Style untuk tab filter status */
        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
        }
        .nav-tabs .nav-link.active {
            border-bottom-color: var(--bs-primary, #0d6efd);
            color: var(--bs-primary, #0d6efd);
            font-weight: bold;
        }
        /* Style agar tombol aksi tidak 'wrap' jika tidak perlu */
        #orders-table .d-flex {
            flex-wrap: nowrap;
            gap: 0.25rem !important;
        }
        /* Atur lebar kolom agar lebih rapi */
        #orders-table th:nth-child(1) { width: 5%; } /* No */
        #orders-table th:nth-child(2) { width: 15%; } /* Transaksi */
        #orders-table th:nth-child(3) { width: 25%; } /* Alamat */
        #orders-table th:nth-child(4) { width: 15%; } /* Ekspedisi */
        #orders-table th:nth-child(5) { width: 20%; } /* Isi Paket */
        #orders-table th:nth-child(6) { width: 10%; } /* Status */
        #orders-table th:nth-child(7) { width: 10%; } /* Aksi */
    </style>
@endpush

{{-- Konten Utama Halaman --}}
@section('content')
<div class="container-fluid">

    <!-- Judul Halaman -->
    <h1 class="h3 mb-3 text-gray-800">Data Pesanan Masuk</h1>

    <!-- Card Utama -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            
            <!-- Formulir Pencarian Custom -->
            <form id="search-form" class="d-none d-sm-inline-block form-inline mr-auto my-2 my-md-0 w-50">
                <div class="input-group">
                    <input type="text" id="search-query" class="form-control bg-light border-0 small" placeholder="Cari Resi, Invoice, Nama, atau No. HP..." aria-label="Search" aria-describedby="basic-addon2">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search fa-sm"></i>
                    </button>
                </div>
            </form>

            <!-- Tombol Aksi Kanan Atas -->
            <div class="d-flex gap-2">
                <!-- Tombol Trigger Modal Export PDF -->
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="fas fa-download fa-sm text-white-50"></i> Export Laporan
                </button>
                
                <!-- Tombol Tambah Pesanan (jika ada halaman 'create') -->
                {{-- <a href="{{ route('admin.orders.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Pesanan
                </a> --}}
            </div>
        </div>

        <div class="card-body">
            
            <!-- Tab Filter Status -->
            <ul class="nav nav-tabs mb-3" id="status-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-status="" type="button" role="tab">Semua</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-status="pending" type="button" role="tab">Menunggu Bayar</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-status="menunggu-pickup" type="button" role="tab">Menunggu Pickup</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-status="diproses" type="button" role="tab">Diproses</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-status="terkirim" type="button" role="tab">Terkirim</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-status="batal" type="button" role="tab">Batal</button>
                </li>
            </ul>

            <!-- Tabel Data Pesanan -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="orders-table" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>TRANSAKSI</th>
                            <th>ALAMAT</th>
                            <th>EKSPEDISI & ONGKIR</th>
                            <th>ISI PAKET</th>
                            <th>STATUS</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data akan diisi oleh DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Export Laporan PDF -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Export Laporan Penjualan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Form ini mengarah ke route 'admin.orders.report.pdf' -->
            <form action="{{ route('admin.orders.report.pdf') }}" method="GET" target="_blank">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="end_date" class="form-label">Tanggal Selesai</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Export PDF</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

{{-- Kirim JavaScript tambahan ke layout utama --}}
@push('scripts')
    <!-- jQuery (Asumsi sudah ada di layout, tapi pastikan) -->
    <!-- <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script> -->
    
    <!-- JavaScript DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- JavaScript Toastr (untuk notifikasi) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <!-- JavaScript Laravel Echo (Asumsi sudah di-setup di bootstrap.js) -->
    <!-- <script src="{{ asset('js/app.js') }}"></script> --> {{-- Uncomment jika Anda compile JS --}}

    <script>
        $(document).ready(function() {
            
            // 1. Inisialisasi DataTables
            // Variabel 'table' ini penting agar bisa diakses oleh fungsi lain
            var table = $('#orders-table').DataTable({
                processing: true, // Tampilkan indikator loading
                serverSide: true, // Proses data di sisi server
                responsive: true, // Buat tabel responsif
                // Hilangkan 'order' default DataTables (kita set 'orderBy' di controller)
                order: [], 
                // Tentukan URL untuk mengambil data AJAX
                ajax: {
                    url: "{{ route('admin.orders.data') }}", // Route dari controller
                    type: "GET",
                    // Kirim data tambahan (filter status & pencarian)
                    data: function(d) {
                        // Ambil status dari tab yang 'active'
                        d.status = $('#status-tabs .nav-link.active').data('status');
                        // Ambil query dari kotak pencarian
                        d.search_query = $('#search-query').val();
                        return d;
                    }
                },
                // Definisikan kolom-kolom
                columns: [
                    // { data: 'id', name: 'id' }, // Ganti dengan DT_RowIndex
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'transaksi', name: 'transaksi', orderable: false, searchable: false },
                    { data: 'alamat', name: 'alamat', orderable: false, searchable: false },
                    { data: 'ekspedisi', name: 'ekspedisi', orderable: false, searchable: false },
                    { data: 'isi_paket', name: 'isi_paket', orderable: false, searchable: false },
                    { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                 // Atur bahasa (opsional)
                 language: {
                    processing: "Memuat data...",
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ entri",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
                    infoFiltered: "(disaring dari _MAX_ total entri)",
                    zeroRecords: "Tidak ditemukan data yang sesuai",
                    emptyTable: "Tidak ada data tersedia di tabel",
                    paginate: {
                        first: "Awal",
                        last: "Akhir",
                        next: "Berikutnya",
                        previous: "Sebelumnya"
                    }
                }
            });

            // 2. Event Listener untuk Tab Filter Status
            $('#status-tabs .nav-link').on('click', function (e) {
                e.preventDefault(); // Hentikan aksi default
                
                // Hapus 'active' dari semua tab
                $('#status-tabs .nav-link').removeClass('active');
                // Tambahkan 'active' ke tab yang diklik
                $(this).addClass('active');
                
                // Muat ulang data tabel
                // DataTables akan otomatis mengambil 'data-status' baru
                // dari tab yang kini 'active' (lihat 'ajax.data' di atas)
                table.ajax.reload();
            });

            // 3. Event Listener untuk Form Pencarian Custom
            $('#search-form').on('submit', function(e) {
                e.preventDefault(); // Hentikan form submit standar
                
                // Muat ulang data tabel
                // DataTables akan otomatis mengambil 'search_query' baru
                // dari input (lihat 'ajax.data' di atas)
                table.ajax.reload();
            });


            // 4. Inisialisasi Laravel Echo (Real-time Notification)
            // PASTIKAN: Anda sudah setup Laravel Echo & broadcasting
            // (Pusher/Reverb/Soketi) di backend dan di 'resources/js/bootstrap.js'
            
            /* // Uncomment bagian ini setelah setup Echo selesai
            
            // Ganti 'admin-notifications' dengan nama channel Anda
            Echo.channel('admin-notifications') 
                // Ganti 'AdminNotificationEvent' dengan nama class Event Anda
                .listen('AdminNotificationEvent', (e) => {
                    
                    // Tampilkan notifikasi Toastr
                    toastr.options = {
                        "closeButton": true,
                        "progressBar": true,
                        "positionClass": "toast-top-right",
                    };
                    // 'e.message' dan 'e.title' harus sesuai dengan
                    // properti public yang Anda definisikan di class Event
                    toastr.info(e.message, e.title || 'Notifikasi Baru');

                    // Muat ulang tabel untuk menampilkan data baru
                    table.ajax.reload(null, false); // 'false' agar tidak reset paging
                });

            */ // Akhir blok Echo
            
            
            // Konfigurasi Toastr (opsional, agar muncul di kanan atas)
            toastr.options = {
                "positionClass": "toast-top-right",
                "progressBar": true,
            };

        });
    </script>
@endpush

