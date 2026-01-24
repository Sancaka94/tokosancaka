@extends('layouts.blog')

@section('title', 'Berita & Informasi Terbaru')

@section('content')

{{-- ====================================================================== --}}
{{-- SMARTMAG STYLE ASSETS & CSS (REVISED) --}}
{{-- ====================================================================== --}}
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Serif:wght@400;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --c-main: #dd0017; /* SmartMag Red */
        --c-dark: #161616;
        --c-gray: #777;
        --c-border: #e8e8e8;
        --font-heading: 'IBM Plex Serif', serif;
        --font-body: 'Inter', sans-serif;
    }

    body { font-family: var(--font-body); color: #333; background-color: #fff; }
    a { text-decoration: none; color: inherit; transition: 0.2s; }
    a:hover { color: var(--c-main); }

    /* TYPOGRAPHY */
    h1, h2, h3, h4, h5, h6, .post-title {
        font-family: var(--font-heading);
        font-weight: 700;
        line-height: 1.3;
        color: var(--c-dark);
        letter-spacing: -0.025em;
    }

    .badge-category {
        font-family: var(--font-body);
        text-transform: uppercase;
        font-size: 10px;
        font-weight: 700;
        padding: 4px 8px;
        background-color: var(--c-main);
        color: white;
        border-radius: 2px;
        display: inline-block;
        margin-bottom: 8px;
    }

    /* HEADER BLOCK STYLE (Garis Hitam Tebal Judul) */
    .block-head {
        border-bottom: 2px solid var(--c-dark);
        margin-bottom: 25px;
        margin-top: 40px; /* Jarak antar kategori */
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .block-head h4 {
        background: var(--c-dark);
        color: #fff;
        padding: 8px 15px;
        font-size: 16px;
        text-transform: uppercase;
        margin: 0;
    }
    .block-head-link { font-size: 12px; font-weight: 600; text-transform: uppercase; color: var(--c-gray); }

    /* POST LIST STYLE (Revisi Gambar 1:1) */
    .post-list-item {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--c-border); /* Garis pembatas per postingan */
    }
    .post-list-item:last-child { border-bottom: none; }

    .post-list-img-wrap {
        /* REVISI: Ukuran fix kotak 1:1 */
        width: 180px;
        height: 180px;
        aspect-ratio: 1 / 1;
        flex-shrink: 0;
        overflow: hidden;
        border-radius: 4px;
        background: #f0f0f0;
    }

    .post-list-img {
        width: 100%;
        height: 100%;
        /* REVISI: Cover agar gambar full kotak tanpa gepeng */
        object-fit: cover;
        transition: transform 0.3s;
    }
    .post-list-item:hover .post-list-img { transform: scale(1.05); }

    .post-list-content { flex: 1; }
    .post-list-content h3 { font-size: 20px; margin-bottom: 10px; margin-top: 5px; }
    .post-list-excerpt { font-size: 15px; color: #555; line-height: 1.6; margin-bottom: 10px; }
    .post-meta { font-size: 12px; color: #999; margin-top: 5px; }

    /* RESPONSIVE */
    @media(max-width: 768px) {
        .post-list-item { flex-direction: column; gap: 10px; }
        .post-list-img-wrap { width: 100%; height: auto; aspect-ratio: 16/9; } /* Mobile landscape */
    }

    /* SIDEBAR & WIDGETS */
    .widget { margin-bottom: 40px; }
    .widget-title {
        font-size: 16px;
        border-bottom: 1px solid var(--c-border);
        padding-bottom: 10px;
        margin-bottom: 20px;
        position: relative;
    }
    .widget-title::after { content: ''; position: absolute; bottom: -1px; left: 0; width: 60px; height: 1px; background: var(--c-main); }

    .sm-list-item { display: flex; gap: 15px; margin-bottom: 15px; }
    .sm-list-img { width: 85px; height: 65px; object-fit: cover; border-radius: 3px; flex-shrink: 0; }
    .sm-list-title { font-size: 14px; font-weight: 600; line-height: 1.4; margin-bottom: 4px; }

    /* HERO GRID (Opsional jika ingin dipakai) */
    .hero-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 4px; margin-bottom: 40px; }
    .hero-main-card { position: relative; height: 400px; overflow: hidden; }
    .hero-main-img { width: 100%; height: 100%; object-fit: cover; }
    .hero-overlay { position: absolute; bottom: 0; left: 0; width: 100%; background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 100%); padding: 20px; }
    .hero-title-lg { color: white; font-size: 28px; }

    /* TICKER */
    .trending-ticker-wrap { background: var(--c-dark); color: #fff; font-size: 13px; padding: 8px 0; margin-bottom: 30px; }
    .ticker-label { font-weight: 700; text-transform: uppercase; color: var(--c-main); margin-right: 15px; }
</style>

{{-- 1. TICKER SECTION (TOP BAR) --}}
@if(isset($latestPosts) && $latestPosts->count() > 0)
<div class="trending-ticker-wrap">
    <div class="container d-flex align-items-center overflow-hidden">
        <span class="ticker-label"><i class="fas fa-bolt me-1"></i> Trending</span>
        <marquee behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">
            @foreach($latestPosts->take(5) as $post)
                <a href="{{ route('blog.posts.show', $post->slug) }}" class="me-4 text-white">
                    {{ $post->title }}
                </a>
            @endforeach
        </marquee>
    </div>
</div>
@endif

<section class="py-3">
    <div class="container">

        {{-- 2. HERO SECTION (OPTIONAL - Jika ingin featured post besar di atas) --}}
        @if(isset($latestPosts) && $latestPosts->count() > 0)
        <div class="d-none d-lg-grid hero-grid">
            {{-- Main Big Post --}}
            @php $heroMain = $latestPosts->first(); @endphp
            <div class="hero-main-card">
                <a href="{{ route('blog.posts.show', $heroMain->slug) }}">
                    <img src="{{ asset('/storage/' . $heroMain->featured_image) }}" class="hero-main-img" alt="{{ $heroMain->title }}" onerror="this.onerror=null;this.src='https://placehold.co/800x500/333/fff?text=Headline';">
                    <div class="hero-overlay">
                        <span class="badge-category">{{ $heroMain->category->name ?? 'Utama' }}</span>
                        <h2 class="hero-title-lg">{{ $heroMain->title }}</h2>
                    </div>
                </a>
            </div>
            {{-- Side Small Posts --}}
            <div class="d-flex flex-column gap-1">
                @foreach($latestPosts->skip(1)->take(2) as $sidePost)
                <div style="position: relative; flex: 1; overflow: hidden;">
                    <a href="{{ route('blog.posts.show', $sidePost->slug) }}">
                        <img src="{{ asset('/storage/' . $sidePost->featured_image) }}" style="width: 100%; height: 100%; object-fit: cover;" alt="{{ $sidePost->title }}">
                        <div class="hero-overlay" style="padding: 10px;">
                            <h3 style="color: white; font-size: 16px; margin: 0;">{{ Str::limit($sidePost->title, 50) }}</h3>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- 3. CONTENT AREA --}}
        <div class="row">

            {{-- KOLOM KIRI (BERITA UTAMA & KATEGORI) --}}
            <div class="col-lg-8">

                {{-- A. BAGIAN BERITA TERBARU (10 POST) --}}
                <div class="block-head">
                    <h4>Berita Terbaru</h4>
                </div>

                <div class="latest-news-list">
                    @forelse($latestPosts->take(10) as $post)
                    <article class="post-list-item">
                        <div class="post-list-img-wrap">
                            <a href="{{ route('blog.posts.show', $post->slug) }}">
                                {{-- REVISI: Gambar 1:1 Object Cover --}}
                                <img src="{{ asset('/storage/' . $post->featured_image) }}" class="post-list-img" alt="{{ $post->title }}" onerror="this.onerror=null;this.src='https://placehold.co/300x300/eee/999?text=Image';">
                            </a>
                        </div>
                        <div class="post-list-content">
                            <a href="#" class="text-decoration-none">
                                <span class="badge-category">{{ $post->category->name ?? 'Umum' }}</span>
                            </a>
                            <h3 class="post-title">
                                <a href="{{ route('blog.posts.show', $post->slug) }}">{{ $post->title }}</a>
                            </h3>
                            <div class="post-meta mb-2">
                                <span class="me-2 text-danger fw-bold">SANCAKA</span>
                                <span><i class="far fa-clock"></i> {{ $post->created_at->format('d M Y') }}</span>
                            </div>
                            <p class="post-list-excerpt d-none d-md-block">
                                {{ Str::limit(strip_tags($post->content), 120) }}
                            </p>
                        </div>
                    </article>
                    @empty
                        <div class="alert alert-light border">Tidak ada berita terbaru.</div>
                    @endforelse
                </div>

                {{-- B. BAGIAN PER KATEGORI (5 POST PER KATEGORI) --}}
                @if(isset($categories))
                    @foreach($categories as $category)
                        {{-- Cek jika kategori memiliki postingan --}}
                        @if($category->posts && $category->posts->count() > 0)

                            <div class="block-head">
                                <h4>{{ $category->name }}</h4>
                                <a href="{{ route('blog.posts.index', ['category' => $category->slug]) }}" class="block-head-link">Lihat Semua <i class="fas fa-angle-right"></i></a>
                            </div>

                            <div class="category-news-list">
                                {{-- Loop 5 Postingan per Kategori --}}
                                @foreach($category->posts()->latest()->take(5)->get() as $post)
                                <article class="post-list-item">
                                    <div class="post-list-img-wrap">
                                        <a href="{{ route('blog.posts.show', $post->slug) }}">
                                            <img src="{{ asset('/storage/' . $post->featured_image) }}" class="post-list-img" alt="{{ $post->title }}" onerror="this.onerror=null;this.src='https://placehold.co/300x300/eee/999?text=Image';">
                                        </a>
                                    </div>
                                    <div class="post-list-content">
                                        <h3 class="post-title" style="font-size: 18px;">
                                            <a href="{{ route('blog.posts.show', $post->slug) }}">{{ $post->title }}</a>
                                        </h3>
                                        <div class="post-meta mb-2">
                                            <span><i class="far fa-clock"></i> {{ $post->created_at->format('d M Y') }}</span>
                                        </div>
                                        <p class="post-list-excerpt d-none d-md-block">
                                            {{ Str::limit(strip_tags($post->content), 100) }}
                                        </p>
                                    </div>
                                </article>
                                @endforeach
                            </div>

                        @endif
                    @endforeach
                @endif

            </div>

            {{-- KOLOM KANAN (SIDEBAR) --}}
            <div class="col-lg-4 ps-lg-5">

                {{-- Widget: Social --}}
                <div class="widget">
                    <h5 class="widget-title">Stay Connected</h5>
                    <div class="d-flex gap-2">
                        <a href="#" class="btn btn-dark w-100"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="btn btn-danger w-100"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="btn btn-info text-white w-100"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>

                {{-- Widget: Editor's Picks / Popular --}}
                <div class="widget">
                    <h5 class="widget-title">Terpopuler</h5>
                    <div class="widget-content">
                        @if(isset($topArticles))
                            @foreach($topArticles->take(5) as $article)
                            <div class="sm-list-item">
                                <a href="{{ route('blog.posts.show', $article->slug) }}">
                                    <img src="{{ asset('/storage/' . $article->featured_image) }}" class="sm-list-img" alt="img" onerror="this.onerror=null;this.src='https://placehold.co/100x100/eee/999?text=Img';">
                                </a>
                                <div>
                                    <h6 class="sm-list-title">
                                        <a href="{{ route('blog.posts.show', $article->slug) }}">{{ Str::limit($article->title, 50) }}</a>
                                    </h6>
                                    <div class="post-meta">{{ $article->created_at->format('M d, Y') }}</div>
                                </div>
                            </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                {{-- Widget: Iklan --}}
                <div class="widget">
                    <div style="background: #f8f9fa; border: 1px dashed #ccc; height: 300px; display: flex; align-items: center; justify-content: center; color: #999;">
                        <span>Space Iklan Sidebar</span>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

@endsection
