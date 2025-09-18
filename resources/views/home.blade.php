@extends('layouts.app')



@section('title', 'Selamat Datang di Sancaka Express')



@push('styles')

<style>

    :root {

        --sancaka-primary: #1a73e8; /* Mengganti --google-blue menjadi warna tema */

        --sancaka-secondary: #ffc107;

        --sancaka-danger: #dc3545;

    }

    

    body {
        background-color: #f8f9fa;
    }

    .hero-section {
        background: linear-gradient(135deg, rgba(26, 115, 232, 0.9), rgba(2, 48, 102, 0.9)), url('https://placehold.co/1920x1080/cccccc/ffffff?text=Latar+Belakang') no-repeat center center;
        background-size: cover;
        color: white;
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



    .service-card, .testimonial-card, .partner-logo-card, .trust-logo-card {

        text-align: center;

        padding: 2rem;

        background-color: #ffffff;

        border-radius: 0.75rem;

        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);

        height: 100%;
        transition: transform 0.2s, box-shadow 0.2s;

    }
    .service-card:hover, .testimonial-card:hover, .partner-logo-card:hover, .trust-logo-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
    }


    .partner-logo-card, .trust-logo-card {

        display: flex;

        align-items: center;

        justify-content: center;
        padding: 1rem;

    }

    .partner-logo-card img {

        max-height: 60px;

        width: 100%;

        object-fit: contain;

    }

    .trust-logo-card img {
        width: 100%;
        max-height: 80px;
        object-fit: contain;
        filter: grayscale(100%);
        opacity: 0.7;
        transition: all 0.3s;
    }
    .trust-logo-card:hover img {
        filter: grayscale(0%);
        opacity: 1;
    }

    .post-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .post-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
    }
    .post-card-img {
        object-fit: cover;
    }


/* Tombol floating */

.floating-btn {
    position: fixed;
    width: 55px;
    height: 55px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 1000;
    color: white;
    font-size: 24px;
    cursor: pointer;
    transition: all 0.3s ease-in-out;
}

.floating-btn:hover {
    transform: scale(1.1);
    color: white;
}

/* Tombol WA */
.wa-float {
    bottom: 30px;
    right: 30px;
    background-color: #25d366;
}

/* Tombol Scroll */
#scrollTopBtn {
    bottom: 30px;
    right: 30px;
    background-color: var(--sancaka-primary);
    display: none; /* Diatur oleh JS */
}


    /* ===== STYLING UNTUK FITUR PENCARIAN ALAMAT BARU ===== */

    .address-search-container .nav-pills .nav-link {

        border-radius: 0.5rem;

        font-weight: 500;

        transition: all 0.2s ease-in-out;

    }



    .address-search-container .nav-pills .nav-link.active {

        background-color: var(--sancaka-primary);

        color: white;

        box-shadow: 0 4px 12px rgba(26, 115, 232, 0.25);

    }

    

    .address-search-container .form-select,

    .address-search-container .form-control {

        border-radius: 0.5rem;

    }



    .address-search-container .form-select:disabled {

        background-color: #e9ecef; /* Warna standar bootstrap untuk disabled */

        cursor: not-allowed;

    }

    

    .address-search-container .results-table {

        margin-top: 1.5rem;

        border-radius: 0.75rem;

        box-shadow: 0 2px 10px rgba(0,0,0,0.07);
        overflow: hidden; /* agar border-radius bekerja */

    }
    /* ===== AKHIR STYLING FITUR PENCARIAN ALAMAT BARU ===== */

    /* ===== PENYESUAIAN RESPONSIVE ===== */

    /* Medium devices (tablet, 768px and up) */
    @media (min-width: 768px) {
        .results-list {
             display: none; /* Sembunyikan tampilan list di desktop */
        }
    }


    /* Small devices (landscape phones, 576px and up) */
    @media (max-width: 991.98px) {
        .post-card.flex-lg-row {
            flex-direction: column !important;
        }
    }


    /* Extra small devices (portrait phones, less than 768px) */
    @media (max-width: 767.98px) {
        .section { padding: 2.5rem 0; }
        .section-title { margin-bottom: 1.5rem; font-size: 1.75rem; }
        .hero-section { padding: 3rem 0; }
        .hero-section h1 { font-size: 2.25rem; }
        .hero-section .lead { font-size: 1rem; }
        .action-bar { margin-top: -50px; }

        .results-table.table-responsive {
            display: none; /* Sembunyikan tabel di mobile */
        }
        .results-list {
             display: block; /* Tampilkan list di mobile */
             margin-top: 1.5rem;
        }
        .result-card {
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
        }
        .result-card p { margin-bottom: 0.25rem; }
        .result-card .postcode {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--sancaka-primary);
        }

        /* Perbaikan layout blog di mobile */
        .post-card .rounded-start, .post-card .rounded-end {
            border-radius: 0.5rem 0.5rem 0 0 !important;
            height: 200px;
            width: 100%;
        }
        .post-card.flex-sm-row {
            flex-direction: column !important;
        }
        .post-card.flex-sm-row img {
             width: 100% !important;
             height: 150px;
             aspect-ratio: auto;
             border-radius: 0.5rem 0.5rem 0 0 !important;
        }
    }


</style>

@endpush



@section('content')



<!-- Hero Section -->
<section class="hero-section">
    <div class="container text-center py-5">
        <h1 class="display-4 fw-bold">Solusi Pengiriman Terlengkap dan Terpercaya</h1>
        <p class="lead mt-3 col-lg-8 mx-auto">Kirim paket ke seluruh Indonesia dengan mudah, cepat, dan aman bersama Sancaka Express.</p>
    </div>
</section>

<!-- Action Bar Section -->
<div class="container" style="margin-top: -80px; position: relative; z-index: 20;">
    <div class="action-bar">
        <div class="row g-4">
            <div class="col-lg-4 col-md-12">
                <div class="action-card h-100">
                    <h5 class="fw-bold"><i class="fa-solid fa-magnifying-glass me-2"></i>Lacak Kiriman Anda</h5>
                    <p class="text-muted small">Pantau posisi paket Anda secara akurat dan real-time.</p>
                    <form action="{{-- route('tracking.search') --}}" method="GET" class="mt-auto">
                        <div class="input-group">
                            <input type="text" name="resi" class="form-control form-control-lg" placeholder="Cek Resi" required>
                            <button class="btn btn-primary px-4" type="submit" style="background-color: var(--sancaka-primary); border-color: var(--sancaka-primary);">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="action-card clickable h-100" data-bs-toggle="modal" data-bs-target="#cekOngkirModal">
                    <h5 class="fw-bold"><i class="fa-solid fa-calculator me-2"></i>Cek Estimasi Ongkir</h5>
                    <p class="text-muted small">Hitung biaya pengiriman secara transparan sebelum mengirim.</p>
                    <div class="d-grid mt-auto">
                        <button class="btn btn-lg w-100" style="background-color: var(--sancaka-secondary); color: #333;" type="button">Cek Ongkir</button>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <a href="#cek-alamat" class="text-decoration-none text-dark">
                    <div class="action-card clickable h-100">
                        <h5 class="fw-bold"><i class="fa-solid fa-map-pin me-2"></i>Cek Kode Pos & Wilayah</h5>
                        <p class="text-muted small">Temukan informasi kode pos dan detail wilayah di seluruh Indonesia.</p>
                        <div class="d-grid mt-auto">
                            <button class="btn btn-lg text-white w-100" style="background-color: var(--sancaka-danger);" type="button">Cek Alamat</button>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Pencarian Alamat Section -->
<section id="cek-alamat" class="section bg-light pt-5 mt-5">
    <div class="container">
        <h2 class="section-title">Cek Alamat & Kode Pos Akurat</h2>
        <p class="text-center text-muted mb-5">Temukan informasi alamat lengkap dan kode pos di seluruh Indonesia.</p>
        <div class="card shadow-sm address-search-container">
            <div class="card-body p-4 p-lg-5">
                <ul class="nav nav-pills nav-fill mb-4" id="addressSearchTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="wilayah-tab" data-bs-toggle="pill" data-bs-target="#wilayah-tab-pane" type="button" role="tab" aria-controls="wilayah-tab-pane" aria-selected="true">
                            <i class="fas fa-map-location-dot me-2"></i><span class="d-none d-sm-inline">Cari Berdasarkan</span> Wilayah
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="kodepos-tab" data-bs-toggle="pill" data-bs-target="#kodepos-tab-pane" type="button" role="tab" aria-controls="kodepos-tab-pane" aria-selected="false">
                            <i class="fas fa-magnifying-glass-location me-2"></i><span class="d-none d-sm-inline">Cari Dengan</span> Kata Kunci
                        </button>
                    </li>
                </ul>
                <div class="tab-content" id="addressSearchTabContent">
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
                    <div class="tab-pane fade" id="kodepos-tab-pane" role="tabpanel" aria-labelledby="kodepos-tab" tabindex="0">
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control" id="kodepos-search-input" placeholder="Masukkan nama desa, kecamatan, kota, atau kode pos...">
                            <button class="btn btn-primary" id="kodepos-search-btn">
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
<section id="tentang" class="section">
    <div class="container">
        <h2 class="section-title">Kenapa Memilih Sancaka Express?</h2>
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <img src="https://tokosancaka.biz.id/storage/uploads/logo.jpeg" class="img-fluid rounded shadow-lg" alt="Profil Sancaka Express">
            </div>
            <div class="col-lg-6">
                <h3 class="fw-bold mb-3">CV. SANCAKA KARYA HUTAMA</h3>
                <p class="lead text-muted mb-4">
                    <em>"Lebih dari Sekadar Jasa Kirim, Kami Adalah Mitra Pertumbuhan Bisnis Anda."</em>
                </p>
                <div id="about-short">
                    <h5 class="fw-bold mt-4">Tentang Kami</h5>
                    <p>
                        <strong>Sancaka Express</strong> merupakan perusahaan jasa pengiriman yang berdedikasi untuk memberikan solusi logistik terbaik bagi individu maupun pelaku bisnis. Dengan jaringan distribusi yang luas serta didukung oleh tim profesional, kami berkomitmen untuk memastikan setiap paket sampai ke tujuan dengan cepat, aman, dan tepat waktu.
                    </p>
                </div>
                <div id="about-full" class="d-none">
                     <p>Kami memahami bahwa pengiriman bukan sekadar memindahkan barang, melainkan juga menjaga kepercayaan pelanggan. Oleh karena itu, setiap layanan kami dirancang untuk memberikan pengalaman yang nyaman, transparan, dan dapat diandalkan.</p>
                    <h5 class="fw-bold mt-4">Visi</h5>
                    <p>Menjadi mitra logistik terdepan yang mengutamakan kepuasan pelanggan melalui inovasi teknologi, efisiensi layanan, dan keunggulan operasional.</p>
                    <h5 class="fw-bold mt-4">Misi</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check-circle text-primary me-2"></i>Menyediakan layanan pengiriman yang cepat, aman, dan tepat waktu.</li>
                        <li><i class="fas fa-check-circle text-primary me-2"></i>Membangun kepercayaan pelanggan melalui layanan prima.</li>
                        <li><i class="fas fa-check-circle text-primary me-2"></i>Mengembangkan teknologi logistik untuk mendukung bisnis pelanggan.</li>
                        <li><i class="fas fa-check-circle text-primary me-2"></i>Menjadi mitra strategis dalam rantai pasok.</li>
                    </ul>
                </div>
                <button id="toggle-btn" class="btn btn-outline-primary btn-sm mt-3">
                    Baca Selengkapnya
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Blog Section -->
<section id="blog" class="section bg-light">
    {{-- Kode ini mengasumsikan variabel $headline, $topArticles, $latestPosts, $popularPosts dikirim dari Controller --}}
    <div class="container">
        <h2 class="section-title">Berita & Informasi Terbaru</h2>
        <div class="mb-5">
            <form action="{{ url()->current() }}" method="GET" class="mw-xl mx-auto">
                <div class="input-group input-group-lg">
                    <input type="search" name="search" class="form-control" placeholder="Cari artikel..." value="{{ request('search') }}" aria-label="Cari Artikel">
                    <button class="btn btn-primary px-4" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>

        @if(request()->has('search') && request()->input('search') != '')
            <h4 class="mb-4 fw-bold">Hasil pencarian untuk: "{{ request('search') }}"</h4>
        @endif

        @if(isset($headline) && !request()->filled('search'))
        <div class="row g-4 mb-5">
            <div class="col-lg-8">
                <div class="card post-card shadow-sm h-100">
                    <a href="{{-- route('blog.posts.show', $headline->slug) --}}" class="text-decoration-none text-dark">
                        <img src="{{-- Storage::url($headline->featured_image) --}}" class="card-img-top post-card-img" style="height: 400px;" onerror="this.onerror=null;this.src='https://placehold.co/800x400/1a73e8/ffffff?text=Headline';" alt="{{-- $headline->title --}}">
                        <div class="card-body p-4">
                            <small class="text-primary fw-bold">{{-- $headline->category->name ?? 'UMUM' --}}</small>
                            <h3 class="card-title fw-bold mt-2">{{-- $headline->title --}}</h3>
                            <p class="card-text text-muted">{{-- Str::limit(strip_tags($headline->content), 200) --}}</p>
                            <small class="text-muted">{{-- $headline->created_at->diffForHumans() --}}</small>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="d-flex flex-column gap-3 h-100">
                    @if(isset($topArticles))
                    @foreach($topArticles as $article)
                    <a href="{{-- route('blog.posts.show', $article->slug) --}}" class="text-decoration-none text-dark">
                        <div class="card post-card shadow-sm flex-sm-row h-100">
                           <img src="{{-- Storage::url($article->featured_image) --}}" class="w-25 post-card-img" style="aspect-ratio: 1/1;" onerror="this.onerror=null;this.src='https://placehold.co/100x100/CCCCCC/FFFFFF?text=Image';" alt="{{-- $article->title --}}">
                            <div class="card-body d-flex align-items-center p-3">
                                <h6 class="fw-bold small mb-0">{{-- $article->title --}}</h6>
                            </div>
                        </div>
                    </a>
                    @endforeach
                    @endif
                </div>
            </div>
        </div>
        @endif

        <div class="row g-5">
            <div class="col-lg-8">
                <h4 class="fw-bold mb-4">{{ request()->filled('search') ? 'Hasil Ditemukan' : 'Lainnya dari Blog Kami' }}</h4>
                @if(isset($latestPosts))
                @forelse($latestPosts as $post)
                    <div class="card post-card mb-4 shadow-sm">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <a href="{{-- route('blog.posts.show', $post->slug) --}}">
                                    <img src="{{-- Storage::url($post->featured_image) --}}" class="img-fluid rounded-start h-100 post-card-img" onerror="this.onerror=null;this.src='https://placehold.co/400x250/CCCCCC/FFFFFF?text=Image';" alt="{{-- $post->title --}}">
                                </a>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <h5 class="card-title fw-bold"><a href="{{-- route('blog.posts.show', $post->slug) --}}" class="text-decoration-none text-dark">{{-- $post->title --}}</a></h5>
                                    <p class="card-text text-muted small">{{-- Str::limit(strip_tags($post->content), 150) --}}</p>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <small class="text-muted">{{-- $post->created_at->diffForHumans() --}} by {{-- $post->author->nama_lengkap ?? 'Admin' --}}</small>
                                        <a href="{{-- route('blog.posts.show', $post->slug) --}}" class="btn btn-sm btn-outline-primary">Baca</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-warning">Tidak ada artikel yang cocok.</div>
                @endforelse
                <div class="d-flex justify-content-center mt-4">
                    {{-- $latestPosts->links('pagination::bootstrap-5') --}}
                </div>
                @endif
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 2rem;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 fw-bold">Terpopuler</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        @if(isset($popularPosts))
                        @forelse($popularPosts as $key => $post)
                        <a href="{{-- route('blog.posts.show', $post->slug) --}}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <span class="fw-bold me-3 fs-4 text-muted">{{ $key + 1 }}</span>
                            <span>{{-- $post->title --}}</span>
                        </a>
                        @empty
                        <li class="list-group-item">Belum ada artikel populer.</li>
                        @endforelse
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Layanan & Keunggulan Section -->
<section id="layanan" class="section">
    <div class="container">
        <h2 class="section-title">Layanan & Keunggulan Kami</h2>
        <div class="row g-4">
            <!-- Layanan -->
            <div class="col-lg-4 col-md-6">
                <div class="service-card">
                    <i class="fas fa-box fa-3x text-primary mb-3"></i>
                    <h5 class="fw-bold">Reguler & COD</h5>
                    <p>Pengiriman paket reguler dan Cash on Delivery ke seluruh pelosok Indonesia.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="service-card">
                    <i class="fas fa-truck-moving fa-3x text-primary mb-3"></i>
                    <h5 class="fw-bold">Cargo</h5>
                    <p>Solusi pengiriman barang dalam jumlah besar dengan tarif kompetitif.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="service-card">
                    <i class="fas fa-motorcycle fa-3x text-primary mb-3"></i>
                    <h5 class="fw-bold">Pengiriman Motor</h5>
                    <p>Layanan khusus untuk pengiriman sepeda motor antar kota dengan jaminan keamanan.</p>
                </div>
            </div>
            <!-- Keunggulan -->
            <div class="col-lg-4 col-md-6">
                <div class="service-card">
                    <i class="fas fa-bolt fa-3x text-primary mb-3"></i>
                    <h5 class="fw-bold">Efisien Dan Cepat</h5>
                    <p>Paket bisa dijemput atau diantar ke agen. Pilihan layanan lengkap dari instan sampai kargo.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="service-card">
                    <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                    <h5 class="fw-bold">Support 24 Jam</h5>
                    <p>Tim kami selalu siap bantu kapan saja jika ada kendala dalam proses pengiriman.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="service-card">
                    <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                    <h5 class="fw-bold">Dashboard Monitoring</h5>
                    <p>Kelola paket, lacak real-time, dan lihat laporan lengkap dalam satu tempat.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mitra Ekspedisi Section -->
<section id="mitra" class="section bg-light">
    <div class="container">
        <h2 class="section-title">Didukung Jaringan Ekspedisi Ternama</h2>
        <p class="text-center text-muted mb-5">Fleksibilitas, jangkauan terluas, dan harga terbaik untuk pengiriman Anda.</p>
        <div class="row g-4 justify-content-center align-items-center">
            <div class="col-6 col-md-4 col-lg-2"><div class="partner-logo-card"><img src="https://upload.wikimedia.org/wikipedia/commons/0/01/J%26T_Express_logo.svg" alt="Logo J&T Express"></div></div>
            <div class="col-6 col-md-4 col-lg-2"><div class="partner-logo-card"><img src="https://upload.wikimedia.org/wikipedia/commons/9/92/New_Logo_JNE.png" alt="Logo JNE"></div></div>
            <div class="col-6 col-md-4 col-lg-2"><div class="partner-logo-card"><img src="https://kiriminaja.com/assets/home-v4/pos.png" alt="Logo POS Indonesia"></div></div>
            <div class="col-6 col-md-4 col-lg-2"><div class="partner-logo-card"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png" alt="Logo Indah Cargo"></div></div>
            <div class="col-6 col-md-4 col-lg-2"><div class="partner-logo-card"><img src="https://kiriminaja.com/assets/home-v4/sap.png" alt="Logo SAP Express"></div></div>
            <div class="col-6 col-md-4 col-lg-2"><div class="partner-logo-card"><img src="https://assets.bukalapak.com/beagle/images/courier_logo/id-express.png" alt="Logo ID Express"></div></div>
            <div class="col-6 col-md-4 col-lg-2"><div class="partner-logo-card"><img src="https://i.pinimg.com/736x/22/cf/92/22cf92368c1f901d17e38e99061f4849.jpg" alt="Logo J&T Cargo"></div></div>
            <div class="col-6 col-md-4 col-lg-2"><div class="partner-logo-card"><img src="https://kiriminaja.com/assets/home-v4/lion.png" alt="Logo Lion Parcel"></div></div>
            <div class="col-6 col-md-4 col-lg-2"><div class="partner-logo-card"><img src="https://images.seeklogo.com/logo-png/49/1/spx-express-indonesia-logo-png_seeklogo-499970.png" alt="Logo SPX Express"></div></div>
            <div class="col-6 col-md-4 col-lg-2"><div class="partner-logo-card"><img src="https://kiriminaja.com/assets/home-v4/sicepat.png" alt="Logo Sicepat"></div></div>
            <div class="col-6 col-md-4 col-lg-2"><div class="partner-logo-card"><img src="https://kiriminaja.com/assets/home-v4/ninja.png" alt="Logo Ninja Express"></div></div>
            <div class="col-6 col-md-4 col-lg-2"><div class="partner-logo-card"><img src="https://kiriminaja.com/assets/home-v4/anter-aja.png" alt="Logo Anteraja"></div></div>
        </div>
    </div>
</section>

<!-- Testimoni Section -->
<section id="testimoni" class="section">
    <div class="container">
        <h2 class="section-title">Apa Kata Mereka Tentang Kami?</h2>
        <p class="text-center text-muted mb-5">Kepuasan Anda adalah prioritas utama kami.</p>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="testimonial-card">
                    <p class="fst-italic">"Pelayanannya cepat banget! Paket saya sampai keesokan harinya dalam kondisi baik. Stafnya ramah dan sangat membantu. Recommended!"</p>
                    <div class="mt-auto pt-3 border-top">
                        <h5 class="mb-0">Andi Pratama</h5>
                        <small class="text-muted">Pengusaha Online</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <p class="fst-italic">"Pakai layanan cargo untuk kirim stok barang, biayanya sangat terjangkau dan timnya profesional. Semua barang aman tanpa lecet. Mantap!"</p>
                    <div class="mt-auto pt-3 border-top">
                        <h5 class="mb-0">Citra Lestari</h5>
                        <small class="text-muted">Pemilik Toko</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <p class="fst-italic">"Fitur lacak resinya akurat dan mudah digunakan. Saya jadi tenang tahu posisi paket saya di mana setiap saat. Tidak perlu was-was lagi."</p>
                    <div class="mt-auto pt-3 border-top">
                        <h5 class="mb-0">Budi Santoso</h5>
                        <small class="text-muted">Karyawan Swasta</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Legalitas Section -->
<section id="legalitas" class="section bg-light">
    <div class="container">
        <h2 class="section-title">Legalitas & Kepercayaan</h2>
        <p class="text-center text-muted mb-5">Kami adalah mitra terpercaya yang telah terdaftar dan diakui secara resmi.</p>
        <div class="row g-4 justify-content-center">
            <div class="col-6 col-md-4 col-lg-3"><div class="trust-logo-card"><img src="https://tokosancaka.com/storage/uploads/PSE-HITAM-PUTIH.jpg" alt="Sertifikat PSE Kominfo"></div></div>
            <div class="col-6 col-md-4 col-lg-3"><div class="trust-logo-card"><img src="https://tokosancaka.com/storage/uploads/pengertian-oss.jpg.webp" alt="Logo Terdaftar OSS"></div></div>
            <div class="col-6 col-md-4 col-lg-3"><div class="trust-logo-card"><img src="https://tokosancaka.com/storage/uploads/djki.png" alt="Sertifikat DJKI"></div></div>
            <div class="col-6 col-md-4 col-lg-3"><div class="trust-logo-card"><img src="https://tokosancaka.com/storage/uploads/pupr.png" alt="Mitra Resmi PUPR"></div></div>
        </div>
    </div>
</section>

<!-- Q&A Section -->
<section id="qa" class="section">
    <div class="container">
        <h2 class="section-title">Tanya Jawab (Q&A)</h2>
        <div class="accordion" id="accordionQA">
            <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#q1">Bagaimana cara melacak paket saya?</button></h2><div id="q1" class="accordion-collapse collapse show" data-bs-parent="#accordionQA"><div class="accordion-body">Anda dapat melacak paket dengan memasukkan nomor resi pada kolom "Lacak Kiriman Anda" di bagian atas halaman utama. Status akan ditampilkan secara real-time.</div></div></div>
            <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q2">Apa saja jangkauan pengiriman Sancaka Express?</button></h2><div id="q2" class="accordion-collapse collapse" data-bs-parent="#accordionQA"><div class="accordion-body">Kami melayani pengiriman ke seluruh wilayah di Indonesia, dari kota besar hingga ke daerah terpencil.</div></div></div>
            <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q3">Apa beda layanan Reguler dan Cargo?</button></h2><div id="q3" class="accordion-collapse collapse" data-bs-parent="#accordionQA"><div class="accordion-body">Layanan <strong>Reguler</strong> untuk paket standar. Layanan <strong>Cargo</strong> adalah solusi untuk barang dalam jumlah besar atau berat dengan biaya lebih efisien.</div></div></div>
            <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q4">Apakah ada asuransi untuk barang kiriman?</button></h2><div id="q4" class="accordion-collapse collapse" data-bs-parent="#accordionQA"><div class="accordion-body">Ya, kami menyediakan opsi asuransi untuk perlindungan ekstra terhadap risiko kerusakan atau kehilangan barang berharga Anda.</div></div></div>
            <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q5">Bagaimana jika paket saya rusak atau hilang?</button></h2><div id="q5" class="accordion-collapse collapse" data-bs-parent="#accordionQA"><div class="accordion-body">Segera hubungi layanan pelanggan kami melalui WhatsApp dengan menyertakan nomor resi dan bukti dokumentasi (foto/video). Tim kami akan membantu proses klaim Anda.</div></div></div>
        </div>
    </div>
</section>

<!-- Kontak & Peta Section -->
<section id="kontak" class="section bg-light">
    <div class="container">
        <h2 class="section-title">Hubungi Kami!</h2>
        <p class="text-center text-muted mb-5">Punya pertanyaan atau butuh konsultasi? Tim kami siap membantu Anda.</p>
        <div class="row g-5">
            <div class="col-lg-6">
                <h4>Informasi Kontak</h4>
                <ul class="list-unstyled">
                    <li class="mb-3"><i class="fas fa-map-marker-alt fa-fw me-2 text-primary"></i>Jl. Dokter Wahidin No.18a, Sidomakmur, Ketanggi, Kec. Ngawi, Kabupaten Ngawi, Jawa Timur 63211</li>
                    <li class="mb-3"><i class="fas fa-phone fa-fw me-2 text-primary"></i><a href="tel:+6285745808809" class="text-decoration-none text-dark">0857-4580-8809</a></li>
                    <li class="mb-3"><i class="fas fa-envelope fa-fw me-2 text-primary"></i><a href="mailto:info@sancaka.com" class="text-decoration-none text-dark">info@sancaka.com</a></li>
                </ul>
            </div>
            <div class="col-lg-6">
                <h4>Lokasi Kami di Peta</h4>
                <div class="ratio ratio-16x9 rounded shadow-sm" style="overflow: hidden;">
                   <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3955.975440748102!2d111.4429948748921!3d-7.468200192510255!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e79e00023223a29%3A0xb353590595368a4!2sJl.%20Dokter%20Wahidin%20No.18a%2C%20Sidomakmur%2C%20Ketanggi%2C%20Kec.%20Ngawi%2C%20Kabupaten%20Ngawi%2C%20Jawa%20Timur%2063211!5e0!3m2!1sen!2sid!4v1720345535355!5m2!1sen!2sid" width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Floating Action Buttons -->
<a href="https://wa.me/6285745808809" class="floating-btn wa-float" target="_blank" title="Hubungi via WhatsApp"><i class="fab fa-whatsapp"></i></a>
<button onclick="scrollToTop()" id="scrollTopBtn" class="floating-btn" title="Kembali ke atas"><i class="fas fa-arrow-up"></i></button>


@endsection



@push('scripts')



<script>

document.addEventListener('DOMContentLoaded', function() {

    
    const scrollToTop = () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // Logika Tombol Scroll to Top & Posisi Tombol WA
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    const waFloatBtn = document.querySelector('.wa-float');

    if (scrollTopBtn && waFloatBtn) {
        const scrollFunction = () => {
            if (window.scrollY > 300) {
                scrollTopBtn.style.display = 'flex';
                waFloatBtn.style.bottom = '95px'; // Geser tombol WA ke atas
            } else {
                scrollTopBtn.style.display = 'none';
                waFloatBtn.style.bottom = '30px'; // Kembalikan posisi tombol WA
            }
        };

        window.addEventListener('scroll', scrollFunction);

        scrollTopBtn.addEventListener('click', scrollToTop);
    }



    // ==================================================

    // == LOGIKA BARU UNTUK PENCARIAN ALAMAT ==

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
        provinces: "{{-- route('api.wilayah.provinces') --}}",
        kabupaten: "{{-- route('api.wilayah.kabupaten', ':id') --}}",
        kecamatan: "{{-- route('api.wilayah.kecamatan', ':id') --}}",
        desa: "{{-- route('api.kodepos.by-district', ':id') --}}",
        search: "{{-- route('api.kodepos.public.search') --}}"
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



    // --- FUNGSI BARU UNTUK MENAMPILKAN HASIL (RESPONSIF) ---
    const renderResults = (container, results, pagination) => {
        if (!results || results.length === 0) {
            container.innerHTML = `<div class="alert alert-warning mt-3">Tidak ada data ditemukan.</div>`;
            return;
        }

        // --- Tampilan Tabel untuk Desktop ---
        let tableHtml = `
            <div class="results-table table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kelurahan/Desa</th>
                            <th>Kecamatan</th>
                            <th>Kabupaten/Kota</th>
                            <th>Provinsi</th>
                            <th class="text-end">Kode Pos</th>
                        </tr>
                    </thead>
                    <tbody>`;
        results.forEach(item => {
            tableHtml += `
                <tr>
                    <td>${item.kelurahan_desa || '-'}</td>
                    <td>${item.kecamatan || '-'}</td>
                    <td>${item.kota_kabupaten || '-'}</td>
                    <td>${item.provinsi || '-'}</td>
                    <td class="fw-bold text-end">${item.kode_pos || '-'}</td>
                </tr>`;
        });
        tableHtml += `</tbody></table></div>`;

        // --- Tampilan Kartu untuk Mobile ---
        let listHtml = `<div class="results-list">`;
        results.forEach(item => {
            listHtml += `
                <div class="result-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="fw-bold mb-1">${item.kelurahan_desa || 'N/A'}</p>
                            <p class="text-muted small mb-0">${item.kecamatan}, ${item.kota_kabupaten}, ${item.provinsi}</p>
                        </div>
                        <div class="postcode ms-3">${item.kode_pos || '-'}</div>
                    </div>
                </div>`;
        });
        listHtml += `</div>`;

        // --- Tombol Paginasi ---
        let paginationHtml = '';
        if (pagination && pagination.total > 0 && pagination.last_page > 1) {
            paginationHtml = `
                <div class="d-flex justify-content-between align-items-center p-3 mt-1 border-top bg-light flex-wrap rounded-bottom">
                    <p class="text-muted small mb-2 mb-md-0">
                        Hal ${pagination.current_page} dari ${pagination.last_page} (${pagination.total} hasil)
                    </p>
                    <div class="pagination-controls">
                        <button class="btn btn-sm btn-outline-secondary btn-prev" ${pagination.current_page <= 1 ? 'disabled' : ''} data-page="${pagination.current_page - 1}"><i class="fas fa-chevron-left"></i></button>
                        <button class="btn btn-sm btn-outline-secondary ms-2 btn-next" ${pagination.current_page >= pagination.last_page ? 'disabled' : ''} data-page="${pagination.current_page + 1}"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>`;
        }
        
        // Gabungkan semua HTML dan tampilkan
        container.innerHTML = tableHtml + listHtml + paginationHtml;
    };


    // Memuat Provinsi saat halaman dimuat

    const loadProvinces = async () => {
        if(!apiUrls.provinces) return;
        try {
            const response = await fetch(apiUrls.provinces);
            const data = await response.json();
            populateSelect(selectProvinsi, data, 'Pilih Provinsi');
        } catch (error) {
            console.error('Gagal memuat provinsi:', error);
        }
    };



    // Event Listeners untuk dropdown
    if (selectProvinsi) {
        selectProvinsi.addEventListener('change', async () => {
            const provinceId = selectProvinsi.value;
            selectKabupaten.innerHTML = '<option value="">-- Pilih Kabupaten/Kota --</option>';
            selectKecamatan.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
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
    }

    if (selectKabupaten) {
        selectKabupaten.addEventListener('change', async () => {
            const regencyId = selectKabupaten.value;
            selectKecamatan.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
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
    }

    
    const fetchAndRenderDesa = async (page = 1) => {
        const districtId = selectKecamatan.value;
        if (!districtId) return;
        
        wilayahResultsContainer.innerHTML = `<div class="text-center p-4"><div class="spinner-border spinner-border-sm me-2"></div> Memuat data...</div>`;
        const url = apiUrls.desa.replace(':id', districtId) + `?page=${page}`;
        const response = await fetch(url);
        const result = await response.json();
        renderResults(wilayahResultsContainer, result.data, result);
    };

    if (selectKecamatan) {
        selectKecamatan.addEventListener('change', () => fetchAndRenderDesa(1));
    }
    if (wilayahResultsContainer) {
        wilayahResultsContainer.addEventListener('click', (e) => {
            const button = e.target.closest('.btn-prev, .btn-next');
            if (button) {
                const page = button.dataset.page;
                fetchAndRenderDesa(page);
            }
        });
    }

    // Logika untuk Pencarian Kata Kunci
    const fetchAndRenderSearch = async (page = 1) => {
        if (!kodeposSearchInput) return;
        const query = kodeposSearchInput.value;
        if (query.length < 3) {
            kodeposResultsContainer.innerHTML = `<div class="alert alert-info mt-3">Masukkan minimal 3 karakter untuk memulai pencarian.</div>`;
            return;
        }

        kodeposSearchBtn.disabled = true;
        kodeposSearchBtnText.textContent = 'Mencari...';
        kodeposResultsContainer.innerHTML = `<div class="text-center p-4"><div class="spinner-border spinner-border-sm me-2"></div> Mencari...</div>`;

        const url = `${apiUrls.search}?search=${query}&page=${page}`;
        const response = await fetch(url);
        const result = await response.json();
        renderResults(kodeposResultsContainer, result.data, result);

        kodeposSearchBtn.disabled = false;
        kodeposSearchBtnText.textContent = 'Cari';
    };

    if(kodeposSearchBtn) {
        kodeposSearchBtn.addEventListener('click', () => fetchAndRenderSearch(1));
    }
    if (kodeposSearchInput) {
        kodeposSearchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                fetchAndRenderSearch(1);
            }
        });
    }
    if (kodeposResultsContainer) {
        kodeposResultsContainer.addEventListener('click', (e) => {
            const button = e.target.closest('.btn-prev, .btn-next');
            if (button) {
                const page = button.dataset.page;
                fetchAndRenderSearch(page);
            }
        });
    }

    // Inisialisasi
    loadProvinces();


    // Toggle "Baca Selengkapnya"
    const toggleBtn = document.getElementById('toggle-btn');
    if(toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            const full = document.getElementById('about-full');
            if (full.classList.contains('d-none')) {
                full.classList.remove('d-none');
                this.textContent = "Tutup";
            } else {
                full.classList.add('d-none');
                this.textContent = "Baca Selengkapnya";
            }
        });
    }

});

// Global function for onclick attribute
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

@endpush

