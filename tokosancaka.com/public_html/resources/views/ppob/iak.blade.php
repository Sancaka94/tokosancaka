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

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
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
                                    <label for="product_code_pra" class="form-label fw-semibold">Kode Produk Prabayar</label>
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
                                        <input type="text" class="form-control" id="product_code_pasca" name="product_code" placeholder="Cari kode di tabel bawah (Misal: PLNPOSTPAID)" required>
                                    </div>
                                    <div class="form-text text-warning"><i class="bi bi-info-circle"></i> Sistem akan melakukan pengecekan tagihan (Inquiry) terlebih dahulu sebelum pembayaran.</div>
                                </div>

                                <button type="submit" class="btn btn-dark w-100 py-2 fw-bold">
                                    <i class="bi bi-search me-1"></i> Cek Tagihan (Inquiry)
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
                                    <th class="ps-4">Detail Tujuan</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $trx)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">{{ $trx->customer_id }}</div>
                                        <div class="small text-muted d-flex align-items-center gap-1">
                                            <span class="badge bg-secondary rounded-pill" style="font-size: 0.65rem;">{{ strtoupper($trx->type) }}</span>
                                            {{ $trx->product_code }}
                                        </div>
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
                                    <td class="text-end pe-4">
                                        @if($trx->status == 'PROCESS' && $trx->type == 'pascabayar' && $trx->tr_id)
                                            <a href="{{ route('ppob.check_status', $trx->tr_id) }}" class="btn btn-sm btn-outline-info" title="Cek Status Tagihan">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </a>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-light"></i>
                                        Belum ada riwayat transaksi
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

    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white pt-4 pb-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h5 class="mb-1 fw-bold"><i class="bi bi-list-stars text-success me-2"></i>Pricelist Pascabayar (Tagihan)</h5>
                        <small class="text-muted">Gunakan kode di bawah ini untuk form Pascabayar.</small>
                    </div>

                    <div class="d-flex gap-2 align-items-center">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="searchPricelist" placeholder="Cari nama/kode produk...">
                        </div>

                        <form action="{{ route('ppob.sync_pricelist') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success fw-semibold">
                                <i class="bi bi-cloud-download me-1"></i> Sinkron IAK
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" id="pricelistTable">
                            <thead class="table-light sticky-top" style="z-index: 1;">
                                <tr>
                                    <th class="ps-4">Kategori / Tipe</th>
                                    <th>Nama Produk</th>
                                    <th>Kode Produk</th>
                                    <th>Admin (Fee)</th>
                                    <th class="pe-4">Komisi Anda</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pricelist as $item)
                                <tr class="pricelist-row">
                                    <td class="ps-4">
                                        <span class="badge bg-dark-subtle text-dark text-uppercase border">{{ $item->type }}</span>
                                    </td>
                                    <td class="fw-semibold product-name">{{ $item->name }}</td>
                                    <td>
                                        <code class="fs-6 product-code user-select-all" role="button" title="Klik 2x untuk blok text">{{ $item->code }}</code>
                                    </td>
                                    <td>Rp {{ number_format($item->fee, 0, ',', '.') }}</td>
                                    <td class="pe-4 text-success fw-bold">+ Rp {{ number_format($item->komisi, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-database-exclamation fs-1 d-block mb-3 text-light"></i>
                                        Data pricelist kosong atau belum disinkronisasi.<br>
                                        Silakan klik tombol <strong>Sinkron IAK</strong> di atas untuk menarik data terbaru.
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

@push('scripts')
<script>
    // Fitur pencarian Real-time untuk tabel Pricelist
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchPricelist');
        const rows = document.querySelectorAll('.pricelist-row');

        if(searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                const term = e.target.value.toLowerCase();

                rows.forEach(row => {
                    const name = row.querySelector('.product-name').textContent.toLowerCase();
                    const code = row.querySelector('.product-code').textContent.toLowerCase();

                    if(name.includes(term) || code.includes(term)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });
</script>
@endpush
@endsection
