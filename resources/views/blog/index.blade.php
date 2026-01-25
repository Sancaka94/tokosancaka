@extends('layouts.blog')

@section('title', 'Berita & Informasi Terbaru')

@section('content')

{{-- LOAD GOOGLE FONTS --}}
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Serif:wght@400;600;700&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

@include('blog.partials.ticker')

<div class="container py-4">

    {{-- 1. SEARCH SECTION (Pindahkan ke sini agar rapi) --}}
    <div class="search-section">
        {{-- Ganti baris form menjadi seperti ini --}}
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



    {{-- 2. LOGIKA TAMPILAN (Mencegah Syntax Error) --}}
    @if(isset($latestPosts) && $latestPosts->count() > 0)

        <div class="hero-wrap">

                    @include('blog.partials.hero_section')

        </div>

        @include('blog.category')

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

@endsection
