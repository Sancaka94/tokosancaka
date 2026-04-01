@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h5 class="mb-3 fw-bold"><i class="bi bi-wallet2 text-primary me-2"></i>Transaksi PPOB IAK</h5>

                    <ul class="nav nav-pills nav-fill bg-light p-1 rounded" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold" id="pills-prabayar-tab" data-bs-toggle="pill" data-bs-target="#pills-prabayar" type="button" role="tab" aria-controls="pills-prabayar" aria-selected="true">
                                <i class="bi bi-phone me-1"></i> Prabayar
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold" id="pills-pascabayar-tab" data-bs-toggle="pill" data-bs-target="#pills-pascabayar" type="button" role="tab" aria-controls="pills-pascabayar" aria-selected="false">
                                <i class="bi bi-receipt me-1"></i> Pascabayar
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body pt-4">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="pills-prabayar" role="tabpanel" aria-labelledby="pills-prabayar-tab" tabindex="0">
                            <form action="{{ route('ppob.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="type" value="prabayar">

                                <div class="mb-3">
                                    <label for="customer_id_pra" class="form-label fw-semibold">Nomor HP / Tujuan</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-telephone text-muted"></i></span>
                                        <input type="text" class="form-control" id="customer_id_pra" name="customer_id" placeholder="081234567890" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="product_code_pra" class="form-label fw-semibold">Kode Produk</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-tags text-muted"></i></span>
                                        <input type="text" class="form-control" id="product_code_pra" name="product_code" placeholder="Misal: tsel10000" required>
                                    </div>
                                    <div class="form-text">Masukkan kode produk prabayar IAK yang valid.</div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                                    <i class="bi bi-cart-check me-1"></i> Beli Sekarang
                                </button>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="pills-pascabayar" role="tabpanel" aria-labelledby="pills-pascabayar-tab" tabindex="0">
                            <form action="{{ route('ppob.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="type" value="pascabayar">

                                <div class="mb-3">
                                    <label for="customer_id_pasca" class="form-label fw-semibold">ID Pelanggan / No Tagihan</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-person-vcard text-muted"></i></span>
                                        <input type="text" class="form-control" id="customer_id_pasca" name="customer_id" placeholder="Contoh: 532110000000" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="product_code_pasca" class="form-label fw-semibold">Kode Produk Pascabayar</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-lightning-charge text-muted"></i></span>
                                        <input type="text" class="form-control" id="product_code_pasca" name="product_code" placeholder="Misal: plnpostpaid" required>
                                    </div>
                                    <div class="form-text">Masukkan kode produk pascabayar IAK yang valid.</div>
                                </div>

                                <button type="submit" class="btn btn-dark w-100 py-2 fw-bold">
                                    <i class="bi bi-search me-1"></i> Cek & Bayar Tagihan
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-2">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history text-secondary me-2"></i>5 Transaksi Terakhir</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Tujuan</th>
                                    <th>Produk</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $trx)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold">{{ $trx->customer_id }}</div>
                                        <small class="text-muted" style="font-size: 0.75rem;">{{ $trx->ref_id }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $trx->product_code }}</span>
                                    </td>
                                    <td>
                                        @if($trx->status == 'SUCCESS')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle-fill me-1"></i>Sukses</span>
                                        @elseif($trx->status == 'FAILED')
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x-circle-fill me-1"></i>Gagal</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-arrow-repeat me-1"></i>Proses</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        Belum ada transaksi
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Pastikan Bootstrap Icons dimuat, jika di layouts.app belum ada, Anda bisa uncomment baris di bawah ini --}}
{{--
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush
--}}
