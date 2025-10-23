@extends('layouts.app')



@section('title', 'Selamat Datang di Sancaka Express')



@push('styles')

<style>

    :root {

        --google-blue: #1a73e8;

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



    .service-card {

        text-align: center;

        padding: 2rem;

        background-color: #ffffff;

        border-radius: 0.75rem;

        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);

        height: 100%;

    }



    .partner-logo-card {

        padding: 1rem;

        background-color: #ffffff;

        border-radius: 0.75rem;

        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);

        text-align: center;

        transition: transform 0.2s, box-shadow 0.2s;

        height: 100%;

        display: flex;

        align-items: center;

        justify-content: center;

    }

    .partner-logo-card:hover {

        transform: translateY(-5px);

        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);

    }

    .partner-logo-card img {

        max-height: 60px;

        width: 100%;

        object-fit: contain;

    }



    .testimonial-card {

        background-color: #ffffff;

        border-radius: 0.75rem;

        padding: 2rem;

        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);

        height: 100%;

        display: flex;

        flex-direction: column;

    }



/* Semua tombol floating */

.floating-btn {

position: fixed;

width: 50px;

 height: 50px;

border-radius: 50%;

display: flex;

align-items: center;

justify-content: center;

box-shadow: 0 4px 12px rgba(0,0,0,0.2);

 z-index: 1000;

 color: white;

font-size: 24px; /* untuk FA icon */

cursor: pointer;

 transition: transform 0.2s, background-color 0.3s, bottom 0.3s ease-in-out;

}



/* Hover effect */

.floating-btn:hover {

transform: scale(1.1);

color: white;

}



/* Tombol WA */

.wa-float {

 bottom: 30px; /* Default position */

 right: 30px;

 background-color: #25d366;

}



/* Tombol Scroll */

#scrollTopBtn {

bottom: 30px;

 right: 30px;

background-color: #2563eb;

 display: none; /* Initially hidden */

}



/* Samakan ukuran ikon SVG / FA di dalam tombol */

.floating-btn svg,

.floating-btn i {

width: 22px;

 height: 22px;

 font-size: 22px; /* untuk Font Awesome */

}

    /* ===== STYLING UNTUK FITUR PENCARIAN ALAMAT BARU ===== */

    .address-search-container .nav-pills .nav-link {

        border-radius: 0.5rem;

        font-weight: 500;

        transition: all 0.2s ease-in-out;

    }



    .address-search-container .nav-pills .nav-link.active {

        background-color: var(--google-blue);

        color: white;

        box-shadow: 0 4px 12px rgba(26, 115, 232, 0.25);

    }

    

    .address-search-container .form-select,

    .address-search-container .form-control {

        border-radius: 0.5rem;

    }



    .address-search-container .form-select:disabled {

        background-color: #f3f4f6;

        cursor: not-allowed;

    }

    

    .address-search-container .results-table {

        margin-top: 1.5rem;

        border-radius: 0.75rem;

        box-shadow: 0 2px 10px rgba(0,0,0,0.07);

    }

    /* ===== AKHIR STYLING FITUR PENCARIAN ALAMAT BARU ===== */

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

        <div class="row g-4 row-cols-1 row-cols-lg-3">

            <div class="col">

                <div class="action-card h-100">

                    <h5 class="fw-bold"><i class="fa-solid fa-magnifying-glass me-2"></i>Lacak Kiriman Anda</h5>

                    <p class="text-muted small">Pantau posisi paket Anda secara akurat, real-time dan fast respond.</p>

                    <form action="{{ route('tracking.search') }}" method="GET" class="mt-auto">

                        <div class="input-group">

                            <input type="text" name="resi" class="form-control form-control-lg" placeholder="Cek Resi" required>

                            <button class="btn btn-primary px-4" type="submit" style="background-color: var(--google-blue); border-color: var(--google-blue);">

                                <i class="fas fa-arrow-right"></i>

                            </button>

                        </div>

                    </form>

                </div>

            </div>

            <div class="col">

                <div class="action-card clickable h-100" data-bs-toggle="modal" data-bs-target="#cekOngkirModal">

                    <h5 class="fw-bold"><i class="fa-solid fa-calculator me-2"></i>Cek Estimasi Ongkir</h5>

                    <p class="text-muted small">Hitung biaya pengiriman secara transparan sebelum mengirim.</p>

                    <div class="d-grid mt-auto">

                        <button class="btn btn-warning btn-lg" type="button">Cek Ongkir</button>

                    </div>

                </div>

            </div>

            <div class="col">

                <a href="#cek-alamat" class="text-decoration-none text-dark">

                    <div class="action-card clickable h-100">

                        <h5 class="fw-bold"><i class="fa-solid fa-map-pin me-2"></i>Cek Kode Pos & Wilayah</h5>

                        <p class="text-muted small">Temukan informasi kode pos dan detail wilayah di seluruh Indonesia.</p>

                        <div class="d-grid mt-auto">

                            <button class="btn btn-danger btn-lg text-white" type="button">Cek Alamat</button>

                        </div>

                    </div>

                </a>

            </div>

        </div>

    </div>

</div>



{{-- ====================================================================== --}}

{{-- == BAGIAN PENCARIAN ALAMAT & KODE POS (VERSI BOOTSTRAP 5 + JS) == --}}

{{-- ====================================================================== --}}

<section id="cek-alamat" class="section bg-light pt-5">

    <div class="container">

        <h2 class="section-title">Cek Alamat & Kode Pos Akurat</h2>

        <p class="text-center text-muted mb-5">Temukan informasi alamat lengkap dan kode pos di seluruh Indonesia.</p>



        <div class="card shadow-sm address-search-container">

            <div class="card-body p-4 p-lg-5">

                <!-- Navigasi Tab Bootstrap -->

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

                    <!-- Konten Tab 1: Pencarian Berdasarkan Wilayah -->

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



                    <!-- Konten Tab 2: Pencarian Berdasarkan Kode Pos/Kata Kunci -->

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

<section id="tentang" class="section bg-light">

    <div class="container">

        <h2 class="section-title">Kenapa Memilih Sancaka Express?</h2>

        <div class="row align-items-center g-5">

            <div class="col-lg-6">

                <img src="https://tokosancaka.biz.id/storage/uploads/logo.jpeg" class="img-fluid rounded" alt="Profil Sancaka Express">

            </div>

            <div class="col-lg-6">

                <h3 class="fw-bold mb-3">CV. SANCAKA KARYA HUTAMA</h3>

                <p class="lead text-muted mb-4">

                    <em>"Lebih dari Sekadar Jasa Kirim, Kami Adalah Mitra Pertumbuhan Bisnis Anda."</em>

                </p>

                <div id="about-short">

                    <h5 class="fw-bold mt-4">Tentang Kami</h5>

                    <p>

                        <strong>Sancaka Express</strong> merupakan perusahaan jasa pengiriman yang berdedikasi

                        untuk memberikan solusi logistik terbaik bagi individu maupun pelaku bisnis.

                    </p>

                </div>

                <p>

                    Dengan jaringan distribusi yang luas serta didukung oleh tim profesional dan berpengalaman,

                    kami berkomitmen untuk memastikan setiap paket sampai ke tujuan dengan cepat, aman, dan tepat waktu.

                </p>

                <p>

                    Kami memahami bahwa pengiriman bukan sekadar memindahkan barang, melainkan juga menjaga kepercayaan pelanggan.

                    Oleh karena itu, setiap layanan kami dirancang untuk memberikan pengalaman yang nyaman,

                    transparan, dan dapat diandalkan.

                </p>

                <div id="about-full" class="d-none">

                    <h5 class="fw-bold mt-4">Visi</h5>

                    <p>

                        Menjadi mitra logistik terdepan yang mengutamakan kepuasan pelanggan melalui inovasi teknologi,

                        efisiensi layanan, dan keunggulan operasional.

                    </p>

                    <h5 class="fw-bold mt-4">Misi</h5>

                    <ul class="list-unstyled">

                        <li>1. Menyediakan layanan pengiriman yang cepat, aman, dan tepat waktu.</li>

                        <li>2. Membangun kepercayaan pelanggan melalui layanan prima dan komunikasi transparan.</li>

                        <li>3. Mengembangkan teknologi logistik untuk mendukung pertumbuhan bisnis pelanggan.</li>

                        <li>4. Menjadi mitra strategis dalam rantai pasok, dari skala individu hingga korporasi.</li>

                    </ul>

                </div>

                <button id="toggle-btn" class="btn btn-outline-primary btn-sm mt-3">

                    Baca Selengkapnya

                </button>

            </div>

        </div>

    </div>

</section>



{{-- ====================================================================== --}}

{{-- == BAGIAN BLOG DENGAN DESAIN BARU == --}}

{{-- ====================================================================== --}}

<section id="blog" class="section">

    <div class="container">

        <h2 class="section-title">Berita & Informasi Terbaru</h2>

        

        <!-- ========== FORM PENCARIAN ========== -->

        <div class="mb-5">

            <form action="{{ url()->current() }}" method="GET" class="max-w-xl mx-auto">

                <div class="input-group input-group-lg">

                    <input type="search" name="search" class="form-control" placeholder="Cari artikel berdasarkan judul atau konten..." value="{{ request('search') }}" aria-label="Cari Artikel">

                    <button class="btn btn-primary px-4" type="submit" id="button-addon2"><i class="fas fa-search"></i></button>

                </div>

            </form>

        </div>

        <!-- ========== AKHIR FORM PENCARIAN ========== -->

        

        {{-- Jika sedang dalam mode pencarian, tampilkan judul hasil pencarian --}}

        @if(request()->has('search') && request()->input('search') != '')

            <h4 class="mb-4 fw-bold">Hasil pencarian untuk: "{{ request('search') }}"</h4>

        @endif



        @if($headline && !request()->filled('search'))

        <!-- Bagian Headline Utama (Hanya tampil jika tidak sedang mencari) -->

        <div class="row g-4 mb-5">

            <!-- Artikel Utama (Kiri) -->

            <div class="col-lg-8">

                <div class="card post-card shadow-sm h-100">

                    <a href="{{ route('blog.posts.show', $headline->slug) }}" class="text-decoration-none text-dark">

                        <img src="{{ Storage::url($headline->featured_image) }}"

                             class="card-img-top post-card-img"

                             onerror="this.onerror=null;this.src='https://placehold.co/800x450/1a73e8/ffffff?text=Headline';"

                             alt="{{ $headline->title }}">

                        <div class="card-body p-4">

                            <small class="text-primary fw-bold">{{ $headline->category->name ?? 'UMUM' }}</small>

                            <h3 class="card-title fw-bold mt-2">{{ $headline->title }}</h3>

                            <p class="card-text text-muted">{{ Str::limit(strip_tags($headline->content), 1000) }}</p>

                            <small class="text-muted">{{ $headline->created_at->diffForHumans() }}</small>

                        </div>

                    </a>

                </div>

            </div>



            <!-- 4 Artikel Kecil (Kanan) -->

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



        <!-- Berita Terbaru & Sidebar Populer -->

        <div class="row g-5">

            <!-- Kolom Berita Terbaru (Kiri) -->

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

                                    <h5 class="card-title fw-bold">

                                        <a href="{{ route('blog.posts.show', $post->slug) }}" class="text-decoration-none text-dark">{{ $post->title }}</a>

                                    </h5>

                                    <p class="card-text text-muted small">{{ Str::limit(strip_tags($post->content), 500) }}</p>

                                    <div class="d-flex justify-content-between align-items-center mt-3">

                                        <small class="text-muted">{{ $post->created_at->diffForHumans() }} by {{ $post->author->nama_lengkap ?? 'Admin' }}</small>

                                        <a href="{{ route('blog.posts.show', $post->slug) }}" class="btn btn-sm btn-outline-primary">Baca Selengkapnya</a>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                @empty

                    <div class="alert alert-warning">

                        Tidak ada artikel yang cocok dengan pencarian Anda. Silakan coba kata kunci lain.

                    </div>

                @endforelse



                <div class="d-flex flex-column align-items-center mt-4">

                    {{-- Info jumlah data --}}

                    <div class="mb-2 text-muted">

                        Menampilkan {{ $latestPosts->firstItem() }} - {{ $latestPosts->lastItem() }}

                        dari total {{ $latestPosts->total() }} post

                    </div>



                    {{-- Tombol pagination --}}

                    <div>

                        {{ $latestPosts->appends(request()->query())->links('pagination::bootstrap-5') }}

                    </div>

                </div>

            </div>



            <!-- Sidebar (Kanan) -->

            <div class="col-lg-4">

                <div class="card shadow-sm sticky-top" style="top: 2rem;">

                    <div class="card-header bg-primary text-white">

                        <h5 class="mb-0 fw-bold">Terpopuler</h5>

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



<!-- Layanan Section -->

<section id="layanan" class="section">

    <div class="container">

        <h2 class="section-title">Layanan Kami</h2>

        <div class="row g-4">

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-box fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">Reguler & COD</h5>

                    <p>Pengiriman paket reguler dan Cash on Delivery ke seluruh pelosok Indonesia dengan cepat dan aman.</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-truck-moving fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">Cargo</h5>

                    <p>Solusi pengiriman barang dalam jumlah besar dengan tarif kompetitif dan penanganan profesional.</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-motorcycle fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">Pengiriman Motor</h5>

                    <p>Layanan khusus untuk pengiriman sepeda motor antar kota dan provinsi dengan jaminan keamanan.</p>

                </div>

            </div>

            <h2 class="section-title">Keunggulan Kami</h2>

            <p class="text-lg text-gray-600 mb-5 text-center">Fleksibilitas, jangkauan terluas, dan harga terbaik untuk pengiriman Anda.</p>

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-bolt fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">Efisien Dan Efektif</h5>

                    <p>Paket bisa dijemput langsung atau antar ke agen. Pilihan layanannya dari — instan, same-day, express, sampai kargo.</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-credit-card fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">COD Tanpa Marketplace</h5>

                    <p>Banyak pilihan ekspedisi buat kirim paket COD tanpa lewat marketplace. Dana COD langsung cair real-time, gampang banget!</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-undo-alt fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">Retur Rendah</h5>

                    <p>Dengan Return Management System yang sudah terintegrasi, jadi lebih mudah kelola retur dan ngurangin risiko paket gagal kirim.</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-headset fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">Support 24 Jam</h5>

                    <p>Tim kami selalu siap bantu kapan aja kalau ada kendala atau masalah dalam proses pengiriman, biar semuanya tetap lancar.</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-plug fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">Integrasi API & Plugin</h5>

                    <p>Jadi makin gampang ke toko online-mu lewat API, plugin WooCommerce, sampai Shopify — semua tinggal pakai!</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-card">

                    <div class="icon"><i class="fas fa-chart-line fa-2x text-blue-500"></i></div>

                    <h5 class="fw-bold">Dashboard Monitoring</h5>

                    <p>Mulai dari pengelolaan paket, pelacakan real-time, data pelanggan lengkap, sampai laporan keuangan yang detail — semua bisa dalam satu tempat.</p>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- Jasa Layanan Kami Section -->

<section id="layanan" class="section">

    <div class="container">

        <h2 class="section-title">Didukung Jaringan Ekspedisi Ternama</h2>

        <p class="text-lg text-gray-600 mb-5 text-center">Fleksibilitas, jangkauan terluas, dan harga terbaik untuk pengiriman Anda.</p>

        <div class="row g-4 justify-content-center">

            <!-- J&T Express -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20J%26T%20Express." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://upload.wikimedia.org/wikipedia/commons/0/01/J%26T_Express_logo.svg" alt="Logo J&amp;T Express" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- JNE -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20JNE." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://upload.wikimedia.org/wikipedia/commons/9/92/New_Logo_JNE.png" alt="Logo JNE" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- POS Indonesia -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20POS%20Indonesia." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://kiriminaja.com/assets/home-v4/pos.png" alt="Logo POS Indonesia" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- Indah Cargo -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20Indah%20Cargo." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png" alt="Logo Indah Cargo" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- SAP Express -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20SAP%20Express." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://kiriminaja.com/assets/home-v4/sap.png" alt="Logo SAP Express" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- ID Express -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20ID%20Express." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://kiriminaja.com/assets/home-v4/id-express.png" alt="Logo ID Express" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- J&T Cargo -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20J%26T%20Cargo." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://i.pinimg.com/736x/22/cf/92/22cf92368c1f901d17e38e99061f4849.jpg" alt="Logo J&amp;T Cargo" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- Lion Parcel -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20Lion%20Parcel." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://kiriminaja.com/assets/home-v4/lion.png" alt="Logo Lion Parcel" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- SPX Express -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20SPX%20Express." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://images.seeklogo.com/logo-png/49/1/spx-express-indonesia-logo-png_seeklogo-499970.png" alt="Logo SPX Express" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- Sicepat -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20Sicepat." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://kiriminaja.com/assets/home-v4/sicepat.png" alt="Logo Sicepat" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- NCS Kurir -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20NCS%20Kurir." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjxj3iyyZEjK2L4A4yCIr_E-4W3hF2lk_yb-t0Oj2oFPErCPCMHie5LHqps02xMb6sNa-Gqz5NSX_P_hzWlYpUpJUlCD4iN6_QxiSG9fzY4bsZ9XvLFDn7HCiORtNvIlPfuQbSSdW96p7x7uN8ek3FWyHW9c2bznrFBQkoLd5A9sVAFVKWLfUhT3Dxh/s320/GKL41_NCS%20Kurir%20-%20Koleksilogo.com.jpg" alt="Logo NCS Kurir" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- Ninja Express -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20Ninja%20Express." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://kiriminaja.com/assets/home-v4/ninja.png" alt="Logo Ninja Express" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            <!-- Anteraja -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20Anteraja." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://kiriminaja.com/assets/home-v4/anter-aja.png" alt="Logo Anteraja" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

             <!-- TIKI -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20TIKI." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://kiriminaja.com/assets/home-v4/tiki.png" alt="Logo TIKI" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

             <!-- Sentral Cargo -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20Sentral%20Cargo." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://kiriminaja.com/assets/home-v4/central-cargo.png" alt="Logo Sentral Cargo" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            

            <!-- Borzo -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20Borzo." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://kiriminaja.com/assets/home-v4/borzo.png" alt="Logo Borzo" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            

            <!-- GoSend -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20GoSend." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://kiriminaja.com/assets/home-v4/gosend.png" alt="Logo GoSend" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            

            <!-- GrabExpress -->

            <div class="col-6 col-md-4 col-lg-2">

                <a href="https://wa.me/6285745808809?text=Halo%20CV.%20Sancaka%20Karya%20Hutama,%20saya%20tertarik%20untuk%20menggunakan%20layanan%20GrabExpress." target="_blank" rel="noopener noreferrer" class="text-decoration-none">

                    <div class="partner-logo-card">

                        <img src="https://kiriminaja.com/assets/home-v4/grab.svg" alt="Logo GrabExpress" onerror="this.src='https://placehold.co/150x60/CCCCCC/333333?text=Logo+Error';">

                    </div>

                </a>

            </div>

            

        </div>

    </div>

</section>



<!-- Testimoni Section -->

<section id="testimoni" class="section bg-light">

    <div class="container">

        <h2 class="section-title">Apa Kata Mereka Tentang Kami?</h2>

        <p class="text-center text-muted mb-5">Kepuasan Anda adalah prioritas utama kami. Lihat pengalaman nyata dari pelanggan setia kami.</p>

        <div class="row g-4">

            <div class="col-md-4">

                <div class="testimonial-card">

                    <p>"Pelayanannya cepat banget! Paket saya sampai keesokan harinya dalam kondisi baik. Stafnya ramah dan sangat membantu. Recommended!"</p>

                    <div class="mt-auto">

                        <h5 class="mt-3 mb-0">Andi Pratama</h5>

                        <small class="text-muted">Pengusaha Online</small>

                    </div>

                </div>

            </div>

            <div class="col-md-4">

                <div class="testimonial-card">

                    <p>"Pakai layanan cargo untuk kirim stok barang ke luar pulau, biayanya sangat terjangkau dan timnya sangat profesional. Semua barang aman tanpa lecet. Mantap!"</p>

                    <div class="mt-auto">

                        <h5 class="mt-3 mb-0">Citra Lestari</h5>

                        <small class="text-muted">Pemilik Toko</small>

                    </div>

                </div>

            </div>

            <div class="col-md-4">

                <div class="testimonial-card">

                    <p>"Fitur lacak resinya akurat dan mudah digunakan. Saya jadi tenang tahu posisi paket saya di mana setiap saat. Tidak perlu was-was lagi."</p>

                    <div class="mt-auto">

                        <h5 class="mt-3 mb-0">Budi Santoso</h5>

                        <small class="text-muted">Karyawan Swasta</small>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- BAGIAN BARU: LEGALITAS & KEPERCAYAAN -->

<section id="legalitas" class="section">

    <div class="container">

        <h2 class="section-title">Legalitas & Kepercayaan</h2>

        <p class="text-center text-muted mb-5">Kami adalah mitra terpercaya yang telah <strong>terdaftar</strong> dan diakui secara resmi.</p>

        <div class="row g-4 justify-content-center">

            <!-- Gambar 1 -->

            <div class="col-6 col-md-4 col-lg-3">

                <div class="trust-logo-card">

                    <img src="https://tokosancaka.com/storage/uploads/PSE-HITAM-PUTIH.jpg" class="img-fluid" alt="Sertifikat PSE Kominfo">

                </div>

            </div>

        </div>

    </div>

</section>



<!-- KODE UNTUK BAGIAN TANYA JAWAB (Q&A) -->

<section id="qa" class="section bg-light">

    <div class="container">

        <h2 class="section-title">Tanya Jawab (Q&A)</h2>

        <div class="accordion" id="accordionQA">

            <!-- Pertanyaan 1 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingOne">

                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">

                        Bagaimana cara melacak status pengiriman paket saya?

                    </button>

                </h2>

                <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Anda dapat dengan mudah melacak paket Anda dengan memasukkan nomor resi pada kolom "Lacak Kiriman Anda" di bagian atas halaman utama kami. Status pengiriman akan ditampilkan secara real-time.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 2 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingTwo">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">

                        Apa saja area jangkauan pengiriman Sancaka Express?

                    </button>

                </h2>

                <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Kami melayani pengiriman ke seluruh wilayah di Indonesia, dari kota besar hingga ke daerah-daerah terpencil. Jaringan kami yang luas memastikan paket Anda sampai ke tujuan manapun.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 3 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingThree">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">

                        Apa perbedaan antara layanan Reguler dan Cargo?

                    </button>

                </h2>

                <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Layanan <strong>Reguler</strong> ditujukan untuk pengiriman paket dan dokumen dengan berat standar. Sedangkan layanan <strong>Cargo</strong> adalah solusi untuk pengiriman barang dalam jumlah besar, berat, atau berukuran besar dengan biaya yang lebih efisien.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 4 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingFour">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">

                        Apakah ada asuransi untuk barang kiriman?

                    </button>

                </h2>

                <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Ya, kami menyediakan opsi asuransi untuk memberikan perlindungan ekstra terhadap risiko kerusakan atau kehilangan barang berharga Anda selama proses pengiriman. Anda dapat memilih opsi ini saat melakukan input pengiriman.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 5 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingFive">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive">

                        Bagaimana cara menghitung ongkos kirim?

                    </button>

                </h2>

                <div id="collapseFive" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Ongkos kirim dihitung berdasarkan berat aktual paket atau berat volume (dimensi PxLxT), mana yang lebih besar. Anda dapat menggunakan fitur "Cek Estimasi Ongkir" di halaman utama untuk mendapatkan perkiraan biaya.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 6 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingSix">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix">

                        Apa yang harus saya lakukan jika paket saya rusak atau hilang?

                    </button>

                </h2>

                <div id="collapseSix" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Segera hubungi layanan pelanggan kami melalui WhatsApp atau telepon dengan menyertakan nomor resi dan bukti dokumentasi (foto/video). Tim kami akan membantu proses klaim Anda, terutama jika paket tersebut diasuransikan.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 7 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingSeven">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven">

                        Barang apa saja yang tidak boleh dikirim?

                    </button>

                </h2>

                <div id="collapseSeven" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Barang-barang yang dilarang termasuk (namun tidak terbatas pada): bahan mudah terbakar/meledak, narkotika, senjata tajam, hewan hidup, dan barang ilegal lainnya sesuai dengan peraturan perundang-undangan yang berlaku di Indonesia.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 8 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingEight">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight">

                        Bisakah saya mengubah alamat penerima jika paket sudah dikirim?

                    </button>

                </h2>

                <div id="collapseEight" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Perubahan alamat mungkin dapat dilakukan jika paket belum sampai di kota tujuan akhir. Namun, ini akan bergantung pada status dan lokasi paket saat itu serta mungkin dikenakan biaya tambahan. Silakan hubungi layanan pelanggan kami secepatnya untuk bantuan.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 9 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingNine">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNine">

                        Jam berapa saja operasional kantor Sancaka Express?

                    </button>

                </h2>

                <div id="collapseNine" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Kantor dan agen kami umumnya beroperasi dari hari Senin hingga Sabtu, pukul 07:00 - 17:00 WIB. Beberapa agen mungkin memiliki jam operasional yang berbeda. Untuk layanan pelanggan via WhatsApp, kami siap melayani Anda setiap hari.

                    </div>

                </div>

            </div>

            <!-- Pertanyaan 10 -->

            <div class="accordion-item">

                <h2 class="accordion-header" id="headingTen">

                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTen">

                        Bagaimana cara menjadi agen Sancaka Express?

                    </button>

                </h2>

                <div id="collapseTen" class="accordion-collapse collapse" data-bs-parent="#accordionQA">

                    <div class="accordion-body">

                        Kami sangat senang Anda tertarik untuk bergabung. Untuk informasi lebih lanjut mengenai syarat dan keuntungan menjadi agen, silakan hubungi tim kemitraan kami melalui halaman "Kontak" atau langsung via WhatsApp.

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- Kontak & Peta Section -->

<section id="kontak" class="section">

    <div class="container">

        <h2 class="section-title">Siap Mengirim? Hubungi Kami!</h2>

        <p class="text-center text-muted mb-5">Punya pertanyaan atau butuh konsultasi? Tim kami siap membantu Anda.</p>

        <div class="row g-5">

            <div class="col-lg-6">

                <h4>Kirim Pesan</h4>

                <form>

                    <div class="mb-3"><label for="contactName" class="form-label">Nama Anda</label><input type="text" class="form-control" id="contactName" required></div>

                    <div class="mb-3"><label for="contactEmail" class="form-label">Email</label><input type="email" class="form-control" id="contactEmail" required></div>

                    <div class="mb-3"><label for="contactMessage" class="form-label">Pesan</label><textarea class="form-control" id="contactMessage" rows="5" required></textarea></div>

                    <button type="submit" class="btn btn-primary">Kirim Pesan</button>

                </form>

            </div>

            <div class="col-lg-6">

                <h4>Lokasi Kami</h4>

                <div class="ratio ratio-16x9 rounded" style="overflow: hidden;">

                   <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3955.975440748102!2d111.4429948748921!3d-7.468200192510255!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e79e00023223a29%3A0xb353590595368a4!2sJl.%20Dokter%20Wahidin%20No.18a%2C%20Sidomakmur%2C%20Ketanggi%2C%20Kec.%20Ngawi%2C%20Kabupaten%20Ngawi%2C%20Jawa%20Timur%2063211!5e0!3m2!1sen!2sid!4v1720345535355!5m2!1sen!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- Floating Action Buttons -->

<a href="https://wa.me/6285745808809" class="floating-btn wa-float" target="_blank" title="Hubungi via WhatsApp"><i class="fa fa-whatsapp"></i></a>

<button id="scrollTopBtn" class="floating-btn" title="Kembali ke atas">

 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 24px; height: 24px;">

 <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />

</svg>

</button>



    <!-- Tombol Floating -->



    <a href="https://wa.me/6285745808809" class="floating-btn wa-float" target="_blank" title="Hubungi via WhatsApp">



        <i class="fab fa-whatsapp"></i>



    </a>





@endsection



@push('scripts')



<script>

document.addEventListener('DOMContentLoaded', function() {

    

    // Logika Tombol Scroll to Top (tetap sama)

    const scrollTopBtn = document.getElementById('scrollTopBtn');

    window.onscroll = () => {

        if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {

            scrollTopBtn.style.display = "block";

        } else {

            scrollTopBtn.style.display = "none";

        }

    };

    scrollTopBtn.addEventListener('click', () => {

        window.scrollTo({ top: 0, behavior: 'smooth' });

    });



    // ==================================================

    // == LOGIKA BARU UNTUK PENCARIAN ALAMAT (JS STANDAR) ==

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

        provinces: "{{ route('api.wilayah.provinces') }}",

        kabupaten: "{{ route('api.wilayah.kabupaten', ':id') }}",

        kecamatan: "{{ route('api.wilayah.kecamatan', ':id') }}",

        desa: "{{ route('api.kodepos.by-district', ':id') }}",

        search: "{{ route('api.kodepos.public.search') }}"

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



    // Fungsi untuk menampilkan hasil di tabel

    const renderResults = (container, results, pagination) => {

        if (results.length === 0) {

            container.innerHTML = `<div class="alert alert-warning mt-3">Tidak ada data ditemukan.</div>`;

            return;

        }



        let tableHtml = `

            <div class="results-table table-responsive">

                <table class="table table-hover table-striped mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>Kelurahan/Desa</th>

                            <th>Kecamatan</th>

                            <th>Kabupaten/Kota</th>

                            <th>Provinsi</th>

                            <th class="text-end">Kode Pos</th>

                        </tr>

                    </thead>

                    <tbody>

        `;

        results.forEach(item => {

            tableHtml += `

                <tr>

                    <td>${item.kelurahan_desa || '-'}</td>

                    <td>${item.kecamatan || '-'}</td>

                    <td>${item.kota_kabupaten || '-'}</td>

                    <td>${item.provinsi || '-'}</td>

                    <td class="fw-bold text-end">${item.kode_pos || '-'}</td>

                </tr>

            `;

        });

        tableHtml += `</tbody></table></div>`;



        // Tambah Paginasi

        if (pagination && pagination.total > 0) {

            tableHtml += `

                <div class="d-flex justify-content-between align-items-center p-3 mt-1 border-top bg-light">

                    <p class="text-muted small mb-0">

                        Menampilkan ${pagination.from} - ${pagination.to} dari ${pagination.total} hasil

                    </p>

                    <div>

                        <button class="btn btn-sm btn-outline-secondary btn-prev" ${pagination.current_page <= 1 ? 'disabled' : ''} data-page="${pagination.current_page - 1}">Sebelumnya</button>

                        <button class="btn btn-sm btn-outline-secondary ms-2 btn-next" ${pagination.current_page >= pagination.last_page ? 'disabled' : ''} data-page="${pagination.current_page + 1}">Berikutnya</button>

                    </div>

                </div>

            `;

        }

        container.innerHTML = tableHtml;

    };



    // Memuat Provinsi saat halaman dimuat

    const loadProvinces = async () => {

        try {

            const response = await fetch(apiUrls.provinces);

            const data = await response.json();

            populateSelect(selectProvinsi, data, 'Pilih Provinsi');

        } catch (error) {

            console.error('Gagal memuat provinsi:', error);

        }

    };



    // Event Listeners untuk dropdown

    selectProvinsi.addEventListener('change', async () => {

        const provinceId = selectProvinsi.value;

        selectKabupaten.innerHTML = '';

        selectKecamatan.innerHTML = '';

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

        selectKecamatan.innerHTML = '';

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

            const page = e.target.dataset.page;

            fetchAndRenderDesa(page);

        }

    });





    // Logika untuk Pencarian Kata Kunci

    const fetchAndRenderSearch = async (page = 1) => {

        const query = kodeposSearchInput.value;

        if (query.length < 3) {

            kodeposResultsContainer.innerHTML = `<div class="alert alert-info mt-3">Masukkan minimal 3 karakter untuk memulai pencarian.</div>`;

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

        if (e.key === 'Enter') {

            fetchAndRenderSearch(1);

        }

    });

    kodeposResultsContainer.addEventListener('click', (e) => {

        if (e.target.matches('.btn-prev, .btn-next')) {

            const page = e.target.dataset.page;

            fetchAndRenderSearch(page);

        }

    });





    // Inisialisasi

    loadProvinces();

});

</script>



<script>

document.addEventListener('DOMContentLoaded', function() {



    // Fungsi debounce untuk optimasi pencarian

    const debounce = (func, delay) => {

        let timeout;

        return (...args) => { clearTimeout(timeout); timeout = setTimeout(() => func.apply(this, args), delay); };

    };



    // Setup autocomplete untuk origin & destination

    const setupAutocomplete = (inputId, resultsId, hiddenId, hiddenSubId) => {

    const input = document.getElementById(inputId);

    const resultsContainer = document.getElementById(resultsId);

    const hiddenInput = document.getElementById(hiddenId);

    const hiddenSubInput = document.getElementById(hiddenSubId);



    resultsContainer.classList.add('list-group', 'autocomplete-results');



    const handleSearch = async (event) => {

        const query = event.target.value;

        if (query.length < 3) {

            resultsContainer.classList.add('d-none');

            return;

        }



        try {

            const response = await fetch(`{{ route('api.ongkir.address.search') }}?search=${query}`);

            if (!response.ok) throw new Error('Gagal memuat data alamat.');

            

            const result = await response.json();

            resultsContainer.innerHTML = '';



            if (Array.isArray(result) && result.length > 0) {

                resultsContainer.classList.remove('d-none');

                result.forEach(item => {

                    const div = document.createElement('div');

                    div.className = 'list-group-item list-group-item-action';

                    div.textContent = item.full_address;

                    div.dataset.id = item.district_id;

                    div.dataset.subId = item.subdistrict_id;



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



    // Form submit handler

    const shippingForm = document.getElementById('shipping-form');

    const costResultsContainer = document.getElementById('cost-results-container');

    const submitButton = document.getElementById('submit-button');



    shippingForm.addEventListener('submit', async function(event) {

        event.preventDefault();

        submitButton.disabled = true;

        submitButton.innerHTML = `

            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>

            Sedang menghitung ongkos kirim...

        `;

        costResultsContainer.innerHTML = '';



        const formData = new FormData(this);

        if (document.getElementById('insurance').checked) {

            formData.set('insurance', 'on');

        } else {

            formData.delete('insurance');

        }



        try {

            const response = await fetch("{{ route('api.ongkir.cost.check') }}", {

                method: 'POST',

                body: formData,

                headers: {

                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,

                    'Accept': 'application/json',

                }

            });



            const result = await response.json();
            console.log('Hasil API Ongkir:', result); // 🔍 Tambahkan di sini


            if (response.ok && result.success) {

                displayResults(result);

            } else {

                throw new Error(result.message || 'Terjadi kesalahan.');

            }

        } catch (error) {

            costResultsContainer.innerHTML = `

                <div class="alert alert-danger" role="alert">

                    <strong>Error!</strong> ${error.message}

                </div>

            `;

        } finally {

            submitButton.disabled = false;

            submitButton.innerHTML = 'Cek Harga';

        }

    });



    // Render hasil ongkir

    function displayResults(result) {

        const { final_weight, data } = result;

        const instantServices = data.instant || [];

        const expressCargoServices = data.express_cargo || [];



        let html = '';



        if (final_weight) {

            html += `

                <div class="alert alert-info">

                    <strong>Total Berat:</strong> ${(final_weight / 1000).toFixed(2)} kg (${final_weight.toLocaleString('id-ID')} gram)

                </div>

            `;

        }



        html += `

            <h5 class="fw-bold mb-3">Pilihan Kurir</h5>

            <div class="btn-group mb-3" role="group" aria-label="Filter layanan">

                <input type="radio" class="btn-check" name="service_filter" id="filter_regular" value="regular" checked>

                <label class="btn btn-outline-primary" for="filter_regular">Reguler</label>

                <input type="radio" class="btn-check" name="service_filter" id="filter_instant" value="instant">

                <label class="btn btn-outline-primary" for="filter_instant">Instant</label>

                <input type="radio" class="btn-check" name="service_filter" id="filter_cargo" value="trucking">

                <label class="btn btn-outline-primary" for="filter_cargo">Cargo</label>

            </div>

            <div id="service-list-container"></div>

        `;



        costResultsContainer.innerHTML = html;



        const serviceListContainer = document.getElementById('service-list-container');



        const renderServices = (filter) => {

            let servicesHtml = '';

            if (filter === 'instant') {

                servicesHtml = generateInstantHtml(instantServices);

            } else {

                const filteredServices = expressCargoServices.filter(service => {

                    const group = service.group ? service.group.toLowerCase() : '';

                    const serviceName = service.service_name ? service.service_name.toLowerCase() : '';

                    if (filter === 'trucking') {

                        return group === 'trucking' || serviceName.includes('cargo');

                    }

                    if (filter === 'regular') {

                        return group === 'regular' && !serviceName.includes('cargo');

                    }

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



    function generateExpressCargoHtml(services) {

        if (!services || services.length === 0) {

            return '<div class="alert alert-warning">Layanan tidak tersedia.</div>';

        }

        let html = `

            <div class="list-group">

        `;

        services.sort((a, b) => parseInt(a.cost) - parseInt(b.cost));

        services.forEach(service => {

            const cost = parseInt(service.cost).toLocaleString('id-ID');

            html += `

                <div class="list-group-item d-flex justify-content-between align-items-center">

                    <div>

                        <img src="https://tokosancaka.com/storage/logo-ekspedisi/${service.service}.png" 

                             alt="${service.service}" 

                             class="me-2" style="height:24px;">

                        <strong>${service.service.toUpperCase()}</strong> - ${service.service_name} <br>

                        <small class="text-muted">Estimasi: ${service.etd} Hari</small>

                    </div>

                    <div class="text-end">

                        <div class="fw-bold">Rp ${cost}</div>

                        <button class="btn btn-sm btn-danger mt-1">Pilih</button>

                    </div>

                </div>

            `;

        });

        html += '</div>';

        return html;

    }



    function generateInstantHtml(services) {

        if (!services || services.length === 0) {

            return '<div class="alert alert-warning">Layanan Instant tidak tersedia.</div>';

        }

        let html = `<div class="list-group">`;

        let allServices = [];

        services.forEach(courier => {

            if(courier.costs && Array.isArray(courier.costs)) {

                courier.costs.forEach(cost => { allServices.push({ courierName: courier.name, ...cost }); });

            }

        });

        allServices.sort((a, b) => a.price.total_price - b.price.total_price);

        allServices.forEach(service => {

            const cost = parseInt(service.price.total_price).toLocaleString('id-ID');

            html += `

                <div class="list-group-item d-flex justify-content-between align-items-center">

                    <div>

                        <img src="https://tokosancaka.com/storage/logo-ekspedisi/${service.courierName}.png" 

                             alt="${service.courierName}" 

                             class="me-2" style="height:24px;">

                        <strong>${service.courierName.toUpperCase()}</strong> - ${service.service_type}

                    </div>

                    <div class="text-end">

                        <div class="fw-bold">Rp ${cost}</div>

                        <button class="btn btn-sm btn-danger mt-1">Pilih</button>

                    </div>

                </div>

            `;

        });

        html += `</div>`;

        return html;

    }

});

</script>







    <script>

        // Ambil tombol

        var mybutton = document.getElementById("scrollTopBtn");



        // Ketika pengguna scroll ke bawah 20px dari atas dokumen, tampilkan tombol

        window.onscroll = function() {

            scrollFunction()

        };



        function scrollFunction() {

            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {

                mybutton.style.display = "block";

            } else {

                mybutton.style.display = "none";

            }

        }



        // Ketika pengguna mengklik tombol, scroll ke atas dokumen

        function scrollToTop() {

            window.scrollTo({

                top: 0,

                behavior: 'smooth'

            });

        }

    </script>



<script>

    document.getElementById('toggle-btn').addEventListener('click', function () {

        const full = document.getElementById('about-full');

        if (full.classList.contains('d-none')) {

            full.classList.remove('d-none');

            this.textContent = "Tutup";

        } else {

            full.classList.add('d-none');

            this.textContent = "Baca Selengkapnya";

        }

    });

</script>


    <!-- Memuat JS khusus untuk halaman home -->

    <script src="{{ asset('assets/js/home.js') }}"></script>

@endpush

