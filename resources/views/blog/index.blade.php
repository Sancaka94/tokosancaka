@extends('layouts.blog')

@section('title', 'Berita & Informasi Terbaru')

@section('content')

{{-- LOAD GOOGLE FONTS & STYLES --}}
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Serif:wght@400;600;700&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

{{-- Masukkan Style CSS di sini atau di layout head --}}
<style>
    .category-nav-scroll { display: flex; flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; gap: 10px; padding: 10px 0; scrollbar-width: none; }
    .category-nav-scroll::-webkit-scrollbar { display: none; }
    .btn-cat-nav { flex: 0 0 auto; background-color: #fff; border: 1px solid #e0e0e0; color: #4a4a4a; padding: 8px 20px; border-radius: 50px; font-weight: 700; font-size: 13px; text-transform: uppercase; transition: all 0.2s ease; cursor: pointer; text-decoration: none; }
    .btn-cat-nav:hover { background-color: #f1f1f1; }
    .btn-cat-nav.active { background-color: #2563eb; color: #fff; border-color: #2563eb; }
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

        {{-- BAGIAN BARU: BUTTON NAVIGATION (Seperti Gambar) --}}
        {{-- Tombol ini akan men-trigger scroll via Javascript di bawah --}}
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

        {{-- 3. LOOP CATEGORIES (Target Scroll) --}}
        <div id="category-container">
            @foreach($categories as $category)
                @if($category->posts_count > 0)
                    {{-- Tambahkan ID di sini agar bisa ditemukan oleh tombol --}}
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

    {{-- 4. BOTTOM GRID SECTION --}}
    @include('blog.partials.bottom_grid')

    {{-- 5. PAGINATION --}}
    @if(method_exists($latestPosts, 'links'))
        <div class="d-flex justify-content-center mt-5">
            {{ $latestPosts->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    @endif

</div>

{{-- SCRIPT SCROLL & ACTIVE STATE --}}
<script>
    function scrollToCategory(e, targetId) {
        e.preventDefault();

        // 1. ACTIVE STATE: Pindahkan warna biru
        document.querySelectorAll('.btn-cat-nav').forEach(el => el.classList.remove('active'));
        let clickedButton = e.currentTarget;
        clickedButton.classList.add('active');

        // 2. MENU SCROLL: Geser menu navigasi agar tombol yang diklik berada di tengah
        clickedButton.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
            inline: 'center' // KUNCI: Membuat tombol aktif geser ke tengah layar
        });

        // 3. PAGE SCROLL: Geser halaman ke konten artikel di bawah
        if (targetId === 'top') {
            const container = document.querySelector('.hero-wrap');
            if(container) {
                container.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } else {
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                // Offset -120px supaya tidak tertutup header
                const y = targetElement.getBoundingClientRect().top + window.pageYOffset - 120;
                window.scrollTo({top: y, behavior: 'smooth'});
            }
        }
    }
</script>

@endsection
