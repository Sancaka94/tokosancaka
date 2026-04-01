@extends('layouts.app')

@section('content')
<style>
    /* Efek hover dan klik untuk kartu produk */
    .product-card { transition: all 0.2s ease-in-out; }
    .product-card:hover { transform: translateY(-2px); border-color: #0d6efd !important; }
    .product-card.selected { border-color: #0d6efd !important; background-color: #e7f1ff !important; box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25); }
</style>

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
                                        <input type="text" class="form-control form-control-lg" id="customer_id_pra" name="customer_id" placeholder="081234567890" autocomplete="off" required>
                                    </div>
                                    <div id="operator-badge" class="mt-2 d-none">
                                        <span class="badge bg-primary px-3 py-2" id="operator-name"></span>
                                        <small class="text-muted ms-2" id="operator-msg"></small>
                                    </div>
                                </div>

                                <div class="mb-3 d-none" id="category-selector-container">
                                    <label class="form-label fw-semibold">Jenis Layanan</label>
                                    <select class="form-select" id="product_category_pra">
                                        <option value="pulsa">📱 Pulsa Reguler</option>
                                        <option value="data">🌐 Paket Data</option>
                                    </select>
                                </div>

                                <div class="mb-3 d-none" id="nominal-container">
                                    <label class="form-label fw-semibold">Cari Nominal (Opsional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">Rp</span>
                                        <input type="number" class="form-control" id="nominal_pra" placeholder="Misal: 10000">
                                    </div>
                                    <div class="form-text text-muted">Ketik angka untuk memfilter produk dengan cepat.</div>
                                </div>

                                <div class="mb-4 d-none" id="product-list-container">
                                    <div class="d-flex justify-content-between align-items-end mb-2">
                                        <label class="form-label fw-semibold mb-0">Pilih Nominal / Paket</label>
                                    </div>
                                    <div id="product-message" class="alert alert-warning py-2 small d-none mb-3"></div>

                                    <div class="row g-3" id="product-list">
                                        </div>
                                </div>

                                <div class="mb-4">
                                    <label for="product_code_pra" class="form-label fw-semibold">Kode Produk (Otomatis)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-tags text-muted"></i></span>
                                        <input type="text" class="form-control bg-light" id="product_code_pra" name="product_code" placeholder="Pilih produk di atas..." readonly required>
                                    </div>
                                </div>

                                <div id="alert-saldo-kurang" class="alert alert-danger mt-3 mb-0 text-center border-0 shadow-sm d-none" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Maaf, saldo Anda tidak mencukupi untuk membeli produk ini.
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3 mt-3" id="btn-submit-pra" disabled>
                                    <i class="bi bi-cart-check me-1"></i> Beli Sekarang
                                </button>

                            </form>
                        </div>

                        <div class="tab-pane fade" id="pills-pascabayar" role="tabpanel" aria-labelledby="pills-pascabayar-tab" tabindex="0">
                            </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // PERBAIKAN: Ambil saldo user (pastikan kolom 'balance' sesuai dengan skema database kamu)
        let currentBalance = {{ auth()->user()->balance ?? 0 }};

        const phoneInput = document.getElementById('customer_id_pra');
        const badgeContainer = document.getElementById('operator-badge');
        const operatorNameSpan = document.getElementById('operator-name');
        const operatorMsgSpan = document.getElementById('operator-msg');

        const categoryContainer = document.getElementById('category-selector-container');
        const categorySelect = document.getElementById('product_category_pra');

        const nominalContainer = document.getElementById('nominal-container');
        const nominalInput = document.getElementById('nominal_pra');

        const productContainer = document.getElementById('product-list-container');
        const productList = document.getElementById('product-list');
        const productMessage = document.getElementById('product-message');
        const productCodeInput = document.getElementById('product_code_pra');
        const btnSubmitPra = document.getElementById('btn-submit-pra');
        const alertSaldo = document.getElementById('alert-saldo-kurang'); // Tambahan elemen alert

        let currentOperator = '';
        let typingTimer; // Untuk debounce input nominal
        const doneTypingInterval = 500; // Delay 0.5 detik

        const prefixes = {
            'INDOSAT': { code: ['0814','0815','0816','0855','0856','0857','0858'], color: 'bg-warning text-dark' },
            'XL': { code: ['0817','0818','0819','0859','0878','0877'], color: 'bg-primary' },
            'AXIS': { code: ['0838','0837','0831','0832'], color: 'bg-purple' },
            'TELKOMSEL': { code: ['0812','0813','0852','0853','0821','0823','0822','0851'], color: 'bg-danger' },
            'SMARTFREN': { code: ['0881','0882','0883','0884','0885','0886','0887','0888'], color: 'bg-info text-dark' },
            'THREE': { code: ['0896','0897','0898','0899','0895'], color: 'bg-dark' },
            'BY.U': { code: ['085154','085155','085156','085157','085158'], color: 'bg-primary' }
        };

        if(phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let number = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = number;

                if(number.length >= 4) {
                    let foundOperator = 'UNKNOWN';
                    let foundColor = 'bg-secondary';

                    if(number.length >= 6) {
                        let prefix6 = number.substring(0, 6);
                        if(prefixes['BY.U'].code.includes(prefix6)) {
                            foundOperator = 'BY.U'; foundColor = prefixes['BY.U'].color;
                        }
                    }

                    if(foundOperator === 'UNKNOWN') {
                        let prefix4 = number.substring(0, 4);
                        for (const [operator, data] of Object.entries(prefixes)) {
                            if(operator !== 'BY.U' && data.code.includes(prefix4)) {
                                foundOperator = operator; foundColor = data.color;
                                break;
                            }
                        }
                    }

                    badgeContainer.classList.remove('d-none');
                    operatorNameSpan.className = `badge ${foundColor} px-3 py-2`;

                    if(foundOperator !== 'UNKNOWN') {
                        operatorNameSpan.textContent = foundOperator;
                        operatorMsgSpan.textContent = 'Nomor Valid';
                        operatorMsgSpan.className = 'text-success ms-2 small fw-bold';

                        if(currentOperator !== foundOperator) {
                            currentOperator = foundOperator;
                            categoryContainer.classList.remove('d-none');
                            nominalContainer.classList.remove('d-none');
                            nominalInput.value = ''; // Reset input nominal
                            fetchProducts(currentOperator, categorySelect.value, '');
                        }
                    } else {
                        resetProductView();
                        operatorNameSpan.textContent = 'Tidak Dikenal';
                        operatorNameSpan.className = 'badge bg-secondary px-3 py-2';
                        operatorMsgSpan.textContent = 'Prefix tidak cocok dengan operator manapun';
                        operatorMsgSpan.className = 'text-danger ms-2 small';
                    }

                } else {
                    badgeContainer.classList.add('d-none');
                    resetProductView();
                }
            });
        }

        categorySelect.addEventListener('change', function(e) {
            if(currentOperator) {
                fetchProducts(currentOperator, e.target.value, nominalInput.value);
            }
        });

        // Event listener untuk Input Nominal (menggunakan teknik Debounce)
        nominalInput.addEventListener('keyup', function () {
            clearTimeout(typingTimer);
            if (currentOperator) {
                // Menunggu 0.5 detik setelah user selesai mengetik baru nembak API
                typingTimer = setTimeout(() => {
                    fetchProducts(currentOperator, categorySelect.value, nominalInput.value);
                }, doneTypingInterval);
            }
        });

        function resetProductView() {
            currentOperator = '';
            categoryContainer.classList.add('d-none');
            nominalContainer.classList.add('d-none');
            productContainer.classList.add('d-none');
            productMessage.classList.add('d-none');
            productList.innerHTML = '';
            productCodeInput.value = '';
            if (btnSubmitPra) {
                btnSubmitPra.disabled = true;
            }
            if (alertSaldo) {
                alertSaldo.classList.add('d-none');
            }
        }

        function fetchProducts(operator, type, nominal) {
            productContainer.classList.remove('d-none');
            productMessage.classList.add('d-none');
            productList.innerHTML = `<div class="col-12 text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="ms-2 text-muted">Mencari produk...</span></div>`;

            productCodeInput.value = '';
            if (btnSubmitPra) {
                btnSubmitPra.disabled = true;
            }
            if (alertSaldo) {
                alertSaldo.classList.add('d-none');
            }

            // Memasukkan parameter nominal ke dalam URL AJAX
            fetch(`{{ route('ppob.get_products') }}?operator=${operator}&type=${type}&nominal=${nominal}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success && data.data.length > 0) {
                    let html = '';

                    // Tampilkan pesan fallback jika nominal tidak ketemu
                    if(data.message) {
                        productMessage.innerHTML = `<i class="bi bi-info-circle me-1"></i> ${data.message}`;
                        productMessage.classList.remove('d-none');
                    }

                    data.data.forEach(item => {
                        let rpPrice = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(item.price);

                        // PERBAIKAN: Kirim item.price ke fungsi selectProduct saat diklik
                        html += `
                        <div class="col-6 col-md-4">
                            <div class="card h-100 border product-card cursor-pointer shadow-sm" style="cursor: pointer;" onclick="selectProduct('${item.code}', ${item.price}, this)">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="small fw-bold text-dark mb-2 lh-sm" title="${item.description}">${item.description}</div>
                                    <div class="text-primary fw-bolder fs-6">${rpPrice}</div>
                                </div>
                            </div>
                        </div>`;
                    });
                    productList.innerHTML = html;
                } else {
                    productList.innerHTML = `<div class="col-12 text-center py-3"><div class="text-muted small">Belum ada produk aktif untuk kategori ini.</div></div>`;
                }
            })
            .catch(err => {
                productList.innerHTML = `<div class="col-12 text-center py-3"><div class="text-danger small">Gagal memuat produk. Periksa koneksi Anda.</div></div>`;
            });
        }

        // PERBAIKAN: Fungsi selectProduct sekarang menerima 3 parameter dan mengecek saldo
        window.selectProduct = function(code, price, element) {
            document.querySelectorAll('.product-card').forEach(el => {
                el.classList.remove('selected');
            });
            element.classList.add('selected');
            productCodeInput.value = code;

            // Logika pengecekan saldo
            if (currentBalance >= price) {
                // Saldo Cukup
                if (btnSubmitPra) btnSubmitPra.disabled = false;
                if (alertSaldo) alertSaldo.classList.add('d-none');
            } else {
                // Saldo Kurang
                if (btnSubmitPra) btnSubmitPra.disabled = true;
                if (alertSaldo) alertSaldo.classList.remove('d-none');
            }
        }
    });
</script>
@endpush

@endsection
