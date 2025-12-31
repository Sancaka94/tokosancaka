@extends('layouts.blog')

@section('title', 'Berita & Informasi Terbaru')

@section('content')

{{-- ====================================================================== --}}
{{-- CUSTOM CSS UNTUK TAMPILAN ANTARA NEWS --}}
{{-- ====================================================================== --}}
<style>
    /* Style untuk Main Carousel */
    .hero-carousel-img {
        height: 500px;
        object-fit: cover;
        filter: brightness(0.6); /* Gelapkan gambar agar teks terbaca */
    }
    .carousel-caption {
        background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 2rem;
        text-align: left;
    }
    
    /* Style untuk Right Column Scrollbar */
    .scrollable-posts {
        height: 500px; /* Samakan tinggi dengan gambar utama kiri */
        overflow-y: auto;
        padding-right: 5px;
    }
    /* Mempercantik Scrollbar (Webkit) */
    .scrollable-posts::-webkit-scrollbar {
        width: 6px;
    }
    .scrollable-posts::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .scrollable-posts::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }
    .scrollable-posts::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Style untuk Category Slider Horizontal */
    .category-scroll-wrapper {
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 10px; /* Ruang untuk scrollbar */
    }
    .category-scroll-wrapper::-webkit-scrollbar {
        height: 4px;
    }
    .category-scroll-wrapper::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 2px;
    }
    .category-pill {
        display: inline-block;
        margin-right: 10px;
        padding: 8px 20px;
        border-radius: 50px;
        background: #f8f9fa;
        color: #333;
        border: 1px solid #ddd;
        text-decoration: none;
        transition: all 0.2s;
    }
    .category-pill:hover, .category-pill.active {
        background: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }
</style>

<section id="blog" class="section">
    <div class="container py-4">
        
        {{-- ====================================================================== --}}
        {{-- 1. BAGIAN SLIDER (CAROUSEL) 5 POSTINGAN --}}
        {{-- ====================================================================== --}}
        @if(isset($latestPosts) && $latestPosts->count() > 0)
        <div id="newsCarousel" class="carousel slide mb-5 rounded overflow-hidden shadow-sm" data-bs-ride="carousel">
            <div class="carousel-indicators">
                @foreach($latestPosts->take(5) as $key => $sliderPost)
                    <button type="button" data-bs-target="#newsCarousel" data-bs-slide-to="{{ $key }}" class="{{ $key == 0 ? 'active' : '' }}" aria-current="true"></button>
                @endforeach
            </div>
            
            <div class="carousel-inner">
                @foreach($latestPosts->take(5) as $key => $sliderPost)
                <div class="carousel-item {{ $key == 0 ? 'active' : '' }}">
                    <a href="{{ route('blog.posts.show', $sliderPost->slug) }}">
                        <img src="{{ asset('/storage/' . $sliderPost->featured_image) }}" 
                             class="d-block w-100 hero-carousel-img" 
                             onerror="this.onerror=null;this.src='https://placehold.co/1200x500/1a73e8/ffffff?text=Sancaka+News';"
                             alt="{{ $sliderPost->title }}">
                        <div class="carousel-caption">
                            <span class="badge bg-danger mb-2">{{ $sliderPost->category->name ?? 'Update' }}</span>
                            <h2 class="fw-bold text-white">{{ $sliderPost->title }}</h2>
                            <p class="d-none d-md-block text-white-50">{{ Str::limit(strip_tags($sliderPost->content), 100) }}</p>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
            
            <button class="carousel-control-prev" type="button" data-bs-target="#newsCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#newsCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
        @endif

        {{-- ====================================================================== --}}
        {{-- 2. BAGIAN UTAMA (KIRI: GAMBAR BESAR, KANAN: LIST SCROLL) --}}
        {{-- ====================================================================== --}}
        <div class="row g-4 mb-4">
            <div class="col-12 mb-3">
                <h3 class="fw-bold border-start border-4 border-primary ps-3">Berita Pilihan</h3>
            </div>

            {{-- KOLOM KIRI: HEADLINE BESAR --}}
            <div class="col-lg-8">
                @if($headline)
                <div class="card border-0 shadow-sm h-100 position-relative text-white overflow-hidden">
                    <a href="{{ route('blog.posts.show', $headline->slug) }}">
                        <img src="{{ asset('/storage/' . $headline->featured_image) }}" 
                             class="card-img w-100" 
                             style="height: 500px; object-fit: cover;"
                             onerror="this.onerror=null;this.src='https://placehold.co/800x500/333/fff?text=Headline';"
                             alt="{{ $headline->title }}">
                        <div class="card-img-overlay d-flex flex-column justify-content-end p-4" style="background: linear-gradient(transparent, rgba(0,0,0,0.9));">
                            <span class="badge bg-primary w-auto align-self-start mb-2">{{ $headline->category->name ?? 'Utama' }}</span>
                            <h2 class="card-title fw-bold">{{ $headline->title }}</h2>
                            <p class="card-text">{{ Str::limit(strip_tags($headline->content), 150) }}</p>
                            <small class="text-white-50"><i class="fas fa-clock me-1"></i> {{ $headline->created_at->diffForHumans() }}</small>
                        </div>
                    </a>
                </div>
                @endif
            </div>

            {{-- KOLOM KANAN: LIST POSTINGAN DENGAN SCROLLBAR --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-bold py-3">Terpopuler & Terbaru</div>
                    <div class="card-body p-0 scrollable-posts"> <div class="list-group list-group-flush">
                            @foreach($topArticles as $article)
                            <a href="{{ route('blog.posts.show', $article->slug) }}" class="list-group-item list-group-item-action d-flex gap-3 py-3" aria-current="true">
                                <img src="{{ asset('/storage/' . $article->featured_image) }}" 
                                     alt="twbs" width="80" height="80" class="rounded flex-shrink-0" 
                                     style="object-fit: cover;"
                                     onerror="this.onerror=null;this.src='https://placehold.co/80x80/eee/999?text=Img';">
                                <div class="d-flex flex-column justify-content-center">
                                    <h6 class="mb-1 fw-semibold small">{{ Str::limit($article->title, 60) }}</h6>
                                    <small class="text-muted" style="font-size: 0.75rem;">
                                        <i class="fas fa-calendar-alt me-1"></i> {{ $article->created_at->format('d M') }}
                                    </small>
                                </div>
                            </a>
                            @endforeach
                            
                            {{-- Fallback jika topArticles sedikit, ambil dari latest --}}
                            @if($topArticles->count() < 5)
                                @foreach($latestPosts->take(5) as $extraPost)
                                <a href="{{ route('blog.posts.show', $extraPost->slug) }}" class="list-group-item list-group-item-action d-flex gap-3 py-3">
                                    <img src="{{ asset('/storage/' . $extraPost->featured_image) }}" width="80" height="80" class="rounded flex-shrink-0" style="object-fit: cover;" onerror="this.onerror=null;this.src='https://placehold.co/80x80/eee/999?text=Img';">
                                    <div class="d-flex flex-column justify-content-center">
                                        <h6 class="mb-1 fw-semibold small">{{ Str::limit($extraPost->title, 60) }}</h6>
                                        <small class="text-muted" style="font-size: 0.75rem;">{{ $extraPost->created_at->format('d M') }}</small>
                                    </div>
                                </a>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ====================================================================== --}}
        {{-- 3. SLIDER KATEGORI (DI BAWAH GRID) --}}
        {{-- ====================================================================== --}}
        <div class="mb-5">
            <h5 class="fw-bold mb-3">Jelajahi Kategori</h5>
            <div class="category-scroll-wrapper">
                {{-- Pastikan controller mengirim variable $categories, jika tidak buat manual/dummy --}}
                @if(isset($categories))
                    <a href="{{ route('blog.posts.index') }}" class="category-pill active">Semua</a>
                    @foreach($categories as $cat)
                        <a href="{{ route('blog.posts.index', ['category' => $cat->slug]) }}" class="category-pill">{{ $cat->name }}</a>
                    @endforeach
                @else
                    {{-- Dummy jika variable belum ada --}}
                    <a href="#" class="category-pill active">Semua</a>
                    <a href="#" class="category-pill">Berita</a>
                    <a href="#" class="category-pill">Tutorial</a>
                    <a href="#" class="category-pill">Teknologi</a>
                    <a href="#" class="category-pill">Bisnis</a>
                    <a href="#" class="category-pill">PPOB</a>
                    <a href="#" class="category-pill">Tips & Trik</a>
                @endif
            </div>
        </div>

        {{-- ====================================================================== --}}
        {{-- 4. SISA POSTINGAN (GRID BIASA) --}}
        {{-- ====================================================================== --}}
        <div class="row g-4">
            <div class="col-12"><h4 class="fw-bold">Artikel Lainnya</h4></div>
            @forelse($latestPosts->skip(5) as $post) {{-- Skip 5 karena sudah masuk slider --}}
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <a href="{{ route('blog.posts.show', $post->slug) }}">
                            <img src="{{ asset('/storage/' . $post->featured_image) }}" 
                                 class="card-img-top" height="200" style="object-fit: cover;" 
                                 onerror="this.onerror=null;this.src='https://placehold.co/400x200/eee/999?text=Post';"
                                 alt="{{ $post->title }}">
                        </a>
                        <div class="card-body">
                            <span class="text-primary small fw-bold">{{ $post->category->name ?? 'Umum' }}</span>
                            <h5 class="card-title fw-bold mt-2">
                                <a href="{{ route('blog.posts.show', $post->slug) }}" class="text-decoration-none text-dark">
                                    {{ Str::limit($post->title, 50) }}
                                </a>
                            </h5>
                            <p class="card-text text-muted small">{{ Str::limit(strip_tags($post->content), 100) }}</p>
                        </div>
                        <div class="card-footer bg-white border-0 pt-0">
                            <small class="text-muted">{{ $post->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Belum ada artikel tambahan.</p>
                </div>
            @endforelse
            
            {{-- PAGINATION --}}
{{-- Gunakan justify-center agar posisi di tengah --}}
@if($latestPosts->hasPages())
    <div class="mt-8 flex justify-center">
        {{ $latestPosts->onEachSide(1)->links() }}
    </div>
@endif

    </div>
</section>

@endsection