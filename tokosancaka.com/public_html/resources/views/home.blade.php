@extends('layouts.app')



@section('title', 'Selamat Datang di Sancaka Express')



@push('styles')

<style>

    :root {

        --google-blue: #1a73e8;

    }

    .badge-kirim {
    display: inline-block;
    margin-left: 12px;
    padding: 8px 20px;
    background: linear-gradient(135deg, #0d6efd, #084298); /* biru premium */
    color: #fff;
    font-weight: 700;
    font-size: 0.85em;
    border-radius: 999px;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 0 6px 14px rgba(13, 110, 253, 0.45);
    transition: all 0.3s ease;
    animation: blinkBlue 1.6s infinite, pulseBlue 2s infinite;
}

/* Hover seperti tombol */
.badge-kirim:hover {
    transform: translateY(-2px) scale(1.05);
    background: linear-gradient(135deg, #0b5ed7, #0a58ca);
    box-shadow: 0 10px 22px rgba(13, 110, 253, 0.6);
}

/* Berkedip halus */
@keyframes blinkBlue {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.65; }
}

/* Pulse biru */
@keyframes pulseBlue {
    0% {
        box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.6);
    }
    70% {
        box-shadow: 0 0 0 14px rgba(13, 110, 253, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(13, 110, 253, 0);
    }
}

    .badge-gratis {
    display: inline-block;
    margin-left: 10px;
    padding: 8px 18px;
    background: linear-gradient(135deg, #8b0000, #c40000);
    color: #fff;
    font-weight: 800;
    font-size: 0.85em;
    border-radius: 999px;
    cursor: pointer;
    text-decoration: none;
    box-shadow: 0 6px 14px rgba(139, 0, 0, 0.45);
    transition: all 0.3s ease;
    animation: blink 1.2s infinite, pulse 1.8s infinite;
}

.badge-gratis:hover {
    transform: translateY(-2px) scale(1.05);
    background: linear-gradient(135deg, #a30000, #ff0000);
    box-shadow: 0 10px 22px rgba(255, 0, 0, 0.55);
}

/* Berkedip */
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* Pulse */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(196, 0, 0, 0.7);
    }
    70% {
        box-shadow: 0 0 0 14px rgba(196, 0, 0, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(196, 0, 0, 0);
    }
}



    .action-bar .action-card {

        background-color: #ffffff;

        border-radius: 0.75rem;

        padding: 1.5rem;

        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);

        transition: transform 0.2s, box-shadow 0.2s;

        height: 100%;

        display: flex;

        flex-direction: column;

    }

    .action-bar .action-card.clickable:hover {

        transform: translateY(-5px);

        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);

        cursor: pointer;

    }



    .section {

        padding: 4rem 0;

    }

    .section-title {

        text-align: center;

        font-weight: 700;

        margin-bottom: 3rem;

    }



    .service-card {

        text-align: center;

        padding: 2rem;

        background-color: #ffffff;

        border-radius: 0.75rem;

        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);

        height: 100%;

    }



    .partner-logo-card {

        padding: 1rem;

        background-color: #ffffff;

        border-radius: 0.75rem;

        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);

        text-align: center;

        transition: transform 0.2s, box-shadow 0.2s;

        height: 100%;

        display: flex;

        align-items: center;

        justify-content: center;

    }

    /* KODE BARU (GAMBAR BERWARNA & TERANG) */
.partner-logo-card img {
    max-height: 60px;       /* Ukuran disesuaikan agar pas */
    width: 100%;
    object-fit: contain;    /* Agar gambar tidak gepeng */
    filter: none !important;  /* MEMATIKAN EFEK ABU-ABU */
    opacity: 1 !important;    /* MEMATIKAN EFEK TRANSPARAN/PUDAR */
    transition: transform 0.3s ease; /* Efek animasi saat disentuh */
}

/* Efek saat mouse diarahkan (Hover) */
.partner-logo-card:hover img {
    transform: scale(1.1); /* Gambar membesar sedikit saat di-hover */
}



    .testimonial-card {

        background-color: #ffffff;

        border-radius: 0.75rem;

        padding: 2rem;

        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);

        height: 100%;

        display: flex;

        flex-direction: column;

    }



/* Semua tombol floating */

.floating-btn {

position: fixed;

width: 50px;

 height: 50px;

border-radius: 50%;

display: flex;

align-items: center;

justify-content: center;

box-shadow: 0 4px 12px rgba(0,0,0,0.2);

 z-index: 1000;

 color: white;

font-size: 24px; /* untuk FA icon */

cursor: pointer;

 transition: transform 0.2s, background-color 0.3s, bottom 0.3s ease-in-out;

}



/* Hover effect */

.floating-btn:hover {

transform: scale(1.1);

color: white;

}



/* Tombol WA */

.wa-float {

 bottom: 30px; /* Default position */

 right: 30px;

 background-color: #25d366;

}



/* Tombol Scroll */

#scrollTopBtn {

bottom: 30px;

 right: 30px;

background-color: #2563eb;

 display: none; /* Initially hidden */

}



/* Samakan ukuran ikon SVG / FA di dalam tombol */

.floating-btn svg,

.floating-btn i {

width: 22px;

 height: 22px;

 font-size: 22px; /* untuk Font Awesome */

}

    /* ===== STYLING UNTUK FITUR PENCARIAN ALAMAT BARU ===== */

    .address-search-container .nav-pills .nav-link {

        border-radius: 0.5rem;

        font-weight: 500;

        transition: all 0.2s ease-in-out;

    }



    .address-search-container .nav-pills .nav-link.active {

        background-color: var(--google-blue);

        color: white;

        box-shadow: 0 4px 12px rgba(26, 115, 232, 0.25);

    }



    .address-search-container .form-select,

    .address-search-container .form-control {

        border-radius: 0.5rem;

    }



    .address-search-container .form-select:disabled {

        background-color: #f3f4f6;

        cursor: not-allowed;

    }



    .address-search-container .results-table {

        margin-top: 1.5rem;

        border-radius: 0.75rem;

        box-shadow: 0 2px 10px rgba(0,0,0,0.07);

    }

    /* ===== AKHIR STYLING FITUR PENCARIAN ALAMAT BARU ===== */

/* Mengubah tampilan Modal */
    #cekOngkirModal .modal-content {
        border-radius: 0.75rem; /* 12px */
        border: none;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    #cekOngkirModal .modal-header {
        background-color: #dc2626; /* Biru */
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
        border-bottom: none;
        color: white;
    }
    #cekOngkirModal .modal-header .btn-close-white {
        filter: invert(1) grayscale(100%) brightness(200%);
    }

    /* Membuat input form lebih elegan */
    #cekOngkirModal .form-control,
    #cekOngkirModal .form-select {
        border-radius: 0.5rem; /* 8px */
        padding: 0.75rem 1rem; /* 12px 16px */
        border: 1px solid #ced4da;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    #cekOngkirModal .form-control:focus,
    #cekOngkirModal .form-select:focus {
        border-color: #dc2626;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
    }
    #cekOngkirModal .form-label {
        font-weight: 600;
        color: #374151; /* Gray-700 */
    }

    /* Ikon di dalam input */
    .input-group-elegant {
        position: relative;
    }
    #cekOngkirModal .input-group-elegant .form-control {
        padding-left: 3rem !important; /* PENTING: Paksa padding */
    }
    .input-group-elegant .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af; /* Gray-400 */
        z-index: 5;
        pointer-events: none;
    }

    /* Kustomisasi Tombol Submit */
    #cekOngkirModal #submit-button {
        background-color: #dc2626;
        border-color: #dc2626;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        border-radius: 0.5rem;
        transition: background-color 0.2s;
    }
    #cekOngkirModal #submit-button:hover {
        background-color: #ff0000;
    }

    /* Kustomisasi Tab Hasil */
    #cost-results-container .nav-tabs {
        border-bottom: 2px solid #e5e7eb; /* Gray-200 */
    }
    #cost-results-container .nav-tabs .nav-link {
        border: none;
        border-bottom: 2px solid transparent;
        color: #6b7280; /* Gray-500 */
        font-weight: 600;
        margin-bottom: -2px;
    }
    #cost-results-container .nav-tabs .nav-link.active {
        color: #dc2626; /* Biru */
        border-bottom-color: #dc2626;
    }

    /* Kustomisasi Card Hasil */
    #cost-results-container .list-group-item {
        /* [PERBAIKAN] Paksa background putih (hilangkan merah) */
        background-color: #ffffff !important;
        border-bottom: 1px solid #e5e7eb; /* Gray-200 */
    }
    #cost-results-container .list-group-item:last-child {
        border-bottom: none;
    }
    #cost-results-container .courier-logo {
        height: 30px;
        width: 60px;
        object-fit: contain;
    }
    #cost-results-container .btn-pilih {
        background-color: #dc2626; /* Merah */
        border-color: #dc2626;
        color: white;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 0.25rem 0.75rem;
        border-radius: 0.375rem;
    }

    /* === TAMBAHKAN BLOK INI === */
    /* Perbaikan Z-Index Autocomplete */
    #cekOngkirModal #origin-results,
    #cekOngkirModal #destination-results {
        /* z-index modal Bootstrap adalah 1055 */
        /* Kita buat 1056 agar muncul di atas modal */
        z-index: 1056 !important;
    }

    /* === TAMBAHKAN BLOK INI === */
    /* Perbaikan Teks Putih saat Hover */
    #cost-results-container .list-group-item:hover {
        /* Ganti background hover menjadi abu-abu netral */
        background-color: #f8f9fa !important;
    }

    #cost-results-container .list-group-item:hover h6,
    #cost-results-container .list-group-item:hover small {
        /* Paksa warna teks (nama layanan & estimasi) tetap gelap */
        color: #212529 !important;
    }

    #cost-results-container .list-group-item:hover h5 {
        /* Paksa warna harga tetap hijau */
        color: #198754 !important;
    }
    /* === AKHIR BLOK TAMBAHAN === */

</style>

@endpush

@section('content')



<section class="hero-section">
    <div class="container position-relative z-2">
        <div class="row align-items-center">

            <div class="col-lg-6">
                <div class="hero-badge animate-fade-up">
                    <i class="fas fa-bolt text-warning me-2"></i> Kirim Paket Lebih Cepat
                </div>

                <h1 class="hero-title animate-fade-up">
                    Solusi Pengiriman <br>
                    Cepat & Aman
                </h1>

                <p class="hero-subtitle animate-fade-up delay-1">
                    Jangkauan seluruh Indonesia. Lacak paket real-time, tarif transparan,
                    dan jaminan keamanan terpercaya bersama Sancaka Express.
                </p>

                <div class="d-flex gap-3 justify-content-md-start justify-content-center animate-fade-up delay-2">
                    <a href="#cek-ongkir" class="btn btn-hero-primary">
                        Mulai Kirim
                    </a>
                    <a href="#lacak" class="btn btn-hero-outline">
                        Lacak Paket
                    </a>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="hero-image-container animate-fade-up delay-1">
                    <img src="https://cdni.iconscout.com/illustration/premium/thumb/logistics-service-illustration-download-in-svg-png-gif-file-formats--delivery-transportation-cargo-business-pack-illustrations-3617637.png"
                         alt="Ilustrasi Logistik Sancaka"
                         class="hero-image">
                </div>
            </div>

        </div>
    </div>

    <div class="custom-shape-divider-bottom">
        <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
        </svg>
    </div>
</section>



<!-- Action Bar Section -->

<div class="container" style="margin-top: -80px; position: relative; z-index: 20;">

    <div class="action-bar">

        <div class="row g-4 row-cols-1 row-cols-lg-3">

            <div class="col">

                <div class="action-card h-100">

                    <h5 class="fw-bold"><i class="fa-solid fa-magnifying-glass me-2"></i>Lacak Kiriman Anda</h5>

                    <p class="text-muted small">Pantau posisi paket Anda secara akurat, real-time dan fast respond.</p>

                    <form action="{{ route('tracking.search') }}" method="GET" class="mt-auto">

                        <div class="input-group">

                            <input type="text" name="resi" class="form-control form-control-lg" placeholder="Cek Resi" required>

                            <button class="btn btn-danger px-4" type="submit" style="background-color: var(--google-blue); border-color: var(--google-blue);">

                                <i class="fas fa-arrow-right"></i>

                            </button>

                        </div>

                    </form>

                </div>

            </div>

            <div class="col">

                <div class="action-card clickable h-100" data-bs-toggle="modal" data-bs-target="#cekOngkirModal">

                    <h5 class="fw-bold"><i class="fa-solid fa-calculator me-2"></i>Cek Estimasi Ongkir</h5>

                    <p class="text-muted small">Hitung biaya pengiriman secara transparan sebelum mengirim.</p>

                    <div class="d-grid mt-auto">

                        <button class="btn btn-warning btn-lg" type="button">Cek Ongkir</button>

                    </div>

                </div>

            </div>

            <div class="col">

                <a href="#cek-alamat" class="text-decoration-none text-dark">

                    <div class="action-card clickable h-100">

                        <h5 class="fw-bold"><i class="fa-solid fa-map-pin me-2"></i>Cek Kode Pos & Wilayah</h5>

                        <p class="text-muted small">Temukan informasi kode pos dan detail wilayah di seluruh Indonesia.</p>

                        <div class="d-grid mt-auto">

                            <button class="btn btn-danger btn-lg text-white" type="button">Cek Alamat</button>

                        </div>

                    </div>

                </a>

            </div>

        </div>

    </div>

</div>


{{-- ====================================================================== --}}

{{-- == BAGIAN PENCARIAN ALAMAT & KODE POS (VERSI BOOTSTRAP 5 + JS) == --}}

{{-- ====================================================================== --}}

<section id="cek-alamat" class="section bg-light pt-5">

    <div class="container">

        <h2 class="section-title">Cek Alamat & Kode Pos Akurat</h2>

        <p class="text-center text-muted mb-5">Temukan informasi alamat lengkap dan kode pos di seluruh Indonesia.</p>



        <div class="card shadow-sm address-search-container">

            <div class="card-body p-4 p-lg-5">

                <!-- Navigasi Tab Bootstrap -->

                <ul class="nav nav-pills nav-fill mb-4" id="addressSearchTab" role="tablist">

                    <li class="nav-item" role="presentation">

                        <button class="nav-link active" id="wilayah-tab" data-bs-toggle="pill" data-bs-target="#wilayah-tab-pane" type="button" role="tab" aria-controls="wilayah-tab-pane" aria-selected="true">

                            <i class="fas fa-map-location-dot me-2"></i>Cari Berdasarkan Wilayah

                        </button>

                    </li>

                    <li class="nav-item" role="presentation">

                        <button class="nav-link" id="kodepos-tab" data-bs-toggle="pill" data-bs-target="#kodepos-tab-pane" type="button" role="tab" aria-controls="kodepos-tab-pane" aria-selected="false">

                            <i class="fas fa-magnifying-glass-location me-2"></i>Cari Berdasarkan Kata Kunci

                        </button>

                    </li>

                </ul>



                <!-- Konten Tab -->

                <div class="tab-content" id="addressSearchTabContent">

                    <!-- Konten Tab 1: Pencarian Berdasarkan Wilayah -->

                    <div class="tab-pane fade show active" id="wilayah-tab-pane" role="tabpanel" aria-labelledby="wilayah-tab" tabindex="0">

                        <div class="row g-3">

                            <div class="col-md-4">

                                <label for="select-provinsi" class="form-label fw-bold">Provinsi</label>

                                <select id="select-provinsi" class="form-select"></select>

                            </div>

                            <div class="col-md-4">

                                <label for="select-kabupaten" class="form-label fw-bold">Kabupaten/Kota</label>

                                <select id="select-kabupaten" class="form-select" disabled></select>

                            </div>

                            <div class="col-md-4">

                                <label for="select-kecamatan" class="form-label fw-bold">Kecamatan</label>

                                <select id="select-kecamatan" class="form-select" disabled></select>

                            </div>

                        </div>

                        <div id="wilayah-results-container" class="mt-3"></div>

                    </div>



                    <!-- Konten Tab 2: Pencarian Berdasarkan Kode Pos/Kata Kunci -->

                    <div class="tab-pane fade" id="kodepos-tab-pane" role="tabpanel" aria-labelledby="kodepos-tab" tabindex="0">

                        <div class="input-group input-group-lg">

                            <input type="text" class="form-control" id="kodepos-search-input" placeholder="Masukkan nama desa, kecamatan, kota, atau kode pos...">

                            <button class="btn btn-danger" id="kodepos-search-btn">

                                <i class="fas fa-search"></i> <span id="kodepos-search-btn-text">Cari</span>

                            </button>

                        </div>

                        <div id="kodepos-results-container" class="mt-3"></div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- Tentang Kami Section -->

<section id="tentang" class="section bg-light">

    <div class="container">

        <h2 class="section-title">Kenapa Memilih Sancaka Express?</h2>

        <div class="row align-items-center g-5">

            <div class="col-lg-6">

                <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" class="img-fluid rounded" alt="Profil Sancaka Express">

            </div>

            <div class="col-lg-6">

                <h3 class="fw-bold mb-3">CV. SANCAKA KARYA HUTAMA</h3>

                <p class="lead text-muted mb-4">

                    <em>"Lebih dari Sekadar Jasa Kirim, Kami Adalah Mitra Pertumbuhan Bisnis Anda."</em>

                </p>

                <div id="about-short">

                    <h5 class="fw-bold mt-4">Tentang Kami</h5>

                    <p>

                        <strong>Sancaka Express</strong> merupakan perusahaan jasa pengiriman yang berdedikasi

                        untuk memberikan solusi logistik terbaik bagi individu maupun pelaku bisnis.

                    </p>

                </div>

                <p>

                    Dengan jaringan distribusi yang luas serta didukung oleh tim profesional dan berpengalaman,

                    kami berkomitmen untuk memastikan setiap paket sampai ke tujuan dengan cepat, aman, dan tepat waktu.

                </p>

                <p>

                    Kami memahami bahwa pengiriman bukan sekadar memindahkan barang, melainkan juga menjaga kepercayaan pelanggan.

                    Oleh karena itu, setiap layanan kami dirancang untuk memberikan pengalaman yang nyaman,

                    transparan, dan dapat diandalkan.

                </p>

                <div id="about-full" class="d-none">

                    <h5 class="fw-bold mt-4">Visi</h5>

                    <p>

                        Menjadi mitra logistik terdepan yang mengutamakan kepuasan pelanggan melalui inovasi teknologi,

                        efisiensi layanan, dan keunggulan operasional.

                    </p>

                    <h5 class="fw-bold mt-4">Misi</h5>

                    <ul class="list-unstyled">

                        <li>1. Menyediakan layanan pengiriman yang cepat, aman, dan tepat waktu.</li>

                        <li>2. Membangun kepercayaan pelanggan melalui layanan prima dan komunikasi transparan.</li>

                        <li>3. Mengembangkan teknologi logistik untuk mendukung pertumbuhan bisnis pelanggan.</li>

                        <li>4. Menjadi mitra strategis dalam rantai pasok, dari skala individu hingga korporasi.</li>

                    </ul>

                </div>

                <button id="toggle-btn" class="btn btn-outline-primary btn-sm mt-3">

                    Baca Selengkapnya

                </button>

            </div>

        </div>

    </div>

</section>

<!-- Layanan Section -->

<section id="layanan" class="section">

    <div class="container">

        <h2 class="section-title">Layanan Kami</h2>

        <div class="row g-4">

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-box fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">Reguler & COD</h5>

                    <p>Pengiriman paket reguler dan Cash on Delivery ke seluruh pelosok Indonesia dengan cepat dan aman.</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-truck-moving fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">Cargo</h5>

                    <p>Solusi pengiriman barang dalam jumlah besar dengan tarif kompetitif dan penanganan profesional.</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-motorcycle fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">Pengiriman Motor</h5>

                    <p>Layanan khusus untuk pengiriman sepeda motor antar kota dan provinsi dengan jaminan keamanan.</p>

                </div>

            </div>

            <h2 class="section-title">Keunggulan Kami</h2>

            <p class="text-lg text-gray-600 mb-5 text-center">Fleksibilitas, jangkauan terluas, dan harga terbaik untuk pengiriman Anda.</p>

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-bolt fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">Efisien Dan Efektif</h5>

                    <p>Paket bisa dijemput langsung atau antar ke agen. Pilihan layanannya dari — instan, same-day, express, sampai kargo.</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-credit-card fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">COD Tanpa Marketplace</h5>

                    <p>Banyak pilihan ekspedisi buat kirim paket COD tanpa lewat marketplace. Dana COD langsung cair real-time, gampang banget!</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-undo-alt fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">Retur Rendah</h5>

                    <p>Dengan Return Management System yang sudah terintegrasi, jadi lebih mudah kelola retur dan ngurangin risiko paket gagal kirim.</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-headset fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">Support 24 Jam</h5>

                    <p>Tim kami selalu siap bantu kapan aja kalau ada kendala atau masalah dalam proses pengiriman, biar semuanya tetap lancar.</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-plug fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">Integrasi API & Plugin</h5>

                    <p>Jadi makin gampang ke toko online-mu lewat API, plugin WooCommerce, sampai Shopify — semua tinggal pakai!</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-chart-line fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">Dashboard Monitoring</h5>

                    <p>Mulai dari pengelolaan paket, pelacakan real-time, data pelanggan lengkap, sampai laporan keuangan yang detail — semua bisa dalam satu tempat.</p>

                </div>

            </div>

        </div>

    </div>

</section>

<section id="ppob" class="section bg-light">
    <div class="container">

        <h2 class="section-title">
    Join Mitra Agent Loket PPOB & Top Up Game
    <a href="https://tokosancaka.com/register" class="badge-gratis">
    GRATIS!
    </a>
        </h2>

        <p class="text-lg text-muted mb-5 text-center">Layanan pembayaran tagihan bulanan, pulsa, paket data, PDAM , hingga top up game terlengkap dan Harga Terbaik.</p>

        <div class="row g-4 justify-content-center">

            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/pln.png" alt="PLN" loading="lazy">
                </div>
            </div>
             <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/pln pascabayar.png" alt="PLN Pascabayar" loading="lazy">
                </div>
            </div>
             <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/pertamina gas.png" alt="Pertamina Gas" loading="lazy">
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/dana.png" alt="Dana" loading="lazy">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/ovo.png" alt="OVO" loading="lazy">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/go pay.png" alt="GoPay" loading="lazy">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/shopee pay.png" alt="Shopee Pay" loading="lazy">
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/telkomsel.png" alt="Telkomsel" loading="lazy">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/indosat.png" alt="Indosat" loading="lazy">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/xl.png" alt="XL" loading="lazy">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/axis.png" alt="Axis" loading="lazy">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/tri.png" alt="Tri" loading="lazy">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/smartfren.png" alt="Smartfren" loading="lazy">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/by.u.png" alt="By.U" loading="lazy">
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/bpjs.png" alt="BPJS Kesehatan" loading="lazy">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/k-vision dan gol.png" alt="K-Vision" loading="lazy">
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/mobile legends.png" alt="Mobile Legends" loading="lazy">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-logo-card">
                    <img src="https://tokosancaka.com/storage/logo-ppob/free fire.png" alt="Free Fire" loading="lazy">
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Jasa Layanan Kami Section -->

<section id="layanan" class="section">

    <div class="container">

        <h2 class="section-title">
    Didukung Jaringan Ekspedisi Lengkap <a href="https://tokosancaka.com/buat-pesanan" class="badge-kirim" >Kirim Sekarang !</a>
        </h2>

        <p class="text-lg text-gray-600 mb-5 text-center">Fleksibilitas, jangkauan terluas, dan harga terbaik untuk pengiriman Anda.</p>

        <div class="row g-4 justify-content-center">

            <!-- J&T Express -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20J%26T%20Express." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://upload.wikimedia.org/wikipedia/commons/0/01/J%26T_Express_logo.svg" alt="Logo J&amp;T Express" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- JNE -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20JNE." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://tokosancaka.com/public/storage/logo-ekspedisi/jne.png" alt="Logo JNE" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- POS Indonesia -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20POS%20Indonesia." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png" alt="Logo POS Indonesia" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- Indah Cargo -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20Indah%20Cargo." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png" alt="Logo Indah Cargo" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- SAP Express -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20SAP%20Express." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://tokosancaka.com/public/storage/logo-ekspedisi/sap.png" alt="Logo SAP Express" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- ID Express -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20ID%20Express." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://tokosancaka.com/public/storage/logo-ekspedisi/idx.png" alt="Logo ID Express" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- J&T Cargo -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20J%26T%20Cargo." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://tokosancaka.com/public/storage/logo-ekspedisi/jtcargo.png" alt="Logo J&amp;T Cargo" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- Lion Parcel -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20Lion%20Parcel." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://tokosancaka.com/public/storage/logo-ekspedisi/lion.png" alt="Logo Lion Parcel" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- SPX Express -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20SPX%20Express." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://tokosancaka.com/public/storage/logo-ekspedisi/sap.png" alt="Logo SPX Express" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- Sicepat -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20Sicepat." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://tokosancaka.com/public/storage/logo-ekspedisi/sicepat.png" alt="Logo Sicepat" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- NCS Kurir -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20NCS%20Kurir." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjxj3iyyZEjK2L4A4yCIr_E-4W3hF2lk_yb-t0Oj2oFPErCPCMHie5LHqps02xMb6sNa-Gqz5NSX_P_hzWlYpUpJUlCD4iN6_QxiSG9fzY4bsZ9XvLFDn7HCiORtNvIlPfuQbSSdW96p7x7uN8ek3FWyHW9c2bznrFBQkoLd5A9sVAFVKWLfUhT3Dxh/s320/GKL41_NCS%20Kurir%20-%20Koleksilogo.com.jpg" alt="Logo NCS Kurir" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- Ninja Express -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20Ninja%20Express." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://tokosancaka.com/public/storage/logo-ekspedisi/ninja.png" alt="Logo Ninja Express" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- Anteraja -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20Anteraja." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://tokosancaka.com/public/storage/logo-ekspedisi/anteraja.png" alt="Logo Anteraja" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

             <!-- TIKI -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20TIKI." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://tokosancaka.com/public/storage/logo-ekspedisi/tiki.png" alt="Logo TIKI" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

             <!-- Sentral Cargo -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20Sentral%20Cargo." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://tokosancaka.com/public/storage/logo-ekspedisi/centralcargo.png" alt="Logo Sentral Cargo" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>



            <!-- Borzo -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20Borzo." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://tokosancaka.com/public/storage/logo-ekspedisi/borzo.png" alt="Logo Borzo" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>



            <!-- GoSend -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20GoSend." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png" alt="Logo GoSend" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>



            <!-- GrabExpress -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20GrabExpress." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://tokosancaka.com/public/storage/logo-ekspedisi/grab.png" alt="Logo GrabExpress" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>



        </div>

    </div>

</section>



<!-- Testimoni Section -->

<section id="testimoni" class="section bg-light">

    <div class="container">

        <h2 class="section-title">Apa Kata Mereka Tentang Kami?</h2>

        <p class="text-center text-muted mb-5">Kepuasan Anda adalah prioritas utama kami. Lihat pengalaman nyata dari pelanggan setia kami.</p>

        <div class="row g-4">

            <div class="col-md-4">

                <div class="testimonial-card">

                    <p>"Pelayanannya cepat banget! Paket saya sampai keesokan harinya dalam kondisi baik. Stafnya ramah dan sangat membantu. Recommended!"</p>

                    <div class="mt-auto">

                        <h5 class="mt-3 mb-0">Andi Pratama</h5>

                        <small class="text-muted">Pengusaha Online</small>

                    </div>

                </div>

            </div>

            <div class="col-md-4">

                <div class="testimonial-card">

                    <p>"Pakai layanan cargo untuk kirim stok barang ke luar pulau, biayanya sangat terjangkau dan timnya sangat profesional. Semua barang aman tanpa lecet. Mantap!"</p>

                    <div class="mt-auto">

                        <h5 class="mt-3 mb-0">Citra Lestari</h5>

                        <small class="text-muted">Pemilik Toko</small>

                    </div>

                </div>

            </div>

            <div class="col-md-4">

                <div class="testimonial-card">

                    <p>"Fitur lacak resinya akurat dan mudah digunakan. Saya jadi tenang tahu posisi paket saya di mana setiap saat. Tidak perlu was-was lagi."</p>

                    <div class="mt-auto">

                        <h5 class="mt-3 mb-0">Budi Santoso</h5>

                        <small class="text-muted">Karyawan Swasta</small>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- BAGIAN BARU: LEGALITAS & KEPERCAYAAN -->

<section id="legalitas" class="section">

    <div class="container">

        <h2 class="section-title">Legalitas & Kepercayaan</h2>

        <p class="text-center text-muted mb-5">Kami adalah mitra terpercaya yang telah <strong>terdaftar</strong> dan diakui secara resmi.</p>

        <div class="row g-4 justify-content-center">

            <!-- Gambar 1 -->

            <div class="col-6 col-md-4 col-lg-3">

                <div class="trust-logo-card">

                    <img src="https://tokosancaka.com/storage/uploads/PSE-HITAM-PUTIH.jpg" class="img-fluid" alt="Sertifikat PSE Kominfo">

                </div>

            </div>

        </div>

    </div>

</section>



<!-- KODE UNTUK BAGIAN TANYA JAWAB (Q&A) -->

<section id="qa" class="section bg-light">

    <div class="container">

        <h2 class="section-title">Tanya Jawab Customer (Q&A)</h2>

        <div class="accordion" id="accordionQA">

            <!-- Pertanyaan 1 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingOne">

                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">

                        Bagaimana cara melacak status pengiriman paket saya?

                    </button>

                </h2>

                <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Anda dapat dengan mudah melacak paket Anda dengan memasukkan nomor resi pada kolom "Lacak Kiriman Anda" di bagian atas halaman utama kami. Status pengiriman akan ditampilkan secara real-time.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 2 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingTwo">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">

                        Apa saja area jangkauan pengiriman Sancaka Express?

                    </button>

                </h2>

                <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Kami melayani pengiriman ke seluruh wilayah di Indonesia, dari kota besar hingga ke daerah-daerah terpencil. Jaringan kami yang luas memastikan paket Anda sampai ke tujuan manapun.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 3 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingThree">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">

                        Apa perbedaan antara layanan Reguler dan Cargo?

                    </button>

                </h2>

                <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Layanan <strong>Reguler</strong> ditujukan untuk pengiriman paket dan dokumen dengan berat standar. Sedangkan layanan <strong>Cargo</strong> adalah solusi untuk pengiriman barang dalam jumlah besar, berat, atau berukuran besar dengan biaya yang lebih efisien.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 4 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingFour">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">

                        Apakah ada asuransi untuk barang kiriman?

                    </button>

                </h2>

                <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Ya, kami menyediakan opsi asuransi untuk memberikan perlindungan ekstra terhadap risiko kerusakan atau kehilangan barang berharga Anda selama proses pengiriman. Anda dapat memilih opsi ini saat melakukan input pengiriman.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 5 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingFive">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive">

                        Bagaimana cara menghitung ongkos kirim?

                    </button>

                </h2>

                <div id="collapseFive" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Ongkos kirim dihitung berdasarkan berat aktual paket atau berat volume (dimensi PxLxT), mana yang lebih besar. Anda dapat menggunakan fitur "Cek Estimasi Ongkir" di halaman utama untuk mendapatkan perkiraan biaya.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 6 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingSix">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix">

                        Apa yang harus saya lakukan jika paket saya rusak atau hilang?

                    </button>

                </h2>

                <div id="collapseSix" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Segera hubungi layanan pelanggan kami melalui WhatsApp atau telepon dengan menyertakan nomor resi dan bukti dokumentasi (foto/video). Tim kami akan membantu proses klaim Anda, terutama jika paket tersebut diasuransikan.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 7 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingSeven">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven">

                        Barang apa saja yang tidak boleh dikirim?

                    </button>

                </h2>

                <div id="collapseSeven" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Barang-barang yang dilarang termasuk (namun tidak terbatas pada): bahan mudah terbakar/meledak, narkotika, senjata tajam, hewan hidup, dan barang ilegal lainnya sesuai dengan peraturan perundang-undangan yang berlaku di Indonesia.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 8 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingEight">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight">

                        Bisakah saya mengubah alamat penerima jika paket sudah dikirim?

                    </button>

                </h2>

                <div id="collapseEight" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Perubahan alamat mungkin dapat dilakukan jika paket belum sampai di kota tujuan akhir. Namun, ini akan bergantung pada status dan lokasi paket saat itu serta mungkin dikenakan biaya tambahan. Silakan hubungi layanan pelanggan kami secepatnya untuk bantuan.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 9 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingNine">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNine">

                        Jam berapa saja operasional kantor Sancaka Express?

                    </button>

                </h2>

                <div id="collapseNine" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Kantor dan agen kami umumnya beroperasi dari hari Senin hingga Sabtu, pukul 07:00 - 17:00 WIB. Beberapa agen mungkin memiliki jam operasional yang berbeda. Untuk layanan pelanggan via WhatsApp, kami siap melayani Anda setiap hari.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 10 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingTen">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTen">

                        Bagaimana cara menjadi agen Sancaka Express?

                    </button>

                </h2>

                <div id="collapseTen" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Kami sangat senang Anda tertarik untuk bergabung. Untuk informasi lebih lanjut mengenai syarat dan keuntungan menjadi agen, silakan hubungi tim kemitraan kami melalui halaman "Kontak" atau langsung via WhatsApp.

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>



{{--
    =======================================================
    LOGIKA PENGAMBILAN DATA (LANGSUNG DI BLADE)
    =======================================================
--}}
@php
    $latestPosts = collect();
    try {
        if (class_exists(\App\Models\Post::class)) {
            $latestPosts = \App\Models\Post::with(['category', 'author'])
                ->where('status', 'published')
                ->latest()
                ->paginate(8); // Pagination otomatis aktif
        }
    } catch (\Exception $e) {}
@endphp

{{--
    =======================================================
    TAMPILAN GRID BERITA (DENGAN ID UNTUK AJAX)
    =======================================================
--}}
<section class="py-5 bg-white" id="blog-section">
    <div class="container">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold m-0" style="color: #1a253c;">Berita & Informasi</h4>
                <p class="text-muted small m-0">
                    @if($latestPosts instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        Halaman {{ $latestPosts->currentPage() }} dari {{ $latestPosts->lastPage() }}
                    @else
                        Update terkini seputar layanan & bisnis
                    @endif
                </p>
            </div>
            <a href="{{ url('/blog') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div id="blog-loader" class="text-center py-5 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mt-2 small">Memuat berita...</p>
        </div>

        <div id="blog-content" class="row g-3">
            @forelse($latestPosts as $post)
            <div class="col-6 col-md-4 col-lg-3 fade-in-up">
                <a href="{{ url('/blog/posts/' . $post->slug) }}" class="text-decoration-none text-dark">
                    <div class="card h-100 border-0 shadow-sm overflow-hidden blog-card">

                        <div class="position-relative" style="background: #f8f9fa;">
                            <img src="{{ asset('storage/' . $post->featured_image) }}"
                                class="w-100"
                                alt="{{ $post->title }}"
                                onerror="this.src='https://placehold.co/300x200/eee/999?text=Sancaka';">
                        </div>

                        <div class="card-body p-2 d-flex flex-column">

                        {{-- 1. BADGE KATEGORI (Dipindah ke sini & Hapus position-absolute) --}}
                        <div class="mb-2">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25"
                                style="font-size: 10px; font-weight: 700;">
                                {{ $post->category->name ?? 'Info' }}
                            </span>
                        </div>

                        {{-- 2. JUDUL BERITA --}}
                        <h6 class="card-title fw-bold mb-2 text-truncate-2" style="font-size: 13px; line-height: 1.4; min-height: 36px;">
                            {{ $post->title }}
                        </h6>

                        {{-- 3. META INFO (Penulis & Waktu) --}}
                        <div class="mt-auto d-flex align-items-center justify-content-between text-muted" style="font-size: 10px;">

                            {{-- Penulis --}}
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-circle me-1 text-primary"></i>
                                <span class="text-truncate" style="max-width: 60px;">
                                    {{ $post->author->name ?? $post->user->name ?? 'Admin' }}
                                </span>
                            </div>

                            {{-- Waktu --}}
                            <div class="d-flex align-items-center">
                                <i class="far fa-clock me-1"></i>
                                <span>{{ $post->created_at->diffForHumans(null, true) }}</span>
                            </div>

                        </div>
                    </div>

                    </div>
                </a>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <div class="bg-light rounded p-4 d-inline-block">
                    <i class="far fa-newspaper text-muted mb-2" style="font-size: 30px;"></i>
                    <p class="text-muted m-0 small">Belum ada berita terbaru.</p>
                </div>
            </div>
            @endforelse
        </div>

        @if($latestPosts instanceof \Illuminate\Pagination\LengthAwarePaginator && $latestPosts->hasPages())
        <div class="d-flex justify-content-center mt-5 custom-pagination" id="pagination-wrapper">
            {{ $latestPosts->appends(request()->query())->links('pagination::bootstrap-5') }}
        </div>
        @endif

    </div>
</section>

{{--
    =======================================================
    STYLE CSS (Design Biru & Animasi)
    =======================================================
--}}
@push('styles')
<style>
    /* Animasi masuk halus */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .fade-in-up {
        animation: fadeInUp 0.5s ease-out forwards;
    }

    /* Card Style */
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .blog-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border-radius: 10px;
    }
    .blog-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(13, 110, 253, 0.15) !important; /* Bayangan Biru Tipis */
    }

    /* --- DESAIN PAGINATION BIRU KEREN --- */
    .custom-pagination .pagination {
        gap: 5px; /* Jarak antar tombol */
    }
    .custom-pagination .page-item .page-link {
        color: #0d6efd; /* Biru Utama */
        background-color: #fff;
        border: 2px solid #e9ecef;
        border-radius: 8px; /* Kotak rounded */
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        transition: all 0.2s;
        font-size: 14px;
    }

    /* Hover Effect */
    .custom-pagination .page-item .page-link:hover {
        background-color: #e7f1ff;
        border-color: #0d6efd;
        transform: translateY(-2px);
    }

    /* Active State (Tombol yang sedang aktif) */
    .custom-pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #0d6efd, #0a58ca);
        border-color: #0d6efd;
        color: white;
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
    }

    /* Disabled State */
    .custom-pagination .page-item.disabled .page-link {
        color: #adb5bd;
        background-color: #f8f9fa;
        border-color: #e9ecef;
    }
</style>
@endpush



<!-- Kontak & Peta Section -->

<section id="kontak" class="section">

    <div class="container">

        <h2 class="section-title">Siap Mengirim? Hubungi Kami!</h2>

        <p class="text-center text-muted mb-5">Punya pertanyaan atau butuh konsultasi? Tim kami siap membantu Anda.</p>

        <div class="row g-5">

            <div class="col-lg-6">

                <h4>Kirim Pesan</h4>

                <form>

                    <div class="mb-3"><label for="contactName" class="form-label">Nama Anda</label><input type="text" class="form-control" id="contactName" required></div>

                    <div class="mb-3"><label for="contactEmail" class="form-label">Email</label><input type="email" class="form-control" id="contactEmail" required></div>

                    <div class="mb-3"><label for="contactMessage" class="form-label">Pesan</label><textarea class="form-control" id="contactMessage" rows="5" required></textarea></div>

                    <button type="submit" class="btn btn-danger">Kirim Pesan</button>

                </form>

            </div>

            <div class="col-lg-6">

                <h4>Lokasi Kami</h4>

                <div class="ratio ratio-16x9 rounded" style="overflow: hidden;">

                   <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3955.975440748102!2d111.4429948748921!3d-7.468200192510255!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e79e00023223a29%3A0xb353590595368a4!2sJl.%20Dokter%20Wahidin%20No.18a%2C%20Sidomakmur%2C%20Ketanggi%2C%20Kec.%20Ngawi%2C%20Kabupaten%20Ngawi%2C%20Jawa%20Timur%2063211!5e0!3m2!1sen!2sid!4v1720345535355!5m2!1sen!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>

                </div>

            </div>

        </div>

    </div>

</section>


<div class="modal fade" id="cekOngkirModal" tabindex="-1" aria-labelledby="cekOngkirModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="cekOngkirModalLabel">
                    <i class="fa-solid fa-calculator me-2"></i> Cek Estimasi Ongkos Kirim
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 p-lg-5">

                <form id="shipping-form">
                    @csrf
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="origin" class="form-label">Alamat Asal</label>
                            <div class="position-relative input-group-elegant">
                                <i class="fa-solid fa-map-marker-alt input-icon"></i>
                                <input type="text" class="form-control" id="origin" name="origin_text" placeholder="Ketik alamat asal..." autocomplete="off">
                                <div id="origin-results" class="list-group position-absolute w-100 top-100 start-0 d-none shadow-lg" style="max-height: 200px; overflow-y: auto;"></div>
                            </div>
                            <input type="hidden" id="origin_id" name="origin_id">
                            <input type="hidden" id="origin_subdistrict_id" name="origin_subdistrict_id">
                        </div>

                        <div class="col-md-6">
                            <label for="destination" class="form-label">Alamat Tujuan</label>
                            <div class="position-relative input-group-elegant">
                                <i class="fa-solid fa-map-pin input-icon"></i>
                                <input type="text" class="form-control" id="destination" name="destination_text" placeholder="Ketik alamat tujuan..." autocomplete="off">
                                <div id="destination-results" class="list-group position-absolute w-100 top-100 start-0 d-none shadow-lg" style="max-height: 200px; overflow-y: auto;"></div>
                            </div>
                            <input type="hidden" id="destination_id" name="destination_id">
                            <input type="hidden" id="destination_subdistrict_id" name="destination_subdistrict_id">
                        </div>

                        <div class="col-12"><hr class="my-2"></div>

                        <div class="col-md-6">
                            <label for="weight" class="form-label">Berat (gram)*</label>
                            <div class="input-group-elegant">
                                <i class="fa-solid fa-weight-hanging input-icon"></i>
                                <input type="number" class="form-control" id="weight" name="weight" value="1000" min="1" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="item_value" class="form-label">Nilai Barang (Rp)</label>
                            <div class="input-group-elegant">
                                <i class="fa-solid fa-dollar-sign input-icon"></i>
                                <input type="number" class="form-control" id="item_value" name="item_value" placeholder="0" min="0">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Dimensi (cm)</label>
                            <div class="row g-3">
                                <div class="col-4">
                                    <input type="number" class="form-control" id="length" name="length" placeholder="Panjang" min="1">
                                </div>
                                <div class="col-4">
                                    <input type="number" class="form-control" id="width" name="width" placeholder="Lebar" min="1">
                                </div>
                                <div class="col-4">
                                    <input type="number" class="form-control" id="height" name="height" placeholder="Tinggi" min="1">
                                </div>
                            </div>
                        </div>

                        <div class="col-12 d-flex align-items-end">
                            <div class="form-check form-switch fs-6 mt-2">
                                <input class="form-check-input" type="checkbox" id="insurance" name="insurance">
                                <label class="form-check-label" for="insurance">
                                    Gunakan Asuransi
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg" id="submit-button">
                            <span id="btn-text">Cek Ongkos Kirim</span>
                            <span id="btn-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </form>

                <div id="cost-results-container" class="mt-4"></div>
            </div>
        </div>
    </div>
</div>


<!-- Floating Action Buttons -->

<a href="https://wa.me/6285745808809" class="floating-btn wa-float" target="_blank" title="Hubungi via WhatsApp"><i class="fa fa-whatsapp"></i></a>

<button id="scrollTopBtn" class="floating-btn" title="Kembali ke atas">

 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 24px; height: 24px;">

 <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />

</svg>

</button>



    <!-- Tombol Floating -->



    <a href="https://wa.me/6285745808809" class="floating-btn wa-float" target="_blank" title="Hubungi via WhatsApp">



        <i class="fab fa-whatsapp"></i>



    </a>





@endsection



@push('scripts')



<script>

document.addEventListener('DOMContentLoaded', function() {



    // Logika Tombol Scroll to Top (tetap sama)

    const scrollTopBtn = document.getElementById('scrollTopBtn');

    window.onscroll = () => {

        if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {

            scrollTopBtn.style.display = "block";

        } else {

            scrollTopBtn.style.display = "none";

        }

    };

    scrollTopBtn.addEventListener('click', () => {

        window.scrollTo({ top: 0, behavior: 'smooth' });

    });



    // ==================================================

    // == LOGIKA BARU UNTUK PENCARIAN ALAMAT (JS STANDAR) ==

    // ==================================================

    const selectProvinsi = document.getElementById('select-provinsi');

    const selectKabupaten = document.getElementById('select-kabupaten');

    const selectKecamatan = document.getElementById('select-kecamatan');

    const wilayahResultsContainer = document.getElementById('wilayah-results-container');



    const kodeposSearchInput = document.getElementById('kodepos-search-input');

    const kodeposSearchBtn = document.getElementById('kodepos-search-btn');

    const kodeposSearchBtnText = document.getElementById('kodepos-search-btn-text');

    const kodeposResultsContainer = document.getElementById('kodepos-results-container');



    const apiUrls = {

        provinces: "{{ route('api.wilayah.provinces') }}",

        kabupaten: "{{ route('api.wilayah.kabupaten', ':id') }}",

        kecamatan: "{{ route('api.wilayah.kecamatan', ':id') }}",

        desa: "{{ route('api.kodepos.by-district', ':id') }}",

        search: "{{ route('api.kodepos.public.search') }}"

    };



    // Fungsi untuk mengisi dropdown (select)

    const populateSelect = (selectElement, data, placeholder) => {

        selectElement.innerHTML = `<option value="">-- ${placeholder} --</option>`;

        data.forEach(item => {

            const option = document.createElement('option');

            option.value = item.id;

            option.textContent = item.name;

            selectElement.appendChild(option);

        });

    };



    // Fungsi untuk menampilkan hasil di tabel

    const renderResults = (container, results, pagination) => {

        if (results.length === 0) {

            container.innerHTML = `<div class="alert alert-warning mt-3">Tidak ada data ditemukan.</div>`;

            return;

        }



        let tableHtml = `

            <div class="results-table table-responsive">

                <table class="table table-hover table-striped mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>Kelurahan/Desa</th>

                            <th>Kecamatan</th>

                            <th>Kabupaten/Kota</th>

                            <th>Provinsi</th>

                            <th class="text-end">Kode Pos</th>

                        </tr>

                    </thead>

                    <tbody>

        `;

        results.forEach(item => {

            tableHtml += `

                <tr>

                    <td>${item.kelurahan_desa || '-'}</td>

                    <td>${item.kecamatan || '-'}</td>

                    <td>${item.kota_kabupaten || '-'}</td>

                    <td>${item.provinsi || '-'}</td>

                    <td class="fw-bold text-end">${item.kode_pos || '-'}</td>

                </tr>

            `;

        });

        tableHtml += `</tbody></table></div>`;



        // Tambah Paginasi

        if (pagination && pagination.total > 0) {

            tableHtml += `

                <div class="d-flex justify-content-between align-items-center p-3 mt-1 border-top bg-light">

                    <p class="text-muted small mb-0">

                        Menampilkan ${pagination.from} - ${pagination.to} dari ${pagination.total} hasil

                    </p>

                    <div>

                        <button class="btn btn-sm btn-outline-secondary btn-prev" ${pagination.current_page <= 1 ? 'disabled' : ''} data-page="${pagination.current_page - 1}">Sebelumnya</button>

                        <button class="btn btn-sm btn-outline-secondary ms-2 btn-next" ${pagination.current_page >= pagination.last_page ? 'disabled' : ''} data-page="${pagination.current_page + 1}">Berikutnya</button>

                    </div>

                </div>

            `;

        }

        container.innerHTML = tableHtml;

    };



    // Memuat Provinsi saat halaman dimuat

    const loadProvinces = async () => {

        try {

            const response = await fetch(apiUrls.provinces);

            const data = await response.json();

            populateSelect(selectProvinsi, data, 'Pilih Provinsi');

        } catch (error) {

            console.error('Gagal memuat provinsi:', error);

        }

    };



    // Event Listeners untuk dropdown

    selectProvinsi.addEventListener('change', async () => {

        const provinceId = selectProvinsi.value;

        selectKabupaten.innerHTML = '';

        selectKecamatan.innerHTML = '';

        selectKabupaten.disabled = true;

        selectKecamatan.disabled = true;

        wilayahResultsContainer.innerHTML = '';



        if (provinceId) {

            selectKabupaten.disabled = false;

            const url = apiUrls.kabupaten.replace(':id', provinceId);

            const response = await fetch(url);

            const data = await response.json();

            populateSelect(selectKabupaten, data, 'Pilih Kabupaten/Kota');

        }

    });



    selectKabupaten.addEventListener('change', async () => {

        const regencyId = selectKabupaten.value;

        selectKecamatan.innerHTML = '';

        selectKecamatan.disabled = true;

        wilayahResultsContainer.innerHTML = '';



        if (regencyId) {

            selectKecamatan.disabled = false;

            const url = apiUrls.kecamatan.replace(':id', regencyId);

            const response = await fetch(url);

            const data = await response.json();

            populateSelect(selectKecamatan, data, 'Pilih Kecamatan');

        }

    });



    const fetchAndRenderDesa = async (page = 1) => {

        const districtId = selectKecamatan.value;

        if (!districtId) return;



        wilayahResultsContainer.innerHTML = `<div class="text-center p-4">Memuat data... <div class="spinner-border spinner-border-sm ms-2"></div></div>`;

        const url = apiUrls.desa.replace(':id', districtId) + `?page=${page}`;

        const response = await fetch(url);

        const result = await response.json();

        renderResults(wilayahResultsContainer, result.data, result);

    };



    selectKecamatan.addEventListener('change', () => fetchAndRenderDesa(1));

    wilayahResultsContainer.addEventListener('click', (e) => {

        if (e.target.matches('.btn-prev, .btn-next')) {

            const page = e.target.dataset.page;

            fetchAndRenderDesa(page);

        }

    });





    // Logika untuk Pencarian Kata Kunci

    const fetchAndRenderSearch = async (page = 1) => {

        const query = kodeposSearchInput.value;

        if (query.length < 3) {

            kodeposResultsContainer.innerHTML = `<div class="alert alert-info mt-3">Masukkan minimal 3 karakter untuk memulai pencarian.</div>`;

            return;

        }



        kodeposSearchBtn.disabled = true;

        kodeposSearchBtnText.textContent = 'Mencari...';

        kodeposResultsContainer.innerHTML = `<div class="text-center p-4">Mencari... <div class="spinner-border spinner-border-sm ms-2"></div></div>`;



        const url = `${apiUrls.search}?search=${query}&page=${page}`;

        const response = await fetch(url);

        const result = await response.json();

        renderResults(kodeposResultsContainer, result.data, result);



        kodeposSearchBtn.disabled = false;

        kodeposSearchBtnText.textContent = 'Cari';

    };



    kodeposSearchBtn.addEventListener('click', () => fetchAndRenderSearch(1));

    kodeposSearchInput.addEventListener('keydown', (e) => {

        if (e.key === 'Enter') {

            fetchAndRenderSearch(1);

        }

    });

    kodeposResultsContainer.addEventListener('click', (e) => {

        if (e.target.matches('.btn-prev, .btn-next')) {

            const page = e.target.dataset.page;

            fetchAndRenderSearch(page);

        }

    });





    // Inisialisasi

    loadProvinces();

});

</script>


<script>
document.addEventListener('DOMContentLoaded', function () {

    /* =======================
       SCROLL TO TOP BUTTON
    ======================= */
    const scrollTopBtn = document.getElementById('scrollTopBtn');

    window.onscroll = () => {
        if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
            scrollTopBtn.style.display = "block";
        } else {
            scrollTopBtn.style.display = "none";
        }
    };

    scrollTopBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    /* =======================
       CEK ALAMAT (PROV/KAB/KEC)
    ======================= */
    const selectProvinsi = document.getElementById('select-provinsi');
    const selectKabupaten = document.getElementById('select-kabupaten');
    const selectKecamatan = document.getElementById('select-kecamatan');
    const wilayahResultsContainer = document.getElementById('wilayah-results-container');
    const kodeposSearchInput = document.getElementById('kodepos-search-input');
    const kodeposSearchBtn = document.getElementById('kodepos-search-btn');
    const kodeposSearchBtnText = document.getElementById('kodepos-search-btn-text');
    const kodeposResultsContainer = document.getElementById('kodepos-results-container');

    const apiUrls = {
        provinces: "{{ route('api.wilayah.provinces') }}",
        kabupaten: "{{ route('api.wilayah.kabupaten', ':id') }}",
        kecamatan: "{{ route('api.wilayah.kecamatan', ':id') }}",
        desa: "{{ route('api.kodepos.by-district', ':id') }}",
        search: "{{ route('api.kodepos.public.search') }}"
    };

    const populateSelect = (selectElement, data, placeholder) => {
        selectElement.innerHTML = `<option value="">-- ${placeholder} --</option>`;
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            selectElement.appendChild(option);
        });
    };

    if (selectProvinsi) {
        const loadProvinces = async () => {
            try {
                const response = await fetch(apiUrls.provinces);
                const data = await response.json();
                populateSelect(selectProvinsi, data, 'Pilih Provinsi');
            } catch (error) {
                console.error('Gagal memuat provinsi:', error);
            }
        };
        loadProvinces();
    }

});
</script>

{{-- ======================
     SCRIPT MODAL CEK ONGKIR
====================== --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* =======================
          AUTOCOMPLETE
    ======================= */
    const debounce = (func, delay) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    };

    const setupAutocomplete = (inputId, resultsId, hiddenId, hiddenSubId) => {
        const input = document.getElementById(inputId);
        const resultsContainer = document.getElementById(resultsId);
        const hiddenInput = document.getElementById(hiddenId);
        const hiddenSubInput = document.getElementById(hiddenSubId);

        const handleSearch = async (event) => {
            const query = event.target.value;
            const searchRoute = "{{ route('api.ongkir.address.search') }}";

            if (query.length < 3) {
                resultsContainer.classList.add('d-none');
                return;
            }

            try {
                const response = await fetch(`${searchRoute}?search=${query}`);
                const result = await response.json();

                resultsContainer.innerHTML = "";

                if (Array.isArray(result) && result.length > 0) {
                    resultsContainer.classList.remove('d-none');
                    result.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'list-group-item list-group-item-action';
                        div.style.cursor = 'pointer';
                        div.textContent = item.full_address;
                        div.dataset.id = item.district_id;
                        div.dataset.subId = item.subdistrict_id;

                        div.addEventListener('click', () => {
                            input.value = item.full_address;
                            hiddenInput.value = item.district_id;
                            hiddenSubInput.value = item.subdistrict_id;
                            resultsContainer.classList.add('d-none');
                        });

                        resultsContainer.appendChild(div);
                    });
                } else {
                    resultsContainer.classList.remove('d-none');
                    resultsContainer.innerHTML = `<div class="list-group-item text-muted">Alamat tidak ditemukan.</div>`;
                }

            } catch (error) {
                resultsContainer.innerHTML = `<div class="list-group-item text-danger">${error.message}</div>`;
                resultsContainer.classList.remove('d-none');
            }
        };

        input.addEventListener('input', debounce(handleSearch, 350));

        document.addEventListener('click', (event) => {
            if (!resultsContainer.contains(event.target) && !input.contains(event.target)) {
                resultsContainer.classList.add('d-none');
            }
        });
    };

    setupAutocomplete('origin', 'origin-results', 'origin_id', 'origin_subdistrict_id');
    setupAutocomplete('destination', 'destination-results', 'destination_id', 'destination_subdistrict_id');

    /* =======================
        FORM CEK ONGKIR
    ======================= */
    const shippingForm = document.getElementById('shipping-form');
    const costResultsContainer = document.getElementById('cost-results-container');
    const submitButton = document.getElementById('submit-button');
    const btnText = document.getElementById('btn-text');
    const btnSpinner = document.getElementById('btn-spinner');

    shippingForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        submitButton.disabled = true;
        btnText.textContent = "Mencari...";
        btnSpinner.classList.remove('d-none');

        costResultsContainer.innerHTML = `<div class="text-center p-4"><p class="h5 text-muted">Mencari layanan terbaik, Mohon Ditunggu...</p></div>`;

        const formData = new FormData(this);
        const payload = {};

        for (const [key, value] of formData.entries()) {
            if (['length', 'width', 'height'].includes(key) && !value) {
                payload[key] = "1";
            } else if (key === 'item_value' && !value) {
                payload[key] = "0";
            } else {
                payload[key] = value;
            }
        }

        payload['origin_text'] = document.getElementById('origin').value;
        payload['destination_text'] = document.getElementById('destination').value;

        if (document.getElementById('insurance').checked) {
            payload.insurance = 'on';
        }

        try {
            const costCheckRoute = "{{ route('api.ongkir.cost.check') }}";
            const response = await fetch(costCheckRoute, {
                method: 'POST',
                body: JSON.stringify(payload),
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                displayResults(result);
            } else {
                throw new Error(result.message || 'Terjadi kesalahan.');
            }

        } catch (error) {
            costResultsContainer.innerHTML =
                `<div class="alert alert-danger"><strong>Error:</strong> ${error.message}</div>`;
        } finally {
            submitButton.disabled = false;
            btnText.textContent = "Cek Ongkos Kirim";
            btnSpinner.classList.add('d-none');
        }
    });

    /* =======================
      DISPLAY RESULTS
    ======================= */
    const formatRupiah = number => new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(number);

    const getPrice = service => {
        if (!service) return 0;
        if (service.price?.total_price) return parseFloat(service.price.total_price);
        return parseFloat(service.cost ?? service.rate ?? 0);
    };

    const createServiceCard = service => {
        const price = service.numeric_price;
        const etd = service.etd ? `<small class="text-muted">Estimasi: ${service.etd}</small>` : '';
        const serviceName = service.service_name || service.service_type || "Layanan";

        let logoUrl = '';
        const serviceKey = (service.service || '').toLowerCase();

        if (serviceKey.includes('gosend')) {
            logoUrl = 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png';
        } else if (serviceKey.includes('grab')) {
            logoUrl = 'https://tokosancaka.com/public/storage/logo-ekspedisi/grab.png';
        } else if (service.service) {
            logoUrl = `https://tokosancaka.com/public/storage/logo-ekspedisi/${service.service.toLowerCase()}.png`;
        }

        const imgHtml = logoUrl
            ? `<img src="${logoUrl}" alt="${serviceName}" class="me-3 courier-logo" onerror="this.style.display='none';">`
            : '';

        return `
            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                <div class="d-flex align-items-center">
                    ${imgHtml}
                    <div>
                        <h6 class="fw-bold mb-0">${serviceName}</h6>
                        ${etd}
                    </div>
                </div>
                <div class="text-end">
                    <h5 class="fw-bold text-success mb-1">${formatRupiah(price)}</h5>
                    <button class="btn btn-pilih mt-1" onclick="window.location.href='https://tokosancaka.com/buat-pesanan'">Kirim</button>
                </div>
            </div>
        `;
    };

    const attachTabListeners = () => {
        const tabButtons = costResultsContainer.querySelectorAll('.shipping-tab-btn');
        tabButtons.forEach(button => {
            button.addEventListener('click', e => {
                e.preventDefault();
                const tab = new bootstrap.Tab(e.target);
                tab.show();
            });
        });
    };

    function displayResults(result) {
        const { final_weight, data, warning } = result;

        costResultsContainer.innerHTML = warning
            ? `<div class="alert alert-warning">${warning}</div>`
            : '';

        let instantServices = [];
        let expressServices = [];
        let cargoServices = [];

        if (data.instant?.length) {
            instantServices = data.instant
                .map(s => {
                    s.numeric_price = getPrice(s);
                    return s;
                })
                .filter(Boolean);
        }

        if (data.express_cargo?.length) {
            data.express_cargo.forEach(service => {
                service.numeric_price = getPrice(service);
                const type = (service.service_type || '').toLowerCase();
                const name = (service.service_name || '').toLowerCase();

                if (type.includes('cargo') || name.includes('cargo')) {
                    cargoServices.push(service);
                } else {
                    expressServices.push(service);
                }
            });
        }

        instantServices.sort((a, b) => a.numeric_price - b.numeric_price);
        expressServices.sort((a, b) => a.numeric_price - b.numeric_price);
        cargoServices.sort((a, b) => a.numeric_price - b.numeric_price);

        if (
            instantServices.length === 0 &&
            expressServices.length === 0 &&
            cargoServices.length === 0
        ) {
            costResultsContainer.innerHTML +=
                `<div class="alert alert-warning">Tidak ada layanan pengiriman untuk rute ini.</div>`;
            return;
        }

        let firstActiveTab =
            instantServices.length ? 'instant' :
            expressServices.length ? 'express' :
            'cargo';

        let html = `
            <div class="alert alert-info">
                <strong>Berat Dihitung:</strong> ${final_weight.toLocaleString('id-ID')} gram
            </div>

            <ul class="nav nav-tabs" id="shipping-tabs" role="tablist">
        `;

        if (instantServices.length) {
            html += `
                <li class="nav-item">
                    <button class="nav-link shipping-tab-btn ${firstActiveTab === 'instant' ? 'active' : ''}"
                        data-bs-toggle="tab" data-bs-target="#tab-instant">
                        <i class="fa-solid fa-bolt"></i> Instant (${instantServices.length})
                    </button>
                </li>
            `;
        }

        if (expressServices.length) {
            html += `
                <li class="nav-item">
                    <button class="nav-link shipping-tab-btn ${firstActiveTab === 'express' ? 'active' : ''}"
                        data-bs-toggle="tab" data-bs-target="#tab-express">
                        <i class="fa-solid fa-box"></i> Express (${expressServices.length})
                    </button>
                </li>
            `;
        }

        if (cargoServices.length) {
            html += `
                <li class="nav-item">
                    <button class="nav-link shipping-tab-btn ${firstActiveTab === 'cargo' ? 'active' : ''}"
                        data-bs-toggle="tab" data-bs-target="#tab-cargo">
                        <i class="fa-solid fa-truck"></i> Cargo (${cargoServices.length})
                    </button>
                </li>
            `;
        }

        html += `</ul>
            <div class="tab-content border border-top-0 rounded-bottom">
        `;

        if (instantServices.length) {
            html += `
                <div class="tab-pane fade ${firstActiveTab === 'instant' ? 'show active' : ''}" id="tab-instant">
                    <div class="list-group list-group-flush">
                        ${instantServices.map(createServiceCard).join('')}
                    </div>
                </div>
            `;
        }

        if (expressServices.length) {
            html += `
                <div class="tab-pane fade ${firstActiveTab === 'express' ? 'show active' : ''}" id="tab-express">
                    <div class="list-group list-group-flush">
                        ${expressServices.map(createServiceCard).join('')}
                    </div>
                </div>
            `;
        }

        if (cargoServices.length) {
            html += `
                <div class="tab-pane fade ${firstActiveTab === 'cargo' ? 'show active' : ''}" id="tab-cargo">
                    <div class="list-group list-group-flush">
                        ${cargoServices.map(createServiceCard).join('')}
                    </div>
                </div>
            `;
        }

        html += `</div>`;

        costResultsContainer.innerHTML += html;

        attachTabListeners();
    }

});
</script>




    <script>

        // Ambil tombol

        var mybutton = document.getElementById("scrollTopBtn");



        // Ketika pengguna scroll ke bawah 20px dari atas dokumen, tampilkan tombol

        window.onscroll = function() {

            scrollFunction()

        };



        function scrollFunction() {

            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {

                mybutton.style.display = "block";

            } else {

                mybutton.style.display = "none";

            }

        }



        // Ketika pengguna mengklik tombol, scroll ke atas dokumen

        function scrollToTop() {

            window.scrollTo({

                top: 0,

                behavior: 'smooth'

            });

        }

    </script>



<script>

    document.getElementById('toggle-btn').addEventListener('click', function () {

        const full = document.getElementById('about-full');

        if (full.classList.contains('d-none')) {

            full.classList.remove('d-none');

            this.textContent = "Tutup";

        } else {

            full.classList.add('d-none');

            this.textContent = "Baca Selengkapnya";

        }

    });

</script>

{{--
    =======================================================
    JAVASCRIPT (AJAX NO REFRESH)
    =======================================================
--}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Tangkap klik pada pagination di dalam #blog-section
    $(document).on('click', '#blog-section .custom-pagination a', function(e) {
        e.preventDefault(); // Mencegah refresh halaman

        let url = $(this).attr('href');

        // Efek Loading
        $('#blog-content').addClass('d-none');
        $('#pagination-wrapper').addClass('d-none');
        $('#blog-loader').removeClass('d-none');

        // Scroll halus ke atas section blog
        $('html, body').animate({
            scrollTop: $("#blog-section").offset().top - 100
        }, 500);

        // Ambil data baru via AJAX
        $.get(url, function(data) {
            // Ambil hanya bagian #blog-section dari respons HTML
            let newContent = $(data).find('#blog-content').html();
            let newPagination = $(data).find('#pagination-wrapper').html();
            let newInfo = $(data).find('.text-muted.small.m-0').html(); // Update info halaman

            // Ganti konten lama dengan yang baru
            $('#blog-content').html(newContent).removeClass('d-none');
            $('#pagination-wrapper').html(newPagination).removeClass('d-none');
            $('#blog-loader').addClass('d-none');

            // Update teks "Halaman X dari Y"
            $('#blog-section .text-muted.small.m-0').html(newInfo);

            // Re-apply animasi fade
            $('#blog-content .col-6').addClass('fade-in-up');
        }).fail(function() {
            alert('Gagal memuat berita. Periksa koneksi internet Anda.');
            $('#blog-content').removeClass('d-none');
            $('#pagination-wrapper').removeClass('d-none');
            $('#blog-loader').addClass('d-none');
        });
    });

});
</script>


    <!-- Memuat JS khusus untuk halaman home -->

    <script src="{{ asset('assets/js/home.js') }}"></script>

@endpush

