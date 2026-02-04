{{--
    FILE: resources/views/blog/partials/bottom_grid.blade.php
    DESAIN: 4 COLUMN GRID (TRAVEL, NEWS, SCIENCE, ECONOMY)
--}}

<style>
    /* Styling Header Kategori dengan Garis Hitam Tebal */
    .col-head-style {
        border-top: 2px solid #000;
        padding-top: 15px;
        margin-bottom: 25px;
    }
    .col-head-title {
        font-family: 'Inter', sans-serif;
        font-weight: 800; /* Extra Bold */
        text-transform: uppercase;
        font-size: 13px;
        color: #111;
        margin: 0;
        letter-spacing: 0.5px;
    }

    /* Styling Postingan Utama (Atas) */
    .col-main-post { margin-bottom: 20px; }

    .col-img-wrap {
        /* height: 160px;  <-- DIHAPUS agar tinggi otomatis */
        width: 100%;
        overflow: hidden;
        margin-bottom: 15px;
        background: #f4f4f4;
        border-radius: 3px;
    }

    .col-img {
        width: 100%;
        height: auto; /* <-- DIUBAH jadi auto agar mengikuti rasio asli */
        /* object-fit: cover; <-- DIHAPUS agar tidak di-crop */
        transition: transform 0.4s ease;
        display: block; /* Menghilangkan celah kosong di bawah gambar */
    }
    .col-main-post:hover .col-img { transform: scale(1.03); }

    .col-post-title {
        font-family: 'IBM Plex Serif', serif;
        font-weight: 700;
        font-size: 17px;
        line-height: 1.3;
        margin-bottom: 8px;
        color: #111;
        display: block;
        text-decoration: none;
    }
    .col-post-title:hover { color: #dd0017; }

    .col-meta {
        font-family: 'Inter', sans-serif;
        font-size: 10px;
        color: #999;
        text-transform: uppercase;
        font-weight: 700;
        margin-bottom: 12px;
    }
    .col-meta .cat-label { color: #dd0017; margin-right: 5px; }

    .col-excerpt {
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        color: #666;
        line-height: 1.6;
        display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
    }

    /* Styling List Postingan (Bawah) */
    .col-list-item {
        border-top: 1px solid #eee; /* Garis pemisah tipis */
        padding: 15px 0;
    }
    .col-list-title {
        font-family: 'IBM Plex Serif', serif;
        font-weight: 700;
        font-size: 15px;
        line-height: 1.4;
        margin-bottom: 5px;
        color: #111;
        display: block;
        text-decoration: none;
    }
    .col-list-title:hover { color: #dd0017; }
</style>

<div class="row">
    {{-- Loop 4 Kategori --}}
    @foreach($categories->skip(1)->take(4) as $cat)
    <div class="col-lg-3 col-md-6 mb-5">

        {{-- Header Kategori --}}
        <div class="col-head-style">
            <h5 class="col-head-title">{{ $cat->name }}</h5>
        </div>

        @if($cat->posts->count() > 0)

            {{-- 1. Postingan Pertama (Gambar Besar) --}}
            @php $first = $cat->posts()->latest()->first(); @endphp
            <div class="col-main-post">
                <div class="col-img-wrap">
                    <a href="{{ route('blog.posts.show', $first->slug) }}">
                        <img src="{{ asset('/storage/' . $first->featured_image) }}" class="col-img" alt="{{ $first->title }}" onerror="this.onerror=null;this.src='https://placehold.co/400x250/eee/999?text=Image';">
                    </a>
                </div>
                <a href="{{ route('blog.posts.show', $first->slug) }}" class="col-post-title">
                    {{ Str::limit($first->title, 50) }}
                </a>
                <div class="col-meta">
                    <span class="cat-label">{{ $cat->name }}</span>
                    <span>&mdash; {{ $first->created_at->format('M d, Y') }}</span>
                </div>
                <p class="col-excerpt">{{ Str::limit(strip_tags($first->content), 90) }}</p>
            </div>

            {{-- 2. List Postingan Bawahnya (Tanpa Gambar) --}}
            @foreach($cat->posts()->latest()->skip(1)->take(3)->get() as $listPost)
            <div class="col-list-item">
                <a href="{{ route('blog.posts.show', $listPost->slug) }}" class="col-list-title">
                    {{ Str::limit($listPost->title, 55) }}
                </a>
                <div class="col-meta mb-0" style="color: #bbb; font-weight: 500;">
                    {{ $listPost->created_at->format('M d, Y') }}
                </div>
            </div>
            @endforeach

        @else
            <p class="text-muted small">Belum ada artikel di kategori ini.</p>
        @endif

    </div>
    @endforeach
</div>
