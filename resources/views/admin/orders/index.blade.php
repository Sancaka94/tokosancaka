{{-- Halaman ini adalah view untuk 'AdminOrderController@index' --}}
{{-- Menggunakan layout admin Bootstrap 5 --}}
@extends('layouts.admin') {{-- Pastikan nama layout ini benar --}}

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
            color: #6c757d; /* Warna teks abu-abu */
        }
        .nav-tabs .nav-link.active {
            border-bottom-color: var(--bs-primary, #0d6efd); /* Warna border biru primary */
            color: var(--bs-primary, #0d6efd); /* Warna teks biru primary */
            font-weight: bold;
        }
        /* Style agar tombol aksi tidak 'wrap' jika tidak perlu */
        #orders-table .d-flex {
            flex-wrap: nowrap; /* Mencegah tombol turun baris */
            gap: 0.25rem !important; /* Jarak antar tombol */
        }
        /* Atur lebar kolom agar lebih rapi (sesuaikan jika perlu) */
        #orders-table th:nth-child(1) { width: 5%; } /* No */
        #orders-table th:nth-child(2) { width: 15%; } /* Transaksi */
        #orders-table th:nth-child(3) { width: 25%; } /* Alamat */
        #orders-table th:nth-child(4) { width: 15%; } /* Ekspedisi */
        #orders-table th:nth-child(5) { width: 15%; } /* Isi Paket */
        #orders-table th:nth-child(6) { width: 10%; } /* Status */
        #orders-table th:nth-child(7) { width: 15%; } /* Aksi */
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
                    {{-- Input untuk memasukkan query pencarian --}}
                    <input type="text" id="search-query" class="form-control bg-light border-0 small" placeholder="Cari Resi, Invoice, Nama, atau No. HP..." aria-label="Search" aria-describedby="basic-addon2">
                    {{-- Tombol submit pencarian --}}
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search fa-sm"></i> {{-- Ikon pencarian Font Awesome --}}
                    </button>
                </div>
            </form>

            <!-- Tombol Aksi Kanan Atas -->
            <div class="d-flex gap-2">
                <!-- Tombol Trigger Modal Export PDF -->
                {{-- Tombol ini akan membuka popup (modal) untuk memilih rentang tanggal export --}}
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="fas fa-download fa-sm text-white-50"></i> Export Laporan
                </button>
                
                <!-- Tombol Tambah Pesanan (jika diperlukan) -->
                {{-- Uncomment jika Anda punya route 'admin.orders.create' --}}
                {{-- <a href="{{ route('admin.orders.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Pesanan
                </a> --}}
            </div>
        </div>

        <div class="card-body">
            
            <!-- Tab Filter Status -->
            {{-- Tab ini digunakan untuk memfilter data pesanan berdasarkan status --}}
            <ul class="nav nav-tabs mb-3" id="status-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    {{-- Tab 'Semua', data-status kosong akan mengambil semua data --}}
                    <button class="nav-link active" data-status="" type="button" role="tab">Semua</button>
                </li>
                <li class="nav-item" role="presentation">
                    {{-- Tab 'Menunggu Bayar', data-status="pending" --}}
                    <button class="nav-link" data-status="pending" type="button" role="tab">Menunggu Bayar</button>
                </li>
                <li class="nav-item" role="presentation">
                    {{-- Tab 'Menunggu Pickup', data-status="processing" --}}
                    {{-- !! PENTING: Sesuaikan 'data-status' ini jika status di DB Anda berbeda (misal: 'paid', 'siap-pickup') --}}
                    <button class="nav-link" data-status="processing" type="button" role="tab">Menunggu Pickup</button>
                </li>
                <li class="nav-item" role="presentation">
                    {{-- Tab 'Diproses', data-status="shipping" --}}
                    {{-- !! PENTING: Sesuaikan 'data-status' ini jika status di DB Anda berbeda (misal: 'dikirim', 'on_delivery') --}}
                    <button class="nav-link" data-status="shipping" type="button" role="tab">Diproses</button>
                </li>
                <li class="nav-item" role="presentation">
                    {{-- Tab 'Terkirim', data-status="delivered" --}}
                    {{-- !! PENTING: Sesuaikan 'data-status' ini jika status di DB Anda berbeda (misal: 'selesai', 'completed') --}}
                    <button class="nav-link" data-status="delivered" type="button" role="tab">Terkirim</button>
                </li>
                 <li class="nav-item" role="presentation">
                    {{-- Tab 'Selesai', data-status="completed" --}}
                    {{-- !! PENTING: Sesuaikan 'data-status' ini jika status di DB Anda berbeda (misal: 'selesai', 'completed') --}}
                    <button class="nav-link" data-status="completed" type="button" role="tab">Selesai</button>
                </li>
                <li class="nav-item" role="presentation">
                    {{-- Tab 'Batal', data-status="cancelled" --}}
                    {{-- !! PENTING: Sesuaikan 'data-status' ini jika status di DB Anda berbeda (misal: 'batal', 'failed') --}}
                    <button class="nav-link" data-status="cancelled" type="button" role="tab">Batal</button>
                </li>
            </ul>

            <!-- Tabel Data Pesanan -->
            {{-- Tabel ini akan diisi secara dinamis oleh JavaScript menggunakan DataTables --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="orders-table" width="100%" cellspacing="0">
                    <thead>
                        {{-- Header tabel, harus cocok dengan kolom di controller --}}
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
                        {{-- Data tabel akan dimuat di sini oleh DataTables --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Export Laporan PDF -->
{{-- Popup yang muncul saat tombol 'Export Laporan' diklik --}}
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Export Laporan Penjualan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- Form ini akan mengirim request GET ke route 'admin.orders.report.pdf' saat disubmit --}}
            <form action="{{ route('admin.orders.report.pdf') }}" method="GET" target="_blank"> {{-- target="_blank" agar PDF terbuka di tab baru --}}
                <div class="modal-body">
                    {{-- Input tanggal mulai --}}
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Tanggal Mulai</label>
                        {{-- Default value: awal bulan ini --}}
                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}" required>
                    </div>
                    {{-- Input tanggal selesai --}}
                    <div class="mb-3">
                        <label for="end_date" class="form-label">Tanggal Selesai</label>
                        {{-- Default value: akhir bulan ini --}}
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
    <!-- jQuery (Pastikan sudah ada di layout utama Anda) -->
    <!-- <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script> -->
    
    <!-- JavaScript DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- JavaScript Toastr (untuk notifikasi) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <!-- JavaScript Laravel Echo (Jika Anda menggunakan real-time) -->
    {{-- Pastikan Echo sudah di-setup di resources/js/bootstrap.js --}}
    {{-- Jika Anda mengompilasi JS (npm run dev), uncomment baris di bawah --}}
    {{-- <script src="{{ mix('js/app.js') }}"></script> --}} 

    <script>
        // Pastikan semua elemen DOM siap sebelum menjalankan script
        $(document).ready(function() {
            
            // 1. Inisialisasi DataTables
            // Simpan instance DataTables ke variabel 'table' agar bisa diakses nanti
            var table = $('#orders-table').DataTable({
                processing: true, // Tampilkan pesan "Memuat..." saat AJAX request
                serverSide: true, // Proses sorting, searching, paging di sisi server (controller)
                responsive: true, // Buat tabel responsif di layar kecil
                order: [], // Kosongkan order default agar mengikuti order dari controller
                ajax: {
                    url: "{{ route('admin.orders.data') }}", // URL endpoint API DataTables
                    type: "GET", // Metode HTTP
                    // Fungsi untuk mengirim data tambahan (filter) ke server
                    data: function(d) {
                        // Ambil nilai 'data-status' dari tab yang sedang aktif
                        d.status = $('#status-tabs .nav-link.active').data('status');
                        // Ambil nilai dari input pencarian
                        d.search_query = $('#search-query').val(); 
                        return d; // Kirim objek data ini ke controller
                    }
                },
                // Definisikan kolom tabel dan data source-nya
                columns: [
                    // Kolom nomor urut (otomatis dari DataTables)
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false }, 
                    // Kolom data dari response JSON controller (nama 'data' harus cocok)
                    { data: 'transaksi', name: 'transaksi', orderable: false, searchable: false }, // Tidak bisa di-sort/cari dari client
                    { data: 'alamat', name: 'alamat', orderable: false, searchable: false },
                    { data: 'ekspedisi', name: 'ekspedisi', orderable: false, searchable: false },
                    { data: 'isi_paket', name: 'isi_paket', orderable: false, searchable: false },
                    { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false } // Kolom tombol aksi
                ],
                 // Pengaturan bahasa (opsional)
                 language: {
                    processing: "Memuat data...",
                    search: "_INPUT_", // Ganti label default search
                    searchPlaceholder: "Cari...", // Tambahkan placeholder
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
                }
            });

            // 2. Event Listener untuk Tab Filter Status
            // Ketika tombol nav-link di dalam #status-tabs diklik
            $('#status-tabs .nav-link').on('click', function (e) {
                e.preventDefault(); // Mencegah pindah halaman jika link adalah <a>
                
                // Jangan lakukan apa-apa jika tab yang diklik sudah aktif
                if ($(this).hasClass('active')) {
                    return;
                }

                // Hapus kelas 'active' dari semua tombol tab
                $('#status-tabs .nav-link').removeClass('active');
                // Tambahkan kelas 'active' ke tombol tab yang baru saja diklik
                $(this).addClass('active');
                
                // Muat ulang data DataTables
                // Fungsi 'ajax.data' akan otomatis terpanggil lagi dan mengirim
                // nilai 'data-status' yang baru dari tab yang aktif
                table.ajax.reload();
            });

            // 3. Event Listener untuk Form Pencarian Custom
            // Ketika form #search-form disubmit (baik dengan Enter atau klik tombol)
            $('#search-form').on('submit', function(e) {
                e.preventDefault(); // Mencegah halaman reload
                
                // Muat ulang data DataTables
                // Fungsi 'ajax.data' akan otomatis terpanggil lagi dan mengirim
                // nilai 'search_query' yang baru dari input #search-query
                table.ajax.reload();
            });


            // 4. Inisialisasi Laravel Echo (Real-time Notification)
            // !! PENTING: Pastikan Anda sudah setup Laravel Echo di backend
            // (misal: install Pusher/Reverb, setup .env, uncomment BroadcastServiceProvider)
            // dan di frontend (install 'laravel-echo' & 'pusher-js', setup di resources/js/bootstrap.js)
            // Jalankan `npm install && npm run dev` setelah setup frontend.
            
            // Cek apakah variabel 'Echo' tersedia (di-load dari file JS utama Anda, misal app.js)
            if (typeof Echo !== 'undefined') {
                console.log('Laravel Echo siap, mendengarkan notifikasi...'); // Pesan konfirmasi di console browser
                
                // Mulai mendengarkan di channel 'admin-notifications'
                // Ganti 'admin-notifications' jika Anda menggunakan nama channel berbeda di Event Anda
                Echo.channel('admin-notifications') 
                    // Dengarkan event 'AdminNotificationEvent'
                    // Ganti 'AdminNotificationEvent' jika nama class Event Anda berbeda (nama class saja, tanpa namespace)
                    .listen('AdminNotificationEvent', (e) => {
                        // 'e' adalah objek data yang dikirim bersama event dari backend
                        console.log('Notifikasi diterima:', e); // Tampilkan data event di console untuk debugging
                        
                        // Konfigurasi tampilan notifikasi Toastr
                        toastr.options = { 
                            "closeButton": true,       // Tampilkan tombol close
                            "progressBar": true,       // Tampilkan progress bar
                            "positionClass": "toast-top-right", // Posisi di kanan atas
                            "timeOut": "5000",         // Durasi tampil (5 detik)
                        };
                        
                        // Tampilkan notifikasi Toastr
                        // Ambil 'message' dan 'title' dari data event 'e'
                        // Pastikan Event Anda memiliki public property $message dan $title
                        // Beri nilai default jika properti tidak ada
                        toastr.info(e.message || 'Ada data pesanan baru!', e.title || 'Notifikasi Pesanan');

                        // Muat ulang tabel DataTables untuk menampilkan data terbaru
                        // Parameter kedua 'false' mencegah reset ke halaman pertama
                        table.ajax.reload(null, false); 
                    });

            } else {
                // Beri peringatan jika Echo tidak terdefinisi
                console.warn('Laravel Echo tidak terdefinisi. Fitur notifikasi real-time tidak akan aktif.');
            }
            
            // Akhir blok Echo
            
            
            // Konfigurasi default untuk Toastr (opsional, bisa diatur di sini atau sebelum Echo)
            toastr.options = {
                "positionClass": "toast-top-right",
                "progressBar": true,
                "timeOut": "3000", // Notifikasi standar hilang setelah 3 detik
            };

        });
    </script>
@endpush

