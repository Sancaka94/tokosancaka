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
    - Dibuat oleh Gemini
    - Fokus pada Clean UI, UX, dan Responsiveness
    ============================================
    */
    :root {
        --primary-color: #dc3545; /* Warna Merah */
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
        --font-family-sans-serif: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
        --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        --card-shadow-hover: 0 6px 16px rgba(0, 0, 0, 0.08);
        --border-radius-lg: 1rem;
        --border-radius-md: 0.5rem;
    }

    /*
    ============================================
    PERBAIKAN DESAIN: Lebar Header & Konten 90% di Layar Besar
    ============================================
    */
    @media (min-width: 1366px) {
        .container {
            max-width: 90% !important;
        }
    }

    body {
        background-color: var(--body-bg);
        font-family: var(--font-family-sans-serif);
        color: var(--text-color);
    }

    .navbar {
        background-color: var(--card-bg);
        box-shadow: var(--card-shadow);
    }

    /* PERBAIKAN: Konten tidak tertutup header */
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

    .card.step-card {
        opacity: 1;
        transform: translateY(0);
        transition: opacity 0.5s ease, transform 0.5s ease;
    }

    .card.step-card.d-none {
        opacity: 0;
        transform: translateY(20px);
        position: absolute; /* Mencegah layout shifting saat hide/show */
        width: 100%;
        visibility: hidden;
    }

    .card:hover {
        box-shadow: var(--card-shadow-hover);
    }

    .card-header {
        background-color: transparent;
        border-bottom: 1px solid var(--card-border-color);
        font-weight: 600;
        font-size: 1.1rem;
        padding: 1.25rem 1.5rem;
    }

    .card-header .fa-icon {
        color: var(--primary-color);
    }

    .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: #495057;
    }

    .input-group-text {
        background-color: #f8f9fa;
        border-color: var(--input-border-color);
        border-right: none;
        width: 45px;
        justify-content: center;
        color: var(--secondary-color);
        transition: all 0.2s ease-in-out;
    }

    .form-control, .form-select {
        border-color: var(--input-border-color);
        transition: all 0.2s ease-in-out;
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
        position: absolute;
        z-index: 1050;
        background: var(--card-bg);
        border: 1px solid var(--card-border-color);
        border-radius: var(--border-radius-md);
        max-height: 250px;
        overflow-y: auto;
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        padding: 0.5rem;
        margin-top: 0.25rem;
        list-style: none;
    }
   /* PERBAIKAN Z-INDEX & TAMPILAN AUTOCOMPLETE */
    .ui-autocomplete {
        z-index: 9999 !important; /* Paksa tampil paling depan */
        background-color: #ffffff !important;
        border: 1px solid var(--input-border-color);
        border-radius: var(--border-radius-md);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        max-height: 300px;
        overflow-y: auto;
        overflow-x: hidden;
    }
    .ui-menu-item-wrapper {
        padding: 10px 15px !important;
        border-bottom: 1px solid #f3f4f6;
        transition: background-color 0.2s;
    }

    /* Style untuk list hasil pencarian alamat KiriminAja */
    .search-result-item {
        padding: 10px 15px;
        border-bottom: 1px solid #f3f4f6;
        cursor: pointer; /* <--- INI YANG MENGUBAH KURSOR JADI TANGAN */
        transition: background-color 0.2s;
    }

    /* Efek hover untuk list hasil pencarian alamat */
    .search-result-item:hover, .ui-menu-item-wrapper.ui-state-active {
        background-color: rgba(var(--primary-rgb), 0.08);
    }

    .ui-menu-item-wrapper.ui-state-active {
        background-color: rgba(220, 53, 69, 0.1) !important; /* Warna merah tipis */
        color: var(--primary-color) !important;
        border: none;
    }
    .search-result-item:hover, .ui-menu-item-wrapper.ui-state-active {
        background-color: rgba(var(--primary-rgb), 0.08);
    }

    .btn {
        border-radius: var(--border-radius-md);
        font-weight: 600;
        padding: 0.6rem 1.2rem;
        font-size: 0.9rem;
        transition: all 0.2s ease-in-out;
    }
    .btn-lg {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
    }
    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    .btn-primary:hover {
        background-color: var(--primary-color-darker);
        border-color: var(--primary-color-darker);
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(var(--primary-rgb), 0.2);
    }
    .btn-outline-success {
        border-color: var(--success-color);
        color: var(--success-color);
    }
    .btn-outline-success:hover {
        background-color: var(--success-color);
        color: #fff;
    }

    /* Stepper */
    .stepper {
        display: flex;
        justify-content: space-around;
        position: relative;
        margin-bottom: 2.5rem;
    }
    .stepper::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 15%;
        right: 15%;
        height: 2px;
        background-color: var(--card-border-color);
        z-index: 1;
    }
    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        z-index: 2;
        background-color: var(--body-bg);
        padding: 0 0.5rem;
        width: 80px;
    }
    .step-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--card-bg);
        border: 2px solid var(--card-border-color);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.5rem;
        color: var(--secondary-color);
        font-weight: bold;
        transition: all 0.3s ease;
    }
    .step-text {
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--secondary-color);
    }
    .step.active .step-icon {
        border-color: var(--primary-color);
        color: var(--primary-color);
        background-color: rgba(var(--primary-rgb), 0.08);
    }
    .step.active .step-text {
        color: var(--primary-color);
        font-weight: 600;
    }

    /* Sticky Card for Desktop */
    @media (min-width: 992px) {
        .sticky-lg-top {
            position: sticky;
            top: 2rem;
            z-index: 1020;
        }
    }

    /*
    ============================================
    PERBAIKAN DESAIN: Modal Cek Ongkir & Deliveree
    ============================================
    */
    #ongkirModal .modal-dialog, #delivereeModal .modal-dialog {
        max-width: 90vw; /* Lebar modal 90% dari layar */
    }
    .ongkir-header-row {
        font-weight: 600;
        color: var(--secondary-color);
        font-size: 0.8rem;
        text-transform: uppercase;
        padding: 0 1rem;
        margin-bottom: 0.5rem;
    }
    .ongkir-item-card {
        display: flex;
        flex-direction: row;
        align-items: center;
        background-color: #fff;
        border: 1px solid var(--card-border-color);
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        margin-bottom: 0.75rem;
        font-size: 0.9rem;
        transition: all 0.2s ease-in-out;
    }
    .ongkir-item-card:hover {
        box-shadow: var(--card-shadow);
        transform: translateY(-2px);
        border-color: var(--primary-color);
    }

    /* Card Khusus Deliveree Hover */
    .deliveree-card:hover {
        box-shadow: 0 10px 20px rgba(25, 135, 84, 0.15) !important;
        transform: translateY(-5px);
        border-color: #198754 !important;
    }

    .lalamove-card:hover {
        box-shadow: 0 10px 20px rgba(242, 112, 36, 0.15) !important;
        transform: translateY(-5px);
        border-color: #f27024 !important;
    }

    .ongkir-item-col {
        padding: 0 10px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .ongkir-item-col .col-label { display: none; } /* Sembunyikan label di desktop */
    .col-service { flex: 0 0 35%; flex-direction: row; align-items: center; }
    .ongkir-logo { width: 60px; height: auto; object-fit: contain; margin-right: 15px; }
    .service-info { display: flex; flex-direction: column; }
    .service-name { font-weight: 600; font-size: 0.95rem; color: var(--text-color); }
    .service-type { font-size: 0.8rem; color: var(--secondary-color); }
    .col-etd { flex: 0 0 15%; text-align: center; }
    .col-cod { flex: 0 0 10%; text-align: center; }
    .col-price { flex: 0 0 20%; text-align: right; padding-right: 15px; }
    .price-value .final-price { font-weight: 700; font-size: 1rem; color: var(--success-color); }
    .price-details { font-size: 0.8rem; color: var(--secondary-color); margin-top: 2px; }
    .col-action { flex: 0 0 20%; text-align: right; }
    .btn-kirim { background-color: var(--primary-color); color: #fff; border-radius: 999px; font-weight: 600; font-size: 0.8rem; padding: 0.4rem 1rem; border: none; }
    .btn-kirim:hover { background-color: var(--primary-color-darker); color: #fff; }

    /* Responsif untuk Modal */
    @media (max-width: 991px) {
        .ongkir-item-card { flex-wrap: wrap; padding: 1rem; }
        .ongkir-item-col { padding: 5px 0; text-align: left !important; flex-basis: 50%; }
        .ongkir-item-col .col-label { display: block; font-size: 0.75rem; color: var(--secondary-color); margin-bottom: 2px; font-weight: 500; }
        .col-service { flex-basis: 70%; order: 1; flex-direction: row; }
        .col-action { flex-basis: 30%; order: 2; align-self: center; text-align: center !important;}
        .col-price { order: 3; text-align: left !important; }
        .col-etd { order: 4; }
        .col-cod { order: 5; }
    }

    #map {
            width: 100%;
            height: 380px;
            border-radius: var(--border-radius-lg);
            border: 1px solid var(--card-border-color);
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }
    </style>
    {{-- Load Mapbox GL JS Assets --}}
    {{-- Load Mapbox GL JS Assets --}}
    <script src='https://api.mapbox.com/mapbox-gl-js/v3.2.0/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v3.2.0/mapbox-gl.css' rel='stylesheet' />
@endpush

@section('content')

@include('layouts.partials.notifications')

<div class="container main-content-container">

    <div class="stepper">
        <div class="step active" id="step-indicator-1">
            <div class="step-icon">1</div>
            <div class="step-text">Data Pengirim</div>
        </div>
        <div class="step" id="step-indicator-2">
            <div class="step-icon">2</div>
            <div class="step-text">Data Penerima</div>
        </div>
        <div class="step" id="step-indicator-3">
            <div class="step-icon">3</div>
            <div class="step-text">Detail Paket & Bayar</div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex align-items-center">
            <i class="fas fa-map-marked-alt fa-icon me-2"></i> Preview Rute & Titik Peta Sancaka Express
        </div>
       <div class="card-body p-2">
            {{-- Container Peta dengan UI Toggle --}}
            <div class="card mb-4 border-primary shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
                    <span><i class="fas fa-map-marked-alt me-2"></i> Peta Lokasi (Akurat)</span>
                    <button type="button" id="btn-find-my-location" class="btn btn-light btn-sm fw-bold text-primary rounded-pill shadow-sm">
                        <i class="fas fa-crosshairs me-1"></i> Lokasi Saya
                    </button>
                </div>

                {{-- TAMBAHAN: Tombol Pemilih Mode Peta --}}
                <div class="card-body p-2 bg-light border-bottom">
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check map-mode-toggle" name="map_mode" id="mode_pengirim" value="sender" checked>
                        <label class="btn btn-outline-primary fw-bold" for="mode_pengirim">
                            <i class="fas fa-arrow-up"></i> Set Titik Pengirim
                        </label>

                        <input type="radio" class="btn-check map-mode-toggle" name="map_mode" id="mode_penerima" value="receiver">
                        <label class="btn btn-outline-danger fw-bold" for="mode_penerima">
                            <i class="fas fa-map-marker-alt"></i> Set Titik Penerima
                        </label>
                    </div>
                </div>

                <div class="card-body p-0 position-relative">
                    <div class="position-absolute top-0 start-50 translate-middle-x mt-2 z-3" style="pointer-events: none;">
                        <span class="badge bg-dark shadow-sm px-3 py-2 rounded-pill" style="opacity: 0.85;">
                            <i class="fas fa-hand-pointer me-1"></i> Klik peta / Geser pin untuk titik pas
                        </span>
                    </div>
                    <div id='map' style="width: 100%; height: 380px; border-bottom-left-radius: var(--border-radius-lg); border-bottom-right-radius: var(--border-radius-lg);"></div>
                </div>
            </div>
        </div>
    </div>

    <form id="orderForm" action="{{ route('pesanan.public.store') }}" method="POST">
        @csrf
        {{-- TAMBAHAN: Hidden Input untuk menangkap koordinat GPS perangkat pembeli publik --}}
        <input type="hidden" name="latitude" id="buyer_latitude" value="">
        <input type="hidden" name="longitude" id="buyer_longitude" value="">

        <div class="row g-4 g-lg-5">
            {{-- Kolom Kiri: Informasi Pengirim & Penerima --}}
            <div class="col-12 col-lg-7">
                <div class="d-grid gap-4 position-relative">
                    {{-- Card Pengirim --}}
                    <div class="card step-card" id="card-pengirim">
                        <div class="card-header d-flex align-items-center">
                            <i class="fas fa-arrow-up fa-icon me-2"></i> Informasi Pengirim
                        </div>
                        <div class="card-body p-3 p-md-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="sender_name" class="form-label">Nama Pengirim</label>
                                    <div class="input-group"><span class="input-group-text"><i class="fas fa-user"></i></span><input type="text" name="sender_name" id="sender_name" class="form-control" placeholder="Cari nama atau no. HP" required></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="sender_phone" class="form-label">No. HP Pengirim</label>
                                    <div class="input-group"><span class="input-group-text"><i class="fas fa-phone"></i></span><input type="text" name="sender_phone" id="sender_phone" class="form-control" placeholder="Cari nama atau no. HP" required></div>
                                </div>
                                <div class="col-12">
                                    <label for="sender_address_search" class="form-label">Cari Alamat (Kec/Kel/Kodepos)</label>
                                    <div class="input-group position-relative"><span class="input-group-text"><i class="fas fa-search-location"></i></span><input type="text" id="sender_address_search" class="form-control" placeholder="Ketik disini untuk mencari..." autocomplete="off"><div id="sender_address_results" class="search-results-container d-none w-100"></div></div>
                                </div>
                                <div class="col-12">
                                    <label for="sender_address" class="form-label">Detail Alamat Lengkap</label>
                                    <div class="input-group"><span class="input-group-text align-items-start pt-2"><i class="fas fa-map-marked-alt"></i></span><textarea name="sender_address" id="sender_address" rows="2" class="form-control" placeholder="Contoh: Jl. Merdeka No. 10, RT 01/RW 02" required></textarea></div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="save_sender" id="save_sender" value="1"><label class="form-check-label" for="save_sender">Simpan data pengirim di buku alamat</label></div>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="button" id="nextToPenerima" class="btn btn-primary w-100">Lanjutkan ke Penerima <i class="fas fa-arrow-right ms-2"></i></button>
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

                    {{-- Card Penerima (Awalnya tersembunyi) --}}
                    <div class="card step-card d-none" id="card-penerima">
                        <div class="card-header d-flex align-items-center">
                            <i class="fas fa-map-marker-alt fa-icon me-2"></i> Informasi Penerima
                        </div>
                        <div class="card-body p-3 p-md-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="receiver_name" class="form-label">Nama Penerima</label>
                                    <div class="input-group"><span class="input-group-text"><i class="fas fa-user-friends"></i></span><input type="text" name="receiver_name" id="receiver_name" class="form-control" placeholder="Cari nama atau no. HP" required></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="receiver_phone" class="form-label">No. HP Penerima</label>
                                    <div class="input-group"><span class="input-group-text"><i class="fas fa-mobile-alt"></i></span><input type="text" name="receiver_phone" id="receiver_phone" class="form-control" placeholder="Cari nama atau no. HP" required></div>
                                </div>
                                <div class="col-12">
                                    <label for="receiver_address_search" class="form-label">Cari Alamat (Kec/Kel/Kodepos)</label>
                                    <div class="input-group position-relative"><span class="input-group-text"><i class="fas fa-search-location"></i></span><input type="text" id="receiver_address_search" class="form-control" placeholder="Ketik disini untuk mencari..." autocomplete="off"><div id="receiver_address_results" class="search-results-container d-none w-100"></div></div>
                                </div>
                                <div class="col-12">
                                    <label for="receiver_address" class="form-label">Detail Alamat Lengkap</label>
                                    <div class="input-group"><span class="input-group-text align-items-start pt-2"><i class="fas fa-map-marked-alt"></i></span><textarea name="receiver_address" id="receiver_address" rows="2" class="form-control" placeholder="Contoh: Jl. Pahlawan No. 21, Dusun Mawar" required></textarea></div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="save_receiver" id="save_receiver" value="1"><label class="form-check-label" for="save_receiver">Simpan data penerima di buku alamat</label></div>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="button" id="nextToPaket" class="btn btn-primary w-100">Lanjutkan ke Detail Paket <i class="fas fa-arrow-right ms-2"></i></button>
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
            </div>

            {{-- Kolom Kanan: Detail Paket & Aksi (Awalnya tersembunyi) --}}
            <div class="col-12 col-lg-5 d-none" id="card-paket">
                <div class="card sticky-lg-top">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-box-open fa-icon me-2"></i> Detail Paket & Pengiriman
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3">
                            <div class="col-12"><label for="item_description" class="form-label">Deskripsi Barang</label><div class="input-group"><span class="input-group-text"><i class="fas fa-tag"></i></span><input type="text" name="item_description" id="item_description" class="form-control" placeholder="Contoh: Baju, Sepatu, Dokumen" required></div></div>
                            <div class="col-md-6"><label for="item_price" class="form-label">Harga Barang</label><div class="input-group"><span class="input-group-text fw-bold">Rp</span><input type="number" name="item_price" id="item_price" class="form-control" placeholder="50000" required min="1"></div></div>
                            <div class="col-md-6"><label for="weight" class="form-label">Berat</label><div class="input-group"><span class="input-group-text"><i class="fas fa-weight-hanging"></i></span><input type="number" name="weight" id="weight" class="form-control" placeholder="1000" required min="1"><span class="input-group-text">gr</span></div></div>
                            <div class="col-12"><label class="form-label mb-2">Dimensi (Opsional)</label><div class="row g-2"><div class="col-4"><div class="input-group"><span class="input-group-text">P</span><input type="number" name="length" id="length" class="form-control" placeholder="cm"></div></div><div class="col-4"><div class="input-group"><span class="input-group-text">L</span><input type="number" name="width" id="width" class="form-control" placeholder="cm"></div></div><div class="col-4"><div class="input-group"><span class="input-group-text">T</span><input type="number" name="height" id="height" class="form-control" placeholder="cm"></div></div></div></div>

                            {{-- MODIFIKASI: Layout Pembagian Dropdown Jenis Layanan & Vendor --}}
                            <div class="col-md-4">
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

                            <div class="col-md-4">
                                <label for="service_type" class="form-label">Jenis Layanan</label>
                                <select name="service_type" id="service_type" class="form-select" required>
                                    <option value="regular" selected>Regular</option>
                                    <option value="cargo">Cargo</option>
                                    <option value="instant">Instant / Sameday</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="vendor_filter" class="form-label">Pilihan Ekspedisi</label>
                                    <select id="vendor_filter" class="form-select border-primary bg-light fw-bold text-dark">
                                        <option value="all" selected>Semua</option>
                                        <option value="sancaka_express" class="text-danger fw-bold">Sancaka Express</option> <!-- TAMBAHKAN INI -->
                                        <option value="deliveree" class="text-success fw-bold">Deliveree</option>
                                        <option value="lalamove" class="fw-bold" style="color: #f27024;">Lalamove</option>
                                        <option value="ipaymu" class="fw-bold text-white" style="background-color: #6f42c1;">iPaymu (COD Khusus)</option>
                                    </select>
                            </div>

                           <div class="col-12">
                                <label for="ansuransi" class="form-label">Asuransi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                                    <select name="ansuransi" id="ansuransi" class="form-select" required>
                                        <option value="tidak" selected>Tidak Pakai Asuransi</option>
                                        <option value="iya">Ya, Pakai Asuransi</option>
                                    </select>
                                </div>
                                <!-- TAMBAHAN: Checkbox TOS Asuransi (Disembunyikan default) -->
                                <div id="tos_asuransi_container" class="form-check mt-2 d-none">
                                    <input class="form-check-input" type="checkbox" id="tos_asuransi" name="tos_asuransi">
                                    <label class="form-check-label small text-muted" for="tos_asuransi">
                                        Saya setuju dengan <a href="https://www.delivereetech.com/id/en/goods-insurance-bp/" target="_blank" class="text-success fw-bold text-decoration-none">Kebijakan Asuransi Barang</a>.
                                    </label>
                                </div>
                            </div>
                            <div class="col-12"><hr class="my-3"></div>

                            {{-- START KODE BARU: OPSI EXTRA DELIVEREE --}}
                            <div class="col-12 d-none" id="deliveree_extra_section">
                                <div class="p-3 mt-2 rounded border border-success" style="background-color: #f4f7f6;">
                                    <h6 class="fw-bold text-success mb-2"><i class="fas fa-star me-1"></i> Layanan Tambahan (Deliveree)</h6>

                                    <!-- Driver Preferences -->
                                    <label class="form-label text-muted small mb-1">Preferensi Pengemudi</label>
                                    <select name="driver_preference" id="driver_preference" class="form-select form-select-sm mb-3">
                                        <option value="all" selected>Semua Pengemudi (Tercepat)</option>
                                        <option value="favorite">Hanya Pengemudi Favorit</option>
                                    </select>

                                   <div class="form-check form-switch mb-2">
                                        <input class="form-check-input deliveree-extra-toggle" type="checkbox" name="extra_helper_driver" id="extra_helper_driver" value="1">
                                        <label class="form-check-label small fw-bold" for="extra_helper_driver">
                                            Bantuan Pengemudi <i class="fas fa-info-circle text-muted" title="Sopir bantu angkut barang"></i>
                                            <span id="helper_driver_price_text" class="text-success ms-1" style="font-size: 0.75rem;">(Pilih armada dulu)</span>
                                        </label>
                                    </div>

                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input deliveree-extra-toggle" type="checkbox" name="extra_helper_extra" id="extra_helper_extra" value="1">
                                        <label class="form-check-label small fw-bold" for="extra_helper_extra">
                                            Bantuan Tambahan <i class="fas fa-info-circle text-muted" title="Kenek tambahan untuk angkut barang"></i>
                                            <span id="helper_extra_price_text" class="text-success ms-1" style="font-size: 0.75rem;">(Pilih armada dulu)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            {{-- END KODE BARU --}}

                           {{-- PENAMBAHAN: Hidden input untuk menampung tarif ongkir yang terpilih guna keperluan kalkulasi limit minimum --}}
                            <div class="col-12">
                                <label for="selected_expedition_display" class="form-label">Pilih Ekspedisi</label>
                                <input type="text" id="selected_expedition_display" class="form-control text-start fw-bold" placeholder="Lengkapi data & klik di sini untuk Cek Tarif" readonly required style="cursor:pointer; background-color: #f8f9fa;">
                                <input type="hidden" name="expedition" id="expedition" required>

                                <input type="hidden" id="selected_shipping_cost" value="0">
                                <input type="hidden" id="selected_insurance_cost" value="0">
                                <input type="hidden" id="selected_cod_fee" value="0">
                                <input type="hidden" id="selected_helper_driver_fee" value="0">
                                <input type="hidden" id="selected_helper_extra_fee" value="0">
                                <input type="hidden" name="deliveree_helper_driver_id" id="deliveree_helper_driver_id">
                                <input type="hidden" name="deliveree_helper_extra_id" id="deliveree_helper_extra_id">
                            </div>

                            {{-- TAMBAHAN: Kotak Monitor Total Pembayaran --}}
                            <div class="col-12 my-2">
                                <div class="p-3 rounded" style="background-color: #f8f9fa; border: 1px dashed #ced4da;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted" style="font-size: 0.85rem;">Harga Barang</span>
                                        <span id="summary_item_price" class="fw-bold text-secondary" style="font-size: 0.85rem;">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted" style="font-size: 0.85rem;">Tarif Ongkir Dasar</span>
                                        <span id="summary_shipping_cost" class="fw-bold text-secondary" style="font-size: 0.85rem;">Rp 0</span>
                                    </div>

                                    <div id="summary_insurance_row" class="justify-content-between align-items-center mb-1 d-none">
                                        <span class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-shield-alt text-success me-1"></i> Asuransi Barang</span>
                                        <span id="summary_insurance_cost" class="fw-bold text-success" style="font-size: 0.85rem;">Rp 0</span>
                                    </div>

                                    <div id="summary_helper_driver_row" class="justify-content-between align-items-center mb-1 d-none">
                                        <span class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-user text-success me-1"></i> Bantuan Pengemudi</span>
                                        <span id="summary_helper_driver_cost" class="fw-bold text-success" style="font-size: 0.85rem;">(Menunggu Cek Tarif)</span>
                                    </div>

                                    <div id="summary_helper_extra_row" class="justify-content-between align-items-center mb-2 d-none">
                                        <span class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-user-plus text-success me-1"></i> Bantuan Tambahan</span>
                                        <span id="summary_helper_extra_cost" class="fw-bold text-success" style="font-size: 0.85rem;">(Menunggu Cek Tarif)</span>
                                    </div>

                                    <hr class="my-2" style="border-color: #ced4da;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-dark">Total Pembayaran</span>
                                        <span id="summary_total_cost" class="fw-bold text-danger" style="font-size: 1.15rem;">Rp 0</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12"><label for="paymentMethodButton" class="form-label">Metode Pembayaran</label><div id="paymentMethodButton" class="form-control d-flex justify-content-between align-items-center" style="cursor: pointer;"><div class="d-flex align-items-center"><i id="defaultPaymentIcon" class="fas fa-credit-card fa-lg me-3 text-muted"></i><img id="selectedPaymentLogo" src="" alt="Logo" class="me-2 d-none" style="width:50px; height:25px; object-fit:contain;"><span id="selectedPaymentName">Pilih Pembayaran...</span></div><i class="fas fa-chevron-down text-muted"></i></div><input type="hidden" name="payment_method" id="payment_method" required></div>
                            <div class="d-grid gap-2 mt-4"><button type="button" id="confirmBtn" class="btn btn-primary btn-lg"><i class="fas fa-paper-plane me-2"></i>Buat Pesanan Sekarang</button><button type="button" id="cekOngkirWaBtn" class="btn btn-outline-success"><i class="fab fa-whatsapp me-2"></i>Tanya Ongkir via WA</button></div>
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
                <div id="ongkirResultsContainer">
                    {{-- Hasil ongkir Reguler akan dimuat di sini --}}
                </div>
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
                <div id="delivereeResultsContainer" class="row g-3">
                    {{-- Grid hasil ongkir Deliveree akan dimuat di sini --}}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL KHUSUS LALAMOVE --}}
<div class="modal fade" id="lalamoveModal" tabindex="-1">
    {{-- PERBAIKAN: Hilangkan modal-lg agar lebar modal bisa menyesuaikan isi --}}
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: #f27024;">
                <h5 class="modal-title fw-bold"><i class="fas fa-motorcycle me-2"></i>Pilihan Armada Lalamove</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background-color: #fffaf7;">
                {{-- PERBAIKAN: Tambahkan justify-content-center agar card berada di tengah --}}
                <div id="lalamoveResultsContainer" class="row g-3 justify-content-center">
                    {{-- Grid hasil ongkir Lalamove akan dimuat di sini --}}
                </div>
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
                <div id="ipaymuResultsContainer" class="row g-3 justify-content-center">
                    {{-- Grid hasil ongkir iPaymu akan dimuat di sini via AJAX --}}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Metode Pembayaran --}}
<div class="modal fade" id="paymentMethodModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-credit-card me-2 text-danger"></i>Pilih Metode Pembayaran
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <ul id="paymentOptionsList" class="list-group list-group-flush" style="cursor: pointer;">

                    @auth
                    {{-- 1. OPSI INTERNAL: SALDO SANCAKA (Hanya jika Login) --}}
                    <li class="list-group-item bg-light fw-bold text-muted border-bottom-0" style="font-size: 0.75rem; text-transform: uppercase;">
                        Dompet Sancaka
                    </li>
                    <li class="list-group-item list-group-item-action d-flex align-items-center requires-pin" data-value="Potong Saldo" data-label="Potong Saldo" data-real-balance="{{ Auth::user()->saldo ?? 0 }}">
                        <img src="{{ asset('public/assets/saldo.png') }}" class="me-3" style="width: 40px; height: 40px; object-fit: contain;">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">Potong Saldo</div>
                            <div class="text-muted balance-text" style="font-size: 0.75rem;" data-prefix="Tersedia: ">
                                Tersedia: Rp *** <i class="fas fa-lock ms-1" style="font-size: 0.7rem;"></i>
                            </div>
                        </div>
                    </li>
                    @endauth

                    {{-- 2. OPSI INTERNAL: COD --}}
                    <li class="list-group-item bg-light fw-bold text-muted border-top border-bottom-0" style="font-size: 0.75rem; text-transform: uppercase;">
                        Bayar Di Tempat (Otomatis)
                    </li>
                    <li class="list-group-item list-group-item-action d-flex align-items-center cod-payment-option" data-value="COD" data-label="COD Ongkir">
                        <img src="{{ asset('public/assets/cod.png') }}" class="me-3" style="width: 40px; height: 40px; object-fit: contain;">
                        <div class="fw-bold text-dark" style="font-size: 0.95rem;">COD Ongkir</div>
                    </li>
                    <li class="list-group-item list-group-item-action d-flex align-items-center cod-payment-option" data-value="CODBARANG" data-label="COD Barang + Ongkir">
                        <img src="{{ asset('public/assets/cod.png') }}" class="me-3" style="width: 40px; height: 40px; object-fit: contain;">
                        <div class="fw-bold text-dark" style="font-size: 0.95rem;">COD Barang + Ongkir</div>
                    </li>

                    @auth
                    {{-- 3. OPSI DANA AUTO-DEBIT / BINDING (Hanya jika Login) --}}
                    <li class="list-group-item bg-light fw-bold text-muted border-top border-bottom-0" style="font-size: 0.75rem; text-transform: uppercase;">
                        E-Wallet Auto Debit
                    </li>
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
                                <div class="text-muted balance-text" style="font-size: 0.75rem;" data-prefix="Saldo DANA: ">
                                    Saldo DANA: Rp *** <i class="fas fa-lock ms-1" style="font-size: 0.7rem;"></i>
                                </div>
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

                    {{-- 4. OPSI PAYMENT GATEWAY UTAMA (DOKU, MIDTRANS, DANA WEB) --}}
                    {{-- PENAMBAHAN: Ditambahkan class "gateway-option" untuk di target saat validasi limit minimum --}}
                    <li class="list-group-item bg-light fw-bold text-muted border-top border-bottom-0" style="font-size: 0.75rem; text-transform: uppercase;">
                        Payment Gateway Terintegrasi
                    </li>
                    <li class="list-group-item list-group-item-action d-flex align-items-center gateway-option" data-value="DOKU_JOKUL" data-label="Doku (Rekomendasi)">
                        <img src="{{ asset('public/assets/doku.png') }}" class="me-3 border rounded p-1 bg-white" style="width: 40px; height: 40px; object-fit: contain;">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">DOKU (Rekomendasi Sancaka)</div>
                            <div class="text-muted" style="font-size: 0.75rem;">VA, QRIS, E-Wallet, CC</div>
                        </div>
                    </li>

                    {{-- OPSI PAYPAL (GLOBAL PAYMENT) --}}
                    <li class="list-group-item list-group-item-action d-flex align-items-center gateway-option" data-value="PAYPAL" data-label="PayPal / Credit Card">
                        <img src="https://tokosancaka.com/public/assets/paypal.png" class="me-3 border rounded p-1 bg-white" style="width: 40px; height: 40px; object-fit: contain;" onerror="this.src='https://placehold.co/40x40/EFEFEF/AAAAAA?text=PP'">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">PayPal / Kartu Kredit</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Pembayaran Global (Otomatis konversi USD)</div>
                        </div>
                    </li>

                    <li class="list-group-item list-group-item-action d-flex align-items-center gateway-option" data-value="DANA" data-label="DANA (Web Checkout)">
                        <img src="{{ asset('public/assets/dana.webp') }}" class="me-3 border rounded p-1 bg-white" style="width: 40px; height: 40px; object-fit: contain;">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">DANA (Checkout Gapura)</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Diarahkan ke web/aplikasi DANA</div>
                        </div>
                    </li>

                    <li class="list-group-item list-group-item-action d-flex align-items-center gateway-option" data-value="IPAYMU" data-label="iPaymu">
                        <img src="https://tokosancaka.com/public/assets/ipaymu.jpg" class="me-3 border rounded p-1 bg-white" style="width: 40px; height: 40px; object-fit: contain;" onerror="this.src='https://placehold.co/40x40/EFEFEF/AAAAAA?text=IP'">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">iPaymu (Virtual Account & QRIS)</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Transfer Bank, E-Wallet, Retail (Alfamart/Indomaret)</div>
                        </div>
                    </li>

                    {{-- 5. OPSI TRIPAY (DIMUAT DINAMIS MELALUI API) --}}
                    <li class="list-group-item bg-light fw-bold text-muted border-top border-bottom-0" style="font-size: 0.75rem; text-transform: uppercase;">
                        Saluran Pembayaran Lainnya (Tripay)
                    </li>
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

{{-- Modal Input PIN M-Banking Style --}}
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
{{-- Pustaka jQuery & jQuery UI --}}
<script src="https://code.jquery.com/jquery-3.6.0.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
{{-- SweetAlert untuk notifikasi --}}
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
        // 1. INTEGRASI MAPBOX: FITUR GRAB / GOJEK STYLE DENGAN MAPBOX STANDARD 3D
        // ==============================================================================
        // Gunakan MAPBOX_PUBLIC_TOKEN untuk sisi Client/Browser
        const mapboxToken = '{{ \App\Models\Api::getValue("MAPBOX_PUBLIC_TOKEN", "global") }}';

        let map, senderMarker, receiverMarker;

        if (mapboxToken && mapboxToken.trim() !== '') {
            mapboxgl.accessToken = mapboxToken;

            map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/standard', // 👈 Pakai tema Standard 3D terbaru
                center: [111.4558, -7.4025],
                zoom: 15,
                pitch: 45,
                bearing: -15,
                attributionControl: false,
                config: {
                    basemap: {
                        lightPreset: "dusk",
                        show3dObjects: true,
                        showPedestrianRoads: false
                    }
                }
            });

            // Tambahkan Kontrol Zoom & Rotasi
            map.addControl(new mapboxgl.NavigationControl(), 'bottom-right');

            // Tambahkan Kontrol GPS Bawaan Mapbox
            const geolocateControl = new mapboxgl.GeolocateControl({
                positionOptions: { enableHighAccuracy: true },
                trackUserLocation: true,
                showUserHeading: true
            });
            map.addControl(geolocateControl, 'bottom-right');

            // Tambahkan Label (Popup) agar jelas mana Pengirim dan Penerima
            const senderPopup = new mapboxgl.Popup({ offset: 25, closeButton: false, closeOnClick: false })
                .setHTML('<div class="fw-bold text-primary mb-1"><i class="fas fa-arrow-up"></i> PENGIRIM</div><small class="text-muted">Tarik pin biru ini</small>');

            const receiverPopup = new mapboxgl.Popup({ offset: 25, closeButton: false, closeOnClick: false })
                .setHTML('<div class="fw-bold text-danger mb-1"><i class="fas fa-map-marker-alt"></i> PENERIMA</div><small class="text-muted">Tarik pin merah ini</small>');

            // Marker Pengirim (Biru - Titik Jemput)
            senderMarker = new mapboxgl.Marker({ color: '#007bff', draggable: true })
                .setLngLat([111.4558, -7.4025])
                .setPopup(senderPopup)
                .addTo(map);

            // Marker Penerima (Merah - Titik Antar)
            receiverMarker = new mapboxgl.Marker({ color: '#dc3545', draggable: true })
                .setLngLat([111.4650, -7.4100])
                .setPopup(receiverPopup)
                .addTo(map);

            // Tampilkan label popup secara otomatis
            senderMarker.togglePopup();
            receiverMarker.togglePopup();

            // Fungsi Reverse Geocoding (Ubah Koordinat jadi Teks Alamat)
            function reverseGeocode(lat, lng, prefix) {
                $.ajax({
                    url: `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,
                    success: function(data) {
                        if (data && data.display_name) {
                            $(`#${prefix}_address_search`).val(data.display_name).addClass('is-valid');
                            if(data.address) {
                                const desa = data.address.village || data.address.neighbourhood || '';
                                const kec = data.address.town || data.address.city_district || '';
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
                    }
                });
            }

            // Fungsi saat marker digeser manual (Drag)
            function updateInputsFromMarker(prefix, marker) {
                const lngLat = marker.getLngLat();
                $(`#${prefix}_lat`).val(lngLat.lat);
                $(`#${prefix}_lng`).val(lngLat.lng).trigger('change');
                reverseGeocode(lngLat.lat, lngLat.lng, prefix);
            }

            senderMarker.on('dragend', () => updateInputsFromMarker('sender', senderMarker));
            receiverMarker.on('dragend', () => updateInputsFromMarker('receiver', receiverMarker));

            // Event Listener saat Peta Di-Klik
            map.on('click', function(e) {
                const activeMode = $('input[name="map_mode"]:checked').val();
                if (activeMode === 'receiver') {
                    receiverMarker.setLngLat(e.lngLat);
                    updateInputsFromMarker('receiver', receiverMarker);
                } else {
                    senderMarker.setLngLat(e.lngLat);
                    updateInputsFromMarker('sender', senderMarker);
                }
            });

            // Fokuskan peta ke marker yang dipilih saat tombol toggle ditekan
            $('.map-mode-toggle').on('change', function() {
                const mode = $(this).val();
                if(mode === 'receiver') {
                    map.flyTo({ center: receiverMarker.getLngLat(), zoom: 16 });
                } else {
                    map.flyTo({ center: senderMarker.getLngLat(), zoom: 16 });
                }
            });

            $('#btn-find-my-location').on('click', function(e) {
                e.preventDefault();
                geolocateControl.trigger();
                $(this).html('<i class="fas fa-spinner fa-spin me-1"></i> Mencari...');
            });

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
            });

            map.on('load', function() {
                geolocateControl.trigger();
            });

            window.syncMarkerFromInputs = function(prefix) {
                const lat = parseFloat($(`#${prefix}_lat`).val());
                const lng = parseFloat($(`#${prefix}_lng`).val());
                if (lat && lng) {
                    if (prefix === 'sender') {
                        senderMarker.setLngLat([lng, lat]);
                    } else {
                        receiverMarker.setLngLat([lng, lat]);
                    }
                    map.flyTo({ center: [lng, lat], zoom: 16, essential: true });
                }
            };

        } else {
            console.warn("LOG LOG: Token Mapbox tidak ditemukan.");
            $('#map').html('<div class="p-4 text-center text-muted">Peta tidak tersedia (Token belum diatur).</div>');
        }

        // Tangkap GPS Pembeli (User)
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    $('#buyer_latitude').val(position.coords.latitude);
                    $('#buyer_longitude').val(position.coords.longitude);

                    // Set marker pengirim ke lokasi pembeli saat ini
                    if (map && senderMarker) {
                        senderMarker.setLngLat([position.coords.longitude, position.coords.latitude]);
                        map.setCenter([position.coords.longitude, position.coords.latitude]);
                        $(`#sender_lat`).val(position.coords.latitude);
                        $(`#sender_lng`).val(position.coords.longitude);
                    }
                },
                function(error) {
                    console.warn("LOG LOG: Akses GPS Pembeli ditolak atau bermasalah: " + error.message);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        // Tangkap GPS Pembeli (User)
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    $('#buyer_latitude').val(position.coords.latitude);
                    $('#buyer_longitude').val(position.coords.longitude);

                    // Set marker pengirim ke lokasi pembeli saat ini
                    if (map && senderMarker) {
                        senderMarker.setLngLat([position.coords.longitude, position.coords.latitude]);
                        map.setCenter([position.coords.longitude, position.coords.latitude]);
                        $(`#sender_lat`).val(position.coords.latitude);
                        $(`#sender_lng`).val(position.coords.longitude);
                    }
                },
                function(error) {
                    console.warn("LOG LOG: Akses GPS Pembeli ditolak atau bermasalah: " + error.message);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }
        // ==============================================================================

        function validateStep(stepCardId) {
            let isValid = true;
            $(`#${stepCardId} [required]`).each(function() {
                if ($(this).is(':visible') && !$(this).val()) {
                    isValid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            return isValid;
        }

        // Hitung real-time penambahan/pengurangan biaya Extra Helper
        $(document).on('change', '#extra_helper_driver, #extra_helper_extra', function() {
            let driverFee = $('#extra_helper_driver').is(':checked') ? ($('#extra_helper_driver').data('price') || 0) : 0;
            let extraFee = $('#extra_helper_extra').is(':checked') ? ($('#extra_helper_extra').data('price') || 0) : 0;

            $('#selected_helper_driver_fee').val(driverFee);
            $('#selected_helper_extra_fee').val(extraFee);

            updateTotalSummary();
        });

        $('form [required]').on('input', function() {
            if ($(this).val()) {
                $(this).removeClass('is-invalid');
            }
        });

        $('#nextToPenerima').on('click', function() {
            if (validateStep('card-pengirim')) {
                $('#step-indicator-2').addClass('active');
                $('#card-penerima').removeClass('d-none');
                document.getElementById('card-penerima').scrollIntoView({ behavior: 'smooth', block: 'start' });

                // BARIS INI PENTING: Pindah mode peta otomatis ke Penerima
                $('#mode_penerima').prop('checked', true).trigger('change');
            } else {
                Swal.fire('Data Tidak Lengkap', 'Harap isi semua informasi pengirim yang wajib diisi.', 'warning');
            }
        });

        $('#nextToPaket').on('click', function() {
            if (validateStep('card-penerima')) {
                $('#step-indicator-3').addClass('active');
                $('#card-paket').removeClass('d-none');
                document.getElementById('card-paket').scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                Swal.fire('Data Tidak Lengkap', 'Harap isi semua informasi penerima yang wajib diisi.', 'warning');
            }
        });

        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        @if ($errors->any())
            let errorHtml = '<ul class="list-unstyled text-start mb-0" style="padding-left: 1rem;">';
            @foreach ($errors->all() as $error)
                errorHtml += '<li class="mb-1"><i class="fas fa-exclamation-circle me-2 text-danger"></i>{{ $error }}</li>';
            @endforeach
            errorHtml += '</ul>';
            Swal.fire({ title: 'Data Tidak Valid!', html: errorHtml, icon: 'error', confirmButtonColor: '#dc2626' });
        @endif
        @if(session('success'))
            Swal.fire({ title: 'Berhasil!', text: "{{ session('success') }}", icon: 'success', confirmButtonColor: '#16a34a' });
        @endif
        @if(session('error'))
            Swal.fire({ title: 'Gagal!', text: @json(session('error')), icon: 'error', confirmButtonColor: '#dc2626' });
        @endif

        const ongkirModal = new bootstrap.Modal(document.getElementById('ongkirModal'));
        const delivereeModal = new bootstrap.Modal(document.getElementById('delivereeModal'));
        const paymentModal = new bootstrap.Modal(document.getElementById('paymentMethodModal'));
        const lalamoveModal = new bootstrap.Modal(document.getElementById('lalamoveModal'));
        const ipaymuModal = new bootstrap.Modal(document.getElementById('ipaymuModal'));

        let searchTimeout = null;
        const debounce = (func, delay) => (...args) => { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => func.apply(this, args), delay); };
        function formatRupiah(angka) { return 'Rp ' + (parseInt(angka, 10) || 0).toLocaleString('id-ID'); }

       function updateTotalSummary() {
            let rawItemPrice = $('#item_price').val() || "0";
            let itemPrice = parseInt(rawItemPrice.replace(/\D/g, '')) || 0;

            let baseShippingCost = parseInt($('#selected_shipping_cost').val()) || 0;
            let insuranceCost = parseInt($('#selected_insurance_cost').val()) || 0;
            let codFee = parseInt($('#selected_cod_fee').val()) || 0;
            let helperDriverFee = parseInt($('#selected_helper_driver_fee').val()) || 0;
            let helperExtraFee = parseInt($('#selected_helper_extra_fee').val()) || 0;

            let paymentMethod = $('#payment_method').val();

            let finalShippingCost = baseShippingCost + insuranceCost + helperDriverFee + helperExtraFee;
            let total = finalShippingCost;

            if (paymentMethod === 'COD' || paymentMethod === 'CODBARANG') {
                finalShippingCost = finalShippingCost + codFee;
                total = finalShippingCost;
            }

            if (paymentMethod === 'CODBARANG') {
                total = itemPrice + finalShippingCost;
            }

            $('#summary_item_price').text(formatRupiah(itemPrice));
            $('#summary_shipping_cost').text(formatRupiah(baseShippingCost));

            if ($('#ansuransi').val() === 'iya') {
                $('#summary_insurance_row').removeClass('d-none').addClass('d-flex');
                $('#tos_asuransi_container').removeClass('d-none');
                $('#tos_asuransi').prop('required', true);
                $('#summary_insurance_cost').text(baseShippingCost === 0 ? '(Menunggu Cek Tarif)' : (insuranceCost > 0 ? formatRupiah(insuranceCost) : 'Gratis')).toggleClass('text-muted', baseShippingCost === 0).toggleClass('text-success', baseShippingCost !== 0);
            } else {
                $('#summary_insurance_row').addClass('d-none').removeClass('d-flex');
                $('#tos_asuransi_container').addClass('d-none');
                $('#tos_asuransi').prop('required', false).prop('checked', false);
            }

            if ($('#extra_helper_driver').is(':checked') && $('#vendor_filter').val() === 'deliveree') {
                $('#summary_helper_driver_row').removeClass('d-none').addClass('d-flex');
                $('#summary_helper_driver_cost').text(baseShippingCost === 0 ? '(Menunggu Cek Tarif)' : (helperDriverFee > 0 ? formatRupiah(helperDriverFee) : 'Gratis')).toggleClass('text-muted', baseShippingCost === 0).toggleClass('text-success', baseShippingCost !== 0);
            } else {
                $('#summary_helper_driver_row').addClass('d-none').removeClass('d-flex');
            }

            if ($('#extra_helper_extra').is(':checked') && $('#vendor_filter').val() === 'deliveree') {
                $('#summary_helper_extra_row').removeClass('d-none').addClass('d-flex');
                $('#summary_helper_extra_cost').text(baseShippingCost === 0 ? '(Menunggu Cek Tarif)' : (helperExtraFee > 0 ? formatRupiah(helperExtraFee) : 'Gratis')).toggleClass('text-muted', baseShippingCost === 0).toggleClass('text-success', baseShippingCost !== 0);
            } else {
                $('#summary_helper_extra_row').addClass('d-none').removeClass('d-flex');
            }

            $('#summary_total_cost').text(formatRupiah(total));
        }

        $('#ansuransi').on('change', function() {
            if ($(this).val() === 'iya') {
                $('#tos_asuransi_container').removeClass('d-none');
                $('#tos_asuransi').prop('required', true);
            } else {
                $('#tos_asuransi_container').addClass('d-none');
                $('#tos_asuransi').prop('required', false);
                $('#tos_asuransi').prop('checked', false);
            }
        });

        $('#item_price').on('input', function() {
            updateTotalSummary();
        });

        function maskData(type, value) { if (!value) return '***'; if (type === 'name') { const parts = value.split(' '); return parts.length > 1 ? parts[0] + ' ' + parts.slice(1).map(p => p.replace(/./g, '*')).join(' ') : (value.length > 2 ? value.substring(0, 2) + '***' : value); } if (type === 'phone') { const num = value.replace(/\D/g, ''); return num.length > 8 ? num.substring(0, 3) + '****' + num.substring(num.length - 4) : num.substring(0, 3) + '****'; } if (type === 'address') { const parts = value.split(' '); return parts.length > 2 ? parts.slice(0, 2).join(' ') + ' **** **** ****' : value; } return '***'; }
        function clearHiddenAddress(prefix) { $(`#${prefix}_province, #${prefix}_regency, #${prefix}_district, #${prefix}_village, #${prefix}_postal_code, #${prefix}_district_id, #${prefix}_subdistrict_id, #${prefix}_lat, #${prefix}_lng`).val(''); }

        function fallbackGeocode(prefix, kelurahan, kecamatan, kabupaten) {
            let queries = [];
            if (kelurahan && kecamatan && kabupaten) queries.push(`${kelurahan}, ${kecamatan}, ${kabupaten}`);
            if (kecamatan && kabupaten) queries.push(`${kecamatan}, ${kabupaten}`);
            if (kabupaten) queries.push(`${kabupaten}`);

            function attemptGeo(index) {
                if (index >= queries.length) {
                    console.warn(`LOG LOG: Geocode Frontend Gagal Total untuk ${prefix}`);
                    return;
                }
                let q = queries[index];
                $.ajax({
                    url: 'https://nominatim.openstreetmap.org/search',
                    data: { q: q, format: 'json', limit: 1, countrycodes: 'id' },
                    success: function(res) {
                        if (res && res.length > 0) {
                            console.log(`LOG LOG: Geocode Frontend Sukses [${q}] -> Lat: ${res[0].lat}, Lng: ${res[0].lon}`);
                            $(`#${prefix}_lat`).val(res[0].lat);
                            $(`#${prefix}_lng`).val(res[0].lon);

                            // Sinkronkan marker Mapbox
                            if(typeof window.syncMarkerFromInputs === 'function') window.syncMarkerFromInputs(prefix);
                        } else {
                            attemptGeo(index + 1);
                        }
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
                        const item = results[0];
                        const parts = item.full_address.split(',').map(s => s.trim());
                        $(`#${prefix}_village`).val(parts[0] || data.village).trigger('change');
                        $(`#${prefix}_district`).val(parts[1] || data.district).trigger('change');
                        $(`#${prefix}_regency`).val(parts[2] || data.regency).trigger('change');
                        $(`#${prefix}_province`).val(parts[3] || data.province).trigger('change');
                        $(`#${prefix}_postal_code`).val(parts[4] || data.postal_code).trigger('change');
                        $(`#${prefix}_district_id`).val(item.district_id).trigger('change');
                        $(`#${prefix}_subdistrict_id`).val(item.subdistrict_id).trigger('change');

                        let lat = parseFloat(item.lat);
                        let lon = parseFloat(item.lon);
                        if (!lat || !lon || lat === 0 || lon === 0) {
                            fallbackGeocode(prefix, parts[0] || data.village, parts[1] || data.district, parts[2] || data.regency);
                        } else {
                            $(`#${prefix}_lat`).val(lat);
                            $(`#${prefix}_lng`).val(lon);
                            // Sinkronkan marker Mapbox
                            if(typeof window.syncMarkerFromInputs === 'function') window.syncMarkerFromInputs(prefix);
                        }

                        addressSearchInput.val('Alamat Ditemukan (Privasi Terjaga)').addClass('is-valid').removeClass('is-invalid');
                        setTimeout(() => addressSearchInput.removeClass('is-valid'), 2500);
                    } else {
                        addressSearchInput.val('').addClass('is-invalid').removeClass('is-valid');
                        Swal.fire({ title: 'Alamat Tidak Ditemukan', text: `Detail alamat untuk "${addressQuery}" tidak ditemukan. Anda wajib mencari alamat secara manual.`, icon: 'warning' }).then(() => addressSearchInput.focus());
                    }
                }).fail(() => {
                    addressSearchInput.val('').addClass('is-invalid').removeClass('is-valid');
                }).always(() => addressSearchInput.prop('disabled', false));
            } else {
                addressSearchInput.val('').addClass('is-invalid');
            }
        }

        function setupContactSearch(prefix) {
            $(`#${prefix}_name, #${prefix}_phone`).each(function() {
                $(this).autocomplete({
                    source: function(request, response) {
                        $.ajax({
                            url: "{{ route('api.search.kontak') }}",
                            dataType: "json",
                            data: { term: request.term },
                            success: function(data) {
                                if (!data || !data.length) {
                                    response([{ label: 'Tidak ada data', disabled: true }]);
                                    return;
                                }
                                response($.map(data, function(item) {
                                    return { label: item.nama, value: item.nama, data: item };
                                }));
                            },
                            error: function() { response([{ label: 'Gagal mengambil data', disabled: true }]); }
                        });
                    },
                    minLength: 2,
                    select: function(event, ui) {
                        if (ui.item.disabled) return false;
                        event.preventDefault();
                        fillContactForm(prefix, ui.item.data);
                    }
                }).autocomplete("instance")._renderItem = function(ul, item) {
                    if (item.disabled) {
                        return $("<li class='ui-state-disabled p-2 text-muted text-center'></li>").text(item.label).appendTo(ul);
                    }
                    const maskedName = maskData('name', item.data.nama);
                    const maskedPhone = maskData('phone', item.data.no_hp);

                    return $("<li>").append(`
                        <div class="ui-menu-item-wrapper">
                            <div class="fw-bold text-dark">${maskedName}</div>
                            <small class="text-muted"><i class="fas fa-phone me-1"></i>${maskedPhone}</small>
                        </div>
                    `).appendTo(ul);
                };
            });
        }

        setupContactSearch('sender');
        setupContactSearch('receiver');

        function unmaskDataForSubmit() {
            ['sender', 'receiver'].forEach(p => {
                $(`#${p}_name, #${p}_phone, #${p}_address`).each(function() {
                    if ($(this).attr('data-real-value')) $(this).val($(this).attr('data-real-value'));
                });
            });
        }

        function setupAddressSearch(prefix) {
            const s = $(`#${prefix}_address_search`), r = $(`#${prefix}_address_results`);
            s.on('input', debounce(() => {
                s.removeClass('is-valid is-invalid');
                const q = s.val();
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

                            let lat = parseFloat(i.lat);
                            let lon = parseFloat(i.lon);
                            if (!lat || !lon || lat === 0 || lon === 0) {
                                fallbackGeocode(prefix, p[0], p[1], p[2]);
                            } else {
                                $(`#${prefix}_lat`).val(lat);
                                $(`#${prefix}_lng`).val(lon);
                                // Sinkronkan marker Mapbox
                                if(typeof window.syncMarkerFromInputs === 'function') window.syncMarkerFromInputs(prefix);
                            }
                            r.addClass('d-none');
                        })));
                    } else {
                        r.html('<div class="p-3 text-muted">Alamat tidak ditemukan.</div>');
                    }
                }).fail(() => r.html('<div class="p-3 text-danger">Gagal memuat data.</div>'));
            }, 400));
        }
        setupAddressSearch('sender');
        setupAddressSearch('receiver');

        function getDelivereeVehicleImage(name) {
            const lowerName = name.toLowerCase();
            const baseUrl = 'https://tokosancaka.com/storage/logo-ekspedisi/armada_deliveree';

            if (lowerName.includes('trailer')) return `${baseUrl}/Trailer.png`;
            if (lowerName.includes('tronton')) return `${baseUrl}/TrontonB.png`;
            if (lowerName.includes('fuso')) return `${baseUrl}/Fuso_Heavy.png`;
            if (lowerName.includes('cdd') || lowerName.includes('double engkel')) return `${baseUrl}/CDD.png`;
            if (lowerName.includes('engkel') || lowerName.includes('cde')) return `${baseUrl}/Engkel_Box.png`;
            if (lowerName.includes('small box') || lowerName.includes('box kecil')) return `${baseUrl}/Small_Box.png`;
            if (lowerName.includes('pickup')) return `${baseUrl}/Pickup.png`;
            if (lowerName.includes('van')) return `${baseUrl}/Van.png`;
            if (lowerName.includes('xl') || lowerName.includes('suv')) return `${baseUrl}/carxl-longer_(1).png`;
            if (lowerName.includes('mobil') || lowerName.includes('car') || lowerName.includes('economy') || lowerName.includes('ekonomi') || lowerName.includes('city')) return `${baseUrl}/Economy.png`;
            return `https://placehold.co/300x200/e2e8f0/10b981?text=${encodeURIComponent(name)}`;
        }

        function renderDelivereeModal(results, baseParams) {
            const container = $('#delivereeResultsContainer').empty();
            if (results.length === 0) {
                container.html(`<div class="col-12"><div class="alert alert-warning text-center shadow-sm">Armada Deliveree tidak tersedia.</div></div>`);
                return;
            }

            results.forEach(i => {
                let rawName = i.service_type_label || '';
                let displayServiceType = rawName.replace(/-/g, ' ');

                if (displayServiceType.includes('#')) {
                    let parts = displayServiceType.split('#');
                    displayServiceType = parts[0].trim();
                }

                let imgUrl = getDelivereeVehicleImage(displayServiceType);
                const useInsurance = $('#ansuransi').val() === 'iya';
                const insuranceFeeValue = useInsurance ? (i.insurance || 0) : 0;
                const codFee = (i.setting && i.setting.cod_fee_amount) ? i.setting.cod_fee_amount : 0;

                const baseOngkirCost = parseInt(i.distance_fees || i.cost || 0);
                const actualCodFee = parseInt(codFee || 0);

                const payloadValue = `${baseParams.serviceType}-${i.service_name}-${rawName}-${i.cost}-${insuranceFeeValue}-${codFee}`;
                let etdHtml = i.etd ? `<span>${i.etd} Hari</span>` : '<span>Langsung (Charter)</span>';

                const card = `
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border border-success shadow-sm deliveree-card" style="border-radius:1rem; cursor: pointer; background: white;">
                        <div class="card-body text-center p-4">
                            <img src="${imgUrl}" style="height:100px; width:100%; object-fit:contain; margin-bottom:1rem;" alt="${displayServiceType}">
                            <h6 class="fw-bold text-dark mb-1">DELIVEREE</h6>
                            <span class="badge bg-success mb-3">${displayServiceType}</span>
                            <h4 class="text-success fw-bold mb-2">${formatRupiah(i.cost)}</h4>
                            <div class="text-muted small"><i class="fas fa-clock me-1"></i> Estimasi: ${etdHtml}</div>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-center pb-3">
                            <button type="button" class="btn btn-success w-100 select-ongkir-btn rounded-pill fw-bold"
                                data-value="${payloadValue}"
                                data-display="Deliveree - ${displayServiceType}"
                                data-cod-supported="${i.cod}"
                                data-vehicle-id="${i.vehicle_type_id}" data-shipping-cost="${baseOngkirCost}"
                                data-insurance-cost="${insuranceFeeValue}"
                                data-cod-fee="${actualCodFee}">
                                <i class="fas fa-check-circle me-1">Pilih Armada</i>
                            </button>
                        </div>
                    </div>
                </div>`;
                container.append(card);
            });
        }

        function getLalamoveVehicleImage(name) {
            console.log("LOG LOG LALAMOVE NAMA KENDARAAN ASLI: ", name);
            const lowerName = name.toLowerCase();
            const baseUrl = 'https://tokosancaka.com/public/storage/logo-ekspedisi/lalamove';

            if (lowerName.includes('wingbox') || lowerName.includes('wing box')) return `${baseUrl}/wingbox.png`;
            if (lowerName.includes('fuso bak')) return `${baseUrl}/fusobak.jpg`;
            if (lowerName.includes('fuso')) return `${baseUrl}/fusotruck.png`;
            if (lowerName.includes('cdd box')) return `${baseUrl}/cddbox.png`;
            if (lowerName.includes('cdd')) return `${baseUrl}/cddbak.png`;
            if (lowerName.includes('engkel box') || lowerName.includes('cde box')) return `${baseUrl}/engkelbox.png`;
            if (lowerName.includes('engkel') || lowerName.includes('cde')) return `${baseUrl}/engkelbak.png`;
            if (lowerName.includes('pickup box') || lowerName.includes('pick-up box') || lowerName.includes('pick up box')) return `${baseUrl}/pickupbox.png`;
            if (lowerName.includes('pickup') || lowerName.includes('pick-up') || lowerName.includes('pick up')) return `${baseUrl}/pickupbak.png`;
            if (lowerName.includes('van')) return `${baseUrl}/vannew.png`;
            if (lowerName.includes('sedan')) return `${baseUrl}/sedannew.png`;
            if (lowerName.includes('mpv') || lowerName.includes('car') || lowerName.includes('mobil')) return `${baseUrl}/mpvnew.png`;
            if (lowerName.includes('motor') || lowerName.includes('bike') || lowerName.includes('motorcycle')) return `${baseUrl}/bike.png`;
            return `https://placehold.co/300x200/fffaf7/f27024?text=${encodeURIComponent(name)}`;
        }

        function renderLalamoveModal(results, baseParams) {
            const container = $('#lalamoveResultsContainer').empty();
            if (!results || results.length === 0) {
                container.html(`<div class="col-12"><div class="alert alert-warning text-center shadow-sm">Armada Lalamove tidak tersedia untuk rute ini.</div></div>`);
                return;
            }

            let colClass = results.length <= 2 ? 'col-sm-6' : 'col-sm-6 col-md-4';

            results.forEach(i => {
                let rawName = i.service_type_label || '';
                let displayServiceType = rawName;

                if (displayServiceType.includes('#')) {
                    let parts = displayServiceType.split('#');
                    displayServiceType = parts[0].trim();
                }

                let imgUrl = getLalamoveVehicleImage(displayServiceType);
                const useInsurance = $('#ansuransi').val() === 'iya';
                const insuranceFeeValue = useInsurance ? (i.insurance || 0) : 0;
                const codFee = 0;

                const baseOngkirCost = parseInt(i.distance_fees || i.cost || 0);
                const actualCodFee = 0;
                const payloadValue = `${baseParams.serviceType}-${i.service_name}-${rawName}-${i.cost}-${insuranceFeeValue}-${codFee}`;

                const card = `
                <div class="${colClass} d-flex align-items-stretch">
                    <div class="card h-100 shadow-sm lalamove-card w-100" style="border-radius:1rem; cursor: pointer; background: white; border: 1px solid #f27024;">
                        <div class="card-body text-center p-3 p-md-4">
                            <img src="${imgUrl}" style="height:80px; width:100%; object-fit:contain; margin-bottom:1rem;" alt="${displayServiceType}">
                            <h6 class="fw-bold text-dark mb-1">LALAMOVE</h6>
                            <span class="badge mb-2" style="background-color: #f27024; font-size: 0.75rem;">${displayServiceType}</span>
                            <h5 class="fw-bold mb-2" style="color: #f27024;">${formatRupiah(i.cost)}</h5>
                            <div class="text-muted" style="font-size: 0.75rem;"><i class="fas fa-bolt me-1"></i> Estimasi: Instan</div>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-center pb-3 pt-0">
                            <button type="button" class="btn w-100 select-ongkir-btn rounded-pill fw-bold" style="background-color: #f27024; color: white; font-size: 0.85rem;"
                                data-value="${payloadValue}"
                                data-display="Lalamove - ${displayServiceType}"
                                data-cod-supported="false"
                                data-vehicle-id="" data-shipping-cost="${baseOngkirCost}"
                                data-insurance-cost="${insuranceFeeValue}"
                                data-cod-fee="${actualCodFee}">
                                <i class="fas fa-check-circle me-1"></i> Pilih Armada
                            </button>
                        </div>
                    </div>
                </div>`;
                container.append(card);
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
            if (!results || results.length === 0) {
                container.html(`<div class="col-12"><div class="alert alert-warning text-center shadow-sm">Layanan iPaymu tidak tersedia untuk rute ini.</div></div>`);
                return;
            }
            results.forEach(i => {
                let rawName = i.service_type_label || '';
                let displayServiceType = rawName.toUpperCase().replace('IPAYMU-', '');
                let imgUrl = getIpaymuLogo(displayServiceType);
                const insuranceFeeValue = $('#ansuransi').val() === 'iya' ? (i.insurance || 0) : 0;
                const codFee = (i.setting && i.setting.cod_fee_amount) ? i.setting.cod_fee_amount : 0;
                const baseOngkirCost = parseInt(i.distance_fees || i.cost || 0);
                const actualCodFee = parseInt(codFee || 0);
                const payloadValue = `${baseParams.serviceType}-${i.service_name}-${rawName}-${i.cost}-${insuranceFeeValue}-${codFee}`;

                const card = `
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
                            <button type="button" class="btn w-100 select-ongkir-btn rounded-pill fw-bold" style="background-color: #6f42c1; color: white; font-size: 0.85rem;"
                                data-value="${payloadValue}" data-display="iPaymu - ${displayServiceType}"
                                data-cod-supported="${i.cod}" data-shipping-cost="${baseOngkirCost}"
                                data-insurance-cost="${insuranceFeeValue}" data-cod-fee="${actualCodFee}">
                                <i class="fas fa-check-circle me-1"></i> Pilih Kurir
                            </button>
                        </div>
                    </div>
                </div>`;
                container.append(card);
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
                Swal.fire('Data Belum Lengkap', 'Harap lengkapi: ' + missing.map(s => required[s]).join(', '), 'warning');
                return;
            }

            $('#ongkirResultsContainer').html(`<div class="text-center p-5"><div class="spinner-border text-danger"></div><p class="mt-2 text-muted">Memuat semua tarif...</p></div>`);
            $('#delivereeResultsContainer').html(`<div class="col-12"><div class="text-center p-5"><div class="spinner-border text-success"></div><p class="mt-2 text-muted">Mencari Armada Deliveree di sekitar lokasi...</p></div></div>`);
            $('#lalamoveResultsContainer').html(`<div class="col-12"><div class="text-center p-5"><div class="spinner-border" style="color:#f27024;"></div><p class="mt-2 text-muted">Mencari Armada Lalamove terdekat...</p></div></div>`);
            $('#ipaymuResultsContainer').html(`<div class="col-12"><div class="text-center p-5"><div class="spinner-border" style="color:#6f42c1;"></div><p class="mt-2 text-muted">Mencari Kurir iPaymu...</p></div></div>`);

            if (vendorFilter === 'deliveree') {
                delivereeModal.show();
            } else if (vendorFilter === 'lalamove') {
                lalamoveModal.show();
            } else if (vendorFilter === 'ipaymu') {
                ipaymuModal.show();
            } else {
                ongkirModal.show();
            }

            const serviceType = $('#service_type').val();

            $.ajax({
                url: "{{ route('kirimaja.cekongkir') }}",
                type: "GET",
                data: tempForm.serialize(),
                timeout: 15000,
                success: function(res) {
                    let allResults = [];
                    if (typeof res !== 'object' || res === null) {
                        let errHtml = '<div class="alert alert-danger text-center w-100">Format respons tidak valid dari sistem ekspedisi.</div>';
                        $('#ongkirResultsContainer, #delivereeResultsContainer, #lalamoveResultsContainer, #ipaymuResultsContainer').html(errHtml);
                        return;
                    }

                    const hasData = (res.result && Array.isArray(res.result)) || (res.results && Array.isArray(res.results));

                    if (!hasData || (res.status === false && !hasData)) {
                        let errorMessage = res.message || res.text || 'Layanan pengiriman tidak ditemukan untuk rute ini.';
                        const errHtml = `<div class="alert alert-warning text-center shadow-sm w-100">${errorMessage}</div>`;
                        $('#ongkirResultsContainer').html(errHtml);
                        $('#delivereeResultsContainer').html(`<div class="col-12">${errHtml}</div>`);
                        $('#lalamoveResultsContainer').html(`<div class="col-12">${errHtml}</div>`);
                        $('#ipaymuResultsContainer').html(`<div class="col-12">${errHtml}</div>`);
                        return;
                    }

                    if (res.result && Array.isArray(res.result)) {
                        const fromResult = res.result.flatMap(provider => {
                            let providerNameForLogo = provider.name === 'grab_express' ? 'grab' : provider.name;
                            return provider.costs.map(cost => ({
                                ...cost, service: providerNameForLogo, service_name: `${provider.name.toUpperCase()}`, service_type_label: `${cost.service_type}`,
                                cost: cost.price.total_price, price: cost.price, etd: cost.estimation || '-', setting: cost.setting || {},
                                insurance: cost.price.insurance_fee || 0, cod: cost.cod_available ?? false, is_instant: true
                            }));
                        });
                        allResults.push(...fromResult);
                    }

                    if (res.results && Array.isArray(res.results)) {
                        const fromResults = res.results.map(service => ({
                            ...service, cost: service.cost, price: { base_price: service.cost, total_price: service.cost },
                            insurance: service.insurance || 0, cod: service.cod, service_name: `${service.service.toUpperCase()}`,
                            service_type_label: `${service.service_type}`, is_instant: false
                        }));
                        allResults.push(...fromResults);
                    }

                    allResults.sort((a, b) => a.cost - b.cost);

                    let kiriminAjaResults = [], delivereeResults = [], lalamoveResults = [], ipaymuResults = [];
                    allResults.forEach(service => {
                        let logoName = (service.service || "").toLowerCase().replace(/\s+/g, '');
                        if (logoName === 'deliveree') { delivereeResults.push(service); }
                        else if (logoName === 'lalamove') { lalamoveResults.push(service); }
                        else if (logoName === 'ipaymu') { ipaymuResults.push(service); }
                        else { kiriminAjaResults.push(service); }
                    });

                    if (vendorFilter === 'deliveree') {
                        renderDelivereeModal(delivereeResults, { serviceType: serviceType });
                    } else if (vendorFilter === 'lalamove') {
                        renderLalamoveModal(lalamoveResults, { serviceType: serviceType });
                    } else if (vendorFilter === 'ipaymu') {
                        renderIpaymuModal(ipaymuResults, { serviceType: serviceType });
                    } else {
                        const b = $('#ongkirResultsContainer').empty();
                        if (kiriminAjaResults.length > 0) {
                            b.append(`<div class="ongkir-header-row d-none d-lg-flex"><div class="ongkir-item-col col-service">Layanan</div><div class="ongkir-item-col col-etd">Estimasi</div><div class="ongkir-item-col col-cod">COD</div><div class="ongkir-item-col col-price">Tarif</div><div class="ongkir-item-col col-action"></div></div>`);

                           kiriminAjaResults.forEach(i => {
                                const logoName = (i.service || "").toLowerCase().replace(/[\s_]+/g, '');
                                let logoUrl = '';
                                if (logoName === 'sancakaexpress') {
                                    logoUrl = 'https://tokosancaka.com/storage/uploads/sancaka.png';
                                } else if (logoName === 'gosend') {
                                    logoUrl = 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png';
                                } else if (logoName === 'grab') {
                                    logoUrl = 'https://tokosancaka.com/public/storage/logo-ekspedisi/grab.png';
                                } else {
                                    logoUrl = `{{ asset('public/storage/logo-ekspedisi/') }}/${logoName}.png`;
                                }

                                const safeService = (i.service || '').toString().replace(/-/g, ' ');
                                const safeServiceTypeLabel = (i.service_type_label || '').toString().replace(/-/g, ' ');
                                const useInsurance = $('#ansuransi').val() === 'iya';
                                const insuranceFeeValue = useInsurance ? (i.insurance || 0) : 0;
                                const codFee = (i.setting && i.setting.cod_fee_amount) ? i.setting.cod_fee_amount : (i.extra_fees || 0);
                                const v = `${serviceType}-${safeService}-${safeServiceTypeLabel}-${i.cost}-${insuranceFeeValue}-${codFee}`;
                                const baseOngkirCost = parseInt(i.cost || 0) + parseInt(insuranceFeeValue || 0);
                                const actualCodFee = parseInt(codFee || 0);
                                const hasDiscount = i.price?.base_price && i.price.base_price > i.cost;
                                const basePriceFmt = hasDiscount ? formatRupiah(i.price.base_price) : '';
                                const insuranceFee = i.insurance || 0;

                                let feeDetailsHtml = '';
                                if (useInsurance && insuranceFee > 0) { feeDetailsHtml += `<div><small>Termasuk Asuransi: ${formatRupiah(insuranceFee)}</small></div>`; }
                                if (i.cod && codFee > 0) { feeDetailsHtml += `<div><small>Biaya COD: ${formatRupiah(codFee)}</small></div>`; }

                                let etdText = String(i.etd || '');
                                let etdHtml = '';
                                if (etdText) {
                                    if (etdText.toLowerCase().includes('hari') || etdText.toLowerCase().includes('jam') || etdText.toLowerCase().includes('menit') || i.is_instant) {
                                        etdHtml = `<span>${etdText}</span>`;
                                    } else {
                                        etdHtml = `<span>${etdText} Hari</span>`;
                                    }
                                }

                                let codDisplayHtml = '-';
                                if (i.cod) {
                                    codDisplayHtml = 'Tersedia';
                                    if (actualCodFee > 0) {
                                        codDisplayHtml += `<br><small class="text-danger fw-bold">+ ${formatRupiah(actualCodFee)}</small>`;
                                    }
                                }

                                const buttonHtml = `<button type="button" class="btn btn-kirim select-ongkir-btn" data-value="${v}" data-display="${i.service_name} - ${i.service_type_label}" data-cod-supported="${i.cod}" data-shipping-cost="${parseInt(i.cost || 0)}" data-insurance-cost="${insuranceFeeValue}" data-cod-fee="${actualCodFee}">Kirim Paket</button>`;

                                b.append(`
                                <div class="ongkir-item-card">
                                    <div class="ongkir-item-col col-service">
                                        <img src="${logoUrl}" class="ongkir-logo" style="max-height: 40px; width: auto; object-fit: contain; margin-right: 15px;" alt="${i.service_name}">
                                        <div class="service-info"><span class="service-name">${i.service_name.replace(/_/g, ' ')}</span><span class="service-type">${i.service_type_label}</span></div>
                                    </div>
                                    <div class="ongkir-item-col col-etd"><span class="col-label">Estimasi</span>${etdHtml}</div>
                                    <div class="ongkir-item-col col-cod"><span class="col-label">COD</span><span>${codDisplayHtml}</span></div>
                                    <div class="ongkir-item-col col-price"><span class="col-label">Tarif</span>
                                        <div class="price-value"><span class="final-price">${formatRupiah(i.cost)}</span>${hasDiscount ? `<span class="base-price text-decoration-line-through">${basePriceFmt}</span>` : ''}</div>
                                        <div class="price-details">${feeDetailsHtml}</div>
                                    </div>
                                    <div class="ongkir-item-col col-action">${buttonHtml}</div>
                                </div>`);
                            });
                        }

                        if (ipaymuResults.length > 0) {
                            b.append(`<div class="w-100 my-4 text-center text-muted" style="border-top: 1px dashed #ced4da; padding-top: 10px;"><small class="fw-bold" style="color: #6f42c1;">--- LAYANAN ALTERNATIF DARI IPAYMU COD ---</small></div>`);
                            ipaymuResults.forEach(i => {
                                let rawName = i.service_type_label || '';
                                let displayServiceType = rawName.toUpperCase().replace('IPAYMU-', '');
                                let imgUrl = getIpaymuLogo(displayServiceType);
                                const useInsurance = $('#ansuransi').val() === 'iya';
                                const insuranceFeeValue = useInsurance ? (i.insurance || 0) : 0;
                                const codFee = (i.setting && i.setting.cod_fee_amount) ? i.setting.cod_fee_amount : 0;
                                const baseOngkirCost = parseInt(i.distance_fees || i.cost || 0);
                                const actualCodFee = parseInt(codFee || 0);
                                const payloadValue = `${serviceType}-${i.service_name}-${rawName}-${i.cost}-${insuranceFeeValue}-${codFee}`;

                                let feeDetailsHtml = '';
                                if (useInsurance && insuranceFeeValue > 0) { feeDetailsHtml += `<div><small>Termasuk Asuransi: ${formatRupiah(insuranceFeeValue)}</small></div>`; }
                                if (i.cod && codFee > 0) { feeDetailsHtml += `<div><small>Biaya COD: ${formatRupiah(codFee)}</small></div>`; }

                                const buttonHtml = `<button type="button" class="btn btn-kirim select-ongkir-btn" style="background-color: #6f42c1;" data-value="${payloadValue}" data-display="iPaymu - ${displayServiceType}" data-cod-supported="${i.cod}" data-shipping-cost="${baseOngkirCost}" data-insurance-cost="${insuranceFeeValue}" data-cod-fee="${actualCodFee}">Pilih Kurir</button>`;

                                b.append(`
                                <div class="ongkir-item-card" style="border-left: 4px solid #6f42c1;">
                                    <div class="ongkir-item-col col-service">
                                        <img src="${imgUrl}" class="ongkir-logo" onerror="this.src='https://tokosancaka.com/public/assets/ipaymu.jpg'">
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

                        if (kiriminAjaResults.length === 0 && ipaymuResults.length === 0) {
                            b.html(`<div class="alert alert-warning text-center shadow-sm">Tidak ada layanan reguler yang tersedia untuk rute ini.</div>`);
                        }
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    let errorMsg = jqXHR.responseJSON?.message || 'Gagal mengambil data ongkir dari server.';
                    if (textStatus === 'timeout') {
                        errorMsg = 'Waktu habis (Timeout). Server API ekspedisi merespons terlalu lambat atau sedang bermasalah.';
                    }
                    const errHtml = `<div class="col-12"><div class="alert alert-danger text-center shadow-sm w-100"><i class="fas fa-exclamation-triangle me-2"></i> ${errorMsg}</div></div>`;

                    $('#ongkirResultsContainer').html(errHtml);
                    $('#delivereeResultsContainer').html(errHtml);
                    $('#lalamoveResultsContainer').html(errHtml);
                    $('#ipaymuResultsContainer').html(errHtml);
                }
            });
        }

        const fieldsThatAffectShipping = '#sender_district_id, #receiver_district_id, #item_price, #weight, #length, #width, #height, #ansuransi, #service_type, #vendor_filter';

        $(document).on('change', fieldsThatAffectShipping, function() {
            $('#expedition').val('');
            $('#selected_expedition_display').val('Data berubah, klik untuk cek ulang ongkir').removeClass('is-valid');
            $('.cod-payment-option').hide();

            $('#selected_shipping_cost').val('0');
            $('#selected_insurance_cost').val('0');

            $('#payment_method').val('');
            $('#selectedPaymentName').text('Pilih Pembayaran...');
            $('#selectedPaymentLogo').addClass('d-none').attr('src', '');
            $('#defaultPaymentIcon').removeClass('d-none');

            if ($('#vendor_filter').val() === 'deliveree') {
                $('#deliveree_extra_section').removeClass('d-none');
            } else {
                $('#deliveree_extra_section').addClass('d-none');
                $('#extra_helper_driver, #extra_helper_extra').prop('checked', false).trigger('change');
            }
            updateTotalSummary();
        });

        $('#selected_expedition_display').on('click', runCekOngkir);

        $(document).on('click', '.select-ongkir-btn', function() {
            const expeditionValue = $(this).data('value');
            $('#expedition').val(expeditionValue);
            $('#selected_expedition_display').val($(this).data('display')).addClass('is-valid');

            $('#selected_shipping_cost').val($(this).data('shipping-cost'));
            $('#selected_insurance_cost').val($(this).data('insurance-cost'));
            $('#selected_cod_fee').val($(this).data('cod-fee'));

            if ($(this).data('cod-supported')) {
                $('.cod-payment-option').show();
            } else {
                if (['COD', 'CODBARANG'].includes($('#payment_method').val())) {
                    $('#payment_method').val('');
                    $('#selectedPaymentName').text('Pilih Pembayaran...');
                    $('#selectedPaymentLogo').addClass('d-none').attr('src', '');
                    $('#defaultPaymentIcon').removeClass('d-none');
                }
                $('.cod-payment-option').hide();
            }

            updateTotalSummary();
            ongkirModal.hide();
            delivereeModal.hide();
            lalamoveModal.hide();
            ipaymuModal.hide();

            let vehicleId = $(this).data('vehicle-id');
            let isDeliveree = String(expeditionValue).toLowerCase().includes('deliveree');

            if (isDeliveree && vehicleId) {
                $('#helper_driver_price_text').text('(Mengecek...)').removeClass('text-success text-muted').addClass('text-warning');
                $('#helper_extra_price_text').text('(Mengecek...)').removeClass('text-success text-muted').addClass('text-warning');

                $.get('/api/deliveree/extra-services/' + vehicleId, function(res) {
                    if (res.data) {
                        let helperDriver = res.data.find(s => s.name.toLowerCase().includes('pengemudi'));
                        let helperExtra = res.data.find(s => s.name.toLowerCase().includes('tambahan'));

                        if (helperDriver && helperDriver.unit_price > 0) {
                            $('#deliveree_helper_driver_id').val(helperDriver.id);
                            $('#extra_helper_driver').data('price', helperDriver.unit_price).prop('disabled', false);
                            $('#helper_driver_price_text').text('+ ' + formatRupiah(helperDriver.unit_price)).removeClass('text-warning text-muted').addClass('text-success');
                        } else {
                            $('#deliveree_helper_driver_id').val('');
                            $('#extra_helper_driver').data('price', 0).prop('checked', false).prop('disabled', true);
                            $('#helper_driver_price_text').text('(Gratis / Bawaan)').removeClass('text-warning text-success').addClass('text-muted');
                        }

                        if (helperExtra && helperExtra.unit_price > 0) {
                            $('#deliveree_helper_extra_id').val(helperExtra.id);
                            $('#extra_helper_extra').data('price', helperExtra.unit_price).prop('disabled', false);
                            $('#helper_extra_price_text').text('+ ' + formatRupiah(helperExtra.unit_price)).removeClass('text-warning text-muted').addClass('text-success');
                        } else {
                            $('#deliveree_helper_extra_id').val('');
                            $('#extra_helper_extra').data('price', 0).prop('checked', false).prop('disabled', true);
                            $('#helper_extra_price_text').text('(Tidak tersedia)').removeClass('text-warning text-success').addClass('text-muted');
                        }

                        $('#extra_helper_driver, #extra_helper_extra').trigger('change');
                    }
                }).fail(function(err) {
                    $('#helper_driver_price_text').text('(Gagal cek API)').addClass('text-danger');
                    $('#helper_extra_price_text').text('(Gagal cek API)').addClass('text-danger');
                });
            }
        });

        let isPaymentApiLoaded = false;
        function loadTripayChannels() {
            if (isPaymentApiLoaded) return;
            const container = $('#dynamicPaymentChannels');
            $.ajax({
                url: "{{ route('pesanan.public.get_channels') }}",
                type: "GET",
                success: function(res) {
                    if (res.success && res.data && res.data.length > 0) {
                        container.empty();
                        res.data.forEach(ch => {
                            if (ch.active) {
                                container.append(`
                                    <li class="list-group-item list-group-item-action d-flex align-items-center gateway-option"
                                        data-value="${ch.code}" data-label="${ch.name}">
                                        <img src="${ch.icon_url}" class="me-3 border rounded p-1 bg-white"
                                             style="width: 40px; height: 40px; object-fit: contain;"
                                             onerror="this.src='https://placehold.co/40x40?text=IMG'">
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">${ch.name}</div>
                                            <div class="text-muted" style="font-size: 0.75rem;">${ch.group_name || 'Pembayaran Online'}</div>
                                        </div>
                                    </li>
                                `);
                            }
                        });
                        isPaymentApiLoaded = true;
                        applyGatewayMinimumLimit();
                    } else {
                        container.html('<div class="p-3 text-center text-muted small">Saluran pembayaran Tripay tidak tersedia.</div>');
                    }
                }
            });
        }

        function applyGatewayMinimumLimit() {
            let shippingCost = parseInt($('#selected_shipping_cost').val()) || 0;
            let totalTransaksi = shippingCost;

            if (totalTransaksi < 10000 && totalTransaksi > 0) {
                $('.gateway-option').addClass('disabled').css({ 'pointer-events': 'none', 'opacity': '0.3', 'background-color': '#f3f4f6' });

                let alertMsg = `<i class="fas fa-exclamation-triangle me-1"></i> Total Ongkir (Rp ${totalTransaksi.toLocaleString('id-ID')}) di bawah minimum Rp 10.000. Payment Gateway dimatikan.`;
                if ($('#min-tx-alert').length === 0) {
                    $('#paymentOptionsList').prepend(`<li id="min-tx-alert" class="list-group-item list-group-item-danger text-center small fw-bold p-2 mb-2 rounded border border-danger">${alertMsg}</li>`);
                } else {
                    $('#min-tx-alert').html(alertMsg).show();
                }

                let selectedMethod = $('#payment_method').val();
                if (selectedMethod && $('.gateway-option[data-value="'+selectedMethod+'"]').length > 0) {
                    $('#payment_method').val('');
                    $('#selectedPaymentName').text('Pilih Pembayaran...');
                    $('#selectedPaymentLogo').addClass('d-none').attr('src', '');
                    $('#defaultPaymentIcon').removeClass('d-none');
                    $('.gateway-option').removeClass('active');
                }
            } else {
                $('.gateway-option').removeClass('disabled').css({ 'pointer-events': 'auto', 'opacity': '1', 'background-color': '' });
                $('#min-tx-alert').hide();
            }
        }

        $('#paymentMethodButton').on('click', function() {
            applyGatewayMinimumLimit();
            paymentModal.show();
            loadTripayChannels();
        });

      function executePaymentSelection(element) {
          const value = element.data('value');
          const label = element.data('label');
          const imgSrc = element.find('img').attr('src');

          let finalLabel = label;
          let realBal = element.data('real-balance');
          if (realBal !== undefined && value !== 'COD' && value !== 'CODBARANG') {
              finalLabel = `${label} (${formatRupiah(realBal)})`;
          }

          $('#payment_method').val(value);
          $('#selectedPaymentName').text(finalLabel);
          $('#defaultPaymentIcon').addClass('d-none');
          $('#selectedPaymentLogo').attr('src', imgSrc).removeClass('d-none');
          $('#paymentOptionsList .list-group-item-action').removeClass('active');
          element.addClass('active');
          $('#paymentMethodModal').modal('hide');

          updateTotalSummary();
      }

        $('#paymentOptionsList').on('click', '.list-group-item-action', function(e) {
            if ($(this).hasClass('disabled')) { e.preventDefault(); return false; }
            if (!$(this).data('value')) return;

            if ($(this).hasClass('requires-pin') && !isPinVerified) {
                pendingPaymentSelection = $(this);
                $('#paymentMethodModal').modal('hide');

                setTimeout(() => {
                    $('#pin_error_msg').addClass('d-none');
                    $('.pin-digit').val('');
                    $('#full_pin_value').val('');
                    $('#pinModal').modal('show');
                    setTimeout(() => { $('.pin-digit').first().focus(); }, 500);
                }, 400);
                return;
            }

            executePaymentSelection($(this));
        });

        $('.cod-payment-option').hide();

        $('#confirmBtn').on('click', function(e) {
            e.preventDefault();
            const $this = $(this);
            unmaskDataForSubmit();

            if (!$('#orderForm')[0].checkValidity()) {
                $('#orderForm')[0].reportValidity();
                Swal.fire('Peringatan', 'Harap lengkapi semua field yang wajib diisi.', 'warning');
                return;
            }

            Swal.fire({
                title: 'Konfirmasi Pesanan',
                text: "Apakah semua data sudah benar?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Buat Pesanan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const paymentMethodVal = $('#payment_method').val().toUpperCase();

                    if ((paymentMethodVal === 'POTONG SALDO' || paymentMethodVal === 'DANA_BINDING') && !isPinVerified) {
                        const pinModal = new bootstrap.Modal(document.getElementById('pinModal'));
                        $('#pin_error_msg').addClass('d-none');
                        $('.pin-digit').val('');
                        $('#full_pin_value').val('');

                        pinModal.show();
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
            let pin = '';
            $('.pin-digit').each(function() { pin += $(this).val(); });
            $('#full_pin_value').val(pin);
            if (pin.length === 6) { $('#btnVerifyPin').click(); }
        });

        $('.pin-digit').on('keydown', function(e) {
            if (e.key === 'Backspace' && $(this).val() === '') { $(this).prev('.pin-digit').focus(); }
        });

        $('#btnVerifyPin').on('click', function() {
            const pin = $('#full_pin_value').val();
            if (pin.length < 6) {
                $('#pin_error_msg').text('PIN harus 6 angka.').removeClass('d-none');
                return;
            }

            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin me-2"></i> Memeriksa...');

            $.ajax({
                url: "{{ route('verify.pin') }}",
                type: "POST",
                data: { pin: pin },
                success: function(res) {
                   if (res.success) {
                    $('#pinModal').modal('hide');
                    isPinVerified = true;

                    $('.requires-pin').each(function() {
                        let realBal = $(this).data('real-balance');
                        let prefix = $(this).find('.balance-text').data('prefix') || '';
                        if (realBal !== undefined) { $(this).find('.balance-text').html(`${prefix}${formatRupiah(realBal)}`); }
                        let badge = $(this).find('.status-badge');
                        if(badge.length) { badge.removeClass('bg-secondary').addClass('bg-primary').html('<i class="fas fa-check-circle me-1"></i> Tersambung'); }
                    });

                    if (pendingPaymentSelection) {
                        Swal.fire({ title: 'Akses Terbuka!', text: 'Saldo ditampilkan dan metode dipilih.', icon: 'success', showConfirmButton: false, timer: 1500 }).then(() => {
                            executePaymentSelection(pendingPaymentSelection);
                            pendingPaymentSelection = null;
                        });
                    } else {
                        Swal.fire({ title: 'PIN Terverifikasi!', text: 'Memproses pembayaran Anda...', icon: 'success', showConfirmButton: false, timer: 1500 }).then(() => {
                            $('#confirmBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...');
                            document.getElementById('orderForm').submit();
                        });
                    }
                } else {
                        $('#pin_error_msg').text(res.message).removeClass('d-none');
                        $('.pin-digit').val('').first().focus();
                        $('#full_pin_value').val('');
                        $btn.prop('disabled', false).html('Verifikasi & Bayar');
                    }
                },
                error: function() {
                    $('#pin_error_msg').text('Terjadi kesalahan koneksi server.').removeClass('d-none');
                    $btn.prop('disabled', false).html('Verifikasi & Bayar');
                }
            });
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
    } // Akhir fungsi initSancakaScripts
</script>
@endpush
