@extends('layouts.blog')

@section('title', 'Berita & Informasi Terbaru')

@section('content')

{{-- ====================================================================== --}}
{{-- SMARTMAG STYLE ASSETS & CSS --}}
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

    body {
        font-family: var(--font-body);
        color: #333;
        background-color: #fff;
    }

    a { text-decoration: none; color: inherit; transition: 0.2s; }
    a:hover { color: var(--c-main); }

    /* 1. TYPOGRAPHY & GLOBAL */
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
        letter-spacing: 0.5px;
        padding: 4px 8px;
        background-color: var(--c-main);
        color: white;
        border-radius: 2px;
        display: inline-block;
        margin-bottom: 8px;
    }

    .post-meta {
        font-size: 11px;
        color: #999;
        margin-top: 5px;
        font-family: var(--font-body);
        text-transform: uppercase;
    }

    /* 2. TOP TICKER (NEWS FLASH) */
    .trending-ticker-wrap {
        background: var(--c-dark);
        color: #fff;
        font-size: 13px;
        padding: 8px 0;
    }
    .ticker-label {
        font-weight: 700;
        text-transform: uppercase;
        color: var(--c-main);
        margin-right: 15px;
    }
    .ticker-item { margin-right: 20px; color: #ddd; }

    /* 3. HERO SECTION (MAGAZINE LAYOUT) */
    .hero-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 4px; /* Small gap like SmartMag */
        margin-bottom: 40px;
    }
    @media(max-width: 768px) { .hero-grid { grid-template-columns: 1fr; } }

    .hero-main-card {
        position: relative;
        height: 450px;
        overflow: hidden;
    }
    .hero-main-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    .hero-main-card:hover .hero-main-img { transform: scale(1.05); }

    .hero-overlay {
        position: absolute;
        bottom: 0; left: 0; width: 100%;
        background: linear-gradient(to top, rgba(0,0,0,0.85) 10%, rgba(0,0,0,0.4) 50%, transparent 100%);
        padding: 30px;
        z-index: 2;
    }
    .hero-title-lg { font-size: 32px; color: white; margin-bottom: 10px; }
    .hero-excerpt { color: rgba(255,255,255,0.8); font-size: 14px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-family: var(--font-body); }

    /* Side List in Hero */
    .hero-side-list {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .hero-sm-card {
        position: relative;
        flex: 1;
        overflow: hidden;
        height: 223px; /* half of main + gap */
    }
    .hero-sm-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
    .hero-sm-card:hover .hero-sm-img { transform: scale(1.05); }
    .hero-sm-overlay {
        position: absolute; bottom: 0; left: 0; right: 0;
        padding: 20px;
        background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 100%);
    }
    .hero-title-sm { font-size: 18px; color: white; line-height: 1.3; }

    /* 4. BLOCK HEADERS (GARIS JUDUL) */
    .block-head {
        border-bottom: 2px solid var(--c-dark);
        margin-bottom: 25px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .block-head h4 {
        background: var(--c-dark);
        color: #fff;
        padding: 6px 12px;
        font-size: 14px;
        text-transform: uppercase;
        margin: 0;
        letter-spacing: 0.05em;
    }
    .block-head-link {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--c-gray);
    }

    /* 5. CONTENT LAYOUT (Main + Sidebar) */
    .post-list-item {
        display: flex;
        gap: 20px;
        margin-bottom: 25px;
        padding-bottom: 25px;
        border-bottom: 1px solid var(--c-border);
    }
    .post-list-item:last-child { border-bottom: none; }
    .post-list-img-wrap {
        width: 240px;
        height: 160px;
        flex-shrink: 0;
        overflow: hidden;
        border-radius: 4px;
    }
    .post-list-img { width: 100%; height: 100%; object-fit: cover; transition: opacity 0.3s; }
    .post-list-item:hover .post-list-img { opacity: 0.9; }

    .post-list-content h3 { font-size: 20px; margin-bottom: 10px; }
    .post-list-excerpt { font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 10px; }

    @media(max-width: 768px) {
        .post-list-item { flex-direction: column; gap: 15px; }
        .post-list-img-wrap { width: 100%; height: 200px; }
    }

    /* 6. SIDEBAR WIDGETS */
    .widget { margin-bottom: 40px; }
    .widget-title {
        font-size: 16px;
        border-bottom: 1px solid var(--c-border);
        padding-bottom: 10px;
        margin-bottom: 20px;
        position: relative;
    }
    .widget-title::after {
        content: ''; position: absolute; bottom: -1px; left: 0; width: 60px; height: 1px; background: var(--c-main);
    }

    .sm-list-item {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
    }
    .sm-list-img {
        width: 85px;
        height: 65px;
        object-fit: cover;
        border-radius: 3px;
        flex-shrink: 0;
    }
    .sm-list-title { font-size: 14px; font-weight: 600; line-height: 1.4; margin-bottom: 4px; }

    /* 7. PAGINATION */
    .pagination-wrap { display: flex; justify-content: center; margin-top: 40px; }
    .page-btn {
        border: 1px solid #ddd;
        color: var(--c-dark);
        padding: 8px 16px;
        margin: 0 4px;
        border-radius: 2px;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
    }
    .page-btn.active, .page-btn:hover {
        background: var(--c-dark);
        color: #fff;
        border-color: var(--c-dark);
    }

    /* Category Filter */
    .cat-nav { display: flex; gap: 15px; overflow-x: auto; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    .cat-link { font-weight: 600; font-size: 13px; color: #555; text-transform: uppercase; white-space: nowrap; }
    .cat-link.active { color: var(--c-main); }

</style>

{{-- 1. TICKER SECTION (TOP BAR) --}}
@if(isset($latestPosts) && $latestPosts->count() > 0)
<div class="trending-ticker-wrap">
    <div class="container d-flex align-items-center overflow-hidden">
        <span class="ticker-label"><i class="fas fa-bolt me-1"></i> Trending</span>
        <marquee behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">
            @foreach($latestPosts->take(5) as $post)
                <a href="{{ route('blog.posts.show', $post->slug) }}" class="ticker-item hover-underline">
                    {{ $post->title }}
                </a>
            @endforeach
        </marquee>
    </div>
</div>
@endif

<section class="py-5">
    <div class="container">

        {{-- 2. HERO SECTION (Mag Layout) --}}
        @if(isset($latestPosts) && $latestPosts->count() > 0)
        <div class="hero-grid">
            {{-- Main Big Post (Left) --}}
            @php $heroMain = $latestPosts->first(); @endphp
            <div class="hero-main-card">
                <a href="{{ route('blog.posts.show', $heroMain->slug) }}">
                    <img src="{{ asset('/storage/' . $heroMain->featured_image) }}" class="hero-main-img" alt="{{ $heroMain->title }}" onerror="this.onerror=null;this.src='https://placehold.co/800x500/333/fff?text=Headline';">
                    <div class="hero-overlay">
                        <span class="badge-category">{{ $heroMain->category->name ?? 'Utama' }}</span>
                        <h2 class="hero-title-lg">{{ $heroMain->title }}</h2>
                        <div class="post-meta text-white-50 mb-2">
                            <span><i class="far fa-user me-1"></i> {{ $heroMain->user->name ?? 'Admin' }}</span>
                            <span class="mx-2">&bull;</span>
                            <span>{{ $heroMain->created_at->format('d M, Y') }}</span>
                        </div>
                        <p class="hero-excerpt d-none d-md-block">{{ Str::limit(strip_tags($heroMain->content), 120) }}</p>
                    </div>
                </a>
            </div>

            {{-- Stacked Side Posts (Right) --}}
            <div class="hero-side-list">
                @foreach($latestPosts->skip(1)->take(2) as $sidePost)
                <div class="hero-sm-card">
                    <a href="{{ route('blog.posts.show', $sidePost->slug) }}">
                        <img src="{{ asset('/storage/' . $sidePost->featured_image) }}" class="hero-sm-img" alt="{{ $sidePost->title }}" onerror="this.onerror=null;this.src='https://placehold.co/400x300/333/fff?text=News';">
                        <div class="hero-sm-overlay">
                            <span class="badge-category" style="font-size: 9px; padding: 2px 6px;">{{ $sidePost->category->name ?? 'Info' }}</span>
                            <h3 class="hero-title-sm mt-1">{{ Str::limit($sidePost->title, 50) }}</h3>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- 3. CONTENT AREA (2 Columns) --}}
        <div class="row">

            {{-- LEFT COLUMN: LATEST NEWS --}}
            <div class="col-lg-8">

                {{-- Category Filter --}}
                <div class="block-head">
                    <h4>Berita Terbaru</h4>
                    <div class="d-none d-md-block">
                        @if(isset($categories))
                            @foreach($categories->take(4) as $cat)
                                <a href="{{ route('blog.posts.index', ['category' => $cat->slug]) }}" class="block-head-link ms-3">{{ $cat->name }}</a>
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="latest-news-list">
                    @forelse($latestPosts->skip(3) as $post)
                    <article class="post-list-item">
                        <div class="post-list-img-wrap">
                            <a href="{{ route('blog.posts.show', $post->slug) }}">
                                <img src="{{ asset('/storage/' . $post->featured_image) }}" class="post-list-img" alt="{{ $post->title }}" onerror="this.onerror=null;this.src='https://placehold.co/300x200/eee/999?text=Image';">
                            </a>
                        </div>
                        <div class="post-list-content">
                            <a href="{{ route('blog.posts.index', ['category' => $post->category->slug ?? '']) }}" class="text-decoration-none">
                                <span class="badge-category bg-white text-danger border border-danger p-1" style="font-size: 9px; padding: 2px 5px;">{{ $post->category->name ?? 'Umum' }}</span>
                            </a>
                            <h3 class="post-title">
                                <a href="{{ route('blog.posts.show', $post->slug) }}">{{ $post->title }}</a>
                            </h3>
                            <div class="post-meta mb-2">
                                <span class="me-2">By <strong>{{ $post->user->name ?? 'Admin' }}</strong></span>
                                <span><i class="far fa-clock"></i> {{ $post->created_at->format('d M Y') }}</span>
                            </div>
                            <p class="post-list-excerpt d-none d-md-block">
                                {{ Str::limit(strip_tags($post->content), 140) }}
                            </p>
                        </div>
                    </article>
                    @empty
                        <div class="alert alert-light border">Tidak ada berita lainnya.</div>
                    @endforelse
                </div>

                {{-- PAGINATION --}}
                @if ($latestPosts->hasPages())
                <div class="pagination-wrap">
                    @if ($latestPosts->onFirstPage())
                        <span class="page-btn disabled" style="opacity: 0.5">Prev</span>
                    @else
                        <a href="{{ $latestPosts->previousPageUrl() }}" class="page-btn">Prev</a>
                    @endif

                    {{-- Simple Page Numbers --}}
                    @foreach ($latestPosts->links()->elements as $element)
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $latestPosts->currentPage())
                                    <span class="page-btn active">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    @if ($latestPosts->hasMorePages())
                        <a href="{{ $latestPosts->nextPageUrl() }}" class="page-btn">Next</a>
                    @else
                        <span class="page-btn disabled" style="opacity: 0.5">Next</span>
                    @endif
                </div>
                @endif

            </div>

            {{-- RIGHT COLUMN: SIDEBAR --}}
            <div class="col-lg-4 ps-lg-5">

                {{-- Widget: Social / Subscribe --}}
                <div class="widget">
                    <div class="p-4 bg-light border rounded text-center">
                        <h5 class="fw-bold mb-3" style="font-family: var(--font-heading);">Tetap Terhubung</h5>
                        <div class="d-flex justify-content-center gap-2 mb-3">
                            <a href="#" class="btn btn-sm btn-outline-dark"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="btn btn-sm btn-outline-dark"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="btn btn-sm btn-outline-dark"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="btn btn-sm btn-outline-dark"><i class="fab fa-youtube"></i></a>
                        </div>
                        <p class="small text-muted mb-0">Ikuti kami untuk update berita terbaru setiap hari.</p>
                    </div>
                </div>

                {{-- Widget: Populer / Editor's Pick --}}
                <div class="widget">
                    <h5 class="widget-title">Editor's Picks</h5>
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

                {{-- Widget: Kategori --}}
                <div class="widget">
                    <h5 class="widget-title">Kategori</h5>
                    <div class="list-group list-group-flush">
                        @if(isset($categories))
                            @foreach($categories as $cat)
                            <a href="{{ route('blog.posts.index', ['category' => $cat->slug]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 bg-transparent border-bottom">
                                <span class="fw-semibold text-secondary">{{ $cat->name }}</span>
                                <span class="badge bg-light text-dark border rounded-pill">{{ $cat->posts_count ?? 0 }}</span>
                            </a>
                            @endforeach
                        @endif
                    </div>
                </div>

                {{-- Widget: Iklan / Banner Space --}}
                <div class="widget text-center">
                    <div style="background: #f8f9fa; border: 1px dashed #ccc; height: 250px; display: flex; align-items: center; justify-content: center; color: #999;">
                        <span>Space Iklan 300x250</span>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

@endsection
