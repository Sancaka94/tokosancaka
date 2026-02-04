{{-- File: resources/views/partials/blog/header.blade.php --}}
<header>
    {{-- Navigasi Utama --}}
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">PORTAL BLOG</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavDropdown">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Nasional</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Internasional</a>
                    </li>
                    
                    <!-- Mega Menu Dropdown yang Diperbarui -->
                    <li class="nav-item dropdown megamenu-dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Kategori
                        </a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                            <div class="container">
                                <div class="row">
                                    {{-- Kolom Kategori 1 --}}
                                    <div class="col-lg-3 col-md-6 py-2">
                                        <h6 class="dropdown-header">Berita Utama</h6>
                                        <a class="dropdown-item" href="#">Politik</a>
                                        <a class="dropdown-item" href="#">Hukum</a>
                                        <a class="dropdown-item" href="#">Peristiwa</a>
                                        <a class="dropdown-item" href="#">Daerah</a>
                                    </div>
                                    {{-- Kolom Kategori 2 --}}
                                    <div class="col-lg-3 col-md-6 py-2">
                                        <h6 class="dropdown-header">Gaya Hidup</h6>
                                        <a class="dropdown-item" href="#">Selebriti</a>
                                        <a class="dropdown-item" href="#">Kesehatan</a>
                                        <a class="dropdown-item" href="#">Otomotif</a>
                                        <a class="dropdown-item" href="#">Kuliner</a>
                                    </div>
                                    {{-- Kolom Kategori 3 --}}
                                    <div class="col-lg-3 col-md-6 py-2">
                                        <h6 class="dropdown-header">Bisnis & Tekno</h6>
                                        <a class="dropdown-item" href="#">Ekonomi</a>
                                        <a class="dropdown-item" href="#">Properti</a>
                                        <a class="dropdown-item" href="#">Teknologi</a>
                                        <a class="dropdown-item" href="#">Sains</a>
                                    </div>
                                    {{-- Kolom Unggulan --}}
                                    <div class="col-lg-3 col-md-6 py-2 d-none d-lg-block">
                                        <div class="megamenu-item">
                                            <a href="#" class="text-decoration-none text-dark">
                                                <img src="https://placehold.co/600x400/dc3545/ffffff?text=Laporan+Khusus" class="img-fluid rounded" alt="Laporan Khusus">
                                                <h5 class="fw-bold mt-2">Laporan Khusus</h5>
                                                <p class="small text-muted">Liputan mendalam tentang isu-isu terkini yang paling relevan.</p>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#">Tentang Kami</a>
                    </li>
                </ul>
                <form class="d-flex" action="{{ url('/search') }}" method="GET">
                    <input class="form-control me-2" type="search" name="query" placeholder="Cari berita..." aria-label="Search">
                    <button class="btn btn-outline-primary" type="submit">Cari</button>
                </form>
            </div>
        </div>
    </nav>

    {{-- Navigasi Sekunder (Trending Topics) --}}
    <div class="bg-light border-bottom d-none d-lg-block">
        <div class="container d-flex justify-content-between align-items-center py-2 small">
            <div>
                <span class="fw-bold me-2 text-danger">TRENDING:</span>
                <a href="#" class="text-decoration-none text-dark me-3">#Info Haji 2024</a>
                <a href="#" class="text-decoration-none text-dark me-3">#Pilkada Serentak</a>
                <a href="#" class="text-decoration-none text-dark">#Konflik Timur Tengah</a>
            </div>
            <div class="d-flex align-items-center">
                 <a href="#" class="text-decoration-none text-primary fw-bold me-3"><i class="fas fa-images me-1"></i> FOTO</a>
                 <a href="#" class="text-decoration-none text-danger fw-bold"><i class="fas fa-video me-1"></i> VIDEO</a>
            </div>
        </div>
    </div>
</header>
