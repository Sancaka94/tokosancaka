@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white pt-3 pb-2">
                    <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Konfirmasi Pembayaran Tagihan</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Inquiry Berhasil!</strong> Silakan periksa detail tagihan di bawah ini sebelum melanjutkan pembayaran.
                    </div>

                    <table class="table table-borderless">
                        <tr>
                            <td class="text-muted">ID Pelanggan</td>
                            <td class="fw-bold">: {{ $transaction->customer_id }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Layanan</td>
                            <td class="fw-bold">: {{ $transaction->product_code }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Nama Pelanggan</td>
                            <td class="fw-bold">: {{ $result['data']['desc']['nama'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Periode/Bulan</td>
                            <td class="fw-bold">: {{ $result['data']['desc']['lembar_tagihan'] ?? '1' }} Bulan</td>
                        </tr>
                        <tr class="border-top">
                            <td class="text-muted fs-5">Total Bayar</td>
                            <td class="fw-bold text-primary fs-5">: Rp {{ number_format($transaction->price, 0, ',', '.') }}</td>
                        </tr>
                    </table>

                    <form action="{{ route('ppob.pay_postpaid') }}" method="POST" class="mt-4">
                        @csrf
                        <input type="hidden" name="tr_id" value="{{ $transaction->tr_id }}">

                        <div class="d-flex gap-2">
                            <a href="{{ route('ppob.index') }}" class="btn btn-outline-secondary w-50">Batalkan</a>
                            <button type="submit" class="btn btn-primary w-50 fw-bold">
                                <i class="bi bi-cash-coin me-1"></i> Bayar Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
