@extends('layouts.app')

@section('title', 'Manajemen Order Obat RSUD')

@push('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    .action-cell { white-space: nowrap; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-pills me-2"></i> Data Booking Obat Pasien</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle w-100" id="rsudTable">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>Kode Booking</th>
                                    <th>Tanggal</th>
                                    <th>Pasien (RM)</th>
                                    <th>Metode Pembayaran</th>
                                    <th>Status Bayar</th>
                                    <th>Status Apotek</th>
                                    <th>Resi Ekspedisi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach($orders as $order)
                                <tr>
                                    <td class="fw-bold text-primary text-center">{{ $order->kode_booking }}</td>
                                    <td class="text-center">{{ $order->created_at->format('d M Y H:i') }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $order->receiver_name }}</div>
                                        <small class="text-muted"><i class="fas fa-id-card me-1"></i> {{ $order->nomor_rm ?? 'Tanpa RM' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ strtoupper($order->payment_method) }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($order->payment_status == 'Lunas' || $order->payment_status == 'Lunas / COD')
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Lunas</span>
                                        @else
                                            <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Belum Lunas</span>
                                        @endif
                                    </td>

                                    <td class="status-cell text-center">
                                        @if($order->status_racik == 'Menunggu Diramu')
                                            <span class="badge bg-danger">Menunggu Diramu</span>
                                        @elseif($order->status_racik == 'Selesai Diramu')
                                            <span class="badge bg-info text-dark">Selesai Diramu</span>
                                        @else
                                            <span class="badge bg-success">{{ $order->status_racik }}</span>
                                        @endif
                                    </td>

                                    <td class="resi-cell text-center">
                                        @if($order->resi)
                                            <span class="fw-bold text-success"><i class="fas fa-box me-1"></i> {{ $order->resi }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td class="text-center action-cell" data-kode="{{ $order->kode_booking }}">
                                        @if($order->status_racik == 'Menunggu Diramu')
                                            <button class="btn btn-sm btn-info text-white btn-racik" data-kode="{{ $order->kode_booking }}">
                                                <i class="fas fa-mortar-pestle me-1"></i> Selesai Diracik
                                            </button>
                                        @elseif($order->status_racik == 'Selesai Diramu' && empty($order->resi))
                                            @if($order->payment_status == 'Lunas' || $order->payment_status == 'Lunas / COD')
                                                <button class="btn btn-sm btn-success btn-payload" data-kode="{{ $order->kode_booking }}">
                                                    <i class="fas fa-truck-fast me-1"></i> Panggil Kurir
                                                </button>
                                            @else
                                                <span class="text-danger small fw-bold"><i class="fas fa-exclamation-circle me-1"></i> Menunggu Pelunasan</span>
                                            @endif
                                        @elseif(!empty($order->resi))
                                            <button class="btn btn-sm btn-secondary" disabled>
                                                <i class="fas fa-check-double me-1"></i> Selesai Diproses
                                            </button>
                                        @endif
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
    $(document).ready(function() {
        // Setup CSRF Token
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        // Inisialisasi DataTables dengan Bahasa Indonesia agar lebih ramah user
        $('#rsudTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json',
            }
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
                            row.find('.status-cell').html('<span class="badge bg-info text-dark">Selesai Diramu</span>');
                            row.find('.action-cell').html(`<button class="btn btn-sm btn-success btn-payload" data-kode="${kodeBooking}"><i class="fas fa-truck-fast me-1"></i> Panggil Kurir</button>`);
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
                                // KODENYA ADA DI SINI BRAY!
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then((result) => {
                                    if (result.isConfirmed || result.isDismissed) {
                                        location.reload(); // <--- INI YANG MEMBUAT HALAMAN REFRESH!
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