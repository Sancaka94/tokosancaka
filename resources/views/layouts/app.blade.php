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
    
    {{-- Tambahkan CSS untuk Select2 jika Anda menggunakannya --}}
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />


    <!-- Custom CSS untuk Halaman Depan -->

    <link rel="stylesheet" href="{{ asset('public/assets/css/home-style.css') }}">

    

    <style>

    

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

/* MODAL CEK ONGKIR BARU - DESAIN KEREN             */

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



/* ========================================================== */

/* PERBAIKAN TAMPILAN AUTOCOMPLETE ALAMAT             */

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

/* END MODAL CEK ONGKIR BARU                     */

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



            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">

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



                <!-- Tombol & Akun -->

                <div class="d-lg-flex align-items-center mt-3 mt-lg-0 ms-lg-3">

                    <!-- Dropdown Akun -->

                    <div class="dropdown me-2">

                        <a class="btn btn-outline-primary dropdown-toggle" href="#" id="akunDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">

                            <i class="fa-solid fa-user me-1"></i> Akun

                        </a>

                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="akunDropdown">

                            {{-- Pilihan Login untuk Customer --}}

                            <li>

                                <a class="dropdown-item" href="{{ route('login') }}">

                                    <i class="fa-solid fa-right-to-bracket me-2"></i> Login

                                </a>

                            </li>

                            {{-- Pemisah visual --}}

                            <li><hr class="dropdown-divider"></li>

                            {{-- Pilihan Daftar (tetap untuk Customer) --}}

                            <li>

                                <a class="dropdown-item" href="{{ route('register') }}">

                                    <i class="fa-solid fa-user-plus me-2"></i> Daftar Akun Baru

                                </a>

                            </li>

                        </ul>

                    </div>

                    

                    <!-- Ganti tombol "Order Sekarang" yang lama dengan kode dropdown ini -->

                    <div class="dropdown">

                        <button class="btn btn-danger dropdown-toggle fw-bold" type="button" id="orderDropdown" data-bs-toggle="dropdown" aria-expanded="false">

                            Order Sekarang

                        </button>

                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="orderDropdown">

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

    

    </div> <!-- Penutup .card dari halaman login/register -->

    

    <!-- Modal Cek Ongkir -->



    <div class="modal fade" id="cekOngkirModal" tabindex="-1" aria-labelledby="cekOngkirModalLabel" aria-hidden="true">



        <div class="modal-dialog modal-lg modal-dialog-centered">



            <div class="modal-content">



                <div class="modal-header">



                    <h5 class="modal-title" id="cekOngkirModalLabel"><i class="fa-solid fa-calculator me-2"></i>Formulir Cek Ongkos Kirim</h5>



                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>



                </div>



                <div class="modal-body">



                     <div class="w-full max-w-4xl mx-auto bg-white p-4 p-md-5 rounded">

            <div class="text-center mb-4">

                <h1 class="h3 fw-bold text-dark">Cek Ongkos Kirim</h1>

                <p class="text-muted">Didukung oleh Sancaka Express</p>

            </div>



            <form id="shipping-form" class="space-y-3">

                @csrf

                <!-- Alamat Asal -->

                <div class="mb-3 position-relative">

    <label for="origin" class="form-label">Alamat Asal</label>

    <input type="text" id="origin" name="origin_text" class="form-control" placeholder="Ketik nama Kecamatan/Kelurahan/kodepos..." required>

    <input type="hidden" id="origin_id" name="origin_id">

    <input type="hidden" id="origin_subdistrict_id" name="origin_subdistrict_id">

    <div id="origin-results" class="autocomplete-results d-none"></div>

</div>



                <!-- Alamat Tujuan -->

              <div class="mb-3 position-relative">

    <label for="destination" class="form-label">Alamat Tujuan</label>

    <input type="text" id="destination" name="destination_text" class="form-control" placeholder="Ketik nama Kecamatan/Kelurahan/kodepos..." required>

    <input type="hidden" id="destination_id" name="destination_id">

    <input type="hidden" id="destination_subdistrict_id" name="destination_subdistrict_id">

    <div id="destination-results" class="autocomplete-results d-none"></div>

</div>



                <!-- Berat -->

                <div class="mb-3">

                    <label for="weight" class="form-label">Berat (gram)</label>

                    <input type="number" id="weight" name="weight" class="form-control" placeholder="Contoh: 1000" min="1" required>

                </div>



                <!-- Dimensi -->

                <div class="mb-3">

                    <label class="form-label">Dimensi Paket (cm)</label>

                    <div class="row g-2">

                        <div class="col"><input type="number" id="length" name="length" placeholder="Panjang" class="form-control"></div>

                        <div class="col"><input type="number" id="width" name="width" placeholder="Lebar" class="form-control"></div>

                        <div class="col"><input type="number" id="height" name="height" placeholder="Tinggi" class="form-control"></div>

                    </div>

                    <small class="text-muted">Isi jika ongkir dihitung berdasarkan volume.</small>

                </div>



                <!-- Nilai Barang -->

                <div class="mb-3">

                    <label for="item_value" class="form-label">Nilai Barang (Rp)</label>

                    <input type="number" id="item_value" name="item_value" placeholder="Contoh: 500000" class="form-control">

                    <small class="text-muted d-block">Isi untuk perhitungan biaya asuransi.</small>

                    <div class="form-check mt-2">

                        <input type="checkbox" id="insurance" name="insurance" class="form-check-input">

                        <label for="insurance" class="form-check-label">Gunakan Asuransi</label>

                    </div>

                </div>



                <!-- Tombol -->

                <div class="d-grid">

                    <button type="submit" class="btn btn-danger btn-lg" id="submit-button">Cek Ongkir</button>

                </div>

            </form>



            <div id="cost-results-container" class="mt-4"></div>

        </div>



                </div>



                <div class="modal-footer">



                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kembali</button>





                </div>



            </div>



        </div>



    </div>

    



    <!-- Footer Baru dengan Teks Putih -->



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



                        &copy; 2025 Sancaka Express & Toko Sancaka All Rights Reserved.



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

          Untuk mulai menggunakan layanan kami, silakan klik tombol <span class="badge bg-danger px-2 py-1">Order Sekarang</span> di pojok kanan atas.

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









        <div class="container text-center">

            <p class="mb-0">&copy; {{ date('Y') }} TOKOSANCAKA.COM - DIDUKUNG OLEH CV.SANCAKA KARYA HUTAMA & PT. SANCAKA EMPAT PILAR HUTAMA </p>

        </div>

        

    </footer>

    
<!-- =================================================================== -->
<!-- JAVASCRIPTS - BAGIAN YANG DIPERBAIKI -->
<!-- =================================================================== -->

<!-- 1. Muat SEMUA library vendor terlebih dahulu -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


    <!-- JavaScript Utama -->

    

    @push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    // Cek apakah tutorial sudah ditampilkan sebelumnya

    if (!localStorage.getItem('tutorial_shown')) {

        const tutorialModal = new bootstrap.Modal(document.getElementById('tutorialModal'));

        tutorialModal.show();

        localStorage.setItem('tutorial_shown', 'true'); // Simpan agar tidak muncul lagi

    }

});

</script>

@endpush





    <script>



        document.addEventListener("DOMContentLoaded", function() {



            const apiProxyPath = 'app/api/get_wilayah.php'; // Path ke API lokal Anda







            // --- FUNGSI AUTOCOMPLETE ---



            function setupAutocomplete(inputId, hiddenId, resultsId) {



                const searchInput = document.getElementById(inputId);



                const hiddenInput = document.getElementById(hiddenId);



                const resultsContainer = document.getElementById(resultsId);







                searchInput.addEventListener('input', function() {



                    const term = this.value;



                    if (term.length < 3) { resultsContainer.style.display = 'none'; return; }







                    fetch(`${apiProxyPath}?term=${encodeURIComponent(term)}`)



                        .then(response => response.json())



                        .then(data => {



                            resultsContainer.innerHTML = '';



                            resultsContainer.style.display = 'block';



                            if (data && data.length > 0) {



                                data.forEach(item => {



                                    const resultDiv = document.createElement('div');



                                    resultDiv.classList.add('autocomplete-item');



                                    resultDiv.textContent = item.label;



                                    resultDiv.addEventListener('click', function() {



                                        searchInput.value = item.label;



                                        hiddenInput.value = item.value;



                                        resultsContainer.style.display = 'none';



                                    });



                                    resultsContainer.appendChild(resultDiv);



                                });



                            } else { resultsContainer.innerHTML = '<div class="p-2">Lokasi tidak ditemukan</div>'; }



                        }).catch(error => {



                            console.error('Autocomplete Error:', error);



                            resultsContainer.innerHTML = '<div class="p-2 text-danger">Gagal mengambil data</div>';



                        });



                });



            }







            setupAutocomplete('asal_search', 'asal_id', 'asal_results');



            setupAutocomplete('tujuan_search', 'tujuan_id', 'tujuan_results');



            

            // Sembunyikan hasil jika klik di luar



            document.addEventListener('click', function(e) {



                if (!e.target.closest('.autocomplete-container')) {



                    document.getElementById('asal_results').style.display = 'none';



                    document.getElementById('tujuan_results').style.display = 'none';



                }



            });







            // --- LOGIKA BARU UNTUK JENIS LAYANAN MOTOR ---



            const motorDetailsDiv = document.getElementById('motorDetails');



            const regulerDetailsDiv = document.getElementById('regulerDetails');



            const ekspedisiSelect = document.getElementById('pilihanEkspedisi');



            const allEkspedisiOptions = Array.from(ekspedisiSelect.options); // Simpan semua opsi awal







            document.querySelectorAll('input[name="jenisLayanan"]').forEach(radio => {



                radio.addEventListener('change', function() {



                    if (this.value === 'motor') {



                        motorDetailsDiv.classList.remove('d-none');



                        regulerDetailsDiv.classList.add('d-none');



                        // Filter ekspedisi



                        const motorEkspedisi = ['JNT_CARGO', 'INDAH', 'SANCAKA'];



                        ekspedisiSelect.innerHTML = ''; // Kosongkan



                        allEkspedisiOptions.forEach(option => {



                            if (motorEkspedisi.includes(option.value) || option.value === 'Semua Rekanan') {



                                ekspedisiSelect.add(option.cloneNode(true));



                            }



                        });







                    } else {



                        motorDetailsDiv.classList.add('d-none');



                        regulerDetailsDiv.classList.remove('d-none');



                        // Kembalikan semua opsi ekspedisi



                        ekspedisiSelect.innerHTML = '';



                        allEkspedisiOptions.forEach(option => {



                            ekspedisiSelect.add(option.cloneNode(true));



                        });



                    }



                });



            });











            // --- LOGIKA TOMBOL "CEK SEKARANG" DENGAN FORMAT BARU ---



            document.getElementById('btnCekOngkir').addEventListener('click', function() {



                // Kumpulkan semua data dari form



                const asal = document.getElementById('asal_search').value;



                const tujuan = document.getElementById('tujuan_search').value;



                const jenisLayananRadio = document.querySelector('input[name="jenisLayanan"]:checked');



                const jenisLayanan = jenisLayananRadio ? jenisLayananRadio.value : 'Tidak dipilih';



                const ekspedisi = document.getElementById('pilihanEkspedisi').value;



                

                // Cek apakah lokasi sudah diisi



                if(!asal || !tujuan) {



                    alert('Harap lengkapi Lokasi Asal dan Tujuan terlebih dahulu.');



                    return;



                }







                // --- DESAIN PESAN BARU ---



                let pesan = `Halo Sancaka Express! 🚚\n\n`;



                pesan += `Saya ingin melakukan pengecekan ongkos kirim dengan rincian sebagai berikut:\n\n`;



                

                pesan += `📦 *_DETAIL PENGIRIMAN_*\n`;



                pesan += `   •  *Asal:* ${asal}\n`;



                pesan += `   •  *Tujuan:* ${tujuan}\n`;



                pesan += `   •  *Ekspedisi:* ${ekspedisi}\n\n`;







                pesan += `📝 *_DETAIL LAYANAN_*\n`;



                pesan += `   •  *Jenis:* ${jenisLayanan.toUpperCase()}\n`;







                // Tambahkan detail motor atau paket reguler



                if (jenisLayanan === 'motor') {



                    const motor_cc = document.getElementById('motor_cc').value;



                    const motor_tipe = document.getElementById('motor_tipe').value;



                    pesan += `   •  *CC Motor:* ${motor_cc || '-'}\n`;



                    pesan += `   •  *Tipe Motor:* ${motor_tipe}\n\n`;







                    // Ambil data checklist



                    const checklistItems = [];



                    document.querySelectorAll('#motorChecklistDetails .form-check-input:checked').forEach(checkbox => {



                        checklistItems.push(checkbox.value);



                    });



                    

                    pesan += `📋 *_KELENGKAPAN_*\n`;



                    if (checklistItems.length > 0) {



                        checklistItems.forEach(item => {



                            pesan += `   ✅  ${item}\n`;



                        });



                    } else {



                        pesan += `   (Tidak ada kelengkapan yang dipilih)\n`;



                    }







                } else {



                    const berat = document.getElementById('berat').value;



                    const koli = document.getElementById('koli').value;



                    const panjang = document.getElementById('panjang').value || '0';



                    const lebar = document.getElementById('lebar').value || '0';



                    const tinggi = document.getElementById('tinggi').value || '0';



                    pesan += `   •  *Berat:* ${berat} gram\n`;



                    pesan += `   •  *Jumlah Koli:* ${koli} pcs\n`;



                    pesan += `   •  *Dimensi:* ${panjang}x${lebar}x${tinggi} cm\n`;



                }







                pesan += `\n-----------------------------------\n`;



                pesan += `Mohon informasinya, terima kasih. 🙏`;







                // Buat URL WhatsApp



                const nomorWA = '6285745808809'; // Nomor Anda dalam format internasional



                const urlWA = `https://wa.me/${nomorWA}?text=${encodeURIComponent(pesan)}`;







                // Buka di tab baru



                window.open(urlWA, '_blank');



            });





    </script>

    

    <!-- jQuery (wajib jika pakai plugin lain yang perlu jQuery) -->

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>



<!-- Bootstrap Bundle (sudah termasuk Popper.js) -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



<!-- SweetAlert2 jika diperlukan -->

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



<!-- Optional: Select2 (jika Anda pakai plugin select2 dropdown) -->

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>



<!-- Optional: Aktifkan dropdown & plugin lainnya -->

<script>

    document.addEventListener("DOMContentLoaded", function () {

        // Aktifkan semua dropdown Bootstrap (Rekanan, Akun, dll)

        const dropdownElements = document.querySelectorAll('.dropdown-toggle');

        dropdownElements.forEach(function (dropdownToggleEl) {

            new bootstrap.Dropdown(dropdownToggleEl);

        });



        // Optional: aktifkan Select2 jika ada

        $('.select2').select2({

            theme: 'bootstrap-5'

        });

    });

</script>



    

    <!-- JS khusus halaman (jika ada) -->

    @stack('scripts')

    

    @if (!empty($__debug))

    <div class="bg-yellow-100 text-yellow-800 p-4 text-sm border-t border-yellow-300 mt-8">

        <strong>🔧 DEBUG INFO:</strong>

        <pre class="whitespace-pre-wrap text-xs">{{ print_r($__debug, true) }}</pre>

    </div>

@endif



</body>

</html>

