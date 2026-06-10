{{--
    File: resources/views/admin/rsud/show.blade.php
    Deskripsi: Halaman Detail Pesanan Obat RSUD (Bootstrap 5)
--}}

@extends('layouts.app')

@section('title', 'Detail Pesanan Obat RSUD - ' . $order->kode_booking)

@push('styles')
<style>
    .card-detail { border-radius: 12px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
    .card-header-detail { background-color: #f8f9fa; border-bottom: 1px solid #e9ecef; border-radius: 12px 12px 0 0 !important; font-weight: bold; color: #495057; padding: 1rem 1.25rem; }
    .info-label { font-size: 0.85rem; color: #6c757d; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 0.25rem; }
    .info-value { font-size: 1rem; color: #212529; font-weight: 500; margin-bottom: 1rem; }
    .border-dashed { border-top: 1px dashed #dee2e6; margin: 1rem 0; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    {{-- Header & Tombol Kembali --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-primary"><i class="fas fa-file-invoice-dollar me-2"></i> {{ $order->kode_booking }}</h4>
            <span class="text-muted small"><i class="far fa-clock me-1"></i> Dibuat pada: {{ $order->created_at->format('d F Y, H:i') }} WIB</span>
        </div>
        <div class="d-flex gap-2">
            @if(!empty($order->resi))
                <a href="https://tokosancaka.com/tracking/search?resi={{ $order->resi }}" target="_blank" class="btn btn-success shadow-sm">
                    <i class="fas fa-truck me-1"></i> Lacak Resi
                </a>
            @endif
            <a href="{{ route('admin.rsud.index') }}" class="btn btn-outline-secondary shadow-sm bg-white">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        {{-- KOLOM KIRI: Informasi Utama & Pasien --}}
        <div class="col-12 col-lg-8">

            {{-- Card 1: Status Pemesanan --}}
            <div class="card card-detail">
                <div class="card-header-detail d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-info-circle me-2 text-primary"></i> Status Pesanan</span>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-6 col-md-3 mb-3 mb-md-0">
                            <div class="info-label">Status Apotek</div>
                            @if($order->status_racik == 'Menunggu Diramu')
                                <span class="badge bg-danger px-3 py-2">Menunggu Diramu</span>
                            @elseif($order->status_racik == 'Selesai Diramu')
                                <span class="badge bg-info text-dark px-3 py-2">Selesai Diramu</span>
                            @else
                                <span class="badge bg-success px-3 py-2">{{ $order->status_racik }}</span>
                            @endif
                        </div>
                        <div class="col-6 col-md-3 mb-3 mb-md-0">
                            <div class="info-label">Status Bayar</div>
                            @if($order->payment_status == 'Lunas' || $order->payment_status == 'Lunas / COD')
                                <span class="badge bg-success px-3 py-2"><i class="fas fa-check-circle me-1"></i> Lunas</span>
                            @else
                                <span class="badge bg-warning text-dark px-3 py-2"><i class="fas fa-clock me-1"></i> Belum Lunas</span>
                            @endif
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="info-label">Metode Pembayaran</div>
                            <span class="badge bg-secondary px-3 py-2">{{ strtoupper($order->payment_method ?? '-') }}</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="info-label">Nomor Resi</div>
                            @if($order->resi)
                                <div class="fw-bold text-success"><i class="fas fa-box me-1"></i> {{ $order->resi }}</div>
                            @else
                                <div class="text-muted fst-italic">Belum ada resi</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 2: Data Pasien & Ekspedisi --}}
            <div class="card card-detail">
                <div class="card-header-detail">
                    <i class="fas fa-user-injured me-2 text-primary"></i> Data Pasien & Pengiriman
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6 border-end-md">
                            <h6 class="fw-bold text-dark mb-3">Tujuan (Pasien)</h6>
                            <div class="info-label">Nama Pasien / Penerima</div>
                            <div class="info-value">{{ $order->receiver_name ?? '-' }}</div>

                            <div class="info-label">Nomor Rekam Medis (RM)</div>
                            <div class="info-value text-primary fw-bold"><i class="fas fa-id-card me-1"></i> {{ $order->nomor_rm ?? 'Tanpa RM' }}</div>

                            <div class="info-label">Nomor WhatsApp / HP</div>
                            <div class="info-value">{{ $order->receiver_phone ?? '-' }}</div>

                            <div class="info-label">Alamat Pengiriman</div>
                            <div class="info-value mb-0">
                                {{ $order->receiver_address ?? '-' }}<br>
                                {{ $order->receiver_village ?? '' }}, {{ $order->receiver_district ?? '' }}<br>
                                {{ $order->receiver_regency ?? '' }}, {{ $order->receiver_province ?? '' }} <br>
                                Kode Pos: {{ $order->receiver_postal_code ?? '-' }}
                            </div>
                        </div>

                        <div class="col-md-6 mt-4 mt-md-0 ps-md-4">
                            <h6 class="fw-bold text-dark mb-3">Pengirim & Ekspedisi</h6>
                            <div class="info-label">Dikirim Dari</div>
                            <div class="info-value">
                                <strong>{{ $order->sender_name ?? 'RSUD Dr. Soeroto Ngawi' }}</strong><br>
                                <span class="text-muted small">{{ $order->sender_phone ?? '-' }}</span>
                            </div>

                            <div class="border-dashed"></div>

                            <div class="info-label">Kurir & Layanan</div>
                            <div class="info-value text-uppercase">
                                <i class="fas fa-truck-moving text-muted me-1"></i> {{ str_replace('_', ' ', $order->expedition ?? '-') }}
                            </div>

                            <div class="info-label">Jenis Layanan</div>
                            <div class="info-value text-uppercase">{{ $order->service_type ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- KOLOM KANAN: Isi Paket & Biaya --}}
        <div class="col-12 col-lg-4">

            {{-- Card 3: Detail Paket --}}
            <div class="card card-detail">
                <div class="card-header-detail">
                    <i class="fas fa-pills me-2 text-primary"></i> Rincian Paket
                </div>
                <div class="card-body p-4">
                    <div class="info-label">Isi Paket</div>
                    <div class="info-value">{{ $order->item_description ?? '-' }}</div>

                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="info-label">Berat</div>
                            <div class="info-value">{{ number_format($order->weight ?? 0, 0, ',', '.') }} Gram</div>
                        </div>
                        <div class="text-end">
                            <div class="info-label">Dimensi (P x L x T)</div>
                            <div class="info-value">{{ $order->length ?? 0 }} x {{ $order->width ?? 0 }} x {{ $order->height ?? 0 }} cm</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 4: Rincian Biaya --}}
            <div class="card card-detail">
                <div class="card-header-detail">
                    <i class="fas fa-receipt me-2 text-primary"></i> Rincian Pembayaran
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-2 text-muted">
                        <span>Harga Obat</span>
                        <span class="fw-medium text-dark">Rp {{ number_format($order->item_price ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-muted">
                        <span>Ongkos Kirim</span>
                        <span class="fw-medium text-dark">Rp {{ number_format($order->shipping_cost ?? 0, 0, ',', '.') }}</span>
                    </div>
                    @if(($order->insurance_cost ?? 0) > 0)
                    <div class="d-flex justify-content-between mb-2 text-muted">
                        <span>Asuransi</span>
                        <span class="fw-medium text-dark">Rp {{ number_format($order->insurance_cost, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    @if(($order->cod_fee ?? 0) > 0)
                    <div class="d-flex justify-content-between mb-2 text-muted">
                        <span>Biaya Admin COD</span>
                        <span class="fw-medium text-dark">Rp {{ number_format($order->cod_fee, 0, ',', '.') }}</span>
                    </div>
                    @endif

                    <div class="border-dashed"></div>

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">Total Tagihan</span>
                        <h4 class="fw-bold text-success mb-0">Rp {{ number_format($order->total_price ?? 0, 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
