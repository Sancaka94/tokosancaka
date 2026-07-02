@extends('layouts.app')

@section('title', 'Buat Pesanan Baru')

@push('styles')
{{-- CSRF Token Meta Tag untuk keamanan AJAX --}}
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- Viewport Meta Tag untuk Desain Responsif --}}
<meta name="viewport" content="width=device-width, initial-scale=1.0">

{{-- Font Awesome untuk ikon --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
{{-- CSS untuk jQuery UI Autocomplete --}}
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

<style>
    /*
    ============================================
    PERBAIKAN UTAMA: Desain Modern & Responsif
    ============================================
    */
    :root {
        --primary-color: #dc3545;
        --primary-rgb: 220, 53, 69;
        --primary-color-darker: #b02a37;
        --secondary-color: #6c757d;
        --success-color: #198754;
        --body-bg: #f8f9fa;
        --text-color: #212529;
        --card-bg: #ffffff;
        --card-border-color: #e9ecef;
        --input-border-color: #ced4da;
        --input-focus-border-color: var(--primary-color);
        --font-family-sans-serif: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        --card-shadow-hover: 0 6px 16px rgba(0, 0, 0, 0.08);
        --border-radius-lg: 1rem;
        --border-radius-md: 0.5rem;
    }

    @media (min-width: 1366px) {
        .container { max-width: 95% !important; }
    }

    body {
        background-color: var(--body-bg);
        font-family: var(--font-family-sans-serif);
        color: var(--text-color);
    }

    .main-content-container {
        padding-top: 2rem !important;
        padding-bottom: 2rem;
    }

    .card {
        border: 1px solid var(--card-border-color);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--card-shadow);
        transition: all 0.3s ease-in-out;
        background-color: var(--card-bg);
    }
    .card:hover { box-shadow: var(--card-shadow-hover); }

    .card-header {
        background-color: transparent;
        border-bottom: 1px solid var(--card-border-color);
        font-weight: 600;
        font-size: 1.1rem;
        padding: 1.25rem 1.5rem;
        border-top-left-radius: calc(var(--border-radius-lg) - 1px) !important;
        border-top-right-radius: calc(var(--border-radius-lg) - 1px) !important;
    }
    .card-header .fa-icon { color: var(--primary-color); }
    .form-label { font-weight: 500; margin-bottom: 0.5rem; color: #495057; }

    .input-group-text {
        background-color: #f8f9fa;
        border-color: var(--input-border-color);
        border-right: none;
        width: 45px;
        justify-content: center;
        color: var(--secondary-color);
    }
    .form-control, .form-select {
        border-color: var(--input-border-color);
        height: calc(1.5em + 1rem + 2px);
        padding: 0.5rem 1rem;
    }
    textarea.form-control { height: auto; }
    .form-control:focus, .form-select:focus {
        border-color: var(--input-focus-border-color);
        box-shadow: 0 0 0 0.25rem rgba(var(--primary-rgb), 0.15);
    }
    .input-group:focus-within .input-group-text {
        color: var(--primary-color);
        border-color: var(--input-focus-border-color);
    }
    .input-group .form-control { border-left: none; }

    /* Autocomplete & Search Results */
    .search-results-container, .ui-autocomplete {
        position: absolute; z-index: 9999 !important; background: var(--card-bg);
        border: 1px solid var(--input-border-color); border-radius: var(--border-radius-md);
        max-height: 300px; overflow-y: auto; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        padding: 0.5rem; margin-top: 0.25rem; list-style: none;
    }
    .ui-menu-item-wrapper, .search-result-item {
        padding: 10px 15px; border-bottom: 1px solid #f3f4f6; cursor: pointer; transition: background-color 0.2s;
    }
    .search-result-item:hover, .ui-menu-item-wrapper.ui-state-active {
        background-color: rgba(var(--primary-rgb), 0.08) !important; color: var(--primary-color) !important; border-bottom: 1px solid #f3f4f6;
    }

    .btn { border-radius: var(--border-radius-md); font-weight: 600; padding: 0.6rem 1.2rem; font-size: 0.9rem; transition: all 0.2s; }
    .btn-lg { padding: 0.75rem 1.5rem; font-size: 1rem; }
    .btn-primary { background-color: var(--primary-color); border-color: var(--primary-color); }
    .btn-primary:hover { background-color: var(--primary-color-darker); transform: translateY(-2px); box-shadow: 0 6px 12px rgba(var(--primary-rgb), 0.2); }

    /* Sticky Card for Desktop */
    @media (min-width: 992px) {
        .sticky-lg-top { position: sticky; top: 2rem; z-index: 1020; }
    }

   /*
    ============================================
    Desain Modal Cek Ongkir (DIPISAH TOTAL)
    ============================================
    */
    #ongkirModal .modal-dialog, #delivereeModal .modal-dialog { max-width: 90vw; }

    .ongkir-header-row {
        display: flex; flex-direction: row; align-items: center;
        font-weight: 600; color: var(--secondary-color); font-size: 0.8rem;
        text-transform: uppercase; padding: 0 1rem; margin-bottom: 0.5rem;
    }

    .ongkir-item-card {
        display: flex; flex-direction: row; align-items: center;
        background-color: #fff; border: 1px solid var(--card-border-color);
        border-radius: 0.75rem; padding: 0.75rem 1rem; margin-bottom: 0.75rem;
        font-size: 0.9rem; transition: all 0.2s ease-in-out; width: 100%;
    }
    .ongkir-item-card:hover { box-shadow: var(--card-shadow); border-color: var(--primary-color); transform: translateY(-2px); }

    /* PEMBAGIAN 6 KOLOM TERPISAH (TOTAL 100%) */
    .col-logo    { flex: 0 0 15%; display: flex; justify-content: center; align-items: center; padding-right: 10px; }
    .col-service { flex: 0 0 20%; display: flex; flex-direction: column; justify-content: center; text-align: left; }
    .col-etd     { flex: 0 0 15%; display: flex; flex-direction: column; justify-content: center; text-align: center; }
    .col-cod     { flex: 0 0 15%; display: flex; flex-direction: column; justify-content: center; text-align: center; }
    .col-price   { flex: 0 0 20%; display: flex; flex-direction: column; justify-content: center; text-align: right; padding-right: 15px; }
    .col-action  { flex: 0 0 15%; display: flex; align-items: center; justify-content: flex-end; }

    /* Elemen di dalam kolom */
    .ongkir-logo { width: 75px; height: 35px; object-fit: contain; }
    .service-name { font-weight: 700; font-size: 0.95rem; color: var(--text-color); }
    .service-type { font-size: 0.8rem; color: var(--secondary-color); }
    .price-value .final-price { font-weight: 700; font-size: 1rem; color: var(--success-color); }
    .price-details { font-size: 0.8rem; color: var(--secondary-color); margin-top: 2px; }
    .btn-kirim { background-color: var(--primary-color); color: #fff; border-radius: 999px; font-weight: 600; font-size: 0.8rem; padding: 0.4rem 1rem; border: none; white-space: nowrap; }
    .btn-kirim:hover { background-color: var(--primary-color-darker); color: #fff; }

    /* Tampilan HP (Mobile) */
    .col-label-mobile { display: none; }
    @media (max-width: 991px) {
        .ongkir-item-card { flex-wrap: wrap; padding: 1rem; }
        .col-label-mobile { display: block; font-size: 0.75rem; color: var(--secondary-color); margin-bottom: 2px; font-weight: 500; }

        .col-logo    { flex: 0 0 25%; justify-content: flex-start; margin-bottom: 15px; }
        .col-service { flex: 0 0 75%; margin-bottom: 15px; }
        .col-etd     { flex: 0 0 50%; text-align: left; margin-bottom: 15px; align-items: flex-start; }
        .col-cod     { flex: 0 0 50%; text-align: left; margin-bottom: 15px; align-items: flex-start; }
        .col-price   { flex: 0 0 50%; text-align: left; align-items: flex-start; }
        .col-action  { flex: 0 0 50%; justify-content: center; }
    }

    #map { width: 100%; height: 380px; border-radius: var(--border-radius-lg); border: 1px solid var(--card-border-color); }
</style>

{{-- Load Mapbox GL JS Assets --}}
<script src='https://api.mapbox.com/mapbox-gl-js/v3.2.0/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v3.2.0/mapbox-gl.css' rel='stylesheet' />

{{-- TAMBAHAN: Load Mapbox Search JS Web (Versi Pintar) --}}
<script id="search-js" defer src="https://api.mapbox.com/search-js/v1.5.0/web.js"></script>

<style>
    /* CSS untuk Search Box Component yang baru */
    mapbox-search-box {
        min-width: 300px !important;
    }

    /* Posisi default Desktop */
    .mapboxgl-ctrl-top-left .mapboxgl-ctrl {
        margin-top: 12px !important;
        margin-left: 12px !important;
    }

    /* =======================================================
       KOTAK INFO RUTE UNTUK DESKTOP & TABLET
       ======================================================= */
    #route-info-box {
        top: 12px !important;
        right: 12px !important; /* Biar ada jarak dan tidak nempel garis */
    }

    /* =======================================================
       FIX POJOKAN MAP BAWAH BOCOR (Berlaku Semua Layar)
       ======================================================= */
    #map-section .card {
        overflow: hidden !important;
    }

    /* =======================================================
       PERBAIKAN RESPONSIVE KHUSUS UNTUK HP
       ======================================================= */
    @media (max-width: 768px) {
        /* 1. Sembunyikan badge "Geser pin" KHUSUS DI HP agar atasnya lega */
        .position-absolute.top-0.start-50.translate-middle-x.mt-2.z-3 {
            display: none !important;
        }

        /* 2. Search Box diperlebar karena sekarang kanan atas sudah kosong */
        .mapboxgl-ctrl-top-left .mapboxgl-ctrl {
            margin-top: 10px !important;
            margin-left: 10px !important;
        }
        mapbox-search-box {
            min-width: 250px !important;
            max-width: calc(100vw - 30px) !important;
        }

        /* 3. Kotak info rute (KM & mnt) dipindah ke KANAN BAWAH DALAM PETA */
        #route-info-box {
            top: 330px !important; /* Mentok manis di dalam peta */
            bottom: auto !important;
            right: 50px !important;
        }

        /* 4. Beri bayangan (shadow) sedikit agar KM & Mnt lebih jelas di atas peta */
        #route-info-box .bg-white {
            padding: 0.3rem 0.6rem !important;
            font-size: 0.75rem !important;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3) !important;
        }

        /* 5. Sesuaikan Tinggi Peta agar pas di layar */
        #map {
            height: 380px !important;
        }

        /* 6. Posisikan Box Rincian Sancaka Express di bawah kotak pencarian */
        #map_ongkir_summary {
            top: 60px !important;
            bottom: auto !important;
            left: 10px !important;
            right: 10px !important;
            min-width: 0 !important;
            width: calc(100% - 20px) !important;
        }

        /* 7. Penyesuaian font saat Box Ojek Online di bawah peta tampil */
        #ojek_summary_price { font-size: 1rem !important; }
        #btn-pay-ojek { padding: 0.4rem 1rem !important; font-size: 0.8rem !important; }
    }
</style>
@endpush

@section('content')

@include('layouts.partials.notifications')

<div class="container main-content-container">

    <form id="orderForm" action="{{ route('pesanan.public.store') }}" method="POST">
        @csrf
        <input type="hidden" name="latitude" id="buyer_latitude" value="">
        <input type="hidden" name="longitude" id="buyer_longitude" value="">

        {{-- ROW ATAS: PETA (Hanya muncul jika Sancaka Express Dipilih) --}}
        <div class="row mb-4" id="map-section" style="display: none;">
            <div class="col-12">
                <div class="card border-primary shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
                        <span><i class="fas fa-map-marked-alt me-2"></i> Peta Lokasi Sancaka Express</span>
                        <button type="button" id="btn-find-my-location" class="btn btn-light btn-sm fw-bold text-primary rounded-pill shadow-sm">
                            <i class="fas fa-crosshairs me-1"></i> Lokasi Saya
                        </button>
                    </div>
                    <div class="card-body p-2 bg-light border-bottom">
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check map-mode-toggle" name="map_mode" id="mode_pengirim" value="sender" checked>
                            <label class="btn btn-outline-primary fw-bold" for="mode_pengirim"><i class="fas fa-arrow-up"></i> Set Titik Pengirim</label>
                            <input type="radio" class="btn-check map-mode-toggle" name="map_mode" id="mode_penerima" value="receiver">
                            <label class="btn btn-outline-danger fw-bold" for="mode_penerima"><i class="fas fa-map-marker-alt"></i> Set Titik Penerima</label>
                        </div>
                    </div>
                    <div class="card-body p-0 position-relative">
                        <div class="position-absolute top-0 start-50 translate-middle-x mt-2 z-3" style="pointer-events: none;">
                            <span class="badge bg-dark shadow-sm px-3 py-2 rounded-pill" style="opacity: 0.85;">
                                <i class="fas fa-hand-pointer me-1"></i> Geser pin untuk titik pas
                            </span>
                        </div>

                        <!-- [AWAL TAMBAHAN] KOTAK RINCIAN ONGKIR DI PETA -->
                        <div id="map_ongkir_summary" class="position-absolute z-3 d-none bg-white p-3 shadow-lg" style="top: 70px; left: 10px; border-radius: var(--border-radius-md); border: 2px solid var(--primary-color); min-width: 260px;">
                            <h6 class="fw-bold text-primary mb-2 border-bottom pb-2" style="font-size: 0.9rem;"><i class="fas fa-box-open me-1"></i> Rincian Sancaka Express</h6>
                            <div class="d-flex justify-content-between mb-1" style="font-size: 0.8rem;">
                                <span class="text-muted">Layanan:</span>
                                <span id="map_summary_service" class="fw-bold text-dark text-end">-</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1" style="font-size: 0.8rem;">
                                <span class="text-muted">Ongkir:</span>
                                <span id="map_summary_ongkir" class="fw-bold text-dark">Rp 0</span>
                            </div>
                            <div id="map_summary_asuransi_row" class="d-flex justify-content-between mb-1 d-none" style="font-size: 0.8rem;">
                                <span class="text-muted">Asuransi:</span>
                                <span id="map_summary_asuransi" class="fw-bold text-success">Rp 0</span>
                            </div>
                            <div id="map_summary_cod_row" class="d-flex justify-content-between mb-1 d-none" style="font-size: 0.8rem;">
                                <span class="text-muted">Biaya COD:</span>
                                <span id="map_summary_cod" class="fw-bold text-danger">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                                <span class="fw-bold text-dark" style="font-size: 0.85rem;">Total Estimasi:</span>
                                <span id="map_summary_total" class="fw-bold text-primary" style="font-size: 1rem;">Rp 0</span>
                            </div>
                        </div>
                        <!-- [AKHIR TAMBAHAN] KOTAK RINCIAN ONGKIR DI PETA -->
                        <!-- ======================================================= -->
                        <!-- FITUR BARU: INFO BOX JARAK & DURASI (MUNCUL DI KANAN ATAS) -->
                        <!-- ======================================================= -->
                        <div id="route-info-box" class="position-absolute end-0 z-3 d-none" style="top: 12px; right: 12px;">
                            <div class="bg-white px-3 py-2 rounded shadow-sm border border-primary d-flex align-items-center" style="font-size: 0.9rem;">
                                <div class="text-primary fw-bold me-3" title="Total Jarak">
                                    <i class="fas fa-road me-1"></i> <span id="route-distance">0 km</span>
                                </div>
                                <div class="text-success fw-bold" title="Estimasi Waktu Tempuh">
                                    <i class="fas fa-clock me-1"></i> <span id="route-duration">0 mnt</span>
                                </div>
                            </div>
                        </div>
                        <!-- ======================================================= -->

                        <div id='map' style="border-radius: 0; border: none;"></div>

                        <!-- [AWAL TAMBAHAN] BOX TARIF OJEK ONLINE BAWAH PETA -->
                        <div id="ojek-online-summary" class="d-none bg-white p-3 border-top border-info shadow-sm" style="border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);">
                            <h6 class="fw-bold text-info mb-3"><i class="fas fa-motorcycle me-2"></i>Tarif Ojek Online Sancaka</h6>

                            <!-- Detail Titik Jemput & Tujuan -->
                            <div class="mb-3 px-2">
                                <div class="d-flex align-items-start mb-2">
                                    <i class="fas fa-arrow-up text-primary mt-1 me-2" style="font-size: 0.85rem;"></i>
                                    <div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Titik Jemput (Pengirim)</div>
                                        <div id="ojek_summary_origin_name" class="fw-bold text-dark" style="font-size: 0.9rem; line-height: 1.2;">Menentukan lokasi...</div>
                                        <div id="ojek_summary_origin_address" class="text-muted mt-1" style="font-size: 0.75rem; line-height: 1.2;">-</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-map-marker-alt text-danger mt-1 me-2" style="font-size: 0.85rem;"></i>
                                    <div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Titik Antar (Penerima)</div>
                                        <div id="ojek_summary_destination_name" class="fw-bold text-dark" style="font-size: 0.9rem; line-height: 1.2;">Menentukan lokasi...</div>
                                        <div id="ojek_summary_destination_address" class="text-muted mt-1" style="font-size: 0.75rem; line-height: 1.2;">-</div>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-2 border-zinc-200">

                            <!-- Jarak, Waktu & Harga -->
                            <div class="row text-center mb-2 mt-3">
                                <div class="col-6 border-end">
                                    <span class="text-muted" style="font-size: 0.8rem;">Jarak Tempuh</span><br>
                                    <span id="ojek_summary_distance" class="fw-bold text-dark">0 km</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted" style="font-size: 0.8rem;">Estimasi Tiba</span><br>
                                    <span id="ojek_summary_duration" class="fw-bold text-dark">0 mnt</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                <span class="fw-bold text-dark" style="font-size: 0.85rem;">Total Tarif:</span>
                                <div class="d-flex align-items-center gap-3">
                                    <span id="ojek_summary_price" class="fw-bold text-info" style="font-size: 1.2rem;">Rp 0</span>
                                    <button type="button" id="btn-pay-ojek" class="btn btn-primary rounded-pill px-4 py-2 fw-bold text-white shadow-sm" style="font-size: 0.85rem;">
                                        Bayar Sekarang <i class="fas fa-chevron-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- [AKHIR TAMBAHAN] BOX TARIF OJEK -->

                     </div>
                </div>
            </div>
        </div>

        {{-- ROW KONTEN: 3 KOLOM --}}
        <div class="row g-4">

            {{-- KOLOM KIRI: DETAIL PAKET (Sidebar Kiri) --}}
            <div class="col-12 col-lg-3">
                <div class="card sticky-lg-top">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-box-open fa-icon me-2"></i> Detail Paket
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="vendor_filter" class="form-label text-primary fw-bold">Pilihan Ekspedisi</label>
                                <select id="vendor_filter" class="form-select border-primary bg-light fw-bold text-dark">
                                    <option value="all" selected>Semua (Reguler)</option>
                                    <option value="sancaka_express" class="text-danger fw-bold">Sancaka Express</option>
                                    <option value="ojek_online" class="text-info fw-bold">Ojek Online Sancaka</option>
                                    <option value="deliveree" class="text-success fw-bold">Deliveree</option>
                                    <option value="lalamove" class="fw-bold" style="color: #f27024;">Lalamove</option>
                                    {{--  <option value="ipaymu" class="fw-bold text-white" style="background-color: #6f42c1;">iPaymu (COD Khusus)</option> --}}
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="item_description" class="form-label">Deskripsi Barang</label>
                                <input type="text" name="item_description" id="item_description" class="form-control" placeholder="Baju, Sepatu..." required>
                            </div>
                            <div class="col-12">
                                <label for="item_price" class="form-label">Harga Barang</label>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold" style="width: auto;">Rp</span>
                                    <input type="number" name="item_price" id="item_price" class="form-control" placeholder="50000" required min="1">
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="weight" class="form-label">Berat</label>
                                <div class="input-group">
                                    <input type="number" name="weight" id="weight" class="form-control" placeholder="1000" required min="1">
                                    <span class="input-group-text" style="width: auto;">gr</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label mb-2">Dimensi (Opsional)</label>
                                <div class="row g-2">
                                    <div class="col-4"><input type="number" name="length" id="length" class="form-control text-center" placeholder="P"></div>
                                    <div class="col-4"><input type="number" name="width" id="width" class="form-control text-center" placeholder="L"></div>
                                    <div class="col-4"><input type="number" name="height" id="height" class="form-control text-center" placeholder="T"></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="item_type" class="form-label">Kategori</label>
                                <select name="item_type" id="item_type" class="form-select" required>
                                    <option value="" disabled selected>Pilih...</option>
                                    <option value="1">Peralatan Elektronik & Gadget</option>
                                    <option value="2">Pakaian / Baju / Kain</option>
                                    <option value="3">Pecah Belah</option>
                                    <option value="4">Dokumen / Berkas / Buku</option>
                                    <option value="5">Peralatan Rumah Tangga</option>
                                    <option value="6">Aksesoris</option>
                                    <option value="8">Dokumen Berharga</option>
                                    <option value="9">Peralatan Kesehatan / Kecantikan / Kosmetik</option>
                                    <option value="10">Peralatan Olahraga & Hiburan</option>
                                    <option value="11">Perlengkapan Mobil & Motor</option>
                                    <option value="7">Lain-Lain</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="service_type" class="form-label">Jenis Layanan</label>
                                <select name="service_type" id="service_type" class="form-select" required>
                                    <option value="regular" selected>Regular</option>
                                    <option value="cargo">Cargo</option>
                                    <option value="instant">Instant / Sameday</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="ansuransi" class="form-label">Asuransi</label>
                                <select name="ansuransi" id="ansuransi" class="form-select" required>
                                    <option value="tidak" selected>Tidak Pakai</option>
                                    <option value="iya">Ya, Pakai Asuransi</option>
                                </select>
                                <div id="tos_asuransi_container" class="form-check mt-2 d-none">
                                    <input class="form-check-input" type="checkbox" id="tos_asuransi" name="tos_asuransi">
                                    <label class="form-check-label small text-muted" for="tos_asuransi">Setuju Kebijakan Asuransi.</label>
                                </div>
                            </div>

                            {{-- OPSI EXTRA DELIVEREE --}}
                            <div class="col-12 d-none" id="deliveree_extra_section">
                                <div class="p-2 mt-2 rounded border border-success bg-light">
                                    <h6 class="fw-bold text-success mb-2" style="font-size: 0.85rem;"><i class="fas fa-star me-1"></i> Layanan Deliveree</h6>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input deliveree-extra-toggle" type="checkbox" id="extra_helper_driver" value="1">
                                        <label class="form-check-label small" for="extra_helper_driver">Sopir Bantu Angkut <br><span id="helper_driver_price_text" class="text-success" style="font-size: 0.7rem;">(Pilih armada dulu)</span></label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input deliveree-extra-toggle" type="checkbox" id="extra_helper_extra" value="1">
                                        <label class="form-check-label small" for="extra_helper_extra">Kenek Tambahan <br><span id="helper_extra_price_text" class="text-success" style="font-size: 0.7rem;">(Pilih armada dulu)</span></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM TENGAH: FORM DATA (Pengirim & Penerima) --}}
            <div class="col-12 col-lg-6">

                {{-- Card Pengirim --}}
                <div class="card mb-4" id="card-pengirim">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-arrow-up fa-icon me-2"></i> Informasi Pengirim
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="sender_name" class="form-label">Nama Pengirim</label>
                                <div class="input-group"><span class="input-group-text"><i class="fas fa-user"></i></span><input type="text" name="sender_name" id="sender_name" class="form-control" placeholder="Cari / Ketik Baru" required></div>
                            </div>
                            <div class="col-md-6">
                                <label for="sender_phone" class="form-label">No. HP Pengirim</label>
                                <div class="input-group"><span class="input-group-text"><i class="fas fa-phone"></i></span><input type="text" name="sender_phone" id="sender_phone" class="form-control" placeholder="08..." required></div>
                            </div>
                            <div class="col-12">
                                <label for="sender_address_search" class="form-label">Cari Alamat (Kec/Kel/Kodepos)</label>
                                <div class="input-group position-relative">
                                    <span class="input-group-text"><i class="fas fa-search-location"></i></span>
                                    <input type="text" id="sender_address_search" class="form-control" placeholder="Ketik disini untuk mencari..." autocomplete="off">
                                    <div id="sender_address_results" class="search-results-container d-none w-100"></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="sender_address" class="form-label">Detail Alamat Lengkap</label>
                                <div class="input-group"><span class="input-group-text align-items-start pt-2"><i class="fas fa-map-marked-alt"></i></span><textarea name="sender_address" id="sender_address" rows="2" class="form-control" placeholder="Contoh: Jl. Merdeka No. 10..." required></textarea></div>
                            </div>
                            <div class="col-12">
                                <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="save_sender" id="save_sender" value="1"><label class="form-check-label text-muted small" for="save_sender">Simpan data pengirim ke buku alamat</label></div>
                            </div>
                        </div>
                        <input type="hidden" name="pengirim_id" id="pengirim_id">
                        <input type="hidden" name="sender_lat" id="sender_lat"><input type="hidden" name="sender_lng" id="sender_lng">
                        <input type="hidden" name="sender_province" id="sender_province" required><input type="hidden" name="sender_regency" id="sender_regency" required>
                        <input type="hidden" name="sender_district" id="sender_district" required><input type="hidden" name="sender_village" id="sender_village" required>
                        <input type="hidden" name="sender_postal_code" id="sender_postal_code" required>
                        <input type="hidden" name="sender_district_id" id="sender_district_id" required><input type="hidden" name="sender_subdistrict_id" id="sender_subdistrict_id" required>
                    </div>
                </div>

                {{-- Card Penerima --}}
                <div class="card" id="card-penerima">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-map-marker-alt fa-icon me-2"></i> Informasi Penerima
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="receiver_name" class="form-label">Nama Penerima</label>
                                <div class="input-group"><span class="input-group-text"><i class="fas fa-user-friends"></i></span><input type="text" name="receiver_name" id="receiver_name" class="form-control" placeholder="Cari / Ketik Baru" required></div>
                            </div>
                            <div class="col-md-6">
                                <label for="receiver_phone" class="form-label">No. HP Penerima</label>
                                <div class="input-group"><span class="input-group-text"><i class="fas fa-mobile-alt"></i></span><input type="text" name="receiver_phone" id="receiver_phone" class="form-control" placeholder="08..." required></div>
                            </div>
                            <div class="col-12">
                                <label for="receiver_address_search" class="form-label">Cari Alamat (Kec/Kel/Kodepos)</label>
                                <div class="input-group position-relative">
                                    <span class="input-group-text"><i class="fas fa-search-location"></i></span>
                                    <input type="text" id="receiver_address_search" class="form-control" placeholder="Ketik disini untuk mencari..." autocomplete="off">
                                    <div id="receiver_address_results" class="search-results-container d-none w-100"></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="receiver_address" class="form-label">Detail Alamat Lengkap</label>
                                <div class="input-group"><span class="input-group-text align-items-start pt-2"><i class="fas fa-map-marked-alt"></i></span><textarea name="receiver_address" id="receiver_address" rows="2" class="form-control" placeholder="Contoh: Jl. Pahlawan No. 21..." required></textarea></div>
                            </div>
                            <div class="col-12">
                                <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="save_receiver" id="save_receiver" value="1"><label class="form-check-label text-muted small" for="save_receiver">Simpan data penerima ke buku alamat</label></div>
                            </div>
                        </div>
                        <input type="hidden" name="penerima_id" id="penerima_id">
                        <input type="hidden" name="receiver_lat" id="receiver_lat"><input type="hidden" name="receiver_lng" id="receiver_lng">
                        <input type="hidden" name="receiver_province" id="receiver_province" required><input type="hidden" name="receiver_regency" id="receiver_regency" required>
                        <input type="hidden" name="receiver_district" id="receiver_district" required><input type="hidden" name="receiver_village" id="receiver_village" required>
                        <input type="hidden" name="receiver_postal_code" id="receiver_postal_code" required>
                        <input type="hidden" name="receiver_district_id" id="receiver_district_id" required><input type="hidden" name="receiver_subdistrict_id" id="receiver_subdistrict_id" required>
                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN: PEMBAYARAN (Sidebar Kanan) --}}
            <div class="col-12 col-lg-3" id="card-pembayaran">
                <div class="card sticky-lg-top">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-wallet fa-icon me-2"></i> Pembayaran
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="selected_expedition_display" class="form-label text-primary fw-bold">Cek Tarif / Kurir</label>
                                <input type="text" id="selected_expedition_display" class="form-control text-start fw-bold" placeholder="Klik untuk Pilih Kurir" readonly required style="cursor:pointer; background-color: #e9ecef;">
                                <input type="hidden" name="expedition" id="expedition" required>

                                <input type="hidden" id="selected_shipping_cost" value="0">
                                <input type="hidden" id="selected_insurance_cost" value="0">
                                <input type="hidden" id="selected_cod_fee" value="0">
                                <input type="hidden" id="selected_helper_driver_fee" value="0">
                                <input type="hidden" id="selected_helper_extra_fee" value="0">
                                <input type="hidden" name="deliveree_helper_driver_id" id="deliveree_helper_driver_id">
                                <input type="hidden" name="deliveree_helper_extra_id" id="deliveree_helper_extra_id">
                            </div>

                            <div class="col-12">
                                <div class="p-3 rounded" style="background-color: #f8f9fa; border: 1px dashed #ced4da;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted" style="font-size: 0.85rem;">Harga Barang</span>
                                        <span id="summary_item_price" class="fw-bold text-secondary" style="font-size: 0.85rem;">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted" style="font-size: 0.85rem;">Tarif Ongkir</span>
                                        <span id="summary_shipping_cost" class="fw-bold text-secondary" style="font-size: 0.85rem;">Rp 0</span>
                                    </div>
                                    <div id="summary_insurance_row" class="justify-content-between align-items-center mb-1 d-none">
                                        <span class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-shield-alt text-success me-1"></i> Asuransi</span>
                                        <span id="summary_insurance_cost" class="fw-bold text-success" style="font-size: 0.85rem;">Rp 0</span>
                                    </div>
                                    <div id="summary_helper_driver_row" class="justify-content-between align-items-center mb-1 d-none">
                                        <span class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-user text-success me-1"></i> Bantuan Sopir</span>
                                        <span id="summary_helper_driver_cost" class="fw-bold text-success" style="font-size: 0.85rem;">Rp 0</span>
                                    </div>
                                    <div id="summary_helper_extra_row" class="justify-content-between align-items-center mb-2 d-none">
                                        <span class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-user-plus text-success me-1"></i> Kenek Extra</span>
                                        <span id="summary_helper_extra_cost" class="fw-bold text-success" style="font-size: 0.85rem;">Rp 0</span>
                                    </div>

                                    <hr class="my-2" style="border-color: #ced4da;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-dark">Total</span>
                                        <span id="summary_total_cost" class="fw-bold text-danger" style="font-size: 1.15rem;">Rp 0</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="paymentMethodButton" class="form-label">Metode Pembayaran</label>
                                <div id="paymentMethodButton" class="form-control d-flex justify-content-between align-items-center" style="cursor: pointer;">
                                    <div class="d-flex align-items-center">
                                        <i id="defaultPaymentIcon" class="fas fa-credit-card fa-lg me-3 text-muted"></i>
                                        <img id="selectedPaymentLogo" src="" alt="Logo" class="me-2 d-none" style="width:40px; height:20px; object-fit:contain;">
                                        <span id="selectedPaymentName" style="font-size: 0.9rem;">Pilih Bayar...</span>
                                    </div>
                                    <i class="fas fa-chevron-down text-muted"></i>
                                </div>
                                <input type="hidden" name="payment_method" id="payment_method" required>
                            </div>

                            <div class="col-12 mt-3">
                                <button type="button" id="confirmBtn" class="btn btn-primary w-100 py-2"><i class="fas fa-paper-plane me-2"></i>Buat Pesanan</button>
                                <button type="button" id="cekOngkirWaBtn" class="btn btn-outline-success w-100 mt-2 py-2"><i class="fab fa-whatsapp me-2"></i>Tanya via WA</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

{{-- Modal Pilihan Ekspedisi --}}
<div class="modal fade" id="ongkirModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-shipping-fast me-2"></i>Pilihan Ekspedisi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="ongkirModalBody">
                <div id="ongkirResultsContainer"></div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL KHUSUS DELIVEREE --}}
<div class="modal fade" id="delivereeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: #00b14f;">
                <h5 class="modal-title fw-bold"><i class="fas fa-truck-moving me-2"></i>Pilihan Armada Deliveree</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background-color: #f4f7f6;">
                <div id="delivereeResultsContainer" class="row g-3"></div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL KHUSUS LALAMOVE --}}
<div class="modal fade" id="lalamoveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: #f27024;">
                <h5 class="modal-title fw-bold"><i class="fas fa-motorcycle me-2"></i>Pilihan Armada Lalamove</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background-color: #fffaf7;">
                <div id="lalamoveResultsContainer" class="row g-3 justify-content-center"></div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL KHUSUS IPAYMU --}}
<div class="modal fade" id="ipaymuModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: #6f42c1;">
                <h5 class="modal-title fw-bold"><i class="fas fa-shipping-fast me-2"></i>Pilihan Kurir iPaymu</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background-color: #f8f9fa;">
                <div id="ipaymuResultsContainer" class="row g-3 justify-content-center"></div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Metode Pembayaran --}}
<div class="modal fade" id="paymentMethodModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-credit-card me-2 text-danger"></i>Pilih Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <ul id="paymentOptionsList" class="list-group list-group-flush" style="cursor: pointer;">
                    @auth
                    <li class="list-group-item bg-light fw-bold text-muted border-bottom-0" style="font-size: 0.75rem; text-transform: uppercase;">Dompet Sancaka</li>
                    <li class="list-group-item list-group-item-action d-flex align-items-center requires-pin" data-value="Potong Saldo" data-label="Potong Saldo" data-real-balance="{{ Auth::user()->saldo ?? 0 }}">
                        <img src="{{ asset('public/assets/saldo.png') }}" class="me-3" style="width: 40px; height: 40px; object-fit: contain;">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">Potong Saldo</div>
                            <div class="text-muted balance-text" style="font-size: 0.75rem;" data-prefix="Tersedia: ">Tersedia: Rp *** <i class="fas fa-lock ms-1" style="font-size: 0.7rem;"></i></div>
                        </div>
                    </li>
                    @endauth

                    <li class="list-group-item bg-light fw-bold text-muted border-top border-bottom-0" style="font-size: 0.75rem; text-transform: uppercase;">Bayar Di Tempat (Otomatis)</li>
                    <li class="list-group-item list-group-item-action d-flex align-items-center cod-payment-option" data-value="COD" data-label="COD Ongkir">
                        <img src="{{ asset('public/assets/cod.png') }}" class="me-3" style="width: 40px; height: 40px; object-fit: contain;">
                        <div class="fw-bold text-dark" style="font-size: 0.95rem;">COD Ongkir</div>
                    </li>
                    <li class="list-group-item list-group-item-action d-flex align-items-center cod-payment-option" data-value="CODBARANG" data-label="COD Barang + Ongkir">
                        <img src="{{ asset('public/assets/cod.png') }}" class="me-3" style="width: 40px; height: 40px; object-fit: contain;">
                        <div class="fw-bold text-dark" style="font-size: 0.95rem;">COD Barang + Ongkir</div>
                    </li>

                    @auth
                    <li class="list-group-item bg-light fw-bold text-muted border-top border-bottom-0" style="font-size: 0.75rem; text-transform: uppercase;">E-Wallet Auto Debit</li>
                    @php
                        $userDanaToken = Auth::user()->dana_access_token ?? null;
                        $userDanaBalance = Auth::user()->dana_user_balance ?? 0;
                        $hasDanaBinding = !empty($userDanaToken);
                    @endphp

                    @if($hasDanaBinding)
                        <li class="list-group-item list-group-item-action d-flex align-items-center requires-pin" data-value="DANA_BINDING" data-label="DANA Auto-Debit" style="background-color: #f0f7ff;" data-real-balance="{{ $userDanaBalance }}">
                            <img src="{{ asset('public/assets/dana.webp') }}" class="me-3" style="width: 40px; height: 40px; object-fit: contain;">
                            <div class="flex-grow-1">
                                <div class="fw-bold text-primary" style="font-size: 0.95rem;">DANA Auto-Debit</div>
                                <div class="text-muted balance-text" style="font-size: 0.75rem;" data-prefix="Saldo DANA: ">Saldo DANA: Rp *** <i class="fas fa-lock ms-1" style="font-size: 0.7rem;"></i></div>
                            </div>
                            <span class="badge bg-secondary rounded-pill status-badge"><i class="fas fa-lock me-1"></i> Terkunci</span>
                        </li>
                    @else
                        <li class="list-group-item d-flex align-items-center justify-content-between" style="background-color: #fafafa; border-style: dashed;">
                            <div class="d-flex align-items-center">
                                <img src="{{ asset('public/assets/dana.webp') }}" class="me-3" style="width: 40px; height: 40px; object-fit: contain; filter: grayscale(100%); opacity: 0.6;">
                                <div>
                                    <div class="fw-bold text-muted" style="font-size: 0.95rem;">DANA Auto-Debit</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Bayar 1-klik tanpa PIN</div>
                                </div>
                            </div>
                            <a href="{{ url('/dana/start-binding') }}" class="btn btn-sm btn-primary" style="font-size: 0.75rem;">Hubungkan</a>
                        </li>
                    @endif
                    @endauth

                    <li class="list-group-item bg-light fw-bold text-muted border-top border-bottom-0" style="font-size: 0.75rem; text-transform: uppercase;">Payment Gateway Terintegrasi</li>
                    <li class="list-group-item list-group-item-action d-flex align-items-center gateway-option" data-value="DOKU_JOKUL" data-label="Doku (Rekomendasi)">
                        <img src="{{ asset('public/assets/doku.png') }}" class="me-3 border rounded p-1 bg-white" style="width: 40px; height: 40px; object-fit: contain;">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">DOKU (Rekomendasi)</div>
                            <div class="text-muted" style="font-size: 0.75rem;">VA, QRIS, E-Wallet, CC</div>
                        </div>
                    </li>
                    <li class="list-group-item list-group-item-action d-flex align-items-center gateway-option" data-value="PAYPAL" data-label="PayPal / Credit Card">
                        <img src="https://tokosancaka.com/public/assets/paypal.png" class="me-3 border rounded p-1 bg-white" style="width: 40px; height: 40px; object-fit: contain;" onerror="this.src='https://placehold.co/40x40/EFEFEF/AAAAAA?text=PP'">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">PayPal / Kartu Kredit</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Pembayaran Global</div>
                        </div>
                    </li>
                    <li class="list-group-item list-group-item-action d-flex align-items-center gateway-option" data-value="DANA" data-label="DANA (Web Checkout)">
                        <img src="{{ asset('public/assets/dana.webp') }}" class="me-3 border rounded p-1 bg-white" style="width: 40px; height: 40px; object-fit: contain;">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">DANA (Checkout)</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Arah ke aplikasi DANA</div>
                        </div>
                    </li>
                    <li class="list-group-item list-group-item-action d-flex align-items-center gateway-option" data-value="IPAYMU" data-label="iPaymu">
                        <img src="https://tokosancaka.com/public/assets/ipaymu.jpg" class="me-3 border rounded p-1 bg-white" style="width: 40px; height: 40px; object-fit: contain;" onerror="this.src='https://placehold.co/40x40/EFEFEF/AAAAAA?text=IP'">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">iPaymu (VA & QRIS)</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Transfer Bank, E-Wallet</div>
                        </div>
                    </li>

                    <li class="list-group-item bg-light fw-bold text-muted border-top border-bottom-0" style="font-size: 0.75rem; text-transform: uppercase;">Saluran Pembayaran Lainnya (Tripay)</li>
                    <div id="dynamicPaymentChannels">
                        <div class="p-4 text-center text-muted">
                            <div class="spinner-border spinner-border-sm text-danger mb-2" role="status"></div>
                            <br><small>Menarik API Saluran Pembayaran...</small>
                        </div>
                    </div>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Modal Input PIN --}}
<div class="modal fade" id="pinModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg border-0" style="border-radius: 1rem;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold mx-auto text-dark">Masukkan PIN Anda</h5>
                <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pt-2 pb-4">
                <p class="text-muted small mb-4">Verifikasi transaksi ini menggunakan 6 digit PIN Sancaka Anda.</p>
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <input type="password" class="form-control text-center pin-digit" maxlength="1" style="width: 45px; height: 50px; font-size: 24px; font-weight: bold; border-radius: 0.5rem;" autocomplete="off" inputmode="numeric">
                    <input type="password" class="form-control text-center pin-digit" maxlength="1" style="width: 45px; height: 50px; font-size: 24px; font-weight: bold; border-radius: 0.5rem;" autocomplete="off" inputmode="numeric">
                    <input type="password" class="form-control text-center pin-digit" maxlength="1" style="width: 45px; height: 50px; font-size: 24px; font-weight: bold; border-radius: 0.5rem;" autocomplete="off" inputmode="numeric">
                    <input type="password" class="form-control text-center pin-digit" maxlength="1" style="width: 45px; height: 50px; font-size: 24px; font-weight: bold; border-radius: 0.5rem;" autocomplete="off" inputmode="numeric">
                    <input type="password" class="form-control text-center pin-digit" maxlength="1" style="width: 45px; height: 50px; font-size: 24px; font-weight: bold; border-radius: 0.5rem;" autocomplete="off" inputmode="numeric">
                    <input type="password" class="form-control text-center pin-digit" maxlength="1" style="width: 45px; height: 50px; font-size: 24px; font-weight: bold; border-radius: 0.5rem;" autocomplete="off" inputmode="numeric">
                </div>
                <input type="hidden" id="full_pin_value">
                <div id="pin_error_msg" class="text-danger small fw-bold mb-3 d-none"><i class="fas fa-exclamation-circle me-1"></i> PIN Salah!</div>
                <button type="button" id="btnVerifyPin" class="btn btn-danger w-100 rounded-pill py-2 fw-bold" style="background-color: var(--primary-color);">Verifikasi & Bayar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function loadScript(url, callback) {
        var script = document.createElement("script");
        script.type = "text/javascript";
        script.src = url;
        script.onload = callback;
        document.head.appendChild(script);
    }

    window.onload = function() {
        if (window.jQuery) {
            loadScript("https://code.jquery.com/ui/1.13.2/jquery-ui.min.js", function() {
                initSancakaScripts();
            });
        } else {
            console.error("jQuery tidak terdeteksi! Pastikan layout utama memuat jQuery.");
        }
    };

    function initSancakaScripts() {
        let isPinVerified = false;
        let pendingPaymentSelection = null;

        // ==============================================================================
        // 1. INTEGRASI MAPBOX & LOGIKA MENYEMBUNYIKAN PETA
        // ==============================================================================
        const mapboxToken = '{{ \App\Models\Api::getValue("MAPBOX_PUBLIC_TOKEN", "global") }}';
        let map, senderMarker, receiverMarker;

        // FUNGSI TOGGLE PETA HANYA UNTUK SANCAKA EXPRESS
        function toggleMapVisibility() {
            const vendor = $('#vendor_filter').val();
            if (vendor === 'sancaka_express' || vendor === 'ojek_online') {
                $('#map-section').hide().removeClass('d-none').slideDown(300);
                if (map) {
                    setTimeout(() => map.resize(), 300); // Pastikan map dirender sesuai width baru
                }

                // Tampilkan box Ojek jika vendor ojek dipilih
                if (vendor === 'ojek_online') {
                    $('#map_ongkir_summary').addClass('d-none');
                    $('#ojek-online-summary').removeClass('d-none');
                } else {
                    $('#ojek-online-summary').addClass('d-none');
                }
            } else {
                $('#map-section').slideUp(300, function() {
                    $(this).addClass('d-none');
                });
            }
        }

        // Pasang Event Listener
        $('#vendor_filter').on('change', toggleMapVisibility);

        if (mapboxToken && mapboxToken.trim() !== '') {
            mapboxgl.accessToken = mapboxToken;
            map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/standard',
                center: [111.4558, -7.4025],
                zoom: 15, pitch: 45, bearing: -15, attributionControl: false,
                config: { basemap: { lightPreset: "dusk", show3dObjects: true, showPedestrianRoads: false } }
            });

            map.addControl(new mapboxgl.NavigationControl(), 'bottom-right');
            const geolocateControl = new mapboxgl.GeolocateControl({ positionOptions: { enableHighAccuracy: true }, trackUserLocation: false, showUserHeading: true });
            map.addControl(geolocateControl, 'bottom-right');

            // ==========================================
            // FITUR BARU: MAPBOX SEARCH JS (Pencarian Cerdas)
            // ==========================================
            const searchBox = new MapboxSearchBox();
            searchBox.accessToken = mapboxgl.accessToken;
            searchBox.options = {
                language: 'id',
                country: 'id'
            };

            // Set mapboxgl library agar tersambung dengan ekosistem peta
            searchBox.mapboxgl = mapboxgl;
            searchBox.marker = false; // Matikan pin bawaan karena kita pakai pin biru/merah sendiri

            // Masukkan kotak pencarian ke sudut kiri atas peta
            map.addControl(searchBox, 'top-left');

            // Event saat pembeli mengeklik salah satu saran tempat
            searchBox.addEventListener('retrieve', function(e) {
                const feature = e.detail.features ? e.detail.features[0] : e.detail;
                if (!feature || !feature.geometry) return;

                const coords = feature.geometry.coordinates; // Format: [lng, lat]

                // MENGAMBIL NAMA TEMPAT DAN ALAMAT TERPISAH DARI MAPBOX
                const shortName = feature.properties.name || feature.text || "Lokasi Terpilih";
                const fullAddress = feature.properties.place_formatted || feature.place_name || "";
                const combinedAddress = shortName + (fullAddress ? ', ' + fullAddress : '');

                map.flyTo({ center: coords, zoom: 16, essential: true });

                const activeMode = $('input[name="map_mode"]:checked').val();

                if (activeMode === 'receiver') {
                    $('#receiver_address_search').val(combinedAddress);
                    $('#ojek_summary_destination_name').text(shortName);
                    $('#ojek_summary_destination_address').text(fullAddress);

                    receiverMarker.setLngLat(coords);
                    updateInputsFromMarker('receiver', receiverMarker, true); // TRUE = Kunci namanya, jangan ditimpa!
                } else {
                    $('#sender_address_search').val(combinedAddress);
                    $('#ojek_summary_origin_name').text(shortName);
                    $('#ojek_summary_origin_address').text(fullAddress);

                    senderMarker.setLngLat(coords);
                    updateInputsFromMarker('sender', senderMarker, true); // TRUE = Kunci namanya, jangan ditimpa!
                }

                getRoute(senderMarker.getLngLat(), receiverMarker.getLngLat());
            });

            const senderPopup = new mapboxgl.Popup({ offset: 25, closeButton: false, closeOnClick: false }).setHTML('<div class="fw-bold text-primary mb-1"><i class="fas fa-arrow-up"></i> PENGIRIM</div><small class="text-muted">Tarik pin biru ini</small>');
            const receiverPopup = new mapboxgl.Popup({ offset: 25, closeButton: false, closeOnClick: false }).setHTML('<div class="fw-bold text-danger mb-1"><i class="fas fa-map-marker-alt"></i> PENERIMA</div><small class="text-muted">Tarik pin merah ini</small>');

            senderMarker = new mapboxgl.Marker({ color: '#007bff', draggable: true }).setLngLat([111.4558, -7.4025]).setPopup(senderPopup).addTo(map);
            receiverMarker = new mapboxgl.Marker({ color: '#dc3545', draggable: true }).setLngLat([111.4650, -7.4100]).setPopup(receiverPopup).addTo(map);
            senderMarker.togglePopup(); receiverMarker.togglePopup();

           // ==========================================
            // PENAHAN KAMERA AGAR MAP TIDAK LONCAT SAAT ISI FORM
            // ==========================================
            window.isTypingForm = false;
            $('form input, form textarea, form select').on('focus', function() {
                window.isTypingForm = true; // Jika user ngeklik form, kunci pergerakan kamera peta
            }).on('blur', function() {
                setTimeout(() => { window.isTypingForm = false; }, 1000); // Lepas kunci setelah 1 detik
            });


            // ==========================================
            // UPDATE: FUNGSI RUTE + HITUNG KM & MENIT
            // ==========================================
            function getRoute(start, end) {
                const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${start.lng},${start.lat};${end.lng},${end.lat}?geometries=geojson&access_token=${mapboxgl.accessToken}`;

                $.ajax({
                    url: url,
                    success: function(json) {
                        if(json.routes && json.routes.length > 0) {
                            const route = json.routes[0];
                            const routeGeometry = route.geometry;

                            // 1. Update data garis ular di peta
                            if (map.getSource('route')) {
                                map.getSource('route').setData({
                                    type: 'Feature',
                                    properties: {},
                                    geometry: routeGeometry
                                });
                            }

                            // 2. HITUNG & TAMPILKAN JARAK SERTA WAKTU
                            const distanceKm = (route.distance / 1000).toFixed(1); // Ubah meter ke KM (1 desimal)
                            const durationMin = Math.round(route.duration / 60); // Ubah detik ke Menit pembulatan

                            $('#route-distance').text(distanceKm + ' km');
                            $('#route-duration').text(durationMin + ' mnt');
                            $('#route-info-box').removeClass('d-none'); // Tampilkan kotaknya

                           // --- [AWAL TAMBAHAN] KALKULASI OJEK ONLINE SECARA LIVE ---
                            const tarifDasarOjek = parseInt('{{ \App\Models\Api::getValue("SANCAKA_OJEK_BASE_FARE", "global", 5000) }}');
                            const tarifPerKmOjek = parseInt('{{ \App\Models\Api::getValue("SANCAKA_OJEK_PER_KM", "global", 2500) }}');

                            let totalTarifOjek = tarifDasarOjek + (parseFloat(distanceKm) * tarifPerKmOjek);
                            totalTarifOjek = Math.ceil(totalTarifOjek / 500) * 500; // Pembulatan ke atas kelipatan 500 perak

                            // Ambil nama tempat dari kotak pencarian form
                            let originName = $('#sender_address_search').val() || $('#sender_village').val() || 'Menentukan lokasi...';
                            let destName = $('#receiver_address_search').val() || $('#receiver_village').val() || 'Menentukan lokasi...';

                            $('#ojek_summary_origin').text(originName);
                            $('#ojek_summary_destination').text(destName);

                            $('#ojek_summary_distance').text(distanceKm + ' km');
                            $('#ojek_summary_duration').text(durationMin + ' mnt');
                            $('#ojek_summary_price').text(formatRupiah(totalTarifOjek));
                            // --- [AKHIR TAMBAHAN] ---

                            // 3. Zoom otomatis (Fit Bounds) HANYA jika user tidak sedang mengisi form di bawah
                            if (!window.isTypingForm) {
                                const coordinates = routeGeometry.coordinates;
                                const bounds = new mapboxgl.LngLatBounds(coordinates[0], coordinates[0]);
                                for (const coord of coordinates) {
                                    bounds.extend(coord);
                                }
                                map.fitBounds(bounds, { padding: 60, maxZoom: 16 });
                            }
                        }
                    }
                });
            }

           function reverseGeocode(lat, lng, prefix) {
                // 1. Tampilkan animasi loading saat pin digeser
                if (prefix === 'sender') {
                    $('#ojek_summary_origin_name').html('<i class="fas fa-spinner fa-spin text-primary"></i> Menarik data...');
                } else {
                    $('#ojek_summary_destination_name').html('<i class="fas fa-spinner fa-spin text-danger"></i> Menarik data...');
                }

                $.ajax({
                    url: `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,
                    success: function(data) {
                        if (data && data.display_name) {
                            // Update input text pencarian di form atas
                            $(`#${prefix}_address_search`).val(data.display_name).addClass('is-valid');

                            // =======================================================
                            // LOGIKA CERDAS: PARSING NAMA TEMPAT vs ALAMAT LENGKAP
                            // =======================================================
                            let shortName = "Lokasi Pin Peta";
                            let detailAddress = data.display_name;

                            if (data.address) {
                                // Prioritaskan urutan pencarian nama: Gedung/Toko -> Fasilitas Umum -> Jalan -> Desa
                                shortName = data.address.amenity ||
                                            data.address.building ||
                                            data.address.shop ||
                                            data.address.office ||
                                            data.address.tourism ||
                                            data.address.road ||
                                            data.address.neighbourhood ||
                                            data.address.village ||
                                            "Titik Kordinat";
                            }

                            // Update text di kotak UI Ojek Online
                            if (prefix === 'sender') {
                                $('#ojek_summary_origin_name').text(shortName);
                                $('#ojek_summary_origin_address').text(detailAddress);
                            } else if (prefix === 'receiver') {
                                $('#ojek_summary_destination_name').text(shortName);
                                $('#ojek_summary_destination_address').text(detailAddress);
                            }

                            // Isi otomatis form hidden (Kecamatan, Kelurahan, Kodepos)
                            if (data.address) {
                                const desa = data.address.village || data.address.neighbourhood || '';
                                // Di OSM, nama kecamatan bisa masuk town, city_district, atau municipality
                                const kec = data.address.town || data.address.city_district || data.address.municipality || '';
                                const kab = data.address.city || data.address.county || '';
                                const prov = data.address.state || '';
                                const kodepos = data.address.postcode || '';

                                if(desa) $(`#${prefix}_village`).val(desa);
                                if(kec) $(`#${prefix}_district`).val(kec);
                                if(kab) $(`#${prefix}_regency`).val(kab);
                                if(prov) $(`#${prefix}_province`).val(prov);
                                if(kodepos) $(`#${prefix}_postal_code`).val(kodepos);
                            }
                        }
                    },
                    error: function() {
                        // Jika koneksi internet lemot/putus
                        if (prefix === 'sender') {
                            $('#ojek_summary_origin_name').text("Titik Pengirim");
                            $('#ojek_summary_origin_address').text("Gagal memuat alamat (Masalah Jaringan)");
                        } else {
                            $('#ojek_summary_destination_name').text("Titik Penerima");
                            $('#ojek_summary_destination_address').text("Gagal memuat alamat (Masalah Jaringan)");
                        }
                    }
                });
            }

            function updateInputsFromMarker(prefix, marker, keepName = false) {
                const lngLat = marker.getLngLat();
                $(`#${prefix}_lat`).val(lngLat.lat);
                $(`#${prefix}_lng`).val(lngLat.lng).trigger('change');

                // Teruskan perintah keepName ke Nominatim
                reverseGeocode(lngLat.lat, lngLat.lng, prefix, keepName);
            }

           // Update saat marker selesai digeser manual
            senderMarker.on('dragend', () => {
                updateInputsFromMarker('sender', senderMarker);
                getRoute(senderMarker.getLngLat(), receiverMarker.getLngLat()); // <--- Update Garis
            });

            receiverMarker.on('dragend', () => {
                updateInputsFromMarker('receiver', receiverMarker);
                getRoute(senderMarker.getLngLat(), receiverMarker.getLngLat()); // <--- Update Garis
            });

            // Update saat peta di-klik
            map.on('click', function(e) {
                const activeMode = $('input[name="map_mode"]:checked').val();
                if (activeMode === 'receiver') {
                    receiverMarker.setLngLat(e.lngLat);
                    updateInputsFromMarker('receiver', receiverMarker);
                } else {
                    senderMarker.setLngLat(e.lngLat);
                    updateInputsFromMarker('sender', senderMarker);
                }

                getRoute(senderMarker.getLngLat(), receiverMarker.getLngLat()); // <--- Update Garis
            });

            // Tambahkan juga di dalam event geolocateControl (saat GPS nyala pertama kali)
            geolocateControl.on('geolocate', function(e) {
                const lon = e.coords.longitude;
                const lat = e.coords.latitude;
                $('#btn-find-my-location').html('<i class="fas fa-crosshairs me-1"></i> Lokasi Saya');
                $('#buyer_latitude').val(lat);
                $('#buyer_longitude').val(lon);

                const activeMode = $('input[name="map_mode"]:checked').val();
                if (activeMode !== 'receiver') {
                    senderMarker.setLngLat([lon, lat]);
                    updateInputsFromMarker('sender', senderMarker);
                } else {
                    receiverMarker.setLngLat([lon, lat]);
                    updateInputsFromMarker('receiver', receiverMarker);
                }

                // Update Garis setelah dapet GPS
                getRoute(senderMarker.getLngLat(), receiverMarker.getLngLat());
            });

            // Tambahkan juga di window.syncMarkerFromInputs (saat user cari alamat dari text input)
            window.syncMarkerFromInputs = function(prefix) {
                const lat = parseFloat($(`#${prefix}_lat`).val());
                const lng = parseFloat($(`#${prefix}_lng`).val());
                if (lat && lng) {
                    if (prefix === 'sender') {
                        senderMarker.setLngLat([lng, lat]);
                    } else {
                        receiverMarker.setLngLat([lng, lat]);
                    }
                    // Update garis rute
                    getRoute(senderMarker.getLngLat(), receiverMarker.getLngLat());
                }
            };

            $('.map-mode-toggle').on('change', function() {
                const mode = $(this).val();
                if(mode === 'receiver') {
                    map.flyTo({ center: receiverMarker.getLngLat(), zoom: 16 });
                } else {
                    map.flyTo({ center: senderMarker.getLngLat(), zoom: 16 });
                }
            });

            $('#btn-find-my-location').on('click', function(e) {
                e.preventDefault(); geolocateControl.trigger();
                $(this).html('<i class="fas fa-spinner fa-spin me-1"></i> Mencari...');
            });

            geolocateControl.on('geolocate', function(e) {
                const lon = e.coords.longitude, lat = e.coords.latitude;
                $('#btn-find-my-location').html('<i class="fas fa-crosshairs me-1"></i> Lokasi Saya');
                $('#buyer_latitude').val(lat); $('#buyer_longitude').val(lon);

                const activeMode = $('input[name="map_mode"]:checked').val();
                if (activeMode !== 'receiver') {
                    senderMarker.setLngLat([lon, lat]); updateInputsFromMarker('sender', senderMarker);
                } else {
                    receiverMarker.setLngLat([lon, lat]); updateInputsFromMarker('receiver', receiverMarker);
                }
            });

            map.on('load', function() {
                // 1. Buat penampung data (source) kosong untuk rute
                map.addSource('route', {
                    'type': 'geojson',
                    'data': {
                        'type': 'Feature',
                        'properties': {},
                        'geometry': { 'type': 'LineString', 'coordinates': [] }
                    }
                });

                // 2. Tambahkan Layer Outline (Bayangan garis biar tebal kayak Google Maps)
                map.addLayer({
                    'id': 'route-casing',
                    'type': 'line',
                    'source': 'route',
                    'layout': { 'line-join': 'round', 'line-cap': 'round' },
                    'paint': { 'line-color': '#1e40af', 'line-width': 8, 'line-opacity': 0.5 }
                });

                // 3. Tambahkan Layer Rute Utama (Garis biru cerah di atas outline)
                map.addLayer({
                    'id': 'route-main',
                    'type': 'line',
                    'source': 'route',
                    'layout': { 'line-join': 'round', 'line-cap': 'round' },
                    'paint': { 'line-color': '#3b82f6', 'line-width': 5 }
                });

                geolocateControl.trigger();

                // Gambar rute awal berdasarkan posisi pin default
                getRoute(senderMarker.getLngLat(), receiverMarker.getLngLat());
            });

            window.syncMarkerFromInputs = function(prefix) {
                const lat = parseFloat($(`#${prefix}_lat`).val()), lng = parseFloat($(`#${prefix}_lng`).val());
                if (lat && lng) {
                    if (prefix === 'sender') { senderMarker.setLngLat([lng, lat]); }
                    else { receiverMarker.setLngLat([lng, lat]); }
                    map.flyTo({ center: [lng, lat], zoom: 16, essential: true });
                }
            };
        } else {
            console.warn("LOG LOG: Token Mapbox tidak ditemukan.");
            $('#map').html('<div class="p-4 text-center text-muted">Peta tidak tersedia (Token belum diatur).</div>');
        }

        // Panggil saat load untuk menyembunyikan peta jika secara default vendor bukan sancaka
        toggleMapVisibility();

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    $('#buyer_latitude').val(position.coords.latitude);
                    $('#buyer_longitude').val(position.coords.longitude);
                    if (map && senderMarker) {
                        senderMarker.setLngLat([position.coords.longitude, position.coords.latitude]);
                        map.setCenter([position.coords.longitude, position.coords.latitude]);
                        $(`#sender_lat`).val(position.coords.latitude);
                        $(`#sender_lng`).val(position.coords.longitude);
                    }
                },
                function(error) { console.warn("Akses GPS Pembeli bermasalah: " + error.message); },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }
        // ==============================================================================

        $(document).on('change', '#extra_helper_driver, #extra_helper_extra', function() {
            let driverFee = $('#extra_helper_driver').is(':checked') ? ($('#extra_helper_driver').data('price') || 0) : 0;
            let extraFee = $('#extra_helper_extra').is(':checked') ? ($('#extra_helper_extra').data('price') || 0) : 0;
            $('#selected_helper_driver_fee').val(driverFee); $('#selected_helper_extra_fee').val(extraFee);
            updateTotalSummary();
        });

        $('form [required]').on('input', function() {
            if ($(this).val()) { $(this).removeClass('is-invalid'); }
        });

        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        @if ($errors->any())
            let errorHtml = '<ul class="list-unstyled text-start mb-0" style="padding-left: 1rem;">';
            @foreach ($errors->all() as $error) errorHtml += '<li class="mb-1"><i class="fas fa-exclamation-circle me-2 text-danger"></i>{{ $error }}</li>'; @endforeach
            errorHtml += '</ul>';
            Swal.fire({ title: 'Data Tidak Valid!', html: errorHtml, icon: 'error', confirmButtonColor: '#dc2626' });
        @endif
        @if(session('success')) Swal.fire({ title: 'Berhasil!', text: "{{ session('success') }}", icon: 'success', confirmButtonColor: '#16a34a' }); @endif
        @if(session('error')) Swal.fire({ title: 'Gagal!', text: @json(session('error')), icon: 'error', confirmButtonColor: '#dc2626' }); @endif

        const ongkirModal = new bootstrap.Modal(document.getElementById('ongkirModal'));
        const delivereeModal = new bootstrap.Modal(document.getElementById('delivereeModal'));
        const paymentModal = new bootstrap.Modal(document.getElementById('paymentMethodModal'));
        const lalamoveModal = new bootstrap.Modal(document.getElementById('lalamoveModal'));
        const ipaymuModal = new bootstrap.Modal(document.getElementById('ipaymuModal'));

        let searchTimeout = null;
        const debounce = (func, delay) => (...args) => { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => func.apply(this, args), delay); };
        function formatRupiah(angka) { return 'Rp ' + (parseInt(angka, 10) || 0).toLocaleString('id-ID'); }

        function updateTotalSummary() {
            let itemPrice = parseInt(($('#item_price').val() || "0").replace(/\D/g, '')) || 0;
            let baseShippingCost = parseInt($('#selected_shipping_cost').val()) || 0;
            let insuranceCost = parseInt($('#selected_insurance_cost').val()) || 0;
            let codFee = parseInt($('#selected_cod_fee').val()) || 0;
            let helperDriverFee = parseInt($('#selected_helper_driver_fee').val()) || 0;
            let helperExtraFee = parseInt($('#selected_helper_extra_fee').val()) || 0;

            let paymentMethod = $('#payment_method').val();
            let finalShippingCost = baseShippingCost + insuranceCost + helperDriverFee + helperExtraFee;
            let total = finalShippingCost;

            if (paymentMethod === 'COD' || paymentMethod === 'CODBARANG') { finalShippingCost += codFee; total = finalShippingCost; }
            if (paymentMethod === 'CODBARANG') { total = itemPrice + finalShippingCost; }

            $('#summary_item_price').text(formatRupiah(itemPrice));
            $('#summary_shipping_cost').text(formatRupiah(baseShippingCost));

            if ($('#ansuransi').val() === 'iya') {
                $('#summary_insurance_row').removeClass('d-none').addClass('d-flex');
                $('#tos_asuransi_container').removeClass('d-none'); $('#tos_asuransi').prop('required', true);
                $('#summary_insurance_cost').text(baseShippingCost === 0 ? '(Menunggu Cek Tarif)' : (insuranceCost > 0 ? formatRupiah(insuranceCost) : 'Gratis')).toggleClass('text-muted', baseShippingCost === 0).toggleClass('text-success', baseShippingCost !== 0);
            } else {
                $('#summary_insurance_row').addClass('d-none').removeClass('d-flex');
                $('#tos_asuransi_container').addClass('d-none'); $('#tos_asuransi').prop('required', false).prop('checked', false);
            }

            if ($('#extra_helper_driver').is(':checked') && $('#vendor_filter').val() === 'deliveree') {
                $('#summary_helper_driver_row').removeClass('d-none').addClass('d-flex');
                $('#summary_helper_driver_cost').text(baseShippingCost === 0 ? '(Menunggu Cek Tarif)' : (helperDriverFee > 0 ? formatRupiah(helperDriverFee) : 'Gratis')).toggleClass('text-muted', baseShippingCost === 0).toggleClass('text-success', baseShippingCost !== 0);
            } else { $('#summary_helper_driver_row').addClass('d-none').removeClass('d-flex'); }

            if ($('#extra_helper_extra').is(':checked') && $('#vendor_filter').val() === 'deliveree') {
                $('#summary_helper_extra_row').removeClass('d-none').addClass('d-flex');
                $('#summary_helper_extra_cost').text(baseShippingCost === 0 ? '(Menunggu Cek Tarif)' : (helperExtraFee > 0 ? formatRupiah(helperExtraFee) : 'Gratis')).toggleClass('text-muted', baseShippingCost === 0).toggleClass('text-success', baseShippingCost !== 0);
            } else { $('#summary_helper_extra_row').addClass('d-none').removeClass('d-flex'); }

            $('#summary_total_cost').text(formatRupiah(total));
        }

        $('#ansuransi, #item_price').on('change input', updateTotalSummary);

        function maskData(type, value) { if (!value) return '***'; if (type === 'name') { const parts = value.split(' '); return parts.length > 1 ? parts[0] + ' ' + parts.slice(1).map(p => p.replace(/./g, '*')).join(' ') : (value.length > 2 ? value.substring(0, 2) + '***' : value); } if (type === 'phone') { const num = value.replace(/\D/g, ''); return num.length > 8 ? num.substring(0, 3) + '****' + num.substring(num.length - 4) : num.substring(0, 3) + '****'; } if (type === 'address') { const parts = value.split(' '); return parts.length > 2 ? parts.slice(0, 2).join(' ') + ' **** **** ****' : value; } return '***'; }
        function clearHiddenAddress(prefix) { $(`#${prefix}_province, #${prefix}_regency, #${prefix}_district, #${prefix}_village, #${prefix}_postal_code, #${prefix}_district_id, #${prefix}_subdistrict_id, #${prefix}_lat, #${prefix}_lng`).val(''); }

        function fallbackGeocode(prefix, kelurahan, kecamatan, kabupaten) {
            let queries = [];
            if (kelurahan && kecamatan && kabupaten) queries.push(`${kelurahan}, ${kecamatan}, ${kabupaten}`);
            if (kecamatan && kabupaten) queries.push(`${kecamatan}, ${kabupaten}`);
            if (kabupaten) queries.push(`${kabupaten}`);

            function attemptGeo(index) {
                if (index >= queries.length) return;
                let q = queries[index];
                $.ajax({
                    url: 'https://nominatim.openstreetmap.org/search', data: { q: q, format: 'json', limit: 1, countrycodes: 'id' },
                    success: function(res) {
                        if (res && res.length > 0) {
                            $(`#${prefix}_lat`).val(res[0].lat); $(`#${prefix}_lng`).val(res[0].lon);
                            if(typeof window.syncMarkerFromInputs === 'function') window.syncMarkerFromInputs(prefix);
                        } else { attemptGeo(index + 1); }
                    },
                    error: function() { attemptGeo(index + 1); }
                });
            }
            if(queries.length > 0) attemptGeo(0);
        }

        function fillContactForm(prefix, data) {
            $(`#${prefix}_name`).val(maskData('name', data.nama)).trigger('blur').attr('data-real-value', data.nama);
            $(`#${prefix}_phone`).val(maskData('phone', data.no_hp)).trigger('blur').attr('data-real-value', data.no_hp);
            $(`#${prefix}_address`).val(maskData('address', data.alamat)).trigger('blur').attr('data-real-value', data.alamat || '');
            $(`#${prefix}_id`).val(data.id);

            clearHiddenAddress(prefix);
            const addressSearchInput = $(`#${prefix}_address_search`);

            if (data.village && data.district) {
                const addressQuery = `${data.village}, ${data.district}`;
                addressSearchInput.val(`Mencari: ${addressQuery}...`).prop('disabled', true).removeClass('is-invalid is-valid');
                $.get("{{ route('api.address.search') }}", { search: addressQuery }).done(function(results) {
                    if (results && results.length > 0) {
                        const item = results[0]; const parts = item.full_address.split(',').map(s => s.trim());
                        $(`#${prefix}_village`).val(parts[0] || data.village).trigger('change');
                        $(`#${prefix}_district`).val(parts[1] || data.district).trigger('change');
                        $(`#${prefix}_regency`).val(parts[2] || data.regency).trigger('change');
                        $(`#${prefix}_province`).val(parts[3] || data.province).trigger('change');
                        $(`#${prefix}_postal_code`).val(parts[4] || data.postal_code).trigger('change');
                        $(`#${prefix}_district_id`).val(item.district_id).trigger('change');
                        $(`#${prefix}_subdistrict_id`).val(item.subdistrict_id).trigger('change');

                        let lat = parseFloat(item.lat), lon = parseFloat(item.lon);
                        if (!lat || !lon || lat === 0 || lon === 0) { fallbackGeocode(prefix, parts[0] || data.village, parts[1] || data.district, parts[2] || data.regency); }
                        else { $(`#${prefix}_lat`).val(lat); $(`#${prefix}_lng`).val(lon); if(typeof window.syncMarkerFromInputs === 'function') window.syncMarkerFromInputs(prefix); }

                        addressSearchInput.val('Alamat Ditemukan (Privasi Terjaga)').addClass('is-valid').removeClass('is-invalid');
                        setTimeout(() => addressSearchInput.removeClass('is-valid'), 2500);
                    } else {
                        addressSearchInput.val('').addClass('is-invalid').removeClass('is-valid');
                    }
                }).fail(() => { addressSearchInput.val('').addClass('is-invalid').removeClass('is-valid'); }).always(() => addressSearchInput.prop('disabled', false));
            } else { addressSearchInput.val('').addClass('is-invalid'); }
        }

        function setupContactSearch(prefix) {
            $(`#${prefix}_name, #${prefix}_phone`).each(function() {
                $(this).autocomplete({
                    source: function(request, response) {
                        $.ajax({
                            url: "{{ route('api.search.kontak') }}", dataType: "json", data: { term: request.term },
                            success: function(data) {
                                if (!data || !data.length) { response([{ label: 'Tidak ada data', disabled: true }]); return; }
                                response($.map(data, function(item) { return { label: item.nama, value: item.nama, data: item }; }));
                            },
                            error: function() { response([{ label: 'Gagal mengambil data', disabled: true }]); }
                        });
                    },
                    minLength: 2,
                    select: function(event, ui) { if (ui.item.disabled) return false; event.preventDefault(); fillContactForm(prefix, ui.item.data); }
                }).autocomplete("instance")._renderItem = function(ul, item) {
                    if (item.disabled) { return $("<li class='ui-state-disabled p-2 text-muted text-center'></li>").text(item.label).appendTo(ul); }
                    const maskedName = maskData('name', item.data.nama), maskedPhone = maskData('phone', item.data.no_hp);
                    return $("<li>").append(`<div class="ui-menu-item-wrapper"><div class="fw-bold text-dark">${maskedName}</div><small class="text-muted"><i class="fas fa-phone me-1"></i>${maskedPhone}</small></div>`).appendTo(ul);
                };
            });
        }
        setupContactSearch('sender'); setupContactSearch('receiver');

        function unmaskDataForSubmit() {
            ['sender', 'receiver'].forEach(p => {
                $(`#${p}_name, #${p}_phone, #${p}_address`).each(function() { if ($(this).attr('data-real-value')) $(this).val($(this).attr('data-real-value')); });
            });
        }

        function setupAddressSearch(prefix) {
            const s = $(`#${prefix}_address_search`), r = $(`#${prefix}_address_results`);
            s.on('input', debounce(() => {
                s.removeClass('is-valid is-invalid'); const q = s.val();
                if (q.length < 3) return r.addClass('d-none');
                $.get("{{ route('api.address.search') }}", { search: q }).done(d => {
                    r.html('').removeClass('d-none');
                    if (d && d.length > 0) {
                        d.forEach(i => r.append($(`<div class="search-result-item"><div class="font-weight-bold">${i.full_address}</div></div>`).on('click', () => {
                            s.val(i.full_address);
                            const p = i.full_address.split(',').map(t => t.trim());
                            $(`#${prefix}_village`).val(p[0] || '').trigger('change');
                            $(`#${prefix}_district`).val(p[1] || '').trigger('change');
                            $(`#${prefix}_regency`).val(p[2] || '').trigger('change');
                            $(`#${prefix}_province`).val(p[3] || '').trigger('change');
                            $(`#${prefix}_postal_code`).val(p[4] || '').trigger('change');
                            $(`#${prefix}_district_id`).val(i.district_id).trigger('change');
                            $(`#${prefix}_subdistrict_id`).val(i.subdistrict_id).trigger('change');

                            let lat = parseFloat(i.lat), lon = parseFloat(i.lon);
                            if (!lat || !lon || lat === 0 || lon === 0) { fallbackGeocode(prefix, p[0], p[1], p[2]); }
                            else { $(`#${prefix}_lat`).val(lat); $(`#${prefix}_lng`).val(lon); if(typeof window.syncMarkerFromInputs === 'function') window.syncMarkerFromInputs(prefix); }
                            r.addClass('d-none');
                        })));
                    } else { r.html('<div class="p-3 text-muted">Alamat tidak ditemukan.</div>'); }
                }).fail(() => r.html('<div class="p-3 text-danger">Gagal memuat data.</div>'));
            }, 400));
        }
        setupAddressSearch('sender'); setupAddressSearch('receiver');

        function getDelivereeVehicleImage(name) {
            const lowerName = name.toLowerCase(), baseUrl = 'https://tokosancaka.com/storage/logo-ekspedisi/armada_deliveree';
            if (lowerName.includes('trailer')) return `${baseUrl}/Trailer.png`; if (lowerName.includes('tronton')) return `${baseUrl}/TrontonB.png`;
            if (lowerName.includes('fuso')) return `${baseUrl}/Fuso_Heavy.png`; if (lowerName.includes('cdd') || lowerName.includes('double engkel')) return `${baseUrl}/CDD.png`;
            if (lowerName.includes('engkel') || lowerName.includes('cde')) return `${baseUrl}/Engkel_Box.png`; if (lowerName.includes('small box') || lowerName.includes('box kecil')) return `${baseUrl}/Small_Box.png`;
            if (lowerName.includes('pickup')) return `${baseUrl}/Pickup.png`; if (lowerName.includes('van')) return `${baseUrl}/Van.png`;
            if (lowerName.includes('xl') || lowerName.includes('suv')) return `${baseUrl}/carxl-longer_(1).png`;
            if (lowerName.includes('mobil') || lowerName.includes('car') || lowerName.includes('economy') || lowerName.includes('ekonomi') || lowerName.includes('city')) return `${baseUrl}/Economy.png`;
            return `https://placehold.co/300x200/e2e8f0/10b981?text=${encodeURIComponent(name)}`;
        }

        function renderDelivereeModal(results, baseParams) {
            const container = $('#delivereeResultsContainer').empty();
            if (results.length === 0) { container.html(`<div class="col-12"><div class="alert alert-warning text-center shadow-sm">Armada Deliveree tidak tersedia.</div></div>`); return; }
            results.forEach(i => {
                let rawName = i.service_type_label || '';
                let displayServiceType = rawName.replace(/-/g, ' ');
                if (displayServiceType.includes('#')) { let parts = displayServiceType.split('#'); displayServiceType = parts[0].trim(); }
                let imgUrl = getDelivereeVehicleImage(displayServiceType);
                const useInsurance = $('#ansuransi').val() === 'iya', insuranceFeeValue = useInsurance ? (i.insurance || 0) : 0, codFee = (i.setting && i.setting.cod_fee_amount) ? i.setting.cod_fee_amount : 0;
                const baseOngkirCost = parseInt(i.distance_fees || i.cost || 0), actualCodFee = parseInt(codFee || 0);
                const payloadValue = `${baseParams.serviceType}-${i.service_name}-${rawName}-${i.cost}-${insuranceFeeValue}-${codFee}`;
                let etdHtml = i.etd ? `<span>${i.etd} Hari</span>` : '<span>Langsung (Charter)</span>';

                container.append(`
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border border-success shadow-sm deliveree-card" style="border-radius:1rem; cursor: pointer; background: white;">
                        <div class="card-body text-center p-4">
                            <img src="${imgUrl}" style="height:100px; width:100%; object-fit:contain; margin-bottom:1rem;" alt="${displayServiceType}">
                            <h6 class="fw-bold text-dark mb-1">DELIVEREE</h6><span class="badge bg-success mb-3">${displayServiceType}</span>
                            <h4 class="text-success fw-bold mb-2">${formatRupiah(i.cost)}</h4>
                            <div class="text-muted small"><i class="fas fa-clock me-1"></i> Estimasi: ${etdHtml}</div>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-center pb-3">
                            <button type="button" class="btn btn-success w-100 select-ongkir-btn rounded-pill fw-bold" data-value="${payloadValue}" data-display="Deliveree - ${displayServiceType}" data-cod-supported="${i.cod}" data-vehicle-id="${i.vehicle_type_id}" data-shipping-cost="${baseOngkirCost}" data-insurance-cost="${insuranceFeeValue}" data-cod-fee="${actualCodFee}">
                                <i class="fas fa-check-circle me-1">Pilih Armada</i>
                            </button>
                        </div>
                    </div>
                </div>`);
            });
        }

        function getLalamoveVehicleImage(name) {
            const lowerName = name.toLowerCase(), baseUrl = 'https://tokosancaka.com/public/storage/logo-ekspedisi/lalamove';
            if (lowerName.includes('wingbox') || lowerName.includes('wing box')) return `${baseUrl}/wingbox.png`; if (lowerName.includes('fuso bak')) return `${baseUrl}/fusobak.jpg`;
            if (lowerName.includes('fuso')) return `${baseUrl}/fusotruck.png`; if (lowerName.includes('cdd box')) return `${baseUrl}/cddbox.png`;
            if (lowerName.includes('cdd')) return `${baseUrl}/cddbak.png`; if (lowerName.includes('engkel box') || lowerName.includes('cde box')) return `${baseUrl}/engkelbox.png`;
            if (lowerName.includes('engkel') || lowerName.includes('cde')) return `${baseUrl}/engkelbak.png`; if (lowerName.includes('pickup box') || lowerName.includes('pick-up box') || lowerName.includes('pick up box')) return `${baseUrl}/pickupbox.png`;
            if (lowerName.includes('pickup') || lowerName.includes('pick-up') || lowerName.includes('pick up')) return `${baseUrl}/pickupbak.png`;
            if (lowerName.includes('van')) return `${baseUrl}/vannew.png`; if (lowerName.includes('sedan')) return `${baseUrl}/sedannew.png`;
            if (lowerName.includes('mpv') || lowerName.includes('car') || lowerName.includes('mobil')) return `${baseUrl}/mpvnew.png`;
            if (lowerName.includes('motor') || lowerName.includes('bike') || lowerName.includes('motorcycle')) return `${baseUrl}/bike.png`;
            return `https://placehold.co/300x200/fffaf7/f27024?text=${encodeURIComponent(name)}`;
        }

        function renderLalamoveModal(results, baseParams) {
            const container = $('#lalamoveResultsContainer').empty();
            if (!results || results.length === 0) { container.html(`<div class="col-12"><div class="alert alert-warning text-center shadow-sm">Armada Lalamove tidak tersedia.</div></div>`); return; }
            let colClass = results.length <= 2 ? 'col-sm-6' : 'col-sm-6 col-md-4';
            results.forEach(i => {
                let rawName = i.service_type_label || '', displayServiceType = rawName;
                if (displayServiceType.includes('#')) { let parts = displayServiceType.split('#'); displayServiceType = parts[0].trim(); }
                let imgUrl = getLalamoveVehicleImage(displayServiceType);
                const useInsurance = $('#ansuransi').val() === 'iya', insuranceFeeValue = useInsurance ? (i.insurance || 0) : 0, codFee = 0;
                const baseOngkirCost = parseInt(i.distance_fees || i.cost || 0), actualCodFee = 0;
                const payloadValue = `${baseParams.serviceType}-${i.service_name}-${rawName}-${i.cost}-${insuranceFeeValue}-${codFee}`;

                container.append(`
                <div class="${colClass} d-flex align-items-stretch">
                    <div class="card h-100 shadow-sm lalamove-card w-100" style="border-radius:1rem; cursor: pointer; background: white; border: 1px solid #f27024;">
                        <div class="card-body text-center p-3 p-md-4">
                            <img src="${imgUrl}" style="height:80px; width:100%; object-fit:contain; margin-bottom:1rem;" alt="${displayServiceType}">
                            <h6 class="fw-bold text-dark mb-1">LALAMOVE</h6><span class="badge mb-2" style="background-color: #f27024; font-size: 0.75rem;">${displayServiceType}</span>
                            <h5 class="fw-bold mb-2" style="color: #f27024;">${formatRupiah(i.cost)}</h5>
                            <div class="text-muted" style="font-size: 0.75rem;"><i class="fas fa-bolt me-1"></i> Estimasi: Instan</div>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-center pb-3 pt-0">
                            <button type="button" class="btn w-100 select-ongkir-btn rounded-pill fw-bold" style="background-color: #f27024; color: white; font-size: 0.85rem;" data-value="${payloadValue}" data-display="Lalamove - ${displayServiceType}" data-cod-supported="false" data-vehicle-id="" data-shipping-cost="${baseOngkirCost}" data-insurance-cost="${insuranceFeeValue}" data-cod-fee="${actualCodFee}">
                                <i class="fas fa-check-circle me-1"></i> Pilih Armada
                            </button>
                        </div>
                    </div>
                </div>`);
            });
        }

        function getIpaymuLogo(name) {
            const lowerName = name.toLowerCase();
            if (lowerName.includes('jne')) return 'https://tokosancaka.com/public/storage/logo-ekspedisi/jne.png';
            if (lowerName.includes('sicepat')) return 'https://tokosancaka.com/public/storage/logo-ekspedisi/sicepat.png';
            if (lowerName.includes('jnt') || lowerName.includes('j&t')) return 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png';
            if (lowerName.includes('ninja')) return 'https://tokosancaka.com/public/storage/logo-ekspedisi/ninja.png';
            if (lowerName.includes('anteraja')) return 'https://tokosancaka.com/public/storage/logo-ekspedisi/anteraja.png';
            if (lowerName.includes('ide')) return 'https://tokosancaka.com/public/storage/logo-ekspedisi/ide.png';
            if (lowerName.includes('sap')) return 'https://tokosancaka.com/public/storage/logo-ekspedisi/sap.png';
            if (lowerName.includes('lion')) return 'https://tokosancaka.com/public/storage/logo-ekspedisi/lion.png';
            return 'https://tokosancaka.com/public/assets/ipaymu.jpg';
        }

        function renderIpaymuModal(results, baseParams) {
            const container = $('#ipaymuResultsContainer').empty();
            if (!results || results.length === 0) { container.html(`<div class="col-12"><div class="alert alert-warning text-center shadow-sm">Layanan iPaymu tidak tersedia.</div></div>`); return; }
            results.forEach(i => {
                let rawName = i.service_type_label || '', displayServiceType = rawName.toUpperCase().replace('IPAYMU-', ''), imgUrl = getIpaymuLogo(displayServiceType);
                const insuranceFeeValue = $('#ansuransi').val() === 'iya' ? (i.insurance || 0) : 0, codFee = (i.setting && i.setting.cod_fee_amount) ? i.setting.cod_fee_amount : 0;
                const baseOngkirCost = parseInt(i.distance_fees || i.cost || 0), actualCodFee = parseInt(codFee || 0);
                const payloadValue = `${baseParams.serviceType}-${i.service_name}-${rawName}-${i.cost}-${insuranceFeeValue}-${codFee}`;

                container.append(`
                <div class="col-md-6 col-lg-4 d-flex align-items-stretch">
                    <div class="card h-100 shadow-sm w-100" style="border-radius:1rem; cursor: pointer; background: white; border: 1px solid #6f42c1;">
                        <div class="card-body text-center p-3 p-md-4">
                            <img src="${imgUrl}" style="height:60px; width:100%; object-fit:contain; margin-bottom:1rem;" alt="${displayServiceType}" onerror="this.src='https://tokosancaka.com/public/assets/ipaymu.jpg'">
                            <h6 class="fw-bold text-dark mb-1">${displayServiceType}</h6>
                            <span class="badge mb-2" style="background-color: #6f42c1; font-size: 0.75rem;">iPaymu COD</span>
                            <h5 class="fw-bold mb-2" style="color: #6f42c1;">${formatRupiah(i.cost)}</h5>
                            <div class="text-muted" style="font-size: 0.75rem;"><i class="fas fa-clock me-1"></i> Estimasi: ${i.etd}</div>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-center pb-3 pt-0">
                            <button type="button" class="btn w-100 select-ongkir-btn rounded-pill fw-bold" style="background-color: #6f42c1; color: white; font-size: 0.85rem;" data-value="${payloadValue}" data-display="iPaymu - ${displayServiceType}" data-cod-supported="${i.cod}" data-shipping-cost="${baseOngkirCost}" data-insurance-cost="${insuranceFeeValue}" data-cod-fee="${actualCodFee}">
                                <i class="fas fa-check-circle me-1"></i> Pilih Kurir
                            </button>
                        </div>
                    </div>
                </div>`);
            });
        }

        function runCekOngkir() {
            let formData = $('#orderForm').serializeArray();
            formData.forEach((item, index) => {
                let realVal = $(`#${item.name.replace(/\[/g, '\\[').replace(/\]/g, '\\]')}`).attr('data-real-value');
                if (realVal) formData[index].value = realVal;
            });

            let tempForm = $('<form>').append($.map(formData, item => $('<input>').attr({type: 'hidden', name: item.name, value: item.value})));
            const vendorFilter = $('#vendor_filter').val();
            tempForm.append($('<input>').attr({type: 'hidden', name: 'vendor_filter', value: vendorFilter}));

            const required = { 'sender_district_id': 'Alamat Pengirim', 'receiver_district_id': 'Alamat Penerima', 'item_price': 'Harga Barang', 'weight': 'Berat' };
            let missing = Object.keys(required).filter(s => !tempForm.find(`[name="${s.replace('#','')}"]`).val());
            if (missing.length > 0) {
                Swal.fire('Data Belum Lengkap', 'Harap lengkapi form dan alamat sebelum mengecek tarif: ' + missing.map(s => required[s]).join(', '), 'warning');
                return;
            }

            $('#ongkirResultsContainer').html(`<div class="text-center p-5"><div class="spinner-border text-danger"></div><p class="mt-2 text-muted">Memuat tarif...</p></div>`);
            $('#delivereeResultsContainer').html(`<div class="col-12"><div class="text-center p-5"><div class="spinner-border text-success"></div><p class="mt-2 text-muted">Mencari Armada Deliveree...</p></div></div>`);
            $('#lalamoveResultsContainer').html(`<div class="col-12"><div class="text-center p-5"><div class="spinner-border" style="color:#f27024;"></div><p class="mt-2 text-muted">Mencari Armada Lalamove...</p></div></div>`);
            $('#ipaymuResultsContainer').html(`<div class="col-12"><div class="text-center p-5"><div class="spinner-border" style="color:#6f42c1;"></div><p class="mt-2 text-muted">Mencari Kurir iPaymu...</p></div></div>`);

            if (vendorFilter === 'deliveree') { delivereeModal.show(); } else if (vendorFilter === 'lalamove') { lalamoveModal.show(); } else if (vendorFilter === 'ipaymu') { ipaymuModal.show(); } else { ongkirModal.show(); }
            const serviceType = $('#service_type').val();

            $.ajax({
                url: "{{ route('kirimaja.cekongkir') }}", type: "GET", data: tempForm.serialize(), timeout: 15000,
                success: function(res) {
                    let allResults = [];
                    if (typeof res !== 'object' || res === null) {
                        const errHtml = '<div class="alert alert-danger text-center w-100">Format respons tidak valid.</div>';
                        $('#ongkirResultsContainer, #delivereeResultsContainer, #lalamoveResultsContainer, #ipaymuResultsContainer').html(errHtml); return;
                    }
                    const hasData = (res.result && Array.isArray(res.result)) || (res.results && Array.isArray(res.results));
                    if (!hasData || (res.status === false && !hasData)) {
                        const errHtml = `<div class="alert alert-warning text-center shadow-sm w-100">${res.message || 'Layanan tidak ditemukan.'}</div>`;
                        $('#ongkirResultsContainer, #delivereeResultsContainer, #lalamoveResultsContainer, #ipaymuResultsContainer').html(errHtml); return;
                    }

                    if (res.result && Array.isArray(res.result)) {
                        allResults.push(...res.result.flatMap(provider => provider.costs.map(cost => ({
                            ...cost, service: provider.name === 'grab_express' ? 'grab' : provider.name, service_name: `${provider.name.toUpperCase()}`, service_type_label: `${cost.service_type}`,
                            cost: cost.price.total_price, price: cost.price, etd: cost.estimation || '-', setting: cost.setting || {}, insurance: cost.price.insurance_fee || 0, cod: cost.cod_available ?? false, is_instant: true
                        }))));
                    }
                    if (res.results && Array.isArray(res.results)) {
                        allResults.push(...res.results.map(service => ({
                            ...service, cost: service.cost, price: { base_price: service.cost, total_price: service.cost }, insurance: service.insurance || 0, cod: service.cod, service_name: `${service.service.toUpperCase()}`, service_type_label: `${service.service_type}`, is_instant: false
                        })));
                    }

                    allResults.sort((a, b) => a.cost - b.cost);
                    let kiriminAjaResults = [], delivereeResults = [], lalamoveResults = [], ipaymuResults = [];
                    allResults.forEach(service => {
                        let logoName = (service.service || "").toLowerCase().replace(/\s+/g, '');
                        if (logoName === 'deliveree') { delivereeResults.push(service); } else if (logoName === 'lalamove') { lalamoveResults.push(service); } else if (logoName === 'ipaymu') { ipaymuResults.push(service); } else { kiriminAjaResults.push(service); }
                    });

                    if (vendorFilter === 'deliveree') { renderDelivereeModal(delivereeResults, { serviceType: serviceType }); }
                    else if (vendorFilter === 'lalamove') { renderLalamoveModal(lalamoveResults, { serviceType: serviceType }); }
                    else if (vendorFilter === 'ipaymu') { renderIpaymuModal(ipaymuResults, { serviceType: serviceType }); }
                    else {
                        const b = $('#ongkirResultsContainer').empty();
                       if (kiriminAjaResults.length > 0) {
                            // 1. HEADER ROW DIPISAH (Biar tulisan LAYANAN tidak nabrak logo)
                            b.append(`
                                <div class="ongkir-header-row d-none d-lg-flex">
                                    <div class="col-logo"></div>
                                    <div class="col-service">Layanan</div>
                                    <div class="col-etd">Estimasi</div>
                                    <div class="col-cod">COD</div>
                                    <div class="col-price">Tarif</div>
                                    <div class="col-action"></div>
                                </div>
                            `);

                            kiriminAjaResults.forEach(i => {
                                const logoName = (i.service || "").toLowerCase().replace(/[\s_]+/g, '');
                                let logoUrl = logoName === 'sancakaexpress' ? 'https://tokosancaka.com/storage/uploads/sancaka.png' : (logoName === 'gosend' ? 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png' : (logoName === 'grab' ? 'https://tokosancaka.com/public/storage/logo-ekspedisi/grab.png' : `{{ asset('public/storage/logo-ekspedisi/') }}/${logoName}.png`));
                                const safeService = (i.service || '').toString().replace(/-/g, ' '), safeServiceTypeLabel = (i.service_type_label || '').toString().replace(/-/g, ' ');
                                const useInsurance = $('#ansuransi').val() === 'iya', insuranceFeeValue = useInsurance ? (i.insurance || 0) : 0, codFee = (i.setting && i.setting.cod_fee_amount) ? i.setting.cod_fee_amount : (i.extra_fees || 0);
                                const v = `${serviceType}-${safeService}-${safeServiceTypeLabel}-${i.cost}-${insuranceFeeValue}-${codFee}`;
                                const baseOngkirCost = parseInt(i.cost || 0) + parseInt(insuranceFeeValue || 0), actualCodFee = parseInt(codFee || 0);
                                const hasDiscount = i.price?.base_price && i.price.base_price > i.cost, basePriceFmt = hasDiscount ? formatRupiah(i.price.base_price) : '';

                                let feeDetailsHtml = '';
                                if (useInsurance && (i.insurance || 0) > 0) { feeDetailsHtml += `<div><small>Termasuk Asuransi: ${formatRupiah(i.insurance || 0)}</small></div>`; }
                                if (i.cod && codFee > 0) { feeDetailsHtml += `<div><small>Biaya COD: ${formatRupiah(codFee)}</small></div>`; }

                                let etdHtml = '';
                                if (i.etd) { etdHtml = `<span>${String(i.etd).toLowerCase().match(/hari|jam|menit/) || i.is_instant ? i.etd : i.etd + ' Hari'}</span>`; }

                                let codDisplayHtml = i.cod ? `Tersedia${actualCodFee > 0 ? `<br><small class="text-danger fw-bold">+ ${formatRupiah(actualCodFee)}</small>` : ''}` : '-';
                                const buttonHtml = `<button type="button" class="btn btn-kirim select-ongkir-btn" data-value="${v}" data-display="${i.service_name} - ${i.service_type_label}" data-cod-supported="${i.cod}" data-shipping-cost="${parseInt(i.cost || 0)}" data-insurance-cost="${insuranceFeeValue}" data-cod-fee="${actualCodFee}">Kirim Paket</button>`;

                                // 2. ISI CARD DIPISAH TOTAL PER KOLOM
                                b.append(`
                                <div class="ongkir-item-card">
                                    <div class="col-logo">
                                        <img src="${logoUrl}" class="ongkir-logo" alt="${i.service_name}">
                                    </div>
                                    <div class="col-service">
                                        <div class="service-name">${i.service_name.replace(/_/g, ' ')}</div>
                                        <div class="service-type">${i.service_type_label}</div>
                                    </div>
                                    <div class="col-etd">
                                        <span class="col-label-mobile">Estimasi</span>
                                        ${etdHtml}
                                    </div>
                                    <div class="col-cod">
                                        <span class="col-label-mobile">COD</span>
                                        <span>${codDisplayHtml}</span>
                                    </div>
                                    <div class="col-price">
                                        <span class="col-label-mobile">Tarif</span>
                                        <div class="price-value"><span class="final-price">${formatRupiah(i.cost)}</span>${hasDiscount ? `<span class="base-price text-decoration-line-through">${basePriceFmt}</span>` : ''}</div>
                                        <div class="price-details">${feeDetailsHtml}</div>
                                    </div>
                                    <div class="col-action">
                                        ${buttonHtml}
                                    </div>
                                </div>`);
                            });
                        }
                        if (ipaymuResults.length > 0) {
                            b.append(`<div class="w-100 my-4 text-center text-muted" style="border-top: 1px dashed #ced4da; padding-top: 10px;"><small class="fw-bold" style="color: #6f42c1;">--- LAYANAN ALTERNATIF DARI IPAYMU COD ---</small></div>`);
                            ipaymuResults.forEach(i => {
                                let rawName = i.service_type_label || '', displayServiceType = rawName.toUpperCase().replace('IPAYMU-', ''), imgUrl = getIpaymuLogo(displayServiceType);
                                const useInsurance = $('#ansuransi').val() === 'iya', insuranceFeeValue = useInsurance ? (i.insurance || 0) : 0, codFee = (i.setting && i.setting.cod_fee_amount) ? i.setting.cod_fee_amount : 0;
                                const baseOngkirCost = parseInt(i.distance_fees || i.cost || 0), actualCodFee = parseInt(codFee || 0);
                                const payloadValue = `${serviceType}-${i.service_name}-${rawName}-${i.cost}-${insuranceFeeValue}-${codFee}`;

                                let feeDetailsHtml = '';
                                if (useInsurance && insuranceFeeValue > 0) { feeDetailsHtml += `<div><small>Termasuk Asuransi: ${formatRupiah(insuranceFeeValue)}</small></div>`; }
                                if (i.cod && codFee > 0) { feeDetailsHtml += `<div><small>Biaya COD: ${formatRupiah(codFee)}</small></div>`; }
                                const buttonHtml = `<button type="button" class="btn btn-kirim select-ongkir-btn" style="background-color: #6f42c1;" data-value="${payloadValue}" data-display="iPaymu - ${displayServiceType}" data-cod-supported="${i.cod}" data-shipping-cost="${baseOngkirCost}" data-insurance-cost="${insuranceFeeValue}" data-cod-fee="${actualCodFee}">Pilih Kurir</button>`;

                               b.append(`
                                <div class="ongkir-item-card" style="border-left: 4px solid #6f42c1;">
                                    <div class="ongkir-item-col col-logo">
                                        <img src="${imgUrl}" class="ongkir-logo" onerror="this.src='https://tokosancaka.com/public/assets/ipaymu.jpg'">
                                    </div>
                                    <div class="ongkir-item-col col-service">
                                        <span class="col-label">Layanan</span>
                                        <div class="service-info"><span class="service-name">${displayServiceType}</span><span class="service-type" style="color:#6f42c1; font-weight:bold;">iPaymu COD</span></div>
                                    </div>
                                    <div class="ongkir-item-col col-etd"><span class="col-label">Estimasi</span><span>${i.etd}</span></div>
                                    <div class="ongkir-item-col col-cod"><span class="col-label">COD</span><span class="text-success fw-bold">Wajib</span></div>
                                    <div class="ongkir-item-col col-price"><span class="col-label">Tarif</span>
                                        <div class="price-value"><span class="final-price" style="color:#6f42c1;">${formatRupiah(i.cost)}</span></div>
                                        <div class="price-details">${feeDetailsHtml}</div>
                                    </div>
                                    <div class="ongkir-item-col col-action">${buttonHtml}</div>
                                </div>`);
                            });
                        }
                        if (kiriminAjaResults.length === 0 && ipaymuResults.length === 0) { b.html(`<div class="alert alert-warning text-center shadow-sm">Tidak ada layanan reguler yang tersedia.</div>`); }
                    }
                },
                error: function(jqXHR, textStatus) {
                    let errorMsg = jqXHR.responseJSON?.message || (textStatus === 'timeout' ? 'Waktu habis (Timeout). Server API ekspedisi merespons terlalu lambat.' : 'Gagal mengambil data ongkir dari server.');
                    const errHtml = `<div class="col-12"><div class="alert alert-danger text-center shadow-sm w-100"><i class="fas fa-exclamation-triangle me-2"></i> ${errorMsg}</div></div>`;
                    $('#ongkirResultsContainer, #delivereeResultsContainer, #lalamoveResultsContainer, #ipaymuResultsContainer').html(errHtml);
                }
            });
        }

        const fieldsThatAffectShipping = '#sender_district_id, #receiver_district_id, #item_price, #weight, #length, #width, #height, #ansuransi, #service_type, #vendor_filter';

        $(document).on('change', fieldsThatAffectShipping, function() {
            $('#expedition').val(''); $('#selected_expedition_display').val('').attr('placeholder', 'Klik untuk Pilih Kurir').removeClass('is-valid');
            $('.cod-payment-option').hide();
            $('#selected_shipping_cost, #selected_insurance_cost').val('0');
            $('#map_ongkir_summary').fadeOut(200, function() { $(this).addClass('d-none'); });

            $('#payment_method').val(''); $('#selectedPaymentName').text('Pilih Pembayaran...');
            $('#selectedPaymentLogo').addClass('d-none').attr('src', ''); $('#defaultPaymentIcon').removeClass('d-none');

            if ($('#vendor_filter').val() === 'deliveree') { $('#deliveree_extra_section').removeClass('d-none'); }
            else { $('#deliveree_extra_section').addClass('d-none'); $('#extra_helper_driver, #extra_helper_extra').prop('checked', false).trigger('change'); }
            updateTotalSummary();
        });

        $('#selected_expedition_display').on('click', runCekOngkir);

        $(document).on('click', '.select-ongkir-btn', function() {
            const expeditionValue = $(this).data('value');
            $('#expedition').val(expeditionValue); $('#selected_expedition_display').val($(this).data('display')).addClass('is-valid');
            $('#selected_shipping_cost').val($(this).data('shipping-cost')); $('#selected_insurance_cost').val($(this).data('insurance-cost'));
            $('#selected_cod_fee').val($(this).data('cod-fee'));

            if ($(this).data('cod-supported')) { $('.cod-payment-option').show(); }
            else {
                if (['COD', 'CODBARANG'].includes($('#payment_method').val())) {
                    $('#payment_method').val(''); $('#selectedPaymentName').text('Pilih Pembayaran...');
                    $('#selectedPaymentLogo').addClass('d-none').attr('src', ''); $('#defaultPaymentIcon').removeClass('d-none');
                }
                $('.cod-payment-option').hide();
            }

            updateTotalSummary();
            ongkirModal.hide(); delivereeModal.hide(); lalamoveModal.hide(); ipaymuModal.hide();

            let vehicleId = $(this).data('vehicle-id');
            if (String(expeditionValue).toLowerCase().includes('deliveree') && vehicleId) {
                $('#helper_driver_price_text, #helper_extra_price_text').text('(Mengecek...)').removeClass('text-success text-muted').addClass('text-warning');
                $.get('/api/deliveree/extra-services/' + vehicleId, function(res) {
                    if (res.data) {
                        let helperDriver = res.data.find(s => s.name.toLowerCase().includes('pengemudi')), helperExtra = res.data.find(s => s.name.toLowerCase().includes('tambahan'));
                        if (helperDriver && helperDriver.unit_price > 0) {
                            $('#deliveree_helper_driver_id').val(helperDriver.id); $('#extra_helper_driver').data('price', helperDriver.unit_price).prop('disabled', false);
                            $('#helper_driver_price_text').text('+ ' + formatRupiah(helperDriver.unit_price)).removeClass('text-warning text-muted').addClass('text-success');
                        } else {
                            $('#deliveree_helper_driver_id').val(''); $('#extra_helper_driver').data('price', 0).prop('checked', false).prop('disabled', true);
                            $('#helper_driver_price_text').text('(Gratis / Bawaan)').removeClass('text-warning text-success').addClass('text-muted');
                        }
                        if (helperExtra && helperExtra.unit_price > 0) {
                            $('#deliveree_helper_extra_id').val(helperExtra.id); $('#extra_helper_extra').data('price', helperExtra.unit_price).prop('disabled', false);
                            $('#helper_extra_price_text').text('+ ' + formatRupiah(helperExtra.unit_price)).removeClass('text-warning text-muted').addClass('text-success');
                        } else {
                            $('#deliveree_helper_extra_id').val(''); $('#extra_helper_extra').data('price', 0).prop('checked', false).prop('disabled', true);
                            $('#helper_extra_price_text').text('(Tidak tersedia)').removeClass('text-warning text-success').addClass('text-muted');
                        }
                        $('#extra_helper_driver, #extra_helper_extra').trigger('change');
                    }
                }).fail(function() {
                    $('#helper_driver_price_text, #helper_extra_price_text').text('(Gagal cek API)').addClass('text-danger');
                });
            }

            // ==========================================================
            // PASTE KODE TAMBAHANNYA DI SINI
            // ==========================================================
            // [AWAL TAMBAHAN] TAMPILKAN RINCIAN DI ATAS PETA JIKA SANCAKA EXPRESS
            if ($('#vendor_filter').val() === 'sancaka_express') {
                let baseOngkir = parseInt($(this).data('shipping-cost')) || 0;
                let asuransi = parseInt($(this).data('insurance-cost')) || 0;
                let codFee = parseInt($(this).data('cod-fee')) || 0;
                let total = baseOngkir + asuransi; // Biaya akhir jika non-COD

                $('#map_summary_service').text($(this).data('display').replace('Sancaka Express - ', ''));
                $('#map_summary_ongkir').text(formatRupiah(baseOngkir));

                if (asuransi > 0) {
                    $('#map_summary_asuransi_row').removeClass('d-none');
                    $('#map_summary_asuransi').text(formatRupiah(asuransi));
                } else {
                    $('#map_summary_asuransi_row').addClass('d-none');
                }

                if (codFee > 0) {
                    $('#map_summary_cod_row').removeClass('d-none');
                    $('#map_summary_cod').text(formatRupiah(codFee) + ' (Jika COD)');
                } else {
                    $('#map_summary_cod_row').addClass('d-none');
                }

                $('#map_summary_total').text(formatRupiah(total));
                $('#map_ongkir_summary').removeClass('d-none').hide().fadeIn(300);
            }

        });

        let isPaymentApiLoaded = false;
        function loadTripayChannels() {
            if (isPaymentApiLoaded) return;
            const container = $('#dynamicPaymentChannels');
            $.ajax({
                url: "{{ route('pesanan.public.get_channels') }}", type: "GET",
                success: function(res) {
                    if (res.success && res.data && res.data.length > 0) {
                        container.empty();
                        res.data.forEach(ch => {
                            if (ch.active) {
                                container.append(`
                                    <li class="list-group-item list-group-item-action d-flex align-items-center gateway-option" data-value="${ch.code}" data-label="${ch.name}">
                                        <img src="${ch.icon_url}" class="me-3 border rounded p-1 bg-white" style="width: 40px; height: 40px; object-fit: contain;" onerror="this.src='https://placehold.co/40x40?text=IMG'">
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">${ch.name}</div>
                                            <div class="text-muted" style="font-size: 0.75rem;">${ch.group_name || 'Pembayaran Online'}</div>
                                        </div>
                                    </li>
                                `);
                            }
                        });
                        isPaymentApiLoaded = true; applyGatewayMinimumLimit();
                    } else { container.html('<div class="p-3 text-center text-muted small">Saluran pembayaran Tripay tidak tersedia.</div>'); }
                }
            });
        }

        function applyGatewayMinimumLimit() {
            let totalTransaksi = parseInt($('#selected_shipping_cost').val()) || 0;
            if (totalTransaksi < 10000 && totalTransaksi > 0) {
                $('.gateway-option').addClass('disabled').css({ 'pointer-events': 'none', 'opacity': '0.3', 'background-color': '#f3f4f6' });
                let alertMsg = `<i class="fas fa-exclamation-triangle me-1"></i> Total Ongkir (Rp ${totalTransaksi.toLocaleString('id-ID')}) di bawah minimum Rp 10.000. Payment Gateway dimatikan.`;
                if ($('#min-tx-alert').length === 0) { $('#paymentOptionsList').prepend(`<li id="min-tx-alert" class="list-group-item list-group-item-danger text-center small fw-bold p-2 mb-2 rounded border border-danger">${alertMsg}</li>`); }
                else { $('#min-tx-alert').html(alertMsg).show(); }
                let selectedMethod = $('#payment_method').val();
                if (selectedMethod && $('.gateway-option[data-value="'+selectedMethod+'"]').length > 0) {
                    $('#payment_method').val(''); $('#selectedPaymentName').text('Pilih Pembayaran...');
                    $('#selectedPaymentLogo').addClass('d-none').attr('src', ''); $('#defaultPaymentIcon').removeClass('d-none');
                    $('.gateway-option').removeClass('active');
                }
            } else {
                $('.gateway-option').removeClass('disabled').css({ 'pointer-events': 'auto', 'opacity': '1', 'background-color': '' });
                $('#min-tx-alert').hide();
            }
        }

        $('#paymentMethodButton').on('click', function() { applyGatewayMinimumLimit(); paymentModal.show(); loadTripayChannels(); });

        function executePaymentSelection(element) {
            const value = element.data('value'), label = element.data('label'), imgSrc = element.find('img').attr('src');
            let finalLabel = label, realBal = element.data('real-balance');
            if (realBal !== undefined && value !== 'COD' && value !== 'CODBARANG') { finalLabel = `${label} (${formatRupiah(realBal)})`; }

            $('#payment_method').val(value); $('#selectedPaymentName').text(finalLabel);
            $('#defaultPaymentIcon').addClass('d-none'); $('#selectedPaymentLogo').attr('src', imgSrc).removeClass('d-none');
            $('#paymentOptionsList .list-group-item-action').removeClass('active'); element.addClass('active');
            $('#paymentMethodModal').modal('hide'); updateTotalSummary();
        }

        $('#paymentOptionsList').on('click', '.list-group-item-action', function(e) {
            if ($(this).hasClass('disabled')) { e.preventDefault(); return false; }
            if (!$(this).data('value')) return;

            if ($(this).hasClass('requires-pin') && !isPinVerified) {
                pendingPaymentSelection = $(this); $('#paymentMethodModal').modal('hide');
                setTimeout(() => {
                    $('#pin_error_msg').addClass('d-none'); $('.pin-digit').val(''); $('#full_pin_value').val('');
                    $('#pinModal').modal('show'); setTimeout(() => { $('.pin-digit').first().focus(); }, 500);
                }, 400);
                return;
            }
            executePaymentSelection($(this));
        });

        $('#confirmBtn').on('click', function(e) {
            e.preventDefault(); const $this = $(this); unmaskDataForSubmit();

            if (!$('#orderForm')[0].checkValidity()) {
                $('#orderForm')[0].reportValidity();
                Swal.fire('Peringatan', 'Harap lengkapi semua form yang wajib diisi.', 'warning'); return;
            }

            Swal.fire({
                title: 'Konfirmasi Pesanan', text: "Apakah semua data sudah benar?", icon: 'question',
                showCancelButton: true, confirmButtonText: 'Ya, Buat Pesanan', cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const paymentMethodVal = $('#payment_method').val().toUpperCase();
                    if ((paymentMethodVal === 'POTONG SALDO' || paymentMethodVal === 'DANA_BINDING') && !isPinVerified) {
                        $('#pin_error_msg').addClass('d-none'); $('.pin-digit').val(''); $('#full_pin_value').val('');
                        new bootstrap.Modal(document.getElementById('pinModal')).show();
                        setTimeout(() => { $('.pin-digit').first().focus(); }, 500);
                    } else {
                        $this.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...');
                        $('#orderForm').submit();
                    }
                }
            });
        });

        $('.pin-digit').on('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if ($(this).val().length === 1) { $(this).next('.pin-digit').focus(); }
            let pin = ''; $('.pin-digit').each(function() { pin += $(this).val(); }); $('#full_pin_value').val(pin);
            if (pin.length === 6) { $('#btnVerifyPin').click(); }
        });

        $('.pin-digit').on('keydown', function(e) { if (e.key === 'Backspace' && $(this).val() === '') { $(this).prev('.pin-digit').focus(); } });

        $('#btnVerifyPin').on('click', function() {
            const pin = $('#full_pin_value').val();
            if (pin.length < 6) { $('#pin_error_msg').text('PIN harus 6 angka.').removeClass('d-none'); return; }
            const $btn = $(this); $btn.prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin me-2"></i> Memeriksa...');

            $.ajax({
                url: "{{ route('verify.pin') }}", type: "POST", data: { pin: pin },
                success: function(res) {
                   if (res.success) {
                    $('#pinModal').modal('hide'); isPinVerified = true;
                    $('.requires-pin').each(function() {
                        let realBal = $(this).data('real-balance'), prefix = $(this).find('.balance-text').data('prefix') || '';
                        if (realBal !== undefined) { $(this).find('.balance-text').html(`${prefix}${formatRupiah(realBal)}`); }
                        let badge = $(this).find('.status-badge');
                        if(badge.length) { badge.removeClass('bg-secondary').addClass('bg-primary').html('<i class="fas fa-check-circle me-1"></i> Tersambung'); }
                    });

                    if (pendingPaymentSelection) {
                        Swal.fire({ title: 'Akses Terbuka!', text: 'Metode dipilih.', icon: 'success', showConfirmButton: false, timer: 1500 }).then(() => {
                            executePaymentSelection(pendingPaymentSelection); pendingPaymentSelection = null;
                        });
                    } else {
                        Swal.fire({ title: 'PIN Terverifikasi!', text: 'Memproses pembayaran...', icon: 'success', showConfirmButton: false, timer: 1500 }).then(() => {
                            $('#confirmBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...');
                            document.getElementById('orderForm').submit();
                        });
                    }
                } else {
                        $('#pin_error_msg').text(res.message).removeClass('d-none');
                        $('.pin-digit').val('').first().focus(); $('#full_pin_value').val('');
                        $btn.prop('disabled', false).html('Verifikasi & Bayar');
                    }
                },
                error: function() {
                    $('#pin_error_msg').text('Terjadi kesalahan koneksi server.').removeClass('d-none');
                    $btn.prop('disabled', false).html('Verifikasi & Bayar');
                }
            });
        });

        // ==========================================================
        // EVENT TOMBOL BAYAR SEKARANG (OJEK ONLINE)
        // ==========================================================
        $('#btn-pay-ojek').on('click', function(e) {
            e.preventDefault();

            // 1. Bypass Validasi Form (Isi data ekspedisi secara gaib)
            // Format: kodevendor-layananspesifik-nama-harga-asuransi-cod
            let tarifOjek = parseInt($('#ojek_summary_price').text().replace(/[^0-9]/g, '')) || 0;
            $('#expedition').val(`sancaka-ojek_online-Ojek Sancaka-${tarifOjek}-0-0`);
            $('#selected_shipping_cost').val(tarifOjek);
            $('#selected_expedition_display').val('Ojek Online Sancaka').addClass('is-valid');

            // 2. Panggil Modal Payment Gateway Tripay/Lainnya
            applyGatewayMinimumLimit();
            paymentModal.show();
            loadTripayChannels();
        });

        $('#cekOngkirWaBtn').on('click', () => {
            unmaskDataForSubmit();
            window.open(`https://wa.me/6285745808809?text=${encodeURIComponent(`Halo, saya mau tanya ongkir:\n\n*Dari:* ${$('#sender_address').val()}, ${$('#sender_village').val()}\n*Ke:* ${$('#receiver_address').val()}, ${$('#receiver_village').val()}\n*Berat:* ${$('#weight').val()} gr\n\nTerima kasih.`)}`, '_blank');
        });

        $(document).on('click', e => {
            if (!$(e.target).closest('.input-group').length && !$(e.target).closest('.ui-autocomplete').length) {
                $('.search-results-container').addClass('d-none');
            }
        });
    }
</script>
@endpush
