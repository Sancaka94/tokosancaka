@extends('layouts.app')

@section('content')
<div class="container mt-4 mb-5">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-3 flex-column flex-md-row d-flex justify-content-between align-items-center">
            <h4 class="mb-0 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i>Riwayat Transaksi PPOB</h4>
            <a href="{{ route('ppob.index') }}" class="btn btn-outline-primary btn-sm mt-3 mt-md-0">
                <i class="bi bi-plus-circle me-1"></i>Transaksi Baru
            </a>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 px-4 border-bottom-0 text-secondary text-uppercase" style="font-size: 13px;">Tanggal</th>
                            <th class="py-3 border-bottom-0 text-secondary text-uppercase" style="font-size: 13px;">Ref ID / Tujuan</th>
                            <th class="py-3 border-bottom-0 text-secondary text-uppercase" style="font-size: 13px;">Produk</th>
                            <th class="py-3 border-bottom-0 text-secondary text-uppercase" style="font-size: 13px;">Harga</th>
                            <th class="py-3 border-bottom-0 text-secondary text-uppercase" style="font-size: 13px;">Status</th>
                            <th class="py-3 border-bottom-0 text-secondary text-uppercase" style="font-size: 13px;">SN / Token</th>
                            <th class="py-3 px-4 border-bottom-0 text-end text-secondary text-uppercase" style="font-size: 13px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $trx)
                        <tr>
                            <td class="px-4">
                                <div class="fw-semibold text-dark">{{ $trx->created_at->format('d M Y') }}</div>
                                <div class="text-muted" style="font-size: 12px;">{{ $trx->created_at->format('H:i') }} WIB</div>
                            </td>
                            <td>
                                <div class="text-muted" style="font-size: 12px;">{{ $trx->ref_id }}</div>
                                <div class="fw-bold text-dark">{{ $trx->customer_id }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border border-secondary border-opacity-25">{{ strtoupper($trx->type) }}</span>
                                <div class="mt-1" style="font-size: 13px;">{{ $trx->product_code }}</div>
                            </td>
                            <td class="fw-semibold">Rp {{ number_format($trx->price, 0, ',', '.') }}</td>
                            <td>
                                @if($trx->status == 'SUCCESS')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1"><i class="bi bi-check-circle me-1"></i>Sukses</span>
                                @elseif(in_array($trx->status, ['PROCESS', 'PENDING']))
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1"><i class="bi bi-arrow-repeat me-1"></i>Proses</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1"><i class="bi bi-x-circle me-1"></i>Gagal</span>
                                @endif
                            </td>
                            <td style="max-width: 200px;">
                                @if(!empty($trx->sn))
                                    <div class="text-wrap text-break font-monospace bg-light p-1 rounded" style="font-size: 12px;">
                                        {{ $trx->sn }}
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="px-4 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    {{-- Tombol Struk / Invoice (Selalu ada) --}}
                                    <a href="{{ route('ppob.iak.invoice', $trx->ref_id) }}" class="btn btn-sm btn-primary" title="Lihat Struk">
                                        <i class="bi bi-receipt"></i>
                                    </a>

                                    {{-- LOGIKA TOMBOL CEK STATUS: Hanya muncul jika status PROCESS / PENDING --}}
                                    @if(in_array($trx->status, ['PROCESS', 'PENDING']))
                                        @if($trx->type === 'prabayar')
                                            {{-- Arahkan ke route checkStatusPrepaid (Gunakan GET) --}}
                                            <a href="{{ route('ppob.iak.check_prepaid', $trx->ref_id) }}" class="btn btn-sm btn-warning text-dark fw-semibold">
                                                <i class="bi bi-arrow-clockwise"></i> Cek
                                            </a>
                                        @else
                                            {{-- Pascabayar pakai tr_id untuk cek status --}}
                                            @if($trx->tr_id)
                                                <a href="{{ route('ppob.iak.check_postpaid', $trx->tr_id) }}" class="btn btn-sm btn-warning text-dark fw-semibold">
                                                    <i class="bi bi-arrow-clockwise"></i> Cek
                                                </a>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary opacity-50"></i>
                                Belum ada riwayat transaksi PPOB.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transactions->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
