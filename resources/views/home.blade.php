@extends('layouts.app')

@section('title', 'Selamat Datang di Sancaka Express & Sancaka Store')

@push('styles')
<style>
    /* === FONT & PALET WARNA BARU === */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

    :root {
        --primary-color: #1a73e8;
        --primary-hover: #1765cc;
        --secondary-color: #ffc107;
        --light-bg: #f8f9fa;
        --dark-text: #212529;
        --muted-text: #6c757d;
        --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        --card-shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.12);
    }

    body {
        font-family: 'Inter', sans-serif;
        background-color: #fff;
    }

    /* === EFEK GRADASI & BENTUK PADA HERO === */
    .hero-section {
        background: linear-gradient(135deg, rgba(26, 115, 232, 0.9) 0%, rgba(26, 115, 232, 0.7) 100%), url('https://tokosancaka.com/storage/uploads/wellcome.jpg');
        background-size: cover;
        background-position: center center;
        color: white;
        padding: 6rem 0;
        position: relative;
        overflow: hidden;
    }
    
    .hero-section::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100%;
        height: 100px;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%23ffffff' fill-opacity='1' d='M0,160L48,176C96,192,192,224,288,213.3C384,203,480,149,576,138.7C672,128,768,160,864,176C960,192,1056,192,1152,181.3C1248,171,1344,149,1392,138.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-size: cover;
    }

    /* === KARTU AKSI DENGAN EFEK MODERN === */
    .action-card {
        background-color: #ffffff;
        border-radius: 0.75rem;
        padding: 1.5rem;
        box-shadow: var(--card-shadow);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        border: 1px solid #e5e7eb;
    }

    .action-card.clickable:hover {
        transform: translateY(-8px);
        box-shadow: var(--card-shadow-hover);
        cursor: pointer;
    }
    .action-card .btn {
        transition: background-color 0.3s ease, transform 0.2s ease;
    }
    .action-card .btn:hover {
        transform: scale(1.03);
    }
    
    /* === JUDUL SEKSI YANG LEBIH MENONJOL === */
    .section {
        padding: 5rem 0;
    }
    .section-title {
        text-align: center;
        font-weight: 800;
        margin-bottom: 0.5rem;
        color: var(--dark-text);
    }
    .section-subtitle {
        text-align: center;
        color: var(--muted-text);
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
        margin-bottom: 4rem;
    }

    /* === KARTU LAYANAN & KEUNGGULAN DENGAN IKON BARU === */
    .feature-card {
        text-align: center;
        padding: 2rem;
        background-color: #ffffff;
        border-radius: 0.75rem;
        box-shadow: var(--card-shadow);
        height: 100%;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--card-shadow-hover);
    }
    .feature-card .icon-wrapper {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: rgba(26, 115, 232, 0.1);
        color: var(--primary-color);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        font-size: 1.5rem;
    }

    /* === KARTU LOGO PARTNER & LEGALITAS === */
    .logo-card {
        padding: 1.5rem;
        background-color: #ffffff;
        border-radius: 0.75rem;
        box-shadow: var(--card-shadow);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .logo-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--card-shadow-hover);
    }
    .logo-card img {
        max-height: 60px;
        width: 100%;
        object-fit: contain;
        filter: grayscale(100%);
        opacity: 0.8;
        transition: filter 0.3s ease, opacity 0.3s ease;
    }
    .logo-card:hover img {
        filter: grayscale(0%);
        opacity: 1;
    }

    /* === KARTU TESTIMONI YANG LEBIH ELEGAN === */
    .testimonial-card {
        background-color: #ffffff;
        border-radius: 0.75rem;
        padding: 2rem;
        box-shadow: var(--card-shadow);
        height: 100%;
        display: flex;
        flex-direction: column;
        position: relative;
        border-top: 4px solid var(--primary-color);
    }
    .testimonial-card::before {
        content: '\f10d'; /* Font Awesome quote-left */
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        position: absolute;
        top: 1rem;
        left: 1.5rem;
        font-size: 2rem;
        color: rgba(26, 115, 232, 0.1);
    }

    /* === TOMBOL FLOATING === */
    .floating-btn {
        position: fixed;
        width: 55px;
        height: 55px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        z-index: 1000;
        color: white;
        font-size: 28px;
        cursor: pointer;
        transition: transform 0.2s ease, background-color 0.3s ease, bottom 0.3s ease;
    }
    .floating-btn:hover {
        transform: scale(1.1);
        color: white;
    }
    .wa-float {
        bottom: 30px;
        right: 30px;
        background-color: #25d366;
    }
    #scrollTopBtn {
        bottom: 100px;
        right: 30px;
        background-color: var(--primary-color);
        display: none;
    }

    /* === FITUR PENCARIAN ALAMAT === */
    .address-search-container .nav-pills .nav-link {
        border-radius: 0.5rem;
        font-weight: 600;
        transition: all 0.2s ease-in-out;
    }
    .address-search-container .nav-pills .nav-link.active {
        background-color: var(--primary-color);
        color: white;
        box-shadow: 0 4px 12px rgba(26, 115, 232, 0.25);
    }
    .address-search-container .form-select:disabled {
        background-color: #e9ecef;
    }
    .address-search-container .results-table {
        margin-top: 1.5rem;
        border-radius: 0.75rem;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.07);
    }

    /* === BLOG SECTION === */
    .post-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-radius: 0.75rem;
        overflow: hidden;
    }
    .post-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--card-shadow-hover);
    }
    .post-card-img {
        aspect-ratio: 16/9;
        object-fit: cover;
    }

    /* === ACCORDION Q&A === */
    .accordion-button:not(.collapsed) {
        background-color: rgba(26, 115, 232, 0.1);
        color: var(--dark-text);
        font-weight: 600;
    }
    .accordion-item {
        border-radius: 0.5rem !important;
        overflow: hidden;
        margin-bottom: 1rem;
        border: 1px solid #e5e7eb;
    }
    .accordion-button:focus {
        box-shadow: 0 0 0 0.25rem rgba(26, 115, 232, 0.25);
    }

</style>
@endpush

@section('content')

<!-- Hero Section -->
<section class="hero-section">
    <div class="container text-center py-5">
        <h1 class="display-3 fw-bold">Solusi Logistik Terlengkap dan Terpercaya</h1>
        <p class="lead mt-3 col-lg-8 mx-auto">Kirim paket, kargo, hingga motor ke seluruh Indonesia dengan mudah, cepat, dan aman bersama Sancaka Express.</p>
    </div>
</section>

<!-- Action Bar Section -->
<div class="container" style="margin-top: -90px; position: relative; z-index: 10;">
    <div class="action-bar">
        <div class="row g-4">
            <div class="col-lg-4 col-md-12">
                <div class="action-card h-100">
                    <h5 class="fw-bold"><i class="fas fa-search-location me-2 text-primary"></i>Lacak Kiriman Anda</h5>
                    <p class="text-muted small mb-3">Pantau posisi paket Anda secara akurat dan real-time.</p>
                    <form action="{{ route('tracking.search') }}" method="GET" class="mt-auto">
                        <div class="input-group">
                            <input type="text" name="resi" class="form-control form-control-lg" placeholder="Masukkan nomor resi..." required>
                            <button class="btn btn-primary px-4" type="submit" style="background-color: var(--primary-color); border-color: var(--primary-color);">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="action-card clickable h-100" data-bs-toggle="modal" data-bs-target="#cekOngkirModal">
                    <h5 class="fw-bold"><i class="fas fa-calculator me-2 text-warning"></i>Cek Estimasi Ongkir</h5>
                    <p class="text-muted small mb-3">Hitung biaya pengiriman secara transparan sebelum mengirim.</p>
                    <div class="d-grid mt-auto">
                        <button class="btn btn-warning btn-lg" type="button">Cek Ongkir Sekarang</button>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <a href="#cek-alamat" class="text-decoration-none text-dark">
                    <div class="action-card clickable h-100">
                        <h5 class="fw-bold"><i class="fas fa-map-marked-alt me-2 text-success"></i>Cek Kode Pos & Wilayah</h5>
                        <p class="text-muted small mb-3">Temukan informasi kode pos dan detail wilayah di seluruh Indonesia.</p>
                        <div class="d-grid mt-auto">
                            <button class="btn btn-success btn-lg text-white" type="button">Cek Alamat Sekarang</button>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Cek Alamat Section -->
<section id="cek-alamat" class="section bg-light pt-5">
    <div class="container">
        <h2 class="section-title">Cek Alamat & Kode Pos Akurat</h2>
        <p class="section-subtitle">Temukan informasi alamat lengkap dan kode pos di seluruh Indonesia dengan mudah melalui pencarian wilayah atau kata kunci.</p>
        <div class="card shadow-sm address-search-container">
            <div class="card-body p-4 p-lg-5">
                <!-- Navigasi Tab -->
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
                    <div class="tab-pane fade show active" id="wilayah-tab-pane" role="tabpanel" aria-labelledby="wilayah-tab" tabindex="0">
                        <div class="row g-3">
                            <div class="col-md-4"><label for="select-provinsi" class="form-label fw-bold">Provinsi</label><select id="select-provinsi" class="form-select"></select></div>
                            <div class="col-md-4"><label for="select-kabupaten" class="form-label fw-bold">Kabupaten/Kota</label><select id="select-kabupaten" class="form-select" disabled></select></div>
                            <div class="col-md-4"><label for="select-kecamatan" class="form-label fw-bold">Kecamatan</label><select id="select-kecamatan" class="form-select" disabled></select></div>
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

<!-- Keunggulan Section -->
<section id="keunggulan" class="section">
    <div class="container">
        <h2 class="section-title">Kenapa Memilih Sancaka Express?</h2>
        <p class="section-subtitle">Kami bukan sekadar jasa kirim, kami adalah mitra pertumbuhan bisnis Anda yang menawarkan fleksibilitas, jangkauan terluas, dan harga terbaik.</p>
        <div class="row g-4">
            @php
                $features = [
                    ['icon' => 'fa-bolt', 'title' => 'Efisien & Efektif', 'text' => 'Paket bisa dijemput atau diantar ke agen. Pilihan layanan lengkap dari instan, same-day, express, hingga kargo.'],
                    ['icon' => 'fa-hand-holding-dollar', 'title' => 'COD Tanpa Marketplace', 'text' => 'Kirim paket COD dengan mudah. Dana langsung cair real-time, tanpa perantara marketplace.'],
                    ['icon' => 'fa-rotate-left', 'title' => 'Retur Rendah', 'text' => 'Return Management System terintegrasi memudahkan kelola retur dan mengurangi risiko paket gagal kirim.'],
                    ['icon' => 'fa-headset', 'title' => 'Support 24 Jam', 'text' => 'Tim kami selalu siap membantu kapan saja jika ada kendala dalam proses pengiriman Anda.'],
                    ['icon' => 'fa-plug-circle-bolt', 'title' => 'Integrasi API & Plugin', 'text' => 'Hubungkan toko online Anda dengan mudah melalui API, plugin WooCommerce, hingga Shopify.'],
                    ['icon' => 'fa-chart-pie', 'title' => 'Dashboard Monitoring', 'text' => 'Kelola paket, lacak real-time, hingga lihat laporan keuangan detail dalam satu platform terpusat.'],
                ];
            @endphp
            @foreach ($features as $feature)
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="icon-wrapper"><i class="fas {{ $feature['icon'] }}"></i></div>
                    <h5 class="fw-bold">{{ $feature['title'] }}</h5>
                    <p class="text-muted">{{ $feature['text'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Partner Ekspedisi Section -->
<section id="partner" class="section bg-light">
    <div class="container">
        <h2 class="section-title">Didukung Jaringan Ekspedisi Ternama</h2>
        <p class="section-subtitle">Kami bekerjasama dengan puluhan mitra ekspedisi terpercaya untuk memberikan Anda jangkauan terluas dan harga terbaik.</p>
        <div class="row g-4 justify-content-center">
            @php
                $partners = [
                    ['url' => 'https://upload.wikimedia.org/wikipedia/commons/0/01/J%26T_Express_logo.svg', 'name' => 'J&T Express', 'wa_text' => 'J&T Express'],
                    ['url' => 'https://upload.wikimedia.org/wikipedia/commons/9/92/New_Logo_JNE.png', 'name' => 'JNE', 'wa_text' => 'JNE'],
                    ['url' => 'https://kiriminaja.com/assets/home-v4/pos.png', 'name' => 'POS Indonesia', 'wa_text' => 'POS Indonesia'],
                    ['url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png', 'name' => 'Indah Cargo', 'wa_text' => 'Indah Cargo'],
                    ['url' => 'https://kiriminaja.com/assets/home-v4/sap.png', 'name' => 'SAP Express', 'wa_text' => 'SAP Express'],
                    ['url' => 'https://assets.bukalapak.com/beagle/images/courier_logo/id-express.png', 'name' => 'ID Express', 'wa_text' => 'ID Express'],
                    ['url' => 'https://i.pinimg.com/736x/22/cf/92/22cf92368c1f901d17e38e99061f4849.jpg', 'name' => 'J&T Cargo', 'wa_text' => 'J&T Cargo'],
                    ['url' => 'https://kiriminaja.com/assets/home-v4/lion.png', 'name' => 'Lion Parcel', 'wa_text' => 'Lion Parcel'],
                    ['url' => 'https://images.seeklogo.com/logo-png/49/1/spx-express-indonesia-logo-png_seeklogo-499970.png', 'name' => 'SPX Express', 'wa_text' => 'SPX Express'],
                    ['url' => 'https://kiriminaja.com/assets/home-v4/sicepat.png', 'name' => 'Sicepat', 'wa_text' => 'Sicepat'],
                    ['url' => 'https://kiriminaja.com/assets/home-v4/ninja.png', 'name' => 'Ninja Express', 'wa_text' => 'Ninja Express'],
                    ['url' => 'https://kiriminaja.com/assets/home-v4/anter-aja.png', 'name' => 'Anteraja', 'wa_text' => 'Anteraja'],
                ];
            @endphp
            @foreach($partners as $partner)
            <div class="col-4 col-md-3 col-lg-2">
                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20{{ urlencode($partner['wa_text']) }}." target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                    <div class="logo-card">
                        <img src="{{ $partner['url'] }}" alt="Logo {{ $partner['name'] }}" onerror="this.src='https://placehold.co/150x60/e9ecef/6c757d?text=Logo';">
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Blog Section -->
<section id="blog" class="section">
    <div class="container">
        <h2 class="section-title">Berita & Informasi Terbaru</h2>
        <p class="section-subtitle">Dapatkan wawasan, tips, dan berita terkini dari dunia logistik langsung dari para ahli di Sancaka Express.</p>
        
        <!-- Search Form -->
        <div class="mb-5">
            <form action="{{ url()->current() }}#blog" method="GET" class="mw-xl mx-auto">
                <div class="input-group input-group-lg">
                    <input type="search" name="search" class="form-control" placeholder="Cari artikel berdasarkan judul atau konten..." value="{{ request('search') }}" aria-label="Cari Artikel">
                    <button class="btn btn-primary px-4" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
        
        @if(request()->has('search') && request()->input('search') != '')
            <h4 class="mb-4 fw-bold">Hasil pencarian untuk: "{{ request('search') }}"</h4>
        @endif

        @if($headline && !request()->filled('search'))
        <!-- Headline Section -->
        <div class="row g-4 mb-5">
            <div class="col-lg-8">
                <div class="card post-card shadow-sm h-100">
                    <a href="{{ route('blog.posts.show', $headline->slug) }}" class="text-decoration-none text-dark">
                        <img src="{{ Storage::url($headline->featured_image) }}" class="card-img-top post-card-img" onerror="this.onerror=null;this.src='https://placehold.co/800x450/1a73e8/ffffff?text=Headline';" alt="{{ $headline->title }}">
                        <div class="card-body p-4">
                            <small class="text-primary fw-bold">{{ $headline->category->name ?? 'UMUM' }}</small>
                            <h3 class="card-title fw-bold mt-2">{{ $headline->title }}</h3>
                            <p class="card-text text-muted">{{ Str::limit(strip_tags($headline->content), 200) }}</p>
                            <small class="text-muted">{{ $headline->created_at->diffForHumans() }}</small>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="d-flex flex-column gap-3 h-100">
                    @foreach($topArticles as $article)
                    <a href="{{ route('blog.posts.show', $article->slug) }}" class="text-decoration-none text-dark">
                        <div class="card post-card shadow-sm flex-row h-100">
                           <img src="{{ Storage::url($article->featured_image) }}" class="w-25" style="aspect-ratio: 1/1; object-fit: cover;" onerror="this.onerror=null;this.src='https://placehold.co/100x100/CCCCCC/FFFFFF?text=Image';" alt="{{ $article->title }}">
                            <div class="card-body d-flex align-items-center p-3">
                                <h6 class="fw-bold small mb-0">{{ $article->title }}</h6>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Latest Posts & Sidebar -->
        <div class="row g-5">
            <div class="col-lg-8">
                <h4 class="fw-bold mb-4">{{ request()->filled('search') ? 'Hasil Ditemukan' : 'Lainnya dari Blog Kami' }}</h4>
                @forelse($latestPosts as $post)
                <div class="card post-card mb-4 shadow-sm">
                    <div class="row g-0">
                        <div class="col-md-4">
                            <a href="{{ route('blog.posts.show', $post->slug) }}">
                                <img src="{{ Storage::url($post->featured_image) }}" class="img-fluid rounded-start h-100 post-card-img" onerror="this.onerror=null;this.src='https://placehold.co/400x250/CCCCCC/FFFFFF?text=Image';" alt="{{ $post->title }}">
                            </a>
                        </div>
                        <div class="col-md-8">
                            <div class="card-body">
                                <h5 class="card-title fw-bold"><a href="{{ route('blog.posts.show', $post->slug) }}" class="text-decoration-none text-dark">{{ $post->title }}</a></h5>
                                <p class="card-text text-muted small">{{ Str::limit(strip_tags($post->content), 150) }}</p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <small class="text-muted">{{ $post->created_at->diffForHumans() }} by {{ $post->author->nama_lengkap ?? 'Admin' }}</small>
                                    <a href="{{ route('blog.posts.show', $post->slug) }}" class="btn btn-sm btn-outline-primary">Baca</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="alert alert-warning">Tidak ada artikel yang cocok dengan pencarian Anda.</div>
                @endforelse

                <div class="d-flex flex-column align-items-center mt-4">
                    <div class="mb-2 text-muted"><small>Menampilkan {{ $latestPosts->firstItem() }} - {{ $latestPosts->lastItem() }} dari total {{ $latestPosts->total() }} post</small></div>
                    <div>{{ $latestPosts->appends(request()->query())->links('pagination::bootstrap-5') }}</div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 2rem;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-fire me-2"></i>Terpopuler</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse($popularPosts as $key => $post)
                        <a href="{{ route('blog.posts.show', $post->slug) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <span class="fw-bold me-3 fs-4 text-muted">{{ $key + 1 }}</span>
                            <span>{{ $post->title }}</span>
                        </a>
                        @empty
                        <li class="list-group-item">Belum ada artikel populer.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Q&A Section -->
<section id="qa" class="section bg-light">
    <div class="container">
        <h2 class="section-title">Tanya Jawab (Q&A)</h2>
        <p class="section-subtitle">Temukan jawaban atas pertanyaan yang paling sering diajukan mengenai layanan kami.</p>
        <div class="accordion" id="accordionQA">
             @php
                $qas = [
                    ['q' => 'Bagaimana cara melacak status pengiriman paket saya?', 'a' => 'Anda dapat dengan mudah melacak paket Anda dengan memasukkan nomor resi pada kolom "Lacak Kiriman Anda" di bagian atas halaman utama kami. Status pengiriman akan ditampilkan secara real-time.', 'show' => true],
                    ['q' => 'Apa saja area jangkauan pengiriman Sancaka Express?', 'a' => 'Kami melayani pengiriman ke seluruh wilayah di Indonesia, dari kota besar hingga ke daerah-daerah terpencil. Jaringan kami yang luas memastikan paket Anda sampai ke tujuan manapun.'],
                    ['q' => 'Apa perbedaan antara layanan Reguler dan Cargo?', 'a' => 'Layanan <strong>Reguler</strong> ditujukan untuk pengiriman paket dan dokumen dengan berat standar. Sedangkan layanan <strong>Cargo</strong> adalah solusi untuk pengiriman barang dalam jumlah besar, berat, atau berukuran besar dengan biaya yang lebih efisien.'],
                    ['q' => 'Apakah ada asuransi untuk barang kiriman?', 'a' => 'Ya, kami menyediakan opsi asuransi untuk memberikan perlindungan ekstra terhadap risiko kerusakan atau kehilangan barang berharga Anda. Anda dapat memilih opsi ini saat melakukan input pengiriman.'],
                    ['q' => 'Bagaimana cara menghitung ongkos kirim?', 'a' => 'Ongkos kirim dihitung berdasarkan berat aktual paket atau berat volume (dimensi PxLxT), mana yang lebih besar. Gunakan fitur "Cek Estimasi Ongkir" di halaman utama untuk mendapatkan perkiraan biaya.'],
                    ['q' => 'Apa yang harus saya lakukan jika paket saya rusak atau hilang?', 'a' => 'Segera hubungi layanan pelanggan kami melalui WhatsApp dengan menyertakan nomor resi dan bukti dokumentasi (foto/video). Tim kami akan membantu proses klaim Anda, terutama jika paket tersebut diasuransikan.'],
                ];
            @endphp
            @foreach($qas as $index => $qa)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button @if(!$loop->first) collapsed @endif" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}">
                        {{ $qa['q'] }}
                    </button>
                </h2>
                <div id="collapse{{ $index }}" class="accordion-collapse collapse @if($loop->first) show @endif" data-bs-parent="#accordionQA">
                    <div class="accordion-body">{{ $qa['a'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Kontak & Peta Section -->
<section id="kontak" class="section">
    <div class="container">
        <h2 class="section-title">Siap Mengirim? Hubungi Kami!</h2>
        <p class="section-subtitle">Punya pertanyaan atau butuh konsultasi? Tim kami selalu siap membantu Anda menemukan solusi pengiriman terbaik.</p>
        <div class="row g-5">
            <div class="col-lg-6">
                <div class="card shadow-sm h-100"><div class="card-body p-4"><iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3955.975440748102!2d111.4429948748921!3d-7.468200192510255!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e79e00023223a29%3A0xb353590595368a4!2sJl.%20Dokter%20Wahidin%20No.18a%2C%20Sidomakmur%2C%20Ketanggi%2C%20Kec.%20Ngawi%2C%20Kabupaten%20Ngawi%2C%20Jawa%20Timur%2063211!5e0!3m2!1sen!2sid!4v1720345535355!5m2!1sen!2sid" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div></div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm h-100"><div class="card-body p-4">
                    <h4 class="fw-bold mb-3">Kirim Pesan Langsung</h4>
                    <form>
                        <div class="mb-3"><label for="contactName" class="form-label">Nama Anda</label><input type="text" class="form-control" id="contactName" required></div>
                        <div class="mb-3"><label for="contactEmail" class="form-label">Email</label><input type="email" class="form-control" id="contactEmail" required></div>
                        <div class="mb-3"><label for="contactMessage" class="form-label">Pesan</label><textarea class="form-control" id="contactMessage" rows="5" required></textarea></div>
                        <button type="submit" class="btn btn-primary btn-lg">Kirim Pesan</button>
                    </form>
                </div></div>
            </div>
        </div>
    </div>
</section>

<!-- Floating Action Buttons -->
<a href="https://wa.me/6285745808809" class="floating-btn wa-float" target="_blank" title="Hubungi via WhatsApp"><i class="fab fa-whatsapp"></i></a>
<button id="scrollTopBtn" class="floating-btn" title="Kembali ke atas">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 24px; height: 24px;">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
    </svg>
</button>

@endsection

@push('scripts')
{{-- Gabungkan semua skrip menjadi satu untuk efisiensi --}}
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ===== SCROLL TO TOP BUTTON =====
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    if (scrollTopBtn) {
        window.addEventListener('scroll', () => {
            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                scrollTopBtn.style.display = "block";
            } else {
                scrollTopBtn.style.display = "none";
            }
        });
        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ===== READ MORE BUTTON (TENTANG KAMI) =====
    const toggleBtn = document.getElementById('toggle-btn');
    if (toggleBtn) {
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

    // ===== PENCARIAN ALAMAT & KODE POS =====
    const addressSearchContainer = document.getElementById('cek-alamat');
    if (addressSearchContainer) {
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

        const renderResults = (container, results, pagination) => {
            if (results.length === 0) {
                container.innerHTML = `<div class="alert alert-warning mt-3">Tidak ada data ditemukan.</div>`;
                return;
            }
            let tableHtml = `<div class="results-table table-responsive"><table class="table table-hover table-striped mb-0"><thead class="table-light"><tr><th>Kelurahan/Desa</th><th>Kecamatan</th><th>Kabupaten/Kota</th><th>Provinsi</th><th class="text-end">Kode Pos</th></tr></thead><tbody>`;
            results.forEach(item => {
                tableHtml += `<tr><td>${item.kelurahan_desa || '-'}</td><td>${item.kecamatan || '-'}</td><td>${item.kota_kabupaten || '-'}</td><td>${item.provinsi || '-'}</td><td class="fw-bold text-end">${item.kode_pos || '-'}</td></tr>`;
            });
            tableHtml += `</tbody></table></div>`;
            if (pagination && pagination.total > pagination.per_page) {
                tableHtml += `<div class="d-flex justify-content-between align-items-center p-3 mt-1 border-top bg-light"><p class="text-muted small mb-0">Menampilkan ${pagination.from} - ${pagination.to} dari ${pagination.total} hasil</p><div><button class="btn btn-sm btn-outline-secondary btn-prev" ${pagination.current_page <= 1 ? 'disabled' : ''} data-page="${pagination.current_page - 1}">Sebelumnya</button><button class="btn btn-sm btn-outline-secondary ms-2 btn-next" ${pagination.current_page >= pagination.last_page ? 'disabled' : ''} data-page="${pagination.current_page + 1}">Berikutnya</button></div></div>`;
            }
            container.innerHTML = tableHtml;
        };

        const loadProvinces = async () => {
            try {
                const response = await fetch(apiUrls.provinces);
                const data = await response.json();
                populateSelect(selectProvinsi, data, 'Pilih Provinsi');
            } catch (error) { console.error('Gagal memuat provinsi:', error); }
        };

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
                fetchAndRenderDesa(e.target.dataset.page);
            }
        });

        const fetchAndRenderSearch = async (page = 1) => {
            const query = kodeposSearchInput.value;
            if (query.length < 3) {
                kodeposResultsContainer.innerHTML = `<div class="alert alert-info mt-3">Masukkan minimal 3 karakter.</div>`;
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
            if (e.key === 'Enter') { e.preventDefault(); fetchAndRenderSearch(1); }
        });
        kodeposResultsContainer.addEventListener('click', (e) => {
            if (e.target.matches('.btn-prev, .btn-next')) {
                fetchAndRenderSearch(e.target.dataset.page);
            }
        });

        loadProvinces();
    }
    
    // ===== CEK ONGKIR MODAL =====
    const cekOngkirModal = document.getElementById('cekOngkirModal');
    if (cekOngkirModal) {
        const debounce = (func, delay) => {
            let timeout;
            return (...args) => { clearTimeout(timeout); timeout = setTimeout(() => func.apply(this, args), delay); };
        };

        const setupAutocomplete = (inputId, resultsId, hiddenId, hiddenSubId) => {
            const input = document.getElementById(inputId);
            const resultsContainer = document.getElementById(resultsId);
            const hiddenInput = document.getElementById(hiddenId);
            const hiddenSubInput = document.getElementById(hiddenSubId);
            resultsContainer.classList.add('list-group', 'autocomplete-results');
            const handleSearch = async (event) => {
                const query = event.target.value;
                if (query.length < 3) { resultsContainer.classList.add('d-none'); return; }
                try {
                    const response = await fetch(`{{ route('api.ongkir.address.search') }}?search=${query}`);
                    if (!response.ok) throw new Error('Gagal memuat data alamat.');
                    const result = await response.json();
                    resultsContainer.innerHTML = '';
                    if (Array.isArray(result) && result.length > 0) {
                        result.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'list-group-item list-group-item-action';
                            div.textContent = item.full_address;
                            div.addEventListener('click', () => {
                                input.value = item.full_address;
                                hiddenInput.value = item.district_id;
                                hiddenSubInput.value = item.subdistrict_id;
                                resultsContainer.classList.add('d-none');
                            });
                            resultsContainer.appendChild(div);
                        });
                    } else {
                        resultsContainer.innerHTML = `<div class="list-group-item text-muted">Alamat tidak ditemukan.</div>`;
                    }
                    resultsContainer.classList.remove('d-none');
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

        const shippingForm = document.getElementById('shipping-form');
        const costResultsContainer = document.getElementById('cost-results-container');
        const submitButton = document.getElementById('submit-button');

        shippingForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            submitButton.disabled = true;
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Menghitung...`;
            costResultsContainer.innerHTML = '';
            const formData = new FormData(this);
            try {
                const response = await fetch("{{ route('api.ongkir.cost.check') }}", {
                    method: 'POST', body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json',
                    }
                });
                const result = await response.json();
                if (response.ok && result.success) { displayResults(result); }
                else { throw new Error(result.message || 'Terjadi kesalahan.'); }
            } catch (error) {
                costResultsContainer.innerHTML = `<div class="alert alert-danger"><strong>Error!</strong> ${error.message}</div>`;
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Cek Harga';
            }
        });

        function displayResults(result) {
            const { final_weight, data } = result;
            let html = `<div class="alert alert-info"><strong>Total Berat Dihitung:</strong> ${(final_weight / 1000).toFixed(2)} kg</div><h5 class="fw-bold mb-3">Pilihan Kurir</h5><div class="btn-group mb-3 w-100" role="group"><input type="radio" class="btn-check" name="service_filter" id="filter_regular" value="regular" checked><label class="btn btn-outline-primary" for="filter_regular">Reguler</label><input type="radio" class="btn-check" name="service_filter" id="filter_instant" value="instant"><label class="btn btn-outline-primary" for="filter_instant">Instant</label><input type="radio" class="btn-check" name="service_filter" id="filter_cargo" value="trucking"><label class="btn btn-outline-primary" for="filter_cargo">Cargo</label></div><div id="service-list-container"></div>`;
            costResultsContainer.innerHTML = html;
            const serviceListContainer = document.getElementById('service-list-container');
            const renderServices = (filter) => {
                let servicesHtml = '';
                if (filter === 'instant') {
                    servicesHtml = generateInstantHtml(data.instant || []);
                } else {
                    const filteredServices = (data.express_cargo || []).filter(service => {
                        const group = (service.group || '').toLowerCase();
                        const serviceName = (service.service_name || '').toLowerCase();
                        if (filter === 'trucking') return group === 'trucking' || serviceName.includes('cargo');
                        if (filter === 'regular') return group === 'regular' && !serviceName.includes('cargo');
                        return false;
                    });
                    servicesHtml = generateExpressCargoHtml(filteredServices);
                }
                serviceListContainer.innerHTML = servicesHtml;
            };
            document.querySelectorAll('input[name="service_filter"]').forEach(radio => {
                radio.addEventListener('change', (e) => renderServices(e.target.value));
            });
            renderServices('regular');
        }

        function generateServiceHtml(services, isInstant) {
            if (!services || services.length === 0) {
                return '<div class="alert alert-warning mt-2">Layanan tidak tersedia untuk rute ini.</div>';
            }
            let allServices = [];
            if (isInstant) {
                services.forEach(c => c.costs && Array.isArray(c.costs) && c.costs.forEach(cost => allServices.push({ courierName: c.name, ...cost })));
                allServices.sort((a, b) => a.price.total_price - b.price.total_price);
            } else {
                allServices = [...services].sort((a, b) => parseInt(a.cost) - parseInt(b.cost));
            }

            let html = '<div class="list-group">';
            allServices.forEach(service => {
                const name = isInstant ? service.courierName : service.service;
                const serviceName = isInstant ? service.service_type : service.service_name;
                const etd = isInstant ? 'Beberapa Jam' : `${service.etd} Hari`;
                const cost = isInstant ? service.price.total_price : service.cost;

                html += `<div class="list-group-item d-flex justify-content-between align-items-center"><div class="me-2"><img src="https://tokosancaka.com/storage/logo-ekspedisi/${name}.png" alt="${name}" class="me-2" style="height:24px; width: auto;"><strong class="text-uppercase">${name}</strong> - ${serviceName}<br><small class="text-muted">Estimasi: ${etd}</small></div><div class="text-end"><div class="fw-bold fs-5">Rp ${parseInt(cost).toLocaleString('id-ID')}</div><button class="btn btn-sm btn-danger mt-1">Pilih</button></div></div>`;
            });
            html += '</div>';
            return html;
        }
        const generateExpressCargoHtml = (services) => generateServiceHtml(services, false);
        const generateInstantHtml = (services) => generateServiceHtml(services, true);
    }
});
</script>
@endpush
