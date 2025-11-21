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
        padding-top: 8rem; /* Disesuaikan agar ada ruang dari header */
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
    .ui-autocomplete { min-width: 300px; }
    .search-result-item, .ui-menu-item-wrapper {
        padding: 0.75rem 1rem;
        cursor: pointer;
        font-size: 0.9rem;
        border-radius: 0.375rem;
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
    .col-service { flex: 0 0 24%; flex-direction: row; align-items: center; }
    .ongkir-logo { width: 60px; height: auto; object-fit: contain; margin-right: 15px; }
    .service-info { display: flex; flex-direction: column; }
    .service-name { font-weight: 600; font-size: 0.95rem; color: var(--text-color); }
    .service-type { font-size: 0.8rem; color: var(--secondary-color); }
    .col-etd, .col-cod { flex: 0 0 12%; text-align: center; }
    .col-price { flex: 0 0 16%; text-align: right; }
    .price-value .final-price { font-weight: 700; font-size: 1rem; color: var(--success-color); }
    .price-details { font-size: 0.8rem; color: var(--secondary-color); margin-top: 2px; }
    .col-action { flex: 0 0 12%; text-align: right; }
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
    
    <!-- Stepper/Progres Indikator -->
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
    
    <form id="orderForm" action="{{ route('pesanan.public.store') }}" method="POST">
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
                        {{-- Konten detail paket tidak berubah --}}
                        <div class="row g-3">
                            <div class="col-12"><label for="item_description" class="form-label">Deskripsi Barang</label><div class="input-group"><span class="input-group-text"><i class="fas fa-tag"></i></span><input type="text" name="item_description" id="item_description" class="form-control" placeholder="Contoh: Baju, Sepatu, Dokumen" required></div></div>
                            <div class="col-md-6"><label for="item_price" class="form-label">Harga Barang</label><div class="input-group"><span class="input-group-text fw-bold">Rp</span><input type="number" name="item_price" id="item_price" class="form-control" placeholder="50000" required min="1"></div></div>
                            <div class="col-md-6"><label for="weight" class="form-label">Berat</label><div class="input-group"><span class="input-group-text"><i class="fas fa-weight-hanging"></i></span><input type="number" name="weight" id="weight" class="form-control" placeholder="1000" required min="1"><span class="input-group-text">gr</span></div></div>
                            <div class="col-12"><label class="form-label mb-2">Dimensi (Opsional)</label><div class="row g-2"><div class="col-4"><div class="input-group"><span class="input-group-text">P</span><input type="number" name="length" id="length" class="form-control" placeholder="cm"></div></div><div class="col-4"><div class="input-group"><span class="input-group-text">L</span><input type="number" name="width" id="width" class="form-control" placeholder="cm"></div></div><div class="col-4"><div class="input-group"><span class="input-group-text">T</span><input type="number" name="height" id="height" class="form-control" placeholder="cm"></div></div></div></div>
                            
                            <div class="col-md-6">
  <label for="item_type" class="form-label">Jenis Barang</label>
  <select name="item_type" id="item_type" class="form-select" required>
    <option value="" disabled selected>Pilih...</option>

    <!-- Elektronik & Gadget -->
    <option value="1">Elektronik</option>
    <option value="2">HP & Gadget</option>
    <option value="3">Komputer & Laptop</option>
    <option value="4">Aksesoris Elektronik</option>

    <!-- Fashion -->
    <option value="5">Pakaian Pria</option>
    <option value="6">Pakaian Wanita</option>
    <option value="7">Pakaian Anak</option>
    <option value="8">Sepatu & Sandal</option>
    <option value="9">Tas & Dompet</option>
    <option value="10">Perhiasan & Aksesoris</option>

    <!-- Rumah Tangga -->
    <option value="11">Peralatan Rumah Tangga</option>
    <option value="12">Peralatan Dapur</option>
    <option value="13">Furniture</option>
    <option value="14">Dekorasi Rumah</option>

    <!-- Kecantikan & Kesehatan -->
    <option value="15">Kosmetik & Makeup</option>
    <option value="16">Skincare</option>
    <option value="17">Alat Kesehatan</option>
    <option value="18">Obat & Suplemen</option>

    <!-- Hobi & Lifestyle -->
    <option value="19">Olahraga</option>
    <option value="20">Alat Musik</option>
    <option value="21">Fotografi & Kamera</option>
    <option value="22">Otomotif (Sparepart, Aksesoris Motor/Mobil)</option>

    <!-- Bayi & Anak -->
    <option value="23">Mainan Anak</option>
    <option value="24">Perlengkapan Bayi</option>
    <option value="25">Fashion Bayi & Anak</option>

    <!-- Makanan & Minuman -->
    <option value="26">Makanan & Minuman</option>
    <option value="27">Snack & Camilan</option>
    <option value="28">Kopi & Teh</option>
    <option value="29">Bahan Pokok</option>

    <!-- Buku & ATK -->
    <option value="30">Buku</option>
    <option value="31">Alat Tulis & Kantor</option>

    <!-- Lainnya -->
    <option value="32">Dokumen</option>
    <option value="33">Barang Pecah Belah</option>
    <option value="34">Lainnya</option>
  </select>
</div>


                            <div class="col-md-6"><label for="service_type" class="form-label">Jenis Layanan</label><select name="service_type" id="service_type" class="form-select" required><option value="regular" selected>Regular</option><option value="cargo">Cargo</option><option value="instant">Instant / Sameday</option></select></div>
                            <div class="col-12"><label for="ansuransi" class="form-label">Asuransi</label><div class="input-group"><span class="input-group-text"><i class="fas fa-shield-alt"></i></span><select name="ansuransi" id="ansuransi" class="form-select" required><option value="tidak" selected>Tidak Pakai Asuransi</option><option value="iya">Ya, Pakai Asuransi</option></select></div></div>
                            <div class="col-12"><hr class="my-3"></div>
                            <div class="col-12"><label for="selected_expedition_display" class="form-label">Pilih Ekspedisi</label><input type="text" id="selected_expedition_display" class="form-control text-start fw-bold" placeholder="Lengkapi data & klik di sini" readonly required><input type="hidden" name="expedition" id="expedition" required></div>
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
<div class="modal fade" id="paymentMethodModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title fw-bold"><i class="fas fa-credit-card me-2 text-danger"></i>Pilih Metode Pembayaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-0"><ul id="paymentOptionsList" class="list-group list-group-flush" style="cursor: pointer;">
    <li class="list-group-item list-group-item-action d-flex align-items-center cod-payment-option" data-value="COD" data-label="COD Ongkir"><img src="{{ asset('public/assets/cod.png') }}" class="me-3" style="width: 50px; object-fit: contain;">COD Ongkir</li>
    <li class="list-group-item list-group-item-action d-flex align-items-center cod-payment-option" data-value="CODBARANG" data-label="COD Barang + Ongkir"><img src="{{ asset('public/assets/cod.png') }}" class="me-3" style="width: 50px; object-fit: contain;">COD Barang + Ongkir</li>
    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="PERMATAVA" data-label="Permata VA"><img src="{{ asset('public/assets/permata.webp') }}" class="me-3" style="width: 50px; object-fit: contain;">Permata VA</li>
    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="BNIVA" data-label="BNI VA"><img src="{{ asset('public/assets/bni.webp') }}" class="me-3" style="width: 50px; object-fit: contain;">BNI VA</li>
    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="BRIVA" data-label="BRI VA"><img src="{{ asset('public/assets/bri.webp') }}" class="me-3" style="width: 50px; object-fit: contain;">BRI VA</li>
    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="MANDIRIVA" data-label="Mandiri VA"><img src="{{ asset('public/assets/mandiri.webp') }}" class="me-3" style="width: 50px; object-fit: contain;">Mandiri VA</li>
    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="BCAVA" data-label="BCA VA"><img src="{{ asset('public/assets/bca.webp') }}" class="me-3" style="width: 50px; object-fit: contain;">BCA VA</li>
    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="ALFAMART" data-label="Alfamart"><img src="{{ asset('public/assets/alfamart.webp') }}" class="me-3" style="width: 50px; object-fit: contain;">Alfamart</li>
    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="INDOMARET" data-label="Indomaret"><img src="{{ asset('public/assets/indomaret.webp') }}" class="me-3" style="width: 50px; object-fit: contain;">Indomaret</li>
    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="OVO" data-label="OVO"><img src="{{ asset('public/assets/ovo.webp') }}" class="me-3" style="width: 50px; object-fit: contain;">OVO</li>
    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="DANA" data-label="DANA"><img src="{{ asset('public/assets/dana.webp') }}" class="me-3" style="width: 50px; object-fit: contain;">DANA</li>
    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="SHOPEEPAY" data-label="ShopeePay"><img src="{{ asset('public/assets/shopeepay.webp') }}" class="me-3" style="width: 50px; object-fit: contain;">ShopeePay</li>
    <li class="list-group-item list-group-item-action d-flex align-items-center" data-value="QRIS" data-label="QRIS"><img src="{{ asset('public/assets/qris2.png') }}" class="me-3" style="width: 50px; object-fit: contain;">QRIS</li>
</ul></div></div></div></div>
@endsection

@push('scripts')
{{-- Pustaka jQuery & jQuery UI --}}
<script src="https://code.jquery.com/jquery-3.6.0.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

<script>
$(document).ready(function () {
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

    function maskData(type, value) { if (!value) return '***'; if (type === 'name') { const parts = value.split(' '); return parts.length > 1 ? parts[0] + ' ' + parts.slice(1).map(p => p.replace(/./g, '*')).join(' ') : (value.length > 2 ? value.substring(0, 2) + '***' : value); } if (type === 'phone') { const num = value.replace(/\D/g, ''); return num.length > 8 ? num.substring(0, 3) + '****' + num.substring(num.length - 4) : num.substring(0, 3) + '****'; } if (type === 'address') { const parts = value.split(' '); return parts.length > 2 ? parts.slice(0, 2).join(' ') + ' **** **** ****' : value; } return '***'; }
    function clearHiddenAddress(prefix) { $(`#${prefix}_province, #${prefix}_regency, #${prefix}_district, #${prefix}_village, #${prefix}_postal_code, #${prefix}_district_id, #${prefix}_subdistrict_id`).val(''); }
    
    function fillContactForm(prefix, data) {
        $(`#${prefix}_name`).val(maskData('name', data.nama)).trigger('blur').attr('data-real-value', data.nama);
        $(`#${prefix}_phone`).val(maskData('phone', data.no_hp)).trigger('blur').attr('data-real-value', data.no_hp);
        $(`#${prefix}_address`).val(maskData('address', data.alamat)).trigger('blur').attr('data-real-value', data.alamat);
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
                    addressSearchInput.val('Alamat Ditemukan (Privasi Terjaga)').addClass('is-valid').removeClass('is-invalid');
                    setTimeout(() => addressSearchInput.removeClass('is-valid'), 2500);
                } else {
                    addressSearchInput.val('').addClass('is-invalid').removeClass('is-valid');
                    Swal.fire({ title: 'Alamat Tidak Ditemukan', text: `Detail alamat untuk "${addressQuery}" tidak ditemukan. Anda wajib mencari alamat secara manual.`, icon: 'warning', confirmButtonColor: '#dc3545' }).then(() => addressSearchInput.focus());
                }
            }).fail(() => {
                addressSearchInput.val('').addClass('is-invalid').removeClass('is-valid');
                Swal.fire({ title: 'Error', text: 'Gagal memuat detail alamat. Anda wajib mencari alamat secara manual.', icon: 'error', confirmButtonColor: '#dc3545' }).then(() => addressSearchInput.focus());
            }).always(() => addressSearchInput.prop('disabled', false));
        } else {
            addressSearchInput.val('').addClass('is-invalid');
            Swal.fire({ title: 'Data Tidak Lengkap', text: 'Kontak yang dipilih tidak memiliki data alamat. Anda wajib mengisi dan mencari alamat secara manual.', icon: 'info', confirmButtonColor: '#0d6efd' }).then(() => addressSearchInput.focus());
        }
    }

    function setupContactSearch(prefix) {
        $(`#${prefix}_name, #${prefix}_phone`).autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: "{{ route('api.search.kontak') }}",
                    dataType: "json",
                    data: { term: request.term },
                    success: function(data) {
                        if (!data || !data.length) {
                            response([{ label: 'Kontak tidak ditemukan', value: request.term, disabled: true }]);
                            return;
                        }
                        response($.map(data, function(item) {
                            return { label: `${item.nama} - ${item.no_hp}`, value: item.nama, data: item };
                        }));
                    },
                    error: function() {
                        response([]);
                    }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                if (ui.item.disabled) return false;
                event.preventDefault();
                fillContactForm(prefix, ui.item.data);
            },
            focus: function(event, ui) {
                if (ui.item.disabled) return false;
                event.preventDefault();
                $(`#${prefix}_name`).val(ui.item.data.nama);
                $(`#${prefix}_phone`).val(ui.item.data.no_hp);
            }
        }).autocomplete("instance")._renderItem = function(ul, item) {
            if (item.disabled) {
                return $("<li class='ui-state-disabled p-3 text-muted'></li>").text(item.label).appendTo(ul);
            }
            return $("<li>").append(`<div class="ui-menu-item-wrapper"><div class="font-weight-bold">${item.data.nama}</div><small>${item.data.no_hp}</small></div>`).appendTo(ul);
        };
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

    function runCekOngkir() {
        let formData = $('#orderForm').serializeArray();
        formData.forEach((item, index) => { let realVal = $(`#${item.name.replace(/\[/g, '\\[').replace(/\]/g, '\\]')}`).attr('data-real-value'); if (realVal) formData[index].value = realVal; });
        let tempForm = $('<form>').append($.map(formData, item => $('<input>').attr({type: 'hidden', name: item.name, value: item.value})));

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
                if (!hasData) {
                    let errorMessage = res.text || 'Layanan pengiriman tidak ditemukan untuk rute atau jenis layanan ini.';
                    $('#ongkirResultsContainer').html(`<div class="alert alert-warning text-center">${errorMessage}</div>`);
                    return;
                }

                if (res.result && Array.isArray(res.result)) {
                    const fromResult = res.result.flatMap(provider =>
                        provider.costs.map(cost => ({...cost, service: provider.name, service_name: `${provider.name.toUpperCase()}`, service_type_label: `${cost.service_type}`, cost: cost.price.total_price, price: cost.price, etd: cost.estimation || '-', setting: cost.setting || {}, insurance: cost.price.insurance_fee || 0 }))
                    );
                    allResults.push(...fromResult);
                }

                if (res.results && Array.isArray(res.results)) {
                    const fromResults = res.results.map(service => ({ ...service, cost: service.cost, price: { base_price: service.cost, total_price: service.cost }, insurance: service.insurance || 0, cod: service.cod, service_name: `${service.service.toUpperCase()}`, service_type_label: `${service.service_type}`}));
                    allResults.push(...fromResults);
                }

                allResults.sort((a, b) => a.cost - b.cost);
                const b = $('#ongkirResultsContainer').empty();
                const headerHtml = `<div class="ongkir-header-row d-none d-lg-flex"><div class="ongkir-item-col col-service">Layanan</div><div class="ongkir-item-col col-etd">Estimasi</div><div class="ongkir-item-col col-cod">COD</div><div class="ongkir-item-col col-pickup">Opsi Penjemputan</div><div class="ongkir-item-col col-discount">Diskon</div><div class="ongkir-item-col col-price">Tarif</div><div class="ongkir-item-col col-action"></div></div>`;
                b.append(headerHtml);

                allResults.forEach(i => {
                    const safeService = (i.service || '').toString().replace(/-/g, ' ');
                    const safeServiceTypeLabel = (i.service_type_label || '').toString().replace(/-/g, ' ');
                    const useInsurance = $('#ansuransi').val() === 'iya';
                    const insuranceFeeValue = useInsurance ? (i.insurance || 0) : 0;
                    const v = `${serviceType}-${safeService}-${safeServiceTypeLabel}-${i.cost}-${insuranceFeeValue}-${i.setting?.cod_fee_amount||0}`;
                    const hasDiscount = i.price?.base_price && i.price.base_price > i.cost;
                    const basePriceFmt = hasDiscount ? formatRupiah(i.price.base_price) : '';
                    const discountFmt = hasDiscount ? `${Math.round(((i.price.base_price - i.cost) / i.price.base_price) * 100)}%` : 'FLAT';
                    const codFee = i.setting?.cod_fee_amount || 0;
                    const insuranceFee = i.insurance || 0;
                    let feeDetailsHtml = '';
                    if (useInsurance && insuranceFee > 0) { feeDetailsHtml += `<div><small>Termasuk Asuransi: ${formatRupiah(insuranceFee)}</small></div>`; }
                    if (i.cod && codFee > 0) { feeDetailsHtml += `<div><small>Biaya COD: ${formatRupiah(codFee)}</small></div>`; }
                    const buttonHtml = `<button type="button" class="btn btn-kirim select-ongkir-btn" data-value="${v}" data-display="${i.service_name} - ${i.service_type_label}" data-cod-supported="${i.cod}">Kirim Paket</button>`;
                    const itemHtml = `<div class="ongkir-item-card"><div class="ongkir-item-col col-service"><img src="{{ asset('storage/logo-ekspedisi/') }}/${i.service.toLowerCase().replace(/\s+/g, '')}.png" class="ongkir-logo" onerror="this.style.display='none'"><div class="service-info"><span class="service-name">${i.service_name}</span><span class="service-type">${i.service_type_label}</span></div></div><div class="ongkir-item-col col-etd"><span class="col-label">Estimasi</span><span>${i.etd} Hari</span></div><div class="ongkir-item-col col-cod"><span class="col-label">COD</span><span>${i.cod ? 'Tersedia' : '-'}</span></div><div class="ongkir-item-col col-pickup"><span class="col-label">Opsi Penjemputan</span><span>Pick Up & Drop Off</span></div><div class="ongkir-item-col col-discount"><span class="col-label">Diskon</span><span>${discountFmt}</span></div><div class="ongkir-item-col col-price"><span class="col-label">Tarif</span><div class="price-value"><span class="final-price">${formatRupiah(i.cost)}</span>${hasDiscount ? `<span class="base-price">${basePriceFmt}</span>` : ''}</div><div class="price-details">${feeDetailsHtml}</div></div><div class="ongkir-item-col col-action">${buttonHtml}</div></div>`;
                    b.append(itemHtml);
                });
            },
            error: function() {
                $('#ongkirResultsContainer').html(`<div class="alert alert-danger text-center">Gagal mengambil data ongkir. Silakan periksa koneksi Anda dan coba lagi.</div>`);
            }
        });
    }

    const fieldsThatAffectShipping = '#sender_district_id, #receiver_district_id, #item_price, #weight, #length, #width, #height, #ansuransi, #service_type';
    $(document).on('change', fieldsThatAffectShipping, function() {
        $('#expedition').val('');
        $('#selected_expedition_display').val('Data berubah, klik untuk cek ulang ongkir').removeClass('is-valid');
        $('.cod-payment-option').hide();
    });

    $('#selected_expedition_display').on('click', runCekOngkir);

    $(document).on('click', '.select-ongkir-btn', function() {
        const expeditionValue = $(this).data('value');
        $('#expedition').val(expeditionValue);
        $('#selected_expedition_display').val($(this).data('display')).addClass('is-valid');
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
        ongkirModal.hide();
    });

    $('#paymentMethodButton').on('click', () => paymentModal.show());
    $('#paymentOptionsList .list-group-item').on('click', function() { $('#payment_method').val($(this).data('value')); $('#selectedPaymentName').text($(this).data('label')); $('#defaultPaymentIcon').addClass('d-none'); $('#selectedPaymentLogo').attr('src', $(this).find('img').attr('src')).removeClass('d-none'); $('#paymentOptionsList .list-group-item').removeClass('active'); $(this).addClass('active'); paymentModal.hide(); });
    $('.cod-payment-option').hide();
    $('#confirmBtn').on('click', function(e) { e.preventDefault(); const $this = $(this); unmaskDataForSubmit(); if (!$('#orderForm')[0].checkValidity()) { $('#orderForm')[0].reportValidity(); Swal.fire('Peringatan', 'Harap lengkapi semua field yang wajib diisi.', 'warning'); return; } Swal.fire({ title: 'Konfirmasi Pesanan', text: "Apakah semua data sudah benar?", icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Buat Pesanan', cancelButtonText: 'Batal' }).then((result) => { if (result.isConfirmed) { $this.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...'); $('#orderForm').submit(); } }); });
    $('#cekOngkirWaBtn').on('click', () => { unmaskDataForSubmit(); window.open(`https://wa.me/6285745808809?text=${encodeURIComponent(`Halo, saya mau tanya ongkir:\n\n*Dari:* ${$('#sender_address').val()}, ${$('#sender_village').val()}\n*Ke:* ${$('#receiver_address').val()}, ${$('#receiver_village').val()}\n*Berat:* ${$('#weight').val()} gr\n\nTerima kasih.`)}`, '_blank'); });
    $(document).on('click', e => { if (!$(e.target).closest('.input-group').length) { $('.search-results-container').addClass('d-none'); } });
});
</script>
@endpush

