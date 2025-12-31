@extends('layouts.blog')

@section('title', 'Berita & Informasi Terbaru')

@section('content')

{{-- ====================================================================== --}}
{{-- CUSTOM CSS --}}
{{-- ====================================================================== --}}
<style>
    /* 1. Carousel Hero Styling */
    .hero-img {
        height: 500px;
        object-fit: cover;
        /* filter: brightness(0.6);  <-- BARIS INI DIHAPUS AGAR GAMBAR CERAH */
    }
    .carousel-caption {
        /* Gradient diperkuat agar teks tetap terbaca di atas gambar terang */
        background: linear-gradient(to top, rgba(0,0,0,0.9) 20%, rgba(0,0,0,0.6) 60%, transparent 100%);
        bottom: 0; left: 0; width: 100%; padding: 40px 20px 20px 20px; text-align: left;
    }

    /* 2. Scrollable List (Kolom Kanan) */
    .scroll-list-container { height: 500px; overflow-y: auto; }
    .scroll-list-container::-webkit-scrollbar { width: 5px; }
    .scroll-list-container::-webkit-scrollbar-track { background: #f1f1f1; }
    .scroll-list-container::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
    .scroll-list-container::-webkit-scrollbar-thumb:hover { background: #aaa; }

    /* 3. Category Horizontal Scroll */
    .category-scroll {
        display: flex; flex-wrap: nowrap; overflow-x: auto; gap: 10px; padding-bottom: 10px;
        -webkit-overflow-scrolling: touch;
    }
    .category-scroll::-webkit-scrollbar { height: 4px; }
    .category-scroll::-webkit-scrollbar-thumb { background: #0d6efd; border-radius: 10px; }
    .cat-pill {
        white-space: nowrap; padding: 8px 20px; border-radius: 50px; background: #fff;
        border: 1px solid #dee2e6; color: #333; text-decoration: none; transition: 0.3s;
    }
    .cat-pill:hover, .cat-pill.active {
        background: #0d6efd; color: white; border-color: #0d6efd;
    }

    /* 4. Card & Image Fixes */
    .card-hover:hover {
        transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; transition: all 0.3s ease;
    }
    .img-hover-zoom { overflow: hidden; }
    .img-hover-zoom img { transition: transform 0.5s ease; }
    .card-hover:hover .img-hover-zoom img { transform: scale(1.02); }
    
    .card-img-grid {
        width: 100%; height: auto; min-height: 200px; object-fit: contain; background-color: #f8f9fa;
    }

    /* 5. Pagination Styling */
    .custom-pagination-wrapper { display: flex; justify-content: center; margin-top: 30px; margin-bottom: 50px; font-family: sans-serif; }
    .custom-pagination { display: flex; list-style: none; padding: 0; margin: 0; gap: 6px; align-items: center; }
    .custom-pagination li a, .custom-pagination li span {
        display: flex; align-items: center; justify-content: center; min-width: 36px; height: 36px;
        padding: 0 12px; font-size: 14px; font-weight: 500; color: #555; background-color: #fff;
        border: 1px solid #e0e0e0; border-radius: 50px; text-decoration: none; transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .custom-pagination li a:hover { border-color: #3b82f6; color: #3b82f6; background-color: #f0f7ff; transform: translateY(-1px); }
    .custom-pagination li.active span { background-color: #3b82f6; color: #fff; border-color: #3b82f6; box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3); }
    .custom-pagination li.disabled span { background-color: #f9fafb; color: #ccc; cursor: not-allowed; border-color: #eee; }

    /* 6. STYLE RUNNING SLIDER */
    .running-news-container {
        overflow: hidden; position: relative; width: 100%; background: #fff;
        border-top: 4px solid #dc3545; border-bottom: 1px solid #dee2e6; padding: 20px 0;
    }
    .running-label {
        position: absolute; top: 0; left: 0; background: #dc3545; color: white;
        padding: 2px 10px; font-size: 10px; font-weight: bold; text-transform: uppercase;
        z-index: 10; border-bottom-right-radius: 5px;
    }
    .running-track {
        display: flex; gap: 15px; width: max-content; animation: scroll-left 40s linear infinite;
    }
    .running-track:hover { animation-play-state: paused; }
    @keyframes scroll-left {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
    .news-box {
        width: 180px;
        flex-shrink: 0; border: 1px solid #f1f1f1; border-radius: 8px;
        overflow: hidden; background: #fff; transition: transform 0.2s;
    }
    .news-box:hover { transform: scale(1.05); border-color: #dc3545; z-index: 5; }
    
    .news-box-img { 
        width: 100%; height: 100px; object-fit: contain; background: #f8f9fa;
    }
    
    .news-box-body { padding: 8px; }
    .news-box-title {
        font-size: 11px; font-weight: bold; line-height: 1.3; color: #333; margin-bottom: 0;
        display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
    }
</style>

<section class="py-4 bg-light">
    <div class="container">

        {{-- 1. HERO CAROUSEL --}}
        @if(isset($latestPosts) && $latestPosts->count() > 0)
        <div id="heroCarousel" class="carousel slide mb-5 shadow rounded overflow-hidden" data-bs-ride="carousel">
            <div class="carousel-indicators">
                @foreach($latestPosts->take(5) as $key => $post)
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="{{ $key }}" class="{{ $key == 0 ? 'active' : '' }}" aria-current="true"></button>
                @endforeach
            </div>
            <div class="carousel-inner">
                @foreach($latestPosts->take(5) as $key => $post)
                <div class="carousel-item {{ $key == 0 ? 'active' : '' }}">
                    <a href="{{ route('blog.posts.show', $post->slug) }}">
                        {{-- HERO IMG TIDAK LAGI GELAP --}}
                        <img src="{{ asset('/storage/' . $post->featured_image) }}" class="d-block w-100 hero-img" alt="{{ $post->title }}" onerror="this.onerror=null;this.src='https://placehold.co/1200x500/1a73e8/ffffff?text=Sancaka+News';">
                        <div class="carousel-caption">
                            <span class="badge bg-danger mb-2">{{ $post->category->name ?? 'Info' }}</span>
                            <h2 class="fw-bold text-white">{{ $post->title }}</h2>
                            <p class="d-none d-md-block text-light">{{ Str::limit(strip_tags($post->content), 120) }}</p>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>
        </div>
        @endif

        {{-- 2. MAIN SECTION (SPLIT LAYOUT) --}}
        <div class="row g-4 mb-5">
            <div class="col-12"><h3 class="fw-bold border-start border-5 border-primary ps-3 mb-0">Berita Pilihan</h3></div>
            <div class="col-lg-8">
                @if($headline)
                <div class="card bg-dark text-white border-0 shadow h-100 overflow-hidden card-hover">
                    <a href="{{ route('blog.posts.show', $headline->slug) }}" class="text-white">
                        {{-- Opacity 0.7 dihapus agar gambar cerah --}}
                        <img src="{{ asset('/storage/' . $headline->featured_image) }}" class="card-img h-100" style="object-fit: cover; height: 500px;" alt="{{ $headline->title }}" onerror="this.onerror=null;this.src='https://placehold.co/800x500/333/fff?text=Headline';">
                        {{-- Gradient overlay dipertahankan agar teks terbaca --}}
                        <div class="card-img-overlay d-flex flex-column justify-content-end p-4" style="background: linear-gradient(to top, rgba(0,0,0,0.9) 10%, rgba(0,0,0,0.5) 50%, transparent 100%);">
                            <span class="badge bg-primary w-auto align-self-start mb-2">{{ $headline->category->name ?? 'Utama' }}</span>
                            <h2 class="card-title fw-bold display-6">{{ $headline->title }}</h2>
                            <p class="card-text d-none d-md-block">{{ Str::limit(strip_tags($headline->content), 150) }}</p>
                            <small><i class="fas fa-clock me-1"></i> {{ $headline->created_at->diffForHumans() }}</small>
                        </div>
                    </a>
                </div>
                @else <div class="alert alert-info">Belum ada berita utama.</div> @endif
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow h-100">
                    <div class="card-header bg-white fw-bold py-3 border-bottom">Terpopuler & Terbaru</div>
                    <div class="card-body p-0 scroll-list-container">
                        <div class="list-group list-group-flush">
                            @foreach($topArticles as $article)
                            <a href="{{ route('blog.posts.show', $article->slug) }}" class="list-group-item list-group-item-action d-flex gap-3 py-3 border-bottom-0">
                                <img src="{{ asset('/storage/' . $article->featured_image) }}" alt="img" class="rounded flex-shrink-0" width="80" height="80" style="object-fit: cover;" onerror="this.onerror=null;this.src='https://placehold.co/80x80/eee/999?text=Img';">
                                <div class="w-100">
                                    <h6 class="mb-1 fw-bold small text-dark">{{ Str::limit($article->title, 55) }}</h6>
                                    <small class="text-muted" style="font-size: 0.75rem;">{{ $article->created_at->format('d M, H:i') }}</small>
                                </div>
                            </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. RUNNING SLIDER (POSISI DI ATAS KATEGORI) --}}
        <div class="mb-5">
            <div class="running-news-container">
                <div class="running-label">Berita Lainnya</div>
                <div class="running-track">
                    {{-- LOOP 1 --}}
                    @foreach($latestPosts->take(10) as $post)
                    <a href="{{ route('blog.posts.show', $post->slug) }}" class="text-decoration-none">
                        <div class="news-box shadow-sm">
                            <img src="{{ asset('/storage/' . $post->featured_image) }}" class="news-box-img" alt="{{ $post->title }}" onerror="this.onerror=null;this.src='https://placehold.co/180x100/eee/999?text=News';">
                            <div class="news-box-body">
                                <p class="news-box-title" title="{{ $post->title }}">{{ Str::limit($post->title, 50) }}</p>
                                <small class="text-muted" style="font-size: 9px;">{{ $post->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    </a>
                    @endforeach
                    {{-- LOOP 2 (Duplikasi) --}}
                    @foreach($latestPosts->take(10) as $post)
                    <a href="{{ route('blog.posts.show', $post->slug) }}" class="text-decoration-none">
                        <div class="news-box shadow-sm">
                            <img src="{{ asset('/storage/' . $post->featured_image) }}" class="news-box-img" alt="{{ $post->title }}" onerror="this.onerror=null;this.src='https://placehold.co/180x100/eee/999?text=News';">
                            <div class="news-box-body">
                                <p class="news-box-title" title="{{ $post->title }}">{{ Str::limit($post->title, 50) }}</p>
                                <small class="text-muted" style="font-size: 9px;">{{ $post->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 4. CATEGORY SLIDER --}}
        <div class="mb-5">
            <h5 class="fw-bold mb-3">Jelajahi Kategori</h5>
            <div class="category-scroll">
                @if(isset($categories))
                    <a href="{{ route('blog.posts.index') }}" class="cat-pill active">Semua</a>
                    @foreach($categories as $cat)
                        <a href="{{ route('blog.posts.index', ['category' => $cat->slug]) }}" class="cat-pill">{{ $cat->name }}</a>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- 5. ARTIKEL LAINNYA (GRID SYSTEM) --}}
        <div class="row g-4">
            <div class="col-12"><h4 class="fw-bold border-start border-5 border-danger ps-3">Artikel Lainnya</h4></div>
            @forelse($latestPosts->skip(5) as $post)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm card-hover">
                    <div class="img-hover-zoom">
                        <a href="{{ route('blog.posts.show', $post->slug) }}">
                            <img src="{{ asset('/storage/' . $post->featured_image) }}" class="card-img-top card-img-grid" alt="{{ $post->title }}" onerror="this.onerror=null;this.src='https://placehold.co/400x220/eee/999?text=Artikel';">
                        </a>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="mb-2"><span class="badge bg-light text-primary border">{{ $post->category->name ?? 'Umum' }}</span></div>
                        <h5 class="card-title fw-bold">
                            <a href="{{ route('blog.posts.show', $post->slug) }}" class="text-decoration-none text-dark stretched-link">{{ Str::limit($post->title, 60) }}</a>
                        </h5>
                        <p class="card-text text-muted small flex-grow-1">{{ Str::limit(strip_tags($post->content), 100) }}</p>
                    </div>
                    <div class="card-footer bg-white border-0 pt-0 pb-3"><small class="text-muted"><i class="far fa-clock me-1"></i> {{ $post->created_at->diffForHumans() }}</small></div>
                </div>
            </div>
            @empty
            <div class="col-12 py-5 text-center"><p class="text-muted">Belum ada artikel tambahan.</p></div>
            @endforelse

            {{-- PAGINATION --}}
            @if ($latestPosts->hasPages())
            <div class="custom-pagination-wrapper">
                <ul class="custom-pagination">
                    @if ($latestPosts->onFirstPage()) <li class="disabled" aria-disabled="true"><span>&lsaquo; Prev</span></li> @else <li><a href="{{ $latestPosts->previousPageUrl() }}" rel="prev">&lsaquo; Prev</a></li> @endif
                    @foreach ($latestPosts->links()->elements as $element)
                        @if (is_string($element)) <li class="disabled" aria-disabled="true"><span>{{ $element }}</span></li> @endif
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $latestPosts->currentPage()) <li class="active" aria-current="page"><span>{{ $page }}</span></li> @else <li><a href="{{ $url }}">{{ $page }}</a></li> @endif
                            @endforeach
                        @endif
                    @endforeach
                    @if ($latestPosts->hasMorePages()) <li><a href="{{ $latestPosts->nextPageUrl() }}" rel="next">Next &rsaquo;</a></li> @else <li class="disabled" aria-disabled="true"><span>Next &rsaquo;</span></li> @endif
                </ul>
            </div>
            @endif
        </div>
        
    </div>
</section>

@endsection