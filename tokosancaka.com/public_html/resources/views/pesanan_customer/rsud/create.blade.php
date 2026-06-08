@extends('layouts.app')

@section('title', 'Booking Obat Pasien')

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
        --primary-rgb: 255, 255, 255; /* <-- SEPERTI INI */
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
        padding-top: 2rem; /* Disesuaikan agar ada ruang dari header */
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
    .ui-menu-item-wrapper.ui-state-active {
        background-color: rgba(220, 53, 69, 0.1) !important; /* Warna merah tipis */
        color: var(--primary-color) !important;
        border: none;
    }
   .search-result-item {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer !important; /* Paksa jadi jari */
        transition: all 0.2s ease-in-out;
    }

    /* TAMBAHKAN INI: Agar saat text di-hover, kursor tetap jari */
    .search-result-item * {
        cursor: pointer !important;
    }

    .search-result-item:hover {
        background-color: rgba(var(--primary-rgb), 0.08);
        border-left: 3px solid var(--primary-color); /* Tambahan visual indikator */
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
    PERBAIKAN DESAIN: Modal Cek Ongkir
    ============================================
    */
    #ongkirModal .modal-dialog {
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
</style>
@endpush

@section('content')

@include('layouts.partials.notifications')

<div class="container main-content-container">

    <div class="stepper">
        <div class="step active" id="step-indicator-1">
            <div class="step-icon">1</div>
            <div class="step-text">Pengirim</div>
        </div>
        <div class="step" id="step-indicator-2">
            <div class="step-icon">2</div>
            <div class="step-text">Penerima</div>
        </div>
        <div class="step" id="step-indicator-3">
            <div class="step-icon">3</div>
            <div class="step-text">Detail & Bayar</div>
        </div>
    </div>

    <form id="orderForm" action="{{ route('rsud.pesanan.store') }}" method="POST">
        @csrf
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

                                {{-- ========================================== --}}
                                {{-- TAMBAHAN: BLOK INPUT NOMOR RM (REKAM MEDIS) --}}
                                {{-- ========================================== --}}
                                <div class="col-12 mb-2">
                                    <div class="p-3 bg-light border border-primary rounded">
                                        <label for="nomor_rm" class="form-label fw-bold text-primary">
                                            <i class="fas fa-id-card me-2"></i>Masukkan Nomor Rekam Medis (RM)
                                        </label>
                                        <div class="input-group">
                                            <input type="text" id="nomor_rm" name="nomor_rm" class="form-control form-control-lg border-primary" placeholder="Masukan Nomor RM Disini Contoh: 123456" autocomplete="off">
                                            <button type="button" class="btn btn-primary px-4" id="btnCekRM">
                                                <i class="fas fa-search me-2"></i>Cari Data
                                            </button>
                                        </div>
                                        <small class="text-muted mt-2 d-block" id="rm_status_text">Sistem akan otomatis mengisi data diri Anda berdasarkan Nomor RM.</small>
                                    </div>
                                </div>
                                {{-- ========================================== --}}

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

                            <div class="col-md-6">
                                <label for="item_type" class="form-label">Jenis Barang</label>
                                <select name="item_type" id="item_type" class="form-select" required>
                                    <option value="" disabled selected>Pilih...</option>
                                    <option value="1">Peralatan Elektronik & Gadget</option>
                                    <option value="2">Pakaian / Baju / Kain</option>
                                    <option value="3">Pecah Belah</option>
                                    <option value="4">Dokumen / Berkas / Buku</option>
                                    <option value="5">Peralatan Rumah Tangga</option>
                                    <option value="6">Aksesoris</option>
                                    <option value="7">Lain-Lain</option>
                                    <option value="8">Dokumen Berharga</option>
                                    <option value="9">Peralatan Kesehatan / Kecantikan / Kosmetik</option>
                                    <option value="10">Peralatan Olahraga & Hiburan</option>
                                    <option value="11">Perlengkapan Mobil & Motor</option>
                                </select>
                            </div>

                            <div class="col-md-6"><label for="service_type" class="form-label">Jenis Layanan</label><select name="service_type" id="service_type" class="form-select" required><option value="regular" selected>Regular</option><option value="cargo">Cargo</option><option value="instant">Instant / Sameday</option></select></div>
                            <div class="col-12"><label for="ansuransi" class="form-label">Asuransi</label><div class="input-group"><span class="input-group-text"><i class="fas fa-shield-alt"></i></span><select name="ansuransi" id="ansuransi" class="form-select" required><option value="tidak" selected>Tidak Pakai Asuransi</option><option value="iya">Ya, Pakai Asuransi</option></select></div></div>
                            <div class="col-12"><hr class="my-3"></div>

                            {{-- PENAMBAHAN: Hidden input untuk menampung tarif ongkir yang terpilih guna keperluan kalkulasi limit minimum --}}
                            <div class="col-12">
                                <label for="selected_expedition_display" class="form-label">Pilih Ekspedisi</label>
                                <input type="text" id="selected_expedition_display" class="form-control text-start fw-bold" placeholder="Lengkapi data & klik di sini" readonly required>
                                <input type="hidden" name="expedition" id="expedition" required>
                                <input type="hidden" id="selected_shipping_cost" value="0">
                            </div>

                            {{-- TAMBAHAN: Kotak Monitor Total Pembayaran --}}
                            <div class="col-12 my-2">
                                <div class="p-3 rounded" style="background-color: #f8f9fa; border: 1px dashed #ced4da;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted" style="font-size: 0.85rem;">Harga Barang</span>
                                        <span id="summary_item_price" class="fw-bold text-secondary" style="font-size: 0.85rem;">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted" style="font-size: 0.85rem;">Tarif Ongkir</span>
                                        <span id="summary_shipping_cost" class="fw-bold text-secondary" style="font-size: 0.85rem;">Rp 0</span>
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
                    {{-- Hasil ongkir akan dimuat di sini oleh JavaScript --}}
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

                    {{--<li class="list-group-item list-group-item-action d-flex align-items-center gateway-option" data-value="MIDTRANS" data-label="Midtrans">
                        <img src="{{ asset('public/assets/midtrans.png') }}" class="me-3 border rounded p-1 bg-white" style="width: 40px; height: 40px; object-fit: contain;">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">Midtrans</div>
                            <div class="text-muted" style="font-size: 0.75rem;">VA, QRIS, E-Wallet (Otomatis)</div>
                        </div>
                    </li>
                    --}}

                    <li class="list-group-item list-group-item-action d-flex align-items-center gateway-option" data-value="DANA" data-label="DANA (Web Checkout)">
                        <img src="{{ asset('public/assets/dana.webp') }}" class="me-3 border rounded p-1 bg-white" style="width: 40px; height: 40px; object-fit: contain;">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">DANA (Checkout Gapura)</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Diarahkan ke web/aplikasi DANA</div>
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
    // Fungsi untuk memuat script secara berurutan
    function loadScript(url, callback) {
        var script = document.createElement("script");
        script.type = "text/javascript";
        script.src = url;
        script.onload = callback;
        document.head.appendChild(script);
    }

    // Tunggu sampai seluruh halaman dan jQuery bawaan selesai dimuat
    window.onload = function() {
        // Cek apakah jQuery sudah ada dari layouts.app
        if (window.jQuery) {
            // console.log("jQuery terdeteksi, memuat jQuery UI...");
            // Load jQuery UI
            loadScript("https://code.jquery.com/ui/1.13.2/jquery-ui.min.js", function() {
                // console.log("jQuery UI berhasil dimuat!");
                // Panggil fungsi utama kita setelah jQuery UI siap
                initSancakaScripts();
            });
        } else {
            console.error("jQuery tidak terdeteksi! Pastikan layout utama memuat jQuery.");
        }
    };

    // Pindahkan $(document).ready() kamu ke dalam fungsi ini
    function initSancakaScripts() {

     let isPinVerified = false;
     let pendingPaymentSelection = null;
     let isAutoRMFlow = false;
        // ============================================
        // LOGIKA STEP-BY-STEP FORM
        // ============================================
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



        // ============================================
        // LOGIKA INTI: Pencarian, Modal, Submit
        // ============================================
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
        const paymentModal = new bootstrap.Modal(document.getElementById('paymentMethodModal'));

        let searchTimeout = null;
        const debounce = (func, delay) => (...args) => { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => func.apply(this, args), delay); };
        function formatRupiah(angka) { return 'Rp ' + (parseInt(angka, 10) || 0).toLocaleString('id-ID'); }

        // PENAMBAHAN: Fungsi Update Monitor Total
        function updateTotalSummary() {
            // Hilangkan semua karakter non-angka sebelum diparse
            let rawItemPrice = $('#item_price').val() || "0";
            let itemPrice = parseInt(rawItemPrice.replace(/\D/g, '')) || 0;

            let baseShippingCost = parseInt($('#selected_shipping_cost').val()) || 0;
            let codFee = parseInt($('#selected_cod_fee').val()) || 0;
            let paymentMethod = $('#payment_method').val();

            // Default: Tampilan Tarif & Total murni hanya Ongkir Dasar
            let finalShippingCost = baseShippingCost;
            let total = baseShippingCost;

            // Jika memilih metode COD/CODBARANG, barulah tambahkan biaya COD
            if (paymentMethod === 'COD' || paymentMethod === 'CODBARANG') {
                finalShippingCost = parseInt(baseShippingCost) + parseInt(codFee);
                total = finalShippingCost;
            }

            // Jika memilih CODBARANG, tambahkan Harga Barang ke Total
            if (paymentMethod === 'CODBARANG') {
                total = parseInt(itemPrice) + parseInt(finalShippingCost);
            }

            $('#summary_item_price').text(formatRupiah(itemPrice));
            $('#summary_shipping_cost').text(formatRupiah(finalShippingCost));
            $('#summary_total_cost').text(formatRupiah(total));
        }

            // Update monitor secara real-time saat user mengetik harga barang
            $('#item_price').on('input', function() {
                updateTotalSummary();
            });

        function maskData(type, value) { if (!value) return '***'; if (type === 'name') { const parts = value.split(' '); return parts.length > 1 ? parts[0] + ' ' + parts.slice(1).map(p => p.replace(/./g, '*')).join(' ') : (value.length > 2 ? value.substring(0, 2) + '***' : value); } if (type === 'phone') { const num = value.replace(/\D/g, ''); return num.length > 8 ? num.substring(0, 3) + '****' + num.substring(num.length - 4) : num.substring(0, 3) + '****'; } if (type === 'address') { const parts = value.split(' '); return parts.length > 2 ? parts.slice(0, 2).join(' ') + ' **** **** ****' : value; } return '***'; }
        function clearHiddenAddress(prefix) { $(`#${prefix}_province, #${prefix}_regency, #${prefix}_district, #${prefix}_village, #${prefix}_postal_code, #${prefix}_district_id, #${prefix}_subdistrict_id, #${prefix}_lat, #${prefix}_lng`).val(''); }

       // --- FUNGSI MENGISI FORM (DENGAN SENSOR MASKING) ---
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

                        $(`#${prefix}_lat`).val(item.lat || '');
                        $(`#${prefix}_lng`).val(item.lon || '');

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

        // --- FUNGSI AUTOCOMPLETE JQUERY UI ---
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
                                    return {
                                        label: item.nama,
                                        value: item.nama,
                                        data: item
                                    };
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
                        d.forEach(i => r.append($(`
                            <div class="search-result-item d-flex justify-content-between align-items-center">
                                <div class="font-weight-bold flex-grow-1 pe-3">${i.full_address}</div>
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" style="white-space: nowrap;">
                                    <i class="fas fa-check me-1"></i> Pilih
                                </button>
                            </div>
                        `).on('click', () => {
                            s.val(i.full_address);
                            const p = i.full_address.split(',').map(t => t.trim());
                            $(`#${prefix}_village`).val(p[0] || '').trigger('change');
                            $(`#${prefix}_district`).val(p[1] || '').trigger('change');
                            $(`#${prefix}_regency`).val(p[2] || '').trigger('change');
                            $(`#${prefix}_province`).val(p[3] || '').trigger('change');
                            $(`#${prefix}_postal_code`).val(p[4] || '').trigger('change');
                            $(`#${prefix}_district_id`).val(i.district_id).trigger('change');
                            $(`#${prefix}_subdistrict_id`).val(i.subdistrict_id).trigger('change');
                            $(`#${prefix}_lat`).val(i.lat || '');
                            $(`#${prefix}_lng`).val(i.lon || '');
                            r.addClass('d-none');

                           // =======================================================
                            // TRIGGER OTOMATIS: DARI STEP 3 -> CEK ONGKIR -> AUTO-POS -> BUKA PEMBAYARAN
                            // =======================================================
                            if (prefix === 'receiver' && isAutoRMFlow) {
                                isAutoRMFlow = false;

                                // 1. Langsung pindah Step 3 (Detail Paket)
                                $('#nextToPaket').click();

                                // 2. Auto-Fill Detail Barang
                                $('#item_description').val('OBAT').addClass('is-valid');
                                $('#item_type').val('7').trigger('change').addClass('is-valid');
                                $('#weight').val('1000').addClass('is-valid');
                                $('#length').val('10'); $('#width').val('10'); $('#height').val('10');
                                if (!$('#item_price').val()) {
                                    $('#item_price').val('1000').trigger('input').addClass('is-valid');
                                }
                                updateTotalSummary();

                                // 3. Eksekusi Berantai
                                setTimeout(() => {
                                    // Klik tombol "Pilih Ekspedisi"
                                    $('#selected_expedition_display').click();

                                    // Pantau kapan modal ongkir selesai memuat POS Indonesia
                                    let checkPosInterval = setInterval(() => {
                                        let posBtn = $('.select-ongkir-btn').filter(function() {
                                            return $(this).data('display').toUpperCase().includes('POS');
                                        }).first();

                                        if (posBtn.length > 0) {
                                            clearInterval(checkPosInterval); // Hentikan pengintai

                                            // Klik tombol "Kirim Paket" (POS)
                                            posBtn.click();

                                            // Jeda 500ms setelah modal tertutup, lalu buka Modal Pembayaran
                                            setTimeout(() => {
                                                $('#paymentMethodButton').click();

                                                const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                                                Toast.fire({ icon: 'success', title: 'Ekspedisi & Pembayaran Siap!' });
                                            }, 500);
                                        }
                                    }, 300); // Cek setiap 300ms apakah tombol POS sudah muncul di modal
                                }, 800);
                            }
                            // =======================================================

                        })));
                    } else {
                        r.html('<div class="p-3 text-muted">Alamat tidak ditemukan.</div>');
                    }
                }).fail(() => r.html('<div class="p-3 text-danger">Gagal memuat data.</div>'));
            }, 400));
        }
        setupAddressSearch('sender');
        setupAddressSearch('receiver');

        function runCekOngkir() {
            let formData = $('#orderForm').serializeArray();
            formData.forEach((item, index) => { let realVal = $(`#${item.name.replace(/\[/g, '\\[').replace(/\]/g, '\\]')}`).attr('data-real-value'); if (realVal) formData[index].value = realVal; });

            let tempForm = $('<form>').append($.map(formData, item => $('<input>').attr({type: 'hidden', name: item.name, value: item.value})));
            tempForm.append($('<input>').attr({type: 'hidden', name: 'sender_lat', value: $('#sender_lat').val()}));
            tempForm.append($('<input>').attr({type: 'hidden', name: 'sender_lng', value: $('#sender_lng').val()}));
            tempForm.append($('<input>').attr({type: 'hidden', name: 'receiver_lat', value: $('#receiver_lat').val()}));
            tempForm.append($('<input>').attr({type: 'hidden', name: 'receiver_lng', value: $('#receiver_lng').val()}));

            const required = { 'sender_district_id': 'Alamat Pengirim', 'receiver_district_id': 'Alamat Penerima', 'item_price': 'Harga Barang', 'weight': 'Berat' };
            let missing = Object.keys(required).filter(s => !tempForm.find(`[name="${s.replace('#','')}"]`).val());
            if (missing.length > 0) { Swal.fire('Data Belum Lengkap', 'Harap lengkapi: ' + missing.map(s => required[s]).join(', '), 'warning'); return; }

            $('#ongkirResultsContainer').html(`<div class="text-center p-5"><div class="spinner-border text-danger"></div><p class="mt-2 text-muted">Memuat semua tarif...</p></div>`);
            ongkirModal.show();

            const serviceType = $('#service_type').val();

            $.ajax({
                url: "{{ route('kirimaja.cekongkir') }}",
                type: "GET",
                data: tempForm.serialize(),
                success: function(res) {
                    let allResults = [];
                    if (typeof res !== 'object' || res === null) {
                        $('#ongkirResultsContainer').html('<div class="alert alert-danger text-center">Format respons tidak valid.</div>');
                        return;
                    }
                    const hasData = (res.result && Array.isArray(res.result)) || (res.results && Array.isArray(res.results));
                    if (!hasData || (res.status === false && !hasData)) {
                        let errorMessage = res.message || res.text || 'Layanan pengiriman tidak ditemukan untuk rute atau jenis layanan ini.';
                        $('#ongkirResultsContainer').html(`<div class="alert alert-warning text-center">${errorMessage}</div>`);
                        return;
                    }
                    if (res.result && Array.isArray(res.result)) {
                        const fromResult = res.result.flatMap(provider => {
                            let providerNameForLogo = provider.name;
                            if (providerNameForLogo === 'grab_express') providerNameForLogo = 'grab';

                            return provider.costs.map(cost => ({
                                ...cost,
                                service: providerNameForLogo,
                                service_name: `${provider.name.toUpperCase()}`,
                                service_type_label: `${cost.service_type}`,
                                cost: cost.price.total_price,
                                price: cost.price,
                                etd: cost.estimation || '-',
                                setting: cost.setting || {},
                                insurance: cost.price.insurance_fee || 0,
                                cod: cost.cod_available ?? false,
                                is_instant: true
                            }));
                        });
                        allResults.push(...fromResult);
                    }

                    if (res.results && Array.isArray(res.results)) {
                        const fromResults = res.results.map(service => ({ ...service, cost: service.cost, price: { base_price: service.cost, total_price: service.cost }, insurance: service.insurance || 0, cod: service.cod, service_name: `${service.service.toUpperCase()}`, service_type_label: `${service.service_type}`, is_instant: false}));
                        allResults.push(...fromResults);
                    }

                    allResults.sort((a, b) => a.cost - b.cost);
                    const b = $('#ongkirResultsContainer').empty();

                    if (allResults.length === 0) {
                         $('#ongkirResultsContainer').html(`<div class="alert alert-warning text-center">Tidak ada layanan yang tersedia untuk filter ini.</div>`);
                         return;
                    }

                    const headerHtml = `<div class="ongkir-header-row d-none d-lg-flex"><div class="ongkir-item-col col-service">Layanan</div><div class="ongkir-item-col col-etd">Estimasi</div><div class="ongkir-item-col col-cod">COD</div><div class="ongkir-item-col col-price">Tarif</div><div class="ongkir-item-col col-action"></div></div>`;
                    b.append(headerHtml);

                    allResults.forEach(i => {
                        const logoName = (i.service || "").toLowerCase().replace(/\s+/g, '');
                        let logoUrl = '';
                        if (logoName === 'gosend') {
                            logoUrl = 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png';
                        } else if (logoName === 'grab') {
                            logoUrl = 'https://tokosancaka.com/public/storage/logo-ekspedisi/grab.png';
                        } else if (logoName) {
                            logoUrl = `{{ asset('public/storage/logo-ekspedisi/') }}/${logoName}.png`;
                        }

                        const safeService = (i.service || '').toString().replace(/-/g, ' ');
                        const safeServiceTypeLabel = (i.service_type_label || '').toString().replace(/-/g, ' ');
                        const useInsurance = $('#ansuransi').val() === 'iya';
                        const insuranceFeeValue = useInsurance ? (i.insurance || 0) : 0;
                        const codFee = (i.setting && i.setting.cod_fee_amount) ? i.setting.cod_fee_amount : 0;
                        const v = `${serviceType}-${safeService}-${safeServiceTypeLabel}-${i.cost}-${insuranceFeeValue}-${codFee}`;

                        // PERBAIKAN: Pisahkan murni Ongkir Dasar dan Biaya COD
                        const baseOngkirCost = parseInt(i.cost || 0) + parseInt(insuranceFeeValue || 0);
                        const actualCodFee = parseInt(codFee || 0);

                        const hasDiscount = i.price?.base_price && i.price.base_price > i.cost;
                        const basePriceFmt = hasDiscount ? formatRupiah(i.price.base_price) : '';

                        const insuranceFee = i.insurance || 0;
                        let feeDetailsHtml = '';
                        if (useInsurance && insuranceFee > 0) { feeDetailsHtml += `<div><small>Termasuk Asuransi: ${formatRupiah(insuranceFee)}</small></div>`; }
                        if (i.cod && codFee > 0) { feeDetailsHtml += `<div><small>Biaya COD: ${formatRupiah(codFee)}</small></div>`; }

                        let etdHtml = '';
                        if (i.etd) {
                            const etdText = i.etd.toString();
                            if (i.is_instant) {
                                etdHtml = `<span>${etdText}</span>`;
                            } else {
                                etdHtml = `<span>${etdText} Hari</span>`;
                            }
                        }

                        // PENAMBAHAN: Atribut data-shipping-cost di HTML
                        const buttonHtml = `<button type="button" class="btn btn-kirim select-ongkir-btn" data-value="${v}" data-display="${i.service_name} - ${i.service_type_label}" data-cod-supported="${i.cod}" data-shipping-cost="${baseOngkirCost}" data-cod-fee="${actualCodFee}">Kirim Paket</button>`;

                        const itemHtml = `
                        <div class="ongkir-item-card">
                            <div class="ongkir-item-col col-service">
                                <img src="${logoUrl}" class="ongkir-logo" onerror="this.style.display='none'">
                                <div class="service-info">
                                    <span class="service-name">${i.service_name}</span>
                                    <span class="service-type">${i.service_type_label}</span>
                                </div>
                            </div>
                            <div class="ongkir-item-col col-etd">
                                <span class="col-label">Estimasi</span>
                                ${etdHtml}
                            </div>
                            <div class="ongkir-item-col col-cod">
                                <span class="col-label">COD</span>
                                <span>${i.cod ? 'Tersedia' : '-'}</span>
                            </div>
                            <div class="ongkir-item-col col-price">
                                <span class="col-label">Tarif</span>
                                <div class="price-value">
                                    <span class="final-price">${formatRupiah(i.cost)}</span>
                                    ${hasDiscount ? `<span class="base-price text-decoration-line-through">${basePriceFmt}</span>` : ''}
                                </div>
                                <div class="price-details">${feeDetailsHtml}</div>
                            </div>
                            <div class="ongkir-item-col col-action">
                                ${buttonHtml}
                            </div>
                        </div>`;
                        b.append(itemHtml);
                    });
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    let errorMsg = 'Gagal mengambil data ongkir. Silakan periksa koneksi Anda dan coba lagi.';
                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errorMsg = jqXHR.responseJSON.message;
                    }
                    $('#ongkirResultsContainer').html(`<div class="alert alert-danger text-center">${errorMsg}</div>`);
                }
            });
        }

        const fieldsThatAffectShipping = '#sender_district_id, #receiver_district_id, #item_price, #weight, #length, #width, #height, #ansuransi, #service_type';
        $(document).on('change', fieldsThatAffectShipping, function() {
            $('#expedition').val('');
            $('#selected_expedition_display').val('Data berubah, klik untuk cek ulang ongkir').removeClass('is-valid');
            $('.cod-payment-option').hide();

            // PENAMBAHAN: Reset cost ongkir dan reset payment gateway jika harga berubah
            $('#selected_shipping_cost').val('0');

            $('#payment_method').val('');
            $('#selectedPaymentName').text('Pilih Pembayaran...');
            $('#selectedPaymentLogo').addClass('d-none').attr('src', '');
            $('#defaultPaymentIcon').removeClass('d-none');

            // Panggil update monitor
            updateTotalSummary();
        });

        $('#selected_expedition_display').on('click', runCekOngkir);

       $(document).on('click', '.select-ongkir-btn', function() {
            const expeditionValue = $(this).data('value');
            $('#expedition').val(expeditionValue);
            $('#selected_expedition_display').val($(this).data('display')).addClass('is-valid');

            // Tangkap nilai yang sudah terpisah murni
            $('#selected_shipping_cost').val($(this).data('shipping-cost'));
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
                                // PENAMBAHAN: Inject class "gateway-option" agar secara otomatis kena filter styling di event klik modal PG
                                const li = $(`
                                    <li class="list-group-item list-group-item-action d-flex align-items-center gateway-option"
                                        data-value="${ch.code}"
                                        data-label="${ch.name}">
                                        <img src="${ch.icon_url}" class="me-3 border rounded p-1 bg-white"
                                             style="width: 40px; height: 40px; object-fit: contain;"
                                             onerror="this.src='https://placehold.co/40x40?text=IMG'">
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">${ch.name}</div>
                                            <div class="text-muted" style="font-size: 0.75rem;">${ch.group_name || 'Pembayaran Online'}</div>
                                        </div>
                                    </li>
                                `);
                                container.append(li);
                            }
                        });
                        isPaymentApiLoaded = true;

                        // Eksekusi ulang pengecekan style (kalau API Tripay lebih lambat me-render daripada modal di klik)
                        applyGatewayMinimumLimit();
                    } else {
                        container.html('<div class="p-3 text-center text-muted small">Saluran pembayaran Tripay tidak tersedia.</div>');
                    }
                },
                error: function(err) {
                    console.error("Tripay API Error:", err);
                    container.html('<div class="p-3 text-center text-danger small"><i class="fas fa-exclamation-triangle me-1"></i> Gagal terhubung ke API Pembayaran.</div>');
                }
            });
        }

       function applyGatewayMinimumLimit() {
        // 1. Ambil harga barang (jika kosong, nilainya 0)
        let itemPrice = parseInt($('#item_price').val()) || 0;

        // 2. Ambil ongkir langsung dari input hidden yang terpercaya (bukan hasil split)
        let shippingCost = parseInt($('#selected_shipping_cost').val()) || 0;

        // 3. Hitung total keseluruhan murni (Hanya Ongkir. Jika ingin termasuk harga barang, hapus komentar di atas)
        let totalTransaksi = shippingCost; // <-- Sesuai permintaan Anda: HANYA berdasarkan Ongkir

        // 4. KUNCI JIKA TOTAL DI BAWAH 10.000
        if (totalTransaksi < 10000 && totalTransaksi > 0) { // Pastikan > 0 supaya tidak terkunci saat awal buka form
            // Matikan tombol dengan CSS paksa
            $('.gateway-option')
                .addClass('disabled')
                .css({
                    'pointer-events': 'none',
                    'opacity': '0.3',
                    'background-color': '#f3f4f6'
                });

            // Munculkan pesan peringatan merah
            let alertMsg = `<i class="fas fa-exclamation-triangle me-1"></i> Total Ongkir (Rp ${totalTransaksi.toLocaleString('id-ID')}) di bawah minimum Rp 10.000. Payment Gateway dimatikan.`;
            if ($('#min-tx-alert').length === 0) {
                $('#paymentOptionsList').prepend(`<li id="min-tx-alert" class="list-group-item list-group-item-danger text-center small fw-bold p-2 mb-2 rounded border border-danger">${alertMsg}</li>`);
            } else {
                $('#min-tx-alert').html(alertMsg).show();
            }

            // Reset form jika terlanjur diklik
            let selectedMethod = $('#payment_method').val();
            if (selectedMethod && $('.gateway-option[data-value="'+selectedMethod+'"]').length > 0) {
                $('#payment_method').val('');
                $('#selectedPaymentName').text('Pilih Pembayaran...');
                $('#selectedPaymentLogo').addClass('d-none').attr('src', '');
                $('#defaultPaymentIcon').removeClass('d-none');
                $('.gateway-option').removeClass('active');
            }
        } else {
            // BUKA KUNCI JIKA 10.000 ATAU LEBIH
            $('.gateway-option')
                .removeClass('disabled')
                .css({
                    'pointer-events': 'auto',
                    'opacity': '1',
                    'background-color': ''
                });
            $('#min-tx-alert').hide();
        }
    }

        $('#paymentMethodButton').on('click', function() {
            // PENAMBAHAN: Validasi dijalankan setiap kali tombol "Metode Pembayaran" diklik
            applyGatewayMinimumLimit();

            paymentModal.show();
            loadTripayChannels();
        });

      // Fungsi Helper untuk mengeksekusi pilihan metode pembayaran
function executePaymentSelection(element) {
    const value = element.data('value');
    const label = element.data('label');
    const imgSrc = element.find('img').attr('src');

    // Tampilkan saldo aslinya di kotak pilihan (opsional agar lebih informatif)
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

    // PENAMBAHAN: Panggil fungsi update agar monitor total langsung berubah
    updateTotalSummary();
}

        // Logika Klik Opsi Pembayaran
        $('#paymentOptionsList').on('click', '.list-group-item-action', function(e) {
            // BLOKIR KERAS JIKA TOMBOL DALAM MODE DISABLED
            if ($(this).hasClass('disabled')) {
                e.preventDefault();
                return false;
            }

            if (!$(this).data('value')) return;

            // Jika butuh PIN dan belum verifikasi, CEGAT DISINI
            if ($(this).hasClass('requires-pin') && !isPinVerified) {
                pendingPaymentSelection = $(this); // Simpan sementara opsi yang diklik
                $('#paymentMethodModal').modal('hide');

                // Munculkan Modal PIN
                setTimeout(() => {
                    $('#pin_error_msg').addClass('d-none');
                    $('.pin-digit').val('');
                    $('#full_pin_value').val('');
                    $('#pinModal').modal('show');
                    setTimeout(() => { $('.pin-digit').first().focus(); }, 500);
                }, 400);
                return; // Hentikan eksekusi di sini
            }

            // Jika tidak butuh PIN, langsung eksekusi
            executePaymentSelection($(this));
        });

        $('.cod-payment-option').hide();

        // ============================================
        // LOGIKA SUBMIT PESANAN & INTERSEPSI PIN
        // ============================================
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
            if ($(this).val().length === 1) {
                $(this).next('.pin-digit').focus();
            }
            let pin = '';
            $('.pin-digit').each(function() { pin += $(this).val(); });
            $('#full_pin_value').val(pin);
            if (pin.length === 6) {
                $('#btnVerifyPin').click();
            }
        });

        $('.pin-digit').on('keydown', function(e) {
            if (e.key === 'Backspace' && $(this).val() === '') {
                $(this).prev('.pin-digit').focus();
            }
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
                    isPinVerified = true; // Tandai bahwa PIN sudah benar untuk sesi ini

                    // BUKA SENSOR SALDO & STATUS di list HTML
                    $('.requires-pin').each(function() {
                        let realBal = $(this).data('real-balance');
                        let prefix = $(this).find('.balance-text').data('prefix') || '';
                        if (realBal !== undefined) {
                            $(this).find('.balance-text').html(`${prefix}${formatRupiah(realBal)}`);
                        }
                        let badge = $(this).find('.status-badge');
                        if(badge.length) { badge.removeClass('bg-secondary').addClass('bg-primary').html('<i class="fas fa-check-circle me-1"></i> Tersambung'); }
                    });

                    // Cek dari mana asalnya verifikasi ini
                    if (pendingPaymentSelection) {
                        // Asalnya dari memilih metode pembayaran
                        Swal.fire({ title: 'Akses Terbuka!', text: 'Saldo ditampilkan dan metode dipilih.', icon: 'success', showConfirmButton: false, timer: 1500 })
                        .then(() => {
                            executePaymentSelection(pendingPaymentSelection); // Eksekusi pilihan yang tertunda
                            pendingPaymentSelection = null;
                        });
                    } else {
                        // Asalnya dari meng-klik tombol konfirmasi "Buat Pesanan" (Bypass)
                        Swal.fire({ title: 'PIN Terverifikasi!', text: 'Memproses pembayaran Anda...', icon: 'success', showConfirmButton: false, timer: 1500 })
                        .then(() => {
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

       // =========================================================
        // EFEK DEMO: AUTO-FILL PENGIRIM RSUD SOEROTO & ENTER OTOMATIS
        // =========================================================
        function autoFillRSUD() {
            // 1. Isi form secara visual
            $('#sender_name').val('RSUD dr. Soeroto Ngawi').removeClass('is-invalid').addClass('is-valid');
            $('#sender_phone').val('08123456789').removeClass('is-invalid').addClass('is-valid');
            $('#sender_address').val('Jl. Dr. Wahidin No.27, Karangtengah, Ngawi').removeClass('is-invalid').addClass('is-valid');
            $('#sender_address_search').val('Mencari titik koordinat RSUD...');

            // =========================================================
            // 2. KUNCI TOTAL FORM PENGIRIM (Visual & Interaksi Dimatikan)
            // =========================================================
            // Jadikan semua input "readonly" (agar bisa disubmit), lalu ubah warna
            // jadi abu-abu dan matikan interaksi kursor dengan 'pointer-events: none'
            $('#card-pengirim .form-control').prop('readonly', true).css({
                'background-color': '#e9ecef', // Warna abu-abu ala bootstrap disabled
                'pointer-events': 'none',      // Blokir total klik/kursor jari maupun teks
                'color': '#495057'             // Gelapkan warna teks
            });

            // Ubah juga icon di sebelah kiri input agar ikut abu-abu dan menyatu
            $('#card-pengirim .input-group-text').css({
                'background-color': '#e9ecef',
                'color': '#6c757d'
            });

            // Kunci spesifik checkbox dan kolom pencarian dengan atribut 'disabled' penuh
            // (Karena 2 field ini memang tidak dikirim ke Controller Laravel Anda)
            $('#save_sender').prop('disabled', true);
            $('#sender_address_search').prop('disabled', true);

            // =========================================================

            // 3. Tembak API Alamat untuk mendapatkan ID Ekspedisi KiriminAja
            $.get("{{ route('api.address.search') }}", { search: 'Karangtengah, Ngawi' })
            .done(function(results) {
                if (results && results.length > 0) {
                    // Ambil hasil paling akurat
                    let item = results[0];
                    let parts = item.full_address.split(',').map(s => s.trim());

                    // Set hidden input untuk KiriminAja
                    $('#sender_village').val(parts[0] || 'Karangtengah').trigger('change');
                    $('#sender_district').val(parts[1] || 'Ngawi').trigger('change');
                    $('#sender_regency').val(parts[2] || 'Ngawi').trigger('change');
                    $('#sender_province').val(parts[3] || 'Jawa Timur').trigger('change');
                    $('#sender_postal_code').val(parts[4] || '63218').trigger('change');
                    $('#sender_district_id').val(item.district_id).trigger('change');
                    $('#sender_subdistrict_id').val(item.subdistrict_id).trigger('change');
                    $('#sender_lat').val(item.lat || '');
                    $('#sender_lng').val(item.lon || '');

                    // Ubah teks pencarian saat data ditemukan (Tetap dibiarkan terkunci)
                    $('#sender_address_search').val('📍 Titik RSUD Ditemukan & Terkunci').addClass('is-valid');

                    // 4. ENTER OTOMATIS! (Klik tombol Lanjutkan ke Penerima)
                    setTimeout(() => {
                        $('#nextToPenerima').click();

                        // Notifikasi kecil
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Data Pengirim Terkunci & Diteruskan'
                        });

                    }, 1000); // Jeda 1 detik agar audiens sempat melihat form terisi
                }
            });
        }

        // Panggil fungsi ini tepat setelah halaman selesai dimuat
        // Diberi jeda 500ms agar rendering UI selesai dulu sebelum animasi jalan
        setTimeout(autoFillRSUD, 500);

       // =========================================================
        // LOGIKA PENCARIAN & AUTOFILL BERDASARKAN NOMOR RM
        // =========================================================
        $('#btnCekRM').on('click', function() {
            let rawInput = $('#nomor_rm').val().trim();
            let numericRm = rawInput.replace(/[^0-9]/g, '');

            if(!numericRm) {
                Swal.fire('Oops!', 'Silakan masukkan angka Nomor RM terlebih dahulu.', 'warning');
                return;
            }

            let rm = 'RM-' + numericRm;
            let $btn = $(this);
            let originalText = $btn.html();
            $btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
            $('#rm_status_text').text('Mencari data pasien...');

            $.ajax({
                url: `/rsud/api/cek-rm/${rm}`,
                type: 'GET',
                success: function(res) {
                    if(res.status && res.data) {
                        $('#receiver_name').val(res.data.nama_lengkap).removeClass('is-invalid').addClass('is-valid');
                        $('#receiver_phone').val(res.data.no_hp).removeClass('is-invalid').addClass('is-valid');
                        $('#receiver_address').val(res.data.alamat).removeClass('is-invalid').addClass('is-valid');
                        $('#rm_status_text').html('<span class="text-success fw-bold"><i class="fas fa-check-circle"></i> Data Pasien Berhasil Ditemukan!</span>');

                        // AKTIFKAN PENANDA OTOMATISASI
                        isAutoRMFlow = true;

                        if(res.data.kelurahan && res.data.kecamatan) {
                            let addressQuery = `${res.data.kelurahan}, ${res.data.kecamatan}`;
                            $('#receiver_address_search').val(addressQuery).trigger('input');

                            Swal.fire({
                                title: 'Pilih Alamat',
                                text: 'Silakan klik tombol "Pilih" pada alamat ekspedisi di bawah untuk melanjutkan ke pengiriman.',
                                icon: 'info',
                                timer: 3000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Berhasil!', 'Data pasien ditemukan, tetapi Anda harus mencari alamat secara manual untuk ekspedisi.', 'info');
                        }
                    }
                },
                error: function(err) {
                    $('#rm_status_text').html('<span class="text-danger fw-bold"><i class="fas fa-times-circle"></i> RM tidak ditemukan.</span>');
                    Swal.fire('Tidak Ditemukan', 'Nomor RM (' + numericRm + ') tidak terdaftar di sistem.', 'error');
                },
                complete: function() {
                    $btn.html(originalText).prop('disabled', false);
                }
            });
        });

        // =========================================================
        // TAMBAHAN: AUTO-SELECT EKSPEDISI POS INDONESIA
        // =========================================================
        $(document).ajaxComplete(function(event, xhr, settings) {
            // Memastikan kita hanya mencegat request API cekongkir
            if (settings.url.includes("cekongkir")) {

                setTimeout(() => {
                    // Cari tombol yang memiliki teks "POS" atau "POS INDONESIA" di atribut data-display
                    let posButton = $('.select-ongkir-btn').filter(function() {
                        let textName = $(this).data('display') ? $(this).data('display').toUpperCase() : '';
                        return textName.includes('POS INDONESIA') || textName.includes('POSAJA') || textName.includes('POS');
                    }).first(); // Ambil layanan pertama yang ditemukan (misal: Reguler)

                    if (posButton.length > 0) {
                        // Jika ditemukan, eksekusi klik otomatis
                        posButton.click();

                        // Beri notifikasi visual bahwa otomasi berhasil
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Ekspedisi POS INDONESIA Otomatis Dipilih!'
                        });
                    } else {
                        console.log("LOG: Layanan POS Indonesia tidak ditemukan untuk rute ini.");
                    }
                }, 1000); // Beri jeda 1 detik agar elemen DOM dalam modal dirender sempurna
            }
        });

    } // Akhir fungsi initSancakaScripts
</script>
@endpush
