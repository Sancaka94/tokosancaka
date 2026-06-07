@extends('layouts.app')

@section('title', 'Manajemen Order Obat RSUD')

@push('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-pills me-2"></i> Data Booking Obat Pasien</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle" id="rsudTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode Booking</th>
                                    <th>Tanggal</th>
                                    <th>Pasien (RM)</th>
                                    <th>Metode Pembayaran</th>
                                    <th>Status Bayar</th>
                                    <th>Status Apotek</th>
                                    <th>Resi Ekspedisi</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                               @foreach($orders as $order)
                                <tr>
                                    <td class="fw-bold text-primary">{{ $order->kode_booking }}</td>
                                    <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $order->receiver_name }}</div>
                                        <small class="text-muted">{{ $order->nomor_rm ?? 'Tanpa RM' }}</small>
                                    </td>
                                    <td>{{ strtoupper($order->payment_method) }}</td>
                                    <td>
                                        @if($order->payment_status == 'Lunas' || $order->payment_status == 'Lunas / COD')
                                            <span class="badge bg-success"><i class="fas fa-check-circle"></i> Lunas</span>
                                        @else
                                            <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Belum Lunas</span>
                                        @endif
                                    </td>

                                    <td class="status-cell">
                                        @if($order->status_racik == 'Menunggu Diramu')
                                            <span class="badge bg-danger">Menunggu Diramu</span>
                                        @elseif($order->status_racik == 'Selesai Diramu')
                                            <span class="badge bg-info">Selesai Diramu</span>
                                        @else
                                            <span class="badge bg-success">{{ $order->status_racik }}</span>
                                        @endif
                                    </td>

                                    <td class="resi-cell">
                                        @if($order->resi)
                                            <span class="fw-bold text-success">{{ $order->resi }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td class="text-center action-cell" data-kode="{{ $order->kode_booking }}">
                                        @if($order->status_racik == 'Menunggu Diramu')
                                            <button class="btn btn-sm btn-info text-white btn-racik" data-kode="{{ $order->kode_booking }}">
                                                <i class="fas fa-mortar-pestle"></i> Selesai Diracik
                                            </button>
                                        @elseif($order->status_racik == 'Selesai Diramu' && empty($order->resi))
                                            @if($order->payment_status == 'Lunas' || $order->payment_status == 'Lunas / COD')
                                                <button class="btn btn-sm btn-success btn-payload" data-kode="{{ $order->kode_booking }}">
                                                    <i class="fas fa-truck-fast"></i> Panggil Kurir
                                                </button>
                                            @else
                                                <span class="text-danger small fw-bold">Menunggu Pelunasan</span>
                                            @endif
                                        @elseif(!empty($order->resi))
                                            <button class="btn btn-sm btn-secondary" disabled>
                                                <i class="fas fa-check"></i> Selesai diproses
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
        $('#rsudTable').DataTable();
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        $(document).on('click', '.btn-racik', function() {
            let kodeBooking = $(this).data('kode');
            let btn = $(this);
            let row = btn.closest('tr');

            Swal.fire({
                title: 'Konfirmasi Obat',
                text: "Apakah obat untuk kode " + kodeBooking + " sudah siap?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Sudah Siap!'
            }).then((result) => {
                if (result.isConfirmed) {
                    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                    $.ajax({
                        url: "{{ route('admin.rsud.update_racik') }}",
                        type: "POST",
                        data: { kode_booking: kodeBooking },
                        success: function(response) {
                            Swal.fire('Berhasil!', response.message, 'success');
                            row.find('.status-cell').html('<span class="badge bg-info">Selesai Diramu</span>');
                            row.find('.action-cell').html(`<button class="btn btn-sm btn-success btn-payload" data-kode="${kodeBooking}"><i class="fas fa-truck-fast"></i> Panggil Kurir</button>`);
                        },
                        error: function() {
                            Swal.fire('Error!', 'Gagal update status.', 'error');
                            btn.prop('disabled', false).html('<i class="fas fa-mortar-pestle"></i> Selesai Diracik');
                        }
                    });
                }
            });
        });

        $(document).on('click', '.btn-payload', function() {
            let kodeBooking = $(this).data('kode');
            let btn = $(this);
            let row = btn.closest('tr');

            Swal.fire({
                title: 'Panggil Ekspedisi?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Kirim Sekarang!'
            }).then((result) => {
                if (result.isConfirmed) {
                    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                    $.ajax({
                        url: "{{ route('admin.rsud.payload_kiriminaja') }}",
                        type: "POST",
                        data: { kode_booking: kodeBooking },
                        success: function(response) {
                            if(response.success) {
                                Swal.fire('Berhasil!', 'Resi: ' + response.resi, 'success');
                                row.find('.resi-cell').html(`<span class="fw-bold text-success">${response.resi}</span>`);
                                row.find('.status-cell').html('<span class="badge bg-success">Diserahkan ke Kurir</span>');
                                row.find('.action-cell').html('<button class="btn btn-sm btn-secondary" disabled><i class="fas fa-check"></i> Selesai</button>');
                            } else {
                                Swal.fire('Gagal!', response.message, 'error');
                                btn.prop('disabled', false).html('<i class="fas fa-truck-fast"></i> Panggil Kurir');
                            }
                        },
                        
                        error: function(xhr) {
                            Swal.fire('Error!', xhr.responseJSON.message, 'error');
                            btn.prop('disabled', false).html('<i class="fas fa-truck-fast"></i> Panggil Kurir');
                        }
                    });
                }
            });
        });
    });
</script>
@endpush