@extends('layouts.app')

@section('title', 'Tentang Kami - CV. Sancaka Karya Hutama')

@section('content')

{{-- CUSTOM CSS (Untuk mempercantik Bootstrap) --}}
<style>
    /* Hero Section */
    .hero-about {
        background: linear-gradient(135deg, #0d6efd 0%, #052c65 100%);
        color: white;
        padding: 100px 0;
        position: relative;
        overflow: hidden;
    }
    .hero-bg-icon {
        position: absolute;
        right: -50px; bottom: -50px;
        font-size: 15rem;
        opacity: 0.1;
        transform: rotate(-15deg);
    }

    /* Card Hover Effect */
    .hover-lift {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }

    /* Service Icons */
    .service-icon {
        width: 60px; height: 60px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%;
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
    }

    /* Payment & Partner Badges */
    .badge-partner {
        font-size: 0.9rem;
        padding: 10px 15px;
        background: #fff;
        color: #555;
        border: 1px solid #dee2e6;
        margin: 5px;
        display: inline-block;
        border-radius: 8px;
        font-weight: 600;
    }
</style>

{{-- 1. HERO SECTION --}}
<div class="hero-about text-center">
    <i class="fas fa-building hero-bg-icon"></i>
    <div class="container position-relative z-1">
        <span class="badge bg-warning text-dark mb-3 px-3 py-2 rounded-pill shadow-sm">Mitra Solusi Bisnis Terpercaya</span>
        <h1 class="display-4 fw-bold mb-3">CV. SANCAKA KARYA HUTAMA</h1>
        <p class="lead mx-auto mb-4 text-light opacity-75" style="max-width: 800px;">
            Perusahaan terpercaya yang bergerak di bidang jual beli barang dan jasa, pengiriman, perizinan, hingga solusi digital dan pembayaran modern.
        </p>
    </div>
</div>

{{-- 2. VISI & MISI --}}
<div class="py-5 bg-white">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-10 text-center">
                <h2 class="fw-bold text-primary mb-4">Visi & Misi</h2>
                <p class="text-muted fs-5">
                    "Menjadi perusahaan terkemuka di bidang jual beli barang dan jasa di Indonesia, yang dikenal karena inovasi, kepercayaan, dan layanan terbaik."
                </p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm bg-light">
                    <div class="card-body p-4">
                        <h4 class="card-title fw-bold text-dark mb-3"><i class="fas fa-eye text-primary me-2"></i> Visi Kami</h4>
                        <p class="card-text text-secondary">
                            Membangun hubungan jangka panjang dengan pelanggan berdasarkan kepercayaan, integritas, dan layanan terbaik untuk memenuhi kebutuhan pelanggan di seluruh Indonesia.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm bg-light">
                    <div class="card-body p-4">
                        <h4 class="card-title fw-bold text-dark mb-3"><i class="fas fa-bullseye text-danger me-2"></i> Misi Kami</h4>
                        <ul class="list-unstyled text-secondary">
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Layanan berkualitas tinggi & profesional.</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Solusi pengiriman aman, cepat, bergaransi.</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Layanan digital inovatif (Desain & Website).</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Perizinan PBG, SLF, IMB yang cepat & transparan.</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Mitra percetakan berkualitas di Ngawi.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 3. LAYANAN KAMI --}}
<div class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark">Layanan Unggulan</h2>
            <p class="text-muted">Solusi komprehensif untuk kebutuhan pribadi dan bisnis Anda.</p>
        </div>

        <div class="row g-4">

            {{-- Jual Beli --}}
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-lift">
                    <div class="card-body p-4 text-center">
                        <div class="service-icon bg-primary bg-opacity-10 text-primary mx-auto">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h5 class="fw-bold">Jual Beli Barang & Jasa</h5>
                        <p class="text-muted small">Menyediakan beragam barang berkualitas tinggi serta layanan profesional dengan hasil terbaik.</p>
                    </div>
                </div>
            </div>

            {{-- Perizinan (PBG, SLF, IMB) --}}
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-lift">
                    <div class="card-body p-4 text-center">
                        <div class="service-icon bg-success bg-opacity-10 text-success mx-auto">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <h5 class="fw-bold">Jasa Perizinan Bangunan</h5>
                        <p class="text-muted small mb-2">Layanan pengurusan izin cepat, efisien, dan transparan:</p>
                        <ul class="text-start small text-muted ps-4">
                            <li><b>PBG</b> (Persetujuan Bangunan Gedung)</li>
                            <li><b>SLF</b> (Sertifikat Laik Fungsi)</li>
                            <li><b>IMB</b> (Izin Mendirikan Bangunan)</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Desain & Digital --}}
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-lift">
                    <div class="card-body p-4 text-center">
                        <div class="service-icon bg-info bg-opacity-10 text-info mx-auto">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                        <h5 class="fw-bold">Desain & Digital Marketing</h5>
                        <p class="text-muted small">Desain grafis kreatif untuk branding, pembuatan website profesional, dan strategi pemasaran digital yang efektif.</p>
                    </div>
                </div>
            </div>

            {{-- Pengiriman --}}
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-lift">
                    <div class="card-body p-4 text-center">
                        <div class="service-icon bg-danger bg-opacity-10 text-danger mx-auto">
                            <i class="fas fa-truck-fast"></i>
                        </div>
                        <h5 class="fw-bold">Ekspedisi & Cargo</h5>
                        <p class="text-muted small">Pengiriman barang cepat dan murah, pindahan rumah/kos, hingga kirim motor dengan keamanan maksimal.</p>
                    </div>
                </div>
            </div>

            {{-- Percetakan --}}
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-lift">
                    <div class="card-body p-4 text-center">
                        <div class="service-icon bg-warning bg-opacity-10 text-warning mx-auto">
                            <i class="fas fa-print"></i>
                        </div>
                        <h5 class="fw-bold">Percetakan & Printing</h5>
                        <p class="text-muted small">Layanan percetakan, printing, dan print copy berkualitas tinggi di wilayah Ngawi dan sekitarnya.</p>
                    </div>
                </div>
            </div>

            {{-- Customer Support --}}
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm bg-primary text-white hover-lift">
                    <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                        <i class="fas fa-headset fa-3x mb-3 text-white-50"></i>
                        <h5 class="fw-bold">Butuh Konsultasi?</h5>
                        <p class="small text-white-50">Tim kami siap membantu kebutuhan bisnis Anda.</p>
                        <a href="https://wa.me/6285745808809" class="btn btn-light btn-sm fw-bold rounded-pill mt-2">Hubungi WhatsApp</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- 4. PEMBAYARAN OTOMATIS (BARU) --}}
<div class="py-5 bg-white border-bottom">
    <div class="container text-center">
        <h3 class="fw-bold mb-3">Metode Pembayaran Otomatis</h3>
        <p class="text-muted mb-4 mx-auto" style="max-width: 700px;">
            Kami mendukung pembayaran yang mudah, aman, dan otomatis. Terintegrasi dengan seluruh Bank di Indonesia, E-Wallet, dan QRIS.
        </p>

        <div class="d-flex flex-wrap justify-content-center gap-2">
            {{-- Bank Transfer --}}
            <span class="badge-partner"><i class="fas fa-university text-primary me-1"></i> BCA</span>
            <span class="badge-partner"><i class="fas fa-university text-primary me-1"></i> BRI</span>
            <span class="badge-partner"><i class="fas fa-university text-primary me-1"></i> Mandiri</span>
            <span class="badge-partner"><i class="fas fa-university text-primary me-1"></i> BNI</span>
            <span class="badge-partner"><i class="fas fa-university text-primary me-1"></i> BSI</span>
            <span class="badge-partner"><i class="fas fa-university text-primary me-1"></i> Permata</span>

            {{-- E-Wallet & QRIS --}}
            <span class="badge-partner"><i class="fas fa-qrcode text-dark me-1"></i> QRIS</span>
            <span class="badge-partner"><i class="fas fa-wallet text-info me-1"></i> GoPay</span>
            <span class="badge-partner"><i class="fas fa-wallet text-info me-1"></i> OVO</span>
            <span class="badge-partner"><i class="fas fa-wallet text-info me-1"></i> Dana</span>
            <span class="badge-partner"><i class="fas fa-wallet text-info me-1"></i> ShopeePay</span>
            <span class="badge-partner"><i class="fas fa-store text-danger me-1"></i> Alfamart/Indomaret</span>
        </div>
    </div>
</div>

{{-- 5. MITRA EKSPEDISI --}}
<div class="py-5 bg-light">
    <div class="container text-center">
        <p class="text-uppercase fw-bold text-muted small mb-4 ls-1">Bekerja Sama dengan Ekspedisi Terpercaya</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            @php
                $partners = ['JNE', 'TIKI', 'POS Indonesia', 'SiCepat', 'J&T Express', 'Ninja Xpress', 'Lion Parcel', 'Wahana', 'ID Express', 'Dakota Cargo', 'Indah Cargo', 'SAP Express'];
            @endphp
            @foreach($partners as $partner)
                <div class="badge-partner shadow-sm border-0">{{ $partner }}</div>
            @endforeach
        </div>
        <p class="small text-muted mt-3">Menjamin pengiriman barang Anda cepat, aman, dan bergaransi.</p>
    </div>
</div>

{{-- 6. FOOTER & KONTAK --}}
<div class="bg-dark text-white py-5">
    <div class="container">
        <div class="row g-5 align-items-center">

            {{-- Info Kontak --}}
            <div class="col-lg-6">
                <h3 class="fw-bold mb-4 border-bottom border-secondary pb-3 d-inline-block">Hubungi Kami</h3>
                <p class="text-white-50 mb-4">
                    Kami selalu siap membantu kebutuhan Anda. Untuk informasi lebih lanjut mengenai layanan kami, silakan hubungi kontak di bawah ini.
                </p>

                <div class="d-flex mb-3">
                    <div class="me-3 text-primary fs-4"><i class="fas fa-map-marker-alt"></i></div>
                    <div>
                        <h6 class="fw-bold mb-1">Alamat</h6>
                        <p class="text-white-50 mb-0">
                            Jl. Dr. Wahidin No.18A RT.22 RW.05,<br>
                            Kel. Ketanggi, Kec. Ngawi, Kab. Ngawi,<br>
                            Jawa Timur 63211
                        </p>
                    </div>
                </div>

                <div class="d-flex mb-3">
                    <div class="me-3 text-success fs-4"><i class="fab fa-whatsapp"></i></div>
                    <div>
                        <h6 class="fw-bold mb-1">WhatsApp</h6>
                        <p class="text-white-50 mb-0 font-monospace">0857-4580-8809</p>
                    </div>
                </div>

                <div class="d-flex mb-3">
                    <div class="me-3 text-info fs-4"><i class="fas fa-globe"></i></div>
                    <div>
                        <h6 class="fw-bold mb-1">Website Kami</h6>
                        <a href="https://sancaka.bisnis.pro" class="text-white-50 text-decoration-none">sancaka.bisnis.pro</a><br>
                        <a href="https://bisnis.pro" class="text-white-50 text-decoration-none">bisnis.pro</a><br>
                        <a href="https://tokosancaka.com" class="text-white-50 text-decoration-none">tokosancaka.com</a><br>
                        <a href="https://tokosancaka.biz.id" class="text-white-50 text-decoration-none">tokosancaka.biz.id</a><br>
                        <a href="https://sancaka.my.id" class="text-white-50 text-decoration-none">sancaka.my.id</a>

                    </div>
                </div>
            </div>

            {{-- Maps --}}
            <div class="col-lg-6">
                <div class="ratio ratio-16x9 rounded-3 overflow-hidden shadow border border-secondary">
                    {{-- Ganti src dengan link embed Google Maps asli lokasi Anda --}}
                    <iframe
                        src="https://www.google.com/maps?q=Jl.+Dr.+Wahidin+No.18A,+Ngawi&output=embed"
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
