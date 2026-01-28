@extends('layouts.app')

@section('title', 'Tentang Kami - CV. Sancaka Karya Hutama')

@section('content')

{{-- CUSTOM CSS UNTUK HALAMAN INI --}}
<style>
    /* Hero Section Gradient & Overlay */
    .hero-about {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        color: white;
        padding: 80px 0;
        position: relative;
        overflow: hidden;
    }
    .hero-about::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background-image: url('https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
        background-size: cover;
        background-position: center;
        opacity: 0.15;
    }
    .hero-content {
        position: relative;
        z-index: 2;
    }

    /* Kartu Layanan */
    .service-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        border-top: 4px solid transparent;
    }
    .service-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
    .border-top-blue { border-top-color: #0d6efd; }
    .border-top-red { border-top-color: #dc3545; }
    .border-top-green { border-top-color: #198754; }
    .border-top-purple { border-top-color: #6f42c1; }
    .border-top-yellow { border-top-color: #ffc107; }

    /* Icon Circle */
    .icon-box {
        width: 60px; height: 60px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
</style>

{{-- 1. HERO SECTION --}}
<div class="hero-about text-center">
    <div class="container hero-content">
        <span class="badge rounded-pill bg-light text-primary mb-3 px-3 py-2 border">Terpercaya & Profesional</span>
        <h1 class="display-4 fw-bold mb-3">CV. SANCAKA KARYA HUTAMA</h1>
        <p class="lead mx-auto mb-0" style="max-width: 800px;">
            Mitra Solusi Terbaik untuk Bisnis dan Kebutuhan Anda. Bergerak di bidang jual beli barang jasa, pengiriman, perizinan, hingga digital marketing.
        </p>
    </div>
</div>

{{-- 2. INTRODUCTION & VISI MISI --}}
<div class="py-5 bg-white">
    <div class="container">

        {{-- Intro Text --}}
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <h6 class="text-primary fw-bold text-uppercase ls-1">Tentang Kami</h6>
                <p class="mt-3 text-muted fs-5">
                    Selamat datang di <b>CV. SANCAKA KARYA HUTAMA</b>, perusahaan terpercaya yang bergerak di bidang jual beli barang dan jasa. Kami hadir untuk memberikan solusi komprehensif dalam berbagai kebutuhan Anda, termasuk jasa pengiriman, desain grafis, pemasaran digital, percetakan, hingga layanan profesional lainnya.
                </p>
            </div>
        </div>

        {{-- Visi & Misi --}}
        <div class="row g-4 align-items-start">
            {{-- Visi --}}
            <div class="col-md-6">
                <div class="p-4 bg-light rounded-3 shadow-sm h-100 border-start border-4 border-primary">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary text-white rounded p-3 me-3">
                            <i class="fas fa-eye fa-lg"></i>
                        </div>
                        <h3 class="fw-bold m-0 text-dark">Visi Kami</h3>
                    </div>
                    <p class="text-secondary fst-italic fs-5 mb-0">
                        "Menjadi perusahaan terkemuka di bidang jual beli barang dan jasa di Indonesia, yang dikenal karena inovasi, kepercayaan, dan layanan terbaik untuk memenuhi kebutuhan pelanggan."
                    </p>
                </div>
            </div>

            {{-- Misi --}}
            <div class="col-md-6">
                <div class="h-100 ps-md-3">
                    <h3 class="fw-bold mb-4 d-flex align-items-center text-dark">
                        <i class="fas fa-bullseye text-primary me-3"></i> Misi Kami
                    </h3>
                    <ul class="list-unstyled">
                        <li class="d-flex mb-3">
                            <i class="fas fa-check-circle text-success mt-1 me-3 flex-shrink-0"></i>
                            <span class="text-secondary">Memberikan layanan berkualitas tinggi dengan mengutamakan profesionalisme.</span>
                        </li>
                        <li class="d-flex mb-3">
                            <i class="fas fa-check-circle text-success mt-1 me-3 flex-shrink-0"></i>
                            <span class="text-secondary">Menghadirkan solusi pengiriman barang aman, cepat, dan bergaransi.</span>
                        </li>
                        <li class="d-flex mb-3">
                            <i class="fas fa-check-circle text-success mt-1 me-3 flex-shrink-0"></i>
                            <span class="text-secondary">Mendukung kebutuhan digital melalui desain grafis & website inovatif.</span>
                        </li>
                        <li class="d-flex mb-3">
                            <i class="fas fa-check-circle text-success mt-1 me-3 flex-shrink-0"></i>
                            <span class="text-secondary">Menyediakan layanan perizinan (PBG, SLF, IMB) yang cepat & transparan.</span>
                        </li>
                        <li class="d-flex mb-3">
                            <i class="fas fa-check-circle text-success mt-1 me-3 flex-shrink-0"></i>
                            <span class="text-secondary">Menjadi mitra andal dalam percetakan berkualitas di Ngawi.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 3. LAYANAN KAMI --}}
<div class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Layanan Unggulan</h2>
            <p class="text-muted">Solusi lengkap untuk kebutuhan pribadi dan bisnis Anda.</p>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">

            {{-- Card 1 --}}
            <div class="col">
                <div class="card h-100 shadow-sm service-card border-top-blue p-3">
                    <div class="card-body">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h5 class="card-title fw-bold">Jual Beli Barang & Jasa</h5>
                        <p class="card-text text-muted small">Menyediakan beragam barang berkualitas tinggi serta layanan profesional dengan hasil terbaik untuk kepuasan Anda.</p>
                    </div>
                </div>
            </div>

            {{-- Card 2 --}}
            <div class="col">
                <div class="card h-100 shadow-sm service-card border-top-red p-3">
                    <div class="card-body">
                        <div class="icon-box bg-danger bg-opacity-10 text-danger">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <h5 class="card-title fw-bold">Jasa Pengiriman & Cargo</h5>
                        <p class="card-text text-muted small mb-3">Pengiriman cepat, murah, amanah & bergaransi. Melayani paket reguler, kargo, pindahan rumah/kos, hingga kirim motor.</p>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach(['JNE','J&T','SiCepat','POS','TiKi','Ninja','Lion','Wahana'] as $exp)
                                <span class="badge bg-secondary bg-opacity-10 text-dark border">{{ $exp }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 3 --}}
            <div class="col">
                <div class="card h-100 shadow-sm service-card border-top-green p-3">
                    <div class="card-body">
                        <div class="icon-box bg-success bg-opacity-10 text-success">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <h5 class="card-title fw-bold">Jasa Perizinan Bangunan</h5>
                        <ul class="small text-muted ps-3 mb-2">
                            <li><b>PBG</b> (Persetujuan Bangunan Gedung)</li>
                            <li><b>SLF</b> (Sertifikat Laik Fungsi)</li>
                            <li><b>IMB</b> (Izin Mendirikan Bangunan)</li>
                        </ul>
                        <p class="card-text text-muted small">Proses cepat, efisien, dan transparan.</p>
                    </div>
                </div>
            </div>

            {{-- Card 4 --}}
            <div class="col">
                <div class="card h-100 shadow-sm service-card border-top-purple p-3">
                    <div class="card-body">
                        <div class="icon-box bg-info bg-opacity-10 text-dark" style="color: #6f42c1;">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                        <h5 class="card-title fw-bold">Desain & Digital Marketing</h5>
                        <p class="card-text text-muted small">Layanan desain grafis kreatif untuk branding, pembuatan website profesional, dan strategi pemasaran digital yang efektif.</p>
                    </div>
                </div>
            </div>

            {{-- Card 5 --}}
            <div class="col">
                <div class="card h-100 shadow-sm service-card border-top-yellow p-3">
                    <div class="card-body">
                        <div class="icon-box bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-print"></i>
                        </div>
                        <h5 class="card-title fw-bold">Percetakan & Printing</h5>
                        <p class="card-text text-muted small">Layanan percetakan, printing, dan print copy dengan kualitas tinggi di wilayah Ngawi dan sekitarnya.</p>
                    </div>
                </div>
            </div>

            {{-- Card 6: Support --}}
            <div class="col">
                <div class="card h-100 shadow-sm bg-primary text-white service-card p-3 text-center d-flex align-items-center justify-content-center">
                    <div class="card-body">
                        <i class="fas fa-headset fa-3x mb-3 text-white-50"></i>
                        <h5 class="card-title fw-bold">Butuh Bantuan?</h5>
                        <p class="card-text small text-white-50 mb-4">Tim kami siap membantu kebutuhan bisnis Anda kapan saja.</p>
                        <a href="https://wa.me/6285745808809" target="_blank" class="btn btn-light fw-bold rounded-pill px-4 text-primary">
                            <i class="fab fa-whatsapp me-2"></i> Hubungi WhatsApp
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- 4. PARTNERS --}}
<div class="py-5 bg-white border-top border-bottom">
    <div class="container text-center">
        <p class="text-uppercase fw-bold text-muted small mb-4" style="letter-spacing: 2px;">Bekerja sama dengan Ekspedisi Terpercaya</p>
        <div class="d-flex flex-wrap justify-content-center gap-3 opacity-75">
            @php
                $partners = ['JNE', 'TIKI', 'POS Indonesia', 'SiCepat', 'J&T Express', 'Ninja Xpress', 'Lion Parcel', 'Wahana', 'ID Express', 'Dakota Cargo', 'Indah Cargo', 'SAP Express'];
            @endphp
            @foreach($partners as $partner)
                <span class="badge bg-white text-dark border p-3 fs-6 shadow-sm">{{ $partner }}</span>
            @endforeach
        </div>
    </div>
</div>

{{-- 5. CONTACT & FOOTER --}}
<div class="bg-dark text-white py-5">
    <div class="container">
        <div class="row align-items-center g-5">

            {{-- Contact Info --}}
            <div class="col-lg-6">
                <h2 class="fw-bold mb-4">Hubungi Kami</h2>
                <p class="text-white-50 mb-5">
                    Kami selalu siap membantu kebutuhan Anda. Untuk informasi lebih lanjut mengenai layanan kami, silakan hubungi kontak di bawah ini.
                </p>

                <div class="d-flex mb-4">
                    <div class="me-4 text-primary fs-4"><i class="fas fa-map-marker-alt"></i></div>
                    <div>
                        <h5 class="fw-bold mb-1">Alamat Kantor</h5>
                        <p class="text-white-50 mb-0">
                            Jl. Dr. Wahidin No.18A RT.22 RW.05,<br>
                            Kel. Ketanggi, Kec. Ngawi,<br>
                            Kab. Ngawi, Jawa Timur 63211
                        </p>
                    </div>
                </div>

                <div class="d-flex mb-4">
                    <div class="me-4 text-success fs-4"><i class="fab fa-whatsapp"></i></div>
                    <div>
                        <h5 class="fw-bold mb-1">WhatsApp</h5>
                        <p class="text-white-50 mb-0 font-monospace fs-5">0857-4580-8809</p>
                    </div>
                </div>

                <div class="d-flex mb-4">
                    <div class="me-4 text-info fs-4"><i class="fas fa-globe"></i></div>
                    <div>
                        <h5 class="fw-bold mb-1">Website</h5>
                        <a href="https://sancaka.bisnis.pro" target="_blank" class="text-white-50 text-decoration-none border-bottom border-secondary pb-1 hover-text-white">
                            sancaka.bisnis.pro
                        </a>
                    </div>
                </div>
            </div>

            {{-- Google Maps --}}
            <div class="col-lg-6">
                <div class="ratio ratio-16x9 rounded-3 overflow-hidden shadow-lg border border-secondary">
                    <iframe
                        src="https://maps.google.com/maps?q=Jl.%20Dr.%20Wahidin%20No.18A%20Ngawi&t=&z=15&ie=UTF8&iwloc=&output=embed"
                        allowfullscreen=""
                        loading="lazy"
                        style="border:0;">
                    </iframe>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
