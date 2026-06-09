{{--
    File: resources/views/admin/rsud/index.blade.php
    Deskripsi: Manajemen Order Obat RSUD (Bootstrap 5 + Read More Mobile + Stat Cards)
--}}

@extends('layouts.app')

@section('title', 'Manajemen Order Obat RSUD')

@push('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    /* === DESKTOP VIEW & STICKY COLUMN === */
    @media (min-width: 768px) {
        .table-responsive {
            overflow-x: auto;
        }
        th.sticky-col, td.sticky-col {
            position: -webkit-sticky;
            position: sticky;
            right: 0;
            background-color: #fff;
            z-index: 10;
            border-left: 1px solid #dee2e6;
            box-shadow: -4px 0 6px -1px rgba(0,0,0,0.05);
        }
        thead th.sticky-col {
            background-color: #f8f9fa;
            z-index: 20;
        }
        tr:hover td.sticky-col {
            background-color: #f8f9fa;
        }
    }

    /* === MOBILE VIEW (KARTU & READ MORE) === */
    @media (max-width: 767px) {
        /* Paksa DataTables menjadi Block Layout */
        #rsudTable, #rsudTable thead, #rsudTable tbody, #rsudTable th, #rsudTable td, #rsudTable tr {
            display: block;
            width: 100%;
        }
        /* Sembunyikan Header Tabel Asli */
        #rsudTable thead tr {
            position: absolute;
            top: -9999px;
            left: -9999px;
        }
        /* Styling Kartu per Baris */
        #rsudTable tr {
            margin-bottom: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.75rem;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        /* Styling Isi Kartu */
        #rsudTable td {
            border: none;
            border-bottom: 1px solid #f8f9fa;
            position: relative;
            padding: 0.75rem 1rem !important;
            text-align: left !important; /* Paksa rata kiri di mobile */
        }
        #rsudTable td:last-child {
            border-bottom: none;
        }

        /* Label Judul Kolom untuk Mobile */
        .mobile-label {
            display: block;
            font-size: 0.75rem;
            font-weight: bold;
            color: #6c757d;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
        }

        /* Utility Class untuk Read More */
        .d-md-table-cell {
            display: none !important; /* Sembunyikan default di mobile */
        }
        .d-md-table-cell.show-mobile {
            display: block !important;
            animation: fadeIn 0.4s ease-in-out;
        }
    }

    /* === ANIMASI & STAT CARDS === */
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

    .stat-card {
        transition: all 0.3s ease;
        overflow: hidden;
        position: relative;
        border: none;
        border-radius: 0.75rem;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
    .stat-icon {
        position: absolute;
        right: -10px;
        top: -10px;
        font-size: 6rem;
        opacity: 0.15;
        transform: rotate(15deg);
    }
    .action-cell { white-space: nowrap; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    {{-- === 1. CARD MONITOR (STATISTIK) === --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card stat-card bg-primary text-white shadow-sm h-100 p-3">
                <div class="position-relative z-1">
                    <h3 class="fw-bold mb-0">{{ number_format($countMenunggu ?? 0, 0, ',', '.') }} <span class="fs-6 fw-normal">Antrean</span></h3>
                    <p class="text-uppercase fw-bold opacity-75 mb-0 small mt-1">Menunggu Diramu</p>
                    <p class="small opacity-50 mb-0">Resep masuk baru</p>
                </div>
                <i class="fas fa-file-prescription stat-icon text-white"></i>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card stat-card bg-info text-white shadow-sm h-100 p-3">
                <div class="position-relative z-1">
                    <h3 class="fw-bold mb-0">{{ number_format($countSelesaiRamuan ?? 0, 0, ',', '.') }} <span class="fs-6 fw-normal">Paket</span></h3>
                    <p class="text-uppercase fw-bold opacity-75 mb-0 small mt-1">Selesai Diramu</p>
                    <p class="small opacity-50 mb-0">Obat siap di-pickup</p>
                </div>
                <i class="fas fa-mortar-pestle stat-icon text-white"></i>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card stat-card bg-warning text-dark shadow-sm h-100 p-3">
                <div class="position-relative z-1">
                    <h3 class="fw-bold mb-0">{{ number_format($countMenungguBayar ?? 0, 0, ',', '.') }} <span class="fs-6 fw-normal">Tagihan</span></h3>
                    <p class="text-uppercase fw-bold opacity-75 mb-0 small mt-1">Belum Lunas</p>
                    <p class="small opacity-50 mb-0">Menunggu pembayaran</p>
                </div>
                <i class="fas fa-wallet stat-icon text-dark"></i>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card stat-card bg-success text-white shadow-sm h-100 p-3">
                <div class="position-relative z-1">
                    <h3 class="fw-bold mb-0">{{ number_format($countDikirim ?? 0, 0, ',', '.') }} <span class="fs-6 fw-normal">Resi</span></h3>
                    <p class="text-uppercase fw-bold opacity-75 mb-0 small mt-1">Sedang Dikirim</p>
                    <p class="small opacity-50 mb-0">Diserahkan ke kurir</p>
                </div>
                <i class="fas fa-truck-fast stat-icon text-white"></i>
            </div>
        </div>
    </div>

    {{-- === 2. TABEL DATA (RESPONSIVE + READ MORE) === --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-pills me-2"></i> Data Booking Obat Pasien</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle w-100" id="rsudTable">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>Kode Booking</th>
                                    <th>Tanggal</th>
                                    <th>Pasien (RM)</th>
                                    <th>Metode Pembayaran</th>
                                    <th>Status Bayar</th>
                                    <th>Status Apotek</th>
                                    <th>Resi Ekspedisi</th>
                                    <th class="sticky-col">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $index => $order)
                                <tr>
                                    {{-- 1. KODE BOOKING (Trigger Read More di Mobile) --}}
                                    <td class="text-center text-md-center">
                                        <span class="mobile-label d-md-none">KODE BOOKING & TANGGAL</span>
                                        <span class="fw-bold text-primary fs-5 fs-md-6">{{ $order->kode_booking }}</span>
                                        <div class="d-md-none mt-1 text-muted small"><i class="far fa-clock me-1"></i> {{ $order->created_at->format('d M Y H:i') }}</div>

                                        {{-- Tombol Trigger Read More (Hanya muncul di Mobile) --}}
                                        <button type="button" class="btn btn-light btn-sm w-100 mt-3 d-md-none fw-bold text-secondary border shadow-sm" onclick="toggleDetails({{ $index }}, this)">
                                            <span>Lihat Detail Lengkap</span> <i class="fas fa-chevron-down ms-1"></i>
                                        </button>
                                    </td>

                                    {{-- 2. TANGGAL --}}
                                    <td class="text-center d-none d-md-table-cell toggle-target-{{ $index }}">
                                        {{ $order->created_at->format('d M Y H:i') }}
                                    </td>

                                    {{-- 3. PASIEN (RM) --}}
                                    <td class="d-none d-md-table-cell toggle-target-{{ $index }}">
                                        <span class="mobile-label d-md-none border-bottom pb-1 mb-2">👤 Data Pasien</span>
                                        <div class="fw-bold text-dark">{{ $order->receiver_name }}</div>
                                        <small class="text-muted"><i class="fas fa-id-card me-1 text-primary"></i> {{ $order->nomor_rm ?? 'Tanpa RM' }}</small>
                                    </td>

                                    {{-- 4. METODE PEMBAYARAN --}}
                                    <td class="text-center d-none d-md-table-cell toggle-target-{{ $index }}">
                                        <span class="mobile-label d-md-none border-bottom pb-1 mb-2">💳 Metode Pembayaran</span>
                                        <span class="badge bg-secondary px-3 py-2">{{ strtoupper($order->payment_method) }}</span>
                                    </td>

                                    {{-- 5. STATUS BAYAR --}}
                                    <td class="text-center d-none d-md-table-cell toggle-target-{{ $index }}">
                                        <span class="mobile-label d-md-none border-bottom pb-1 mb-2">💰 Status Bayar</span>
                                        @if($order->payment_status == 'Lunas' || $order->payment_status == 'Lunas / COD')
                                            <span class="badge bg-success px-3 py-2"><i class="fas fa-check-circle me-1"></i> Lunas</span>
                                        @else
                                            <span class="badge bg-warning text-dark px-3 py-2"><i class="fas fa-clock me-1"></i> Belum Lunas</span>
                                        @endif
                                    </td>

                                    {{-- 6. STATUS APOTEK --}}
                                    <td class="status-cell text-center d-none d-md-table-cell toggle-target-{{ $index }}">
                                        <span class="mobile-label d-md-none border-bottom pb-1 mb-2">💊 Status Racikan</span>
                                        @if($order->status_racik == 'Menunggu Diramu')
                                            <span class="badge bg-danger px-3 py-2">Menunggu Diramu</span>
                                        @elseif($order->status_racik == 'Selesai Diramu')
                                            <span class="badge bg-info text-dark px-3 py-2">Selesai Diramu</span>
                                        @else
                                            <span class="badge bg-success px-3 py-2">{{ $order->status_racik }}</span>
                                        @endif
                                    </td>

                                    {{-- 7. RESI EKSPEDISI --}}
                                    <td class="resi-cell text-center d-none d-md-table-cell toggle-target-{{ $index }}">
                                        <span class="mobile-label d-md-none border-bottom pb-1 mb-2">📦 Resi Pengiriman</span>
                                        @if($order->resi)
                                            <div class="bg-light border border-success text-success fw-bold p-2 rounded d-inline-block">
                                                <i class="fas fa-box me-1"></i> {{ $order->resi }}
                                            </div>
                                        @else
                                            <span class="text-muted fst-italic">Menunggu Resi</span>
                                        @endif
                                    </td>

                                   <td class="text-center action-cell sticky-col d-none d-md-table-cell toggle-target-{{ $index }}" data-kode="{{ $order->kode_booking }}">
                                        <span class="mobile-label d-md-none border-bottom pb-1 mb-3 text-center">⚙️ Tindakan / Aksi</span>

                                        <div class="d-flex flex-column align-items-center w-100 gap-2"> <div class="w-100">
                                                @if($order->status_racik == 'Menunggu Diramu')
                                                    <button class="btn btn-sm btn-info text-white btn-racik shadow-sm w-100 w-md-auto" data-kode="{{ $order->kode_booking }}">
                                                        <i class="fas fa-mortar-pestle me-1"></i> Selesai Diracik
                                                    </button>
                                                @elseif($order->status_racik == 'Selesai Diramu' && empty($order->resi))
                                                    @if($order->payment_status == 'Lunas' || $order->payment_status == 'Lunas / COD')
                                                        <button class="btn btn-sm btn-success btn-payload shadow-sm w-100 w-md-auto" data-kode="{{ $order->kode_booking }}">
                                                            <i class="fas fa-truck-fast me-1"></i> Panggil Kurir
                                                        </button>
                                                    @else
                                                        <div class="bg-danger text-white rounded px-2 py-1 small fw-bold shadow-sm">
                                                            <i class="fas fa-exclamation-triangle me-1"></i> Menunggu Pelunasan
                                                        </div>
                                                    @endif
                                                @elseif(!empty($order->resi))
                                                    <button class="btn btn-sm btn-secondary w-100 w-md-auto" disabled>
                                                        <i class="fas fa-check-double me-1"></i> Selesai Diproses
                                                    </button>
                                                @endif
                                            </div>

                                            <div class="d-flex justify-content-center w-100 gap-2 pt-2 border-top border-light">
                                                <a href="{{ route('admin.rsud.show', $order->kode_booking) }}" class="btn btn-sm btn-outline-primary shadow-sm px-3" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                @if(!empty($order->resi))
                                                    <a href="https://tokosancaka.com/tracking/search?resi={{ $order->resi }}" target="_blank" class="btn btn-sm btn-outline-success shadow-sm px-3" title="Lacak Resi">
                                                        <i class="fas fa-truck"></i>
                                                    </a>
                                                @endif

                                                <form action="{{ route('admin.rsud.destroy', $order->kode_booking) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pesanan ini secara permanen?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger shadow-sm px-3" title="Hapus">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>

                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // === FUNGSI READ MORE MOBILE ===
    function toggleDetails(index, btn) {
        // Cari semua td dengan class toggle-target sesuai index barisnya
        const targets = document.querySelectorAll('.toggle-target-' + index);
        const icon = btn.querySelector('i');
        const textSpan = btn.querySelector('span');

        let isHidden = true;

        targets.forEach(target => {
            if (target.classList.contains('d-none')) {
                // Buka Kartu
                target.classList.remove('d-none');
                target.classList.add('show-mobile');
                isHidden = false;
            } else {
                // Tutup Kartu
                target.classList.add('d-none');
                target.classList.remove('show-mobile');
                isHidden = true;
            }
        });

        // Animasi Tombol
        if (!isHidden) {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
            textSpan.innerText = "Tutup Detail";
            btn.classList.replace('btn-light', 'btn-primary');
            btn.classList.replace('text-secondary', 'text-white');
        } else {
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
            textSpan.innerText = "Lihat Detail Lengkap";
            btn.classList.replace('btn-primary', 'btn-light');
            btn.classList.replace('text-white', 'text-secondary');
        }
    }

    // === SCRIPT ASLI (TIDAK ADA YANG DIHAPUS) ===
    $(document).ready(function() {
        // Setup CSRF Token
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        // Inisialisasi DataTables
        $('#rsudTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json',
            },
            ordering: false // Dinonaktifkan sementara agar index mobile toggler tidak bentrok saat di-sort
        });

        // Event handler untuk tombol Selesai Diracik
        $(document).on('click', '.btn-racik', function() {
            let btn = $(this);
            let kodeBooking = btn.data('kode');
            let row = btn.closest('tr');

            Swal.fire({
                title: 'Konfirmasi Obat',
                text: "Apakah obat untuk kode " + kodeBooking + " sudah siap?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '<i class="fas fa-check"></i> Ya, Sudah Siap!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    let originalHtml = btn.html();
                    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Proses...');

                    $.ajax({
                        url: "{{ route('admin.rsud.update_racik') }}",
                        type: "POST",
                        data: { kode_booking: kodeBooking },
                        success: function(response) {
                            Swal.fire('Berhasil!', response.message, 'success');
                            row.find('.status-cell').html('<span class="badge bg-info text-dark px-3 py-2">Selesai Diramu</span>');
                            row.find('.action-cell').html(`<div class="d-flex justify-content-center w-100"><button class="btn btn-sm btn-success btn-payload shadow-sm w-100 w-md-auto" data-kode="${kodeBooking}"><i class="fas fa-truck-fast me-1"></i> Panggil Kurir</button></div>`);
                        },
                        error: function(xhr) {
                            let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Gagal update status.';
                            Swal.fire('Error!', msg, 'error');
                            btn.prop('disabled', false).html(originalHtml);
                        }
                    });
                }
            });
        });

        // Event handler untuk tombol Panggil Kurir
        $(document).on('click', '.btn-payload', function() {
            let btn = $(this);
            let kodeBooking = btn.data('kode');

            Swal.fire({
                title: 'Panggil Ekspedisi?',
                text: "Sistem akan panggil ekspedisi.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: '<i class="fas fa-paper-plane"></i> Ya, Kirim Sekarang!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    let originalHtml = btn.html();
                    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Mohon Tunggu...');

                    $.ajax({
                        url: "{{ route('admin.rsud.payload_kiriminaja') }}",
                        type: "POST",
                        data: { kode_booking: kodeBooking },
                        success: function(response) {
                            if(response.success) {
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then((result) => {
                                    if (result.isConfirmed || result.isDismissed) {
                                        location.reload();
                                    }
                                });
                            } else {
                                Swal.fire('Payload Gagal!', response.message, 'error');
                                btn.prop('disabled', false).html(originalHtml);
                            }
                        },
                        error: function(xhr) {
                            let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.';
                            Swal.fire('Error!', msg, 'error');
                            btn.prop('disabled', false).html(originalHtml);
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
