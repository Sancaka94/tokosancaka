@extends('layouts.app')

@section('content')

@php
    // Ekstrak data dari JSON Response IAK
    $data = $result['data'] ?? [];
    $desc = $data['desc'] ?? [];

    // Informasi Dasar (Universal untuk semua produk)
    $trName  = $data['tr_name'] ?? '-';
    $period  = $data['period'] ?? '-';
    $nominal = $data['nominal'] ?? 0;
    $admin   = $data['admin'] ?? 0;

    // Informasi Spesifik (Bisa ada, bisa tidak, tergantung PDAM/PLN/BPJS)
    $alamat   = $desc['address'] ?? $desc['alamat'] ?? null;
    $pdamName = $desc['pdam_name'] ?? null;
    $daya     = $desc['daya'] ?? null;
    $golongan = $desc['kode_tarif'] ?? $desc['tarif'] ?? null;
    $lembar   = $desc['bill_quantity'] ?? $desc['lembar_tagihan'] ?? $desc['jumlah_tagihan'] ?? 1;

    // Rincian Meteran & Denda (Khusus PDAM / PLN)
    $meterAwal  = $desc['bill']['detail'][0]['first_meter'] ?? null;
    $meterAkhir = $desc['bill']['detail'][0]['last_meter'] ?? null;
    $denda      = $desc['bill']['detail'][0]['penalty'] ?? 0;
@endphp

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-gradient-to-r bg-dark text-white pt-4 pb-3 border-0 text-center">
                    <i class="bi bi-receipt fs-1 text-primary-light mb-2 block"></i>
                    <h5 class="mb-0 fw-bold tracking-wide">Detail Tagihan Pascabayar</h5>
                </div>

                <div class="card-body p-4 bg-light">
                    <div class="alert alert-success border-0 shadow-sm rounded-3 py-2 px-3 mb-4 d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
                        <div>
                            <strong class="d-block text-dark">Inquiry Berhasil!</strong>
                            <small class="text-muted">Periksa kembali detail di bawah sebelum membayar.</small>
                        </div>
                    </div>

                    <div class="bg-white p-3 rounded-3 shadow-sm border border-gray-100">
                        <table class="table table-sm table-borderless mb-0 align-middle">
                            <tr>
                                <td class="text-muted py-2" style="width: 40%;">ID Pelanggan</td>
                                <td class="fw-bold text-end text-dark py-2">{{ $transaction->customer_id }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted py-2">Layanan</td>
                                <td class="fw-bold text-end text-primary py-2">{{ $transaction->product_code }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted py-2">Nama Pelanggan</td>
                                <td class="fw-bold text-end text-dark py-2">{{ $trName }}</td>
                            </tr>

                            @if($pdamName)
                            <tr>
                                <td class="text-muted py-2">Instansi</td>
                                <td class="fw-semibold text-end text-dark py-2">{{ $pdamName }}</td>
                            </tr>
                            @endif

                            @if($alamat)
                            <tr>
                                <td class="text-muted py-2 align-top">Alamat</td>
                                <td class="fw-semibold text-end text-secondary py-2 text-break" style="font-size: 13px;">{{ $alamat }}</td>
                            </tr>
                            @endif

                            <tr>
                                <td class="text-muted py-2">Periode Tagihan</td>
                                <td class="fw-semibold text-end text-dark py-2">{{ $period }} ({{ $lembar }} Bulan)</td>
                            </tr>

                            @if($golongan || $daya)
                            <tr>
                                <td class="text-muted py-2">Tarif / Daya</td>
                                <td class="fw-semibold text-end text-dark py-2">{{ $golongan ?? '-' }} / {{ $daya ?? '-' }}</td>
                            </tr>
                            @endif

                            @if(isset($meterAwal) && isset($meterAkhir))
                            <tr>
                                <td class="text-muted py-2">Stand Meter</td>
                                <td class="fw-semibold text-end text-dark py-2">
                                    <span class="badge bg-secondary text-white">{{ $meterAwal }}</span> <i class="bi bi-arrow-right mx-1 text-muted"></i> <span class="badge bg-primary text-white">{{ $meterAkhir }}</span>
                                </td>
                            </tr>
                            @endif

                            <tr>
                                <td colspan="2"><hr class="my-2 text-gray-300" style="border-style: dashed;"></td>
                            </tr>

                            <tr>
                                <td class="text-muted py-1">Tagihan Pokok</td>
                                <td class="text-end py-1 fw-medium text-dark">Rp {{ number_format($nominal, 0, ',', '.') }}</td>
                            </tr>

                            @if($denda > 0)
                            <tr>
                                <td class="text-danger py-1">Denda Keterlambatan</td>
                                <td class="text-end py-1 fw-medium text-danger">Rp {{ number_format($denda, 0, ',', '.') }}</td>
                            </tr>
                            @endif

                            <tr>
                                <td class="text-muted py-1">Biaya Admin</td>
                                <td class="text-end py-1 fw-medium text-dark">Rp {{ number_format($admin, 0, ',', '.') }}</td>
                            </tr>

                            <tr class="border-top">
                                <td class="text-dark fw-bold fs-6 pt-3">TOTAL BAYAR</td>
                                <td class="fw-bolder text-primary fs-4 text-end pt-3">Rp {{ number_format($transaction->price, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>

                    <form action="{{ route('ppob.pay_postpaid') }}" method="POST" class="mt-4" onsubmit="btnSubmit.disabled=true; btnSubmit.innerHTML='<span class=\'spinner-border spinner-border-sm me-2\'></span>Memproses...';">
                        @csrf
                        <input type="hidden" name="tr_id" value="{{ $transaction->tr_id }}">

                        <div class="row g-2">
                            <div class="col-12 col-sm-5">
                                <a href="{{ route('ppob.index') }}" class="btn btn-light border-secondary text-secondary w-100 py-3 fw-semibold hover-bg-gray-200 transition-colors">
                                    Batal
                                </a>
                            </div>
                            <div class="col-12 col-sm-7">
                                <button type="submit" name="btnSubmit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm hover-shadow-lg transition-all">
                                    <i class="bi bi-wallet2 me-2"></i> Bayar Sekarang
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>

            <div class="text-center mt-3 text-muted" style="font-size: 12px;">
                <i class="bi bi-shield-check me-1 text-success"></i> Pembayaran aman didukung oleh sistem Sancaka Express.
            </div>
        </div>
    </div>
</div>

<style>
    /* Sedikit perapian style khusus kotak ini */
    .bg-gradient-to-r { background: linear-gradient(to right, #1e293b, #334155); }
    .text-primary-light { color: #93c5fd; }
    .border-dashed { border-style: dashed !important; }
    .hover-shadow-lg:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
    .transition-all { transition: all 0.2s ease-in-out; }
</style>
@endsection
