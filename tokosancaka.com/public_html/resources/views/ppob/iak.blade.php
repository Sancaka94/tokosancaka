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
                                        <input type="text" class="form-control" id="customer_id_pra" name="customer_id" placeholder="081234567890" autocomplete="off" required>
                                    </div>
                                    <div id="operator-badge" class="mt-2 d-none">
                                        <span class="badge bg-primary px-3 py-2" id="operator-name"></span>
                                        <small class="text-muted ms-2" id="operator-msg"></small>
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
                                @forelse($transactions ?? [] as $trx)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">{{ $trx->customer_id }}</div>
                                        <div class="small text-muted d-flex align-items-center gap-1 mb-1">
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
        </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        // 1. Logika Pencarian Tabel (Pricelist Pascabayar)
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

        // ==========================================
        // 2. LOGIKA DETEKSI OPERATOR IAK
        // ==========================================
        const phoneInput = document.getElementById('customer_id_pra');
        const badgeContainer = document.getElementById('operator-badge');
        const operatorNameSpan = document.getElementById('operator-name');
        const operatorMsgSpan = document.getElementById('operator-msg');

        // Data Prefix sesuai dokumentasi IAK
        const prefixes = {
            'INDOSAT': { code: ['0814','0815','0816','0855','0856','0857','0858'], color: 'bg-warning text-dark' },
            'XL': { code: ['0817','0818','0819','0859','0878','0877'], color: 'bg-primary' },
            'AXIS': { code: ['0838','0837','0831','0832'], color: 'bg-purple' }, // custom style or use primary
            'TELKOMSEL': { code: ['0812','0813','0852','0853','0821','0823','0822','0851'], color: 'bg-danger' },
            'SMARTFREN': { code: ['0881','0882','0883','0884','0885','0886','0887','0888'], color: 'bg-info text-dark' },
            'THREE': { code: ['0896','0897','0898','0899','0895'], color: 'bg-dark' },
            'BY.U': { code: ['085154','085155','085156','085157','085158'], color: 'bg-primary' }
        };

        if(phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let number = e.target.value;

                // Hapus karakter non-angka
                number = number.replace(/[^0-9]/g, '');
                e.target.value = number;

                // Mulai deteksi jika nomor sudah 4 digit
                if(number.length >= 4) {
                    let foundOperator = 'UNKNOWN';
                    let foundColor = 'bg-secondary';

                    // Cek khusus by.U yang butuh 6 digit
                    if(number.length >= 6) {
                        let prefix6 = number.substring(0, 6);
                        if(prefixes['BY.U'].code.includes(prefix6)) {
                            foundOperator = 'BY.U';
                            foundColor = prefixes['BY.U'].color;
                        }
                    }

                    // Jika bukan by.U, cek 4 digit pertama
                    if(foundOperator === 'UNKNOWN') {
                        let prefix4 = number.substring(0, 4);
                        for (const [operator, data] of Object.entries(prefixes)) {
                            if(operator !== 'BY.U' && data.code.includes(prefix4)) {
                                foundOperator = operator;
                                foundColor = data.color;
                                break;
                            }
                        }
                    }

                    // Tampilkan Badge
                    badgeContainer.classList.remove('d-none');
                    operatorNameSpan.className = `badge ${foundColor} px-3 py-2`;

                    if(foundOperator !== 'UNKNOWN') {
                        operatorNameSpan.textContent = foundOperator;
                        operatorMsgSpan.textContent = 'Nomor Valid';
                        operatorMsgSpan.className = 'text-success ms-2 small fw-bold';
                    } else {
                        operatorNameSpan.textContent = 'Tidak Dikenal';
                        operatorNameSpan.className = 'badge bg-secondary px-3 py-2';
                        operatorMsgSpan.textContent = 'Prefix tidak cocok dengan operator manapun';
                        operatorMsgSpan.className = 'text-danger ms-2 small';
                    }

                } else {
                    // Sembunyikan jika kurang dari 4 digit
                    badgeContainer.classList.add('d-none');
                }
            });
        }
    });
</script>
@endpush
@endsection
