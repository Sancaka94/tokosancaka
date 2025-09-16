<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Berita Sancaka')</title>
    
    <link rel="icon" type="image/png" href="https://tokosancaka.biz.id/storage/uploads/sancaka.png">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Roboto+Slab:wght@700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            padding-top: 70px; /* Adjusted for navbar height */
        }
        .section-title {
            font-family: 'Roboto Slab', serif;
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
        }
        .card {
            border: none;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
        }
        .object-cover { object-fit: cover; width: 100%; height: 100%; }
        .navbar-brand strong { font-family: 'Roboto Slab', serif; }
        .footer { background-color: #212529; color: #adb5bd; padding: 3rem 0; }
        .footer a { color: #adb5bd; text-decoration: none; }
        .footer a:hover { color: #ffffff; text-decoration: underline; }
        .footer .social-icons a {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #495057;
            color: #ffffff;
            margin-right: 10px;
            transition: background-color 0.3s;
        }
        .footer .social-icons a:hover { background-color: #0d6efd; }
        .btn-read-more { font-weight: bold; }

        /* ====================== */
        /* Mega Menu Styles */
        /* ====================== */
        .mega-dropdown {
            position: static;
        }
        .mega-dropdown .dropdown-menu {
            width: 100%;
            left: 0;
            right: 0;
            padding: 2rem 1.5rem;
            margin-top: 0;
            border: none;
            border-top: 1px solid #eee;
            border-radius: 0 0 .5rem .5rem;
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.1);
        }
        @media (min-width: 992px) {
            .mega-dropdown .dropdown-menu {
                width: 960px;
                left: 50%;
                transform: translateX(-50%);
            }
        }
        .megamenu-heading {
            font-family: 'Roboto Slab', serif;
            font-size: 1rem;
            font-weight: 700;
            color: #0d6efd;
            margin-bottom: 1rem;
        }
        .megamenu-list-item {
            padding: .3rem 0;
        }
        .megamenu-list-item a {
            color: #495057;
            text-decoration: none;
            transition: color .2s;
        }
        .megamenu-list-item a:hover {
            color: #0d6efd;
        }
        .megamenu-tags .badge {
            transition: all .2s;
        }
        .megamenu-tags a:hover .badge {
            background-color: #0d6efd !important;
            color: white !important;
        }
        .megamenu-feature-card {
            border: none;
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: .5rem;
            height: 100%;
        }
        .megamenu-feature-card img {
            border-radius: .375rem;
        }
    </style>
</head>
<body>

<!-- ================== HEADER BLOG ================== -->
<header>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                <img src="https://tokosancaka.biz.id/storage/uploads/sancaka.png" alt="Sancaka Blog" height="40">
                <strong class="ms-2">Sancaka Blog</strong>
            </a>

            <!-- Toggler Mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#blogNavbar" aria-controls="blogNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navbar Links -->
            <div class="collapse navbar-collapse" id="blogNavbar">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link" href="https://tokosancaka.biz.id/blog">Beranda</a></li>
                    
                    <!-- MEGA MENU START -->
                    <li class="nav-item dropdown mega-dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="blogMegaMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Jelajahi
                        </a>
                        <div class="dropdown-menu" aria-labelledby="blogMegaMenu">
                            <div class="row">
                                <!-- KATEGORI COLUMN 1 -->
                                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                                    <h6 class="megamenu-heading">Kategori</h6>
                                    <ul class="list-unstyled">
                                        <li class="megamenu-list-item"><a href="#">Bisnis & UKM</a></li>
                                        <li class="megamenu-list-item"><a href="#">Logistik & Pengiriman</a></li>
                                        <li class="megamenu-list-item"><a href="#">Manajemen Properti</a></li>
                                        <li class="megamenu-list-item"><a href="#">Teknologi & Inovasi</a></li>
                                        <li class="megamenu-list-item"><a href="#">Tips & Trik</a></li>
                                    </ul>
                                </div>
                                <!-- LAYANAN COLUMN -->
                                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                                    <h6 class="megamenu-heading">Layanan Kami</h6>
                                    <ul class="list-unstyled">
                                        <li class="megamenu-list-item"><a href="#">Perizinan Properti (IMB, SLF)</a></li>
                                        <li class="megamenu-list-item"><a href="#">Pendirian Badan Usaha (CV/PT)</a></li>
                                        <li class="megamenu-list-item"><a href="#">Layanan Digital Marketing</a></li>
                                        <li class="megamenu-list-item"><a href="#">Konsultasi Bisnis</a></li>
                                        <li class="megamenu-list-item"><a href="#">Sertifikasi Tanah</a></li>
                                    </ul>
                                </div>
                                <!-- TAGS COLUMN -->
                                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                                    <h6 class="megamenu-heading">Tag Populer</h6>
                                    <div class="d-flex flex-wrap megamenu-tags">
                                        <a href="#" class="me-2 mb-2"><span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">#Ekspedisi</span></a>
                                        <a href="#" class="me-2 mb-2"><span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">#IMB</span></a>
                                        <a href="#" class="me-2 mb-2"><span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">#Startup</span></a>
                                        <a href="#" class="me-2 mb-2"><span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">#Marketing</span></a>
                                        <a href="#" class="me-2 mb-2"><span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">#Investasi</span></a>
                                        <a href="#" class="me-2 mb-2"><span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">#Pajak</span></a>
                                    </div>
                                </div>
                                <!-- FEATURED POST COLUMN -->
                                <div class="col-lg-3 col-md-6">
                                    <div class="megamenu-feature-card">
                                        <img src="https://placehold.co/600x400/0d6efd/white?text=Artikel+Unggulan" class="img-fluid mb-2" alt="Featured Post">
                                        <h6 class="fw-bold small">5 Strategi Jitu Memulai Bisnis Kargo di Era Digital</h6>
                                        <a href="#" class="btn btn-sm btn-outline-primary">Baca Selengkapnya</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    <!-- MEGA MENU END -->
                    
                    <li class="nav-item"><a class="nav-link" href="{{ route('blog.about') }}">Tentang</a></li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-primary btn-sm" href="{{ url('/') }}">Kunjungi Web Utama</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<!-- ================== KONTEN UTAMA ================== -->
<main class="py-5">
    @yield('content')
</main>

<!-- ================== FOOTER ================== -->
<footer class="footer">
    <div class="container text-center">
        <p>&copy; {{ date('Y') }} Sancaka Blog. All Rights Reserved.</p>
        <div class="social-icons mt-2">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
