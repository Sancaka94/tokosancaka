<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="4IxG847KU8uhUC2VQZVSIOYJi6k1qI8PWR0yRcMe2nc" />
    <title>Sancaka Express - Jasa Perizinan, Konstruksi & Ekspedisi Terlengkap Ngawi</title>
    
    <meta name="description" content="Jasa Perizinan PBG, SLF, IMB, PT CV Kilat Ngawi. Kontraktor Bangun Rumah, Gudang, Alfamart Terpercaya. Ekspedisi Murah JNT JNE SAP Lion Parcel. Arsitek Desain 3D Terbaik.">
    <meta name="keywords" content="Jasa PBG Ngawi, Biro Jasa IMB, Kontraktor Ngawi, Ekspedisi Murah Ngawi, Desain Arsitek, Buat PT CV, Sancaka Express, CV Sancaka Karya Hutama">
    <meta name="author" content="CV. Sancaka Karya Hutama">
    <meta name="robots" content="index, follow">

    <link rel="icon" href="https://tokosancaka.com/storage/uploads/logo.jpeg" type="image/jpeg">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        :root {
            /* GRADASI WARNA KANTOR PAJAK (ELEGAN & PROFESIONAL) */
            --primary-blue: #0a2e5c;
            --secondary-yellow: #fecb00;
            --accent-red: #dc3545;
            --accent-green: #198754;
            --gradient-main: linear-gradient(135deg, #061f3e 0%, #0a2e5c 100%);
            --gradient-gold: linear-gradient(45deg, #fecb00, #ffae00);
        }

        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
            background-color: #f8f9fa;
        }

        /* --- NAVBAR & MEGA MENU --- */
        .navbar {
            background: var(--gradient-main);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        .navbar-brand img {
            height: 50px;
            border-radius: 8px;
            border: 2px solid white;
        }
        .navbar-brand span {
            color: white;
            font-weight: 800;
            font-size: 1.5rem;
            margin-left: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 600;
            transition: 0.3s;
        }
        .nav-link:hover {
            color: var(--secondary-yellow) !important;
        }
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-top: 3px solid var(--secondary-yellow);
        }
        .mega-menu {
            width: 100%;
            left: 0;
            right: 0;
            position: absolute;
            padding: 20px;
        }
        .mega-title {
            color: var(--primary-blue);
            font-weight: 800;
            border-bottom: 2px solid var(--secondary-yellow);
            padding-bottom: 5px;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        /* --- HERO SLIDER --- */
        .carousel-item {
            height: 85vh;
            min-height: 500px;
            background: no-repeat center center scroll;
            background-size: cover;
        }
        .carousel-caption {
            background: rgba(10, 46, 92, 0.7);
            border-radius: 15px;
            padding: 40px;
            bottom: 20%;
            border: 1px solid var(--secondary-yellow);
        }
        .carousel-caption h1 {
            font-weight: 900;
            font-size: 3.5rem;
            color: var(--secondary-yellow);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
        }

        /* --- CARDS & SERVICES --- */
        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }
        .section-title h2 {
            font-weight: 800;
            color: var(--primary-blue);
            text-transform: uppercase;
        }
        .section-title .line {
            width: 80px;
            height: 4px;
            background: var(--gradient-gold);
            margin: 10px auto;
        }
        .card-service {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: 0.4s;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            height: 100%;
        }
        .card-service:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        .card-header-custom {
            background: var(--gradient-main);
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: bold;
        }
        .price-tag {
            background: var(--accent-red);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            position: absolute;
            top: 10px;
            right: 10px;
            font-weight: bold;
        }

        /* --- TESTIMONI SLIDER (MARQUEE) --- */
        .testimoni-slider {
            background: var(--secondary-yellow);
            padding: 40px 0;
            overflow: hidden;
            white-space: nowrap;
        }
        .testimoni-track {
            display: inline-block;
            animation: scroll 30s linear infinite;
        }
        .testimoni-card {
            display: inline-block;
            width: 350px;
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 0 15px;
            white-space: normal;
            vertical-align: top;
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        }
        @keyframes scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* --- PAYMENTS --- */
        .payment-logo {
            height: 40px;
            margin: 10px 15px;
            filter: grayscale(100%);
            transition: 0.3s;
            opacity: 0.7;
        }
        .payment-logo:hover {
            filter: grayscale(0%);
            opacity: 1;
            transform: scale(1.1);
        }

        /* --- FOOTER --- */
        footer {
            background: #061f3e;
            color: white;
            padding-top: 60px;
            position: relative;
        }
        footer h5 {
            color: var(--secondary-yellow);
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        footer a {
            color: #ccc;
            text-decoration: none;
            transition: 0.3s;
        }
        footer a:hover {
            color: var(--secondary-yellow);
            padding-left: 5px;
        }
        .footer-bottom {
            background: #021124;
            padding: 20px 0;
            margin-top: 40px;
            text-align: center;
        }

        /* --- FLOATING BUTTONS --- */
        .btn-wa-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #25d366;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            text-align: center;
            font-size: 30px;
            line-height: 60px;
            z-index: 1000;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            animation: pulse 2s infinite;
        }
        .btn-top {
            position: fixed;
            bottom: 100px;
            right: 30px;
            background: var(--primary-blue);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 5px;
            text-align: center;
            line-height: 40px;
            z-index: 999;
            cursor: pointer;
            display: none;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(37, 211, 102, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }
        }

        .btn-cta-main {
            background: var(--gradient-gold);
            color: var(--primary-blue);
            font-weight: 800;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            text-transform: uppercase;
            box-shadow: 0 5px 15px rgba(254, 203, 0, 0.4);
            transition: 0.3s;
        }
        .btn-cta-main:hover {
            transform: scale(1.05);
            color: black;
            background: white;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Logo Sancaka Express">
                <span>SANCAKA EXPRESS</span>
            </a>
            <button class="navbar-toggler bg-warning" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#home">BERANDA</a></li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="servicesDropdown" role="button" data-bs-toggle="dropdown">
                            LAYANAN KAMI
                        </a>
                        <div class="dropdown-menu mega-menu">
                            <div class="container">
                                <div class="row">
                                    <div class="col-md-3">
                                        <h6 class="mega-title"><i class="fas fa-file-contract"></i> Legalitas & Izin</h6>
                                        <ul class="list-unstyled">
                                            <li><a class="dropdown-item" href="#">Jasa PBG & SLF</a></li>
                                            <li><a class="dropdown-item" href="#">Urus IMB Kilat</a></li>
                                            <li><a class="dropdown-item" href="#">Pendirian PT, CV, Yayasan</a></li>
                                            <li><a class="dropdown-item" href="#">Izin BPOM, Halal, PIRT</a></li>
                                            <li><a class="dropdown-item" href="#">Daftar Merk & Paten</a></li>
                                        </ul>
                                    </div>
                                    <div class="col-md-3">
                                        <h6 class="mega-title"><i class="fas fa-hard-hat"></i> Konstruksi & Arsitek</h6>
                                        <ul class="list-unstyled">
                                            <li><a class="dropdown-item" href="#">Jasa Bangun Rumah & Toko</a></li>
                                            <li><a class="dropdown-item" href="#">Kontraktor Alfamart/Indomaret</a></li>
                                            <li><a class="dropdown-item" href="#">Desain Arsitek 2D & 3D</a></li>
                                            <li><a class="dropdown-item" href="#">Hitung RAB Detail</a></li>
                                            <li><a class="dropdown-item" href="#">Pengeboran Sumur SIPA</a></li>
                                        </ul>
                                    </div>
                                    <div class="col-md-3">
                                        <h6 class="mega-title"><i class="fas fa-shipping-fast"></i> Ekspedisi & Logistik</h6>
                                        <ul class="list-unstyled">
                                            <li><a class="dropdown-item" href="#">Lion Parcel & JNT Cargo</a></li>
                                            <li><a class="dropdown-item" href="#">JNE, POS, SAP Express</a></li>
                                            <li><a class="dropdown-item" href="#">Indah Cargo & ID Express</a></li>
                                            <li><a class="dropdown-item" href="#">Daftar Jadi Agen Ekspedisi</a></li>
                                        </ul>
                                    </div>
                                    <div class="col-md-3">
                                        <h6 class="mega-title"><i class="fas fa-laptop-code"></i> Digital & PPOB</h6>
                                        <ul class="list-unstyled">
                                            <li><a class="dropdown-item" href="#">Jasa Pembuatan Website</a></li>
                                            <li><a class="dropdown-item" href="#">Pendaftaran Agen PPOB</a></li>
                                            <li><a class="dropdown-item" href="#">Top Up E-Wallet</a></li>
                                            <li><a class="dropdown-item text-danger fw-bold" href="https://tokosancaka.com/etalase">Marketplace Sancaka</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>

                    <li class="nav-item"><a class="nav-link" href="#keunggulan">KEUNGGULAN</a></li>
                    <li class="nav-item"><a class="nav-link" href="#harga">HARGA</a></li>
                    <li class="nav-item"><a class="nav-link" href="#testimoni">TESTIMONI</a></li>
                    <li class="nav-item ms-3">
                        <a href="https://wa.me/6285745808809" class="btn btn-cta-main"><i class="fab fa-whatsapp"></i> HUBUNGI KAMI</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4000">
        <div class="carousel-inner">
            <div class="carousel-item active" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1541888946425-d81bb19240f5?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');">
                <div class="carousel-caption">
                    <h1 data-aos="fade-up">KONSULTAN PERIZINAN & KONSTRUKSI TERPERCAYA</h1>
                    <p class="lead text-white" data-aos="fade-up" data-aos-delay="200">Urus PBG, SLF, IMB, PT/CV Tanpa Ribet. Bangun Rumah & Gudang Kualitas Terbaik di Ngawi & Jawa Timur.</p>
                    <a href="https://wa.me/6285745808809" class="btn btn-cta-main mt-3">KONSULTASI GRATIS SEKARANG</a>
                </div>
            </div>
            <div class="carousel-item" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');">
                <div class="carousel-caption">
                    <h1>EKSPEDISI & LOGISTIK TERLENGKAP</h1>
                    <p class="lead text-white">Kirim Paket Murah via Lion Parcel, JNT, JNE, Indah Cargo. Solusi Pengiriman Cepat & Aman.</p>
                    <a href="https://tokosancaka.com" class="btn btn-cta-main mt-3">CEK TARIF ONGKIR</a>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>

    <section id="keunggulan" class="py-5">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>KENAPA MEMILIH SANCAKA EXPRESS?</h2>
                <div class="line"></div>
                <p class="text-muted">10 Alasan Mengapa Kami Adalah Mitra Terbaik Bisnis Anda</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4 col-sm-6" data-aos="flip-left"><div class="card-service p-4 text-center"><i class="fas fa-bolt fa-3x text-warning mb-3"></i><h5>Proses Super Cepat</h5><p>Layanan kilat untuk perizinan dan pengiriman.</p></div></div>
                <div class="col-md-4 col-sm-6" data-aos="flip-left" data-aos-delay="100"><div class="card-service p-4 text-center"><i class="fas fa-hand-holding-usd fa-3x text-primary mb-3"></i><h5>Harga Termurah</h5><p>Biaya transparan, mulai Rp 2.5 Juta saja.</p></div></div>
                <div class="col-md-4 col-sm-6" data-aos="flip-left" data-aos-delay="200"><div class="card-service p-4 text-center"><i class="fas fa-users fa-3x text-success mb-3"></i><h5>Tim Profesional</h5><p>Didukung arsitek dan legal ahli berpengalaman.</p></div></div>
                <div class="col-md-4 col-sm-6" data-aos="flip-left"><div class="card-service p-4 text-center"><i class="fas fa-shield-alt fa-3x text-danger mb-3"></i><h5>Terjamin Legalitas</h5><p>Dokumen resmi negara, aman dan valid 100%.</p></div></div>
                <div class="col-md-4 col-sm-6" data-aos="flip-left" data-aos-delay="100"><div class="card-service p-4 text-center"><i class="fas fa-truck fa-3x text-info mb-3"></i><h5>Ekspedisi All-in-One</h5><p>Tersedia semua kurir: JNE, JNT, Lion, POS, dll.</p></div></div>
                <div class="col-md-4 col-sm-6" data-aos="flip-left" data-aos-delay="200"><div class="card-service p-4 text-center"><i class="fas fa-drafting-compass fa-3x text-dark mb-3"></i><h5>Desain Presisi</h5><p>Gambar 2D/3D detail dengan perhitungan RAB akurat.</p></div></div>
                <div class="col-md-4 col-sm-6" data-aos="flip-left"><div class="card-service p-4 text-center"><i class="fas fa-headset fa-3x text-warning mb-3"></i><h5>Support 24/7</h5><p>Layanan konsultasi via WhatsApp kapan saja.</p></div></div>
                <div class="col-md-4 col-sm-6" data-aos="flip-left" data-aos-delay="100"><div class="card-service p-4 text-center"><i class="fas fa-network-wired fa-3x text-primary mb-3"></i><h5>Jaringan Luas</h5><p>Mencakup seluruh wilayah Indonesia.</p></div></div>
                <div class="col-md-4 col-sm-6" data-aos="flip-left" data-aos-delay="200"><div class="card-service p-4 text-center"><i class="fas fa-award fa-3x text-success mb-3"></i><h5>Garansi Kepuasan</h5><p>Ribuan klien puas dengan layanan kami.</p></div></div>
            </div>
        </div>
    </section>

    <section id="harga" class="py-5 bg-light">
        <div class="container">
            <div class="section-title">
                <h2>LAYANAN UNGGULAN & PENAWARAN SPESIAL</h2>
                <div class="line"></div>
                <p>Solusi Satu Pintu untuk Kebutuhan Bisnis & Properti Anda</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card card-service h-100 border-0">
                        <div class="card-header-custom">IZIN USAHA & LEGALITAS</div>
                        <div class="card-body">
                            <span class="price-tag">Mulai Rp 2.500.000</span>
                            <img src="https://images.unsplash.com/photo-1450101499163-c8848c66ca85?auto=format&fit=crop&w=500&q=60" class="img-fluid mb-3 rounded" alt="Jasa Izin Usaha">
                            <h5 class="card-title fw-bold">Paket Pendirian Usaha</h5>
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Pendirian PT / CV / Yayasan</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> NIB & OSS RBA</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Izin BPOM, Halal, PIRT</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Daftar Merk & Paten (HAKI)</li>
                            </ul>
                            <a href="https://wa.me/6285745808809?text=Halo%20Sancaka,%20saya%20mau%20konsultasi%20Legalitas" class="btn btn-primary w-100">Pesan Sekarang</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card card-service h-100 border-0">
                        <div class="card-header-custom bg-warning text-dark">KONSTRUKSI & PERIZINAN BANGUNAN</div>
                        <div class="card-body">
                            <span class="price-tag bg-primary">Mulai Rp 5.000.000</span>
                            <img src="https://images.unsplash.com/photo-1503387762-592deb58ef4e?auto=format&fit=crop&w=500&q=60" class="img-fluid mb-3 rounded" alt="Jasa Konstruksi">
                            <h5 class="card-title fw-bold">Paket Bangun & PBG/IMB</h5>
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Pengurusan PBG & SLF</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Gambar Arsitek 2D & 3D</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Hitung RAB & Struktur</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Pengeboran Sumur SIPA</li>
                            </ul>
                            <a href="https://wa.me/6285745808809?text=Halo%20Sancaka,%20saya%20mau%20konsultasi%20Konstruksi" class="btn btn-warning w-100 fw-bold">Konsultasi Gratis</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card card-service h-100 border-0">
                        <div class="card-header-custom bg-success">DIGITAL & WEBSITE</div>
                        <div class="card-body">
                            <span class="price-tag bg-dark">Mulai Rp 15.000.000</span>
                            <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=500&q=60" class="img-fluid mb-3 rounded" alt="Jasa Website">
                            <h5 class="card-title fw-bold">Paket Bisnis Go Digital</h5>
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Pembuatan Website Profesional</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> SEO & Google Ads</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Payment Gateway Integration</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Company Profile & Katalog</li>
                            </ul>
                            <a href="https://wa.me/6285745808809?text=Halo%20Sancaka,%20saya%20mau%20buat%20Website" class="btn btn-success w-100">Go Digital Sekarang</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-5">
                <div class="col-12 text-center bg-white p-5 rounded shadow" style="border-left: 5px solid var(--secondary-yellow);">
                    <h3 class="fw-bold text-primary">INGIN PENGHASILAN TAMBAHAN JUTAAN RUPIAH?</h3>
                    <p class="lead">Bergabunglah menjadi <strong>AGEN EKSPEDISI</strong> (Lion Parcel, JNT, dll) & <strong>AGEN LOKET PPOB</strong> bersama Kami.</p>
                    <p>Atau Mulai Berjualan Online di Marketplace Sancaka!</p>
                    <div class="d-flex justify-content-center gap-3 mt-3">
                        <a href="https://wa.me/6285745808809?text=Saya%20Mau%20Daftar%20Agen" class="btn btn-danger btn-lg rounded-pill px-5">DAFTAR AGEN</a>
                        <a href="https://tokosancaka.com/etalase" class="btn btn-outline-primary btn-lg rounded-pill px-5">BUKA TOKO ONLINE</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="testimoni">
        <div class="container-fluid p-0">
            <div class="section-title mt-5">
                <h2>APA KATA MEREKA?</h2>
                <p>Bukti Nyata Kepuasan Pelanggan Sancaka Express</p>
            </div>
            <div class="testimoni-slider">
                <div class="testimoni-track">
                    <div class="testimoni-card">
                        <div class="text-warning mb-2"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="fst-italic">"Urus PBG dan IMB di Sancaka cepat banget! Timnya ramah, gambar arsiteknya juga keren. Recommended!"</p>
                        <h6 class="fw-bold mt-3">- Budi Santoso, Owner Alfamart Ngawi</h6>
                    </div>
                    <div class="testimoni-card">
                        <div class="text-warning mb-2"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="fst-italic">"Kirim paket lewat Sancaka Express murah dan pilihannya banyak. Bisa pilih Lion Parcel atau JNT Cargo sesuka hati."</p>
                        <h6 class="fw-bold mt-3">- Siti Aminah, Online Shop</h6>
                    </div>
                    <div class="testimoni-card">
                        <div class="text-warning mb-2"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="fst-italic">"Buat PT dan CV kilat, 3 hari jadi. Legalitas aman, pelayanan bintang 5. Maju terus CV Sancaka!"</p>
                        <h6 class="fw-bold mt-3">- PT. Maju Jaya Abadi</h6>
                    </div>
                    <div class="testimoni-card">
                        <div class="text-warning mb-2"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="fst-italic">"Desain rumah impian jadi nyata berkat arsitek Sancaka. RAB-nya detail, jadi gak boncos pas bangun."</p>
                        <h6 class="fw-bold mt-3">- Pak Hartono, Madiun</h6>
                    </div>
                    <div class="testimoni-card">
                        <div class="text-warning mb-2"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="fst-italic">"Website perusahaan saya jadi elegan dan profesional. SEO-nya mantap, langsung halaman 1 Google."</p>
                        <h6 class="fw-bold mt-3">- Direktur CV. Berkah Alam</h6>
                    </div>
                     <div class="testimoni-card">
                        <div class="text-warning mb-2"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="fst-italic">"Urus PBG dan IMB di Sancaka cepat banget! Timnya ramah, gambar arsiteknya juga keren."</p>
                        <h6 class="fw-bold mt-3">- Budi Santoso</h6>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-white">
        <div class="container">
            <div class="section-title">
                <h2>PERTANYAAN UMUM (Q&A)</h2>
                <div class="line"></div>
            </div>
            <div class="accordion" id="accordionExample">
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">Berapa lama proses pembuatan IMB/PBG?</button></h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionExample"><div class="accordion-body">Proses tergantung kelengkapan dokumen, rata-rata estimasi 7-14 hari kerja untuk wilayah Ngawi dan sekitarnya.</div></div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">Apakah bisa melayani luar kota Ngawi?</button></h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionExample"><div class="accordion-body">Tentu! Kami melayani jasa legalitas dan desain arsitek untuk seluruh wilayah Indonesia. Untuk konstruksi fisik, kami melayani area Jawa Timur dan Jawa Tengah.</div></div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">Ekspedisi apa saja yang tersedia?</button></h2>
                    <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionExample"><div class="accordion-body">Lengkap! Kami bermitra dengan Lion Parcel, JNE, J&T, Sicepat, Anteraja, ID Express, SAP, Indah Cargo, dan Pos Indonesia.</div></div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-4 bg-light">
        <div class="container text-center">
            <p class="fw-bold text-muted mb-3">METODE PEMBAYARAN TERLENGKAP (QRIS & ALL BANK)</p>
            <div class="d-flex justify-content-center flex-wrap align-items-center">
                <img src="https://placehold.co/100x40/png?text=BCA" class="payment-logo" alt="BCA">
                <img src="https://placehold.co/100x40/png?text=MANDIRI" class="payment-logo" alt="Mandiri">
                <img src="https://placehold.co/100x40/png?text=BRI" class="payment-logo" alt="BRI">
                <img src="https://placehold.co/100x40/png?text=BNI" class="payment-logo" alt="BNI">
                <img src="https://placehold.co/100x40/png?text=QRIS" class="payment-logo" alt="QRIS">
                <img src="https://placehold.co/100x40/png?text=DANA" class="payment-logo" alt="Dana">
                <img src="https://placehold.co/100x40/png?text=OVO" class="payment-logo" alt="OVO">
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Logo" height="60" class="rounded me-2 border border-warning">
                        <h4 class="text-white fw-bold m-0">SANCAKA EXPRESS</h4>
                    </div>
                    <p class="small text-secondary">
                        CV. SANCAKA KARYA HUTAMA adalah perusahaan "One Stop Solution" di Ngawi yang bergerak di bidang Jasa Perizinan (Legalitas), Kontraktor Bangunan, Arsitektur, dan Ekspedisi Logistik Terlengkap.
                    </p>
                    <div class="mt-3">
                        <a href="#" class="me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-tiktok fa-lg"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-youtube fa-lg"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Layanan</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#">Jasa PBG & SLF</a></li>
                        <li class="mb-2"><a href="#">Pendirian PT/CV</a></li>
                        <li class="mb-2"><a href="#">Bangun Rumah</a></li>
                        <li class="mb-2"><a href="#">Cek Resi</a></li>
                        <li class="mb-2"><a href="#">Cek Ongkir</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Hubungi Kami</h5>
                    <ul class="list-unstyled text-secondary">
                        <li class="mb-3"><i class="fas fa-map-marker-alt text-warning me-2"></i> 
                            Jl. Dr. Wahidin No. 18A, RT.22 RW.05, Kel. Ketanggi, Kec. Ngawi, Kab. Ngawi, Jawa Timur 63211
                        </li>
                        <li class="mb-3"><i class="fab fa-whatsapp text-warning me-2"></i> 0857-4580-8809</li>
                        <li class="mb-3"><i class="fas fa-envelope text-warning me-2"></i> admin@tokosancaka.com</li>
                        <li class="mb-3"><i class="fas fa-clock text-warning me-2"></i> Senin - Sabtu: 08.00 - 17.00</li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Lokasi Kantor</h5>
                    <div class="ratio ratio-1x1 rounded overflow-hidden border border-secondary">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3955.334114298124!2d111.4454506!3d-7.3929826!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e79e79314052f63%3A0x1bf49943d0caef65!2sJalan%20Dokter%20Wahidin%20No.18A%2C%20RT.22%2FRW.05%2C%20Sidomakmur%2C%20Ketanggi%2C%20Kec.%20Ngawi%2C%20Kabupaten%20Ngawi%2C%20Jawa%20Timur%2063211!5e0!3m2!1sen!2sid!4v1690000000000!5m2!1sen!2sid" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container">
                <p class="m-0 small text-secondary">&copy; 2024 <strong>CV. SANCAKA KARYA HUTAMA</strong>. All Rights Reserved. Designed with <i class="fas fa-heart text-danger"></i> in Ngawi.</p>
            </div>
        </div>
    </footer>

    <a href="https://wa.me/6285745808809?text=Halo%20Admin%20Sancaka%20Express,%20saya%20butuh%20bantuan." class="btn-wa-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>
    <div class="btn-top" onclick="scrollToTop()">
        <i class="fas fa-arrow-up"></i>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Init Animation on Scroll
        AOS.init({
            duration: 1000,
            once: true
        });

        // Scroll to Top Logic
        window.onscroll = function() {
            var btnTop = document.querySelector('.btn-top');
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                btnTop.style.display = "block";
            } else {
                btnTop.style.display = "none";
            }
        };

        function scrollToTop() {
            window.scrollTo({top: 0, behavior: 'smooth'});
        }
    </script>
</body>
</html>