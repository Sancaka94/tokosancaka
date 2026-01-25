@extends('layouts.blog')

@section('title', 'Berita & Informasi Terbaru')

@section('content')

{{-- LOAD GOOGLE FONTS --}}
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Serif:wght@400;600;700&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

@include('blog.partials.ticker')

<div class="container py-4">

    {{-- 1. SEARCH SECTION (Pindahkan ke sini agar rapi) --}}
    <div class="search-section">
        <form action="{{ route('blog.index') }}" method="GET">
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
                <a href="{{ route('blog.index') }}" class="text-danger ms-2 text-decoration-none">
                    <i class="fas fa-times-circle"></i> Bersihkan
                </a>
            </div>
        @endif
    </div>

    {{-- 2. LOGIKA TAMPILAN (Mencegah Syntax Error) --}}
    @if(isset($latestPosts) && $latestPosts->count() > 0)

        <div class="hero-wrap">
            <div class="row">
                {{-- KIRI: HEADLINE --}}
                <div class="col-lg-7">
                    @php $headline = $latestPosts->first(); @endphp
                    <div class="hero-main">
                        <a href="{{ route('blog.posts.show', $headline->slug) }}">
                            <img src="{{ asset('/storage/' . $headline->featured_image) }}" alt="{{ $headline->title }}" onerror="this.onerror=null;this.src='https://placehold.co/800x800/000/fff?text=Headline';">
                            <div class="hero-overlay">
                                <span class="hero-cat">{{ $headline->category->name ?? 'News' }}</span>
                                <h2 class="hero-title">{{ Str::limit($headline->title, 70) }}</h2>
                                <div class="hero-meta">
                                    <i class="far fa-clock me-1"></i> {{ $headline->created_at->format('d M Y') }}
                                    <span class="mx-2">•</span>
                                    By {{ $headline->author->nama_lengkap ?? 'Admin' }}
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                {{-- KANAN: LIST SCROLLABLE --}}
                <div class="col-lg-5">
                    <div class="hero-right-scroll">
                        <div class="d-flex flex-column">
                            @foreach($latestPosts->skip(1) as $post)
                                <div class="hero-list-item">
                                    <div class="hero-list-img">
                                        <a href="{{ route('blog.posts.show', $post->slug) }}">
                                            <img src="{{ asset('/storage/' . $post->featured_image) }}" alt="{{ $post->title }}" onerror="this.onerror=null;this.src='https://placehold.co/200x200/000/fff?text=Img';">
                                        </a>
                                    </div>
                                    <div>
                                        <h4 class="hero-list-title">
                                            <a href="{{ route('blog.posts.show', $post->slug) }}">{{ Str::limit($post->title, 60) }}</a>
                                        </h4>
                                        <div class="text-muted small" style="font-size: 10px; text-transform: uppercase;">
                                            <span class="text-danger fw-bold">{{ $post->category->name ?? 'Info' }}</span> • {{ $post->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. LOOP CATEGORIES --}}
        @foreach($categories as $category)
            @if($category->posts_count > 0)
                @include('blog.partials.categories_smartmag', ['category' => $category])
                @if(!$loop->last)
                    <div style="border-top: 1px dashed #ccc; margin: 50px 0;"></div>
                @endif
            @endif
        @endforeach

    @else
        {{-- TAMPILAN JIKA KOSONG (SEARCH TIDAK KETEMU) --}}
        <div class="col-12 text-center py-5">
            <div class="mb-3">
                <i class="fas fa-search-minus fa-3x text-muted"></i>
            </div>
            <h5>Maaf, artikel tidak ditemukan</h5>
            <p class="text-muted">Coba gunakan kata kunci lain atau periksa ejaan Anda.</p>
            <a href="{{ route('blog.index') }}" class="btn btn-outline-danger">Lihat Semua Artikel</a>
        </div>
    @endif

    {{-- 4. PAGINATION --}}
    @if(method_exists($latestPosts, 'links'))
        <div class="d-flex justify-content-center mt-5">
            {{ $latestPosts->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    @endif

    @include('blog.partials.bottom_grid')

</div>

@endsection
