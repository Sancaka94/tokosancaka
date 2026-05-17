@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<style>
    /* Efek hover dan klik untuk kartu produk */
    .product-card { transition: all 0.2s ease-in-out; }
    .product-card:hover { transform: translateY(-2px); border-color: #0d6efd !important; }
    .product-card.selected { border-color: #0d6efd !important; background-color: #e7f1ff !important; box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25); }
    .cursor-pointer { cursor: pointer; }

    /* Penyesuaian tinggi Select2 agar sejajar dengan form-control-lg bawaan Bootstrap */
    .select2-container--bootstrap-5 .select2-selection {
        min-height: calc(1.5em + 1rem + 2px);
        padding: .5rem 1rem;
        font-size: 1.25rem;
        border-radius: .3rem;
    }
</style>

{{-- 🔥 TAMBAHAN KODE PENGAMAN (IDEMPOTENCY) 🔥 --}}
@php
    // Membuat kunci unik untuk mencegah dobel input saat user submit form berkali-kali
    if (!isset($idempotencyKey)) {
        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();
    }
@endphp

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

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Gagal!</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12">
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

                        {{-- ===================================== --}}
                        {{-- TAB PRABAYAR --}}
                        {{-- ===================================== --}}
                        <div class="tab-pane fade show active" id="pills-prabayar" role="tabpanel" aria-labelledby="pills-prabayar-tab" tabindex="0">

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Pilih Layanan Prabayar</label>
                                <select class="form-select form-select-lg" id="kategori_layanan">
                                    <option value="pulsa" selected>📱 Pulsa & Paket Data</option>
                                    <option value="pln">⚡ Token PLN</option>
                                    <option value="ovo">🟣 Saldo OVO</option>
                                    <option value="game">🎮 Voucher / Topup Game</option>
                                </select>
                            </div>

                            <form action="{{ route('ppob.iak.store') }}" method="POST" id="form-prabayar">
                                @csrf
                                <input type="hidden" name="type" value="prabayar">
                                <input type="hidden" name="customer_id" id="final_customer_id">
                                <input type="hidden" name="product_code" id="product_code_pra" required>
                                <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

                                {{-- SECTION: PULSA & DATA --}}
                                <div id="section-pulsa" class="section-pra">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nomor HP</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-telephone text-muted"></i></span>
                                            <input type="text" class="form-control form-control-lg" id="customer_id_pulsa" placeholder="081234567890" autocomplete="off">
                                        </div>
                                        <div id="operator-badge" class="mt-2 d-none">
                                            <span class="badge bg-primary px-3 py-2" id="operator-name"></span>
                                            <small class="text-muted ms-2" id="operator-msg"></small>
                                        </div>
                                    </div>
                                    <div class="mb-3 d-none" id="category-selector-container">
                                        <label class="form-label fw-semibold">Jenis Produk</label>
                                        <select class="form-select" id="product_category_pulsa">
                                            <option value="pulsa">Pulsa Reguler</option>
                                            <option value="data">Paket Data</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- SECTION: PLN TOKEN --}}
                                <div id="section-pln" class="section-pra d-none">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nomor Meter / ID Pelanggan PLN</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-lightning-charge text-warning"></i></span>
                                            <input type="text" class="form-control form-control-lg" id="customer_id_pln" placeholder="Masukkan ID Pelanggan">
                                            <button type="button" class="btn btn-outline-primary" id="btn-cek-pln">Cek ID</button>
                                        </div>
                                        <div id="pln-info" class="alert alert-info mt-3 d-none p-2 mb-0 small">
                                            <strong>Nama:</strong> <span id="pln-name"></span> <br>
                                            <strong>Daya:</strong> <span id="pln-segment"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- SECTION: OVO --}}
                                <div id="section-ovo" class="section-pra d-none">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nomor HP OVO</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-wallet text-purple"></i></span>
                                            <input type="text" class="form-control form-control-lg" id="customer_id_ovo" placeholder="08xxxx">
                                            <button type="button" class="btn btn-outline-primary" id="btn-cek-ovo">Cek Nomor</button>
                                        </div>
                                        <div id="ovo-info" class="alert alert-info mt-3 d-none p-2 mb-0 small">
                                            <strong>Atas Nama:</strong> <span id="ovo-name"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- SECTION: GAME --}}
                                <div id="section-game" class="section-pra d-none">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Pilih Game</label>
                                        <select class="form-select" id="game_selector">
                                            <option value="">-- Memuat Data Game... --</option>
                                        </select>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-7">
                                            <label class="form-label fw-semibold">User ID / Player ID</label>
                                            <input type="text" class="form-control" id="game_player_id" placeholder="Masukkan ID Game">
                                            <small class="text-muted d-block mt-1" id="game_format_help"></small>
                                        </div>
                                        <div class="col-md-5 d-none" id="game_server_container">
                                            <label class="form-label fw-semibold">Server / Zone</label>
                                            <select class="form-select" id="game_server_id"></select>
                                        </div>
                                    </div>
                                </div>

                                {{-- PRODUK LIST CONTAINER --}}
                                <div class="mb-3 d-none" id="nominal-container">
                                    <div class="input-group input-group-sm mb-2 w-50">
                                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                        <input type="number" class="form-control" id="nominal_pra" placeholder="Cari nominal cepat...">
                                    </div>
                                </div>

                                <div class="mb-4 d-none" id="product-list-container">
                                    <label class="form-label fw-semibold mb-2">Pilih Nominal / Paket</label>
                                    <div id="product-message" class="alert alert-warning py-2 small d-none mb-3"></div>
                                    <div class="row g-3" id="product-list"></div>
                                </div>

                                {{-- GRUP DETAIL PEMBAYARAN PRABAYAR --}}
                                <div class="card bg-light border-0 p-3 mb-3 rounded-3 mt-4" id="area-pembayaran-pra">
                                    <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-1"></i>Detail Pembayaran</h6>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Nomor WhatsApp Struk</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-whatsapp text-success"></i></span>
                                            <input type="text" class="form-control wa-formatter" name="whatsapp_number" value="{{ auth()->user()->no_wa ?? '' }}" placeholder="08xxxx (Opsional)">
                                        </div>
                                    </div>

                                    {{-- METODE PEMBAYARAN PRABAYAR (DIUBAH MENJADI MODAL TRIGGER) --}}
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Metode Pembayaran</label>
                                        <input type="hidden" name="payment_method" class="payment_method_hidden" id="payment_method_pra" required>
                                        <div class="form-control form-control-lg d-flex justify-content-between align-items-center cursor-pointer btn-payment-trigger" data-target="prabayar" style="background-color: #fff;">
                                            <div class="d-flex align-items-center">
                                                <img src="https://cdn-icons-png.flaticon.com/512/2331/2331941.png" class="payment-logo me-3" style="width: 28px; height: 28px; object-fit: contain;">
                                                <span class="payment-name fw-semibold text-dark fs-6">Pilih Metode Bayar...</span>
                                            </div>
                                            <i class="bi bi-chevron-down text-muted"></i>
                                        </div>
                                    </div>

                                    {{-- BLOK VERIFIKASI SALDO (PRABAYAR) --}}
                                    <div class="saldo_payment_section d-none border border-primary p-3 rounded bg-white">
                                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-shield-lock me-1"></i> Verifikasi Akun Sancaka</h6>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">No WhatsApp Akun Sancaka</label>
                                            <input type="text" class="form-control wa-formatter wa_pembayaran_input" name="wa_pembayaran" value="{{ auth()->user()->no_wa ?? '' }}" placeholder="08xxxx">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold text-danger">PIN Keamanan</label>
                                            <input type="password" class="form-control pin_pembayaran_input" name="pin_pembayaran" placeholder="******" maxlength="6">
                                        </div>
                                        <button type="button" class="btn btn-outline-primary w-100 btn-sm btn_cek_saldo_ajax">Cek & Verifikasi Saldo</button>

                                        <div class="info_saldo_box alert alert-success d-none p-2 small mb-0 mt-3">
                                            <i class="bi bi-person-check-fill"></i> <span class="nama_akun_teks fw-bold"></span><br>
                                            <i class="bi bi-wallet2"></i> Sisa Saldo: Rp <span class="nominal_saldo_teks fw-bold"></span>
                                        </div>
                                        <div class="error_saldo_box alert alert-danger d-none p-2 small mb-0 mt-3"></div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3 mt-2" id="btn-submit-pra" disabled>
                                    <i class="bi bi-cart-check me-1"></i> Beli Sekarang
                                </button>
                            </form>
                        </div>

                        {{-- ===================================== --}}
                        {{-- TAB PASCABAYAR (TAGIHAN) --}}
                        {{-- ===================================== --}}
                        <div class="tab-pane fade" id="pills-pascabayar" role="tabpanel" aria-labelledby="pills-pascabayar-tab" tabindex="0">
                            <form action="{{ route('ppob.iak.store') }}" method="POST" id="form-pascabayar">
                                @csrf
                                <input type="hidden" name="type" value="pascabayar">
                                <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Pilih Layanan Tagihan</label>
                                    <select class="form-select form-select-lg select2-enable" name="product_code" id="pasca_product_code" required>
                                        <option value="">-- Ketik Nama Tagihan / Produk --</option>
                                        @foreach($pricelist as $item)
                                            <option value="{{ $item->code }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Nomor Pelanggan / Tagihan</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-receipt text-muted"></i></span>
                                        <input type="text" class="form-control form-control-lg" name="customer_id" placeholder="Masukkan ID Pelanggan" required autocomplete="off">
                                    </div>
                                </div>

                                {{-- INPUT TAMBAHAN KHUSUS BPJS --}}
                                <div class="mb-4 d-none" id="container-month-bpjs">
                                    <label class="form-label fw-semibold">Bayar Untuk Berapa Bulan?</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-calendar-check text-muted"></i></span>
                                        <input type="number" class="form-control form-control-lg" name="month" id="input_month" value="1" min="1" max="12" placeholder="1">
                                        <span class="input-group-text bg-white">Bulan</span>
                                    </div>
                                </div>

                                {{-- INPUT TAMBAHAN KHUSUS E-SAMSAT --}}
                                <div class="mb-4 d-none" id="container-esamsat">
                                    <label class="form-label fw-semibold">Nomor Identitas (NIK KTP)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-person-badge text-muted"></i></span>
                                        <input type="text" class="form-control form-control-lg" name="nomor_identitas" id="input_identitas" placeholder="Masukkan NIK Pemilik Kendaraan">
                                    </div>
                                </div>

                                {{-- INPUT TAMBAHAN KHUSUS PBB --}}
                                <div class="mb-4 d-none" id="container-pbb-year">
                                    <label class="form-label fw-semibold">Tahun Pajak</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-calendar text-muted"></i></span>
                                        <input type="number" class="form-control form-control-lg" name="year" id="input_year" value="{{ date('Y') }}" placeholder="Misal: 2024">
                                    </div>
                                </div>

                                {{-- INPUT TAMBAHAN KHUSUS DONASI / CUSTOM DENOM --}}
                                <div class="mb-4 d-none" id="container-amount-custom">
                                    <label class="form-label fw-semibold">Nominal Tagihan / Top Up</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">Rp</span>
                                        <input type="number" class="form-control form-control-lg" name="amount" id="input_amount" placeholder="Misal: 50000" min="1000">
                                    </div>
                                    <div class="form-text text-muted">Masukkan nominal yang ingin dibayarkan secara manual.</div>
                                </div>

                                {{-- GRUP DETAIL PEMBAYARAN PASCABAYAR --}}
                                <div class="card bg-light border-0 p-3 mb-3 rounded-3 mt-4">
                                    <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-1"></i>Detail Pembayaran</h6>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Nomor WhatsApp Struk</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-whatsapp text-success"></i></span>
                                            <input type="text" class="form-control wa-formatter" name="whatsapp_number" value="{{ auth()->user()->no_wa ?? '' }}" placeholder="08xxxx (Opsional untuk struk)">
                                        </div>
                                    </div>

                                    {{-- METODE PEMBAYARAN PASCABAYAR (DIUBAH MENJADI MODAL TRIGGER) --}}
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Metode Pembayaran</label>
                                        <input type="hidden" name="payment_method" class="payment_method_hidden" id="payment_method_pasca" required>
                                        <div class="form-control form-control-lg d-flex justify-content-between align-items-center cursor-pointer btn-payment-trigger" data-target="pascabayar" style="background-color: #fff;">
                                            <div class="d-flex align-items-center">
                                                <img src="https://cdn-icons-png.flaticon.com/512/2331/2331941.png" class="payment-logo me-3" style="width: 28px; height: 28px; object-fit: contain;">
                                                <span class="payment-name fw-semibold text-dark fs-6">Pilih Metode Bayar...</span>
                                            </div>
                                            <i class="bi bi-chevron-down text-muted"></i>
                                        </div>
                                    </div>

                                    {{-- BLOK VERIFIKASI SALDO (PASCABAYAR) --}}
                                    <div class="saldo_payment_section d-none border border-primary p-3 rounded bg-white">
                                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-shield-lock me-1"></i> Verifikasi Akun Sancaka</h6>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">No WhatsApp Akun Sancaka</label>
                                            <input type="text" class="form-control wa-formatter wa_pembayaran_input" name="wa_pembayaran" value="{{ auth()->user()->no_wa ?? '' }}" placeholder="08xxxx">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold text-danger">PIN Keamanan</label>
                                            <input type="password" class="form-control pin_pembayaran_input" name="pin_pembayaran" placeholder="******" maxlength="6">
                                        </div>
                                        <button type="button" class="btn btn-outline-primary w-100 btn-sm btn_cek_saldo_ajax">Cek & Verifikasi Saldo</button>

                                        <div class="info_saldo_box alert alert-success d-none p-2 small mb-0 mt-3">
                                            <i class="bi bi-person-check-fill"></i> <span class="nama_akun_teks fw-bold"></span><br>
                                            <i class="bi bi-wallet2"></i> Sisa Saldo: Rp <span class="nominal_saldo_teks fw-bold"></span>
                                        </div>
                                        <div class="error_saldo_box alert alert-danger d-none p-2 small mb-0 mt-3"></div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-success w-100 py-3 fw-bold rounded-3 mt-2" id="btn-submit-pasca" disabled>
                                    <i class="bi bi-search me-1"></i> Lanjut Cek Tagihan
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PEMBAYARAN BOOTSTRAP 5 -->
<div class="modal fade" id="paymentMethodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-2">
                <h5 class="modal-title fw-bold"><i class="bi bi-credit-card text-primary me-2"></i>Pilih Metode Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="list-group list-group-flush rounded-0" id="paymentOptionsList">
                    <!-- Manual -->
                    <button type="button" class="list-group-item list-group-item-action p-3 d-flex align-items-center payment-option" data-value="SALDO" data-label="Potong Saldo (Verifikasi WA & PIN)" data-img="https://cdn-icons-png.flaticon.com/512/1086/1086060.png">
                        <img src="https://cdn-icons-png.flaticon.com/512/1086/1086060.png" class="me-3" style="width:32px; height:32px;">
                        <div class="text-start">
                            <div class="fw-bold text-dark">Potong Saldo</div>
                            <div class="text-muted small">Verifikasi via WA & PIN Keamanan</div>
                        </div>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action p-3 d-flex align-items-center payment-option" data-value="DANA" data-label="Bayar Pakai DANA" data-img="https://cdn-icons-png.flaticon.com/512/2489/2489756.png">
                        <img src="https://cdn-icons-png.flaticon.com/512/2489/2489756.png" class="me-3" style="width:32px; height:32px;">
                        <div class="fw-bold text-dark">DANA</div>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action p-3 d-flex align-items-center payment-option" data-value="DOKU" data-label="DOKU Jokul" data-img="https://cdn-icons-png.flaticon.com/512/2331/2331941.png">
                        <img src="https://cdn-icons-png.flaticon.com/512/2331/2331941.png" class="me-3" style="width:32px; height:32px;">
                        <div class="fw-bold text-dark">DOKU Jokul</div>
                    </button>

                    <div class="bg-light p-2 text-muted small fw-bold text-uppercase border-top border-bottom">Transfer Bank & E-Wallet (Otomatis)</div>

                    <!-- Wadah API Tripay -->
                    <div id="tripayChannelsContainer">
                        <div class="p-4 text-center text-muted small">
                            <div class="spinner-border spinner-border-sm me-2"></div>Memuat saluran pembayaran...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // INISIALISASI SELECT2 UNTUK PASCABAYAR
    $(document).ready(function() {
        $('#pasca_product_code').select2({
            theme: 'bootstrap-5',
            placeholder: "-- Ketik Nama Tagihan / Produk --",
            width: '100%',
            allowClear: true
        });

        $('#pasca_product_code').on('select2:select select2:clear', function (e) {
            this.dispatchEvent(new Event('change'));
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        let selectedProductPrice = 0;

        // Elements Prabayar
        const formPra = document.getElementById('form-prabayar');
        const finalCustomerId = document.getElementById('final_customer_id');
        const btnSubmitPra = document.getElementById('btn-submit-pra');
        const productCodeInput = document.getElementById('product_code_pra');

        // Product List Elements
        const productContainer = document.getElementById('product-list-container');
        const productList = document.getElementById('product-list');
        const productMessage = document.getElementById('product-message');
        const nominalContainer = document.getElementById('nominal-container');
        const nominalInput = document.getElementById('nominal_pra');

        let currentOperator = '';
        let currentCategory = '';
        let typingTimer;

        // ==========================================
        // 1. LOGIK SWICTH LAYANAN PRABAYAR
        // ==========================================
        document.getElementById('kategori_layanan').addEventListener('change', function(e) {
            let cat = e.target.value;
            document.querySelectorAll('.section-pra').forEach(el => el.classList.add('d-none'));
            document.getElementById('section-' + cat).classList.remove('d-none');
            resetProductView();
            if (cat === 'game') loadGameList();
        });

        // ==========================================
        // 2. LOGIK PULSA & DATA
        // ==========================================
        const phoneInput = document.getElementById('customer_id_pulsa');
        const categorySelectPulsa = document.getElementById('product_category_pulsa');
        const badgeContainer = document.getElementById('operator-badge');
        const operatorNameSpan = document.getElementById('operator-name');
        const operatorMsgSpan = document.getElementById('operator-msg');

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
                            currentCategory = categorySelectPulsa.value;
                            document.getElementById('category-selector-container').classList.remove('d-none');
                            fetchProducts(currentOperator, currentCategory, '');
                        }
                    } else {
                        resetProductView();
                        operatorNameSpan.textContent = 'Tidak Dikenal';
                        operatorNameSpan.className = 'badge bg-secondary px-3 py-2';
                        operatorMsgSpan.textContent = 'Prefix tidak cocok';
                        operatorMsgSpan.className = 'text-danger ms-2 small';
                    }
                } else {
                    badgeContainer.classList.add('d-none');
                    resetProductView();
                }
            });
        }

        if(categorySelectPulsa) {
            categorySelectPulsa.addEventListener('change', function(e) {
                if(currentOperator) {
                    currentCategory = e.target.value;
                    fetchProducts(currentOperator, currentCategory, nominalInput.value);
                }
            });
        }

        // ==========================================
        // 3. LOGIK PLN
        // ==========================================
        const btnCekPln = document.getElementById('btn-cek-pln');
        if(btnCekPln) {
            btnCekPln.addEventListener('click', function() {
                let meter = document.getElementById('customer_id_pln').value;
                if(!meter) return alert("Masukkan nomor meter PLN!");

                this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                this.disabled = true;

                fetch("{{ route('ppob.iak.inquiry_pln') }}", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                    body: JSON.stringify({ customer_id: meter })
                })
                .then(res => res.json())
                .then(data => {
                    this.innerHTML = 'Cek ID';
                    this.disabled = false;
                    if(data.success) {
                        document.getElementById('pln-name').innerText = data.data.name;
                        document.getElementById('pln-segment').innerText = data.data.segment_power;
                        document.getElementById('pln-info').classList.remove('d-none');
                        fetchProducts('PLN', '', '');
                    } else {
                        alert(data.message);
                        document.getElementById('pln-info').classList.add('d-none');
                        resetProductView();
                    }
                }).catch(err => {
                    this.innerHTML = 'Cek ID'; this.disabled = false; alert("Gagal koneksi ke server");
                });
            });
        }

        // ==========================================
        // 4. LOGIK OVO
        // ==========================================
        const btnCekOvo = document.getElementById('btn-cek-ovo');
        if(btnCekOvo) {
            btnCekOvo.addEventListener('click', function() {
                let phone = document.getElementById('customer_id_ovo').value;
                if(!phone) return alert("Masukkan nomor OVO!");

                this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                this.disabled = true;

                fetch("{{ route('ppob.iak.inquiry_ovo') }}", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                    body: JSON.stringify({ customer_id: phone })
                })
                .then(res => res.json())
                .then(data => {
                    this.innerHTML = 'Cek Nomor';
                    this.disabled = false;
                    if(data.success) {
                        document.getElementById('ovo-name').innerText = data.data.name;
                        document.getElementById('ovo-info').classList.remove('d-none');
                        fetchProducts('OVO', '', '');
                    } else {
                        alert(data.message);
                        document.getElementById('ovo-info').classList.add('d-none');
                        resetProductView();
                    }
                }).catch(err => {
                    this.innerHTML = 'Cek Nomor'; this.disabled = false; alert("Gagal koneksi ke server");
                });
            });
        }

        // ==========================================
        // 5. LOGIK GAME
        // ==========================================
        function loadGameList() {
            let gameSel = document.getElementById('game_selector');
            if(gameSel.options.length > 1) return;

            fetch("{{ route('ppob.iak.gamelist') }}", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    let opt = '<option value="">-- Pilih Game --</option>';
                    data.data.forEach(g => {
                        opt += `<option value="${g.game_code}" data-name="${g.name}">${g.name}</option>`;
                    });
                    gameSel.innerHTML = opt;
                } else {
                    gameSel.innerHTML = '<option value="">Gagal memuat game</option>';
                }
            });
        }

        const gameSelector = document.getElementById('game_selector');
        if(gameSelector) {
            gameSelector.addEventListener('change', function() {
                let gameCode = this.value;
                let gameName = this.options[this.selectedIndex].getAttribute('data-name');

                document.getElementById('game_format_help').innerText = '';
                document.getElementById('game_server_container').classList.add('d-none');
                resetProductView();

                if(!gameCode) return;

                fetch("{{ route('ppob.iak.inquiry_game_format') }}", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                    body: JSON.stringify({ game_code: gameCode })
                }).then(r => r.json()).then(d => {
                    if(d.success) document.getElementById('game_format_help').innerText = 'Format Input: ' + d.data.formatGameId;
                });

                fetch("{{ route('ppob.iak.inquiry_game_server') }}", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                    body: JSON.stringify({ game_code: gameCode })
                }).then(r => r.json()).then(d => {
                    if(d.success && d.data.servers && d.data.servers.length > 0) {
                        document.getElementById('game_server_container').classList.remove('d-none');
                        let sOpt = '<option value="">Pilih Server/Zone</option>';
                        d.data.servers.forEach(s => sOpt += `<option value="${s.value}">${s.name}</option>`);
                        document.getElementById('game_server_id').innerHTML = sOpt;
                    }
                });

                fetchProducts(gameName, '', '');
            });
        }

        // ==========================================
        // 6. CORE FUNGSI FETCH PRODUK
        // ==========================================
        if(nominalInput) {
            nominalInput.addEventListener('keyup', function () {
                clearTimeout(typingTimer);
                if (currentOperator) {
                    typingTimer = setTimeout(() => {
                        fetchProducts(currentOperator, currentCategory, nominalInput.value);
                    }, 500);
                }
            });
        }

        function resetProductView() {
            productContainer.classList.add('d-none');
            nominalContainer.classList.add('d-none');
            productList.innerHTML = '';
            productCodeInput.value = '';
            selectedProductPrice = 0;
            updateBtnSubmit('prabayar');
        }

        function fetchProducts(operator, type, nominal) {
            currentOperator = operator;
            productContainer.classList.remove('d-none');
            nominalContainer.classList.remove('d-none');
            productMessage.classList.add('d-none');
            productList.innerHTML = `<div class="col-12 text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="ms-2 text-muted">Mencari produk...</span></div>`;

            productCodeInput.value = '';
            selectedProductPrice = 0;
            updateBtnSubmit('prabayar');

            fetch(`{{ route('ppob.get_products') }}?operator=${operator}&type=${type}&nominal=${nominal}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success && data.data.length > 0) {
                    let html = '';
                    if(data.message) {
                        productMessage.innerHTML = `<i class="bi bi-info-circle me-1"></i> ${data.message}`;
                        productMessage.classList.remove('d-none');
                    }
                    data.data.forEach(item => {
                    let rpPrice = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(item.price);
                    let iconHtml = (item.icon_url && item.icon_url !== '-')
                        ? `<img src="${item.icon_url}" class="img-fluid mb-2" style="height: 40px; object-fit: contain;" alt="icon">`
                        : `<div class="mb-2 text-primary"><i class="bi bi-box" style="font-size: 24px;"></i></div>`;

                    html += `
                    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                        <div class="card h-100 border product-card cursor-pointer shadow-sm text-center" onclick="selectProduct('${item.code}', ${item.price}, this)">
                        <div class="card-body p-3 d-flex flex-column align-items-center justify-content-between">
                                ${iconHtml}
                                <div class="small fw-bold text-dark mb-1 lh-sm" title="${item.description}">${item.description}</div>
                                <div class="text-primary fw-bolder fs-6">${rpPrice}</div>
                            </div>
                        </div>
                    </div>`;
                });
                    productList.innerHTML = html;
                } else {
                    productList.innerHTML = `<div class="col-12 text-center py-3"><div class="text-muted small">Belum ada produk aktif untuk kategori ini.</div></div>`;
                }
            }).catch(err => {
                productList.innerHTML = `<div class="col-12 text-center py-3"><div class="text-danger small">Gagal memuat produk. Periksa koneksi Anda.</div></div>`;
            });
        }

        window.selectProduct = function(code, price, element) {
            document.querySelectorAll('.product-card').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            productCodeInput.value = code;
            selectedProductPrice = price;
            updateBtnSubmit('prabayar');

            // 🔥 KODE BARU: Auto scroll ke area pembayaran
                const paymentArea = document.getElementById('area-pembayaran-pra');
                if(paymentArea) {
                    paymentArea.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }
            }
        

        // ==========================================
        // 7. HANDLE SUBMIT FORM PRABAYAR
        // ==========================================
        if(formPra) {
            formPra.addEventListener('submit', function(e) {
                let cat = document.getElementById('kategori_layanan').value;
                let finalId = '';

                if(cat === 'pulsa') {
                    finalId = document.getElementById('customer_id_pulsa').value;
                } else if(cat === 'pln') {
                    finalId = document.getElementById('customer_id_pln').value;
                } else if(cat === 'ovo') {
                    finalId = document.getElementById('customer_id_ovo').value;
                } else if(cat === 'game') {
                    let pId = document.getElementById('game_player_id').value;
                    let sContainer = document.getElementById('game_server_container');
                    let sId = !sContainer.classList.contains('d-none') ? document.getElementById('game_server_id').value : '';
                    finalId = sId ? pId + sId : pId;
                }

                finalCustomerId.value = finalId;

                if(!finalId) {
                    e.preventDefault();
                    alert("Harap isi tujuan / ID Pelanggan terlebih dahulu!");
                }
            });
        }

        // ==========================================
        // 8. LOGIK KHUSUS PASCABAYAR (DINAMIS FORM INPUT)
        // ==========================================
        const pascaProductSelect = document.getElementById('pasca_product_code');
        const containerBpjsMonth = document.getElementById('container-month-bpjs');
        const inputMonth = document.getElementById('input_month');
        const containerAmountCustom = document.getElementById('container-amount-custom');
        const inputAmount = document.getElementById('input_amount');
        const containerEsamsat = document.getElementById('container-esamsat');
        const inputIdentitas = document.getElementById('input_identitas');
        const containerPbbYear = document.getElementById('container-pbb-year');
        const inputYear = document.getElementById('input_year');

        if(pascaProductSelect) {
            pascaProductSelect.addEventListener('change', function() {
                let val = this.value.toUpperCase();

                // 1. BPJS (Bulan)
                if (val.includes('BPJS')) {
                    containerBpjsMonth.classList.remove('d-none');
                    inputMonth.setAttribute('required', 'required');
                } else {
                    containerBpjsMonth.classList.add('d-none');
                    inputMonth.removeAttribute('required');
                }

                // 2. Custom Denom
                if (val.includes('MEMBER') || val.includes('PAY') || val === 'DANA' || val === 'OVO' || val === 'GOPAY' || val === 'LINKAJA') {
                    containerAmountCustom.classList.remove('d-none');
                    inputAmount.setAttribute('required', 'required');
                } else {
                    containerAmountCustom.classList.add('d-none');
                    inputAmount.removeAttribute('required');
                }

                // 3. E-Samsat (NIK KTP)
                if (val.startsWith('ESAMSAT')) {
                    containerEsamsat.classList.remove('d-none');
                    inputIdentitas.setAttribute('required', 'required');
                } else {
                    containerEsamsat.classList.add('d-none');
                    inputIdentitas.removeAttribute('required');
                }

                // 4. PBB (Tahun Pajak)
                if (val.startsWith('PBB')) {
                    containerPbbYear.classList.remove('d-none');
                    inputYear.setAttribute('required', 'required');
                } else {
                    containerPbbYear.classList.add('d-none');
                    inputYear.removeAttribute('required');
                }
            });
        }

        // ==========================================
        // 9. LOGIKA METODE PEMBAYARAN & AJAX CEK SALDO (UI BARU / MODAL)
        // ==========================================
        let globalFetchedSaldo = {
            'prabayar': 0,
            'pascabayar': 0
        };

        // Fungsi Helper untuk mengaktifkan/mematikan tombol Submit
        function updateBtnSubmit(type) {
            let container = document.getElementById(`form-${type}`);
            let btnSubmit = container.querySelector('button[type="submit"]');

            // Ambil dari input hidden sekarang
            let methodHidden = container.querySelector('.payment_method_hidden');
            let method = methodHidden ? methodHidden.value : '';

            let productCode = (type === 'prabayar') ? document.getElementById('product_code_pra').value : container.querySelector('[name="product_code"]').value;

            // Jika form prabayar tapi belum pilih produk, selalu matikan tombol
            if (type === 'prabayar' && !productCode) {
                if(btnSubmit) btnSubmit.disabled = true;
                return;
            }

            // Jika form pascabayar dan sudah pilih layanan, anggap harga bisa bayar dulu (di handle pas checkout)
            // Namun pastikan dia pilih metode payment apa
            if (method === '') {
                 if(btnSubmit) btnSubmit.disabled = true;
                 return;
            }

            if (method === 'SALDO') {
                // Kalo prabayar, kita tau harganya
                if(type === 'prabayar') {
                     if (globalFetchedSaldo[type] < selectedProductPrice || globalFetchedSaldo[type] === 0) {
                         if(btnSubmit) btnSubmit.disabled = true;
                     } else {
                         if(btnSubmit) btnSubmit.disabled = false;
                     }
                } else {
                     // Kalo pascabayar, kita gatau harganya sampai di inquiry, jadi kalo verifikasi sukses, buka aja
                     if (globalFetchedSaldo[type] > 0) {
                          if(btnSubmit) btnSubmit.disabled = false;
                     } else {
                          if(btnSubmit) btnSubmit.disabled = true;
                     }
                }
            } else {
                // Kalo pake Doku/Tripay/Dana buka aja (khusus prabayar asalkan udh pilih produk)
                if(btnSubmit) btnSubmit.disabled = false;
            }
        }

        // === START: INTEGRASI TRIPAY CHANNEL MODAL ===
        let activePaymentTarget = 'prabayar';
        let paymentModalInstance = null;
        let tripayLoaded = false;

        paymentModalInstance = new bootstrap.Modal(document.getElementById('paymentMethodModal'));

        document.querySelectorAll('.btn-payment-trigger').forEach(btn => {
            btn.addEventListener('click', function() {
                activePaymentTarget = this.dataset.target; // 'prabayar' atau 'pascabayar'
                paymentModalInstance.show();
                loadTripayChannels();
            });
        });

        async function loadTripayChannels() {
            if (tripayLoaded) return;
            const container = document.getElementById('tripayChannelsContainer');
            try {
                // Sesuaikan route jika Anda memakai nama route yang berbeda untuk pemanggilan channel
                const response = await fetch("{{ route('admin.pesanan.get_channels') }}");
                const res = await response.json();

                if (res.success && res.data.length > 0) {
                    container.innerHTML = '';
                    res.data.forEach(channel => {
                        if (channel.active) {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'list-group-item list-group-item-action p-3 d-flex align-items-center payment-option';
                            btn.dataset.value = channel.code;
                            btn.dataset.label = channel.name;
                            btn.dataset.img = channel.icon_url;

                            btn.innerHTML = `
                                <img src="${channel.icon_url}" class="me-3 border rounded p-1" style="width:40px; height:40px; object-fit:contain; background:#fff;" onerror="this.src='https://placehold.co/40'">
                                <div class="text-start">
                                    <div class="fw-bold text-dark">${channel.name}</div>
                                    <div class="text-muted small">${channel.group_name || channel.group || ''}</div>
                                </div>
                            `;
                            btn.addEventListener('click', () => selectPayment(btn));
                            container.appendChild(btn);
                        }
                    });
                    tripayLoaded = true;
                } else {
                    container.innerHTML = '<div class="p-4 text-center text-danger small">Gagal memuat saluran pembayaran.</div>';
                }
            } catch (error) {
                console.error(error);
                container.innerHTML = '<div class="p-4 text-center text-danger small">Gagal terhubung ke API Channel Pembayaran.</div>';
            }
        }

        function selectPayment(element) {
            const val = element.dataset.value;
            const label = element.dataset.label;
            const img = element.dataset.img || element.querySelector('img').src;

            // Targetkan Form yang benar
            const isPra = (activePaymentTarget === 'prabayar');
            const formId = isPra ? 'form-prabayar' : 'form-pascabayar';
            const formEl = document.getElementById(formId);

            // Update Input Hidden
            const hiddenInput = isPra ? document.getElementById('payment_method_pra') : document.getElementById('payment_method_pasca');
            hiddenInput.value = val;

            // Update UI Tombol Pemicu
            const triggerBtn = document.querySelector(`.btn-payment-trigger[data-target="${activePaymentTarget}"]`);
            triggerBtn.querySelector('.payment-name').textContent = label;
            triggerBtn.querySelector('.payment-logo').src = img;

            // Styling Modal Option yang dipilih
            document.querySelectorAll('#paymentOptionsList .payment-option').forEach(opt => opt.classList.remove('bg-primary-subtle'));
            element.classList.add('bg-primary-subtle');

            // Logika Unik untuk "Potong Saldo"
            const saldoContainer = formEl.querySelector('.saldo_payment_section');
            const waInput = saldoContainer.querySelector('.wa_pembayaran_input');
            const pinInput = saldoContainer.querySelector('.pin_pembayaran_input');
            const infoBox = saldoContainer.querySelector('.info_saldo_box');

            if (val === 'SALDO') {
                saldoContainer.classList.remove('d-none');
                waInput.setAttribute('required', 'required');
                pinInput.setAttribute('required', 'required');
                if(infoBox.classList.contains('d-none')) {
                     globalFetchedSaldo[activePaymentTarget] = 0;
                }
            } else {
                saldoContainer.classList.add('d-none');
                waInput.removeAttribute('required');
                pinInput.removeAttribute('required');
                pinInput.value = '';
            }

            paymentModalInstance.hide();
            updateBtnSubmit(activePaymentTarget);
        }

        // Daftarkan listener klik untuk opsi manual (Dana, Doku, Saldo) yang ter-render statis di modal
        document.querySelectorAll('#paymentOptionsList .payment-option').forEach(btn => {
            btn.addEventListener('click', () => selectPayment(btn));
        });
        // === END: INTEGRASI TRIPAY CHANNEL MODAL ===

        // Aksi Klik Tombol "Cek Saldo"
        document.querySelectorAll('.btn_cek_saldo_ajax').forEach(function(btn) {
            btn.addEventListener('click', function() {
                let container = this.closest('.saldo_payment_section');
                let formType = container.closest('form').id.replace('form-', '');
                let noWa = container.querySelector('.wa_pembayaran_input').value;
                let pin = container.querySelector('.pin_pembayaran_input').value;
                let infoBox = container.querySelector('.info_saldo_box');
                let errBox = container.querySelector('.error_saldo_box');
                let btnSubmit = this.closest('form').querySelector('button[type="submit"]');

                if(!noWa || !pin) {
                    errBox.innerHTML = "Harap isi Nomor WA dan PIN!";
                    errBox.classList.remove('d-none');
                    infoBox.classList.add('d-none');
                    return;
                }

                this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Cek...';
                this.disabled = true;
                errBox.classList.add('d-none');
                infoBox.classList.add('d-none');

                fetch("{{ route('ppob.verify_saldo') }}", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                    body: JSON.stringify({ no_wa: noWa, pin: pin })
                })
                .then(res => res.json())
                .then(data => {
                    this.innerHTML = 'Cek & Verifikasi Saldo';
                    this.disabled = false;

                    if (data.success) {
                        globalFetchedSaldo[formType] = data.saldo;
                        container.querySelector('.nama_akun_teks').innerText = data.nama;
                        container.querySelector('.nominal_saldo_teks').innerText = data.saldo_format;
                        infoBox.classList.remove('d-none');

                        // Cek khusus untuk Prabayar (karena harga sudah diketahui)
                        if (formType === 'prabayar') {
                            if (selectedProductPrice > 0 && data.saldo < selectedProductPrice) {
                                errBox.innerHTML = `Maaf, saldo kurang. Harga: Rp ${selectedProductPrice.toLocaleString('id-ID')}`;
                                errBox.classList.remove('d-none');
                            }
                        }

                        // Perbarui status tombol
                        updateBtnSubmit(formType);

                    } else {
                        globalFetchedSaldo[formType] = 0;
                        errBox.innerHTML = data.message;
                        errBox.classList.remove('d-none');
                        updateBtnSubmit(formType);
                    }
                }).catch(err => {
                    this.innerHTML = 'Cek & Verifikasi Saldo';
                    this.disabled = false;
                    errBox.innerHTML = "Gagal terhubung ke server.";
                    errBox.classList.remove('d-none');
                });
            });
        });

        // ==========================================
        // 10. AUTO FORMATTER NOMOR WHATSAPP
        // ==========================================
        document.querySelectorAll('.wa-formatter').forEach(function(input) {
            input.addEventListener('input', function(e) {
                let val = this.value.replace(/[^0-9]/g, '');
                if (val.startsWith('62')) {
                    val = '0' + val.substring(2);
                }
                else if (val.startsWith('8')) {
                    val = '0' + val;
                }
                this.value = val;
            });
        });

    });
</script>
@endpush

@endsection
