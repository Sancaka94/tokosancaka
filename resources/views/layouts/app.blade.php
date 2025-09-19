<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sancaka Express')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://tokosancaka.biz.id/storage/uploads/sancaka.png">

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Vendor CSS (from CDN) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS untuk Halaman Depan -->
    <link rel="stylesheet" href="{{ asset('public/assets/css/home-style.css') }}">
    
    <style>
        /*
        ==========================================================
        PERBAIKAN DESAIN: Layout 80% di Layar Besar
        ==========================================================
        */
    

        .navbar .container {
            max-width: 100% !important;
        }

        
        /*
        ==========================================================
        PERBAIKAN TABEL RESPONSIVE (BISA SCROLL)
        ==========================================================
        */
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* CSS for Mega Menu */
        .dropdown.mega-dropdown {
          position: static;
        }

        .dropdown-menu.dropdown-megamenu {
          width: 100%;
          left: 0;
          right: 0;
          padding: 20px 30px;
          margin-top: 0; /* Removes the small gap */
          border-top: 1px solid #eee;
          border-radius: 0 0 0.375rem 0.375rem;
        }

        .megamenu-heading {
            font-size: 1rem;
            font-weight: 600;
            color: #f57224; /* Orange color from your button */
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        .megamenu-list a {
            padding: 6px 0;
            display: block;
            color: #495057;
            text-decoration: none;
            transition: color 0.2s;
            background-color: transparent !important; /* Override Bootstrap hover */
        }

        .megamenu-list a:hover {
            color: #0d6efd;
        }

        .megamenu-list a .fa-solid {
            width: 20px;
            text-align: center;
        }

        .megamenu-feature {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 0.375rem;
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .text-purple { color: #6f42c1 !important; }

        
        /* Hanya target mega menu, jangan global .btn */
        .mega-dropdown { position: static; }
        .mega-menu {
        width: 100%;
        border-top: 3px solid #0d6efd;
        background: #fff;
        position: absolute; /* biar dropdown muncul di atas konten */
        z-index: 1050; /* cukup tinggi tapi tidak mengganggu nav lain */
        }

        /* Tombol Shopee agar selalu terlihat */
        .btn-shopee {
            position: relative;
            z-index: 2000; /* lebih tinggi dari mega menu (1050) */
            background-color: #ff5722; /* orange */
            color: #fff;
            border: none;
        }

        .btn-shopee:hover {
            background-color: #e64a19;
            color: #fff;
        }


        /* ========================================================== */
        /* MODAL CEK ONGKIR BARU - DESAIN KEREN                       */
        /* ========================================================== */
        #cekOngkirModal .modal-content {
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        #cekOngkirModal .modal-header {
            background: linear-gradient(135deg, #ff6b6b, #ff4757);
            color: white;
            border-bottom: none;
            padding: 2rem;
            position: relative;
            border-radius: 1.5rem 1.5rem 0 0;
        }

        #cekOngkirModal .modal-header::before {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            bottom: -20px;
            left: -20px;
            background: url('https://placehold.co/800x600/ffffff/ffffff') no-repeat;
            opacity: 0.1;
            z-index: -1;
        }

        #cekOngkirModal .modal-title {
            font-weight: 700;
            font-size: 1.5rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        #cekOngkirModal .btn-close {
            filter: invert(1);
            opacity: 0.8;
        }

        #cekOngkirModal .modal-body {
            padding: 2.5rem;
            background-color: #f8f9fa;
        }

        #cekOngkirModal .form-control,
        #cekOngkirModal .form-select {
            border: 1px solid #dee2e6;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        #cekOngkirModal .form-control:focus,
        #cekOngkirModal .form-select:focus {
            border-color: #ff4757;
            box-shadow: 0 0 0 0.25rem rgba(255, 71, 87, 0.25);
        }

        #cekOngkirModal .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        #cekOngkirModal .btn-danger.btn-lg {
            border-radius: 2rem;
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ff4757, #ff6b6b);
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(255, 71, 87, 0.4);
        }

        #cekOngkirModal .btn-danger.btn-lg:hover {
            background: linear-gradient(135deg, #ff6b6b, #ff4757);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 71, 87, 0.5);
        }

        #cekOngkirModal .text-center h1 {
            color: #343a40;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        #cekOngkirModal .text-muted {
            font-size: 0.9rem;
        }

        #cekOngkirModal .modal-footer {
            border-top: none;
            padding: 1.5rem 2.5rem;
            background-color: #f8f9fa;
        }
        
        .autocomplete-results {
            position: absolute;
            background-color: white;
            border: 1px solid #ddd;
            border-top: none;
            z-index: 1055;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

         /* 3. Style tambahan untuk hasil autocomplete */
        .autocomplete-results {
            position: absolute; z-index: 1000; width: 100%;
            max-height: 250px; overflow-y: auto; background-color: #fff;
            border: 1px solid #ddd; border-top: none;
            border-radius: 0 0 0.5rem 0.5rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .autocomplete-item { padding: 10px 15px; cursor: pointer; }
        .autocomplete-item:hover { background-color: #f1f5f9; }
        .modal-backdrop { z-index: 40; }
        .modal { z-index: 50; }

        /* ========================================================== */
        /* PERBAIKAN TAMPILAN AUTOCOMPLETE ALAMAT                       */
        /* ========================================================== */
        .autocomplete-container {
            position: relative;
        }

        .autocomplete-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: all 0.2s ease-in-out; 
            border-radius: 0.5rem; 
            border: 1px solid transparent; 
        }

        .autocomplete-item:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); 
        }

        /* ========================================================== */
        /* END MODAL CEK ONGKIR BARU                                  */
        /* ========================================================== */
    </style>

    @stack('styles')
</head>
<body style="font-family: 'Poppins', sans-serif;">

<!-- =================================================================== -->
<!-- HEADER / NAVBAR -->
<!-- =================================================================== -->
<header>
   <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                <img src="https://tokosancaka.biz.id/storage/uploads/sancaka.png" alt="Sancaka Express Logo" style="max-height: 40px;" class="me-2">
                <strong>SANCAKA EXPRESS</strong>
            </a>

            <!-- Tombol toggle (harus di dalam .container) -->
      <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" 
              data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" 
              aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('/') ? 'active fw-bold' : '' }}" href="{{ url('/') }}">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#layanan">Layanan</a>
                    </li>
                    
                    <li class="nav-item dropdown mega-dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="megaMenuServices" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Jasa Kami
                        </a>
                        <div class="dropdown-menu mega-menu p-4" aria-labelledby="megaMenuServices">
                            <div class="row g-4">
                                <!-- Perizinan Properti -->
                                <div class="col-md-3 col-6">
                                    <h6 class="fw-bold text-primary mb-2">
                                        <i class="fas fa-home me-1"></i> Perizinan Properti
                                    </h6>
                                    <p class="small mb-2">
                                        <i class="fas fa-money-bill-wave text-primary me-1"></i>
                                        <span class="text-primary">Mulai dari Rp 7.000.000</span>
                                    </p>
                                    <ul class="list-unstyled">
                                        <li><a href="#" class="dropdown-item">Sertifikat Tanah</a></li>
                                        <li><a href="#" class="dropdown-item">IMB</a></li>
                                        <li><a href="#" class="dropdown-item">PBG</a></li>
                                        <li><a href="#" class="dropdown-item">SLF</a></li>
                                    </ul>
                                </div>

                                <!-- Perizinan Produk -->
                                <div class="col-md-3 col-6">
                                    <h6 class="fw-bold text-success mb-2">
                                        <i class="fas fa-box-open me-1"></i> Perizinan Produk
                                    </h6>
                                    <p class="small mb-2">
                                        <i class="fas fa-money-bill-wave text-success me-1"></i>
                                        <span class="text-success">Mulai dari Rp 8.000.000</span>
                                    </p>
                                    <ul class="list-unstyled">
                                        <li><a href="#" class="dropdown-item">BPOM</a></li>
                                        <li><a href="#" class="dropdown-item">Sertifikasi Halal</a></li>
                                        <li><a href="#" class="dropdown-item">PIRT</a></li>
                                    </ul>
                                </div>

                                <!-- Kekayaan Intelektual -->
                                <div class="col-md-3 col-6">
                                    <h6 class="fw-bold text-warning mb-2">
                                        <i class="fas fa-lightbulb me-1"></i> Kekayaan Intelektual
                                    </h6>
                                    <p class="small mb-2">
                                        <i class="fas fa-money-bill-wave text-warning me-1"></i>
                                        <span class="text-warning">Mulai dari Rp 5.000.000</span>
                                    </p>
                                    <ul class="list-unstyled">
                                        <li><a href="#" class="dropdown-item">Pendaftaran Merk Dagang</a></li>
                                        <li><a href="#" class="dropdown-item">Pendaftaran Paten</a></li>
                                    </ul>
                                </div>

                                <!-- Legalitas Usaha & Website -->
                                <div class="col-md-3 col-6">
                                    <h6 class="fw-bold text-danger mb-2">
                                        <i class="fas fa-gavel me-1"></i> Legalitas Usaha & Website
                                    </h6>
                                    <p class="small mb-2">
                                        <i class="fas fa-money-bill-wave text-danger me-1"></i>
                                        <span class="text-danger">Mulai dari Rp 10.000.000</span>
                                    </p>
                                    <ul class="list-unstyled">
                                        <li><a href="#" class="dropdown-item">Pendirian CV</a></li>
                                        <li><a href="#" class="dropdown-item">Pendirian PT</a></li>
                                        <li><a href="#" class="dropdown-item">Pendirian Yayasan</a></li>
                                        <li><a href="#" class="dropdown-item">Website Profesional</a></li>
                                    </ul>
                                </div>
                            </div>

                            <hr>

                            <div class="row g-4">
                                <!-- Konstruksi & RAB -->
                                <div class="col-md-3 col-6">
                                    <h6 class="fw-bold text-info mb-2">
                                        <i class="fas fa-hard-hat me-1"></i> Konstruksi & RAB
                                    </h6>
                                    <p class="small mb-2">
                                        <i class="fas fa-money-bill-wave text-info me-1"></i>
                                        <span class="text-info">Mulai Rp 10K - 20K</span>
                                    </p>
                                    <ul class="list-unstyled">
                                        <li><a href="#" class="dropdown-item">Hitung RAB Akurat</a></li>
                                        <li><a href="#" class="dropdown-item">Bangun Rumah Impian</a></li>
                                        <li><a href="#" class="dropdown-item">Bangun Toko Modern</a></li>
                                    </ul>
                                </div>

                                <!-- Pengeboran Sumur & SIPA -->
                                <div class="col-md-3 col-6">
                                    <h6 class="fw-bold text-secondary mb-2">
                                        <i class="fas fa-tint me-1"></i> Pengeboran Sumur & SIPA
                                    </h6>
                                    <p class="small mb-2">
                                        <i class="fas fa-money-bill-wave text-secondary me-1"></i>
                                        <span class="text-secondary">Mulai dari Rp 30.000.000</span>
                                    </p>
                                    <ul class="list-unstyled">
                                        <li><a href="#" class="dropdown-item">Pengeboran Sumur Dalam</a></li>
                                        <li><a href="#" class="dropdown-item">Pengurusan SIPA</a></li>
                                    </ul>
                                </div>

                                <!-- Jasa Ekspedisi -->
                                <div class="col-md-3 col-6">
                                    <h6 class="fw-bold text-dark mb-2">
                                        <i class="fas fa-truck me-1"></i> Jasa Ekspedisi
                                    </h6>
                                    <p class="small mb-2">
                                        <i class="fas fa-money-bill-wave text-dark me-1"></i>
                                        <span class="text-dark">Mulai Rp 2.424</span>
                                    </p>
                                    <ul class="list-unstyled">
                                        <li><a href="#" class="dropdown-item">J&T Express</a></li>
                                        <li><a href="#" class="dropdown-item">JNE</a></li>
                                        <li><a href="#" class="dropdown-item">Pos Indonesia</a></li>
                                        <li><a href="#" class="dropdown-item">Indah Cargo</a></li>
                                        <li><a href="#" class="dropdown-item">SPX Express</a></li>
                                    </ul>
                                </div>

                                <!-- Desain Arsitek -->
                                <div class="col-md-3 col-6">
                                    <h6 class="fw-bold text-purple mb-2">
                                        <i class="fas fa-pencil-ruler me-1"></i> Desain Arsitek
                                    </h6>
                                    <p class="small mb-2">
                                        <i class="fas fa-money-bill-wave text-purple me-1"></i>
                                        <span class="text-purple">Mulai dari Rp 6.000.000</span>
                                    </p>
                                    <ul class="list-unstyled">
                                        <li><a href="#" class="dropdown-item">Desain Denah Bangunan</a></li>
                                        <li><a href="#" class="dropdown-item">Gambar Arsitektur 2D</a></li>
                                        <li><a href="#" class="dropdown-item">Visualisasi 3D Realistis</a></li>
                                        <li><a href="#" class="dropdown-item">Perencanaan Ruang Optimal</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </li>
                    
                    <li class="nav-item ms-2">
                        <a class="btn btn-shopee d-flex align-items-center" href="{{ url('') }}/etalase">
                        <i class="fas fa-shopping-cart me-2"></i> Etalase
                        </a>
                    </li>
                    
                    <li class="nav-item ms-2">
                        <a class="btn btn-success d-flex align-items-center" href="{{ url('') }}/blog">
                        <i class="fas fa-blog me-2"></i> Blog
                        </a>
                    </li>
                    
                    <!-- MEGA MENU REKANAN (START) -->
                    <li class="nav-item dropdown mega-dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="rekananDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Rekanan
                        </a>
                        <div class="dropdown-menu dropdown-megamenu shadow" aria-labelledby="rekananDropdown">
                            @php
                                $rekanan = [
                                    'JNE' => 'fa-truck-fast',
                                    'J&T EXPRESS' => 'fa-truck',
                                    'J&T CARGO' => 'fa-box',
                                    'WAHANA EXPRESS' => 'fa-boxes-stacked',
                                    'POS INDONESIA' => 'fa-envelope',
                                    'SAP EXPRESS' => 'fa-paper-plane',
                                    'INDAH CARGO' => 'fa-cube',
                                    'LION PARCEL' => 'fa-plane-departure',
                                    'ID EXPRESS' => 'fa-truck-front',
                                    'SPX EXPRESS' => 'fa-truck-arrow-right',
                                    'NCS' => 'fa-road',
                                    'SENTRAL CARGO' => 'fa-truck-ramp-box',
                                    'SANCAKA EXPRESS' => 'fa-bolt'
                                ];
                                $kurirPopuler = ['JNE', 'J&T EXPRESS', 'SPX EXPRESS', 'POS INDONESIA', 'ID EXPRESS'];
                                $kargoLogistik = ['J&T CARGO', 'INDAH CARGO', 'SENTRAL CARGO', 'LION PARCEL', 'WAHANA EXPRESS'];
                                $lainnya = ['SAP EXPRESS', 'NCS'];
                            @endphp
                            <div class="row">
                                <!-- Column 1: Kurir Populer -->
                                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                                    <h5 class="megamenu-heading">Kurir Populer</h5>
                                    <ul class="list-unstyled megamenu-list">
                                        @foreach($rekanan as $name => $icon)
                                            @if(in_array($name, $kurirPopuler))
                                                <li><a href="#"><i class="fa-solid {{ $icon }} me-2"></i>{{ $name }}</a></li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>

                                <!-- Column 2: Kargo & Logistik -->
                                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                                    <h5 class="megamenu-heading">Kargo & Logistik</h5>
                                    <ul class="list-unstyled megamenu-list">
                                        @foreach($rekanan as $name => $icon)
                                            @if(in_array($name, $kargoLogistik))
                                                <li><a href="#"><i class="fa-solid {{ $icon }} me-2"></i>{{ $name }}</a></li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>

                                <!-- Column 3: Lainnya -->
                                <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
                                    <h5 class="megamenu-heading">Lainnya</h5>
                                    <ul class="list-unstyled megamenu-list">
                                        @foreach($rekanan as $name => $icon)
                                            @if(in_array($name, $lainnya))
                                                <li><a href="#"><i class="fa-solid {{ $icon }} me-2"></i>{{ $name }}</a></li>
                                            @endif
                                        @endforeach
                                        <li><hr class="my-2"></li>
                                        <li><a href="#"><i class="fa-solid {{ $rekanan['SANCAKA EXPRESS'] }} me-2"></i>SANCAKA EXPRESS</a></li>
                                    </ul>
                                </div>

                                <!-- Column 4: Featured Service -->
                                <div class="col-lg-3 col-md-6">
                                    <div class="megamenu-feature">
                                        <i class="fas fa-barcode fa-3x text-primary mb-3"></i>
                                        <h6 class="fw-bold">Input Resi SPX Cepat</h6>
                                        <p class="small text-muted">Daftarkan paket SPX Express Anda dengan mudah melalui scan barcode atau input manual.</p>
                                        <a href="{{ route('scan.spx.show') }}" class="btn btn-primary btn-sm">Mulai Sekarang</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    <!-- MEGA MENU REKANAN (END) -->

                    <li class="nav-item">
                        <a class="nav-link" href="#kontak">Kontak</a>
                    </li>
                </ul>

                <!-- Tombol & Akun Gabungan -->
                <div class="d-lg-flex align-items-center mt-3 mt-lg-0 ms-lg-3">
                    <div class="dropdown">
                        <button class="btn btn-danger dropdown-toggle fw-bold" type="button" id="mainDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-user me-1"></i> Login / Order
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="mainDropdown">
                
                            {{-- Bagian Akun --}}
                            <li>
                                <a class="dropdown-item" href="{{ route('login') }}">
                                    <i class="fa-solid fa-right-to-bracket me-2"></i> Login
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('register') }}">
                                    <i class="fa-solid fa-user-plus me-2"></i> Daftar Akun Baru
                                </a>
                            </li>
                
                            <li><hr class="dropdown-divider"></li>
                
                            {{-- Bagian Order --}}
                            <li>
                                <a class="dropdown-item" href="{{ route('pesanan.public.create') }}">
                                    <i class="fas fa-shipping-fast me-2"></i> Order via Sancaka Express
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('scan.spx.show') }}">
                                    <i class="fas fa-barcode me-2"></i> Input Resi SPX Express
                                </a>
                            </li>
                
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>

<main>
    <!-- Konten halaman spesifik akan dimuat di sini -->
    @yield('content')
</main>

<!-- =================================================================== -->
<!-- FOOTER -->
<!-- =================================================================== -->
<footer class="text-white pt-5 pb-4" style="background-color: #1a253c;">
    <div class="container text-center text-md-start">
        <div class="row text-center text-md-start">

            <!-- Kolom Sancaka Express -->
            <div class="col-md-3 col-lg-3 col-xl-3 mx-auto mt-3">
                <h5 class="text-uppercase mb-4 fw-bold text-white">Sancaka Express</h5>
                <p class="text-white-50">Solusi pengiriman terpercaya untuk semua kebutuhan personal dan bisnis Anda. Cepat, aman, dan dapat diandalkan.</p>
                <img src="https://tokosancaka.com/storage/uploads/sectigo.png" alt="Sectigo Secure" style="max-width: 120px; margin-top: 15px;">
            </div>

            <!-- Kolom Navigasi -->
            <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mt-3">
                <h5 class="text-uppercase mb-4 fw-bold text-white">Navigasi</h5>
                <p><a href="#" class="text-white-50 text-decoration-none">Layanan</a></p>
                <p><a href="#" class="text-white-50 text-decoration-none">Rekanan</a></p>
                <p><a href="#" class="text-white-50 text-decoration-none">Testimoni</a></p>
            </div>

            <!-- Kolom Layanan -->
            <div class="col-md-3 col-lg-2 col-xl-2 mx-auto mt-3">
                 <h5 class="text-uppercase mb-4 fw-bold text-white">Layanan</h5>
                <p><a href="#" class="text-white-50 text-decoration-none">Reguler & COD</a></p>
                <p><a href="#" class="text-white-50 text-decoration-none">Cargo</a></p>
                <p><a href="#" class="text-white-50 text-decoration-none">Pengiriman Motor</a></p>
            </div>

            <!-- Kolom Hubungi Kami -->
            <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mt-3">
                <h5 class="text-uppercase mb-4 fw-bold text-white">Hubungi Kami</h5>
                <p class="text-white-50"><i class="fas fa-home me-3"></i>Jl. Dr. Wahidin No.18A RT.22 RW.05 Kel Ketanggi Kec.Ngawi Kab.Ngawi Jawa Timur 63211</p>
                <p class="text-white-50"><i class="fas fa-envelope me-3"></i>kontak@tokosancaka.com</p>
                <p class="text-white-50"><i class="fas fa-phone me-3"></i>+62 85 745 808 809</p>
            </div>
        </div>

        <hr class="my-3">

        <!-- Bagian Copyright & Social Media -->
        <div class="row align-items-center">
            <div class="col-md-7 col-lg-8">
                <p class="text-center text-md-start text-white-50">
                    &copy; {{ date('Y') }} Sancaka Express & Toko Sancaka All Rights Reserved.
                </p>
            </div>
            <div class="col-md-5 col-lg-4">
                <div class="text-center text-md-end">
                    <ul class="list-unstyled list-inline">
                        <li class="list-inline-item">
                            <a href="#" class="btn-floating btn-sm text-white" style="font-size: 23px;"><i class="fab fa-facebook"></i></a>
                        </li>
                         <li class="list-inline-item">
                            <a href="#" class="btn-floating btn-sm text-white" style="font-size: 23px;"><i class="fab fa-instagram"></i></a>
                        </li>
                         <li class="list-inline-item">
                            <a href="#" class="btn-floating btn-sm text-white" style="font-size: 23px;"><i class="fab fa-twitter"></i></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</footer>
    
<!-- Awal Kode Modal Cek Ongkir yang Didesain Ulang -->
<div class="modal fade" id="cekOngkirModal" tabindex="-1" aria-labelledby="cekOngkirModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <!-- Menggunakan class Tailwind untuk styling, bukan class default Bootstrap -->
        <div class="modal-content bg-slate-50 text-slate-800 rounded-2xl shadow-2xl border-0">

            <!-- Header -->
            <div class="modal-header border-b-0 p-6 text-center relative">
                <div class="w-full">
                    <div class="mx-auto bg-red-100 text-red-600 w-16 h-16 rounded-full flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-truck"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                    </div>
                    <h5 class="modal-title text-2xl font-bold text-slate-900" id="cekOngkirModalLabel">
                        Cek Ongkos Kirim
                    </h5>
                    <p class="text-slate-500 text-sm mt-1">Didukung oleh <span class="font-semibold text-red-500">Sancaka Express</span></p>
                </div>
                <button type="button" class="btn-close absolute top-4 right-4 text-slate-400 hover:text-slate-600" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body p-6 pt-0">
                <form id="shipping-form">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                        <!-- Alamat Asal -->
                        <div class="md:col-span-1 relative">
                            <label for="origin" class="block text-sm font-semibold text-slate-600 mb-2">Alamat Asal</label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <input type="text" id="origin" name="origin_text" class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition" placeholder="Kecamatan/Kelurahan/Kodepos..." required>
                            </div>
                            <input type="hidden" id="origin_id" name="origin_id">
                            <input type="hidden" id="origin_subdistrict_id" name="origin_subdistrict_id">
                            <div id="origin-results" class="autocomplete-results d-none"></div> <!-- Menggunakan d-none dari bootstrap agar JS Anda tetap bekerja -->
                        </div>

                        <!-- Alamat Tujuan -->
                        <div class="md:col-span-1 relative">
                            <label for="destination" class="block text-sm font-semibold text-slate-600 mb-2">Alamat Tujuan</label>
                             <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                     <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <input type="text" id="destination" name="destination_text" class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition" placeholder="Kecamatan/Kelurahan/Kodepos..." required>
                            </div>
                            <input type="hidden" id="destination_id" name="destination_id">
                            <input type="hidden" id="destination_subdistrict_id" name="destination_subdistrict_id">
                            <div id="destination-results" class="autocomplete-results d-none"></div> <!-- Menggunakan d-none dari bootstrap -->
                        </div>

                        <!-- Berat -->
                        <div class="md:col-span-2">
                            <label for="weight" class="block text-sm font-semibold text-slate-600 mb-2">Berat</label>
                            <div class="relative">
                                <input type="number" id="weight" name="weight" class="w-full pr-14 pl-4 py-2.5 bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition" placeholder="Contoh: 1000" min="1" required>
                                <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-sm text-slate-500">gram</span>
                            </div>
                        </div>

                        <!-- Dimensi -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-600 mb-2">Dimensi Paket (Opsional)</label>
                            <div class="grid grid-cols-3 gap-4">
                                <input type="number" id="length" name="length" placeholder="Panjang (cm)" class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition">
                                <input type="number" id="width" name="width" placeholder="Lebar (cm)" class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition">
                                <input type="number" id="height" name="height" placeholder="Tinggi (cm)" class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition">
                            </div>
                            <small class="text-slate-500 mt-2 block">Isi jika ongkir dihitung berdasarkan volume.</small>
                        </div>

                        <!-- Nilai Barang & Asuransi -->
                        <div class="md:col-span-2 p-4 bg-slate-100 rounded-lg">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                <div class="flex-grow">
                                    <label for="item_value" class="block text-sm font-semibold text-slate-600 mb-2">Nilai Barang (Opsional)</label>
                                    <div class="relative">
                                       <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm text-slate-500">Rp</span>
                                        <input type="number" id="item_value" name="item_value" placeholder="500000" class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition">
                                    </div>
                                </div>
                                <div class="flex-shrink-0 pt-2 sm:pt-7">
                                    <div class="relative flex items-center">
                                        <!-- Checkbox ini hanya diubah tampilannya, fungsionalitasnya tetap sama -->
                                        <input type="checkbox" id="insurance" name="insurance" class="appearance-none w-10 h-6 bg-slate-300 rounded-full cursor-pointer transition-colors duration-300 checked:bg-red-500 peer">
                                        <label for="insurance" class="absolute left-1 top-1/2 -translate-y-1/2 w-4 h-4 bg-white rounded-full cursor-pointer transition-transform duration-300 peer-checked:translate-x-4"></label>
                                        <label for="insurance" class="ml-3 text-sm font-medium text-slate-700 cursor-pointer">Gunakan Asuransi</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Submit -->
                    <div class="mt-8">
                        <button type="submit" class="w-full bg-red-600 text-white font-bold py-3.5 px-4 rounded-lg shadow-lg hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 transition-all duration-300 flex items-center justify-center gap-2" id="submit-button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            Cek Ongkir Sekarang
                        </button>
                    </div>
                </form>

                <!-- Hasil akan ditampilkan di sini -->
                <div id="cost-results-container" class="mt-6"></div>

            </div>
            <!-- Footer tidak saya sertakan karena tidak ada di kode awal Anda, namun bisa ditambahkan jika perlu -->
        </div>
    </div>
</div>
    
<!-- Modal Tutorial Onboarding -->
<div class="modal fade" id="tutorialModal" tabindex="-1" aria-labelledby="tutorialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <!-- Header -->
            <div class="modal-header bg-gradient text-white rounded-top-4" style="background: linear-gradient(90deg, #ff4d4d, #b30000);">
                <h5 class="modal-title fw-bold" id="tutorialModalLabel">
                    <span class="text-dark fw-semibold">🎉 Selamat Datang di Sancaka Express</span>!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            
            <!-- Body -->
            <div class="modal-body text-center py-4">
                <p class="fs-5 fw-semibold text-dark">🚀 Yuk Mulai Kirim Barang!</p>
                <p class="text-secondary mb-4">
                    Untuk mulai menggunakan layanan kami, silakan klik tombol <span class="badge bg-danger px-2 py-1">Login / Order</span> di pojok kanan atas.
                </p>
                <p class="text-muted small">💼 Kami siap bantu kiriman Anda dengan cepat, aman, dan terpercaya.</p>
            </div>

            <!-- Footer -->
            <div class="modal-footer justify-content-center border-0 pb-4">
                <button type="button" class="btn btn-lg btn-danger rounded-pill px-4 shadow-sm" data-bs-dismiss="modal">
                    Saya Mengerti
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =================================================================== -->
<!-- JAVASCRIPTS -->
<!-- =================================================================== -->
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap Bundle (sudah termasuk Popper.js) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>



<!-- JS khusus halaman (jika ada) -->
@stack('scripts')
    
@if (!empty($__debug))
<div class="bg-yellow-100 text-yellow-800 p-4 text-sm border-t border-yellow-300 mt-8">
    <strong>🔧 DEBUG INFO:</strong>
    <pre class="whitespace-pre-wrap text-xs">{{ print_r($__debug, true) }}</pre>
</div>
@endif

<!-- Custom & Init Scripts -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        
        // --- INISIALISASI ---
        if (!localStorage.getItem('tutorial_shown')) {
            const tutorialModal = new bootstrap.Modal(document.getElementById('tutorialModal'));
            tutorialModal.show();
            localStorage.setItem('tutorial_shown', 'true');
        }

    });
</script>

</body>
</html>