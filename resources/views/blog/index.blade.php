@extends('layouts.blog')

@section('title', 'Berita & Informasi Terbaru')

@section('content')

{{-- LOAD GOOGLE FONTS --}}
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Serif:wght@400;600;700&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
    /* --- 1. CONTAINER SCROLL --- */
    .category-nav-scroll {
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto; /* Wajib auto/scroll agar bisa digeser */
        -webkit-overflow-scrolling: touch; /* Smooth scroll di HP */
        gap: 10px;
        padding-bottom: 15px; /* Ruang untuk scrollbar */
        margin-bottom: 20px;

        /* Support Firefox */
        scrollbar-width: thin;
        scrollbar-color: #ff0000 #f1f1f1;
    }

    /* --- 2. KUSTOMISASI SCROLLBAR (Chrome, Edge, Safari) --- */
    .category-nav-scroll::-webkit-scrollbar {
        height: 8px; /* Tinggi scrollbar (Cukup tebal biar terlihat) */
        display: block; /* Pastikan MUNCUL */
    }

    /* Jalur Scrollbar (Track) */
    .category-nav-scroll::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    /* Batang Geser (Thumb) */
    .category-nav-scroll::-webkit-scrollbar-thumb {
        background: #ff0000; /* Warna Abu Gelap (Biar kontras) */
        border-radius: 4px;
    }

    /* Batang Geser saat disorot Mouse */
    .category-nav-scroll::-webkit-scrollbar-thumb:hover {
        background: #ff0000;
    }

    /* --- 3. STYLE TOMBOL --- */
    .btn-cat-nav {
        flex: 0 0 auto; /* Supaya ukuran tombol tidak menyusut */
        background-color: #fff;
        border: 1px solid #e0e0e0;
        color: #4a4a4a;
        padding: 8px 20px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
        white-space: nowrap; /* Teks satu baris */
    }

    .btn-cat-nav:hover {
        background-color: #f1f1f1;
        color: #333;
    }

    /* Status Aktif */
    .btn-cat-nav.active {
        background-color: #eb2525;
        color: #fff;
        border-color: #eb2525;
        box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
    }

    /* --- 3. STYLE FLOATING BUTTON (WA & UP) --- */
    .floating-container {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 9999; /* Agar selalu di paling atas layer */
        display: flex;
        flex-direction: column; /* Susun vertikal */
        gap: 15px; /* Jarak antar tombol */
    }

    .btn-float {
        width: 55px;
        height: 55px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: white !important;
        text-decoration: none;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .btn-float:hover {
        transform: scale(1.1); /* Efek membesar saat hover */
        box-shadow: 0 6px 14px rgba(0,0,0,0.4);
    }

    /* Warna Tombol WA */
    .btn-wa {
        background-color: #25D366;
    }

    /* Warna Tombol Panah Atas */
    .btn-up {
        background-color: #2563eb; /* Biru tema */
    }

</style>

@include('blog.partials.ticker')

<div class="container py-4">

    {{-- 1. SEARCH SECTION --}}
    <div class="search-section mb-4">
        <form action="{{ url('/blog') }}" method="GET">
            <div class="row g-2">
                <div class="col-md-9">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="form-control"
                            placeholder="Cari judul artikel atau konten..."
                            value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sancaka w-100 h-100">
                        CARI ARTIKEL
                    </button>
                </div>
            </div>
        </form>

        @if(request('search'))
            <div class="mt-3 small text-muted">
                Menampilkan hasil pencarian untuk: <strong>"{{ request('search') }}"</strong>
                <a href="{{ url('/blog/search/') }}" class="text-danger ms-2 text-decoration-none">
                    <i class="fas fa-times-circle"></i> Bersihkan
                </a>
            </div>
        @endif
    </div>

    {{-- 2. LOGIKA TAMPILAN --}}
    @if(isset($latestPosts) && $latestPosts->count() > 0)

        <div class="hero-wrap mb-4">
            @include('blog.partials.hero_section')
        </div>

        {{-- BAGIAN NAVIGASI KATEGORI --}}
        <div class="category-nav-scroll mb-4">
            {{-- Tombol SEMUA --}}
            <a href="#" class="btn-cat-nav active" onclick="scrollToCategory(event, 'top')">
                SEMUA
            </a>

            {{-- Loop Tombol Kategori --}}
            @foreach($categories as $category)
                @if($category->posts_count > 0)
                    <a href="#" class="btn-cat-nav" onclick="scrollToCategory(event, 'cat-{{ $category->id }}')">
                        {{ $category->name }}
                    </a>
                @endif
            @endforeach
        </div>

        {{-- 3. LOOP KONTEN KATEGORI --}}
        <div id="category-container">
            @foreach($categories as $category)
                @if($category->posts_count > 0)
                    {{-- Anchor ID --}}
                    <div id="cat-{{ $category->id }}" class="category-section-wrapper">
                        @include('blog.partials.categories_smartmag', ['category' => $category])
                    </div>

                    @if(!$loop->last)
                        <div style="border-top: 1px dashed #ccc; margin: 50px 0;"></div>
                    @endif
                @endif
            @endforeach
        </div>

    @else
        {{-- TAMPILAN JIKA KOSONG --}}
        <div class="col-12 text-center py-5">
            <div class="mb-3">
                <i class="fas fa-search-minus fa-3x text-muted"></i>
            </div>
            <h5>Maaf, artikel tidak ditemukan</h5>
            <p class="text-muted">Coba gunakan kata kunci lain atau periksa ejaan Anda.</p>
            <a href="{{ url('/blog') }}" class="btn btn-outline-danger">Lihat Semua Artikel</a>
        </div>
    @endif

    {{-- 4. BOTTOM GRID --}}
    @include('blog.partials.bottom_grid')

    {{-- 5. PAGINATION --}}
    @if(method_exists($latestPosts, 'links'))
        <div class="d-flex justify-content-center mt-5">
            {{ $latestPosts->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    @endif

</div>

{{-- === FLOATING BUTTONS SECTION (WA & SCROLL TOP) === --}}
<div class="floating-container">
    {{-- 1. Tombol Scroll Top --}}
    <a href="#" onclick="scrollToTop(event)" class="btn-float btn-up" title="Kembali ke Atas">
        <i class="fas fa-arrow-up"></i>
    </a>

    {{-- 2. Tombol WhatsApp --}}
    {{-- Format nomor: 628... (tanpa 0 di depan) --}}
    <a href="https://wa.me/6285745808809?text=Halo,%20saya%20ingin%20bertanya%20mengenai%20artikel..."
       target="_blank"
       class="btn-float btn-wa"
       title="Hubungi via WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>
</div>

{{-- SCRIPT --}}
<script>
    // Fungsi untuk Scroll ke Paling Atas
    function scrollToTop(e) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function scrollToCategory(e, targetId) {
        e.preventDefault();

        // 1. ACTIVE STATE (Pindah Warna Biru)
        document.querySelectorAll('.btn-cat-nav').forEach(el => el.classList.remove('active'));
        let clickedButton = e.currentTarget;
        clickedButton.classList.add('active');

        // 2. MENU SCROLL (Auto Center Button)
        clickedButton.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
            inline: 'center'
        });

        // 3. PAGE SCROLL (Scroll ke Konten)
        if (targetId === 'top') {
            const container = document.querySelector('.hero-wrap');
            if(container) {
                container.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } else {
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                const y = targetElement.getBoundingClientRect().top + window.pageYOffset - 120;
                window.scrollTo({top: y, behavior: 'smooth'});
            }
        }
    }
</script>

@endsection
