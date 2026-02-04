<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="M5GwyjoDoCcRA93IrehnwMAWLPXZPP2HNPMYU8pnIk8" />
    <title>@yield('title', 'Sancaka Express')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://tokosancaka.com/storage/uploads/sancaka.png">

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vendor CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('public/assets/css/home-style.css') }}">

    <style>
        /*
        ==========================================================
        KONFIGURASI & GAYA TAMBAHAN
        ==========================================================
        */
        body {
            font-family: 'Poppins', sans-serif;
            padding-top: 70px; /* Menambahkan padding untuk navbar fixed-top */
        }

        /* Layout 100% di semua layar */
        .navbar .container {
            max-width: 100% !important;
        }

        /* Perbaikan Tabel agar Responsif */
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /*
        ==========================================================
        MEGA MENU STYLING (BOOTSTRAP)
        ==========================================================
        */
        .dropdown.mega-dropdown {
            position: static;
        }

        .dropdown-menu.dropdown-megamenu {
            width: 100%;
            left: 0;
            right: 0;
            padding: 20px 30px;
            margin-top: 0;
            border-top: 1px solid #eee;
            border-radius: 0 0 0.375rem 0.375rem;
        }

        .megamenu-heading {
            font-size: 1rem;
            font-weight: 600;
            color: #f57224;
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
            background-color: transparent !important;
        }

        .megamenu-list a:hover {
            color: #0d6efd;
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

        .text-purple {
            color: #6f42c1 !important;
        }

        /*
        ==========================================================
        AUTOCOMPLETE STYLING (BOOTSTRAP)
        ==========================================================
        */
        .autocomplete-results {
            position: absolute;
            z-index: 1055; /* Pastikan lebih tinggi dari elemen form lain */
            width: 100%;
            max-height: 250px;
            overflow-y: auto;
            background-color: #fff;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 0.5rem 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .autocomplete-item {
            padding: 10px 15px;
            cursor: pointer;
        }

        .autocomplete-item:hover {
            background-color: #f1f5f9;
        }

        .btn-orange {
  background-color: #fd7e14;
  border-color: #fd7e14;
  color: #fff;
}
.btn-orange:hover {
  background-color: #e36414;
  border-color: #e36414;
  color: #fff;
}

    </style>

    @stack('styles')
</head>
<body>


    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                    <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka Express Logo" style="max-height: 40px;" class="me-2">
                    <strong>SANCAKA EXPRESS</strong>
                </a>

                <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto align-items-lg-center">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('/') ? 'active fw-bold' : '' }}" href="{{ url('/') }}">Beranda</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/about') }}">Tentang Kami</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="#layanan">Layanan</a>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->is('privacy-policy') || request()->is('terms-and-conditions') ? 'active fw-bold' : '' }}"
                            href="#"
                            id="legalDropdown"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                            Legal
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="legalDropdown">
                                <li>
                                    <a class="dropdown-item {{ request()->is('privacy-policy') ? 'active fw-bold' : '' }}"
                                    href="{{ route('privacy.policy') }}">
                                    Privacy Policy
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item {{ request()->is('terms-and-conditions') ? 'active fw-bold' : '' }}"
                                    href="{{ route('terms.conditions') }}">
                                    Terms & Conditions
                                    </a>
                                </li>
                            </ul>
                        </li>



                        <li class="nav-item dropdown mega-dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="megaMenuServices" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Jasa Kami
                            </a>
                            <div class="dropdown-menu dropdown-megamenu p-4" aria-labelledby="megaMenuServices">
                                <div class="row g-4">
                                    <!-- Perizinan Properti -->
                                    <div class="col-md-3 col-6">
                                        <h6 class="fw-bold text-primary mb-2"><i class="fas fa-home me-1"></i> Perizinan Properti</h6>
                                        <p class="small mb-2"><i class="fas fa-money-bill-wave text-primary me-1"></i><span class="text-primary">Mulai dari Rp 7.000.000</span></p>
                                        <ul class="list-unstyled">
                                            <li><a href="#" class="dropdown-item">Sertifikat Tanah</a></li>
                                            <li><a href="#" class="dropdown-item">IMB</a></li>
                                            <li><a href="#" class="dropdown-item">PBG</a></li>
                                            <li><a href="#" class="dropdown-item">SLF</a></li>
                                        </ul>
                                    </div>
                                    <!-- Perizinan Produk -->
                                    <div class="col-md-3 col-6">
                                        <h6 class="fw-bold text-success mb-2"><i class="fas fa-box-open me-1"></i> Perizinan Produk</h6>
                                        <p class="small mb-2"><i class="fas fa-money-bill-wave text-success me-1"></i><span class="text-success">Mulai dari Rp 8.000.000</span></p>
                                        <ul class="list-unstyled">
                                            <li><a href="#" class="dropdown-item">BPOM</a></li>
                                            <li><a href="#" class="dropdown-item">Sertifikasi Halal</a></li>
                                            <li><a href="#" class="dropdown-item">PIRT</a></li>
                                        </ul>
                                    </div>
                                    <!-- Kekayaan Intelektual -->
                                    <div class="col-md-3 col-6">
                                        <h6 class="fw-bold text-warning mb-2"><i class="fas fa-lightbulb me-1"></i> Kekayaan Intelektual</h6>
                                        <p class="small mb-2"><i class="fas fa-money-bill-wave text-warning me-1"></i><span class="text-warning">Mulai dari Rp 5.000.000</span></p>
                                        <ul class="list-unstyled">
                                            <li><a href="#" class="dropdown-item">Pendaftaran Merk Dagang</a></li>
                                            <li><a href="#" class="dropdown-item">Pendaftaran Paten</a></li>
                                        </ul>
                                    </div>
                                    <!-- Legalitas Usaha & Website -->
                                    <div class="col-md-3 col-6">
                                        <h6 class="fw-bold text-danger mb-2"><i class="fas fa-gavel me-1"></i> Legalitas Usaha & Website</h6>
                                        <p class="small mb-2"><i class="fas fa-money-bill-wave text-danger me-1"></i><span class="text-danger">Mulai dari Rp 10.000.000</span></p>
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
                                        <h6 class="fw-bold text-info mb-2"><i class="fas fa-hard-hat me-1"></i> Konstruksi & RAB</h6>
                                        <p class="small mb-2"><i class="fas fa-money-bill-wave text-info me-1"></i><span class="text-info">Mulai Rp 10K - 20K</span></p>
                                        <ul class="list-unstyled">
                                            <li><a href="#" class="dropdown-item">Hitung RAB Akurat</a></li>
                                            <li><a href="#" class="dropdown-item">Bangun Rumah Impian</a></li>
                                            <li><a href="#" class="dropdown-item">Bangun Toko Modern</a></li>
                                        </ul>
                                    </div>
                                    <!-- Pengeboran Sumur & SIPA -->
                                    <div class="col-md-3 col-6">
                                        <h6 class="fw-bold text-secondary mb-2"><i class="fas fa-tint me-1"></i> Pengeboran Sumur & SIPA</h6>
                                        <p class="small mb-2"><i class="fas fa-money-bill-wave text-secondary me-1"></i><span class="text-secondary">Mulai dari Rp 30.000.000</span></p>
                                        <ul class="list-unstyled">
                                            <li><a href="#" class="dropdown-item">Pengeboran Sumur Dalam</a></li>
                                            <li><a href="#" class="dropdown-item">Pengurusan SIPA</a></li>
                                        </ul>
                                    </div>
                                    <!-- Jasa Ekspedisi -->
                                    <div class="col-md-3 col-6">
                                        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-truck me-1"></i> Jasa Ekspedisi</h6>
                                        <p class="small mb-2"><i class="fas fa-money-bill-wave text-dark me-1"></i><span class="text-dark">Mulai Rp 2.424</span></p>
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
                                        <h6 class="fw-bold text-purple mb-2"><i class="fas fa-pencil-ruler me-1"></i> Desain Arsitek</h6>
                                        <p class="small mb-2"><i class="fas fa-money-bill-wave text-purple me-1"></i><span class="text-purple">Mulai dari Rp 6.000.000</span></p>
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

                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-orange d-flex align-items-center" href="{{ url('') }}/etalase">
                                <i class="fas fa-shopping-cart me-2"></i> Etalase
                            </a>
                        </li>

                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-success d-flex align-items-center" href="{{ url('') }}/blog">
                                <i class="fas fa-blog me-2"></i> Blog
                            </a>
                        </li>

                        <!-- MEGA MENU REKANAN -->
                        <li class="nav-item dropdown mega-dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="rekananDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Rekanan
                            </a>
                            <div class="dropdown-menu dropdown-megamenu shadow" aria-labelledby="rekananDropdown">
                                @php
                                    $rekanan = [
                                        'JNE' => 'fa-truck-fast', 'J&T EXPRESS' => 'fa-truck',
                                        'J&T CARGO' => 'fa-box', 'WAHANA EXPRESS' => 'fa-boxes-stacked',
                                        'POS INDONESIA' => 'fa-envelope', 'SAP EXPRESS' => 'fa-paper-plane',
                                        'INDAH CARGO' => 'fa-cube', 'LION PARCEL' => 'fa-plane-departure',
                                        'ID EXPRESS' => 'fa-truck-front', 'SPX EXPRESS' => 'fa-truck-arrow-right',
                                        'NCS' => 'fa-road', 'SENTRAL CARGO' => 'fa-truck-ramp-box',
                                        'SANCAKA EXPRESS' => 'fa-bolt'
                                    ];
                                    $kurirPopuler = ['JNE', 'J&T EXPRESS', 'SPX EXPRESS', 'POS INDONESIA', 'ID EXPRESS'];
                                    $kargoLogistik = ['J&T CARGO', 'INDAH CARGO', 'SENTRAL CARGO', 'LION PARCEL', 'WAHANA EXPRESS'];
                                    $lainnya = ['SAP EXPRESS', 'NCS'];
                                @endphp
                                <div class="row">
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
                                    <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
                                        <h5 class="megamenu-heading">Lainnya</h5>
                                        <ul class="list-unstyled megamenu-list">
                                            @foreach($rekanan as $name => $icon)
                                                @if(in_array($name, $lainnya))
                                                    <li><a href="#"><i class="fa-solid {{ $icon }} me-2"></i>{{ $name }}</a></li>
                                                @endif
                                            @endforeach
                                            <li><hr class="my-2"></li>
                                            <li><a href="#"><i class="fa-solid {{ $rekanan['SANCAKA EXPRESS'] }} me-2"></i><strong>SANCAKA EXPRESS</strong></a></li>
                                        </ul>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="megamenu-feature">
                                            <i class="fas fa-barcode fa-3x text-primary mb-3"></i>
                                            <h6 class="fw-bold">Ekpedisi Lengkap</h6>
                                            <p class="small text-muted">Daftarkan paket Favorit Anda dengan mudah melalui sancaka express dengan auto pickup by Kurir.</p>
                                            <a href="https://tokosancaka.com/buat-pesanan" class="btn btn-primary btn-sm">Kirim Paket Sekarang</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="#kontak">Kontak</a>
                        </li>
                    </ul>

@auth
    @php
        $user = Auth::user();
        // Pastikan role lowercase agar tidak error saat pengecekan (misal: "Agent" jadi "agent")
        $userRole = strtolower($user->role ?? 'user');

        // Default values
        $dashboardRoute = route('home');
        $roleName = 'User';

        // LOGIKA PENENTUAN ROLE & LINK DASHBOARD
        if ($userRole === 'admin') {
            $dashboardRoute = route('admin.dashboard');
            $roleName = 'Admin';
        } elseif ($userRole === 'seller') {
            $dashboardRoute = route('seller.dashboard') ?? route('customer.dashboard');
            $roleName = 'Seller';
        } elseif ($userRole === 'agent') {
            // Gunakan route agent.dashboard jika ada, atau fallback ke customer
            $dashboardRoute = Route::has('agent.dashboard') ? route('agent.dashboard') : route('customer.dashboard');
            $roleName = 'Agen Sancaka';
        } elseif ($userRole === 'pelanggan') {
            $dashboardRoute = route('customer.dashboard');
            $roleName = 'Pelanggan';
        }

        // Ambil NAMA LENGKAP
        $displayName = $user->nama_lengkap ?? $user->name ?? $user->email;
    @endphp

    <button class="btn btn-danger dropdown-toggle fw-bold" type="button" id="authDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fa-solid fa-circle-user me-2"></i> MEMBER AREA
    </button>

    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="authDropdown">

        <li class="px-3 py-2 bg-light border-bottom mb-2 text-center">
            <div class="fw-bold text-dark text-truncate" style="max-width: 250px; margin: 0 auto;">
                {{ $displayName }}
            </div>
            @if($userRole === 'admin')
                <span class="badge bg-danger rounded-pill mt-1">Administrator</span>
            @elseif($userRole === 'agent')
                <span class="badge bg-primary rounded-pill mt-1">Official Agent</span>
            @elseif($userRole === 'seller')
                <span class="badge bg-success rounded-pill mt-1">Seller / VIP</span>
            @else
                <span class="badge bg-secondary rounded-pill mt-1">Member</span>
            @endif
        </li>

        {{-- Tautan Dashboard Umum --}}
        <li>
            <a class="dropdown-item fw-bold text-danger" href="{{ $dashboardRoute }}">
                <i class="fa-solid fa-gauge-high me-2"></i> Dashboard {{ $roleName }}
            </a>
        </li>

        {{-- =================================================== --}}
        {{-- MENU ADMIN --}}
        {{-- =================================================== --}}
        @if ($userRole === 'admin')
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header text-primary fw-bold">Menu Admin</h6></li>

            {{-- Order Management --}}
            <li>
                <a class="dropdown-item" href="{{ route('admin.pesanan.create') }}">
                    <i class="fa-solid fa-truck-fast me-2 text-success"></i> Kirim Paket Baru
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ url('/admin/pesanan/buat-multi') }}">
                    <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i> Kirim Massal
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.pesanan.index') }}">
                    <i class="fa-solid fa-box-open me-2 text-warning"></i> Data Pesanan
                </a>
            </li>

            {{-- User Management --}}
            <li>
                <a class="dropdown-item" href="{{ route('admin.customers.index') }}">
                    <i class="fa-solid fa-users me-2 text-info"></i> Manajemen Pelanggan
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ url('/admin/registrations') }}">
                    <i class="fa-solid fa-user-plus me-2 text-danger"></i> Pendaftaran User Baru
                </a>
            </li>

            {{-- Other Data --}}
            <li>
                <a class="dropdown-item" href="{{ url('/admin/spx-scans') }}">
                    <i class="fa-solid fa-qrcode me-2 text-secondary"></i> Data Scan SPX
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ url('/admin/ppob/data') }}">
                    <i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i> Data Transaksi PPOB
                </a>
            </li>
        @endif

        {{-- =================================================== --}}
        {{-- MENU AGENT (BARU) --}}
        {{-- =================================================== --}}
        @if ($userRole === 'agent')
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header text-primary fw-bold">Menu Agen</h6></li>

            <li><a class="dropdown-item" href="{{ route('customer.pesanan.create') }}"><i class="fa-solid fa-paper-plane me-2 text-success"></i> Input Paket Agen</a></li>
            <li><a class="dropdown-item" href="{{ route('customer.pesanan.index') }}"><i class="fa-solid fa-clipboard-list me-2 text-warning"></i> Riwayat Transaksi</a></li>
            <li><a class="dropdown-item" href="{{ route('customer.topup.index') }}"><i class="fa-solid fa-wallet me-2 text-info"></i> Saldo Agen</a></li>
            <li><a class="dropdown-item" href="{{ route('customer.scan.spx') }}"><i class="fa-solid fa-barcode me-2 text-secondary"></i> Scan Resi (Dropoff)</a></li>
        @endif

        {{-- =================================================== --}}
        {{-- MENU SELLER --}}
        {{-- =================================================== --}}
        @if ($userRole === 'seller')
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header text-primary fw-bold">Menu Seller</h6></li>

            <li><a class="dropdown-item" href="{{ route('customer.pesanan.create') }}"><i class="fa-solid fa-box me-2 text-success"></i> Kirim Paket</a></li>
            <li><a class="dropdown-item" href="{{ route('customer.pesanan.index') }}"><i class="fa-solid fa-clock-rotate-left me-2 text-warning"></i> Riwayat Kiriman</a></li>
            <li><a class="dropdown-item" href="{{ route('customer.topup.index') }}"><i class="fa-solid fa-wallet me-2 text-info"></i> Dompet Seller</a></li>
            <li><a class="dropdown-item" href="{{ route('customer.scan.spx') }}"><i class="fa-solid fa-barcode me-2 text-secondary"></i> Scan Resi</a></li>
        @endif

        {{-- =================================================== --}}
        {{-- MENU PELANGGAN BIASA --}}
        {{-- =================================================== --}}
        @if ($userRole === 'pelanggan')
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header text-primary fw-bold">Menu Pelanggan</h6></li>

            <li><a class="dropdown-item" href="{{ route('customer.pesanan.create') }}"><i class="fa-solid fa-map-location-dot me-2 text-success"></i> Kirim Paket Saya</a></li>
            <li><a class="dropdown-item" href="{{ route('customer.pesanan.index') }}"><i class="fa-solid fa-list-ul me-2 text-warning"></i> Riwayat Pesanan</a></li>
            <li><a class="dropdown-item" href="{{ route('customer.topup.index') }}"><i class="fa-solid fa-wallet me-2 text-info"></i> Isi Saldo (Topup)</a></li>
            <li><a class="dropdown-item" href="{{ route('customer.scan.spx') }}"><i class="fa-solid fa-barcode me-2 text-secondary"></i> Riwayat Scan</a></li>
        @endif

        <li><hr class="dropdown-divider"></li>

        {{-- Tautan Logout --}}
        <li>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item text-danger">
                    <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                </button>
            </form>
        </li>
    </ul>
@endauth

        @guest
            <button class="btn btn-danger dropdown-toggle fw-bold" type="button" id="guestDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-user me-1"></i> Login / Order
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="guestDropdown">
                <li><a class="dropdown-item" href="{{ route('login') }}"><i class="fa-solid fa-right-to-bracket me-2"></i> Login</a></li>
                <li><a class="dropdown-item" href="{{ route('register') }}"><i class="fa-solid fa-user-plus me-2"></i>Daftar Akun Baru</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('pesanan.public.create') }}"><i class="fas fa-shipping-fast me-2"></i>Order via <strong>Sancaka Express</strong></a></li>
                <li><a class="dropdown-item" href="{{ route('scan.spx.show') }}"><i class="fas fa-barcode me-2"></i> Input Resi SPX Express</a></li>
            </ul>
        @endguest
    </div>
</div>
<!-- =================================================== -->
<!-- AKHIR KODE PERBAIKAN -->
<!-- =================================================== -->
                </div>
            </div>
        </nav>
    </header>

    <main>
        @yield('content')
    </main>

    <!-- =================================================================== -->
    <!-- FOOTER -->
    <!-- =================================================================== -->
    <footer class="text-white pt-5 pb-4" style="background-color: #1a253c;">
        <div class="container text-center text-md-start">
            <div class="row text-center text-md-start">
                <div class="col-md-3 col-lg-3 col-xl-3 mx-auto mt-3">
                    <h5 class="text-uppercase mb-4 fw-bold text-white"><strong>Sancaka Express</strong></h5>
                    <p class="text-white-50">Solusi pengiriman terpercaya untuk semua kebutuhan personal dan bisnis Anda. Cepat, aman, dan dapat diandalkan.</p>
                    <img src="https://tokosancaka.com/storage/uploads/sectigo.png" alt="Sectigo Secure" style="max-width: 120px; margin-top: 15px;">
                </div>
                <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mt-3">
                    <h5 class="text-uppercase mb-4 fw-bold text-white">Navigasi</h5>
                    <p><a href="#" class="text-white-50 text-decoration-none">Layanan</a></p>
                    <p><a href="#" class="text-white-50 text-decoration-none">Rekanan</a></p>
                    <p><a href="#" class="text-white-50 text-decoration-none">Testimoni</a></p>
                </div>
                <div class="col-md-3 col-lg-2 col-xl-2 mx-auto mt-3">
                     <h5 class="text-uppercase mb-4 fw-bold text-white">Layanan</h5>
                    <p><a href="#" class="text-white-50 text-decoration-none">Reguler & COD</a></p>
                    <p><a href="#" class="text-white-50 text-decoration-none">Cargo</a></p>
                    <p><a href="#" class="text-white-50 text-decoration-none">Pengiriman Motor</a></p>
                </div>
                <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mt-3">
                    <h5 class="text-uppercase mb-4 fw-bold text-white">Hubungi Kami</h5>
                    <p class="text-white-50"><i class="fas fa-home me-3"></i>Jl. Dr. Wahidin No.18A RT.22 RW.05 Kel Ketanggi Kec.Ngawi Kab.Ngawi Jawa Timur 63211</p>
                    <p class="text-white-50"><i class="fas fa-envelope me-3"></i>kontak@tokosancaka.com</p>
                    <p class="text-white-50"><i class="fas fa-phone me-3"></i>+62 85 745 808 809</p>
                </div>
            </div>
            <hr class="my-3">
            <div class="row align-items-center">
                <div class="col-md-7 col-lg-8">
                    <p class="text-center text-md-start text-white-50">
                        &copy; {{ date('Y') }} <strong>Sancaka Express</strong> & Toko Sancaka All Rights Reserved.
                    </p>
                </div>
                <div class="col-md-5 col-lg-4">
                    <div class="text-center text-md-end">
                        <ul class="list-unstyled list-inline mb-0">
    <li class="list-inline-item">
        <a href="https://www.facebook.com/sancakakarya.hutama" target="_blank" class="text-white" style="font-size: 23px;">
            <i class="fab fa-facebook"></i>
        </a>
    </li>
    <li class="list-inline-item">
        <a href="https://www.instagram.com/cvsancakakaryahutama" target="_blank" class="text-white" style="font-size: 23px;">
            <i class="fab fa-instagram"></i>
        </a>
    </li>
    <li class="list-inline-item">
        <a href="https://www.tiktok.com/@sancaka.legal" target="_blank" class="text-white" style="font-size: 23px;">
            <i class="fab fa-tiktok"></i>
        </a>
    </li>
    <li class="list-inline-item">
        <a href="https://wa.me/628819435180" target="_blank" class="text-white" style="font-size: 23px;">
            <i class="fab fa-whatsapp"></i>
        </a>
    </li>
</ul>

                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modal Cek Ongkir (Desain Bootstrap 5) -->
    <div class="modal fade" id="cekOngkirModal" tabindex="-1" aria-labelledby="cekOngkirModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 rounded-3 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <div class="w-100 text-center">
                        <h5 class="modal-title fw-bold text-dark" id="cekOngkirModalLabel">
                            <i class="fa-solid fa-truck-fast text-danger me-2"></i> Cek Ongkos Kirim
                        </h5>
                        <p class="text-muted small mb-0">Didukung oleh <span class="fw-semibold text-danger"><strong>Sancaka Express</strong></span></p>
                    </div>
                    <button type="button" class="btn-close position-absolute end-0 top-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 p-md-5">
                    <form id="shipping-form">
                        @csrf
                        <!-- Alamat Asal -->
                        <div class="mb-3 position-relative">
                            <label for="origin" class="form-label fw-semibold">Alamat Asal</label>
                            <input type="text" id="origin" name="origin_text" class="form-control" placeholder="Ketik nama Kecamatan/Kelurahan/kodepos..." required>
                            <input type="hidden" id="origin_id" name="origin_id">
                            <input type="hidden" id="origin_subdistrict_id" name="origin_subdistrict_id">
                            <div id="origin-results" class="autocomplete-results d-none"></div>
                        </div>
                        <!-- Alamat Tujuan -->
                        <div class="mb-3 position-relative">
                            <label for="destination" class="form-label fw-semibold">Alamat Tujuan</label>
                            <input type="text" id="destination" name="destination_text" class="form-control" placeholder="Ketik nama Kecamatan/Kelurahan/kodepos..." required>
                            <input type="hidden" id="destination_id" name="destination_id">
                            <input type="hidden" id="destination_subdistrict_id" name="destination_subdistrict_id">
                            <div id="destination-results" class="autocomplete-results d-none"></div>
                        </div>
                        <!-- Berat -->
                        <div class="mb-3">
                            <label for="weight" class="form-label fw-semibold">Berat (gram)</label>
                            <input type="number" id="weight" name="weight" class="form-control" placeholder="Contoh: 1000" min="1" required>
                        </div>

                        <!-- Tombol Opsi Lanjutan -->
                        <div class="d-flex justify-content-end gap-2 mb-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggle-volume-btn">
                                <i class="fa-solid fa-box-open me-1"></i> Hitung Volume
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggle-insurance-btn">
                                <i class="fa-solid fa-shield-halved me-1"></i> Gunakan Asuransi
                            </button>
                        </div>

                        <!-- Dimensi (Hidden by default) -->
                        <div class="mb-3 d-none" id="volume-fields">
                            <label class="form-label fw-semibold">Dimensi Paket (cm)</label>
                            <div class="row g-3">
                                <div class="col"><input type="number" id="length" name="length" placeholder="Panjang" class="form-control"></div>
                                <div class="col"><input type="number" id="width" name="width" placeholder="Lebar" class="form-control"></div>
                                <div class="col"><input type="number" id="height" name="height" placeholder="Tinggi" class="form-control"></div>
                            </div>
                            <small class="text-muted">Isi jika ongkir dihitung berdasarkan volume.</small>
                        </div>
                        <!-- Nilai Barang (Hidden by default) -->
                        <div class="mb-4 d-none" id="insurance-fields">
                            <label for="item_value" class="form-label fw-semibold">Nilai Barang (Rp)</label>
                            <input type="number" id="item_value" name="item_value" placeholder="Contoh: 500000" class="form-control">
                            <div class="form-check mt-2">
                                <input type="checkbox" id="insurance" name="insurance" class="form-check-input">
                                <label for="insurance" class="form-check-label">Gunakan Asuransi</label>
                            </div>
                        </div>
                        <!-- Tombol Submit -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger btn-lg rounded-pill" id="submit-button">
                                <i class="fa-solid fa-magnifying-glass-location me-2"></i> Cek Ongkir
                            </button>
                        </div>
                    </form>


                    <!-- Hasil -->
                    <div id="cost-results-container" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tutorial Onboarding -->
    <div class="modal fade" id="tutorialModal" tabindex="-1" aria-labelledby="tutorialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-gradient text-white rounded-top-4" style="background: linear-gradient(90deg, #ff4d4d, #b30000);">
                    <h5 class="modal-title fw-bold" id="tutorialModalLabel">
                        <span class="fw-semibold">🎉 Selamat Datang di <strong>Sancaka Express</strong></span>!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="fs-5 fw-semibold text-dark">🚀 Yuk Mulai Kirim Barang!</p>
                    <p class="text-secondary mb-4">
                        Untuk mulai menggunakan layanan kami, silakan klik tombol <span class="badge bg-danger px-2 py-1">Login / Order</span> di pojok kanan atas.
                    </p>
                    <p class="text-muted small">💼 Kami siap bantu kiriman Anda dengan cepat, aman, dan terpercaya.</p>
                </div>
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    @stack('scripts')

    @if (!empty($__debug))
    <div class="bg-yellow-100 text-yellow-800 p-4 text-sm border-t border-yellow-300 mt-8">
        <strong>🔧 DEBUG INFO:</strong>
        <pre class="whitespace-pre-wrap text-xs">{{ print_r($__debug, true) }}</pre>
    </div>
    @endif

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Tampilkan modal tutorial hanya jika belum pernah ditampilkan sebelumnya
            if (!localStorage.getItem('tutorial_shown')) {
                const tutorialModal = new bootstrap.Modal(document.getElementById('tutorialModal'));
                tutorialModal.show();
                localStorage.setItem('tutorial_shown', 'true');
            }

            // --- LOGIC UNTUK TOMBOL OPSI LANJUTAN DI MODAL CEK ONGKIR ---
            const toggleVolumeBtn = document.getElementById('toggle-volume-btn');
            const toggleInsuranceBtn = document.getElementById('toggle-insurance-btn');
            const volumeFields = document.getElementById('volume-fields');
            const insuranceFields = document.getElementById('insurance-fields');

            if (toggleVolumeBtn && volumeFields) {
                toggleVolumeBtn.addEventListener('click', function() {
                    volumeFields.classList.toggle('d-none');
                });
            }

            if (toggleInsuranceBtn && insuranceFields) {
                toggleInsuranceBtn.addEventListener('click', function() {
                    insuranceFields.classList.toggle('d-none');
                });
            }
        });
    </script>
</body>
</html>

